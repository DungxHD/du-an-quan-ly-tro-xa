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
    ? 'bg-primary text-white'
    : 'bg-primary text-white shadow-sm';
$panelLinkBaseClass = $panelTheme === 'admin'
    ? 'text-gray-300 hover:bg-gray-800'
    : 'text-gray-700 hover:bg-gray-100';
$panelHeaderIcon = $panelTheme === 'admin' ? 'admin_panel_settings' : 'apartment';
$panelTopLink = $panelTopLink ?? [
    'label' => $panelTheme === 'admin' ? 'Xem website' : 'Trang chủ',
    'url' => BASE_URL . '?page=home',
];
$panelWelcome = $panelWelcome ?? (isset($_SESSION['full_name']) ? 'Xin chào, ' . $_SESSION['full_name'] : '');
$panelContentClass = $panelContentClass ?? 'p-6 md:p-8 md:ml-64';
$showFallbackBanner = !Database::hasConnection();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $panelTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary: '#00685f', secondary: '#4b41e1', surface: '#f9f9ff' },
                fontFamily: { sans: ['Inter', 'sans-serif'] }
            }}
        }
    </script>
</head>
<body class="<?= e($panelBodyClass) ?> font-sans antialiased">
<nav class="fixed top-0 w-full z-40 <?= e($panelShellClass) ?>" id="panelNav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center border border-white/10">
                <span class="material-symbols-outlined text-primary text-3xl"><?= e($panelHeaderIcon) ?></span>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-lg truncate"><?= e($panelTitle) ?></p>
                <p class="text-xs <?= $panelTheme === 'admin' ? 'text-gray-400' : 'text-gray-500' ?> truncate">
                    <?= e($panelSubtitle !== '' ? $panelSubtitle : $siteName . ' panel') ?>
                </p>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-3">
            <?php if ($panelWelcome !== ''): ?>
            <span class="text-sm <?= $panelTheme === 'admin' ? 'text-gray-300' : 'text-gray-600' ?>">
                <?= e($panelWelcome) ?>
            </span>
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
    <aside class="w-64 <?= e($panelSidebarClass) ?> min-h-[calc(100vh-4rem)] fixed left-0 top-16 p-4 hidden md:block">
        <nav class="space-y-1">
            <?php foreach ($panelNavItems as $item): ?>
                <?php $linkClass = $item['active'] ? $panelAccentClass : $panelLinkBaseClass; ?>
                <a href="<?= e($item['url']) ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition <?= e($linkClass) ?>">
                    <span class="material-symbols-outlined"><?= e($item['icon']) ?></span>
                    <span class="font-medium"><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="flex-1 <?= e($panelContentClass) ?>">
        <div class="md:hidden mb-4 flex gap-2 overflow-x-auto pb-2">
            <?php foreach ($panelNavItems as $item): ?>
                <?php $mobileClass = $item['active'] ? $panelAccentClass : 'bg-white text-gray-700 border border-gray-200'; ?>
                <a href="<?= e($item['url']) ?>"
                   class="px-4 py-2 rounded-xl text-sm whitespace-nowrap font-medium transition <?= e($mobileClass) ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($showFallbackBanner): ?>
        <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 flex flex-wrap items-center justify-between gap-3">
            <span>Đang hiển thị dữ liệu demo fallback để giao diện luôn hoạt động mượt và không phụ thuộc kết nối MySQL.</span>
            <a href="<?= BASE_URL ?>?page=rooms" class="font-semibold hover:underline">Xem danh sách phòng</a>
        </section>
        <?php endif; ?>
