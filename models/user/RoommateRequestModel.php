<?php
/**
 * Yêu cầu ở ghép — người A mời người B, admin duyệt.
 * Bảng: roommate_requests
 * Trạng thái: pending_admin → approved (admin duyệt, B vào ngay) | rejected (admin từ chối) | cancelled (A hủy)
 */
class RoommateRequestModel {

    public static function create($data) {
        $gender = $data['gender'] ?? 'other';
        $payload = [
            'requester_id' => (int)($data['requester_id'] ?? 0),
            'host_user_id' => (int)($data['host_user_id'] ?? 0),
            'room_id'      => (int)($data['room_id'] ?? 0),
            'gender'       => in_array($gender, ['male', 'female', 'other'], true) ? $gender : 'other',
            'relationship' => trim((string)($data['relationship'] ?? '')),
            'status'       => $data['status'] ?? 'pending_admin',
        ];
        return (int)Database::insert('roommate_requests', $payload);
    }

    public static function getById($id) {
        if (!Database::hasConnection()) { return null; }
        $rows = Database::fetchAll('SELECT * FROM roommate_requests WHERE id = ?', [(int)$id]);
        return !empty($rows) ? $rows[0] : null;
    }

    /** Các yêu cầu chờ admin duyệt cho người A (host). */
    public static function getPendingByHost($hostUserId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            "SELECT rr.*, u.full_name AS requester_name, u.email AS requester_email, u.phone AS requester_phone,
                    r.name AS room_name
             FROM roommate_requests rr
             INNER JOIN users u ON u.id = rr.requester_id
             INNER JOIN rooms r ON r.id = rr.room_id
             WHERE rr.host_user_id = ? AND rr.status = 'pending_admin'
             ORDER BY rr.id DESC",
            [(int)$hostUserId]
        );
        return is_array($rows) ? $rows : [];
    }

    /** Tất cả yêu cầu của người A (host) - để hiển thị lịch sử. */
    public static function getByHost($hostUserId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            "SELECT rr.*, u.full_name AS requester_name, u.email AS requester_email, u.phone AS requester_phone,
                    r.name AS room_name
             FROM roommate_requests rr
             INNER JOIN users u ON u.id = rr.requester_id
             INNER JOIN rooms r ON r.id = rr.room_id
             WHERE rr.host_user_id = ?
             ORDER BY rr.id DESC",
            [(int)$hostUserId]
        );
        return is_array($rows) ? $rows : [];
    }

    /** Các yêu cầu mà người B đã được mời (requester). */
    public static function getByRequester($requesterId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            "SELECT rr.*, u.full_name AS host_name, u.email AS host_email, u.phone AS host_phone,
                    r.name AS room_name
             FROM roommate_requests rr
             INNER JOIN users u ON u.id = rr.host_user_id
             INNER JOIN rooms r ON r.id = rr.room_id
             WHERE rr.requester_id = ?
             ORDER BY rr.id DESC",
            [(int)$requesterId]
        );
        return is_array($rows) ? $rows : [];
    }

    public static function hasPendingByRequester($requesterId) {
        if (!Database::hasConnection()) { return false; }
        $rows = Database::fetchAll(
            "SELECT id FROM roommate_requests WHERE requester_id = ? AND status = 'pending_admin' LIMIT 1",
            [(int)$requesterId]
        );
        return !empty($rows);
    }

    /** Danh sách cho admin theo dõi / duyệt. */
    public static function getAll($filters = []) {
        if (!Database::hasConnection()) { return []; }
        $sql = "SELECT rr.*,
                       rq.full_name AS requester_name, rq.email AS requester_email, rq.phone AS requester_phone,
                       hs.full_name AS host_name, hs.email AS host_email, hs.phone AS host_phone,
                       rm.name AS room_name
                FROM roommate_requests rr
                INNER JOIN users rq ON rq.id = rr.requester_id
                INNER JOIN users hs ON hs.id = rr.host_user_id
                INNER JOIN rooms rm ON rm.id = rr.room_id";
        $params = [];
        $conditions = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'rr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['room_id'])) {
            $conditions[] = 'rr.room_id = ?';
            $params[] = (int)$filters['room_id'];
        }
        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $conditions[] = '(rq.full_name LIKE ? OR rm.name LIKE ? OR rq.email LIKE ? OR rq.phone LIKE ? OR hs.full_name LIKE ? OR hs.email LIKE ? OR hs.phone LIKE ?)';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
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

    public static function setStatus($id, $status, $adminNote = null) {
        $payload = ['status' => $status];
        if ($adminNote !== null) {
            $payload['admin_note'] = trim((string)$adminNote);
        }
        return Database::update('roommate_requests', $payload, 'id = :id', ['id' => (int)$id]);
    }

    /**
     * Đếm yêu cầu ở ghép đang chờ admin duyệt (pending_admin + pending cũ) cho badge "Cần xử lý".
     */
    public static function countPendingAdmin() {
        if (!Database::hasConnection()) { return 0; }
        $rows = Database::fetchAll(
            "SELECT COUNT(*) AS total FROM roommate_requests WHERE status IN ('pending_admin', 'pending')"
        );
        return (int)($rows[0]['total'] ?? 0);
    }
}