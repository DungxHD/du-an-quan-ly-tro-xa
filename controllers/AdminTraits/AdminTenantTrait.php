<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminTenantTrait
{
/**
     * Danh sách tenant và form gán phòng kèm tạo hợp đồng.
     */
    public function tenants()
    {
        $tenants = array_values(array_filter(
            UserModel::getAll(),
            static fn($user) => (int)($user['role'] ?? 0) === 0
        ));

        $activeContracts = ContractModel::getAll(['status' => 'active']);
        $activeContractsByUserId = [];
        foreach ($activeContracts as $contract) {
            $activeContractsByUserId[(int)($contract['user_id'] ?? 0)] = $contract;
        }

        $rooms = array_values(array_filter(array_map(static function ($room) {
            $room['occupant_count'] = RoomModel::countOccupants((int)($room['id'] ?? 0));
            $room['available_slots'] = max(0, (int)($room['max_occupancy'] ?? 0) - (int)($room['occupant_count'] ?? 0));
            return $room;
        }, RoomModel::getAll(['status' => 'available'])), static function ($room) {
            return (int)($room['available_slots'] ?? 0) > 0;
        }));

        $tenantMessage = pullFlash('admin_tenant_message');
        $tenantError = pullFlash('admin_tenant_error');
        $oldTenantAssignment = pullFlash('admin_tenant_old', []);
        $assignmentForm = array_merge([
            'user_id' => 0,
            'room_id' => 0,
            'move_in_date' => date('Y-m-d'),
            'rent_price' => '',
            'deposit_amount' => '',
            'initial_electricity_index' => '',
            'initial_water_index' => '',
        ], is_array($oldTenantAssignment) ? $oldTenantAssignment : []);

        $pageTitle = 'Quản lý Người thuê - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/tenants.php';
    }
/**
     * Danh sách toàn bộ hợp đồng để admin tra cứu nhanh.
     */
    public function contracts()
    {
        $contracts = ContractModel::getAll();
        $selectedContract = null;
        $contractMessage = pullFlash('admin_contract_message');
        $contractError = pullFlash('admin_contract_error');
        $terminationForm = pullFlash('admin_contract_termination_old', []);
        $pageTitle = 'Quản lý Hợp đồng - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/contracts.php';
    }
/**
     * Xem chi tiết một hợp đồng và giải mã thông tin tenant phục vụ in giấy.
     */
    public function viewContract($id)
    {
        $contractId = (int)$id;
        $selectedContract = $contractId > 0 ? ContractModel::getById($contractId) : null;

        if (!$selectedContract) {
            setFlash('admin_contract_error', 'Hợp đồng không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-contracts');
        }

        $selectedContract = Encryption::decryptFields($selectedContract, UserModel::getContractFields());
        $contracts = ContractModel::getAll();
        $contractMessage = pullFlash('admin_contract_message');
        $contractError = pullFlash('admin_contract_error');
        $terminationForm = array_merge([
            'move_out_date' => date('Y-m-d'),
        ], (array)pullFlash('admin_contract_termination_old', []));
        $pageTitle = 'Chi tiết Hợp đồng - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/contracts.php';
    }
/**
     * Kết thúc hợp đồng đang active và giải phóng phòng tương ứng.
     */
    public function terminateContract($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-contracts');
        }
        verify_csrf();

        $contractId = (int)($id ?: ($_POST['contract_id'] ?? 0));
        $moveOutDate = trim((string)($_POST['move_out_date'] ?? ''));

        if (!$this->isValidDateInput($moveOutDate)) {
            setFlash('admin_contract_error', 'Ngày chuyển đi không đúng định dạng.');
            setFlash('admin_contract_termination_old', ['move_out_date' => $moveOutDate]);
            redirectTo('admin-view-contract', ['id' => $contractId]);
        }

        try {
            ContractModel::terminate($contractId, $moveOutDate);

            // Apply pending price changes ngay lập tức khi phòng giải phóng
            $contract = ContractModel::getById($contractId);
            if ($contract && isset($contract['room_id'])) {
                RoomPriceChangeModel::applyPendingImmediately((int)$contract['room_id']);
            }
            setFlash('admin_contract_message', 'Đã kết thúc hợp đồng và giải phóng phòng.');
            redirectTo('admin-contracts');
        } catch (Throwable $exception) {
            setFlash('admin_contract_error', $exception->getMessage());
            setFlash('admin_contract_termination_old', ['move_out_date' => $moveOutDate]);
            redirectTo('admin-view-contract', ['id' => $contractId]);
        }
    }
/**
     * Kiểm tra một chuỗi ngày có đúng chuẩn `Y-m-d` để tránh ghi sai vào DB.
     */
    private function isValidDateInput($value)
    {
        $resolvedValue = trim((string)$value);
        if ($resolvedValue === '') {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $resolvedValue);
        return $date !== false && $date->format('Y-m-d') === $resolvedValue;
    }
/**
     * Chuẩn hóa payload tạo hợp đồng từ form admin để controller/model dùng cùng một shape.
     */
    private function normalizeTenantAssignmentInput(array $source)
    {
        $electricityIndex = trim((string)($source['initial_electricity_index'] ?? ''));
        $waterIndex = trim((string)($source['initial_water_index'] ?? ''));

        return [
            'user_id' => (int)($source['user_id'] ?? 0),
            'room_id' => (int)($source['room_id'] ?? 0),
            'move_in_date' => trim((string)($source['move_in_date'] ?? '')),
            'rent_price' => (float)($source['rent_price'] ?? 0),
            'deposit_amount' => (float)($source['deposit_amount'] ?? 0),
            'initial_electricity_index' => $electricityIndex === '' ? null : (float)$electricityIndex,
            'initial_water_index' => $waterIndex === '' ? null : (float)$waterIndex,
            'contract_date' => date('Y-m-d'),
        ];
    }
/**
     * Gán tenant vào phòng và tạo hợp đồng active trong cùng một thao tác.
     */
    public function addTenant()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-tenants');
        }
        verify_csrf();

        $payload = $this->normalizeTenantAssignmentInput($_POST);
        $oldInput = [
            'user_id' => $payload['user_id'],
            'room_id' => $payload['room_id'],
            'move_in_date' => $payload['move_in_date'],
            'rent_price' => $_POST['rent_price'] ?? '',
            'deposit_amount' => $_POST['deposit_amount'] ?? '',
            'initial_electricity_index' => $_POST['initial_electricity_index'] ?? '',
            'initial_water_index' => $_POST['initial_water_index'] ?? '',
        ];

        if ($payload['user_id'] <= 0) {
            setFlash('admin_tenant_error', 'Vui lòng chọn tenant cần gán phòng.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if ($payload['room_id'] <= 0) {
            setFlash('admin_tenant_error', 'Vui lòng chọn phòng trống hợp lệ.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if (!$this->isValidDateInput($payload['move_in_date'])) {
            setFlash('admin_tenant_error', 'Ngày vào ở không đúng định dạng.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if ($payload['rent_price'] <= 0) {
            setFlash('admin_tenant_error', 'Giá thuê trong hợp đồng phải lớn hơn 0.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if ($payload['deposit_amount'] < 0) {
            setFlash('admin_tenant_error', 'Tiền cọc không được nhỏ hơn 0.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if ($payload['initial_electricity_index'] !== null && $payload['initial_electricity_index'] < 0) {
            setFlash('admin_tenant_error', 'Chỉ số điện đầu kỳ không được nhỏ hơn 0.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
        if ($payload['initial_water_index'] !== null && $payload['initial_water_index'] < 0) {
            setFlash('admin_tenant_error', 'Chỉ số nước đầu kỳ không được nhỏ hơn 0.');
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }

        try {
            $contractId = UserModel::assignToRoom($payload['user_id'], $payload['room_id'], $payload);
            setFlash('admin_tenant_message', 'Đã gán tenant vào phòng và tạo hợp đồng thành công.');
            redirectTo('admin-view-contract', ['id' => $contractId]);
        } catch (Throwable $exception) {
            setFlash('admin_tenant_error', $exception->getMessage());
            setFlash('admin_tenant_old', $oldInput);
            redirectTo('admin-tenants');
        }
    }
/**
     * Trang quản lý yêu cầu thuê phòng và ở ghép (hai cột: thuê / ở ghép).
     * Mỗi cột có button filter: Tất cả, Cần xử lý, Đã duyệt, Từ chối + ô tìm kiếm tên phòng/user/email/phone.
     */
    public function rentRequests()
    {
        // ===== CỘT 1: YÊU CẦU THUÊ PHÒNG =====
        // Button filter: all, pending, approved, rejected
        $rentFilter = trim((string)($_GET['rent_filter'] ?? 'all'));
        $rentAllowed = ['all', 'pending', 'approved', 'rejected'];
        if (!in_array($rentFilter, $rentAllowed, true)) {
            $rentFilter = 'all';
        }
        $rentStatus = $rentFilter === 'all' ? '' : $rentFilter;
        $rentKeyword = trim((string)($_GET['rent_keyword'] ?? ''));
        $rentParams = [];
        if ($rentStatus !== '') {
            $rentParams['status'] = $rentStatus;
        }
        if ($rentKeyword !== '') {
            $rentParams['keyword'] = $rentKeyword;
        }
        $requests = RentalRequestModel::getAllWithDetails($rentParams);

        // Đếm số yêu cầu đang chờ xử lý (pending) cho badge "Cần xử lý"
        $pendingRentParams = ['status' => 'pending'];
        $pendingRentAll = RentalRequestModel::getAllWithDetails($pendingRentParams);
        $pendingRentCount = count($pendingRentAll);

        // ===== CỘT 2: YÊU CẦU Ở GHÉP =====
        // Button filter: all, pending_admin, approved, rejected
        $roommateFilter = trim((string)($_GET['roommate_filter'] ?? 'all'));
        $roommateAllowed = ['all', 'pending_admin', 'approved', 'rejected'];
        if (!in_array($roommateFilter, $roommateAllowed, true)) {
            $roommateFilter = 'all';
        }
        $roommateStatus = $roommateFilter === 'all' ? '' : $roommateFilter;
        $roommateKeyword = trim((string)($_GET['roommate_keyword'] ?? ''));
        $roommateParams = [];
        if ($roommateStatus !== '') {
            $roommateParams['status'] = $roommateStatus;
        }
        if ($roommateKeyword !== '') {
            $roommateParams['keyword'] = $roommateKeyword;
        }
        $roommateRequests = RoommateRequestModel::getAll($roommateParams);

        // Đếm số yêu cầu ở ghép đang chờ admin duyệt (pending_admin) cho badge "Cần xử lý"
        $pendingRoommateParams = ['status' => 'pending_admin'];
        $pendingRoommateAll = RoommateRequestModel::getAll($pendingRoommateParams);
        $pendingRoommateCount = count($pendingRoommateAll);

        // Flash messages
        $message = pullFlash('rent_request_message', '');
        $error = pullFlash('rent_request_error', '');
        $roommateMessage = pullFlash('roommate_admin_message', '');
        $roommateError = pullFlash('roommate_admin_error', '');
        $pageTitle = 'Yêu cầu thuê & ở ghép - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/rent_requests.php';
    }
/**
     * Duyệt yêu cầu thuê: kiểm tra trùng hợp đồng + sức chứa, tạo contract, đồng bộ phòng, báo cho user.
     */
    public function approveRentRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RentalRequestModel::getById($requestId);

        if (!$request) {
            setFlash('rent_request_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-rent-requests');
        }
        if ((string)($request['status'] ?? '') !== 'pending') {
            setFlash('rent_request_error', 'Yêu cầu này đã được xử lý trước đó.');
            redirectTo('admin-rent-requests');
        }

        $userId = (int)($request['user_id'] ?? 0);
        $roomId = (int)($request['room_id'] ?? 0);
        $room = RoomModel::getById($roomId);
        if (!$room) {
            setFlash('rent_request_error', 'Phòng trong yêu cầu không còn tồn tại.');
            redirectTo('admin-rent-requests');
        }
        if (ContractModel::getActiveByUserId($userId)) {
            setFlash('rent_request_error', 'Người này đã có hợp đồng đang hoạt động, không thể duyệt thêm.');
            redirectTo('admin-rent-requests');
        }

        $currentOccupants = RoomModel::countOccupants($roomId);
        $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants + 1 > $maxOcc) {
            setFlash('rent_request_error', 'Phòng đã đủ sức chứa (' . $currentOccupants . '/' . $maxOcc . '), không thể duyệt thêm người.');
            redirectTo('admin-rent-requests');
        }

        $moveInDate = trim((string)($request['move_in_date'] ?? '')) ?: date('Y-m-d');
        try {
            $contractId = ContractModel::create([
                'user_id' => $userId,
                'room_id' => $roomId,
                'move_in_date' => $moveInDate,
                'rent_price' => (float)($room['price'] ?? 0),
                'deposit_amount' => 0,
                'initial_electricity_index' => null,
                'initial_water_index' => null,
                'contract_date' => date('Y-m-d'),
            ]);
            Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $userId]);
            ContractModel::syncRoomStatus($roomId);
            RentalRequestModel::setStatus($requestId, 'approved', 'Yêu cầu đã được duyệt.');
            NotificationModel::create([
                'user_id' => $userId,
                'type' => 'general',
                'title' => 'Yêu cầu thuê phòng đã được duyệt',
                'content' => 'Chúc mừng! Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" của bạn đã được admin duyệt. Ngày vào ở: ' . date('d/m/Y', strtotime($moveInDate)) . '.',
            ]);
            setFlash('rent_request_message', 'Đã duyệt yêu cầu và tạo hợp đồng cho phòng "' . ($room['name'] ?? '') . '".');
        } catch (Throwable $exception) {
            setFlash('rent_request_error', 'Không duyệt được yêu cầu: ' . $exception->getMessage());
        }
        redirectTo('admin-rent-requests');
    }
/**
     * Từ chối yêu cầu thuê: ghi lý do, báo cho user (user được gửi yêu cầu phòng khác).
     */
    public function rejectRentRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));
        $request = RentalRequestModel::getById($requestId);

        if (!$request) {
            setFlash('rent_request_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-rent-requests');
        }
        if ((string)($request['status'] ?? '') !== 'pending') {
            setFlash('rent_request_error', 'Yêu cầu này đã được xử lý trước đó.');
            redirectTo('admin-rent-requests');
        }

        $note = $adminNote !== '' ? $adminNote : 'Admin chưa phản hồi lý do cụ thể.';
        RentalRequestModel::setStatus($requestId, 'rejected', $note);
        $room = RoomModel::getById((int)($request['room_id'] ?? 0));
        NotificationModel::create([
            'user_id' => (int)($request['user_id'] ?? 0),
            'type' => 'general',
            'title' => 'Yêu cầu thuê phòng bị từ chối',
            'content' => 'Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" của bạn đã bị từ chối. Lý do: ' . $note . '. Bạn có thể gửi yêu cầu cho phòng khác.',
        ]);
        setFlash('rent_request_message', 'Đã từ chối yêu cầu thuê.');
        redirectTo('admin-rent-requests');
    }
/**
     * Yêu cầu ở ghép đã gộp vào trang quản lý yêu cầu (admin-rent-requests).
     * Giữ route cũ để link/redirect cũ không bị lỗi.
     */
    public function roommateRequests()
    {
        redirectTo('admin-rent-requests');
    }
/**
     * Admin duyệt yêu cầu ở ghép: tạo contract cho người B, đồng bộ phòng.
     */
    public function approveRoommate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RoommateRequestModel::getById($requestId);
        if (!$request) {
            setFlash('roommate_admin_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-rent-requests');
        }
        $status = (string)$request['status'];
        if ($status !== 'pending_admin') {
            setFlash('roommate_admin_error', 'Yêu cầu này không ở trạng thái chờ duyệt.');
            redirectTo('admin-rent-requests');
        }

        $requesterId = (int)$request['requester_id']; // người B
        $hostUserId = (int)$request['host_user_id'];  // người A
        $roomId = (int)$request['room_id'];
        $room = RoomModel::getById($roomId);
        if (!$room) {
            setFlash('roommate_admin_error', 'Phòng không tồn tại.');
            redirectTo('admin-rent-requests');
        }

        // Kiểm tra người B đã có phòng/hợp đồng chưa
        if (!empty(UserModel::getById($requesterId)['room_id']) || ContractModel::getActiveByUserId($requesterId)) {
            setFlash('roommate_admin_error', 'Người được mời đã có phòng/hợp đồng.');
            redirectTo('admin-rent-requests');
        }

        // Kiểm tra phòng còn chỗ
        $currentOcc = RoomModel::countOccupants($roomId);
        $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOcc >= $maxOcc) {
            setFlash('roommate_admin_error', 'Phòng đã đủ người, không thể duyệt.');
            redirectTo('admin-rent-requests');
        }

        try {
            ContractModel::create([
                'user_id' => $requesterId,
                'room_id' => $roomId,
                'move_in_date' => date('Y-m-d'),
                'rent_price' => (float)($room['price'] ?? 0),
                'deposit_amount' => 0,
                'initial_electricity_index' => null,
                'initial_water_index' => null,
                'contract_date' => date('Y-m-d'),
            ]);
            Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $requesterId]);
            ContractModel::syncRoomStatus($roomId);
            RoommateRequestModel::setStatus($requestId, 'approved');
            
            // Thông báo cho người B
            NotificationModel::create([
                'user_id' => $requesterId,
                'type' => 'general',
                'title' => 'Yêu cầu ở ghép đã được duyệt',
                'content' => 'Admin đã duyệt yêu cầu ở ghép của bạn tại phòng ' . ($room['name'] ?? '') . '.',
            ]);
            // Thông báo cho người A
            NotificationModel::create([
                'user_id' => $hostUserId,
                'type' => 'general',
                'title' => 'Yêu cầu mời ở ghép được duyệt',
                'content' => 'Admin đã duyệt yêu cầu mời ' . (UserModel::getById($requesterId)['full_name'] ?? '') . ' ở ghép tại phòng ' . ($room['name'] ?? '') . '.',
            ]);
            setFlash('roommate_admin_message', 'Đã duyệt ở ghép thành công.');
        } catch (Throwable $exception) {
            setFlash('roommate_admin_error', 'Không duyệt được: ' . $exception->getMessage());
        }
        redirectTo('admin-rent-requests');
    }

    /**
     * Admin từ chối yêu cầu ở ghép.
     */
    public function rejectRoommate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RoommateRequestModel::getById($requestId);
        if (!$request) {
            setFlash('roommate_admin_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-rent-requests');
        }
        $status = (string)$request['status'];
        if ($status !== 'pending_admin') {
            setFlash('roommate_admin_error', 'Yêu cầu này không ở trạng thái chờ duyệt.');
            redirectTo('admin-rent-requests');
        }

        $requesterId = (int)$request['requester_id'];
        $hostUserId = (int)$request['host_user_id'];
        $roomId = (int)$request['room_id'];

        RoommateRequestModel::setStatus($requestId, 'rejected');
        // Thông báo cho người B
        NotificationModel::create([
            'user_id' => $requesterId,
            'type' => 'general',
            'title' => 'Yêu cầu ở ghép bị từ chối',
            'content' => 'Admin đã từ chối yêu cầu ở ghép của bạn.',
        ]);
        // Thông báo cho người A
        NotificationModel::create([
            'user_id' => $hostUserId,
            'type' => 'general',
            'title' => 'Yêu cầu mời ở ghép bị từ chối',
            'content' => 'Admin đã từ chối yêu cầu mời ở ghép tại phòng ' . (RoomModel::getById($roomId)['name'] ?? '') . '.',
        ]);
        setFlash('roommate_admin_message', 'Đã từ chối yêu cầu ở ghép.');
        redirectTo('admin-rent-requests');
    }

    /**
     * Admin veto yêu cầu ở ghép đã duyệt: gỡ người B khỏi phòng.
     */
    public function vetoRoommate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RoommateRequestModel::getById($requestId);
        if (!$request) {
            setFlash('roommate_admin_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-rent-requests');
        }
        $status = (string)$request['status'];
        $requesterId = (int)$request['requester_id'];
        $roomId = (int)$request['room_id'];

        if ($status === 'approved') {
            $contract = ContractModel::getActiveByUserId($requesterId);
            try {
                if ($contract && (int)$contract['room_id'] === $roomId) {
                    ContractModel::terminate((int)$contract['id'], date('Y-m-d'));
                } else {
                    Database::update('users', ['room_id' => null], 'id = :id', ['id' => $requesterId]);
                    ContractModel::syncRoomStatus($roomId);
                }
            } catch (Throwable $exception) {
                setFlash('roommate_admin_error', 'Không gỡ được người ở ghép: ' . $exception->getMessage());
                redirectTo('admin-rent-requests');
            }
            RoommateRequestModel::setStatus($requestId, 'admin_rejected');
            NotificationModel::create([
                'user_id' => $requesterId,
                'type' => 'general',
                'title' => 'Yêu cầu ở ghép bị admin gỡ bỏ',
                'content' => 'Admin đã gỡ bạn khỏi phòng do yêu cầu ở ghép bị hủy bỏ.',
            ]);
            setFlash('roommate_admin_message', 'Đã gỡ người ở ghép khỏi phòng.');
        } elseif ($status === 'pending_admin') {
            RoommateRequestModel::setStatus($requestId, 'admin_rejected');
            $requesterId = (int)$request['requester_id'];
            NotificationModel::create([
                'user_id' => $requesterId,
                'type' => 'general',
                'title' => 'Yêu cầu ở ghép bị admin từ chối',
                'content' => 'Admin đã từ chối yêu cầu ở ghép của bạn.',
            ]);
            setFlash('roommate_admin_message', 'Đã từ chối yêu cầu ở ghép.');
        } else {
            setFlash('roommate_admin_error', 'Yêu cầu đã được xử lý trước đó.');
        }
        redirectTo('admin-rent-requests');
    }

}
