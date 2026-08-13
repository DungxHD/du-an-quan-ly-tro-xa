<?php

/**
 * RoomModel ưu tiên giao diện:
 * - Trả dữ liệu đã được ghép/join sẵn để view chỉ việc render.
 * - Khi thiếu DB, mọi dữ liệu được lấy từ lớp fallback của Database.
 */
class RoomModel
{
    private static $settingsCache = null;

    // ========== SETTINGS ==========
    public static function loadSettings()
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        self::$settingsCache = [];
        $rows = Database::hasConnection()
            ? Database::fetchAll('SELECT setting_key, setting_value FROM settings')
            : Database::getTable('settings');

        foreach ($rows as $row) {
            self::$settingsCache[$row['setting_key']] = $row['setting_value'];
        }

        return self::$settingsCache;
    }

    public static function getSetting($key, $default = '')
    {
        self::loadSettings();
        return isset(self::$settingsCache[$key]) && self::$settingsCache[$key] !== ''
            ? self::$settingsCache[$key]
            : $default;
    }

    public static function saveSetting($key, $value)
    {
        Database::saveSetting($key, $value);
        self::$settingsCache = null;
    }

    /**
     * Xóa cache settings để các màn hình public/admin đọc lại dữ liệu mới nhất ngay sau khi admin lưu.
     */
    public static function resetSettingsCache()
    {
        self::$settingsCache = null;
    }

    public static function getSettingsByGroup($group)
    {
        if (Database::hasConnection()) {
            return Database::fetchAll(
                'SELECT * FROM settings WHERE setting_group = ? ORDER BY setting_key ASC',
                [$group]
            );
        }

        $rows = array_filter(
            Database::getTable('settings'),
            static fn($row) => ($row['setting_group'] ?? '') === $group
        );
        usort($rows, static fn($a, $b) => strcmp($a['setting_key'], $b['setting_key']));
        return array_values($rows);
    }


    // ========== ROOMS ==========
    public static function getAvailableOrUpcoming($limit = 6)
    {
        $rooms = self::getAll();
        $rooms = array_filter($rooms, static function ($room) {
            return ($room['status'] ?? '') === 'available'
                || ((int)($room['notice_given'] ?? 0) === 1 && ($room['status'] ?? '') === 'rented');
        });

        usort($rooms, static function ($a, $b) {
            $aPriority = ($a['status'] ?? '') === 'available' ? 0 : 1;
            $bPriority = ($b['status'] ?? '') === 'available' ? 0 : 1;

            if ($aPriority === $bPriority) {
                return (int)($b['views'] ?? 0) <=> (int)($a['views'] ?? 0);
            }

            return $aPriority <=> $bPriority;
        });

        return array_slice(array_values($rooms), 0, $limit);
    }

    /**
     * Chuẩn hoá bộ lọc cho trang public để controller và view dùng chung một cấu trúc.
     * Giá hỗ trợ nhập ngắn như "2", "2.5", "1500", "2tr",...
     */
    public static function normalizePublicFilters($filters = [])
    {
        $featureMap = [];
        foreach (self::getPublicFeatureOptions() as $feature) {
            $featureMap[$feature['key']] = $feature;
        }

        $selectedFeatures = $filters['amenities'] ?? ($filters['services'] ?? []);
        if (!is_array($selectedFeatures)) {
            $selectedFeatures = [$selectedFeatures];
        }
        $selectedFeatures = array_values(array_filter(array_map('trim', $selectedFeatures), static fn($key) => isset($featureMap[$key])));

        $normalized = [
            'area_id' => (int)($filters['area_id'] ?? ($filters['building_id'] ?? 0)),
            'min_price' => null,
            'max_price' => null,
            'min_price_input' => trim((string)($filters['min_price'] ?? '')),
            'max_price_input' => trim((string)($filters['max_price'] ?? '')),
            'amenities' => $selectedFeatures,
            'messages' => [],
        ];

        $normalized['min_price'] = self::parseHumanPrice($normalized['min_price_input']);
        $normalized['max_price'] = self::parseHumanPrice($normalized['max_price_input']);

        // Ràng buộc giá tối thiểu 500.000đ
        if ($normalized['min_price'] !== null && $normalized['min_price'] < 500000) {
            $normalized['min_price'] = 500000;
            $normalized['messages'][] = 'Giá tối thiểu là 500.000đ, hệ thống đã tự điều chỉnh.';
        }

        // Ràng buộc khoảng cách giá tối thiểu 500.000đ
        if ($normalized['min_price'] !== null && $normalized['max_price'] !== null) {
            $minimumGap = 500000;
            if ($normalized['max_price'] < $normalized['min_price'] + $minimumGap) {
                $normalized['max_price'] = $normalized['min_price'] + $minimumGap;
                $normalized['messages'][] = 'Khoảng giá tối thiểu là 500.000đ nên hệ thống đã tự nới giá kết thúc cho phù hợp.';
            }
        }

        // Nếu chỉ có max_price mà không có min_price, và max_price < 500k → nâng lên 500k
        if ($normalized['min_price'] === null && $normalized['max_price'] !== null && $normalized['max_price'] < 500000) {
            $normalized['max_price'] = 500000;
            $normalized['messages'][] = 'Giá tối thiểu là 500.000đ, hệ thống đã tự điều chỉnh giá kết thúc.';
        }

        $normalized['min_price_display'] = self::formatPriceInput($normalized['min_price']);
        $normalized['max_price_display'] = self::formatPriceInput($normalized['max_price']);
        return $normalized;
    }

    /**
     * Danh sách phòng public chỉ hiển thị phòng còn trống để tránh lộ dữ liệu
     * các phòng đã kín hoặc đang bảo trì.
     */
    public static function getPublicCatalog($filters = [])
    {
        $normalized = self::normalizePublicFilters($filters);
        $rooms = Database::hasConnection()
            ? self::getPublicCatalogRowsFromDatabase($normalized)
            : self::getPublicCatalogRowsFromFallback($normalized);

        $rooms = self::attachPublicCatalogMeta($rooms);
        $rooms = array_values(array_filter($rooms, static function ($room) use ($normalized) {
            foreach ($normalized['amenities'] as $featureKey) {
                if (!self::roomMatchesFeature($room, $featureKey)) {
                    return false;
                }
            }

            return true;
        }));

        usort($rooms, static function ($a, $b) {
            $viewCompare = (int)($b['views'] ?? 0) <=> (int)($a['views'] ?? 0);
            if ($viewCompare !== 0) {
                return $viewCompare;
            }

            return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
        });

        return $rooms;
    }

    /**
     * Lấy danh sách phòng và join ngược lên `floors -> areas`.
     * Đồng thời giữ lại alias `building_*` để các màn hình cũ chưa refactor hết vẫn dùng được.
     */
    public static function getAll($filters = [])
    {
        $areaFilterId = (int)($filters['area_id'] ?? ($filters['building_id'] ?? 0));

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    r.*,
                    f.area_id,
                    f.name AS floor_name,
                    f.floor_number,
                    a.name AS area_name,
                    a.image AS area_image,
                    a.id AS building_id,
                    a.name AS building_name,
                    'area' AS building_type
                FROM rooms r
                INNER JOIN floors f ON f.id = r.floor_id
                INNER JOIN areas a ON a.id = f.area_id
                WHERE 1 = 1
            ";
            $params = [];

            if (!empty($filters['status'])) {
                $sql .= ' AND r.status = ?';
                $params[] = $filters['status'];
            }
            if ($areaFilterId > 0) {
                $sql .= ' AND f.area_id = ?';
                $params[] = $areaFilterId;
            }
            if (!empty($filters['floor_id'])) {
                $sql .= ' AND r.floor_id = ?';
                $params[] = (int)$filters['floor_id'];
            }
            if (!empty($filters['search'])) {
                $sql .= ' AND r.name LIKE ?';
                $params[] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['min_price'])) {
                $sql .= ' AND r.price >= ?';
                $params[] = (float)$filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $sql .= ' AND r.price <= ?';
                $params[] = (float)$filters['max_price'];
            }

            $sql .= ' ORDER BY a.name ASC, f.floor_number ASC, r.id DESC';
            return array_map([self::class, 'normalizeJoinedRoom'], Database::fetchAll($sql, $params));
        }

        $floors = [];
        foreach (Database::getTable('floors') as $floor) {
            $floors[(int)($floor['id'] ?? 0)] = $floor;
        }

        $areas = [];
        foreach (Database::getTable('areas') as $area) {
            $areas[(int)($area['id'] ?? 0)] = $area;
        }

        $rooms = array_map(static function ($room) use ($floors, $areas) {
            $floor = $floors[(int)($room['floor_id'] ?? 0)] ?? [];
            $area = $areas[(int)($floor['area_id'] ?? 0)] ?? [];

            $room['area_id'] = (int)($floor['area_id'] ?? 0);
            $room['area_name'] = $area['name'] ?? 'Chưa có khu';
            $room['area_image'] = $area['image'] ?? '';
            $room['building_id'] = (int)($area['id'] ?? ($room['building_id'] ?? 0));
            $room['building_name'] = $area['name'] ?? 'Chưa có khu';
            $room['building_type'] = 'area';
            $room['floor_name'] = $floor['name'] ?? 'Chưa có tầng';
            $room['floor_number'] = (int)($floor['floor_number'] ?? ($room['floor'] ?? 0));
            return self::normalizeJoinedRoom($room);
        }, Database::getTable('rooms'));

        $rooms = array_filter($rooms, static function ($room) use ($filters, $areaFilterId) {
            if (!empty($filters['status']) && ($room['status'] ?? '') !== $filters['status']) {
                return false;
            }
            if ($areaFilterId > 0 && (int)($room['area_id'] ?? 0) !== $areaFilterId) {
                return false;
            }
            if (!empty($filters['floor_id']) && (int)($room['floor_id'] ?? 0) !== (int)$filters['floor_id']) {
                return false;
            }
            if (!empty($filters['search']) && stripos($room['name'] ?? '', $filters['search']) === false) {
                return false;
            }
            if (!empty($filters['min_price']) && (float)($room['price'] ?? 0) < (float)$filters['min_price']) {
                return false;
            }
            if (!empty($filters['max_price']) && (float)($room['price'] ?? 0) > (float)$filters['max_price']) {
                return false;
            }
            return true;
        });

        usort($rooms, static function ($left, $right) {
            $areaCompare = strcmp((string)($left['area_name'] ?? ''), (string)($right['area_name'] ?? ''));
            if ($areaCompare !== 0) {
                return $areaCompare;
            }

            $floorCompare = (int)($left['floor_number'] ?? 0) <=> (int)($right['floor_number'] ?? 0);
            if ($floorCompare !== 0) {
                return $floorCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rooms);
    }

    public static function getById($id, $options = [])
    {
        $id = (int)$id;
        $publicOnly = (bool)($options['public_only'] ?? false);
        if ($id <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    r.*,
                    f.area_id,
                    f.name AS floor_name,
                    f.floor_number,
                    a.name AS area_name,
                    a.image AS area_image,
                    a.id AS building_id,
                    a.name AS building_name,
                    'area' AS building_type
                FROM rooms r
                INNER JOIN floors f ON f.id = r.floor_id
                INNER JOIN areas a ON a.id = f.area_id
                WHERE r.id = ?
            ";
            $params = [$id];

            if ($publicOnly) {
                $sql .= " AND r.status = 'available'";
            }

            $room = Database::fetchOne($sql, $params);
            if (!$room) {
                return null;
            }

            $room = self::normalizeJoinedRoom($room);
            $room['images'] = self::getRoomImages($id);
            return self::hydrateRoomDetail($room);
        }

        foreach (self::getAll() as $room) {
            if ((int)($room['id'] ?? 0) !== $id) {
                continue;
            }

            if ($publicOnly && ($room['status'] ?? '') !== 'available') {
                return null;
            }

            return self::hydrateRoomDetail($room);
        }

        return null;
    }

    /**
     * Lưu phòng theo schema mới: `rooms.floor_id` là bắt buộc và không còn `area_id`.
     */
    public static function save($data, $id = null)
    {
        $payload = [
            'floor_id'      => (int)($data['floor_id'] ?? 0),
            'name'          => trim((string)($data['name'] ?? '')),
            'position'      => (int)($data['position'] ?? 0),
            'price'         => (float)($data['price'] ?? 0),
            'area'          => (float)($data['area'] ?? 0),
            'max_occupancy' => (int)($data['max_occupancy'] ?? 2),
            'description' => trim((string)($data['description'] ?? '')),
            'amenities' => trim((string)($data['amenities'] ?? '')),
            'status' => $data['status'] ?? 'available',
            'thumbnail' => trim((string)($data['thumbnail'] ?? '')) ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900',
        ];
        if ($id) {
            Database::update('rooms', $payload, 'id = :id', ['id' => (int)$id]);
            return (int)$id;
        }
        return (int)Database::insert('rooms', $payload);
    }

    /**
     * Kiểm tra tầng có tồn tại hay không để controller validate trước khi lưu.
     */
    public static function floorExists($floorId)
    {
        return FloorModel::getById((int)$floorId) !== null;
    }

    /**
     * Đếm số người đang được gán trực tiếp vào phòng.
     * Đây là chốt chặn tối thiểu trước khi cho phép xóa phòng.
     */
    public static function countOccupants($roomId)
    {
        $roomId = (int)$roomId;

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "SELECT COUNT(*) AS total FROM contracts WHERE room_id = ? AND status = 'active'",
                [$roomId]
            );

            return (int)($row['total'] ?? 0);
        }

        return count(array_filter(
            Database::getTable('users'),
            static fn($user) => (int)($user['room_id'] ?? 0) === $roomId
        ));
    }

    /**
     * Trả về true khi phòng vẫn còn người ở để controller chặn thao tác xóa.
     */
    public static function hasActiveOccupants($roomId)
    {
        return self::countOccupants($roomId) > 0;
    }

    /**
     * Cập nhật nhanh trạng thái phòng cho dropdown thao tác tại danh sách admin.
     */
    public static function updateStatus($id, $status)
    {
        Database::update(
            'rooms',
            ['status' => $status],
            'id = :id',
            ['id' => (int)$id]
        );
    }

    /**
     * Xóa phòng theo ID.
     */
    public static function delete($id)
    {
        Database::delete('rooms', 'id = :id', ['id' => (int)$id]);
    }


    public static function incrementViews($id)
    {
        $room = self::getById($id);
        if (!$room) {
            return;
        }
        Database::update('rooms', ['views' => (int)($room['views'] ?? 0) + 1], 'id = :id', ['id' => (int)$id]);
    }

    public static function count()
    {
        return count(self::getAll());
    }

    public static function countByStatus($status)
    {
        $resolvedStatus = trim((string)$status);
        if ($resolvedStatus === '') {
            return 0;
        }

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                'SELECT COUNT(*) AS total FROM rooms WHERE status = ?',
                [$resolvedStatus]
            );
            return (int)($row['total'] ?? 0);
        }

        return count(array_filter(
            Database::getTable('rooms'),
            static fn($room) => (string)($room['status'] ?? '') === $resolvedStatus
        ));
    }

    /**
     * Chuẩn hóa dữ liệu phòng sau khi join để DB thật và fallback trả về cùng shape.
     */
    private static function normalizeJoinedRoom($room)
    {
        $room['floor_id'] = (int)($room['floor_id'] ?? 0);
        $room['area_id'] = (int)($room['area_id'] ?? 0);
        $room['building_id'] = (int)($room['building_id'] ?? ($room['area_id'] ?? 0));
        $room['floor_number'] = (int)($room['floor_number'] ?? ($room['floor'] ?? 0));
        $room['floor'] = $room['floor_number'];
        $room['price'] = (float)($room['price'] ?? 0);
        $room['area'] = (float)($room['area'] ?? 0);
        $room['max_occupancy'] = (int)($room['max_occupancy'] ?? 0);
        $room['views'] = (int)($room['views'] ?? 0);
        $room['thumbnail'] = trim((string)($room['thumbnail'] ?? '')) ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900';
        $rawAmenities = $room['amenities'] ?? null;
        $decodedAmenities = is_string($rawAmenities) ? json_decode($rawAmenities, true) : null;
        $room['amenities_list'] = is_array($decodedAmenities)
            ? array_values(array_filter(array_map(static fn($a) => trim((string)$a), $decodedAmenities), static fn($a) => $a !== ''))
            : [];
        $room['area_name'] = $room['area_name'] ?? ($room['building_name'] ?? 'Chưa có khu');
        $room['area_image'] = trim((string)($room['area_image'] ?? ''));
        $room['building_name'] = $room['building_name'] ?? $room['area_name'];
        $room['floor_name'] = $room['floor_name'] ?? ('Tầng ' . $room['floor_number']);
        $room['building_type'] = $room['building_type'] ?? 'area';
        return $room;
    }

    /**
     * Ảnh phụ của phòng (bảng room_images). Ảnh chính nằm trong rooms.thumbnail.
     */
    public static function getRoomImages($roomId) {
        $roomId = (int)$roomId;
        if ($roomId <= 0 || !Database::hasConnection()) {
            return [];
        }
        $rows = Database::fetchAll(
            'SELECT id, image_url AS url, is_primary, sort_order FROM room_images WHERE room_id = ? ORDER BY sort_order ASC, id ASC',
            [$roomId]
        );
        return array_map(static function ($row) {
            return [
                'id' => (int)($row['id'] ?? 0),
                'url' => trim((string)($row['url'] ?? '')),
                'is_primary' => (int)($row['is_primary'] ?? 0),
                'sort_order' => (int)($row['sort_order'] ?? 0),
            ];
        }, is_array($rows) ? $rows : []);
    }

    public static function getTotalRevenue() {
        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "SELECT COALESCE(SUM(price), 0) AS total_revenue FROM rooms WHERE status = 'rented'"
            );
            return (float)($row['total_revenue'] ?? 0);
        }

        return array_reduce(
            Database::getTable('rooms'),
            static function ($carry, $room) {
                if ((string)($room['status'] ?? '') !== 'rented') {
                    return $carry;
                }

                return $carry + (float)($room['price'] ?? 0);
            },
            0.0
        );
    }

    /**
     * Thống kê số phòng theo từng khu để admin xem tổng phòng, phòng trống và tỷ lệ lấp đầy.
     * Có hỗ trợ lọc 1 khu cụ thể để tái sử dụng cho trang thống kê.
     */
    public static function getStatsByArea($areaId = 0)
    {
        $resolvedAreaId = (int)$areaId;

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    a.id,
                    a.name,
                    COUNT(r.id) AS total_rooms,
                    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) AS available_rooms,
                    SUM(CASE WHEN r.status = 'rented' THEN 1 ELSE 0 END) AS rented_rooms,
                    SUM(CASE WHEN r.status = 'draft' THEN 1 ELSE 0 END) AS draft_rooms,
                    COUNT(DISTINCT f.id) AS total_floors
                FROM areas a
                LEFT JOIN floors f ON f.area_id = a.id
                LEFT JOIN rooms r ON r.floor_id = f.id
                WHERE 1 = 1
            ";
            $params = [];

            if ($resolvedAreaId > 0) {
                $sql .= ' AND a.id = ?';
                $params[] = $resolvedAreaId;
            }

            $sql .= ' GROUP BY a.id ORDER BY a.name ASC';
            return array_map([self::class, 'normalizeAreaStatRow'], Database::fetchAll($sql, $params));
        }

        $floors = Database::getTable('floors');
        $rooms = Database::getTable('rooms');
        $rows = array_map(static function ($area) use ($floors, $rooms, $resolvedAreaId) {
            if ($resolvedAreaId > 0 && (int)($area['id'] ?? 0) !== $resolvedAreaId) {
                return null;
            }

            $areaFloors = array_values(array_filter(
                $floors,
                static fn($floor) => (int)($floor['area_id'] ?? 0) === (int)($area['id'] ?? 0)
            ));
            $floorIds = array_map(static fn($floor) => (int)($floor['id'] ?? 0), $areaFloors);
            $areaRooms = array_values(array_filter(
                $rooms,
                static fn($room) => in_array((int)($room['floor_id'] ?? 0), $floorIds, true)
            ));

            return self::normalizeAreaStatRow([
                'id' => (int)($area['id'] ?? 0),
                'name' => $area['name'] ?? 'Chưa có khu',
                'total_rooms' => count($areaRooms),
                'available_rooms' => count(array_filter($areaRooms, static fn($room) => (string)($room['status'] ?? '') === 'available')),
                'rented_rooms' => count(array_filter($areaRooms, static fn($room) => (string)($room['status'] ?? '') === 'rented')),
                'draft_rooms' => count(array_filter($areaRooms, static fn($room) => (string)($room['status'] ?? '') === 'draft')),
                'total_floors' => count($areaFloors),
            ]);
        }, Database::getTable('areas'));

        $rows = array_values(array_filter($rows));
        usort($rows, static fn($left, $right) => strcmp((string)($left['name'] ?? ''), (string)($right['name'] ?? '')));
        return $rows;
    }

    public static function getDaysUntilVacant($dateStr)
    {
        if (!$dateStr) {
            return null;
        }
        $now = new DateTime();
        $target = new DateTime($dateStr);
        $diff = $now->diff($target);
        return $diff->invert ? 0 : $diff->days;
    }

/**
     * Danh sách tiện ích chuẩn dùng chung cho admin, public filter, detail page.
     * Mỗi item: key (dùng để lưu DB/filter), label (hiển thị), icon (material-symbols).
     */
    public static function getCanonicalAmenities()
    {
        return [
            ['key' => 'dieu_hoa',  'label' => 'Điều hòa',   'icon' => 'ac_unit'],
            ['key' => 'nong_lanh', 'label' => 'Nóng lạnh',  'icon' => 'water_heater'],
            ['key' => 'tu_lanh',   'label' => 'Tủ lạnh',    'icon' => 'kitchen'],
            ['key' => 'giuong',    'label' => 'Giường',     'icon' => 'bed'],
            ['key' => 'ban_ghe',   'label' => 'Bàn ghế',    'icon' => 'chair'],
            ['key' => 'tu_quan_ao','label' => 'Tủ quần áo', 'icon' => 'checkroom'],
            ['key' => 'may_giat',  'label' => 'Máy giặt',   'icon' => 'local_laundry_service'],
            ['key' => 'wifi',      'label' => 'Wifi',       'icon' => 'wifi'],
        ];
    }

    /**
     * Lấy các key tiện ích chuẩn (dùng cho validation, filter).
     */
    public static function getCanonicalAmenityKeys()
    {
        return array_column(self::getCanonicalAmenities(), 'key');
    }

    /**
     * Bộ lọc tiện ích cho trang public - trả về TẤT CẢ 8 tiện ích chuẩn.
     * Mỗi item: key (dùng để lưu DB/filter), label (hiển thị), icon (material-symbols).
     * KHÔNG còn dùng aliases - match chính xác key trong cột rooms.amenities.
     */
    public static function getPublicFeatureOptions()
    {
        return self::getCanonicalAmenities();
    }

    /**
     * Bổ sung metadata chỉ phục vụ trang public để view không cần suy diễn thêm.
     */
    private static function attachPublicCatalogMeta(array $rooms)
    {
        $roomIds = array_map(static fn($room) => (int)($room['id'] ?? 0), $rooms);
        $serviceMap = ServiceModel::getRoomServiceMap($roomIds);
        $canonicalAmenities = self::getCanonicalAmenities();
        $amenityMap = [];
        foreach ($canonicalAmenities as $a) {
            $amenityMap[$a['key']] = ['key' => $a['key'], 'label' => $a['label'], 'icon' => $a['icon']];
        }

        return array_map(static function ($room) use ($serviceMap, $amenityMap) {
            $roomId = (int)($room['id'] ?? 0);
            $services = $serviceMap[$roomId] ?? [];
            $serviceNames = array_values(array_filter(array_map(static fn($service) => trim((string)($service['name'] ?? '')), $services)));
            $isUpcoming = self::isUpcomingVacancy($room);
            $expectedVacantText = $isUpcoming ? self::formatExpectedVacantText($room['expected_vacant_date'] ?? null) : '';

            // Tạo amenity_list: chỉ những key trong rooms.amenities có trong canonical
            $rawAmenities = trim((string)($room['amenities'] ?? ''));
            $roomAmenityKeys = $rawAmenities !== '' ? array_values(array_filter(array_map('trim', explode(',', $rawAmenities)), static fn($k) => $k !== '')) : [];
            $amenityList = [];
            foreach ($roomAmenityKeys as $key) {
                if (isset($amenityMap[$key])) {
                    $amenityList[] = $amenityMap[$key];
                }
            }

            $room['service_names'] = array_values(array_filter(array_map(static fn($service) => trim((string)($service['name'] ?? '')), $services)));
            $room['amenity_list'] = $amenityList; // mảng [{key, label, icon}, ...]
            $room['isUpcoming'] = $isUpcoming;
            $room['daysLeft'] = $isUpcoming ? self::getDaysUntilVacant($room['expected_vacant_date'] ?? null) : null;
            $room['expectedVacantText'] = $expectedVacantText;
            $room['availabilityLabel'] = ($room['status'] ?? '') === 'available'
                ? 'Trống ngay'
                : ($isUpcoming ? 'Trống từ ' . self::formatCompactDate($room['expected_vacant_date'] ?? null) : 'Đã thuê');
            $room['availabilityClass'] = ($room['status'] ?? '') === 'available'
                ? 'bg-green-500'
                : ($isUpcoming ? 'bg-amber-500' : 'bg-gray-500');
            $room['availabilityNote'] = ($room['status'] ?? '') === 'available'
                ? 'Có thể vào ở ngay'
                : ($isUpcoming && $expectedVacantText !== '' ? 'Dự kiến trống từ ' . $expectedVacantText : 'Hiện chưa mở cho thuê');

            return $room;
        }, $rooms);
    }

    private static function isUpcomingVacancy(array $room)
    {
        return (int)($room['notice_given'] ?? 0) === 1
            && ($room['status'] ?? '') === 'rented'
            && !empty($room['expected_vacant_date']);
    }

    private static function roomMatchesFeature(array $room, $featureKey)
    {
        // Chỉ match chính xác KEY trong cột rooms.amenities (chuỗi comma-separated các KEY)
        // KHÔNG tìm kiếm trong description, service_names hay dùng aliases
        $raw = trim((string)($room['amenities'] ?? ''));
        if ($raw === '') {
            return false;
        }
        // Tách các key, trim, loại bỏ rỗng
        $keys = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn($k) => $k !== ''));
        return in_array($featureKey, $keys, true);
    }

    /**
     * Parse giá từ text người dùng nhập thành số nguyên VNĐ.
     * Hỗ trợ: "2 triệu", "2.5 triệu", "2,5 triệu", "3 triệu", "2.1 triệu", "2.7 triệu", "3,3 triệu", "1.5tr", "2tr5",
     *         "1500k", "1500 nghìn", "500k", "2000000", "500000", "500" (hiểu là 500k)
     * Quy tắc:
     * 1. Bỏ khoảng trắng, "vnđ", "vnd", "đ", "đồng"
     * 2. Chứa "triệu"/"tr"/"m" → nhân 1,000,000 (hỗ trợ dấu chấm/phẩy thập phân, cả "2tr5" = 2.5 triệu)
     * 3. Chứa "nghìn"/"k"/"ngàn" → nhân 1,000
     * 4. Số thuần: < 100 → triệu (VD: "2" = 2 triệu); 100-99999 → nghìn (VD: "500" = 500k, "1500" = 1.5tr); >= 100000 → nguyên
     * 5. Làm tròn hàng trăm nghìn (nếu chưa chia hết 100,000)
     */
    private static function parseHumanPrice($value)
    {
        $rawValue = trim((string)$value);
        if ($rawValue === '') {
            return null;
        }

        $normalized = mb_strtolower($rawValue, 'UTF-8');
        // Loại bỏ ký tự tiền tệ và khoảng trắng
        $normalized = str_replace(['vnđ', 'vnd', 'đ', 'dong', ' ', 'đồng'], '', $normalized);

        // Xác định đơn vị nhân
        $multiplier = 1;
        $hasTrieu = false;
        $hasNghin = false;

        // 2tr5 → 2.5 triệu: detect "tr" followed by digits
        if (preg_match('/tr(\d+)/', $normalized, $m)) {
            $normalized = preg_replace('/tr(\d+)/', '.tr$1', $normalized);
        }
        $hasTrieu = str_contains($normalized, 'triệu') || str_contains($normalized, 'tr') || str_contains($normalized, 'm');
        $hasNghin = str_contains($normalized, 'nghìn') || str_contains($normalized, 'nghin') || str_contains($normalized, 'ngàn') || str_contains($normalized, 'k');

        if ($hasTrieu) {
            $multiplier = 1000000;
            $normalized = str_replace(['triệu', 'tr', 'm'], '', $normalized);
        } elseif ($hasNghin) {
            $multiplier = 1000;
            $normalized = str_replace(['nghìn', 'nghin', 'ngàn', 'k'], '', $normalized);
        }

        // Lấy phần số (giữ lại chữ số, dấu chấm, dấu phẩy)
        $numberText = preg_replace('/[^0-9\.,]/', '', $normalized);
        if ($numberText === '') {
            return null;
        }

        // Chuẩn hóa dấu thập phân: thay dấu phẩy bằng dấu chấm
        $numberText = str_replace(',', '.', $numberText);
        $number = (float)$numberText;
        if ($number <= 0) {
            return null;
        }

        // Nếu chưa xác định multiplier qua từ khóa, đoán theo quy tắc số thuần
        if (!$hasTrieu && !$hasNghin) {
            if ($number < 100) {
                $multiplier = 1000000; // "2" = 2 triệu, "50" = 50 triệu
            } elseif ($number < 100000) {
                $multiplier = 1000;    // "500" = 500k, "1500" = 1.5tr, "9999" = 9.999tr
            } else {
                $multiplier = 1;       // "2000000" = 2.000.000
            }
        }

        $price = (int)round($number * $multiplier);

        // Làm tròn đến hàng trăm nghìn nếu chưa chia hết
        $remainder = $price % 100000;
        if ($remainder !== 0) {
            $price = $price - $remainder + 100000;
        }

        return $price;
    }

    public static function formatPriceInput($price)
    {
        if ($price === null || (float)$price <= 0) {
            return '';
        }

        $price = (float)$price;
        if ($price >= 1000000) {
            $millionValue = $price / 1000000;
            $formatted = floor($millionValue) == $millionValue
                ? number_format($millionValue, 0, ',', '.')
                : number_format($millionValue, 1, '.', '');
            return rtrim(rtrim($formatted, '0'), '.') . ' triệu';
        }

        return number_format($price / 1000, 0, ',', '.') . ' nghìn';
    }

    public static function formatExpectedVacantText($dateStr)
    {
        if (!$dateStr) {
            return '';
        }
        return date('d/m/Y', strtotime($dateStr));
    }

    /**
     * Dùng định dạng ngày ngắn cho badge để nội dung không bị dài và vỡ layout thẻ phòng.
     */
    private static function formatCompactDate($dateStr)
    {
        if (!$dateStr) {
            return '';
        }
        return date('d/m', strtotime($dateStr));
    }

    // ========== AMENITIES ==========
    public static function getAmenities()
    {
        return AmenityModel::getAllActive();
    }

    // ========== COMMENTS ==========
    public static function getCommentsByRoom($roomId)
    {
        if (Database::hasConnection()) {
            $comments = Database::fetchAll(
                "
                SELECT c.*, u.full_name, u.avatar
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.room_id = ? AND c.status = 1 AND c.is_spam = 0
                ORDER BY c.rating DESC, c.is_spam ASC, c.toxicity_score ASC, c.created_at DESC
                ",
                [$roomId]
            );

            return array_map([self::class, 'normalizePublicComment'], $comments);
        }

        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[$user['id']] = $user;
        }

        $comments = array_filter(Database::getTable('comments'), static function ($comment) use ($roomId) {
            return (int)($comment['room_id'] ?? 0) === (int)$roomId
                && (int)($comment['status'] ?? 0) === 1
                && (int)($comment['is_spam'] ?? 0) === 0;
        });

        $comments = array_map(static function ($comment) use ($users) {
            $user = $users[$comment['user_id']] ?? [];
            $comment['full_name'] = $user['full_name'] ?? 'Cư dân';
            $comment['avatar'] = $user['avatar'] ?? '';
            return self::normalizePublicComment($comment);
        }, $comments);

        usort($comments, static function ($left, $right) {
            $ratingCompare = (int)($right['rating'] ?? 0) <=> (int)($left['rating'] ?? 0);
            if ($ratingCompare !== 0) {
                return $ratingCompare;
            }

            $spamCompare = (int)($left['is_spam'] ?? 0) <=> (int)($right['is_spam'] ?? 0);
            if ($spamCompare !== 0) {
                return $spamCompare;
            }

            $toxicityCompare = (float)($left['toxicity_score'] ?? 0) <=> (float)($right['toxicity_score'] ?? 0);
            if ($toxicityCompare !== 0) {
                return $toxicityCompare;
            }

            return strcmp((string)($right['created_at'] ?? ''), (string)($left['created_at'] ?? ''));
        });
        return array_values($comments);
    }

    /**
     * Truy vấn danh sách phòng public theo schema mới `areas -> floors -> rooms`.
     */
    private static function getPublicCatalogRowsFromDatabase(array $filters)
    {
        $sql = "
            SELECT
                r.*,
                f.area_id,
                f.name AS floor_name,
                f.floor_number,
                a.name AS area_name,
                a.image AS area_image,
                a.id AS building_id,
                a.name AS building_name,
                'area' AS building_type
            FROM rooms r
            INNER JOIN floors f ON f.id = r.floor_id
            INNER JOIN areas a ON a.id = f.area_id
            WHERE r.status = 'available'
        ";
        $params = [];

        if ((int)($filters['area_id'] ?? 0) > 0) {
            $sql .= ' AND f.area_id = ?';
            $params[] = (int)$filters['area_id'];
        }
        if (($filters['min_price'] ?? null) !== null) {
            $sql .= ' AND r.price >= ?';
            $params[] = (float)$filters['min_price'];
        }
        if (($filters['max_price'] ?? null) !== null) {
            $sql .= ' AND r.price <= ?';
            $params[] = (float)$filters['max_price'];
        }

        $sql .= ' ORDER BY r.views DESC, r.id DESC';
        return array_map([self::class, 'normalizeJoinedRoom'], Database::fetchAll($sql, $params));
    }

    /**
     * Fallback public catalog mô phỏng cùng shape dữ liệu với DB thật.
     */
    private static function getPublicCatalogRowsFromFallback(array $filters)
    {
        $rooms = array_filter(self::getAll(), static function ($room) use ($filters) {
            if (($room['status'] ?? '') !== 'available') {
                return false;
            }
            if ((int)($filters['area_id'] ?? 0) > 0 && (int)($room['area_id'] ?? 0) !== (int)$filters['area_id']) {
                return false;
            }
            if (($filters['min_price'] ?? null) !== null && (float)($room['price'] ?? 0) < (float)$filters['min_price']) {
                return false;
            }
            if (($filters['max_price'] ?? null) !== null && (float)($room['price'] ?? 0) > (float)$filters['max_price']) {
                return false;
            }

            return true;
        });

        return array_values($rooms);
    }

    /**
     * Bổ sung dữ liệu tiện ích, gallery và nhãn trạng thái cho trang chi tiết public.
     */
    private static function hydrateRoomDetail(array $room)
    {
        $room = self::attachPublicCatalogMeta([$room])[0];
        $room['services'] = ServiceModel::getByRoom((int)($room['id'] ?? 0));
        $room['gallery_images'] = self::buildGalleryImages($room);
        $room['location_label'] = trim(implode(' • ', array_filter([
            $room['area_name'] ?? '',
            $room['floor_name'] ?? '',
        ])));

        return $room;
    }

    /**
     * Gallery tận dụng ảnh thật đang có trong hệ thống, tránh lặp thumb vô nghĩa.
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-08]
     * Gallery ưu tiên đọc từ bảng `room_images`:
     * ảnh chính (is_primary=1) đứng đầu, rồi đến ảnh phụ theo sort_order.
     * Chỉ fallback về thumbnail/area_image khi phòng chưa có ảnh trong room_images.
     */
    private static function buildGalleryImages(array $room)
    {
        $roomId = (int)($room['id'] ?? 0);
        $images = [];

        if ($roomId > 0) {
            $rows = [];
            if (Database::hasConnection()) {
                try {
                    $rows = Database::fetchAll(
                        'SELECT image_url, is_primary, sort_order, id
                     FROM room_images
                     WHERE room_id = ?
                     ORDER BY is_primary DESC, sort_order ASC, id ASC',
                        [$roomId]
                    );
                } catch (Throwable $exception) {
                    $rows = []; // bảng room_images chưa tồn tại => giữ hành vi cũ, không fatal
                }
            } else {
                $rows = array_values(array_filter(
                    Database::getTable('room_images'),
                    static fn($row) => (int)($row['room_id'] ?? 0) === $roomId
                ));
                usort($rows, static function ($left, $right) {
                    $primaryCompare = (int)($right['is_primary'] ?? 0) <=> (int)($left['is_primary'] ?? 0);
                    if ($primaryCompare !== 0) {
                        return $primaryCompare;
                    }
                    $sortCompare = (int)($left['sort_order'] ?? 0) <=> (int)($right['sort_order'] ?? 0);
                    if ($sortCompare !== 0) {
                        return $sortCompare;
                    }
                    return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
                });
            }

            foreach ($rows as $row) {
                $url = trim((string)($row['image_url'] ?? ''));
                if ($url !== '') {
                    $images[] = $url;
                }
            }
        }

        // Fallback: phòng chưa upload ảnh nào vào room_images
        if (empty($images)) {
            $images = array_values(array_filter(array_unique([
                trim((string)($room['thumbnail'] ?? '')),
                trim((string)($room['area_image'] ?? '')),
            ])));
        }

        if (empty($images)) {
            $images[] = 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900';
        }

        return array_values(array_unique($images));
    }

    /**
     * Chuẩn hoá bình luận công khai để view không cần tự vá dữ liệu thiếu.
     */
    private static function normalizePublicComment(array $comment)
    {
        $comment['full_name'] = trim((string)($comment['full_name'] ?? '')) ?: 'Khách thuê';
        $comment['avatar'] = trim((string)($comment['avatar'] ?? ''));
        $comment['content'] = trim((string)($comment['content'] ?? ''));
        $comment['rating'] = max(1, min(5, (int)($comment['rating'] ?? 5)));
        $comment['is_spam'] = (int)($comment['is_spam'] ?? 0);
        $comment['toxicity_score'] = (float)($comment['toxicity_score'] ?? 0);
        return $comment;
    }

    /**
     * Chuẩn hóa một dòng thống kê khu để controller/view không phải tự tính lại.
     */
    private static function normalizeAreaStatRow(array $row)
    {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['name'] = trim((string)($row['name'] ?? '')) ?: 'Chưa có khu';
        $row['total_rooms'] = (int)($row['total_rooms'] ?? 0);
        $row['available_rooms'] = (int)($row['available_rooms'] ?? 0);
        $row['rented_rooms'] = (int)($row['rented_rooms'] ?? 0);
        $row['draft_rooms'] = (int)($row['draft_rooms'] ?? 0);
        $row['total_floors'] = (int)($row['total_floors'] ?? 0);
        $row['occupied_rooms'] = $row['rented_rooms'];
        $knownRooms = max(0, $row['total_rooms'] - $row['draft_rooms']);
        $row['occupancy_rate'] = $knownRooms > 0
            ? round(($row['rented_rooms'] / $knownRooms) * 100, 1)
            : 0.0;

        return $row;
    }

    // ========== USERS ==========
    public static function findUserByEmail($email)
    {
        return UserModel::findByEmail($email);
    }
}
