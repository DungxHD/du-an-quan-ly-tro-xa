<?php
/**
 * RoomModel ưu tiên giao diện:
 * - Trả dữ liệu đã được ghép/join sẵn để view chỉ việc render.
 * - Khi thiếu DB, mọi dữ liệu được lấy từ lớp fallback của Database.
 */
class RoomModel {
    private static $settingsCache = null;

    // ========== SETTINGS ==========
    public static function loadSettings() {
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

    public static function getSetting($key, $default = '') {
        self::loadSettings();
        return isset(self::$settingsCache[$key]) && self::$settingsCache[$key] !== ''
            ? self::$settingsCache[$key]
            : $default;
    }

    public static function saveSetting($key, $value) {
        Database::saveSetting($key, $value);
        self::$settingsCache = null;
    }

    public static function getSettingsByGroup($group) {
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

    // ========== BUILDINGS ==========
    public static function getBuildings() {
        return BuildingModel::getAll();
    }

    // ========== ROOMS ==========
    public static function getAvailableOrUpcoming($limit = 6) {
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
    public static function normalizePublicFilters($filters = []) {
        $featureMap = [];
        foreach (self::getPublicFeatureOptions() as $feature) {
            $featureMap[$feature['key']] = $feature;
        }

        $selectedFeatures = $filters['services'] ?? [];
        if (!is_array($selectedFeatures)) {
            $selectedFeatures = [$selectedFeatures];
        }
        $selectedFeatures = array_values(array_filter(array_map('trim', $selectedFeatures), static fn($key) => isset($featureMap[$key])));

        $normalized = [
            'building_id' => (int)($filters['building_id'] ?? 0),
            'status' => in_array(($filters['status'] ?? ''), ['available', 'upcoming'], true) ? $filters['status'] : '',
            'min_price' => null,
            'max_price' => null,
            'min_price_input' => trim((string)($filters['min_price'] ?? '')),
            'max_price_input' => trim((string)($filters['max_price'] ?? '')),
            'services' => $selectedFeatures,
            'messages' => [],
        ];

        $normalized['min_price'] = self::parseHumanPrice($normalized['min_price_input']);
        $normalized['max_price'] = self::parseHumanPrice($normalized['max_price_input']);

        if ($normalized['min_price'] !== null && $normalized['max_price'] !== null) {
            $minimumGap = 500000;
            if ($normalized['max_price'] < $normalized['min_price'] + $minimumGap) {
                $normalized['max_price'] = $normalized['min_price'] + $minimumGap;
                $normalized['messages'][] = 'Khoảng giá tối thiểu là 500.000đ nên hệ thống đã tự nới giá kết thúc cho phù hợp.';
            }
        }

        $normalized['min_price_display'] = self::formatPriceInput($normalized['min_price']);
        $normalized['max_price_display'] = self::formatPriceInput($normalized['max_price']);
        return $normalized;
    }

    /**
     * Danh sách phòng public chỉ hiển thị phòng còn trống hoặc sắp trống,
     * đúng với nhu cầu người đi thuê thay vì lộ toàn bộ phòng đã kín.
     */
    public static function getPublicCatalog($filters = []) {
        $normalized = self::normalizePublicFilters($filters);
        $rooms = self::attachPublicCatalogMeta(self::getAll());

        $rooms = array_filter($rooms, static function ($room) use ($normalized) {
            if ((int)$normalized['building_id'] > 0 && (int)($room['building_id'] ?? 0) !== (int)$normalized['building_id']) {
                return false;
            }

            $matchesAvailability = ($room['status'] ?? '') === 'available' || !empty($room['isUpcoming']);
            if (!$matchesAvailability) {
                return false;
            }

            if ($normalized['status'] === 'available' && ($room['status'] ?? '') !== 'available') {
                return false;
            }
            if ($normalized['status'] === 'upcoming' && empty($room['isUpcoming'])) {
                return false;
            }
            if ($normalized['min_price'] !== null && (float)($room['price'] ?? 0) < $normalized['min_price']) {
                return false;
            }
            if ($normalized['max_price'] !== null && (float)($room['price'] ?? 0) > $normalized['max_price']) {
                return false;
            }

            foreach ($normalized['services'] as $featureKey) {
                if (!self::roomMatchesFeature($room, $featureKey)) {
                    return false;
                }
            }

            return true;
        });

        usort($rooms, static function ($a, $b) {
            $aPriority = ($a['status'] ?? '') === 'available' ? 0 : 1;
            $bPriority = ($b['status'] ?? '') === 'available' ? 0 : 1;
            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            $aVacant = $a['expected_vacant_date'] ?? '9999-12-31';
            $bVacant = $b['expected_vacant_date'] ?? '9999-12-31';
            if ($aVacant !== $bVacant) {
                return strcmp($aVacant, $bVacant);
            }

            return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
        });

        return array_values($rooms);
    }

    public static function getAll($filters = []) {
        if (Database::hasConnection()) {
            $sql = "
                SELECT r.*, b.name as building_name, b.type as building_type
                FROM rooms r
                LEFT JOIN buildings b ON r.building_id = b.id
                WHERE 1 = 1
            ";
            $params = [];

            if (!empty($filters['status'])) {
                $sql .= ' AND r.status = ?';
                $params[] = $filters['status'];
            }
            if (!empty($filters['building_id'])) {
                $sql .= ' AND r.building_id = ?';
                $params[] = (int)$filters['building_id'];
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

            $sql .= ' ORDER BY r.id DESC';
            return Database::fetchAll($sql, $params);
        }

        $buildings = [];
        foreach (BuildingModel::getAll() as $building) {
            $buildings[$building['id']] = $building;
        }

        $rooms = array_map(static function ($room) use ($buildings) {
            $building = $buildings[$room['building_id']] ?? [];
            $room['building_name'] = $building['name'] ?? 'Chưa phân khu';
            $room['building_type'] = $building['type'] ?? 'building';
            return $room;
        }, Database::getTable('rooms'));

        $rooms = array_filter($rooms, static function ($room) use ($filters) {
            if (!empty($filters['status']) && ($room['status'] ?? '') !== $filters['status']) {
                return false;
            }
            if (!empty($filters['building_id']) && (int)($room['building_id'] ?? 0) !== (int)$filters['building_id']) {
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

        usort($rooms, static fn($a, $b) => (int)$b['id'] <=> (int)$a['id']);
        return array_values($rooms);
    }

    public static function getById($id) {
        foreach (self::getAll() as $room) {
            if ((int)($room['id'] ?? 0) === (int)$id) {
                return $room;
            }
        }
        return null;
    }

    public static function save($data, $id = null) {
        $payload = [
            'building_id' => (int)($data['building_id'] ?? 0),
            'name' => trim($data['name'] ?? ''),
            'floor' => (int)($data['floor'] ?? 1),
            'price' => (float)($data['price'] ?? 0),
            'area' => (float)($data['area'] ?? 0),
            'max_occupancy' => (int)($data['max_occupancy'] ?? 2),
            'description' => trim($data['description'] ?? ''),
            'status' => $data['status'] ?? 'available',
            'thumbnail' => trim($data['thumbnail'] ?? '') ?: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900',
            'notice_given' => (int)($data['notice_given'] ?? 0),
            'expected_vacant_date' => $data['expected_vacant_date'] ?? null,
        ];

        if ($id) {
            Database::update('rooms', $payload, 'id = :id', ['id' => $id]);
            return $id;
        }

        $payload['views'] = (int)($data['views'] ?? 0);
        return Database::insert('rooms', $payload);
    }

    public static function delete($id) {
        Database::delete('rooms', 'id = :id', ['id' => $id]);
    }

    public static function incrementViews($id) {
        $room = self::getById($id);
        if (!$room) {
            return;
        }
        Database::update('rooms', ['views' => (int)($room['views'] ?? 0) + 1], 'id = :id', ['id' => $id]);
    }

    public static function count() {
        return count(self::getAll());
    }

    public static function countByStatus($status) {
        $count = 0;
        foreach (self::getAll() as $room) {
            if (($room['status'] ?? '') === $status) {
                $count++;
            }
        }
        return $count;
    }

    public static function getTotalRevenue() {
        $total = 0;
        foreach (self::getAll(['status' => 'rented']) as $room) {
            $total += (float)($room['price'] ?? 0);
        }
        return $total;
    }

    public static function getDaysUntilVacant($dateStr) {
        if (!$dateStr) {
            return null;
        }
        $now = new DateTime();
        $target = new DateTime($dateStr);
        $diff = $now->diff($target);
        return $diff->invert ? 0 : $diff->days;
    }

    /**
     * Bộ lọc tiện ích phòng được thiết kế tách riêng để sau này có thể nâng cấp
     * sang bảng room_features mà không phải thay đổi controller/view.
     */
    public static function getPublicFeatureOptions() {
        return [
            [
                'key' => 'air_conditioner',
                'label' => 'Điều hòa',
                'icon' => 'ac_unit',
                'aliases' => ['điều hòa', 'máy lạnh', 'dieu hoa', 'may lanh', 'air conditioner'],
            ],
            [
                'key' => 'water_heater',
                'label' => 'Nóng lạnh',
                'icon' => 'water_heater',
                'aliases' => ['nóng lạnh', 'nuoc nong', 'nước nóng', 'máy nước nóng', 'water heater'],
            ],
            [
                'key' => 'wifi',
                'label' => 'Wifi',
                'icon' => 'wifi',
                'aliases' => ['wifi', 'internet'],
            ],
            [
                'key' => 'parking',
                'label' => 'Chỗ để xe',
                'icon' => 'directions_car',
                'aliases' => ['giữ xe', 'de xe', 'để xe', 'bãi xe', 'parking'],
            ],
        ];
    }

    /**
     * Bổ sung metadata chỉ phục vụ trang public để view không cần suy diễn thêm.
     */
    private static function attachPublicCatalogMeta(array $rooms) {
        $roomIds = array_map(static fn($room) => (int)($room['id'] ?? 0), $rooms);
        $serviceMap = ServiceModel::getRoomServiceMap($roomIds);

        return array_map(static function ($room) use ($serviceMap) {
            $roomId = (int)($room['id'] ?? 0);
            $services = $serviceMap[$roomId] ?? [];
            $serviceNames = array_values(array_filter(array_map(static fn($service) => trim((string)($service['name'] ?? '')), $services)));
            $isUpcoming = self::isUpcomingVacancy($room);
            $expectedVacantText = $isUpcoming ? self::formatExpectedVacantText($room['expected_vacant_date'] ?? null) : '';

            $room['service_names'] = $serviceNames;
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

    private static function isUpcomingVacancy(array $room) {
        return (int)($room['notice_given'] ?? 0) === 1
            && ($room['status'] ?? '') === 'rented'
            && !empty($room['expected_vacant_date']);
    }

    private static function roomMatchesFeature(array $room, $featureKey) {
        $feature = null;
        foreach (self::getPublicFeatureOptions() as $item) {
            if (($item['key'] ?? '') === $featureKey) {
                $feature = $item;
                break;
            }
        }

        if (!$feature) {
            return false;
        }

        $normalizedServiceNames = array_map(
            static fn($serviceName) => mb_strtolower(trim((string)$serviceName), 'UTF-8'),
            $room['service_names'] ?? []
        );
        foreach ($feature['aliases'] as $alias) {
            if (in_array(mb_strtolower($alias, 'UTF-8'), $normalizedServiceNames, true)) {
                return true;
            }
        }

        $searchBlob = mb_strtolower(implode(' ', array_filter([
            $room['name'] ?? '',
            $room['description'] ?? '',
            implode(' ', $room['service_names'] ?? []),
        ])), 'UTF-8');

        foreach ($feature['aliases'] as $alias) {
            if (mb_strpos($searchBlob, mb_strtolower($alias, 'UTF-8')) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function parseHumanPrice($value) {
        $rawValue = trim((string)$value);
        if ($rawValue === '') {
            return null;
        }

        $normalized = mb_strtolower($rawValue, 'UTF-8');
        $normalized = str_replace(['vnđ', 'vnd', 'đ', 'dong', ' '], '', $normalized);
        $numberText = preg_replace('/[^0-9\.,]/', '', $normalized);
        if ($numberText === '') {
            return null;
        }

        $number = (float)str_replace(',', '.', $numberText);
        if ($number <= 0) {
            return null;
        }

        if (str_contains($normalized, 'tr') || str_contains($normalized, 'triệu') || $number < 1000) {
            return (int)round($number * 1000000);
        }
        if (str_contains($normalized, 'k') || str_contains($normalized, 'nghin') || str_contains($normalized, 'nghìn') || $number < 100000) {
            return (int)round($number * 1000);
        }

        return (int)round($number);
    }

    public static function formatPriceInput($price) {
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

    public static function formatExpectedVacantText($dateStr) {
        if (!$dateStr) {
            return '';
        }
        return date('d/m/Y', strtotime($dateStr));
    }

    /**
     * Dùng định dạng ngày ngắn cho badge để nội dung không bị dài và vỡ layout thẻ phòng.
     */
    private static function formatCompactDate($dateStr) {
        if (!$dateStr) {
            return '';
        }
        return date('d/m', strtotime($dateStr));
    }

    // ========== AMENITIES ==========
    public static function getAmenities() {
        return AmenityModel::getAllActive();
    }

    // ========== COMMENTS ==========
    public static function getCommentsByRoom($roomId) {
        if (Database::hasConnection()) {
            return Database::fetchAll(
                "
                SELECT c.*, u.full_name, u.avatar
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.room_id = ? AND c.status = 1
                ORDER BY c.created_at DESC
                ",
                [$roomId]
            );
        }

        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[$user['id']] = $user;
        }

        $comments = array_filter(Database::getTable('comments'), static function ($comment) use ($roomId) {
            return (int)($comment['room_id'] ?? 0) === (int)$roomId && (int)($comment['status'] ?? 0) === 1;
        });

        $comments = array_map(static function ($comment) use ($users) {
            $user = $users[$comment['user_id']] ?? [];
            $comment['full_name'] = $user['full_name'] ?? 'Cư dân';
            $comment['avatar'] = $user['avatar'] ?? '';
            return $comment;
        }, $comments);

        usort($comments, static fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_values($comments);
    }

    // ========== USERS ==========
    public static function findUserByEmail($email) {
        return UserModel::findByEmail($email);
    }
}
