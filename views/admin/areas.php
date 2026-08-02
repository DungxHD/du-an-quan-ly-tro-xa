<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'areas';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý khu, thống kê phòng và cây khu -> tầng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$expandedAreaId = (int)($expandedAreaId ?? 0);
require BASE_PATH . 'views/layouts/panel_header.php';

$panelPageScripts = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const imageInput = document.getElementById('area-image-input');
    const preview = document.getElementById('area-image-preview');
    const placeholder = document.getElementById('area-image-placeholder');

    const refreshPreview = () => {
        if (!imageInput || !preview || !placeholder) {
            return;
        }

        const value = imageInput.value.trim();
        if (value === '') {
            preview.classList.add('hidden');
            preview.removeAttribute('src');
            placeholder.classList.remove('hidden');
            return;
        }

        preview.src = value;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    };

    if (imageInput) {
        imageInput.addEventListener('input', refreshPreview);
        refreshPreview();
    }

    if (preview) {
        preview.addEventListener('error', () => {
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
        });
    }
});
</script>
HTML;
?>
        <div class="flex flex-col gap-6">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold">Quản lý Khu</h2>
                    <p class="text-gray-500 mt-2">Theo dõi số tầng, số phòng trống/đã thuê và cây phân cấp khu → tầng ngay trên một màn hình.</p>
                </div>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Xóa khu sẽ xóa toàn bộ tầng và phòng bên trong theo quan hệ cascade.
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                        <div>
                            <h3 class="text-lg font-bold"><?= $editArea ? 'Sửa khu' : 'Thêm khu mới' ?></h3>
                            <p class="text-sm text-gray-500 mt-1">Ảnh dùng URL trực tiếp để admin preview nhanh trước khi lưu.</p>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>?page=admin-save-area" data-validate class="space-y-4">
                            <?php if ($editArea): ?>
                            <input type="hidden" name="id" value="<?= (int)($editArea['id'] ?? 0) ?>">
                            <?php endif; ?>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Tên khu *</label>
                                <input
                                    type="text"
                                    name="name"
                                    required
                                    value="<?= e($editArea['name'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Địa chỉ</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="<?= e($editArea['address'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Mô tả</label>
                                <textarea
                                    name="description"
                                    rows="4"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                ><?= e($editArea['description'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">URL ảnh</label>
                                <input
                                    id="area-image-input"
                                    type="url"
                                    name="image"
                                    value="<?= e($editArea['image'] ?? '') ?>"
                                    placeholder="https://..."
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                >
                            </div>

                            <div class="rounded-2xl border border-dashed border-gray-200 p-3 bg-gray-50">
                                <p class="text-sm font-semibold mb-3">Xem trước ảnh</p>
                                <div class="aspect-[16/10] rounded-2xl overflow-hidden bg-white border border-gray-200 flex items-center justify-center">
                                    <img
                                        id="area-image-preview"
                                        src="<?= e($editArea['image'] ?? '') ?>"
                                        alt="Ảnh khu xem trước"
                                        class="<?= !empty($editArea['image']) ? '' : 'hidden' ?> w-full h-full object-cover"
                                    >
                                    <div id="area-image-placeholder" class="<?= !empty($editArea['image']) ? 'hidden' : '' ?> text-center px-6 text-sm text-gray-400">
                                        Nhập URL ảnh để xem preview ngay tại đây.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                                <?= $editArea ? 'Cập nhật khu' : 'Thêm khu' ?>
                            </button>

                            <?php if ($editArea): ?>
                            <a href="<?= BASE_URL ?>?page=admin-areas" class="block w-full py-3 text-center text-gray-600 hover:text-primary">
                                Hủy chỉnh sửa
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <?php if (empty($areaTree)): ?>
                    <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                        Chưa có khu nào. Tạo khu đầu tiên để bắt đầu quản lý tầng và phòng.
                    </div>
                    <?php endif; ?>

                    <?php foreach ($areaTree as $area): ?>
                    <?php
                        $isExpanded = $expandedAreaId > 0
                            ? (int)($area['id'] ?? 0) === $expandedAreaId
                            : false;
                    ?>
                    <details class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group" <?= $isExpanded ? 'open' : '' ?>>
                        <summary class="list-none cursor-pointer px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-100 shrink-0">
                                    <?php if (!empty($area['image'])): ?>
                                    <img src="<?= e($area['image']) ?>" alt="<?= e($area['name'] ?? 'Ảnh khu') ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <span class="material-symbols-outlined text-3xl">image</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-xl font-bold"><?= e($area['name'] ?? '') ?></h3>
                                        <span class="px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold">
                                            <?= (int)($area['floor_count'] ?? 0) ?> tầng
                                        </span>
                                        <span class="px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-xs font-semibold">
                                            <?= (int)($area['room_count'] ?? 0) ?> phòng
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2"><?= e(fallbackText($area['address'] ?? '', 'Chưa cập nhật địa chỉ')) ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?= e(fallbackText($area['description'] ?? '', 'Chưa có mô tả cho khu này.')) ?></p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <span class="px-3 py-2 rounded-xl bg-green-50 text-green-700 text-sm font-semibold">
                                    Trống: <?= (int)($area['available_count'] ?? 0) ?>
                                </span>
                                <span class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold">
                                    Đã thuê: <?= (int)($area['rented_count'] ?? 0) ?>
                                </span>
                                <span class="px-3 py-2 rounded-xl bg-orange-50 text-orange-700 text-sm font-semibold">
                                    Bảo trì: <?= (int)($area['maintenance_count'] ?? 0) ?>
                                </span>
                            </div>
                        </summary>

                        <div class="px-6 pb-6 border-t border-gray-100 space-y-4">
                            <div class="pt-4 flex flex-wrap gap-3">
                                <a href="<?= BASE_URL ?>?page=admin-areas&edit=<?= (int)($area['id'] ?? 0) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">
                                    <span class="material-symbols-outlined text-base">edit</span>
                                    Sửa khu
                                </a>
                                <a
                                    href="<?= BASE_URL ?>?page=admin-delete-area&id=<?= (int)($area['id'] ?? 0) ?>"
                                    data-confirm="Xóa khu này sẽ xóa tất cả tầng và phòng thuộc khu. Bạn có chắc chắn muốn tiếp tục?"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-700 font-semibold hover:bg-red-100 transition"
                                >
                                    <span class="material-symbols-outlined text-base">delete</span>
                                    Xóa khu
                                </a>
                                <a href="<?= BASE_URL ?>?page=admin-floors&area_id=<?= (int)($area['id'] ?? 0) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
                                    <span class="material-symbols-outlined text-base">stairs_2</span>
                                    Quản lý tầng
                                </a>
                            </div>

                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex items-center justify-between gap-4 mb-4">
                                    <h4 class="font-bold text-gray-900">Cây tầng của khu</h4>
                                    <span class="text-sm text-gray-500">Bấm vào badge phòng để lọc qua trang quản lý phòng.</span>
                                </div>

                                <?php if (empty($area['floors'])): ?>
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-6 text-sm text-gray-500 text-center">
                                    Khu này chưa có tầng nào. Hãy tạo tầng đầu tiên để bắt đầu phân phòng.
                                </div>
                                <?php endif; ?>

                                <div class="space-y-3">
                                    <?php foreach ($area['floors'] as $floor): ?>
                                    <div class="bg-white rounded-2xl border border-gray-100 px-4 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900"><?= e($floor['name'] ?? '') ?></p>
                                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                                    Số tầng: <?= (int)($floor['floor_number'] ?? 0) ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1">Theo dõi nhanh số phòng trống, đã thuê và bảo trì trên từng tầng.</p>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="<?= BASE_URL ?>?page=admin-rooms&floor_id=<?= (int)($floor['id'] ?? 0) ?>" class="px-3 py-2 rounded-xl bg-violet-100 text-violet-700 text-sm font-semibold hover:bg-violet-200 transition">
                                                Tổng phòng: <?= (int)($floor['room_count'] ?? 0) ?>
                                            </a>
                                            <span class="px-3 py-2 rounded-xl bg-green-50 text-green-700 text-sm font-semibold">
                                                Trống: <?= (int)($floor['available_count'] ?? 0) ?>
                                            </span>
                                            <span class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold">
                                                Đã thuê: <?= (int)($floor['rented_count'] ?? 0) ?>
                                            </span>
                                            <span class="px-3 py-2 rounded-xl bg-orange-50 text-orange-700 text-sm font-semibold">
                                                Bảo trì: <?= (int)($floor['maintenance_count'] ?? 0) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
