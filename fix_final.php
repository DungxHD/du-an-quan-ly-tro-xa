<?php
$bakDir = 'backups/20260811_181839';
if (!file_exists("$bakDir/rooms.php") || !file_exists("$bakDir/AdminController.php")) {
    die("[FAIL] Khong thay backup. Dung xoa thu cong gi ca, bao toi.\n");
}

// ===== 1. KHOI PHUC VE BAN TRUOC PATCH =====
copy("$bakDir/rooms.php", 'views/admin/rooms/rooms.php');
copy("$bakDir/AdminController.php", 'controllers/AdminController.php');
echo "[OK] Khoi phuc ca 2 file tu backup\n";

// ===== 2. VA rooms.php =====
$v = file_get_contents('views/admin/rooms/rooms.php');
$eol = (strpos($v, "\r\n") !== false) ? "\r\n" : "\n";

// 2a. Bien $hasArea + $selectedAreaName
$formTag = '<form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">';
$phpVars = '<?php $hasArea = ($selectedAreaId > 0); $selectedAreaName = "khu"; foreach ($areas as $a) { if ((int)($a["id"] ?? 0) === (int)$selectedAreaId) { $selectedAreaName = $a["name"] ?? "khu"; break; } } ?>';
if (strpos($v, $formTag) === false) die("[FAIL] Khong thay form filter\n");
$v = str_replace($formTag, $formTag . $eol . '            ' . $phpVars, $v);
echo "[OK] Them \$hasArea + \$selectedAreaName\n";

// 2b. Khoi nut (khop CRLF)
$oldLines = [
'            <div class="flex items-end">',
'                <?php if ($hasFloor): ?>',
'                    <button type="button" id="btn-add-room" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">',
'                        <span class="material-symbols-outlined text-base">add_home</span> Thêm phòng vào <?= e($selectedFloor["name"] ?? "tầng") ?>',
'                    </button>',
'                <?php else: ?>',
'                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một tầng để hiện nút "Thêm phòng".</p>',
'                <?php endif; ?>',
'            </div>',
];
$oldBlock = implode($eol, $oldLines);
if (strpos($v, $oldBlock) === false) {
    // thu ban LF neu file LF
    $oldBlock = implode("\n", $oldLines);
    if (strpos($v, $oldBlock) === false) die("[FAIL] Khong khop khoi nut Them phong\n");
}
$newBlock = implode($eol, [
'            <div class="flex items-end">',
'                <?php if ($hasFloor): ?>',
'                    <button type="button" id="btn-add-room" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">',
'                        <span class="material-symbols-outlined text-base">add_home</span> Thêm phòng vào <?= e($selectedFloor["name"] ?? "tầng") ?>',
'                    </button>',
'                <?php elseif ($hasArea): ?>',
'                    <button type="button" id="btn-add-floor" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">',
'                        <span class="material-symbols-outlined text-base">add_business</span> Thêm tầng vào <?= e($selectedAreaName) ?>',
'                    </button>',
'                <?php else: ?>',
'                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một khu để hiện nút "Thêm tầng".</p>',
'                <?php endif; ?>',
'            </div>',
]);
$v = str_replace($oldBlock, $newBlock, $v);
echo "[OK] Them nut Them-tang (dieu kien khu + tat ca tang)\n";

// 2c. Modal dung schema + csrf, neo vao panel_footer
$modal = <<<'EOT'
<!-- Modal Thêm Tầng -->
<div id="modal-add-floor" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-bold mb-4">Thêm tầng mới vào <?= e($selectedAreaName) ?></h3>
        <form id="form-add-floor" method="POST" action="<?= BASE_URL ?>?page=admin-add-floor">
            <?= csrf_field() ?>
            <input type="hidden" name="area_id" value="<?= (int)$selectedAreaId ?>">
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">Số phòng nháp cần tạo</label>
                <input type="number" name="room_count" min="0" max="50" value="0" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                <p class="text-xs text-gray-500 mt-1">Hệ thống tự đặt tên tầng (Tầng 3, Tầng 4...). Nhập 0 nếu chỉ tạo tầng trống.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" id="btn-cancel-add-floor" class="flex-1 rounded-xl border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">Hủy</button>
                <button type="submit" class="flex-1 rounded-xl bg-primary px-4 py-2 font-semibold text-white hover:bg-opacity-90">Thêm tầng</button>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
    var btn = document.getElementById('btn-add-floor');
    var modal = document.getElementById('modal-add-floor');
    var cancel = document.getElementById('btn-cancel-add-floor');
    if (btn) btn.addEventListener('click', function(){ modal.classList.remove('hidden'); modal.classList.add('flex'); });
    if (cancel) cancel.addEventListener('click', function(){ modal.classList.add('hidden'); modal.classList.remove('flex'); });
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal){ modal.classList.add('hidden'); modal.classList.remove('flex'); } });
})();
</script>
EOT;
$footer = "<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>";
if (strpos($v, $footer) === false) die("[FAIL] Khong thay dong require panel_footer\n");
$v = str_replace($footer, $modal . $eol . $footer, $v);
file_put_contents('views/admin/rooms/rooms.php', $v);
echo "[OK] Them modal (csrf + room_count) truoc panel_footer\n";

// ===== 3. VA AdminController CHIR TRONG addFloorQuick =====
$ac = file_get_contents('controllers/AdminController.php');
$s = strpos($ac, 'function addFloorQuick(');
$o = strpos($ac, '{', $s); $d = 0; $e = $o;
for ($i = $o; $i < strlen($ac); $i++) { if ($ac[$i]==='{') $d++; elseif ($ac[$i]==='}') { $d--; if ($d===0){ $e=$i; break; } } }
$method = substr($ac, $s, $e - $s + 1);
$m2 = str_replace("redirectTo('admin-areas', ['area' => \$areaId]);", "redirectTo('admin-rooms', ['area_id' => \$areaId, 'floor_id' => 0]);", $method);
$m2 = str_replace("setFlash('admin_area_message',", "setFlash('admin_room_message',", $m2);
if ($m2 === $method) die("[FAIL] Khong thay dong can sua trong addFloorQuick\n");
$ac = substr_replace($ac, $m2, $s, $e - $s + 1);
file_put_contents('controllers/AdminController.php', $ac);
echo "[OK] addFloorQuick: redirect ve admin-rooms + flash key (chi trong method nay)\n";

// ===== 4. XAC MINH =====
$ac = file_get_contents('controllers/AdminController.php');
$v = file_get_contents('views/admin/rooms/rooms.php');
echo "\n=== XAC MINH ===\n";
echo "AdminController 'admin_area_message' (saveArea/deleteArea phai >0): " . substr_count($ac, "admin_area_message") . "\n";
echo "rooms btn-add-floor: " . (strpos($v,'btn-add-floor')!==false?'CO':'KHONG') . "\n";
echo "rooms modal + csrf: " . ((strpos($v,'modal-add-floor')!==false && strpos($v,'csrf_field()')!==false)?'CO':'KHONG') . "\n";
echo "rooms default_price (phai KHONG): " . (strpos($v,'default_price')!==false?'CON!!':'KHONG') . "\n";
