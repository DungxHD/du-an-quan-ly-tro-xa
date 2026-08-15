<?php
/**
 * Yêu cầu thuê phòng — khách/tenant gửi, admin duyệt.
 * Bảng: rental_requests
 */
class RentalRequestModel {

    public static function create($data) {
        $gender = $data['gender'] ?? 'other';
        $payload = [
            'user_id'        => (int)($data['user_id'] ?? 0),
            'room_id'        => (int)($data['room_id'] ?? 0),
            'move_in_date'   => trim((string)($data['move_in_date'] ?? '')) ?: date('Y-m-d'),
            'gender'         => in_array($gender, ['male', 'female', 'other'], true) ? $gender : 'other',
            'occupant_count' => max(1, (int)($data['occupant_count'] ?? 1)),
            'status'         => 'pending',
            'admin_note'     => trim((string)($data['admin_note'] ?? '')),
        ];
        return (int)Database::insert('rental_requests', $payload);
    }

    public static function getById($id) {
        if (!Database::hasConnection()) { return null; }
        $rows = Database::fetchAll('SELECT * FROM rental_requests WHERE id = ?', [(int)$id]);
        return !empty($rows) ? $rows[0] : null;
    }

    /** Yêu cầu đang CHỜ XỬ LÝ của user (phục vụ quy tắc 1-yêu-cầu-pending). */
    public static function getPendingByUser($userId) {
        if (!Database::hasConnection()) { return null; }
        $rows = Database::fetchAll(
            "SELECT * FROM rental_requests WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1",
            [(int)$userId]
        );
        return !empty($rows) ? $rows[0] : null;
    }

    public static function hasPendingByUser($userId) {
        return self::getPendingByUser($userId) !== null;
    }

    /** Lịch sử yêu cầu của 1 user. */
    public static function getByUser($userId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            'SELECT * FROM rental_requests WHERE user_id = ? ORDER BY id DESC',
            [(int)$userId]
        );
        return is_array($rows) ? $rows : [];
    }

    /** Hàng đợi cho admin: kèm tên user + phòng + khu/tầng để hiển thị. */
    public static function getPendingWithDetails() {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            "SELECT rr.*,
                    u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone,
                    r.name AS room_name, r.max_occupancy AS room_max_occupancy,
                    f.name AS floor_name, a.name AS area_name
             FROM rental_requests rr
             INNER JOIN users u ON u.id = rr.user_id
             INNER JOIN rooms r ON r.id = rr.room_id
             INNER JOIN floors f ON f.id = r.floor_id
             INNER JOIN areas a ON a.id = f.area_id
             WHERE rr.status = 'pending'
             ORDER BY rr.id ASC"
        );
        return is_array($rows) ? $rows : [];
    }

    /**
     * Danh sách yêu cầu kèm thông tin user + phòng + khu/tầng cho trang admin.
     */
    public static function getAllWithDetails($filters = []) {
        if (!Database::hasConnection()) { return []; }
        $sql = "SELECT rr.*,
                       u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone,
                       r.name AS room_name, r.max_occupancy AS room_max_occupancy,
                       f.name AS floor_name, a.name AS area_name
                FROM rental_requests rr
                INNER JOIN users u ON u.id = rr.user_id
                INNER JOIN rooms r ON r.id = rr.room_id
                LEFT JOIN floors f ON f.id = r.floor_id
                LEFT JOIN areas a ON a.id = f.area_id";
        $params = [];
        $conditions = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'rr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $conditions[] = '(u.full_name LIKE ? OR r.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY rr.id DESC';
        $rows = Database::fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public static function getAll($filters = []) {
        if (!Database::hasConnection()) { return []; }
        $sql = 'SELECT * FROM rental_requests';
        $params = [];
        $conditions = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['room_id'])) {
            $conditions[] = 'room_id = ?';
            $params[] = (int)$filters['room_id'];
        }
        if (!empty($filters['user_id'])) {
            $conditions[] = 'user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY id DESC';
        $rows = Database::fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /** Đổi trạng thái (approved / rejected). Admin ghi kèm phản hồi. */
    public static function setStatus($id, $status, $adminNote = null) {
        $payload = ['status' => $status];
        if ($adminNote !== null) {
            $payload['admin_note'] = trim((string)$adminNote);
        }
        return Database::update('rental_requests', $payload, 'id = :id', ['id' => (int)$id]);
    }

    /** User tự hủy yêu cầu đang chờ của chính mình (để đổi phòng khác). */
    public static function cancelByUser($id, $userId) {
        return Database::update(
            'rental_requests',
            ['status' => 'cancelled'],
            'id = :id AND user_id = :user_id AND status = :status',
            ['id' => (int)$id, 'user_id' => (int)$userId, 'status' => 'pending']
        );
    }

    /** Số yêu cầu đang chờ của 1 phòng (hiển thị badge nếu cần). */
    public static function countPendingByRoom($roomId) {
        if (!Database::hasConnection()) { return 0; }
        $rows = Database::fetchAll(
            "SELECT COUNT(*) AS total FROM rental_requests WHERE room_id = ? AND status = 'pending'",
            [(int)$roomId]
        );
        return (int)($rows[0]['total'] ?? 0);
    }
}