<?php
$hero = $hero ?? [];
$heroImage = $hero['heroImage'] ?? 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600';
$siteSlogan = $hero['siteSlogan'] ?? 'Trang chính thức của khu trọ';
$siteDescription = $hero['siteDescription'] ?? 'Xem phòng trống, tiện ích và khu nhà rõ ràng trước khi liên hệ.';
$headline1 = $hero['headline1'] ?? 'Xem Phòng Rõ';
$headline2 = $hero['headline2'] ?? 'Chọn Chỗ Ở Dễ';
$heroBadges = $heroBadges ?? [];
$quickStats = $quickStats ?? [];
$heroStats = $heroStats ?? [];
$marketingHighlights = $marketingHighlights ?? [];
$amenities = $amenities ?? [];
$featured = $featured ?? [];
$livingSteps = $livingSteps ?? [];
$areaShowcase = $areaShowcase ?? [];
$faqItems = $faqItems ?? [];
$siteName = $siteName ?? ($layout['siteName'] ?? 'NhaTroA');
$phone = $layout['contact']['phone'] ?? fallbackText('');
$phoneTel = trim((string)($layout['contact']['phoneTel'] ?? ''));
$roomsPageUrl = BASE_URL . '?page=rooms';
?>

<section class="home-hero relative overflow-hidden bg-surface">
    <div class="home-hero-grid max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="home-hero-copy relative z-10 flex flex-col justify-center">
            <div class="home-hero-kicker inline-flex items-center gap-2 reveal">
                <span class="material-symbols-outlined text-base">verified_user</span>
                <span data-cms="site_slogan"><?= e($siteSlogan) ?></span>
            </div>
            <h1 class="home-hero-title text-5xl md:text-7xl font-black leading-[.98] mt-6 mb-6 reveal">
                <?= e($headline1) ?><br>
                <span><?= e($headline2) ?></span>
            </h1>
            <p class="home-hero-description text-lg md:text-xl max-w-xl mb-8 reveal" data-cms="hero_subheadline">
                <?= e($siteDescription) ?>
            </p>
            <div class="flex flex-col sm:flex-row gap-3 reveal">
                <a href="<?= e($roomsPageUrl) ?>" class="nta-button nta-button--primary px-6 py-3.5">
                    <span class="material-symbols-outlined text-base">search</span>
                    Xem phòng trống
                </a>
                <a href="<?= BASE_URL ?>?page=intro" class="nta-button nta-button--outline px-6 py-3.5">
                    <span class="material-symbols-outlined text-base">arrow_outward</span>
                    Tìm hiểu khu trọ
                </a>
            </div>

            <?php if (!empty($heroBadges)): ?>
                <div class="home-hero-badges mt-9 flex flex-wrap gap-2 reveal">
                    <?php foreach ($heroBadges as $badge): ?>
                        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-full border text-sm">
                            <span class="material-symbols-outlined text-base"><?= e($badge['icon'] ?? 'verified') ?></span>
                            <span class="font-semibold"><?= e($badge['label'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="home-hero-visual relative reveal-right">
            <div class="home-hero-image-wrap">
                <div class="hero-bg absolute inset-0 bg-cover bg-center" data-cms="hero_image" style="background-image: url('<?= e($heroImage) ?>');"></div>
                <div class="home-hero-image-overlay absolute inset-0"></div>
                <div class="home-hero-image-caption absolute left-5 right-5 bottom-5 flex items-end justify-between gap-4 text-white">
                    <div>
                        <p class="text-xs uppercase tracking-[.18em] text-white/70">NhaTroA / living, clearly</p>
                        <p class="mt-2 text-lg font-bold">Một nơi ở được mô tả đủ rõ trước khi bạn đến xem.</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl">north_east</span>
                </div>
            </div>

            <?php if (!empty($heroStats)): ?>
                <div class="home-stat-rail absolute -bottom-6 -left-4 sm:left-6 lg:-left-10 grid grid-cols-<?= min(3, max(1, count($heroStats))) ?> gap-2 sm:gap-3 reveal-scale">
                    <?php foreach ($heroStats as $stat): ?>
                        <div class="home-stat-card px-4 py-3 sm:px-5 sm:py-4">
                            <p class="text-2xl sm:text-3xl font-extrabold nta-mono">
                                <span data-target="<?= e($stat['value'] ?? 0) ?>">0</span><?= e($stat['suffix'] ?? '') ?>
                            </p>
                            <p class="text-xs mt-1"><?= e($stat['label'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="home-scroll-hint absolute bottom-6 right-6 hidden md:flex items-center gap-2 text-xs font-semibold">
        <span>Cuộn để khám phá</span>
        <span class="material-symbols-outlined text-base">south</span>
    </div>
</section>

<?php if (!empty($quickStats)): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 relative z-20 mb-20">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 stagger-children">
        <?php foreach ($quickStats as $item): ?>
            <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 flex items-center gap-5 card-hover">
                <div class="w-16 h-16 <?= e($item['wrapperClass'] ?? 'bg-primary/10 text-primary') ?> rounded-2xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl"><?= e($item['icon'] ?? 'bar_chart') ?></span>
                </div>
                <div>
                    <p class="text-3xl font-bold">
                        <?php if (!empty($item['useCounter'])): ?>
                            <span data-target="<?= e($item['value'] ?? 0) ?>">0</span>
                        <?php else: ?>
                            <?= e($item['value'] ?? '') ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-sm text-gray-500"><?= e($item['label'] ?? '') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($marketingHighlights)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-5 reveal">
                <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    Thông tin đủ rõ để <span class="gradient-text">quyết định nhanh hơn</span>
                </h2>
                <p class="text-gray-600 text-lg mb-8">
                    Trang chủ không chỉ trưng bày hình ảnh. Mục tiêu là giúp người xem biết ngay khu nào đang có phòng, tiện ích nào đang hoạt động và nên bấm vào đâu để xem danh sách phòng trống.
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
                <?php foreach ($marketingHighlights as $item): ?>
                    <div class="bg-surface border border-gray-100 rounded-2xl p-7 card-hover">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-gray-100 flex items-center justify-center mb-5">
                            <span class="material-symbols-outlined text-2xl text-primary"><?= e($item['icon'] ?? 'verified') ?></span>
                        </div>
                        <h3 class="text-lg font-extrabold mb-2"><?= e($item['title'] ?? '') ?></h3>
                        <p class="text-sm text-gray-600"><?= e($item['text'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-amenity-dropzone>
        <div class="text-center mb-14 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Tiện ích đang có tại <span class="gradient-text" data-cms="site_name"><?= e($siteName) ?></span></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Danh sách này chỉ hiển thị các tiện ích đang hoạt động để khách xem không bị nhiễu thông tin.</p>
        </div>

        <?php if (empty($amenities) && empty($GLOBALS['cmsPreviewAdmin'])): ?>
            <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-500">
                Chưa có tiện ích nào được bật hiển thị.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 stagger-children">
                <?php foreach ($amenities as $item): ?>
                    <div class="relative group bg-white p-6 rounded-2xl border border-gray-100 text-center card-hover hover:border-primary/30 transition" data-amenity-id="<?= (int)($item['id'] ?? 0) ?>">
                        <?php if (!empty($GLOBALS['cmsPreviewAdmin'])): ?>
                            <button type="button"
                                class="cms-amenity-remove absolute top-2 right-2 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center text-lg font-bold shadow hover:bg-red-600 transition"
                                title="Gỡ tiện ích khỏi website"
                                data-amenity-remove="<?= (int)($item['id'] ?? 0) ?>">−</button>
                        <?php endif; ?>
                        <div class="w-20 h-20 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4 transition group-hover:bg-primary group-hover:text-white">
                            <span class="material-symbols-outlined text-4xl"><?= e($item['icon'] ?? 'apartment') ?></span>
                        </div>
                        <h3 class="font-bold text-lg mb-2"><?= e($item['title'] ?? '') ?></h3>
                        <p class="text-sm text-gray-500"><?= e($item['description'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($GLOBALS['cmsPreviewAdmin'])): ?>
                    <?php $slotCount = 8 - (int)count($amenities); ?>
                    <?php for ($i = 0; $i < $slotCount; $i++): ?>
                        <div class="cms-amenity-slot rounded-2xl border-2 border-dashed border-gray-300 min-h-[180px] flex flex-col items-center justify-center text-gray-400"
                            data-drop-slot="<?= (int)count($amenities) + $i ?>"
                            title="Kéo tiện ích vào đây để hiển thị">
                            <span class="material-symbols-outlined text-3xl">add_circle</span>
                            <span class="text-xs mt-1">Thả tiện ích vào đây</span>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="rooms" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-end gap-5 mb-10 reveal">
            <div>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Phòng <span class="gradient-text">nổi bật</span></h2>
                <p class="text-gray-600">Chỉ hiển thị các phòng đang còn trống và được nhiều người xem.</p>
            </div>
            <a href="<?= e($roomsPageUrl) ?>" class="inline-flex items-center gap-2 text-primary font-bold">
                Xem toàn bộ phòng
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="bg-surface p-12 rounded-2xl text-center text-gray-500 border border-gray-100">
                Hiện chưa có phòng trống để nổi bật trên trang chủ.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 stagger-children">
                <?php foreach ($featured as $room): ?>
                    <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($room['id'] ?? 0) ?>" class="bg-surface rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover block">
                        <div class="relative aspect-video overflow-hidden">
                            <img src="<?= e($room['thumbnail'] ?? '') ?>" alt="<?= e($room['name'] ?? '') ?>" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                            <span class="absolute top-4 right-4 px-3 py-1 bg-green-500 text-white text-xs rounded-full font-semibold shadow-lg">
                                Còn trống
                            </span>
                            <div class="absolute bottom-4 left-4 px-3 py-1 bg-black/55 backdrop-blur-md text-white text-xs rounded-full flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                <?= (int)($room['views'] ?? 0) ?> lượt xem
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <p class="text-xs text-primary font-semibold"><?= e($room['area_name'] ?? '') ?></p>
                                <span class="text-xs text-gray-500"><?= e($room['floor_name'] ?? ('Tầng ' . ($room['floor_number'] ?? ''))) ?></span>
                            </div>
                            <h3 class="text-xl font-bold mb-3"><?= e($room['name'] ?? '') ?></h3>
                            <div class="grid grid-cols-3 gap-3 text-sm text-gray-500 mb-5">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">square_foot</span>
                                    <?= e($room['area'] ?? 0) ?>m²
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">groups</span>
                                    <?= (int)($room['max_occupancy'] ?? 0) ?> người
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">layers</span>
                                    T<?= (int)($room['floor_number'] ?? 0) ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div>
                                    <p class="text-xs text-gray-500">Giá thuê</p>
                                    <p class="text-2xl font-bold text-primary">
                                        <?= number_format(((float)($room['price'] ?? 0)) / 1000000, 1) ?>M
                                        <span class="text-sm font-normal text-gray-500">/tháng</span>
                                    </p>
                                </div>
                                <span class="px-4 py-2 bg-primary/10 text-primary rounded-lg text-sm font-semibold">
                                    Xem chi tiết
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($livingSteps)): ?>
<section class="py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4">Quy trình <span class="gradient-text">3 bước</span> để vào ở</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Ngắn gọn, đủ rõ và phù hợp với người đang cần xem phòng nhanh.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($livingSteps as $step): ?>
                <div class="rounded-2xl bg-white border border-gray-100 p-8 card-hover">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center">
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

<section id="areas" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Chọn <span class="gradient-text">khu muốn xem</span></h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Bấm vào từng khu để chuyển thẳng sang danh sách phòng theo `area_id`. Mỗi card hiển thị số tầng, số phòng và trạng thái mở hiện tại.</p>
        </div>

        <?php if (empty($areaShowcase)): ?>
            <div class="bg-surface rounded-2xl border border-gray-100 p-10 text-center text-gray-500">
                Chưa có khu nào để hiển thị trên trang chủ.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 stagger-children">
                <?php foreach ($areaShowcase as $area): ?>
                    <a href="<?= e($area['rooms_url'] ?? $roomsPageUrl) ?>" class="group bg-surface rounded-2xl overflow-hidden border border-gray-100 shadow-sm card-hover block">
                        <div class="relative h-60 overflow-hidden">
                            <img src="<?= e($area['image'] ?? '') ?>" alt="<?= e($area['name'] ?? '') ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent"></div>
                            <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-primary text-white text-xs rounded-full font-semibold shadow-lg">
                                    <span class="material-symbols-outlined text-sm">apartment</span>
                                    Khu nhà
                                </span>
                                <?php if ((int)($area['open_room_count'] ?? 0) > 0): ?>
                                    <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full font-semibold shadow-lg">
                                        <?= (int)($area['open_room_count'] ?? 0) ?> phòng đang mở
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                                <h3 class="text-2xl font-bold mb-2"><?= e($area['name'] ?? '') ?></h3>
                                <p class="text-sm text-gray-200 line-clamp-2"><?= e(fallbackText($area['address'] ?? '', 'Địa chỉ sẽ được cập nhật sau')) ?></p>
                            </div>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-600 mb-4"><?= e(fallbackText($area['description'] ?? '', 'Khu này đang được mở bán phòng với đầy đủ thông tin cơ bản.')) ?></p>
                            <div class="grid grid-cols-3 gap-3 mb-5">
                                <div class="rounded-xl bg-white border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-lg font-bold text-gray-900"><?= (int)($area['floor_count'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500">Tầng</p>
                                </div>
                                <div class="rounded-xl bg-white border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-lg font-bold text-gray-900"><?= (int)($area['room_count'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500">Phòng</p>
                                </div>
                                <div class="rounded-xl bg-white border border-gray-100 px-4 py-3 text-center">
                                    <p class="text-lg font-bold text-green-600"><?= (int)($area['available_count'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500">Trống ngay</p>
                                </div>
                            </div>

                            <?php if (!empty($area['floor_labels'])): ?>
                                <div class="flex flex-wrap gap-2 mb-5">
                                    <?php foreach ($area['floor_labels'] as $label): ?>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold"><?= e($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
                                <div class="text-sm text-gray-500">
                                    <?= (int)($area['available_count'] ?? 0) ?> trống ngay
                                    <?php if ((int)($area['upcoming_count'] ?? 0) > 0): ?>
                                        · <?= (int)($area['upcoming_count'] ?? 0) ?> dự kiến trống
                                    <?php endif; ?>
                                </div>
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary group-hover:gap-2 transition-all">
                                    Xem phòng
                                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($faqItems)): ?>
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4">Câu hỏi <span class="gradient-text">thường gặp</span></h2>
            <p class="text-gray-600">Giải đáp nhanh trước khi bạn đặt lịch xem phòng.</p>
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

<section class="py-20 bg-gradient-to-br from-primary to-secondary">
    <div class="max-w-4xl mx-auto px-4 text-center text-white reveal">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">Muốn xem phòng phù hợp ngay hôm nay?</h2>
        <p class="text-xl mb-10 opacity-90">Bắt đầu từ danh sách phòng trống hoặc liên hệ trực tiếp để được hướng dẫn theo từng khu.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= e($roomsPageUrl) ?>" class="px-8 py-4 bg-white text-primary rounded-xl font-bold hover:scale-105 transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">search</span>
                Xem phòng trống
            </a>
            <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                <a href="tel:<?= e($phoneTel) ?>" class="px-8 py-4 bg-white/10 backdrop-blur-md text-white rounded-xl font-bold border-2 border-white/30 hover:bg-white/20 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">call</span>
                    <?= e($phone !== '' ? $phone : 'Gọi tư vấn') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
