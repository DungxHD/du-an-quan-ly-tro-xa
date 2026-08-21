<?php
$siteName = $siteName ?? ($layout['siteName'] ?? 'NhaTroA');
$introStory = $introStory ?? [];
$introValues = $introValues ?? [];
$introStats = $introStats ?? [];
$introJourney = $introJourney ?? [];
$heroBadges = $heroBadges ?? [];
$areasPreview = $areasPreview ?? [];
$introImage = $introImage ?? 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600';
$phone = $layout['contact']['phone'] ?? fallbackText('');
$phoneTel = trim((string)($layout['contact']['phoneTel'] ?? ''));
$roomsPageUrl = BASE_URL . '?page=rooms';
?>

<section class="intro-hero py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="intro-hero-header max-w-4xl mb-14 reveal">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 text-primary text-sm font-semibold mb-5">
                <span class="material-symbols-outlined text-base">home_work</span>
                <?= e($introStory['eyebrow'] ?? 'Giới thiệu khu trọ') ?>
            </span>
            <h1 class="intro-display text-5xl md:text-6xl font-black mb-5">Về <span class="gradient-text" data-cms="site_name"><?= e($siteName) ?></span></h1>
            <p class="text-xl text-gray-600 leading-relaxed">
                Trang này tập trung vào chính khu trọ đang vận hành, không dùng nội dung chung chung kiểu landing page.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-20">
            <div class="reveal-left">
                <img src="<?= e($introImage) ?>" alt="<?= e($siteName) ?>" data-cms="hero_image" class="w-full rounded-3xl shadow-2xl object-cover min-h-[320px]">
            </div>
            <div class="reveal-right">
                <h2 class="text-3xl md:text-4xl font-bold mb-5"><?= e($introStory['title'] ?? '') ?></h2>
                <p class="text-gray-600 leading-relaxed mb-5">
                    <?= e($introStory['text'] ?? '') ?>
                </p>

                <?php if (!empty($heroBadges)): ?>
                    <div class="flex flex-wrap gap-3 mb-6">
                        <?php foreach ($heroBadges as $badge): ?>
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-100 text-sm shadow-sm">
                                <span class="material-symbols-outlined text-base text-primary"><?= e($badge['icon'] ?? 'verified') ?></span>
                                <span class="font-semibold text-gray-700"><?= e($badge['label'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="<?= e($roomsPageUrl) ?>" class="px-6 py-3 rounded-xl bg-primary text-white font-bold hover:opacity-95 transition inline-flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">search</span>
                        Xem phòng trống
                    </a>
                    <?php if ($phoneTel !== '' && preg_match('/^[0-9+]+$/', $phoneTel)): ?>
                        <a href="tel:<?= e($phoneTel) ?>" class="px-6 py-3 rounded-xl bg-white border border-gray-200 font-bold hover:bg-gray-50 transition inline-flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">call</span>
                            <?= e($phone !== '' ? $phone : 'Liên hệ tư vấn') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($introStats)): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 stagger-children">
                <?php foreach ($introStats as $stat): ?>
                    <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm hover:shadow-card text-center card-hover transition-all">
                        <p class="text-4xl font-black gradient-text mb-3">
                            <span data-target="<?= e($stat['value'] ?? 0) ?>">0</span><?= e($stat['suffix'] ?? '') ?>
                        </p>
                        <p class="text-base font-semibold text-gray-900 mb-2"><?= e($stat['label'] ?? '') ?></p>
                        <p class="text-sm text-gray-500"><?= e($stat['note'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($introValues)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Giá trị mà khu trọ này giữ lại</h2>
            <p class="text-gray-600 max-w-3xl mx-auto">Không đẩy mạnh câu chữ màu mè. Điều quan trọng là thông tin đúng, trải nghiệm xem dễ và cảm giác tin cậy khi liên hệ.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($introValues as $value): ?>
                <div class="bg-surface rounded-3xl border border-gray-100 p-8 shadow-sm hover:shadow-card card-hover transition-all">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-2xl"><?= e($value['icon'] ?? 'verified') ?></span>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?= e($value['title'] ?? '') ?></h3>
                    <p class="text-sm text-gray-600"><?= e($value['text'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($introJourney)): ?>
<section class="py-20 bg-surface">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">Cách khu trọ này được tổ chức</h2>
            <p class="text-gray-600">Nhìn từ vận hành thực tế: phải dễ xem, dễ chọn và dễ quay lại tra cứu.</p>
        </div>

        <div class="space-y-6">
            <?php foreach ($introJourney as $index => $item): ?>
                <div class="bg-white rounded-3xl border border-gray-100 p-6 md:p-8 shadow-sm hover:shadow-card transition-all reveal">
                    <div class="flex flex-col md:flex-row md:items-start gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black text-xl shrink-0">
                            <?= $index + 1 ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2"><?= e($item['title'] ?? '') ?></h3>
                            <p class="text-gray-600"><?= e($item['text'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($areasPreview)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12 reveal">
            <div>
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Một vài khu đang vận hành</h2>
                <p class="text-gray-600">Mỗi khu đều có cấu trúc tầng và số lượng phòng riêng để người xem định hình nhanh không gian sống.</p>
            </div>
            <a href="<?= e($roomsPageUrl) ?>" class="inline-flex items-center gap-2 text-primary font-bold">
                Xem danh sách phòng
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-children">
            <?php foreach ($areasPreview as $area): ?>
                <div class="bg-surface rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-card card-hover transition-all">
                    <img src="<?= e(!empty($area['image']) ? $area['image'] : $introImage) ?>" alt="<?= e($area['name'] ?? '') ?>" class="w-full h-52 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?= e($area['name'] ?? '') ?></h3>
                        <p class="text-sm text-gray-600 mb-5"><?= e(fallbackText($area['address'] ?? '', 'Địa chỉ đang được cập nhật.')) ?></p>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl bg-white border border-gray-100 px-3 py-4">
                                <p class="text-lg font-bold"><?= (int)($area['floor_count'] ?? 0) ?></p>
                                <p class="text-xs text-gray-500">Tầng</p>
                            </div>
                            <div class="rounded-xl bg-white border border-gray-100 px-3 py-4">
                                <p class="text-lg font-bold"><?= (int)($area['room_count'] ?? 0) ?></p>
                                <p class="text-xs text-gray-500">Phòng</p>
                            </div>
                            <div class="rounded-xl bg-white border border-gray-100 px-3 py-4">
                                <p class="text-lg font-bold text-green-600"><?= (int)($area['available_count'] ?? 0) ?></p>
                                <p class="text-xs text-gray-500">Trống</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
