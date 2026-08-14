<?php
class PasswordResetModel {
    /**
     * Tạo OTP mới và lưu vào database.
     * Trả về mã OTP plaintext (chỉ dùng để gửi email, KH��NG lưu).
     */
    public static function createOtp($userId, $ip) {
        $otpLength = (int)RoomModel::getSetting('otp_length', 4);
        $otp = str_pad((string)random_int(0, (int)pow(10, $otpLength) - 1), $otpLength, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        $ttlMinutes = (int)RoomModel::getSetting('otp_ttl_minutes', 2);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlMinutes * 60);

        Database::insert('password_reset_otps', [
            'user_id' => (int)$userId,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'used_at' => null,
            'ip' => $ip,
        ]);

        return $otp;
    }

    /**
     * Lấy OTP mới nhất chưa dùng, chưa hết hạn của user.
     * Sử dụng UTC_TIMESTAMP() để so sánh chính xác với expires_at được lưu ở UTC.
     */
    public static function getLatestValidOtp($userId) {
        if (Database::hasConnection()) {
            return Database::fetchOne(
                "SELECT * FROM password_reset_otps
                 WHERE user_id = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()
                 ORDER BY created_at DESC LIMIT 1",
                [(int)$userId]
            );
        }

        $rows = array_filter(Database::getTable('password_reset_otps'), function ($row) use ($userId) {
            return (int)$row['user_id'] === (int)$userId
                && empty($row['used_at'])
                && strtotime($row['expires_at']) > time();
        });
        usort($rows, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $rows[0] ?? null;
    }

    /**
     * Xác thực OTP.
     * Trả về: true (thành công), 'expired' (hết hạn), 'invalid' (sai), 'max_attempts' (quá số lần thử).
     */
    public static function verifyOtp($userId, $otpInput) {
        $otpRecord = self::getLatestValidOtp($userId);
        if (!$otpRecord) {
            return 'expired';
        }

        $maxAttempts = (int)RoomModel::getSetting('otp_max_verify_attempts', 5);
        if ((int)$otpRecord['attempts'] >= $maxAttempts) {
            return 'max_attempts';
        }

        if (!password_verify($otpInput, $otpRecord['otp_hash'])) {
            Database::update('password_reset_otps', ['attempts' => (int)$otpRecord['attempts'] + 1], 'id = :id', ['id' => (int)$otpRecord['id']]);
            return 'invalid';
        }

        Database::update('password_reset_otps', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$otpRecord['id']]);
        return true;
    }

    /**
     * Kiểm tra rate limit gửi OTP.
     * Trả về: true (được gửi), 'resend_wait' (chờ resend), 'max_daily' (quá 5 lần/24h).
     */
    public static function checkSendRateLimit($userId, $ip) {
        $resendSeconds = (int)RoomModel::getSetting('otp_resend_seconds', 60);
        $maxPerDay = (int)RoomModel::getSetting('otp_max_send_per_24h', 5);

        if (Database::hasConnection()) {
            $lastSend = Database::fetchOne(
                "SELECT sent_at FROM password_reset_send_attempts
                 WHERE user_id = ? AND ip = ?
                 ORDER BY sent_at DESC LIMIT 1",
                [(int)$userId, $ip]
            );
            if ($lastSend) {
                $secondsSinceLast = time() - strtotime($lastSend['sent_at']);
                if ($secondsSinceLast < $resendSeconds) {
                    return ['allowed' => false, 'reason' => 'resend_wait', 'wait_seconds' => $resendSeconds - $secondsSinceLast];
                }
            }

            $count24h = Database::fetchOne(
                "SELECT COUNT(*) as cnt FROM password_reset_send_attempts
                 WHERE user_id = ? AND ip = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                [(int)$userId, $ip]
            );
            if ((int)$count24h['cnt'] >= $maxPerDay) {
                return ['allowed' => false, 'reason' => 'max_daily'];
            }

            return ['allowed' => true];
        }

        $attempts = array_filter(Database::getTable('password_reset_send_attempts'), function ($row) use ($userId, $ip) {
            return (int)$row['user_id'] === (int)$userId && $row['ip'] === $ip;
        });
        usort($attempts, fn($a, $b) => strcmp($b['sent_at'] ?? '', $a['sent_at'] ?? ''));
        if (!empty($attempts)) {
            $secondsSinceLast = time() - strtotime($attempts[0]['sent_at']);
            if ($secondsSinceLast < $resendSeconds) {
                return ['allowed' => false, 'reason' => 'resend_wait', 'wait_seconds' => $resendSeconds - $secondsSinceLast];
            }
        }

        $count24h = count(array_filter($attempts, function ($row) {
            return strtotime($row['sent_at']) >= time() - 86400;
        }));
        if ($count24h >= $maxPerDay) {
            return ['allowed' => false, 'reason' => 'max_daily'];
        }

        return ['allowed' => true];
    }

    /**
     * Ghi nhận lần gửi OTP.
     */
    public static function recordSendAttempt($userId, $ip) {
        Database::insert('password_reset_send_attempts', [
            'user_id' => (int)$userId,
            'ip' => $ip,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cập nhật mật khẩu user.
     */
    public static function updatePassword($userId, $newPassword) {
        Database::update('users', ['password' => password_hash($newPassword, PASSWORD_DEFAULT)], 'id = :id', ['id' => (int)$userId]);
    }

    /**
     * Tìm user theo identifier (email hoặc phone đã chuẩn hóa).
     */
    public static function findByIdentifier($identifier) {
        if (str_contains($identifier, '@')) {
            $email = mb_strtolower(trim($identifier));
            return UserModel::findByEmail($email);
        }

        $phone = UserModel::normalizePhone($identifier);
        if (!$phone) {
            return null;
        }

        if (Database::hasConnection()) {
            $user = Database::fetchOne("SELECT * FROM users WHERE phone = ?", [$phone]);
            return $user ? UserModel::hydrateUser($user) : null;
        }

        foreach (Database::getTable('users') as $user) {
            if (($user['phone'] ?? '') === $phone) {
                return UserModel::hydrateUser($user);
            }
        }
        return null;
    }

    /**
     * Kiểm tra phone đã tồn tại (dùng phone đã chuẩn hóa).
     */
    public static function phoneExists($phone) {
        $normalized = UserModel::normalizePhone($phone);
        if (!$normalized) {
            return false;
        }

        if (Database::hasConnection()) {
            return (bool)Database::fetchOne("SELECT id FROM users WHERE phone = ?", [$normalized]);
        }

        foreach (Database::getTable('users') as $user) {
            if (($user['phone'] ?? '') === $normalized) {
                return true;
            }
        }
        return false;
    }
}