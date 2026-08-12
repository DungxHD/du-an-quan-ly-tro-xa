<?php
foreach (['views/admin/rooms/areas.php','controllers/AdminController.php'] as $f) { copy($f, $f.'.bak_'.date('Ymd_His')); }

// ===== 1. Controller: pass $allFloors =====
$ac = file_get_contents('controllers/AdminController.php');
$anchorC = '$areaTree = AreaModel::getTree();';
if (strpos($ac, $anchorC) !== false && strpos($ac, '$allFloors = FloorModel::getAll();') === false) {
    $ac = str_replace($anchorC, $anchorC . "\n        \$allFloors = FloorModel::getAll();", $ac);
    file_put_contents('controllers/AdminController.php', $ac);
    echo "[OK] areas(): pass \$allFloors\n";
}

// ===== 2. View: go form inline sai + them bo loc =====
$v = file_get_contents('views/admin/rooms/areas.php');

// 2a. Gỡ form add-floor-quick inline (patch sai)
$v = preg_replace('/<!-- \[add-floor-quick\].*?<\/form>\n?/s', '', $v);
echo "[OK] areas.php: go form inline Them-tang\n";

// 2b. Them bo loc Khu/Tang + action bar
$block = <<<'EOT'
    <!-- ===== [FILTER KHU/TANG] Dieu khien Them tang / Them phong ===== -->
    <?php
    $floorsMap = [];
    foreach ($allFloors as $fl) {
        $aid = (int)($fl['area_id'] ?? 0);
        $floorsMap[$aid][] = ['id' => (int)($fl['id'] ?? 0), 'name' => trim((string)($fl['name'] ?? '')) !== '' ? $fl['name'] : ('Tầng ' . (int)($fl['floor_number'] ?? 0))];
    }
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold mb-2">Chọn khu</label>
                <select id="filter-area" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="0">-- Tất cả khu --</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int)($a['id'] ?? 0) ?>"><?= e($a['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Chọn tầng</label>
                <select id="filter-floor" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="0">-- Tất cả tầng --</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <p id="filter-hint" class="text-sm text-gray-500">Chọn một khu cụ thể để thêm tầng. Chọn thêm một tầng cụ thể để thêm phòng.</p>
            <form id="quick-add-floor-form" method="POST" action="<?= BASE_URL ?>?page=admin-add-floor" class="hidden inline-flex items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="area_id" id="quick-add-floor-area" value="0">
                <input type="number" name="room_count" min="0" max="50" value="0" title="Số phòng nháp tạo sẵn (0 = không tạo)" class="w-24 px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                    <span class="material-symbols-outlined text-base">add_home</span> Thêm tầng
                </button>
            </form>
            <a id="quick-add-room-link" href="#" class="hidden inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-100 text-violet-700 font-semibold hover:bg-violet-200 transition">
                <span class="material-symbols-outlined text-base">meeting_room</span> Thêm phòng
            </a>
        </div>
    </div>
    <script>
    (function(){
        var floorsByArea = <?= json_encode($floorsMap, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
        var areaSel = document.getElementById('filter-area');
        var floorSel = document.getElementById('filter-floor');
        var addFloorForm = document.getElementById('quick-add-floor-form');
        var addFloorArea = document.getElementById('quick-add-floor-area');
        var addRoomLink = document.getElementById('quick-add-room-link');
        var hint = document.getElementById('filter-hint');
        function populateFloors(a){
            floorSel.innerHTML = '<option value="0">-- Tất cả tầng --</option>';
            var list = floorsByArea[a] || [];
            for (var i=0;i<list.length;i++){
                var o = document.createElement('option');
                o.value = String(list[i].id);
                o.textContent = list[i].name;
                floorSel.appendChild(o);
            }
            floorSel.value = '0';
        }
        function updateBar(){
            var a = parseInt(areaSel.value,10)||0;
            var f = parseInt(floorSel.value,10)||0;
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
        }
        areaSel.addEventListener('change', function(){ populateFloors(parseInt(areaSel.value,10)||0); updateBar(); });
        floorSel.addEventListener('change', updateBar);
        updateBar();
    })();
    </script>

EOT;

$anchorV = '<?php if (!empty($areaMessage)): ?>';
if (strpos($v, 'filter-area') === false && strpos($v, $anchorV) !== false) {
    $v = str_replace($anchorV, $block . $anchorV, $v);
    file_put_contents('views/admin/rooms/areas.php', $v);
    echo "[OK] areas.php: them bo loc Khu/Tang + action bar\n";
} else {
    echo "[SKIP/WARN] bo loc da ton tai hoac thieu anchor\n";
}
