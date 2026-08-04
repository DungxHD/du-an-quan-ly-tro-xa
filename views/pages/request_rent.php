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
<section class="py-12 bg-surface min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($room['id'] ?? 0) ?>" class="inline-flex items-center gap-2 text-primary hover:gap-3 transition-all mb-6">
            <span class="material-symbols-outlined">arrow_back</span> Quay lại trang phòng
        </a>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
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
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 text-center">
                <span class="material-symbols-outlined text-5xl text-amber-500">hourglass_top</span>
                <h3 class="mt-3 text-lg font-bold">Yêu cầu đang chờ xét duyệt</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Yêu cầu thuê phòng "<?= e($room['name'] ?? '') ?>" đã được gửi, vui lòng chờ admin xét duyệt.
                    Admin có thể phản hồi qua thông báo hoặc liên hệ trực tiếp số điện thoại của bạn — hãy chú ý phản hồi từ admin.
                </p>
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
                    <input type="date" name="move_in_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Giới tính *</label>
                    <select name="gender" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Số người sẽ vào ở *</label>
                    <input type="number" name="occupant_count" min="1" value="1" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php if ((int)($room['max_occupancy'] ?? 0) > 0): ?>
                    <p class="mt-1 text-xs text-gray-500">Phòng tối đa <?= (int)$room['max_occupancy'] ?> người.</p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">Gửi yêu cầu thuê</button>
            </form>
        <?php endif; ?>
    </div>
</section>