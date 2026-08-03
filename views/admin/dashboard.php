<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Tổng quan vận hành khu trọ, KPI phòng/tenant/doanh thu';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-3xl font-bold">Bảng điều khiển quản trị</h2>
        <p class="text-gray-500 mt-1">Theo dõi nhanh toàn bộ khu, phòng, người thuê và doanh thu dự kiến trước khi đi vào từng module chi tiết.</p>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Tổng khu</p>
            <p class="text-2xl font-bold"><?= (int)($stats['total_areas'] ?? 0) ?></p>
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

    <div class="bg-white p-6 rounded-2xl shadow-sm border">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h3 class="text-xl font-bold mb-4">Phòng trống mới cập nhật</h3>
                <div class="space-y-3">
                    <?php foreach (($recentRooms ?? []) as $room): ?>
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 p-4">
                        <div>
                            <p class="font-semibold"><?= e($room['name'] ?? '') ?></p>
                            <p class="text-sm text-gray-500"><?= e($room['area_name'] ?? ($room['building_name'] ?? 'Chưa phân khu')) ?></p>
                        </div>
                        <span class="text-primary font-bold"><?= number_format((float)($room['price'] ?? 0)) ?> ₫</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentRooms)): ?>
                    <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                        Chưa có phòng trống để hiển thị nhanh.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Người thuê gần đây</h3>
                <div class="space-y-3">
                    <?php foreach (($recentTenants ?? []) as $tenant): ?>
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 p-4">
                        <div>
                            <p class="font-semibold"><?= e($tenant['full_name'] ?? '') ?></p>
                            <p class="text-sm text-gray-500"><?= e($tenant['email'] ?? '') ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <?= !empty($tenant['room_name']) ? 'Đang ở phòng ' . e($tenant['room_name']) : 'Chưa có phòng được gán' ?>
                            </p>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full <?= !empty($tenant['room_id']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                            <?= !empty($tenant['room_id']) ? 'Đã có phòng' : 'Chờ xếp phòng' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentTenants)): ?>
                    <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                        Chưa có người thuê nào trong hệ thống.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>