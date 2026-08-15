<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminBillingTrait
{
/**
     * Trang quản lý hóa đơn tháng: preview, danh sách và xác nhận thanh toán tiền mặt.
     */
    public function invoices()
    {
        redirectTo('admin-meter-readings');
        return;
    }
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

        $redirectPage = trim((string)($_POST["redirect_page"] ?? ""));
        $allowedRedirect = ["admin-invoices", "admin-meter-readings"];
        redirectTo(in_array($redirectPage, $allowedRedirect, true) ? $redirectPage : "admin-invoices", $redirectParams);
    }
/**
     * Tạo hóa đơn cho một phòng cụ thể (từ trang meter_readings).
     * Chỉ tạo khi đã điền đủ chỉ số cũ và mới cho tất cả dịch vụ meter.
     */
    public function generateInvoicePerRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-meter-readings');
        }
        verify_csrf();

        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $areaId = (int)($_POST['area_id'] ?? 0);
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $search = trim((string)($_POST['search'] ?? ''));
        $page = max(1, (int)($_POST['page'] ?? 1));

        if ($roomId <= 0) {
            setFlash('admin_invoice_error', 'Vui lòng chọn phòng cần tạo hóa đơn.');
            redirectTo('admin-meter-readings', [
                'month' => $period['month'],
                'year' => $period['year'],
                'search' => $search ?: null,
                'area_id' => $areaId > 0 ? $areaId : null,
                'floor_id' => $floorId > 0 ? $floorId : null,
                'page' => $page > 1 ? $page : null,
            ]);
            return;
        }

        $redirectParams = array_filter([
            'month' => $period['month'],
            'year' => $period['year'],
            'search' => $search !== '' ? $search : null,
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'page' => $page > 1 ? $page : null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $result = PaymentModel::generateInvoices($period['month'], $period['year'], $roomId);

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

        redirectTo('admin-meter-readings', $redirectParams);
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
     * Trang nhập chỉ số điện/nước + tạo hóa đơn thống nhất cho các phòng đang thuê.
     * Hỗ trợ tìm kiếm, lọc khu/tầng, phân trang.
     */
    public function meterReadings()
    {
        PriceChangeModel::applyDueChanges();

        // Params
        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 20;
        $search = trim((string)($_GET['search'] ?? ''));
        $areaId = (int)($_GET['area_id'] ?? 0);
        $floorId = (int)($_GET['floor_id'] ?? 0);

        // Normalize period
        $period = MeterReadingModel::normalizePeriod($month, $year);

        // Get meter data with filters + pagination
        $meterData = MeterReadingModel::getAdminMatrix($period['month'], $period['year'], [
            'search' => $search,
            'area_id' => $areaId,
            'floor_id' => $floorId,
        ], $page, $perPage);

        // Flash messages
        $meterMessage = pullFlash('admin_meter_message');
        $meterError = pullFlash('admin_meter_error');
        $meterRowErrors = pullFlash('admin_meter_row_errors', []);
        $meterOldInput = pullFlash('admin_meter_old', []);
        $invoiceMsg = pullFlash("admin_invoice_message");
        $invoiceErr = pullFlash("admin_invoice_error");
        if ($invoiceMsg) {
            $meterMessage = trim(($meterMessage ? $meterMessage . " " : "") . $invoiceMsg);
        }
        if ($invoiceErr) {
            $meterError = trim(($meterError ? $meterError . " " : "") . $invoiceErr);
        }

        // Areas & floors for filter dropdowns
        $areas = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $filterFloors = $areaId > 0 ? FloorModel::getByAreaId($areaId) : $allFloors;

        // If floor selected but area not, sync area from floor
        $selectedFloor = $floorId > 0 ? FloorModel::getById($floorId) : null;
        if ($selectedFloor && $areaId <= 0) {
            $areaId = (int)($selectedFloor['area_id'] ?? 0);
        }

        // Build redirect params preserving filters
        $redirectParams = [
            'month' => $period['month'],
            'year' => $period['year'],
            'search' => $search !== '' ? $search : null,
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'page' => $page > 1 ? $page : null,
        ];
        $redirectParams = array_filter($redirectParams, fn($v) => $v !== null && $v !== '');

        $pageTitle = 'Hóa đơn - NhaTroA';
        $invoiceList = PaymentModel::getInvoices(['month' => $period['month'], 'year' => $period['year']]);
        require_once BASE_PATH . 'views/admin/billing/meter_readings.php';
    }
/**
     * Lưu chỉ số hàng loạt hoặc theo từng dòng phòng nhưng vẫn giữ validate độc lập từng ô.
     * Hỗ trợ old_index unlock/edit với validation đầy đủ.
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
        $generateRoomId = (int)($_POST['generate_room_id'] ?? 0);
        $result = MeterReadingModel::saveReadings(
            $period['month'],
            $period['year'],
            is_array($submittedReadings) ? $submittedReadings : [],
            ['room_id' => $saveRoomId > 0 ? $saveRoomId : ($generateRoomId > 0 ? $generateRoomId : null)]
        );

        $generateRequested = $generateRoomId > 0 && empty($result['errors'][$generateRoomId]);

        if (!empty($result['saved_count'])) {
            $prefix = $saveRoomId > 0 || $generateRoomId > 0 ? 'Đã lưu chỉ số cho dòng phòng đã chọn.' : 'Đã lưu chỉ số thành công.';
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
            if (!($generateRequested && empty($result['errors']) && empty($result['form_error']))) {
                $errorMessage = $result['form_error'] ?? 'Một số dòng chưa hợp lệ. Hệ thống đã tô đỏ các ô cần kiểm tra.';
                if (empty($result['form_error']) && !empty($result['saved_count'])) {
                    $errorMessage = 'Một số dòng chưa hợp lệ. Hệ thống đã lưu phần đúng và giữ lại phần lỗi để bạn sửa tiếp.';
                }

                setFlash('admin_meter_error', $errorMessage);
                setFlash('admin_meter_row_errors', $result['errors']);
                setFlash('admin_meter_old', is_array($submittedReadings) ? $submittedReadings : []);
            }
        }

        // Tạo hóa đơn ngay cho phòng khi chỉ số đã hợp lệ.
        if ($generateRequested) {
            try {
                $invoiceResult = PaymentModel::generateInvoices($period['month'], $period['year'], $generateRoomId);
                if (!empty($invoiceResult['created_count'])) {
                    setFlash('admin_invoice_message', 'Đã tạo hóa đơn cho phòng và gửi thông báo tới cư dân.');
                } else {
                    $blockedPreview = !empty($invoiceResult['blocked']) ? ' ' . implode(' || ', array_slice($invoiceResult['blocked'], 0, 3)) : '';
                    setFlash('admin_invoice_error', 'Chưa tạo được hóa đơn.' . $blockedPreview);
                }
            } catch (Throwable $exception) {
                setFlash('admin_invoice_error', $exception->getMessage());
            }
        }

        // Preserve filter params on redirect
        $redirectParams = [
            'month' => $period['month'],
            'year' => $period['year'],
            'search' => trim((string)($_POST['search'] ?? '')) !== '' ? trim((string)$_POST['search']) : null,
            'area_id' => (int)($_POST['area_id'] ?? 0) > 0 ? (int)$_POST['area_id'] : null,
            'floor_id' => (int)($_POST['floor_id'] ?? 0) > 0 ? (int)$_POST['floor_id'] : null,
            'page' => (int)($_POST['page'] ?? 1) > 1 ? (int)$_POST['page'] : null,
        ];
        $redirectParams = array_filter($redirectParams, fn($v) => $v !== null && $v !== '');
        redirectTo('admin-meter-readings', $redirectParams);
    }

}
