<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'invoices';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Tạo hóa đơn tháng, xem chi tiết và xác nhận thanh toán';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$formatNumber = static function ($value) {
    $number = (float)$value;
    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
$currentPeriod = $period ?? PaymentModel::normalizePeriod(null, null);
$selectedRoomId = (int)($filters['room_id'] ?? 0);
$generatedCount = count($invoiceList ?? []);
$paidCount = count(array_filter($invoiceList ?? [], static fn($invoice) => ($invoice['status'] ?? 'unpaid') === 'paid'));
$unpaidCount = count(array_filter($invoiceList ?? [], static fn($invoice) => ($invoice['status'] ?? 'unpaid') === 'unpaid'));
$readyCount = count(array_filter($invoiceRoomRows ?? [], static fn($row) => !empty($row['can_generate']) && empty($row['existing_payment_id'])));
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Hóa đơn & Thanh toán</h2>
            <p class="text-gray-500 mt-2">Admin có thể preview công thức tính tiền trước khi chốt, tạo hóa đơn theo từng phòng hoặc toàn bộ phòng đang ở và xác nhận thanh toán tiền mặt.</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>" class="bg-white border border-gray-200 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3 shadow-sm">
            <input type="hidden" name="page" value="admin-invoices">
            <div>
                <label for="invoice-month" class="block text-sm font-semibold mb-2">Tháng</label>
                <select id="invoice-month" name="month" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?= $month ?>" <?= (int)($currentPeriod['month'] ?? date('n')) === $month ? 'selected' : '' ?>>
                        Tháng <?= $month ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="invoice-year" class="block text-sm font-semibold mb-2">Năm</label>
                <input
                    id="invoice-year"
                    type="number"
                    name="year"
                    min="2000"
                    max="2100"
                    value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>
            <div>
                <label for="invoice-status" class="block text-sm font-semibold mb-2">Trạng thái</label>
                <select id="invoice-status" name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php foreach ($invoiceStatusOptions as $statusValue => $statusLabel): ?>
                    <option value="<?= e($statusValue) ?>" <?= ($filters['status'] ?? '') === $statusValue ? 'selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="invoice-area" class="block text-sm font-semibold mb-2">Khu</label>
                <select id="invoice-area" name="area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="0">Tất cả khu</option>
                    <?php foreach ($areas as $area): ?>
                    <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($filters['area_id'] ?? 0) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($area['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="invoice-floor" class="block text-sm font-semibold mb-2">Tầng</label>
                <select id="invoice-floor" name="floor_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="0">Tất cả tầng</option>
                    <?php foreach ($filterFloors as $floor): ?>
                    <option value="<?= (int)($floor['id'] ?? 0) ?>" <?= (int)($filters['floor_id'] ?? 0) === (int)($floor['id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e(($floor['name'] ?? 'Tầng') . ' - ' . ($floor['area_name'] ?? '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-5 py-3 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Kỳ đang xem</p>
            <p class="text-xl font-bold"><?= e($currentPeriod['label'] ?? '') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Đã tạo hóa đơn</p>
            <p class="text-xl font-bold text-primary"><?= $generatedCount ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Chưa thanh toán</p>
            <p class="text-xl font-bold text-amber-600"><?= $unpaidCount ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Sẵn sàng tạo</p>
            <p class="text-xl font-bold text-green-600"><?= $readyCount ?></p>
        </div>
    </div>

    <?php if (!empty($invoiceMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($invoiceMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($invoiceError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($invoiceError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
        <section class="2xl:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold">Preview hóa đơn</h3>
                        <p class="text-sm text-gray-500 mt-1">Chọn một phòng để xem trước toàn bộ phân rã trước khi tạo hóa đơn.</p>
                    </div>
                    <?php if (!empty($invoicePreview['existing_payment']['id'])): ?>
                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">Đã tạo</span>
                    <?php endif; ?>
                </div>

                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4">
                    <input type="hidden" name="page" value="admin-invoices">
                    <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                    <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                    <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                    <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                    <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                    <div>
                        <label for="preview-room" class="block text-sm font-semibold mb-2">Phòng cần preview</label>
                        <select id="preview-room" name="room_id" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <option value="0">Chọn phòng</option>
                            <?php foreach ($invoiceRoomRows as $roomRow): ?>
                            <option value="<?= (int)($roomRow['room_id'] ?? 0) ?>" <?= $selectedRoomId === (int)($roomRow['room_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e(($roomRow['room_name'] ?? 'Phòng') . ' - ' . ($roomRow['area_name'] ?? '') . ' - ' . ($roomRow['floor_name'] ?? '')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if (empty($invoicePreview['room'])): ?>
                <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                    Chưa có phòng phù hợp để preview trong kỳ đã chọn.
                </div>
                <?php else: ?>
                <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50 space-y-2">
                    <p class="text-lg font-bold text-gray-900"><?= e($invoicePreview['room']['name'] ?? '') ?></p>
                    <p class="text-sm text-gray-500"><?= e(($invoicePreview['room']['area_name'] ?? 'Chưa có khu') . ' - ' . ($invoicePreview['room']['floor_name'] ?? 'Chưa có tầng')) ?></p>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Kỳ <?= e($currentPeriod['label'] ?? '') ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">Cư dân: <?= count($invoicePreview['tenants'] ?? []) ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">Tổng dự kiến: <?= e($formatMoney($invoicePreview['total_amount'] ?? 0)) ?></span>
                    </div>
                </div>

                <?php if (!empty($invoicePreview['tenants'])): ?>
                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-2">Cư dân đang ở</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($invoicePreview['tenants'] as $tenant): ?>
                        <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-sm font-medium">
                            <?= e($tenant['full_name'] ?? '') ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($invoicePreview['errors'])): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 space-y-2">
                    <p class="font-semibold text-red-700">Không thể tạo hóa đơn</p>
                    <?php foreach ($invoicePreview['errors'] as $error): ?>
                    <p class="text-sm text-red-600">- <?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($invoicePreview['warnings'])): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 space-y-2">
                    <p class="font-semibold text-amber-800">Lưu ý</p>
                    <?php foreach ($invoicePreview['warnings'] as $warning): ?>
                    <p class="text-sm text-amber-700">- <?= e($warning) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($invoicePreview['items'])): ?>
                <div class="overflow-hidden rounded-2xl border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <h4 class="font-semibold text-gray-900">Bảng phân rã</h4>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-white sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khoản thu</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đơn giá</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SL</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($invoicePreview['items'] as $item): ?>
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-gray-900"><?= e($item['item_name'] ?? '') ?></p>
                                        <?php if (!empty($item['note'])): ?>
                                        <p class="text-xs text-gray-500 mt-1"><?= e($item['note']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 align-top text-sm text-gray-700"><?= e($formatMoney($item['unit_price'] ?? 0)) ?></td>
                                    <td class="px-4 py-3 align-top text-sm text-gray-700"><?= e($formatNumber($item['quantity'] ?? 0)) ?></td>
                                    <td class="px-4 py-3 align-top font-semibold text-primary"><?= e($formatMoney($item['amount'] ?? 0)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-right font-semibold text-slate-700">Tổng cộng</td>
                                    <td class="px-4 py-4 font-bold text-secondary"><?= e($formatMoney($invoicePreview['total_amount'] ?? 0)) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-3">
                    <?php if (!empty($invoicePreview['existing_payment']['id'])): ?>
                    <a href="<?= BASE_URL ?>?page=admin-invoices&month=<?= (int)($currentPeriod['month'] ?? date('n')) ?>&year=<?= (int)($currentPeriod['year'] ?? date('Y')) ?>&area_id=<?= (int)($filters['area_id'] ?? 0) ?>&floor_id=<?= (int)($filters['floor_id'] ?? 0) ?>&status=<?= e($filters['status'] ?? '') ?>&room_id=<?= $selectedRoomId ?>&invoice_id=<?= (int)($invoicePreview['existing_payment']['id'] ?? 0) ?>" class="block w-full py-3 text-center bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition">
                        Xem hóa đơn đã tạo
                    </a>
                    <?php elseif (!empty($invoicePreview['can_generate'])): ?>
                    <form method="POST" action="<?= BASE_URL ?>?page=admin-generate-invoice">
                        <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                        <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                        <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                        <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                        <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                        <input type="hidden" name="room_id" value="<?= $selectedRoomId ?>">
                        <input type="hidden" name="generate_scope" value="single">
                        <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                            Tạo hóa đơn cho phòng này
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="2xl:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">Trạng thái tạo hóa đơn theo phòng</h3>
                        <p class="text-sm text-gray-500 mt-1">Phòng đã có hóa đơn sẽ hiện nút xem. Phòng thiếu chỉ số hoặc thiếu dữ liệu sẽ bị khóa tạo.</p>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>?page=admin-generate-invoice">
                        <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                        <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                        <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                        <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                        <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                        <input type="hidden" name="generate_scope" value="all">
                        <button type="submit" class="px-5 py-3 bg-secondary text-white rounded-xl font-semibold hover:bg-opacity-90 transition" <?= empty($invoiceRoomRows) ? 'disabled' : '' ?>>
                            Tạo cho tất cả phòng
                        </button>
                    </form>
                </div>

                <?php if (empty($invoiceRoomRows)): ?>
                <div class="px-6 py-10 text-center text-gray-500">
                    Không có phòng nào đang ở trong kỳ đã chọn.
                </div>
                <?php else: ?>
                <div class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <?php foreach ($invoiceRoomRows as $roomRow): ?>
                    <article class="rounded-2xl border border-gray-200 p-5 bg-white shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-gray-900"><?= e($roomRow['room_name'] ?? '') ?></h4>
                                    <?php if (!empty($roomRow['existing_payment_id'])): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= e($roomRow['existing_payment_meta']['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>">
                                        <?= e($roomRow['existing_payment_meta']['label'] ?? 'Đã tạo') ?>
                                    </span>
                                    <?php elseif (!empty($roomRow['can_generate'])): ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Sẵn sàng tạo</span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Thiếu dữ liệu</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 mt-1"><?= e(($roomRow['area_name'] ?? '') . ' - ' . ($roomRow['floor_name'] ?? '')) ?></p>
                                <p class="text-sm text-gray-500 mt-1">Cư dân: <?= (int)($roomRow['occupant_count'] ?? 0) ?> người</p>
                            </div>
                            <p class="text-lg font-bold text-secondary"><?= e($formatMoney($roomRow['preview_total'] ?? 0)) ?></p>
                        </div>

                        <?php if (!empty($roomRow['tenants'])): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php foreach ($roomRow['tenants'] as $tenant): ?>
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                <?= e($tenant['full_name'] ?? '') ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($roomRow['preview_errors'])): ?>
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3">
                            <?php foreach (array_slice($roomRow['preview_errors'], 0, 3) as $error): ?>
                            <p class="text-sm text-red-600">- <?= e($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-3">
                            <a href="<?= BASE_URL ?>?page=admin-invoices&month=<?= (int)($currentPeriod['month'] ?? date('n')) ?>&year=<?= (int)($currentPeriod['year'] ?? date('Y')) ?>&area_id=<?= (int)($filters['area_id'] ?? 0) ?>&floor_id=<?= (int)($filters['floor_id'] ?? 0) ?>&status=<?= e($filters['status'] ?? '') ?>&room_id=<?= (int)($roomRow['room_id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                Preview
                            </a>

                            <?php if (!empty($roomRow['existing_payment_id'])): ?>
                            <a href="<?= BASE_URL ?>?page=admin-invoices&month=<?= (int)($currentPeriod['month'] ?? date('n')) ?>&year=<?= (int)($currentPeriod['year'] ?? date('Y')) ?>&area_id=<?= (int)($filters['area_id'] ?? 0) ?>&floor_id=<?= (int)($filters['floor_id'] ?? 0) ?>&status=<?= e($filters['status'] ?? '') ?>&room_id=<?= (int)($roomRow['room_id'] ?? 0) ?>&invoice_id=<?= (int)($roomRow['existing_payment_id'] ?? 0) ?>" class="text-slate-900 hover:text-primary font-semibold text-sm">
                                Xem hóa đơn
                            </a>
                            <?php elseif (!empty($roomRow['can_generate'])): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-generate-invoice">
                                <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                                <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                                <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                                <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                                <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                                <input type="hidden" name="room_id" value="<?= (int)($roomRow['room_id'] ?? 0) ?>">
                                <input type="hidden" name="generate_scope" value="single">
                                <button type="submit" class="text-green-600 hover:text-green-800 font-semibold text-sm">
                                    Tạo hóa đơn
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-lg">Danh sách hóa đơn đã tạo</h3>
                    <p class="text-sm text-gray-500 mt-1">Admin có thể lọc theo tháng, trạng thái, khu hoặc tầng. Hóa đơn đã trả sẽ hiện rõ người trả và thời điểm chốt.</p>
                </div>

                <?php if (empty($invoiceList)): ?>
                <div class="px-6 py-10 text-center text-gray-500">
                    Chưa có hóa đơn nào trong kỳ đã chọn.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tổng tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người trả</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chi tiết</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($invoiceList as $invoiceRow): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 align-top">
                                    <p class="font-semibold text-gray-900"><?= e($invoiceRow['room']['name'] ?? '') ?></p>
                                    <p class="text-sm text-gray-500 mt-1"><?= e(($invoiceRow['room']['area_name'] ?? '') . ' - ' . ($invoiceRow['room']['floor_name'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4 align-top font-semibold text-secondary"><?= e($formatMoney($invoiceRow['amount'] ?? 0)) ?></td>
                                <td class="px-6 py-4 align-top">
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= e($invoiceRow['status_meta']['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>">
                                        <?= e($invoiceRow['status_meta']['label'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <?php if (!empty($invoiceRow['payer']['full_name'])): ?>
                                    <p class="font-medium text-gray-900"><?= e($invoiceRow['payer']['full_name'] ?? '') ?></p>
                                    <p class="text-sm text-gray-500 mt-1"><?= e(!empty($invoiceRow['paid_at']) ? date('d/m/Y H:i', strtotime((string)$invoiceRow['paid_at'])) : '') ?></p>
                                    <?php else: ?>
                                    <p class="text-sm text-gray-400">Chưa ghi nhận</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <a href="<?= BASE_URL ?>?page=admin-invoices&month=<?= (int)($currentPeriod['month'] ?? date('n')) ?>&year=<?= (int)($currentPeriod['year'] ?? date('Y')) ?>&status=<?= e($filters['status'] ?? '') ?>&area_id=<?= (int)($filters['area_id'] ?? 0) ?>&floor_id=<?= (int)($filters['floor_id'] ?? 0) ?>&room_id=<?= (int)($invoiceRow['room_id'] ?? 0) ?>&invoice_id=<?= (int)($invoiceRow['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                        Xem chi tiết
                                    </a>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <?php if (($invoiceRow['status'] ?? 'unpaid') === 'unpaid'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>?page=admin-confirm-payment" class="space-y-2">
                                        <input type="hidden" name="payment_id" value="<?= (int)($invoiceRow['id'] ?? 0) ?>">
                                        <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                                        <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                                        <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                                        <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                                        <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                                        <input type="hidden" name="room_id" value="<?= (int)($invoiceRow['room_id'] ?? 0) ?>">
                                        <select name="payer_user_id" class="w-full min-w-[180px] px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                            <option value="0">Chọn người trả</option>
                                            <?php foreach (($invoiceRow['tenants'] ?? []) as $tenant): ?>
                                            <option value="<?= (int)($tenant['id'] ?? 0) ?>">
                                                <?= e($tenant['full_name'] ?? '') ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition text-sm">
                                            Xác nhận đã trả
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-sm font-semibold text-green-600">Đã hoàn tất</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($selectedInvoice)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">Chi tiết hóa đơn #<?= (int)($selectedInvoice['id'] ?? 0) ?></h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= e(($selectedInvoice['room']['name'] ?? '') . ' - ' . ($selectedInvoice['period_label'] ?? '')) ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= e($selectedInvoice['status_meta']['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>">
                            <?= e($selectedInvoice['status_meta']['label'] ?? '') ?>
                        </span>
                        <span class="text-lg font-bold text-secondary"><?= e($formatMoney($selectedInvoice['amount'] ?? 0)) ?></span>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 overflow-x-auto">
                        <table class="w-full min-w-[760px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khoản thu</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đơn giá</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số lượng</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Billing mode</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach (($selectedInvoice['items'] ?? []) as $item): ?>
                                <tr>
                                    <td class="px-4 py-4 font-semibold text-gray-900"><?= e($item['item_name'] ?? '') ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-700"><?= e($formatMoney($item['unit_price'] ?? 0)) ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-700"><?= e($formatNumber($item['quantity'] ?? 0)) ?></td>
                                    <td class="px-4 py-4 text-sm text-gray-700"><?= e($item['billing_mode'] ?? 'fixed') ?></td>
                                    <td class="px-4 py-4 font-semibold text-primary"><?= e($formatMoney($item['amount'] ?? 0)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-right font-semibold text-slate-700">Tổng thanh toán</td>
                                    <td class="px-4 py-4 font-bold text-secondary"><?= e($formatMoney($selectedInvoice['amount'] ?? 0)) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-gray-200 p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Phòng</p>
                            <p class="text-lg font-bold mt-1"><?= e($selectedInvoice['room']['name'] ?? '') ?></p>
                            <p class="text-sm text-gray-500 mt-1"><?= e(($selectedInvoice['room']['area_name'] ?? '') . ' - ' . ($selectedInvoice['room']['floor_name'] ?? '')) ?></p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Người trả</p>
                            <p class="text-lg font-bold mt-1"><?= e($selectedInvoice['payer']['full_name'] ?? 'Chưa thanh toán') ?></p>
                            <?php if (!empty($selectedInvoice['paid_at'])): ?>
                            <p class="text-sm text-gray-500 mt-1">Lúc <?= e(date('d/m/Y H:i', strtotime((string)$selectedInvoice['paid_at']))) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 bg-gray-50">
                            <p class="text-sm text-gray-500">Cư dân cùng phòng</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php foreach (($selectedInvoice['tenants'] ?? []) as $tenant): ?>
                                <span class="px-3 py-1.5 rounded-full bg-white border border-gray-200 text-sm font-medium text-gray-700">
                                    <?= e($tenant['full_name'] ?? '') ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if (($selectedInvoice['status'] ?? 'unpaid') === 'unpaid'): ?>
                        <form method="POST" action="<?= BASE_URL ?>?page=admin-confirm-payment" class="rounded-2xl border border-green-200 bg-green-50 p-4 space-y-3">
                            <input type="hidden" name="payment_id" value="<?= (int)($selectedInvoice['id'] ?? 0) ?>">
                            <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                            <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                            <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                            <input type="hidden" name="area_id" value="<?= (int)($filters['area_id'] ?? 0) ?>">
                            <input type="hidden" name="floor_id" value="<?= (int)($filters['floor_id'] ?? 0) ?>">
                            <input type="hidden" name="room_id" value="<?= (int)($selectedInvoice['room_id'] ?? 0) ?>">
                            <p class="font-semibold text-green-800">Xác nhận thu tiền mặt</p>
                            <select name="payer_user_id" class="w-full px-4 py-3 border border-green-200 rounded-xl focus:ring-2 focus:ring-green-200 outline-none">
                                <option value="0">Chọn tenant đã trả tiền</option>
                                <?php foreach (($selectedInvoice['tenants'] ?? []) as $tenant): ?>
                                <option value="<?= (int)($tenant['id'] ?? 0) ?>">
                                    <?= e($tenant['full_name'] ?? '') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="w-full py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition">
                                Xác nhận đã thanh toán
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
