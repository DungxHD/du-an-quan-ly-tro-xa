<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'meter-readings';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = "Nhập chỉ số công tơ và tạo hóa đơn hàng loạt cho từng phòng";
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
<h2 class="text-3xl font-bold">Hóa đơn</h2>
    <p class="text-gray-500 mt-2">Nhập chỉ số mới cho dịch vụ tính theo chỉ số (chỉ số cũ mặc định 0, khóa cứng và có thể mở khóa chỉnh sửa). Điền đủ chỉ số cho một phòng sẽ làm sáng nút Tạo hóa đơn của phòng đó.</p>
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
            <div>
                <label for="meter-area-filter" class="block text-sm font-semibold mb-2">Khu</label>
                <select id="meter-area-filter" name="area_id" class="w-full sm:w-44 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả khu</option>
                    <?php foreach (($areas ?? []) as $area): ?>
                    <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($areaId ?? 0) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($area['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="meter-floor-filter" class="block text-sm font-semibold mb-2">Tầng</label>
                <select id="meter-floor-filter" name="floor_id" class="w-full sm:w-44 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả tầng</option>
                    <?php foreach (($filterFloors ?? []) as $floor): ?>
                    <option value="<?= (int)($floor['id'] ?? 0) ?>" <?= (int)($floorId ?? 0) === (int)($floor['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($floor['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="meter-search-filter" class="block text-sm font-semibold mb-2">Tìm kiếm</label>
                <input
                    id="meter-search-filter"
                    type="text"
                    name="search"
                    value="<?= e(trim((string)($search ?? ''))) ?>"
                    placeholder="Tên phòng..."
                    class="w-full sm:w-52 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
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

    <?php $canBulkCreate = ((int)($meterData["line_count"] ?? 0) > 0) && ((int)($meterData["completed_count"] ?? 0) === (int)($meterData["line_count"] ?? 0)); ?>
    <div class="flex justify-end">
        <form method="POST" action="<?= BASE_URL ?>?page=admin-generate-invoice" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="month" value="<?= $monthValue ?>">
            <input type="hidden" name="year" value="<?= $yearValue ?>">
            <input type="hidden" name="generate_scope" value="all">
            <input type="hidden" name="redirect_page" value="admin-meter-readings">
            <?php if ($canBulkCreate): ?>
                <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    Tạo hóa đơn hàng loạt
                </button>
            <?php else: ?>
                <button type="button" disabled class="px-5 py-3 bg-gray-200 text-gray-400 rounded-xl font-semibold cursor-not-allowed inline-flex items-center gap-2" title="Cần điền đủ chỉ số cho tất cả dòng trong kỳ">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    Tạo hóa đơn hàng loạt
                </button>
            <?php endif; ?>
        </form>
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
                <p class="text-sm text-gray-500 mt-1">Chỉ số cũ luôn khóa. Bấm "Chỉnh sửa" để mở khóa, sửa xong bấm "Xác nhận" để khóa lại. Khi chưa có mốc chỉ số, hệ thống mặc định bắt đầu từ 0.</p>
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
<?= csrf_field() ?>
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
$rawNewIndex = null;
if (array_key_exists("new_index", $oldInputCell) && trim((string)$oldInputCell["new_index"]) !== "") {
    $rawNewIndex = (float)$oldInputCell["new_index"];
} elseif ($cell && $cell["new_index"] !== null) {
    $rawNewIndex = (float)$cell["new_index"];
}
if ($cell && $rawNewIndex !== null && $cell["old_index"] !== null) {
    if ($rawNewIndex >= (float)$cell["old_index"]) {
        $previewConsumption = $rawNewIndex - (float)$cell["old_index"];
        $previewAmount = $previewConsumption * (float)($cell["price"] ?? 0);
    }
} elseif ($cell && $cell["consumption"] !== null) {
    $previewConsumption = (float)$cell["consumption"];
    $previewAmount = (float)$cell["amount"];
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
                                    <div class="flex gap-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="readings[<?= $roomId ?>][<?= $serviceId ?>][old_index]"
                                            value="<?= e(array_key_exists('old_index', $oldInputCell) ? trim((string)$oldInputCell['old_index']) : $formatIndex($cell['old_index'] ?? 0)) ?>"
                                            readonly
                                            data-old-index-input="<?= $roomId ?>-<?= $serviceId ?>"
                                            class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 font-semibold outline-none <?= $cellError !== '' ? 'border-red-300 bg-red-50 focus:ring-red-200' : '' ?>"
                                        >
                                        <input type="hidden" name="readings[<?= $roomId ?>][<?= $serviceId ?>][old_index_editable]" value="1">
                                        <button
                                            type="button"
                                            data-toggle-old-index="<?= $roomId ?>-<?= $serviceId ?>"
                                            class="shrink-0 px-3 py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold hover:bg-gray-100 transition text-xs whitespace-nowrap"
                                            title="Mở khóa để chỉnh sửa chỉ số cũ"
                                        >Chỉnh sửa</button>
                                    </div>
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
                                        data-new-index-input="<?= $roomId ?>"
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
                                <div class="flex flex-col gap-2 min-w-[150px]">
                                    <button
                                        type="submit"
                                        name="save_room_id"
                                        value="<?= $roomId ?>"
                                        class="px-4 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition whitespace-nowrap"
                                    >
                                        Lưu từng dòng
                                    </button>
                                    <?php $roomHasInvoice = !empty($row['existing_payment_id']); ?>
                                    <button
                                        type="submit"
                                        name="generate_room_id"
                                        value="<?= $roomId ?>"
                                        data-generate-invoice="<?= $roomId ?>"
                                        data-has-invoice="<?= $roomHasInvoice ? '1' : '0' ?>"
                                        <?= $roomHasInvoice ? 'disabled' : 'disabled' ?>
                                        class="px-4 py-3 rounded-xl font-semibold transition whitespace-nowrap inline-flex items-center justify-center gap-1 <?= $roomHasInvoice ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-200 text-gray-400 cursor-not-allowed' ?>"
                                        title="<?= $roomHasInvoice ? 'Phòng này đã có hóa đơn trong kỳ' : 'Điền đủ chỉ số cho phòng để tạo hóa đơn' ?>"
                                    >
                                        <span class="material-symbols-outlined text-base">receipt_long</span>
                                        Tạo hóa đơn
                                    </button>
                                </div>
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

        <?php $pagination = $meterData['pagination'] ?? null; ?>
        <?php if ($pagination && (int)($pagination['total_pages'] ?? 1) > 1): ?>
        <?php
        $buildPageUrl = static function (int $pageNumber) use ($monthValue, $yearValue, $search, $areaId, $floorId) {
            $params = [
                'page' => 'admin-meter-readings',
                'month' => $monthValue,
                'year' => $yearValue,
                'search' => trim((string)($search ?? '')),
                'area_id' => (int)($areaId ?? 0) > 0 ? (int)$areaId : '',
                'floor_id' => (int)($floorId ?? 0) > 0 ? (int)$floorId : '',
            ];
            if ($pageNumber > 1) {
                $params['page'] = 'admin-meter-readings';
                $params['p'] = $pageNumber;
            }
            $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
            return BASE_URL . '?' . http_build_query($params);
        };
        ?>
        <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-2">
            <?php $currentPage = (int)($pagination['current_page'] ?? 1); ?>
            <?php $totalPages = (int)($pagination['total_pages'] ?? 1); ?>
            <?php if ($currentPage > 1): ?>
            <a href="<?= e($buildPageUrl(1)) ?>" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition" title="Trang đầu">&laquo;</a>
            <a href="<?= e($buildPageUrl($currentPage - 1)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">&lsaquo; Trước</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $currentPage): ?>
            <span class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold shadow-sm"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= e($buildPageUrl($i)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="<?= e($buildPageUrl($currentPage + 1)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">Sau &rsaquo;</a>
            <a href="<?= e($buildPageUrl($totalPages)) ?>" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition" title="Trang cuối">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<!-- [DEV-QWEN-A][NHOM-3] Invoice History Section -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mt-6">
<div class="px-6 py-4 border-b border-gray-100 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
<div>
<h3 class="font-bold text-lg">Lịch sử hóa đơn</h3>
<p class="text-sm text-gray-500 mt-1">Danh sách hóa đơn đã tạo cho kỳ <?= e($meterPeriod['label'] ?? '') ?></p>
</div>
<form method="POST" action="<?= BASE_URL ?>?page=admin-generate-invoice">
<?= csrf_field() ?>
<input type="hidden" name="month" value="<?= (int)($meterPeriod['month'] ?? date('n')) ?>">
<input type="hidden" name="year" value="<?= (int)($meterPeriod['year'] ?? date('Y')) ?>">
<input type="hidden" name="generate_scope" value="all">
<input type="hidden" name="redirect_page" value="admin-meter-readings">
<button type="submit" class="px-5 py-3 bg-secondary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">Tạo hóa đơn hàng loạt</button>
</form>
</div>
<?php if (empty($invoiceList ?? [])): ?>
<div class="px-6 py-10 text-center text-gray-500">Chưa có hóa đơn nào cho kỳ này.</div>
<?php else: ?>
<div class="overflow-x-auto">
<table class="w-full min-w-[800px]">
<thead class="bg-gray-50"><tr>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng tiền</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người trả</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($invoiceList as $invoiceRow): ?>
<tr class="hover:bg-gray-50 transition">
<td class="px-6 py-4 align-top">
<p class="font-semibold text-gray-900"><?= e($invoiceRow['room']['name'] ?? '') ?></p>
<p class="text-sm text-gray-500 mt-1"><?= e(($invoiceRow['room']['area_name'] ?? '') . ' - ' . ($invoiceRow['room']['floor_name'] ?? '')) ?></p>
</td>
<td class="px-6 py-4 align-top font-semibold text-secondary"><?= number_format((float)($invoiceRow['amount'] ?? 0), 0, ',', '.') ?> đ</td>
<td class="px-6 py-4 align-top">
<span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= e($invoiceRow['status_meta']['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>"><?= e($invoiceRow['status_meta']['label'] ?? '') ?></span>
</td>
<td class="px-6 py-4 align-top">
<?php if (!empty($invoiceRow['payer']['full_name'])): ?>
<p class="font-medium text-gray-900"><?= e($invoiceRow['payer']['full_name'] ?? '') ?></p>
<p class="text-sm text-gray-500 mt-1"><?= e(!empty($invoiceRow['paid_at']) ? date('d/m/Y H:i', strtotime((string)$invoiceRow['paid_at'])) : '') ?></p>
<?php else: ?><p class="text-sm text-gray-400">Chưa ghi nhận</p><?php endif; ?>
</td>
<td class="px-6 py-4 align-top">
<?php if (($invoiceRow['status'] ?? 'unpaid') === 'unpaid'): ?>
<form method="POST" action="<?= BASE_URL ?>?page=admin-confirm-payment" class="space-y-2">
<?= csrf_field() ?>
<input type="hidden" name="payment_id" value="<?= (int)($invoiceRow['id'] ?? 0) ?>">
<input type="hidden" name="month" value="<?= (int)($meterPeriod['month'] ?? date('n')) ?>">
<input type="hidden" name="year" value="<?= (int)($meterPeriod['year'] ?? date('Y')) ?>">
<input type="hidden" name="redirect_page" value="admin-meter-readings">
<select name="payer_user_id" class="w-full min-w-[160px] px-3 py-2 border border-gray-200 rounded-xl text-sm outline-none">
<option value="0">Chọn người trả</option>
<?php foreach (($invoiceRow['tenants'] ?? []) as $tenant): ?>
<option value="<?= (int)($tenant['id'] ?? 0) ?>"><?= e($tenant['full_name'] ?? '') ?></option>
<?php endforeach; ?>
</select>
<button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition text-sm">Xác nhận đã trả</button>
</form>
<?php else: ?><span class="text-sm font-semibold text-green-600">Đã hoàn tất</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<script>
(function() {
    // 1. Khóa / mở khóa chỉ số cũ: mặc định khóa, nút Chỉnh sửa mở khóa, Xác nhận khóa lại.
    document.querySelectorAll('[data-toggle-old-index]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = this.getAttribute('data-toggle-old-index');
            var input = document.querySelector('[data-old-index-input="' + key + '"]');
            if (!input) return;
            var unlocking = input.readOnly;
            input.readOnly = !unlocking;
            if (unlocking) {
                input.classList.remove('bg-gray-100', 'text-gray-700', 'font-semibold');
                input.classList.add('bg-white', 'text-gray-900', 'border-amber-300');
                this.textContent = 'Xác nhận';
                this.classList.add('bg-amber-100', 'border-amber-300', 'text-amber-700');
                this.removeAttribute('title');
                input.focus();
            } else {
                input.classList.add('bg-gray-100', 'text-gray-700', 'font-semibold');
                input.classList.remove('bg-white', 'text-gray-900', 'border-amber-300');
                this.textContent = 'Chỉnh sửa';
                this.classList.remove('bg-amber-100', 'border-amber-300', 'text-amber-700');
                this.setAttribute('title', 'Mở khóa để chỉnh sửa chỉ số cũ');
            }
        });
    });

    // 2. Nút "Tạo hóa đơn" chỉ sáng khi phòng đã điền đủ chỉ số mới và chưa có hóa đơn trong kỳ.
    var generateButtons = Array.prototype.slice.call(document.querySelectorAll('[data-generate-invoice]'));
    if (generateButtons.length) {
        function updateGenerateButton(btn, roomId) {
            if (btn.getAttribute('data-has-invoice') === '1') return;
            var inputs = document.querySelectorAll('[data-new-index-input="' + roomId + '"]');
            var allFilled = inputs.length > 0;
            inputs.forEach(function(inp) {
                if (inp.value.trim() === '') allFilled = false;
            });
            if (allFilled) {
                btn.disabled = false;
                btn.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btn.classList.add('bg-primary', 'text-white', 'hover:bg-opacity-90', 'cursor-pointer');
                btn.setAttribute('title', 'Điền đủ chỉ số, bấm để tạo hóa đơn cho phòng này');
            } else {
                btn.disabled = true;
                btn.classList.add('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btn.classList.remove('bg-primary', 'text-white', 'hover:bg-opacity-90', 'cursor-pointer');
                btn.setAttribute('title', 'Điền đủ chỉ số cho phòng để tạo hóa đơn');
            }
        }
        var roomIds = [];
        generateButtons.forEach(function(btn) {
            var roomId = btn.getAttribute('data-generate-invoice');
            if (roomIds.indexOf(roomId) === -1) roomIds.push(roomId);
        });
        roomIds.forEach(function(roomId) {
            var btn = document.querySelector('[data-generate-invoice="' + roomId + '"]');
            if (!btn) return;
            updateGenerateButton(btn, roomId);
            document.querySelectorAll('[data-new-index-input="' + roomId + '"]').forEach(function(inp) {
                inp.addEventListener('input', function() { updateGenerateButton(btn, roomId); });
                inp.addEventListener('change', function() { updateGenerateButton(btn, roomId); });
            });
        });
    }

    // 3. Chọn khu → cập nhật danh sách tầng tương ứng.
    var areaSelect = document.getElementById('meter-area-filter');
    var floorSelect = document.getElementById('meter-floor-filter');
    if (areaSelect && floorSelect) {
        var allFloors = <?= json_encode($allFloors ?? [], JSON_UNESCAPED_UNICODE) ?> || [];
        function renderFloorOptions(areaId, keepSelected) {
            var current = keepSelected ? floorSelect.value : '';
            floorSelect.innerHTML = '<option value="">Tất cả tầng</option>';
            allFloors.forEach(function(floor) {
                if (areaId !== '' && String(floor.area_id) !== String(areaId)) return;
                var opt = document.createElement('option');
                opt.value = String(floor.id);
                opt.textContent = floor.name || '';
                if (String(floor.id) === String(current)) opt.selected = true;
                floorSelect.appendChild(opt);
            });
        }
        areaSelect.addEventListener('change', function() {
            renderFloorOptions(this.value, false);
        });
        renderFloorOptions(areaSelect.value, true);
    }
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
