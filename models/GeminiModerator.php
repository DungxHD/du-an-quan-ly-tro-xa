<?php
/**
 * Adapter gọi Gemini API để chấm điểm độc hại cho bình luận.
 * Lớp này chỉ chịu trách nhiệm giao tiếp API, không tự quyết định ẩn/hiện comment.
 */
class GeminiModerator {
    /**
     * Phân tích một bình luận và trả về toxicity_score trong khoảng 0-1.
     */
    public static function analyzeComment($content, $apiKey) {
        $content = trim((string)$content);
        $apiKey = trim((string)$apiKey);

        if ($content === '') {
            return [
                'success' => true,
                'toxicity_score' => 0.0,
                'message' => '',
            ];
        }

        if ($apiKey === '') {
            return [
                'success' => false,
                'toxicity_score' => 0.0,
                'message' => 'Thiếu Gemini API key.',
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'toxicity_score' => 0.0,
                'message' => 'Máy chủ chưa bật cURL nên không thể gọi Gemini.',
            ];
        }

        $payload = json_encode([
            'contents' => [[
                'parts' => [[
                    'text' => 'Phân tích bình luận sau có phản cảm không? Trả về JSON {"toxicity_score": 0-1}. Bình luận: ' . $content,
                ]],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 8,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'toxicity_score' => 0.0,
                'message' => $curlError !== '' ? $curlError : 'Không nhận được phản hồi từ Gemini.',
            ];
        }

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'toxicity_score' => 0.0,
                'message' => 'Gemini trả về HTTP ' . $httpCode . '.',
            ];
        }

        $decoded = json_decode($response, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $toxicityScore = self::extractToxicityScore($text);

        if ($toxicityScore === null) {
            return [
                'success' => false,
                'toxicity_score' => 0.0,
                'message' => 'Gemini trả dữ liệu không đúng định dạng mong đợi.',
            ];
        }

        return [
            'success' => true,
            'toxicity_score' => $toxicityScore,
            'message' => '',
        ];
    }

    /**
     * Tách `toxicity_score` từ JSON hoặc text fallback do Gemini trả về.
     */
    private static function extractToxicityScore($text) {
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        $json = json_decode($text, true);
        if (is_array($json) && isset($json['toxicity_score']) && is_numeric($json['toxicity_score'])) {
            return max(0, min(1, (float)$json['toxicity_score']));
        }

        if (preg_match('/"toxicity_score"\s*:\s*([0-9]*\.?[0-9]+)/i', $text, $matches)) {
            return max(0, min(1, (float)$matches[1]));
        }

        return null;
    }
}
