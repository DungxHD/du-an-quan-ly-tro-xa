<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'notifications';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Gửi thông báo cho tenant và theo dõi lịch sử đã phát hành';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Thông báo</h2>
            <p class="text-gray-500 mt-2">Admin có thể gửi broadcast cho tất cả cư dân hoặc gửi riêng từng tenant. Loại `price_change` sẽ được sinh tự động khi đổi giá dịch vụ.</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng tenant</p>
                <p class="text-xl font-bold"><?= count($tenants ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Lịch sử thông báo</p>
                <p class="text-xl font-bold text-primary"><?= count($notificationHistory ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Loại đang chọn</p>
                <p class="text-xl font-bold text-secondary"><?= e($notificationTypeOptions[$notificationForm['type'] ?? 'general'] ?? 'Chung') ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đối tượng</p>
                <p class="text-xl font-bold text-green-600"><?= ($notificationForm['recipient_scope'] ?? 'all') === 'all' ? 'Tất cả' : 'Cá nhân' ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($notificationMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($notificationMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($notificationError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($notificationError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
        <section class="2xl:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                <div>
                    <h3 class="text-lg font-bold">Gửi thông báo mới</h3>
                    <p class="text-sm text-gray-500 mt-1">Form này dùng cho thông báo thủ công. Riêng đổi giá dịch vụ sẽ tự tạo thông báo `price_change`.</p>
                </div>

                <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-semibold text-gray-900"><?= e($notificationForm['title'] ?: 'Tiêu đề thông báo') ?></p>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <?= e($notificationTypeOptions[$notificationForm['type'] ?? 'general'] ?? 'Chung') ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600"><?= e($notificationForm['content'] ?: 'Nội dung preview sẽ hiển thị ở đây.') ?></p>
                    <p class="text-xs text-gray-500">
                        Đối tượng: <?= ($notificationForm['recipient_scope'] ?? 'all') === 'all' ? 'Tất cả tenant' : 'Một tenant cụ thể' ?>
                    </p>
                </div>

                <form method="POST" action="<?= BASE_URL ?>?page=admin-send-notification" class="space-y-4">
<?= csrf_field() ?>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Tiêu đề</label>
                        <input
                            type="text"
                            name="title"
                            value="<?= e($notificationForm['title'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Nội dung</label>
                        <textarea
                            name="content"
                            rows="5"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        ><?= e($notificationForm['content'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Loại</label>
                            <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <?php foreach ($notificationTypeOptions as $typeValue => $typeLabel): ?>
                                <option value="<?= e($typeValue) ?>" <?= ($notificationForm['type'] ?? 'general') === $typeValue ? 'selected' : '' ?>>
                                    <?= e($typeLabel) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Đối tượng</label>
                            <select name="recipient_scope" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                                <option value="all" <?= ($notificationForm['recipient_scope'] ?? 'all') === 'all' ? 'selected' : '' ?>>Tất cả tenant</option>
                                <option value="user" <?= ($notificationForm['recipient_scope'] ?? 'all') === 'user' ? 'selected' : '' ?>>Chọn 1 tenant</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tenant nhận riêng</label>
                        <select name="user_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <option value="0">Không chọn</option>
                            <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= (int)($tenant['id'] ?? 0) ?>" <?= (int)($notificationForm['user_id'] ?? 0) === (int)($tenant['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($tenant['full_name'] ?? '') ?><?= !empty($tenant['room_name']) ? ' - ' . e($tenant['room_name']) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        Gửi thông báo
                    </button>
                </form>
            </div>
        </section>

        <section class="2xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg">Lịch sử thông báo đã gửi</h3>
                <p class="text-sm text-gray-500 mt-1">Danh sách này giúp admin tra lại thông báo broadcast, thông báo riêng và các bản tin tự sinh từ đổi giá.</p>
            </div>

            <?php if (empty($notificationHistory)): ?>
            <div class="px-6 py-10 text-center text-gray-500">
                Chưa có thông báo nào được gửi.
            </div>
            <?php else: ?>
            <div class="p-6 space-y-4">
                <?php foreach ($notificationHistory as $notification): ?>
                <?php
                $notificationHref = '';
                $notificationLink = trim((string)($notification['link'] ?? ''));
                if ($notificationLink !== '') {
                    $notificationHref = strpos($notificationLink, '/') === 0
                        ? $notificationLink
                        : BASE_URL . ltrim($notificationLink, '/');
                }
                ?>
                <article class="rounded-2xl border border-gray-200 p-5 bg-white">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($notificationHref !== ''): ?>
                                <a href="<?= e($notificationHref) ?>" class="font-bold text-gray-900 hover:text-primary transition"><?= e($notification['title'] ?? '') ?></a>
                                <?php else: ?>
                                <h4 class="font-bold text-gray-900"><?= e($notification['title'] ?? '') ?></h4>
                                <?php endif; ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?= e($notification['type_label'] ?? '') ?></span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700"><?= e($notification['recipient_label'] ?? '') ?></span>
                            </div>
                            <?php if ($notificationHref !== ''): ?>
                            <a href="<?= e($notificationHref) ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline mt-1">
                                <span class="material-symbols-outlined text-sm">open_in_new</span> Xem chi tiết
                            </a>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600 mt-2"><?= e($notification['content'] ?? '') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Thời gian gửi</p>
                            <p class="font-semibold text-gray-900 mt-1"><?= e(!empty($notification['created_at']) ? date('d/m/Y H:i', strtotime((string)$notification['created_at'])) : 'Chưa rõ') ?></p>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
