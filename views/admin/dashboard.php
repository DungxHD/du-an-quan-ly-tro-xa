<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Tổng quan vận hành và cấu hình giao diện';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="max-w-6xl mx-auto">
    <!-- Landing page admin: ưu tiên tổng quan nhanh trước, cấu hình giao diện sau. -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold">Bảng điều khiển quản trị</h2>
            <p class="text-gray-500 mt-1">Theo dõi nhanh dữ liệu vận hành và chỉnh giao diện ngay trong cùng một trang.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>?page=admin-buildings" class="px-4 py-2 bg-white border rounded-xl font-semibold hover:border-primary hover:text-primary">Khu / Tòa</a>
            <a href="<?= BASE_URL ?>?page=admin-rooms" class="px-4 py-2 bg-white border rounded-xl font-semibold hover:border-primary hover:text-primary">Phòng</a>
            <a href="<?= BASE_URL ?>?page=admin-tenants" class="px-4 py-2 bg-white border rounded-xl font-semibold hover:border-primary hover:text-primary">Người thuê</a>
            <a href="<?= BASE_URL ?>?page=admin-stats" class="px-4 py-2 bg-primary text-white rounded-xl font-semibold">Thống kê</a>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Khu / Tòa</p>
            <p class="text-2xl font-bold"><?= (int)($stats['total_buildings'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Tổng phòng</p>
            <p class="text-2xl font-bold"><?= (int)($stats['total_rooms'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Phòng trống</p>
            <p class="text-2xl font-bold text-green-600"><?= (int)($stats['available_rooms'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Phòng đã thuê</p>
            <p class="text-2xl font-bold text-gray-800"><?= (int)($stats['rented_rooms'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Người thuê</p>
            <p class="text-2xl font-bold text-secondary"><?= (int)($stats['total_tenants'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Doanh thu dự kiến</p>
            <p class="text-2xl font-bold text-primary"><?= number_format((float)($stats['total_revenue'] ?? 0) / 1000000, 1) ?>M</p>
        </div>
    </div>
    
    <?php if (isset($_GET['saved'])): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        Đã lưu thành công! Thay đổi sẽ hiển thị ngay trên trang chủ.
    </div>
    <?php endif; ?>
    
    <form method="POST" action="<?= BASE_URL ?>?page=admin-save-settings" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-xl font-bold mb-4">Phòng trống mới cập nhật</h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentRooms ?? [], 0, 3) as $room): ?>
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 p-4">
                            <div>
                                <p class="font-semibold"><?= e($room['name']) ?></p>
                                <p class="text-sm text-gray-500"><?= e($room['building_name'] ?? 'Chưa phân khu') ?></p>
                            </div>
                            <span class="text-primary font-bold"><?= number_format((float)$room['price']) ?> ₫</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Người thuê gần đây</h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice(array_filter($recentTenants ?? [], static fn($item) => (int)($item['role'] ?? 0) === 0), 0, 3) as $tenant): ?>
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 p-4">
                            <div>
                                <p class="font-semibold"><?= e($tenant['full_name']) ?></p>
                                <p class="text-sm text-gray-500"><?= e($tenant['email']) ?></p>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full <?= !empty($tenant['room_name']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= !empty($tenant['room_name']) ? 'Đã có phòng' : 'Chờ xếp phòng' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thương hiệu -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">storefront</span>
                Thương hiệu
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($brandSettings as $s): ?>
                <?php $label = ucwords(str_replace('_', ' ', $s['setting_key'])); ?>
                <div>
                    <label class="block text-sm font-semibold mb-2"><?= e($label) ?></label>
                    <input type="text" name="setting_<?= e($s['setting_key']) ?>" value="<?= e($s['setting_value']) ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Hero Banner -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">image</span>
                Hero Banner
            </h3>
            <div class="space-y-4">
                <?php foreach ($heroSettings as $s): ?>
                <?php $label = ucwords(str_replace('_', ' ', $s['setting_key'])); ?>
                <div>
                    <label class="block text-sm font-semibold mb-2"><?= e($label) ?></label>
                    <?php if ($s['setting_key'] === 'hero_subheadline' || $s['setting_key'] === 'site_description'): ?>
                    <textarea name="setting_<?= e($s['setting_key']) ?>" rows="2" 
                              class="w-full px-4 py-2 border rounded-lg"><?= e($s['setting_value']) ?></textarea>
                    <?php else: ?>
                    <input type="<?= $s['setting_key'] === 'hero_image' ? 'url' : 'text' ?>" 
                           name="setting_<?= e($s['setting_key']) ?>" value="<?= e($s['setting_value']) ?>" 
                           class="w-full px-4 py-2 border rounded-lg">
                    <?php endif; ?>
                    <?php if ($s['setting_key'] === 'hero_image'): ?>
                    <img src="<?= e($s['setting_value']) ?>" class="mt-2 w-64 h-32 object-cover rounded-lg border">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Liên hệ -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">contact_phone</span>
                Thông tin liên hệ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($contactSettings as $s): ?>
                <?php $label = ucwords(str_replace('_', ' ', $s['setting_key'])); ?>
                <div>
                    <label class="block text-sm font-semibold mb-2"><?= e($label) ?></label>
                    <?php if ($s['setting_key'] === 'contact_address'): ?>
                    <textarea name="setting_<?= e($s['setting_key']) ?>" rows="2" 
                              class="w-full px-4 py-2 border rounded-lg"><?= e($s['setting_value']) ?></textarea>
                    <?php else: ?>
                    <input type="text" name="setting_<?= e($s['setting_key']) ?>" value="<?= e($s['setting_value']) ?>" 
                           class="w-full px-4 py-2 border rounded-lg">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-bold hover:bg-opacity-90 transition">
            Lưu tất cả thay đổi
        </button>
    </form>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
