<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'services';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Dịch vụ đang dùng và dịch vụ có thể đăng ký';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Dịch vụ cá nhân</h2>
            <p class="text-gray-600 mt-2">Bạn có thể tự đăng ký hoặc hủy các dịch vụ áp dụng theo từng người, không ảnh hưởng người ở cùng phòng.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Phòng đang ở</p>
                <p class="text-lg font-bold"><?= e($room['name'] ?? 'Chưa có phòng') ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Dịch vụ đã đăng ký</p>
                <p class="text-lg font-bold text-primary"><?= count($myServices ?? []) ?></p>
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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">check_circle</span>
                    Dịch vụ đang dùng
                </h3>
                <p class="text-sm text-gray-500 mt-1">Mỗi dịch vụ hiển thị đúng số lượng bạn đã đăng ký và thành tiền tương ứng.</p>
            </div>

            <div class="p-6">
                <?php if (empty($myServices)): ?>
                <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                    Bạn chưa đăng ký dịch vụ cá nhân nào.
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($myServices as $service): ?>
                    <div class="bg-primary/5 border border-primary/10 rounded-2xl p-5">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-2xl"><?= e($service['icon'] ?? 'settings') ?></span>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-gray-900"><?= e($service['name'] ?? '') ?></h4>
                                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold">Đã đăng ký</span>
                                        <?php if ((int)($service['is_active'] ?? 0) === 0): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-semibold">Tạm ngừng nhận mới</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($service['description'] ?? '')) ?></p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Số lượng: <?= (int)($service['quantity'] ?? 1) ?></span>
                                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">/<?= e($service['unit'] ?? 'đơn vị') ?></span>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-register-service">
                                <input type="hidden" name="service_id" value="<?= (int)($service['id'] ?? 0) ?>">
                                <input type="hidden" name="service_action" value="cancel">
                                <button type="submit" class="px-4 py-2 rounded-xl border border-red-200 text-red-600 font-semibold hover:bg-red-50 transition">
                                    Hủy
                                </button>
                            </form>
                        </div>

                        <div class="mt-4 pt-4 border-t border-primary/10 flex items-center justify-between">
                            <div>
                                <?php if ((float)($service['price'] ?? 0) <= 0): ?>
                                <p class="text-lg font-bold text-green-600">Miễn phí</p>
                                <?php else: ?>
                                <p class="text-lg font-bold text-primary"><?= number_format((float)($service['line_total'] ?? 0)) ?> ₫</p>
                                <p class="text-sm text-gray-500"><?= number_format((float)($service['price'] ?? 0)) ?> ₫ x <?= (int)($service['quantity'] ?? 1) ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500">Đăng ký riêng cho tài khoản của bạn.</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">add_circle</span>
                    Dịch vụ có thể đăng ký
                </h3>
                <p class="text-sm text-gray-500 mt-1">Chỉ hiển thị dịch vụ đang mở đăng ký và áp dụng theo người.</p>
            </div>

            <div class="p-6">
                <?php if (empty($availableServices)): ?>
                <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                    Hiện không còn dịch vụ cá nhân nào khả dụng để đăng ký thêm.
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($availableServices as $service): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl"><?= e($service['icon'] ?? 'settings') ?></span>
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-gray-900"><?= e($service['name'] ?? '') ?></h4>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-semibold">Theo người</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($service['description'] ?? '')) ?></p>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <?php if ((float)($service['price'] ?? 0) <= 0): ?>
                                    <p class="text-lg font-bold text-green-600">Miễn phí</p>
                                    <?php else: ?>
                                    <p class="text-lg font-bold text-gray-900"><?= number_format((float)($service['price'] ?? 0)) ?> ₫<span class="text-sm text-gray-500">/<?= e($service['unit'] ?? 'đơn vị') ?></span></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>?page=tenant-register-service" class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-end gap-3">
                            <input type="hidden" name="service_id" value="<?= (int)($service['id'] ?? 0) ?>">
                            <input type="hidden" name="service_action" value="register">
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
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
