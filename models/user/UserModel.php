<?php
class UserModel {
    /**
     * Chuẩn hóa số điện thoại về dạng 0xxxxxxxxx.
     * Trả về null nếu không hợp lệ.
     *
     * Quy tắc:
     * 1. Xóa tất cả whitespace.
     * 2. Chỉ chấp nhận chữ số và dấu cộng ở đầu.
     * 3. +84xxxxxxxxx -> 0xxxxxxxxx (9 số sau +84, số đầu không được là 0)
     * 4. 84xxxxxxxxx (không có +) -> 0xxxxxxxxx (9 số sau 84, số đầu không được là 0)
     * 5. 0xxxxxxxxx (10 số) -> giữ nguyên
     * 6. Các trường hợp khác -> null
     */
    public static function normalizePhone($rawPhone) {
        if ($rawPhone === null || $rawPhone === '') {
            return null;
        }

        $phone = preg_replace('/\s+/', '', (string)$rawPhone);

        if (!preg_match('/^[0-9+]+$/', $phone)) {
            return null;
        }

        if (strpos($phone, '+') !== false && strpos($phone, '+') !== 0) {
            return null;
        }

        if (str_starts_with($phone, '+84')) {
            $suffix = substr($phone, 3);
            if (strlen($suffix) !== 9 || !ctype_digit($suffix)) {
                return null;
            }
            if ($suffix[0] === '0') {
                return null;
            }
            return '0' . $suffix;
        }

        if (str_starts_with($phone, '84') && !str_starts_with($phone, '+')) {
            $suffix = substr($phone, 2);
            if (strlen($suffix) !== 9 || !ctype_digit($suffix)) {
                return null;
            }
            if ($suffix[0] === '0') {
                return null;
            }
            return '0' . $suffix;
        }

        if (str_starts_with($phone, '0')) {
            if (strlen($phone) !== 10 || !ctype_digit($phone)) {
                return null;
            }
            return $phone;
        }

        return null;
    }

    /**
     * Kiểm tra định dạng email nghiêm ngặt.
     * Trả về true nếu hợp lệ, false nếu không.
     */
    public static function validateEmailStrict($email) {
        $email = trim((string)$email);
        if ($email === '') {
            return false;
        }

        if (mb_strlen($email) > 150) {
            return false;
        }

        if (substr_count($email, '@') !== 1) {
            return false;
        }

        if (str_contains($email, ' ')) {
            return false;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        if ($localPart === '' || $domain === '') {
            return false;
        }

        if ($localPart[0] === '.' || $localPart[strlen($localPart) - 1] === '.') {
            return false;
        }

        if (str_contains($localPart, '..')) {
            return false;
        }

        if (substr_count($domain, '.') < 1) {
            return false;
        }

        if ($domain[0] === '.' || $domain[strlen($domain) - 1] === '.') {
            return false;
        }

        if (str_contains($domain, '..')) {
            return false;
        }

        if (str_ends_with($localPart, '.') || str_starts_with($domain, '.')) {
            return false;
        }

        $tld = substr($domain, strrpos($domain, '.') + 1);
        if (!preg_match('/^[a-zA-Z]{2,}$/', $tld)) {
            return false;
        }

        if (strtolower($domain) === 'localhost') {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9._%+-]+$/', $localPart)) {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9.-]+$/', $domain)) {
            return false;
        }

        // Chỉ chấp nhận email @gmail.com (đuôi luôn là .com)
        if (strtolower($domain) !== 'gmail.com') {
            return false;
        }

        return true;
    }

    /**
     * Validate họ và tên dùng chung cho mọi form tạo/sửa tài khoản.
     * Trả về chuỗi lỗi (hoặc '' nếu hợp lệ).
     */
    public static function validateFullName($fullName) {
        $fullName = (string)$fullName;
        if ($fullName === '') {
            return 'Vui lòng nhập họ và tên.';
        }
        if (trim($fullName) === '') {
            return 'Họ và tên không được chỉ chứa khoảng trắng.';
        }
        if (mb_strlen($fullName) > 100) {
            return 'Họ và tên không được vượt quá 100 ký tự.';
        }
        if (!preg_match('/^[\p{L}\p{N}\s\-\'\.]+$/u', $fullName)) {
            return 'Họ và tên chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, khoảng trắng, dấu gạch ngang, dấu chấm, dấu nháy đơn.';
        }
        return '';
    }

    /**
     * Validate mật khẩu dùng chung cho mọi form tạo/sửa tài khoản.
     * Rule: bắt buộc, tối thiểu 6 ký tự, có ít nhất 1 chữ cái và 1 số.
     * Trả về chuỗi lỗi (hoặc '' nếu hợp lệ). $fieldLabel dùng cho thông báo trường trống.
     */
    public static function validatePassword($password, $fieldLabel = 'mật khẩu') {
        $password = (string)$password;
        if ($password === '') {
            return 'Vui lòng nhập ' . $fieldLabel . '.';
        }
        if (strlen($password) < 6) {
            return 'Mật khẩu phải có ít nhất 6 ký tự.';
        }
        if (!preg_match('/[A-Za-z]/', $password)) {
            return 'Mật khẩu phải chứa ít nhất 1 chữ cái.';
        }
        if (!preg_match('/\d/', $password)) {
            return 'Mật khẩu phải chứa ít nhất 1 số.';
        }
        return '';
    }

    /**
     * Chuẩn hóa record user sau khi đọc ra.
     */
    private static function hydrateUser(array $user) {
        $user['id'] = (int)($user['id'] ?? 0);
        $user['role'] = (int)($user['role'] ?? 0);
        $user['room_id'] = isset($user['room_id']) && $user['room_id'] !== null ? (int)$user['room_id'] : null;
        return $user;
    }

    /**
     * Chuẩn hóa payload trước khi update.
     */
    private static function prepareUpdatePayload(array $data) {
        if (isset($data['password']) && trim((string)$data['password']) !== '') {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    public static function getAll() {
        if (Database::hasConnection()) {
            $users = Database::fetchAll(
                "SELECT
                    u.*,
                    r.name AS room_name,
                    f.name AS floor_name,
                    a.name AS building_name
                FROM users u
                LEFT JOIN rooms r ON u.room_id = r.id
                LEFT JOIN floors f ON r.floor_id = f.id
                LEFT JOIN areas a ON f.area_id = a.id
                ORDER BY u.created_at DESC"
            );

            return array_map([self::class, 'hydrateUser'], $users);
        }

        $rooms = [];
        foreach (Database::getTable('rooms') as $room) {
            $rooms[$room['id']] = $room;
        }
        $floors = [];
        foreach (Database::getTable('floors') as $floor) {
            $floors[$floor['id']] = $floor;
        }
        $areas = [];
        foreach (Database::getTable('areas') as $area) {
            $areas[$area['id']] = $area;
        }

        $users = array_map(static function ($user) use ($rooms, $floors, $areas) {
            $room = $rooms[$user['room_id']] ?? null;
            $floor = $room ? ($floors[$room['floor_id']] ?? null) : null;
            $area = $floor ? ($areas[$floor['area_id']] ?? null) : null;
            $user['room_name'] = $room['name'] ?? null;
            $user['room_price'] = $room['price'] ?? 0;
            $user['floor_name'] = $floor['name'] ?? null;
            $user['building_name'] = $area['name'] ?? null;
            return self::hydrateUser($user);
        }, Database::getTable('users'));

        usort($users, static fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $users;
    }
    
    public static function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $user = Database::fetchOne(
                "SELECT
                    u.*,
                    r.name AS room_name,
                    f.name AS floor_name,
                    a.name AS building_name
                FROM users u
                LEFT JOIN rooms r ON u.room_id = r.id
                LEFT JOIN floors f ON r.floor_id = f.id
                LEFT JOIN areas a ON f.area_id = a.id
                WHERE u.id = ?",
                [$id]
            );

            return $user ? self::hydrateUser($user) : null;
        }

        foreach (self::getAll() as $user) {
            if ((int)($user['id'] ?? 0) === $id) {
                return $user;
            }
        }
        return null;
    }
    
    public static function findByEmail($email) {
        $normalizedEmail = mb_strtolower(trim((string)$email));

        if (Database::hasConnection()) {
            $user = Database::fetchOne("SELECT * FROM users WHERE LOWER(email) = ?", [$normalizedEmail]);
            return $user ? self::hydrateUser($user) : null;
        }

        foreach (Database::getTable('users') as $user) {
            if (mb_strtolower(trim((string)($user['email'] ?? ''))) === $normalizedEmail) {
                return self::hydrateUser($user);
            }
        }
        return null;
    }

    /**
     * Chỉ kiểm tra tồn tại email để bám đúng luồng đăng ký và tránh tải dư dữ liệu.
     */
    public static function emailExists($email) {
        $normalizedEmail = mb_strtolower(trim((string)$email));

        if (Database::hasConnection()) {
            return (bool)Database::fetchOne("SELECT id FROM users WHERE LOWER(email) = ?", [$normalizedEmail]);
        }

        foreach (Database::getTable('users') as $user) {
            if (mb_strtolower(trim((string)($user['email'] ?? ''))) === $normalizedEmail) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tìm user theo số điện thoại (đã chuẩn hóa).
     */
    public static function findByPhone($phone) {
        $normalizedPhone = self::normalizePhone($phone);
        if (!$normalizedPhone) {
            return null;
        }

        if (Database::hasConnection()) {
            $user = Database::fetchOne("SELECT * FROM users WHERE phone = ?", [$normalizedPhone]);
            return $user ? self::hydrateUser($user) : null;
        }

        foreach (Database::getTable('users') as $user) {
            if (($user['phone'] ?? '') === $normalizedPhone) {
                return self::hydrateUser($user);
            }
        }
        return null;
    }

    /**
     * Kiểm tra số điện thoại đã tồn tại (dùng phone đã chuẩn hóa).
     */
    public static function phoneExists($phone) {
        $normalizedPhone = self::normalizePhone($phone);
        if (!$normalizedPhone) {
            return false;
        }

        if (Database::hasConnection()) {
            return (bool)Database::fetchOne("SELECT id FROM users WHERE phone = ?", [$normalizedPhone]);
        }

        foreach (Database::getTable('users') as $user) {
            if (($user['phone'] ?? '') === $normalizedPhone) {
                return true;
            }
        }
        return false;
    }
    
    public static function create($data) {
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $payload = [
            'full_name' => trim($data['full_name'] ?? ''),
            'email' => $email !== '' ? $email : null,
            'phone' => trim($data['phone'] ?? ''),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => (int)($data['role'] ?? 0),
            'room_id' => $data['room_id'] ?? null,
        ];
        return Database::insert('users', $payload);
    }
    
    public static function update($id, $data) {
        $payload = self::prepareUpdatePayload($data);
        Database::update('users', $payload, 'id = :id', ['id' => (int)$id]);
    }

    /**
     * Cập nhật hồ sơ cơ bản của tenant mà không đụng tới email.
     */
    public static function updateProfile($id, array $data) {
        $payload = [
            'full_name' => trim((string)($data['full_name'] ?? '')),
            'phone' => trim((string)($data['phone'] ?? '')),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = (string)$data['password'];
        }

        self::update($id, $payload);
    }

    /**
     * Gán tenant vào phòng.
     * $allowRented = true cho phép gán vào phòng đang thuê (luồng duyệt ở ghép).
     */
    public static function assignToRoom($userId, $roomId, $allowRented = false) {
        $resolvedUserId = (int)$userId;
        $resolvedRoomId = (int)$roomId;
        $tenant = self::getById($resolvedUserId);
        $room = RoomModel::getById($resolvedRoomId);

        if (!$tenant || (int)($tenant['role'] ?? 0) !== 0) {
            throw new RuntimeException('Người được chọn không phải tenant hợp lệ.');
        }
        if (!$room) {
            throw new RuntimeException('Phòng được chọn không tồn tại.');
        }
        if (($room['status'] ?? '') !== 'available' && !$allowRented) {
            throw new RuntimeException('Phòng này hiện không mở cho gán người thuê mới.');
        }
        if (!empty($tenant['room_id'])) {
            throw new RuntimeException('Tenant này đang được gán vào một phòng khác.');
        }

        $currentOccupants = RoomModel::countOccupants($resolvedRoomId);
        $maxOccupancy = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants >= $maxOccupancy) {
            throw new RuntimeException('Phòng đã đủ sức chứa tối đa.');
        }

Database::update('users', ['room_id' => $resolvedRoomId], 'id = :id', ['id' => $resolvedUserId]);
        RoomModel::syncRoomStatus($resolvedRoomId);

        return true;
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

    /**
     * Trả danh sách tenant đang gán vào một phòng cụ thể.
     */
    public static function getTenantsByRoomId($roomId) {
        $resolvedRoomId = (int)$roomId;
        if ($resolvedRoomId <= 0) {
            return [];
        }

        $tenants = [];
        foreach (self::getAll() as $user) {
            if ((int)($user['role'] ?? -1) !== 0) {
                continue;
            }
            if ((int)($user['room_id'] ?? 0) === $resolvedRoomId) {
                $tenants[] = $user;
            }
        }

        return $tenants;
    }
}
