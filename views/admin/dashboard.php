<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Tổng quan vận hành khu trọ, KPI phòng/tenant/doanh thu';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$panelPageScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
const areaCtx = document.getElementById("areaOccupancyChart");
if (areaCtx) {
new Chart(areaCtx, {
type: "bar",
data: {
labels: ' . json_encode(array_map(static fn($row) => $row['name'] ?? 'Khu', $areaStats ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',
datasets: [{
label: "Tổng phòng",
data: ' . json_encode(array_map(static fn($row) => (int)($row['total_rooms'] ?? 0), $areaStats ?? [])) . ',
backgroundColor: "#00685f",
borderRadius: 10
}, {
label: "Phòng trống",
data: ' . json_encode(array_map(static fn($row) => (int)($row['available_rooms'] ?? 0), $areaStats ?? [])) . ',
backgroundColor: "#4b41e1",
borderRadius: 10
}]
},
options: {
responsive: true,
maintainAspectRatio: false,
plugins: { legend: { position: "bottom" } },
scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
}
});
}
const revenueCtx = document.getElementById("revenueByMonthChart");
if (revenueCtx) {
new Chart(revenueCtx, {
type: "line",
data: {
labels: ' . json_encode(array_map(static fn($row) => $row['label'] ?? '', $revenueStats['rows'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',
datasets: [{
label: "Doanh thu đã thu",
data: ' . json_encode(array_map(static fn($row) => (float)($row['total_amount'] ?? 0), $revenueStats['rows'] ?? [])) . ',
borderColor: "#00685f",
backgroundColor: "rgba(0, 104, 95, 0.12)",
fill: true,
tension: 0.3,
pointRadius: 4,
pointHoverRadius: 6
}]
},
options: {
responsive: true,
maintainAspectRatio: false,
plugins: { legend: { position: "bottom" } },
scales: { y: { beginAtZero: true } }
}
});
}
});
</script>';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-3xl font-bold">Bảng điều khiển quản trị</h2>
        <p class="text-gray-500 mt-1">Theo dõi nhanh toàn bộ khu, phòng, người thuê và doanh thu dự kiến trước khi đi vào từng module chi tiết.</p>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-7 gap-4">
        <button type="button" data-detail="areas" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-primary/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Tổng khu</p>
            <p class="text-2xl font-bold"><?= (int)($stats['total_areas'] ?? 0) ?></p>
            <p class="text-xs text-primary mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <button type="button" data-detail="rooms" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-primary/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Tổng phòng</p>
            <p class="text-2xl font-bold"><?= (int)($stats['total_rooms'] ?? 0) ?></p>
            <p class="text-xs text-primary mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <button type="button" data-detail="available" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-green-500/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Phòng trống</p>
            <p class="text-2xl font-bold text-green-600"><?= (int)($stats['available_rooms'] ?? 0) ?></p>
            <p class="text-xs text-green-600 mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <button type="button" data-detail="rented" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-blue-500/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Phòng đã thuê</p>
            <p class="text-2xl font-bold text-gray-800"><?= (int)($stats['rented_rooms'] ?? 0) ?></p>
            <p class="text-xs text-blue-600 mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <button type="button" data-detail="draft" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-amber-500/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Phòng chưa có thông tin</p>
            <p class="text-2xl font-bold text-amber-600"><?= (int)($stats['draft_rooms'] ?? 0) ?></p>
            <p class="text-xs text-amber-600 mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <button type="button" data-detail="tenants" class="bg-white rounded-2xl border p-5 text-left cursor-pointer hover:shadow-md hover:border-secondary/50 transition text-center">
            <p class="text-sm text-gray-500 mb-1">Người thuê</p>
            <p class="text-2xl font-bold text-secondary"><?= (int)($stats['total_tenants'] ?? 0) ?></p>
            <p class="text-xs text-secondary mt-1">Bấm để xem chi tiết ▾</p>
        </button>
        <div class="bg-white rounded-2xl border p-5">
            <p class="text-sm text-gray-500 mb-1">Doanh thu dự kiến</p>
            <p class="text-2xl font-bold text-primary"><?= number_format((float)($stats['total_revenue'] ?? 0) / 1000000, 1) ?>M</p>
        </div>
    </div>

    <div id="dashboardDetail" class="hidden space-y-4">

        <div id="detail-areas" data-detail-panel class="hidden bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="font-bold text-lg">Danh sách khu vực <span class="text-sm font-normal text-gray-400">(<?= count($areaStats ?? []) ?>)</span></h3>
                <input type="text" class="detail-search px-4 py-2 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-primary w-full sm:w-64" placeholder="Tìm kiếm tên khu..." data-target="detail-areas-table">
            </div>
            <div class="overflow-x-auto">
                <table id="detail-areas-table" class="w-full min-w-[600px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng phòng</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng trống</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đã thuê</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chưa có thông tin</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lấp đầy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach (($areaStats ?? []) as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-semibold"><?= e($row['name'] ?? '') ?></td>
                            <td class="px-6 py-3"><?= (int)($row['total_rooms'] ?? 0) ?></td>
                            <td class="px-6 py-3 text-green-600 font-semibold"><?= (int)($row['available_rooms'] ?? 0) ?></td>
                            <td class="px-6 py-3"><?= (int)($row['rented_rooms'] ?? 0) ?></td>
                            <td class="px-6 py-3 text-amber-600 font-semibold"><?= (int)($row['draft_rooms'] ?? 0) ?></td>
                            <td class="px-6 py-3"><?= number_format((float)($row['occupancy_rate'] ?? 0), 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($areaStats ?? [])): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">Chưa có khu nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        $roomStatusLabels = [
            'available' => ['Phòng trống', 'bg-green-100 text-green-700'],
            'rented' => ['Đang thuê', 'bg-blue-100 text-blue-700'],
            'draft' => ['Chưa có thông tin', 'bg-amber-100 text-amber-700'],
        ];
        $renderRoomRows = static function ($roomList, $tableId) use ($roomStatusLabels) {
            foreach ($roomList as $room):
                $label = $roomStatusLabels[$room['status'] ?? ''] ?? [($room['status'] ?? 'Chưa rõ'), 'bg-gray-100 text-gray-600'];
        ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3 font-semibold"><?= e($room['name'] ?? '') ?></td>
                <td class="px-6 py-3"><?= e($room['area_name'] ?? ($room['building_name'] ?? 'Chưa phân khu')) ?></td>
                <td class="px-6 py-3"><?= e($room['floor_name'] ?? '') ?></td>
                <td class="px-6 py-3"><span class="text-xs px-3 py-1 rounded-full <?= $label[1] ?>"><?= $label[0] ?></span></td>
                <td class="px-6 py-3"><?= number_format((float)($room['price'] ?? 0)) ?> ₫</td>
                <td class="px-6 py-3"><?= (int)($room['max_occupancy'] ?? 0) ?> người</td>
            </tr>
        <?php
            endforeach;
        };
        $renderRoomTable = static function ($panelId, $title, $searchPlaceholder, $tableId, $roomList, $columns) use ($renderRoomRows) {
        ?>
            <div id="<?= $panelId ?>" data-detail-panel class="hidden bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="font-bold text-lg"><?= $title ?> <span class="text-sm font-normal text-gray-400">(<?= count($roomList) ?>)</span></h3>
                    <input type="text" class="detail-search px-4 py-2 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-primary w-full sm:w-64" placeholder="<?= $searchPlaceholder ?>" data-target="<?= $tableId ?>">
                </div>
                <div class="overflow-x-auto">
                    <table id="<?= $tableId ?>" class="w-full min-w-[650px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên phòng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tầng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giá thuê</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sức chứa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php $renderRoomRows($roomList, $tableId); ?>
                            <?php if (empty($roomList)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">Không có phòng nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
        };
        $renderRoomTable('detail-rooms', 'Danh sách tất cả phòng', 'Tìm kiếm tên phòng...', 'detail-rooms-table', $allRooms ?? [], '');
        $renderRoomTable('detail-available', 'Danh sách phòng trống', 'Tìm kiếm tên phòng...', 'detail-available-table', array_values(array_filter($allRooms ?? [], static fn($r) => ($r['status'] ?? '') === 'available')), '');
        $renderRoomTable('detail-rented', 'Danh sách phòng đang thuê', 'Tìm kiếm tên phòng...', 'detail-rented-table', array_values(array_filter($allRooms ?? [], static fn($r) => ($r['status'] ?? '') === 'rented')), '');
        $renderRoomTable('detail-draft', 'Danh sách phòng chưa có thông tin', 'Tìm kiếm tên phòng...', 'detail-draft-table', array_values(array_filter($allRooms ?? [], static fn($r) => ($r['status'] ?? '') === 'draft')), '');
        ?>

        <div id="detail-tenants" data-detail-panel class="hidden bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="font-bold text-lg">Tài khoản đã thuê phòng <span class="text-sm font-normal text-gray-400">(<?= count($tenantsWithRooms ?? []) ?>)</span></h3>
                <input type="text" class="detail-search px-4 py-2 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-primary w-full sm:w-64" placeholder="Tìm theo tên hoặc số điện thoại..." data-target="detail-tenants-table">
            </div>
            <div class="overflow-x-auto">
                <table id="detail-tenants-table" class="w-full min-w-[650px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên tài khoản</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số điện thoại</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng đang ở</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach (($tenantsWithRooms ?? []) as $tenant): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-semibold"><?= e($tenant['full_name'] ?? '') ?></td>
                            <td class="px-6 py-3"><?= e($tenant['phone'] ?? '') ?></td>
                            <td class="px-6 py-3"><?= e($tenant['email'] ?? '') ?></td>
                            <td class="px-6 py-3"><?= e($tenant['room_name'] ?? 'Chưa rõ') ?></td>
                            <td class="px-6 py-3"><?= e($tenant['building_name'] ?? 'Chưa phân khu') ?></td>
                            <td class="px-6 py-3"><span class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700">Đã thuê phòng</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tenantsWithRooms ?? [])): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">Chưa có tài khoản nào đã thuê phòng.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
<!-- [DEV-QWEN-A][NHOM-3] Stats section -->
<div class="bg-white rounded-2xl border p-6 mt-6">
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
<h3 class="text-xl font-bold">Thống kê theo khu</h3>
<form method="GET" action="<?= BASE_URL ?>" class="flex gap-3">
<input type="hidden" name="page" value="admin">
<select name="area_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary">
<option value="0">Tất cả khu</option>
<?php foreach (($areas ?? []) as $area): ?>
<option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($selectedAreaId ?? 0) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>><?= e($area['name'] ?? '') ?></option>
<?php endforeach; ?>
</select>
<select name="year" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary">
<?php foreach (($revenueStats['available_years'] ?? [(int)date('Y')]) as $year): ?>
<option value="<?= (int)$year ?>" <?= (int)($selectedYear ?? date('Y')) === (int)$year ? 'selected' : '' ?>><?= (int)$year ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
<div class="grid grid-cols-2 xl:grid-cols-7 gap-4 mb-6">
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Khu</p><p class="text-xl font-bold"><?= (int)($statsSummary['tracked_areas'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Tổng phòng</p><p class="text-xl font-bold"><?= (int)($statsSummary['tracked_rooms'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Phòng trống</p><p class="text-xl font-bold text-green-600"><?= (int)($statsSummary['tracked_available_rooms'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Phòng chưa có thông tin</p><p class="text-xl font-bold text-amber-600"><?= (int)($statsSummary['tracked_draft_rooms'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Lấp đầy</p><p class="text-xl font-bold text-primary"><?= number_format((float)($statsSummary['tracked_occupancy_rate'] ?? 0), 1) ?>%</p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Doanh thu năm</p><p class="text-xl font-bold text-emerald-600"><?= number_format((float)($statsSummary['year_total'] ?? 0)) ?> ₫</p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">HĐ đã trả</p><p class="text-xl font-bold text-secondary"><?= (int)($statsSummary['paid_invoice_count'] ?? 0) ?></p></div>
</div>
<?php if (!empty($areaStats)): ?>
<div class="overflow-x-auto mb-6">
<table class="w-full min-w-[700px]">
<thead class="bg-gray-50"><tr>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu</th>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng phòng</th>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trống</th>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đã thuê</th>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng chưa có thông tin</th>
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lấp đầy</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($areaStats as $row): ?>
<tr class="hover:bg-gray-50">
<td class="px-4 py-3 font-semibold"><?= e($row['name'] ?? '') ?></td>
<td class="px-4 py-3"><?= (int)($row['total_rooms'] ?? 0) ?></td>
<td class="px-4 py-3 text-green-600 font-semibold"><?= (int)($row['available_rooms'] ?? 0) ?></td>
<td class="px-4 py-3"><?= (int)($row['rented_rooms'] ?? 0) ?></td>
<td class="px-4 py-3 text-amber-600 font-semibold"><?= (int)($row['draft_rooms'] ?? 0) ?></td>
<td class="px-4 py-3"><div class="flex items-center gap-2"><div class="w-24 bg-gray-200 rounded-full h-2"><div class="bg-primary h-2 rounded-full" style="width:<?= max(0,min(100,(float)($row['occupancy_rate'] ?? 0))) ?>%"></div></div><span class="text-sm font-semibold"><?= number_format((float)($row['occupancy_rate'] ?? 0),1) ?>%</span></div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
<div><h4 class="font-bold mb-3">Phân bổ phòng theo khu</h4><div class="h-[300px]"><canvas id="areaOccupancyChart"></canvas></div></div>
<div><h4 class="font-bold mb-3">Doanh thu theo tháng</h4><div class="h-[300px]"><canvas id="revenueByMonthChart"></canvas></div></div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('dashboardDetail');
    if (!container) return;

    const closeAllPanels = () => {
        container.querySelectorAll('[data-detail-panel]').forEach((p) => p.classList.add('hidden'));
    };

    document.querySelectorAll('[data-detail]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = 'detail-' + btn.dataset.detail;
            const panel = document.getElementById(target);
            if (!panel) return;
            const isOpen = !panel.classList.contains('hidden');
            closeAllPanels();
            if (!isOpen) {
                panel.classList.remove('hidden');
                container.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                const search = panel.querySelector('.detail-search');
                if (search) search.focus();
            }
        });
    });

    document.querySelectorAll('.detail-search').forEach((input) => {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            const table = document.getElementById(input.dataset.target);
            if (!table) return;
            table.querySelectorAll('tbody tr').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
});
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>