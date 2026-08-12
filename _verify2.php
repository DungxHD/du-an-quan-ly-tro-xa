<?php
$v = file_get_contents('views/admin/rooms/rooms.php');
$ac = file_get_contents('controllers/AdminController.php');
echo "=== XAC MINH ===\n";
echo "admin_area_message: " . substr_count($ac, "admin_area_message") . " (>0 la dung)\n";
echo "hasArea var: " . (strpos($v, '$hasArea = ($selectedAreaId') !== false ? 'CO' : 'KHONG') . "\n";
echo "elseif hasArea: " . (strpos($v, 'elseif ($hasArea)') !== false ? 'CO' : 'KHONG') . "\n";
echo 'id="btn-add-floor" (KHONG phai cancel): ' . (strpos($v, 'id="btn-add-floor"') !== false ? 'CO' : 'KHONG') . "\n";
echo "selectedAreaName: " . (strpos($v, '$selectedAreaName') !== false ? 'CO' : 'KHONG') . "\n";
echo "modal: " . (strpos($v, 'modal-add-floor') !== false ? 'CO' : 'KHONG') . "\n";
echo "csrf in modal: " . (strpos($v, 'csrf_field()') !== false ? 'CO' : 'KHONG') . "\n";
echo "default_price (KHONG): " . (strpos($v, 'default_price') !== false ? 'CON!!' : 'KHONG') . "\n";
echo "addFloorQuick redirect admin-rooms: " . (strpos($ac, "redirectTo('admin-rooms', ['area_id' => \$areaId, 'floor_id' => 0])") !== false ? 'CO' : 'KHONG') . "\n";