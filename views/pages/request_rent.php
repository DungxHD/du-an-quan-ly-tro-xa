<?php
$room = $room ?? [];
$pendingRequest = $pendingRequest ?? null;
$message = $message ?? '';
$error = $error ?? '';
$contactPhone = RoomModel::getSetting('contact_phone', '');
$phoneTel = preg_replace('/\s+/', '', (string)$contactPhone);
$isPendingThisRoom = !empty($pendingRequest) && (int)($pendingRequest['room_id'] ?? 0) === (int)($room['id'] ?? 0);
$isPendingOtherRoom = !empty($pendingRequest) && !$isPendingThisRoom;
$pendingRoomName = '';
if ($isPendingOtherRoom) {
    $pendingRoom = RoomModel::getById((int)($pendingRequest['room_id'] ?? 0));
    $pendingRoomName = $pendingRoom['name'] ?? '';
}
?>
<section class="rent-request-page py-12 bg-surface min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($room['id'] ?? 0) ?>" class="inline-flex items-center gap-2 text-primary hover:gap-3 transition-all mb-6">
            <span class="material-symbols-outlined">arrow_back</span> Quay lại trang phòng
        </a>

        <div class="rent-request-heading bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-2xl font-bold">Yêu cầu thuê phòng</h2>
            <p class="mt-1 text-gray-600"><?= e($room['name'] ?? '') ?> · <?= number_format(((float)($room['price'] ?? 0)) / 1000000, 1) ?>M/tháng</p>
        </div>

        <?php if ($message !== ''): ?>
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($isPendingThisRoom): ?>
            <?php
            $payConfirmed = (string)($pendingRequest['payment_status'] ?? 'pending') === 'confirmed';
            $qrBank = RoomModel::getSetting('bank_name', 'Vietcombank');
            $qrAccount = RoomModel::getSetting('bank_account_number', '');
            $qrHolder = RoomModel::getSetting('bank_account_holder', '');
            $qrAmount = (float)($pendingRequest['deposit'] ?? 0) > 0 ? (float)$pendingRequest['deposit'] : (float)($room['price'] ?? 0);
            $qrText = 'Chuyen khoan thue phong ' . ($room['name'] ?? '')
                . ' - So tien: ' . number_format($qrAmount, 0, ',', '.') . ' VND'
                . ' - Ngan hang: ' . $qrBank
                . ' - STK: ' . $qrAccount
                . ' - Chu TK: ' . $qrHolder;
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 text-center">
                <?php if ($payConfirmed): ?>
                <span class="material-symbols-outlined text-5xl text-sky-500">qr_code_2</span>
                <h3 class="mt-3 text-lg font-bold">Yêu cầu của bạn đã được admin chấp nhận</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Vui lòng thanh toán tiền cọc <span class="font-bold text-sky-700"><?= number_format($qrAmount, 0, ',', '.') ?>đ</span>
                    để giữ phòng "<?= e($room['name'] ?? '') ?>" cho bạn cho đến hết ngày dự kiến vào ở
                    (<?= date('d/m/Y', strtotime((string)($pendingRequest['move_in_date'] ?? ''))) ?>).
                    Mã QR thanh toán bên dưới.
                </p>
                <?php else: ?>
                <span class="material-symbols-outlined text-5xl text-amber-500">hourglass_top</span>
                <h3 class="mt-3 text-lg font-bold">Yêu cầu đang chờ xét duyệt</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Yêu cầu thuê phòng "<?= e($room['name'] ?? '') ?>" đã được gửi, vui lòng chờ admin xét duyệt.
                    Admin có thể phản hồi qua thông báo hoặc liên hệ trực tiếp số điện thoại của bạn — hãy chú ý phản hồi từ admin.
                </p>
                <?php endif; ?>
                <?php if ($payConfirmed): ?>
                <div class="mt-5 mx-auto max-w-sm rounded-2xl border border-sky-200 bg-sky-50 p-5">
                    <p class="text-sm font-bold text-sky-800">Quét mã QR để chuyển tiền cọc</p>
                    <p class="mt-1 text-xs text-sky-700">Số tiền cọc: <span class="font-bold"><?= number_format($qrAmount, 0, ',', '.') ?>đ</span></p>
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= e(urlencode($qrText)) ?>"
                        alt="Mã QR chuyển tiền cọc"
                        class="mx-auto mt-3 w-44 h-44 rounded-xl bg-white p-2"
                        loading="lazy"
                    >
                    <p class="mt-3 text-xs text-sky-800">
                        <?= e($qrBank) ?> · STK: <?= e($qrAccount) ?><br>
                        Chủ TK: <?= e($qrHolder) ?>
                    </p>
                    <form method="POST" action="<?= BASE_URL ?>?page=tenant-paid-rent-request" class="mt-4" onsubmit="return confirm('Xác nhận bạn đã thanh toán tiền cọc thành công? Bạn sẽ được xếp vào phòng ngay lập tức.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int)($pendingRequest['id'] ?? 0) ?>">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 transition">
                            Tôi đã thanh toán tiền cọc
                        </button>
                    </form>
                    <p class="mt-2 text-[11px] text-sky-700">Sau khi xác nhận, bạn sẽ được chuyển vào phòng ngay. Admin sẽ nhận được thông báo thanh toán thành công.</p>
                </div>
                <?php endif; ?>
                <div class="mt-5 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= BASE_URL ?>?page=rooms" class="px-5 py-2.5 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">Quay lại</a>
                    <?php if ($phoneTel !== ''): ?>
                    <a href="tel:<?= e($phoneTel) ?>" class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">Liên hệ ngay với admin</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php if ($isPendingOtherRoom): ?>
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
                <p class="font-semibold">Bạn đang có yêu cầu thuê phòng "<?= e($pendingRoomName) ?>" đang chờ xét duyệt.</p>
                <p class="mt-1 text-sm">Nếu muốn đổi sang phòng hiện tại, hãy hủy yêu cầu trước đó bên dưới rồi tạo yêu cầu mới.</p>
                <form method="POST" action="<?= BASE_URL ?>?page=cancel-rent-request" class="mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= (int)($pendingRequest['id'] ?? 0) ?>">
                    <input type="hidden" name="room_id" value="<?= (int)($room['id'] ?? 0) ?>">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition">
                        Hủy yêu cầu thuê phòng "<?= e($pendingRoomName) ?>"
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>?page=submit-rent-request&id=<?= (int)($room['id'] ?? 0) ?>" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-semibold mb-2">Ngày dự kiến vào ở *</label>
                    <input type="date" name="move_in_date" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <p class="mt-1 text-xs text-gray-500">Chỉ được chọn ngày tối đa 30 ngày kể từ hôm nay.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Giới tính *</label>
                    <select name="gender" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">Gửi yêu cầu thuê</button>
            </form>
        <?php endif; ?>
    </div>
</section>