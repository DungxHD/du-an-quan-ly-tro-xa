<?php
/**
 * Lớp truy cập dữ liệu:
 * - Ưu tiên kết nối MySQL thật khi có sẵn.
 * - Nếu thiếu DB, toàn hệ thống vẫn chạy bằng bộ dữ liệu fallback trong bộ nhớ.
 */
class Database {
    private static $instance = null;
    private static $fallbackData = null;
    private static $lastInsertIds = [];

    private $conn = null;
    private $connected = false;
    private $connectionError = null;

    private $host = 'localhost';
    private $db_name = 'nhatroa_db';
    private $username = 'root';
    private $password = '';

    private function __construct() {
        $this->bootConnection();
        self::bootFallbackData();
    }

    /**
     * Thử kết nối DB thật. Nếu thất bại chỉ ghi nhận lỗi, không dừng ứng dụng.
     */
    private function bootConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->connected = true;
        } catch (Throwable $e) {
            $this->conn = null;
            $this->connected = false;
            $this->connectionError = $e->getMessage();
        }
    }

    private static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getInstance() {
        return self::instance()->conn;
    }

    public static function hasConnection() {
        return self::instance()->connected;
    }

    public static function getConnectionError() {
        return self::instance()->connectionError;
    }

    public static function query($sql, $params = []) {
        if (!self::hasConnection()) {
            return new DatabaseArrayStatement();
        }

        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll($sql, $params = []) {
        if (!self::hasConnection()) {
            return [];
        }
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne($sql, $params = []) {
        if (!self::hasConnection()) {
            return null;
        }
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Lấy toàn bộ dữ liệu của một bảng từ lớp fallback.
     */
    public static function getTable($table) {
        self::bootFallbackData();
        return self::$fallbackData[$table] ?? [];
    }

    public static function setTable($table, $rows) {
        self::bootFallbackData();
        self::$fallbackData[$table] = array_values($rows);
    }

    public static function find($table, $id) {
        foreach (self::getTable($table) as $row) {
            if ((int)($row['id'] ?? 0) === (int)$id) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Ghi/upsert một setting để cả DB thật và fallback cùng hành xử giống nhau.
     */
    public static function saveSetting($key, $value, $group = null) {
        if (self::hasConnection()) {
            if ($group === null) {
                self::query(
                    "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                    [$key, $value]
                );
            } else {
                self::query(
                    "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)",
                    [$key, $value, $group]
                );
            }
            return true;
        }

        $settings = self::getTable('settings');
        $updated = false;

        foreach ($settings as &$setting) {
            if (($setting['setting_key'] ?? '') === $key) {
                $setting['setting_value'] = $value;
                if ($group !== null) {
                    $setting['setting_group'] = $group;
                }
                $updated = true;
                break;
            }
        }
        unset($setting);

        if (!$updated) {
            $settings[] = [
                'id' => self::nextId('settings'),
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => $group ?? self::guessSettingGroup($key),
            ];
        }

        self::setTable('settings', $settings);
        return true;
    }

    public static function insert($table, $data) {
        if (self::hasConnection()) {
            $columns = array_keys($data);
            $placeholders = array_map(static fn($column) => ':' . $column, $columns);
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($data);
            return (int)self::getInstance()->lastInsertId();
        }

        $rows = self::getTable($table);
        $newRow = $data;
        if (!isset($newRow['id'])) {
            $newRow['id'] = self::nextId($table);
        }
        if (!isset($newRow['created_at'])) {
            $newRow['created_at'] = date('Y-m-d H:i:s');
        }
        $rows[] = $newRow;
        self::setTable($table, $rows);
        return $newRow['id'];
    }

    public static function update($table, $data, $where, $params = []) {
        if (self::hasConnection()) {
            $setFragments = [];
            foreach (array_keys($data) as $column) {
                $setFragments[] = $column . ' = :set_' . $column;
            }

            $sql = sprintf(
                'UPDATE %s SET %s WHERE %s',
                $table,
                implode(', ', $setFragments),
                $where
            );

            $executeParams = $params;
            foreach ($data as $column => $value) {
                $executeParams['set_' . $column] = $value;
            }

            $stmt = self::getInstance()->prepare($sql);
            return $stmt->execute($executeParams);
        }

        $rows = self::getTable($table);
        foreach ($rows as $index => $row) {
            if (self::matchesWhere($row, $where, $params)) {
                $rows[$index] = array_merge($row, $data);
            }
        }
        self::setTable($table, $rows);
        return true;
    }

    public static function delete($table, $where, $params = []) {
        if (self::hasConnection()) {
            $stmt = self::getInstance()->prepare(sprintf('DELETE FROM %s WHERE %s', $table, $where));
            return $stmt->execute($params);
        }

        $rows = array_filter(
            self::getTable($table),
            static fn($row) => !self::matchesWhere($row, $where, $params)
        );
        self::setTable($table, $rows);
        return true;
    }

    private static function bootFallbackData() {
        if (self::$fallbackData !== null) {
            return;
        }

        $adminPassword = password_hash('123456', PASSWORD_DEFAULT);
        $tenantPassword = password_hash('123456', PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        self::$fallbackData = [
            'settings' => [
                ['id' => 1, 'setting_key' => 'site_name', 'setting_value' => 'NhaTroA', 'setting_group' => 'brand'],
                ['id' => 2, 'setting_key' => 'site_slogan', 'setting_value' => 'Trang chính thức của khu trọ', 'setting_group' => 'brand'],
                ['id' => 3, 'setting_key' => 'site_description', 'setting_value' => 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ với chủ trọ.', 'setting_group' => 'brand'],
                ['id' => 4, 'setting_key' => 'hero_image', 'setting_value' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600', 'setting_group' => 'hero'],
                ['id' => 5, 'setting_key' => 'hero_headline_1', 'setting_value' => 'Xem Phòng Rõ', 'setting_group' => 'hero'],
                ['id' => 6, 'setting_key' => 'hero_headline_2', 'setting_value' => 'Chọn Chỗ Ở Dễ', 'setting_group' => 'hero'],
                ['id' => 7, 'setting_key' => 'stat_1_value', 'setting_value' => 'Linh hoạt', 'setting_group' => 'hero'],
                ['id' => 8, 'setting_key' => 'stat_1_label', 'setting_value' => 'Thủ tục vào ở đơn giản', 'setting_group' => 'hero'],
                ['id' => 9, 'setting_key' => 'stat_2_value', 'setting_value' => '24/7', 'setting_group' => 'hero'],
                ['id' => 10, 'setting_key' => 'stat_2_label', 'setting_value' => 'Hỗ trợ cư dân nhanh chóng', 'setting_group' => 'hero'],
                ['id' => 11, 'setting_key' => 'contact_address', 'setting_value' => 'Khu Công nghệ cao, TP. Thủ Đức, TP.HCM', 'setting_group' => 'contact'],
                ['id' => 12, 'setting_key' => 'contact_phone', 'setting_value' => '0901 234 567', 'setting_group' => 'contact'],
                ['id' => 13, 'setting_key' => 'contact_email', 'setting_value' => 'admin@nhatroa.vn', 'setting_group' => 'contact'],
            ],
            'buildings' => [
                // Giữ tên và loại hình đồng nhất để trang chủ hiển thị đúng badge khu/dãy/tòa.
                ['id' => 1, 'name' => 'Khu A - Sinh viên', 'type' => 'zone', 'address' => 'Đường số 1, TP. Thủ Đức', 'description' => 'Khu trọ gần trường, nhiều ánh sáng và có khu sinh hoạt chung.', 'sort_order' => 1, 'image' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=900'],
                ['id' => 2, 'name' => 'Dãy B - Người đi làm', 'type' => 'block', 'address' => 'Đường số 8, TP. Thủ Đức', 'description' => 'Không gian riêng tư, yên tĩnh và quản lý ra vào chặt chẽ.', 'sort_order' => 2, 'image' => 'https://images.unsplash.com/photo-1494526585095-c41746248156?w=900'],
                ['id' => 3, 'name' => 'Tòa C - Studio', 'type' => 'building', 'address' => 'Xa lộ Hà Nội, TP. Thủ Đức', 'description' => 'Thiết kế studio hiện đại, phù hợp ở dài hạn.', 'sort_order' => 3, 'image' => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=900'],
            ],
            'rooms' => [
                ['id' => 1, 'building_id' => 1, 'name' => 'A101', 'floor' => 1, 'price' => 3200000, 'area' => 20, 'max_occupancy' => 2, 'description' => 'Phòng có cửa sổ lớn, máy lạnh, nước nóng và bàn học.', 'status' => 'available', 'thumbnail' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900', 'views' => 126, 'notice_given' => 0, 'expected_vacant_date' => null],
                ['id' => 2, 'building_id' => 1, 'name' => 'A202', 'floor' => 2, 'price' => 3600000, 'area' => 24, 'max_occupancy' => 3, 'description' => 'Phòng rộng, có ban công nhỏ và khu bếp gọn gàng.', 'status' => 'rented', 'thumbnail' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=900', 'views' => 95, 'notice_given' => 1, 'expected_vacant_date' => date('Y-m-d', strtotime('+10 days'))],
                ['id' => 3, 'building_id' => 2, 'name' => 'B103', 'floor' => 1, 'price' => 4100000, 'area' => 28, 'max_occupancy' => 2, 'description' => 'Phòng riêng tư, phù hợp người đi làm, có máy giặt chung theo tầng.', 'status' => 'available', 'thumbnail' => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=900', 'views' => 188, 'notice_given' => 0, 'expected_vacant_date' => null],
                ['id' => 4, 'building_id' => 2, 'name' => 'B205', 'floor' => 2, 'price' => 4300000, 'area' => 30, 'max_occupancy' => 3, 'description' => 'Không gian yên tĩnh, nội thất cơ bản mới.', 'status' => 'maintenance', 'thumbnail' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900', 'views' => 42, 'notice_given' => 0, 'expected_vacant_date' => null],
                ['id' => 5, 'building_id' => 3, 'name' => 'C301', 'floor' => 3, 'price' => 5200000, 'area' => 32, 'max_occupancy' => 2, 'description' => 'Studio cao cấp, đầy đủ nội thất và khu bếp riêng.', 'status' => 'rented', 'thumbnail' => 'https://images.unsplash.com/photo-1494526585095-c41746248156?w=900', 'views' => 210, 'notice_given' => 1, 'expected_vacant_date' => date('Y-m-d', strtotime('+5 days'))],
                ['id' => 6, 'building_id' => 3, 'name' => 'C101', 'floor' => 1, 'price' => 4700000, 'area' => 26, 'max_occupancy' => 2, 'description' => 'Phòng studio tiêu chuẩn, vào ở ngay.', 'status' => 'available', 'thumbnail' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=900', 'views' => 162, 'notice_given' => 0, 'expected_vacant_date' => null],
            ],
            'users' => [
                ['id' => 1, 'full_name' => 'Quản trị viên NhaTroA', 'email' => 'admin@nhatroa.vn', 'phone' => '0901 234 567', 'password' => $adminPassword, 'role' => 1, 'status' => 1, 'room_id' => null, 'avatar' => '', 'created_at' => $now],
                ['id' => 2, 'full_name' => 'Nguyễn Minh An', 'email' => 'tenant1@gmail.com', 'phone' => '0908 888 999', 'password' => $tenantPassword, 'role' => 0, 'status' => 1, 'room_id' => 2, 'avatar' => '', 'created_at' => $now],
                ['id' => 3, 'full_name' => 'Trần Thu Hà', 'email' => 'tenant2@gmail.com', 'phone' => '0909 123 456', 'password' => $tenantPassword, 'role' => 0, 'status' => 1, 'room_id' => null, 'avatar' => '', 'created_at' => $now],
            ],
            'services' => [
                ['id' => 1, 'name' => 'Internet tốc độ cao', 'description' => 'Wi-Fi riêng tầng, ổn định cho học tập và làm việc.', 'price' => 120000, 'unit' => 'tháng', 'icon' => 'wifi'],
                ['id' => 2, 'name' => 'Giữ xe', 'description' => 'Bãi xe an ninh, camera 24/7.', 'price' => 100000, 'unit' => 'xe', 'icon' => 'directions_car'],
                ['id' => 3, 'name' => 'Dọn vệ sinh', 'description' => 'Hỗ trợ vệ sinh khu vực chung hằng tuần.', 'price' => 80000, 'unit' => 'tháng', 'icon' => 'cleaning_services'],
            ],
            'room_services' => [
                ['id' => 1, 'room_id' => 2, 'service_id' => 1, 'quantity' => 1, 'created_at' => $now],
                ['id' => 2, 'room_id' => 2, 'service_id' => 2, 'quantity' => 1, 'created_at' => $now],
            ],
            'amenities' => [
                ['id' => 1, 'title' => 'Camera an ninh', 'description' => 'Giám sát 24/7 toàn khu trọ.', 'icon' => 'videocam', 'is_active' => 1, 'sort_order' => 1],
                ['id' => 2, 'title' => 'Khóa vân tay', 'description' => 'Ra vào nhanh, an toàn hơn.', 'icon' => 'fingerprint', 'is_active' => 1, 'sort_order' => 2],
                ['id' => 3, 'title' => 'Khu giặt chung', 'description' => 'Máy giặt hoạt động ổn định.', 'icon' => 'local_laundry_service', 'is_active' => 1, 'sort_order' => 3],
                ['id' => 4, 'title' => 'Khu bếp cơ bản', 'description' => 'Thuận tiện sinh hoạt hằng ngày.', 'icon' => 'kitchen', 'is_active' => 1, 'sort_order' => 4],
            ],
            'comments' => [
                ['id' => 1, 'room_id' => 1, 'user_id' => 2, 'content' => 'Phòng sáng, sạch và ở khá yên tĩnh.', 'rating' => 5, 'status' => 1, 'created_at' => $now],
                ['id' => 2, 'room_id' => 3, 'user_id' => 2, 'content' => 'Khu trọ gọn gàng, bảo vệ hỗ trợ nhanh.', 'rating' => 4, 'status' => 1, 'created_at' => $now],
            ],
        ];

        foreach (self::$fallbackData as $table => $rows) {
            $ids = array_column($rows, 'id');
            self::$lastInsertIds[$table] = empty($ids) ? 0 : max($ids);
        }
    }

    private static function nextId($table) {
        self::bootFallbackData();
        self::$lastInsertIds[$table] = (self::$lastInsertIds[$table] ?? 0) + 1;
        return self::$lastInsertIds[$table];
    }

    private static function matchesWhere($row, $where, $params) {
        $clauses = preg_split('/\s+AND\s+/i', trim($where));
        $positionalIndex = 0;

        foreach ($clauses as $clause) {
            $clause = trim($clause);
            if ($clause === '') {
                continue;
            }

            if (!preg_match('/^([a-zA-Z0-9_]+)\s*=\s*(?::([a-zA-Z0-9_]+)|\?)$/', $clause, $matches)) {
                return false;
            }

            $field = $matches[1];
            if (!empty($matches[2])) {
                $paramKey = $matches[2];
                $expected = $params[$paramKey] ?? null;
            } else {
                $expected = $params[$positionalIndex] ?? null;
                $positionalIndex++;
            }

            if (($row[$field] ?? null) != $expected) {
                return false;
            }
        }

        return true;
    }

    private static function guessSettingGroup($key) {
        if (str_starts_with($key, 'contact_')) {
            return 'contact';
        }
        if (str_starts_with($key, 'hero_') || str_starts_with($key, 'stat_')) {
            return 'hero';
        }
        return 'brand';
    }
}

/**
 * Statement rỗng dùng khi hệ thống ở chế độ fallback để tránh fatal error.
 */
class DatabaseArrayStatement {
    public function fetchAll() {
        return [];
    }

    public function fetch() {
        return false;
    }
}
