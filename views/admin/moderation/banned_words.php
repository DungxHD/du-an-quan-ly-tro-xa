<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'banned-words';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý bộ từ cấm offline, chuẩn hóa trước khi lưu và bật/tắt linh hoạt';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Bộ từ cấm</h2>
            <p class="text-gray-500 mt-2">
                Hệ thống lưu từ cấm ở dạng đã bỏ dấu, chữ thường để lọc ổn định ngay cả khi người dùng nhập có dấu.
            </p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng từ</p>
                <p class="text-xl font-bold"><?= (int)($bannedWordStats['total'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang bật</p>
                <p class="text-xl font-bold text-green-600"><?= (int)($bannedWordStats['active'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang tắt</p>
                <p class="text-xl font-bold text-slate-600"><?= (int)($bannedWordStats['inactive'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Từ đơn</p>
                <p class="text-xl font-bold text-primary"><?= (int)($bannedWordStats['word'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Cụm từ</p>
                <p class="text-xl font-bold text-amber-600"><?= (int)($bannedWordStats['phrase'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Viết tắt</p>
                <p class="text-xl font-bold text-purple-600"><?= (int)($bannedWordStats['abbreviation'] ?? 0) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($bannedWordMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($bannedWordMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($bannedWordError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($bannedWordError) ?>
    </div>
    <?php endif; ?>

    <section class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-20 space-y-5">
                <div>
                    <h3 class="text-xl font-bold"><?= !empty($bannedWordForm['id']) ? 'Cập nhật từ cấm' : 'Thêm từ cấm mới' ?></h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Preview bên dưới cho thấy hệ thống sẽ lưu gì sau khi bỏ dấu, đưa về chữ thường và gom khoảng trắng.
                    </p>
                </div>

                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-banned-word" class="space-y-4">
<?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)($bannedWordForm['id'] ?? 0) ?>">
                    <input type="hidden" name="return_type" value="<?= e($bannedWordFilters['type'] ?? '') ?>">
                    <input type="hidden" name="return_keyword" value="<?= e($bannedWordFilters['keyword'] ?? '') ?>">
                    <input type="hidden" name="return_is_active" value="<?= e($bannedWordFilters['is_active'] ?? '') ?>">

                    <div>
                        <label class="block text-sm font-semibold mb-2">Từ/cụm từ gốc</label>
                        <input
                            type="text"
                            name="word"
                            value="<?= e($bannedWordForm['word'] ?? '') ?>"
                            placeholder="Ví dụ: mất dạy, đm, vcl..."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            data-normalize-source
                        >
                    </div>

                    <div class="rounded-2xl border border-dashed border-primary/30 bg-primary/5 px-4 py-4">
                        <p class="text-xs font-semibold text-primary uppercase tracking-wide">Preview chuẩn hóa</p>
                        <p class="mt-2 text-sm text-gray-700" data-normalize-preview><?= e($normalizedPreview !== '' ? $normalizedPreview : 'Chưa có dữ liệu để chuẩn hóa') ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Loại</label>
                            <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <?php foreach ($bannedWordTypeOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= ($bannedWordForm['type'] ?? 'word') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Chuỗi thay thế</label>
                            <input
                                type="text"
                                name="replacement"
                                value="<?= e($bannedWordForm['replacement'] ?? '***') ?>"
                                placeholder="***"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            >
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-primary" <?= !empty($bannedWordForm['is_active']) ? 'checked' : '' ?>>
                        <span>
                            <span class="font-semibold text-gray-800">Đang hoạt động</span>
                            <span class="block text-sm text-gray-500">Tắt mục này nếu chỉ muốn giữ lại để tham khảo nhưng không áp dụng khi quét comment.</span>
                        </span>
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                            <?= !empty($bannedWordForm['id']) ? 'Lưu cập nhật' : 'Thêm từ cấm' ?>
                        </button>
                        <?php if (!empty($bannedWordForm['id'])): ?>
                        <a href="<?= BASE_URL ?>?page=admin-banned-words" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Tạo mới
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="xl:col-span-3 space-y-6">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <form method="GET" action="<?= BASE_URL ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="page" value="admin-banned-words">

                    <div>
                        <label class="block text-sm font-semibold mb-2">Từ khóa</label>
                        <input
                            type="text"
                            name="keyword"
                            value="<?= e($bannedWordFilters['keyword'] ?? '') ?>"
                            placeholder="Tìm theo từ đã chuẩn hóa"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Loại</label>
                        <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Tất cả</option>
                            <?php foreach ($bannedWordTypeOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($bannedWordFilters['type'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Trạng thái</label>
                        <select name="is_active" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Tất cả</option>
                            <option value="1" <?= ($bannedWordFilters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Đang bật</option>
                            <option value="0" <?= ($bannedWordFilters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Đang tắt</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button type="submit" class="flex-1 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                            Lọc
                        </button>
                        <a href="<?= BASE_URL ?>?page=admin-banned-words" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-lg">Danh sách từ cấm</h3>
                    <p class="text-sm text-gray-500 mt-1">Bản ghi dài hơn được ưu tiên quét trước để tránh phrase bị nuốt bởi word ngắn hơn.</p>
                </div>

                <?php if (empty($bannedWords)): ?>
                <div class="px-6 py-12 text-center text-gray-500">
                    Không có từ cấm nào khớp bộ lọc hiện tại.
                </div>
                <?php else: ?>
                <div class="p-6 space-y-4">
                    <?php foreach ($bannedWords as $word): ?>
                    <article class="rounded-2xl border border-gray-200 p-5 bg-white">
                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-gray-900"><?= e($word['word'] ?? '') ?></p>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <?= e($word['type_label'] ?? '') ?>
                                    </span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= (int)($word['is_active'] ?? 0) === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                        <?= (int)($word['is_active'] ?? 0) === 1 ? 'Đang bật' : 'Đang tắt' ?>
                                    </span>
                                </div>
                                <p class="mt-3 text-sm text-gray-500">
                                    Chuỗi thay thế: <span class="font-semibold text-gray-800"><?= e($word['replacement'] ?? '***') ?></span>
                                </p>
                                <p class="mt-2 text-xs text-gray-400">
                                    Tạo lúc <?= e($word['created_at_label'] ?? '') ?>
                                </p>
                            </div>

                            <div class="w-full xl:w-auto flex flex-wrap gap-3">
                                <a href="<?= BASE_URL ?>?page=admin-banned-words&edit=<?= (int)($word['id'] ?? 0) ?>" class="px-4 py-3 rounded-xl border border-primary text-primary text-center font-semibold hover:bg-primary/5 transition">
                                    Chỉnh sửa
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-banned-word" onsubmit="return confirm('Bạn chắc chắn muốn xóa từ cấm này?');">
<?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)($word['id'] ?? 0) ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="return_type" value="<?= e($bannedWordFilters['type'] ?? '') ?>">
                                    <input type="hidden" name="return_keyword" value="<?= e($bannedWordFilters['keyword'] ?? '') ?>">
                                    <input type="hidden" name="return_is_active" value="<?= e($bannedWordFilters['is_active'] ?? '') ?>">
                                    <button type="submit" class="px-4 py-3 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition">
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>

<script>
(() => {
    const source = document.querySelector('[data-normalize-source]');
    const preview = document.querySelector('[data-normalize-preview]');

    if (!source || !preview) {
        return;
    }

    const normalize = (value) => value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9\s]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const paint = () => {
        const normalized = normalize(source.value || '');
        preview.textContent = normalized || 'Chưa có dữ liệu để chuẩn hóa';
    };

    source.addEventListener('input', paint);
    paint();
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
