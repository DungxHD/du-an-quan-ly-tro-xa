<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'rooms';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý phòng trọ theo khu - tầng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];

$statusMap = [
    'draft'       => ['label' => 'Nháp (chưa đăng web)', 'badge' => 'bg-slate-100 text-slate-600 border-slate-200'],
    'available'   => ['label' => 'Còn trống', 'badge' => 'bg-green-100 text-green-700 border-green-200'],
    'rented'      => ['label' => 'Đã thuê', 'badge' => 'bg-rose-100 text-rose-700 border-rose-200'],
    'maintenance' => ['label' => 'Bảo trì', 'badge' => 'bg-amber-100 text-amber-700 border-amber-200'],
];
$formStatusOptions = [
    'draft' => $statusMap['draft'],
    'available' => $statusMap['available'],
    'maintenance' => $statusMap['maintenance'],
];

$currentFilters = [
    'area_id'  => (int)($filters['area_id'] ?? 0),
    'floor_id' => (int)($filters['floor_id'] ?? 0),
    'status'   => (string)($filters['status'] ?? ''),
];
$filterResetUrl = BASE_URL . '?page=admin-rooms';
$floorsJson = json_encode($allFloors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$roomsJson  = json_encode($rooms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$imagesByRoom = [];
if (class_exists('RoomImageModel')) {
    foreach ($rooms as $r) {
        $rid = (int)($r['id'] ?? 0);
        if ($rid > 0) {
            $imagesByRoom[$rid] = RoomImageModel::getByRoom($rid);
        }
    }
}
$imagesJson = json_encode($imagesByRoom, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$formRoom = $formRoom ?? null;
$isEditing = !empty($formRoom['id']);
$formRoomId = (int)($formRoom['id'] ?? 0);
$formAreaId = (int)($formAreaId ?? ($formRoom['area_id'] ?? ($currentFilters['area_id'] ?: ($areas[0]['id'] ?? 0))));
$formFloors = $formFloors ?? ($formAreaId > 0 ? FloorModel::getByAreaId($formAreaId) : []);

$drawerImages = $isEditing ? ($imagesByRoom[$formRoomId] ?? []) : [];
$drawerPrimary = '';
$drawerSubs = ['', '', ''];
$__si = 0;
foreach ($drawerImages as $__img) {
    $__u = (string)($__img['image_url'] ?? '');
    if ((int)($__img['is_primary'] ?? 0) === 1) {
        $drawerPrimary = $__u;
    } elseif ($__si < 3) {
        $drawerSubs[$__si++] = $__u;
    }
}

$hasFloor = $currentFilters['floor_id'] > 0;
$defaultThumb = 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900';

require BASE_PATH . 'views/layouts/panel_header.php';
?>
<style>
    #room-drawer::-webkit-scrollbar {
        width: 8px
    }

    #room-drawer::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px
    }
</style>

<div class="flex flex-col gap-6">
    <!-- ===== NÚT QUAY LẠI KHU NHÀ ===== -->
    <div>
        <a href="<?= BASE_URL ?>?page=admin-areas"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-700 shadow-sm transition hover:border-primary hover:text-primary">
            <span class="material-symbols-outlined text-base">arrow_back</span> Quay lại khu nhà
        </a>
    </div>

    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h2 class="text-3xl font-bold">Quản lý Phòng trọ</h2>
            <p class="mt-2 text-gray-600">Muốn xem chi tiết các phòng ở từng tầng có thể sử dụng bộ lọc ở bên dưới <br> hoặc sử dụng thanh tìm kiếm để tìm tên phòng.</p>
        </div>
        <a href="<?= $filterResetUrl ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-700 transition hover:border-primary hover:text-primary">
            <span class="material-symbols-outlined text-base">filter_alt_off</span> Xóa toàn bộ bộ lọc
        </a>
    </div>

    <?php if (!empty($roomMessage)): ?><div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($roomMessage) ?></div><?php endif; ?>
    <?php if (!empty($roomError)): ?>
        <!-- [DEV-QWEN-A][FIX-UX] Alert dismiss -->
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800 relative" id="room-error-box">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-rose-500 mt-0.5">error</span>
                <div class="flex-1">
                    <p class="text-sm mt-1"><?= e($roomError) ?></p>
                    <button type="button" onclick="document.getElementById('room-error-box').remove()"
                            class="mt-2 px-3 py-1.5 bg-rose-200 text-rose-800 rounded-lg text-xs font-bold hover:bg-rose-300 transition">Đã hiểu, ẩn thông báo</button>
                </div>
                <button type="button" onclick="document.getElementById('room-error-box').remove()" class="text-rose-400 hover:text-rose-600 text-xl font-bold">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <?php ($currentFilters['area_id'] > 0) = (($currentFilters['area_id'] > 0)); $selectedAreaName = "khu"; foreach ($areas as $a) { if ((int)($a["id"] ?? 0) === (int)$selectedAreaId) { $selectedAreaName = $a["name"] ?? "khu"; break; } } ?>
            <input type="hidden" name="page" value="admin-rooms">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Khu</label>
                <select name="area_id" id="filter-area" onchange="var f=document.getElementById('filter-floor'); if(f){f.value='0'; f.disabled=(this.value=='0');} this.form.submit();" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="0">Tất cả khu</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int)($area['id'] ?? 0) ?>" <?= $currentFilters['area_id'] === (int)($area['id'] ?? 0) ? 'selected' : '' ?>><?= e($area['name'] ?? 'Khu') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Tầng</label>
                <select name="floor_id" id="filter-floor" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 <?= $currentFilters['area_id'] <= 0 ? 'bg-gray-100 cursor-not-allowed text-gray-400' : '' ?>" <?= $currentFilters['area_id'] <= 0 ? 'disabled' : '' ?>>
                    <option value="0">-- Tất cả tầng --</option>
                    <?php foreach ($filterFloors as $floor): ?>
                        <option value="<?= (int)($floor['id'] ?? 0) ?>" <?= $currentFilters['floor_id'] === (int)($floor['id'] ?? 0) ? 'selected' : '' ?>><?= e($floor['name'] ?? 'Tầng') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Trạng thái</label>
                <select name="status" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statusMap as $key => $meta): ?>
                        <option value="<?= $key ?>" <?= $currentFilters['status'] === $key ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <?php if ($hasFloor): ?>
                    <button type="button" id="btn-add-room" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                        <span class="material-symbols-outlined text-base">add_home</span> Thêm phòng vào <?= e($selectedFloor['name'] ?? 'tầng') ?>
                    </button>
                <?php elseif (($currentFilters['area_id'] > 0)): ?>
                    <button type="button" id="btn-add-floor" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                        <span class="material-symbols-outlined text-base">add_business</span> Thêm tầng vào <?= e($selectedAreaName) ?>
                    </button>
                <?php else: ?>
                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một khu để hiện "Thêm tầng"; chọn thêm tầng để hiện "Thêm phòng".</p>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="rounded-3xl border border-gray-100 bg-white shadow-sm">
        <!-- ===== TIÊU ĐỀ DANH SÁCH + THANH TÌM KIẾM ===== -->
        <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-bold">Danh sách phòng (<span id="room-count"><?= count($rooms) ?></span>)</h3>
            <?php if (!empty($rooms)): ?>
                <div class="relative md:w-80">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base">search</span>
                    <input type="text" id="room-search" placeholder="Tìm theo tên phòng..." autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 pl-9 pr-9 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <button type="button" id="room-search-clear" title="Xóa tìm kiếm"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="px-6 py-12 text-center text-gray-500">Không có phòng nào khớp bộ lọc.</div>
        <?php else: ?>
            <div id="room-grid" class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <?php foreach ($rooms as $room): ?>
                    <?php
                    $roomId = (int)($room['id'] ?? 0);
                    $meta = $statusMap[$room['status'] ?? 'draft'] ?? $statusMap['draft'];
                    $isRented = ($room['status'] ?? '') === 'rented' || (int)($room['occupant_count'] ?? 0) > 0;
                    $deleteParams = http_build_query(array_filter(['page' => 'admin-delete-room', 'id' => $roomId, 'area_id' => $currentFilters['area_id'], 'floor_id' => $currentFilters['floor_id'], 'status' => $currentFilters['status']], static fn($v) => $v !== '' && $v !== null));
                    ?>
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                        data-room-card data-room-name="<?= e($room['name'] ?? '') ?>">
                        <button type="button" data-edit-room="<?= $roomId ?>" class="relative block aspect-video w-full overflow-hidden bg-gray-100 text-left">
                            <img src="<?= e(trim((string)($room['thumbnail'] ?? '')) ?: $defaultThumb) ?>" alt="<?= e($room['name'] ?? 'Phòng') ?>" class="h-full w-full object-cover">
                            <span class="absolute right-3 top-3 rounded-full border px-3 py-1 text-xs font-semibold <?= $meta['badge'] ?>"><?= e($meta['label']) ?></span>
                        </button>
                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-gray-900"><?= e($room['name'] ?? 'Phòng') ?></p>
                                    <p class="text-xs text-gray-500"><?= e($room['area_name'] ?? '') ?> · <?= e($room['floor_name'] ?? '') ?></p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Ở: <?= (int)($room['occupant_count'] ?? 0) ?>/<?= (int)($room['max_occupancy'] ?? 0) ?></span>
                            </div>
                            <p class="font-semibold text-primary"><?= number_format((float)($room['price'] ?? 0), 0, ',', '.') ?>đ/tháng</p>
                            <div class="mt-auto flex items-center gap-2 pt-2">
                                <button type="button" data-edit-room="<?= $roomId ?>" class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                    <span class="material-symbols-outlined text-base">edit</span> Sửa
                                </button>
                                <?php if ($isRented): ?>
                                    <span class="inline-flex flex-1 cursor-not-allowed items-center justify-center gap-1 rounded-xl bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-400"><span class="material-symbols-outlined text-base">lock</span> Khóa xóa</span>
                                <?php else: ?>
                                    <form method="POST" action="<?= BASE_URL ?>?<?= $deleteParams ?>" class="inline-flex flex-1" onsubmit="return confirm('Xóa phòng <?= e($room['name'] ?? '') ?>?');">
    <?= csrf_field() ?>
    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"><span class="material-symbols-outlined text-base">delete</span> Xóa</button>
</form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Kết quả tìm kiếm rỗng -->
            <div id="room-search-empty" class="hidden px-6 py-12 text-center text-gray-500">
                <span class="material-symbols-outlined text-4xl text-gray-300 mb-2">search_off</span>
                <p>Không có phòng nào khớp từ khóa tìm kiếm.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- DRAWER -->
<div id="room-drawer-backdrop" class="hidden fixed inset-0 z-40 bg-black/40"></div>
<aside id="room-drawer" data-csrf="<?= e(csrf_token()) ?>" data-upload-url="<?= BASE_URL ?>?page=admin-upload-image"
    class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-xl translate-x-full bg-white shadow-2xl transition-transform duration-300">
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
            <div>
                <h3 id="drawer-title" class="text-lg font-bold">Thêm phòng mới</h3>
                <p id="drawer-context" class="mt-0.5 text-xs text-gray-500"></p>
            </div>
            <button type="button" data-close-drawer class="rounded-xl border border-gray-200 p-2 text-gray-600 transition hover:bg-gray-50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="room-drawer-form" method="POST" action="<?= BASE_URL ?>?page=admin-save-room" class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="drawer-room-id" value="0">
            <input type="hidden" name="position" id="drawer-position" value="0">
            <input type="hidden" name="extend_limit" id="drawer-extend" value="0">
            <input type="hidden" name="return_area_id" value="<?= $currentFilters['area_id'] ?>">
            <input type="hidden" name="return_floor_id" value="<?= $currentFilters['floor_id'] ?>">
            <input type="hidden" name="return_status" value="<?= e($currentFilters['status']) ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Khu</label>
                    <input type="text" id="drawer-area-display" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-gray-500 cursor-not-allowed outline-none" value="">
                    <input type="hidden" name="area_id" id="drawer-area-id" value="">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Tầng</label>
                    <input type="text" id="drawer-floor-display" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-gray-500 cursor-not-allowed outline-none" value="">
                    <input type="hidden" name="floor_id" id="drawer-floor-id" value="">
                </div>
            </div>

            <div><label class="mb-1 block text-sm font-semibold text-gray-700">Tên phòng (để trống sẽ tự sinh)</label>
                <input type="text" name="name" id="drawer-name" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1 block text-sm font-semibold text-gray-700">Giá thuê (VNĐ) *</label>
                    <input type="number" name="price" id="drawer-price" min="0" step="1000" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <input type="hidden" name="price_effective_month" id="drawer-price-month" value="0">
                    <input type="hidden" name="price_effective_year" id="drawer-price-year" value="0">
                </div>
                <div><label class="mb-1 block text-sm font-semibold text-gray-700">Diện tích (m2)</label>
                    <input type="number" name="area" id="drawer-area" min="0" step="0.1" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1 block text-sm font-semibold text-gray-700">Sức chứa tối đa *</label>
                    <input type="number" name="max_occupancy" id="drawer-max" min="1" value="2" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div><label class="mb-1 block text-sm font-semibold text-gray-700">Trạng thái *</label>
                    <select name="status" id="drawer-status" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <?php foreach ($formStatusOptions as $key => $meta): ?><option value="<?= $key ?>"><?= e($meta['label']) ?></option><?php endforeach; ?>
                    </select>
                    <input type="text" id="drawer-status-readonly" readonly class="hidden w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-gray-500 cursor-not-allowed outline-none" value="Đã thuê">
                    <input type="hidden" name="status" id="drawer-status-hidden" value="" disabled>
                </div>
            </div>

            <div><label class="mb-1 block text-sm font-semibold text-gray-700">Mô tả *</label>
                <textarea name="description" id="drawer-description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
            </div>

            <div class="space-y-3">
                <label class="mb-1 block text-sm font-semibold text-gray-700">Tiện ích phòng</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Available amenities -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <h4 class="text-sm font-bold text-gray-800 mb-2">Tiện ích khả dụng</h4>
                        <div id="amenities-available" class="space-y-1 max-h-48 overflow-y-auto"></div>
                    </div>
                    <!-- Assigned amenities -->
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <h4 class="text-sm font-bold text-gray-800 mb-2">Đang gán cho phòng</h4>
                        <div id="amenities-assigned" class="space-y-1 max-h-48 overflow-y-auto"></div>
                    </div>
                </div>
                <!-- Preview card -->
                <div class="rounded-xl border border-dashed border-gray-300 bg-blue-50/50 p-3">
                    <h4 class="text-sm font-bold text-gray-800 mb-2">Preview "website thu nhỏ" (cập nhật theo bản nháp)</h4>
                    <div id="amenity-preview" class="text-sm text-gray-700">Chưa có tiện ích nào</div>
                </div>
                <!-- Hidden CSV input -->
                <input type="hidden" name="amenities" id="drawer-amenities" value="<?= e($drawerAmenities ?? '') ?>">
            </div>

            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 space-y-3">
                <p class="text-sm font-bold text-gray-800">Ảnh phòng (1 ảnh chính + 3 ảnh phụ) — upload bằng file</p>
                <div class="space-y-2" data-image-field>
                    <label class="block text-xs font-semibold text-gray-700">Ảnh chính</label>
                    <img data-image-preview src="<?= e($drawerPrimary !== '' ? $drawerPrimary : $defaultThumb) ?>" class="h-32 w-full rounded-xl border border-gray-200 object-cover">
                    <input type="hidden" name="primary_image" data-image-value value="<?= e($drawerPrimary) ?>">
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-file class="w-full text-xs">
                </div>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="space-y-2" data-image-field>
                        <label class="block text-xs font-semibold text-gray-700">Ảnh phụ <?= $i + 1 ?></label>
                        <img data-image-preview src="<?= e($drawerSubs[$i] !== '' ? $drawerSubs[$i] : $defaultThumb) ?>" class="h-24 w-full rounded-xl border border-gray-200 object-cover">
                        <input type="hidden" name="gallery_images[<?= $i ?>]" data-image-value value="<?= e($drawerSubs[$i]) ?>">
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-file class="w-full text-xs">
                    </div>
                <?php endfor; ?>
            </div>

            <button type="submit" class="w-full rounded-xl bg-primary px-4 py-3 font-semibold text-white transition hover:bg-opacity-90">
                <span class="material-symbols-outlined text-base align-middle">save</span> Lưu phòng
            </button>

<script>
(function() {
    const allAmenities = <?= json_encode($allAmenities ?? []) ?>;
    let assigned = [];

    function initAmenities() {
        const csv = $('drawer-amenities').value.trim();
        assigned = csv ? csv.split(',').map(s => s.trim()).filter(Boolean) : [];
        renderAvailable();
        renderAssigned();
        renderPreview();
    }

    function renderAvailable() {
        const container = $('amenities-available');
        const available = allAmenities.filter(a => !assigned.includes(a.title));
        if (available.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500">Không còn tiện ích nào</p>';
            return;
        }
        container.innerHTML = available.map(a => 
            `<button type="button" class="w-full text-left px-2 py-1.5 rounded-lg bg-white border border-gray-200 hover:border-primary hover:bg-primary/5 transition text-sm" onclick="addAmenity('${a.title.replace(/'/g, "\\'")}')">
                <span class="material-symbols-outlined text-base mr-1">${a.icon}</span>
                ${a.title}
                <span class="float-right text-primary font-bold">+</span>
            </button>`
        ).join('');
    }

    function renderAssigned() {
        const container = $('amenities-assigned');
        if (assigned.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500">Chưa gán tiện ích nào</p>';
            return;
        }
        container.innerHTML = assigned.map(title => {
            const amenity = allAmenities.find(a => a.title === title);
            const icon = amenity ? amenity.icon : 'add';
            return `<button type="button" class="w-full text-left px-2 py-1.5 rounded-lg bg-primary/10 border border-primary/30 hover:bg-red-50 hover:border-red-300 transition text-sm" onclick="removeAmenity('${title.replace(/'/g, "\\'")}')">
                <span class="material-symbols-outlined text-base mr-1">${icon}</span>
                ${title}
                <span class="float-right text-red-600 font-bold">−</span>
            </button>`;
        }).join('');
    }

    function renderPreview() {
        const container = $('amenity-preview');
        if (assigned.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 italic">Chưa có tiện ích nào — phòng sẽ không hiển thị tiện ích trên website</p>';
            return;
        }
        const items = assigned.map(title => {
            const amenity = allAmenities.find(a => a.title === title);
            const icon = amenity ? amenity.icon : 'add';
            return `<div class="inline-flex items-center gap-1 px-2 py-1 bg-white rounded-lg border border-gray-200 text-xs mr-1 mb-1">
                <span class="material-symbols-outlined text-sm">${icon}</span>
                <span>${title}</span>
            </div>`;
        }).join('');
        container.innerHTML = `<div class="flex flex-wrap">${items}</div>`;
    }

    window.addAmenity = function(title) {
        if (!assigned.includes(title)) {
            assigned.push(title);
            renderAvailable();
            renderAssigned();
            renderPreview();
            syncCSV();
        }
    };

    window.removeAmenity = function(title) {
        assigned = assigned.filter(t => t !== title);
        renderAvailable();
        renderAssigned();
        renderPreview();
        syncCSV();
    };

    function syncCSV() {
        $('drawer-amenities').value = assigned.join(', ');
    }

    // Wait for drawer to open
    const observer = new MutationObserver((mutations, obs) => {
        if ($('drawer-amenities')) {
            initAmenities();
            obs.disconnect();
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
        </form>
    </div>
</aside>

<script>
    (() => {
        const floors = <?= $floorsJson ?: '[]' ?>;
        const areas = <?= json_encode($areas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const rooms = <?= $roomsJson ?: '[]' ?>;
        const roomImages = <?= $imagesJson ?: '{}' ?>;
        const drawer = document.getElementById('room-drawer');
        const backdrop = document.getElementById('room-drawer-backdrop');
        const form = document.getElementById('room-drawer-form');
        const $ = (id) => document.getElementById(id);
        const defaultThumb = '<?= $defaultThumb ?>';
        let currentRoomId = 0;

        const getAreaName = (id) => {
            const a = areas.find(x => Number(x.id) === Number(id));
            return a ? (a.name || '') : '';
        };
        const getFloorName = (id) => {
            const f = floors.find(x => Number(x.id) === Number(id));
            return f ? (f.name || '') : '';
        };

        /* ===== TÌM KIẾM THEO TÊN PHÒNG (client-side, bỏ dấu tiếng Việt) ===== */
        const normalizeText = (s) => (s || '').toString().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd');
        const searchInput = $('room-search');
        const searchClear = $('room-search-clear');
        const roomCards = Array.from(document.querySelectorAll('[data-room-card]'));
        const roomCountEl = $('room-count');
        const gridEl = $('room-grid');
        const searchEmptyEl = $('room-search-empty');
        const totalRooms = roomCards.length;

        const applySearch = () => {
            const term = normalizeText((searchInput ? searchInput.value : '').trim());
            let visible = 0;
            roomCards.forEach(card => {
                const name = normalizeText(card.getAttribute('data-room-name') || '');
                const match = term === '' || name.includes(term);
                card.classList.toggle('hidden', !match);
                if (match) visible++;
            });
            if (roomCountEl) roomCountEl.textContent = term === '' ? totalRooms : visible;
            if (gridEl) gridEl.classList.toggle('hidden', visible === 0);
            if (searchEmptyEl) searchEmptyEl.classList.toggle('hidden', visible > 0);
            if (searchClear) searchClear.classList.toggle('hidden', (searchInput.value || '') === '');
        };
        if (searchInput) {
            searchInput.addEventListener('input', applySearch);
            if (searchClear) searchClear.addEventListener('click', () => {
                searchInput.value = '';
                applySearch();
                searchInput.focus();
            });
        }

        const openDrawer = () => {
            backdrop.classList.remove('hidden');
            drawer.classList.remove('hidden');
            requestAnimationFrame(() => drawer.classList.remove('translate-x-full'));
        };
        const closeDrawer = () => {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
            setTimeout(() => drawer.classList.add('hidden'), 300);
        };
        document.querySelectorAll('[data-close-drawer]').forEach(b => b.addEventListener('click', closeDrawer));
        backdrop.addEventListener('click', closeDrawer);

        const setFieldImg = (idx, url) => {
            const list = document.querySelectorAll('[data-image-field]');
            if (!list[idx]) return;
            const v = list[idx].querySelector('[data-image-value]');
            const p = list[idx].querySelector('[data-image-preview]');
            if (v) v.value = url || '';
            if (p) p.src = url || defaultThumb;
        };

        const openEdit = (id) => {
            const r = rooms.find(x => Number(x.id) === Number(id));
            if (!r) return;
            currentRoomId = Number(id);
            form.reset();
            $('drawer-room-id').value = id;
            $('drawer-position').value = r.position || 0;
            $('drawer-extend').value = 0;
            $('drawer-area-id').value = String(r.area_id || '');
            $('drawer-floor-id').value = String(r.floor_id || '');
            $('drawer-area-display').value = getAreaName(r.area_id);
            $('drawer-floor-display').value = getFloorName(r.floor_id);
            $('drawer-name').value = r.name || '';
            $('drawer-price').value = r.price || '';
            $('drawer-area').value = r.area || '';
            $('drawer-max').value = r.max_occupancy || 2;
            if (r.status === 'rented') {
                $('drawer-status').classList.add('hidden');
                $('drawer-status-readonly').classList.remove('hidden');
                $('drawer-status-hidden').value = 'rented';
                $('drawer-status-hidden').disabled = false;
                $('drawer-status').disabled = true;
            } else {
                $('drawer-status').classList.remove('hidden');
                $('drawer-status-readonly').classList.add('hidden');
                $('drawer-status-hidden').value = '';
                $('drawer-status-hidden').disabled = true;
                $('drawer-status').disabled = false;
                $('drawer-status').value = (['draft', 'available', 'maintenance'].includes(r.status)) ? r.status : 'draft';
            }
            $('drawer-description').value = r.description || '';
            $('drawer-amenities').value = r.amenities || '';
            const imgs = roomImages[id] || [];
            const primary = imgs.find(i => i.is_primary === 1) || imgs[0] || null;
            const subs = imgs.filter(i => i.is_primary === 0);
            setFieldImg(0, primary ? primary.image_url : '');
            for (let i = 0; i < 3; i++) setFieldImg(1 + i, subs[i] ? subs[i].image_url : '');
            $('drawer-title').textContent = 'Sửa phòng ' + (r.name || '#' + id);
            $('drawer-context').textContent = (r.area_name || '') + ' · ' + (r.floor_name || '');
            openDrawer();
        };

        const openAdd = () => {
            currentRoomId = 0;
            form.reset();
            $('drawer-room-id').value = 0;
            $('drawer-position').value = 0;
            $('drawer-extend').value = 1;
            const areaId = '<?= $currentFilters['area_id'] ?>';
            const floorId = '<?= $currentFilters['floor_id'] ?>';
            $('drawer-area-id').value = areaId;
            $('drawer-floor-id').value = floorId;
            $('drawer-area-display').value = getAreaName(areaId);
            $('drawer-floor-display').value = getFloorName(floorId);
            setFieldImg(0, '');
            for (let i = 0; i < 3; i++) setFieldImg(1 + i, '');
            $('drawer-status').value = 'draft';
            $('drawer-title').textContent = 'Thêm phòng mới';
            $('drawer-context').textContent = 'Phòng mới gắn vào: ' + getAreaName(areaId) + ' · ' + getFloorName(floorId);
            openDrawer();
        };

        document.querySelectorAll('[data-edit-room]').forEach(b => b.addEventListener('click', () => openEdit(b.getAttribute('data-edit-room'))));
        const addBtn = document.getElementById('btn-add-room');
        if (addBtn) addBtn.addEventListener('click', openAdd);

        document.querySelectorAll('[data-image-field]').forEach((field) => {
            const fileInput = field.querySelector('[data-image-file]');
            const hiddenInp = field.querySelector('[data-image-value]');
            const previewImg = field.querySelector('[data-image-preview]');
            if (!fileInput || !hiddenInp || !previewImg) return;
            fileInput.addEventListener('change', async () => {
                const f = fileInput.files && fileInput.files[0];
                if (!f) return;
                if (f.size > 5 * 1024 * 1024) {
                    alert('Ảnh vượt quá 5MB.');
                    fileInput.value = '';
                    return;
                }
                const fd = new FormData();
                fd.append('image', f);
                fd.append('_csrf_token', drawer.dataset.csrf || '');
                fd.append('slot', currentRoomId > 0 ? ('room_' + currentRoomId) : 'room_new');
                try {
                    const res = await fetch(drawer.dataset.uploadUrl, {
                        method: 'POST',
                        body: fd
                    });
                    const payload = await res.json().catch(() => ({}));
                    if (!res.ok || !payload.ok) {
                        alert(payload.message || 'Tải ảnh thất bại.');
                        return;
                    }
                    hiddenInp.value = payload.url;
                    previewImg.src = payload.url;
                } catch (e) {
                    alert('Tải ảnh thất bại: ' + e.message);
                }
                fileInput.value = '';
            });
        });

                // === PRICE CHANGE INTERCEPTOR ===
        let originalPrice = null;
        let isRented = false;
        
        // Override openEdit để track giá gốc và status
        const originalOpenEdit = openEdit;
        openEdit = (id) => {
            const r = rooms.find(x => Number(x.id) === Number(id));
            if (r) {
                originalPrice = parseFloat(r.price || 0);
                isRented = (r.status === 'rented');
            }
            originalOpenEdit(id);
        };
        
        form.addEventListener('submit', function(e) {
            if (!isRented) return; // Không intercept nếu không rented
            
            const newPrice = parseFloat($('drawer-price').value || 0);
            if (Math.abs(originalPrice - newPrice) < 0.01) return; // Giá không đổi
            
            // Giá thay đổi + phòng rented → show modal
            e.preventDefault();
            e.stopPropagation();
            window.showPriceChangeModal();
        }, true); // Use capture phase
        form.addEventListener('submit', e => {
            if (Number($('drawer-room-id').value || 0) > 0) return;
            const floor = floors.find(f => Number(f.id) === Number($('drawer-floor-id').value || 0));
            if (!floor) return;
            const limit = Number(floor.room_limit || 0);
            e.preventDefault();
            const ok = window.confirm('Giới hạn hiện tại của ' + (floor.name || 'tầng') + ' là ' + limit + ' phòng.\nThêm phòng mới sẽ tăng lên ' + (limit + 1) + '. Tiếp tục?');
            if (ok) {
                $('drawer-extend').value = 1;
                form.submit();
            }
        });

        <?php if (!empty($drawerOpenFlag) && $isEditing): ?>openEdit(<?= $formRoomId ?>);
    <?php endif; ?>
    })();
</script>
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
                <p class="text-xs text-gray-500 mt-1">Hệ thống tự đặt tên tầng. Nhập 0 nếu chỉ tạo tầng trống.</p>
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

<!-- Modal Chọn Tháng Áp Dụng Giá -->
<div id="price-change-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-bold mb-4">Phòng đang được thuê</h3>
        <p class="text-sm text-gray-600 mb-4">Giá mới sẽ không áp dụng ngay lập tức. Vui lòng chọn tháng áp dụng:</p>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-sm font-semibold mb-2">Tháng</label>
                <select id="modal-month" class="w-full rounded-xl border border-gray-200 px-3 py-2">
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Năm</label>
                <select id="modal-year" class="w-full rounded-xl border border-gray-200 px-3 py-2">
                </select>
            </div>
        </div>
        <p class="text-xs text-gray-500 mb-4">* Tối thiểu từ tháng sau</p>
        <div class="flex gap-3">
            <button type="button" id="modal-cancel" class="flex-1 rounded-xl border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">Hủy</button>
            <button type="button" id="modal-confirm" class="flex-1 rounded-xl bg-primary px-4 py-2 font-semibold text-white hover:bg-opacity-90">Xác nhận</button>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('price-change-modal');
    const monthSel = document.getElementById('modal-month');
    const yearSel = document.getElementById('modal-year');
    const cancelBtn = document.getElementById('modal-cancel');
    const confirmBtn = document.getElementById('modal-confirm');
    
    // Populate month/year options
    const now = new Date();
    let startMonth = now.getMonth() + 2; // Tháng sau
    let startYear = now.getFullYear();
    if (startMonth > 12) { startMonth = 1; startYear++; }
    
    for (let y = startYear; y <= startYear + 2; y++) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSel.appendChild(opt);
    }
    
    for (let m = 1; m <= 12; m++) {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = 'Tháng ' + m;
        monthSel.appendChild(opt);
    }
    
    monthSel.value = startMonth;
    yearSel.value = startYear;
    
    // Update month options based on selected year
    yearSel.addEventListener('change', () => {
        const selYear = parseInt(yearSel.value);
        const minMonth = (selYear === startYear) ? startMonth : 1;
        for (let i = 0; i < monthSel.options.length; i++) {
            const m = parseInt(monthSel.options[i].value);
            monthSel.options[i].disabled = (selYear === startYear && m < minMonth);
        }
        // Select first valid month
        for (let i = 0; i < monthSel.options.length; i++) {
            if (!monthSel.options[i].disabled) {
                monthSel.selectedIndex = i;
                break;
            }
        }
    });
    yearSel.dispatchEvent(new Event('change'));
    
    window.showPriceChangeModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };
    
    window.hidePriceChangeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
    
    cancelBtn.addEventListener('click', window.hidePriceChangeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) window.hidePriceChangeModal();
    });
    
    confirmBtn.addEventListener('click', () => {
        document.getElementById('drawer-price-month').value = monthSel.value;
        document.getElementById('drawer-price-year').value = yearSel.value;
        window.hidePriceChangeModal();
        document.getElementById('room-drawer-form').submit();
    });
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>