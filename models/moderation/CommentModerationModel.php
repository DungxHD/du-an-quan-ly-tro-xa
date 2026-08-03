<?php
/**
 * Quản lý pipeline kiểm duyệt comment:
 * - lấy cấu hình moderation,
 * - lọc từ cấm offline,
 * - gọi Gemini khi bật,
 * - theo dõi số lần spam và khóa tạm user.
 */
class CommentModerationModel {
    /**
     * Lấy toàn bộ setting moderation đã ép kiểu sẵn.
     */
    public static function getSettings() {
        return [
            'enable_comment_moderation' => (int)SettingModel::get('enable_comment_moderation', '1') === 1,
            'min_days_to_review' => max(0, (int)SettingModel::get('min_days_to_review', '15')),
            'comment_edit_hours' => max(1, (int)SettingModel::get('comment_edit_hours', '24')),
            'max_comment_attempts' => max(1, (int)SettingModel::get('max_comment_attempts', '3')),
            'comment_lock_hours' => max(1, (int)SettingModel::get('comment_lock_hours', '24')),
            'enable_gemini_moderation' => (int)SettingModel::get('enable_gemini_moderation', '0') === 1,
            'gemini_api_key' => trim((string)SettingModel::get('gemini_api_key', '')),
            'toxicity_threshold' => max(0, min(1, (float)SettingModel::get('toxicity_threshold', '0.70'))),
        ];
    }

    /**
     * Trả trạng thái khóa hiện tại của user, gồm cả thông điệp đếm ngược thân thiện cho UX.
     */
    public static function getLockState($userId, array $settings = []) {
        $userId = (int)$userId;
        $settings = !empty($settings) ? $settings : self::getSettings();
        $moderation = self::getModerationRow($userId);
        $lockedUntil = trim((string)($moderation['locked_until'] ?? ''));

        if ($lockedUntil !== '' && strtotime($lockedUntil) !== false && strtotime($lockedUntil) > time()) {
            return [
                'locked' => true,
                'locked_until' => $lockedUntil,
                'remaining_seconds' => max(0, strtotime($lockedUntil) - time()),
                'message' => 'Bạn bị khóa đánh giá 24h. Có thể thử lại sau '
                    . self::formatRemainingTime(strtotime($lockedUntil) - time())
                    . ' (đến ' . date('d/m/Y H:i', strtotime($lockedUntil)) . ').',
            ];
        }

        if ($moderation && $lockedUntil !== '' && strtotime($lockedUntil) !== false && strtotime($lockedUntil) <= time()) {
            self::clearSpamAttempts($userId);
        }

        return [
            'locked' => false,
            'locked_until' => null,
            'remaining_seconds' => 0,
            'message' => '',
        ];
    }

    /**
     * Chạy toàn bộ pipeline kiểm duyệt và trả về payload sẵn để lưu vào bảng `comments`.
     */
    public static function moderate($content, array $settings = []) {
        $settings = !empty($settings) ? $settings : self::getSettings();
        $content = trim((string)$content);

        if ($content === '') {
            return [
                'content' => null,
                'flagged_words' => [],
                'flagged_words_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                'toxicity_score' => 0.0,
                'is_spam' => 0,
                'status' => 1,
                'hidden_by_ai' => false,
                'notice' => '',
            ];
        }

        if (!$settings['enable_comment_moderation']) {
            return [
                'content' => $content,
                'flagged_words' => [],
                'flagged_words_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                'toxicity_score' => 0.0,
                'is_spam' => 0,
                'status' => 1,
                'hidden_by_ai' => false,
                'notice' => '',
            ];
        }

        $offline = BannedWordModel::sanitizeContent($content);
        $sanitizedContent = $offline['content'];
        $flaggedWords = $offline['flagged_words'];
        $isSpam = self::isMaskedOnlyContent($sanitizedContent) ? 1 : 0;
        $toxicityScore = 0.0;
        $status = 1;
        $hiddenByAi = false;
        $notice = '';

        if (
            $isSpam === 0
            && $settings['enable_gemini_moderation']
            && $settings['gemini_api_key'] !== ''
            && $sanitizedContent !== null
            && trim((string)$sanitizedContent) !== ''
        ) {
            $gemini = GeminiModerator::analyzeComment($sanitizedContent, $settings['gemini_api_key']);
            if ($gemini['success']) {
                $toxicityScore = round((float)$gemini['toxicity_score'], 2);
                if ($toxicityScore > (float)$settings['toxicity_threshold']) {
                    $status = 0;
                    $hiddenByAi = true;
                }
            } else {
                $notice = 'Gemini không khả dụng, dùng bộ lọc từ cấm.';
            }
        }

        return [
            'content' => $sanitizedContent,
            'flagged_words' => $flaggedWords,
            'flagged_words_json' => json_encode(array_values(array_unique($flaggedWords)), JSON_UNESCAPED_UNICODE),
            'toxicity_score' => $toxicityScore,
            'is_spam' => $isSpam,
            'status' => $status,
            'hidden_by_ai' => $hiddenByAi,
            'notice' => $notice,
        ];
    }

    /**
     * Ghi nhận một lần vi phạm spam. Đủ ngưỡng thì khóa user và reset attempt_count về 0.
     */
    public static function registerSpamAttempt($userId, array $settings = []) {
        $userId = (int)$userId;
        $settings = !empty($settings) ? $settings : self::getSettings();
        $row = self::getModerationRow($userId);
        $attemptCount = (int)($row['attempt_count'] ?? 0) + 1;
        $lockedUntil = null;

        if ($attemptCount >= (int)$settings['max_comment_attempts']) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . (int)$settings['comment_lock_hours'] . ' hours'));
            $attemptCount = 0;
        }

        self::saveModerationRow($userId, [
            'attempt_count' => $attemptCount,
            'locked_until' => $lockedUntil,
            'last_attempt_at' => date('Y-m-d H:i:s'),
        ], $row);
    }

    /**
     * Reset trạng thái vi phạm khi user gửi bình luận hợp lệ hoặc đã hết hạn khóa.
     */
    public static function clearSpamAttempts($userId) {
        $userId = (int)$userId;
        $row = self::getModerationRow($userId);
        if (!$row) {
            return;
        }

        self::saveModerationRow($userId, [
            'attempt_count' => 0,
            'locked_until' => null,
            'last_attempt_at' => date('Y-m-d H:i:s'),
        ], $row);
    }

    /**
     * Lấy bản ghi moderation theo user.
     */
    public static function getModerationRow($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            return Database::fetchOne('SELECT * FROM comment_moderation WHERE user_id = ? LIMIT 1', [$userId]);
        }

        foreach (Database::getTable('comment_moderation') as $row) {
            if ((int)($row['user_id'] ?? 0) === $userId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Upsert bản ghi moderation để DB thật và fallback cùng hành vi.
     */
    private static function saveModerationRow($userId, array $data, $existingRow = null) {
        $userId = (int)$userId;

        if ($existingRow) {
            Database::update(
                'comment_moderation',
                $data,
                'id = :id',
                ['id' => (int)($existingRow['id'] ?? 0)]
            );
            return;
        }

        Database::insert('comment_moderation', array_merge([
            'user_id' => $userId,
        ], $data));
    }

    /**
     * Kiểm tra nội dung sau lọc có còn gì ngoài `***`, dấu câu và khoảng trắng hay không.
     */
    private static function isMaskedOnlyContent($content) {
        $content = trim((string)$content);
        if ($content === '') {
            return false;
        }

        $probe = preg_replace('/[\s\*\.,;:!\?_\-\'"`~\(\)\[\]\{\}\/\\\\|+=<>@#$%^&]+/u', '', $content);
        return trim((string)$probe) === '';
    }

    /**
     * Đổi số giây còn lại thành nhãn gọn cho UX đếm ngược.
     */
    private static function formatRemainingTime($seconds) {
        $seconds = max(0, (int)$seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return max(1, $minutes) . ' phút';
    }
}
