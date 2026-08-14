<?php
// [DEV-QWEN-A][NHOM-2][PAGINATION] Phân trang 10 phòng/trang, giữ filter khi chuyển trang
$roomsPerPage = 10;
$totalRooms = count($rooms);
$totalPages = max(1, (int)ceil($totalRooms / $roomsPerPage));
$currentPage = max(1, min((int)($_GET['p'] ?? 1), $totalPages));
$pagedRooms = array_slice($rooms, ($currentPage - 1) * $roomsPerPage, $roomsPerPage);
$buildPageUrl = static function (int $page) use ($filters, $selectedArea) {
    $params = ['page' => 'rooms', 'p' => $page];
    if (!empty($filters['area_id'])) $params['area_id'] = $filters['area_id'];
    if (($filters['min_price_input'] ?? '') !== '') $params['min_price'] = $filters['min_price_input'];
    if (($filters['max_price_input'] ?? '') !== '') $params['max_price'] = $filters['max_price_input'];
    if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
        foreach ($filters['amenities'] as $am) { $params['amenities'][] = $am; }
    }
    return BASE_URL . '?' . http_build_query($params);
};

// Gom sẵn một số giá trị để view gọn hơn và không phải lặp lại biểu thức dài.
$selectedArea = $selectedArea ?? null;
$areas = $areas ?? [];
$featureOptions = $featureOptions ?? [];
$filters = $filters ?? [];
$selectedAmenities = $filters['amenities'] ?? [];
$filterMessages = $filters['messages'] ?? [];
$roomFilterBaseUrl = BASE_URL . '?page=rooms';
?>

<section class="py-12 bg-surface min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 reveal">
            <h1 class="text-4xl font-bold mb-2">
                <?= $selectedArea ? 'Phòng trống tại <span class="gradient-text">' . e($selectedArea['name'] ?? '') . '</span>' : 'Danh sách <span class="gradient-text">phòng đang còn trống</span>' ?>
            </h1>
            <p class="text-gray-600">
                <?= $selectedArea
                    ? 'Đang hiển thị các phòng còn trống của khu bạn đã chọn để khách xem và liên hệ trực tiếp.'
                    : 'Tìm thấy ' . count($rooms) . ' phòng còn trống phù hợp với nhu cầu hiện tại.' ?>
            </p>
        </div>

        <?php if (!empty($filterMessages)): ?>
        <div class="mb-6 space-y-3">
            <?php foreach ($filterMessages as $message): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                <?= e($message) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar bộ lọc -->
            <aside class="lg:col-span-1">
                <form method="GET" id="room-filter-form" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20" data-price-min-gap="500000">
                    <input type="hidden" name="page" value="rooms">

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">filter_list</span>
                            Bộ lọc tìm phòng
                        </h3>
                        <button type="button" id="clear-filters-btn" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 hover:text-primary transition" title="Xóa bộ lọc" aria-label="Xóa bộ lọc">
                            <span class="material-symbols-outlined text-lg">refresh</span>
                        </button>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2">Khu</label>
                        <select name="area_id" id="filter-area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none" data-auto-submit="change">
                            <option value="">Tất cả khu</option>
                            <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)($area['id'] ?? 0) ?>" <?= (int)($filters['area_id'] ?? 0) === (int)($area['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e($area['name'] ?? 'Chưa có dữ liệu') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <label class="block text-sm font-semibold">Khoảng giá</label>
                            <span class="text-xs text-gray-500">Gợi ý nhanh theo triệu và 500k</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" name="min_price" id="filter-min_price" list="price-suggestion-start"
                                value="<?= e($filters['min_price_display'] ?? '') ?>"
                                class="px-3 py-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                                placeholder="VD: 1 triệu, 1500k, 2000000" data-price-input="start" autocomplete="off" data-auto-submit="debounce">
                            <input type="text" name="max_price" id="filter-max_price" list="price-suggestion-end"
                                value="<?= e($filters['max_price_display'] ?? '') ?>"
                                class="px-3 py-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                                placeholder="VD: 3 triệu, 3500k, 5000000" data-price-input="end" autocomplete="off" data-auto-submit="debounce">
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-price-helper>
                            Hỗ trợ: "2 triệu", "2.5tr", "2500000", "500k". Khoảng cách tối thiểu 500.000đ.
                        </p>
                        <datalist id="price-suggestion-start">
                            <option value="500 nghìn"></option>
                            <option value="1 triệu"></option>
                            <option value="1.5 triệu"></option>
                            <option value="2 triệu"></option>
                            <option value="2.5 triệu"></option>
                            <option value="3 triệu"></option>
                            <option value="3.5 triệu"></option>
                            <option value="4 triệu"></option>
                            <option value="5 triệu"></option>
                        </datalist>
                        <datalist id="price-suggestion-end">
                            <option value="1 triệu"></option>
                            <option value="1.5 triệu"></option>
                            <option value="2 triệu"></option>
                            <option value="2.5 triệu"></option>
                            <option value="3 triệu"></option>
                            <option value="3.5 triệu"></option>
                            <option value="4 triệu"></option>
                            <option value="5 triệu"></option>
                            <option value="6 triệu"></option>
                            <option value="7 triệu"></option>
                            <option value="8 triệu"></option>
                            <option value="10 triệu"></option>
                        </datalist>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3">Tiện ích</label>
                        <div class="space-y-3">
                            <?php foreach ($featureOptions as $feature): ?>
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 hover:border-primary/40 hover:bg-primary/5 transition cursor-pointer">
                                <input type="checkbox" name="amenities[]" value="<?= e($feature['key'] ?? '') ?>"
                                    class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary"
                                    <?= in_array($feature['key'] ?? '', $selectedAmenities, true) ? 'checked' : '' ?>
                                    data-auto-submit="change">
                                <span class="material-symbols-outlined text-primary text-base"><?= e($feature['icon'] ?? 'check') ?></span>
                                <span class="text-sm font-medium text-gray-700"><?= e($feature['label'] ?? 'Chưa có dữ liệu') ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition mb-2 hidden" id="filter-submit-btn">
                        Tìm phòng
                    </button>
                    <a href="<?= e($roomFilterBaseUrl) ?>" id="clear-filters-link" class="block w-full py-3 text-center text-gray-600 hover:text-primary transition hidden">
                        Xóa bộ lọc
                    </a>
                </form>
            </aside>

            <!-- Danh sách phòng -->
            <div class="lg:col-span-3">
                <?php if (empty($rooms)): ?>
                <div class="bg-white p-12 rounded-2xl text-center">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">search_off</span>
                    <p class="text-gray-700 font-semibold mb-2">Chưa tìm thấy phòng phù hợp</p>
                    <p class="text-gray-500">Bạn có thể đổi khu nhà, nới khoảng giá hoặc bỏ bớt tiện ích đang chọn.</p>
                </div>
                <?php else: ?>
                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <span class="px-4 py-2 rounded-full bg-white border border-gray-200 text-sm font-semibold text-gray-700">
                        <?= count($rooms) ?> phòng phù hợp
                    </span>
                    <?php if ($selectedArea): ?>
                    <span class="px-4 py-2 rounded-full bg-primary/10 text-primary text-sm font-semibold">
                        <?= e($selectedArea['name'] ?? 'Chưa có dữ liệu') ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 stagger-children">
                    <?php foreach ($pagedRooms as $room): ?>
                    <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($room['id'] ?? 0) ?>"
                        class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover block">
                        <div class="relative aspect-video overflow-hidden">
                            <img src="<?= e($room['thumbnail'] ?? '') ?>" alt="<?= e($room['name'] ?? 'Phòng trọ') ?>"
                                class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                            <span class="absolute top-4 right-4 px-3 py-1 <?= e($room['availabilityClass'] ?? 'bg-gray-500') ?> text-white text-xs rounded-full font-semibold">
                                <?= e($room['availabilityLabel'] ?? 'Chưa có dữ liệu') ?>
                            </span>
                            <!-- Badge lượt xem -->
                            <?php $views = (int)($room['views'] ?? 0); ?>
                            <?php if ($views > 0): ?>
                            <span class="absolute top-4 left-4 px-3 py-1 bg-black/60 text-white text-xs rounded-full font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">visibility</span>
                                <?= number_format($views) ?> lượt xem
                            </span>
                            <?php else: ?>
                            <span class="absolute top-4 left-4 px-3 py-1 bg-blue-500/90 text-white text-xs rounded-full font-semibold">
                                Mới đăng
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-xs text-primary font-semibold"><?= e($room['area_name'] ?? 'Chưa có dữ liệu') ?></p>
                                <p class="text-xs text-gray-500"><?= e($room['floor_name'] ?? 'Chưa có dữ liệu') ?></p>
                            </div>
                            <h3 class="text-lg font-bold mb-3"><?= e($room['name'] ?? 'Chưa có dữ liệu') ?></h3>
                            <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">square_foot</span>
                                    <?= e($room['area'] ?? 'Chưa có dữ liệu') ?>m²
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">person</span>
                                    <?= e($room['max_occupancy'] ?? 'Chưa có dữ liệu') ?>
                                </span>
                            </div>

                            <?php if (!empty($room['availabilityNote'])): ?>
                            <p class="mb-4 text-xs font-medium text-green-700">
                                <?= e($room['availabilityNote']) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Hiển thị tiện ích canonical (tối đa 4 + badge +N) -->
                            <?php if (!empty($room['amenity_list']) && is_array($room['amenity_list'])): ?>
                            <div class="mb-4">
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $amenitiesToShow = array_slice($room['amenity_list'], 0, 4);
                                    $remaining = count($room['amenity_list']) - 4;
                                    foreach ($amenitiesToShow as $amenity): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        <span class="material-symbols-outlined text-xs"><?= e($amenity['icon'] ?? 'check') ?></span>
                                        <?= e($amenity['label'] ?? '') ?>
                                    </span>
                                    <?php endforeach; ?>
                                    <?php if ($remaining > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                                        +<?= $remaining ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($room['service_names'])): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php foreach (array_slice($room['service_names'], 0, 3) as $serviceName): ?>
                                <span class="px-3 py-1 rounded-full bg-surface text-gray-600 text-xs font-medium">
                                    <?= e($serviceName) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div>
                                    <p class="text-xs text-gray-500">Giá thuê</p>
                                    <p class="text-2xl font-bold text-primary">
                                        <?= number_format(((float)($room['price'] ?? 0)) / 1000000, 1) ?>M
                                        <span class="text-sm font-normal text-gray-500">/tháng</span>
                                    </p>
                                </div>
                                <span class="text-primary text-sm font-semibold">Xem chi tiết →</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($totalPages > 1): ?>
<nav class="mt-10 mb-4 flex flex-wrap items-center justify-center gap-2" aria-label="Phan trang phong tro">
    <?php if ($currentPage > 1): ?>
    <a href="<?= e($buildPageUrl(1)) ?>" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition" title="Trang đầu">&laquo;</a>
    <a href="<?= e($buildPageUrl($currentPage - 1)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">&lsaquo; Trước</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php if ($i === $currentPage): ?>
    <span class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold shadow-sm"><?= $i ?></span>
    <?php else: ?>
    <a href="<?= e($buildPageUrl($i)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition"><?= $i ?></a>
    <?php endif; ?>
    <?php endfor; ?>
    <?php if ($currentPage < $totalPages): ?>
    <a href="<?= e($buildPageUrl($currentPage + 1)) ?>" class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">Sau &rsaquo;</a>
    <a href="<?= e($buildPageUrl($totalPages)) ?>" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition" title="Trang cuối">&raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<script>
// Auto-submit filter form with debounce for price inputs
(function() {
    const form = document.getElementById('room-filter-form');
    if (!form) return;

    const priceInputs = form.querySelectorAll('[data-price-input]');
    const autoSubmitInputs = form.querySelectorAll('[data-auto-submit]');
    const clearBtn = document.getElementById('clear-filters-btn');
    const submitBtn = document.getElementById('filter-submit-btn');
    const clearLink = document.getElementById('clear-filters-link');

    let debounceTimers = {};

    function submitForm() {
        form.submit();
    }

    function debouncedSubmit(name, delay) {
        if (debounceTimers[name]) {
            clearTimeout(debounceTimers[name]);
        }
        debounceTimers[name] = setTimeout(submitForm, delay);
    }

    // Price inputs: debounce 800ms on input, immediate on blur/Enter
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            debouncedSubmit(this.name, 800);
        });
        input.addEventListener('blur', function() {
            if (debounceTimers[this.name]) {
                clearTimeout(debounceTimers[this.name]);
            }
            // Chỉ submit nếu giá trị thực sự thay đổi so với khi focus
            // (Có thể so sánh với dataset.initialValue nếu cần)
            submitForm();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (debounceTimers[this.name]) {
                    clearTimeout(debounceTimers[this.name]);
                }
                submitForm();
            }
        });
    });

    // Area select & checkboxes: immediate submit on change
    autoSubmitInputs.forEach(input => {
        if (input.dataset.autoSubmit === 'change') {
            input.addEventListener('change', submitForm);
        }
    });

    // Clear filters button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            // Reset form to default (empty)
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else if (input.tagName === 'SELECT') {
                    input.value = '';
                } else if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            submitForm();
        });
    }
})();
</script>