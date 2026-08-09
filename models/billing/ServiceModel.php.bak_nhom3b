<?php
class ServiceModel {
    private const BILLING_MODES = ['fixed', 'meter', 'per_person', 'per_unit'];
    private const APPLIES_TO = ['room', 'person'];

    /**
     * Trả danh sách lựa chọn cách tính giá để controller/view không hard-code nhiều nơi.
     */
    public static function getBillingModeOptions() {
        return [
            'fixed' => 'Cố định',
            'meter' => 'Theo chỉ số',
            'per_person' => 'Theo người',
            'per_unit' => 'Theo số lượng',
        ];
    }

    /**
     * Trả danh sách đối tượng áp dụng của dịch vụ.
     */
    public static function getAppliesToOptions() {
        return [
            'room' => 'Theo phòng',
            'person' => 'Theo người',
        ];
    }

    /**
     * Chuẩn hóa bản ghi dịch vụ để cả DB thật và fallback cùng một shape dữ liệu.
     */
    private static function normalizeServiceRow(array $service) {
        return [
            'id' => (int)($service['id'] ?? 0),
            'name' => trim((string)($service['name'] ?? '')),
            'price' => (float)($service['price'] ?? 0),
            'unit' => trim((string)($service['unit'] ?? 'tháng')),
            'icon' => trim((string)($service['icon'] ?? 'settings')),
            'description' => trim((string)($service['description'] ?? '')),
            'is_required' => !empty($service['is_required']) ? 1 : 0,
            'billing_mode' => in_array(($service['billing_mode'] ?? 'fixed'), self::BILLING_MODES, true)
                ? $service['billing_mode']
                : 'fixed',
            'applies_to' => in_array(($service['applies_to'] ?? 'room'), self::APPLIES_TO, true)
                ? $service['applies_to']
                : 'room',
            'is_active' => array_key_exists('is_active', $service) ? (!empty($service['is_active']) ? 1 : 0) : 1,
        ];
    }

    /**
     * Trả danh sách dịch vụ có hỗ trợ lọc theo trạng thái, đối tượng áp dụng và tính bắt buộc.
     */
    public static function getAll($filters = []) {
        $filters = array_merge([
            'applies_to' => null,
            'active_only' => false,
            'exclude_required' => false,
            'required_only' => false,
        ], $filters);

        if (Database::hasConnection()) {
            $sql = 'SELECT * FROM services WHERE 1 = 1';
            $params = [];

            if (!empty($filters['applies_to']) && in_array($filters['applies_to'], self::APPLIES_TO, true)) {
                $sql .= ' AND applies_to = ?';
                $params[] = $filters['applies_to'];
            }
            if (!empty($filters['active_only'])) {
                $sql .= ' AND is_active = 1';
            }
            if (!empty($filters['exclude_required'])) {
                $sql .= ' AND is_required = 0';
            }
            if (!empty($filters['required_only'])) {
                $sql .= ' AND is_required = 1';
            }

            $sql .= ' ORDER BY is_required DESC, is_active DESC, name ASC';
            return array_map([self::class, 'normalizeServiceRow'], Database::fetchAll($sql, $params));
        }

        $services = array_map([self::class, 'normalizeServiceRow'], Database::getTable('services'));
        $services = array_values(array_filter($services, static function ($service) use ($filters) {
            if (!empty($filters['applies_to']) && ($service['applies_to'] ?? '') !== $filters['applies_to']) {
                return false;
            }
            if (!empty($filters['active_only']) && (int)($service['is_active'] ?? 0) !== 1) {
                return false;
            }
            if (!empty($filters['exclude_required']) && (int)($service['is_required'] ?? 0) === 1) {
                return false;
            }
            if (!empty($filters['required_only']) && (int)($service['is_required'] ?? 0) !== 1) {
                return false;
            }
            return true;
        }));

        usort($services, static function ($a, $b) {
            $requiredCompare = (int)($b['is_required'] ?? 0) <=> (int)($a['is_required'] ?? 0);
            if ($requiredCompare !== 0) {
                return $requiredCompare;
            }

            $activeCompare = (int)($b['is_active'] ?? 0) <=> (int)($a['is_active'] ?? 0);
            if ($activeCompare !== 0) {
                return $activeCompare;
            }

            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return $services;
    }

    /**
     * Lấy chi tiết một dịch vụ theo ID.
     */
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $service = Database::fetchOne('SELECT * FROM services WHERE id = ?', [$id]);
            return $service ? self::normalizeServiceRow($service) : null;
        }

        $service = Database::find('services', $id);
        return $service ? self::normalizeServiceRow($service) : null;
    }

    /**
     * Tạo mới hoặc cập nhật dịch vụ.
     */
    public static function save(array $data, $id = null) {
        $payload = self::normalizeServiceRow($data);
        unset($payload['id']);

        $resolvedId = (int)$id;
        if ($resolvedId > 0) {
            Database::update('services', $payload, 'id = :id', ['id' => $resolvedId]);
            return $resolvedId;
        }

        return (int)Database::insert('services', $payload);
    }

    /**
     * Xóa dịch vụ, nhưng chặn cứng dịch vụ bắt buộc để tránh phá logic tính tiền về sau.
     */
    public static function delete($id) {
        $service = self::getById($id);
        if (!$service) {
            throw new RuntimeException('Dịch vụ không tồn tại hoặc đã bị xóa trước đó.');
        }
        if ((int)($service['is_required'] ?? 0) === 1) {
            throw new RuntimeException('Dịch vụ bắt buộc không thể xóa.');
        }

        Database::delete('room_services', 'service_id = :service_id', ['service_id' => (int)$id]);
        Database::delete('user_services', 'service_id = :service_id', ['service_id' => (int)$id]);
        Database::delete('services', 'id = :id', ['id' => (int)$id]);
    }

    /**
     * Lấy các dịch vụ đã gán cho một phòng.
     */
    public static function getByRoom($roomId) {
        return self::getAssignmentsByRoom($roomId);
    }

    /**
     * Trả danh sách gán dịch vụ theo phòng để admin và dashboard dùng chung.
     */
    public static function getAssignmentsByRoom($roomId) {
        $roomId = (int)$roomId;
        if ($roomId <= 0) {
            return [];
        }

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "SELECT s.*, rs.quantity, rs.registered_at
                 FROM services s
                 INNER JOIN room_services rs ON rs.service_id = s.id
                 WHERE rs.room_id = ?
                 ORDER BY s.name ASC",
                [$roomId]
            );

            return array_map(static function ($row) {
                $service = self::normalizeServiceRow($row);
                $service['quantity'] = max(1, (int)($row['quantity'] ?? 1));
                $service['registered_at'] = $row['registered_at'] ?? null;
                return $service;
            }, $rows);
        }

        $services = [];
        foreach (self::getAll() as $service) {
            $services[(int)$service['id']] = $service;
        }

        $rows = [];
        foreach (Database::getTable('room_services') as $assignment) {
            if ((int)($assignment['room_id'] ?? 0) !== $roomId) {
                continue;
            }

            $service = $services[(int)($assignment['service_id'] ?? 0)] ?? null;
            if (!$service) {
                continue;
            }

            $service['quantity'] = max(1, (int)($assignment['quantity'] ?? 1));
            $service['registered_at'] = $assignment['registered_at'] ?? ($assignment['created_at'] ?? null);
            $rows[] = $service;
        }

        usort($rows, static fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        return $rows;
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

                $service = $services[(int)($row['service_id'] ?? 0)] ?? [];
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
            $resolvedRoomId = (int)($row['room_id'] ?? 0);
            if (!isset($servicesByRoom[$resolvedRoomId])) {
                $servicesByRoom[$resolvedRoomId] = [];
            }
            $servicesByRoom[$resolvedRoomId][] = $row;
        }

        return $servicesByRoom;
    }

    /**
     * Gán dịch vụ loại `room` cho một phòng, nếu đã có thì cập nhật số lượng.
     */
    public static function assignToRoom($roomId, $serviceId, $quantity = 1) {
        $room = RoomModel::getById((int)$roomId);
        if (!$room) {
            throw new RuntimeException('Phòng được chọn không tồn tại.');
        }

        $service = self::getById($serviceId);
        if (!$service) {
            throw new RuntimeException('Dịch vụ không tồn tại.');
        }
        if (($service['applies_to'] ?? '') !== 'room') {
            throw new RuntimeException('Chỉ được gán dịch vụ áp dụng theo phòng.');
        }
        if ((int)($service['is_required'] ?? 0) === 1) {
            throw new RuntimeException('Dịch vụ bắt buộc tự áp cho mọi phòng, không cần gán tay.');
        }
        if ((int)($service['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Không thể gán dịch vụ đang tạm ngừng kinh doanh.');
        }

        $resolvedQuantity = max(1, (int)$quantity);
        $existing = self::findRoomService($roomId, $serviceId);

        if ($existing) {
            Database::update(
                'room_services',
                ['quantity' => $resolvedQuantity],
                'room_id = :room_id AND service_id = :service_id',
                ['room_id' => (int)$roomId, 'service_id' => (int)$serviceId]
            );
            return 'updated';
        }

        Database::insert('room_services', [
            'room_id' => (int)$roomId,
            'service_id' => (int)$serviceId,
            'quantity' => $resolvedQuantity,
            'registered_at' => date('Y-m-d H:i:s'),
        ]);
        return 'created';
    }

    /**
     * Gỡ một dịch vụ đang gán khỏi phòng.
     */
    public static function removeFromRoom($roomId, $serviceId) {
        Database::delete(
            'room_services',
            'room_id = :room_id AND service_id = :service_id',
            ['room_id' => (int)$roomId, 'service_id' => (int)$serviceId]
        );
    }

    /**
     * Lấy các dịch vụ cá nhân mà người dùng đang đăng ký.
     */
    public static function getByUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }

        if (Database::hasConnection()) {
            $rows = Database::fetchAll(
                "SELECT s.*, us.quantity, us.registered_at
                 FROM services s
                 INNER JOIN user_services us ON us.service_id = s.id
                 WHERE us.user_id = ?
                 ORDER BY us.registered_at DESC, s.name ASC",
                [$userId]
            );

            return array_map(static function ($row) {
                $service = self::normalizeServiceRow($row);
                $service['quantity'] = max(1, (int)($row['quantity'] ?? 1));
                $service['registered_at'] = $row['registered_at'] ?? null;
                $service['line_total'] = self::calculateServiceAmount($service, 1);
                return $service;
            }, $rows);
        }

        $services = [];
        foreach (self::getAll() as $service) {
            $services[(int)$service['id']] = $service;
        }

        $rows = [];
        foreach (Database::getTable('user_services') as $registration) {
            if ((int)($registration['user_id'] ?? 0) !== $userId) {
                continue;
            }

            $service = $services[(int)($registration['service_id'] ?? 0)] ?? null;
            if (!$service) {
                continue;
            }

            $service['quantity'] = max(1, (int)($registration['quantity'] ?? 1));
            $service['registered_at'] = $registration['registered_at'] ?? ($registration['created_at'] ?? null);
            $service['line_total'] = self::calculateServiceAmount($service, 1);
            $rows[] = $service;
        }

        usort($rows, static fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        return $rows;
    }

    /**
     * Trả các dịch vụ cá nhân còn mở đăng ký mà user chưa dùng.
     */
    public static function getAvailablePersonalServices($userId) {
        $registeredIds = array_map(
            static fn($service) => (int)($service['id'] ?? 0),
            self::getByUser($userId)
        );

        return array_values(array_filter(
            self::getAll([
                'applies_to' => 'person',
                'active_only' => true,
            ]),
            static fn($service) => !in_array((int)($service['id'] ?? 0), $registeredIds, true)
        ));
    }

    /**
     * Đăng ký dịch vụ cá nhân cho tenant, nếu đã có thì cập nhật số lượng.
     */
    public static function registerForUser($userId, $serviceId, $quantity = 1) {
        $user = UserModel::getById((int)$userId);
        if (!$user) {
            throw new RuntimeException('Người dùng không tồn tại.');
        }

        $service = self::getById($serviceId);
        if (!$service) {
            throw new RuntimeException('Dịch vụ không tồn tại.');
        }
        if (($service['applies_to'] ?? '') !== 'person') {
            throw new RuntimeException('Chỉ được đăng ký dịch vụ áp dụng theo người.');
        }
        if ((int)($service['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Dịch vụ này đang tạm ngừng nhận đăng ký mới.');
        }

        $resolvedQuantity = max(1, (int)$quantity);
        $existing = self::findUserService($userId, $serviceId);

        if ($existing) {
            Database::update(
                'user_services',
                ['quantity' => $resolvedQuantity],
                'user_id = :user_id AND service_id = :service_id',
                ['user_id' => (int)$userId, 'service_id' => (int)$serviceId]
            );
            return 'updated';
        }

        Database::insert('user_services', [
            'user_id' => (int)$userId,
            'service_id' => (int)$serviceId,
            'quantity' => $resolvedQuantity,
            'registered_at' => date('Y-m-d H:i:s'),
        ]);
        return 'created';
    }

    /**
     * Hủy đăng ký dịch vụ cá nhân của tenant.
     */
    public static function unregisterForUser($userId, $serviceId) {
        Database::delete(
            'user_services',
            'user_id = :user_id AND service_id = :service_id',
            ['user_id' => (int)$userId, 'service_id' => (int)$serviceId]
        );
    }

    /**
     * Tính tổng dịch vụ đang gán theo phòng để dashboard cũ tiếp tục hoạt động.
     * Dịch vụ `meter` trả về 0 vì cần chỉ số tháng riêng mới tính đúng.
     */
    public static function getTotalServiceCost($roomId) {
        $occupantCount = max(1, RoomModel::countOccupants((int)$roomId));
        $total = 0;

        foreach (self::getAssignmentsByRoom($roomId) as $service) {
            $total += self::calculateServiceAmount($service, $occupantCount);
        }

        return $total;
    }

    /**
     * Tính thành tiền cho một dòng dịch vụ theo `billing_mode`.
     */
    public static function calculateServiceAmount(array $service, $occupantCount = 1) {
        $price = (float)($service['price'] ?? 0);
        $quantity = max(1, (int)($service['quantity'] ?? 1));
        $resolvedOccupantCount = max(1, (int)$occupantCount);

        switch ($service['billing_mode'] ?? 'fixed') {
            case 'meter':
                return 0.0;
            case 'per_person':
                return $price * $quantity * $resolvedOccupantCount;
            case 'per_unit':
            case 'fixed':
            default:
                return $price * $quantity;
        }
    }

    /**
     * Tìm nhanh một bản ghi gán dịch vụ cho phòng.
     */
    private static function findRoomService($roomId, $serviceId) {
        if (Database::hasConnection()) {
            return Database::fetchOne(
                'SELECT * FROM room_services WHERE room_id = ? AND service_id = ?',
                [(int)$roomId, (int)$serviceId]
            );
        }

        foreach (Database::getTable('room_services') as $row) {
            if ((int)($row['room_id'] ?? 0) === (int)$roomId && (int)($row['service_id'] ?? 0) === (int)$serviceId) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Tìm nhanh một bản ghi đăng ký dịch vụ cá nhân.
     */
    private static function findUserService($userId, $serviceId) {
        if (Database::hasConnection()) {
            return Database::fetchOne(
                'SELECT * FROM user_services WHERE user_id = ? AND service_id = ?',
                [(int)$userId, (int)$serviceId]
            );
        }

        foreach (Database::getTable('user_services') as $row) {
            if ((int)($row['user_id'] ?? 0) === (int)$userId && (int)($row['service_id'] ?? 0) === (int)$serviceId) {
                return $row;
            }
        }
        return null;
    }
}
