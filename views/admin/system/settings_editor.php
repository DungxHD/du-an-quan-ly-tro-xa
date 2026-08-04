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
    'hero_image'        => ['label' => 'Hero Banner (ảnh nền trang chủ)', 'type' => 'image'],

    'contact_address'   => ['label' => 'Liên hệ — Địa chỉ', 'type' => 'text'],
    'contact_phone'     => ['label' => 'Liên hệ — Số điện thoại', 'type' => 'text'],
    'contact_email'     => ['label' => 'Liên hệ — Email', 'type' => 'text'],
];
$visualKeys = array_keys($visualFields);
?>
<style>
    .cms-flash {
        outline: 3px solid #00685f;
        outline-offset: 4px;
        border-radius: 12px;
    }
</style>

<div class="max-w-[1500px] mx-auto space-y-6"
    id="cmsEditorRoot"
    data-csrf="<?= e(csrf_token()) ?>"
    data-upload-url="<?= BASE_URL ?>?page=admin-upload-image"
    data-home-url="<?= BASE_URL ?>?page=home"
    data-intro-url="<?= BASE_URL ?>?page=intro">

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Cấu hình hệ thống</h2>
            <p class="mt-1 text-gray-500">Nhấp vào vùng có viền đứt trên bản xem trước để chỉnh sửa, bấm Xác nhận rồi Lưu & Áp dụng để đưa lên website thật.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-xl border border-gray-200 bg-white p-1">
                <button type="button" data-cms-tab="home" class="cms-tab px-4 py-2 rounded-lg text-sm font-semibold bg-primary text-white">Trang chủ</button>
                <button type="button" data-cms-tab="intro" class="cms-tab px-4 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:text-primary">Giới thiệu</button>
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
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-semibold text-gray-600 inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-base text-primary">visibility</span>
                    Bản xem trước tĩnh — nhấp vào vùng viền đứt để chỉnh sửa
                </p>
                <span id="cmsPreviewStatus" class="text-xs text-gray-400">Đang tải...</span>
            </div>
            <div id="cmsPreviewShell" class="relative overflow-auto bg-gray-100" style="height: 760px;">
                <iframe id="cmsFrame" title="Xem trước website" style="width:1280px; height:2000px; border:0; transform-origin:0 0;"></iframe>
            </div>
        </div>

        <!-- Editor panel -->
        <div class="space-y-4 xl:sticky xl:top-24 xl:max-h-[calc(100vh-8rem)] xl:overflow-y-auto">
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
                                    <img id="cmsHeroThumb" src="<?= e($getValue($key)) ?>" alt="Preview" class="w-full h-24 object-cover rounded-xl border border-gray-200 <?= $getValue($key) === '' ? 'hidden' : '' ?>">
                                    <button type="button" id="cmsClearImage" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm font-bold shadow <?= $getValue($key) === '' ? 'hidden' : '' ?>" title="Xóa ảnh, hoàn tác về mặc định">✕</button>
                                </div>
                                <input type="text" name="settings[<?= e($key) ?>]" data-cms-input="<?= e($key) ?>" value="<?= e($getValue($key)) ?>" placeholder="Dán link ảnh hoặc chọn tệp bên dưới" class="w-full px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-sm">
                                <label class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-dashed border-gray-300 rounded-xl text-xs text-gray-600 cursor-pointer hover:border-primary hover:text-primary transition">
                                    <span class="material-symbols-outlined text-sm">upload</span>
                                    <span id="cmsUploadLabel">Chọn ảnh từ máy (tối đa 5MB)</span>
                                    <input type="file" id="cmsHeroFile" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
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
    const heroThumb = document.getElementById('cmsHeroThumb');
    const heroFile = document.getElementById('cmsHeroFile');
    const uploadLabel = document.getElementById('cmsUploadLabel');
    const clearImageBtn = document.getElementById('cmsClearImage');

    // Collect all visual inputs
    const inputs = {};
    root.querySelectorAll('[data-cms-input]').forEach((el) => {
        inputs[el.getAttribute('data-cms-input')] = el;
    });

    // Store initial values for reset
    const initialValues = {};
    Object.keys(inputs).forEach((key) => { initialValues[key] = inputs[key].value; });

    let activeTab = 'home';
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
            '[data-cms]:hover { outline-color: #4b41e1; background: rgba(0,104,95,.08); }'
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

        // 5. Auto-height
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

    // Tab switching
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            activeTab = tab.dataset.cmsTab === 'intro' ? 'intro' : 'home';
            tabs.forEach((t) => {
                const active = t === tab;
                t.classList.toggle('bg-primary', active);
                t.classList.toggle('text-white', active);
                t.classList.toggle('text-gray-600', !active);
            });
            loadPreview();
        });
    });

    // Reset
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            Object.keys(initialValues).forEach((key) => {
                if (inputs[key]) { inputs[key].value = initialValues[key]; }
            });
            if (heroThumb) {
                heroThumb.src = initialValues['hero_image'] || '';
                heroThumb.classList.toggle('hidden', !initialValues['hero_image']);
            }
            if (clearImageBtn) {
                clearImageBtn.classList.toggle('hidden', !initialValues['hero_image']);
            }
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

    // Hero image live thumb
    if (inputs['hero_image'] && heroThumb) {
        inputs['hero_image'].addEventListener('input', () => {
            const value = inputs['hero_image'].value.trim();
            if (value === '') {
                heroThumb.classList.add('hidden');
                if (clearImageBtn) { clearImageBtn.classList.add('hidden'); }
                return;
            }
            heroThumb.src = value;
            heroThumb.classList.remove('hidden');
            if (clearImageBtn) { clearImageBtn.classList.remove('hidden'); }
        });
    }

    // Clear image button
    if (clearImageBtn && inputs['hero_image']) {
        clearImageBtn.addEventListener('click', () => {
            inputs['hero_image'].value = initialValues['hero_image'] || '';
            if (heroThumb) {
                heroThumb.src = initialValues['hero_image'] || '';
                heroThumb.classList.toggle('hidden', !initialValues['hero_image']);
            }
            clearImageBtn.classList.add('hidden');
            loadPreview();
        });
    }

    // Upload
    if (heroFile) {
        heroFile.addEventListener('change', async () => {
            const file = heroFile.files && heroFile.files[0];
            if (!file) { return; }
            if (file.size > 5 * 1024 * 1024) {
                window.alert('Ảnh vượt quá 5MB.');
                heroFile.value = '';
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
                } else if (inputs['hero_image']) {
                    inputs['hero_image'].value = payload.url;
                    inputs['hero_image'].dispatchEvent(new Event('input'));
                    loadPreview();
                }
            } catch (err) {
                window.alert('Tải ảnh lên thất bại. Kiểm tra thư mục .uploads.');
            } finally {
                if (uploadLabel) { uploadLabel.textContent = originalLabel; }
                heroFile.value = '';
            }
        });
    }

    applyScale();
    loadPreview();
});
</script>
HTML;
require BASE_PATH . 'views/layouts/panel_footer.php';
?>
