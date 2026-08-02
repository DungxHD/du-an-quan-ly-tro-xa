<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'contract';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Khai báo thông tin phục vụ hợp đồng';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';

$formatContractDate = static function ($value) {
    $dateValue = trim((string)($value ?? ''));
    if ($dateValue === '') {
        return 'Chưa khai báo';
    }

    $timestamp = strtotime($dateValue);
    return $timestamp ? date('d/m/Y', $timestamp) : $dateValue;
};
?>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-3xl font-bold">Thông tin hợp đồng</h2>
                    <p class="text-gray-500 mt-2">Các trường bên dưới được mã hóa AES trước khi lưu. Bạn có thể cập nhật lại bất kỳ lúc nào.</p>
                </div>
                <a href="<?= BASE_URL ?>?page=tenant-profile"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-base">person</span>
                    Quay lại hồ sơ
                </a>
            </div>

            <?php if (!empty($success)): ?>
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($success) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
                <div class="xl:col-span-3 bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold">Khai báo thông tin</h3>
                        <p class="text-sm text-gray-500 mt-1">CCCD/CMND chỉ chấp nhận 9 hoặc 12 chữ số. Để trống nếu bạn chưa có thông tin tương ứng.</p>
                    </div>

                    <!-- Form hợp đồng tách riêng để backend mã hóa đúng 5 field nhạy cảm, không ảnh hưởng hồ sơ thường. -->
                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Ngày sinh</label>
                            <input type="date" name="date_of_birth"
                                   value="<?= e($user['date_of_birth'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Hộ khẩu thường trú</label>
                            <textarea name="permanent_address" rows="4"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                                      placeholder="Nhập địa chỉ hộ khẩu thường trú"><?= e($user['permanent_address'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Số CCCD/CMND</label>
                            <input type="text" name="identity_number" inputmode="numeric"
                                   value="<?= e($user['identity_number'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                                   placeholder="9 hoặc 12 chữ số">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Ngày cấp</label>
                                <input type="date" name="identity_issue_date"
                                       value="<?= e($user['identity_issue_date'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Nơi cấp</label>
                                <input type="text" name="identity_issue_place"
                                       value="<?= e($user['identity_issue_place'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                                       placeholder="Ví dụ: Cục Cảnh sát QLHC về TTXH">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02]">
                            Lưu thông tin hợp đồng
                        </button>
                    </form>
                </div>

                <div class="xl:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
                    <div class="flex items-start justify-between gap-3 mb-5">
                        <div>
                            <h3 class="text-xl font-bold">Bản xem nhanh</h3>
                            <p class="text-sm text-gray-500 mt-1">Thông tin đã giải mã để bạn tự kiểm tra trước khi in hợp đồng giấy.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">Read-only</span>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Họ và tên</p>
                            <p class="font-semibold text-gray-900"><?= e(fallbackText($user['full_name'] ?? '')) ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Ngày sinh</p>
                            <p class="font-semibold text-gray-900"><?= e($formatContractDate($user['date_of_birth'] ?? '')) ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Hộ khẩu thường trú</p>
                            <p class="font-semibold text-gray-900 whitespace-pre-line"><?= e(fallbackText($user['permanent_address'] ?? '')) ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Số CCCD/CMND</p>
                            <p class="font-semibold text-gray-900"><?= e(fallbackText($user['identity_number'] ?? '')) ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Ngày cấp</p>
                            <p class="font-semibold text-gray-900"><?= e($formatContractDate($user['identity_issue_date'] ?? '')) ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-gray-500 mb-1">Nơi cấp</p>
                            <p class="font-semibold text-gray-900"><?= e(fallbackText($user['identity_issue_place'] ?? '')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
