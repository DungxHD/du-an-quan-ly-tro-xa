<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'stats';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Thống kê nhanh theo từng khu nhà';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$chartLabels = array_map(static fn($building) => $building['name'], $buildings);
$chartRoomCounts = array_map(static fn($building) => (int)$building['room_count'], $buildings);
$chartAvailableCounts = array_map(static fn($building) => (int)$building['available_count'], $buildings);
$panelPageScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById("buildingChart");
if (ctx) {
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ' . json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',
            datasets: [{
                label: "Tổng phòng",
                data: ' . json_encode($chartRoomCounts) . ',
                backgroundColor: "#00685f",
                borderRadius: 12
            }, {
                label: "Phòng trống",
                data: ' . json_encode($chartAvailableCounts) . ',
                backgroundColor: "#4b41e1",
                borderRadius: 12
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}
</script>';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <h2 class="text-3xl font-bold mb-6">Thống kê chi tiết</h2>
        
        <!-- Stats by Building -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <h3 class="font-bold text-lg mb-4">Thống kê theo Tòa nhà</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tòa/Khu</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng phòng</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng trống</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tỷ lệ lấp đầy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($buildings as $b): 
                            $occupancy = $b['room_count'] > 0 ? round(($b['room_count'] - $b['available_count']) / $b['room_count'] * 100, 1) : 0;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($b['name']) ?></td>
                            <td class="px-6 py-4"><?= $b['room_count'] ?></td>
                            <td class="px-6 py-4 text-green-600"><?= $b['available_count'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-xs">
                                        <div class="bg-primary h-2 rounded-full" style="width: <?= $occupancy ?>%"></div>
                                    </div>
                                    <span class="text-sm font-semibold"><?= $occupancy ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4">Biểu đồ phân bổ phòng</h3>
            <canvas id="buildingChart" height="100"></canvas>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
