<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'services';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Tất cả dịch vụ áp dụng cho phòng bạn: bắt buộc, gán phòng, và đăng ký thêm';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$formatNumber = static function ($value) {
    $number = (float)$value;
    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Dịch vụ phòng</h2>
            <p class="text-gray-600 mt-2">Mọi dịch vụ phòng bạn đăng ký đều áp cho cả phòng và tính vào hóa đơn của phòng (bất kể ai trong phòng đăng ký).</p>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Phòng đang ở</p>
                <p class="text-lg font-bold"><?= e($room['name'] ?? 'Chưa có phòng') ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Dịch vụ phòng</p>
                <p class="text-lg font-bold text-primary"><?= count($roomServices ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đăng ký được thêm</p>
                <p class="text-lg font-bold text-secondary"><?= count($availableServices ?? []) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($tenantServiceMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($tenantServiceMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($tenantServiceError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($tenantServiceError) ?>
    </div>
    <?php endif; ?>

    <!-- SECTION 1: Dịch vụ phòng (bắt buộc + gán phòng) -->
    <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">meeting_room</span>
                Dịch vụ phòng (bắt buộc & gán phòng)
            </h3>
            <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs rounded-full font-semibold">Tự động áp dụng</span>
            <p class="text-sm text-gray-500 ml-auto">Dịch vụ bắt buộc không thể hủy; dịch vụ gán phòng bạn có thể hủy</p>
        </div>

        <div class="p-6">
            <?php if (empty($roomServices)): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                Phòng này chưa được gán dịch vụ nào ngoài các dịch vụ bắt buộc hệ thống.
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($roomServices as $service): ?>
                <div class="bg-primary/5 border border-primary/10 rounded-3xl p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl"><?= e($service['icon'] ?? 'settings') ?></span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-gray-900"><?= e($service['name'] ?? '') ?></h4>
                                    <?php if ($service['source'] === 'mandatory'): ?>
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full font-semibold">Bắt buộc</span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold">Gán phòng</span>
                                    <?php endif; ?>
                                    <?php if ((int)($service['is_active'] ?? 0) === 0): ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-semibold">Tạm ngừng</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($service['description'] ?? '')) ?></p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Số lượng: <?= (int)($service['quantity'] ?? 1) ?></span>
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">/<?= e($service['unit'] ?? 'đơn vị') ?></span>
<?php if (($service['billing_mode'] ?? '') === 'meter'): ?>
<span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold">Theo chỉ số</span>
<?php elseif (($service['billing_mode'] ?? '') === 'per_person'): ?>
<span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Theo người</span>
<?php elseif (($service['billing_mode'] ?? '') === 'per_unit'): ?>
<span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">Theo số lượng</span>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <?php if ((float)($service['price'] ?? 0) <= 0): ?>
                            <p class="text-lg font-bold text-green-600">Miễn phí</p>
                            <?php else: ?>
                            <p class="text-lg font-bold text-primary"><?= $formatMoney((float)($service['price'] ?? 0)) ?><span class="text-sm text-gray-500">/<?= e($service['unit'] ?? 'tháng') ?></span></p>
                            <p class="text-sm text-gray-500">Tự động tính vào hóa đơn phòng</p>
                            <?php endif; ?>
                            <?php if (($service['source'] ?? '') === 'room_assignment'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-register-service">
<?= csrf_field() ?>
                                <input type="hidden" name="service_id" value="<?= (int)($service['id'] ?? 0) ?>">
                                <input type="hidden" name="service_action" value="cancel">
                                <button type="submit" class="px-4 py-2 rounded-xl border border-red-200 text-red-600 font-semibold hover:bg-red-50 transition">Hủy</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SECTION 2: Dịch vụ có thể đăng ký thêm -->
    <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">add_circle</span>
                Dịch vụ có thể đăng ký thêm cho phòng
            </h3>
            <p class="text-sm text-gray-500 mt-1">Các dịch vụ đang mở mà phòng chưa dùng. Đăng ký sẽ áp cho cả phòng và tính vào hóa đơn của phòng.</p>
        </div>

        <div class="p-6">
            <?php if (empty($availableServices)): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                Hiện không còn dịch vụ nào khả dụng để đăng ký thêm.
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($availableServices as $service): ?>
                <div class="bg-white border border-gray-200 rounded-3xl p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl"><?= e($service['icon'] ?? 'settings') ?></span>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-900"><?= e($service['name'] ?? '') ?></h4>
                                <?php if (($service['applies_to'] ?? '') === 'room'): ?>
                                <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs rounded-full font-semibold">Theo phòng</span>
                                <?php else: ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold">Theo người</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($service['description'] ?? '')) ?></p>

                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <?php if ((float)($service['price'] ?? 0) <= 0): ?>
                                <p class="text-lg font-bold text-green-600">Miễn phí</p>
                                <?php else: ?>
                                <p class="text-lg font-bold text-gray-900"><?= $formatMoney((float)($service['price'] ?? 0)) ?><span class="text-sm text-gray-500">/<?= e($service['unit'] ?? 'đơn vị') ?></span></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>?page=tenant-register-service" class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-end gap-3">
<?= csrf_field() ?>
                        <input type="hidden" name="service_id" value="<?= (int)($service['id'] ?? 0) ?>">
                        <input type="hidden" name="service_action" value="register">
                        <?php if (($service['billing_mode'] ?? '') === 'meter'): ?>
                        <p class="text-sm text-gray-500 sm:mr-auto">Chỉ số sẽ được nhập khi lập hóa đơn.</p>
                        <?php else: ?>
                        <div class="sm:w-40">
                            <label class="block text-sm font-semibold mb-2">Số lượng</label>
                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                value="1"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            >
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="px-5 py-3 bg-secondary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                            Đăng ký
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>