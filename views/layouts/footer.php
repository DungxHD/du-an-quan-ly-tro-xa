</main>

<footer class="nta-public-footer bg-gray-900 text-gray-300 mt-20">
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-white text-xl font-bold mb-4" data-cms="site_name"><?= e($siteName) ?></h3>
                <p class="text-sm" data-cms="site_description"><?= e($metaDescription) ?></p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Liên hệ</h4>
                <p class="text-sm flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-sm">location_on</span>
                    <span data-cms="contact_address"><?= e($contactAddress) ?></span>
                </p>
                <p class="text-sm flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-sm">call</span>
                    <span data-cms="contact_phone"><?= e($contactPhone) ?></span>
                </p>
                <p class="text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">mail</span>
                    <span data-cms="contact_email"><?= e($contactEmail) ?></span>
                </p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4">Liên kết</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= $homeUrl ?>" class="hover:text-white">Trang chủ</a></li>
                    <li><a href="<?= $roomsUrl ?>" class="hover:text-white">Phòng trọ</a></li>
                    <li><a href="<?= $introUrl ?>" class="hover:text-white">Giới thiệu</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
            &copy; <?= date('Y') ?> <span data-cms="site_name"><?= e($siteName) ?></span>. All rights reserved.
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
