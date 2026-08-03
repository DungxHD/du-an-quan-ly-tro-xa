<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'amenities';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý tiện ích hiển thị ngoài landing page';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$isEditing = !empty($formAmenity['id']);
$panelPageScripts = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const iconSelect = document.getElementById('amenity-icon-select');
    const iconPreview = document.getElementById('amenity-icon-preview');
    const iconName = document.getElementById('amenity-icon-name');

    const refreshIconPreview = () => {
        if (!iconSelect || !iconPreview || !iconName) {
            return;
        }

        const selectedOption = iconSelect.options[iconSelect.selectedIndex];
        const iconKey = iconSelect.value || 'apartment';
        iconPreview.textContent = iconKey;
        iconName.textContent = selectedOption ? selectedOption.textContent.trim() : iconKey;
    };

    if (iconSelect) {
        iconSelect.addEventListener('change', refreshIconPreview);
        refreshIconPreview();
    }
});
</script>
HTML;
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Quản lý Tiện ích</h2>
            <p class="text-gray-500 mt-2">Admin có thể thêm, sửa, xóa và bật/tắt từng tiện ích đang hiển thị trên landing page.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng tiện ích</p>
                <p class="text-xl font-bold"><?= count($amenities ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang hiển thị</p>
                <p class="text-xl font-bold text-green-600"><?= count(array_filter($amenities ?? [], static fn($item) => (int)($item['is_active'] ?? 0) === 1)) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($amenityMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($amenityMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($amenityError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($amenityError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                <div>
                    <h3 class="text-lg font-bold"><?= $isEditing ? 'Sửa tiện ích' : 'Thêm tiện ích mới' ?></h3>
                    <p class="text-sm text-gray-500 mt-1">Dùng danh sách icon Material cố định để tránh nhập sai tên icon.</p>
                </div>

                <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50">
                    <p class="text-sm font-semibold mb-3">Xem trước icon</p>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                            <span id="amenity-icon-preview" class="material-symbols-outlined text-4xl"><?= e($formAmenity['icon'] ?? 'apartment') ?></span>
                        </div>
                        <div>
                            <p id="amenity-icon-name" class="font-semibold text-gray-900"><?= e($formAmenity['icon'] ?? 'apartment') ?></p>
                            <p class="text-sm text-gray-500">Icon sẽ hiển thị trực tiếp ngoài landing page.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-amenity" class="space-y-4">
<?= csrf_field() ?>
                    <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int)($formAmenity['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div>
                        <label for="amenity-icon-select" class="block text-sm font-semibold mb-2">Icon Material</label>
                        <select
                            id="amenity-icon-select"
                            name="icon"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                            <?php foreach ($amenityIcons as $icon): ?>
                            <option value="<?= e($icon['key']) ?>" <?= ($formAmenity['icon'] ?? 'apartment') === $icon['key'] ? 'selected' : '' ?>>
                                <?= e($icon['label']) ?> (<?= e($icon['key']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tên tiện ích *</label>
                        <input
                            type="text"
                            name="title"
                            required
                            value="<?= e($formAmenity['title'] ?? '') ?>"
                            placeholder="Ví dụ: Wifi cáp quang"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Mô tả</label>
                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Mô tả ngắn, thực tế, đủ để khách hiểu tiện ích này mang lại gì."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        ><?= e($formAmenity['description'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Thứ tự hiển thị</label>
                            <input
                                type="number"
                                name="sort_order"
                                value="<?= (int)($formAmenity['sort_order'] ?? 0) ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Trạng thái</label>
                            <label class="inline-flex w-full items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50">
                                <span class="text-sm text-gray-700">Hiển thị ngoài landing page</span>
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    <?= !empty($formAmenity['is_active']) ? 'checked' : '' ?>
                                    class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
                                >
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        <?= $isEditing ? 'Cập nhật tiện ích' : 'Thêm tiện ích' ?>
                    </button>

                    <?php if ($isEditing): ?>
                    <a href="<?= BASE_URL ?>?page=admin-amenities" class="block w-full py-3 text-center text-gray-600 hover:text-primary">
                        Hủy chỉnh sửa
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">Danh sách tiện ích</h3>
                        <p class="text-sm text-gray-500 mt-1">Có thể nhập `sort_order` để sắp xếp hoặc bật/tắt nhanh từng dòng.</p>
                    </div>
                    <a href="<?= BASE_URL ?>?page=home" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 font-semibold hover:border-primary hover:text-primary transition">
                        <span class="material-symbols-outlined text-base">open_in_new</span>
                        Xem landing page
                    </a>
                </div>

                <?php if (empty($amenities)): ?>
                <div class="px-6 py-10 text-center text-gray-500">
                    Chưa có tiện ích nào. Hãy tạo tiện ích đầu tiên ở form bên trái.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tiện ích</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mô tả</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sắp xếp</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($amenities as $item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                                            <span class="material-symbols-outlined text-3xl"><?= e($item['icon'] ?? 'apartment') ?></span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?= e($item['title'] ?? '') ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?= e($item['icon'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600"><?= e(fallbackText($item['description'] ?? '', 'Chưa có mô tả cho tiện ích này.')) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-sm font-semibold">
                                        <?= (int)($item['sort_order'] ?? 0) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="<?= BASE_URL ?>?page=admin-save-amenity" class="inline-flex">
<?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
                                        <input type="hidden" name="icon" value="<?= e($item['icon'] ?? 'apartment') ?>">
                                        <input type="hidden" name="title" value="<?= e($item['title'] ?? '') ?>">
                                        <input type="hidden" name="description" value="<?= e($item['description'] ?? '') ?>">
                                        <input type="hidden" name="sort_order" value="<?= (int)($item['sort_order'] ?? 0) ?>">
                                        <?php if ((int)($item['is_active'] ?? 0) === 0): ?>
                                        <input type="hidden" name="is_active" value="1">
                                        <?php endif; ?>
                                        <button type="submit" class="px-3 py-2 rounded-xl text-sm font-semibold <?= (int)($item['is_active'] ?? 0) === 1 ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> transition">
                                            <?= (int)($item['is_active'] ?? 0) === 1 ? 'Đang bật' : 'Đang tắt' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-3">
                                        <a href="<?= BASE_URL ?>?page=admin-amenities&edit=<?= (int)($item['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                            Sửa
                                        </a>
                                        <a
                                            href="<?= BASE_URL ?>?page=admin-delete-amenity&id=<?= (int)($item['id'] ?? 0) ?>"
                                            data-confirm="Bạn chắc chắn muốn xóa tiện ích này khỏi hệ thống?"
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
