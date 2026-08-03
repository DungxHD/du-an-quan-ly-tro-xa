<?php
/**
 * Lớp mã hóa dữ liệu nhạy cảm theo chuẩn AES-256-CBC.
 * DB chỉ lưu chuỗi có prefix `ENC:` để code dễ nhận diện và tránh mã hóa lặp.
 */
class Encryption {
    private const CIPHER = 'AES-256-CBC';
    private const PREFIX = 'ENC:';
    private const ENV_KEY = 'NHATROA_ENCRYPTION_KEY';
    private const FALLBACK_KEY = 'nhatroa-contract-key-change-me';

    /**
     * Lấy khóa bí mật dùng chung cho toàn hệ thống.
     * Ưu tiên biến môi trường để dễ thay ở production mà không sửa code.
     */
    private static function getSecretKey() {
        $configuredKey = getenv(self::ENV_KEY);
        if ($configuredKey === false || trim((string)$configuredKey) === '') {
            $configuredKey = self::FALLBACK_KEY;
        }

        return hash('sha256', (string)$configuredKey, true);
    }

    /**
     * Mã hóa một giá trị đơn lẻ.
     * Giá trị rỗng được chuẩn hóa về NULL để DB sạch hơn.
     */
    public static function encrypt($value) {
        if ($value === null) {
            return null;
        }

        $plainText = trim((string)$value);
        if ($plainText === '') {
            return null;
        }

        if (str_starts_with($plainText, self::PREFIX)) {
            return $plainText;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            self::getSecretKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipherText === false) {
            throw new RuntimeException('Không thể mã hóa dữ liệu nhạy cảm.');
        }

        return self::PREFIX . base64_encode($iv . $cipherText);
    }

    /**
     * Giải mã một giá trị đã được mã hóa.
     * Nếu dữ liệu cũ đang lưu plain text thì trả nguyên trạng để không làm vỡ demo/fallback.
     */
    public static function decrypt($value) {
        if ($value === null) {
            return null;
        }

        $cipherValue = trim((string)$value);
        if ($cipherValue === '' || !str_starts_with($cipherValue, self::PREFIX)) {
            return $cipherValue;
        }

        $payload = base64_decode(substr($cipherValue, strlen(self::PREFIX)), true);
        if ($payload === false) {
            return $cipherValue;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($payload) <= $ivLength) {
            return $cipherValue;
        }

        $iv = substr($payload, 0, $ivLength);
        $cipherText = substr($payload, $ivLength);
        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            self::getSecretKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $plainText === false ? $cipherValue : $plainText;
    }

    /**
     * Mã hóa hàng loạt các field nhạy cảm trước khi lưu DB.
     */
    public static function encryptFields(array $data, array $fields) {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Giải mã hàng loạt field để controller/view chỉ làm việc với dữ liệu sạch.
     */
    public static function decryptFields(array $data, array $fields) {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::decrypt($data[$field]);
            }
        }

        return $data;
    }
}
