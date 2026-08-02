<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'rooms';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý danh sách phòng theo khu và tầng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];

/**
 * Map trạng thái dùng chung cho badge, dropdown và confirm message.
 */
$statusMap = [
    'available' => ['label' => 'Còn trống', 'badge' => 'bg-green-100 text-green-700 border-green-200'],
    'rented' => ['label' => 'Đã thuê', 'badge' => 'bg-rose-100 text-rose-700 border-rose-200'],
    'maintenance' => ['label' => 'Bảo trì', 'badge' => 'bg-amber-100 text-amber-700 border-amber-200'],
];

/**
 * Gom bộ lọc hiện tại để tái sử dụng ở link sửa/xóa và form đổi trạng thái nhanh.
 */
$currentFilters = [
    'area_id' => (int)($filters['area_id'] ?? 0),
    'floor_id' => (int)($filters['floor_id'] ?? 0),
    'status' => (string)($filters['status'] ?? ''),
];
$filterResetUrl = BASE_URL . '?page=admin-rooms';
$floorsJson = json_encode($allFloors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$formRoom = $formRoom ?? null;
$isEditing = !empty($formRoom['id']);
$formThumbnail = trim((string)($formRoom['thumbnail'] ?? ''));
$previewThumbnail = $formThumbnail !== '' ? $formThumbnail : 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900';

require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-3xl font-bold">Quản lý Phòng trọ</h2>
                    <p class="mt-2 text-gray-600">Thêm, sửa, lọc, đổi trạng thái và kiểm soát xóa phòng theo đúng cấu trúc Khu - Tầng.</p>
                </div>
                <a href="<?= $filterResetUrl ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-700 transition hover:border-primary hover:text-primary">
                    <span class="material-symbols-outlined text-base">filter_alt_off</span>
                    Xóa toàn bộ bộ lọc
                </a>
            </div>

            <?php if (!empty($roomMessage)): ?>
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                <?= e($roomMessage) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($roomError)): ?>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <?= e($roomError) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($selectedFloor)): ?>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
                <p class="font-semibold text-violet-800">Đang lọc theo <?= e($selectedFloor['area_name'] ?? 'Khu') ?> / <?= e($selectedFloor['name'] ?? 'Tầng') ?></p>
                <p class="mt-1 text-sm text-violet-700">Số tầng: <?= (int)($selectedFloor['floor_number'] ?? 0) ?>. Dữ liệu bảng và hành động nhanh bên dưới đang bám theo bộ lọc này.</p>
            </div>
            <?php endif; ?>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">filter_list</span>
                    <h3 class="text-lg font-bold">Bộ lọc danh sách phòng</h3>
                </div>
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4" data-room-admin-filter>
                    <input type="hidden" name="page" value="admin-rooms">
                    <div>
                        <label for="filter-area-id" class="mb-1 block text-sm font-semibold text-gray-700">Khu</label>
                        <select id="filter-area-id" name="area_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" data-area-select data-target-floor="#filter-floor-id">
                            <option value="0">Tất cả khu</option>
                            <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)($area['id'] ?? 0) ?>" <?= $currentFilters['area_id'] === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($area['name'] ?? 'Khu') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="filter-floor-id" class="mb-1 block text-sm font-semibold text-gray-700">Tầng</label>
                        <select id="filter-floor-id" name="floor_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" data-floor-select data-placeholder="Tất cả tầng" data-selected-value="<?= (int)$currentFilters['floor_id'] ?>">
                            <option value="0">Tất cả tầng</option>
                            <?php foreach ($filterFloors as $floor): ?>
                            <option value="<?= (int)($floor['id'] ?? 0) ?>" <?= $currentFilters['floor_id'] === (int)($floor['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($floor['name'] ?? 'Tầng') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="filter-status" class="mb-1 block text-sm font-semibold text-gray-700">Trạng thái</label>
                        <select id="filter-status" name="status" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statusMap as $statusKey => $statusMeta): ?>
                            <option value="<?= $statusKey ?>" <?= $currentFilters['status'] === $statusKey ? 'selected' : '' ?>>
                                <?= e($statusMeta['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                            <span class="material-symbols-outlined text-base">search</span>
                            Lọc danh sách
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="xl:col-span-1">
                    <div class="sticky top-20 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold"><?= $isEditing ? 'Sửa phòng' : 'Thêm phòng mới' ?></h3>
                                <p class="mt-1 text-sm text-gray-500">Phòng chỉ lưu `floor_id`, nên khu chỉ dùng để dẫn xuất danh sách tầng đúng ngữ cảnh.</p>
                            </div>
                            <?php if ($isEditing): ?>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Đang sửa</span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>?page=admin-save-room" data-validate class="space-y-4" data-room-admin-form>
                            <?php if ($isEditing): ?>
                            <input type="hidden" name="id" value="<?= (int)($formRoom['id'] ?? 0) ?>">
                            <?php endif; ?>
                            <input type="hidden" name="return_area_id" value="<?= $currentFilters['area_id'] ?>">
                            <input type="hidden" name="return_floor_id" value="<?= $currentFilters['floor_id'] ?>">
                            <input type="hidden" name="return_status" value="<?= e($currentFilters['status']) ?>">

                            <div>
                                <label for="room-area-id" class="mb-1 block text-sm font-semibold text-gray-700">Khu *</label>
                                <select id="room-area-id" name="area_id" required class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" data-area-select data-target-floor="#room-floor-id">
                                    <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int)($area['id'] ?? 0) ?>" <?= $formAreaId === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= e($area['name'] ?? 'Khu') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="room-floor-id" class="mb-1 block text-sm font-semibold text-gray-700">Tầng *</label>
                                <select id="room-floor-id" name="floor_id" required class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" data-floor-select data-placeholder="Chọn tầng" data-selected-value="<?= (int)($formRoom['floor_id'] ?? 0) ?>">
                                    <option value="">Chọn tầng</option>
                                    <?php foreach ($formFloors as $floor): ?>
                                    <option value="<?= (int)($floor['id'] ?? 0) ?>" <?= (int)($formRoom['floor_id'] ?? 0) === (int)($floor['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= e($floor['name'] ?? 'Tầng') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Khi đổi khu, dropdown tầng sẽ tự nạp lại theo khu đã chọn.</p>
                            </div>

                            <div>
                                <label for="room-name" class="mb-1 block text-sm font-semibold text-gray-700">Tên phòng *</label>
                                <input id="room-name" type="text" name="name" required value="<?= e($formRoom['name'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Ví dụ: Phòng A101">
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="room-price" class="mb-1 block text-sm font-semibold text-gray-700">Giá thuê (VNĐ) *</label>
                                    <input id="room-price" type="number" name="price" min="1" step="1000" required value="<?= e($formRoom['price'] ?? 3000000) ?>" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    <p class="mt-1 text-xs text-gray-500">Validation bắt buộc lớn hơn 0 ở cả client và PHP.</p>
                                </div>
                                <div>
                                    <label for="room-area" class="mb-1 block text-sm font-semibold text-gray-700">Diện tích (m2)</label>
                                    <input id="room-area" type="number" name="area" min="0" step="0.1" value="<?= e($formRoom['area'] ?? 20) ?>" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="room-max-occupancy" class="mb-1 block text-sm font-semibold text-gray-700">Sức chứa tối đa *</label>
                                    <input id="room-max-occupancy" type="number" name="max_occupancy" min="1" step="1" required value="<?= e($formRoom['max_occupancy'] ?? 2) ?>" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                </div>
                                <div>
                                    <label for="room-status" class="mb-1 block text-sm font-semibold text-gray-700">Trạng thái *</label>
                                    <select id="room-status" name="status" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        <?php foreach ($statusMap as $statusKey => $statusMeta): ?>
                                        <option value="<?= $statusKey ?>" <?= ($formRoom['status'] ?? 'available') === $statusKey ? 'selected' : '' ?>>
                                            <?= e($statusMeta['label']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="room-thumbnail" class="mb-1 block text-sm font-semibold text-gray-700">URL ảnh thumbnail</label>
                                <input id="room-thumbnail" type="url" name="thumbnail" value="<?= e($formThumbnail) ?>" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="https://...">
                            </div>

                            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-3">
                                <p class="mb-2 text-sm font-semibold text-gray-700">Preview ảnh phòng</p>
                                <img src="<?= e($previewThumbnail) ?>" alt="Preview thumbnail" class="h-48 w-full rounded-2xl object-cover" data-room-thumbnail-preview>
                            </div>

                            <div>
                                <label for="room-description" class="mb-1 block text-sm font-semibold text-gray-700">Mô tả</label>
                                <textarea id="room-description" name="description" rows="4" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Điểm nổi bật của phòng, tiện ích, lưu ý cho người thuê..."><?= e($formRoom['description'] ?? '') ?></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 font-semibold text-white transition hover:bg-opacity-90">
                                    <span class="material-symbols-outlined text-base"><?= $isEditing ? 'save' : 'add_home' ?></span>
                                    <?= $isEditing ? 'Cập nhật phòng' : 'Thêm phòng' ?>
                                </button>
                                <?php if ($isEditing): ?>
                                <a href="<?= $filterResetUrl ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-3 font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                                    Hủy
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <div class="rounded-3xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-bold">Danh sách phòng (<?= count($rooms) ?>)</h3>
                                <p class="mt-1 text-sm text-gray-500">Bảng hiển thị ảnh thumbnail, khu, tầng, giá, số người đang ở và thao tác nhanh đổi trạng thái.</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-2 text-sm text-gray-600">
                                Tổng khu: <strong><?= count($areas) ?></strong> · Tổng tầng: <strong><?= count($allFloors) ?></strong>
                            </div>
                        </div>

                        <?php if (empty($rooms)): ?>
                        <div class="px-6 py-12 text-center">
                            <span class="material-symbols-outlined text-5xl text-gray-300">meeting_room</span>
                            <p class="mt-4 text-lg font-semibold text-gray-700">Không có phòng nào khớp bộ lọc hiện tại</p>
                            <p class="mt-2 text-sm text-gray-500">Hãy đổi khu, tầng hoặc trạng thái để xem thêm dữ liệu.</p>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Phòng</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Khu / Tầng</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Giá</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Trạng thái</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($rooms as $room): ?>
                                    <?php
                                    $roomStatus = $statusMap[$room['status'] ?? 'available'] ?? $statusMap['available'];
                                    $deleteParams = http_build_query(array_filter([
                                        'page' => 'admin-delete-room',
                                        'id' => (int)($room['id'] ?? 0),
                                        'area_id' => $currentFilters['area_id'],
                                        'floor_id' => $currentFilters['floor_id'],
                                        'status' => $currentFilters['status'],
                                    ], static fn($value) => $value !== '' && $value !== null));
                                    $editParams = http_build_query(array_filter([
                                        'page' => 'admin-rooms',
                                        'edit' => (int)($room['id'] ?? 0),
                                        'area_id' => $currentFilters['area_id'],
                                        'floor_id' => $currentFilters['floor_id'],
                                        'status' => $currentFilters['status'],
                                    ], static fn($value) => $value !== '' && $value !== null));
                                    $deleteMessage = 'Bạn có chắc chắn muốn xóa phòng này?';
                                    if ((int)($room['occupant_count'] ?? 0) > 0) {
                                        $deleteMessage = 'Phòng đang có người ở! Hệ thống sẽ chặn xóa. Bạn vẫn muốn tiếp tục thao tác này?';
                                    } elseif (($room['status'] ?? '') === 'rented') {
                                        $deleteMessage = 'Phòng này đang ở trạng thái đã thuê. Hãy kiểm tra kỹ trước khi xóa. Bạn có chắc chắn muốn tiếp tục?';
                                    }
                                    ?>
                                    <tr class="align-top transition hover:bg-gray-50">
                                        <td class="px-4 py-4">
                                            <div class="flex min-w-[260px] items-start gap-3">
                                                <img src="<?= e($room['thumbnail'] ?? '') ?>" alt="<?= e($room['name'] ?? 'Phòng') ?>" class="h-16 w-16 rounded-2xl object-cover">
                                                <div>
                                                    <p class="font-semibold text-gray-900"><?= e($room['name'] ?? 'Phòng') ?></p>
                                                    <p class="mt-1 text-sm text-gray-500"><?= number_format((float)($room['area'] ?? 0), 1) ?> m2 · Tối đa <?= (int)($room['max_occupancy'] ?? 0) ?> người</p>
                                                    <p class="mt-1 text-xs text-gray-500 line-clamp-2"><?= e($room['description'] ?? 'Chưa có mô tả.') ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-gray-800"><?= e($room['area_name'] ?? 'Chưa có khu') ?></p>
                                            <p class="mt-1 text-sm text-gray-500"><?= e($room['floor_name'] ?? 'Chưa có tầng') ?></p>
                                            <p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                Đang ở: <?= (int)($room['occupant_count'] ?? 0) ?> người
                                            </p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-primary"><?= number_format((float)($room['price'] ?? 0), 0, ',', '.') ?>đ</p>
                                            <p class="mt-1 text-xs text-gray-500">ID phòng: #<?= (int)($room['id'] ?? 0) ?></p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold <?= $roomStatus['badge'] ?>">
                                                <?= e($roomStatus['label']) ?>
                                            </span>
                                            <form method="POST" action="<?= BASE_URL ?>?page=admin-save-room" class="mt-3">
                                                <input type="hidden" name="id" value="<?= (int)($room['id'] ?? 0) ?>">
                                                <input type="hidden" name="quick_status_update" value="1">
                                                <input type="hidden" name="area_id" value="<?= $currentFilters['area_id'] ?>">
                                                <input type="hidden" name="floor_id" value="<?= $currentFilters['floor_id'] ?>">
                                                <input type="hidden" name="return_area_id" value="<?= $currentFilters['area_id'] ?>">
                                                <input type="hidden" name="return_floor_id" value="<?= $currentFilters['floor_id'] ?>">
                                                <input type="hidden" name="return_status" value="<?= e($currentFilters['status']) ?>">
                                                <select name="status" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" onchange="this.form.submit()">
                                                    <?php foreach ($statusMap as $statusKey => $statusMeta): ?>
                                                    <option value="<?= $statusKey ?>" <?= ($room['status'] ?? '') === $statusKey ? 'selected' : '' ?>>
                                                        <?= e($statusMeta['label']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                <a href="<?= BASE_URL ?>?<?= $editParams ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                                    <span class="material-symbols-outlined text-base">edit</span>
                                                    Sửa
                                                </a>
                                                <a href="<?= BASE_URL ?>?<?= $deleteParams ?>" data-confirm="<?= e($deleteMessage) ?>" class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                                    <span class="material-symbols-outlined text-base">delete</span>
                                                    Xóa
                                                </a>
                                            </div>
                                            <?php if ((int)($room['occupant_count'] ?? 0) > 0): ?>
                                            <p class="mt-2 text-xs font-semibold text-rose-600">Không thể xóa khi vẫn còn người đang được gán vào phòng.</p>
                                            <?php elseif (($room['status'] ?? '') === 'rented'): ?>
                                            <p class="mt-2 text-xs font-semibold text-amber-600">Phòng đang ở trạng thái đã thuê, cần kiểm tra lại trước khi xóa.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (() => {
            /**
             * Đồng bộ dropdown tầng theo khu để không cho admin chọn sai cặp Khu - Tầng.
             */
            const floors = <?= $floorsJson ?: '[]' ?>;

            function renderFloorOptions(floorSelect, areaId, placeholder) {
                if (!floorSelect) {
                    return;
                }

                const selectedValue = floorSelect.dataset.selectedValue || floorSelect.value || '';
                const normalizedAreaId = Number(areaId || 0);
                const shouldShowAll = normalizedAreaId === 0;
                const matchedFloors = floors.filter((floor) => shouldShowAll || Number(floor.area_id || 0) === normalizedAreaId);

                floorSelect.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = shouldShowAll ? '0' : '';
                placeholderOption.textContent = placeholder || (shouldShowAll ? 'Tất cả tầng' : 'Chọn tầng');
                floorSelect.appendChild(placeholderOption);

                matchedFloors.forEach((floor) => {
                    const option = document.createElement('option');
                    option.value = String(floor.id || '');
                    option.textContent = floor.name || `Tầng ${floor.floor_number || ''}`;
                    if (String(floor.id || '') === String(selectedValue)) {
                        option.selected = true;
                    }
                    floorSelect.appendChild(option);
                });

                const hasMatchedSelection = matchedFloors.some((floor) => String(floor.id || '') === String(selectedValue));
                if (!hasMatchedSelection) {
                    floorSelect.value = shouldShowAll ? '0' : '';
                }
            }

            /**
             * Gắn hành vi cho từng cặp select khu/tầng để dùng chung cho form lọc và form lưu.
             */
            document.querySelectorAll('[data-area-select]').forEach((areaSelect) => {
                const targetSelector = areaSelect.dataset.targetFloor;
                const floorSelect = targetSelector ? document.querySelector(targetSelector) : null;
                if (!floorSelect) {
                    return;
                }

                const placeholder = floorSelect.dataset.placeholder || 'Chọn tầng';
                const syncFloorSelect = () => renderFloorOptions(floorSelect, areaSelect.value, placeholder);

                syncFloorSelect();
                areaSelect.addEventListener('change', () => {
                    floorSelect.dataset.selectedValue = '';
                    syncFloorSelect();
                });
            });

            /**
             * Preview ảnh để admin thấy ngay thumbnail hợp lệ hay đã nhập sai URL.
             */
            const thumbnailInput = document.getElementById('room-thumbnail');
            const thumbnailPreview = document.querySelector('[data-room-thumbnail-preview]');
            if (thumbnailInput && thumbnailPreview) {
                const fallbackImage = thumbnailPreview.getAttribute('src');

                const updatePreview = () => {
                    const imageUrl = thumbnailInput.value.trim();
                    thumbnailPreview.src = imageUrl !== '' ? imageUrl : fallbackImage;
                };

                thumbnailInput.addEventListener('input', updatePreview);
                thumbnailPreview.addEventListener('error', () => {
                    thumbnailPreview.src = fallbackImage;
                });
            }
        })();
        </script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
