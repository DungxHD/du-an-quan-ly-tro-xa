<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = $panelTheme ?? 'admin';
$panelActive = $panelActive ?? '';
$panelTitle = $panelTitle ?? $siteName;
$panelSubtitle = $panelSubtitle ?? '';
$panelNavItems = $panelNavItems ?? getPanelNavigation($panelTheme, $panelActive);
$panelBodyClass = $panelTheme === 'admin' ? 'bg-gray-50' : 'bg-surface';
$panelShellClass = $panelTheme === 'admin'
    ? 'bg-gray-900 text-white border-b border-gray-800'
    : 'bg-white text-gray-900 border-b border-gray-100 shadow-sm';
$panelSidebarClass = $panelTheme === 'admin'
    ? 'bg-gray-900 text-gray-300'
    : 'bg-white text-gray-700 border-r border-gray-100';
$panelAccentClass = $panelTheme === 'admin'
    ? 'bg-primary text-white shadow-card font-bold'
    : 'bg-primary text-white shadow-card font-bold';
$panelLinkBaseClass = $panelTheme === 'admin'
    ? 'text-gray-300 hover:bg-gray-800 transition-transform duration-200 hover:translate-x-1 font-medium'
    : 'text-gray-700 hover:bg-gray-100 transition-transform duration-200 hover:translate-x-1 font-medium';
$panelHeaderIcon = $panelTheme === 'admin' ? 'admin_panel_settings' : 'apartment';
$panelTopLink = $panelTopLink ?? [
    'label' => $panelTheme === 'admin' ? 'Xem website' : 'Trang chủ',
    'url' => BASE_URL . '?page=home',
];
$panelWelcome = $panelWelcome ?? (isset($_SESSION['full_name']) ? 'Xin chào, ' . $_SESSION['full_name'] : '');
$panelContentClass = $panelContentClass ?? 'p-6 md:p-8 md:ml-64';
$showFallbackBanner = !Database::hasConnection();
$tenantNotificationUnreadCount = 0;
$tenantNotificationRecent = [];
$adminNotificationUnreadCount = 0;
$adminNotificationRecent = [];

if ($panelTheme === 'tenant' && !empty($_SESSION['user_id'])) {
    $tenantNotificationUnreadCount = NotificationModel::getUnreadCount((int)$_SESSION['user_id']);
    $tenantNotificationRecent = NotificationModel::getRecentForUser((int)$_SESSION['user_id'], 5);
} elseif ($panelTheme === 'admin' && !empty($_SESSION['user_id'])) {
    $adminNotificationUnreadCount = NotificationModel::getUnreadCount((int)$_SESSION['user_id']);
    $adminNotificationRecent = NotificationModel::getRecentForUser((int)$_SESSION['user_id'], 5);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $panelTitle) ?></title>
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('nta_theme');
                var systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = storedTheme === 'dark' || storedTheme === 'light' ? storedTheme : systemTheme;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        }());
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--nta-brand)',
                        secondary: 'var(--nta-secondary)',
                        surface: 'var(--nta-bg)'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Playfair Display', 'serif'],
                        mono: ['DM Mono', 'monospace']
                    }
                }
            }
        }
    </script>
</head>

<body class="nta-panel-body <?= e($panelBodyClass) ?> font-sans antialiased page-transition-wrapper" data-panel-theme="<?= e($panelTheme) ?>">
    <nav class="nta-panel-topbar fixed top-0 w-full z-40 <?= e($panelShellClass) ?>" id="panelNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-card">
                    <span class="material-symbols-outlined text-white text-[26px]"><?= e($panelHeaderIcon) ?></span>
                </div>
                <div class="min-w-0">
                    <p class="font-bold text-lg truncate"><?= e($panelTitle) ?></p>
                    <p class="text-xs <?= $panelTheme === 'admin' ? 'text-gray-400' : 'text-gray-500' ?> truncate">
                        <?= e($panelSubtitle !== '' ? $panelSubtitle : $siteName . ' panel') ?>
                    </p>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
                <button type="button" class="nta-theme-toggle inline-flex items-center justify-center w-10 h-10 rounded-xl <?= $panelTheme === 'admin' ? 'border border-gray-700 text-gray-300 hover:bg-gray-800' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' ?> transition" data-theme-toggle aria-pressed="false" aria-label="Đổi giao diện sáng tối">
                    <span class="material-symbols-outlined text-[20px]" data-theme-icon>dark_mode</span>
                </button>
                <?php if ($panelWelcome !== ''): ?>
                    <span class="text-sm <?= $panelTheme === 'admin' ? 'text-gray-300' : 'text-gray-600' ?>">
                        <?= e($panelWelcome) ?>
                    </span>
                <?php endif; ?>
                <?php if ($panelTheme === 'tenant'): ?>
                    <details class="relative">
                        <summary class="list-none cursor-pointer">
                            <div class="relative w-11 h-11 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 transition flex items-center justify-center">
                                <span class="material-symbols-outlined text-gray-700">notifications</span>
                                <?php if ($tenantNotificationUnreadCount > 0): ?>
                                    <span class="absolute -top-1 -right-1 min-w-[1.35rem] h-[1.35rem] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center">
                                        <?= $tenantNotificationUnreadCount > 99 ? '99+' : (int)$tenantNotificationUnreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </summary>
                        <div class="absolute right-0 mt-3 w-[380px] rounded-2xl border border-gray-200 bg-white shadow-xl overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">Thông báo</p>
                                    <p class="text-xs text-gray-500"><?= (int)$tenantNotificationUnreadCount ?> chưa đọc</p>
                                </div>
                                <form method="POST" action="<?= BASE_URL ?>?page=tenant-mark-notification-read">
<?= csrf_field() ?>
                                    <input type="hidden" name="mark_all" value="1">
                                    <input type="hidden" name="redirect_page" value="<?= e($_GET['page'] ?? 'tenant-notifications') ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                        <span class="material-symbols-outlined text-sm">done_all</span>
                                        Đã đọc tất cả
                                    </button>
                                </form>
                            </div>

                            <div class="max-h-[360px] overflow-y-auto">
                                <?php if (empty($tenantNotificationRecent)): ?>
                                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                                        Chưa có thông báo nào.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($tenantNotificationRecent as $notification): ?>
                                        <form method="POST" action="<?= BASE_URL ?>?page=tenant-mark-notification-read" class="border-b border-gray-100 last:border-b-0">
<?= csrf_field() ?>
                                            <input type="hidden" name="notification_id" value="<?= (int)($notification['id'] ?? 0) ?>">
                                            <input type="hidden" name="redirect_page" value="tenant-notifications">
                                            <button type="submit" class="w-full px-4 py-3 text-left hover:bg-gray-50 transition <?= (int)($notification['is_read'] ?? 0) === 0 ? 'bg-blue-50' : 'bg-white' ?>">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="font-semibold text-gray-900"><?= e($notification['title'] ?? '') ?></p>
                                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= e($notification['content'] ?? '') ?></p>
                                                    </div>
                                                    <?php if ((int)($notification['is_read'] ?? 0) === 0): ?>
                                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mt-1"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center justify-between mt-2">
                                                    <span class="text-[11px] font-semibold text-primary uppercase tracking-wide"><?= e($notification['type_label'] ?? '') ?></span>
                                                    <span class="text-[11px] text-gray-400"><?= e(!empty($notification['created_at']) ? date('d/m/Y H:i', strtotime((string)$notification['created_at'])) : '') ?></span>
                                                </div>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="px-4 py-3 border-t border-gray-100">
                                <a href="<?= BASE_URL ?>?page=tenant-notifications" class="text-sm font-semibold text-primary hover:underline">
                                    Xem tất cả
                                </a>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
                <?php if ($panelTheme === 'admin'): ?>
                    <details class="relative">
                        <summary class="list-none cursor-pointer">
                            <div class="relative w-11 h-11 rounded-2xl border border-gray-700 bg-gray-900 hover:bg-gray-800 transition flex items-center justify-center">
                                <span class="material-symbols-outlined text-gray-300">notifications</span>
                                <?php if ($adminNotificationUnreadCount > 0): ?>
                                    <span class="absolute -top-1 -right-1 min-w-[1.35rem] h-[1.35rem] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center">
                                        <?= $adminNotificationUnreadCount > 99 ? '99+' : (int)$adminNotificationUnreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </summary>
                        <div class="absolute right-0 mt-3 w-[380px] rounded-2xl border border-gray-700 bg-gray-900 shadow-xl overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-white">Thông báo</p>
                                    <p class="text-xs text-gray-400"><?= (int)$adminNotificationUnreadCount ?> chưa đọc</p>
                                </div>
                                <form method="POST" action="<?= BASE_URL ?>?page=admin-mark-notification-read">
<?= csrf_field() ?>
                                    <input type="hidden" name="mark_all" value="1">
                                    <input type="hidden" name="redirect_page" value="<?= e($_GET['page'] ?? 'admin-notifications') ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                        <span class="material-symbols-outlined text-sm">done_all</span>
                                        Đã đọc tất cả
                                    </button>
                                </form>
                            </div>

                            <div class="max-h-[360px] overflow-y-auto">
                                <?php if (empty($adminNotificationRecent)): ?>
                                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                                        Chưa có thông báo nào.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($adminNotificationRecent as $notification): ?>
                                        <form method="POST" action="<?= BASE_URL ?>?page=admin-mark-notification-read" class="border-b border-gray-800 last:border-b-0">
<?= csrf_field() ?>
                                            <input type="hidden" name="notification_id" value="<?= (int)($notification['id'] ?? 0) ?>">
                                            <input type="hidden" name="redirect_page" value="admin-notifications">
                                            <button type="submit" class="w-full px-4 py-3 text-left hover:bg-gray-800 transition <?= (int)($notification['is_read'] ?? 0) === 0 ? 'bg-gray-800' : 'bg-transparent' ?>">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="font-semibold text-white"><?= e($notification['title'] ?? '') ?></p>
                                                        <p class="text-xs text-gray-400 mt-1 line-clamp-2"><?= e($notification['content'] ?? '') ?></p>
                                                    </div>
                                                    <?php if ((int)($notification['is_read'] ?? 0) === 0): ?>
                                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mt-1"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center justify-between mt-2">
                                                    <span class="text-[11px] font-semibold text-primary uppercase tracking-wide"><?= e($notification['type_label'] ?? '') ?></span>
                                                    <span class="text-[11px] text-gray-500"><?= e(!empty($notification['created_at']) ? date('d/m/Y H:i', strtotime((string)$notification['created_at'])) : '') ?></span>
                                                </div>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="px-4 py-3 border-t border-gray-700">
                                <a href="<?= BASE_URL ?>?page=admin-notifications" class="text-sm font-semibold text-primary hover:underline">
                                    Xem lịch sử đầy đủ
                                </a>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
                <a href="<?= e($panelTopLink['url'] ?? (BASE_URL . '?page=home')) ?>"
                    class="px-4 py-2 rounded-xl border <?= $panelTheme === 'admin' ? 'border-gray-700 text-gray-200 hover:bg-gray-800' : 'border-gray-200 text-gray-700 hover:bg-gray-50' ?> text-sm font-semibold transition">
                    <?= e($panelTopLink['label'] ?? 'Trang chủ') ?>
                </a>
                <a href="<?= BASE_URL ?>?page=logout"
                    class="px-4 py-2 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600 transition">
                    Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="flex pt-16 min-h-screen">
        <aside class="nta-panel-sidebar w-64 <?= e($panelSidebarClass) ?> min-h-[calc(100vh-4rem)] fixed left-0 top-16 p-4 hidden md:block">
            <nav class="space-y-1" id="panelSidebarNav">
                <?php foreach ($panelNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <?php
                        $groupId = 'nav-group-' . $item['id'];
                        $groupOpen = !empty($item['has_active_child']);
                        $groupToggleClass = $groupOpen
                            ? ($panelTheme === 'admin' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-900')
                            : $panelLinkBaseClass;
                        ?>
                        <div data-nav-group>
                            <button type="button"
                                class="nav-group-toggle w-full flex items-center justify-between gap-2 px-4 py-3 rounded-2xl transition <?= e($groupToggleClass) ?>"
                                aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>"
                                aria-controls="<?= e($groupId) ?>">
                                <span class="flex items-center gap-3 min-w-0">
                                    <span class="material-symbols-outlined"><?= e($item['icon']) ?></span>
                                    <span class="font-medium truncate"><?= e($item['label']) ?></span>
                                </span>
                                <span class="material-symbols-outlined nav-group-chevron text-xl transition-transform duration-300 <?= $groupOpen ? 'rotate-180' : '' ?>">expand_more</span>
                            </button>
                            <div id="<?= e($groupId) ?>"
                                class="nav-group-body overflow-hidden transition-[max-height] duration-300 ease-in-out <?= $groupOpen ? '' : 'max-h-0' ?>">
                                <div class="mt-1 mb-1 ml-5 pl-3 space-y-1 <?= $panelTheme === 'admin' ? 'border-l border-gray-700' : 'border-l border-gray-200' ?>">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <?php $childClass = !empty($child['active']) ? $panelAccentClass : $panelLinkBaseClass; ?>
                                        <a href="<?= e($child['url']) ?>"
                                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition <?= e($childClass) ?>">
                                            <span class="material-symbols-outlined text-[20px]"><?= e($child['icon']) ?></span>
                                            <span class="font-medium"><?= e($child['label']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $linkClass = $item['active'] ? $panelAccentClass : $panelLinkBaseClass; ?>
                        <a href="<?= e($item['url']) ?>"
                            class="flex items-center gap-3 px-4 py-3 rounded-2xl transition <?= e($linkClass) ?>">
                            <span class="material-symbols-outlined"><?= e($item['icon']) ?></span>
                            <span class="font-medium"><?= e($item['label']) ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="nta-panel-content flex-1 <?= e($panelContentClass) ?>">
            <div class="md:hidden mb-4 flex items-center gap-2 overflow-x-auto pb-2">
                <button type="button" class="nta-theme-toggle shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white text-gray-600 transition" data-theme-toggle aria-pressed="false" aria-label="Đổi giao diện sáng tối">
                    <span class="material-symbols-outlined text-[20px]" data-theme-icon>dark_mode</span>
                </button>
                <?php foreach ($panelNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <?php foreach ($item['children'] as $child): ?>
                            <?php $mobileClass = !empty($child['active']) ? $panelAccentClass : 'bg-white text-gray-700 border border-gray-200'; ?>
                            <a href="<?= e($child['url']) ?>"
                                class="px-4 py-2 rounded-xl text-sm whitespace-nowrap font-medium transition <?= e($mobileClass) ?>">
                                <?= e($child['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php $mobileClass = $item['active'] ? $panelAccentClass : 'bg-white text-gray-700 border border-gray-200'; ?>
                        <a href="<?= e($item['url']) ?>"
                            class="px-4 py-2 rounded-xl text-sm whitespace-nowrap font-medium transition <?= e($mobileClass) ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($panelTheme === 'tenant'): ?>
                    <a href="<?= BASE_URL ?>?page=tenant-notifications" class="relative px-4 py-2 rounded-xl text-sm whitespace-nowrap font-medium transition bg-white text-gray-700 border border-gray-200">
                        Thông báo
                        <?php if ($tenantNotificationUnreadCount > 0): ?>
                            <span class="ml-2 inline-flex min-w-[1.3rem] h-[1.3rem] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold items-center justify-center">
                                <?= $tenantNotificationUnreadCount > 99 ? '99+' : (int)$tenantNotificationUnreadCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($showFallbackBanner): ?>
                <section class="mb-6 rounded-2xl border-2 border-red-400 bg-red-50 px-6 py-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-red-600 text-2xl mt-0.5">warning</span>
                        <div>
                            <p class="font-bold text-red-700 text-base">⚠️ CẢNH BÁO: Đang chạy bằng dữ liệu demo (Fallback Mode)</p>
                            <p class="text-red-600 text-sm mt-1">
                                Kết nối MySQL thất bại. Mọi thao tác thêm/sửa/xóa sẽ <strong>không được lưu</strong> và sẽ mất khi tải lại trang.
                                Kiểm tra kết nối MySQL và đảm bảo database <code>manage</code> tồn tại.
                            </p>
                            <?php if (($panelTheme ?? '') === 'admin'): ?>
                                <p class="text-red-500 text-xs mt-2">
                                    Lỗi kết nối: <?= e(Database::getConnectionError() ?? 'Không rõ') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>