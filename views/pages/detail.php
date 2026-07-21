<?php
// Trang chi tiết giữ CTA rõ ràng để người xem có thể đăng ký hoặc liên hệ ngay. 
$isUpcoming = $room['notice_given'] == 1 && $room['status'] == 'rented';
$daysLeft = $isUpcoming ? RoomModel::getDaysUntilVacant($room['expected_vacant_date']) : null;
?>

<section class="py-12 bg-surface min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="<?= BASE_URL ?>?page=home" class="inline-flex items-center gap-2 text-primary hover:gap-3 transition-all mb-6 reveal">
            <span class="material-symbols-outlined">arrow_back</span> Quay lại trang chủ
        </a>
        
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Gallery -->
            <div class="lg:col-span-3 reveal-left">
                <div class="aspect-video rounded-2xl overflow-hidden mb-4 shadow-xl">
                    <img src="<?= e($room['thumbnail']) ?>" alt="<?= e($room['name']) ?>" class="w-full h-full object-cover">
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <?php for($i=0; $i<4; $i++): ?>
                    <div class="aspect-square rounded-xl overflow-hidden">
                        <img src="<?= e($room['thumbnail']) ?>" class="w-full h-full object-cover">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Info -->
            <div class="lg:col-span-2 reveal-right">
                <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 sticky top-20">
                    <span class="inline-block px-3 py-1 <?= $isUpcoming ? 'bg-amber-100 text-amber-700' : ($room['status'] == 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700') ?> text-xs font-semibold rounded-full mb-3">
                        <?= $isUpcoming ? '⏰ Sắp trống' : ($room['status'] == 'available' ? '✓ Còn trống' : 'Đã thuê') ?>
                    </span>
                    <p class="text-sm text-primary font-semibold mb-1"><?= e($room['building_name']) ?></p>
                    <h1 class="text-3xl font-bold mb-4"><?= e($room['name']) ?></h1>
                    
                    <div class="flex items-center gap-4 text-gray-500 mb-6 pb-6 border-b border-gray-100">
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined">square_foot</span> <?= $room['area'] ?>m²</span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined">person</span> Max <?= $room['max_occupancy'] ?></span>
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined">layers</span> Tầng <?= $room['floor'] ?></span>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-sm text-gray-500">Giá thuê hàng tháng</p>
                        <p class="text-4xl font-bold text-primary">
                            <?= number_format($room['price']/1000000, 1) ?>M
                            <span class="text-base font-normal text-gray-500">/tháng</span>
                        </p>
                    </div>
                    
                    <a href="<?= isset($_SESSION['user_id']) ? (BASE_URL . '?page=tenant') : (BASE_URL . '?page=register') ?>" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] shadow-lg flex items-center justify-center gap-2 mb-2">
                        <span class="material-symbols-outlined">send</span>
                        <?= $isUpcoming ? 'Đặt trước phòng' : 'Tạo yêu cầu thuê' ?>
                    </a>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', RoomModel::getSetting('contact_phone', ''))) ?>" 
                       class="w-full py-3 border-2 border-gray-200 rounded-xl font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">call</span>
                        Gọi: <?= e(RoomModel::getSetting('contact_phone', '')) ?>
                    </a>
                    
                    <?php if ($isUpcoming && $room['expected_vacant_date']): ?>
                    <div class="mt-4 p-3 bg-amber-50 rounded-lg flex items-center gap-2 text-sm text-amber-700">
                        <span class="material-symbols-outlined">info</span>
                        Dự kiến trống từ: <strong><?= date('d/m/Y', strtotime($room['expected_vacant_date'])) ?></strong>
                        <?php if ($daysLeft !== null): ?>(còn <?= $daysLeft ?> ngày)<?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <div class="mt-12 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 reveal">
            <h2 class="text-2xl font-bold mb-4">Mô tả chi tiết</h2>
            <p class="text-gray-600 leading-relaxed"><?= nl2br(e($room['description'])) ?></p>
        </div>
        
        <!-- Comments -->
        <div class="mt-8 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 reveal">
            <h2 class="text-2xl font-bold mb-6">Đánh giá (<?= count($comments) ?>)</h2>
            <div class="space-y-4">
                <?php if (empty($comments)): ?>
                <p class="text-gray-400 text-center py-8">Chưa có đánh giá nào</p>
                <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <div class="flex gap-4 p-4 bg-surface rounded-xl">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                        <?= mb_substr($c['full_name'], 0, 1) ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold"><?= e($c['full_name']) ?></p>
                            <div class="flex text-yellow-400">
                                <?php for($i=0; $i<$c['rating']; $i++): ?>
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="text-gray-700"><?= nl2br(e($c['content'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
