<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'stats';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Thống kê theo khu và doanh thu tháng bám đúng schema areas, floors, rooms, payments';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$areaChartLabels = array_map(static fn($row) => $row['name'] ?? 'Khu', $areaStats ?? []);
$areaChartTotalRooms = array_map(static fn($row) => (int)($row['total_rooms'] ?? 0), $areaStats ?? []);
$areaChartAvailableRooms = array_map(static fn($row) => (int)($row['available_rooms'] ?? 0), $areaStats ?? []);
$revenueChartLabels = array_map(static fn($row) => $row['label'] ?? '', $revenueStats['rows'] ?? []);
$revenueChartTotals = array_map(static fn($row) => (float)($row['total_amount'] ?? 0), $revenueStats['rows'] ?? []);
$panelPageScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const themeStyles = getComputedStyle(document.documentElement);
    const chartBrand = themeStyles.getPropertyValue("--nta-brand").trim() || "#00685f";
    const chartAccent = themeStyles.getPropertyValue("--nta-secondary").trim() || "#4b41e1";
    const chartInk = themeStyles.getPropertyValue("--nta-ink").trim() || "#17211f";
    const areaCtx = document.getElementById("areaOccupancyChart");
    if (areaCtx) {
        new Chart(areaCtx, {
            type: "bar",
            data: {
                labels: ' . json_encode($areaChartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',
                datasets: [{
                    label: "Tổng phòng",
                    data: ' . json_encode($areaChartTotalRooms) . ',
backgroundColor: chartBrand,
                    borderRadius: 10
                }, {
                    label: "Phòng trống",
                    data: ' . json_encode($areaChartAvailableRooms) . ',
backgroundColor: chartAccent,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
legend: { position: "bottom", labels: { color: chartInk } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: chartInk },
                        grid: { color: chartInk + "1A" }
                    }
                }
            }
        });
    }

    const revenueCtx = document.getElementById("revenueByMonthChart");
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: ' . json_encode($revenueChartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',
                datasets: [{
                    label: "Doanh thu đã thu",
                    data: ' . json_encode($revenueChartTotals) . ',
borderColor: chartBrand,
                    backgroundColor: chartBrand + "1F",
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom" }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Thống kê vận hành</h2>
            <p class="text-gray-500 mt-2">
                Theo dõi mức lấp đầy theo khu và doanh thu đã thu theo tháng trong cùng một màn hình.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>?page=admin" class="px-4 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                Về dashboard
            </a>
            <a href="<?= BASE_URL ?>?page=admin-rooms" class="px-4 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                Quản lý phòng
            </a>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="GET" action="<?= BASE_URL ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="page" value="admin-stats">

            <div>
                <label class="block text-sm font-semibold mb-2">Lọc theo khu</label>
                <select name="area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="0">Tất cả khu</option>
                    <?php foreach (($areas ?? []) as $area): ?>
                    <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($selectedAreaId ?? 0) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($area['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Năm doanh thu</label>
                <select name="year" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php foreach (($revenueStats['available_years'] ?? [(int)date('Y')]) as $year): ?>
                    <option value="<?= (int)$year ?>" <?= (int)($selectedYear ?? date('Y')) === (int)$year ? 'selected' : '' ?>>
                        <?= (int)$year ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Xem thống kê
                </button>
                <a href="<?= BASE_URL ?>?page=admin-stats" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Khu đang xem</p>
            <p class="text-2xl font-bold"><?= (int)($statsSummary['tracked_areas'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Tổng phòng</p>
            <p class="text-2xl font-bold"><?= (int)($statsSummary['tracked_rooms'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Phòng trống</p>
            <p class="text-2xl font-bold text-green-600"><?= (int)($statsSummary['tracked_available_rooms'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Tỷ lệ lấp đầy</p>
            <p class="text-2xl font-bold text-primary"><?= number_format((float)($statsSummary['tracked_occupancy_rate'] ?? 0), 1) ?>%</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Doanh thu năm</p>
            <p class="text-2xl font-bold text-emerald-600"><?= number_format((float)($statsSummary['year_total'] ?? 0)) ?> ₫</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 mb-1">Hóa đơn đã trả</p>
            <p class="text-2xl font-bold text-secondary"><?= (int)($statsSummary['paid_invoice_count'] ?? 0) ?></p>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg">Thống kê theo khu</h3>
            <p class="text-sm text-gray-500 mt-1">
                <?= !empty($selectedArea) ? 'Đang lọc theo khu: ' . e($selectedArea['name'] ?? '') : 'Đang hiển thị tất cả khu trong hệ thống.' ?>
            </p>
        </div>

        <?php if (empty($areaStats)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Không có dữ liệu phòng cho bộ lọc hiện tại.
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tầng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng phòng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng trống</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đã thuê</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tỷ lệ lấp đầy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($areaStats as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold text-gray-900"><?= e($row['name'] ?? '') ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= (int)($row['total_floors'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-gray-700"><?= (int)($row['total_rooms'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-green-600 font-semibold"><?= (int)($row['available_rooms'] ?? 0) ?></td>
                        <td class="px-6 py-4 text-slate-700"><?= (int)($row['rented_rooms'] ?? 0) ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-full max-w-[180px] bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-primary h-2.5 rounded-full" style="width: <?= max(0, min(100, (float)($row['occupancy_rate'] ?? 0))) ?>%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700"><?= number_format((float)($row['occupancy_rate'] ?? 0), 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-lg">Biểu đồ phân bổ phòng theo khu</h3>
                <p class="text-sm text-gray-500 mt-1">So sánh tổng số phòng và số phòng đang trống của từng khu.</p>
            </div>
        </div>
        <div class="h-[340px]">
            <canvas id="areaOccupancyChart"></canvas>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg">Doanh thu theo tháng năm <?= (int)($selectedYear ?? date('Y')) ?></h3>
            <p class="text-sm text-gray-500 mt-1">Chỉ cộng các hóa đơn trong `payments` có trạng thái `paid`.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tháng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Doanh thu</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hóa đơn đã trả</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach (($revenueStats['rows'] ?? []) as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold text-gray-900"><?= e($row['label'] ?? '') ?></td>
                        <td class="px-6 py-4 text-emerald-700 font-semibold"><?= number_format((float)($row['total_amount'] ?? 0)) ?> ₫</td>
                        <td class="px-6 py-4 text-gray-700"><?= (int)($row['paid_invoice_count'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">Tổng năm</td>
                        <td class="px-6 py-4 font-bold text-emerald-700"><?= number_format((float)($revenueStats['year_total'] ?? 0)) ?> ₫</td>
                        <td class="px-6 py-4 font-bold text-gray-900"><?= (int)($revenueStats['paid_invoice_count'] ?? 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="mb-4">
            <h3 class="font-bold text-lg">Biểu đồ doanh thu theo tháng</h3>
            <p class="text-sm text-gray-500 mt-1">Đường doanh thu giúp nhìn nhanh các tháng cao điểm và tháng trống dữ liệu.</p>
        </div>
        <div class="h-[340px]">
            <canvas id="revenueByMonthChart"></canvas>
        </div>
    </section>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
