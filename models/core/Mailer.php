<?php
class Mailer {
    private static ?array $config = null;

    private static function loadConfig() {
        if (self::$config !== null) {
            return self::$config;
        }

        self::$config = [
            'host' => RoomModel::getSetting('smtp_host', ''),
            'port' => (int)RoomModel::getSetting('smtp_port', 587),
            'username' => RoomModel::getSetting('smtp_username', ''),
            'password' => RoomModel::getSetting('smtp_password', ''),
            'from_email' => RoomModel::getSetting('smtp_from_email', ''),
            'from_name' => RoomModel::getSetting('smtp_from_name', ''),
            'encryption' => RoomModel::getSetting('smtp_encryption', 'tls'),
        ];

        return self::$config;
    }

    public static function isConfigured() {
        $config = self::loadConfig();

        if (empty($config['host']) || $config['host'] === 'smtp.example.com') {
            return false;
        }
        if (empty($config['username']) || $config['username'] === 'your_email@example.com') {
            return false;
        }
        if (empty($config['password']) || $config['password'] === 'your_smtp_password') {
            return false;
        }
        if (empty($config['from_email'])) {
            return false;
        }

        return true;
    }

    /**
     * Gửi email qua SMTP.
     * Trả về true nếu thành công, false nếu thất bại.
     * Không throw exception, chỉ log l��i và trả về false.
     */
    public static function send($toEmail, $subject, $htmlBody, $textBody = '') {
        if (!self::isConfigured()) {
            error_log('[Mailer] SMTP not configured');
            return false;
        }

        $config = self::loadConfig();

        try {
            $headers = [];
            $headers[] = 'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>';
            $headers[] = 'Reply-To: ' . $config['from_email'];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            $headerString = implode("\r\n", $headers);

            $result = @mail($toEmail, $subject, $htmlBody, $headerString);

            if (!$result) {
                error_log('[Mailer] mail() returned false');
                return false;
            }

            return true;
        } catch (Throwable $e) {
            error_log('[Mailer] Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi email OTP đặt lại mật khẩu.
     */
    public static function sendOtpEmail($toEmail, $otp, $userName) {
        $subject = 'Mã OTP đặt lại mật khẩu - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $ttlMinutes = (int)RoomModel::getSetting('otp_ttl_minutes', 2);

        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0e7a64; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
                .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0e7a64; text-align: center; padding: 20px; background: white; border-radius: 8px; margin: 20px 0; font-family: monospace; }
                .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px; }
                .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 8px; margin: 20px 0; color: #92400e; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . e(RoomModel::getSetting('site_name', 'NhaTroA')) . '</h1>
                </div>
                <div class="content">
                    <p>Xin chào ' . e($userName) . ',</p>
                    <p>Bạn đã yêu cầu đặt lại mật khẩu. Mã OTP của bạn là:</p>
                    <div class="otp-code">' . e($otp) . '</div>
                    <p>Mã này có hiệu lực trong <strong>' . $ttlMinutes . ' phút</strong>. Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
                    <div class="warning">
                        <strong>Lưu ý:</strong> Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này hoặc liên hệ chủ trọ.
                    </div>
                </div>
                <div class="footer">
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                    <p>&copy; ' . date('Y') . ' ' . e(RoomModel::getSetting('site_name', 'NhaTroA')) . '</p>
                </div>
            </div>
        </body>
        </html>';

        return self::send($toEmail, $subject, $htmlBody);
    }
}