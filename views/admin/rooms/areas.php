<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'areas';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý khu, thống kê phòng và cây khu -> tầng';
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
                    <p class="text-gray-500 mt-2">Tạo khu kèm số tầng, thêm phòng lồng trong form, và thao tác nhanh Sửa / Thêm tầng / Thêm phòng trên từng khu.</p>
                </div>
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
                        <span class="material-symbols-outlined text-base">add_business</span>
                        Thêm khu mới
                    </button>
                    <?php endif; ?>

                    <div id="area-form-card" class="<?= $editArea ? '' : 'hidden' ?> bg-white p-6 rounded-2xl shadow-sm border border-gray-100 xl:sticky xl:top-20 space-y-5">
                        <div>
                            <h3 class="text-lg font-bold"><?= $editArea ? 'Sửa khu' : 'Thêm khu mới' ?></h3>
                            <p class="text-sm text-gray-500 mt-1"><?= $editArea ? 'Cập nhật thông tin và ảnh khu. Tầng/phòng quản lý bằng các nút trên thẻ khu.' : 'Ảnh có thể dán link hoặc tải lên. Chọn số tầng và thêm phòng lồng ngay trong form.' ?></p>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>?page=admin-save-area" data-validate id="area-main-form" class="space-y-4">
<?= csrf_field() ?>
                            <?php if ($editArea): ?>
                            <input type="hidden" name="id" value="<?= (int)($editArea['id'] ?? 0) ?>">
                            <?php endif; ?>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Tên khu *</label>
                                <input type="text" name="name" required value="<?= e($editArea['name'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Địa chỉ</label>
                                <input type="text" name="address" value="<?= e($editArea['address'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Mô tả</label>
                                <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"><?= e($editArea['description'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Ảnh khu (dán link hoặc tải lên)</label>
                                <div class="space-y-2">
                                    <input id="area-image-input" type="text" name="image" value="<?= e($editArea['image'] ?? '') ?>" placeholder="https://..." class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                    <label class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-dashed border-gray-300 rounded-xl text-sm text-gray-600 cursor-pointer hover:border-primary hover:text-primary transition">
                                        <span class="material-symbols-outlined text-base">upload</span>
                                        <span id="area-upload-label">Chọn ảnh từ máy (tối đa 5MB)</span>
                                        <input type="file" id="area-image-file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden">
                                    </label>
                                </div>
                                <div class="mt-2 rounded-2xl border border-dashed border-gray-200 p-3 bg-gray-50">
                                    <div class="aspect-[16/10] rounded-2xl overflow-hidden bg-white border border-gray-200 flex items-center justify-center">
                                        <img id="area-image-preview" src="<?= e($editArea['image'] ?? '') ?>" alt="Ảnh khu" class="<?= !empty($editArea['image']) ? '' : 'hidden' ?> w-full h-full object-cover">
                                        <div id="area-image-placeholder" class="<?= !empty($editArea['image']) ? 'hidden' : '' ?> text-center px-6 text-sm text-gray-400">Chưa có ảnh khu.</div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!$editArea): ?>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Số tầng (mặc định 1, tối thiểu 1)</label>
                                <input type="number" id="area-floor-count" name="floor_count" min="1" max="50" step="1" value="1" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <p class="mt-1 text-xs text-gray-500">Tầng tự đánh số 1, 2, 3... Không cần đặt tên tầng.</p>
                            </div>

                            <!-- Thêm phòng lồng trong form tạo khu -->
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-gray-800 inline-flex items-center gap-1"><span class="material-symbols-outlined text-base text-primary">meeting_room</span> Phòng của khu mới</p>
                                    <button type="button" id="room-builder-toggle" class="inline-flex items-center gap-1 rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-black transition">
                                        <span class="material-symbols-outlined text-sm">add</span> Thêm phòng
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500">Phòng là tùy chọn — có thể thêm sau bằng nút "Thêm phòng" trên thẻ khu.</p>

                                <div id="room-builder-panel" class="hidden space-y-3 rounded-xl border border-gray-200 bg-white p-4">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Vào tầng *</label>
                                        <select id="nb-floor" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary"></select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Tên phòng *</label>
                                        <input id="nb-name" type="text" placeholder="VD: A101" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">Giá (VNĐ) *</label>
                                            <input id="nb-price" type="number" min="1" step="1000" placeholder="2500000" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">Sức chứa *</label>
                                            <input id="nb-max" type="number" min="1" step="1" value="2" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Diện tích (m2)</label>
                                        <input id="nb-area" type="number" min="0" step="0.1" placeholder="20" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary">
                                    </div>
                                    <button type="button" id="nb-commit" disabled class="w-full rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                                        Thêm phòng vào danh sách
                                    </button>
                                    <p id="nb-hint" class="text-xs text-gray-500">Điền đủ tên phòng, giá > 0 và chọn tầng để nút bật sáng.</p>
                                </div>

                                <div id="nb-list" class="space-y-2"></div>
                                <div id="nb-hidden"></div>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                                <?= $editArea ? 'Cập nhật khu' : 'Tạo khu' ?>
                            </button>
                            <?php if ($editArea): ?>
                            <a href="<?= BASE_URL ?>?page=admin-areas" class="block w-full py-3 text-center text-gray-600 hover:text-primary">Hủy chỉnh sửa</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- ============ DANH SACH KHU ============ -->
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
                        $nextFloor = 1;
                        foreach (($area['floors'] ?? []) as $fTmp) { $nextFloor = max($nextFloor, (int)($fTmp['floor_number'] ?? 0) + 1); }
                        $floorsJson = json_encode(array_map(static function ($f) { return ['id' => (int)($f['id'] ?? 0), 'name' => (string)($f['name'] ?? 'Tầng')]; }, $area['floors'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                                <span class="px-3 py-2 rounded-xl bg-orange-50 text-orange-700 text-sm font-semibold">Bảo trì: <?= (int)($area['maintenance_count'] ?? 0) ?></span>
                            </div>
                        </summary>

                        <div class="px-6 pb-6 border-t border-gray-100 space-y-4">
                            <div class="pt-4 flex flex-wrap gap-3">
                                <a href="<?= BASE_URL ?>?page=admin-areas&edit=<?= $areaIdNow ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">
                                    <span class="material-symbols-outlined text-base">edit</span> Sửa khu
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>?page=admin-add-floor" class="inline">
<?= csrf_field() ?>
                                    <input type="hidden" name="area_id" value="<?= $areaIdNow ?>">
                                    <button type="submit" data-confirm="Thêm Tầng <?= $nextFloor ?> vào khu <?= e($area['name'] ?? '') ?>? Tầng sẽ tự đánh số tiếp theo." class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
                                        <span class="material-symbols-outlined text-base">stairs_2</span> Thêm tầng
                                    </button>
                                </form>
                                <button type="button" data-open-floor-picker data-area-id="<?= $areaIdNow ?>" data-area-name="<?= e($area['name'] ?? '') ?>" data-floors="<?= e($floorsJson) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-100 text-violet-700 font-semibold hover:bg-violet-200 transition">
                                    <span class="material-symbols-outlined text-base">meeting_room</span> Thêm phòng
                                </button>
                                <button type="button" data-open-delete-modal data-area-name="<?= e($area['name'] ?? '') ?>" data-room-count="<?= (int)($area['room_count'] ?? 0) ?>" data-rented-count="<?= (int)($area['rented_count'] ?? 0) ?>" data-delete-url="<?= BASE_URL ?>?page=admin-delete-area&id=<?= $areaIdNow ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-700 font-semibold hover:bg-red-100 transition">
                                    <span class="material-symbols-outlined text-base">delete</span> Xóa khu
                                </button>
                            </div>

                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <h4 class="font-bold text-gray-900 mb-4">Cây tầng của khu</h4>
                                <?php if (empty($area['floors'])): ?>
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-6 text-sm text-gray-500 text-center">Khu này chưa có tầng nào.</div>
                                <?php endif; ?>
                                <div class="space-y-3">
                                    <?php foreach ($area['floors'] as $floor): ?>
                                    <div class="bg-white rounded-2xl border border-gray-100 px-4 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900"><?= e($floor['name'] ?? '') ?></p>
                                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Số tầng: <?= (int)($floor['floor_number'] ?? 0) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="<?= BASE_URL ?>?page=admin-rooms&floor_id=<?= (int)($floor['id'] ?? 0) ?>" class="px-3 py-2 rounded-xl bg-violet-100 text-violet-700 text-sm font-semibold hover:bg-violet-200 transition">Tổng phòng: <?= (int)($floor['room_count'] ?? 0) ?></a>
                                            <span class="px-3 py-2 rounded-xl bg-green-50 text-green-700 text-sm font-semibold">Trống: <?= (int)($floor['available_count'] ?? 0) ?></span>
                                            <span class="px-3 py-2 rounded-xl bg-gray-100 text-gray-700 text-sm font-semibold">Đã thuê: <?= (int)($floor['rented_count'] ?? 0) ?></span>
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

        <!-- Modal xoa khu 3 tang an toan -->
        <div id="delete-area-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div id="dam-blocked" class="hidden space-y-4">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-red-600"><span class="material-symbols-outlined">block</span> Không thể xóa khu</h3>
                    <p id="dam-blocked-reason" class="text-sm text-gray-600"></p>
                    <button type="button" id="dam-back" class="w-full rounded-xl bg-gray-900 px-4 py-2.5 font-semibold text-white hover:bg-black transition">Quay lại!</button>
                </div>
                <div id="dam-simple" class="hidden space-y-4">
                    <h3 class="text-lg font-bold">Xóa khu</h3>
                    <p id="dam-simple-text" class="text-sm text-gray-600"></p>
                    <div class="flex gap-3">
                        <button type="button" class="dam-cancel flex-1 rounded-xl border border-gray-200 px-4 py-2.5 font-semibold text-gray-700 hover:bg-gray-50 transition">Hủy</button>
                        <a id="dam-simple-confirm" href="#" class="flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white hover:bg-red-600 transition">Xác nhận xóa</a>
                    </div>
                </div>
                <div id="dam-typed" class="hidden space-y-4">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-amber-600"><span class="material-symbols-outlined">warning</span> Khu này đã có phòng</h3>
                    <p id="dam-typed-text" class="text-sm text-gray-600"></p>
                    <p class="text-sm text-gray-700">Để xác nhận, hãy nhập chính xác <span id="dam-typed-phrase" class="font-bold text-red-600"></span> vào ô bên dưới.</p>
                    <input id="dam-typed-input" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-red-400 focus:ring-2 focus:ring-red-200" placeholder="Nhập cụm từ xác nhận">
                    <div class="flex gap-3">
                        <button type="button" class="dam-cancel flex-1 rounded-xl border border-gray-200 px-4 py-2.5 font-semibold text-gray-700 hover:bg-gray-50 transition">Hủy</button>
                        <a id="dam-typed-confirm" href="#" class="flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white transition opacity-0 pointer-events-none">Xác nhận xóa</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal chọn tầng để thêm phòng -->
        <div id="floor-picker-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold">Thêm phòng vào khu</h3>
                <p class="mt-1 text-sm text-gray-500" id="floor-picker-area-name"></p>
                <div id="floor-picker-list" class="mt-4 max-h-64 space-y-2 overflow-y-auto"></div>
                <p id="floor-picker-empty" class="hidden mt-4 text-sm text-gray-500">Khu này chưa có tầng nào. Hãy bấm "Thêm tầng" trước.</p>
                <div class="mt-5 flex gap-3">
                    <button type="button" id="floor-picker-cancel" class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 font-semibold text-gray-700 hover:bg-gray-50 transition">Hủy</button>
                    <button type="button" id="floor-picker-confirm" class="flex-1 rounded-xl bg-primary px-4 py-2.5 font-semibold text-white hover:bg-opacity-90 transition">Xác nhận</button>
                </div>
            </div>
        </div>

        <script>
        (() => {
            const uploadUrl = '<?= BASE_URL ?>?page=admin-upload-image';
            const csrfToken = '<?= e(csrf_token()) ?>';
            const areaSlot = <?= $editArea ? '"area_' . (int)($editArea['id'] ?? 0) . '"' : '"area_new"' ?>;

            // ---- Sổ form thêm khu ----
            const toggleBtn = document.getElementById('area-form-toggle');
            const formCard = document.getElementById('area-form-card');
            if (toggleBtn && formCard) {
                toggleBtn.addEventListener('click', () => {
                    const hidden = formCard.classList.toggle('hidden');
                    toggleBtn.innerHTML = hidden
                        ? '<span class="material-symbols-outlined text-base">add_business</span> Thêm khu mới'
                        : '<span class="material-symbols-outlined text-base">close</span> Đóng form';
                });
            }

            // ---- Ảnh khu: preview + upload ----
            const imageInput = document.getElementById('area-image-input');
            const preview = document.getElementById('area-image-preview');
            const placeholder = document.getElementById('area-image-placeholder');
            const fileInput = document.getElementById('area-image-file');
            const uploadLabel = document.getElementById('area-upload-label');
            const refreshPreview = () => {
                if (!imageInput || !preview || !placeholder) { return; }
                const value = imageInput.value.trim();
                if (value === '') { preview.classList.add('hidden'); preview.removeAttribute('src'); placeholder.classList.remove('hidden'); return; }
                preview.src = value; preview.classList.remove('hidden'); placeholder.classList.add('hidden');
            };
            if (imageInput) { imageInput.addEventListener('input', refreshPreview); refreshPreview(); }
            if (fileInput) {
                fileInput.addEventListener('change', async () => {
                    const file = fileInput.files && fileInput.files[0];
                    if (!file) { return; }
                    if (file.size > 5 * 1024 * 1024) { alert('Ảnh vượt quá 5MB.'); fileInput.value = ''; return; }
                    const original = uploadLabel ? uploadLabel.textContent : '';
                    if (uploadLabel) { uploadLabel.textContent = 'Đang tải ảnh lên...'; }
                    try {
                        const fd = new FormData();
                        fd.append('image', file);
                        fd.append('_csrf_token', csrfToken);
                        fd.append('slot', areaSlot);
                        const res = await fetch(uploadUrl, { method: 'POST', body: fd });
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok || !payload.ok) { alert(payload.message || 'Tải ảnh lên thất bại.'); }
                        else if (imageInput) { imageInput.value = payload.url; refreshPreview(); }
                    } catch (err) { alert('Tải ảnh lên thất bại.'); }
                    finally { if (uploadLabel) { uploadLabel.textContent = original; } fileInput.value = ''; }
                });
            }

            // ---- Builder phòng lồng trong form tạo khu ----
            const floorCountInput = document.getElementById('area-floor-count');
            const builderToggle = document.getElementById('room-builder-toggle');
            const builderPanel = document.getElementById('room-builder-panel');
            const nbFloor = document.getElementById('nb-floor');
            const nbName = document.getElementById('nb-name');
            const nbPrice = document.getElementById('nb-price');
            const nbMax = document.getElementById('nb-max');
            const nbArea = document.getElementById('nb-area');
            const nbCommit = document.getElementById('nb-commit');
            const nbList = document.getElementById('nb-list');
            const nbHidden = document.getElementById('nb-hidden');
            let pendingRooms = [];

            const rebuildFloorOptions = () => {
                if (!nbFloor || !floorCountInput) { return; }
                const count = Math.max(1, parseInt(floorCountInput.value, 10) || 1);
                const current = nbFloor.value;
                nbFloor.innerHTML = '';
                for (let i = 1; i <= count; i++) {
                    const opt = document.createElement('option');
                    opt.value = String(i);
                    opt.textContent = 'Tầng ' + i;
                    nbFloor.appendChild(opt);
                }
                nbFloor.value = current && parseInt(current, 10) <= count ? current : '1';
                // Loại phòng đang chờ nằm ngoài số tầng hiện tại
                const before = pendingRooms.length;
                pendingRooms = pendingRooms.filter((r) => r.floor_number <= count);
                if (pendingRooms.length !== before) { renderPending(); }
            };

            const nbValid = () => {
                const name = (nbName ? nbName.value.trim() : '');
                const price = nbPrice ? parseFloat(nbPrice.value) : 0;
                const maxOcc = nbMax ? parseInt(nbMax.value, 10) : 0;
                return name !== '' && price > 0 && maxOcc >= 1 && !!nbFloor && nbFloor.value !== '';
            };
            const refreshCommit = () => { if (nbCommit) { nbCommit.disabled = !nbValid(); } };

            const renderPending = () => {
                if (!nbList || !nbHidden) { return; }
                nbList.innerHTML = '';
                nbHidden.innerHTML = '';
                pendingRooms.forEach((room, idx) => {
                    const chip = document.createElement('div');
                    chip.className = 'flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm';
                    chip.innerHTML = '<span class="font-semibold text-gray-800">Tầng ' + room.floor_number + ' — ' + escapeHtml(room.name) + ' <span class="text-gray-500">(' + Number(room.price).toLocaleString('vi-VN') + 'đ)</span></span>';
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'text-red-600 font-semibold hover:underline';
                    removeBtn.textContent = 'Xóa';
                    removeBtn.addEventListener('click', () => { pendingRooms.splice(idx, 1); renderPending(); });
                    chip.appendChild(removeBtn);
                    nbList.appendChild(chip);

                    ['floor_number', 'name', 'price', 'area', 'max_occupancy', 'description', 'thumbnail'].forEach((key) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'rooms[' + idx + '][' + key + ']';
                        input.value = String(room[key] ?? '');
                        nbHidden.appendChild(input);
                    });
                });
            };

            const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));

            if (builderToggle && builderPanel) {
                builderToggle.addEventListener('click', () => builderPanel.classList.toggle('hidden'));
            }
            if (floorCountInput) { floorCountInput.addEventListener('input', () => { rebuildFloorOptions(); refreshCommit(); }); }
            [nbName, nbPrice, nbMax].forEach((el) => { if (el) { el.addEventListener('input', refreshCommit); } });
            if (nbCommit) {
                nbCommit.addEventListener('click', () => {
                    if (!nbValid()) { return; }
                    pendingRooms.push({
                        floor_number: parseInt(nbFloor.value, 10),
                        name: nbName.value.trim(),
                        price: parseFloat(nbPrice.value),
                        area: nbArea ? (parseFloat(nbArea.value) || 0) : 0,
                        max_occupancy: parseInt(nbMax.value, 10) || 2,
                        description: '',
                        thumbnail: ''
                    });
                    nbName.value = ''; nbPrice.value = ''; nbArea.value = '';
                    renderPending();
                    refreshCommit();
                    nbName.focus();
                });
            }
            rebuildFloorOptions();
            refreshCommit();

            // ---- Modal chọn tầng cho nút "Thêm phòng" trên thẻ khu ----
            const modal = document.getElementById('floor-picker-modal');
            const pickerList = document.getElementById('floor-picker-list');
            const pickerEmpty = document.getElementById('floor-picker-empty');
            const pickerAreaName = document.getElementById('floor-picker-area-name');
            const pickerCancel = document.getElementById('floor-picker-cancel');
            const pickerConfirm = document.getElementById('floor-picker-confirm');
            let pickerAreaId = 0;
            let pickerSelectedFloor = 0;

            document.querySelectorAll('[data-open-floor-picker]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    pickerAreaId = parseInt(btn.dataset.areaId, 10) || 0;
                    pickerAreaName.textContent = 'Khu: ' + (btn.dataset.areaName || '');
                    const floors = JSON.parse(btn.dataset.floors || '[]');
                    pickerList.innerHTML = '';
                    pickerSelectedFloor = 0;
                    if (!floors.length) { pickerEmpty.classList.remove('hidden'); }
                    else {
                        pickerEmpty.classList.add('hidden');
                        floors.forEach((floor, i) => {
                            const label = document.createElement('label');
                            label.className = 'flex items-center gap-3 rounded-xl border border-gray-200 px-3 py-2.5 text-sm cursor-pointer hover:border-primary';
                            label.innerHTML = '<input type="radio" name="picker-floor" value="' + floor.id + '" class="w-4 h-4" ' + (i === 0 ? 'checked' : '') + '> <span class="font-semibold">' + escapeHtml(floor.name) + '</span>';
                            const radio = label.querySelector('input');
                            radio.addEventListener('change', () => { pickerSelectedFloor = parseInt(radio.value, 10); });
                            if (i === 0) { pickerSelectedFloor = floor.id; }
                            pickerList.appendChild(label);
                        });
                    }
                    modal.classList.remove('hidden');
                });
            });
            if (pickerCancel) { pickerCancel.addEventListener('click', () => modal.classList.add('hidden')); }
            if (pickerConfirm) {
                pickerConfirm.addEventListener('click', () => {
                    if (!pickerAreaId || !pickerSelectedFloor) { return; }
                    window.location.href = '<?= BASE_URL ?>?page=admin-rooms&area_id=' + pickerAreaId + '&floor_id=' + pickerSelectedFloor;
                });
            }
            // ---- Modal xoa khu 3 tang ----
            const deleteModal = document.getElementById('delete-area-modal');
            const damBlocked = document.getElementById('dam-blocked');
            const damSimple = document.getElementById('dam-simple');
            const damTyped = document.getElementById('dam-typed');
            let damTimer = null;
            const closeDeleteModal = () => {
                deleteModal.classList.add('hidden');
                if (damTimer) { clearTimeout(damTimer); damTimer = null; }
            };
            document.querySelectorAll('[data-open-delete-modal]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const name = btn.dataset.areaName || '';
                    const rooms = parseInt(btn.dataset.roomCount, 10) || 0;
                    const rented = parseInt(btn.dataset.rentedCount, 10) || 0;
                    const url = btn.dataset.deleteUrl || '#';
                    damBlocked.classList.add('hidden');
                    damSimple.classList.add('hidden');
                    damTyped.classList.add('hidden');

                    if (rented > 0) {
                        document.getElementById('dam-blocked-reason').textContent = 'Khu ' + name + ' không thể xóa. Lý do: Khu ' + name + ' này có phòng vẫn đang hoạt động, không thể xóa.';
                        damBlocked.classList.remove('hidden');
                    } else if (rooms > 0) {
                        const phrase = 'Xác nhận xóa khu ' + name;
                        document.getElementById('dam-typed-text').textContent = 'Khu ' + name + ' hiện có ' + rooms + ' phòng trống. Hành động này sẽ xóa toàn bộ tầng và phòng trống trong khu.';
                        document.getElementById('dam-typed-phrase').textContent = '"' + phrase + '"';
                        const input = document.getElementById('dam-typed-input');
                        const confirmLink = document.getElementById('dam-typed-confirm');
                        input.value = '';
                        confirmLink.className = 'flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white transition opacity-0 pointer-events-none';
                        confirmLink.href = '#';
                        damTyped.classList.remove('hidden');
                        damTimer = setTimeout(() => {
                            confirmLink.className = 'flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white transition opacity-50 pointer-events-none';
                        }, 3000);
                        input.oninput = () => {
                            if (input.value.trim() === phrase) {
                                confirmLink.className = 'flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white hover:bg-red-600 transition opacity-100';
                                confirmLink.href = url;
                            } else {
                                confirmLink.className = 'flex-1 rounded-xl bg-red-500 px-4 py-2.5 text-center font-semibold text-white transition opacity-50 pointer-events-none';
                                confirmLink.href = '#';
                            }
                        };
                    } else {
                        document.getElementById('dam-simple-text').textContent = 'Khu ' + name + ' chưa có phòng. Bạn có chắc chắn muốn xóa khu này?';
                        document.getElementById('dam-simple-confirm').href = url;
                        damSimple.classList.remove('hidden');
                    }
                    deleteModal.classList.remove('hidden');
                });
            });
            document.getElementById('dam-back').addEventListener('click', closeDeleteModal);
            deleteModal.querySelectorAll('.dam-cancel').forEach((b) => b.addEventListener('click', closeDeleteModal));

        })();
        </script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>