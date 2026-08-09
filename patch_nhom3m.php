<?php
$base = __DIR__ . '/';
$log = []; $allOk = true;
function putLog(&$ok,$label,$ok2){ global $log; $log[] = ($ok2?'OK: ':'FAIL: ').$label; $ok = $ok && $ok2; }
function insertBefore(&$c, $needle, $inject, $label, &$ok){
if (strpos($c, $needle) === false) { putLog($ok, $label, false); return; }
$c = substr_replace($c, $inject, strpos($c, $needle), 0);
putLog($ok, $label, true);
}
function replaceOnce(&$c, $search, $replace, $label, &$ok){
$n = 0; $c2 = str_replace($search, $replace, $c, $n);
if ($n === 1) { $c = $c2; putLog($ok, $label, true); } else putLog($ok, $label, false);
}

// ===== ServiceModel =====
$f = $base.'models/billing/ServiceModel.php';
$c = file_get_contents($f); copy($f, $f.'.bak3m');
$smExtra = <<<'EOT'
public static function deriveUnit($kind, $billingMode) {
if ($kind === 'electricity') { return 'kWh'; }
if ($kind === 'trash') { return 'người/tháng'; }
if ($billingMode === 'meter') { return $kind === 'water' ? 'm3' : 'tháng'; }
if ($billingMode === 'per_person') { return 'người/tháng'; }
return 'tháng';
}
public static function countRoomsUsing($serviceId) {
$serviceId = (int)$serviceId;
if (Database::hasConnection()) {
$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM room_services WHERE service_id = ?');
$stmt->execute([$serviceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
return (int)($row['total'] ?? 0);
}
$count = 0;
foreach (Database::getTable('room_services') as $assignment) {
if ((int)($assignment['service_id'] ?? 0) === $serviceId) { $count++; }
}
return $count;
}
public static function isPendingDelete(array $service) {
return ($service['delete_month'] ?? null) !== null && (int)($service['delete_month'] ?? 0) > 0;
}
public static function scheduleDelete($id, $month, $year) {
Database::update('services', ['delete_month' => (int)$month, 'delete_year' => (int)$year], 'id = :id', ['id' => (int)$id]);
}
public static function undoDelete($id) {
Database::update('services', ['delete_month' => null, 'delete_year' => null], 'id = :id', ['id' => (int)$id]);
}
public static function applyDueDeletes() {
$currentOrder = ((int)date('Y') * 100) + (int)date('n');
$deleted = 0;
foreach (self::getAll() as $service) {
if (!self::isPendingDelete($service)) { continue; }
$order = ((int)($service['delete_year'] ?? 0) * 100) + (int)($service['delete_month'] ?? 0);
if ($order <= $currentOrder) {
try { self::delete((int)($service['id'] ?? 0)); $deleted++; } catch (Throwable $e) {}
}
}
return $deleted;
}
EOT;
insertBefore($c, 'public static function getAssignmentsByRoom(', $smExtra, 'SM: 6 phuong thuc moi', $allOk);
replaceOnce($c,
"'is_active' => array_key_exists('is_active', \$service) ? (!empty(\$service['is_active']) ? 1 : 0) : 1,",
"'is_active' => array_key_exists('is_active', \$service) ? (!empty(\$service['is_active']) ? 1 : 0) : 1,
'delete_month' => \$service['delete_month'] ?? null,
'delete_year' => \$service['delete_year'] ?? null,", 'SM: normalize delete_month/year', $allOk);
file_put_contents($f, $c);

// ===== PriceChangeModel =====
$f = $base.'models/billing/PriceChangeModel.php';
$c = file_get_contents($f); copy($f, $f.'.bak3m');
$pcExtra = <<<'EOT'
public static function cancelPendingChange($changeId) {
$row = Database::hasConnection()
? Database::fetchOne('SELECT * FROM price_changes WHERE id = ?', [(int)$changeId])
: (function() use ($changeId) { foreach (Database::getTable('price_changes') as $r) { if ((int)($r['id'] ?? 0) === (int)$changeId) { return $r; } } return null; })();
if (!$row) { throw new RuntimeException('Không tìm thấy lịch thay đổi cần hủy.'); }
if ((int)($row['applied'] ?? 0) === 1) { throw new RuntimeException('Lịch này đã áp dụng rồi, không thể hủy.'); }
Database::delete('price_changes', 'id = :id', ['id' => (int)$changeId]);
return true;
}
public static function getPendingHistoryByService($serviceId) {
$result = [];
foreach (self::getHistoryByServiceId((int)$serviceId) as $row) {
if ((int)($row['applied'] ?? 0) === 0) { $result[] = $row; }
}
return $result;
}
EOT;
insertBefore($c, 'public static function getPendingByServiceMap(', $pcExtra, 'PC: cancel + pending history', $allOk);
file_put_contents($f, $c);

// ===== AdminController =====
$f = $base.'controllers/AdminController.php';
$c = file_get_contents($f); copy($f, $f.'.bak3m');
replaceOnce($c,
"require_once BASE_PATH . 'views/admin/billing/services.php';",
"ServiceModel::applyDueDeletes();
\$priceHistories = [];
\$pendingDeleteByService = [];
foreach (\$services as \$svc) {
\$svcId = (int)(\$svc['id'] ?? 0);
\$priceHistories[\$svcId] = PriceChangeModel::getPendingHistoryByService(\$svcId);
\$pendingDeleteByService[\$svcId] = ServiceModel::isPendingDelete(\$svc);
}
require_once BASE_PATH . 'views/admin/billing/services.php';", 'AC: services() them lich su + pending delete', $allOk);
$acExtra = <<<'EOT'
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
}
EOT;
insertBefore($c, 'public function deleteService(', $acExtra, 'AC: undoDelete + cancelPriceChange', $allOk);
replaceOnce($c,
"public function deleteService(\$id)
{
\$redirectParams = [];",
"public function deleteService(\$id)
{
\$service = ServiceModel::getById((int)\$id);
if (\$service) {
\$locked = (int)(\$service['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind(\$service['kind'] ?? 'other');
if (\$locked) {
setFlash('admin_service_error', 'Dịch vụ bắt buộc (điện/nước/rác) không thể xóa.');
redirectTo('admin-services');
}
\$using = ServiceModel::countRoomsUsing((int)\$id);
if (\$using > 0) {
\$nextMonth = (int)date('n') + 1;
\$nextYear = (int)date('Y');
if (\$nextMonth > 12) { \$nextMonth = 1; \$nextYear++; }
ServiceModel::scheduleDelete((int)\$id, \$nextMonth, \$nextYear);
setFlash('admin_service_message', 'Dịch vụ đang có ' . \$using . ' phòng sử dụng. Sẽ bị xóa khi sang tháng ' . str_pad((string)\$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . \$nextYear . '. Bạn có thể Hoàn tác trước thời điểm đó.');
redirectTo('admin-services');
}
}
\$redirectParams = [];", 'AC: deleteService hoan xoa', $allOk);
file_put_contents($f, $c);

echo implode("\n", $log)."\n";
if (!$allOk) { echo "CO BUOC FAIL - KIEM TRA LAI LOG.\n"; exit(1); }
echo "PATCH 3M HOAN TAT.\n";