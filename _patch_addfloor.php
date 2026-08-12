<?php
foreach (['views/admin/rooms/areas.php','controllers/AdminController.php'] as $f) { copy($f, $f.'.bak_'.date('Ymd_His')); }

// ===== 1. View: form Thêm tầng trong mỗi card khu =====
$v = file_get_contents('views/admin/rooms/areas.php');
if (strpos($v, 'add-floor-quick') === false) {
    $anchor = 'meeting_room</span> Quản lý phòng';
    $pos = strpos($v, $anchor);
    if ($pos !== false) {
        $closeA = strpos($v, '</a>', $pos);
        $insertAt = $closeA + strlen('</a>');
        $form = <<<'EOT'

                            <!-- [add-floor-quick] Them tang cho khu cu the -->
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-add-floor" class="inline-flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="area_id" value="<?= $areaIdNow ?>">
                                <input type="number" name="room_count" min="0" max="50" value="0" title="Số phòng nháp tạo sẵn (0 = không tạo)" class="w-24 px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                                    <span class="material-symbols-outlined text-base">add_home</span> Thêm tầng
                                </button>
                            </form>
EOT;
        $v = substr_replace($v, $form, $insertAt, 0);
        file_put_contents('views/admin/rooms/areas.php', $v);
        echo "[OK] areas.php: them form Them-tang vao moi card khu\n";
    } else { echo "[FAIL] khong thay anchor 'Quan ly phong'\n"; }
} else { echo "[SKIP] form Them-tang da ton tai\n"; }

// ===== 2. Backend: dat ten mac dinh cho tang =====
$ac = file_get_contents('controllers/AdminController.php');
$done = false;
foreach (["'name' => '',\n            'floor_number' => \$next,", "'name' => '',\r\n            'floor_number' => \$next,"] as $old) {
    if (strpos($ac, $old) !== false) {
        $ac = str_replace($old, "'name' => 'Tầng ' . \$next,\n            'floor_number' => \$next,", $ac);
        $done = true; break;
    }
}
if ($done) { file_put_contents('controllers/AdminController.php', $ac); echo "[OK] addFloorQuick: ten tang mac dinh 'Tầng N'\n"; }
else { echo "[WARN] khong thay dong 'name' trong addFloorQuick (kiem tra tay)\n"; }
