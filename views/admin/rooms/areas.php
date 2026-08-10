<?php

/**
 * [DEV-QWEN-A][NHOM-2][2026-08-07]
 * Chức năng: Form thêm/sửa khu nhà
 * Thay đổi:
 *   - Bỏ input mã khu (area_code)
 *   - Ảnh khu: đổi từ text sang file upload
 *   - Floor builder: 1 select chọn tầng + 1 input số phòng, giá trị giữ khi chuyển tầng
 *   - [NEW] Nút "Xem tất cả phòng" góc phải header -> admin-rooms&area_id=0
 */
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'areas';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý khu, thêm khu mới kèm tầng và phòng nháp';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$expandedAreaId = (int)($expandedAreaId ?? 0);
$areaMessage = pullFlash('admin_area_message');
$areaError = pullFlash('admin_area_error');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Quản lý Khu</h2>
            <p class="text-gray-500 mt-2">Thêm khu mới kèm số tầng, tạo phòng nháp tự động, rồi hoàn thiện từng phòng ở trang Quản lý Phòng.</p>
        </div>

        <!-- ===== [NEW] NÚT XEM TẤT CẢ PHÒNG (góc phải) ===== -->
        <a href="<?= BASE_URL ?>?page=admin-rooms&area_id=0"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 font-semibold text-white transition hover:bg-opacity-90 shadow-sm shrink-0">
            <span class="material-symbols-outlined text-base">grid_view</span> Xem tất cả phòng
        </a>
    </div>

    <?php if (!empty($areaMessage)): ?>
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($areaMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($areaError)): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($areaError) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- ============ FORM KHU ============ -->
        <div class="xl:col-span-1">
            <?php if (!$editArea): ?>
                <button type="button" id="area-form-toggle" class="mb-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-4 py-3 font-semibold text-white transition hover:bg-opacity-90">
                    <span class="material-symbols-outlined text-base">add_business</span> Thêm khu mới
                </button>
            <?php endif; ?>

            <div id="area-form-card" class="<?= $editArea ? '' : 'hidden' ?> bg-white p-6 rounded-2xl shadow-sm border border-gray-100 xl:sticky xl:top-20 space-y-5">
                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-area" id="area-main-form" enctype="multipart/form-data" class="space-y-4">
                    <?= csrf_field() ?>
                    <?php if ($editArea): ?>
                        <input type="hidden" name="id" value="<?= (int)($editArea['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tên khu (để trống sẽ tự đặt)</label>
                        <input type="text" name="name" value="<?= e($editArea['name'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            placeholder="VD: Khu A - Sinh viên">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Địa chỉ</label>
                        <input type="text" name="address" value="<?= e($editArea['address'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            placeholder="VD: 123 Đường ABC, Quận 9">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Mô tả</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            placeholder="Mô tả ngắn về khu nhà..."><?= e($editArea['description'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Ảnh khu (chọn file từ máy)</label>
                        <input type="file" name="area_image" accept="image/jpeg,image/png,image/webp,image/gif"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold hover:file:bg-primary/20 cursor-pointer">
                        <p class="mt-1 text-xs text-gray-500">Chấp nhận JPG, PNG, WEBP, GIF. Tối đa 5MB.</p>
                    </div>

                    <?php if (!$editArea): ?>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Số tầng của khu *</label>
                            <input type="number" id="area-floor-count" name="floor_count" min="1" max="50" step="1" value="1"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <p class="mt-1 text-xs text-gray-500">Nhập tổng số tầng. Hệ thống sẽ tạo select để bạn chọn từng tầng và nhập số phòng.</p>
                        </div>

                        <div id="floor-builder" class="space-y-3">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Chọn tầng để nhập số phòng</label>
                                <select id="floor-selector" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                    <option value="">-- Chọn tầng --</option>
                                </select>
                            </div>
                            <div id="floor-room-input-wrap" class="hidden">
                                <label class="block text-sm font-semibold mb-2">Số phòng của <span id="floor-label-display">tầng</span> (giới hạn tối đa)</label>
                                <input type="number" id="floor-room-input" min="0" max="50" value="0"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <p class="mt-1 text-xs text-gray-500">Hệ thống tạo sẵn phòng nháp (VD: 01, 02, 03...). Không thể thêm vượt quá.</p>
                            </div>
                            <div id="floor-hidden-inputs"></div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        <?= $editArea ? 'Cập nhật khu' : 'Tạo khu + tạo phòng nháp' ?>
                    </button>
                    <?php if ($editArea): ?>
                        <a href="<?= BASE_URL ?>?page=admin-areas" class="block w-full py-3 text-center text-gray-600 hover:text-primary">Hủy chỉnh sửa</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ============ DANH SÁCH KHU ============ -->
        <div class="xl:col-span-2 space-y-4">
            <?php if (empty($areaTree)): ?>
                <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                    Chưa có khu nào. Bấm "Thêm khu mới" để bắt đầu.
                </div>
            <?php endif; ?>
            <?php foreach ($areaTree as $area): ?>
                <?php
                $areaIdNow = (int)($area['id'] ?? 0);
                $isExpanded = $expandedAreaId > 0 ? $areaIdNow === $expandedAreaId : false;
                ?>
                <details class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" <?= $isExpanded ? 'open' : '' ?>>
                    <summary class="list-none cursor-pointer px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-100 shrink-0">
                                <?php if (!empty($area['image'])): ?>
                                    <img src="<?= e($area['image']) ?>" alt="<?= e($area['name'] ?? 'Ảnh khu') ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400"><span class="material-symbols-outlined text-3xl">image</span></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-xl font-bold"><?= e($area['name'] ?? '') ?></h3>
                                    <span class="px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold"><?= (int)($area['floor_count'] ?? 0) ?> tầng</span>
                                    <span class="px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-xs font-semibold"><?= (int)($area['room_count'] ?? 0) ?> phòng</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-2"><?= e(fallbackText($area['address'] ?? '', 'Chưa cập nhật địa chỉ')) ?></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <span class="px-3 py-2 rounded-xl bg-green-50 text-green-700 text-sm font-semibold">Trống: <?= (int)($area['available_count'] ?? 0) ?></span>
                            <span class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold">Đã thuê: <?= (int)($area['rented_count'] ?? 0) ?></span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6 border-t border-gray-100 space-y-4">
                        <div class="pt-4 flex flex-wrap gap-3">
                            <a href="<?= BASE_URL ?>?page=admin-areas&edit=<?= $areaIdNow ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">
                                <span class="material-symbols-outlined text-base">edit</span> Sửa khu
                            </a>
                            <a href="<?= BASE_URL ?>?page=admin-rooms&area_id=<?= $areaIdNow ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-100 text-violet-700 font-semibold hover:bg-violet-200 transition">
                                <span class="material-symbols-outlined text-base">meeting_room</span> Quản lý phòng
                            </a>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    (function() {
        var toggleBtn = document.getElementById('area-form-toggle');
        var formCard = document.getElementById('area-form-card');
        if (toggleBtn && formCard) {
            toggleBtn.addEventListener('click', function() {
                var isHidden = formCard.classList.toggle('hidden');
                toggleBtn.innerHTML = isHidden ?
                    '<span class="material-symbols-outlined text-base">add_business</span> Thêm khu mới' :
                    '<span class="material-symbols-outlined text-base">close</span> Đóng form';
            });
        }

        var floorCountInput = document.getElementById('area-floor-count');
        var floorSelector = document.getElementById('floor-selector');
        var floorRoomInput = document.getElementById('floor-room-input');
        var floorRoomWrap = document.getElementById('floor-room-input-wrap');
        var floorLabelDisplay = document.getElementById('floor-label-display');
        var floorHiddenInputs = document.getElementById('floor-hidden-inputs');

        if (!floorCountInput || !floorSelector || !floorRoomInput) {
            return;
        }

        var floorRoomData = {};
        var currentFloor = '';

        function renderFloorOptions() {
            var count = parseInt(floorCountInput.value, 10) || 1;
            if (count < 1) count = 1;
            if (count > 50) count = 50;

            floorSelector.innerHTML = '<option value="">-- Chọn tầng --</option>';
            for (var n = 1; n <= count; n++) {
                var opt = document.createElement('option');
                opt.value = String(n);
                opt.textContent = 'Tầng ' + n;
                if (String(n) === currentFloor) {
                    opt.selected = true;
                }
                floorSelector.appendChild(opt);
            }

            var keys = Object.keys(floorRoomData);
            for (var i = 0; i < keys.length; i++) {
                if (parseInt(keys[i], 10) > count) {
                    delete floorRoomData[keys[i]];
                }
            }

            renderHiddenInputs();
        }

        function renderHiddenInputs() {
            floorHiddenInputs.innerHTML = '';
            var keys = Object.keys(floorRoomData);
            for (var i = 0; i < keys.length; i++) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'floor_rooms[' + keys[i] + ']';
                inp.value = String(floorRoomData[keys[i]]);
                floorHiddenInputs.appendChild(inp);
            }
        }

        floorSelector.addEventListener('change', function() {
            if (currentFloor !== '') {
                floorRoomData[currentFloor] = parseInt(floorRoomInput.value, 10) || 0;
            }
            currentFloor = floorSelector.value;
            if (currentFloor === '') {
                floorRoomWrap.classList.add('hidden');
                renderHiddenInputs();
                return;
            }
            floorRoomWrap.classList.remove('hidden');
            floorLabelDisplay.textContent = 'Tầng ' + currentFloor;
            var savedValue = floorRoomData[currentFloor];
            floorRoomInput.value = (savedValue !== undefined) ? savedValue : 0;
        });

        floorRoomInput.addEventListener('input', function() {
            if (currentFloor !== '') {
                floorRoomData[currentFloor] = parseInt(floorRoomInput.value, 10) || 0;
                renderHiddenInputs();
            }
        });

        floorCountInput.addEventListener('input', function() {
            renderFloorOptions();
        });

        renderFloorOptions();
    })();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>