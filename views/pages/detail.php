<?php
// Trang chi tiết dùng dữ liệu đã được controller/model chuẩn hoá để view chỉ tập trung render.
$galleryImages = $room['gallery_images'] ?? [];
$primaryImage = $galleryImages[0] ?? ($room['thumbnail'] ?? '');
$services = $room['services'] ?? [];
$commentBundle = $commentBundle ?? ['public_comments' => [], 'owner_comment' => null, 'public_count' => 0];
$publicComments = $commentBundle['public_comments'] ?? [];
$ownerComment = $commentBundle['owner_comment'] ?? null;
$commentCount = (int)($commentBundle['public_count'] ?? count($publicComments));
$isLoggedIn = isset($_SESSION['user_id']);
$isTenant = $isLoggedIn && (int)($_SESSION['role'] ?? 1) === 0;
$canCreateComment = $isTenant && !$ownerComment && !empty($commentEligibility['allowed']);
$commentBlockedReason = $isTenant && !$ownerComment && empty($commentEligibility['allowed'])
    ? trim((string)($commentEligibility['message'] ?? ''))
    : '';
$requestRentUrl = BASE_URL . '?page=request-rent&id=' . (int)($room['id'] ?? 0);
$registerUrl = BASE_URL . '?page=register';
$contactPhone = RoomModel::getSetting('contact_phone', '');
$phoneTel = preg_replace('/\s+/', '', (string)$contactPhone);
?>

<section class="py-12 bg-surface min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="<?= BASE_URL ?>?page=rooms" class="inline-flex items-center gap-2 text-primary hover:gap-3 transition-all mb-6 reveal">
            <span class="material-symbols-outlined">arrow_back</span> Quay lại danh sách phòng
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Gallery -->
            <div class="lg:col-span-3 reveal-left">
                <div class="aspect-video rounded-2xl overflow-hidden mb-4 shadow-xl">
                    <img src="<?= e($primaryImage) ?>" alt="<?= e($room['name']) ?>" class="w-full h-full object-cover">
                </div>
                <?php $subImages = array_slice($galleryImages, 1); ?>
                <?php if (!empty($subImages)): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($subImages as $subIdx => $imageUrl): ?>
                            <div class="aspect-video rounded-xl overflow-hidden border border-gray-100 bg-white">
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($room['name']) ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="lg:col-span-2 reveal-right">
                <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 sticky top-20">
                    <span class="inline-block px-3 py-1 <?= e(($room['status'] ?? '') === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700') ?> text-xs font-semibold rounded-full mb-3">
                        <?= e($room['availabilityLabel'] ?? 'Đang mở cho thuê') ?>
                    </span>
                    <p class="text-sm text-primary font-semibold mb-1"><?= e($room['area_name'] ?? 'Chưa có khu') ?></p>
                    <h1 class="text-3xl font-bold mb-4"><?= e($room['name']) ?></h1>

                    <div class="grid grid-cols-2 gap-3 text-sm text-gray-600 mb-6 pb-6 border-b border-gray-100">
                        <div class="rounded-xl bg-surface px-4 py-3">
                            <p class="text-xs text-gray-500 mb-1">Khu / Tầng</p>
                            <p class="font-semibold"><?= e($room['location_label'] ?? 'Chưa có dữ liệu') ?></p>
                        </div>
                        <div class="rounded-xl bg-surface px-4 py-3">
                            <p class="text-xs text-gray-500 mb-1">Sức chứa</p>
                            <p class="font-semibold"><?= (int)($room['max_occupancy'] ?? 0) ?> người</p>
                        </div>
                        <div class="rounded-xl bg-surface px-4 py-3">
                            <p class="text-xs text-gray-500 mb-1">Diện tích</p>
                            <p class="font-semibold"><?= e($room['area'] ?? 0) ?> m²</p>
                        </div>
                        <div class="rounded-xl bg-surface px-4 py-3">
                            <p class="text-xs text-gray-500 mb-1">Lượt xem</p>
                            <p class="font-semibold"><?= (int)($room['views'] ?? 0) ?> lượt</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <p class="text-sm text-gray-500">Giá thuê hàng tháng</p>
                        <p class="text-4xl font-bold text-primary">
                            <?= number_format(((float)($room['price'] ?? 0)) / 1000000, 1) ?>M
                            <span class="text-base font-normal text-gray-500">/tháng</span>
                        </p>
                    </div>

                    <?php if (!empty($room['availabilityNote'])): ?>
                        <div class="mb-6 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                            <?= e($room['availabilityNote']) ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?= e($requestRentUrl) ?>" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] shadow-lg flex items-center justify-center gap-2 mb-2">
                        <span class="material-symbols-outlined">send</span>
                        Yêu cầu thuê
                    </a>
                    <?php if (!$isLoggedIn): ?>
                        <p class="mb-3 text-xs text-gray-500 text-center">
                            Cần tài khoản để gửi yêu cầu thuê. <a href="<?= e($registerUrl) ?>" class="font-semibold text-primary hover:underline">Đăng ký ngay</a>
                        </p>
                    <?php endif; ?>

                    <a href="tel:<?= e($phoneTel) ?>"
                        class="w-full py-3 border-2 border-gray-200 rounded-xl font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">call</span>
                        Gọi tư vấn: <?= e($contactPhone) ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="mt-12 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 reveal">
            <h2 class="text-2xl font-bold mb-4">Mô tả chi tiết</h2>
            <p class="text-gray-600 leading-relaxed"><?= nl2br(e($room['description'])) ?></p>
        </div>

        <?php
        /**
         * [DEV-QWEN-A][NHOM-2][2026-08-14]
         * Hiển thị tiện ích đồng nhất với admin và public filter:
         * - Dùng danh sách chuẩn từ RoomModel::getCanonicalAmenities()
         * - Map tiện ích nhập tay (rooms.amenities) + dịch vụ gán (services) về key chuẩn
         * - Tự loại trùng, hiển thị icon Material Symbols
         */
        $canonicalAmenities = RoomModel::getCanonicalAmenities();
        $canonicalKeyMap = [];
        foreach ($canonicalAmenities as $a) {
            $canonicalKeyMap[$a['key']] = $a;
            // Thêm alias lowercase để match dễ dàng
            $canonicalKeyMap[mb_strtolower($a['label'], 'UTF-8')] = $a;
        }

        $rawAmenities = array_values(array_filter(array_map('trim', explode(',', (string)($room['amenities'] ?? '')))));
        $roomServiceLabels = array_values(array_filter(array_map(static fn($svc) => trim((string)($svc['name'] ?? '')), $services ?? [])));

        $merged = [];
        $mergedKeys = [];
        foreach (array_merge($rawAmenities, $roomServiceLabels) as $labelItem) {
            $labelKey = mb_strtolower($labelItem, 'UTF-8');
            if ($labelItem === '' || isset($mergedKeys[$labelKey])) {
                continue;
            }
            $matched = null;
            // Thử match với key chuẩn hoặc label chuẩn
            if (isset($canonicalKeyMap[$labelKey])) {
                $matched = $canonicalKeyMap[$labelKey];
            } else {
                // Thử match một phần (vd: "máy lạnh" match "dieu_hoa")
                foreach ($canonicalAmenities as $ca) {
                    $caKey = mb_strtolower($ca['key'], 'UTF-8');
                    $caLabel = mb_strtolower($ca['label'], 'UTF-8');
                    if (mb_strpos($labelKey, $caKey) !== false || mb_strpos($labelKey, $caLabel) !== false ||
                        mb_strpos($caKey, $labelKey) !== false || mb_strpos($caLabel, $labelKey) !== false) {
                        $matched = $ca;
                        break;
                    }
                }
            }
            if ($matched) {
                $matchedKey = $matched['key'];
                if (!isset($mergedKeys[$matchedKey])) {
                    $mergedKeys[$matchedKey] = true;
                    $merged[] = $matched;
                }
            } else {
                // Tiện ích custom không trong danh sách chuẩn
                if (!isset($mergedKeys[$labelKey])) {
                    $mergedKeys[$labelKey] = true;
                    $merged[] = ['key' => $labelKey, 'label' => $labelItem, 'icon' => 'check'];
                }
            }
        }
        ?>
        <div class="mt-8 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 reveal">
            <h2 class="text-2xl font-bold mb-6">Tiện ích của phòng</h2>
            <?php if (empty($merged)): ?>
                <p class="text-gray-500">Phòng chưa có tiện ích nào được cập nhật.</p>
            <?php else: ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($merged as $amenity): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-semibold">
                            <span class="material-symbols-outlined text-base"><?= e($amenity['icon'] ?? 'check') ?></span>
                            <?= e($amenity['label']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Comments -->
        <div class="mt-8 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 reveal">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-bold">Đánh giá</h2>
                <span class="rounded-full bg-surface px-4 py-2 text-sm font-semibold text-gray-700">
                    <?= $commentCount ?> đánh giá công khai
                </span>
            </div>

            <?php if (!empty($commentMessage)): ?>
                <div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    <?= e($commentMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($commentError)): ?>
                <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span>
                    <?= e($commentError) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <section class="xl:col-span-1">
                    <div class="rounded-2xl border border-gray-100 bg-surface p-5 sticky top-20 space-y-4">
                        <div>
                            <h3 class="text-lg font-bold"><?= $ownerComment ? 'Đánh giá của bạn' : 'Viết đánh giá' ?></h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Tenant đang ở hoặc vừa chuyển đi trong 15 ngày có thể chấm sao và chia sẻ trải nghiệm.
                            </p>
                        </div>

                        <?php if ($ownerComment): ?>
                            <article class="rounded-2xl border border-primary/15 bg-white p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= e($ownerComment['full_name'] ?? 'Bạn') ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?= e($ownerComment['created_at_label'] ?? '') ?></p>
                                    </div>
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' <?= $i <= (int)($ownerComment['rating'] ?? 0) ? 1 : 0 ?>;">star</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <?php if (!empty($ownerComment['visibility_badges'])): ?>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <?php foreach ($ownerComment['visibility_badges'] as $badge): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= e($badge['class'] ?? 'bg-slate-100 text-slate-700') ?>">
                                                <?= e($badge['label'] ?? '') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ownerComment['is_edited'])): ?>
                                    <p class="mt-3 text-xs font-medium text-gray-500">
                                        Đã sửa lúc <?= e($ownerComment['edited_at_label'] ?? '') ?>
                                    </p>
                                <?php endif; ?>

                                <p class="mt-4 text-gray-700 leading-relaxed">
                                    <?= $ownerComment['content'] !== null && $ownerComment['content'] !== ''
                                        ? nl2br(e($ownerComment['content']))
                                        : 'Bạn đã chọn chỉ chấm sao cho phòng này.' ?>
                                </p>

                                <?php if (!empty($ownerComment['can_edit'])): ?>
                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <a href="<?= BASE_URL ?>?page=tenant-edit-comment&id=<?= (int)($ownerComment['id'] ?? 0) ?>" class="px-4 py-2 rounded-xl border border-primary text-primary font-semibold hover:bg-primary/5 transition">
                                            Sửa
                                        </a>
                                        <form method="POST" action="<?= BASE_URL ?>?page=tenant-delete-comment" onsubmit="return confirm('Bạn chắc chắn muốn xóa đánh giá này?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="comment_id" value="<?= (int)($ownerComment['id'] ?? 0) ?>">
                                            <input type="hidden" name="room_id" value="<?= (int)($ownerComment['room_id'] ?? 0) ?>">
                                            <button type="submit" class="px-4 py-2 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                    <p class="mt-3 text-xs text-gray-500">
                                        Bạn còn quyền sửa/xóa đến <?= e($ownerComment['edit_deadline'] ?? '') ?>.
                                    </p>
                                <?php else: ?>
                                    <p class="mt-4 text-sm text-amber-700 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                        Đã quá thời hạn 24h. Vui lòng liên hệ admin để sửa hoặc xóa đánh giá này.
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php elseif ($isTenant && $canCreateComment): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-add-comment" class="space-y-4">
                                <?= csrf_field() ?>
                                <input type="hidden" name="room_id" value="<?= (int)($room['id'] ?? 0) ?>">
                                <input type="hidden" name="rating" value="5" data-rating-input>

                                <div>
                                    <label class="block text-sm font-semibold mb-2">Số sao</label>
                                    <div class="flex items-center gap-1" data-rating-widget>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button
                                                type="button"
                                                class="rating-star text-yellow-400 transition hover:scale-110"
                                                data-rating-value="<?= $i ?>"
                                                aria-label="Chọn <?= $i ?> sao">
                                                <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">star</span>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-2">Nội dung</label>
                                    <textarea
                                        name="content"
                                        rows="5"
                                        placeholder="Chia sẻ trải nghiệm của bạn..."
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                                    <p class="mt-2 text-xs text-gray-500">Bạn có thể chỉ chấm sao mà không cần nhập nội dung.</p>
                                </div>

                                <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                                    Gửi đánh giá
                                </button>
                            </form>
                        <?php elseif ($isTenant): ?>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700">
                                <?= e($commentBlockedReason !== '' ? $commentBlockedReason : 'Hiện bạn chưa đủ điều kiện để đánh giá phòng này.') ?>
                            </div>
                        <?php else: ?>
                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm text-gray-600">
                                Đăng nhập bằng tài khoản tenant để gửi đánh giá sau khi bạn ở phòng đủ thời gian quy định.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="xl:col-span-2 space-y-4">
                    <?php if (empty($publicComments) && !$ownerComment): ?>
                        <p class="text-gray-400 text-center py-12 rounded-2xl border border-dashed border-gray-200">
                            Chưa có đánh giá nào cho phòng này.
                        </p>
                    <?php else: ?>
                        <?php foreach ($publicComments as $c): ?>
                            <article class="flex gap-4 p-5 bg-surface rounded-2xl border border-gray-100">
                                <?php if (!empty($c['avatar'])): ?>
                                    <img src="<?= e($c['avatar']) ?>" alt="<?= e($c['full_name']) ?>" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                        <?= e(mb_strtoupper(mb_substr((string)($c['full_name'] ?? 'K'), 0, 1))) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-2">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900"><?= e($c['full_name'] ?? '') ?></p>
                                                <?php if (!empty($c['is_edited'])): ?>
                                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                        Đã sửa <?= e($c['edited_at_label'] ?? '') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-gray-400 mt-1"><?= e($c['created_at_label'] ?? '') ?></p>
                                        </div>
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' <?= $i <= (int)($c['rating'] ?? 0) ? 1 : 0 ?>;">star</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed">
                                        <?= $c['content'] !== null && $c['content'] !== ''
                                            ? nl2br(e($c['content']))
                                            : 'Người dùng chỉ chấm sao cho phòng này.' ?>
                                    </p>
                                    <?php if ($isTenant): ?>
                                        <details class="mt-4 rounded-2xl border border-gray-200 bg-white">
                                            <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between text-sm font-semibold text-rose-600">
                                                <span>Báo cáo đánh giá</span>
                                                <span class="material-symbols-outlined text-base">flag</span>
                                            </summary>
                                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-report-comment" class="px-4 pb-4 space-y-3">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="comment_id" value="<?= (int)($c['id'] ?? 0) ?>">
                                                <input type="hidden" name="room_id" value="<?= (int)($room['id'] ?? 0) ?>">
                                                <textarea
                                                    name="reason"
                                                    rows="3"
                                                    placeholder="Nêu ngắn gọn lý do bạn muốn báo cáo đánh giá này..."
                                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"
                                                    required></textarea>
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 text-white font-semibold hover:bg-rose-600 transition">
                                                    Gửi báo cáo
                                                </button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('[data-rating-widget]').forEach((widget) => {
        const input = widget.parentElement.parentElement.querySelector('[data-rating-input]');
        const buttons = Array.from(widget.querySelectorAll('[data-rating-value]'));

        const paint = (value) => {
            buttons.forEach((button) => {
                const filled = Number(button.dataset.ratingValue) <= Number(value);
                const icon = button.querySelector('.material-symbols-outlined');
                if (icon) {
                    icon.style.fontVariationSettings = filled ? "'FILL' 1" : "'FILL' 0";
                }
            });
        };

        paint(input ? input.value : 5);
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                if (input) {
                    input.value = button.dataset.ratingValue;
                }
                paint(button.dataset.ratingValue);
            });
        });
    });
</script>


<!-- [DEV-QWEN-A][NHOM-2][LIGHTBOX-V2] Click anh chinh/phu de zoom, next/prev, Esc/click ngoai de dong -->
<div id="roomLightbox" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/90" onclick="if(event.target===this) closeRoomLightbox();">
    <button type="button" onclick="closeRoomLightbox()" class="absolute top-4 right-4 z-[10001] w-11 h-11 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/40 text-white text-2xl" aria-label="Dong">&times;</button>
    <button type="button" onclick="roomLightboxStep(-1)" class="absolute left-3 top-1/2 -translate-y-1/2 z-[10001] w-12 h-12 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/40 text-white text-3xl" aria-label="Anh truoc">&#10094;</button>
    <button type="button" onclick="roomLightboxStep(1)" class="absolute right-3 top-1/2 -translate-y-1/2 z-[10001] w-12 h-12 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/40 text-white text-3xl" aria-label="Anh sau">&#10095;</button>
    <img id="roomLightboxImg" src="" alt="Anh phong" class="max-w-[92vw] max-h-[88vh] object-contain rounded-lg" onclick="event.stopPropagation()">
    <div id="roomLightboxCounter" class="absolute bottom-5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-black/50 text-white text-sm"></div>
</div>
<script>
(function(){
    var urls = <?= json_encode(array_values((array)($room['gallery_images'] ?? []))) ?>;
    var lb = document.getElementById('roomLightbox');
    var img = document.getElementById('roomLightboxImg');
    var cnt = document.getElementById('roomLightboxCounter');
    var cur = 0;
    function render(){ if (!urls.length) return; img.src = urls[cur]; cnt.textContent = (cur + 1) + ' / ' + urls.length; }
    window.openRoomLightbox = function(i){ if (!urls.length) return; cur = Math.max(0, Math.min(i | 0, urls.length - 1)); render(); lb.classList.remove('hidden'); lb.classList.add('flex'); document.body.style.overflow = 'hidden'; };
    window.closeRoomLightbox = function(){ lb.classList.add('hidden'); lb.classList.remove('flex'); document.body.style.overflow = ''; };
    window.roomLightboxStep = function(d){ if (!urls.length) return; cur = (cur + d + urls.length) % urls.length; render(); };
    document.addEventListener('keydown', function(e){ if (lb.classList.contains('hidden')) return; if (e.key === 'Escape') closeRoomLightbox(); else if (e.key === 'ArrowLeft') roomLightboxStep(-1); else if (e.key === 'ArrowRight') roomLightboxStep(1); });
    function bind(){
        document.querySelectorAll('img').forEach(function(el){
            if (el.dataset.lbBound) return;
            var oc = el.getAttribute('onclick') || '';
            if (oc.indexOf('openRoomLightbox') !== -1) return;
            var idx = urls.indexOf(el.getAttribute('src') || '');
            if (idx === -1) return;
            el.dataset.lbBound = '1';
            el.style.cursor = 'zoom-in';
            el.addEventListener('click', function(){ window.openRoomLightbox(idx); });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind); else bind();
})();
</script>