<?php
/**
 * ContractModel gom toàn bộ nghiệp vụ hợp đồng để controller chỉ điều phối luồng admin.
 * Hỗ trợ cả MySQL thật lẫn dữ liệu fallback trong `Database`.
 */
class ContractModel {
    /**
     * Lấy danh sách hợp đồng kèm tenant, phòng, tầng và khu để admin render trực tiếp.
     */
    public static function getAll(array $filters = []) {
        $statusFilter = trim((string)($filters['status'] ?? ''));

        if (Database::hasConnection()) {
            $sql = "
                SELECT
                    c.*,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.date_of_birth,
                    u.permanent_address,
                    u.identity_number,
                    u.identity_issue_date,
                    u.identity_issue_place,
                    r.name AS room_name,
                    r.price AS room_price,
                    r.max_occupancy,
                    f.name AS floor_name,
                    f.floor_number,
                    a.name AS area_name
                FROM contracts c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                LEFT JOIN floors f ON f.id = r.floor_id
                LEFT JOIN areas a ON a.id = f.area_id
                WHERE 1 = 1
            ";
            $params = [];

            if ($statusFilter !== '') {
                $sql .= ' AND c.status = ?';
                $params[] = $statusFilter;
            }

            $sql .= ' ORDER BY c.contract_date DESC, c.id DESC';

            return array_map(
                [self::class, 'normalizeContractRow'],
                Database::fetchAll($sql, $params)
            );
        }

        $rows = self::buildFallbackRows();
        if ($statusFilter !== '') {
            $rows = array_filter(
                $rows,
                static fn($row) => ($row['status'] ?? '') === $statusFilter
            );
        }

        usort($rows, static function ($left, $right) {
            $dateCompare = strcmp((string)($right['contract_date'] ?? ''), (string)($left['contract_date'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0);
        });

        return array_values($rows);
    }

    /**
     * Lấy một hợp đồng theo ID để admin xem/in chi tiết.
     */
    public static function getById($id) {
        $contractId = (int)$id;
        if ($contractId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT
                    c.*,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.date_of_birth,
                    u.permanent_address,
                    u.identity_number,
                    u.identity_issue_date,
                    u.identity_issue_place,
                    r.name AS room_name,
                    r.price AS room_price,
                    r.max_occupancy,
                    f.name AS floor_name,
                    f.floor_number,
                    a.name AS area_name
                FROM contracts c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                LEFT JOIN floors f ON f.id = r.floor_id
                LEFT JOIN areas a ON a.id = f.area_id
                WHERE c.id = ?
                ",
                [$contractId]
            );

            return $row ? self::normalizeContractRow($row) : null;
        }

        foreach (self::buildFallbackRows() as $row) {
            if ((int)($row['id'] ?? 0) === $contractId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Lấy hợp đồng active hiện tại của một tenant để chặn ký trùng.
     */
    public static function getActiveByUserId($userId) {
        $tenantId = (int)$userId;
        if ($tenantId <= 0) {
            return null;
        }

        if (Database::hasConnection()) {
            $row = Database::fetchOne(
                "
                SELECT
                    c.*,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.date_of_birth,
                    u.permanent_address,
                    u.identity_number,
                    u.identity_issue_date,
                    u.identity_issue_place,
                    r.name AS room_name,
                    r.price AS room_price,
                    r.max_occupancy,
                    f.name AS floor_name,
                    f.floor_number,
                    a.name AS area_name
                FROM contracts c
                INNER JOIN users u ON u.id = c.user_id
                INNER JOIN rooms r ON r.id = c.room_id
                LEFT JOIN floors f ON f.id = r.floor_id
                LEFT JOIN areas a ON a.id = f.area_id
                WHERE c.user_id = ? AND c.status = 'active'
                ORDER BY c.id DESC
                LIMIT 1
                ",
                [$tenantId]
            );

            return $row ? self::normalizeContractRow($row) : null;
        }

        foreach (self::getAll(['status' => 'active']) as $row) {
            if ((int)($row['user_id'] ?? 0) === $tenantId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Tạo mới hợp đồng thuê với snapshot giá thuê.
     * Tự động đặt ngày kết thúc = ngày vào + 1 năm.
     */
    public static function create(array $data) {
        // Tự động tính ngày kết thúc = ngày vào + 1 năm
        $moveInDate = trim((string)($data['move_in_date'] ?? ''));
        $moveOutDate = null;
        if ($moveInDate !== '') {
            $moveInTimestamp = strtotime($moveInDate);
            if ($moveInTimestamp !== false) {
                $moveOutDate = date('Y-m-d', strtotime('+1 year', $moveInTimestamp));
            }
        }

        $payload = [
            'user_id' => (int)($data['user_id'] ?? 0),
            'room_id' => (int)($data['room_id'] ?? 0),
            'move_in_date' => $moveInDate,
            'move_out_date' => $moveOutDate,
            'rent_price' => (float)($data['rent_price'] ?? 0),
            'deposit_amount' => (float)($data['deposit_amount'] ?? 0),
            'status' => 'active',
            'contract_date' => trim((string)($data['contract_date'] ?? '')) ?: date('Y-m-d'),
        ];

        return (int)Database::insert('contracts', $payload);
    }

    /**
     * Kết thúc hợp đồng, trả phòng cho tenant và đồng bộ lại trạng thái phòng.
     */
    public static function terminate($id, $moveOutDate) {
        $contractId = (int)$id;
        $resolvedMoveOutDate = trim((string)$moveOutDate);
        $contract = self::getById($contractId);

        if (!$contract) {
            throw new RuntimeException('Hợp đồng không tồn tại.');
        }
        if (($contract['status'] ?? '') !== 'active') {
            throw new RuntimeException('Hợp đồng này đã được kết thúc trước đó.');
        }
        if ($resolvedMoveOutDate === '') {
            throw new RuntimeException('Ngày chuyển đi là bắt buộc.');
        }
        if (strtotime($resolvedMoveOutDate) === false) {
            throw new RuntimeException('Ngày chuyển đi không hợp lệ.');
        }
        if (!empty($contract['move_in_date']) && $resolvedMoveOutDate < (string)$contract['move_in_date']) {
            throw new RuntimeException('Ngày chuyển đi không được nhỏ hơn ngày vào ở.');
        }

        $connection = Database::hasConnection() ? Database::getInstance() : null;
        $useTransaction = $connection instanceof PDO;

        if ($useTransaction) {
            $connection->beginTransaction();
        }

        try {
            Database::update(
                'contracts',
                [
                    'move_out_date' => $resolvedMoveOutDate,
                    'status' => 'terminated',
                ],
                'id = :id',
                ['id' => $contractId]
            );

            Database::update(
                'users',
                ['room_id' => null],
                'id = :id',
                ['id' => (int)$contract['user_id']]
            );

            self::syncRoomStatus((int)$contract['room_id']);

            if ($useTransaction) {
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if ($useTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Tính lại trạng thái phòng dựa trên số người đang được gán thực tế.
     * Có người thuê (>=1) thì chuyển `rented`, hoàn toàn trống thì `available`.
     */
    public static function syncRoomStatus($roomId) {
        $resolvedRoomId = (int)$roomId;
        $room = RoomModel::getById($resolvedRoomId);

        if (!$room) {
            return;
        }

        $occupantCount = RoomModel::countOccupants($resolvedRoomId);
        $nextStatus = $occupantCount >= 1 ? 'rented' : 'available';

        RoomModel::updateStatus($resolvedRoomId, $nextStatus);
    }

    /**
     * Chuẩn hóa dữ liệu hợp đồng để view/controller không phải ép kiểu lặp lại.
     */
    private static function normalizeContractRow(array $row) {
        $row['id'] = (int)($row['id'] ?? 0);
        $row['user_id'] = (int)($row['user_id'] ?? 0);
        $row['room_id'] = (int)($row['room_id'] ?? 0);
        $row['rent_price'] = (float)($row['rent_price'] ?? 0);
        $row['deposit_amount'] = (float)($row['deposit_amount'] ?? 0);
        $row['start_date'] = trim((string)($row['move_in_date'] ?? ''));
        $row['end_date'] = trim((string)($row['move_out_date'] ?? ''));
        $row['deposit'] = $row['deposit_amount'];
        $row['room_price'] = isset($row['room_price']) ? (float)$row['room_price'] : 0.0;
        $row['max_occupancy'] = isset($row['max_occupancy']) ? (int)$row['max_occupancy'] : 0;
        $row['floor_number'] = isset($row['floor_number']) ? (int)$row['floor_number'] : 0;
        $row['full_name'] = trim((string)($row['full_name'] ?? ''));
        $row['email'] = trim((string)($row['email'] ?? ''));
        $row['phone'] = trim((string)($row['phone'] ?? ''));
        $row['room_name'] = trim((string)($row['room_name'] ?? ''));
        $row['floor_name'] = trim((string)($row['floor_name'] ?? ''));
        $row['area_name'] = trim((string)($row['area_name'] ?? ''));
        return $row;
    }

    /**
     * Dựng dữ liệu fallback có cùng shape với truy vấn join của DB thật.
     */
    private static function buildFallbackRows() {
        $users = [];
        foreach (Database::getTable('users') as $user) {
            $users[(int)($user['id'] ?? 0)] = $user;
        }

        $rooms = [];
        foreach (RoomModel::getAll() as $room) {
            $rooms[(int)($room['id'] ?? 0)] = $room;
        }

        return array_map(static function ($contract) use ($users, $rooms) {
            $user = $users[(int)($contract['user_id'] ?? 0)] ?? [];
            $room = $rooms[(int)($contract['room_id'] ?? 0)] ?? [];

            $contract['full_name'] = $user['full_name'] ?? '';
            $contract['email'] = $user['email'] ?? '';
            $contract['phone'] = $user['phone'] ?? '';
            $contract['date_of_birth'] = $user['date_of_birth'] ?? null;
            $contract['permanent_address'] = $user['permanent_address'] ?? null;
            $contract['identity_number'] = $user['identity_number'] ?? null;
            $contract['identity_issue_date'] = $user['identity_issue_date'] ?? null;
            $contract['identity_issue_place'] = $user['identity_issue_place'] ?? null;
            $contract['room_name'] = $room['name'] ?? '';
            $contract['room_price'] = $room['price'] ?? 0;
            $contract['max_occupancy'] = $room['max_occupancy'] ?? 0;
            $contract['floor_name'] = $room['floor_name'] ?? '';
            $contract['floor_number'] = $room['floor_number'] ?? 0;
            $contract['area_name'] = $room['area_name'] ?? '';

            return self::normalizeContractRow($contract);
        }, Database::getTable('contracts'));
    }
}
