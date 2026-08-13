<?php
/**
 * [DEV-QWEN-A][NHOM-2][2026-08-14]
 * Gửi Phản ánh cho chủ trọ.
 */
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'feedback';
$panelTitle = $siteName;
$panelSubtitle = 'Gửi phản ánh, khiếu nại, đề xuất trực tiếp cho chủ trọ';
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="flex flex-col gap-6">
    <div>
        <h2 class="text-3xl font-bold">Gửi Phản ánh</h2>
        <p class="text-gray-500 mt-2">Gửi khiếu nại, đề xuất, báo sự cố hoặc bất kỳ vấn đề nào trực tiếp cho chủ trọ. Phản ánh sẽ được chủ trọ xem và xử lý.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-dismissible rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 flex items-center justify-between">
            <span><?= e($message) ?></span>
            <button type="button" data-dismiss-alert class="ml-4 text-green-800 hover:text-green-950 font-bold text-lg" aria-label="Đóng thông báo">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-dismissible rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
            <p><?= e($error) ?></p>
            <button type="button" data-dismiss-alert class="mt-3 inline-flex items-center gap-1 rounded-lg bg-rose-200 px-3 py-1.5 text-xs font-bold text-rose-900 hover:bg-rose-300">
                <span class="material-symbols-outlined text-base">arrow_back</span> Quay lại
            </button>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= BASE_URL ?>?page=tenant-send-feedback" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-semibold mb-2">Phòng liên quan <span class="text-gray-400">(tùy chọn)</span></label>
                <select name="room_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">— Không gắn phòng —</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int)$room['id'] ?>"><?= e($room['name']) ?> (<?= e($room['area_name'] ?? 'Khu chưa rõ') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">Chọn phòng nếu phản ánh liên quan đến phòng cụ thể. Để trống nếu là vấn đề chung chung.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" name="subject" required maxlength="255"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                    placeholder="VD: Máy lạnh phòng 101 không mát, Đề xuất thêm giặt ủi...">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Nội dung phản ánh <span class="text-red-500">*</span></label>
                <textarea name="content" required rows="6" maxlength="2000"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"
                    placeholder="Mô tả chi tiết vấn đề, khiếu nại hoặc đề xuất của bạn..."></textarea>
                <p class="mt-1 text-xs text-gray-500">Vui lòng cung cấp càng nhiều chi tiết càng tốt để chủ trọ dễ dàng xử lý.</p>
            </div>

            <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                <span class="material-symbols-outlined text-base">send</span> Gửi phản ánh
            </button>
        </form>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>