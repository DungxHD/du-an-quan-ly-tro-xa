<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'comment-reports';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Theo dõi báo cáo cộng đồng, ẩn comment vi phạm hoặc bác bỏ nếu báo cáo sai';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Báo cáo đánh giá</h2>
            <p class="text-gray-500 mt-2">
                Khi giải quyết, hệ thống sẽ ẩn đánh giá và đóng luôn các báo cáo đang chờ liên quan tới cùng comment.
            </p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng báo cáo</p>
                <p class="text-xl font-bold"><?= (int)($commentReportStats['total'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Chờ xử lý</p>
                <p class="text-xl font-bold text-amber-600"><?= (int)($commentReportStats['pending'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đã giải quyết</p>
                <p class="text-xl font-bold text-green-600"><?= (int)($commentReportStats['resolved'] ?? 0) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đã bác bỏ</p>
                <p class="text-xl font-bold text-slate-600"><?= (int)($commentReportStats['dismissed'] ?? 0) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($commentReportMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($commentReportMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($commentReportError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($commentReportError) ?>
    </div>
    <?php endif; ?>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form method="GET" action="<?= BASE_URL ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="page" value="admin-comment-reports">

            <div>
                <label class="block text-sm font-semibold mb-2">Trạng thái</label>
                <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả</option>
                    <option value="pending" <?= ($commentReportFilters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                    <option value="resolved" <?= ($commentReportFilters['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Đã giải quyết</option>
                    <option value="dismissed" <?= ($commentReportFilters['status'] ?? '') === 'dismissed' ? 'selected' : '' ?>>Đã bác bỏ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Từ khóa</label>
                <input
                    type="text"
                    name="keyword"
                    value="<?= e($commentReportFilters['keyword'] ?? '') ?>"
                    placeholder="Người báo cáo, người viết, phòng..."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                >
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                    Lọc
                </button>
                <a href="<?= BASE_URL ?>?page=admin-comment-reports" class="px-5 py-3 rounded-xl border border-gray-200 font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg">Danh sách báo cáo</h3>
            <p class="text-sm text-gray-500 mt-1">Pending được ưu tiên lên trước để admin xử lý nhanh nội dung nhạy cảm.</p>
        </div>

        <?php if (empty($commentReports)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Không có báo cáo nào khớp bộ lọc hiện tại.
        </div>
        <?php else: ?>
        <div class="p-6 space-y-4">
            <?php foreach ($commentReports as $report): ?>
            <article class="rounded-2xl border border-gray-200 p-5 bg-white">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                    <div class="flex-1 space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= ($report['status'] ?? '') === 'pending' ? 'bg-amber-100 text-amber-700' : (($report['status'] ?? '') === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700') ?>">
                                <?= e($report['status_label'] ?? '') ?>
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= (int)($report['comment_status'] ?? 0) === 1 ? 'bg-cyan-100 text-cyan-700' : 'bg-rose-100 text-rose-700' ?>">
                                <?= (int)($report['comment_status'] ?? 0) === 1 ? 'Comment đang hiện' : 'Comment đang ẩn' ?>
                            </span>
                            <?php if ((float)($report['toxicity_score'] ?? 0) > 0): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                Toxicity <?= number_format((float)($report['toxicity_score'] ?? 0), 2) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-2xl bg-surface px-4 py-4 border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Người báo cáo</p>
                                <p class="mt-2 font-semibold text-gray-900"><?= e($report['reporter_name'] ?? '') ?></p>
                                <p class="mt-1 text-gray-500">Gửi lúc <?= e($report['created_at_label'] ?? '') ?></p>
                            </div>
                            <div class="rounded-2xl bg-surface px-4 py-4 border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Đánh giá bị báo cáo</p>
                                <p class="mt-2 font-semibold text-gray-900"><?= e($report['comment_author_name'] ?? '') ?> - Phòng <?= e($report['room_name'] ?? '') ?></p>
                                <p class="mt-1 text-gray-500"><?= (int)($report['comment_rating'] ?? 0) ?> sao</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                            <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Lý do báo cáo</p>
                            <p class="mt-2 text-sm text-amber-900"><?= e($report['reason'] ?? '') ?></p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nội dung comment</p>
                            <p class="mt-2 text-sm text-gray-700 leading-relaxed">
                                <?= !empty($report['comment_content'])
                                    ? nl2br(e($report['comment_content']))
                                    : 'Người dùng chỉ chấm sao cho phòng này.' ?>
                            </p>
                        </div>
                    </div>

                    <div class="w-full xl:w-auto xl:min-w-[220px]">
                        <div class="flex flex-col gap-3">
                            <?php if (($report['status'] ?? '') === 'pending'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-resolve-report">
<?= csrf_field() ?>
                                <input type="hidden" name="report_id" value="<?= (int)($report['id'] ?? 0) ?>">
                                <input type="hidden" name="resolve_action" value="resolve">
                                <input type="hidden" name="return_status" value="<?= e($commentReportFilters['status'] ?? '') ?>">
                                <input type="hidden" name="return_keyword" value="<?= e($commentReportFilters['keyword'] ?? '') ?>">
                                <button type="submit" class="w-full px-4 py-3 rounded-xl bg-rose-500 text-white font-semibold hover:bg-rose-600 transition">
                                    Giải quyết và ẩn
                                </button>
                            </form>

                            <form method="POST" action="<?= BASE_URL ?>?page=admin-resolve-report">
<?= csrf_field() ?>
                                <input type="hidden" name="report_id" value="<?= (int)($report['id'] ?? 0) ?>">
                                <input type="hidden" name="resolve_action" value="dismiss">
                                <input type="hidden" name="return_status" value="<?= e($commentReportFilters['status'] ?? '') ?>">
                                <input type="hidden" name="return_keyword" value="<?= e($commentReportFilters['keyword'] ?? '') ?>">
                                <button type="submit" class="w-full px-4 py-3 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition">
                                    Bác bỏ báo cáo
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="px-4 py-3 rounded-xl bg-surface border border-gray-200 text-sm text-gray-600">
                                Báo cáo này đã được xử lý.
                            </div>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($report['room_id'] ?? 0) ?>" class="w-full px-4 py-3 rounded-xl border border-primary text-primary text-center font-semibold hover:bg-primary/5 transition">
                                Xem phòng
                            </a>
                            <a href="<?= BASE_URL ?>?page=admin-comments&keyword=<?= urlencode((string)($report['room_name'] ?? '')) ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-center font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Mở trang đánh giá
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
