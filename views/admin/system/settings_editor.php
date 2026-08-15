<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'settings';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Chỉnh sửa giao diện website với bản xem trước thu nhỏ (Trang chủ & Giới thiệu)';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';

$allFields = [];
foreach (($settingSections ?? []) as $section) {
    foreach (($section['fields'] ?? []) as $field) {
        $fieldKey = $field['key'] ?? '';
        if ($fieldKey !== '') {
            $allFields[$fieldKey] = $field;
        }
    }
}
$getValue = static function ($key) use ($allFields) {
    return (string)($allFields[$key]['value'] ?? '');
};

// Các field hiển thị trực quan trong panel editor
    $visualFields = [
        'site_name'         => ['label' => 'Tên website', 'type' => 'text'],
        'site_slogan'       => ['label' => 'Slogan (badge verified trên hero)', 'type' => 'text'],
        'hero_subheadline'  => ['label' => 'Mô tả trang chủ (dưới tiêu đề hero)', 'type' => 'textarea'],
        'site_description'  => ['label' => 'Mô tả website (SEO + footer)', 'type' => 'textarea'],
        'hero_image'        => ['label' => 'Ảnh Hero (Trang chủ)', 'type' => 'image'],
        'intro_image'       => ['label' => 'Ảnh Trang Giới thiệu', 'type' => 'image'],

        'contact_address'   => ['label' => 'Liên hệ — Địa chỉ', 'type' => 'text'],
        'contact_phone'     => ['label' => 'Liên hệ — Số điện thoại', 'type' => 'text'],
        'contact_email'     => ['label' => 'Liên hệ — Email', 'type' => 'text'],
    ];
$visualKeys = array_keys($visualFields);

$amenities = $amenities ?? [];
$amenityIcons = $amenityIcons ?? [];
$amenityCount = count($amenities);
$amenityOld = $amenityOld ?? null;
$formAmenity = is_array($amenityOld) ? $amenityOld : null;
$hasAmenityFlash = !empty($amenityMessage) || !empty($amenityError) || is_array($amenityOld);
$initialTab = $hasAmenityFlash ? 'amenities' : 'home';
?>
<style>
    .cms-flash {
        outline: 3px solid #00685f;
        outline-offset: 4px;
        border-radius: 12px;
    }
    .cms-amenity-chip.dragging,
    .cms-amenity-row.dragging {
        opacity: .4;
    }
    .cms-amenity-chip.cms-drag-over,
    .cms-amenity-row.cms-drag-over {
        outline: 2px dashed #00685f;
        outline-offset: 3px;
    }
    #cmsAmenitiesPanel .cms-drop-hint {
        border: 2px dashed #00685f;
        background: rgba(0, 104, 95, .06);
    }
</style>

<div class="max-w-[1500px] mx-auto space-y-6"
    id="cmsEditorRoot"
    data-csrf="<?= e(csrf_token()) ?>"
    data-upload-url="<?= BASE_URL ?>?page=admin-upload-image"
    data-home-url="<?= BASE_URL ?>?page=home"
    data-intro-url="<?= BASE_URL ?>?page=intro"
    data-save-order-url="<?= BASE_URL ?>?page=admin-save-amenity-order"
    data-save-amenity-url="<?= BASE_URL ?>?page=admin-save-amenity"
    data-initial-tab="<?= e($initialTab) ?>">

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Cấu hình hệ thống</h2>
            <p class="mt-1 text-gray-500">Nhấp vào vùng có viền đứt trên bản xem trước để chỉnh sửa, bấm Xác nhận rồi Lưu & Áp dụng để đưa lên website thật.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-xl border border-gray-200 bg-white p-1">
                <button type="button" data-cms-tab="home" class="cms-tab px-4 py-2 rounded-lg text-sm font-semibold bg-primary text-white">Trang chủ</button>
                <button type="button" data-cms-tab="intro" class="cms-tab px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:text-primary">Giới thiệu</button>
                <button type="button" data-cms-tab="amenities" class="cms-tab px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:text-primary inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">apps</span>
                    Tiện ích
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500"><?= (int)$amenityCount ?>/10</span>
                </button>
            </div>
            <button type="button" id="cmsResetBtn" class="px-4 py-2 rounded-xl border border-gray-200 bg-white font-semibold text-gray-700 hover:border-primary hover:text-primary transition">Đặt lại</button>
            <button type="button" id="cmsSaveBtn" class="px-5 py-2 rounded-xl bg-primary text-white font-bold hover:opacity-90 transition inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-base">save</span>
                Lưu & Áp dụng
            </button>
        </div>
    </div>

    <?php if (!empty($dashboardMessage)): ?>
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= e($dashboardMessage) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($dashboardError)): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= e($dashboardError) ?>
        </div>
    <?php endif; ?>

    <form id="cmsForm" method="POST" action="<?= BASE_URL ?>?page=admin-save-settings" class="grid grid-cols-1 xl:grid-cols-[1fr_380px] gap-6 items-start">
        <?= csrf_field() ?>

        <!-- Preview -->
        <div id="cmsPreviewWrap" class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-semibold text-gray-600 inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-primary">visibility</span>
                    Bản xem trước tĩnh — nhấp vào vùng viền đứt để chỉnh sửa, kéo tiện ích từ panel phải vào đây để thêm
                </p>
                <span id="cmsPreviewStatus" class="text-xs text-gray-400">Đang tải...</span>
            </div>
            <div id="cmsPreviewShell" class="relative overflow-auto bg-gray-100" style="height: 760px;">
                <iframe id="cmsFrame" title="Xem trước website" style="width:1280px; height:2000px; border:0; transform-origin:0 0;"></iframe>
            </div>
        </div>

        <!-- Editor panel -->
        <div id="cmsEditorWrap" class="space-y-4 xl:sticky xl:top-24 xl:max-h-[calc(100vh-8rem)] xl:overflow-y-auto">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
                <h3 class="font-bold text-lg">Nội dung chỉnh sửa</h3>

                <?php foreach ($visualFields as $key => $meta): ?>
                <div data-cms-field="<?= e($key) ?>">
                    <label class="block text-sm font-semibold mb-1 text-gray-800"><?= e($meta['label']) ?></label>
                    <div class="flex gap-2 items-start">
                        <?php if ($meta['type'] === 'textarea'): ?>
                            <textarea name="settings[<?= e($key) ?>]" data-cms-input="<?= e($key) ?>" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm"><?= e($getValue($key)) ?></textarea>
                        <?php elseif ($meta['type'] === 'image'): ?>
                            <div class="w-full space-y-2">
                                <div class="relative">
                                    <img id="cmsThumb_<?= e($key) ?>" src="<?= e($getValue($key)) ?>" alt="Preview" class="w-full h-24 object-cover rounded-xl border border-gray-200 <?= $getValue($key) === '' ? 'hidden' : '' ?>" data-cms-thumb="<?= e($key) ?>">
                                    <button type="button" class="cms-clear-image absolute top-1 right-1 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm font-bold shadow <?= $getValue($key) === '' ? 'hidden' : '' ?>" title="Xóa ảnh, hoàn tác về mặc định" data-cms-clear="<?= e($key) ?>">✕</button>
                                </div>
                                <input type="text" name="settings[<?= e($key) ?>]" data-cms-input="<?= e($key) ?>" value="<?= e($getValue($key)) ?>" placeholder="Dán link ảnh hoặc chọn tệp bên dưới" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm">
                                <label class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-dashed border-gray-300 rounded-xl text-xs text-gray-600 cursor-pointer hover:border-primary hover:text-primary transition">
                                    <span class="material-symbols-outlined text-sm">upload</span>
                                    <span class="cms-upload-label">Chọn ảnh từ máy (tối đa 5MB)</span>
                                    <input type="file" class="cms-file-input hidden" accept="image/jpeg,image/png,image/webp,image/gif" data-cms-file="<?= e($key) ?>">
                                </label>
                            </div>
                        <?php else: ?>
                            <input type="text" name="settings[<?= e($key) ?>]" data-cms-input="<?= e($key) ?>" value="<?= e($getValue($key)) ?>" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm">
                        <?php endif; ?>
                        <button type="button" data-cms-confirm="<?= e($key) ?>" class="shrink-0 px-3 py-2 rounded-xl bg-primary text-white text-xs font-semibold">Xác nhận</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tiện ích trong bản xem trước -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-lg">Tiện ích trong bản xem trước</h3>
                    <span class="text-xs text-gray-400"><?= (int)$amenityCount ?>/10</span>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Bấm vào tiện ích để bật/tắt hiển thị. Kéo thả để sắp xếp thứ tự, hoặc kéo một tiện ích vào bản xem trước và thả vào vị trí mong muốn.
                </p>
                <?php if (empty($amenities)): ?>
                    <p class="text-sm text-gray-400">Chưa có tiện ích nào. Chuyển sang tab <span class="font-semibold text-primary">Tiện ích</span> phía trên để thêm mới.</p>
                <?php else: ?>
                    <div id="cmsChipList" class="space-y-2">
                        <?php foreach ($amenities as $item): ?>
                            <div class="cms-amenity-chip flex items-center gap-2 px-3 py-2 rounded-xl border cursor-grab select-none <?= !empty($item['is_active']) ? 'bg-primary/5 border-primary/40' : 'bg-gray-50 border-dashed border-gray-300 opacity-75' ?>"
                                draggable="true"
                                data-id="<?= (int)$item['id'] ?>"
                                data-active="<?= !empty($item['is_active']) ? '1' : '0' ?>"
                                data-order="<?= (int)$item['sort_order'] ?>"
                                data-icon="<?= e($item['icon'] ?? 'apartment') ?>"
                                data-title="<?= e($item['title'] ?? '') ?>"
                                data-desc="<?= e($item['description'] ?? '') ?>"
                                title="<?= !empty($item['is_active']) ? 'Bấm để ẩn khỏi bản xem trước' : 'Bấm để hiển thị trong bản xem trước' ?>">
                                <span class="material-symbols-outlined text-base text-primary">drag_indicator</span>
                                <span class="material-symbols-outlined text-lg <?= !empty($item['is_active']) ? 'text-primary' : 'text-gray-400' ?>"><?= e($item['icon'] ?? 'apartment') ?></span>
                                <span class="text-sm font-semibold flex-1 truncate <?= !empty($item['is_active']) ? 'text-gray-800' : 'text-gray-500' ?>"><?= e($item['title'] ?? '') ?></span>
                                <span class="material-symbols-outlined text-base <?= !empty($item['is_active']) ? 'text-primary' : 'text-gray-300' ?>"><?= !empty($item['is_active']) ? 'visibility' : 'visibility_off' ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <p class="text-xs text-gray-400">Các cấu hình khác (kiểm duyệt, Gemini...) được giữ nguyên khi lưu từ trang này.</p>
        </div>

        <!-- Hidden inputs cho tất cả field khác -->
        <div class="hidden">
            <?php foreach ($allFields as $key => $field): ?>
                <?php if (in_array($key, $visualKeys, true)) { continue; } ?>
                <?php $fieldType = $field['type'] ?? 'text'; ?>
                <?php if ($fieldType === 'password'): ?>
                    <input type="hidden" name="settings[<?= e($key) ?>]" value="">
                <?php else: ?>
                    <input type="hidden" name="settings[<?= e($key) ?>]" value="<?= e((string)($field['value'] ?? '')) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </form>

    <!-- Quản lý tiện ích (thay thế bản xem trước khi bấm tab Tiện ích) -->
    <div id="cmsAmenitiesPanel" class="hidden rounded-2xl border border-gray-200 bg-white overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50">
            <div>
                <h3 class="font-bold text-lg inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">apps</span>
                    Quản lý tiện ích
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Kéo thả để sắp xếp thứ tự hiển thị trên website. Tối đa 10 tiện ích.</p>
            </div>
            <button type="button" id="cmsAmenityAddBtn" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold hover:opacity-90 transition inline-flex items-center gap-2 <?= $amenityCount >= 10 ? 'opacity-40 pointer-events-none' : '' ?>" <?= $amenityCount >= 10 ? 'title="Đã đạt giới hạn 10 tiện ích"' : '' ?>>
                <span class="material-symbols-outlined text-base">add</span>
                Thêm tiện ích mới
            </button>
        </div>

        <?php if (!empty($amenityMessage)): ?>
            <div class="m-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($amenityMessage) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($amenityError)): ?>
            <div class="m-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($amenityError) ?>
            </div>
        <?php endif; ?>

        <!-- Form thêm / sửa -->
        <div id="cmsAmenityFormWrap" class="border-b border-gray-100 px-5 py-4 <?= $formAmenity ? '' : 'hidden' ?>">
            <form method="POST" action="<?= BASE_URL ?>?page=admin-save-amenity" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)($formAmenity['id'] ?? 0) ?>">
                <input type="hidden" name="sort_order" value="<?= (int)($formAmenity['sort_order'] ?? $amenityCount + 1) ?>">
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800">Icon</label>
                    <select name="icon" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm bg-white">
                        <?php foreach ($amenityIcons as $iconOpt): ?>
                            <option value="<?= e($iconOpt['key']) ?>" <?= (string)($formAmenity['icon'] ?? 'apartment') === $iconOpt['key'] ? 'selected' : '' ?>>
                                <?= e($iconOpt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-gray-800">Tên tiện ích <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= e((string)($formAmenity['title'] ?? '')) ?>" placeholder="Ví dụ: Wifi tốc độ cao" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-1 text-gray-800">Mô tả</label>
                    <input type="text" name="description" value="<?= e((string)($formAmenity['description'] ?? '')) ?>" placeholder="Mô tả ngắn gọn về tiện ích này" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm">
                </div>
                <div class="flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-primary" <?= !empty($formAmenity['is_active']) ? 'checked' : '' ?>>
                        Hiển thị ngay trên website
                    </label>
                </div>
                <div class="flex items-center gap-2 md:justify-end">
                    <button type="button" id="cmsAmenityFormCancel" class="px-4 py-2 rounded-xl border border-gray-200 font-semibold text-gray-600 hover:border-red-300 hover:text-red-500 transition">Hủy</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-white font-bold hover:opacity-90 transition">Lưu tiện ích</button>
                </div>
            </form>
        </div>

        <!-- Danh sách tiện ích -->
        <div id="cmsAmenityList" class="p-5 space-y-2">
            <?php if (empty($amenities)): ?>
                <p class="text-sm text-gray-400 py-6 text-center">Chưa có tiện ích nào. Bấm "Thêm tiện ích mới" để bắt đầu (tối đa 10).</p>
            <?php else: ?>
                <?php foreach ($amenities as $item): ?>
                    <div class="cms-amenity-row flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-100 bg-white cursor-grab select-none hover:border-primary/40 transition"
                        draggable="true"
                        data-id="<?= (int)$item['id'] ?>"
                        data-active="<?= !empty($item['is_active']) ? '1' : '0' ?>"
                        data-order="<?= (int)$item['sort_order'] ?>"
                        data-icon="<?= e($item['icon'] ?? 'apartment') ?>"
                        data-title="<?= e($item['title'] ?? '') ?>"
                        data-desc="<?= e($item['description'] ?? '') ?>">
                        <span class="material-symbols-outlined text-gray-300">drag_indicator</span>
                        <span class="w-11 h-11 rounded-xl <?= !empty($item['is_active']) ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-400' ?> flex items-center justify-center">
                            <span class="material-symbols-outlined"><?= e($item['icon'] ?? 'apartment') ?></span>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm <?= !empty($item['is_active']) ? 'text-gray-800' : 'text-gray-400' ?>"><?= e($item['title'] ?? '') ?></p>
                            <p class="text-xs text-gray-500 truncate"><?= e($item['description'] ?? '') ?></p>
                        </div>
                        <span class="text-[10px] px-2 py-1 rounded-full font-semibold <?= !empty($item['is_active']) ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' ?>">
                            <?= !empty($item['is_active']) ? 'Đang hiển thị' : 'Đang ẩn' ?>
                        </span>
                        <button type="button" class="cms-amenity-toggle w-9 h-9 rounded-lg flex items-center justify-center <?= !empty($item['is_active']) ? 'text-primary hover:bg-primary/10' : 'text-gray-400 hover:bg-gray-100' ?>" title="<?= !empty($item['is_active']) ? 'Ẩn tiện ích' : 'Hiển thị tiện ích' ?>">
                            <span class="material-symbols-outlined text-lg"><?= !empty($item['is_active']) ? 'visibility' : 'visibility_off' ?></span>
                        </button>
                        <button type="button" class="cms-amenity-edit w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:text-primary hover:bg-primary/10" title="Chỉnh sửa tiện ích">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <form method="POST" action="<?= BASE_URL ?>?page=admin-delete-amenity" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa tiện ích này khỏi hệ thống?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-50" title="Xóa tiện ích">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$panelPageScripts = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('cmsEditorRoot');
    if (!root) { return; }

    const frame = document.getElementById('cmsFrame');
    const shell = document.getElementById('cmsPreviewShell');
    const statusEl = document.getElementById('cmsPreviewStatus');
    const form = document.getElementById('cmsForm');
    const saveBtn = document.getElementById('cmsSaveBtn');
    const resetBtn = document.getElementById('cmsResetBtn');
    const tabs = Array.from(document.querySelectorAll('[data-cms-tab]'));
    const previewWrap = document.getElementById('cmsPreviewWrap');
    const editorWrap = document.getElementById('cmsEditorWrap');
    const amenitiesPanel = document.getElementById('cmsAmenitiesPanel');

    // Collect all visual inputs
    const inputs = {};
    root.querySelectorAll('[data-cms-input]').forEach((el) => {
        inputs[el.getAttribute('data-cms-input')] = el;
    });

    // Store initial values for reset
    const initialValues = {};
    Object.keys(inputs).forEach((key) => { initialValues[key] = inputs[key].value; });

    let activeTab = 'home';
    {
        const stored = sessionStorage.getItem('cmsActiveTab');
        if (root.dataset.initialTab === 'amenities') {
            activeTab = 'amenities';
        } else if (stored && ['home', 'intro', 'amenities'].indexOf(stored) !== -1) {
            activeTab = stored;
        }
        sessionStorage.removeItem('cmsActiveTab');
    }
    const DESIGN_WIDTH = 1280;

    const applyScale = () => {
        if (!frame || !shell) { return; }
        const scale = Math.min(1, shell.clientWidth / DESIGN_WIDTH);
        frame.style.transform = 'scale(' + scale + ')';
    };
    window.addEventListener('resize', applyScale);

    const buildPreviewUrl = () => {
        const base = activeTab === 'home' ? root.dataset.homeUrl : root.dataset.introUrl;
        const params = new URLSearchParams();
        params.set('cms_preview', '1');
        Object.keys(inputs).forEach((key) => {
            if (inputs[key]) { params.set('ov[' + key + ']', inputs[key].value); }
        });
        return base + '&' + params.toString();
    };

    const focusField = (key) => {
        const box = root.querySelector('[data-cms-field="' + key + '"]');
        const input = inputs[key];
        if (box) {
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            box.classList.add('cms-flash');
            window.setTimeout(() => box.classList.remove('cms-flash'), 1800);
        }
        if (input) { input.focus(); }
    };

    let pendingScrollKey = '';

    const getFrameScale = () => {
        const match = /scale\(([\d.]+)\)/.exec(frame.style.transform || '');
        return match ? parseFloat(match[1]) : 1;
    };

    // Cuộn bản xem trước tới vùng đang chỉnh sửa
    const scrollPreviewTo = (key) => {
        let doc = null;
        try {
            doc = frame.contentDocument || (frame.contentWindow ? frame.contentWindow.document : null);
        } catch (err) {
            doc = null;
        }
        if (!doc) { return; }
        const target = doc.querySelector('[data-cms="' + key + '"]');
        if (!target || !shell) { return; }
        const scale = getFrameScale();
        const top = target.getBoundingClientRect().top * scale;
        shell.scrollTo({ top: Math.max(0, top - shell.clientHeight / 3), behavior: 'smooth' });
    };

    // Lưu thứ tự tiện ích sau khi kéo thả (activate_id dùng khi kéo tiện ích đang ẩn vào bản xem trước)
    const saveAmenityOrder = (orderedIds, activateId, thenReload) => {
        const body = new URLSearchParams();
        body.set('_csrf_token', root.dataset.csrf || '');
        body.set('ordered_ids', orderedIds.join(','));
        if (activateId) { body.set('activate_id', String(activateId)); }
        fetch(root.dataset.saveOrderUrl, { method: 'POST', body })
            .then(() => {
                if (thenReload) {
                    sessionStorage.setItem('cmsActiveTab', activeTab);
                    window.location.reload();
                }
            })
            .catch(() => { window.alert('Không lưu được thứ tự tiện ích.'); });
    };

    // Bật/tắt hiển thị tiện ích
    const toggleAmenity = (chip, active) => {
        const list = chip.closest('#cmsChipList') || chip.closest('#cmsAmenityList');
        const currentIndex = list
            ? Array.from(list.querySelectorAll('.cms-amenity-chip, .cms-amenity-row')).indexOf(chip)
            : 0;
        const body = new URLSearchParams();
        body.set('_csrf_token', root.dataset.csrf || '');
        body.set('id', chip.dataset.id);
        body.set('icon', chip.dataset.icon || 'apartment');
        body.set('title', chip.dataset.title || '');
        body.set('description', chip.dataset.desc || '');
        body.set('sort_order', String(Math.max(0, currentIndex)));
        body.set('is_active', active ? '1' : '0');
        fetch(root.dataset.saveAmenityUrl, { method: 'POST', body })
            .then(() => {
                sessionStorage.setItem('cmsActiveTab', activeTab);
                window.location.reload();
            })
            .catch(() => { window.alert('Không cập nhật được tiện ích.'); });
    };

    // ---------- Kéo thả tiện ích (chip trong panel editor + dòng trong bảng quản lý) ----------
    const getDragPayload = (event) => {
        try {
            const raw = event.dataTransfer.getData('application/x-amenity');
            return raw ? JSON.parse(raw) : null;
        } catch (err) {
            return null;
        }
    };

    const setupDraggable = (el) => {
        el.addEventListener('dragstart', (event) => {
            event.dataTransfer.setData('application/x-amenity', JSON.stringify({
                id: el.dataset.id,
                title: el.dataset.title || '',
                active: el.dataset.active === '1' ? 1 : 0
            }));
            event.dataTransfer.effectAllowed = 'move';
            el.classList.add('dragging');
        });
        el.addEventListener('dragend', () => {
            el.classList.remove('dragging');
            document.querySelectorAll('.cms-drag-over').forEach((n) => n.classList.remove('cms-drag-over'));
        });
        el.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        });
    };

    // Sắp xếp trong cùng một danh sách (chip hoặc bảng quản lý)
    const wireListReorder = (listEl) => {
        if (!listEl) { return; }
        listEl.addEventListener('dragover', (event) => {
            const target = event.target.closest('.cms-amenity-chip, .cms-amenity-row');
            if (!target) { return; }
            event.preventDefault();
            listEl.querySelectorAll('.cms-drag-over').forEach((n) => n.classList.remove('cms-drag-over'));
            target.classList.add('cms-drag-over');
        });
        listEl.addEventListener('drop', (event) => {
            const payload = getDragPayload(event);
            const target = event.target.closest('.cms-amenity-chip, .cms-amenity-row');
            if (!payload || !target || String(target.dataset.id) === String(payload.id)) {
                listEl.querySelectorAll('.cms-drag-over').forEach((n) => n.classList.remove('cms-drag-over'));
                return;
            }
            event.preventDefault();
            listEl.querySelectorAll('.cms-drag-over').forEach((n) => n.classList.remove('cms-drag-over'));
            const items = Array.from(listEl.querySelectorAll('.cms-amenity-chip, .cms-amenity-row'))
                .filter((n) => String(n.dataset.id) !== String(payload.id));
            const targetIndex = items.indexOf(target);
            const insertAt = targetIndex === -1 ? items.length : targetIndex;
            items.splice(insertAt, 0, target.closest('.cms-amenity-chip, .cms-amenity-row'));
            saveAmenityOrder(items.map((n) => n.dataset.id), 0, true);
        });
        listEl.addEventListener('dragleave', (event) => {
            if (!listEl.contains(event.relatedTarget)) {
                listEl.querySelectorAll('.cms-drag-over').forEach((n) => n.classList.remove('cms-drag-over'));
            }
        });
    };

    root.querySelectorAll('.cms-amenity-chip, .cms-amenity-row').forEach(setupDraggable);
    wireListReorder(document.getElementById('cmsChipList'));
    wireListReorder(document.getElementById('cmsAmenityList'));

    // Bấm chip để bật/tắt hiển thị trong bản xem trước
    root.querySelectorAll('.cms-amenity-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            toggleAmenity(chip, chip.dataset.active !== '1');
        });
    });

    // ---------- Bản xem trước: kéo tiện ích vào để thêm/sắp xếp ----------
    const decorateFrame = () => {
        let doc = null;
        try {
            doc = frame.contentDocument || (frame.contentWindow ? frame.contentWindow.document : null);
        } catch (err) {
            doc = null;
        }
        if (!doc) { return; }

        // 1. Disable ALL animations, transitions, hover effects for static preview
        const style = doc.createElement('style');
        style.textContent = [
            '*, *::before, *::after { transition: none !important; animation: none !important; }',
            '.hero-bg { transform: none !important; }',
            '.reveal, .reveal-left, .reveal-right, .reveal-scale { opacity: 1 !important; transform: none !important; }',
            '.stagger-children > * { opacity: 1 !important; transform: none !important; }',
            'a:hover, button:hover, .card-hover:hover { transform: none !important; box-shadow: none !important; opacity: 1 !important; }',
            '.animate-bounce { animation: none !important; }',
            '[data-cms] { outline: 2px dashed #00685f; outline-offset: 3px; cursor: pointer; }',
            '[data-cms]:hover { outline-color: #4b41e1; background: rgba(0,104,95,.08); }',
            '[data-amenity-dropzone] [data-amenity-id].cms-drop-target { outline: 2px dashed #00685f; outline-offset: 3px; background: rgba(0,104,95,.08); }'
        ].join('\n');
        if (doc.head) { doc.head.appendChild(style); }

        // 2. Force reveal elements visible immediately
        doc.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children').forEach((el) => {
            el.classList.add('active');
        });

        // 3. Force counter elements to show final value
        doc.querySelectorAll('[data-target]').forEach((el) => {
            el.textContent = el.getAttribute('data-target') || '0';
        });

        // 4. Block link navigation
        doc.addEventListener('click', (event) => {
            const target = event.target;
            if (!target || !target.closest) { return; }
            const link = target.closest('a');
            if (link) { event.preventDefault(); }
            const editable = target.closest('[data-cms]');
            if (editable) {
                event.preventDefault();
                focusField(editable.getAttribute('data-cms'));
            }
        }, true);

        // 5. Khu vực thả tiện ích (chỉ tab Trang chủ)
        if (activeTab === 'home') {
            const dropzone = doc.querySelector('[data-amenity-dropzone]');
            if (dropzone) {
                const clearDropTargets = () => {
                    dropzone.querySelectorAll('.cms-drop-target').forEach((n) => n.classList.remove('cms-drop-target'));
                };
                dropzone.addEventListener('dragover', (event) => {
                    const payload = getDragPayload(event);
                    if (!payload) { return; }
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    clearDropTargets();
                    const card = event.target.closest('[data-amenity-id]');
                    if (card) { card.classList.add('cms-drop-target'); }
                });
                dropzone.addEventListener('dragleave', (event) => {
                    if (!dropzone.contains(event.relatedTarget)) { clearDropTargets(); }
                });
                dropzone.addEventListener('drop', (event) => {
                    const payload = getDragPayload(event);
                    if (!payload) { return; }
                    event.preventDefault();
                    clearDropTargets();
                    const cards = Array.from(dropzone.querySelectorAll('[data-amenity-id]'))
                        .filter((n) => String(n.dataset.amenityId) !== String(payload.id));
                    let insertIndex = cards.length;
                    const targetCard = event.target.closest('[data-amenity-id]');
                    if (targetCard) {
                        const targetIndex = cards.indexOf(targetCard);
                        const rect = targetCard.getBoundingClientRect();
                        insertIndex = targetIndex === -1 ? cards.length : (event.clientY < rect.top + rect.height / 2 ? targetIndex : targetIndex + 1);
                    }
                    const orderedIds = cards.map((n) => n.dataset.amenityId);
                    orderedIds.splice(insertIndex, 0, payload.id);
                    saveAmenityOrder(orderedIds, payload.active ? 0 : payload.id, true);
                });
            }
        }

        // 6. Auto-height
        const contentHeight = doc.documentElement ? doc.documentElement.scrollHeight : 2400;
        frame.style.height = Math.max(1200, contentHeight) + 'px';
        applyScale();
        if (statusEl) {
            statusEl.textContent = 'Đang xem trước: ' + (activeTab === 'home' ? 'Trang chủ' : 'Giới thiệu');
        }
        if (pendingScrollKey) {
            const keyToScroll = pendingScrollKey;
            pendingScrollKey = '';
            window.setTimeout(() => scrollPreviewTo(keyToScroll), 80);
        }
    };

    const loadPreview = () => {
        if (!frame) { return; }
        if (statusEl) { statusEl.textContent = 'Đang tải bản xem trước...'; }
        frame.removeEventListener('load', decorateFrame);
        frame.addEventListener('load', decorateFrame);
        frame.src = buildPreviewUrl();
        applyScale();
    };

    // Confirm buttons: reload preview rồi cuộn demo tới vùng vừa sửa
    root.querySelectorAll('[data-cms-confirm]').forEach((btn) => {
        btn.addEventListener('click', () => {
            pendingScrollKey = btn.getAttribute('data-cms-confirm') || '';
            loadPreview();
        });
    });

    // Focus vào ô nhập bên phải -> cuộn demo tới vùng tương ứng
    Object.keys(inputs).forEach((key) => {
        const el = inputs[key];
        if (!el) { return; }
        el.addEventListener('focus', () => scrollPreviewTo(key));
    });

    // Tab switching: Trang chủ / Giới thiệu / Tiện ích
    const switchTab = (tab) => {
        activeTab = tab.dataset.cmsTab || 'home';
        tabs.forEach((t) => {
            const active = t === tab;
            t.classList.toggle('bg-primary', active);
            t.classList.toggle('text-white', active);
            t.classList.toggle('text-gray-600', !active);
        });
        const isAmenities = activeTab === 'amenities';
        if (previewWrap) { previewWrap.classList.toggle('hidden', isAmenities); }
        if (editorWrap) { editorWrap.classList.toggle('hidden', isAmenities); }
        if (amenitiesPanel) { amenitiesPanel.classList.toggle('hidden', !isAmenities); }
        if (!isAmenities) { loadPreview(); }
    };
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => switchTab(tab));
    });
    switchTab(tabs.find((t) => t.dataset.cmsTab === activeTab) || tabs[0]);

    // Reset
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            Object.keys(initialValues).forEach((key) => {
                if (inputs[key]) { inputs[key].value = initialValues[key]; }
            });
            root.querySelectorAll('[data-cms-thumb]').forEach((thumb) => {
                const key = thumb.getAttribute('data-cms-thumb');
                thumb.src = initialValues[key] || '';
                thumb.classList.toggle('hidden', !initialValues[key]);
            });
            root.querySelectorAll('[data-cms-clear]').forEach((btn) => {
                btn.classList.toggle('hidden', !initialValues[btn.getAttribute('data-cms-clear')]);
            });
            loadPreview();
        });
    }

    // Save
    if (saveBtn && form) {
        saveBtn.addEventListener('click', () => {
            if (window.confirm('Áp dụng toàn bộ nội dung đang xem trước lên website thật?')) {
                form.submit();
            }
        });
    }

    // Image fields: live thumb, clear, upload (hero_image + intro_image)
    root.querySelectorAll('[data-cms-thumb]').forEach((thumb) => {
        const key = thumb.getAttribute('data-cms-thumb');
        const input = inputs[key];
        const clearBtn = root.querySelector('[data-cms-clear="' + key + '"]');
        const fileInput = root.querySelector('[data-cms-file="' + key + '"]');
        const uploadLabel = fileInput ? fileInput.closest('label').querySelector('.cms-upload-label') : null;

        const refreshThumb = () => {
            const value = input ? input.value.trim() : '';
            thumb.src = value || '';
            thumb.classList.toggle('hidden', value === '');
            if (clearBtn) { clearBtn.classList.toggle('hidden', value === ''); }
        };

        if (input) {
            input.addEventListener('input', refreshThumb);
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (input) { input.value = initialValues[key] || ''; }
                refreshThumb();
                loadPreview();
            });
        }
        if (fileInput) {
            fileInput.addEventListener('change', async () => {
                const file = fileInput.files && fileInput.files[0];
                if (!file) { return; }
                if (file.size > 5 * 1024 * 1024) {
                    window.alert('Ảnh vượt quá 5MB.');
                    fileInput.value = '';
                    return;
                }
                const originalLabel = uploadLabel ? uploadLabel.textContent : '';
                if (uploadLabel) { uploadLabel.textContent = 'Đang tải ảnh lên...'; }
                try {
                    const data = new FormData();
                    data.append('image', file);
                    data.append('_csrf_token', root.dataset.csrf || '');
                    data.append('slot', 'home');
                    const response = await fetch(root.dataset.uploadUrl, { method: 'POST', body: data });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || !payload.ok) {
                        window.alert(payload.message || 'Tải ảnh lên thất bại.');
                    } else if (input) {
                        input.value = payload.url;
                        refreshThumb();
                        loadPreview();
                    }
                } catch (err) {
                    window.alert('Tải ảnh lên thất bại. Kiểm tra thư mục .uploads.');
                } finally {
                    if (uploadLabel) { uploadLabel.textContent = originalLabel; }
                    fileInput.value = '';
                }
            });
        }
    });

    // ---------- Quản lý tiện ích: mở form thêm / sửa ----------
    const addBtn = document.getElementById('cmsAmenityAddBtn');
    const formWrap = document.getElementById('cmsAmenityFormWrap');
    const formCancel = document.getElementById('cmsAmenityFormCancel');
    const openAmenityForm = (data) => {
        if (!formWrap) { return; }
        formWrap.classList.remove('hidden');
        const f = formWrap.querySelector('form');
        if (f) {
            f.querySelector('[name="id"]').value = data && data.id ? data.id : 0;
            f.querySelector('[name="sort_order"]').value = data && data.order ? data.order : 0;
            f.querySelector('[name="icon"]').value = data && data.icon ? data.icon : 'apartment';
            f.querySelector('[name="title"]').value = data && data.title ? data.title : '';
            f.querySelector('[name="description"]').value = data && data.desc ? data.desc : '';
            f.querySelector('[name="is_active"]').checked = !data || data.active === '1';
        }
        formWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    if (addBtn) {
        addBtn.addEventListener('click', () => openAmenityForm(null));
    }
    if (formCancel) {
        formCancel.addEventListener('click', () => {
            if (formWrap) { formWrap.classList.add('hidden'); }
        });
    }
    root.querySelectorAll('.cms-amenity-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.cms-amenity-row');
            if (!row) { return; }
            openAmenityForm({
                id: row.dataset.id,
                icon: row.dataset.icon,
                title: row.dataset.title,
                desc: row.dataset.desc,
                active: row.dataset.active,
                order: row.dataset.order
            });
        });
    });
    root.querySelectorAll('.cms-amenity-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.cms-amenity-row');
            if (!row) { return; }
            toggleAmenity(row, row.dataset.active !== '1');
        });
    });

    applyScale();
    if (activeTab !== 'amenities') { loadPreview(); }
});
</script>
HTML;
require BASE_PATH . 'views/layouts/panel_footer.php';
?>