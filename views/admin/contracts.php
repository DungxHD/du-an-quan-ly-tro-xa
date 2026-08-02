<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'contracts';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý hợp đồng thuê';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';

$formatDate = static function ($value, $default = 'Chưa có dữ liệu') {
    $dateValue = trim((string)($value ?? ''));
    if ($dateValue === '') {
        return $default;
    }

    $timestamp = strtotime($dateValue);
    return $timestamp ? date('d/m/Y', $timestamp) : $dateValue;
};

$formatMoney = static function ($value) {
    return number_format((float)$value, 0, ',', '.') . 'đ';
};

$totalContracts = count($contracts);
$activeContracts = count(array_filter($contracts, static fn($contract) => ($contract['status'] ?? '') === 'active'));
$terminatedContracts = count(array_filter($contracts, static fn($contract) => ($contract['status'] ?? '') === 'terminated'));
?>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900"><?= $selectedContract ? 'Chi tiết Hợp đồng' : 'Quản lý Hợp đồng' ?></h2>
                    <p class="text-gray-500 mt-2">
                        <?= $selectedContract
                            ? 'Thông tin tenant đã được giải mã để admin đối chiếu, in giấy và xử lý kết thúc hợp đồng.'
                            : 'Theo dõi toàn bộ hợp đồng đang hiệu lực hoặc đã kết thúc trên một màn hình tập trung.' ?>
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>?page=admin-tenants"
                       class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                        <span class="material-symbols-outlined text-base">group</span>
                        Sang quản lý tenant
                    </a>
                    <?php if ($selectedContract): ?>
                    <a href="<?= BASE_URL ?>?page=admin-contracts"
                       class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                        <span class="material-symbols-outlined text-base">list_alt</span>
                        Quay lại danh sách
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($contractMessage)): ?>
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($contractMessage) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($contractError)): ?>
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($contractError) ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Tổng hợp đồng</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= (int)$totalContracts ?></p>
                    <p class="text-sm text-gray-500 mt-2">Bao gồm cả hợp đồng đang ở và hợp đồng đã kết thúc.</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Đang hiệu lực</p>
                    <p class="text-3xl font-bold text-primary mt-2"><?= (int)$activeContracts ?></p>
                    <p class="text-sm text-gray-500 mt-2">Có thể tiếp tục theo dõi hoặc kết thúc tại trang chi tiết.</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Đã kết thúc</p>
                    <p class="text-3xl font-bold text-gray-700 mt-2"><?= (int)$terminatedContracts ?></p>
                    <p class="text-sm text-gray-500 mt-2">Dùng làm lịch sử cư trú và tra soát hợp đồng cũ.</p>
                </div>
            </div>

            <?php if ($selectedContract): ?>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden print:shadow-none print:border-0">
                        <div class="p-6 border-b border-gray-100 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-primary uppercase tracking-[0.2em]">Hop dong thue phong</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-2">Mã hợp đồng #<?= (int)($selectedContract['id'] ?? 0) ?></h3>
                                <p class="text-sm text-gray-500 mt-2">Ngày ký: <?= e($formatDate($selectedContract['contract_date'] ?? '')) ?> • Trạng thái:
                                    <span class="<?= ($selectedContract['status'] ?? '') === 'active' ? 'text-green-600' : 'text-gray-600' ?> font-semibold">
                                        <?= ($selectedContract['status'] ?? '') === 'active' ? 'Đang hiệu lực' : 'Đã kết thúc' ?>
                                    </span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-3 print:hidden">
                                <button type="button"
                                        onclick="window.print()"
                                        class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                                    <span class="material-symbols-outlined text-base">print</span>
                                    In hợp đồng
                                </button>
                                <a href="<?= BASE_URL ?>?page=admin-contracts"
                                   class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                                    <span class="material-symbols-outlined text-base">arrow_back</span>
                                    Danh sách
                                </a>
                            </div>
                        </div>

                        <div class="p-6 space-y-8">
                            <section>
                                <h4 class="text-lg font-bold text-gray-900">1. Thông tin bên thuê</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm">
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Họ và tên</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['full_name'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Ngày sinh</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatDate($selectedContract['date_of_birth'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Email</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['email'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Số điện thoại</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['phone'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">CCCD/CMND</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['identity_number'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Ngày cấp / Nơi cấp</p>
                                        <p class="font-semibold text-gray-900 mt-1">
                                            <?= e($formatDate($selectedContract['identity_issue_date'] ?? '')) ?>
                                            <?= !empty($selectedContract['identity_issue_place']) ? ' - ' . e($selectedContract['identity_issue_place']) : '' ?>
                                        </p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 md:col-span-2">
                                        <p class="text-gray-500">Hộ khẩu thường trú</p>
                                        <p class="font-semibold text-gray-900 mt-1 whitespace-pre-line"><?= e(fallbackText($selectedContract['permanent_address'] ?? '')) ?></p>
                                    </div>
                                </div>
                            </section>

                            <section>
                                <h4 class="text-lg font-bold text-gray-900">2. Thông tin phòng và giá trị hợp đồng</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm">
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Phòng thuê</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['room_name'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Khu / Tầng</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['area_name'] ?? '')) ?> • <?= e(fallbackText($selectedContract['floor_name'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Ngày vào ở</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatDate($selectedContract['move_in_date'] ?? '')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Giá thuê snapshot</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatMoney($selectedContract['rent_price'] ?? 0)) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Tiền cọc</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatMoney($selectedContract['deposit_amount'] ?? 0)) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Ngày chuyển đi</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatDate($selectedContract['move_out_date'] ?? '', 'Chưa kết thúc')) ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Chỉ số điện đầu kỳ</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($selectedContract['initial_electricity_index'] !== null ? number_format((float)$selectedContract['initial_electricity_index'], 2, '.', '') : 'Chưa khai báo') ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Chỉ số nước đầu kỳ</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($selectedContract['initial_water_index'] !== null ? number_format((float)$selectedContract['initial_water_index'], 2, '.', '') : 'Chưa khai báo') ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                        <p class="text-gray-500">Giá niêm yết phòng hiện tại</p>
                                        <p class="font-semibold text-gray-900 mt-1"><?= e($formatMoney($selectedContract['room_price'] ?? 0)) ?></p>
                                    </div>
                                </div>
                            </section>

                            <section>
                                <h4 class="text-lg font-bold text-gray-900">3. Ghi chú quản trị</h4>
                                <div class="p-5 rounded-2xl bg-gray-50 border border-gray-100 text-sm text-gray-600 leading-7">
                                    Hợp đồng này lưu theo cơ chế snapshot để đảm bảo giá thuê, tiền cọc và chỉ số đầu kỳ không bị thay đổi khi dữ liệu phòng thay đổi về sau.
                                    Khi tenant chuyển đi, admin cần kết thúc hợp đồng để hệ thống trả `users.room_id = NULL` và đồng bộ lại trạng thái phòng.
                                </div>
                            </section>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 print:hidden">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Tác vụ nhanh</h3>
                                <p class="text-sm text-gray-500 mt-1">Dùng khi tenant chuyển đi hoặc cần in giấy ngay.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full <?= ($selectedContract['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?> text-xs font-semibold">
                                <?= ($selectedContract['status'] ?? '') === 'active' ? 'Active' : 'Terminated' ?>
                            </span>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                <p class="text-gray-500">Tenant</p>
                                <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['full_name'] ?? '')) ?></p>
                            </div>
                            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                                <p class="text-gray-500">Phòng</p>
                                <p class="font-semibold text-gray-900 mt-1"><?= e(fallbackText($selectedContract['room_name'] ?? '')) ?></p>
                            </div>
                        </div>

                        <?php if (($selectedContract['status'] ?? '') === 'active'): ?>
                        <form method="POST"
                              action="<?= BASE_URL ?>?page=admin-terminate-contract&id=<?= (int)($selectedContract['id'] ?? 0) ?>"
                              class="mt-5 space-y-4"
                              onsubmit="return confirm('Xác nhận kết thúc hợp đồng này và giải phóng phòng?');">
                            <!-- Form tách riêng để admin luôn nhập rõ ngày chuyển đi trước khi hệ thống cập nhật các bảng liên quan. -->
                            <input type="hidden" name="contract_id" value="<?= (int)($selectedContract['id'] ?? 0) ?>">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Ngày chuyển đi</label>
                                <input type="date"
                                       name="move_out_date"
                                       value="<?= e($terminationForm['move_out_date'] ?? date('Y-m-d')) ?>"
                                       required
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition">
                                <span class="material-symbols-outlined text-base">logout</span>
                                Kết thúc hợp đồng
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="mt-5 p-4 rounded-2xl bg-gray-50 border border-gray-100 text-sm text-gray-600">
                            Hợp đồng đã kết thúc vào ngày <?= e($formatDate($selectedContract['move_out_date'] ?? '', 'Không rõ')) ?>.
                        </div>
                        <?php endif; ?>
                    </section>

                    <section class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 print:hidden">
                        <h3 class="text-lg font-bold text-gray-900">Checklist in giấy</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">Đối chiếu họ tên, CCCD/CMND và địa chỉ thường trú sau khi giải mã.</div>
                            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">Kiểm tra snapshot giá thuê và tiền cọc khớp với thỏa thuận thực tế.</div>
                            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">Ghi rõ chỉ số điện/nước đầu kỳ để chốt tháng đầu không sai lệch.</div>
                        </div>
                    </section>
                </div>
            </div>
            <?php endif; ?>

            <section class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden print:hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Danh sách hợp đồng</h3>
                        <p class="text-sm text-gray-500 mt-1">Bấm "Xem" để mở chi tiết, in giấy hoặc kết thúc hợp đồng.</p>
                    </div>
                    <span class="text-sm text-gray-500">Tổng <?= (int)$totalContracts ?> bản ghi</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã HĐ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tenant</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày vào</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày ra</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giá thuê</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($contracts as $contract): ?>
                            <tr class="hover:bg-gray-50 transition <?= $selectedContract && (int)($selectedContract['id'] ?? 0) === (int)($contract['id'] ?? 0) ? 'bg-primary/5' : '' ?>">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">#<?= (int)($contract['id'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Ký <?= e($formatDate($contract['contract_date'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900"><?= e(fallbackText($contract['full_name'] ?? '')) ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= e(fallbackText($contract['email'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900"><?= e(fallbackText($contract['room_name'] ?? '')) ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= e(fallbackText($contract['area_name'] ?? '')) ?> • <?= e(fallbackText($contract['floor_name'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= e($formatDate($contract['move_in_date'] ?? '')) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= e($formatDate($contract['move_out_date'] ?? '', 'Chưa kết thúc')) ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?= e($formatMoney($contract['rent_price'] ?? 0)) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold <?= ($contract['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                        <span class="material-symbols-outlined text-sm"><?= ($contract['status'] ?? '') === 'active' ? 'check_circle' : 'history' ?></span>
                                        <?= ($contract['status'] ?? '') === 'active' ? 'Đang hiệu lực' : 'Đã kết thúc' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= BASE_URL ?>?page=admin-view-contract&id=<?= (int)($contract['id'] ?? 0) ?>"
                                       class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        Xem
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($contracts)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    Chưa có hợp đồng nào trong hệ thống.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
