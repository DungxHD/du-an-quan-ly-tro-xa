<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'meter-readings';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Nhập chỉ số điện nước theo tháng cho từng phòng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$meterPeriod = $meterData['period'] ?? MeterReadingModel::normalizePeriod(null, null);
$meterRows = $meterData['rows'] ?? [];
$meterServices = $meterData['service_catalog'] ?? [];
$monthValue = (int)($meterPeriod['month'] ?? date('n'));
$yearValue = (int)($meterPeriod['year'] ?? date('Y'));
$formatIndex = static function ($value) {
    if ($value === null || $value === '') {
        return '0';
    }

    $number = (float)$value;
    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Ghi chỉ số điện nước</h2>
            <p class="text-gray-500 mt-2">Bảng nhập liệu hỗ trợ tab qua lại như spreadsheet, tự khóa chỉ số cũ và báo đỏ đúng dòng lỗi thay vì đoán bừa mốc công tơ.</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-end gap-3 shadow-sm">
            <input type="hidden" name="page" value="admin-meter-readings">
            <div>
                <label for="meter-month" class="block text-sm font-semibold mb-2">Tháng</label>
                <select id="meter-month" name="month" class="w-full sm:w-32 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?= $month ?>" <?= $monthValue === $month ? 'selected' : '' ?>>
                        Tháng <?= $month ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="meter-year" class="block text-sm font-semibold mb-2">Năm</label>
                <input
                    id="meter-year"
                    type="number"
                    name="year"
                    min="2000"
                    max="2100"
                    value="<?= $yearValue ?>"
                    class="w-full sm:w-36 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>
            <button type="submit" class="px-5 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition">
                Xem kỳ này
            </button>
        </form>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Kỳ đang nhập</p>
            <p class="text-xl font-bold"><?= e($meterPeriod['label'] ?? '') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Phòng có công tơ</p>
            <p class="text-xl font-bold text-primary"><?= (int)($meterData['room_count'] ?? 0) ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Dòng chỉ số</p>
            <p class="text-xl font-bold text-secondary"><?= (int)($meterData['line_count'] ?? 0) ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Đã lưu kỳ này</p>
            <p class="text-xl font-bold text-green-600"><?= (int)($meterData['completed_count'] ?? 0) ?></p>
        </div>
    </div>

    <?php if (!empty($meterMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($meterMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($meterError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($meterError) ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold">Bảng nhập liệu tháng <?= e($meterPeriod['label'] ?? '') ?></h3>
                <p class="text-sm text-gray-500 mt-1">Ô chỉ số cũ luôn readonly. Nếu tháng trước chưa có dữ liệu hoặc hợp đồng thiếu mốc đầu kỳ, hệ thống sẽ khóa lưu đúng ô đó.</p>
            </div>
            <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600">
                <p class="font-semibold text-slate-900">Mẹo thao tác nhanh</p>
                <p class="mt-1">Dùng phím `Tab` để nhảy ô. Nút "Lưu từng dòng" chỉ lưu riêng phòng đó, còn "Lưu tất cả" sẽ quét toàn bộ ô đã nhập.</p>
            </div>
        </div>

        <?php if (empty($meterServices) || empty($meterRows)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Chưa có phòng hoặc dịch vụ `billing_mode = meter` phù hợp trong kỳ đã chọn.
        </div>
        <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>?page=admin-save-meter-readings" class="space-y-4">
            <input type="hidden" name="month" value="<?= $monthValue ?>">
            <input type="hidden" name="year" value="<?= $yearValue ?>">

            <div class="px-6 pt-4 flex justify-end">
                <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Lưu tất cả
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1280px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase align-bottom">Phòng</th>
                            <th rowspan="2" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase align-bottom">Vị trí</th>
                            <?php foreach ($meterServices as $service): ?>
                            <th colspan="4" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase border-l border-gray-200">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm text-primary"><?= e($service['icon'] ?? 'settings') ?></span>
                                    <span><?= e($service['name'] ?? 'Dịch vụ') ?></span>
                                </div>
                            </th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase align-bottom">Thao tác</th>
                        </tr>
                        <tr>
                            <?php foreach ($meterServices as $service): ?>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase border-l border-gray-200">Chỉ số cũ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chỉ số mới</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tiêu thụ</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thành tiền</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($meterRows as $row): ?>
                        <?php
                        $roomId = (int)($row['room_id'] ?? 0);
                        $roomErrors = $meterRowErrors[$roomId] ?? [];
                        $rowHasError = !empty($roomErrors);
                        ?>
                        <tr class="<?= $rowHasError ? 'bg-red-50/60' : 'hover:bg-gray-50' ?> transition">
                            <td class="px-4 py-4 align-top">
                                <div class="space-y-1">
                                    <p class="font-semibold text-gray-900"><?= e($row['room_name'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500">Đang ở: <?= (int)($row['occupant_count'] ?? 0) ?> người</p>
                                    <?php if ($rowHasError && !empty($roomErrors['_room'])): ?>
                                    <p class="text-xs text-red-600"><?= e($roomErrors['_room']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-medium text-gray-800"><?= e(($row['area_name'] ?? 'Chưa có khu') . ' • ' . ($row['floor_name'] ?? 'Chưa có tầng')) ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Hợp đồng từ <?= e(!empty($row['contract_move_in_date']) ? date('d/m/Y', strtotime((string)$row['contract_move_in_date'])) : 'Chưa rõ') ?>
                                </p>
                            </td>

                            <?php foreach ($meterServices as $service): ?>
                            <?php
                            $serviceId = (int)($service['id'] ?? 0);
                            $cell = $row['cells'][$serviceId] ?? null;
                            $oldInputCell = $meterOldInput[$roomId][$serviceId] ?? [];
                            $displayValue = array_key_exists('new_index', $oldInputCell)
                                ? trim((string)$oldInputCell['new_index'])
                                : (($cell && $cell['new_index'] !== null) ? $formatIndex($cell['new_index']) : '');
                            $cellError = $roomErrors[$serviceId] ?? '';
                            $previewConsumption = null;
                            $previewAmount = null;

                            if ($cell && $displayValue !== '' && is_numeric($displayValue) && $cell['old_index'] !== null) {
                                $resolvedNewIndex = (float)$displayValue;
                                if ($resolvedNewIndex >= (float)$cell['old_index']) {
                                    $previewConsumption = $resolvedNewIndex - (float)$cell['old_index'];
                                    $previewAmount = $previewConsumption * (float)($cell['price'] ?? 0);
                                }
                            } elseif ($cell && $cell['consumption'] !== null) {
                                $previewConsumption = (float)$cell['consumption'];
                                $previewAmount = (float)$cell['amount'];
                            }
                            ?>

                            <?php if (!$cell): ?>
                            <td colspan="4" class="px-4 py-4 align-top border-l border-gray-100">
                                <div class="rounded-xl border border-dashed border-gray-200 px-4 py-5 text-sm text-gray-400 text-center">
                                    Không áp dụng cho phòng này
                                </div>
                            </td>
                            <?php else: ?>
                            <td class="px-4 py-4 align-top border-l border-gray-100">
                                <div class="space-y-2 min-w-[170px]">
                                    <input
                                        type="text"
                                        readonly
                                        value="<?= e($formatIndex($cell['old_index'] ?? 0)) ?>"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 font-semibold"
                                    >
                                    <?php if (!empty($cell['baseline_note'])): ?>
                                    <p class="text-xs <?= !empty($cell['baseline_error']) ? 'text-red-600' : 'text-gray-500' ?>">
                                        <?= e($cell['baseline_note']) ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if (!empty($cell['baseline_error'])): ?>
                                    <p class="text-xs text-red-600"><?= e($cell['baseline_error']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="space-y-2 min-w-[190px]">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        name="readings[<?= $roomId ?>][<?= $serviceId ?>][new_index]"
                                        value="<?= e($displayValue) ?>"
                                        placeholder="Nhập chỉ số mới"
                                        class="w-full px-4 py-3 border rounded-xl outline-none focus:ring-2 <?= $cellError !== '' ? 'border-red-300 bg-red-50 focus:ring-red-200' : 'border-gray-200 focus:ring-primary' ?>"
                                        <?= !empty($cell['baseline_error']) ? 'disabled' : '' ?>
                                    >
                                    <?php if (!empty($cell['has_reading']) && $cellError === ''): ?>
                                    <p class="text-xs text-green-600">Kỳ này đã có bản ghi, lưu lại sẽ cập nhật.</p>
                                    <?php endif; ?>
                                    <?php if ($cellError !== ''): ?>
                                    <p class="text-xs text-red-600"><?= e($cellError) ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="min-w-[150px]">
                                    <?php if ($previewConsumption !== null): ?>
                                    <p class="font-semibold text-gray-900"><?= e($formatIndex($previewConsumption)) ?> <?= e($cell['unit'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= e($formatIndex((float)($cell['old_index'] ?? 0))) ?> → <?= e($displayValue !== '' ? $displayValue : $formatIndex($cell['new_index'] ?? 0)) ?></p>
                                    <?php else: ?>
                                    <p class="text-sm text-gray-400">Chưa có dữ liệu</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="min-w-[190px]">
                                    <?php if ($previewAmount !== null): ?>
                                    <p class="font-semibold text-primary"><?= e($formatMoney($previewAmount)) ?></p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= e($formatIndex($previewConsumption)) ?> <?= e($cell['unit'] ?? '') ?> x <?= e(number_format((float)($cell['price'] ?? 0), 0, ',', '.')) ?>đ
                                    </p>
                                    <?php else: ?>
                                    <p class="text-sm text-gray-400">Chưa tính được</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                            <?php endforeach; ?>

                            <td class="px-4 py-4 align-top">
                                <button
                                    type="submit"
                                    name="save_room_id"
                                    value="<?= $roomId ?>"
                                    class="px-4 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition whitespace-nowrap"
                                >
                                    Lưu từng dòng
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 pb-6 flex justify-end">
                <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Lưu tất cả
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
