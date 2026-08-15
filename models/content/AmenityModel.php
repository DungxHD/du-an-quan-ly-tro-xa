<?php
class AmenityModel {
    /**
     * Chuẩn hóa một tiện ích để giao diện public luôn có icon, tiêu đề và mô tả an toàn.
     */
    private static function normalizeAmenity($row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'icon' => trim((string)($row['icon'] ?? '')) ?: 'apartment',
            'title' => trim((string)($row['title'] ?? '')) ?: 'Tiện ích nội khu',
            'description' => trim((string)($row['description'] ?? '')) ?: 'Khu trọ đã bố trí sẵn tiện ích thiết yếu cho cư dân.',
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'is_active' => (int)($row['is_active'] ?? 0),
        ];
    }

    public static function getAllActive() {
        $rows = Database::hasConnection()
            ? Database::fetchAll("SELECT * FROM amenities WHERE is_active = 1 ORDER BY sort_order ASC")
            : Database::getTable('amenities');

        $rows = array_filter($rows, static fn($row) => (int)($row['is_active'] ?? 0) === 1);
        usort($rows, static fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
        return array_values(array_map([self::class, 'normalizeAmenity'], $rows));
    }

    /**
     * Đếm số tiện ích đang hiển thị trên website (giới hạn tối đa 8).
     */
    public static function countActive() {
        return count(self::getAllActive());
    }
    
    public static function getAll() {
        $rows = Database::hasConnection()
            ? Database::fetchAll("SELECT * FROM amenities ORDER BY sort_order ASC")
            : Database::getTable('amenities');

        usort($rows, static fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
        return array_values(array_map([self::class, 'normalizeAmenity'], $rows));
    }

    /**
     * Lấy chi tiết một tiện ích để admin đổ dữ liệu lên form chỉnh sửa.
     */
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $row = Database::hasConnection()
            ? Database::fetchOne('SELECT * FROM amenities WHERE id = ?', [$id])
            : Database::find('amenities', $id);

        return $row ? self::normalizeAmenity($row) : null;
    }

    /**
     * Trả dữ liệu tiện ích tối ưu cho trang chủ, chỉ gồm các mục đang hoạt động (tối đa 8).
     */
    public static function getHomepageItems() {
        return array_slice(self::getAllActive(), 0, 8);
    }

    /**
     * Chuẩn hóa dữ liệu đầu vào từ form admin để tránh icon/title rỗng và sai kiểu.
     */
    private static function normalizePayload($data) {
        return [
            'icon' => trim((string)($data['icon'] ?? '')) ?: 'apartment',
            'title' => trim((string)($data['title'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 0) === 1 ? 1 : 0,
        ];
    }
    
    /**
     * Lưu tiện ích theo chuẩn thống nhất cho cả DB thật và dữ liệu fallback.
     */
    public static function save($data, $id = null) {
        $payload = self::normalizePayload($data);

        if ($id) {
            Database::update('amenities', $payload, 'id = :id', ['id' => (int)$id]);
            return (int)$id;
        }

        return Database::insert('amenities', $payload);
    }
    
    /**
     * Xóa một tiện ích khỏi danh sách hiển thị/admin.
     */
    public static function delete($id) {
        Database::delete('amenities', 'id = :id', ['id' => (int)$id]);
    }
}
