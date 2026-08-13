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
        <p class="text-gray-500 mt-2">Gửi khiếu nại, đề xuất, báo sự cố hoặc bất kỳ vấn đề nào trực tiếp cho chủ trọ. Bạn có thể phản ánh bất cứ lúc nào, không cần điều kiện ở tối thiểu.</p>
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
        <form method="POST" action="<?= BASE_URL ?>?page=tenant-send-feedback" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-semibold mb-2">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" name="subject" required maxlength="255"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                    placeholder="VD: Máy lạnh không mát, Đề xuất thêm giặt ủi...">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Nội dung phản ánh <span class="text-red-500">*</span></label>
                <textarea name="content" required rows="6" maxlength="2000"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"
                    placeholder="Mô tả chi tiết vấn đề, khiếu nại hoặc đề xuất của bạn..."></textarea>
                <p class="mt-1 text-xs text-gray-500">Vui lòng cung cấp càng nhiều chi tiết càng tốt để chủ trọ dễ dàng xử lý.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Ảnh minh họa <span class="text-gray-400">(tùy chọn)</span></label>
                <input type="file" name="feedback_image" id="feedback-image-input" accept="image/jpeg,image/png,image/webp,image/gif"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none file:mr-3 file:px-4 file:py-2 file:rounded-xl file:border-0 file:bg-primary/10 file:text-primary file:font-semibold hover:file:bg-primary/20">
                <p class="mt-1 text-xs text-gray-500">Chấp nhận JPG, PNG, WEBP, GIF — tối đa 5MB. Không bắt buộc tải ảnh.</p>
                <img id="feedback-image-preview" alt="Xem trước ảnh phản ánh" class="mt-3 hidden max-h-48 rounded-xl border border-gray-200 object-cover">
            </div>

            <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                <span class="material-symbols-outlined text-base">send</span> Gửi phản ánh
            </button>
        </form>
    </div>

    <?php if (!empty($myFeedbacks)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-xl font-bold mb-1">Lịch sử phản ánh của bạn</h3>
        <p class="text-sm text-gray-500 mb-4">Theo dõi trạng thái xử lý và câu trả lời từ chủ trọ.</p>

        <div class="space-y-4">
            <?php foreach ($myFeedbacks as $fb): ?>
            <article class="rounded-2xl border border-gray-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="font-bold text-gray-900"><?= e($fb['subject']) ?></h4>
                        <p class="text-xs text-gray-500 mt-0.5">#<?= (int)$fb['id'] ?> · <?= e($fb['created_at_label']) ?></p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        <?= $fb['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($fb['status'] === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') ?>">
                        <?= e($fb['status_label']) ?>
                    </span>
                </div>
                <p class="mt-3 text-sm text-gray-600 whitespace-pre-line"><?= e($fb['content']) ?></p>
                <?php if (!empty($fb['image'])): ?>
                    <a href="<?= e($fb['image']) ?>" target="_blank" rel="noopener">
                        <img src="<?= e($fb['image']) ?>" alt="Ảnh minh họa phản ánh" class="mt-3 max-h-48 rounded-xl border border-gray-200 object-cover">
                    </a>
                <?php endif; ?>
                <?php if (!empty($fb['admin_reply'])): ?>
                    <div class="mt-4 rounded-xl bg-green-50 border border-green-200 p-4">
                        <p class="text-xs font-semibold text-green-700 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">support_agent</span> Trả lời từ chủ trọ
                        </p>
                        <p class="text-sm text-green-800 whitespace-pre-line"><?= e($fb['admin_reply']) ?></p>
                    </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('feedback-image-input');
        var preview = document.getElementById('feedback-image-preview');
        if (input && preview) {
            input.addEventListener('change', function () {
                if (input.files && input.files[0]) {
                    preview.src = URL.createObjectURL(input.files[0]);
                    preview.classList.remove('hidden');
                } else {
                    preview.classList.add('hidden');
                }
            });
        }
    });
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>