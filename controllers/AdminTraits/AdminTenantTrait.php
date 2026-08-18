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

        // Tự động đặt tiền cọc = giá phòng cơ bản (không tính dịch vụ)
        $room = RoomModel::getById($payload['room_id']);
        if ($room) {
            $payload['deposit_amount'] = (float)($room['price'] ?? 0);
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
                'deposit_amount' => (float)($room['price'] ?? 0),
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
     * Từ chối yêu cầu thuê: không cần lý do (yêu cầu thuê phòng), báo cho user (user được gửi yêu cầu phòng khác).
     */
    public function rejectRentRequest()
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

        RentalRequestModel::setStatus($requestId, 'rejected');
        $room = RoomModel::getById((int)($request['room_id'] ?? 0));
        NotificationModel::create([
            'user_id' => (int)($request['user_id'] ?? 0),
            'type' => 'general',
            'title' => 'Yêu cầu thuê phòng bị từ chối',
            'content' => 'Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" của bạn đã bị từ chối. Bạn có thể gửi yêu cầu cho phòng khác.',
        ]);
        setFlash('rent_request_message', 'Đã từ chối yêu cầu thuê.');
        redirectTo('admin-rent-requests');
    }
/**
     * Bước 1: Admin xác nhận yêu cầu thuê → hiện mã QR chuyển tiền chờ người thuê thanh toán.
     */
    public function confirmRentRequest()
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

        $room = RoomModel::getById((int)($request['room_id'] ?? 0));
        if (!$room) {
            setFlash('rent_request_error', 'Phòng trong yêu cầu không còn tồn tại.');
            redirectTo('admin-rent-requests');
        }
        if ((string)($room['status'] ?? '') !== 'available') {
            setFlash('rent_request_error', 'Phòng "' . ($room['name'] ?? '') . '" không còn trống, không thể xác nhận yêu cầu.');
            redirectTo('admin-rent-requests');
        }

        $deposit = trim((string)($_POST['deposit'] ?? ''));
        if ($deposit === '' || !is_numeric($deposit) || (float)$deposit <= 0) {
            setFlash('rent_request_error', 'Vui lòng nhập số tiền cọc hợp lệ (lớn hơn 0) để giữ phòng cho người thuê.');
            redirectTo('admin-rent-requests');
        }
        $deposit = (float)$deposit;

        RentalRequestModel::confirmByAdmin($requestId, $deposit);
        $tenant = UserModel::getById((int)($request['user_id'] ?? 0));
        $roomName = (string)($room['name'] ?? '');
        $moveInDate = trim((string)($request['move_in_date'] ?? '')) ?: date('Y-m-d');
        NotificationModel::create([
            'user_id' => (int)($request['user_id'] ?? 0),
            'type' => 'rental_request',
            'title' => 'Yêu cầu thuê đã được chấp nhận',
            'content' => 'Yêu cầu thuê phòng "' . $roomName . '" của bạn đã được admin chấp nhận. Vui lòng thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ để giữ phòng này cho đến hết ngày dự kiến vào ở (' . date('d/m/Y', strtotime($moveInDate)) . '). Mã QR thanh toán đã sẵn sàng.',
            'link' => '?page=request-rent&id=' . (int)($request['room_id'] ?? 0),
        ]);
        setFlash('rent_request_message', 'Đã xác nhận yêu cầu thuê của "' . ($tenant['full_name'] ?? '') . '" với tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ. Mã QR chuyển tiền đã sẵn sàng cho người thuê.');
        redirectTo('admin-rent-requests');
    }
/**
     * Bước 2a: Admin hủy yêu cầu đã xác nhận → người thuê không vào phòng, phòng vẫn trống.
     */
    public function cancelRentRequestAdmin()
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

        RentalRequestModel::cancelByAdmin($requestId);
        $room = RoomModel::getById((int)($request['room_id'] ?? 0));
        $tenant = UserModel::getById((int)($request['user_id'] ?? 0));
        $tenantName = (string)($tenant['full_name'] ?? 'Tài khoản');
        $roomName = (string)($room['name'] ?? '');
        NotificationModel::create([
            'user_id' => (int)($request['user_id'] ?? 0),
            'type' => 'general',
            'title' => 'Tài khoản đã hủy đăng ký thuê',
            'content' => 'Tài khoản ' . $tenantName . ' đã hủy đăng ký thuê phòng "' . $roomName . '". Phòng vẫn ở trạng thái trống.',
        ]);
        setFlash('rent_request_message', 'Đã hủy yêu cầu thuê của "' . $tenantName . '". Người này không được thêm vào phòng, phòng "' . $roomName . '" vẫn còn trống.');
        redirectTo('admin-rent-requests');
    }
/**
     * Bước 2b: Admin xác nhận người thuê đã thanh toán → tạo hợp đồng, xếp phòng, phòng thành "đang thuê".
     */
    public function paidRentRequest()
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
            setFlash('rent_request_error', 'Người này đã có hợp đồng đang hoạt động, không thể xếp thêm phòng.');
            redirectTo('admin-rent-requests');
        }

        $currentOccupants = RoomModel::countOccupants($roomId);
        $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants + 1 > $maxOcc) {
            setFlash('rent_request_error', 'Phòng đã đủ sức chứa (' . $currentOccupants . '/' . $maxOcc . '), không thể thêm người.');
            redirectTo('admin-rent-requests');
        }

        $moveInDate = trim((string)($request['move_in_date'] ?? '')) ?: date('Y-m-d');
        $deposit = (float)($request['deposit'] ?? 0);
        if ($deposit <= 0) {
            $deposit = (float)($room['price'] ?? 0);
        }
        try {
            $contractId = ContractModel::create([
                'user_id' => $userId,
                'room_id' => $roomId,
                'move_in_date' => $moveInDate,
                'rent_price' => (float)($room['price'] ?? 0),
                'deposit_amount' => $deposit,
                'initial_electricity_index' => null,
                'initial_water_index' => null,
                'contract_date' => date('Y-m-d'),
            ]);
            Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $userId]);
            ContractModel::syncRoomStatus($roomId);
            RentalRequestModel::markPaid($requestId);
            $tenant = UserModel::getById($userId);
            $tenantName = (string)($tenant['full_name'] ?? 'Người thuê');
            $roomName = (string)($room['name'] ?? '');
            foreach (UserModel::getAll() as $admin) {
                if ((int)($admin['role'] ?? 1) === 1) {
                    NotificationModel::create([
                        'user_id' => (int)$admin['id'],
                        'type' => 'rental_request',
                        'title' => 'Tiền cọc đã được thanh toán',
                        'content' => 'Người thuê ' . $tenantName . ' đã thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ thành công cho phòng "' . $roomName . '".',
                        'link' => '?page=admin-rent-requests&rent_filter=approved',
                    ]);
                }
            }
            NotificationModel::create([
                'user_id' => (int)($request['user_id'] ?? 0),
                'type' => 'general',
                'title' => 'Chào mừng đến với phòng ' . $roomName,
                'content' => 'Bạn đã thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ thành công và chính thức là người thuê phòng "' . $roomName . '". Ngày vào ở: ' . date('d/m/Y', strtotime($moveInDate)) . '.',
            ]);
            setFlash('rent_request_message', 'Người thuê "' . $tenantName . '" đã thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ thành công và được thêm vào phòng "' . $roomName . '". Phòng và người thuê chuyển sang trạng thái đang thuê.');
        } catch (Throwable $exception) {
            setFlash('rent_request_error', 'Không xác nhận thanh toán được: ' . $exception->getMessage());
        }
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
                'deposit_amount' => (float)($room['price'] ?? 0),
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
     * Admin từ chối yêu cầu ở ghép: ghi lý do từ chối, gửi về người đang thuê trong phòng (người A) và người được mời (người B).
     */
    public function rejectRoommate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rent-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));
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

        $note = $adminNote !== '' ? $adminNote : 'Admin chưa phản hồi lý do cụ thể.';
        $requesterId = (int)$request['requester_id'];
        $hostUserId = (int)$request['host_user_id'];
        $roomId = (int)$request['room_id'];

        RoommateRequestModel::setStatus($requestId, 'rejected', $note);
        // Thông báo cho người A (người đang thuê trong phòng — người gửi lời mời): kèm lý do
        NotificationModel::create([
            'user_id' => $hostUserId,
            'type' => 'general',
            'title' => 'Yêu cầu mời ở ghép bị từ chối',
            'content' => 'Admin đã từ chối yêu cầu mời ở ghép tại phòng ' . (RoomModel::getById($roomId)['name'] ?? '') . '. Lý do: ' . $note,
        ]);
        // Thông báo cho người B (người được mời)
        NotificationModel::create([
            'user_id' => $requesterId,
            'type' => 'general',
            'title' => 'Yêu cầu ở ghép bị từ chối',
            'content' => 'Admin đã từ chối yêu cầu ở ghép của bạn tại phòng ' . (RoomModel::getById($roomId)['name'] ?? '') . '. Lý do: ' . $note,
        ]);
        setFlash('roommate_admin_message', 'Đã từ chối yêu cầu ở ghép.');
        redirectTo('admin-rent-requests');
    }

    /**
     * Admin veto yêu cầu ở ghép đã duyệt: gỡ người B khỏi phòng.
     * KHÔNG cho phép gỡ người đã được duyệt qua yêu cầu ở ghép (theo yêu cầu người dùng).
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
            // KHÔNG cho phép admin gỡ người đã được duyệt qua yêu cầu ở ghép
            setFlash('roommate_admin_error', 'Không thể gỡ người ở ghép đã được duyệt. Hết hạn hợp đồng thì tự động kết thúc.');
            redirectTo('admin-rent-requests');
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

    /**
     * Quản lý tài khoản: admin (không thêm/xóa) + người dùng (tenant/khách vãng lai).
     * Bộ lọc theo trạng thái thuê phòng, tìm kiếm theo tên, phân trang 10/trang.
     */
    public function accounts()
    {
        $keyword = trim((string)($_GET['search'] ?? ''));
        $statusFilter = trim((string)($_GET['status'] ?? 'all'));
        if (!in_array($statusFilter, ['all', 'renting', 'not_renting'], true)) {
            $statusFilter = 'all';
        }
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 10;

        $activeContractByUserId = [];
        foreach (ContractModel::getAll(['status' => 'active']) as $contract) {
            $activeContractByUserId[(int)($contract['user_id'] ?? 0)] = $contract;
        }

        $admins = [];
        $users = [];
        foreach (UserModel::getAll() as $userRow) {
            $isAdmin = (int)($userRow['role'] ?? 0) === 1;
            $userRow['account_status'] = $isAdmin
                ? 'admin'
                : (!empty($activeContractByUserId[(int)($userRow['id'] ?? 0)]) ? 'renting' : 'not_renting');
            $userRow['active_contract'] = $activeContractByUserId[(int)($userRow['id'] ?? 0)] ?? null;
            if ($isAdmin) {
                $admins[] = $userRow;
            } else {
                $users[] = $userRow;
            }
        }
        $allUsersStatus = array_map(
            static fn($userRow) => ['account_status' => (string)($userRow['account_status'] ?? 'not_renting')],
            $users
        );

        $filtered = $users;
        if ($keyword !== '') {
            $normalizedKeyword = mb_strtolower($keyword);
            $filtered = array_values(array_filter(
                $filtered,
                static fn($userRow) => mb_strpos(mb_strtolower((string)($userRow['full_name'] ?? '')), $normalizedKeyword) !== false
            ));
        }
        if ($statusFilter === 'renting') {
            $filtered = array_values(array_filter($filtered, static fn($userRow) => ($userRow['account_status'] ?? '') === 'renting'));
        } elseif ($statusFilter === 'not_renting') {
            $filtered = array_values(array_filter($filtered, static fn($userRow) => ($userRow['account_status'] ?? '') === 'not_renting'));
        }

        $totalUsers = count($filtered);
        $totalPages = max(1, (int)ceil($totalUsers / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $pagedUsers = array_slice($filtered, $offset, $perPage);

        $accountMessage = pullFlash('admin_account_message');
        $accountError = pullFlash('admin_account_error');
        $oldAccountInput = pullFlash('admin_account_old', []);
        $accountForm = array_merge([
            'full_name' => '',
            'phone' => '',
            'email' => '',
        ], is_array($oldAccountInput) ? $oldAccountInput : []);

        $buildAccountPageUrl = static function ($pageNumber, $statusOverride = null) use ($keyword, $statusFilter) {
            $params = [
                'page' => 'admin-accounts',
                'search' => $keyword,
                'status' => $statusOverride !== null ? $statusOverride : $statusFilter,
            ];
            if ($pageNumber > 1) {
                $params['p'] = $pageNumber;
            }
            return BASE_URL . '?' . http_build_query(array_filter($params, static fn($value) => $value !== '' && $value !== null));
        };

        $pageTitle = 'Quản lý tài khoản - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/accounts.php';
    }

    /**
     * Thêm tài khoản người dùng mới (luôn là tenant/khách vãng lai, không tạo admin).
     */
    public function saveAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-accounts');
        }
        verify_csrf();

        $payload = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'email' => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
            'password' => (string)($_POST['password'] ?? ''),
        ];
        setFlash('admin_account_old', $payload);

        $fullNameError = UserModel::validateFullName($payload['full_name']);
        if ($fullNameError !== '') {
            setFlash('admin_account_error', $fullNameError);
            redirectTo('admin-accounts');
        }
        $normalizedPhone = UserModel::normalizePhone($payload['phone']);
        if (!$normalizedPhone) {
            setFlash('admin_account_error', 'Số điện thoại không hợp lệ. Vui lòng nhập số 10 chữ số dạng 0xxxxxxxxx.');
            redirectTo('admin-accounts');
        }
        if (UserModel::phoneExists($normalizedPhone)) {
            setFlash('admin_account_error', 'Số điện thoại này đã được đăng ký.');
            redirectTo('admin-accounts');
        }
        if ($payload['email'] !== '' && !UserModel::validateEmailStrict($payload['email'])) {
            setFlash('admin_account_error', 'Email không đúng định dạng.');
            redirectTo('admin-accounts');
        }
        if ($payload['email'] !== '' && UserModel::emailExists($payload['email'])) {
            setFlash('admin_account_error', 'Email này đã được đăng ký.');
            redirectTo('admin-accounts');
        }
        $passwordError = UserModel::validatePassword($payload['password']);
        if ($passwordError !== '') {
            setFlash('admin_account_error', $passwordError);
            redirectTo('admin-accounts');
        }

        try {
            UserModel::create([
                'full_name' => $payload['full_name'],
                'phone' => $normalizedPhone,
                'email' => $payload['email'],
                'password' => $payload['password'],
                'role' => 0,
            ]);
            setFlash('admin_account_message', 'Đã thêm tài khoản "' . e($payload['full_name']) . '" thành công.');
        } catch (Throwable $exception) {
            setFlash('admin_account_error', 'Không tạo được tài khoản: ' . $exception->getMessage());
        }
        redirectTo('admin-accounts');
    }

    /**
     * Xóa tài khoản người dùng. Chặn cứng: admin không xóa được, người đang thuê phòng không xóa được.
     */
    public function deleteAccount($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-accounts');
        }
        verify_csrf();

        $userId = (int)$id;
        $user = $userId > 0 ? UserModel::getById($userId) : null;
        if (!$user) {
            setFlash('admin_account_error', 'Tài khoản không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-accounts');
        }
        if ((int)($user['role'] ?? 0) === 1) {
            setFlash('admin_account_error', 'Không thể xóa tài khoản quản trị viên.');
            redirectTo('admin-accounts');
        }
        $activeContract = ContractModel::getActiveByUserId($userId);
        if ($activeContract) {
            setFlash('admin_account_error', 'Không thể xóa tài khoản đang thuê phòng "' . e($activeContract['room_name'] ?? '') . '". Hãy kết thúc hợp đồng trước khi xóa.');
            redirectTo('admin-accounts');
        }

        $connection = Database::hasConnection() ? Database::getInstance() : null;
        $useTransaction = $connection instanceof PDO;

        if ($useTransaction) {
            $connection->beginTransaction();
        }

        try {
            Database::query('DELETE FROM payment_items WHERE payment_id IN (SELECT id FROM payments WHERE user_id = ?)', [$userId]);
            Database::query('DELETE FROM payments WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM notifications WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM notification_reads WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM comments WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM comment_reports WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM feedbacks WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM contracts WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM rental_requests WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM roommate_requests WHERE host_user_id = ?', [$userId]);
            Database::query('DELETE FROM user_services WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM password_reset_otps WHERE user_id = ?', [$userId]);
            Database::query('DELETE FROM password_reset_send_attempts WHERE user_id = ?', [$userId]);
            Database::update('maintenance_requests', ['rejected_by_user_id' => null], 'rejected_by_user_id = :rejected_by_user_id', ['rejected_by_user_id' => $userId]);
            Database::delete('users', 'id = :id', ['id' => $userId]);

            if ($useTransaction && $connection->inTransaction()) {
                $connection->commit();
            }
            setFlash('admin_account_message', 'Đã xóa tài khoản "' . e($user['full_name'] ?? '') . '" thành công.');
        } catch (Throwable $exception) {
            if ($useTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }
            setFlash('admin_account_error', 'Không xóa được tài khoản: ' . $exception->getMessage());
        }
        redirectTo('admin-accounts');
    }

    /**
     * Cập nhật tài khoản người dùng (admin có thể đổi mật khẩu không cần mật khẩu cũ/OTP).
     */
    public function updateAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-accounts');
        }
        verify_csrf();

        $userId = (int)($_POST['id'] ?? 0);
        $user = $userId > 0 ? UserModel::getById($userId) : null;
        if (!$user) {
            setFlash('admin_account_error', 'Tài khoản không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-accounts');
        }
        if ((int)($user['role'] ?? 0) === 1) {
            setFlash('admin_account_error', 'Không thể sửa tài khoản quản trị viên.');
            redirectTo('admin-accounts');
        }

        $payload = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'email' => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
            'password' => (string)($_POST['password'] ?? ''),
        ];
        setFlash('admin_account_old', array_merge($payload, ['id' => $userId]));

        $fullNameError = UserModel::validateFullName($payload['full_name']);
        if ($fullNameError !== '') {
            setFlash('admin_account_error', $fullNameError);
            redirectTo('admin-accounts');
        }
        $normalizedPhone = UserModel::normalizePhone($payload['phone']);
        if (!$normalizedPhone) {
            setFlash('admin_account_error', 'Số điện thoại không hợp lệ. Vui lòng nhập số 10 chữ số dạng 0xxxxxxxxx.');
            redirectTo('admin-accounts');
        }
        if (UserModel::phoneExists($normalizedPhone) && $normalizedPhone !== ($user['phone'] ?? '')) {
            setFlash('admin_account_error', 'Số điện thoại này đã được đăng ký.');
            redirectTo('admin-accounts');
        }
        if ($payload['email'] !== '' && !UserModel::validateEmailStrict($payload['email'])) {
            setFlash('admin_account_error', 'Email không đúng định dạng.');
            redirectTo('admin-accounts');
        }
        if ($payload['email'] !== '' && UserModel::emailExists($payload['email']) && $payload['email'] !== ($user['email'] ?? '')) {
            setFlash('admin_account_error', 'Email này đã được đăng ký.');
            redirectTo('admin-accounts');
        }
        if ($payload['password'] !== '') {
            $passwordError = UserModel::validatePassword($payload['password']);
            if ($passwordError !== '') {
                setFlash('admin_account_error', $passwordError);
                redirectTo('admin-accounts');
            }
        }

        try {
            $updateData = [
                'full_name' => $payload['full_name'],
                'phone' => $normalizedPhone,
                'email' => $payload['email'],
            ];
            if ($payload['password'] !== '') {
                $updateData['password'] = $payload['password'];
            }
            UserModel::update($userId, $updateData);
            setFlash('admin_account_message', 'Đã cập nhật tài khoản "' . e($payload['full_name']) . '" thành công.');
        } catch (Throwable $exception) {
            setFlash('admin_account_error', 'Không cập nhật được tài khoản: ' . $exception->getMessage());
        }
        redirectTo('admin-accounts');
    }

}
