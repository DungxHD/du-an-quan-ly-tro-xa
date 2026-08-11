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
<div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Khu</p><p class="text-xl font-bold"><?= (int)($statsSummary['tracked_areas'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Tổng phòng</p><p class="text-xl font-bold"><?= (int)($statsSummary['tracked_rooms'] ?? 0) ?></p></div>
<div class="bg-surface rounded-xl p-4"><p class="text-xs text-gray-500">Phòng trống</p><p class="text-xl font-bold text-green-600"><?= (int)($statsSummary['tracked_available_rooms'] ?? 0) ?></p></div>
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
<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lấp đầy</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($areaStats as $row): ?>
<tr class="hover:bg-gray-50">
<td class="px-4 py-3 font-semibold"><?= e($row['name'] ?? '') ?></td>
<td class="px-4 py-3"><?= (int)($row['total_rooms'] ?? 0) ?></td>
<td class="px-4 py-3 text-green-600 font-semibold"><?= (int)($row['available_rooms'] ?? 0) ?></td>
<td class="px-4 py-3"><?= (int)($row['rented_rooms'] ?? 0) ?></td>
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
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>