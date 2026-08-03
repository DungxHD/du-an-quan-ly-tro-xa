<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'comments';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý đánh giá công khai, đánh giá spam và trạng thái ẩn/hiện';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Đánh giá phòng</h2>
            <p class="text-gray-500 mt-2">
                Admin xem toàn bộ đánh giá, kể cả comment bị đánh dấu spam hoặc đang bị ẩn, sau đó quyết định ẩn hoặc hiện lại.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>?page=admin-banned-words" class="px-4 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                Quản lý từ cấm
            </a>
            <a href="<?= BASE_URL ?>?page=admin-comment-reports" class="px-4 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                Xem báo cáo cộng đồng
            </a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng đánh giá</p>
                <p class="text-xl font-bold"><?= (int)($commentStats['total'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang hiện</p>
                <p class="text-xl font-bold text-green-600"><?= (int)($commentStats['visible'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang ẩn</p>
                <p class="text-xl font-bold text-rose-600"><?= (int)($commentStats['hidden'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Spam</p>
                <p class="text-xl font-bold text-amber-600"><?= (int)($commentStats['spam'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Sạch</p>
                <p class="text-xl font-bold text-primary"><?= (int)($commentStats['clean'] ?? 0) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($commentMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($commentMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($commentError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($commentError) ?>
    </div>
    <?php endif; ?>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="GET" action="<?= BASE_URL ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="page" value="admin-comments">

            <div>
                <label class="block text-sm font-semibold mb-2">Trạng thái hiển thị</label>
                <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả</option>
                    <option value="visible" <?= ($commentFilters['status'] ?? '') === 'visible' ? 'selected' : '' ?>>Đang hiện</option>
                    <option value="hidden" <?= ($commentFilters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Đang ẩn</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Loại nội dung</label>
                <select name="spam" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả</option>
                    <option value="clean" <?= ($commentFilters['spam'] ?? '') === 'clean' ? 'selected' : '' ?>>Chưa spam</option>
                    <option value="spam" <?= ($commentFilters['spam'] ?? '') === 'spam' ? 'selected' : '' ?>>Spam</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Từ khóa</label>
                <input
                    type="text"
                    name="keyword"
                    value="<?= e($commentFilters['keyword'] ?? '') ?>"
                    placeholder="Tên tenant, phòng, nội dung..."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Lọc
                </button>
                <a href="<?= BASE_URL ?>?page=admin-comments" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg">Danh sách đánh giá</h3>
            <p class="text-sm text-gray-500 mt-1">Thứ tự đang bám đúng đặc tả: sao cao trước, rồi đến spam, độ độc hại và thời gian tạo.</p>
        </div>

        <?php if (empty($comments)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Không có đánh giá nào khớp bộ lọc hiện tại.
        </div>
        <?php else: ?>
        <div class="p-6 space-y-4">
            <?php foreach ($comments as $comment): ?>
            <article class="rounded-2xl border border-gray-200 p-5 bg-white">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-gray-900"><?= e($comment['full_name'] ?? '') ?></p>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                Phòng <?= e($comment['room_name'] ?? '') ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= (int)($comment['status'] ?? 0) === 1 ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700' ?>">
                                <?= (int)($comment['status'] ?? 0) === 1 ? 'Đang hiện' : 'Đang ẩn' ?>
                            </span>
                            <?php if ((int)($comment['status'] ?? 0) === 0): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= !empty($comment['is_hidden_by_ai']) ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' ?>">
                                <?= e($comment['hidden_reason_label'] ?? 'Đang ẩn') ?>
                            </span>
                            <?php endif; ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= (int)($comment['is_spam'] ?? 0) === 1 ? 'bg-amber-100 text-amber-700' : 'bg-cyan-100 text-cyan-700' ?>">
                                <?= (int)($comment['is_spam'] ?? 0) === 1 ? 'Spam' : 'Chưa spam' ?>
                            </span>
                            <?php if (!empty($comment['is_edited'])): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                Đã sửa <?= e($comment['edited_at_label'] ?? '') ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 flex items-center gap-1 text-yellow-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' <?= $i <= (int)($comment['rating'] ?? 0) ? 1 : 0 ?>;">star</span>
                            <?php endfor; ?>
                            <span class="ml-3 text-xs text-gray-500">
                                Toxicity: <?= number_format((float)($comment['toxicity_score'] ?? 0), 2) ?>
                            </span>
                        </div>

                        <p class="mt-4 text-gray-700 leading-relaxed">
                            <?= $comment['content'] !== null && $comment['content'] !== ''
                                ? nl2br(e($comment['content']))
                                : 'Người dùng chỉ chấm sao cho phòng này.' ?>
                        </p>

                        <?php if (!empty($comment['flagged_words_list'])): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php foreach ($comment['flagged_words_list'] as $flaggedWord): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <?= e($flaggedWord) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <p class="mt-4 text-xs text-gray-400">
                            Tạo lúc <?= e($comment['created_at_label'] ?? '') ?>
                        </p>
                    </div>

                    <div class="w-full xl:w-auto">
                        <form method="POST" action="<?= BASE_URL ?>?page=admin-toggle-comment" class="flex flex-col gap-3 xl:min-w-[180px]">
<?= csrf_field() ?>
                            <input type="hidden" name="comment_id" value="<?= (int)($comment['id'] ?? 0) ?>">
                            <input type="hidden" name="target_status" value="<?= (int)($comment['status'] ?? 0) === 1 ? 0 : 1 ?>">
                            <input type="hidden" name="return_status" value="<?= e($commentFilters['status'] ?? '') ?>">
                            <input type="hidden" name="return_spam" value="<?= e($commentFilters['spam'] ?? '') ?>">
                            <input type="hidden" name="return_keyword" value="<?= e($commentFilters['keyword'] ?? '') ?>">
                            <button type="submit" class="px-4 py-3 rounded-xl font-semibold transition <?= (int)($comment['status'] ?? 0) === 1 ? 'bg-rose-500 text-white hover:bg-rose-600' : 'bg-green-500 text-white hover:bg-green-600' ?>">
                                <?= (int)($comment['status'] ?? 0) === 1 ? 'Ẩn đánh giá' : 'Hiện đánh giá' ?>
                            </button>
                            <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($comment['room_id'] ?? 0) ?>" class="px-4 py-3 rounded-xl border border-gray-200 text-center font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Xem phòng
                            </a>
                        </form>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
