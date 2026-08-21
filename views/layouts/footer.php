</main>

<footer class="nta-public-footer mt-20 border-t">
    <?php
    $layout = $layout ?? [];
    $siteName = $layout['siteName'] ?? 'NhaTroA';
    $metaDescription = $layout['meta']['description'] ?? '';
    $urls = $layout['urls'] ?? [];
    $homeUrl = $urls['home'] ?? (BASE_URL . '?page=home');
    $roomsUrl = $urls['rooms'] ?? (BASE_URL . '?page=rooms');
    $introUrl = $urls['intro'] ?? (BASE_URL . '?page=intro');
    $contactAddress = $layout['contact']['address'] ?? fallbackText('');
    $contactPhone = $layout['contact']['phone'] ?? fallbackText('');
    $contactEmail = $layout['contact']['email'] ?? fallbackText('');
    ?>
    <div class="public-footer-inner w-[min(100%-28px,1240px)] mx-auto py-14">
        <div class="grid grid-cols-1 md:grid-cols-[1.35fr_1fr_1fr] gap-10 stagger-children reveal-slide-up">
            <div class="public-footer-brand">
                <a href="<?= $homeUrl ?>" class="inline-flex items-center gap-3 text-white no-underline" data-cms="site_name">
                    <span class="public-brand-mark"><span class="material-symbols-outlined" aria-hidden="true">home</span></span>
                    <span class="text-xl font-bold"><?= e($siteName) ?></span>
                </a>
                <p class="mt-5 max-w-md text-sm leading-7" data-cms="site_description"><?= e($metaDescription) ?></p>
                <p class="mt-6 text-xs uppercase tracking-[.16em] text-white/45">Sống rõ ràng · ở dễ chịu</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Liên hệ</h4>
                <div class="space-y-3 text-sm">
                    <p class="public-footer-contact flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white/65" aria-hidden="true">location_on</span>
                        <span data-cms="contact_address"><?= e($contactAddress) ?></span>
                    </p>
                    <p class="public-footer-contact flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white/65" aria-hidden="true">call</span>
                        <span data-cms="contact_phone"><?= e($contactPhone) ?></span>
                    </p>
                    <p class="public-footer-contact flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white/65" aria-hidden="true">mail</span>
                        <span data-cms="contact_email"><?= e($contactEmail) ?></span>
                    </p>
                </div>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Khám phá</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= $homeUrl ?>">Trang chủ</a></li>
                    <li><a href="<?= $roomsUrl ?>">Phòng trọ</a></li>
                    <li><a href="<?= $introUrl ?>">Giới thiệu</a></li>
                </ul>
            </div>
        </div>
        <div class="public-footer-bottom border-t mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span>&copy; <?= date('Y') ?> <span data-cms="site_name"><?= e($siteName) ?></span>. All rights reserved.</span>
            <span class="text-white/45">Thông tin rõ ràng trước khi bạn đến xem phòng.</span>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
