<?php
/**
 * ResendEmailSender - Gửi email qua Resend API.
 * Chỉ dùng cho OTP quên mật khẩu (theo yêu cầu).
 */
class ResendEmailSender {
    private const API_URL = 'https://api.resend.com/emails';
    private const FROM_EMAIL = 'no-reply@lvdung.id.vn';
    private const FROM_NAME = 'Hệ Thống Quản Lý Nhà Trọ';

    /**
     * Lấy API key từ settings.
     */
    public static function getApiKey() {
        return trim((string)SettingModel::get('resend_api_key', ''));
    }

    /**
     * Gửi email qua Resend API.
     * Trả về: true (thành công), false (thất bại).
     */
    public static function send($toEmail, $subject, $htmlBody) {
        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            error_log('[ResendEmailSender] API key not configured');
            return false;
        }

        $payload = json_encode([
            'from' => self::FROM_NAME . ' <' . self::FROM_EMAIL . '>',
            'to' => [$toEmail],
            'subject' => $subject,
            'html' => $htmlBody,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 500) {
            error_log('[ResendEmailSender] HTTP ' . $httpCode . ' curl_error=' . $curlError . ' body=' . substr((string)$response, 0, 200));
            return false;
        }

        if ($httpCode === 401 || $httpCode === 403) {
            error_log('[ResendEmailSender] Unauthorized/Forbidden: check API key. body=' . substr((string)$response, 0, 300));
            return false;
        }

        if ($httpCode === 422) {
            error_log('[ResendEmailSender] Validation error (check from email/domain). body=' . substr((string)$response, 0, 300));
            return false;
        }

        if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 202) {
            error_log('[ResendEmailSender] HTTP ' . $httpCode . ' body=' . substr((string)$response, 0, 300));
            return false;
        }

        return true;
    }
}