<?php
class UserModel {
    public static function getAll() {
        if (Database::hasConnection()) {
            return Database::fetchAll("SELECT u.*, r.name as room_name, b.name as building_name 
                                        FROM users u 
                                        LEFT JOIN rooms r ON u.room_id = r.id 
                                        LEFT JOIN buildings b ON r.building_id = b.id
                                        ORDER BY u.created_at DESC");
        }

        $rooms = [];
        foreach (Database::getTable('rooms') as $room) {
            $rooms[$room['id']] = $room;
        }
        $buildings = [];
        foreach (Database::getTable('buildings') as $building) {
            $buildings[$building['id']] = $building;
        }

        $users = array_map(static function ($user) use ($rooms, $buildings) {
            $room = $rooms[$user['room_id']] ?? null;
            $building = $room ? ($buildings[$room['building_id']] ?? null) : null;
            $user['room_name'] = $room['name'] ?? null;
            $user['room_price'] = $room['price'] ?? 0;
            $user['building_name'] = $building['name'] ?? null;
            return $user;
        }, Database::getTable('users'));

        usort($users, static fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $users;
    }
    
    public static function getById($id) {
        foreach (self::getAll() as $user) {
            if ((int)($user['id'] ?? 0) === (int)$id) {
                return $user;
            }
        }
        return null;
    }
    
    public static function findByEmail($email) {
        if (Database::hasConnection()) {
            return Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        }

        foreach (Database::getTable('users') as $user) {
            if (strcasecmp($user['email'] ?? '', $email) === 0) {
                return $user;
            }
        }
        return null;
    }
    
    public static function create($data) {
        $payload = [
            'full_name' => trim($data['full_name'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => (int)($data['role'] ?? 0),
            'status' => (int)($data['status'] ?? 1),
            'room_id' => $data['room_id'] ?? null,
            'avatar' => $data['avatar'] ?? '',
        ];
        return Database::insert('users', $payload);
    }
    
    public static function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        Database::update('users', $data, 'id = :id', ['id' => $id]);
    }
    
    public static function assignToRoom($userId, $roomId) {
        Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $userId]);
        Database::update('rooms', ['status' => 'rented'], 'id = :id', ['id' => $roomId]);
    }
    
    public static function countByRole($role) {
        $count = 0;
        foreach (self::getAll() as $user) {
            if ((int)($user['role'] ?? -1) === (int)$role) {
                $count++;
            }
        }
        return $count;
    }
}
