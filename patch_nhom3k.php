<?php
$base = __DIR__ . '/';
$log = []; $allOk = true;
function putLog(&$ok,$l,$s){ global $log; $log[] = ($s?'OK: ':'FAIL: ').$l; $ok=$ok&&$s; }
function rep(&$c,$search,$replace,$label,&$ok){ $n=0; $c2=str_replace($search,$replace,$c,$n); if($n>=1){$c=$c2;putLog($ok,$label,true);} else putLog($ok,$label,false); }

// ===== ServiceModel =====
$f=$base.'models/billing/ServiceModel.php'; $c=file_get_contents($f); copy($f,$f.'.bak3k');
rep($c,
"public static function isLockedKind(\$kind) {
return in_array((string)\$kind, ['electricity', 'water', 'trash'], true);
}",
"public static function isLockedKind(\$kind) {
return in_array((string)\$kind, ['electricity', 'water', 'trash'], true);
}
public static function deriveUnit(\$kind, \$billingMode) {
if (\$kind === 'electricity') return 'kWh';
if (\$kind === 'water') return \$billingMode === 'meter' ? 'm3' : 'người/tháng';
if (\$kind === 'trash') return 'người/tháng';
if (\$billingMode === 'per_person') return 'người/tháng';
return 'tháng';
}
public static function countRoomsUsing(\$serviceId) {
\$id = (int)\$serviceId;
if (Database::hasConnection()) {
\$row = Database::fetchOne('SELECT COUNT(*) AS c FROM room_services WHERE service_id = ?', [\$id]);
return (int)(\$row['c'] ?? 0);
}
\$n = 0;
foreach (Database::getTable('room_services') as \$r) { if ((int)(\$r['service_id'] ?? 0) === \$id) \$n++; }
return \$n;
}
public static function isPendingDelete(array \$service) {
return (\$service['delete_month'] ?? null) !== null;
}
public static function scheduleDelete(\$id, \$month, \$year) {
Database::update('services', ['delete_month' => (int)\$month, 'delete_year' => (int)\$year], 'id = :id', ['id' => (int)\$id]);
}
public static function undoDelete(\$id) {
Database::update('services', ['delete_month' => null, 'delete_year' => null], 'id = :id', ['id' => (int)\$id]);
}
public static function applyDueDeletes() {
\$cur = ((int)date('Y')*100)+(int)date('n');
\$rows = Database::hasConnection()
? Database::fetchAll('SELECT * FROM services WHERE delete_month IS NOT NULL')
: array_values(array_filter(Database::getTable('services'), static fn(\$s) => (\$s['delete_month'] ?? null) !== null));
\$n = 0;
foreach (\$rows as \$s) {
\$ord = ((int)(\$s['delete_year'] ?? 0)*100)+(int)(\$s['delete_month'] ?? 0);
if (\$ord <= \$cur) { try { self::delete((int)\$s['id']); \$n++; } catch (Throwable \$e) {} }
}
return \$n;
}", $c, $allOk);
rep($c,
"'kind' => in_array((\$service['kind'] ?? 'other'), self::KINDS, true) ? \$service['kind'] : 'other',",
"'kind' => in_array((\$service['kind'] ?? 'other'), self::KINDS, true) ? \$service['kind'] : 'other',
'delete_month' => isset(\$service['delete_month']) && \$service['delete_month'] !== null ? (int)\$service['delete_month'] : null,
'delete_year' => isset(\$service['delete_year']) && \$service['delete_year'] !== null ? (int)\$service['delete_year'] : null,", $c, $allOk);
file_put_contents($f,$c);

// ===== PriceChangeModel: hủy lịch chờ =====
$f=$base.'models/billing/PriceChangeModel.php'; $c=file_get_contents($f); copy($f,$f.'.bak3k');
rep($c,
"public static function getPendingByServiceMap() {",
"public static function cancelPendingChange(\$id) {
\$id = (int)\$id;
\$row = Database::hasConnection() ? Database::fetchOne('SELECT * FROM price_changes WHERE id = ?', [\$id]) : null;
if (!\$row) { throw new RuntimeException('Lịch thay đổi không tồn tại.'); }
if ((int)(\$row['applied'] ?? 0) === 1) { throw new RuntimeException('Lịch đã áp dụng, không thể hủy.'); }
Database::delete('price_changes', 'id = :id', ['id' => \$id]);
}
public static function getPendingByServiceMap() {", $c, $allOk);
file_put_contents($f,$c);

// ===== AdminController =====
$f=$base.'controllers/AdminController.php'; $c=file_get_contents($f); copy($f,$f.'.bak3k');
// services(): lazy delete + priceHistories
rep($c,
"require_once BASE_PATH . 'views/admin/billing/services.php';",
"ServiceModel::applyDueDeletes();
\$priceHistories = [];
foreach (\$services as \$svc) {
\$hist = PriceChangeModel::getAll(['service_id' => (int)(\$svc['id'] ?? 0)]);
\$priceHistories[(int)(\$svc['id'] ?? 0)] = array_values(array_filter(\$hist, static fn(\$h) => (int)(\$h['applied'] ?? 0) === 0));
}
require_once BASE_PATH . 'views/admin/billing/services.php';", $c, $allOk);
// deleteService: hoãn + undo + cancel price
rep($c,
"public function deleteService(\$id) {",
"public function undoDeleteService(\$id) {
ServiceModel::undoDelete((int)\$id);
setFlash('admin_service_message', 'Đã hoàn tác xóa. Dịch vụ tiếp tục hoạt động.');
redirectTo('admin-services');
}
public function cancelPriceChange(\$id) {
try { PriceChangeModel::cancelPendingChange((int)\$id); setFlash('admin_service_message', 'Đã hủy lịch thay đổi giá.'); }
catch (Throwable \$e) { setFlash('admin_service_error', \$e->getMessage()); }
redirectTo('admin-services', ['edit' => (int)($_GET['service_id'] ?? 0) > 0 ? (int)\$_GET['service_id'] : null]);
}
public function deleteService(\$id) {
\$service = ServiceModel::getById((int)\$id);
\$using = \$service ? ServiceModel::countRoomsUsing((int)\$id) : 0;
if (\$service && \$using > 0) {
\$nm = (int)date('n')+1; \$ny = (int)date('Y'); if (\$nm>12){\$nm=1;\$ny++;}
ServiceModel::scheduleDelete((int)\$id, \$nm, \$ny);
setFlash('admin_service_message', 'Dịch vụ đang có '.\$using.' phòng sử dụng. Sẽ bị xóa khi sang tháng '.\$nm.'/'.\$ny.'. Có thể Hoàn tác trước thời điểm đó.');
redirectTo('admin-services');
}
", $c, $allOk);
file_put_contents($f,$c);
echo implode("\n",$log)."\n";
echo $allOk ? "PATCH 3K (BACKEND) HOAN TAT.\n" : "CO BUOC FAIL.\n";