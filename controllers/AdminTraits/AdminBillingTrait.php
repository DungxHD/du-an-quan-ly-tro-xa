<?php
/**
 * AdminBillingTrait - Quản lý hóa đơn: preview, danh sách, tạo hóa đơn, xác nhận thanh toán
 */
trait AdminBillingTrait
{
    // ==========================================
    // INVOICES PAGE
    // ==========================================

    /**
     * Trang quản lý hóa đơn: preview per-room, danh sách, xác nhận thanh toán
     */
    public function invoices(): void
    {
        PriceChangeModel::applyDueChanges();

        $month      = (int)($_GET['month'] ?? date('n'));
        $year       = (int)($_GET['year'] ?? date('Y'));
        $page       = max(1, (int)($_GET['p'] ?? 1));
        $perPage    = 20;
        $search     = trim($_GET['search'] ?? '');
        $areaId     = (int)($_GET['area_id'] ?? 0);
        $floorId    = (int)($_GET['floor_id'] ?? 0);
        $status     = trim($_GET['status'] ?? '');

        $filters = [
            'room_id' => (int)($_GET['room_id'] ?? 0),
            'search'  => $search,
            'area_id' => $areaId,
            'floor_id'=> $floorId,
            'status'  => $status,
        ];

        $period = PaymentModel::normalizePeriod($month, $year);

        $invoices = PaymentModel::getInvoices([
            'month'      => $period['month'],
            'year'       => $period['year'],
            'status'     => $status !== '' ? $status : null,
            'area_id'    => $areaId > 0 ? $areaId : null,
            'floor_id'   => $floorId > 0 ? $floorId : null,
            'search'     => $search !== '' ? $search : null,
            'page'       => $page,
            'per_page'   => $perPage,
        ]);

        $areas        = AreaModel::getAllWithStats();
        $allFloors    = FloorModel::getAll();
        $filterFloors = $areaId > 0 ? FloorModel::getByAreaId($areaId) : $allFloors;

        $selectedFloor = $floorId > 0 ? FloorModel::getById($floorId) : null;
        if ($selectedFloor && $areaId <= 0) $areaId = (int)($selectedFloor['area_id'] ?? 0);

        $invoiceMessage = pullFlash('admin_invoice_message');
        $invoiceError   = pullFlash('admin_invoice_error');

        $invoiceRoomRows = PaymentModel::getRoomInvoiceOverview($period['month'], $period['year'], [
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id'=> $floorId > 0 ? $floorId : null,
        ]);

        $meterRoomMap = [];
        foreach ($invoiceRoomRows as $roomRow) {
            $roomId = (int)($roomRow['room_id'] ?? 0);
            if ($roomId <= 0) continue;
            $meterServices = MeterReadingModel::getMeterServicesForRoom($roomId, $period['month'], $period['year']);
            if (empty($meterServices)) continue;
            $meterCells = [];
            foreach ($meterServices as $ms) {
                $sid = (int)($ms['id'] ?? 0);
                $reading = MeterReadingModel::getReadingByPeriod($roomId, $sid, $period['month'], $period['year']);
                $baseline = MeterReadingModel::resolvePeriodBaseline($roomId, $ms, $period['month'], $period['year']);
                $meterCells[$sid] = [
                    'old_index'    => $reading ? (float)($reading['old_index'] ?? 0) : (float)($baseline['old_index'] ?? 0),
                    'new_value'    => $reading ? (string)($reading['new_index'] ?? '') : '',
                    'need_old'     => !$reading && $baseline['error'] !== null,
                    'has_reading'  => $reading !== null,
                ];
            }
            $meterRoomMap[$roomId] = ['services' => $meterServices, 'cells' => $meterCells];
        }

        $invoiceId      = (int)($_GET['invoice_id'] ?? 0);
        $selectedInvoice = null;
        if ($invoiceId > 0) {
            $selectedInvoice = PaymentModel::getInvoiceById($invoiceId);
            if (empty($selectedInvoice)) setFlash('admin_invoice_error', 'Hóa đơn không tồn tại hoặc đã bị xóa.');
        }

        $invoiceStatusOptions = ['unpaid' => 'Chưa trả', 'paid' => 'Đã trả'];

        $redirectParams = array_filter([
            'month'     => $period['month'],
            'year'      => $period['year'],
            'status'    => $status !== '' ? $status : null,
            'area_id'   => $areaId > 0 ? $areaId : null,
            'floor_id'  => $floorId > 0 ? $floorId : null,
            'page'      => $page > 1 ? $page : null,
        ], fn($v) => $v !== null && $v !== '');

        $pageTitle = 'Hóa đơn - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/invoices.php';
    }

    public function generateInvoice(): void
    {
        PriceChangeModel::applyDueChanges();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-invoices');
        verify_csrf();

        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $generateScope = trim($_POST['generate_scope'] ?? 'single');
        $roomId     = (int)($_POST['room_id'] ?? 0);
        $areaId     = (int)($_POST['area_id'] ?? 0);
        $floorId    = (int)($_POST['floor_id'] ?? 0);
        $status     = trim($_POST['status'] ?? '');

        $redirectParams = array_filter([
            'month'    => $period['month'],
            'year'     => $period['year'],
            'status'   => $status !== '' ? $status : null,
            'area_id'  => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'room_id'  => $roomId > 0 ? $roomId : null,
        ], fn($v) => $v !== null && $v !== '');

        try {
            if ($generateScope === 'all') {
                $result = PaymentModel::generateInvoices($period['month'], $period['year'], null, [
                    'area_id' => $areaId,
                    'floor_id'=> $floorId,
                ]);
            } else {
                if ($roomId <= 0) throw new RuntimeException('Vui lòng chọn phòng.');

                $submitted = $_POST['meter_readings'] ?? [];
                if (!empty($submitted[$roomId]) && is_array($submitted[$roomId])) {
                    $saveResult = MeterReadingModel::saveReadings(
                        $period['month'], $period['year'],
                        [$roomId => $submitted[$roomId]],
                        ['room_id' => $roomId]
                    );
                    if (!empty($saveResult['form_error']) || !empty($saveResult['errors'][$roomId])) {
                        $err = $saveResult['form_error'] ?? 'Kiểm tra lại các ô nhập chỉ số.';
                        if (!empty($saveResult['errors'][$roomId])) $err .= ' ' . implode(' ', array_values($saveResult['errors'][$roomId]));
                        setFlash('admin_invoice_error', 'Chưa lưu được chỉ số: ' . $err);
                        redirectTo('admin-invoices', $redirectParams);
                        return;
                    }
                }
                $result = PaymentModel::generateInvoices($period['month'], $period['year'], $roomId);
            }

            $msgParts = [];
            if (!empty($result['created_count']))     $msgParts[] = 'Đã tạo ' . (int)$result['created_count'] . ' hóa đơn';
            if (!empty($result['skipped_existing_count'])) $msgParts[] = (int)$result['skipped_existing_count'] . ' phòng đã có hóa đơn';
            if (!empty($result['blocked_count']))     $msgParts[] = (int)$result['blocked_count'] . ' phòng bị chặn do thiếu dữ liệu';

            if (empty($result['created_count'])) {
                $blocked = !empty($result['blocked']) ? ' ' . implode(' || ', array_slice($result['blocked'], 0, 3)) : '';
                $exist   = !empty($result['skipped_existing']) ? ' ' . implode(', ', array_slice($result['skipped_existing'], 0, 3)) : '';
                setFlash('admin_invoice_error', 'Không tạo được hóa đơn mới.' . $exist . $blocked);
            } else {
                setFlash('admin_invoice_message', implode('. ', $msgParts) . '.');
            }
        } catch (Throwable $e) {
            setFlash('admin_invoice_error', $e->getMessage());
        }

        $redirectPage = trim($_POST['redirect_page'] ?? '');
        redirectTo(in_array($redirectPage, ['admin-invoices'], true) ? $redirectPage : 'admin-invoices', $redirectParams);
    }

    public function generateInvoicePerRoom(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-invoices');
        verify_csrf();

        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $roomId  = (int)($_POST['room_id'] ?? 0);
        $areaId  = (int)($_POST['area_id'] ?? 0);
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $search  = trim($_POST['search'] ?? '');
        $page    = max(1, (int)($_POST['page'] ?? 1));

        if ($roomId <= 0) {
            setFlash('admin_invoice_error', 'Vui lòng chọn phòng.');
            redirectTo('admin-invoices', array_filter([
                'month' => $period['month'], 'year' => $period['year'], 'search' => $search ?: null,
                'area_id' => $areaId > 0 ? $areaId : null, 'floor_id' => $floorId > 0 ? $floorId : null,
                'page' => $page > 1 ? $page : null,
            ], fn($v) => $v !== null && $v !== ''));
            return;
        }

        $redirectParams = array_filter([
            'month' => $period['month'], 'year' => $period['year'], 'search' => $search ?: null,
            'area_id' => $areaId > 0 ? $areaId : null, 'floor_id' => $floorId > 0 ? $floorId : null,
            'page' => $page > 1 ? $page : null,
        ], fn($v) => $v !== null && $v !== '');

        try {
            $result = PaymentModel::generateInvoices($period['month'], $period['year'], $roomId);

            $msg = [];
            if (!empty($result['created_count'])) $msg[] = 'Đã tạo ' . (int)$result['created_count'] . ' hóa đơn';
            if (!empty($result['skipped_existing_count'])) $msg[] = (int)$result['skipped_existing_count'] . ' phòng đã có hóa đơn';
            if (!empty($result['blocked_count'])) $msg[] = (int)$result['blocked_count'] . ' phòng bị chặn';

            if (empty($result['created_count'])) {
                $b = !empty($result['blocked']) ? ' ' . implode(' || ', array_slice($result['blocked'], 0, 3)) : '';
                $e = !empty($result['skipped_existing']) ? ' ' . implode(', ', array_slice($result['skipped_existing'], 0, 3)) : '';
                setFlash('admin_invoice_error', 'Không tạo được hóa đơn mới.' . $e . $b);
            } else {
                setFlash('admin_invoice_message', implode('. ', $msg) . '.');
            }
        } catch (Throwable $e) {
            setFlash('admin_invoice_error', $e->getMessage());
        }

        redirectTo('admin-invoices', $redirectParams);
    }

    public function confirmPayment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-invoices');
        verify_csrf();

        $paymentId   = (int)($_POST['payment_id'] ?? 0);
        $payerUserId = (int)($_POST['payer_user_id'] ?? 0);
        $period      = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);

        $redirectParams = array_filter([
            'month'      => $period['month'],
            'year'       => $period['year'],
            'status'     => trim($_POST['status'] ?? '') ?: null,
            'area_id'    => (int)($_POST['area_id'] ?? 0) > 0 ? (int)$_POST['area_id'] : null,
            'floor_id'   => (int)($_POST['floor_id'] ?? 0) > 0 ? (int)$_POST['floor_id'] : null,
            'room_id'    => (int)($_POST['room_id'] ?? 0) > 0 ? (int)$_POST['room_id'] : null,
            'invoice_id' => $paymentId > 0 ? $paymentId : null,
        ], fn($v) => $v !== null && $v !== '');

        try {
            $invoice   = PaymentModel::confirmPayment($paymentId, $payerUserId);
            $payerName = $invoice['payer']['full_name'] ?? 'tenant';
            setFlash('admin_invoice_message', 'Đã xác nhận hóa đơn thanh toán thành công cho ' . $payerName . '.');
        } catch (Throwable $e) {
            setFlash('admin_invoice_error', $e->getMessage());
        }

        redirectTo('admin-invoices', $redirectParams);
    }
}