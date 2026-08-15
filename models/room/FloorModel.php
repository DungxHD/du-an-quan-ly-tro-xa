<?php

/**
 * Model tầng phục vụ CRUD, lọc theo khu và thống kê số phòng từng tầng.
 */
class FloorModel
{
    /**
     * Lấy danh sách tầng. Có thể truyền `areaId` để chỉ lấy tầng của một khu.
     */
    public static function getAll($areaId = 0)
    {
        $areaId = (int)$areaId;

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    f.*,
                    a.name AS area_name,
                    COUNT(r.id) AS room_count,
                    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                    SUM(CASE WHEN r.status = 'rented' THEN 1 ELSE 0 END) AS rented_count,
                    SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_count
                FROM floors f
                INNER JOIN areas a ON a.id = f.area_id
                LEFT JOIN rooms r ON r.floor_id = f.id
            ";
            $params = [];

            if ($areaId > 0) {
                $sql .= ' WHERE f.area_id = ?';
                $params[] = $areaId;
            }

            $sql .= ' GROUP BY f.id ORDER BY a.name ASC, f.floor_number ASC, f.id ASC';
            return array_map([self::class, 'normalizeFloorStats'], Database::fetchAll($sql, $params));
        }

        $areas = [];
        foreach (Database::getTable('areas') as $area) {
            $areas[(int)($area['id'] ?? 0)] = $area;
        }

        $rows = array_filter(Database::getTable('floors'), static function ($floor) use ($areaId) {
            return $areaId <= 0 || (int)($floor['area_id'] ?? 0) === $areaId;
        });

        $rooms = Database::getTable('rooms');
        $rows = array_map(static function ($floor) use ($areas, $rooms) {
            $floorRooms = array_values(array_filter($rooms, static fn($room) => (int)($room['floor_id'] ?? 0) === (int)($floor['id'] ?? 0)));
            $floor['area_name'] = $areas[(int)($floor['area_id'] ?? 0)]['name'] ?? 'Chưa có khu';
            $floor['room_count'] = count($floorRooms);
            $floor['available_count'] = count(array_filter($floorRooms, static fn($room) => ($room['status'] ?? '') === 'available'));
            $floor['rented_count'] = count(array_filter($floorRooms, static fn($room) => ($room['status'] ?? '') === 'rented'));
            $floor['maintenance_count'] = count(array_filter($floorRooms, static fn($room) => ($room['status'] ?? '') === 'maintenance'));
            return self::normalizeFloorStats($floor);
        }, array_values($rows));

        usort($rows, static function ($left, $right) {
            $areaCompare = strcmp((string)($left['area_name'] ?? ''), (string)($right['area_name'] ?? ''));
            if ($areaCompare !== 0) {
                return $areaCompare;
            }

            $floorCompare = (int)($left['floor_number'] ?? 0) <=> (int)($right['floor_number'] ?? 0);
            if ($floorCompare !== 0) {
                return $floorCompare;
            }

            return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
        });

        return $rows;
    }

    /**
     * Alias dễ đọc hơn cho controller khi cần lọc tầng theo khu.
     */
    public static function getByAreaId($areaId)
    {
        return self::getAll($areaId);
    }

    /**
     * Lấy một tầng theo ID.
     */
    public static function getById($id)
    {
        if (Database::hasConnection()) {
            $sql = "
                SELECT f.*, a.name AS area_name
                FROM floors f
                INNER JOIN areas a ON a.id = f.area_id
                WHERE f.id = ?
            ";
            return Database::fetchOne($sql, [(int)$id]);
        }

        $floor = Database::find('floors', $id);
        if (!$floor) {
            return null;
        }

        $area = Database::find('areas', (int)($floor['area_id'] ?? 0));
        $floor['area_name'] = $area['name'] ?? 'Chưa có khu';
        return $floor;
    }

    /**
     * Tạo mới hoặc cập nhật tầng.
     */
    public static function save($data, $id = null)
    {
        $floorNumber = (int)($data['floor_number'] ?? 1);
        $payload = [
            'area_id'      => (int)($data['area_id'] ?? 0),
            'name'         => trim((string)($data['name'] ?? '')) ?: ($floorNumber === 0 ? 'Tầng 1' : 'Tầng ' . $floorNumber),
            'floor_number' => $floorNumber,
            'room_limit'   => max(0, (int)($data['room_limit'] ?? 0)),
        ];
        if ($id) {
            Database::update('floors', $payload, 'id = :id', ['id' => (int)$id]);
            return (int)$id;
        }
        return (int)Database::insert('floors', $payload);
    }

    /**
     * Xóa tầng. Theo `database.sql` hiện tại, thao tác này sẽ kéo theo xóa phòng liên quan do FK cascade.
     * Nếu muốn `SET NULL`, bắt buộc phải đổi schema trước.
     */
    public static function delete($id)
    {
        $id = (int)$id;

        if (!Database::hasConnection()) {
            Database::setTable(
                'rooms',
                array_values(array_filter(
                    Database::getTable('rooms'),
                    static fn($room) => (int)($room['floor_id'] ?? 0) !== $id
                ))
            );
        }

        Database::delete('floors', 'id = :id', ['id' => $id]);
    }

    /**
     * Chuẩn hóa kiểu số giữa DB thật và fallback.
     */
    private static function normalizeFloorStats($floor)
    {
        $floor['area_id'] = (int)($floor['area_id'] ?? 0);
        $floor['floor_number'] = (int)($floor['floor_number'] ?? 0);
        $floor['room_count'] = (int)($floor['room_count'] ?? 0);
        $floor['available_count'] = (int)($floor['available_count'] ?? 0);
        $floor['rented_count'] = (int)($floor['rented_count'] ?? 0);
        $floor['maintenance_count'] = (int)($floor['maintenance_count'] ?? 0);
        return $floor;
    }
}
