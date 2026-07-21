<?php
class ServiceModel {
    public static function getAll() {
        if (Database::hasConnection()) {
            return Database::fetchAll("SELECT * FROM services ORDER BY name ASC");
        }

        $services = Database::getTable('services');
        usort($services, static fn($a, $b) => strcmp($a['name'], $b['name']));
        return $services;
    }
    
    public static function getById($id) {
        if (Database::hasConnection()) {
            return Database::fetchOne("SELECT * FROM services WHERE id = ?", [$id]);
        }
        return Database::find('services', $id);
    }
    
    public static function getByRoom($roomId) {
        if (Database::hasConnection()) {
            return Database::fetchAll(
                "SELECT s.*, rs.quantity 
                 FROM services s 
                 INNER JOIN room_services rs ON s.id = rs.service_id 
                 WHERE rs.room_id = ?",
                [$roomId]
            );
        }

        $services = [];
        foreach (self::getAll() as $service) {
            $services[$service['id']] = $service;
        }

        $rows = array_filter(Database::getTable('room_services'), static fn($row) => (int)$row['room_id'] === (int)$roomId);
        $rows = array_map(static function ($row) use ($services) {
            $service = $services[$row['service_id']] ?? [];
            return array_merge($service, ['quantity' => (int)($row['quantity'] ?? 1)]);
        }, $rows);

        return array_values($rows);
    }

    /**
     * Trả map dịch vụ theo room_id để các màn public có thể lọc/phối hợp dữ liệu
     * mà không phải lặp lại truy vấn cho từng phòng.
     */
    public static function getRoomServiceMap(array $roomIds = []) {
        $roomIds = array_values(array_unique(array_map('intval', $roomIds)));
        if (empty($roomIds)) {
            return [];
        }

        $servicesByRoom = [];
        if (Database::hasConnection()) {
            $placeholders = implode(', ', array_fill(0, count($roomIds), '?'));
            $rows = Database::fetchAll(
                "SELECT rs.room_id, s.id AS service_id, s.name, s.icon, s.description
                 FROM room_services rs
                 INNER JOIN services s ON s.id = rs.service_id
                 WHERE rs.room_id IN ($placeholders)",
                $roomIds
            );
        } else {
            $services = [];
            foreach (self::getAll() as $service) {
                $services[$service['id']] = $service;
            }

            $rows = [];
            foreach (Database::getTable('room_services') as $row) {
                $roomId = (int)($row['room_id'] ?? 0);
                if (!in_array($roomId, $roomIds, true)) {
                    continue;
                }

                $service = $services[$row['service_id']] ?? [];
                $rows[] = [
                    'room_id' => $roomId,
                    'service_id' => (int)($service['id'] ?? 0),
                    'name' => $service['name'] ?? '',
                    'icon' => $service['icon'] ?? '',
                    'description' => $service['description'] ?? '',
                ];
            }
        }

        foreach ($rows as $row) {
            $roomId = (int)($row['room_id'] ?? 0);
            if (!isset($servicesByRoom[$roomId])) {
                $servicesByRoom[$roomId] = [];
            }
            $servicesByRoom[$roomId][] = $row;
        }

        return $servicesByRoom;
    }
    
    public static function registerForRoom($roomId, $serviceId, $quantity = 1) {
        // Nếu có DB thật thì dùng truy vấn; nếu không thì tra trên fallback data.
        $existing = Database::hasConnection()
            ? Database::fetchOne(
                "SELECT id FROM room_services WHERE room_id = ? AND service_id = ?",
                [$roomId, $serviceId]
            )
            : self::findRoomService($roomId, $serviceId);

        if ($existing) {
            Database::update('room_services', 
                ['quantity' => $quantity], 
                'room_id = :room_id AND service_id = :service_id',
                ['room_id' => $roomId, 'service_id' => $serviceId]
            );
        } else {
            Database::insert('room_services', [
                'room_id' => $roomId,
                'service_id' => $serviceId,
                'quantity' => $quantity
            ]);
        }
    }
    
    public static function getTotalServiceCost($roomId) {
        $total = 0;
        foreach (self::getByRoom($roomId) as $service) {
            $total += (float)($service['price'] ?? 0) * (int)($service['quantity'] ?? 1);
        }
        return $total;
    }

    private static function findRoomService($roomId, $serviceId) {
        foreach (Database::getTable('room_services') as $row) {
            if ((int)($row['room_id'] ?? 0) === (int)$roomId && (int)($row['service_id'] ?? 0) === (int)$serviceId) {
                return $row;
            }
        }
        return null;
    }
}
