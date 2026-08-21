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

<section class="rooms-page py-12 bg-surface min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rooms-page-header mb-8 reveal">
            <h1 class="text-4xl font-bold mb-2">
                <?= $selectedArea ? 'Phòng trống tại <span class="gradient-text">' . e($selectedArea['name'] ?? '') . '</span>' : 'Danh sách <span class="gradient-text">phòng đang còn trống</span>' ?>
            </h1>
            <p class="text-gray-600">
                <?= $selectedArea
                    ? 'Đang hiển thị các phòng còn trống của khu bạn đã chọn để khách xem và liên hệ trực tiếp.'
                    : 'Tìm thấy ' . count($rooms) . ' phòng còn trống phù hợp với nhu cầu hiện tại.' ?>
            </p>
        </div>

        <!-- Filter messages (from initial load or AJAX) -->
        <div id="filter-messages" class="mb-6 space-y-3">
            <?php if (!empty($filterMessages)): ?>
                <?php foreach ($filterMessages as $message): ?>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <?= e($message) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar bộ lọc -->
            <aside class="lg:col-span-1">
                <form method="GET" id="room-filter-form" class="rooms-filter-panel bg-white p-6 rounded-3xl shadow-card border border-gray-100 sticky top-20" data-price-min-gap="500000">
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
                        <select name="area_id" id="filter-area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none" data-auto-fetch="change">
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
                                placeholder="VD: 1 triệu, 1500k, 2000000" data-price-input="start" autocomplete="off" data-auto-fetch="debounce">
                            <input type="text" name="max_price" id="filter-max_price" list="price-suggestion-end"
                                value="<?= e($filters['max_price_display'] ?? '') ?>"
                                class="px-3 py-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                                placeholder="VD: 3 triệu, 3500k, 5000000" data-price-input="end" autocomplete="off" data-auto-fetch="debounce">
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-price-helper>
                            Hỗ trợ: "2 triệu", "2.5tr", "2500000", "500k". Giá tối thiểu phải nhỏ hơn giá tối đa và cách nhau tối thiểu 500.000đ.
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
                                    data-auto-fetch="change">
                                <span class="material-symbols-outlined text-primary text-base"><?= e($feature['icon'] ?? 'check') ?></span>
                                <span class="text-sm font-medium text-gray-700"><?= e($feature['label'] ?? 'Chưa có dữ liệu') ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </aside>

            <!-- Danh sách phòng -->
            <div class="rooms-results-column lg:col-span-3">
                <div id="rooms-results" class="rooms-context-bar mb-5 flex flex-wrap items-center gap-3">
                    <span class="px-4 py-2 rounded-full bg-white border border-gray-200 text-sm font-semibold text-gray-700" id="rooms-count">
                        <?= count($rooms) ?> phòng phù hợp
                    </span>
                    <?php if ($selectedArea): ?>
                    <span class="px-4 py-2 rounded-full bg-primary/10 text-primary text-sm font-semibold">
                        <?= e($selectedArea['name'] ?? 'Chưa có dữ liệu') ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div id="rooms-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 stagger-children">
                    <?php foreach ($pagedRooms as $room): ?>
                    <?= RoomModel::renderRoomCardHtml($room) ?>
                    <?php endforeach; ?>
                </div>

                <!-- Loading state (hidden by default) -->
                <div id="rooms-loading" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 animate-pulse">
                            <div class="aspect-video bg-gray-200"></div>
                            <div class="p-6 space-y-4">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-6 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-full"></div>
                                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                                <div class="pt-4 border-t border-gray-100">
                                    <div class="h-6 bg-gray-200 rounded w-1/4"></div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Empty state (hidden by default) -->
                <div id="rooms-empty" class="hidden bg-white p-12 rounded-2xl text-center">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">search_off</span>
                    <p class="text-gray-700 font-semibold mb-2">Không tìm thấy phòng phù hợp</p>
                    <p class="text-gray-500">Bạn có thể đổi khu nhà, nới khoảng giá hoặc bỏ bớt tiện ích đang chọn.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($totalPages > 1): ?>
<nav class="mt-10 mb-4 flex flex-wrap items-center justify-center gap-2" aria-label="Phân trang phòng trọ">
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
    // Expose BASE_URL to JavaScript
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script>
// AJAX Filter for rooms page
(function() {
    const filterForm = document.getElementById('room-filter-form');
    const roomsGrid = document.getElementById('rooms-grid');
    const roomsLoading = document.getElementById('rooms-loading');
    const roomsEmpty = document.getElementById('rooms-empty');
    const roomsCount = document.getElementById('rooms-count');
    const filterMessages = document.getElementById('filter-messages');
    const clearBtn = document.getElementById('clear-filters-btn');
    
    if (!filterForm || !roomsGrid) return;

    let debounceTimers = {};
    let requestSeq = 0;
    const API_URL = BASE_URL + '?page=api-rooms-filter';

    // Ngăn browser submit form thủ công (Enter/implicit submit) → reload trang
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    function showLoading() {
        roomsGrid.classList.add('hidden');
        roomsEmpty.classList.add('hidden');
        roomsLoading.classList.remove('hidden');
    }

    function hideLoading() {
        roomsLoading.classList.add('hidden');
    }

    function showEmpty() {
        roomsGrid.classList.add('hidden');
        roomsLoading.classList.add('hidden');
        roomsEmpty.classList.remove('hidden');
    }

    function showResults(html, total, messages) {
        hideLoading();
        if (total > 0) {
            roomsGrid.innerHTML = html;
            roomsGrid.classList.remove('hidden');
            roomsEmpty.classList.add('hidden');
        } else {
            showEmpty();
        }
        if (roomsCount) {
            roomsCount.textContent = total + ' phòng phù hợp';
        }
        // Update filter messages
        if (filterMessages) {
            filterMessages.innerHTML = '';
            if (messages && messages.length > 0) {
                messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700';
                    div.textContent = msg;
                    filterMessages.appendChild(div);
                });
            }
        }
    }

    function showError(message) {
        hideLoading();
        roomsGrid.classList.add('hidden');
        roomsEmpty.classList.add('hidden');
        if (filterMessages) {
            filterMessages.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
            div.textContent = message || 'Có lỗi xảy ra khi tải danh sách phòng. Vui lòng thử lại.';
            filterMessages.appendChild(div);
        }
    }

    // Giá tối thiểu phải nhỏ hơn giá tối đa. Trả về true nếu khoảng giá không hợp lệ.
    function isPriceRangeInvalid() {
        const startInput = filterForm.querySelector('[data-price-input="start"]');
        const endInput = filterForm.querySelector('[data-price-input="end"]');
        const start = typeof parseHumanPriceClient === 'function' && startInput
            ? parseHumanPriceClient(startInput.value) : null;
        const end = typeof parseHumanPriceClient === 'function' && endInput
            ? parseHumanPriceClient(endInput.value) : null;
        return start !== null && end !== null && start >= end;
    }

    function showPriceRangeError() {
        if (filterMessages) {
            filterMessages.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
            div.textContent = 'Giá tối thiểu phải nhỏ hơn giá tối đa. Vui lòng điều chỉnh lại khoảng giá.';
            filterMessages.appendChild(div);
        }
    }

    function buildFetchUrl() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (key === 'amenities[]') {
                params.append('amenities[]', value);
            } else {
                params.append(key, value);
            }
        }
        return API_URL + '&' + params.toString();
    }

    function fetchFilteredRooms() {
        // Chặn khoảng giá không hợp lệ (min >= max) trước khi gọi API
        if (isPriceRangeInvalid()) {
            showPriceRangeError();
            return;
        }

        const seq = ++requestSeq;
        const url = buildFetchUrl();
        showLoading();

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (seq !== requestSeq) return; // bỏ qua response cũ khi user đã thay đổi filter
            if (data.success) {
                showResults(data.rooms, data.total, data.messages);
            } else {
                showError(data.message || 'Không thể tải danh sách phòng');
            }
        })
        .catch(error => {
            if (seq !== requestSeq) return;
            console.error('Fetch error:', error);
            showError('Có lỗi xảy ra khi kết nối máy chủ. Vui lòng thử lại.');
        });
    }

    function debouncedFetch(name, delay) {
        if (debounceTimers[name]) {
            clearTimeout(debounceTimers[name]);
        }
        debounceTimers[name] = setTimeout(fetchFilteredRooms, delay);
    }

    // Price inputs: debounce 800ms on input, immediate on blur/Enter
    const priceInputs = filterForm.querySelectorAll('[data-price-input]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            debouncedFetch(this.name, 800);
        });
        input.addEventListener('blur', function() {
            if (debounceTimers[this.name]) {
                clearTimeout(debounceTimers[this.name]);
            }
            fetchFilteredRooms();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (debounceTimers[this.name]) {
                    clearTimeout(debounceTimers[this.name]);
                }
                fetchFilteredRooms();
            }
        });
    });

    // Area select & checkboxes: immediate fetch on change
    const autoFetchInputs = filterForm.querySelectorAll('[data-auto-fetch="change"]');
    autoFetchInputs.forEach(input => {
        input.addEventListener('change', fetchFilteredRooms);
    });

    // Clear filters button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const inputs = filterForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else if (input.tagName === 'SELECT') {
                    input.value = '';
                } else if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            fetchFilteredRooms();
        });
    }
})();
</script>