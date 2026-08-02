<?php
/**
 * NotificationModel quản lý thông báo admin -> tenant.
 * Broadcast dùng `notifications.user_id = NULL`, còn trạng thái đọc riêng từng tenant được lưu tại `notification_reads`.
 */
class NotificationModel {
    private const TYPES = [
        'price_change' => 'Đổi giá dịch vụ',
        'payment' => 'Thanh toán',
        'general' => 'Chung',
    ];

    private static $ensuredReadTable = false;

    /**
     * Trả danh sách loại thông báo hợp lệ để controller/view dùng chung.
     */
    public static function getTypeOptions() {
        return self::TYPES;
    }

    /**
     * Đảm bảo bảng `notification_reads` tồn tại để support trạng thái đọc riêng cho broadcast.
     * Không sửa schema cũ theo hướng phá hủy, chỉ bổ sung bảng phụ trợ an toàn.
     */
    private static function ensureReadTableExists() {
        if (self::$ensuredReadTable || !Database::hasConnection()) {
            self::$ensuredReadTable = true;
            return;
        }

        Database::query(
            "
            CREATE TABLE IF NOT EXISTS notification_reads (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              notification_id INT UNSIGNED NOT NULL,
              user_id INT UNSIGNED NOT NULL,
              read_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_notification_user (notification_id, user_id),
              KEY fk_nr_user (user_id),
              CONSTRAINT fk_nr_notification FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE,
              CONSTRAINT fk_nr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        );

        self::$ensuredReadTable = true;
    }

    /**
     * Chuẩn hóa một dòng thông báo để mọi nơi nhận cùng kiểu dữ liệu.
     */
    private static function normalizeRow(array $row) {
        $type = in_array(($row['type'] ?? 'general'), array_keys(self::TYPES), true)
            ? $row['type']
            : 'general';

        return [
            'id' => (int)($row['id'] ?? 0),
            'user_id' => isset($row['user_id']) && $row['user_id'] !== null ? (int)$row['user_id'] : null,
            'title' => trim((string)($row['title'] ?? '')),
            'content' => trim((string)($row['content'] ?? '')),
            'type' => $type,
            'type_label' => self::TYPES[$type] ?? self::TYPES['general'],
            'is_read' => !empty($row['is_read']) ? 1 : 0,
            'created_at' => $row['created_at'] ?? null,
            'recipient_name' => trim((string)($row['recipient_name'] ?? '')),
        ];
    }

    /**
     * Tạo một thông báo mới từ admin.
     */
    public static function create(array $data) {
        $type = trim((string)($data['type'] ?? 'general'));
        if (!isset(self::TYPES[$type])) {
            throw new RuntimeException('Loại thông báo không hợp lệ.');
        }

        $title = trim((string)($data['title'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Tiêu đề thông báo là bắt buộc.');
        }
        if ($content === '') {
            throw new RuntimeException('Nội dung thông báo là bắt buộc.');
        }

        $userId = array_key_exists('user_id', $data) && $data['user_id'] !== null ? (int)$data['user_id'] : null;
        if ($userId !== null && !UserModel::getById($userId)) {
            throw new RuntimeException('Tenant nhận thông báo không tồn tại.');
        }

        return (int)Database::insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'is_read' => 0,
        ]);
    }

    /**
     * Trả lịch sử thông báo admin đã gửi, có thể lọc theo loại hoặc đối tượng.
     */
    public static function getAdminHistory(array $filters = []) {
        $type = trim((string)($filters['type'] ?? ''));
        $userId = (int)($filters['user_id'] ?? 0);

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    n.*,
                    u.full_name AS recipient_name
                FROM notifications n
                LEFT JOIN users u ON u.id = n.user_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($type !== '' && isset(self::TYPES[$type])) {
                $sql .= ' AND n.type = ?';
                $params[] = $type;
            }
            if ($userId > 0) {
                $sql .= ' AND n.user_id = ?';
                $params[] = $userId;
            } elseif (!empty($filters['recipient_scope']) && $filters['recipient_scope'] === 'all') {
                $sql .= ' AND n.user_id IS NULL';
            }

            $sql .= ' ORDER BY n.created_at DESC, n.id DESC';
            $rows = Database::fetchAll($sql, $params);
        } else {
            $users = [];
            foreach (UserModel::getAll() as $user) {
                $users[(int)($user['id'] ?? 0)] = $user;
            }

            $rows = array_map(static function ($row) use ($users) {
                $recipient = !empty($row['user_id']) ? ($users[(int)$row['user_id']] ?? []) : [];
                $row['recipient_name'] = $recipient['full_name'] ?? '';
                return $row;
            }, Database::getTable('notifications'));

            $rows = array_values(array_filter($rows, static function ($row) use ($type, $userId, $filters) {
                if ($type !== '' && ($row['type'] ?? '') !== $type) {
                    return false;
                }
                if ($userId > 0 && (int)($row['user_id'] ?? 0) !== $userId) {
                    return false;
                }
                if (!empty($filters['recipient_scope']) && $filters['recipient_scope'] === 'all' && ($row['user_id'] ?? null) !== null) {
                    return false;
                }
                return true;
            }));

            usort($rows, static function ($left, $right) {
                $createdCompare = strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
                if ($createdCompare !== 0) {
                    return $createdCompare;
                }

                return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
            });
        }

        return array_map(static function ($row) {
            $notification = self::normalizeRow($row);
            $notification['recipient_label'] = $notification['user_id'] === null
                ? 'Tất cả cư dân'
                : ($notification['recipient_name'] !== '' ? $notification['recipient_name'] : 'Tenant riêng lẻ');
            return $notification;
        }, $rows);
    }

    /**
     * Lấy danh sách thông báo của tenant, gồm broadcast và thông báo gửi riêng.
     * Full list tuân thủ yêu cầu hiển thị cũ -> mới nếu `order = asc`.
     */
    public static function getForUser($userId, array $options = []) {
        self::ensureReadTableExists();

        $resolvedUserId = (int)$userId;
        $limit = isset($options['limit']) ? max(1, (int)$options['limit']) : null;
        $order = strtolower(trim((string)($options['order'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc';
        $onlyUnread = !empty($options['only_unread']);

        if (Database::hasConnection()) {
            $limitSql = $limit !== null ? ' LIMIT ' . $limit : '';
            $sql = "
                SELECT
                    n.*,
                    CASE
                        WHEN nr.id IS NOT NULL THEN 1
                        WHEN n.user_id = ? AND n.is_read = 1 THEN 1
                        ELSE 0
                    END AS resolved_is_read
                FROM notifications n
                LEFT JOIN notification_reads nr
                    ON nr.notification_id = n.id
                   AND nr.user_id = ?
                WHERE (n.user_id IS NULL OR n.user_id = ?)
            ";
            $params = [$resolvedUserId, $resolvedUserId, $resolvedUserId];

            if ($onlyUnread) {
                $sql .= ' AND nr.id IS NULL AND NOT (n.user_id = ? AND n.is_read = 1)';
                $params[] = $resolvedUserId;
            }

            $sql .= ' ORDER BY n.created_at ' . strtoupper($order) . ', n.id ' . strtoupper($order) . $limitSql;
            $rows = Database::fetchAll($sql, $params);
        } else {
            $readMap = self::getFallbackReadMap($resolvedUserId);
            $rows = array_values(array_filter(Database::getTable('notifications'), static function ($row) use ($resolvedUserId) {
                return ($row['user_id'] ?? null) === null || (int)($row['user_id'] ?? 0) === $resolvedUserId;
            }));

            $rows = array_map(static function ($row) use ($resolvedUserId, $readMap) {
                $notificationId = (int)($row['id'] ?? 0);
                $row['resolved_is_read'] = !empty($readMap[$notificationId])
                    || ((int)($row['user_id'] ?? 0) === $resolvedUserId && !empty($row['is_read']))
                    ? 1
                    : 0;
                return $row;
            }, $rows);

            if ($onlyUnread) {
                $rows = array_values(array_filter($rows, static fn($row) => empty($row['resolved_is_read'])));
            }

            usort($rows, static function ($left, $right) use ($order) {
                $createdCompare = strcmp((string)($left['created_at'] ?? ''), (string)($right['created_at'] ?? ''));
                if ($createdCompare === 0) {
                    $createdCompare = (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
                }

                return $order === 'asc' ? $createdCompare : -$createdCompare;
            });

            if ($limit !== null) {
                $rows = array_slice($rows, 0, $limit);
            }
        }

        return array_map(static function ($row) {
            $row['is_read'] = !empty($row['resolved_is_read']) ? 1 : 0;
            return self::normalizeRow($row);
        }, $rows);
    }

    /**
     * Đếm số thông báo chưa đọc để render badge chuông trên header tenant.
     */
    public static function getUnreadCount($userId) {
        $resolvedUserId = (int)$userId;
        return count(self::getForUser($resolvedUserId, ['only_unread' => true]));
    }

    /**
     * Lấy 5 thông báo mới nhất cho dropdown chuông.
     */
    public static function getRecentForUser($userId, $limit = 5) {
        return self::getForUser((int)$userId, [
            'limit' => max(1, (int)$limit),
            'order' => 'desc',
        ]);
    }

    /**
     * Tìm một thông báo và đảm bảo tenant hiện tại có quyền đọc.
     */
    public static function getByIdForUser($notificationId, $userId) {
        $resolvedNotificationId = (int)$notificationId;
        $resolvedUserId = (int)$userId;

        foreach (self::getForUser($resolvedUserId, ['order' => 'desc']) as $notification) {
            if ((int)($notification['id'] ?? 0) === $resolvedNotificationId) {
                return $notification;
            }
        }

        return null;
    }

    /**
     * Đánh dấu một thông báo là đã đọc cho đúng tenant hiện tại.
     */
    public static function markAsRead($notificationId, $userId) {
        self::ensureReadTableExists();

        $notification = self::getByIdForUser((int)$notificationId, (int)$userId);
        if (!$notification) {
            throw new RuntimeException('Thông báo không tồn tại hoặc bạn không có quyền truy cập.');
        }

        if (Database::hasConnection()) {
            Database::query(
                'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)',
                [(int)$notificationId, (int)$userId]
            );

            if ((int)($notification['user_id'] ?? 0) === (int)$userId) {
                Database::update(
                    'notifications',
                    ['is_read' => 1],
                    'id = :id',
                    ['id' => (int)$notificationId]
                );
            }

            return true;
        }

        $reads = Database::getTable('notification_reads');
        foreach ($reads as $row) {
            if ((int)($row['notification_id'] ?? 0) === (int)$notificationId && (int)($row['user_id'] ?? 0) === (int)$userId) {
                return true;
            }
        }

        $reads[] = [
            'id' => count($reads) + 1,
            'notification_id' => (int)$notificationId,
            'user_id' => (int)$userId,
            'read_at' => date('Y-m-d H:i:s'),
        ];
        Database::setTable('notification_reads', $reads);

        if ((int)($notification['user_id'] ?? 0) === (int)$userId) {
            $notifications = Database::getTable('notifications');
            foreach ($notifications as $index => $row) {
                if ((int)($row['id'] ?? 0) === (int)$notificationId) {
                    $notifications[$index]['is_read'] = 1;
                    break;
                }
            }
            Database::setTable('notifications', $notifications);
        }

        return true;
    }

    /**
     * Đánh dấu toàn bộ thông báo hiện có của tenant là đã đọc.
     */
    public static function markAllAsRead($userId) {
        $resolvedUserId = (int)$userId;
        $notifications = self::getForUser($resolvedUserId, ['order' => 'desc']);

        foreach ($notifications as $notification) {
            if ((int)($notification['is_read'] ?? 0) === 1) {
                continue;
            }

            self::markAsRead((int)($notification['id'] ?? 0), $resolvedUserId);
        }

        return true;
    }

    /**
     * Dựng map đọc/chưa đọc trong fallback để tránh lặp logic nhiều nơi.
     */
    private static function getFallbackReadMap($userId) {
        $resolvedUserId = (int)$userId;
        $map = [];

        foreach (Database::getTable('notification_reads') as $row) {
            if ((int)($row['user_id'] ?? 0) === $resolvedUserId) {
                $map[(int)($row['notification_id'] ?? 0)] = true;
            }
        }

        return $map;
    }
}
