<?php
// Gom sẵn một số giá trị để view gọn hơn và không phải lặp lại biểu thức dài.
$selectedArea = $selectedArea ?? null;
$areas = $areas ?? [];
$featureOptions = $featureOptions ?? [];
$filters = $filters ?? [];
$selectedAmenities = $filters['amenities'] ?? [];
$filterMessages = $filters['messages'] ?? [];
// Giữ sẵn URL reset để mọi hành động xóa lọc đều quay về đúng trang danh sách phòng.
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
            <!-- Sidebar chỉ chứa các bộ lọc thực sự cần cho người đang tìm trọ. -->
            <aside class="lg:col-span-1">
                <form method="GET" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20" data-room-filter-form data-price-min-gap="500000">
                    <input type="hidden" name="page" value="rooms">

                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">filter_list</span>
                        Bộ lọc tìm phòng
                    </h3>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2">Khu</label>
                        <select name="area_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
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
                            <input type="text" name="min_price" list="price-suggestion-start"
                                   value="<?= e($filters['min_price_display'] ?? '') ?>"
                                   class="px-3 py-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Từ giá" data-price-input="start" autocomplete="off">
                            <input type="text" name="max_price" list="price-suggestion-end"
                                   value="<?= e($filters['max_price_display'] ?? '') ?>"
                                   class="px-3 py-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Đến giá" data-price-input="end" autocomplete="off">
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-price-helper>
                            Nhập ngắn như `2`, `2.5`, `1500`, `2 triệu`. Hệ thống luôn giữ khoảng cách tối thiểu 500.000đ.
                        </p>

                        <datalist id="price-suggestion-start">
                            <option value="1 triệu"></option>
                            <option value="1.5 triệu"></option>
                            <option value="2 triệu"></option>
                            <option value="2.5 triệu"></option>
                            <option value="3 triệu"></option>
                            <option value="3.5 triệu"></option>
                            <option value="4 triệu"></option>
                        </datalist>
                        <datalist id="price-suggestion-end">
                            <option value="1.5 triệu"></option>
                            <option value="2 triệu"></option>
                            <option value="2.5 triệu"></option>
                            <option value="3 triệu"></option>
                            <option value="3.5 triệu"></option>
                            <option value="4 triệu"></option>
                            <option value="5 triệu"></option>
                            <option value="6 triệu"></option>
                        </datalist>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3">Tiện ích</label>
                        <div class="space-y-3">
                            <?php foreach ($featureOptions as $feature): ?>
                                <label class="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 hover:border-primary/40 hover:bg-primary/5 transition cursor-pointer">
                                    <input type="checkbox" name="amenities[]" value="<?= e($feature['key'] ?? '') ?>"
                                           class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary"
                                           <?= in_array($feature['key'] ?? '', $selectedAmenities, true) ? 'checked' : '' ?>>
                                    <span class="material-symbols-outlined text-primary text-base"><?= e($feature['icon'] ?? 'check') ?></span>
                                    <span class="text-sm font-medium text-gray-700"><?= e($feature['label'] ?? 'Chưa có dữ liệu') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition mb-2">
                        Tìm phòng
                    </button>
                    <a href="<?= e($roomFilterBaseUrl) ?>" class="block w-full py-3 text-center text-gray-600 hover:text-primary transition">
                        Xóa bộ lọc
                    </a>
                </form>
            </aside>
            
            <!-- Danh sách phòng chỉ hiện các lựa chọn còn khả dụng cho khách thuê. -->
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
                        <?php foreach ($rooms as $room): ?>
                            <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)($room['id'] ?? 0) ?>" 
                               class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 card-hover block">
                                <div class="relative aspect-video overflow-hidden">
                                    <img src="<?= e($room['thumbnail'] ?? '') ?>" alt="<?= e($room['name'] ?? 'Phòng trọ') ?>" 
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                    <span class="absolute top-4 right-4 px-3 py-1 <?= e($room['availabilityClass'] ?? 'bg-gray-500') ?> text-white text-xs rounded-full font-semibold">
                                        <?= e($room['availabilityLabel'] ?? 'Chưa có dữ liệu') ?>
                                    </span>
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
