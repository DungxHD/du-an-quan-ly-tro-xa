<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'meter';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Theo dõi chỉ số điện nước và mức tiêu thụ hàng tháng';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$meterPeriod = $meterSummary['period'] ?? MeterReadingModel::normalizePeriod(null, null);
$meterItems = $meterSummary['items'] ?? [];
$meterHistory = $meterSummary['history'] ?? [];
$meterRoom = $meterSummary['room'] ?? null;
$monthValue = (int)($meterPeriod['month'] ?? date('n'));
$yearValue = (int)($meterPeriod['year'] ?? date('Y'));
$formatIndex = static function ($value) {
    $number = (float)$value;
    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$totalAmount = array_reduce($meterItems, static fn($carry, $item) => $carry + (float)($item['amount'] ?? 0), 0.0);
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Chỉ số điện nước</h2>
            <p class="text-gray-600 mt-2">Xem chỉ số cũ, chỉ số mới, lượng tiêu thụ và tiền tương ứng của phòng bạn theo từng tháng.</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-end gap-3 shadow-sm">
            <input type="hidden" name="page" value="tenant-meter">
            <div>
                <label for="tenant-meter-month" class="block text-sm font-semibold mb-2">Tháng</label>
                <select id="tenant-meter-month" name="month" class="w-full sm:w-32 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?= $month ?>" <?= $monthValue === $month ? 'selected' : '' ?>>
                        Tháng <?= $month ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="tenant-meter-year" class="block text-sm font-semibold mb-2">Năm</label>
                <input
                    id="tenant-meter-year"
                    type="number"
                    name="year"
                    min="2000"
                    max="2100"
                    value="<?= $yearValue ?>"
                    class="w-full sm:w-36 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>
            <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                Xem chỉ số
            </button>
        </form>
    </div>

    <?php if (!$meterRoom): ?>
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-10 text-center">
        <span class="material-symbols-outlined text-6xl text-gray-300">home_work</span>
        <h3 class="text-2xl font-bold mt-4">Bạn chưa được gán vào phòng nào</h3>
        <p class="text-gray-500 mt-2">Khi có phòng, hệ thống sẽ mở màn chỉ số điện nước theo đúng phòng bạn đang ở.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Phòng đang ở</p>
            <p class="text-xl font-bold"><?= e($meterRoom['name'] ?? 'Chưa có phòng') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Kỳ đang xem</p>
            <p class="text-xl font-bold"><?= e($meterPeriod['label'] ?? '') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Dịch vụ có chỉ số</p>
            <p class="text-xl font-bold text-primary"><?= count($meterItems) ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Tổng tiền điện nước</p>
            <p class="text-xl font-bold text-secondary"><?= e($formatMoney($totalAmount)) ?></p>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
                Bảng chỉ số tháng <?= e($meterPeriod['label'] ?? '') ?>
            </h3>
            <p class="text-sm text-gray-500 mt-1">Mỗi dòng hiển thị rõ công thức tính: tiêu thụ x đơn giá = thành tiền.</p>
        </div>

        <div class="p-6">
            <?php if (empty($meterItems)): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center text-gray-500">
                Chưa có chỉ số tháng này.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dịch vụ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chỉ số cũ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chỉ số mới</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tiêu thụ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thành tiền</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Công thức</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($meterItems as $item): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                        <span class="material-symbols-outlined text-2xl"><?= e($item['service_icon'] ?? 'settings') ?></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= e($item['service_name'] ?? '') ?></p>
                                        <?php if (!empty($item['baseline_note'])): ?>
                                        <p class="text-xs text-blue-600 mt-1"><?= e($item['baseline_note']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top font-medium text-gray-800"><?= e($formatIndex($item['old_index'] ?? 0)) ?></td>
                            <td class="px-4 py-4 align-top font-medium text-gray-800"><?= e($formatIndex($item['new_index'] ?? 0)) ?></td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-gray-900"><?= e($formatIndex($item['consumption'] ?? 0)) ?> <?= e($item['unit'] ?? '') ?></p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-gray-900"><?= e($formatMoney($item['price'] ?? 0)) ?></p>
                                <p class="text-xs text-gray-500 mt-1">/<?= e($item['unit'] ?? 'đơn vị') ?></p>
                            </td>
                            <td class="px-4 py-4 align-top font-semibold text-primary"><?= e($formatMoney($item['amount'] ?? 0)) ?></td>
                            <td class="px-4 py-4 align-top">
                                <p class="text-sm text-gray-600"><?= e($item['formula'] ?? '') ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50">
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-right font-semibold text-slate-700">Tổng tiền điện nước</td>
                            <td colspan="2" class="px-4 py-4 font-bold text-secondary text-lg"><?= e($formatMoney($totalAmount)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($meterHistory)): ?>
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">bar_chart</span>
                Biểu đồ tiêu thụ gần đây
            </h3>
            <p class="text-sm text-gray-500 mt-1">Biểu đồ tách riêng từng dịch vụ để tránh cộng dồn các đơn vị đo khác nhau.</p>
        </div>

        <div class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
            <?php foreach ($meterHistory as $historyItem): ?>
            <?php $maxConsumption = max(1, (float)($historyItem['max_consumption'] ?? 0)); ?>
            <div class="rounded-2xl border border-gray-200 p-5 bg-slate-50">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl"><?= e($historyItem['icon'] ?? 'settings') ?></span>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900"><?= e($historyItem['service_name'] ?? '') ?></h4>
                        <p class="text-sm text-gray-500">Đơn vị: <?= e($historyItem['unit'] ?? 'đơn vị') ?></p>
                    </div>
                </div>

                <div class="space-y-3">
                    <?php foreach (($historyItem['points'] ?? []) as $point): ?>
                    <?php $barWidth = max(8, (int)round(((float)($point['consumption'] ?? 0) / $maxConsumption) * 100)); ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700"><?= e($point['label'] ?? '') ?></span>
                            <span class="text-gray-500"><?= e($formatIndex($point['consumption'] ?? 0)) ?> <?= e($historyItem['unit'] ?? '') ?></span>
                        </div>
                        <div class="w-full h-3 rounded-full bg-white border border-gray-200 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary to-secondary" style="width: <?= $barWidth ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
