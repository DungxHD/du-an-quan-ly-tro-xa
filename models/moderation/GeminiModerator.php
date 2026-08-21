<?php
/**
 * GeminiModerator lọc và mã hóa từ ngữ không chuẩn mực trong nội dung đánh giá:
 * - Ưu tiên dùng Gemini AI để nhận diện và thay từ không chuẩn mực bằng ***.
 * - Nếu API lỗi/không kết nối được, fallback sang danh sách từ cấm trong bảng banned_words.
 */
class GeminiModerator {
    private const DEFAULT_API_KEY = 'AQ.Ab8RN6LMQF5wDAHkyWRf4PC6bn7spcxx1kxK2xfInVLYo6Zggg';
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';
    private const MODELS = ['gemini-3.6-flash', 'gemini-2.0-flash'];

    private const PROMPT = <<<'TXT'
Bạn là bộ lọc nội dung tiếng Việt cho hệ thống đánh giá phòng trọ. Hãy phân tích văn bản sau một cách NGHIÊM NGẶT.

Các loại nội dung BẮT BUỘC TỪ CHỐI (không được phép):
1. Từ tục tĩu, thô tục, chửi thề trực tiếp: địt, đú, lồn, cặc, buồi, đĩ, điếm, mẹ mày, bố mày, cờ hó, chó má, óc chó, ngu dốt, chết tiệt, vãi lồn, đéo
2. Cụm từ ẩn dụ, bẩy tono, chữa tiếng: mất dậy, có cái cứt, cặc cùi, lồn đen, đéo gì, đm, cc, vl, clgt, dmcs, địt mẹ, đéo ai, buồi con, chó chết, cặc lón, lồn cُونَ, mẹ kiếp
3. Ngôn từ xúc phạm, khiếm nhã, phân biệt đối xử, đe dọa, khiêu khích
4. Spam, quảng cáo, liên kết, số điện thoại, email sai mục đích
5. Nội dung vô nghĩa, test lặp ký tự, flood

YÊU CẦU TRẢ VỀ (CHỈ 1 DÒNG, KHÔNG GIẢI THÍCH):
- Nếu KHÔNG vi phạm: OK|<văn bản gốc>
- Nếu CÓ vi phạm: VIOLATION|<lý do chi tiết>|<độ nghiêm trọng: high/medium/low>

Văn bản:
TXT;

    /**
     * Lấy API key từ settings, fallback về key mặc định của dự án.
     */
    public static function getApiKey() {
        $key = trim((string)SettingModel::get('gemini_api_key', ''));
        return $key !== '' ? $key : self::DEFAULT_API_KEY;
    }

    /**
     * Lọc nội dung: thay từ không chuẩn mực bằng ***.
     * Trả về ['content' => nội dung sau khi lọc, 'had_bad_words' => bool, 'source' => 'gemini'|'local'|'none'].
     */
    public static function sanitize($content) {
        $text = trim((string)$content);
        if ($text === '') {
            return ['content' => $text, 'had_bad_words' => false, 'source' => 'none'];
        }

        // Ưu tiên local fallback (nhanh, không giới hạn quota) - AI chỉ dùng khi config bật
        $localResult = self::maskViaLocalList($text);
        if ($localResult['had_bad_words']) {
            return $localResult;
        }

        // Nếu local không phát hiện từ cấm, thử AI (nếu có quota)
        $geminiResult = self::maskViaGemini($text);
        if ($geminiResult !== null) {
            return $geminiResult;
        }

        return $localResult;
    }

    /**
     * Kiểm tra nội dung chi tiết bằng AI (prompt nghiêm ngặt).
     * Trả về: ['is_clean' => bool, 'reason' => string, 'severity' => 'high|medium|low', 'source' => 'gemini'|'local']
     */
    public static function checkContent($content) {
        $text = trim((string)$content);
        if ($text === '') {
            return ['is_clean' => true, 'reason' => '', 'severity' => 'none', 'source' => 'none'];
        }

        // Thử AI trước (prompt nghiêm ngặt)
        $geminiResult = self::checkViaGemini($text);
        if ($geminiResult !== null) {
            return $geminiResult;
        }

        // Fallback local: kiểm tra từ cấm
        return self::checkViaLocalList($text);
    }

    /**
     * Gọi Gemini AI với prompt nghiêm ngặt để kiểm tra.
     * Trả về array hoặc null nếu thất bại.
     */
    private static function checkViaGemini($text) {
        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            return null;
        }

        $payload = json_encode([
            'contents' => [['parts' => [['text' => self::PROMPT . "\n\"" . $text . "\""]]]],
            'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 512],
        ], JSON_UNESCAPED_UNICODE);

        foreach (self::MODELS as $model) {
            $result = self::callGemini($model, $apiKey, $payload);
            if ($result === false) {
                continue;
            }
            if (is_array($result)) {
                return self::parseAiCheckResponse($result['text'], $text);
            }
        }

        return null;
    }

    /**
     * Parse response từ AI check.
     */
    private static function parseAiCheckResponse($response, $original) {
        $response = trim((string)$response);
        $response = trim($response, "\"'");

        // Format: "OK|<text>" hoặc "VIOLATION|<reason>|<severity>"
        if (str_starts_with($response, 'OK|')) {
            return [
                'is_clean' => true,
                'reason' => '',
                'severity' => 'none',
                'source' => 'gemini',
            ];
        }

        if (str_starts_with($response, 'VIOLATION|')) {
            $parts = explode('|', $response, 3);
            $reason = $parts[1] ?? 'Nội dung không phù hợp';
            $severity = $parts[2] ?? 'medium';
            return [
                'is_clean' => false,
                'reason' => $reason,
                'severity' => in_array($severity, ['high', 'medium', 'low']) ? $severity : 'medium',
                'source' => 'gemini',
            ];
        }

        // Fallback nếu AI trả về format lạ
        return [
            'is_clean' => true,
            'reason' => '',
            'severity' => 'none',
            'source' => 'gemini',
        ];
    }

    /**
     * Fallback local: kiểm tra từ cấm.
     */
    private static function checkViaLocalList($text) {
        $words = self::loadBannedWords();
        $found = [];

        foreach ($words as $word) {
            $word = trim((string)$word);
            if ($word === '') continue;

            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            if (preg_match($pattern, $text)) {
                $found[] = $word;
            }
        }

        if (!empty($found)) {
            return [
                'is_clean' => false,
                'reason' => 'Chứa từ cấm: ' . implode(', ', $found),
                'severity' => 'high',
                'source' => 'local',
            ];
        }

        return [
            'is_clean' => true,
            'reason' => '',
            'severity' => 'none',
            'source' => 'local',
        ];
    }

    /**
     * Gọi Gemini AI để mã hóa từ không chuẩn mực. Trả null nếu gọi thất bại.
     */
    private static function maskViaGemini($text) {
        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            return null;
        }

        $payload = json_encode([
            'contents' => [['parts' => [['text' => self::PROMPT . "\n\"" . $text . "\""]]]],
            'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 2048],
        ], JSON_UNESCAPED_UNICODE);

        foreach (self::MODELS as $model) {
            $result = self::callGemini($model, $apiKey, $payload);
            if ($result === false) {
                continue;
            }
            if (is_array($result)) {
                $masked = self::normalizeAiOutput($result['text'], $text);
                return [
                    'content' => $masked,
                    'had_bad_words' => $masked !== $text,
                    'source' => 'gemini',
                ];
            }
        }

        return null;
    }

    /**
     * Gọi generateContent cho một model. Trả về:
     * - array ['text' => ...]: thành công
     * - false: model không khả dụng hoặc lỗi mạng -> thử model khác / fallback
     * - string: lỗi nghiêm trọng (log, không dùng kết quả)
     */
    private static function callGemini($model, $apiKey, $payload) {
        $url = sprintf(self::API_URL, $model, urlencode($apiKey));
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 500) {
            error_log('GeminiModerator: HTTP ' . $httpCode . ' curl_error=' . $curlError . ' body=' . substr((string)$response, 0, 200));
            return false;
        }
        if ($httpCode === 404 || $httpCode === 400) {
            error_log('GeminiModerator: HTTP ' . $httpCode . ' body=' . substr((string)$response, 0, 300));
            return false;
        }
        if ($httpCode === 429) {
            error_log('GeminiModerator: Quota exceeded (429). body=' . substr((string)$response, 0, 300));
            return false;
        }
        if ($httpCode !== 200) {
            error_log('GeminiModerator: HTTP ' . $httpCode . ' body=' . substr((string)$response, 0, 300));
            return false;
        }

        $decoded = json_decode($response, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text)) {
            $blockReason = $decoded['promptFeedback']['blockReason'] ?? ($decoded['candidates'][0]['finishReason'] ?? 'unknown');
            error_log('GeminiModerator: no text in response. blockReason=' . $blockReason . ' body=' . substr((string)$response, 0, 300));
            return false;
        }

        return ['text' => $text];
    }

    /**
     * Chuẩn hóa output của AI: bỏ dấu ngoặc kép thừa, trim.
     */
    private static function normalizeAiOutput($output, $original) {
        $masked = trim((string)$output);
        $masked = trim($masked, "\"'");
        if ($masked === '""' || $masked === "''") {
            $masked = $original;
        }

        return $masked !== '' ? $masked : $original;
    }

    /**
     * Fallback cục bộ: mã hóa từ cấm (từ bảng banned_words + danh sách tĩnh) thành ***.
     */
    private static function maskViaLocalList($text) {
        $words = self::loadBannedWords();
        $masked = $text;
        $hit = false;

        foreach ($words as $word) {
            $word = trim((string)$word);
            if ($word === '') {
                continue;
            }

            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $count = 0;
            $masked = preg_replace($pattern, '***', $masked, -1, $count);
            if ($count > 0) {
                $hit = true;
            }
        }

        return [
            'content' => $masked,
            'had_bad_words' => $hit,
            'source' => 'local',
        ];
    }

    /**
     * Nạp từ cấm: ưu tiên bảng banned_words nếu còn tồn tại, kèm danh sách tĩnh bổ sung.
     */
    private static function loadBannedWords() {
        $words = [];

        if (Database::hasConnection()) {
            try {
                $rows = Database::fetchAll("SELECT word FROM banned_words WHERE word <> ''");
                foreach ($rows as $row) {
                    $words[] = (string)($row['word'] ?? '');
                }
            } catch (Throwable $e) {
                // Bảng có thể đã bị xóa ở bản cài mới -> dùng danh sách tĩnh.
            }
        }

        $words = array_merge($words, [
            'địt', 'đụ', 'lồn', 'cặc', 'buồi', 'đĩ', 'điếm', 'mẹ mày', 'bố mày',
            'cờ hó', 'chó má', 'óc chó', 'ngu dốt', 'chết tiệt', 'vãi lồn', 'đéo',
            // Cụm từ ẩn dụ/phóng tục phổ biến
            'mất dậy', 'có cái cứt', 'cặc cùi', 'lồn đen', 'đéo gì', 'đm', 'cc',
            'vl', 'clgt', 'dmcs', 'địt mẹ', 'đéo ai', 'buồi con', 'chó chết',
        ]);

        return array_values(array_unique(array_filter(array_map('trim', $words))));
    }
}