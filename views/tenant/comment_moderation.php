<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = '';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Cảnh báo nội dung đánh giá';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-600 text-3xl">warning</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Nội dung đánh giá không phù hợp</h2>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
            <?= e($moderation_warning['reason'] ?? 'Nội dung bạn nhập chứa từ ngữ không chuẩn mực.') ?>
        </p>

        <?php if (!empty($moderation_warning['attempts'])): ?>
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800">
            <p class="font-semibold">Lần vi phạm: <?= (int)$moderation_warning['attempts'] ?> / 3</p>
            <?php if (!empty($moderation_warning['locked_until'])): ?>
                <p class="mt-2">Tài khoản bị tạm khóa gửi đánh giá đến <strong><?= date('H:i d/m/Y', (int)$moderation_warning['locked_until']) ?></strong></p>
            <?php else: ?>
                <p class="mt-2">Nếu vi phạm thêm <strong><?= 3 - (int)$moderation_warning['attempts'] ?></strong> lần nữa, bạn sẽ bị khóa gửi đánh giá trong 24 giờ.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= BASE_URL ?>?page=tenant-comment-rewrite"
               class="px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition w-full sm:w-auto">
                <span class="material-symbols-outlined inline-block align-middle mr-2">edit</span>
                Viết lại nội dung
            </a>
            <a href="<?= BASE_URL ?>?page=tenant-comment-cancel"
               class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition w-full sm:w-auto">
                <span class="material-symbols-outlined inline-block align-middle mr-2">cancel</span>
                Hủy bỏ đánh giá
            </a>
        </div>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>