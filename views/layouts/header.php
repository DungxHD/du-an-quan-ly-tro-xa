<?php
$layout = $layout ?? [];
$siteName = $layout['siteName'] ?? 'NhaTroA';
$pageTitle = $layout['pageTitle'] ?? ($siteName . ' - Hệ thống trọ cao cấp');
$activePage = $layout['activePage'] ?? '';
$urls = $layout['urls'] ?? [];
$homeUrl = $urls['home'] ?? (BASE_URL . '?page=home');
$roomsUrl = $urls['rooms'] ?? (BASE_URL . '?page=rooms');
$introUrl = $urls['intro'] ?? (BASE_URL . '?page=intro');
$registerUrl = $urls['register'] ?? (BASE_URL . '?page=register');
$loginUrl = $urls['login'] ?? (BASE_URL . '?page=login');
$logoutUrl = $urls['logout'] ?? (BASE_URL . '?page=logout');
$adminUrl = $urls['admin'] ?? (BASE_URL . '?page=admin');
$tenantUrl = $urls['tenant'] ?? (BASE_URL . '?page=tenant');
$isFallbackMode = (bool)($layout['isFallbackMode'] ?? false);
$metaDescription = $layout['meta']['description'] ?? '';
$phone = $layout['contact']['phone'] ?? '';
$phoneTel = trim((string)($layout['contact']['phoneTel'] ?? ''));
$brandTagline = $layout['brand']['tagline'] ?? 'Xem phòng, đặt lịch và quản lý cư dân';
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = (int)($_SESSION['role'] ?? 0) === 1;
$sessionRoomId = $_SESSION['room_id'] ?? null;
$userName = trim((string)($_SESSION['full_name'] ?? ''));
$memberUrl = $sessionRoomId ? $tenantUrl : $roomsUrl;
$memberLabel = $sessionRoomId ? 'Khu cư dân' : 'Tìm phòng';

$navClass = static function ($id) use ($activePage) {
    $base = 'nav-link public-nav-link px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5';
    if ($id !== '' && $id === $activePage) {
        return $base . ' bg-primary/10 text-primary shadow-sm';
    }
    return $base . ' hover:bg-gray-50 hover:text-primary';
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary: 'var(--nta-brand)', secondary: 'var(--nta-secondary)', surface: 'var(--nta-bg)' },
                fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Playfair Display', 'serif'], mono: ['DM Mono', 'monospace'] }
            }}
        }
    </script>
</head>
<body class="nta-public-body bg-surface text-gray-900 font-sans antialiased page-transition-wrapper">

<nav class="nta-public-nav fixed top-0 w-full z-40" id="mainNav">
    <div class="public-nav-inner">
        <a href="<?= $homeUrl ?>" class="public-brand group" aria-label="<?= e($siteName) ?> - Trang chủ">
            <span class="public-brand-mark">
                <span class="material-symbols-outlined" aria-hidden="true">home</span>
            </span>
            <span class="public-brand-copy">
                <span class="public-brand-name"><?= e($siteName) ?></span>
                <span class="public-brand-tagline"><?= e($brandTagline) ?></span>
            </span>
        </a>

        <div class="public-nav-links hidden lg:flex" aria-label="Điều hướng chính">
            <a href="<?= $homeUrl ?>" class="<?= e($navClass('home')) ?>">Trang chủ</a>
            <a href="<?= $roomsUrl ?>" class="<?= e($navClass('rooms')) ?>">Phòng trọ</a>
            <a href="<?= $introUrl ?>" class="<?= e($navClass('intro')) ?>">Giới thiệu</a>
        </div>

        <div class="public-nav-actions hidden lg:flex">
            <button type="button" class="public-icon-button nta-theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Đổi giao diện sáng tối" title="Đổi giao diện sáng tối">
                <span class="material-symbols-outlined text-[20px]" data-theme-icon aria-hidden="true">dark_mode</span>
            </button>
            <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                <a href="tel:<?= e($phoneTel) ?>" class="public-phone" aria-label="Gọi tư vấn <?= e($phone !== '' ? $phone : '') ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">call</span>
                    <span class="public-phone-label"><?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?></span>
                </a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <div class="public-user-pill" title="Tài khoản <?= e($userName) ?>">
                    <span class="public-user-avatar" aria-hidden="true">
                        <?= e(mb_substr($userName !== '' ? $userName : 'U', 0, 1)) ?>
                    </span>
                    <span class="public-user-name"><?= e($userName !== '' ? $userName : 'Tài khoản') ?></span>
                </div>
                <?php if ($isAdmin): ?>
                    <a href="<?= $adminUrl ?>" class="nta-button nta-button--primary public-cta">Khu quản trị</a>
                <?php else: ?>
                    <a href="<?= $memberUrl ?>" class="nta-button nta-button--secondary public-cta"><?= e($memberLabel) ?></a>
                <?php endif; ?>
                <a href="<?= $logoutUrl ?>" class="nta-button nta-button--danger public-cta">Đăng xuất</a>
            <?php else: ?>
                <a href="<?= $registerUrl ?>" class="nta-button nta-button--outline public-cta">Đăng ký</a>
                <a href="<?= $loginUrl ?>" class="nta-button nta-button--primary public-cta">Đăng nhập</a>
            <?php endif; ?>
        </div>

        <div class="public-mobile-actions lg:hidden">
            <button type="button" class="public-icon-button nta-theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Đổi giao diện sáng tối" title="Đổi giao diện sáng tối">
                <span class="material-symbols-outlined text-[20px]" data-theme-icon aria-hidden="true">dark_mode</span>
            </button>
            <button type="button" id="mobileMenuBtn" class="public-mobile-menu-button" aria-label="Mở menu" aria-expanded="false" aria-controls="mobileMenu">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
        </div>
    </div>
</nav>

<div id="mobileMenuBackdrop" class="nta-mobile-backdrop hidden fixed inset-0 transition-opacity duration-300" aria-hidden="true"></div>
<aside id="mobileMenu" class="nta-mobile-drawer fixed top-0 right-0 h-full translate-x-full transition-transform duration-300 shadow-modal" aria-label="Menu di động" aria-hidden="true">
    <div class="mobile-menu-header p-5 border-b flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="public-brand-mark">
                <span class="material-symbols-outlined" aria-hidden="true">home</span>
            </span>
            <div class="min-w-0">
                <p class="public-brand-name"><?= e($siteName) ?></p>
                <p class="text-xs text-gray-500">Menu điều hướng</p>
            </div>
        </div>
        <button type="button" id="mobileMenuCloseBtn" class="public-icon-button" aria-label="Đóng menu" title="Đóng menu">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
    </div>
    <div class="p-5 space-y-2 overflow-y-auto h-[calc(100%-73px)]">
        <a href="<?= $homeUrl ?>" class="mobile-menu-link px-4 py-3 <?= $activePage === 'home' ? 'is-active' : '' ?>">
            <span class="material-symbols-outlined text-base" aria-hidden="true">home</span> Trang chủ
        </a>
        <a href="<?= $roomsUrl ?>" class="mobile-menu-link px-4 py-3 <?= $activePage === 'rooms' ? 'is-active' : '' ?>">
            <span class="material-symbols-outlined text-base" aria-hidden="true">meeting_room</span> Phòng trọ
        </a>
        <a href="<?= $introUrl ?>" class="mobile-menu-link px-4 py-3 <?= $activePage === 'intro' ? 'is-active' : '' ?>">
            <span class="material-symbols-outlined text-base" aria-hidden="true">info</span> Giới thiệu
        </a>

        <div class="mobile-menu-divider"></div>

        <?php if ($isLoggedIn): ?>
            <div class="px-4 py-3 rounded-xl bg-surface border border-gray-100">
                <p class="text-xs text-gray-500">Xin chào</p>
                <p class="font-bold truncate"><?= e($userName) ?></p>
            </div>
            <?php if ($isAdmin): ?>
                <a href="<?= $adminUrl ?>" class="nta-button nta-button--primary w-full">Khu quản trị</a>
            <?php else: ?>
                <a href="<?= $memberUrl ?>" class="nta-button nta-button--secondary w-full"><?= e($memberLabel) ?></a>
            <?php endif; ?>
            <a href="<?= $logoutUrl ?>" class="nta-button nta-button--danger w-full">Đăng xuất</a>
        <?php else: ?>
            <a href="<?= $registerUrl ?>" class="nta-button nta-button--outline w-full">Đăng ký</a>
            <a href="<?= $loginUrl ?>" class="nta-button nta-button--primary w-full">Đăng nhập</a>
        <?php endif; ?>

        <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
            <a href="tel:<?= e($phoneTel) ?>" class="mobile-menu-link px-4 py-3 mt-2 border border-gray-200">
                <span class="material-symbols-outlined text-base" aria-hidden="true">call</span>
                <?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?>
            </a>
        <?php endif; ?>
    </div>
</aside>

<main class="public-main">
    <?php if ($isFallbackMode): ?>
    <section class="public-fallback-banner border-b">
        <div class="w-[min(100%-28px,1240px)] mx-auto px-0 py-3 text-sm text-amber-700 flex flex-wrap items-center justify-between gap-2">
            <span>Đang chạy bằng dữ liệu demo fallback để giao diện luôn hoạt động ổn định.</span>
            <a href="<?= $roomsUrl ?>" class="font-semibold hover:underline">Xem phòng ngay</a>
        </div>
    </section>
    <?php endif; ?>
