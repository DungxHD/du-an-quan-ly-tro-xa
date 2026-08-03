<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'notifications';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Danh sách thông báo của bạn và trạng thái đã đọc';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$unreadCount = count(array_filter($tenantNotifications ?? [], static fn($item) => (int)($item['is_read'] ?? 0) === 0));
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Thông báo</h2>
            <p class="text-gray-600 mt-2">Trang này hiển thị toàn bộ thông báo áp dụng cho bạn, gồm thông báo broadcast và thông báo gửi riêng. Danh sách đang sắp theo cũ → mới đúng theo yêu cầu.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng thông báo</p>
                <p class="text-lg font-bold"><?= count($tenantNotifications ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Chưa đọc</p>
                <p class="text-lg font-bold text-red-600"><?= $unreadCount ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($tenantNotificationMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($tenantNotificationMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($tenantNotificationError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($tenantNotificationError) ?>
    </div>
    <?php endif; ?>

    <div class="flex justify-end">
        <form method="POST" action="<?= BASE_URL ?>?page=tenant-mark-notification-read">
<?= csrf_field() ?>
            <input type="hidden" name="mark_all" value="1">
            <input type="hidden" name="redirect_page" value="tenant-notifications">
            <button type="submit" class="px-5 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                Đánh dấu tất cả đã đọc
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">notifications</span>
                    Danh sách thông báo
                </h3>
                <p class="text-sm text-gray-500 mt-1">Thông báo chưa đọc có nền xanh nhạt để bạn nhận ra ngay.</p>
            </div>

            <?php if (empty($tenantNotifications)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                Chưa có thông báo nào.
            </div>
            <?php else: ?>
            <div class="p-6 space-y-4">
                <?php foreach ($tenantNotifications as $notification): ?>
                <article class="rounded-2xl border p-5 transition <?= (int)($notification['is_read'] ?? 0) === 0 ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white' ?>">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-900"><?= e($notification['title'] ?? '') ?></h4>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?= e($notification['type_label'] ?? '') ?></span>
                                <?php if ((int)($notification['is_read'] ?? 0) === 0): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Chưa đọc</span>
                                <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Đã đọc</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mt-2"><?= e($notification['content'] ?? '') ?></p>
                            <p class="text-xs text-gray-400 mt-3"><?= e(!empty($notification['created_at']) ? date('d/m/Y H:i', strtotime((string)$notification['created_at'])) : '') ?></p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <?php if ((int)($notification['is_read'] ?? 0) === 0): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-mark-notification-read">
<?= csrf_field() ?>
                                <input type="hidden" name="notification_id" value="<?= (int)($notification['id'] ?? 0) ?>">
                                <input type="hidden" name="redirect_page" value="tenant-notifications">
                                <button type="submit" class="px-4 py-2 rounded-xl border border-blue-200 text-blue-700 font-semibold hover:bg-blue-100 transition">
                                    Đánh dấu đã đọc
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>?page=tenant-notifications&notification_id=<?= (int)($notification['id'] ?? 0) ?>" class="text-sm font-semibold text-primary hover:underline">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500">Thông báo đang chọn</p>
                <?php if (empty($selectedNotification)): ?>
                <p class="text-lg font-bold mt-2">Chưa chọn thông báo</p>
                <p class="text-sm text-gray-500 mt-2">Bấm vào một thông báo trong danh sách để xem lại chi tiết riêng.</p>
                <?php else: ?>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <p class="text-lg font-bold"><?= e($selectedNotification['title'] ?? '') ?></p>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?= e($selectedNotification['type_label'] ?? '') ?></span>
                </div>
                <p class="text-sm text-gray-600 mt-3"><?= e($selectedNotification['content'] ?? '') ?></p>
                <p class="text-xs text-gray-400 mt-4"><?= e(!empty($selectedNotification['created_at']) ? date('d/m/Y H:i', strtotime((string)$selectedNotification['created_at'])) : '') ?></p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
