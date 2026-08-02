<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'floors';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý tầng theo khu và sắp xếp bằng floor_number';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';

$selectedAreaId = (int)($selectedAreaId ?? 0);
$hasGroundFloor = false;
foreach ($floors as $floorRow) {
    if ((int)($floorRow['floor_number'] ?? 0) === 0) {
        $hasGroundFloor = true;
        break;
    }
}
?>
        <div class="space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold">Quản lý Tầng</h2>
                    <p class="text-gray-500 mt-2">Chọn khu để xem danh sách tầng, thứ tự sắp xếp và số phòng theo từng tầng.</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Theo schema hiện tại, xóa tầng sẽ kéo theo xóa các phòng thuộc tầng đó.
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
                    <input type="hidden" name="page" value="admin-floors">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2">Lọc theo khu</label>
                        <select name="area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($area['id'] ?? 0) === $selectedAreaId ? 'selected' : '' ?>>
                                <?= e($area['name'] ?? '') ?> (<?= (int)($area['floor_count'] ?? 0) ?> tầng)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
                        Xem tầng
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                        <div>
                            <h3 class="text-lg font-bold"><?= $editFloor ? 'Sửa tầng' : 'Thêm tầng mới' ?></h3>
                            <p class="text-sm text-gray-500 mt-1">Khu nhà trệt nên tạo một tầng với số tầng bằng `0` và tên `Tầng trệt`.</p>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>?page=admin-save-floor" data-validate class="space-y-4">
                            <?php if ($editFloor): ?>
                            <input type="hidden" name="id" value="<?= (int)($editFloor['id'] ?? 0) ?>">
                            <?php endif; ?>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Khu *</label>
                                <select name="area_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                    <?php foreach ($areas as $area): ?>
                                    <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($editFloor['area_id'] ?? $selectedAreaId) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= e($area['name'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Tên tầng</label>
                                <input
                                    type="text"
                                    name="name"
                                    value="<?= e($editFloor['name'] ?? '') ?>"
                                    placeholder="Ví dụ: Tầng trệt, Tầng 1"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Số tầng *</label>
                                <input
                                    type="number"
                                    name="floor_number"
                                    required
                                    value="<?= e($editFloor['floor_number'] ?? 1) ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                >
                            </div>

                            <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                                <?= $editFloor ? 'Cập nhật tầng' : 'Thêm tầng' ?>
                            </button>

                            <?php if ($editFloor): ?>
                            <a href="<?= BASE_URL ?>?page=admin-floors&area_id=<?= (int)($selectedAreaId ?: ($editFloor['area_id'] ?? 0)) ?>" class="block w-full py-3 text-center text-gray-600 hover:text-primary">
                                Hủy chỉnh sửa
                            </a>
                            <?php endif; ?>
                        </form>

                        <?php if ($selectedArea && !$hasGroundFloor): ?>
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-700">
                            Khu `<?= e($selectedArea['name'] ?? '') ?>` hiện chưa có `Tầng trệt`. Nếu đây là khu nhà trệt, nên thêm một tầng với `floor_number = 0`.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-bold"><?= e($selectedArea['name'] ?? 'Chưa chọn khu') ?></h3>
                                <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($selectedArea['address'] ?? '', 'Chưa có địa chỉ khu')) ?></p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-2 rounded-xl bg-sky-100 text-sky-700 text-sm font-semibold">
                                    <?= count($floors) ?> tầng
                                </span>
                                <span class="px-3 py-2 rounded-xl bg-violet-100 text-violet-700 text-sm font-semibold">
                                    <?= (int)array_sum(array_map(static fn($floor) => (int)($floor['room_count'] ?? 0), $floors)) ?> phòng
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-bold text-lg">Danh sách tầng</h3>
                        </div>

                        <?php if (empty($floors)): ?>
                        <div class="px-6 py-10 text-center text-gray-500">
                            Khu này chưa có tầng nào. Hãy thêm tầng đầu tiên ở form bên trái.
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tầng</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sắp xếp</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($floors as $floor): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <p class="font-semibold text-gray-900"><?= e($floor['name'] ?? '') ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?= e($floor['area_name'] ?? '') ?></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-sm font-semibold">
                                                floor_number = <?= (int)($floor['floor_number'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="<?= BASE_URL ?>?page=admin-rooms&floor_id=<?= (int)($floor['id'] ?? 0) ?>" class="inline-flex items-center gap-2 text-primary font-semibold hover:underline">
                                                <?= (int)($floor['room_count'] ?? 0) ?> phòng
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <span class="px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-semibold">
                                                    Trống <?= (int)($floor['available_count'] ?? 0) ?>
                                                </span>
                                                <span class="px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                                                    Đã thuê <?= (int)($floor['rented_count'] ?? 0) ?>
                                                </span>
                                                <span class="px-3 py-1.5 rounded-full bg-orange-50 text-orange-700 text-xs font-semibold">
                                                    Bảo trì <?= (int)($floor['maintenance_count'] ?? 0) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-3">
                                                <a href="<?= BASE_URL ?>?page=admin-floors&area_id=<?= (int)($selectedAreaId ?: ($floor['area_id'] ?? 0)) ?>&edit=<?= (int)($floor['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                                    Sửa
                                                </a>
                                                <a
                                                    href="<?= BASE_URL ?>?page=admin-delete-floor&id=<?= (int)($floor['id'] ?? 0) ?>"
                                                    data-confirm="Theo schema hiện tại, xóa tầng sẽ xóa luôn các phòng thuộc tầng này. Bạn vẫn muốn xóa?"
                                                    class="text-red-600 hover:text-red-800 font-semibold text-sm"
                                                >
                                                    Xóa
                                                </a>
                                            </div>
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
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
