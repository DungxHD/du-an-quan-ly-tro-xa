<?php
$base = __DIR__ . '/';
$log = [];
function putLog(&$ok, $label, $success) { global $log; $log[] = ($success ? 'OK: ' : 'FAIL: ') . $label; $ok = $ok && $success; }
$allOk = true;
$files = [];
foreach (['controllers/AdminController.php','models/billing/ServiceModel.php','models/billing/PriceChangeModel.php','models/billing/PaymentModel.php','models/billing/MeterReadingModel.php'] as $rel) {
    $files[$rel] = str_replace("\r\n", "\n", file_get_contents($base . $rel));
    copy($base . $rel, $base . $rel . '.bak_nhom3e');
}

// ---------- ServiceModel ----------
$c = &$files['models/billing/ServiceModel.php'];
$r = preg_replace('/private\s+const\s+APPLIES_TO\s*=\s*\[\s*\'room\'\s*,\s*\'person\'\s*\]\s*;/', <<<'EOT'
private const APPLIES_TO = ['room', 'person'];
    private const KINDS = ['other', 'electricity', 'water', 'trash'];
    public static function getKindOptions() {
        return ['other' => 'Dịch vụ khác', 'electricity' => 'Tiền điện (bắt buộc)', 'water' => 'Tiền nước (bắt buộc)', 'trash' => 'Tiền rác (bắt buộc)'];
    }
    public static function getKindBillingModesMap() {
        return [
            'electricity' => ['meter'],
            'water' => ['meter', 'per_person'],
            'trash' => ['per_person'],
            'other' => self::BILLING_MODES,
        ];
    }
    public static function isLockedKind($kind) {
        return in_array((string)$kind, ['electricity', 'water', 'trash'], true);
    }
EOT
, $c, 1, $cnt);
putLog($allOk, 'SM kinds', $cnt === 1);

$r = preg_replace('/\'is_active\'\s*=>\s*array_key_exists\(\s*\'is_active\'\s*,\s*\$service\s*\)\s*\?\s*\(\s*!empty\(\s*\$service\[\s*\'is_active\'\s*\]\s*\)\s*\?\s*1\s*:\s*0\s*\)\s*:\s*1\s*,/', <<<'EOT'
'is_active' => array_key_exists('is_active', $service) ? (!empty($service['is_active']) ? 1 : 0) : 1,
            'kind' => in_array(($service['kind'] ?? 'other'), self::KINDS, true) ? $service['kind'] : 'other',
EOT
, $c, 1, $cnt);
putLog($allOk, 'SM normalize kind', $cnt === 1);

$r = preg_replace('/if\s*\(\s*\(int\)\(\s*\$service\[\s*\'is_required\'\s*\]\s*\?\?\s*0\s*\)\s*===\s*1\s*\)\s*\{[\s\S]*?throw\s+new\s+RuntimeException\(\s*\'Dịch vụ bắt buộc không thể xóa\.\'\s*\)\s*;\s*\}/', <<<'EOT'
if ((int)($service['is_required'] ?? 0) === 1 || self::isLockedKind($service['kind'] ?? 'other')) {
            throw new RuntimeException('Dịch vụ bắt buộc (điện/nước/rác) không thể xóa.');
        }
EOT
, $c, 1, $cnt);
putLog($allOk, 'SM delete lock', $cnt === 1);

$r = preg_replace('/public\s+static\s+function\s+save\(\s*array\s+\$data\s*,\s*\$id\s*=\s*null\s*\)\s*\{[\s\S]*?\$payload\s*=\s*self::normalizeServiceRow\(\s*\$data\s*\)\s*;/', <<<'EOT'
public static function save(array $data, $id = null) {
        $payload = self::normalizeServiceRow($data);
        if (self::isLockedKind($payload['kind'])) {
            $payload['is_required'] = 1;
            $payload['is_active'] = 1;
            $payload['applies_to'] = 'room';
        }
EOT
, $c, 1, $cnt);
putLog($allOk, 'SM save lock', $cnt === 1);

// ---------- PriceChangeModel ----------
$c = &$files['models/billing/PriceChangeModel.php'];
$r = preg_replace('/\'service_icon\'\s*=>\s*trim\(\s*\(string\)\(\s*\$row\[\s*\'service_icon\'\s*\]\s*\?\?\s*\'settings\'\s*\)\s*\)\s*,/', <<<'EOT'
'service_icon' => trim((string)($row['service_icon'] ?? 'settings')),
            'old_billing_mode' => $row['old_billing_mode'] ?? null,
            'new_billing_mode' => $row['new_billing_mode'] ?? null,
            'applied' => (int)($row['applied'] ?? 0) === 1 ? 1 : 0,
EOT
, $c, 1, $cnt);
putLog($allOk, 'PC normalize', $cnt === 1);

$r = preg_replace('/public\s+static\s+function\s+saveChange\([\s\S]*?public\s+static\s+function\s+getEffectivePriceForPeriod/', <<<'EOT'
public static function scheduleServiceChange($serviceId, $newPrice, $newBillingMode, $effectiveMonth, $effectiveYear, $createdBy = null) {
        $service = ServiceModel::getById((int)$serviceId);
        if (!$service) { throw new RuntimeException('Dịch vụ cần đổi giá không tồn tại.'); }
        $currentPrice = (float)($service['price'] ?? 0);
        $currentMode = (string)($service['billing_mode'] ?? 'fixed');
        $hasPriceChange = $newPrice !== null && abs((float)$newPrice - $currentPrice) > 0.001;
        $hasModeChange = $newBillingMode !== null && $newBillingMode !== $currentMode;
        if (!$hasPriceChange && !$hasModeChange) { throw new RuntimeException('Giá và cách tính mới đang trùng hiện tại, không có thay đổi.'); }
        if ($hasPriceChange && (float)$newPrice <= 0) { throw new RuntimeException('Giá mới phải lớn hơn 0.'); }
        if ($hasModeChange) {
            $allowed = ServiceModel::getKindBillingModesMap()[$service['kind'] ?? 'other'] ?? ServiceModel::BILLING_MODES;
            if (!in_array($newBillingMode, $allowed, true)) { throw new RuntimeException('Cách tính này không được phép cho loại dịch vụ này.'); }
        }
        $period = MeterReadingModel::normalizePeriod($effectiveMonth, $effectiveYear);
        $targetOrder = ($period['year'] * 100) + $period['month'];
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        if ($targetOrder <= $currentOrder) { throw new RuntimeException('Tháng hiệu lực phải lớn hơn tháng hiện tại.'); }
        if (self::existsForPeriod((int)$serviceId, $period['month'], $period['year'])) { throw new RuntimeException('Dịch vụ này đã có lịch thay đổi cho đúng tháng hiệu lực đã chọn.'); }
        $priceChangeId = (int)Database::insert('price_changes', [
            'service_id' => (int)$service['id'],
            'old_price' => $currentPrice,
            'new_price' => $hasPriceChange ? (float)$newPrice : $currentPrice,
            'old_billing_mode' => $currentMode,
            'new_billing_mode' => $hasModeChange ? $newBillingMode : null,
            'effective_month' => $period['month'],
            'effective_year' => $period['year'],
            'applied' => 0,
            'created_by' => $createdBy !== null ? (int)$createdBy : null,
        ]);
        NotificationModel::create([
            'user_id' => null,
            'title' => 'Thay đổi giá dịch vụ',
            'content' => self::buildNotificationContent($service, $currentPrice, $hasPriceChange ? (float)$newPrice : $currentPrice, $period['month'], $period['year']),
            'type' => 'price_change',
        ]);
        return $priceChangeId;
    }
    public static function applyDueChanges() {
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        $rows = Database::hasConnection()
            ? Database::fetchAll('SELECT * FROM price_changes WHERE applied = 0 ORDER BY effective_year ASC, effective_month ASC, id ASC')
            : array_values(array_filter(Database::getTable('price_changes'), static fn($r) => (int)($r['applied'] ?? 0) === 0));
        $count = 0;
        foreach ($rows as $row) {
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder > $currentOrder) { continue; }
            $payload = ['price' => (float)($row['new_price'] ?? 0)];
            if (!empty($row['new_billing_mode'])) { $payload['billing_mode'] = $row['new_billing_mode']; }
            Database::update('services', $payload, 'id = :id', ['id' => (int)($row['service_id'] ?? 0)]);
            Database::update('price_changes', ['applied' => 1], 'id = :id', ['id' => (int)($row['id'] ?? 0)]);
            $count++;
        }
        return $count;
    }
    public static function getPendingByServiceMap() {
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        $map = [];
        foreach (self::getAll() as $row) {
            if ((int)($row['applied'] ?? 0) === 1) { continue; }
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder <= $currentOrder) { continue; }
            $map[(int)$row['service_id']] = $row;
        }
        return $map;
    }
    public static function getEffectivePriceForPeriod
EOT
, $c, 1, $cnt);
putLog($allOk, 'PC schedule+apply', $cnt === 1);

$r = preg_replace('/public\s+static\s+function\s+getEffectivePriceForPeriod\([\s\S]*?public\s+static\s+function\s+buildNotificationContent/', <<<'EOT'
public static function getEffectiveConfigForPeriod(array $service, $month, $year) {
        $basePrice = (float)($service['price'] ?? 0);
        $baseMode = (string)($service['billing_mode'] ?? 'fixed');
        $history = self::getHistoryByServiceId((int)($service['id'] ?? 0));
        $targetOrder = ((int)$year * 100) + (int)$month;
        $currentOrder = ((int)date('Y') * 100) + (int)date('n');
        if (empty($history)) { return ['price' => $basePrice, 'billing_mode' => $baseMode]; }
        if ($targetOrder <= $currentOrder) {
            $price = $basePrice;
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $row = $history[$i];
                if ((int)($row['applied'] ?? 0) !== 1) { continue; }
                $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
                if ($rowOrder > $targetOrder) { $price = (float)($row['old_price'] ?? $price); }
            }
            return ['price' => $price, 'billing_mode' => $baseMode];
        }
        $price = $basePrice; $mode = $baseMode;
        foreach ($history as $row) {
            if ((int)($row['applied'] ?? 0) === 1) { continue; }
            $rowOrder = ((int)($row['effective_year'] ?? 0) * 100) + (int)($row['effective_month'] ?? 0);
            if ($rowOrder <= $targetOrder) {
                $price = (float)($row['new_price'] ?? $price);
                if (!empty($row['new_billing_mode'])) { $mode = (string)$row['new_billing_mode']; }
            }
        }
        return ['price' => $price, 'billing_mode' => $mode];
    }
    public static function getEffectivePriceForPeriod($serviceId, $month, $year, $fallbackPrice = 0.0) {
        $service = ServiceModel::getById((int)$serviceId) ?? ['price' => $fallbackPrice, 'billing_mode' => 'fixed'];
        return self::getEffectiveConfigForPeriod($service, $month, $year)['price'];
    }
    public static function buildNotificationContent
EOT
, $c, 1, $cnt);
putLog($allOk, 'PC config period', $cnt === 1);

// ---------- AdminController ----------
$c = &$files['controllers/AdminController.php'];
$newServices = <<<'EOT'
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
        require_once BASE_PATH . 'views/admin/billing/services.php';
    }
EOT;
$r = preg_replace('/public\s+function\s+services\(\)\s*\{[\s\S]*?public\s+function\s+priceChanges\(\)\s*\{/', $newServices . "\n    public function priceChanges()\n    {\n        PriceChangeModel::applyDueChanges();", $c, 1, $cnt);
putLog($allOk, 'Admin services()', $cnt === 1);

$newSavePrice = <<<'EOT'
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
EOT;
$r = preg_replace('/public\s+function\s+savePriceChange\(\)\s*\{[\s\S]*?public\s+function\s+notifications\(\)\s*\{/', $newSavePrice . "\n    public function notifications()\n    {", $c, 1, $cnt);
putLog($allOk, 'Admin savePriceChange()', $cnt === 1);

$newSaveService = <<<'EOT'
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
            ServiceModel::save($core, $id);
            $priceChanged = abs($submittedPrice - (float)$existing['price']) > 0.001;
            $modeChanged = $submittedMode !== (string)$existing['billing_mode'];
            if ($priceChanged || $modeChanged) {
                try {
                    $nextMonth = (int)date('n') + 1;
                    $nextYear = (int)date('Y');
                    if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
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
EOT;
$r = preg_replace('/public\s+function\s+saveService\(\)\s*\{[\s\S]*?public\s+function\s+deleteService\(\s*\$id\s*\)\s*\{/', $newSaveService . "\n    public function deleteService(\$id)\n    {", $c, 1, $cnt);
putLog($allOk, 'Admin saveService()', $cnt === 1);

// lazy triggers - sửa bug OK-giả: đếm số lần thay thật
$before = $c;
$c = preg_replace('/public\s+function\s+meterReadings\(\)\s*\{/', "public function meterReadings()\n    {\n        PriceChangeModel::applyDueChanges();", $c, 1, $c1);
$c = preg_replace('/public\s+function\s+generateInvoice\(\)\s*\{/', "public function generateInvoice()\n    {\n        PriceChangeModel::applyDueChanges();", $c, 1, $c2);
putLog($allOk, 'Admin lazy triggers', ($c1 === 1 && $c2 === 1));

// ---------- PaymentModel ----------
$c = &$files['models/billing/PaymentModel.php'];
$r = preg_replace('/PriceChangeModel::getEffectivePriceForPeriod\(\s*\$serviceId\s*,\s*\(int\)\$month\s*,\s*\(int\)\$year\s*,\s*\(float\)\(\s*\$service\[\s*\'price\'\s*\]\s*\?\?\s*0\s*\)\s*\)\s*;\s*\$billingMode\s*=\s*\$service\[\s*\'billing_mode\'\s*\]\s*\?\?\s*\'fixed\'\s*;/', <<<'EOT'
$effectiveConfig = PriceChangeModel::getEffectiveConfigForPeriod($service, (int)$month, (int)$year);
        $unitPrice = $effectiveConfig['price'];
        $billingMode = $effectiveConfig['billing_mode'];
EOT
, $c, 1, $cnt);
putLog($allOk, 'Payment effective config', $cnt === 1);

// ---------- MeterReadingModel ----------
$c = &$files['models/billing/MeterReadingModel.php'];
$r = preg_replace('/private\s+static\s+function\s+resolveInitialIndexField\(\s*array\s+\$service\s*\)\s*\{[\s\S]*?private\s+static\s+function\s+getPreviousPeriod\(\s*/', <<<'EOT'
private static function resolveInitialIndexField(array $service) {
        switch ((string)($service['kind'] ?? 'other')) {
            case 'electricity':
                return 'initial_electricity_index';
            case 'water':
                return 'initial_water_index';
            default:
                return null;
        }
    }

    private static function getPreviousPeriod(
EOT
, $c, 1, $cnt);
putLog($allOk, 'Meter kind field', $cnt === 1);

if (!$allOk) {
    echo implode("\n", $log) . "\n";
    echo "CO BUOC FAIL - KHONG GHI FILE. Paste log nay cho AI.\n";
    exit(1);
}
foreach ($files as $rel => $content) {
    file_put_contents($base . $rel, $content);
}
echo implode("\n", $log) . "\n";
echo "PATCH NHOM 3E HOAN TAT.\n";