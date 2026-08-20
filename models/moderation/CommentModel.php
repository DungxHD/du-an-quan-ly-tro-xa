<?php
/**
 * CommentModel gom toàn bộ nghiệp vụ đánh giá phòng:
 * - Kiểm tra tenant có đủ điều kiện đánh giá hay không (đang ở trong phòng, đủ số ngày tối thiểu).
 * - Mỗi người chỉ đánh giá mỗi phòng duy nhất một lần.
 * - Sửa đánh giá trong 24h kể từ khi gửi; sau 24h chỉ được xóa.
 */
class CommentModel {

    /**
     * Lấy cấu hình đánh giá và ép kiểu ngay tại model để controller/view không tự xử lý lẻ tẻ.
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

        if (self::getByUserAndRoom($resolvedUserId, $resolvedRoomId)) {
            return ['allowed' => false, 'message' => 'Bạn đã đánh giá phòng này rồi. Mỗi người chỉ được đánh giá mỗi phòng một lần.'];
        }

        $stay = self::getLatestEligibleStay($resolvedUserId, $resolvedRoomId);
        if (!$stay) {
            return ['allowed' => false, 'message' => 'Chỉ tenant đang ở phòng này mới được đánh giá phòng.'];
        }

        if (!self::hasReachedMinimumStay(
            (string)($stay['move_in_date'] ?? ''),
            $stay['move_out_date'] ?? null,
            $settings['min_days_to_review']
        )) {
            return ['allowed' => false, 'message' => 'Bạn cần ở đủ ' . $settings['min_days_to_review'] . ' ngày để được đánh giá phòng này.'];
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

        // Giới hạn 150 ký tự
        if ($resolvedContent !== '' && mb_strlen($resolvedContent, 'UTF-8') > 150) {
            throw new RuntimeException('Nội dung đánh giá không được vượt quá 150 ký tự.');
        }

        $moderation = null;
        if ($resolvedContent !== '') {
            $moderation = GeminiModerator::sanitize($resolvedContent);
            $resolvedContent = $moderation['content'];
        }

        $payload = [
            'room_id' => (int)$roomId,
            'user_id' => (int)$userId,
            'content' => $resolvedContent !== '' ? $resolvedContent : null,
            'rating' => $resolvedRating,
            'status' => 1,
            'edited_at' => null,
        ];

        try {
            $commentId = (int)Database::insert('comments', $payload);
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'uq_user_room') !== false || stripos($exception->getMessage(), 'Duplicate') !== false) {
                throw new RuntimeException('Bạn đã đánh giá phòng này rồi. Mỗi người chỉ được đánh giá mỗi phòng một lần.');
            }

            throw $exception;
        }

        // [DEV-QWEN-A][NHOM-2][2026-08-14] Gửi thông báo khi có review mới cho admin và các tenant
        self::notifyNewReview($commentId, $userId);

        $comment = self::getById($commentId);
        if ($moderation) {
            $comment['moderation_masked'] = (bool)($moderation['had_bad_words'] ?? false);
        }

        return $comment;
    }

    /**
     * Gửi thông báo cho tất cả admin và tenant đang thuê khi có đánh giá mới.
     */
    private static function notifyNewReview($commentId, $userId) {
        $comment = self::getById($commentId);
        if (!$comment) return;

        $user = UserModel::getById($userId);
        $userName = $user ? $user['full_name'] : 'Người thuê';
        $roomName = $comment['room_name'] ?? 'Phòng #' . ($comment['room_id'] ?? '');

        $users = UserModel::getAll();
        foreach ($users as $target) {
            $targetId = (int)($target['id'] ?? 0);
            if ($targetId <= 0 || $targetId === (int)$userId) {
                continue;
            }

            $isAdmin = (int)($target['role'] ?? -1) === 1;
            if (!$isAdmin && (int)($target['role'] ?? -1) !== 0) {
                continue;
            }

            NotificationModel::create([
                'user_id' => $targetId,
                'title' => 'Đánh giá phòng mới',
                'content' => "Phòng \"{$roomName}\" được đánh giá từ người thuê \"{$userName}\".",
                'type' => 'review',
                'link' => $isAdmin ? '?page=admin-comments' : '?page=tenant',
            ]);
        }
    }

    /**
     * Kiểm tra quyền sửa đánh giá của chính tenant trong 24h kể từ khi gửi.
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
                'message' => 'Đánh giá đã quá ' . $settings['comment_edit_hours'] . ' giờ kể từ khi gửi, bạn chỉ có thể xóa đánh giá này.',
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

        $resolvedContent = trim((string)$content);

        // Giới hạn 150 ký tự
        if ($resolvedContent !== '' && mb_strlen($resolvedContent, 'UTF-8') > 150) {
            throw new RuntimeException('Nội dung đánh giá không được vượt quá 150 ký tự.');
        }

        $moderation = null;
        if ($resolvedContent !== '') {
            $moderation = GeminiModerator::sanitize($resolvedContent);
            $resolvedContent = $moderation['content'];
        }

        Database::update(
            'comments',
            [
                'content' => $resolvedContent !== '' ? $resolvedContent : null,
                'rating' => $resolvedRating,
                'edited_at' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => (int)$commentId]
        );

        $comment = self::getById($commentId);
        if ($moderation) {
            $comment['moderation_masked'] = (bool)($moderation['had_bad_words'] ?? false);
        }

        return $comment;
    }

    /**
     * Xóa đánh giá của chính tenant. Sau 24h không sửa được nhưng vẫn xóa được bất cứ lúc nào.
     */
    public static function deleteByOwner($commentId, $userId) {
        $comment = self::getById($commentId);
        if (!$comment) {
            throw new RuntimeException('Đánh giá không tồn tại hoặc đã bị xóa.');
        }

        if ((int)($comment['user_id'] ?? 0) !== (int)$userId) {
            throw new RuntimeException('Bạn không có quyền xóa đánh giá này.');
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
     * - đánh giá riêng của chủ comment (nếu có) kể cả đang bị ẩn
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
            'public_count' => count($publicComments) + (($ownerComment && (int)($ownerComment['status'] ?? 0) === 1) ? 1 : 0),
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
                WHERE c.room_id = ? AND c.status = 1
                ORDER BY c.rating DESC, c.created_at DESC
                ",
                [$resolvedRoomId]
            );

            return array_map([self::class, 'normalizeCommentRow'], $rows);
        }

        $rows = array_filter(self::buildFallbackRows(), static function ($row) use ($resolvedRoomId) {
            return (int)($row['room_id'] ?? 0) === $resolvedRoomId
                && (int)($row['status'] ?? 0) === 1;
        });

        usort($rows, [self::class, 'compareCommentRows']);
        return array_values($rows);
    }

    /**
     * Danh sách đánh giá cho trang quản trị với filter trạng thái/tìm kiếm.
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

            if ($normalized['keyword'] !== '') {
                $sql .= ' AND (u.full_name LIKE ? OR r.name LIKE ? OR c.content LIKE ?)';
                $keyword = '%' . $normalized['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }

            $sql .= ' ORDER BY c.rating DESC, c.created_at DESC';
            return array_map([self::class, 'normalizeCommentRow'], Database::fetchAll($sql, $params));
        }

        $rows = array_filter(self::buildFallbackRows(), static function ($row) use ($normalized) {
            if ($normalized['status'] !== '') {
                $expectedStatus = $normalized['status'] === 'visible' ? 1 : 0;
                if ((int)($row['status'] ?? 0) !== $expectedStatus) {
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
        ];

        foreach ($rows as $row) {
            $stats['total']++;
            if ((int)($row['status'] ?? 0) === 1) {
                $stats['visible']++;
            } else {
                $stats['hidden']++;
            }
        }

        return $stats;
    }

    /**
     * Tìm lần ở gần nhất còn đủ điều kiện đánh giá.
     * Không còn hợp đồng: chỉ tenant đang được gán trong phòng (users.room_id) mới đủ điều kiện.
     */
    private static function getLatestEligibleStay($userId, $roomId) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT id, room_id, created_at
                FROM users
                WHERE id = ? AND room_id = ?
                LIMIT 1
                ",
                [$resolvedUserId, $resolvedRoomId]
            );
        } else {
            foreach (Database::getTable('users') as $userRow) {
                if ((int)($userRow['id'] ?? 0) === $resolvedUserId && (int)($userRow['room_id'] ?? 0) === $resolvedRoomId) {
                    $row = $userRow;
                    break;
                }
            }
            $row = $row ?? null;
        }

        if (!$row) {
            return null;
        }

        return [
            'user_id' => $resolvedUserId,
            'room_id' => $resolvedRoomId,
            'move_in_date' => (string)($row['created_at'] ?? ''),
            'move_out_date' => null,
        ];
    }

    /**
     * Kiểm tra tenant đã ở đủ số ngày tối thiểu để được đánh giá chưa.
     * Mốc vào ở lấy từ users.created_at (ngày tạo tài khoản) làm xấp xỉ, tính đến hôm nay.
     */
    private static function hasReachedMinimumStay($moveInDate, $moveOutDate, $minDays) {
        $resolvedMoveIn = trim((string)$moveInDate);
        if ($resolvedMoveIn === '' || strtotime($resolvedMoveIn) === false) {
            return false;
        }

        $start = new DateTime($resolvedMoveIn);
        $moveOut = !empty($moveOutDate) && strtotime((string)$moveOutDate) !== false
            ? new DateTime((string)$moveOutDate)
            : null;

        $end = ($moveOut !== null && $moveOut <= new DateTime()) ? $moveOut : new DateTime();

        if ($end < $start) {
            return false;
        }

        return (int)$start->diff($end)->days >= (int)$minDays;
    }

    /**
     * Chuẩn hóa dữ liệu filter admin để cả GET/POST dùng cùng một shape.
     */
    private static function normalizeAdminFilters(array $filters) {
        $status = trim((string)($filters['status'] ?? ''));

        return [
            'status' => in_array($status, ['visible', 'hidden'], true) ? $status : '',
            'keyword' => trim((string)($filters['keyword'] ?? '')),
        ];
    }

    /**
     * Ép kiểu dữ liệu comment và gắn thêm metadata hiển thị cho view.
     */
    private static function normalizeCommentRow(array $row) {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['room_id'] = (int)($row['room_id'] ?? 0);
        $row['user_id'] = (int)($row['user_id'] ?? 0);
        $row['rating'] = self::normalizeRating($row['rating'] ?? 5);
        $row['status'] = (int)($row['status'] ?? 1);
        $row['content'] = $row['content'] !== null ? trim((string)$row['content']) : null;
        $row['full_name'] = trim((string)($row['full_name'] ?? '')) ?: 'Khách thuê';
        $row['avatar'] = trim((string)($row['avatar'] ?? ''));
        $row['room_name'] = trim((string)($row['room_name'] ?? ''));
        $row['is_edited'] = !empty($row['edited_at']);
        $row['hidden_reason_label'] = (int)($row['status'] ?? 0) === 0 ? 'Ẩn bởi admin' : 'Đang hiện';
        $row['created_at_label'] = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string)$row['created_at'])) : '';
        $row['edited_at_label'] = !empty($row['edited_at']) ? date('d/m/Y H:i', strtotime((string)$row['edited_at'])) : '';
        return $row;
    }

    /**
     * So sánh comment đúng thứ tự hiển thị công khai/quan trị đã được đặc tả.
     */
    private static function compareCommentRows(array $left, array $right) {
        $ratingCompare = (int)($right['rating'] ?? 0) <=> (int)($left['rating'] ?? 0);
        if ($ratingCompare !== 0) {
            return $ratingCompare;
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

        if ((int)($comment['status'] ?? 0) === 0) {
            $badges[] = [
                'label' => 'Đã bị admin ẩn',
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