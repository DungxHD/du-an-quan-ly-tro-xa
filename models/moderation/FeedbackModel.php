<?php
/**
 * Quản lý Phản ánh từ người thuê gửi trực tiếp cho chủ trọ.
 * - Tenant gửi phản ánh (khiếu nại, đề xuất, báo sự cố...) kèm ảnh minh họa tùy chọn.
 * - Admin xem, xử lý, trả lời phản ánh; tenant nhận thông báo khi có phản hồi.
 */
class FeedbackModel {
    /**
     * Tạo bộ filter chuẩn cho màn admin feedbacks.
     */
    public static function normalizeFilters(array $filters = []) {
        $status = trim((string)($filters['status'] ?? ''));

        return [
            'status' => in_array($status, ['pending', 'resolved', 'dismissed'], true) ? $status : '',
            'keyword' => trim((string)($filters['keyword'] ?? '')),
        ];
    }

    /**
     * Tenant gửi phản ánh mới. Không giới hạn thời gian ở (không liên quan đánh giá),
     * ảnh minh họa là tùy chọn.
     */
    public static function create($userId, $subject, $content, $imageUrl = null) {
        $userId = (int)$userId;
        $subject = trim((string)$subject);
        $content = trim((string)$content);
        $imageUrl = trim((string)($imageUrl ?? ''));

        if ($userId <= 0) {
            throw new RuntimeException('Bạn cần đăng nhập để gửi phản ánh.');
        }

        if ($subject === '') {
            throw new RuntimeException('Vui lòng nhập tiêu đề phản ánh.');
        }

        if ($content === '') {
            throw new RuntimeException('Vui lòng nhập nội dung phản ánh.');
        }

        Database::insert('feedbacks', [
            'user_id' => $userId,
            'subject' => mb_substr($subject, 0, 255, 'UTF-8'),
            'content' => mb_substr($content, 0, 2000, 'UTF-8'),
            'image' => $imageUrl !== '' ? $imageUrl : null,
            'status' => 'pending',
            'admin_note' => '',
            'admin_reply' => '',
        ]);

        // Gửi thông báo cho admin
        self::notifyAdmins($userId, $subject);

        return true;
    }

    /**
     * Gửi thông báo cho tất cả admin khi có phản ánh mới.
     */
    private static function notifyAdmins($userId, $subject) {
        $user = UserModel::getById($userId);
        $userName = $user ? $user['full_name'] : 'Người thuê';
        
        // Lấy danh sách admin
        $admins = UserModel::getAll(['role' => 1]);
        foreach ($admins as $admin) {
            NotificationModel::create([
                'user_id' => (int)$admin['id'],
                'title' => 'Phản ánh mới từ người thuê',
                'content' => "{$userName} đã gửi phản ánh: {$subject}",
                'type' => 'feedback',
                'link' => '?page=admin-feedbacks',
            ]);
        }
    }

    /**
     * Lấy danh sách phản ánh để admin duyệt.
     */
    public static function getAdminFeedbacks(array $filters = []) {
        $filters = self::normalizeFilters($filters);

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    f.*,
                    u.full_name AS tenant_name,
                    u.email AS tenant_email,
                    r.name AS room_name
                FROM feedbacks f
                INNER JOIN users u ON u.id = f.user_id
                LEFT JOIN rooms r ON r.id = f.room_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($filters['status'] !== '') {
                $sql .= ' AND f.status = ?';
                $params[] = $filters['status'];
            }

            if ($filters['keyword'] !== '') {
                $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR f.subject LIKE ? OR f.content LIKE ? OR r.name LIKE ?)';
                $keyword = '%' . $filters['keyword'] . '%';
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }

            $sql .= " ORDER BY CASE f.status WHEN 'pending' THEN 0 WHEN 'resolved' THEN 1 ELSE 2 END ASC, f.created_at DESC";
            return array_map([self::class, 'normalizeRow'], Database::fetchAll($sql, $params));
        }

        $feedbacks = self::buildFallbackRows();
        $feedbacks = array_filter($feedbacks, static function ($row) use ($filters) {
            if ($filters['status'] !== '' && (string)($row['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['keyword'] !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    (string)($row['tenant_name'] ?? ''),
                    (string)($row['tenant_email'] ?? ''),
                    (string)($row['room_name'] ?? ''),
                    (string)($row['subject'] ?? ''),
                    (string)($row['content'] ?? ''),
                ])), 'UTF-8');

                if (mb_strpos($haystack, mb_strtolower($filters['keyword'], 'UTF-8'), 0, 'UTF-8') === false) {
                    return false;
                }
            }

            return true;
        });

        usort($feedbacks, [self::class, 'compareRows']);
        return array_values($feedbacks);
    }

    /**
     * Lấy một phản ánh theo ID.
     */
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        foreach (self::getAdminFeedbacks() as $row) {
            if ((int)($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Lấy danh sách phản ánh của một tenant (cho trang Phản ánh bên tenant).
     */
    public static function getForUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "
                SELECT
                    f.*,
                    u.full_name AS tenant_name,
                    u.email AS tenant_email
                FROM feedbacks f
                INNER JOIN users u ON u.id = f.user_id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC, f.id DESC
                ",
                [$userId]
            );
            return array_map([self::class, 'normalizeRow'], $rows);
        }

        $rows = self::buildFallbackRows();
        $rows = array_values(array_filter($rows, static function ($row) use ($userId) {
            return (int)($row['user_id'] ?? 0) === $userId;
        }));
        usort($rows, [self::class, 'compareRows']);
        return $rows;
    }

    /**
     * Lưu hoặc cập nhật phản ánh (admin xử lý).
     * Khi admin nhập câu trả lời (admin_reply) sẽ gửi thông báo cho tenant.
     */
    public static function save($data, $id = null) {
        $id = $id ? (int)$id : null;
        $adminNote = trim((string)($data['admin_note'] ?? ''));
        $adminReply = trim((string)($data['admin_reply'] ?? ''));
        $status = in_array((string)($data['status'] ?? ''), ['pending', 'resolved', 'dismissed'], true)
            ? (string)$data['status']
            : 'pending';

        if ($id !== null && $id > 0) {
            $feedback = self::getById($id);
            if (!$feedback) {
                throw new RuntimeException('Phản ánh không tồn tại hoặc đã bị xóa.');
            }

            // Cập nhật
            Database::update('feedbacks', [
                'admin_note' => mb_substr($adminNote, 0, 1000, 'UTF-8'),
                'admin_reply' => mb_substr($adminReply, 0, 2000, 'UTF-8'),
                'status' => $status,
            ], 'id = :id', ['id' => $id]);

            // Nếu admin có câu trả lời mới -> thông báo cho tenant
            if ($adminReply !== '' && trim((string)($feedback['admin_reply'] ?? '')) !== $adminReply) {
                self::notifyTenantReply($id, $adminReply);
            }

            return $id;
        }

        // Không hỗ trợ tạo mới từ admin (chỉ tenant mới tạo)
        throw new RuntimeException('Admin không thể tạo phản ánh mới từ đây.');
    }

    /**
     * Gửi thông báo cho tenant khi admin trả lời phản ánh.
     */
    private static function notifyTenantReply($feedbackId, $adminReply) {
        $feedback = self::getById($feedbackId);
        if (!$feedback) {
            return;
        }

        $tenantId = (int)($feedback['user_id'] ?? 0);
        if ($tenantId <= 0) {
            return;
        }

        NotificationModel::create([
            'user_id' => $tenantId,
            'title' => 'Chủ trọ đã phản hồi phản ánh của bạn',
            'content' => 'Phản ánh "' . $feedback['subject'] . '": ' . mb_substr($adminReply, 0, 150, 'UTF-8'),
            'type' => 'feedback',
            'link' => '?page=tenant-feedback',
        ]);
    }

    /**
     * Admin giải quyết phản ánh.
     */
    public static function resolve($feedbackId, $action) {
        $feedbackId = (int)$feedbackId;
        $action = trim((string)$action);
        $targetStatus = $action === 'resolve' ? 'resolved' : ($action === 'dismiss' ? 'dismissed' : '');

        if ($feedbackId <= 0 || $targetStatus === '') {
            throw new RuntimeException('Thao tác xử lý phản ánh không hợp lệ.');
        }

        $feedback = self::getById($feedbackId);
        if (!$feedback) {
            throw new RuntimeException('Phản ánh không tồn tại hoặc đã bị xóa.');
        }

        Database::update('feedbacks', ['status' => $targetStatus], 'id = :id', ['id' => $feedbackId]);

        return [
            'action' => $targetStatus,
            'feedback_id' => $feedbackId,
        ];
    }

    /**
     * Xóa phản ánh.
     */
    public static function delete($id) {
        $id = (int)$id;
        if ($id <= 0) {
            throw new RuntimeException('ID phản ánh không hợp lệ.');
        }

        if (!Database::hasConnection()) {
            $rows = Database::getTable('feedbacks');
            $rows = array_filter($rows, static fn($row) => (int)($row['id'] ?? 0) !== $id);
            Database::setTable('feedbacks', array_values($rows));
            return;
        }

        Database::query('DELETE FROM feedbacks WHERE id = ?', [$id]);
    }

    /**
     * Trả thống kê nhanh cho màn quản trị phản ánh.
     */
    public static function getAdminStats(array $rows) {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'resolved' => 0,
            'dismissed' => 0,
        ];

        foreach ($rows as $row) {
            $stats['total']++;
            $status = (string)($row['status'] ?? 'pending');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    /**
     * Chuẩn hóa một dòng feedback để view không phải tự suy diễn.
     */
    private static function normalizeRow(array $row) {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['user_id'] = (int)($row['user_id'] ?? 0);
        $row['room_id'] = $row['room_id'] !== null ? (int)$row['room_id'] : null;
        $row['subject'] = trim((string)($row['subject'] ?? ''));
        $row['content'] = trim((string)($row['content'] ?? ''));
        $row['image'] = trim((string)($row['image'] ?? ''));
        $row['admin_note'] = trim((string)($row['admin_note'] ?? ''));
        $row['admin_reply'] = trim((string)($row['admin_reply'] ?? ''));
        $row['status'] = in_array((string)($row['status'] ?? ''), ['pending', 'resolved', 'dismissed'], true)
            ? (string)$row['status']
            : 'pending';
        $row['tenant_name'] = trim((string)($row['tenant_name'] ?? '')) ?: 'Người thuê';
        $row['tenant_email'] = trim((string)($row['tenant_email'] ?? ''));
        $row['room_name'] = $row['room_name'] ? trim((string)$row['room_name']) : '';
        $row['created_at_label'] = !empty($row['created_at']) && strtotime((string)$row['created_at']) !== false
            ? date('d/m/Y H:i', strtotime((string)$row['created_at']))
            : '';
        $row['status_label'] = [
            'pending' => 'Chờ xử lý',
            'resolved' => 'Đã xử lý',
            'dismissed' => 'Đã bác bỏ',
        ][$row['status']];

        return $row;
    }

    /**
     * Dựng feedback rows khi chạy bằng fallback data.
     */
    private static function buildFallbackRows() {
        $rooms = [];
        foreach (Database::getTable('rooms') as $room) {
            $rooms[(int)($room['id'] ?? 0)] = $room;
        }

        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[(int)($user['id'] ?? 0)] = $user;
        }

        return array_map(static function ($feedback) use ($rooms, $users) {
            $room = $feedback['room_id'] ? $rooms[(int)$feedback['room_id']] : null;
            $tenant = $users[(int)$feedback['user_id']] ?? [];

            $feedback['room_name'] = $room ? $room['name'] : '';
            $feedback['tenant_name'] = $tenant['full_name'] ?? 'Người thuê';
            $feedback['tenant_email'] = $tenant['email'] ?? '';

            return self::normalizeRow($feedback);
        }, Database::getTable('feedbacks'));
    }

    /**
     * So sánh theo thứ tự ưu tiên: pending trước, sau đó mới đến thời gian tạo mới nhất.
     */
    private static function compareRows(array $left, array $right) {
        $order = ['pending' => 0, 'resolved' => 1, 'dismissed' => 2];
        $statusCompare = ($order[$left['status']] ?? 99) <=> ($order[$right['status']] ?? 99);
        if ($statusCompare !== 0) {
            return $statusCompare;
        }

        return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
    }
}