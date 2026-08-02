<?php
/**
 * CommentModel gom toàn bộ nghiệp vụ đánh giá phòng:
 * - Kiểm tra tenant có đủ điều kiện đánh giá hay không.
 * - Kiểm duyệt nội dung qua từ cấm và Gemini tùy cấu hình.
 * - Quản lý quyền sửa/xóa trong 24h và danh sách admin/public.
 */
class CommentModel {
    private const REVIEW_WINDOW_DAYS = 15;

    /**
     * Lấy cấu hình moderation và ép kiểu ngay tại model để controller/view không tự xử lý lẻ tẻ.
     */
    public static function getModerationSettings() {
        return CommentModerationModel::getSettings();
    }

    /**
     * Kiểm tra tenant có thể xem chi tiết phòng ở chế độ không-public hay không.
     * Dùng cho trường hợp phòng đang thuê hoặc vừa chuyển đi nhưng vẫn cần xem/sửa đánh giá.
     */
    public static function canUserAccessRoomDetail($userId, $roomId) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;

        if ($resolvedUserId <= 0 || $resolvedRoomId <= 0) {
            return false;
        }

        if (self::getByUserAndRoom($resolvedUserId, $resolvedRoomId)) {
            return true;
        }

        return self::getLatestEligibleStay($resolvedUserId, $resolvedRoomId) !== null;
    }

    /**
     * Kiểm tra trước khi tạo mới đánh giá để trả lỗi rõ ràng đúng UX yêu cầu.
     */
    public static function validateCreatePermission($userId, $roomId) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;
        $settings = self::getModerationSettings();

        if ($resolvedUserId <= 0) {
            return ['allowed' => false, 'message' => 'Bạn cần đăng nhập để đánh giá phòng.'];
        }

        if ($resolvedRoomId <= 0 || !RoomModel::getById($resolvedRoomId)) {
            return ['allowed' => false, 'message' => 'Phòng cần đánh giá không hợp lệ hoặc không tồn tại.'];
        }

        $lockState = self::getLockState($resolvedUserId, $settings);
        if ($lockState['locked']) {
            return ['allowed' => false, 'message' => $lockState['message']];
        }

        if (self::getByUserAndRoom($resolvedUserId, $resolvedRoomId)) {
            return ['allowed' => false, 'message' => 'Bạn đã đánh giá phòng này.'];
        }

        $stay = self::getLatestEligibleStay($resolvedUserId, $resolvedRoomId);
        if (!$stay) {
            return ['allowed' => false, 'message' => 'Chỉ tenant đang ở hoặc vừa chuyển đi trong 15 ngày mới được đánh giá phòng này.'];
        }

        if (!self::hasReachedMinimumStay(
            (string)($stay['move_in_date'] ?? ''),
            $stay['move_out_date'] ?? null,
            $settings['min_days_to_review']
        )) {
            return ['allowed' => false, 'message' => 'Bạn cần ở đủ ' . $settings['min_days_to_review'] . ' ngày để đánh giá.'];
        }

        return [
            'allowed' => true,
            'message' => '',
            'stay' => $stay,
            'settings' => $settings,
        ];
    }

    /**
     * Tạo mới đánh giá cho phòng. Nội dung được phép để trống nếu tenant chỉ muốn chấm sao.
     */
    public static function create($userId, $roomId, $rating, $content) {
        $permission = self::validateCreatePermission($userId, $roomId);
        if (!$permission['allowed']) {
            throw new RuntimeException($permission['message']);
        }

        $resolvedRating = self::normalizeRating($rating);
        $resolvedContent = trim((string)$content);
        if ($resolvedRating < 1 || $resolvedRating > 5) {
            throw new RuntimeException('Số sao đánh giá phải từ 1 đến 5.');
        }

        $moderated = self::moderateContent($resolvedContent, $permission['settings']);
        $payload = [
            'room_id' => (int)$roomId,
            'user_id' => (int)$userId,
            'content' => $moderated['content'],
            'rating' => $resolvedRating,
            'toxicity_score' => $moderated['toxicity_score'],
            'is_spam' => $moderated['is_spam'],
            'flagged_words' => $moderated['flagged_words_json'],
            'status' => $moderated['status'],
            'edited_at' => null,
        ];

        try {
            $commentId = (int)Database::insert('comments', $payload);
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'uq_user_room') !== false || stripos($exception->getMessage(), 'Duplicate') !== false) {
                throw new RuntimeException('Bạn đã đánh giá phòng này.');
            }

            throw $exception;
        }

        if ($moderated['is_spam'] === 1) {
            self::registerSpamAttempt((int)$userId, $permission['settings']);
        } else {
            self::clearSpamAttempts((int)$userId);
        }

        $comment = self::getById($commentId);
        if ($comment) {
            $comment['moderation_notice'] = $moderated['notice'] ?? '';
        }

        return $comment;
    }

    /**
     * Kiểm tra quyền sửa/xóa đánh giá của chính tenant trong giới hạn thời gian cấu hình.
     */
    public static function validateOwnerAction($commentId, $userId) {
        $comment = self::getById($commentId);
        $settings = self::getModerationSettings();

        if (!$comment) {
            return ['allowed' => false, 'message' => 'Đánh giá không tồn tại hoặc đã bị xóa.', 'comment' => null];
        }

        if ((int)($comment['user_id'] ?? 0) !== (int)$userId) {
            return ['allowed' => false, 'message' => 'Bạn không có quyền thao tác trên đánh giá này.', 'comment' => $comment];
        }

        $window = self::buildEditWindowMeta($comment, $settings['comment_edit_hours']);
        if (!$window['can_edit']) {
            return [
                'allowed' => false,
                'message' => 'Đánh giá đã quá ' . $settings['comment_edit_hours'] . ' giờ. Vui lòng liên hệ admin để sửa hoặc xóa.',
                'comment' => $comment,
                'meta' => $window,
            ];
        }

        return [
            'allowed' => true,
            'message' => '',
            'comment' => $comment,
            'settings' => $settings,
            'meta' => $window,
        ];
    }

    /**
     * Cập nhật đánh giá của tenant. `created_at` được giữ nguyên để không reset thời gian 24h.
     */
    public static function updateByOwner($commentId, $userId, $rating, $content) {
        $permission = self::validateOwnerAction($commentId, $userId);
        if (!$permission['allowed']) {
            throw new RuntimeException($permission['message']);
        }

        $resolvedRating = self::normalizeRating($rating);
        if ($resolvedRating < 1 || $resolvedRating > 5) {
            throw new RuntimeException('Số sao đánh giá phải từ 1 đến 5.');
        }

        $moderated = self::moderateContent(trim((string)$content), $permission['settings']);
        Database::update(
            'comments',
            [
                'content' => $moderated['content'],
                'rating' => $resolvedRating,
                'toxicity_score' => $moderated['toxicity_score'],
                'is_spam' => $moderated['is_spam'],
                'flagged_words' => $moderated['flagged_words_json'],
                'status' => $moderated['status'],
                'edited_at' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => (int)$commentId]
        );

        if ($moderated['is_spam'] === 1) {
            self::registerSpamAttempt((int)$userId, $permission['settings']);
        } else {
            self::clearSpamAttempts((int)$userId);
        }

        $comment = self::getById($commentId);
        if ($comment) {
            $comment['moderation_notice'] = $moderated['notice'] ?? '';
        }

        return $comment;
    }

    /**
     * Xóa đánh giá của chính tenant trong thời hạn cho phép.
     */
    public static function deleteByOwner($commentId, $userId) {
        $permission = self::validateOwnerAction($commentId, $userId);
        if (!$permission['allowed']) {
            throw new RuntimeException($permission['message']);
        }

        Database::delete('comments', 'id = :id', ['id' => (int)$commentId]);
        return true;
    }

    /**
     * Lấy một comment theo ID kèm dữ liệu phòng/người dùng để view quản trị dùng trực tiếp.
     */
    public static function getById($id) {
        $commentId = (int)$id;
        if ($commentId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT
                    c.*,
                    u.full_name,
                    u.avatar,
                    r.name AS room_name
                FROM comments c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                WHERE c.id = ?
                LIMIT 1
                ",
                [$commentId]
            );

            return $row ? self::normalizeCommentRow($row) : null;
        }

        foreach (self::buildFallbackRows() as $row) {
            if ((int)($row['id'] ?? 0) === $commentId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Tìm đánh giá duy nhất của một user trong một phòng.
     */
    public static function getByUserAndRoom($userId, $roomId) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;
        if ($resolvedUserId <= 0 || $resolvedRoomId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT
                    c.*,
                    u.full_name,
                    u.avatar,
                    r.name AS room_name
                FROM comments c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                WHERE c.user_id = ? AND c.room_id = ?
                LIMIT 1
                ",
                [$resolvedUserId, $resolvedRoomId]
            );

            return $row ? self::normalizeCommentRow($row) : null;
        }

        foreach (self::buildFallbackRows() as $row) {
            if ((int)($row['user_id'] ?? 0) === $resolvedUserId && (int)($row['room_id'] ?? 0) === $resolvedRoomId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Trả về block dữ liệu cho trang chi tiết:
     * - danh sách đánh giá công khai
     * - đánh giá riêng của chủ comment (nếu có) kể cả spam/ẩn
     */
    public static function getRoomDetailComments($roomId, $viewerUserId = 0) {
        $resolvedRoomId = (int)$roomId;
        $resolvedViewerId = (int)$viewerUserId;
        $publicComments = self::getPublicByRoom($resolvedRoomId);
        $ownerComment = $resolvedViewerId > 0 ? self::getByUserAndRoom($resolvedViewerId, $resolvedRoomId) : null;

        if ($ownerComment) {
            $publicComments = array_values(array_filter(
                $publicComments,
                static fn($comment) => (int)($comment['id'] ?? 0) !== (int)($ownerComment['id'] ?? 0)
            ));

            $ownerWindow = self::buildEditWindowMeta($ownerComment, self::getModerationSettings()['comment_edit_hours']);
            $ownerComment['can_edit'] = $ownerWindow['can_edit'];
            $ownerComment['edit_deadline'] = $ownerWindow['deadline_label'];
            $ownerComment['visibility_badges'] = self::buildVisibilityBadges($ownerComment);
        }

        return [
            'public_comments' => $publicComments,
            'owner_comment' => $ownerComment,
            'public_count' => count($publicComments) + (($ownerComment && (int)($ownerComment['status'] ?? 0) === 1 && (int)($ownerComment['is_spam'] ?? 0) === 0) ? 1 : 0),
        ];
    }

    /**
     * Danh sách comment public đúng điều kiện hiển thị cho khách xem.
     */
    public static function getPublicByRoom($roomId) {
        $resolvedRoomId = (int)$roomId;

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "
                SELECT
                    c.*,
                    u.full_name,
                    u.avatar,
                    r.name AS room_name
                FROM comments c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                WHERE c.room_id = ? AND c.status = 1 AND c.is_spam = 0
                ORDER BY c.rating DESC, c.is_spam ASC, c.toxicity_score ASC, c.created_at DESC
                ",
                [$resolvedRoomId]
            );

            return array_map([self::class, 'normalizeCommentRow'], $rows);
        }

        $rows = array_filter(self::buildFallbackRows(), static function ($row) use ($resolvedRoomId) {
            return (int)($row['room_id'] ?? 0) === $resolvedRoomId
                && (int)($row['status'] ?? 0) === 1
                && (int)($row['is_spam'] ?? 0) === 0;
        });

        usort($rows, [self::class, 'compareCommentRows']);
        return array_values($rows);
    }

    /**
     * Danh sách đánh giá cho trang quản trị với filter spam/trạng thái/tìm kiếm.
     */
    public static function getAdminComments(array $filters = []) {
        $normalized = self::normalizeAdminFilters($filters);

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    c.*,
                    u.full_name,
                    u.avatar,
                    r.name AS room_name
                FROM comments c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($normalized['status'] !== '') {
                $sql .= ' AND c.status = ?';
                $params[] = $normalized['status'] === 'visible' ? 1 : 0;
            }

            if ($normalized['spam'] !== '') {
                $sql .= ' AND c.is_spam = ?';
                $params[] = $normalized['spam'] === 'spam' ? 1 : 0;
            }

            if ($normalized['keyword'] !== '') {
                $sql .= ' AND (u.full_name LIKE ? OR r.name LIKE ? OR c.content LIKE ?)';
                $keyword = '%' . $normalized['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }

            $sql .= ' ORDER BY c.rating DESC, c.is_spam ASC, c.toxicity_score ASC, c.created_at DESC';
            return array_map([self::class, 'normalizeCommentRow'], Database::fetchAll($sql, $params));
        }

        $rows = array_filter(self::buildFallbackRows(), static function ($row) use ($normalized) {
            if ($normalized['status'] !== '') {
                $expectedStatus = $normalized['status'] === 'visible' ? 1 : 0;
                if ((int)($row['status'] ?? 0) !== $expectedStatus) {
                    return false;
                }
            }

            if ($normalized['spam'] !== '') {
                $expectedSpam = $normalized['spam'] === 'spam' ? 1 : 0;
                if ((int)($row['is_spam'] ?? 0) !== $expectedSpam) {
                    return false;
                }
            }

            if ($normalized['keyword'] !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    (string)($row['full_name'] ?? ''),
                    (string)($row['room_name'] ?? ''),
                    (string)($row['content'] ?? ''),
                ])), 'UTF-8');

                if (mb_strpos($haystack, mb_strtolower($normalized['keyword'], 'UTF-8')) === false) {
                    return false;
                }
            }

            return true;
        });

        usort($rows, [self::class, 'compareCommentRows']);
        return array_values($rows);
    }

    /**
     * Bật/tắt hiển thị comment từ admin.
     */
    public static function toggleStatus($commentId, $targetStatus = null) {
        $comment = self::getById($commentId);
        if (!$comment) {
            throw new RuntimeException('Đánh giá không tồn tại hoặc đã bị xóa.');
        }

        $nextStatus = $targetStatus === null
            ? ((int)($comment['status'] ?? 0) === 1 ? 0 : 1)
            : ((int)$targetStatus === 1 ? 1 : 0);

        Database::update(
            'comments',
            ['status' => $nextStatus],
            'id = :id',
            ['id' => (int)$commentId]
        );

        return self::getById($commentId);
    }

    /**
     * Trả thống kê nhanh cho màn hình admin để render card tổng quan.
     */
    public static function getAdminStats(array $rows) {
        $stats = [
            'total' => 0,
            'visible' => 0,
            'hidden' => 0,
            'spam' => 0,
            'clean' => 0,
        ];

        foreach ($rows as $row) {
            $stats['total']++;
            if ((int)($row['status'] ?? 0) === 1) {
                $stats['visible']++;
            } else {
                $stats['hidden']++;
            }

            if ((int)($row['is_spam'] ?? 0) === 1) {
                $stats['spam']++;
            } else {
                $stats['clean']++;
            }
        }

        return $stats;
    }

    /**
     * Tìm lần ở gần nhất còn đủ điều kiện đánh giá.
     */
    private static function getLatestEligibleStay($userId, $roomId) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;
        $boundaryDate = date('Y-m-d', strtotime('-' . self::REVIEW_WINDOW_DAYS . ' days'));

        if (Database::hasConnection()) {
            return Database::fetchOne(
                "
                SELECT *
                FROM contracts
                WHERE user_id = ?
                    AND room_id = ?
                    AND (move_out_date IS NULL OR move_out_date >= ?)
                ORDER BY
                    CASE WHEN move_out_date IS NULL THEN 0 ELSE 1 END ASC,
                    COALESCE(move_out_date, '9999-12-31') DESC,
                    move_in_date DESC,
                    id DESC
                LIMIT 1
                ",
                [$resolvedUserId, $resolvedRoomId, $boundaryDate]
            );
        }

        $rows = array_filter(Database::getTable('contracts'), static function ($row) use ($resolvedUserId, $resolvedRoomId, $boundaryDate) {
            if ((int)($row['user_id'] ?? 0) !== $resolvedUserId || (int)($row['room_id'] ?? 0) !== $resolvedRoomId) {
                return false;
            }

            $moveOutDate = $row['move_out_date'] ?? null;
            return $moveOutDate === null || $moveOutDate === '' || $moveOutDate >= $boundaryDate;
        });

        usort($rows, static function ($left, $right) {
            $leftPriority = empty($left['move_out_date']) ? 0 : 1;
            $rightPriority = empty($right['move_out_date']) ? 0 : 1;
            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            $dateCompare = strcmp((string)($right['move_out_date'] ?? '9999-12-31'), (string)($left['move_out_date'] ?? '9999-12-31'));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string)($right['move_in_date'] ?? ''), (string)($left['move_in_date'] ?? ''));
        });

        return !empty($rows) ? array_values($rows)[0] : null;
    }

    /**
     * Kiểm tra tenant đã ở đủ số ngày tối thiểu để được đánh giá chưa.
     */
    private static function hasReachedMinimumStay($moveInDate, $moveOutDate, $minDays) {
        $resolvedMoveIn = trim((string)$moveInDate);
        if ($resolvedMoveIn === '' || strtotime($resolvedMoveIn) === false) {
            return false;
        }

        $start = new DateTime($resolvedMoveIn);
        $end = !empty($moveOutDate) && strtotime((string)$moveOutDate) !== false
            ? new DateTime((string)$moveOutDate)
            : new DateTime();

        if ($end < $start) {
            return false;
        }

        return (int)$start->diff($end)->days >= (int)$minDays;
    }

    /**
     * Tính trạng thái khóa comment của người dùng theo bảng `comment_moderation`.
     */
    private static function getLockState($userId, array $settings) {
        return CommentModerationModel::getLockState($userId, $settings);
    }

    /**
     * Ghi nhận một lần nội dung bị xem là spam để áp dụng cơ chế khóa tạm nếu vượt ngưỡng.
     */
    private static function registerSpamAttempt($userId, array $settings) {
        CommentModerationModel::registerSpamAttempt($userId, $settings);
    }

    /**
     * Reset chuỗi vi phạm khi người dùng gửi nội dung hợp lệ hoặc đã hết thời gian khóa.
     */
    private static function clearSpamAttempts($userId) {
        CommentModerationModel::clearSpamAttempts($userId);
    }

    /**
     * Lấy trạng thái moderation hiện tại của user.
     */
    private static function getModerationRow($userId) {
        $resolvedUserId = (int)$userId;
        if ($resolvedUserId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            return Database::fetchOne(
                'SELECT * FROM comment_moderation WHERE user_id = ? LIMIT 1',
                [$resolvedUserId]
            );
        }

        foreach (Database::getTable('comment_moderation') as $row) {
            if ((int)($row['user_id'] ?? 0) === $resolvedUserId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Upsert trạng thái moderation để DB thật và fallback cùng hành vi.
     */
    private static function saveModerationRow($userId, array $data, $existingRow = null) {
        $resolvedUserId = (int)$userId;
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
            'user_id' => $resolvedUserId,
        ], $data));
    }

    /**
     * Chạy toàn bộ pipeline kiểm duyệt nội dung.
     */
    private static function moderateContent($content, array $settings) {
        return CommentModerationModel::moderate($content, $settings);
    }

    /**
     * Mã hóa các từ/cụm từ cấm theo bảng `banned_words`.
     */
    private static function sanitizeByBannedWords($content) {
        $sanitized = (string)$content;
        $flaggedWords = [];

        foreach (self::getBannedWords() as $word) {
            $rawWord = trim((string)($word['word'] ?? ''));
            if ($rawWord === '') {
                continue;
            }

            $replacement = trim((string)($word['replacement'] ?? '***')) ?: '***';
            $type = trim((string)($word['type'] ?? 'word'));
            $pattern = $type === 'word'
                ? '/(?<![\p{L}\p{N}_])' . preg_quote($rawWord, '/') . '(?![\p{L}\p{N}_])/iu'
                : '/' . preg_quote($rawWord, '/') . '/iu';

            if (preg_match($pattern, $sanitized)) {
                $flaggedWords[] = $rawWord;
                $sanitized = preg_replace($pattern, $replacement, $sanitized);
            }
        }

        return [trim((string)$sanitized), array_values(array_unique($flaggedWords))];
    }

    /**
     * Kiểm tra nội dung sau khi lọc có còn gì ngoài dấu `*` và ký tự trang trí hay không.
     */
    private static function isMaskedOnlyContent($content) {
        $resolvedContent = trim((string)$content);
        if ($resolvedContent === '') {
            return false;
        }

        $probe = preg_replace('/[\s\*\.,;:!\?_\-\'"`~\(\)\[\]\{\}\/\\\\|+=<>@#$%^&]+/u', '', $resolvedContent);
        return trim((string)$probe) === '';
    }

    /**
     * Gọi Gemini chấm điểm độc hại. Nếu lỗi mạng/API thì hạ an toàn về 0 để không chặn luồng chính.
     */
    private static function getGeminiToxicityScore($content, $apiKey) {
        $analysis = GeminiModerator::analyzeComment($content, $apiKey);
        return (float)($analysis['toxicity_score'] ?? 0);
    }

    /**
     * Lấy bộ từ cấm. Khi thiếu DB thật thì dùng dữ liệu fallback để chức năng vẫn chạy.
     */
    private static function getBannedWords() {
        return BannedWordModel::getActiveWords();
    }

    /**
     * Chuẩn hóa dữ liệu filter admin để cả GET/POST dùng cùng một shape.
     */
    private static function normalizeAdminFilters(array $filters) {
        $status = trim((string)($filters['status'] ?? ''));
        $spam = trim((string)($filters['spam'] ?? ''));

        return [
            'status' => in_array($status, ['visible', 'hidden'], true) ? $status : '',
            'spam' => in_array($spam, ['spam', 'clean'], true) ? $spam : '',
            'keyword' => trim((string)($filters['keyword'] ?? '')),
        ];
    }

    /**
     * Ép kiểu dữ liệu comment và gắn thêm metadata hiển thị cho view.
     */
    private static function normalizeCommentRow(array $row) {
        $settings = self::getModerationSettings();
        $row['id'] = (int)($row['id'] ?? 0);
        $row['room_id'] = (int)($row['room_id'] ?? 0);
        $row['user_id'] = (int)($row['user_id'] ?? 0);
        $row['rating'] = self::normalizeRating($row['rating'] ?? 5);
        $row['status'] = (int)($row['status'] ?? 1);
        $row['is_spam'] = (int)($row['is_spam'] ?? 0);
        $row['toxicity_score'] = round((float)($row['toxicity_score'] ?? 0), 2);
        $row['content'] = $row['content'] !== null ? trim((string)$row['content']) : null;
        $row['full_name'] = trim((string)($row['full_name'] ?? '')) ?: 'Khách thuê';
        $row['avatar'] = trim((string)($row['avatar'] ?? ''));
        $row['room_name'] = trim((string)($row['room_name'] ?? ''));
        $row['flagged_words_list'] = self::decodeFlaggedWords($row['flagged_words'] ?? '[]');
        $row['is_edited'] = !empty($row['edited_at']);
        $row['is_hidden_by_ai'] = (int)($row['status'] ?? 0) === 0
            && (float)($row['toxicity_score'] ?? 0) > (float)($settings['toxicity_threshold'] ?? 0.7);
        $row['hidden_reason_label'] = $row['is_hidden_by_ai'] ? 'Ẩn do AI' : ((int)($row['status'] ?? 0) === 0 ? 'Ẩn thủ công' : 'Đang hiện');
        $row['created_at_label'] = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string)$row['created_at'])) : '';
        $row['edited_at_label'] = !empty($row['edited_at']) ? date('d/m/Y H:i', strtotime((string)$row['edited_at'])) : '';
        return $row;
    }

    /**
     * Decode danh sách từ bị gắn cờ từ JSON trong DB.
     */
    private static function decodeFlaggedWords($value) {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * So sánh comment đúng thứ tự hiển thị công khai/quan trị đã được đặc tả.
     */
    private static function compareCommentRows(array $left, array $right) {
        $ratingCompare = (int)($right['rating'] ?? 0) <=> (int)($left['rating'] ?? 0);
        if ($ratingCompare !== 0) {
            return $ratingCompare;
        }

        $spamCompare = (int)($left['is_spam'] ?? 0) <=> (int)($right['is_spam'] ?? 0);
        if ($spamCompare !== 0) {
            return $spamCompare;
        }

        $toxicityCompare = (float)($left['toxicity_score'] ?? 0) <=> (float)($right['toxicity_score'] ?? 0);
        if ($toxicityCompare !== 0) {
            return $toxicityCompare;
        }

        return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
    }

    /**
     * Tạo metadata cửa sổ sửa/xóa để view không tự tính thời gian.
     */
    private static function buildEditWindowMeta(array $comment, $editHours) {
        $createdAt = trim((string)($comment['created_at'] ?? ''));
        if ($createdAt === '' || strtotime($createdAt) === false) {
            return ['can_edit' => false, 'deadline_label' => ''];
        }

        $deadline = strtotime($createdAt . ' +' . (int)$editHours . ' hours');
        return [
            'can_edit' => $deadline >= time(),
            'deadline_label' => date('d/m/Y H:i', $deadline),
        ];
    }

    /**
     * Badge trạng thái hiển thị riêng cho chủ comment.
     */
    private static function buildVisibilityBadges(array $comment) {
        $badges = [];

        if ((int)($comment['is_spam'] ?? 0) === 1) {
            $badges[] = ['label' => 'Chỉ mình bạn thấy', 'class' => 'bg-amber-100 text-amber-700'];
        }

        if ((int)($comment['status'] ?? 0) === 0) {
            $badges[] = [
                'label' => !empty($comment['is_hidden_by_ai']) ? 'Ẩn do AI' : 'Đã bị admin ẩn',
                'class' => 'bg-rose-100 text-rose-700',
            ];
        }

        return $badges;
    }

    /**
     * Chuẩn hóa rating về khoảng 1-5.
     */
    private static function normalizeRating($rating) {
        return max(1, min(5, (int)$rating));
    }

    /**
     * Dựng dữ liệu fallback có cùng shape với truy vấn join DB thật.
     */
    private static function buildFallbackRows() {
        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[(int)($user['id'] ?? 0)] = $user;
        }

        $rooms = [];
        foreach (Database::getTable('rooms') as $room) {
            $rooms[(int)($room['id'] ?? 0)] = $room;
        }

        return array_map(static function ($comment) use ($users, $rooms) {
            $user = $users[(int)($comment['user_id'] ?? 0)] ?? [];
            $room = $rooms[(int)($comment['room_id'] ?? 0)] ?? [];
            $comment['full_name'] = $user['full_name'] ?? 'Khách thuê';
            $comment['avatar'] = $user['avatar'] ?? '';
            $comment['room_name'] = $room['name'] ?? '';
            return self::normalizeCommentRow($comment);
        }, Database::getTable('comments'));
    }
}
