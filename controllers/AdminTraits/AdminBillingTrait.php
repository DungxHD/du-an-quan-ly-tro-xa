<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminBillingTrait
{
/**
     * Trang quản lý hóa đơn tháng: preview, danh sách và xác nhận thanh toán tiền mặt.
     */
    public function invoices()
    {
        PriceChangeModel::applyDueChanges();

        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 20;
        $search = trim((string)($_GET['search'] ?? ''));
        $areaId = (int)($_GET['area_id'] ?? 0);
        $floorId = (int)($_GET['floor_id'] ?? 0);
        $status = trim((string)($_GET['status'] ?? ''));

        $period = PaymentModel::normalizePeriod($month, $year);

        $invoices = PaymentModel::getInvoices([
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => $status !== '' ? $status : null,
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'search' => $search !== '' ? $search : null,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $areas = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $filterFloors = $areaId > 0 ? FloorModel::getByAreaId($areaId) : $allFloors;

        $selectedFloor = $floorId > 0 ? FloorModel::getById($floorId) : null;
        if ($selectedFloor && $areaId <= 0) {
            $areaId = (int)($selectedFloor['area_id'] ?? 0);
        }

        $invoiceMessage = pullFlash('admin_invoice_message');
        $invoiceError = pullFlash('admin_invoice_error');

        $redirectParams = array_filter([
            'month' => $period['month'],
            'year' => $period['year'],
            'status' => $status !== '' ? $status : null,
            'area_id' => $areaId > 0 ? $areaId : null,
            'floor_id' => $floorId > 0 ? $floorId : null,
            'page' => $page > 1 ? $page : null,
        ], fn($v) => $v !== null && $v !== '');

        $pageTitle = 'Hóa đơn - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/invoices.php';
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
        $allowedRedirect = ["admin-invoices"];
        redirectTo(in_array($redirectPage, $allowedRedirect, true) ? $redirectPage : "admin-invoices", $redirectParams);
    }
 /**
     * Tạo hóa đơn cho một phòng cụ thể.
     */
    public function generateInvoicePerRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-invoices');
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
            redirectTo('admin-invoices', [
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

}