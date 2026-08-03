<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = '';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Sửa lại đánh giá của bạn trong thời hạn cho phép';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Sửa đánh giá</h2>
            <p class="text-gray-600 mt-2">
                Đánh giá giữ nguyên thời gian tạo ban đầu. Bạn chỉ được sửa hoặc xóa trước hạn
                <?= e($commentWindow['deadline_label'] ?? '') ?>.
            </p>
        </div>
        <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($comment['room_id'] ?? 0) ?>" class="px-5 py-3 rounded-xl border border-gray-200 bg-white font-semibold text-gray-700 hover:bg-gray-50 transition">
            Quay lại chi tiết phòng
        </a>
    </div>

    <?php if (!empty($tenantCommentMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($tenantCommentMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($tenantCommentError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($tenantCommentError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <form method="POST" action="<?= BASE_URL ?>?page=tenant-edit-comment" class="space-y-5">
<?= csrf_field() ?>
                <input type="hidden" name="comment_id" value="<?= (int)($comment['id'] ?? 0) ?>">
                <input type="hidden" name="room_id" value="<?= (int)($comment['room_id'] ?? 0) ?>">
                <input type="hidden" name="rating" value="<?= (int)($comment['rating'] ?? 5) ?>" data-rating-input>

                <div>
                    <label class="block text-sm font-semibold mb-2">Số sao</label>
                    <div class="flex items-center gap-1" data-rating-widget>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button
                            type="button"
                            class="rating-star text-yellow-400 transition hover:scale-110"
                            data-rating-value="<?= $i ?>"
                            aria-label="Chọn <?= $i ?> sao"
                        >
                            <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' <?= $i <= (int)($comment['rating'] ?? 5) ? 1 : 0 ?>;">star</span>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Nội dung</label>
                    <textarea
                        name="content"
                        rows="7"
                        placeholder="Chia sẻ trải nghiệm của bạn..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"
                    ><?= e((string)($comment['content'] ?? '')) ?></textarea>
                    <p class="mt-2 text-xs text-gray-500">Hệ thống sẽ kiểm duyệt lại nội dung sau khi bạn lưu thay đổi.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        Lưu thay đổi
                    </button>
                    <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($comment['room_id'] ?? 0) ?>" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Hủy
                    </a>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Phòng</p>
                <p class="text-xl font-bold mt-1"><?= e($comment['room_name'] ?? 'Phòng hiện tại') ?></p>
                <p class="text-sm text-gray-500 mt-3">Tạo lúc</p>
                <p class="font-semibold mt-1"><?= e($comment['created_at_label'] ?? '') ?></p>
                <?php if (!empty($comment['is_edited'])): ?>
                <p class="text-sm text-gray-500 mt-3">Lần sửa gần nhất</p>
                <p class="font-semibold mt-1"><?= e($comment['edited_at_label'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-800">
                Nếu hết thời hạn sửa/xóa, bạn cần liên hệ admin để được hỗ trợ xử lý đánh giá này.
            </div>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>

<script>
document.querySelectorAll('[data-rating-widget]').forEach((widget) => {
    const input = widget.parentElement.parentElement.querySelector('[data-rating-input]');
    const buttons = Array.from(widget.querySelectorAll('[data-rating-value]'));

    const paint = (value) => {
        buttons.forEach((button) => {
            const filled = Number(button.dataset.ratingValue) <= Number(value);
            const icon = button.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.style.fontVariationSettings = filled ? "'FILL' 1" : "'FILL' 0";
            }
        });
    };

    paint(input ? input.value : 5);
    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            if (input) {
                input.value = button.dataset.ratingValue;
            }
            paint(button.dataset.ratingValue);
        });
    });
});
</script>
