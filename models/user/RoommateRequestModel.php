<?php
/**
 * Yêu cầu ở ghép — người B xin ở cùng người A.
 * Bảng: roommate_requests
 * Trạng thái: pending → approved (A duyệt, B vào ngay) | rejected (A từ chối) | admin_rejected (admin veto)
 */
class RoommateRequestModel {

    public static function create($data) {
        $gender = $data['gender'] ?? 'other';
        $payload = [
            'requester_id' => (int)($data['requester_id'] ?? 0),
            'target_user_id' => (int)($data['target_user_id'] ?? 0),
            'room_id'      => (int)($data['room_id'] ?? 0),
            'gender'       => in_array($gender, ['male', 'female', 'other'], true) ? $gender : 'other',
            'relationship' => trim((string)($data['relationship'] ?? '')),
            'status'       => 'pending',
        ];
        return (int)Database::insert('roommate_requests', $payload);
    }

    public static function getById($id) {
        if (!Database::hasConnection()) { return null; }
        $rows = Database::fetchAll('SELECT * FROM roommate_requests WHERE id = ?', [(int)$id]);
        return !empty($rows) ? $rows[0] : null;
    }

    /** Các yêu cầu đang chờ người A (host) duyệt. */
    public static function getPendingByHost($hostUserId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            "SELECT rr.*, u.full_name AS requester_name, u.email AS requester_email, u.phone AS requester_phone,
                    r.name AS room_name
             FROM roommate_requests rr
             INNER JOIN users u ON u.id = rr.requester_id
             INNER JOIN rooms r ON r.id = rr.room_id
             WHERE rr.target_user_id = ? AND rr.status = 'pending'
             ORDER BY rr.id DESC",
            [(int)$hostUserId]
        );
        return is_array($rows) ? $rows : [];
    }

    public static function hasPendingByRequester($requesterId) {
        if (!Database::hasConnection()) { return false; }
        $rows = Database::fetchAll(
            "SELECT id FROM roommate_requests WHERE requester_id = ? AND status = 'pending' LIMIT 1",
            [(int)$requesterId]
        );
        return !empty($rows);
    }

    public static function getByRequester($requesterId) {
        if (!Database::hasConnection()) { return []; }
        $rows = Database::fetchAll(
            'SELECT * FROM roommate_requests WHERE requester_id = ? ORDER BY id DESC',
            [(int)$requesterId]
        );
        return is_array($rows) ? $rows : [];
    }

    /** Danh sách cho admin theo dõi / veto. */
    public static function getAll($filters = []) {
        if (!Database::hasConnection()) { return []; }
        $sql = 'SELECT * FROM roommate_requests';
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
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY id DESC';
        $rows = Database::fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public static function setStatus($id, $status) {
        return Database::update('roommate_requests', ['status' => $status], 'id = :id', ['id' => (int)$id]);
    }
}