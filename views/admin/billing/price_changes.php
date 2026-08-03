<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'price-changes';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý lịch sử đổi giá dịch vụ theo tháng hiệu lực';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$previewService = $priceChangePreviewService ?? null;
$previewOldPrice = (float)($previewService['price'] ?? 0);
$previewNewPrice = (float)($priceChangeForm['new_price'] ?? 0);
$previewMonth = (int)($priceChangeForm['effective_month'] ?? date('n'));
$previewYear = (int)($priceChangeForm['effective_year'] ?? date('Y'));
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Đổi giá dịch vụ</h2>
            <p class="text-gray-500 mt-2">Giá mới được lưu vào lịch sử và chỉ có hiệu lực từ tháng tương lai. Hóa đơn các tháng cũ vẫn đọc giá đúng từ timeline này.</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng dịch vụ</p>
                <p class="text-xl font-bold"><?= count($services ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Dịch vụ đang chọn</p>
                <p class="text-xl font-bold text-primary"><?= e($selectedService['name'] ?? 'Chưa chọn') ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Lịch sử đã lưu</p>
                <p class="text-xl font-bold text-secondary"><?= count($priceChangeHistory ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Giá hiện tại</p>
                <p class="text-xl font-bold text-green-600"><?= e($formatMoney($selectedService['price'] ?? 0)) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($priceChangeMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($priceChangeMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($priceChangeError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($priceChangeError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
        <section class="2xl:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                <div>
                    <h3 class="text-lg font-bold">Lên lịch đổi giá</h3>
                    <p class="text-sm text-gray-500 mt-1">Admin nhập giá mới và chọn tháng/năm bắt đầu áp dụng. Hệ thống tự tạo lịch sử và gửi broadcast cho tenant.</p>
                </div>

                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4">
                    <input type="hidden" name="page" value="admin-price-changes">
                    <div>
                        <label for="price-change-service-preview" class="block text-sm font-semibold mb-2">Dịch vụ đang xem timeline</label>
                        <select id="price-change-service-preview" name="service_id" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <?php foreach ($services as $service): ?>
                            <option value="<?= (int)($service['id'] ?? 0) ?>" <?= (int)($selectedServiceId ?? 0) === (int)($service['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($service['name'] ?? '') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl"><?= e($previewService['icon'] ?? 'price_change') ?></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900"><?= e($previewService['name'] ?? 'Chưa chọn dịch vụ') ?></p>
                            <p class="text-sm text-gray-500"><?= e($formatMoney($previewService['price'] ?? 0)) ?>/<?= e($previewService['unit'] ?? 'tháng') ?></p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-white border border-gray-200 p-4">
                        <p class="text-sm font-semibold text-slate-900">Preview thay đổi</p>
                        <p class="text-sm text-slate-600 mt-2">
                            Giá cũ: <?= e($formatMoney($previewOldPrice)) ?> → Giá mới: <?= e($formatMoney($previewNewPrice)) ?>,
                            áp dụng từ <?= e(str_pad((string)$previewMonth, 2, '0', STR_PAD_LEFT) . '/' . $previewYear) ?>
                        </p>
                    </div>
                </div>

                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-price-change" class="space-y-4">
<?= csrf_field() ?>
                    <div>
                        <label for="price-change-service" class="block text-sm font-semibold mb-2">Chọn dịch vụ</label>
                        <select id="price-change-service" name="service_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <?php foreach ($services as $service): ?>
                            <option value="<?= (int)($service['id'] ?? 0) ?>" <?= (int)($priceChangeForm['service_id'] ?? 0) === (int)($service['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($service['name'] ?? '') ?> - <?= e($formatMoney($service['price'] ?? 0)) ?>/<?= e($service['unit'] ?? 'tháng') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Giá mới</label>
                        <input
                            type="number"
                            min="1"
                            step="0.01"
                            name="new_price"
                            value="<?= e($priceChangeForm['new_price'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Tháng hiệu lực</label>
                            <select name="effective_month" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <?php for ($month = 1; $month <= 12; $month++): ?>
                                <option value="<?= $month ?>" <?= (int)($priceChangeForm['effective_month'] ?? 0) === $month ? 'selected' : '' ?>>
                                    Tháng <?= $month ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Năm hiệu lực</label>
                            <input
                                type="number"
                                min="<?= (int)date('Y') ?>"
                                max="<?= (int)date('Y') + 5 ?>"
                                name="effective_year"
                                value="<?= (int)($priceChangeForm['effective_year'] ?? date('Y')) ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            >
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        Lưu lịch sử đổi giá
                    </button>
                </form>
            </div>
        </section>

        <section class="2xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg">Timeline lịch sử giá</h3>
                <p class="text-sm text-gray-500 mt-1">Timeline này là nguồn sự thật để module hóa đơn xác định giá đúng theo từng tháng.</p>
            </div>

            <?php if (empty($priceChangeHistory)): ?>
            <div class="px-6 py-10 text-center text-gray-500">
                Dịch vụ này chưa có lịch sử đổi giá nào.
            </div>
            <?php else: ?>
            <div class="p-6 space-y-5">
                <?php foreach ($priceChangeHistory as $history): ?>
                <article class="relative pl-8">
                    <span class="absolute left-0 top-1 w-3.5 h-3.5 rounded-full bg-primary"></span>
                    <span class="absolute left-[6px] top-5 bottom-[-24px] w-px bg-gray-200 <?= (int)($history['id'] ?? 0) === (int)($priceChangeHistory[array_key_last($priceChangeHistory)]['id'] ?? 0) ? 'hidden' : '' ?>"></span>
                    <div class="rounded-2xl border border-gray-200 p-5 bg-gray-50">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="material-symbols-outlined text-primary"><?= e($history['service_icon'] ?? 'price_change') ?></span>
                                    <h4 class="font-bold text-gray-900"><?= e($history['service_name'] ?? '') ?></h4>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Hiệu lực từ <?= e(str_pad((string)($history['effective_month'] ?? 0), 2, '0', STR_PAD_LEFT) . '/' . ($history['effective_year'] ?? '')) ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Tạo lúc</p>
                                <p class="font-semibold text-gray-900"><?= e(!empty($history['created_at']) ? date('d/m/Y H:i', strtotime((string)$history['created_at'])) : 'Chưa rõ') ?></p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="rounded-xl bg-white border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Giá cũ</p>
                                <p class="text-lg font-bold text-slate-700 mt-1"><?= e($formatMoney($history['old_price'] ?? 0)) ?></p>
                            </div>
                            <div class="rounded-xl bg-white border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Giá mới</p>
                                <p class="text-lg font-bold text-primary mt-1"><?= e($formatMoney($history['new_price'] ?? 0)) ?></p>
                            </div>
                            <div class="rounded-xl bg-white border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Chênh lệch</p>
                                <p class="text-lg font-bold text-secondary mt-1"><?= e($formatMoney(($history['new_price'] ?? 0) - ($history['old_price'] ?? 0))) ?></p>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
