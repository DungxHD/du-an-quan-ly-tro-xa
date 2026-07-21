<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Trang theo dõi phòng ở và chi phí hàng tháng';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$supportPhone = RoomModel::getSetting('contact_phone', 'Chưa có dữ liệu');
$supportEmail = RoomModel::getSetting('contact_email', 'Chưa có dữ liệu');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <?php if ($room): ?>
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-primary to-secondary p-6 rounded-2xl shadow-lg mb-6 text-white">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Xin chào, <?= e($user['full_name']) ?>!</h2>
                    <p class="opacity-90">Bạn đang ở: <strong><?= e($room['name']) ?></strong> - <?= e(fallbackText($room['building_name'] ?? '')) ?></p>
                </div>
                <div class="bg-white/20 backdrop-blur-md px-4 py-2 rounded-xl">
                    <p class="text-xs opacity-90">Tiền phòng tháng này</p>
                    <p class="text-2xl font-bold"><?= number_format($totalBill/1000000, 2) ?>M ₫</p>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <span class="material-symbols-outlined text-primary text-3xl mb-2">home</span>
                <p class="text-gray-500 text-sm mb-1">Phòng của bạn</p>
                    <p class="font-bold"><?= e($room['name']) ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <span class="material-symbols-outlined text-secondary text-3xl mb-2">square_foot</span>
                <p class="text-gray-500 text-sm mb-1">Diện tích</p>
                <p class="font-bold"><?= $room['area'] ?> m²</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <span class="material-symbols-outlined text-blue-500 text-3xl mb-2">room_service</span>
                <p class="text-gray-500 text-sm mb-1">Dịch vụ đã đăng ký</p>
                <p class="font-bold"><?= count($services) ?> dịch vụ</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <span class="material-symbols-outlined text-orange-500 text-3xl mb-2">payments</span>
                <p class="text-gray-500 text-sm mb-1">Phí dịch vụ</p>
                <p class="font-bold text-orange-500"><?= number_format($serviceCost) ?> ₫</p>
            </div>
        </div>
        
        <!-- Bill Details -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
                Chi tiết hóa đơn tháng này
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span>Tiền phòng cơ bản</span>
                    <span class="font-semibold"><?= number_format($room['price']) ?> ₫</span>
                </div>
                <?php foreach ($services as $s): ?>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm"><?= e($s['icon']) ?></span>
                        <?= e($s['name']) ?> 
                        <?php if ($s['quantity'] > 1): ?>
                        <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full">x<?= $s['quantity'] ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="font-semibold"><?= number_format($s['price'] * $s['quantity']) ?> ₫</span>
                </div>
                <?php endforeach; ?>
                <div class="flex justify-between items-center pt-4 text-xl font-bold">
                    <span>Tổng cộng</span>
                    <span class="text-primary"><?= number_format($totalBill) ?> ₫</span>
                </div>
            </div>
            <button class="w-full mt-6 py-3 bg-primary text-white rounded-xl font-bold hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">payments</span>
                Xác nhận đã thanh toán
            </button>
        </div>
        
        <!-- Room Info -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4">Thông tin phòng</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <img src="<?= e($room['thumbnail']) ?>" class="w-full rounded-xl aspect-video object-cover" alt="<?= e($room['name']) ?>">
                </div>
                <div>
                    <h4 class="font-bold text-xl mb-2"><?= e($room['name']) ?></h4>
                    <p class="text-sm text-gray-600 mb-4"><?= e(fallbackText($room['description'] ?? '')) ?></p>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">square_foot</span> <?= $room['area'] ?>m²</div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">person</span> Max <?= $room['max_occupancy'] ?> người</div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">layers</span> Tầng <?= $room['floor'] ?></div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">apartment</span> <?= e(fallbackText($room['building_name'] ?? '')) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- No room assigned -->
        <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">home_work</span>
            <h2 class="text-2xl font-bold mb-2">Bạn chưa được gán vào phòng nào</h2>
            <p class="text-gray-500 mb-6">Vui lòng liên hệ chủ trọ để được gán vào phòng của bạn.</p>
            <div class="bg-blue-50 p-4 rounded-xl text-sm text-gray-600 max-w-md mx-auto">
                <p class="font-semibold mb-2">Liên hệ hỗ trợ:</p>
                <p>Hotline: <?= e($supportPhone) ?></p>
                <p>Email: <?= e($supportEmail) ?></p>
            </div>
        </div>
        <?php endif; ?>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
