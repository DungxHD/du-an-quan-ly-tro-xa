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

$navClass = static function ($id) use ($activePage) {
    $base = 'nav-link px-3 py-2 rounded-lg text-sm font-semibold transition';
    if ($id !== '' && $id === $activePage) {
        return $base . ' bg-primary/10 text-primary';
    }
    return $base . ' hover:bg-gray-50';
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
<body class="bg-surface text-gray-900 font-sans antialiased">

<nav class="fixed top-0 w-full bg-white/80 backdrop-blur-xl shadow-sm z-40 border-b border-gray-100" id="mainNav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="<?= $homeUrl ?>" class="flex items-center gap-3 group">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center shadow-sm transition group-hover:scale-[1.03]">
                    <span class="material-symbols-outlined">home</span>
                </span>
                <span class="leading-tight">
                    <span class="block text-base font-extrabold tracking-tight text-gray-900"><?= e($siteName) ?></span>
                    <span class="block text-[12px] text-gray-500"><?= e($brandTagline) ?></span>
                </span>
            </a>

            <div class="hidden lg:flex items-center gap-1">
                <a href="<?= $homeUrl ?>" class="<?= e($navClass('home')) ?>">Trang chủ</a>
                <a href="<?= $roomsUrl ?>" class="<?= e($navClass('rooms')) ?>">Phòng trọ</a>
                <a href="<?= $introUrl ?>" class="<?= e($navClass('intro')) ?>">Giới thiệu</a>
            </div>

            <div class="hidden lg:flex items-center gap-3">
                <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                    <a href="tel:<?= e($phoneTel) ?>" class="hidden xl:inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 transition text-sm font-semibold">
                        <span class="material-symbols-outlined text-base">call</span>
                        <?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                    <a href="<?= $adminUrl ?>" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold hover:opacity-95 transition">Quản trị</a>
                    <a href="<?= $logoutUrl ?>" class="px-4 py-2 rounded-xl bg-red-500 text-white text-sm font-bold hover:opacity-95 transition">Đăng xuất</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="hidden xl:flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-gray-200">
                        <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">
                            <?= e(mb_substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                        </span>
                        <span class="text-sm font-semibold"><?= e($_SESSION['full_name'] ?? '') ?></span>
                    </div>
                    <a href="<?= $tenantUrl ?>" class="px-4 py-2 rounded-xl bg-secondary text-white text-sm font-bold hover:opacity-95 transition">Khu cư dân</a>
                    <a href="<?= $logoutUrl ?>" class="px-4 py-2 rounded-xl bg-red-500 text-white text-sm font-bold hover:opacity-95 transition">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?= $registerUrl ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-bold hover:bg-gray-50 transition">Đăng ký</a>
                    <a href="<?= $loginUrl ?>" class="px-5 py-2 rounded-xl bg-primary text-white text-sm font-bold hover:opacity-95 transition">Đăng nhập</a>
                <?php endif; ?>
            </div>

            <button id="mobileMenuBtn" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl hover:bg-gray-100 transition" aria-label="Mở menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
</nav>

<div id="mobileMenuBackdrop" class="hidden fixed inset-0 bg-gray-900/40 backdrop-blur-[2px] z-40"></div>
<aside id="mobileMenu" class="fixed top-0 right-0 h-full w-[86%] max-w-sm bg-white z-50 translate-x-full transition-transform duration-300">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center">
                <span class="material-symbols-outlined">home</span>
            </span>
            <div>
                <p class="font-extrabold"><?= e($siteName) ?></p>
                <p class="text-xs text-gray-500">Menu</p>
            </div>
        </div>
        <button id="mobileMenuCloseBtn" class="w-10 h-10 rounded-xl hover:bg-gray-100 transition" aria-label="Đóng menu">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="p-5 space-y-2">
        <a href="<?= $homeUrl ?>" class="block px-4 py-3 rounded-xl <?= $activePage === 'home' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-gray-50' ?>">Trang chủ</a>
        <a href="<?= $roomsUrl ?>" class="block px-4 py-3 rounded-xl <?= $activePage === 'rooms' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-gray-50' ?>">Phòng trọ</a>
        <a href="<?= $introUrl ?>" class="block px-4 py-3 rounded-xl <?= $activePage === 'intro' ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-gray-50' ?>">Giới thiệu</a>

        <div class="h-px bg-gray-100 my-3"></div>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
            <a href="<?= $adminUrl ?>" class="block px-4 py-3 rounded-xl bg-primary text-white font-bold">Quản trị</a>
            <a href="<?= $logoutUrl ?>" class="block px-4 py-3 rounded-xl bg-red-500 text-white font-bold">Đăng xuất</a>
        <?php elseif (isset($_SESSION['user_id'])): ?>
            <div class="px-4 py-3 rounded-xl bg-surface border border-gray-100">
                <p class="text-xs text-gray-500">Xin chào</p>
                <p class="font-bold"><?= e($_SESSION['full_name'] ?? '') ?></p>
            </div>
            <a href="<?= $tenantUrl ?>" class="block px-4 py-3 rounded-xl bg-secondary text-white font-bold">Khu cư dân</a>
            <a href="<?= $logoutUrl ?>" class="block px-4 py-3 rounded-xl bg-red-500 text-white font-bold">Đăng xuất</a>
        <?php else: ?>
            <a href="<?= $registerUrl ?>" class="block px-4 py-3 rounded-xl bg-white border border-gray-200 font-bold hover:bg-gray-50 transition">Đăng ký</a>
            <a href="<?= $loginUrl ?>" class="block px-4 py-3 rounded-xl bg-primary text-white font-bold">Đăng nhập</a>
        <?php endif; ?>

        <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
            <a href="tel:<?= e($phoneTel) ?>" class="mt-2 block px-4 py-3 rounded-xl border border-gray-200 bg-white font-bold hover:bg-gray-50 transition flex items-center gap-2">
                <span class="material-symbols-outlined text-base">call</span>
                <?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?>
            </a>
        <?php endif; ?>
    </div>
</aside>

<main class="pt-16">
    <?php if ($isFallbackMode): ?>
    <section class="bg-amber-50 border-b border-amber-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 text-sm text-amber-700 flex flex-wrap items-center justify-between gap-2">
            <span>Đang chạy bằng dữ liệu demo fallback để giao diện luôn hoạt động ổn định.</span>
            <a href="<?= $roomsUrl ?>" class="font-semibold hover:underline">Xem phòng ngay</a>
        </div>
    </section>
    <?php endif; ?>
