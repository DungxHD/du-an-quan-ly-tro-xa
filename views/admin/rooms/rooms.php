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
$formStatusOptions = ['draft' => $statusMap['draft'], 'available' => $statusMap['available'], 'maintenance' => $statusMap['maintenance']];

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

$hasFloor = $currentFilters['floor_id'] > 0;
$defaultThumb = 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<style>
    #room-drawer::-webkit-scrollbar {
        width: 8px;
    }

    #room-drawer::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
</style>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h2 class="text-3xl font-bold">Quản lý Phòng trọ</h2>
            <p class="mt-2 text-gray-600">Chọn khu / tầng để xem danh sách. Bấm "Sửa" trên thẻ phòng để mở form trượt từ bên phải.</p>
        </div>
        <a href="<?= $filterResetUrl ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-700 transition hover:border-primary hover:text-primary">
            <span class="material-symbols-outlined text-base">filter_alt_off</span> Xóa toàn bộ bộ lọc
        </a>
    </div>

    <?php if (!empty($roomMessage)): ?><div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($roomMessage) ?></div><?php endif; ?>
    <?php if (!empty($roomError)): ?><div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($roomError) ?></div><?php endif; ?>

    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <input type="hidden" name="page" value="admin-rooms">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Khu</label>
                <select name="area_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option value="0">Tất cả khu</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int)($area['id'] ?? 0) ?>" <?= $currentFilters['area_id'] === (int)($area['id'] ?? 0) ? 'selected' : '' ?>><?= e($area['name'] ?? 'Khu') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Tầng</label>
                <select name="floor_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
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
                <?php else: ?>
                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một tầng để hiện nút "Thêm phòng".</p>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="rounded-3xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-5">
            <h3 class="text-lg font-bold">Danh sách phòng (<?= count($rooms) ?>)</h3>
        </div>
        <?php if (empty($rooms)): ?>
            <div class="px-6 py-12 text-center text-gray-500">Không có phòng nào khớp bộ lọc.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <?php foreach ($rooms as $room): ?>
                    <?php
                    $roomId = (int)($room['id'] ?? 0);
                    $meta = $statusMap[$room['status'] ?? 'draft'] ?? $statusMap['draft'];
                    $isRented = ($room['status'] ?? '') === 'rented' || (int)($room['occupant_count'] ?? 0) > 0;
                    $deleteParams = http_build_query(array_filter([
                        'page' => 'admin-delete-room',
                        'id' => $roomId,
                        'area_id' => $currentFilters['area_id'],
                        'floor_id' => $currentFilters['floor_id'],
                        'status' => $currentFilters['status'],
                    ], static fn($v) => $v !== '' && $v !== null));
                    ?>
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
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
                                    <span class="inline-flex flex-1 cursor-not-allowed items-center justify-center gap-1 rounded-xl bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-400" title="Phòng đang thuê — không thể xóa">
                                        <span class="material-symbols-outlined text-base">lock</span> Khóa xóa
                                    </span>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>?<?= $deleteParams ?>" data-confirm="Bạn có chắc chắn muốn xóa phòng <?= e($room['name'] ?? '') ?>?" class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                        <span class="material-symbols-outlined text-base">delete</span> Xóa
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Drawer: trượt từ phải, sát mép phải, ẩn mặc định, KHÔNG có nút bật/tắt -->
<div id="room-drawer-backdrop" class="hidden fixed inset-0 z-40 bg-black/40"></div>
<aside id="room-drawer" data-csrf="<?= e(csrf_token()) ?>" data-upload-url="<?= BASE_URL ?>?page=admin-upload-image"
    class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-xl translate-x-full bg-white shadow-2xl transition-transform duration-300">
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
            <div>
                <h3 id="drawer-title" class="text-lg font-bold">Thêm phòng mới</h3>
                <p id="drawer-context" class="mt-0.5 text-xs text-gray-500"></p>
            </div>
            <button type="button" data-close-drawer class="rounded-xl border border-gray-200 p-2 text-gray-600 transition hover:bg-gray-50" aria-label="Đóng form">
                <span class="material-symbols-outlined">close</span>
            </button>
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
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Khu *</label>
                    <select id="drawer-area-id" name="area_id" required data-area-select data-target-floor="#drawer-floor-id" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)($area['id'] ?? 0) ?>"><?= e($area['name'] ?? 'Khu') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Tầng *</label>
                    <select id="drawer-floor-id" name="floor_id" required class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="">Chọn tầng</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Tên phòng (để trống sẽ tự sinh)</label>
                <input type="text" name="name" id="drawer-name" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Giá thuê (VNĐ) *</label>
                    <input type="number" name="price" id="drawer-price" min="0" step="1000" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Diện tích (m2)</label>
                    <input type="number" name="area" id="drawer-area" min="0" step="0.1" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Sức chứa tối đa *</label>
                    <input type="number" name="max_occupancy" id="drawer-max" min="1" value="2" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Trạng thái *</label>
                    <select name="status" id="drawer-status" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <?php foreach ($formStatusOptions as $key => $meta): ?>
                            <option value="<?= $key ?>"><?= e($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Mô tả *</label>
                <textarea name="description" id="drawer-description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Tiện nghi (phân cách bởi dấu phẩy)</label>
                <input type="text" name="amenities" id="drawer-amenities" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>

            <!-- Ảnh: 1 chính + 3 phụ, upload bằng FILE -->
            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 space-y-3">
                <p class="text-sm font-bold text-gray-800">Ảnh phòng (1 ảnh chính + 3 ảnh phụ) — upload bằng file</p>
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-gray-700">Ảnh chính</label>
                    <img id="preview-primary" src="<?= $defaultThumb ?>" class="h-32 w-full rounded-xl border border-gray-200 object-cover">
                    <input type="hidden" name="primary_image" id="input-primary" value="">
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-file data-target="input-primary" data-preview="preview-primary" class="w-full text-xs">
                </div>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-700">Ảnh phụ <?= $i + 1 ?></label>
                        <img id="preview-sub-<?= $i ?>" src="<?= $defaultThumb ?>" class="h-24 w-full rounded-xl border border-gray-200 object-cover">
                        <input type="hidden" name="gallery_images[<?= $i ?>]" id="input-sub-<?= $i ?>" value="">
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-image-file data-target="input-sub-<?= $i ?>" data-preview="preview-sub-<?= $i ?>" class="w-full text-xs">
                    </div>
                <?php endfor; ?>
            </div>

            <button type="submit" class="w-full rounded-xl bg-primary px-4 py-3 font-semibold text-white transition hover:bg-opacity-90">
                <span class="material-symbols-outlined text-base align-middle">save</span> Lưu phòng
            </button>
        </form>
    </div>
</aside>

<script>
    (() => {
        const floors = <?= $floorsJson ?: '[]' ?>;
        const rooms = <?= $roomsJson ?: '[]' ?>;
        const roomImages = <?= $imagesJson ?: '{}' ?>;
        const drawer = document.getElementById('room-drawer');
        const backdrop = document.getElementById('room-drawer-backdrop');
        const form = document.getElementById('room-drawer-form');
        const $ = (id) => document.getElementById(id);
        let currentRoomId = 0;

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
        document.querySelectorAll('[data-close-drawer]').forEach((b) => b.addEventListener('click', closeDrawer));
        backdrop.addEventListener('click', closeDrawer);

        const renderFloors = (areaId, selected) => {
            const sel = $('drawer-floor-id');
            sel.innerHTML = '<option value="">Chọn tầng</option>';
            floors.filter((f) => Number(f.area_id || 0) === Number(areaId)).forEach((f) => {
                const o = document.createElement('option');
                o.value = String(f.id);
                o.textContent = f.name || ('Tầng ' + (f.floor_number || ''));
                if (String(f.id) === String(selected)) o.selected = true;
                sel.appendChild(o);
            });
        };
        $('drawer-area-id').addEventListener('change', (e) => renderFloors(e.target.value, ''));

        const setImg = (inputId, previewId, url) => {
            $(inputId).value = url || '';
            $(previewId).src = url || '<?= $defaultThumb ?>';
        };

        const openEdit = (id) => {
            const r = rooms.find((x) => Number(x.id) === Number(id));
            if (!r) return;
            currentRoomId = Number(id);
            form.reset();
            $('drawer-room-id').value = id;
            $('drawer-position').value = r.position || 0;
            $('drawer-extend').value = 0;
            $('drawer-area-id').value = String(r.area_id || '');
            renderFloors(r.area_id, r.floor_id);
            $('drawer-name').value = r.name || '';
            $('drawer-price').value = r.price || '';
            $('drawer-area').value = r.area || '';
            $('drawer-max').value = r.max_occupancy || 2;
            $('drawer-status').value = (['draft', 'available', 'maintenance'].includes(r.status)) ? r.status : 'draft';
            $('drawer-description').value = r.description || '';
            $('drawer-amenities').value = r.amenities || '';
            const imgs = roomImages[id] || [];
            const primary = imgs.find((i) => i.is_primary === 1) || imgs[0] || null;
            const subs = imgs.filter((i) => i.is_primary === 0);
            setImg('input-primary', 'preview-primary', primary ? primary.image_url : '');
            for (let i = 0; i < 3; i++) setImg('input-sub-' + i, 'preview-sub-' + i, subs[i] ? subs[i].image_url : '');
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
            const areaSel = $('drawer-area-id');
            areaSel.value = '<?= $currentFilters['area_id'] ?>';
            renderFloors(areaSel.value, '<?= $currentFilters['floor_id'] ?>');
            for (let i = 0; i < 3; i++) setImg('input-sub-' + i, 'preview-sub-' + i, '');
            setImg('input-primary', 'preview-primary', '');
            $('drawer-status').value = 'draft';
            $('drawer-title').textContent = 'Thêm phòng mới';
            $('drawer-context').textContent = 'Phòng mới sẽ gắn vào tầng đã chọn.';
            openDrawer();
        };

        document.querySelectorAll('[data-edit-room]').forEach((b) => b.addEventListener('click', () => openEdit(b.getAttribute('data-edit-room'))));
        const addBtn = document.getElementById('btn-add-room');
        if (addBtn) addBtn.addEventListener('click', openAdd);

        // Upload ảnh bằng FILE qua endpoint admin-upload-image
        document.querySelectorAll('[data-image-file]').forEach((inp) => {
            inp.addEventListener('change', async () => {
                const f = inp.files && inp.files[0];
                if (!f) return;
                if (f.size > 5 * 1024 * 1024) {
                    alert('Ảnh vượt quá 5MB.');
                    inp.value = '';
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
                    $(inp.dataset.target).value = payload.url;
                    $(inp.dataset.preview).src = payload.url;
                } catch (e) {
                    alert('Tải ảnh thất bại.');
                }
                inp.value = '';
            });
        });

        // Thêm phòng: xác nhận tăng giới hạn tầng trước khi submit
        form.addEventListener('submit', (e) => {
            if (Number($('drawer-room-id').value || 0) > 0) return; // sửa thì submit thường
            const floorId = Number($('drawer-floor-id').value || 0);
            const floor = floors.find((f) => Number(f.id) === floorId);
            if (!floor) return; // để required của select chặn
            const limit = Number(floor.room_limit || 0);
            e.preventDefault();
            const ok = window.confirm('Giới hạn hiện tại của ' + (floor.name || 'tầng') + ' là ' + limit + ' phòng. Bạn có chắc muốn thêm phòng mới? Giới hạn sẽ tăng từ ' + limit + ' lên ' + (limit + 1) + '.');
            if (ok) {
                $('drawer-extend').value = 1;
                form.submit();
            }
        });
    })();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>