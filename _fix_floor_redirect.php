<?php
$f = 'views/admin/rooms/areas.php';
copy($f, $f.'.bak_'.date('Ymd_His'));
$v = file_get_contents($f);

$old = <<<'EOT'
            <form id="quick-add-floor-form" method="POST" action="<?= BASE_URL ?>?page=admin-add-floor" class="hidden inline-flex items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="area_id" id="quick-add-floor-area" value="0">
                <input type="number" name="room_count" min="0" max="50" value="0" title="Số phòng nháp tạo sẵn (0 = không tạo)" class="w-24 px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                    <span class="material-symbols-outlined text-base">add_home</span> Thêm tầng
                </button>
            </form>
EOT;

$new = <<<'EOT'
            <a id="quick-add-floor-link" href="#" class="hidden inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                <span class="material-symbols-outlined text-base">add_home</span> Thêm tầng
            </a>
EOT;

if (strpos($v, $old) !== false) {
    $v = str_replace($old, $new, $v);
    echo "[OK] Doi form Them-tang thanh link redirect\n";
} else {
    echo "[FAIL] Khong tim thay form Them-tang hien tai\n";
    exit(1);
}

// Cap nhat JS
$jsOld = <<<'EOT'
        var addFloorForm = document.getElementById('quick-add-floor-form');
        var addFloorArea = document.getElementById('quick-add-floor-area');
        var addRoomLink = document.getElementById('quick-add-room-link');
EOT;

$jsNew = <<<'EOT'
        var addFloorLink = document.getElementById('quick-add-floor-link');
        var addRoomLink = document.getElementById('quick-add-room-link');
EOT;

if (strpos($v, $jsOld) !== false) {
    $v = str_replace($jsOld, $jsNew, $v);
    echo "[OK] Cap nhat bien JS\n";
}

$jsOld2 = <<<'EOT'
            if (a === 0) {
                addFloorForm.classList.add('hidden'); addRoomLink.classList.add('hidden'); hint.classList.remove('hidden');
            } else if (f === 0) {
                addFloorArea.value = String(a);
                addFloorForm.classList.remove('hidden'); addRoomLink.classList.add('hidden'); hint.classList.add('hidden');
            } else {
                addFloorForm.classList.add('hidden'); addRoomLink.classList.remove('hidden');
                addRoomLink.href = '<?= BASE_URL ?>?page=admin-rooms&area_id=' + a + '&floor_id=' + f;
                hint.classList.add('hidden');
            }
EOT;

$jsNew2 = <<<'EOT'
            if (a === 0) {
                addFloorLink.classList.add('hidden'); addRoomLink.classList.add('hidden'); hint.classList.remove('hidden');
            } else if (f === 0) {
                addFloorLink.classList.remove('hidden'); addRoomLink.classList.add('hidden');
                addFloorLink.href = '<?= BASE_URL ?>?page=admin-rooms&area_id=' + a + '&floor_id=0';
                hint.classList.add('hidden');
            } else {
                addFloorLink.classList.add('hidden'); addRoomLink.classList.remove('hidden');
                addRoomLink.href = '<?= BASE_URL ?>?page=admin-rooms&area_id=' + a + '&floor_id=' + f;
                hint.classList.add('hidden');
            }
EOT;

if (strpos($v, $jsOld2) !== false) {
    $v = str_replace($jsOld2, $jsNew2, $v);
    file_put_contents($f, $v);
    echo "[OK] Cap nhat logic JS\n";
} else {
    echo "[FAIL] Khong tim thay logic JS hien tai\n";
}
