<?php

class AdminController
{
    /**
     * Trang tổng quan admin.
     */
    public function dashboard()
    {
        $areaStats = RoomModel::getStatsByArea();
        $availableRooms = array_values(array_filter(
            RoomModel::getAll(['status' => 'available']),
            static fn($room) => (string)($room['status'] ?? '') === 'available'
        ));
        usort($availableRooms, static fn($left, $right) => (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0));

        $tenantRows = array_values(array_filter(
            UserModel::getAll(),
            static fn($user) => (int)($user['role'] ?? 1) === 0
        ));

        $stats = [
            'total_areas' => count($areaStats),
            'total_rooms' => RoomModel::count(),
            'available_rooms' => RoomModel::countByStatus('available'),
            'rented_rooms' => RoomModel::countByStatus('rented'),
            'total_tenants' => UserModel::countByRole(0),
            'total_revenue' => RoomModel::getTotalRevenue(),
        ];
        $recentRooms = array_slice($availableRooms, 0, 6);
        $recentTenants = array_slice($tenantRows, 0, 6);
        $settingSections = $this->populateAdminSettingSections();
        $heroImagePreview = $this->getSettingFieldValue($settingSections, 'hero_image');
        $dashboardMessage = pullFlash('admin_dashboard_message');
        $dashboardError = pullFlash('admin_dashboard_error');
        $pageTitle = 'Admin Dashboard - NhaTroA';
        require_once BASE_PATH . 'views/admin/dashboard.php';
    }

    /** Suy ra mã khu: ưu tiên mã admin nhập; nếu không lấy chữ HOA đơn lập đầu tiên ("Khu A - ..." => "A") */
    private function deriveAreaCode($name, $override = '')
    {
        $override = mb_strtoupper(trim((string)$override));
        if ($override !== '') {
            $clean = preg_replace('/[^A-Z0-9]/u', '', $override);
            if ($clean !== '') {
                return mb_substr($clean, 0, 1);
            }
        }
        if (preg_match('/\b([A-Z])\b/u', (string)$name, $m)) {
            return $m[1];
        }
        $first = mb_substr(trim((string)$name), 0, 1);
        return mb_strtoupper($first) ?: 'K';
    }

    /** Phòng đã đủ dữ liệu để đăng web chưa? */
    /** Phòng đã đủ dữ liệu để đăng web chưa? */
    private function roomIsComplete(array $room)
    {
        return (float)($room['price'] ?? 0) > 0
            && (float)($room['area'] ?? 0) > 0
            && trim((string)($room['description'] ?? '')) !== '';
    }

    /** Tạo sẵn N phòng NHÁP đặt tên tự động: {MãKhu}{Tầng}{STT 2 chữ số} */

    /**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Tạo sẵn N phòng NHÁP đặt tên theo vị trí: 01, 02, 03...
     * Thay đổi: Bỏ areaCode khỏi tên, chỉ dùng số thứ tự 2 chữ số.
     */
    private function createRoomSlots($floorId, $floorNumber, $roomCount)
    {
        $created = 0;
        $existing = count(RoomModel::getAll(['floor_id' => (int)$floorId]));

        for ($i = 1; $i <= $roomCount; $i++) {
            $position = $existing + $i;
            RoomModel::save([
                'floor_id'      => (int)$floorId,
                'name'          => str_pad((string)$position, 2, '0', STR_PAD_LEFT),
                'position'      => $position,
                'price'         => 0,
                'area'          => 0,
                'max_occupancy' => 2,
                'description'   => '',
                'amenities'     => '',
                'status'        => 'draft',
            ], null);
            $created++;
        }
        return $created;
    }


    /**
     * Quản lý khu theo schema mới `areas`.
     */
    public function areas()
    {
        $areas = AreaModel::getAllWithStats();
        $areaTree = AreaModel::getTree();
        $editId = (int)($_GET['edit'] ?? 0);
        $expandedAreaId = (int)($_GET['area'] ?? ($editId ?: 0));
        $editArea = $editId > 0 ? AreaModel::getById($editId) : null;
        $pageTitle = 'Quản lý Khu - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/areas.php';
    }

    /**
     * Quản lý tầng thuộc khu.
     */
    public function floors()
    {
        $areas = AreaModel::getAllWithStats();
        $editId = (int)($_GET['edit'] ?? 0);
        $editFloor = $editId > 0 ? FloorModel::getById($editId) : null;
        $selectedAreaId = (int)($_GET['area_id'] ?? ($editFloor['area_id'] ?? ($areas[0]['id'] ?? 0)));
        $selectedArea = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $floors = $selectedAreaId > 0 ? FloorModel::getByAreaId($selectedAreaId) : [];
        $pageTitle = 'Quản lý Tầng - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/floors.php';
    }

    /**
     * Quản lý tiện ích landing page, cho phép thêm/sửa/xóa/bật-tắt ngay trên một màn hình.
     */
    public function amenities()
    {
        $amenities = AmenityModel::getAll();
        $editId = (int)($_GET['edit'] ?? 0);
        $editAmenity = $editId > 0 ? AmenityModel::getById($editId) : null;
        $oldAmenityInput = pullFlash('admin_amenity_old');
        $formAmenity = is_array($oldAmenityInput) ? $oldAmenityInput : ($editAmenity ?? [
            'id' => 0,
            'icon' => 'apartment',
            'title' => '',
            'description' => '',
            'sort_order' => count($amenities) + 1,
            'is_active' => 1,
        ]);
        $amenityIcons = $this->getAmenityIconOptions();
        $amenityMessage = pullFlash('admin_amenity_message');
        $amenityError = pullFlash('admin_amenity_error');
        $pageTitle = 'Quản lý Tiện ích - NhaTroA';
        require_once BASE_PATH . 'views/admin/content/amenities.php';
    }

    /**
     * Danh sách phòng admin theo schema mới `areas -> floors -> rooms`.
     * Màn hình này gom cả filter, form thêm/sửa và bảng thao tác nhanh trạng thái.
     */
    public function rooms()
    {
        $filters = $this->getRoomAdminFilters($_GET);
        $areas = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $selectedFloor = $filters['floor_id'] > 0 ? FloorModel::getById($filters['floor_id']) : null;

        if ($selectedFloor) {
            $filters['area_id'] = (int)($selectedFloor['area_id'] ?? 0);
        }

        $rooms = array_map(static function ($room) {
            $room['occupant_count'] = RoomModel::countOccupants($room['id'] ?? 0);
            return $room;
        }, RoomModel::getAll($filters));

        $editId = (int)($_GET['edit'] ?? 0);
        $editRoom = $editId > 0 ? RoomModel::getById($editId) : null;
        $oldRoomInput = pullFlash('admin_room_old');
        $formRoom = is_array($oldRoomInput) ? $oldRoomInput : ($editRoom ?? null);

        $selectedAreaId = $filters['area_id'];
        if ($selectedAreaId <= 0 && $selectedFloor) {
            $selectedAreaId = (int)($selectedFloor['area_id'] ?? 0);
        }
        if ($selectedAreaId <= 0 && !empty($areas[0]['id'])) {
            $selectedAreaId = (int)$areas[0]['id'];
        }

        $formAreaId = (int)($formRoom['area_id'] ?? ($editRoom['area_id'] ?? $selectedAreaId));
        if ($formAreaId <= 0 && !empty($areas[0]['id'])) {
            $formAreaId = (int)$areas[0]['id'];
        }

        $filterFloors = $selectedAreaId > 0 ? FloorModel::getByAreaId($selectedAreaId) : [];
        $formFloors = $formAreaId > 0 ? FloorModel::getByAreaId($formAreaId) : [];
        $roomMessage = pullFlash('admin_room_message');
        $roomError = pullFlash('admin_room_error');
        $pageTitle = 'Quản lý Phòng - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/rooms.php';
    }

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
     * Trang quản lý hóa đơn tháng: preview, danh sách và xác nhận thanh toán tiền mặt.
     */
    public function invoices()
    {
        $period = PaymentModel::normalizePeriod($_GET['month'] ?? null, $_GET['year'] ?? null);
        $filters = [
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => trim((string)($_GET['status'] ?? '')),
            'area_id' => (int)($_GET['area_id'] ?? 0),
            'floor_id' => (int)($_GET['floor_id'] ?? 0),
            'room_id' => (int)($_GET['room_id'] ?? 0),
            'invoice_id' => (int)($_GET['invoice_id'] ?? 0),
        ];

        $selectedFloor = $filters['floor_id'] > 0 ? FloorModel::getById($filters['floor_id']) : null;
        if ($selectedFloor) {
            $filters['area_id'] = (int)($selectedFloor['area_id'] ?? 0);
        }

        $areas = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $filterFloors = $filters['area_id'] > 0 ? FloorModel::getByAreaId($filters['area_id']) : $allFloors;
        $invoiceRoomRows = PaymentModel::getRoomInvoiceOverview($period['month'], $period['year'], [
            'area_id' => $filters['area_id'],
            'floor_id' => $filters['floor_id'],
        ]);

        if ($filters['room_id'] <= 0 && !empty($invoiceRoomRows[0]['room_id'])) {
            $filters['room_id'] = (int)$invoiceRoomRows[0]['room_id'];
        }

        $invoicePreview = $filters['room_id'] > 0
            ? PaymentModel::buildInvoicePreview($filters['room_id'], $period['month'], $period['year'])
            : null;
        $invoiceList = PaymentModel::getInvoices([
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => $filters['status'],
            'area_id' => $filters['area_id'],
            'floor_id' => $filters['floor_id'],
        ]);
        $selectedInvoice = $filters['invoice_id'] > 0 ? PaymentModel::getInvoiceById($filters['invoice_id']) : null;
        $invoiceMessage = pullFlash('admin_invoice_message');
        $invoiceError = pullFlash('admin_invoice_error');
        $invoiceStatusOptions = [
            '' => 'Tất cả trạng thái',
            'unpaid' => 'Chưa trả',
            'paid' => 'Đã trả',
        ];
        $pageTitle = 'Quản lý Hóa đơn - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/invoices.php';
    }

    /**
     * Tạo hóa đơn cho 1 phòng hoặc toàn bộ phòng đang ở trong kỳ đã chọn.
     */
    public function generateInvoice()
    {
        PriceChangeModel::applyDueChanges();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-invoices');
        }
        verify_csrf();

        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $generateScope = trim((string)($_POST['generate_scope'] ?? 'single'));
        $roomId = (int)($_POST['room_id'] ?? 0);
        $areaId = (int)($_POST['area_id'] ?? 0);
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        $redirectParams = array_filter([
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => $status !== '' ? $status : null,
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'room_id' => $roomId > 0 ? $roomId : null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            if ($generateScope === 'all') {
                $result = PaymentModel::generateInvoices($period['month'], $period['year'], null, [
                    'area_id' => $areaId,
                    'floor_id' => $floorId,
                ]);
            } else {
                if ($roomId <= 0) {
                    throw new RuntimeException('Vui lòng chọn phòng cần tạo hóa đơn.');
                }

                $result = PaymentModel::generateInvoices($period['month'], $period['year'], $roomId);
            }

            $messageParts = [];
            if (!empty($result['created_count'])) {
                $messageParts[] = 'Đã tạo ' . (int)$result['created_count'] . ' hóa đơn';
            }
            if (!empty($result['skipped_existing_count'])) {
                $messageParts[] = (int)$result['skipped_existing_count'] . ' phòng đã có hóa đơn';
            }
            if (!empty($result['blocked_count'])) {
                $messageParts[] = (int)$result['blocked_count'] . ' phòng bị chặn do thiếu dữ liệu';
            }

            if (empty($result['created_count'])) {
                $blockedPreview = !empty($result['blocked']) ? ' ' . implode(' || ', array_slice($result['blocked'], 0, 3)) : '';
                $existingPreview = !empty($result['skipped_existing']) ? ' ' . implode(', ', array_slice($result['skipped_existing'], 0, 3)) : '';
                setFlash('admin_invoice_error', 'Không tạo được hóa đơn mới.' . $existingPreview . $blockedPreview);
            } else {
                setFlash('admin_invoice_message', implode('. ', $messageParts) . '.');
            }
        } catch (Throwable $exception) {
            setFlash('admin_invoice_error', $exception->getMessage());
        }

        redirectTo('admin-invoices', $redirectParams);
    }

    /**
     * Xác nhận tenant đã thanh toán tiền mặt cho hóa đơn đang chọn.
     */
    public function confirmPayment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-invoices');
        }
        verify_csrf();

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $payerUserId = (int)($_POST['payer_user_id'] ?? 0);
        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $redirectParams = array_filter([
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => trim((string)($_POST['status'] ?? '')) ?: null,
            'area_id' => (int)($_POST['area_id'] ?? 0) > 0 ? (int)$_POST['area_id'] : null,
            'floor_id' => (int)($_POST['floor_id'] ?? 0) > 0 ? (int)$_POST['floor_id'] : null,
            'room_id' => (int)($_POST['room_id'] ?? 0) > 0 ? (int)$_POST['room_id'] : null,
            'invoice_id' => $paymentId > 0 ? $paymentId : null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $invoice = PaymentModel::confirmPayment($paymentId, $payerUserId);
            $payerName = $invoice['payer']['full_name'] ?? 'tenant đã chọn';
            setFlash('admin_invoice_message', 'Đã xác nhận hóa đơn thanh toán thành công cho ' . $payerName . '.');
        } catch (Throwable $exception) {
            setFlash('admin_invoice_error', $exception->getMessage());
        }

        redirectTo('admin-invoices', $redirectParams);
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
            setFlash('admin_contract_message', 'Đã kết thúc hợp đồng và giải phóng phòng.');
            redirectTo('admin-contracts');
        } catch (Throwable $exception) {
            setFlash('admin_contract_error', $exception->getMessage());
            setFlash('admin_contract_termination_old', ['move_out_date' => $moveOutDate]);
            redirectTo('admin-view-contract', ['id' => $contractId]);
        }
    }

    public function stats()
    {
        $selectedAreaId = (int)($_GET['area_id'] ?? 0);
        $selectedYear = max(2000, (int)($_GET['year'] ?? date('Y')));
        $areas = AreaModel::getAllWithStats();
        $areaStats = RoomModel::getStatsByArea($selectedAreaId);
        $selectedArea = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $revenueStats = PaymentModel::getRevenueByMonth($selectedYear);
        $statsSummary = [
            'tracked_areas' => count($areaStats),
            'tracked_rooms' => array_sum(array_map(static fn($row) => (int)($row['total_rooms'] ?? 0), $areaStats)),
            'tracked_available_rooms' => array_sum(array_map(static fn($row) => (int)($row['available_rooms'] ?? 0), $areaStats)),
            'tracked_occupancy_rate' => 0,
            'year_total' => (float)($revenueStats['year_total'] ?? 0),
            'paid_invoice_count' => (int)($revenueStats['paid_invoice_count'] ?? 0),
        ];
        if ($statsSummary['tracked_rooms'] > 0) {
            $statsSummary['tracked_occupancy_rate'] = round(
                (($statsSummary['tracked_rooms'] - $statsSummary['tracked_available_rooms']) / $statsSummary['tracked_rooms']) * 100,
                1
            );
        }
        $pageTitle = 'Thống kê - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/stats.php';
    }

    /**
     * Quản lý toàn bộ dịch vụ và thao tác gán dịch vụ theo phòng.
     */
    public function services()
    {
        PriceChangeModel::applyDueChanges();
        $services = ServiceModel::getAll();
        $rooms = array_map(static function ($room) {
            $room['occupant_count'] = RoomModel::countOccupants((int)($room['id'] ?? 0));
            return $room;
        }, RoomModel::getAll());
        $selectedRoomId = (int)($_GET['room_id'] ?? 0);
        if ($selectedRoomId <= 0 && !empty($rooms[0]['id'])) {
            $selectedRoomId = (int)$rooms[0]['id'];
        }
        $selectedRoom = $selectedRoomId > 0 ? RoomModel::getById($selectedRoomId) : null;
        if ($selectedRoom) {
            $selectedRoom['occupant_count'] = RoomModel::countOccupants($selectedRoomId);
        }
        $roomAssignments = $selectedRoom ? ServiceModel::getAssignmentsByRoom($selectedRoomId) : [];
        $roomAssignableServices = ServiceModel::getAll([
            'applies_to' => 'room',
            'active_only' => true,
            'exclude_required' => true,
        ]);
        $requiredRoomServices = ServiceModel::getAll([
            'applies_to' => 'room',
            'required_only' => true,
        ]);
        $editId = (int)($_GET['edit'] ?? 0);
        $editService = $editId > 0 ? ServiceModel::getById($editId) : null;
        $oldServiceInput = pullFlash('admin_service_old');
        $formService = is_array($oldServiceInput) ? $oldServiceInput : ($editService ?? [
            'id' => 0,
            'name' => '',
            'price' => 0,
            'unit' => 'tháng',
            'icon' => 'settings',
            'description' => '',
            'is_required' => 0,
            'billing_mode' => 'fixed',
            'applies_to' => 'room',
            'is_active' => 1,
        ]);
        $serviceMessage = pullFlash('admin_service_message');
        $serviceError = pullFlash('admin_service_error');
        $serviceBillingModes = $this->getServiceBillingModeOptions();
        $serviceAppliesToOptions = $this->getServiceAppliesToOptions();
        $kindBillingModes = ServiceModel::getKindBillingModesMap();
        $pendingChanges = PriceChangeModel::getPendingByServiceMap();
        $pageTitle = 'Quản lý Dịch vụ - NhaTroA';
        ServiceModel::applyDueDeletes();
$priceHistories = [];
$pendingDeleteByService = [];
foreach ($services as $svc) {
$svcId = (int)($svc['id'] ?? 0);
$priceHistories[$svcId] = PriceChangeModel::getPendingHistoryByService($svcId);
$pendingDeleteByService[$svcId] = ServiceModel::isPendingDelete($svc);
}
require_once BASE_PATH . 'views/admin/billing/services.php';
    }
    public function priceChanges()
    {
        PriceChangeModel::applyDueChanges();
        $services = ServiceModel::getAll();
        $selectedServiceId = (int)($_GET['service_id'] ?? 0);
        if ($selectedServiceId <= 0 && !empty($services[0]['id'])) {
            $selectedServiceId = (int)$services[0]['id'];
        }

        $selectedService = $selectedServiceId > 0 ? ServiceModel::getById($selectedServiceId) : null;
        $priceChangeOld = pullFlash('admin_price_change_old');
        $priceChangeForm = array_merge([
            'service_id' => $selectedServiceId,
            'new_price' => $selectedService['price'] ?? '',
            'effective_month' => (int)date('n') + 1 > 12 ? 1 : (int)date('n') + 1,
            'effective_year' => (int)date('n') === 12 ? (int)date('Y') + 1 : (int)date('Y'),
        ], is_array($priceChangeOld) ? $priceChangeOld : []);

        $priceChangePreviewService = !empty($priceChangeForm['service_id']) ? ServiceModel::getById((int)$priceChangeForm['service_id']) : $selectedService;
        $priceChangeHistory = PriceChangeModel::getAll([
            'service_id' => $selectedServiceId > 0 ? $selectedServiceId : 0,
        ]);
        $priceChangeMessage = pullFlash('admin_price_change_message');
        $priceChangeError = pullFlash('admin_price_change_error');
        $pageTitle = 'Đổi giá Dịch vụ - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/price_changes.php';
    }

    /**
     * Lưu lịch sử đổi giá và tự phát sinh thông báo broadcast cho tenant.
     */
    public function savePriceChange()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-price-changes');
        }
        verify_csrf();
        $payload = [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'new_price' => trim((string)($_POST['new_price'] ?? '')),
            'effective_month' => (int)($_POST['effective_month'] ?? 0),
            'effective_year' => (int)($_POST['effective_year'] ?? 0),
        ];
        setFlash('admin_price_change_old', $payload);
        try {
            PriceChangeModel::scheduleServiceChange(
                $payload['service_id'],
                (float)$payload['new_price'],
                null,
                $payload['effective_month'],
                $payload['effective_year'],
                (int)($_SESSION['user_id'] ?? 0)
            );
            setFlash('admin_price_change_message', 'Đã lên lịch đổi giá và gửi thông báo cho tenant.');
        } catch (Throwable $exception) {
            setFlash('admin_price_change_error', $exception->getMessage());
        }
        redirectTo('admin-price-changes', [
            'service_id' => $payload['service_id'] > 0 ? $payload['service_id'] : null,
        ]);
    }
    public function notifications()
    {
        $tenants = array_values(array_filter(
            UserModel::getAll(),
            static fn($user) => (int)($user['role'] ?? 0) === 0
        ));

        $notificationFilters = [
            'type' => trim((string)($_GET['type'] ?? '')),
            'user_id' => (int)($_GET['user_id'] ?? 0),
            'recipient_scope' => trim((string)($_GET['recipient_scope'] ?? '')),
        ];
        $notificationOld = pullFlash('admin_notification_old');
        $notificationForm = array_merge([
            'title' => '',
            'content' => '',
            'type' => 'general',
            'recipient_scope' => 'all',
            'user_id' => 0,
        ], is_array($notificationOld) ? $notificationOld : []);

        $notificationHistory = NotificationModel::getAdminHistory($notificationFilters);
        $notificationTypeOptions = NotificationModel::getTypeOptions();
        $notificationMessage = pullFlash('admin_notification_message');
        $notificationError = pullFlash('admin_notification_error');
        $pageTitle = 'Quản lý Thông báo - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/notifications.php';
    }

    /**
     * Quản lý toàn bộ đánh giá phòng, gồm cả comment spam hoặc đang bị ẩn.
     */
    public function comments()
    {
        $commentFilters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'spam' => trim((string)($_GET['spam'] ?? '')),
            'keyword' => trim((string)($_GET['keyword'] ?? '')),
        ];
        $comments = CommentModel::getAdminComments($commentFilters);
        $commentStats = CommentModel::getAdminStats($comments);
        $commentMessage = pullFlash('admin_comment_message');
        $commentError = pullFlash('admin_comment_error');
        $pageTitle = 'Quản lý Đánh giá - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/comments.php';
    }

    /**
     * Quản lý danh sách từ cấm, hỗ trợ thêm/sửa/xóa/bật-tắt ngay trên một màn hình.
     */
    public function bannedWords()
    {
        $bannedWordFilters = BannedWordModel::normalizeFilters($_GET);
        $bannedWords = BannedWordModel::getAll($bannedWordFilters);
        $bannedWordStats = BannedWordModel::getStats($bannedWords);
        $editId = (int)($_GET['edit'] ?? 0);
        $editBannedWord = $editId > 0 ? BannedWordModel::getById($editId) : null;
        $bannedWordOld = pullFlash('admin_banned_word_old');
        $bannedWordForm = array_merge([
            'id' => 0,
            'word' => '',
            'type' => 'word',
            'replacement' => '***',
            'is_active' => 1,
        ], is_array($bannedWordOld) ? $bannedWordOld : ($editBannedWord ?? []));
        $bannedWordMessage = pullFlash('admin_banned_word_message');
        $bannedWordError = pullFlash('admin_banned_word_error');
        $bannedWordTypeOptions = BannedWordModel::getTypeOptions();
        $normalizedPreview = BannedWordModel::normalizeWord((string)($bannedWordForm['word'] ?? ''));
        $pageTitle = 'Quản lý Từ cấm - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/banned_words.php';
    }

    /**
     * Lưu hoặc xóa từ cấm từ form admin.
     */
    public function saveBannedWord()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-banned-words');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $action = trim((string)($_POST['form_action'] ?? 'save'));
        $redirectParams = array_filter([
            'type' => trim((string)($_POST['return_type'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
            'is_active' => in_array((string)($_POST['return_is_active'] ?? ''), ['0', '1'], true)
                ? (string)$_POST['return_is_active']
                : null,
        ], static fn($value) => $value !== null && $value !== '');

        if ($action === 'delete') {
            try {
                BannedWordModel::delete($id);
                setFlash('admin_banned_word_message', 'Đã xóa từ cấm thành công.');
            } catch (Throwable $exception) {
                setFlash('admin_banned_word_error', $exception->getMessage());
            }

            redirectTo('admin-banned-words', $redirectParams);
        }

        $payload = [
            'word' => trim((string)($_POST['word'] ?? '')),
            'type' => trim((string)($_POST['type'] ?? 'word')),
            'replacement' => trim((string)($_POST['replacement'] ?? '***')),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $savedId = BannedWordModel::save($payload, $id > 0 ? $id : null);
            setFlash('admin_banned_word_message', $id > 0 ? 'Đã cập nhật từ cấm thành công.' : 'Đã thêm từ cấm mới thành công.');
            redirectTo('admin-banned-words', array_merge($redirectParams, ['edit' => $savedId]));
        } catch (Throwable $exception) {
            setFlash('admin_banned_word_old', array_merge($payload, ['id' => $id]));
            setFlash('admin_banned_word_error', $exception->getMessage());
            redirectTo('admin-banned-words', array_merge($redirectParams, $id > 0 ? ['edit' => $id] : []));
        }
    }

    /**
     * Trang quản lý báo cáo cộng đồng cho comment.
     */
    public function commentReports()
    {
        $commentReportFilters = CommentReportModel::normalizeFilters($_GET);
        $commentReports = CommentReportModel::getAdminReports($commentReportFilters);
        $commentReportStats = CommentReportModel::getStats($commentReports);
        $commentReportMessage = pullFlash('admin_comment_report_message');
        $commentReportError = pullFlash('admin_comment_report_error');
        $pageTitle = 'Báo cáo Đánh giá - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/comment_reports.php';
    }

    /**
     * Admin giải quyết hoặc bác bỏ báo cáo comment.
     */
    public function resolveReport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-comment-reports');
        }
        verify_csrf();

        $reportId = (int)($_POST['report_id'] ?? 0);
        $action = trim((string)($_POST['resolve_action'] ?? ''));
        $redirectParams = array_filter([
            'status' => trim((string)($_POST['return_status'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $result = CommentReportModel::resolve($reportId, $action);
            setFlash(
                'admin_comment_report_message',
                ($result['action'] ?? '') === 'resolved'
                    ? 'Đã ẩn đánh giá và đánh dấu các báo cáo liên quan là đã giải quyết.'
                    : 'Đã bác bỏ các báo cáo đang chờ của đánh giá này.'
            );
        } catch (Throwable $exception) {
            setFlash('admin_comment_report_error', $exception->getMessage());
        }

        redirectTo('admin-comment-reports', $redirectParams);
    }

    /**
     * Bật/tắt hiển thị comment theo quyết định của admin.
     */
    public function toggleComment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-comments');
        }
        verify_csrf();

        $commentId = (int)($_POST['comment_id'] ?? 0);
        $targetStatus = isset($_POST['target_status']) ? (int)$_POST['target_status'] : null;
        $redirectParams = array_filter([
            'status' => trim((string)($_POST['return_status'] ?? '')) ?: null,
            'spam' => trim((string)($_POST['return_spam'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $comment = CommentModel::toggleStatus($commentId, $targetStatus);
            $statusText = (int)($comment['status'] ?? 0) === 1 ? 'hiện' : 'ẩn';
            setFlash('admin_comment_message', 'Đã cập nhật trạng thái đánh giá sang ' . $statusText . '.');
        } catch (Throwable $exception) {
            setFlash('admin_comment_error', $exception->getMessage());
        }

        redirectTo('admin-comments', $redirectParams);
    }

    /**
     * Gửi thông báo cho toàn bộ tenant hoặc một tenant cụ thể.
     */
    public function sendNotification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-notifications');
        }
        verify_csrf();

        $recipientScope = trim((string)($_POST['recipient_scope'] ?? 'all'));
        $payload = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'content' => trim((string)($_POST['content'] ?? '')),
            'type' => trim((string)($_POST['type'] ?? 'general')),
            'recipient_scope' => in_array($recipientScope, ['all', 'user'], true) ? $recipientScope : 'all',
            'user_id' => (int)($_POST['user_id'] ?? 0),
        ];
        setFlash('admin_notification_old', $payload);

        try {
            $targetUserId = $payload['recipient_scope'] === 'user' ? $payload['user_id'] : null;
            if ($payload['recipient_scope'] === 'user' && $targetUserId <= 0) {
                throw new RuntimeException('Vui lòng chọn tenant nhận thông báo.');
            }

            NotificationModel::create([
                'user_id' => $targetUserId,
                'title' => $payload['title'],
                'content' => $payload['content'],
                'type' => $payload['type'],
            ]);

            setFlash(
                'admin_notification_message',
                $payload['recipient_scope'] === 'all'
                    ? 'Đã gửi thông báo cho tất cả tenant.'
                    : 'Đã gửi thông báo cho tenant đã chọn.'
            );
        } catch (Throwable $exception) {
            setFlash('admin_notification_error', $exception->getMessage());
        }

        redirectTo('admin-notifications');
    }

    /**
     * Tạo mới hoặc cập nhật cấu hình dịch vụ.
     */
    public function saveService()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $returnRoomId = (int)($_POST['return_room_id'] ?? 0);
        $data = $this->normalizeServiceInput($_POST);
        $existing = $id > 0 ? ServiceModel::getById($id) : null;
        $kind = (string)($existing['kind'] ?? 'other');
        $redirectParams = array_filter([
            'edit' => $id > 0 ? $id : null,
            'room_id' => $returnRoomId > 0 ? $returnRoomId : null,
        ], static fn($value) => $value !== null && $value !== '');
        if ($id > 0 && !$existing) {
            setFlash('admin_service_error', 'Dịch vụ cần cập nhật không tồn tại.');
            redirectTo('admin-services', $redirectParams);
        }
        if ($data['name'] === '') {
            setFlash('admin_service_error', 'Tên dịch vụ là bắt buộc.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        if ($data['price'] < 0) {
            setFlash('admin_service_error', 'Giá dịch vụ không được nhỏ hơn 0.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        $allowedModes = ServiceModel::getKindBillingModesMap()[$kind] ?? array_keys(ServiceModel::getBillingModeOptions());
        if (!in_array($data['billing_mode'], $allowedModes, true)) {
            setFlash('admin_service_error', 'Cách tính giá không hợp lệ cho loại dịch vụ này. Chấp nhận: ' . implode(', ', $allowedModes) . '.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        if (!in_array($data['applies_to'], $this->getAllowedServiceAppliesTo(), true)) {
            setFlash('admin_service_error', 'Đối tượng áp dụng không hợp lệ.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        if (ServiceModel::isLockedKind($kind)) {
            $data['applies_to'] = 'room';
            $data['is_required'] = 1;
            $data['is_active'] = 1;
        }
        if ($data['is_required'] === 1 && $data['applies_to'] !== 'room') {
            setFlash('admin_service_error', 'Dịch vụ bắt buộc chỉ được áp dụng theo phòng.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        if ($data['billing_mode'] === 'meter' && $data['applies_to'] !== 'room') {
            setFlash('admin_service_error', 'Dịch vụ tính theo chỉ số chỉ phù hợp với phòng.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services', $redirectParams);
        }
        if ($existing) {
            $submittedPrice = (float)$data['price'];
            $submittedMode = $data['billing_mode'];
            $core = $data;
            $core['price'] = (float)$existing['price'];
            $core['billing_mode'] = (string)$existing['billing_mode'];
$core['applies_to'] = (string)($existing['applies_to'] ?? 'room');
$core['unit'] = ServiceModel::deriveUnit((string)($existing['kind'] ?? 'other'), (string)$existing['billing_mode']);
            $core['kind'] = (string)($existing['kind'] ?? 'other');
            ServiceModel::save($core, $id);
            $priceChanged = abs($submittedPrice - (float)$existing['price']) > 0.001;
            $modeChanged = $submittedMode !== (string)$existing['billing_mode'];
            if ($priceChanged || $modeChanged) {
                try {
                    $em = (int)($_POST['effective_month'] ?? 0); $ey = (int)($_POST['effective_year'] ?? 0);
$curOrder = ((int)date('Y')*100)+(int)date('n');
if ($em >= 1 && $em <= 12 && $ey >= (int)date('Y') && ($ey*100+$em) > $curOrder) { $nextMonth=$em; $nextYear=$ey; }
else { $nextMonth = (int)date('n') + 1; $nextYear = (int)date('Y'); if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; } }
                    PriceChangeModel::scheduleServiceChange($id, $submittedPrice, $submittedMode, $nextMonth, $nextYear, (int)($_SESSION['user_id'] ?? 0));
                    setFlash('admin_service_message', 'Đã cập nhật dịch vụ. Giá/cách tính mới áp dụng từ tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '.');
                } catch (Throwable $exception) {
                    setFlash('admin_service_error', $exception->getMessage());
                }
            } else {
                setFlash('admin_service_message', 'Đã cập nhật dịch vụ thành công.');
            }
            redirectTo('admin-services', $redirectParams);
        }
        $data['kind'] = 'other';
        $savedId = ServiceModel::save($data, null);
        setFlash('admin_service_message', 'Đã thêm dịch vụ mới thành công.');
        redirectTo('admin-services', array_filter(['edit' => $savedId, 'room_id' => $returnRoomId > 0 ? $returnRoomId : null], static fn($v) => $v !== null && $v !== ''));
    }
    public function undoDeleteService($id) {
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('admin-services'); }
verify_csrf();
$service = ServiceModel::getById((int)$id);
if ($service && ServiceModel::isPendingDelete($service)) {
ServiceModel::undoDelete((int)$id);
setFlash('admin_service_message', 'Đã hoàn tác xóa. Dịch vụ "' . ($service['name'] ?? '') . '" tiếp tục hoạt động.');
} else {
setFlash('admin_service_error', 'Dịch vụ không tồn tại hoặc không ở trạng thái chờ xóa.');
}
redirectTo('admin-services');
}
public function cancelPriceChange($id) {
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('admin-services'); }
verify_csrf();
try {
PriceChangeModel::cancelPendingChange((int)$id);
setFlash('admin_service_message', 'Đã hủy lịch thay đổi giá/cách tính.');
} catch (Throwable $exception) {
setFlash('admin_service_error', $exception->getMessage());
}
redirectTo('admin-services');
}public function deleteService($id)
{
$service = ServiceModel::getById((int)$id);
if ($service) {
$locked = (int)($service['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($service['kind'] ?? 'other');
if ($locked) {
setFlash('admin_service_error', 'Dịch vụ bắt buộc (điện/nước/rác) không thể xóa.');
redirectTo('admin-services');
}
$using = ServiceModel::countRoomsUsing((int)$id);
if ($using > 0) {
$em = (int)($_POST['effective_month'] ?? 0); $ey = (int)($_POST['effective_year'] ?? 0);
$curOrder = ((int)date('Y')*100)+(int)date('n');
if ($em >= 1 && $em <= 12 && $ey >= (int)date('Y') && ($ey*100+$em) > $curOrder) { $nextMonth=$em; $nextYear=$ey; }
else { $nextMonth = (int)date('n') + 1; $nextYear = (int)date('Y'); if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; } }
ServiceModel::scheduleDelete((int)$id, $nextMonth, $nextYear);
setFlash('admin_service_message', 'Dịch vụ đang có ' . $using . ' phòng sử dụng. Sẽ bị xóa khi sang tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '. Bạn có thể Hoàn tác trước thời điểm đó.');
redirectTo('admin-services');
}
}
$redirectParams = [];
        if ((int)($_GET['room_id'] ?? 0) > 0) {
            $redirectParams['room_id'] = (int)$_GET['room_id'];
        }

        try {
            ServiceModel::delete((int)$id);
            setFlash('admin_service_message', 'Đã xóa dịch vụ thành công.');
        } catch (Throwable $exception) {
            setFlash('admin_service_error', $exception->getMessage());
        }

        redirectTo('admin-services', $redirectParams);
    }

    /**
     * Gán hoặc gỡ dịch vụ theo phòng từ màn quản lý dịch vụ.
     */
    public function assignServiceToRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();

        $roomId = (int)($_POST['room_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $assignmentAction = trim((string)($_POST['assignment_action'] ?? 'assign'));

        if ($roomId <= 0) {
            setFlash('admin_service_error', 'Vui lòng chọn phòng cần gán dịch vụ.');
            redirectTo('admin-services');
        }

        try {
            if ($assignmentAction === 'remove') {
                ServiceModel::removeFromRoom($roomId, $serviceId);
                setFlash('admin_service_message', 'Đã gỡ dịch vụ khỏi phòng.');
            } else {
                $result = ServiceModel::assignToRoom($roomId, $serviceId, $quantity);
                setFlash(
                    'admin_service_message',
                    $result === 'updated'
                        ? 'Dịch vụ đã tồn tại, hệ thống đã cập nhật lại số lượng.'
                        : 'Đã gán dịch vụ cho phòng thành công.'
                );
            }
        } catch (Throwable $exception) {
            setFlash('admin_service_error', $exception->getMessage());
        }

        redirectTo('admin-services', ['room_id' => $roomId]);
    }

    /**
     * Trang nhập chỉ số điện/nước theo tháng cho các phòng có dịch vụ tính theo công tơ.
     */
    public function meterReadings()
    {
        PriceChangeModel::applyDueChanges();
        $period = MeterReadingModel::normalizePeriod($_GET['month'] ?? null, $_GET['year'] ?? null);
        $meterData = MeterReadingModel::getAdminMatrix($period['month'], $period['year']);
        $meterMessage = pullFlash('admin_meter_message');
        $meterError = pullFlash('admin_meter_error');
        $meterRowErrors = pullFlash('admin_meter_row_errors', []);
        $meterOldInput = pullFlash('admin_meter_old', []);
        $pageTitle = 'Ghi chỉ số điện nước - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/meter_readings.php';
    }

    /**
     * Lưu chỉ số hàng loạt hoặc theo từng dòng phòng nhưng vẫn giữ validate độc lập từng ô.
     */
    public function saveMeterReadings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-meter-readings');
        }
        verify_csrf();

        $period = MeterReadingModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $submittedReadings = $_POST['readings'] ?? [];
        $saveRoomId = (int)($_POST['save_room_id'] ?? 0);
        $result = MeterReadingModel::saveReadings(
            $period['month'],
            $period['year'],
            is_array($submittedReadings) ? $submittedReadings : [],
            ['room_id' => $saveRoomId > 0 ? $saveRoomId : null]
        );

        if (!empty($result['saved_count'])) {
            $prefix = $saveRoomId > 0 ? 'Đã lưu chỉ số cho dòng phòng đã chọn.' : 'Đã lưu chỉ số thành công.';
            $detail = [];
            if (!empty($result['created_count'])) {
                $detail[] = 'Thêm mới ' . (int)$result['created_count'] . ' dòng';
            }
            if (!empty($result['updated_count'])) {
                $detail[] = 'cập nhật ' . (int)$result['updated_count'] . ' dòng';
            }
            setFlash('admin_meter_message', $prefix . (!empty($detail) ? ' ' . ucfirst(implode(', ', $detail)) . '.' : ''));
        }

        if (!empty($result['form_error']) || !empty($result['errors'])) {
            $errorMessage = $result['form_error'] ?? 'Một số dòng chưa hợp lệ. Hệ thống đã tô đỏ các ô cần kiểm tra.';
            if (empty($result['form_error']) && !empty($result['saved_count'])) {
                $errorMessage = 'Một số dòng chưa hợp lệ. Hệ thống đã lưu phần đúng và giữ lại phần lỗi để bạn sửa tiếp.';
            }

            setFlash('admin_meter_error', $errorMessage);
            setFlash('admin_meter_row_errors', $result['errors']);
            setFlash('admin_meter_old', is_array($submittedReadings) ? $submittedReadings : []);
        }

        redirectTo('admin-meter-readings', [
            'month' => $period['month'],
            'year' => $period['year'],
        ]);
    }


    /**
     * Tạo mới hoặc cập nhật khu theo module `areas`.
     */
    /**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Chức năng: Tạo/sửa khu.
     * Thay đổi:
     *   - Bỏ area_code, tự suy mã từ tên
     *   - Ảnh khu: xử lý file upload thay vì URL text
     *   - Floor builder mới: nhận floor_rooms[N] từ hidden inputs
     */
    public function saveArea()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'        => trim((string)($_POST['name'] ?? '')),
            'address'     => trim((string)($_POST['address'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'image'       => '',
        ];

        // Nếu không nhập tên, tự đặt tên mặc định
        if ($data['name'] === '') {
            $data['name'] = 'Khu mới ' . date('d/m/Y H:i');
        }

        // === XỬ LÝ UPLOAD ẢNH KHU ===
        $uploadedImageUrl = $this->handleAreaImageUpload($id);
        if ($uploadedImageUrl !== null) {
            $data['image'] = $uploadedImageUrl;
        } elseif ($id > 0) {
            // Khi sửa, nếu không upload ảnh mới thì giữ ảnh cũ
            $existingArea = AreaModel::getById($id);
            $data['image'] = $existingArea['image'] ?? '';
        }

        // ==== CHỨC NĂNG SỬA: chỉ cập nhật thông tin khu ====
        if ($id > 0) {
            AreaModel::save($data, $id);
            setFlash('admin_area_message', 'Đã cập nhật thông tin khu.');
            redirectTo('admin-areas', ['area' => $id]);
        }

        // ==== CHỨC NĂNG TẠO MỚI: khu -> tầng -> phòng nháp ====
        $areaCode = $this->deriveAreaCode($data['name'], '');
        $floorCount = max(1, min(50, (int)($_POST['floor_count'] ?? 1)));
        $floorRooms = is_array($_POST['floor_rooms'] ?? null) ? $_POST['floor_rooms'] : [];

        $areaId = (int)AreaModel::save($data, null);
        $createdRooms = 0;

        for ($n = 1; $n <= $floorCount; $n++) {
            $roomLimit = max(0, min(50, (int)($floorRooms[$n] ?? 0)));
            $floorId = (int)FloorModel::save([
                'area_id'      => $areaId,
                'name'         => 'Tầng ' . $n,
                'floor_number' => $n,
                'room_limit'   => $roomLimit,
            ], null);
            $createdRooms += $this->createRoomSlots($floorId, $n, $roomLimit);
        }

        setFlash(
            'admin_area_message',
            "Đã tạo khu với {$floorCount} tầng và {$createdRooms} phòng nháp. " .
                "Hệ thống đã chuyển sang Quản lý Phòng — hoàn thiện từng phòng để đăng lên website."
        );
        redirectTo('admin-rooms', ['area_id' => $areaId]);
    }

    /**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Xử lý upload ảnh khu từ file. Trả về URL hoặc null nếu không có file.
     */
    private function handleAreaImageUpload($areaId = 0)
    {
        $file = $_FILES['area_image'] ?? null;
        if (empty($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            setFlash('admin_area_error', 'Ảnh khu vượt quá 5MB.');
            return null;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            setFlash('admin_area_error', 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
            return null;
        }

        $subFolder = $areaId > 0 ? 'image_khu_' . (int)$areaId : 'image_khu_new';
        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $fileName = 'khu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            setFlash('admin_area_error', 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.');
            return null;
        }

        return BASE_URL . '.uploads/' . $subFolder . '/' . $fileName;
    }

    /** Thêm nhanh 1 tầng (kèm tùy chọn tạo sẵn phòng), vẫn tôn trọng room_limit */
    public function addFloorQuick()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();
        $areaId = (int)($_POST['area_id'] ?? 0);
        $area = $areaId > 0 ? AreaModel::getById($areaId) : null;
        if (!$area) {
            setFlash('admin_area_error', 'Khu không tồn tại.');
            redirectTo('admin-areas');
        }
        $next = 1;
        foreach (FloorModel::getByAreaId($areaId) as $floor) {
            $next = max($next, (int)($floor['floor_number'] ?? 0) + 1);
        }
        $roomLimit = max(0, min(50, (int)($_POST['room_count'] ?? 0)));
        $areaCode = $this->deriveAreaCode($area['name'] ?? '', '');
        $floorId = (int)FloorModel::save([
            'area_id' => $areaId,
            'name' => '',
            'floor_number' => $next,
            'room_limit' => $roomLimit,
        ], null);
        $created = $this->createRoomSlots($floorId, $areaCode, $next, $roomLimit);
        setFlash('admin_area_message', "Đã thêm Tầng {$next}" . ($created > 0 ? " với {$created} phòng nháp." : '.'));
        redirectTo('admin-areas', ['area' => $areaId]);
    }

    /**
     * Xóa khu theo schema mới. DB sẽ tự cascade tầng và phòng liên quan.
     */
    public function deleteArea($id)
    {
        $areaId = (int)$id;
        if ($areaId > 0) {
            $area = AreaModel::getById($areaId);
            if (!$area) {
                setFlash('admin_area_error', 'Khu không tồn tại.');
                redirectTo('admin-areas');
            }
            $roomCount = 0;
            $rentedCount = 0;
            foreach (FloorModel::getByAreaId($areaId) as $floor) {
                $roomCount += (int)($floor['room_count'] ?? 0);
                $rentedCount += (int)($floor['rented_count'] ?? 0);
            }
            if ($rentedCount > 0) {
                setFlash('admin_area_error', 'Khu ' . ($area['name'] ?? '') . ' không thể xóa. Lý do: Khu ' . ($area['name'] ?? '') . ' này có phòng vẫn đang hoạt động, không thể xóa.');
                redirectTo('admin-areas', ['area' => $areaId]);
            }
            AreaModel::delete($areaId);
            setFlash('admin_area_message', 'Đã xóa khu ' . ($area['name'] ?? '') . ($roomCount > 0 ? ' cùng ' . $roomCount . ' phòng trống.' : '.'));
        }

        redirectTo('admin-areas');
    }

    /**
     * Tạo mới hoặc cập nhật tầng.
     */
    public function saveFloor()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $data = [
                'area_id' => (int)($_POST['area_id'] ?? 0),
                'name' => trim((string)($_POST['name'] ?? '')),
                'floor_number' => (int)($_POST['floor_number'] ?? 1),
            ];
            $id = (int)($_POST['id'] ?? 0);

            if ($data['area_id'] > 0) {
                FloorModel::save($data, $id > 0 ? $id : null);
            }

            redirectTo('admin-floors', ['area_id' => $data['area_id']]);
        }

        redirectTo('admin-floors');
    }

    /**
     * Xóa tầng. Theo schema hiện tại thao tác này sẽ kéo theo xóa phòng thuộc tầng.
     */
    public function deleteFloor($id)
    {
        $floor = FloorModel::getById($id);
        if ($floor) {
            $areaId = (int)($floor['area_id'] ?? 0);
            $rentedCount = 0;
            foreach (FloorModel::getByAreaId($areaId) as $f) {
                if ((int)($f['id'] ?? 0) === (int)$id) {
                    $rentedCount = (int)($f['rented_count'] ?? 0);
                    break;
                }
            }
            if ($rentedCount > 0) {
                setFlash('admin_area_error', 'Tầng ' . ($floor['name'] ?? '') . ' không thể xóa. Lý do: Tầng này có phòng vẫn đang hoạt động, không thể xóa.');
                redirectTo('admin-floors', ['area_id' => $areaId]);
            }
            FloorModel::delete($id);
            setFlash('admin_area_message', 'Đã xóa tầng ' . ($floor['name'] ?? '') . '.');
            redirectTo('admin-floors', ['area_id' => $areaId]);
        }

        redirectTo('admin-floors');
    }

    /**
     * Tạo mới hoặc cập nhật tiện ích hiển thị ngoài landing page.
     */
    public function saveAmenity()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-amenities');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $data = $this->normalizeAmenityInput($_POST);
        $iconKeys = $this->getAllowedAmenityIconKeys();

        if ($id > 0 && !AmenityModel::getById($id)) {
            setFlash('admin_amenity_error', 'Tiện ích cần cập nhật không tồn tại.');
            redirectTo('admin-amenities');
        }

        if ($data['title'] === '') {
            setFlash('admin_amenity_error', 'Tên tiện ích là bắt buộc.');
            setFlash('admin_amenity_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-amenities', $id > 0 ? ['edit' => $id] : []);
        }

        if (!in_array($data['icon'], $iconKeys, true)) {
            setFlash('admin_amenity_error', 'Icon tiện ích không hợp lệ.');
            setFlash('admin_amenity_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-amenities', $id > 0 ? ['edit' => $id] : []);
        }

        $savedId = AmenityModel::save($data, $id > 0 ? $id : null);
        setFlash('admin_amenity_message', $id > 0 ? 'Đã cập nhật tiện ích thành công.' : 'Đã thêm tiện ích mới thành công.');
        redirectTo('admin-amenities', ['edit' => (int)$savedId]);
    }

    /**
     * Xóa một tiện ích khỏi danh sách quản trị.
     */
    public function deleteAmenity($id)
    {
        $amenity = AmenityModel::getById($id);
        if (!$amenity) {
            setFlash('admin_amenity_error', 'Tiện ích không tồn tại hoặc đã bị xóa trước đó.');
            redirectTo('admin-amenities');
        }

        AmenityModel::delete($id);
        setFlash('admin_amenity_message', 'Đã xóa tiện ích thành công.');
        redirectTo('admin-amenities');
    }

    /**
     * Lưu cấu hình giao diện và kiểm duyệt đánh giá theo cơ chế UPSERT `settings`.
     */
    public function saveSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-settings');
        }
        verify_csrf();

        $fieldMap = $this->getAdminSettingFieldMap();
        $submittedValues = $_POST['settings'] ?? [];
        $clearFlags = $_POST['settings_clear'] ?? [];
        $currentSettings = SettingModel::loadAll();

        foreach ($fieldMap as $key => $field) {
            if (($field['type'] ?? 'text') === 'password') {
                $typedValue = trim((string)($submittedValues[$key] ?? ''));
                if (!empty($clearFlags[$key])) {
                    $value = '';
                } elseif ($typedValue !== '') {
                    $value = $typedValue;
                } else {
                    $value = (string)($currentSettings[$key] ?? ($field['default'] ?? ''));
                }
            } elseif (($field['type'] ?? 'text') === 'toggle') {
                $value = !empty($submittedValues[$key]) ? '1' : '0';
            } else {
                $value = trim((string)($submittedValues[$key] ?? ($field['default'] ?? '')));
            }

            $validationError = $this->validateSettingField($field, $value);
            if ($validationError !== null) {
                setFlash('admin_dashboard_error', $validationError);
                redirectTo('admin-settings');
            }

            SettingModel::set($key, $value, $field['group'] ?? null);
        }

        // Don anh hero cu trong image_page_home: chi giu anh dang duoc dung
        $this->cleanupAppliedSlotImage(trim((string)($submittedValues['hero_image'] ?? '')), 'image_page_home');

        RoomModel::resetSettingsCache();
        SettingModel::loadAll();
        setFlash('admin_dashboard_message', 'Đã lưu cấu hình thành công.');
        redirectTo('admin-settings');
    }

    /**
     * Trang cấu hình hệ thống: xem trước thu nhỏ website (home + intro) trước khi áp dụng.
     */
    public function settingsEditor()
    {
        $settingSections = $this->populateAdminSettingSections();
        $dashboardMessage = pullFlash('admin_dashboard_message');
        $dashboardError = pullFlash('admin_dashboard_error');
        $pageTitle = 'Cấu hình hệ thống - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/settings_editor.php';
    }

    /**
     * Upload ảnh theo slot: home | area_new | area_{id}.
     * File được đặt vào thư mục con tương ứng trong .uploads và đổi tên theo ngữ cảnh.
     */
    public function uploadImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        verify_csrf();

        $file = $_FILES['image'] ?? null;
        if (empty($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Chưa chọn được tệp ảnh hợp lệ.']);
            exit;
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Ảnh vượt quá 5MB.']);
            exit;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.']);
            exit;
        }

        $slot = trim((string)($_POST['slot'] ?? 'home'));
        $subFolder = 'image_page_home';
        $filePrefix = 'home-hero';
        if ($slot === 'area_new') {
            $subFolder = 'image_khu_new';
            $filePrefix = 'khu-new';
        } elseif (preg_match('/^area_(\d+)$/', $slot, $slotMatch)) {
            $subFolder = 'image_khu_' . (int)$slotMatch[1];
            $filePrefix = 'khu-' . (int)$slotMatch[1];
        } elseif ($slot === 'room_new') {
            $subFolder = 'image_phong_new';
            $filePrefix = 'phong-new';
        } elseif (preg_match('/^room_(\d+)$/', $slot, $slotMatch)) {
            $subFolder = 'image_phong_' . (int)$slotMatch[1];
            $filePrefix = 'phong-' . (int)$slotMatch[1];
        } elseif ($slot === 'room_new') {
            $subFolder = 'image_phong_new';
            $filePrefix = 'phong-new';
        } elseif (preg_match('/^room_(\d+)$/', $slot, $slotMatch)) {
            $subFolder = 'image_phong_' . (int)$slotMatch[1];
            $filePrefix = 'phong-' . (int)$slotMatch[1];
        }

        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $fileName = $filePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.']);
            exit;
        }

        echo json_encode(['ok' => true, 'url' => BASE_URL . '.uploads/' . $subFolder . '/' . $fileName]);
        exit;
    }

    /**
     * Đối chiếu URL ảnh upload về đường dẫn file cục bộ (chặn path traversal).
     */
    private function resolveUploadLocalPath($url)
    {
        $prefix = BASE_URL . '.uploads/';
        if (!is_string($url) || strpos($url, $prefix) !== 0) {
            return null;
        }
        $rel = substr($url, strlen($prefix));
        if (strpos($rel, '..') !== false) {
            return null;
        }
        $local = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($local) ? $local : null;
    }

    /**
     * Sau khi áp dụng ảnh mới: chỉ giữ ảnh đang dùng, xóa các ảnh khác cùng thư mục slot.
     */
    private function cleanupAppliedSlotImage($imageUrl, $folderName)
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== $folderName) {
            return;
        }
        $dir = dirname($local);
        $keep = basename($local);
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === $keep) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Khu mới tạo: chuyển ảnh từ image_khu_new sang image_khu_{id}, cập nhật DB,
     * rồi dọn ảnh nháp còn sót trong image_khu_new.
     */
    private function finalizeNewAreaImage($areaId, $imageUrl)
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== 'image_khu_new') {
            return $imageUrl;
        }
        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_khu_' . (int)$areaId;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        $fileName = basename($local);
        $dest = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@rename($local, $dest)) {
            return $imageUrl;
        }
        $newUrl = BASE_URL . '.uploads/image_khu_' . (int)$areaId . '/' . $fileName;
        Database::update('areas', ['image' => $newUrl], 'id = :id', ['id' => (int)$areaId]);

        $tmpDir = dirname($local);
        foreach (scandir($tmpDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $tmpDir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        return $newUrl;
    }

    /** LƯU PHÒNG: chặn vượt room_limit khi tạo mới; đủ dữ liệu => tự đăng web (available) */
    public function saveRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rooms');
        }
        verify_csrf();
        $redirectParams = $this->getRoomAdminFilters($_POST);
        $id = (int)($_POST['id'] ?? 0);
        $status = $this->normalizeRoomStatus($_POST['status'] ?? 'draft', 'draft');

        // --- Đổi trạng thái nhanh từ danh sách ---
        if (!empty($_POST['quick_status_update'])) {
            $room = RoomModel::getById($id);
            if (!$room) {
                setFlash('admin_room_error', 'Phòng không tồn tại.');
                redirectTo('admin-rooms', $redirectParams);
            }
            if ($status === 'available' && !$this->roomIsComplete($room)) {
                setFlash('admin_room_error', 'Phòng chưa đủ thông tin (tên, giá > 0, diện tích > 0, mô tả) nên không thể đăng công khai.');
                redirectTo('admin-rooms', $redirectParams);
            }
            RoomModel::updateStatus($id, $status);
            setFlash('admin_room_message', 'Đã cập nhật trạng thái phòng.');
            redirectTo('admin-rooms', $redirectParams);
        }

        $data = [
            'floor_id'      => (int)($_POST['floor_id'] ?? 0),
            'name'          => trim((string)($_POST['name'] ?? '')),
            'position'      => (int)($_POST['position'] ?? 0),
            'price'         => (float)($_POST['price'] ?? 0),
            'area'          => (float)($_POST['area'] ?? 0),
            'max_occupancy' => (int)($_POST['max_occupancy'] ?? 2),
            'description'   => trim((string)($_POST['description'] ?? '')),
            'amenities'     => trim((string)($_POST['amenities'] ?? '')),
            'thumbnail'     => trim((string)($_POST['thumbnail'] ?? '')),
        ];
        $formState = array_merge($data, ['id' => $id, 'area_id' => (int)($_POST['area_id'] ?? 0)]);

        $floor = RoomModel::floorExists($data['floor_id']) ? FloorModel::getById($data['floor_id']) : null;
        if (!$floor) {
            setFlash('admin_room_error', 'Tầng không hợp lệ hoặc không tồn tại.');
            setFlash('admin_room_old', $formState);
            redirectTo('admin-rooms', $redirectParams);
        }
        if ($formState['area_id'] > 0 && (int)($floor['area_id'] ?? 0) !== $formState['area_id']) {
            setFlash('admin_room_error', 'Tầng đã chọn không thuộc khu đã chọn.');
            setFlash('admin_room_old', $formState);
            redirectTo('admin-rooms', $redirectParams);
        }

        // --- CHẶN VƯỢT GIỚI HẠN SỐ PHÒNG CỦA TẦNG khi tạo mới ---
        if ($id === 0) {
            $limit = (int)($floor['room_limit'] ?? 0);
            $currentCount = count(RoomModel::getAll(['floor_id' => (int)$floor['id']]));
            if ($limit > 0 && $currentCount >= $limit) {
                setFlash('admin_room_error', "Tầng này đã đạt giới hạn {$limit} phòng — không thể tạo vượt quá.");
                setFlash('admin_room_old', $formState);
                redirectTo('admin-rooms', $redirectParams);
            }
            if ($data['position'] <= 0) {
                $data['position'] = $currentCount + 1;
            }

            // TỰ SINH TÊN PHÒNG nếu người dùng không nhập
            if ($data['name'] === '') {
                $area = AreaModel::getById((int)$floor['area_id']);
                $code = $this->deriveAreaCode($area['name'] ?? '', '');
                $data['name'] = $code . (int)$floor['floor_number'] . str_pad((string)$data['position'], 2, '0', STR_PAD_LEFT);
            }
        }

        if ($data['max_occupancy'] <= 0) {
            setFlash('admin_room_error', 'Sức chứa tối đa phải lớn hơn 0.');
            setFlash('admin_room_old', $formState);
            redirectTo('admin-rooms', $redirectParams);
        }

        // --- QUY TẮC ĐĂNG WEB: đủ dữ liệu => available, thiếu => draft ---
        $complete = $this->roomIsComplete($data);
        $data['status'] = $complete ? ($status === 'maintenance' ? 'maintenance' : 'available') : 'draft';

        RoomModel::save($data, $id > 0 ? $id : null);
        setFlash('admin_room_message', $complete
            ? 'Phòng đã đủ thông tin và ĐƯỢC đăng lên website.'
            : 'Đã lưu phòng NHÁP — chưa hiển thị web. Điền đủ: giá > 0, diện tích > 0, mô tả để đăng web.');
        redirectTo('admin-rooms', [
            'area_id'  => (int)($floor['area_id'] ?? 0),
            'floor_id' => (int)$data['floor_id'],
        ]);
    }

    /**
     * Xóa phòng nhưng chặn cứng khi vẫn còn người đang được gán ở phòng đó.
     */
    /**
     * [DEV-QWEN-A][NHOM-2][2026-08-08]
     * FIX: chặn xóa phòng khi status = 'rented' (ngoài chặn cũ khi còn người ở).
     * Backend là chốt chặn cuối — dù view có bị bypass cũng không xóa được.
     */
    public function deleteRoom($id)
    {
        $redirectParams = $this->getRoomAdminFilters($_GET);
        $room = RoomModel::getById($id);
        if (!$room) {
            setFlash('admin_room_error', 'Phòng không tồn tại hoặc đã bị xóa trước đó.');
            redirectTo('admin-rooms', $redirectParams);
        }
        if (RoomModel::hasActiveOccupants($id)) {
            setFlash('admin_room_error', 'Phòng đang có người ở! Không thể xóa.');
            redirectTo('admin-rooms', $redirectParams);
        }
        if ((string)($room['status'] ?? '') === 'rented') {
            setFlash('admin_room_error', 'Phòng "' . ($room['name'] ?? '') . '" đang ở trạng thái đã thuê — hệ thống chặn xóa. Hãy kết thúc hợp đồng hoặc chuyển trạng thái trước.');
            redirectTo('admin-rooms', $redirectParams);
        }
        RoomModel::delete($id);
        setFlash('admin_room_message', 'Đã xóa phòng thành công.');
        redirectTo('admin-rooms', $redirectParams);
    }

    /**
     * Khai báo schema field cho dashboard admin để view render động, không hard-code rải rác.
     */
    private function buildAdminSettingSections()
    {
        return [
            [
                'id' => 'brand',
                'title' => 'Thương hiệu',
                'icon' => 'storefront',
                'description' => 'Tên website, slogan và mô tả thương hiệu dùng chung cho header, footer và meta công khai.',
                'fields' => [
                    [
                        'key' => 'site_name',
                        'label' => 'Tên website',
                        'type' => 'text',
                        'group' => 'brand',
                        'default' => 'NhaTroA',
                        'placeholder' => 'Ví dụ: Nhà trọ Xanh',
                        'tooltip' => 'Tên hiển thị trên tab trình duyệt, header và panel quản trị.',
                    ],
                    [
                        'key' => 'site_slogan',
                        'label' => 'Slogan',
                        'type' => 'text',
                        'group' => 'brand',
                        'default' => 'Trang chính thức của khu trọ',
                        'placeholder' => 'Một câu ngắn tạo cảm giác tin cậy',
                        'tooltip' => 'Câu giới thiệu ngắn xuất hiện nổi bật ở phần đầu trang.',
                    ],
                    [
                        'key' => 'site_description',
                        'label' => 'Mô tả website',
                        'type' => 'textarea',
                        'group' => 'brand',
                        'default' => 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ với chủ trọ.',
                        'rows' => 3,
                        'placeholder' => 'Mô tả ngắn về khu trọ và trải nghiệm người thuê',
                        'tooltip' => 'Dùng cho phần giới thiệu và meta description cơ bản.',
                    ],
                ],
            ],
            [
                'id' => 'hero',
                'title' => 'Hero Banner',
                'icon' => 'image',
                'description' => 'Điều khiển tiêu đề lớn, ảnh đầu trang và đoạn mô tả chính của landing page.',
                'fields' => [
                    [
                        'key' => 'hero_headline_1',
                        'label' => 'Headline dòng 1',
                        'type' => 'text',
                        'group' => 'hero',
                        'default' => 'Xem Phòng Rõ',
                        'placeholder' => 'Ví dụ: Không Gian Sống',
                        'tooltip' => 'Dòng tiêu đề đầu tiên hiển thị cỡ lớn trên ảnh hero.',
                    ],
                    [
                        'key' => 'hero_headline_2',
                        'label' => 'Headline dòng 2',
                        'type' => 'text',
                        'group' => 'hero',
                        'default' => 'Chọn Chỗ Ở Dễ',
                        'placeholder' => 'Ví dụ: Chuẩn Mực',
                        'tooltip' => 'Dòng nhấn mạnh bằng gradient để tăng điểm nhấn thị giác.',
                    ],
                    [
                        'key' => 'hero_subheadline',
                        'label' => 'Mô tả hero',
                        'type' => 'textarea',
                        'group' => 'hero',
                        'default' => 'Xem phòng trống, tiện ích và mức giá rõ ràng trước khi liên hệ.',
                        'rows' => 3,
                        'placeholder' => 'Đoạn mô tả ngắn ngay dưới headline',
                        'tooltip' => 'Nội dung mô tả chính ngay dưới tiêu đề lớn của trang chủ.',
                    ],
                    [
                        'key' => 'hero_image',
                        'label' => 'Ảnh hero',
                        'type' => 'url',
                        'group' => 'hero',
                        'default' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600',
                        'placeholder' => 'https://...',
                        'tooltip' => 'URL ảnh nền đầu trang. Nên dùng ảnh ngang rõ ánh sáng và không bị vỡ.',
                    ],
                ],
            ],
            [
                'id' => 'contact',
                'title' => 'Liên hệ',
                'icon' => 'contact_phone',
                'description' => 'Thông tin liên hệ hiển thị ở footer, chi tiết phòng và các điểm CTA công khai.',
                'fields' => [
                    [
                        'key' => 'contact_address',
                        'label' => 'Địa chỉ',
                        'type' => 'textarea',
                        'group' => 'contact',
                        'default' => 'Khu Công nghệ cao, TP. Thủ Đức, TP.HCM',
                        'rows' => 3,
                        'placeholder' => 'Địa chỉ đầy đủ của khu trọ',
                        'tooltip' => 'Thông tin địa chỉ hiển thị ở footer và các khu vực liên hệ.',
                    ],
                    [
                        'key' => 'contact_phone',
                        'label' => 'Số điện thoại',
                        'type' => 'tel',
                        'group' => 'contact',
                        'default' => '0901 234 567',
                        'placeholder' => '0901 234 567',
                        'tooltip' => 'Số hotline dùng cho nút gọi nhanh và hỗ trợ cư dân.',
                    ],
                    [
                        'key' => 'contact_email',
                        'label' => 'Email',
                        'type' => 'email',
                        'group' => 'contact',
                        'default' => 'admin@nhatroa.vn',
                        'placeholder' => 'admin@nhatroa.vn',
                        'tooltip' => 'Email hiển thị công khai cho người xem cần liên hệ chi tiết.',
                    ],
                    [
                        'key' => 'contact_zalo',
                        'label' => 'Zalo',
                        'type' => 'text',
                        'group' => 'contact',
                        'default' => '0901234567',
                        'placeholder' => 'Số điện thoại Zalo',
                        'tooltip' => 'Giúp đồng bộ CTA qua Zalo nếu sau này cần mở rộng.',
                    ],
                ],
            ],
            [
                'id' => 'stats',
                'title' => 'Chỉ số nổi bật',
                'icon' => 'bar_chart',
                'description' => 'Nhóm giá trị hiển thị nổi bật trên landing page để truyền tải điểm mạnh của khu trọ.',
                'fields' => [
                    [
                        'key' => 'stat_1_label',
                        'label' => 'Nhãn chỉ số 1',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Phòng đang mở xem',
                        'placeholder' => 'Ví dụ: Phòng đang trống',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số đầu tiên ở hero.',
                    ],
                    [
                        'key' => 'stat_1_value',
                        'label' => 'Giá trị chỉ số 1',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '18+',
                        'placeholder' => 'Ví dụ: 18+',
                        'tooltip' => 'Có thể nhập số, text ngắn hoặc dạng 24/7, 20+.',
                    ],
                    [
                        'key' => 'stat_2_label',
                        'label' => 'Nhãn chỉ số 2',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Khu vận hành ổn định',
                        'placeholder' => 'Ví dụ: Khu đang vận hành',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ hai.',
                    ],
                    [
                        'key' => 'stat_2_value',
                        'label' => 'Giá trị chỉ số 2',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '3 khu',
                        'placeholder' => 'Ví dụ: 3 khu',
                        'tooltip' => 'Giá trị hiển thị của chỉ số thứ hai.',
                    ],
                    [
                        'key' => 'stat_3_label',
                        'label' => 'Nhãn chỉ số 3',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Hỗ trợ cư dân',
                        'placeholder' => 'Ví dụ: Hỗ trợ 24/7',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ ba.',
                    ],
                    [
                        'key' => 'stat_3_value',
                        'label' => 'Giá trị chỉ số 3',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '24/7',
                        'placeholder' => 'Ví dụ: 24/7',
                        'tooltip' => 'Giá trị hiển thị của chỉ số thứ ba.',
                    ],
                ],
            ],
            [
                'id' => 'moderation',
                'title' => 'Kiểm duyệt đánh giá',
                'icon' => 'shield_lock',
                'description' => 'Thiết lập điều kiện gửi/sửa đánh giá và bật tắt kiểm duyệt nội dung bằng Gemini API.',
                'fields' => [
                    [
                        'key' => 'enable_comment_moderation',
                        'label' => 'Bật kiểm duyệt đánh giá',
                        'type' => 'toggle',
                        'group' => 'moderation',
                        'default' => '1',
                        'tooltip' => 'Tắt để bỏ qua lọc từ cấm và các bước kiểm duyệt nội dung khi tenant gửi đánh giá.',
                    ],
                    [
                        'key' => 'min_days_to_review',
                        'label' => 'Số ngày ở tối thiểu',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '15',
                        'min' => 0,
                        'placeholder' => '15',
                        'tooltip' => 'Người thuê phải ở tối thiểu bao nhiêu ngày mới được đánh giá.',
                    ],
                    [
                        'key' => 'comment_edit_hours',
                        'label' => 'Thời gian sửa/xóa',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '24',
                        'min' => 1,
                        'placeholder' => '24',
                        'suffix' => 'giờ',
                        'tooltip' => 'Khoảng thời gian sau khi gửi mà người thuê còn được sửa hoặc xóa đánh giá.',
                    ],
                    [
                        'key' => 'max_comment_attempts',
                        'label' => 'Số lần vi phạm tối đa',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '3',
                        'min' => 1,
                        'placeholder' => '3',
                        'tooltip' => 'Sau số lần vi phạm này, hệ thống sẽ khóa thao tác gửi đánh giá trong thời gian cấu hình.',
                    ],
                    [
                        'key' => 'comment_lock_hours',
                        'label' => 'Thời gian khóa đánh giá',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '24',
                        'min' => 1,
                        'placeholder' => '24',
                        'suffix' => 'giờ',
                        'tooltip' => 'Khoảng thời gian khóa sau khi người dùng vượt ngưỡng vi phạm.',
                    ],
                    [
                        'key' => 'enable_gemini_moderation',
                        'label' => 'Bật Gemini Moderation',
                        'type' => 'toggle',
                        'group' => 'moderation',
                        'default' => '0',
                        'tooltip' => 'Bật để hệ thống gọi Gemini API chấm điểm độc hại cho bình luận.',
                    ],
                    [
                        'key' => 'gemini_api_key',
                        'label' => 'Gemini API Key',
                        'type' => 'password',
                        'group' => 'moderation',
                        'default' => '',
                        'placeholder' => 'Nhập API key mới nếu cần thay đổi',
                        'tooltip' => 'Giữ trống để giữ nguyên khóa cũ. Tích ô xóa nếu muốn reset.',
                    ],
                    [
                        'key' => 'toxicity_threshold',
                        'label' => 'Ngưỡng độc hại',
                        'type' => 'decimal',
                        'group' => 'moderation',
                        'default' => '0.70',
                        'min' => 0,
                        'max' => 1,
                        'step' => '0.01',
                        'placeholder' => '0.70',
                        'tooltip' => 'Giá trị từ 0.00 đến 1.00. Càng thấp thì kiểm duyệt càng chặt.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Gắn giá trị hiện tại vào schema field để view admin chỉ render và không tự suy diễn dữ liệu.
     */
    private function populateAdminSettingSections()
    {
        $settings = SettingModel::loadAll();
        $sections = $this->buildAdminSettingSections();

        foreach ($sections as $sectionIndex => $section) {
            foreach ($section['fields'] as $fieldIndex => $field) {
                $value = $settings[$field['key']] ?? ($field['default'] ?? '');
                $sections[$sectionIndex]['fields'][$fieldIndex]['value'] = (string)$value;
                $sections[$sectionIndex]['fields'][$fieldIndex]['has_value'] = trim((string)$value) !== '';
            }
        }

        return $sections;
    }

    /**
     * Trả map field theo `setting_key` để validate/lưu settings từ dashboard admin.
     */
    private function getAdminSettingFieldMap()
    {
        $fieldMap = [];

        foreach ($this->buildAdminSettingSections() as $section) {
            foreach ($section['fields'] as $field) {
                $fieldMap[$field['key']] = $field;
            }
        }

        return $fieldMap;
    }

    /**
     * Tìm nhanh giá trị của một field trong schema dashboard đã được đổ dữ liệu.
     */
    private function getSettingFieldValue(array $sections, $key)
    {
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if (($field['key'] ?? '') === $key) {
                    return (string)($field['value'] ?? '');
                }
            }
        }

        return '';
    }

    /**
     * Validate dữ liệu settings theo loại field để chặn lỗi cấu hình ngay từ admin.
     */
    private function validateSettingField(array $field, &$value)
    {
        $label = $field['label'] ?? ($field['key'] ?? 'Trường cấu hình');
        $type = $field['type'] ?? 'text';

        if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $label . ' không đúng định dạng email.';
        }

        if ($type === 'url' && $value !== '') {
            $urlOk = filter_var($value, FILTER_VALIDATE_URL);
            if (!$urlOk) {
                // Encode ký tự Unicode (tên thư mục dự án tiếng Việt) rồi kiểm tra lại.
                $encodedUrl = preg_replace_callback('/[^\x20-\x7E]+/', static function ($m) {
                    return rawurlencode($m[0]);
                }, $value);
                $urlOk = filter_var($encodedUrl, FILTER_VALIDATE_URL);
            }
            if (!$urlOk && strpos($value, BASE_URL) === 0) {
                // URL nội bộ (ảnh upload vào .uploads/) luôn được chấp nhận.
                $urlOk = true;
            }
            if (!$urlOk) {
                return $label . ' phải là URL hợp lệ.';
            }
        }

        if ($type === 'number') {
            if ($value === '' || !is_numeric($value)) {
                return $label . ' phải là số nguyên hợp lệ.';
            }

            $value = (string)(int)$value;
            if (isset($field['min']) && (int)$value < (int)$field['min']) {
                return $label . ' không được nhỏ hơn ' . (int)$field['min'] . '.';
            }
            if (isset($field['max']) && (int)$value > (int)$field['max']) {
                return $label . ' không được lớn hơn ' . (int)$field['max'] . '.';
            }
        }

        if ($type === 'decimal') {
            if ($value === '' || !is_numeric($value)) {
                return $label . ' phải là số hợp lệ.';
            }

            $number = (float)$value;
            if (isset($field['min']) && $number < (float)$field['min']) {
                return $label . ' không được nhỏ hơn ' . $field['min'] . '.';
            }
            if (isset($field['max']) && $number > (float)$field['max']) {
                return $label . ' không được lớn hơn ' . $field['max'] . '.';
            }

            $value = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        }

        if ($type === 'toggle') {
            $value = $value === '1' ? '1' : '0';
        }

        return null;
    }

    /**
     * Bộ icon Material dùng cố định cho tiện ích để admin chọn nhanh và tránh nhập icon sai.
     */
    private function getAmenityIconOptions()
    {
        return [
            ['key' => 'wifi', 'label' => 'Wifi'],
            ['key' => 'security', 'label' => 'An ninh'],
            ['key' => 'local_parking', 'label' => 'Bãi xe'],
            ['key' => 'local_laundry_service', 'label' => 'Giặt sấy'],
            ['key' => 'ac_unit', 'label' => 'Điều hòa'],
            ['key' => 'kitchen', 'label' => 'Bếp chung'],
            ['key' => 'water_heater', 'label' => 'Nóng lạnh'],
            ['key' => 'elevator', 'label' => 'Thang máy'],
            ['key' => 'videocam', 'label' => 'Camera'],
            ['key' => 'fingerprint', 'label' => 'Vân tay'],
            ['key' => 'cleaning_services', 'label' => 'Vệ sinh'],
            ['key' => 'yard', 'label' => 'Sân phơi'],
            ['key' => 'bolt', 'label' => 'Điện ổn định'],
            ['key' => 'apartment', 'label' => 'Tiện ích chung'],
        ];
    }

    /**
     * Tạo whitelist icon key để chặn submit icon lạ từ ngoài form.
     */
    private function getAllowedAmenityIconKeys()
    {
        return array_column($this->getAmenityIconOptions(), 'key');
    }

    /**
     * Chuẩn hóa dữ liệu tiện ích từ form admin trước khi đưa vào model.
     */
    private function normalizeAmenityInput(array $source)
    {
        return [
            'icon' => trim((string)($source['icon'] ?? 'apartment')),
            'title' => trim((string)($source['title'] ?? '')),
            'description' => trim((string)($source['description'] ?? '')),
            'sort_order' => (int)($source['sort_order'] ?? 0),
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }

    /**
     * Trả metadata của từng cách tính để view admin render badge và phần giải thích thống nhất.
     */
    private function getServiceBillingModeOptions()
    {
        return [
            [
                'value' => 'fixed',
                'label' => 'Cố định',
                'badge_class' => 'bg-slate-100 text-slate-700',
                'tooltip' => 'Thu cố định theo chu kỳ, thường dùng cho wifi hoặc phí trọn gói.',
            ],
            [
                'value' => 'meter',
                'label' => 'Theo chỉ số',
                'badge_class' => 'bg-cyan-100 text-cyan-700',
                'tooltip' => 'Tính theo công tơ/chỉ số tiêu thụ thực tế như điện hoặc nước.',
            ],
            [
                'value' => 'per_person',
                'label' => 'Theo người',
                'badge_class' => 'bg-purple-100 text-purple-700',
                'tooltip' => 'Nhân với số người đang ở hoặc người được áp dụng.',
            ],
            [
                'value' => 'per_unit',
                'label' => 'Theo số lượng',
                'badge_class' => 'bg-amber-100 text-amber-700',
                'tooltip' => 'Nhân theo số lượng đăng ký như số xe, số bình, số thiết bị.',
            ],
        ];
    }

    /**
     * Trả metadata đối tượng áp dụng để view không phải tự định nghĩa text hiển thị.
     */
    private function getServiceAppliesToOptions()
    {
        return [
            [
                'value' => 'room',
                'label' => 'Theo phòng',
                'tooltip' => 'Một dịch vụ được gán cho cả phòng. Có thể phát sinh số lượng riêng theo từng phòng.',
            ],
            [
                'value' => 'person',
                'label' => 'Theo người',
                'tooltip' => 'Mỗi cư dân tự đăng ký riêng, không ảnh hưởng người ở cùng phòng.',
            ],
        ];
    }

    /**
     * Trả whitelist cách tính để validate request từ form admin.
     */
    private function getAllowedServiceBillingModes()
    {
        return array_column($this->getServiceBillingModeOptions(), 'value');
    }

    /**
     * Trả whitelist đối tượng áp dụng để chặn submit giá trị lạ.
     */
    private function getAllowedServiceAppliesTo()
    {
        return array_column($this->getServiceAppliesToOptions(), 'value');
    }

    /**
     * Chuẩn hóa input dịch vụ từ form admin trước khi chuyển xuống model.
     */
    private function normalizeServiceInput(array $source)
    {
        return [
            'name' => trim((string)($source['name'] ?? '')),
            'price' => (float)($source['price'] ?? 0),
            'unit' => trim((string)($source['unit'] ?? 'tháng')),
            'icon' => trim((string)($source['icon'] ?? 'settings')),
            'description' => trim((string)($source['description'] ?? '')),
            'billing_mode' => trim((string)($source['billing_mode'] ?? 'fixed')),
            'applies_to' => trim((string)($source['applies_to'] ?? 'room')),
            'is_required' => !empty($source['is_required']) ? 1 : 0,
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }

    /**
     * Chuẩn hóa bộ filter cho trang admin-rooms để cả GET lẫn POST dùng chung một nguồn.
     */
    private function getRoomAdminFilters(array $source)
    {
        $status = $this->normalizeRoomStatus($source['return_status'] ?? ($source['status'] ?? ''), '');

        return [
            'area_id' => (int)($source['return_area_id'] ?? ($source['area_id'] ?? 0)),
            'floor_id' => (int)($source['return_floor_id'] ?? ($source['floor_id'] ?? 0)),
            'status' => $status,
        ];
    }

    /**
     * Chỉ cho phép các trạng thái hợp lệ của phòng để tránh update sai enum.
     */
    private function normalizeRoomStatus($status, $default = '')
    {
        $allowedStatuses = ['draft', 'available', 'rented', 'maintenance'];
        return in_array($status, $allowedStatuses, true) ? $status : $default;
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
     * Trang quản lý yêu cầu thuê phòng (hàng đợi pending + lịch sử).
     */
    public function rentRequests()
    {
        $statusFilter = trim((string)($_GET['status'] ?? 'pending'));
        $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
        if (!in_array($statusFilter, $allowedStatuses, true)) {
            $statusFilter = 'pending';
        }
        $requests = RentalRequestModel::getAllWithDetails(['status' => $statusFilter]);
        $message = pullFlash('rent_request_message', '');
        $error = pullFlash('rent_request_error', '');
        $pageTitle = 'Yêu cầu thuê phòng - NhaTroA';
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
     * Admin xem danh sách yêu cầu ở ghép (kèm tên người gửi / người nhận / phòng).
     */
    public function roommateRequests()
    {
        $statusFilter = trim((string)($_GET['status'] ?? 'pending'));
        $allowed = ['pending', 'approved', 'rejected', 'admin_rejected'];
        if (!in_array($statusFilter, $allowed, true)) {
            $statusFilter = 'pending';
        }
        $requests = RoommateRequestModel::getAll(['status' => $statusFilter]);
        foreach ($requests as &$row) {
            $req = UserModel::getById((int)$row['requester_id']);
            $host = UserModel::getById((int)$row['host_user_id']);
            $room = RoomModel::getById((int)$row['room_id']);
            $row['requester_name'] = (string)($req['full_name'] ?? '');
            $row['host_name'] = (string)($host['full_name'] ?? '');
            $row['room_name'] = (string)($room['name'] ?? '');
        }
        unset($row);
        $message = pullFlash('roommate_admin_message', '');
        $error = pullFlash('roommate_admin_error', '');
        $pageTitle = 'Yêu cầu ở ghép - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants/roommate_requests.php';
    }

    /**
     * Admin veto yêu cầu ở ghép: nếu đã duyệt (B đã vào phòng) thì gỡ B ra.
     */
    public function vetoRoommate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-roommate-requests');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RoommateRequestModel::getById($requestId);
        if (!$request) {
            setFlash('roommate_admin_error', 'Yêu cầu không tồn tại.');
            redirectTo('admin-roommate-requests');
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
                redirectTo('admin-roommate-requests');
            }
            RoommateRequestModel::setStatus($requestId, 'admin_rejected');
            NotificationModel::create([
                'user_id' => $requesterId,
                'type' => 'general',
                'title' => 'Yêu cầu ở ghép bị admin từ chối',
                'content' => 'Admin đã từ chối yêu cầu ở ghép của bạn và gỡ bạn khỏi phòng.',
            ]);
            setFlash('roommate_admin_message', 'Đã veto và gỡ người ở ghép khỏi phòng.');
        } elseif ($status === 'pending') {
            RoommateRequestModel::setStatus($requestId, 'admin_rejected');
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
        redirectTo('admin-roommate-requests');
    }

    /**
     * Trang quản lý bảo trì: danh sách đề xuất + form tạo mới + chạy lazy date-trigger.
     */
    public function maintenance()
    {
        MaintenanceRequestModel::activateDue();
        $statusFilter = trim((string)($_GET['status'] ?? 'pending'));
        $allowed = ['pending', 'active', 'rejected', 'completed'];
        if (!in_array($statusFilter, $allowed, true)) {
            $statusFilter = 'pending';
        }
        $requests = MaintenanceRequestModel::getAll(['status' => $statusFilter]);
        $roomsMap = [];
        foreach (RoomModel::getAll() as $roomRow) {
            $roomsMap[(int)($roomRow['id'] ?? 0)] = $roomRow;
        }
        foreach ($requests as &$row) {
            $roomInfo = $roomsMap[(int)($row['room_id'] ?? 0)] ?? null;
            $row['room_name'] = (string)($roomInfo['name'] ?? '');
            $row['area_name'] = (string)($roomInfo['area_name'] ?? '');
        }
        unset($row);
        $rentedRooms = RoomModel::getAll(['status' => 'rented']);
        $message = pullFlash('maintenance_admin_message', '');
        $error = pullFlash('maintenance_admin_error', '');
        $pageTitle = 'Bảo trì - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/maintenance.php';
    }

    /**
     * Admin đề xuất bảo trì phòng đang thuê + thông báo cho toàn bộ cư dân trong phòng.
     */
    public function proposeMaintenance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-maintenance');
        }
        verify_csrf();
        $roomId = (int)($_POST['room_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $durationDays = (int)($_POST['duration_days'] ?? 1);
        $startDate = trim((string)($_POST['start_date'] ?? ''));

        $room = RoomModel::getById($roomId);
        if (!$room) {
            setFlash('maintenance_admin_error', 'Phòng không tồn tại.');
            redirectTo('admin-maintenance');
        }
        if ((string)($room['status'] ?? '') !== 'rented') {
            setFlash('maintenance_admin_error', 'Chỉ đề xuất bảo trì cho phòng đang thuê.');
            redirectTo('admin-maintenance');
        }
        if ($reason === '') {
            setFlash('maintenance_admin_error', 'Lý do bảo trì là bắt buộc.');
            redirectTo('admin-maintenance');
        }
        if ($durationDays < 1) {
            setFlash('maintenance_admin_error', 'Số ngày bảo trì phải lớn hơn 0.');
            redirectTo('admin-maintenance');
        }
        if ($startDate === '' || strtotime($startDate) === false) {
            setFlash('maintenance_admin_error', 'Ngày bắt đầu bảo trì không hợp lệ.');
            redirectTo('admin-maintenance');
        }
        if (MaintenanceRequestModel::getPendingByRoom($roomId)) {
            setFlash('maintenance_admin_error', 'Phòng này đã có đề xuất bảo trì đang chờ duyệt.');
            redirectTo('admin-maintenance');
        }

        MaintenanceRequestModel::create([
            'room_id' => $roomId,
            'admin_id' => (int)($_SESSION['user_id'] ?? 0),
            'reason' => $reason,
            'duration_days' => $durationDays,
            'start_date' => $startDate,
        ]);

        $occupants = array_filter(
            UserModel::getAll(),
            static fn($u) => (int)($u['room_id'] ?? 0) === $roomId && (int)($u['role'] ?? 1) === 0
        );
        foreach ($occupants as $occupant) {
            NotificationModel::create([
                'user_id' => (int)$occupant['id'],
                'type' => 'general',
                'title' => 'Đề xuất bảo trì phòng',
                'content' => 'Phòng ' . ($room['name'] ?? '') . ' dự kiến bảo trì từ ' . date('d/m/Y', strtotime($startDate)) . ' trong ' . $durationDays . ' ngày. Lý do: ' . $reason . '. Nếu không đồng ý, hãy vào mục "Bảo trì" để từ chối trước ngày bắt đầu.',
            ]);
        }
        setFlash('maintenance_admin_message', 'Đã gửi đề xuất bảo trì tới ' . count($occupants) . ' cư dân trong phòng.');
        redirectTo('admin-maintenance');
    }

    /**
     * Admin hoàn tất bảo trì: đánh dấu completed và trả phòng về trạng thái phù hợp.
     */
    public function completeMaintenance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-maintenance');
        }
        verify_csrf();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = MaintenanceRequestModel::getById($requestId);
        if (!$request || (string)$request['status'] !== 'active') {
            setFlash('maintenance_admin_error', 'Đề xuất bảo trì không hợp lệ hoặc chưa đang diễn ra.');
            redirectTo('admin-maintenance');
        }
        $roomId = (int)$request['room_id'];
        MaintenanceRequestModel::markCompleted($requestId);
        $nextStatus = RoomModel::countOccupants($roomId) > 0 ? 'rented' : 'available';
        RoomModel::updateStatus($roomId, $nextStatus);

        $occupants = array_filter(
            UserModel::getAll(),
            static fn($u) => (int)($u['room_id'] ?? 0) === $roomId && (int)($u['role'] ?? 1) === 0
        );
        foreach ($occupants as $occupant) {
            NotificationModel::create([
                'user_id' => (int)$occupant['id'],
                'type' => 'general',
                'title' => 'Bảo trì hoàn tất',
                'content' => 'Phòng của bạn đã hoàn tất bảo trì và trở lại sử dụng bình thường.',
            ]);
        }
        setFlash('maintenance_admin_message', 'Đã hoàn tất bảo trì và khôi phục trạng thái phòng.');
        redirectTo('admin-maintenance');
    }
}
