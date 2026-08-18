<?php
class UserModel {
    /**
     * Danh sách field hợp đồng cần mã hóa AES ở tầng model.
     */
    private const CONTRACT_FIELDS = [
        'date_of_birth',
        'permanent_address',
        'identity_number',
        'identity_issue_date',
        'identity_issue_place',
    ];

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
     * Lấy danh sách các field hợp đồng nhạy cảm để controller/view dùng thống nhất.
     */
    public static function getContractFields() {
        return self::CONTRACT_FIELDS;
    }

    /**
     * Chuẩn hóa record user sau khi đọc ra để mọi nơi luôn nhận được dữ liệu đã giải mã.
     */
    private static function hydrateUser(array $user) {
        $user['id'] = (int)($user['id'] ?? 0);
        $user['role'] = (int)($user['role'] ?? 0);
        $user['room_id'] = isset($user['room_id']) && $user['room_id'] !== null ? (int)$user['room_id'] : null;
        return Encryption::decryptFields($user, self::CONTRACT_FIELDS);
    }

    /**
     * Chuẩn hóa payload trước khi update để field hợp đồng luôn được mã hóa đúng 1 lần.
     */
    private static function prepareUpdatePayload(array $data) {
        if (isset($data['password']) && trim((string)$data['password']) !== '') {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        foreach (self::CONTRACT_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = trim((string)($data[$field] ?? ''));
            }
        }

        return Encryption::encryptFields($data, self::CONTRACT_FIELDS);
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
     * Lưu thông tin phục vụ hợp đồng với cơ chế mã hóa AES trước khi ghi DB.
     */
    public static function updateContractInfo($id, array $data) {
        $payload = [];
        foreach (self::CONTRACT_FIELDS as $field) {
            $payload[$field] = trim((string)($data[$field] ?? ''));
        }

        self::update($id, $payload);
    }
    
    /**
     * Gán tenant vào phòng và tạo hợp đồng active ngay trong cùng một luồng nghiệp vụ.
     * Controller chỉ cần truyền payload hợp đồng đã được validate cơ bản.
     */
    public static function assignToRoom($userId, $roomId, array $contractData = []) {
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
        if (($room['status'] ?? '') !== 'available') {
            throw new RuntimeException('Phòng này hiện không mở cho gán hợp đồng mới.');
        }
        if (!empty($tenant['room_id'])) {
            throw new RuntimeException('Tenant này đang được gán vào một phòng khác.');
        }
        if (ContractModel::getActiveByUserId($resolvedUserId)) {
            throw new RuntimeException('Tenant này đã có hợp đồng còn hiệu lực.');
        }

        $currentOccupants = RoomModel::countOccupants($resolvedRoomId);
        $maxOccupancy = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants >= $maxOccupancy) {
            throw new RuntimeException('Phòng đã đủ sức chứa tối đa.');
        }

        $payload = [
            'user_id' => $resolvedUserId,
            'room_id' => $resolvedRoomId,
            'move_in_date' => trim((string)($contractData['move_in_date'] ?? '')),
            'rent_price' => (float)($contractData['rent_price'] ?? 0),
            'deposit_amount' => (float)($contractData['deposit_amount'] ?? 0),
            'initial_electricity_index' => $contractData['initial_electricity_index'] ?? null,
            'initial_water_index' => $contractData['initial_water_index'] ?? null,
            'contract_date' => trim((string)($contractData['contract_date'] ?? '')) ?: date('Y-m-d'),
        ];

        $connection = Database::hasConnection() ? Database::getInstance() : null;
        $useTransaction = $connection instanceof PDO;

        if ($useTransaction) {
            $connection->beginTransaction();
        }

        try {
            $contractId = ContractModel::create($payload);
            Database::update('users', ['room_id' => $resolvedRoomId], 'id = :id', ['id' => $resolvedUserId]);
            ContractModel::syncRoomStatus($resolvedRoomId);

            if ($useTransaction) {
                $connection->commit();
            }

            return $contractId;
        } catch (Throwable $exception) {
            if ($useTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
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
