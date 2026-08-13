<?php
/**
 * Quản lý dữ liệu khu theo schema mới `areas -> floors -> rooms`.
 * Model nay cung cap cac phuong thuc truy van khu kem thong ke tang va phong.
 */
class AreaModel {
    /**
     * Lấy danh sách khu kèm thống kê số tầng, số phòng và trạng thái sử dụng phòng.
     */
    public static function getAllWithStats() {
        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    a.*,
                    COUNT(DISTINCT f.id) AS floor_count,
                    COALESCE(MAX(f.floor_number), 0) AS max_floor_number,
                    COUNT(r.id) AS room_count,
                    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                    SUM(CASE WHEN r.status = 'rented' THEN 1 ELSE 0 END) AS rented_count,
                    SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_count
                FROM areas a
                LEFT JOIN floors f ON f.area_id = a.id
                LEFT JOIN rooms r ON r.floor_id = f.id
                GROUP BY a.id
                ORDER BY a.id DESC
            ";

            return array_map([self::class, 'normalizeAreaStats'], Database::fetchAll($sql));
        }

        $areas = Database::getTable('areas');
        $floors = Database::getTable('floors');
        $rooms = Database::getTable('rooms');

        $rows = array_map(static function ($area) use ($floors, $rooms) {
            $areaFloors = array_values(array_filter($floors, static fn($floor) => (int)($floor['area_id'] ?? 0) === (int)($area['id'] ?? 0)));
            $floorIds = array_map(static fn($floor) => (int)($floor['id'] ?? 0), $areaFloors);
            $areaRooms = array_values(array_filter($rooms, static fn($room) => in_array((int)($room['floor_id'] ?? 0), $floorIds, true)));

            $maxFloorNumber = 0;
            foreach ($areaFloors as $floor) {
                $fn = (int)($floor['floor_number'] ?? 0);
                if ($fn > $maxFloorNumber) $maxFloorNumber = $fn;
            }

            $area['floor_count'] = count($areaFloors);
            $area['max_floor_number'] = $maxFloorNumber;
            $area['room_count'] = count($areaRooms);
            $area['available_count'] = count(array_filter($areaRooms, static fn($room) => ($room['status'] ?? '') === 'available'));
            $area['rented_count'] = count(array_filter($areaRooms, static fn($room) => ($room['status'] ?? '') === 'rented'));
            $area['maintenance_count'] = count(array_filter($areaRooms, static fn($room) => ($room['status'] ?? '') === 'maintenance'));
            return self::normalizeAreaStats($area);
        }, $areas);

        usort($rows, static fn($a, $b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
        return $rows;
    }

    /**
     * Lấy danh sách khu kèm cây tầng để render accordion/tổng quan phân cấp.
     */
    public static function getTree() {
        $areas = self::getAllWithStats();
        $floors = FloorModel::getAll();
        $floorsByArea = [];

        foreach ($floors as $floor) {
            $areaId = (int)($floor['area_id'] ?? 0);
            $floorsByArea[$areaId][] = $floor;
        }

        foreach ($areas as &$area) {
            $area['floors'] = $floorsByArea[(int)($area['id'] ?? 0)] ?? [];
        }
        unset($area);

        return $areas;
    }

    /**
     * Lấy thông tin một khu theo ID để phục vụ form sửa.
     */
    public static function getById($id) {
        if (Database::hasConnection()) {
            return Database::fetchOne('SELECT * FROM areas WHERE id = ?', [(int)$id]);
        }

        return Database::find('areas', $id);
    }

    /**
     * Tạo mới hoặc cập nhật khu.
     */
    public static function save($data, $id = null) {
        $payload = [
            'name' => trim((string)($data['name'] ?? '')),
            'address' => trim((string)($data['address'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'image' => trim((string)($data['image'] ?? '')),
        ];

        if ($id) {
            Database::update('areas', $payload, 'id = :id', ['id' => (int)$id]);
            return (int)$id;
        }

        return Database::insert('areas', $payload);
    }

    /**
     * Xóa khu. Ở DB thật, cascade nằm ở FK.
     * Ở fallback, model tự xóa tầng và phòng liên quan để mô phỏng hành vi tương tự.
     */
    public static function delete($id) {
        $id = (int)$id;

        if (!Database::hasConnection()) {
            $floors = Database::getTable('floors');
            $floorIds = array_map(
                static fn($floor) => (int)($floor['id'] ?? 0),
                array_filter($floors, static fn($floor) => (int)($floor['area_id'] ?? 0) === $id)
            );

            Database::setTable(
                'rooms',
                array_values(array_filter(
                    Database::getTable('rooms'),
                    static fn($room) => !in_array((int)($room['floor_id'] ?? 0), $floorIds, true)
                ))
            );
            Database::setTable(
                'floors',
                array_values(array_filter($floors, static fn($floor) => (int)($floor['area_id'] ?? 0) !== $id))
            );
        }

        Database::delete('areas', 'id = :id', ['id' => $id]);
    }

    /**
     * Chuẩn hóa kiểu dữ liệu số để view render nhất quán giữa DB thật và fallback.
     */
    private static function normalizeAreaStats($area) {
        $area['floor_count'] = (int)($area['floor_count'] ?? 0);
        $area['room_count'] = (int)($area['room_count'] ?? 0);
        $area['available_count'] = (int)($area['available_count'] ?? 0);
        $area['rented_count'] = (int)($area['rented_count'] ?? 0);
        $area['maintenance_count'] = (int)($area['maintenance_count'] ?? 0);
        return $area;
    }
}
