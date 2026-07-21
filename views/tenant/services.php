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
        <h2 class="text-3xl font-bold mb-2">Quản lý dịch vụ</h2>
        <p class="text-gray-600 mb-6">Đăng ký thêm hoặc xem các dịch vụ bạn đang sử dụng</p>
        
        <!-- Current Services -->
        <?php if (!empty($myServices)): ?>
        <div class="mb-8">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                Dịch vụ đang sử dụng (<?= count($myServices) ?>)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($myServices as $s): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-2 border-primary/20 card-hover">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl"><?= e($s['icon']) ?></span>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold">Đã đăng ký</span>
                    </div>
                    <h4 class="font-bold mb-1"><?= e($s['name']) ?></h4>
                    <p class="text-sm text-gray-500 mb-3"><?= e(fallbackText($s['description'] ?? '')) ?></p>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <p class="text-primary font-bold"><?= number_format($s['price'] * $s['quantity']) ?> ₫</p>
                        <?php if ($s['quantity'] > 1): ?>
                        <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full">x<?= $s['quantity'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Available Services -->
        <div>
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">add_circle</span>
                Dịch vụ có thể đăng ký thêm
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($allServices as $s): 
                    $isRegistered = in_array($s['id'], $myServiceIds);
                ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 card-hover <?= $isRegistered ? 'opacity-60' : '' ?>">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 bg-secondary/10 text-secondary rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl"><?= e($s['icon']) ?></span>
                        </div>
                    </div>
                    <h4 class="font-bold mb-1"><?= e($s['name']) ?></h4>
                    <p class="text-sm text-gray-500 mb-3"><?= e(fallbackText($s['description'] ?? '')) ?></p>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <p class="font-bold text-lg"><?= number_format($s['price']) ?> ₫<span class="text-xs text-gray-500">/<?= $s['unit'] ?></span></p>
                        <?php if ($isRegistered): ?>
                        <span class="px-4 py-2 bg-gray-100 text-gray-500 rounded-lg text-sm">Đã đăng ký</span>
                        <?php else: ?>
                        <form method="POST" action="<?= BASE_URL ?>?page=tenant-register-service">
                            <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="px-4 py-2 bg-secondary text-white rounded-lg text-sm font-semibold hover:bg-opacity-90 transition">
                                Đăng ký
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
