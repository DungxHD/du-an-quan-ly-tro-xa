<?php
$hero = $hero ?? [];
$heroImage = $hero['heroImage'] ?? 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600';
$siteSlogan = $hero['siteSlogan'] ?? 'Hệ thống trọ cao cấp';
$siteDescription = $hero['siteDescription'] ?? 'Không gian sống gọn gàng, an toàn và thuận tiện cho cư dân.';
$headline1 = $hero['headline1'] ?? 'Không Gian Sống';
$headline2 = $hero['headline2'] ?? 'Chuẩn Mực';
$heroBadges = $heroBadges ?? [];
$quickStats = $quickStats ?? [];
$heroStats = $heroStats ?? [];
$marketingHighlights = $marketingHighlights ?? [];
$livingSteps = $livingSteps ?? [];
$faqItems = $faqItems ?? [];
$testimonials = $testimonials ?? [];
$buildingTypeMap = $buildingTypeMap ?? [];
$siteName = $siteName ?? ($layout['siteName'] ?? 'NhaTroA');
$phone = $layout['contact']['phone'] ?? fallbackText('');
$phoneTel = $layout['contact']['phoneTel'] ?? '';
// Gom URL sang trang rooms để các CTA ở trang chủ luôn chuyển đúng về danh sách phòng.
$roomsPageUrl = BASE_URL . '?page=rooms';
?>

<!-- HERO -->
<section class="relative h-screen min-h-[640px] flex items-center justify-center overflow-hidden">
    <div class="hero-bg absolute inset-0 bg-cover bg-center" 
         style="background-image: url('<?= e($heroImage) ?>');">
    </div>
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900/80 via-gray-900/60 to-primary/40"></div>
    
    <div class="relative z-10 text-center px-4 max-w-5xl mx-auto">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md rounded-full text-white text-sm mb-6 reveal">
            <span class="material-symbols-outlined text-base">verified</span>
            ✨ <?= e($siteSlogan) ?>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight reveal">
            <?= e($headline1) ?><br>
            <span class="bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent"><?= e($headline2) ?></span>
        </h1>
        <p class="text-xl md:text-2xl text-gray-200 mb-10 reveal">
            <?= e($siteDescription) ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center reveal">
            <a href="<?= e($roomsPageUrl) ?>" 
               class="px-8 py-4 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition transform hover:scale-105 shadow-2xl flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">search</span>
                Xem phòng trống
            </a>
            <a href="<?= BASE_URL ?>?page=intro" 
               class="px-8 py-4 bg-white/10 backdrop-blur-md text-white rounded-xl font-semibold hover:bg-white/20 transition border border-white/20 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">play_circle</span>
                Tìm hiểu khu trọ
            </a>
        </div>

        <?php if (!empty($heroBadges)): ?>
        <div class="mt-10 flex flex-wrap justify-center gap-3 reveal">
            <?php foreach ($heroBadges as $badge): ?>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/15 backdrop-blur-md text-white text-sm">
                    <span class="material-symbols-outlined text-base"><?= e($badge['icon'] ?? 'verified') ?></span>
                    <span class="font-semibold"><?= e($badge['label'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($heroStats)): ?>
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto reveal">
            <?php foreach ($heroStats as $stat): ?>
                <div class="rounded-2xl bg-white/10 border border-white/10 backdrop-blur-md px-6 py-5 text-white">
                    <p class="text-3xl font-extrabold">
                        <span data-target="<?= e($stat['value'] ?? 0) ?>">0</span><?= e($stat['suffix'] ?? '') ?>
                    </p>
                    <p class="text-sm text-gray-100/90 mt-1"><?= e($stat['label'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white animate-bounce">
        <span class="material-symbols-outlined text-3xl">keyboard_arrow_down</span>
    </div>
</section>

<!-- QUICK STATS -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative z-20 mb-20">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 stagger-children">
        <?php foreach ($quickStats as $item): ?>
            <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 flex items-center gap-6 card-hover">
                <div class="w-16 h-16 <?= e($item['wrapperClass'] ?? 'bg-primary/10 text-primary') ?> rounded-2xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl"><?= e($item['icon'] ?? 'star') ?></span>
                </div>
                <div>
                    <?php if (!empty($item['useCounter'])): ?>
                        <p class="text-3xl font-bold"><span data-target="<?= e($item['value'] ?? 0) ?>">0</span></p>
                    <?php else: ?>
                        <p class="text-3xl font-bold <?= e($item['valueClass'] ?? '') ?>"><?= e($item['value'] ?? '') ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-500"><?= e($item['label'] ?? '') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($marketingHighlights)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-5 reveal">
                <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    Thông tin rõ ràng để <span class="gradient-text">an tâm chọn phòng</span>
                </h2>
                <p class="text-gray-600 text-lg mb-8">
                    Website được xây như trang giới thiệu chính thức của khu trọ, giúp người đang tìm chỗ ở xem phòng, giá thuê, tiện ích và cách liên hệ một cách rõ ràng trước khi quyết định.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= e($roomsPageUrl) ?>" class="px-6 py-3 rounded-xl bg-primary text-white font-bold hover:opacity-95 transition inline-flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">search</span>
                        Xem phòng ngay
                    </a>
                    <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                        <a href="tel:<?= e($phoneTel) ?>" class="px-6 py-3 rounded-xl bg-white border border-gray-200 font-bold hover:bg-gray-50 transition inline-flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">call</span>
                            <?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lg:col-span-7 grid grid-cols-1 md:grid-cols-3 gap-6 stagger-children">
                <?php foreach ($marketingHighlights as $h): ?>
                    <div class="bg-surface border border-gray-100 rounded-2xl p-7 card-hover">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 flex items-center justify-center mb-5">
                            <span class="material-symbols-outlined text-2xl text-primary"><?= e($h['icon'] ?? 'verified') ?></span>
                        </div>
                        <h3 class="text-lg font-extrabold mb-2"><?= e($h['title'] ?? '') ?></h3>
                        <p class="text-sm text-gray-600"><?= e($h['text'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- AMENITIES -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Tại sao chọn <span class="gradient-text"><?= e($siteName) ?></span>?</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Mọi tiện ích bạn cần cho một cuộc sống tiện nghi và an toàn</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 stagger-children">
            <?php foreach ($amenities as $item): ?>
            <div class="bg-surface p-8 rounded-2xl border border-gray-100 text-center card-hover">
                <div class="w-20 h-20 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl"><?= e($item['icon']) ?></span>
                </div>
                <h3 class="font-bold text-lg mb-2"><?= e($item['title']) ?></h3>
                <p class="text-sm text-gray-500"><?= e($item['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED ROOMS -->
<section id="rooms" class="py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 reveal">
            <div>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Phòng <span class="gradient-text">nổi bật</span></h2>
                <p class="text-gray-600">Các phòng đang trống & sắp trống - Chủ động đặt trước</p>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="flex flex-wrap gap-3 mb-8 reveal">
            <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-full text-sm border border-gray-200">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="font-medium">Còn trống</span>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-full text-sm border border-gray-200">
                <span class="w-3 h-3 bg-amber-500 rounded-full"></span>
                <span class="font-medium">Sắp trống (Đặt trước)</span>
            </div>
        </div>
        
        <?php if (empty($featured)): ?>
        <div class="bg-white p-12 rounded-2xl text-center">
            <p class="text-gray-500">Hiện chưa có phòng trống.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($featured as $room): 
                $isUpcoming = !empty($room['isUpcoming']);
                $daysLeft = $room['daysLeft'] ?? null;
            ?>
            <a href="<?= BASE_URL ?>?page=detail&id=<?= $room['id'] ?>" class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover block">
                <div class="relative aspect-video overflow-hidden">
                    <img src="<?= e($room['thumbnail']) ?>" alt="<?= e($room['name']) ?>" 
                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                    <?php if ($isUpcoming): ?>
                        <div class="absolute top-4 right-4 flex flex-col gap-2 items-end">
                            <span class="px-3 py-1 bg-amber-500 text-white text-xs rounded-full font-semibold flex items-center gap-1 shadow-lg">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                Sắp trống
                            </span>
                            <?php if ($daysLeft !== null): ?>
                            <span class="px-3 py-1 bg-white text-amber-600 text-xs rounded-full font-bold shadow">
                                Còn <?= $daysLeft ?> ngày
                            </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="absolute top-4 right-4 px-3 py-1 bg-green-500 text-white text-xs rounded-full font-semibold shadow-lg">
                            Còn trống
                        </span>
                    <?php endif; ?>
                    <div class="absolute bottom-4 left-4 px-3 py-1 bg-black/50 backdrop-blur-md text-white text-xs rounded-full flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">visibility</span>
                        <?= $room['views'] ?> lượt xem
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-xs text-primary font-semibold mb-2"><?= e($room['building_name']) ?></p>
                    <h3 class="text-xl font-bold mb-3"><?= e($room['name']) ?></h3>
                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">square_foot</span>
                            <?= $room['area'] ?>m²
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">person</span>
                            Max <?= $room['max_occupancy'] ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">layers</span>
                            Tầng <?= $room['floor'] ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500">Giá thuê</p>
                            <p class="text-2xl font-bold text-primary">
                                <?= number_format($room['price']/1000000, 1) ?>M
                                <span class="text-sm font-normal text-gray-500">/tháng</span>
                            </p>
                        </div>
                        <span class="px-4 py-2 <?= $isUpcoming ? 'bg-amber-50 text-amber-600' : 'bg-primary/10 text-primary' ?> rounded-lg text-sm font-semibold">
                            <?= $isUpcoming ? 'Đặt trước →' : 'Chi tiết →' ?>
                        </span>
                    </div>
                    <?php if ($isUpcoming && ($room['expected_vacant_text'] ?? ($room['expectedVacantText'] ?? ''))): ?>
                    <div class="mt-4 pt-3 border-t border-amber-100 bg-amber-50 -mx-6 -mb-6 px-6 py-3 flex items-center gap-2 text-xs text-amber-700">
                        <span class="material-symbols-outlined text-base">info</span>
                        <span>Dự kiến trống từ: <strong><?= e($room['expectedVacantText'] ?? '') ?></strong></span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($livingSteps)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4">Quy trình <span class="gradient-text">3 bước</span> để vào ở</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Nhanh gọn, rõ ràng và không gây mơ hồ về chi phí</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($livingSteps as $step): ?>
                <div class="rounded-2xl bg-surface border border-gray-100 p-8 card-hover">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 flex items-center justify-center">
                            <span class="text-lg font-extrabold text-primary"><?= e($step['step'] ?? '') ?></span>
                        </div>
                        <h3 class="text-xl font-extrabold"><?= e($step['title'] ?? '') ?></h3>
                    </div>
                    <p class="text-sm text-gray-600"><?= e($step['text'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BUILDINGS SECTION -->
<section id="buildings" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Chọn <span class="gradient-text">khu nhà muốn xem</span></h2>
            <p class="text-gray-600">Nhấn vào khu, dãy hoặc tòa bất kỳ để chuyển thẳng sang danh sách phòng còn trống hoặc đã có lịch trả phòng của khu đó.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php 
            foreach ($buildings as $building): 
                $type = $building['type'] ?? 'building';
                $typeInfo = $buildingTypeMap[$type] ?? ($buildingTypeMap['building'] ?? ['Tòa nhà', 'bg-purple-500', 'apartment']);
                $buildingRoomsUrl = BASE_URL . '?page=rooms&building_id=' . (int)($building['id'] ?? 0);
                $openRoomCount = (int)($building['open_room_count'] ?? (($building['available_count'] ?? 0) + ($building['upcoming_count'] ?? 0)));
            ?>
            <a href="<?= e($buildingRoomsUrl) ?>" class="relative rounded-2xl overflow-hidden group card-hover h-80 block">
                <img src="<?= e($building['image'] ?: 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800') ?>" 
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                
                <!-- Type badge -->
                <div class="absolute top-4 left-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1 <?= $typeInfo[1] ?> text-white text-xs rounded-full font-semibold shadow-lg">
                        <span class="material-symbols-outlined text-sm"><?= $typeInfo[2] ?></span>
                        <?= $typeInfo[0] ?>
                    </span>
                </div>
                
                <!-- Room count -->
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 bg-white/90 backdrop-blur-md text-gray-900 text-xs rounded-full font-bold shadow">
                        <?= $openRoomCount ?> phòng đang mở
                    </span>
                </div>
                
                <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                    <h3 class="text-2xl font-bold mb-2"><?= e($building['name']) ?></h3>
                    <p class="text-sm text-gray-200 mb-3 line-clamp-2"><?= e($building['description'] ?? '') ?></p>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs text-white/80">
                            <?= (int)($building['available_count'] ?? 0) ?> trống ngay
                            <?php if ((int)($building['upcoming_count'] ?? 0) > 0): ?>
                                · <?= (int)($building['upcoming_count'] ?? 0) ?> sắp trống
                            <?php endif; ?>
                        </span>
                        <span class="inline-flex items-center gap-1 text-sm font-semibold group-hover:gap-2 transition-all">
                            Xem phòng <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Cư dân <span class="gradient-text">nói gì?</span></h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($testimonials as $t): ?>
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 card-hover">
                <div class="flex text-yellow-400 mb-4">
                    <?php for($i=0; $i<5; $i++): ?>
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">star</span>
                    <?php endfor; ?>
                </div>
                <p class="text-gray-600 italic mb-6">"<?= e($t['text']) ?>"</p>
                <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold">
                        <?= mb_substr($t['name'], 0, 1) ?>
                    </div>
                    <div>
                        <p class="font-bold"><?= e($t['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($t['role']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($faqItems)): ?>
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4">Câu hỏi <span class="gradient-text">thường gặp</span></h2>
            <p class="text-gray-600">Giải đáp nhanh để bạn an tâm đặt lịch</p>
        </div>

        <div class="space-y-4 stagger-children">
            <?php foreach ($faqItems as $item): ?>
                <details class="group bg-surface border border-gray-100 rounded-2xl p-6">
                    <summary class="cursor-pointer list-none flex items-start justify-between gap-4">
                        <span class="font-extrabold text-gray-900"><?= e($item['question'] ?? '') ?></span>
                        <span class="material-symbols-outlined text-gray-500 transition group-open:rotate-180">expand_more</span>
                    </summary>
                    <p class="mt-4 text-sm text-gray-600"><?= e($item['answer'] ?? '') ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-primary to-secondary">
    <div class="max-w-4xl mx-auto px-4 text-center text-white reveal">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">Sẵn sàng tìm ngôi nhà mới?</h2>
        <p class="text-xl mb-10 opacity-90">Liên hệ ngay để được tư vấn và đặt lịch xem phòng miễn phí</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                <a href="tel:<?= e($phoneTel) ?>" class="px-8 py-4 bg-white text-primary rounded-xl font-bold hover:scale-105 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">call</span>
                    Gọi ngay: <?= e($phone) ?>
                </a>
            <?php else: ?>
                <a href="<?= e($roomsPageUrl) ?>" class="px-8 py-4 bg-white text-primary rounded-xl font-bold hover:scale-105 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">search</span>
                    Xem phòng trống
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>?page=register" class="px-8 py-4 bg-white/10 backdrop-blur-md text-white rounded-xl font-bold border-2 border-white/30 hover:bg-white/20 transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">person_add</span>
                Tạo tài khoản để đặt lịch
            </a>
        </div>
    </div>
</section>
