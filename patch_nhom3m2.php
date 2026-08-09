<?php
$base = __DIR__ . '/';
$f = $base.'controllers/AdminController.php';
$c = file_get_contents($f);
if (strpos($c, 'countRoomsUsing') !== false && strpos($c, 'scheduleDelete') !== false) {
echo "SKIP: deleteService da duoc va truoc do.\n"; exit(0);
}
$pattern = '/public function deleteService\(\$id\)\s*\{\s*\$redirectParams = \[\];/';
$replacement = <<<'EOT'
public function deleteService($id)
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
$nextMonth = (int)date('n') + 1;
$nextYear = (int)date('Y');
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
ServiceModel::scheduleDelete((int)$id, $nextMonth, $nextYear);
setFlash('admin_service_message', 'Dịch vụ đang có ' . $using . ' phòng sử dụng. Sẽ bị xóa khi sang tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '. Bạn có thể Hoàn tác trước thời điểm đó.');
redirectTo('admin-services');
}
}
$redirectParams = [];
EOT;
$n = 0;
$c2 = preg_replace($pattern, $replacement, $c, 1, $n);
if ($n === 1) {
file_put_contents($f, $c2);
echo "OK: deleteService hoan xoa da ap dung.\n";
} else {
echo "FAIL: khong tim thay deleteService de va.\n";
exit(1);
}