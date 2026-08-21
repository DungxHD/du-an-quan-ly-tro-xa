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
                Đánh giá giữ nguyên thời gian tạo ban đầu. Bạn chỉ được sửa trước hạn
                <?= e($commentWindow['deadline_label'] ?? '') ?>. Sau thời gian này bạn chỉ có thể xóa đánh giá.
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

    <?php
    // Kiểm tra nếu đang viết lại sau khi bị cảnh báo moderation
    $pending = $_SESSION['pending_comment'] ?? null;
    $isRewrite = $pending && ($pending['action'] ?? '') === 'edit';
    $prefillContent = $isRewrite ? ($pending['content'] ?? '') : ($comment['content'] ?? '');
    $prefillRating = $isRewrite ? ($pending['rating'] ?? 0) : ($comment['rating'] ?? 0);
    $prefillReturnPage = $isRewrite ? ($pending['return_page'] ?? 'detail') : ($_GET['return_page'] ?? 'detail');
    $rewriteWarning = $isRewrite ? ($_SESSION['moderation_warning']['reason'] ?? '') : '';
    ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <form method="POST" action="<?= BASE_URL ?>?page=tenant-edit-comment" class="space-y-5">
<?= csrf_field() ?>
                <input type="hidden" name="comment_id" value="<?= (int)($comment['id'] ?? 0) ?>">
                <input type="hidden" name="room_id" value="<?= (int)($comment['room_id'] ?? 0) ?>">
                <input type="hidden" name="return_page" value="<?= e($prefillReturnPage) ?>">
                <input type="hidden" name="rating" value="<?= (int)$prefillRating ?>" data-rating-input>

                <?php if ($rewriteWarning): ?>
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">warning</span>
                    <div>
                        <p class="font-semibold">Vui lòng viết lại nội dung:</p>
                        <p class="text-sm mt-1"><?= e($rewriteWarning) ?></p>
                    </div>
                </div>
                <?php endif; ?>

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
                            <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' <?= $i <= (int)$prefillRating ? 1 : 0 ?>;">star</span>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <p class="mt-2 text-xs text-gray-500" data-rating-hint><?= (int)$prefillRating > 0 ? 'Đang chọn ' . (int)$prefillRating . '/5 sao' : 'Chưa chọn sao' ?></p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Nội dung</label>
                    <textarea
                        name="content"
                        id="commentContent"
                        rows="7"
                        maxlength="150"
                        placeholder="Chia sẻ trải nghiệm của bạn..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"><?= e((string)$prefillContent) ?></textarea>
                    <p class="mt-2 text-xs text-gray-500 flex justify-between">
                        <span>Bạn có thể chỉ chấm sao mà không cần nhập nội dung.</span>
                        <span id="charCount"><?= mb_strlen((string)$prefillContent, 'UTF-8') ?>/150</span>
                    </p>
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
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Phòng</p>
                <p class="text-xl font-bold mt-1"><?= e($comment['room_name'] ?? 'Phòng hiện tại') ?></p>
                <p class="text-sm text-gray-500 mt-3">Tạo lúc</p>
                <p class="font-semibold mt-1"><?= e($comment['created_at_label'] ?? '') ?></p>
                <?php if (!empty($comment['is_edited'])): ?>
                <p class="text-sm text-gray-500 mt-3">Lần sửa gần nhất</p>
                <p class="font-semibold mt-1"><?= e($comment['edited_at_label'] ?? '') ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5 text-sm text-amber-800">
                Sau khi hết hạn sửa, bạn chỉ có thể xóa đánh giá này. Nếu cần hỗ trợ, hãy liên hệ admin.
            </div>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-rating-widget]').forEach((widget) => {
        const form = widget.closest('form');
        const input = form ? form.querySelector('[data-rating-input]') : null;
        const hint = widget.parentElement.querySelector('[data-rating-hint]');
        const buttons = Array.from(widget.querySelectorAll('[data-rating-value]'));

        const paint = (value) => {
            const numValue = Number(value);
            buttons.forEach((button) => {
                const filled = Number(button.dataset.ratingValue) <= numValue;
                const icon = button.querySelector('.material-symbols-outlined');
                if (icon) {
                    icon.style.fontVariationSettings = filled ? "'FILL' 1" : "'FILL' 0";
                }
            });
            if (hint) {
                hint.textContent = numValue > 0 ? 'Đang chọn ' + numValue + '/5 sao' : 'Chưa chọn sao';
            }
        };

        paint(input ? input.value : 0);
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                if (input) {
                    input.value = button.dataset.ratingValue;
                }
                paint(button.dataset.ratingValue);
            });
        });
    });

    // Character counter
    const commentTextarea = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');
    if (commentTextarea && charCount) {
        commentTextarea.addEventListener('input', () => {
            const len = commentTextarea.value.length;
            charCount.textContent = len + '/150';
            charCount.classList.toggle('text-red-500', len > 150);
            charCount.classList.toggle('text-gray-500', len <= 150);
        });
    }
});
</script>