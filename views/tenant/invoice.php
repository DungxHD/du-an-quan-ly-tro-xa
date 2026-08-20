<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'invoice';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Xem hóa đơn tháng và trạng thái thanh toán của cả phòng';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$formatMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$formatNumber = static function ($value) {
    $number = (float)$value;
    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
};
$currentPeriod = $period ?? PaymentModel::normalizePeriod(null, null);
$statusMeta = !empty($invoice['status_meta']) ? $invoice['status_meta'] : PaymentModel::getStatusMeta('unpaid');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Hóa đơn tháng</h2>
            <p class="text-gray-600 mt-2">Bạn xem chung một hóa đơn theo phòng. Nếu một người trong phòng đã thanh toán, cả phòng sẽ thấy trạng thái đã trả và nút thanh toán sẽ tự ẩn.</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-end gap-3 shadow-sm">
            <input type="hidden" name="page" value="tenant-invoice">
            <div>
                <label for="tenant-invoice-month" class="block text-sm font-semibold mb-2">Tháng</label>
                <select id="tenant-invoice-month" name="month" class="w-full sm:w-32 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                    <option value="<?= $month ?>" <?= (int)($currentPeriod['month'] ?? date('n')) === $month ? 'selected' : '' ?>>
                        Tháng <?= $month ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="tenant-invoice-year" class="block text-sm font-semibold mb-2">Năm</label>
                <input
                    id="tenant-invoice-year"
                    type="number"
                    name="year"
                    min="2000"
                    max="2100"
                    value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>"
                    class="w-full sm:w-36 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>
            <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                Xem hóa đơn
            </button>
        </form>
    </div>

    <?php if (!empty($tenantInvoiceMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($tenantInvoiceMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($tenantInvoiceError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($tenantInvoiceError) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($user['room_id'])): ?>
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-10 text-center">
        <span class="material-symbols-outlined text-6xl text-gray-300">home_work</span>
        <h3 class="text-2xl font-bold mt-4">Bạn chưa được gán vào phòng nào</h3>
        <p class="text-gray-500 mt-2">Khi có phòng, hệ thống sẽ hiển thị hóa đơn theo đúng phòng bạn đang ở.</p>
    </div>
    <?php elseif (empty($invoice)): ?>
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-10 text-center">
        <span class="material-symbols-outlined text-6xl text-gray-300">receipt_long</span>
        <h3 class="text-2xl font-bold mt-4">Chưa có hóa đơn cho kỳ <?= e($currentPeriod['label'] ?? '') ?></h3>
        <p class="text-gray-500 mt-2">Admin chưa tạo hóa đơn tháng này.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Phòng đang ở</p>
            <p class="text-xl font-bold"><?= e($invoice['room']['name'] ?? '') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Kỳ hóa đơn</p>
            <p class="text-xl font-bold"><?= e($invoice['period_label'] ?? '') ?></p>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Trạng thái</p>
            <span class="inline-flex mt-2 px-3 py-1.5 rounded-full text-sm font-semibold <?= e($statusMeta['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>">
                <?= e($statusMeta['label'] ?? '') ?>
            </span>
        </div>
        <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
            <p class="text-xs text-gray-500">Tổng phải trả</p>
            <p class="text-xl font-bold text-secondary"><?= e($formatMoney($invoice['amount'] ?? 0)) ?></p>
        </div>
    </div>

    <?php if (($invoice['status'] ?? 'unpaid') === 'paid'): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="font-semibold text-green-800">Hóa đơn đã được thanh toán</p>
            <p class="text-sm text-green-700 mt-1">
                Do <?= e($invoice['payer']['full_name'] ?? 'một cư dân trong phòng') ?> thanh toán
                <?php if (!empty($invoice['paid_at'])): ?>
                vào lúc <?= e(date('d/m/Y H:i', strtotime((string)$invoice['paid_at']))) ?>
                <?php endif; ?>.
            </p>
        </div>
        <span class="px-3 py-1.5 rounded-full bg-white text-green-700 text-sm font-semibold border border-green-200">Không cần thanh toán lại</span>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">receipt_long</span>
                    Bảng phân rã hóa đơn
                </h3>
                <p class="text-sm text-gray-500 mt-1">Tất cả dòng tiền được lấy từ snapshot `payment_items`, nên hóa đơn cũ không bị đổi khi admin cập nhật giá dịch vụ về sau.</p>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px]">
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
                            <?php foreach (($invoice['items'] ?? []) as $item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-gray-900"><?= e($item['item_name'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700"><?= e($formatMoney($item['unit_price'] ?? 0)) ?></td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700"><?= e($formatNumber($item['quantity'] ?? 0)) ?></td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700"><?= e($item['billing_mode'] ?? 'fixed') ?></td>
                                <td class="px-4 py-4 align-top font-semibold text-primary"><?= e($formatMoney($item['amount'] ?? 0)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-right font-semibold text-slate-700">Tổng thanh toán</td>
                                <td class="px-4 py-4 font-bold text-secondary"><?= e($formatMoney($invoice['amount'] ?? 0)) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Thông tin phòng</p>
                <p class="text-xl font-bold mt-1"><?= e($invoice['room']['name'] ?? '') ?></p>
                <p class="text-sm text-gray-500 mt-1"><?= e(($invoice['room']['area_name'] ?? '') . ' - ' . ($invoice['room']['floor_name'] ?? '')) ?></p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Cư dân cùng phòng</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach (($invoice['tenants'] ?? []) as $tenant): ?>
                    <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-sm font-medium">
                        <?= e($tenant['full_name'] ?? '') ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Người trả hiện tại</p>
                <p class="text-xl font-bold mt-1"><?= e($invoice['payer']['full_name'] ?? 'Chưa ghi nhận') ?></p>
                <?php if (!empty($invoice['paid_at'])): ?>
                <p class="text-sm text-gray-500 mt-1">Lúc <?= e(date('d/m/Y H:i', strtotime((string)$invoice['paid_at']))) ?></p>
                <?php endif; ?>
            </div>

            <?php if (($invoice['status'] ?? 'unpaid') === 'unpaid'): ?>
            <form method="POST" action="<?= BASE_URL ?>?page=tenant-pay-invoice" class="bg-primary/5 border border-primary/10 rounded-2xl p-5 space-y-4">
<?= csrf_field() ?>
                <input type="hidden" name="payment_id" value="<?= (int)($invoice['id'] ?? 0) ?>">
                <input type="hidden" name="month" value="<?= (int)($currentPeriod['month'] ?? date('n')) ?>">
                <input type="hidden" name="year" value="<?= (int)($currentPeriod['year'] ?? date('Y')) ?>">
                <div>
                    <p class="text-sm text-primary font-semibold">Thanh toán hóa đơn</p>
                    <p class="text-sm text-gray-600 mt-1">Khi bạn bấm thanh toán, toàn bộ phòng sẽ thấy hóa đơn đã trả dưới tên của bạn.</p>
                </div>
                <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Thanh toán ngay
                </button>
            </form>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
