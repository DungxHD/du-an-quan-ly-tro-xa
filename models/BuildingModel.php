<?php
class BuildingModel {
    public static function getAll() {
        if (Database::hasConnection()) {
            $buildings = Database::fetchAll("SELECT b.*, 
                                            (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id) as room_count,
                                            (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id AND r.status = 'available') as available_count,
                                            (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id AND r.status = 'rented' AND r.notice_given = 1 AND r.expected_vacant_date IS NOT NULL) as upcoming_count
                                            FROM buildings b 
                                            ORDER BY b.sort_order ASC");

            foreach ($buildings as &$building) {
                $building['open_room_count'] = (int)($building['available_count'] ?? 0) + (int)($building['upcoming_count'] ?? 0);
            }
            unset($building);

            return $buildings;
        }

        $rooms = Database::getTable('rooms');
        $buildings = Database::getTable('buildings');

        foreach ($buildings as &$building) {
            $buildingRooms = array_filter($rooms, static fn($room) => (int)$room['building_id'] === (int)$building['id']);
            $building['room_count'] = count($buildingRooms);
            $building['available_count'] = count(array_filter($buildingRooms, static fn($room) => ($room['status'] ?? '') === 'available'));
            // Đếm riêng phòng đã báo trả để trang chủ hiển thị đúng "phòng có thể xem/đặt".
            $building['upcoming_count'] = count(array_filter($buildingRooms, static function ($room) {
                return ($room['status'] ?? '') === 'rented'
                    && (int)($room['notice_given'] ?? 0) === 1
                    && !empty($room['expected_vacant_date']);
            }));
        }
        unset($building);

        foreach ($buildings as &$building) {
            $building['open_room_count'] = (int)($building['available_count'] ?? 0) + (int)($building['upcoming_count'] ?? 0);
        }
        unset($building);

        usort($buildings, static fn($a, $b) => (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0));
        return $buildings;
    }
    
    public static function getById($id) {
        if (Database::hasConnection()) {
            return Database::fetchOne("SELECT * FROM buildings WHERE id = ?", [$id]);
        }
        return Database::find('buildings', $id);
    }
    
    public static function save($data, $id = null) {
        $payload = [
            'name' => trim($data['name'] ?? ''),
            'type' => $data['type'] ?? 'building',
            'address' => trim($data['address'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ];

        if ($id) {
            Database::update('buildings', $payload, 'id = :id', ['id' => $id]);
            return $id;
        } else {
            $payload['image'] = trim($data['image'] ?? '') ?: 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=900';
            return Database::insert('buildings', $payload);
        }
    }
    
    public static function delete($id) {
        Database::delete('buildings', 'id = :id', ['id' => $id]);
    }
    
    public static function count() {
        return count(self::getAll());
    }
}
