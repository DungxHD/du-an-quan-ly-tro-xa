<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Thông tin phòng của bạn';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$supportPhone = RoomModel::getSetting('contact_phone', 'Chưa có dữ liệu');
$supportEmail = RoomModel::getSetting('contact_email', 'Chưa có dữ liệu');
$roomExtra = $roomExtra ?? null;
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<?php if ($room): ?>
<?php
$occupantList = (array)($roomExtra['occupants_list'] ?? []);
$occupants = count($occupantList);
$maxOcc = max(1, (int)($roomExtra['max'] ?? ($room['max_occupancy'] ?? 1)));
$freeSlots = max(0, $maxOcc - $occupants);
$canAdd = $occupants < $maxOcc;
$amenities = (array)($roomExtra['amenities'] ?? []);
?>
<!-- [DEV-QWEN-A][NHOM-2][ROOM-INFO-V2] Chi tiết mở sẵn; ô số người bấm để xem danh sách cư dân -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
    <div class="p-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-primary text-4xl">door_open</span>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Thông tin phòng: <?= e($room['name']) ?></h2>
                <p class="text-sm text-gray-500 mt-1"><?= e((string)($roomExtra['area_name'] ?? '')) ?> · <?= e((string)($roomExtra['floor_name'] ?? '')) ?> · <?= number_format((float)$room['price']) ?> ₫/tháng</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($canAdd): ?>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">Còn <?= $freeSlots ?> chỗ trống</span>
            <?php else: ?>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-600 whitespace-nowrap">Đã đủ <?= $maxOcc ?> người</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-t border-gray-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <img src="<?= e((string)($room['thumbnail'] ?? '')) ?>" class="w-full rounded-xl aspect-video object-cover bg-gray-100" alt="<?= e($room['name']) ?>">
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">home</span> <?= e($room['name']) ?></div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">square_foot</span> <?= (float)$room['area'] ?> m²</div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">payments</span> <?= number_format((float)$room['price']) ?> ₫/tháng</div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">layers</span> <?= e((string)($roomExtra['floor_name'] ?? '')) ?></div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">apartment</span> <?= e((string)($roomExtra['area_name'] ?? '')) ?></div>
                <button type="button" onclick="toggleOccupantList()" title="Bấm để xem ai đang ở" class="flex items-center gap-2 bg-primary/10 rounded-xl px-3 py-2 hover:bg-primary/20 transition text-left cursor-pointer">
                    <span class="material-symbols-outlined text-primary">group</span>
                    <span class="font-semibold text-primary"><?= $occupants ?>/<?= $maxOcc ?> người</span>
                    <span id="occupantChevron" class="material-symbols-outlined text-primary text-base ml-auto">expand_more</span>
                </button>
            </div>
        </div>

        <div id="occupantList" class="hidden mt-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
            <h5 class="text-xs font-bold text-gray-500 uppercase mb-3">Cư dân đang ở phòng này</h5>
            <?php if ($occupantList): ?>
            <div class="space-y-2">
                <?php foreach ($occupantList as $occ): ?>
                <div class="flex items-center gap-3 bg-white rounded-xl px-3 py-2 border border-gray-100">
                    <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm"><?= e(mb_strtoupper(mb_substr(trim((string)($occ['full_name'] ?? '?')), 0, 1, 'UTF-8'))) ?></span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= e((string)($occ['full_name'] ?? '')) ?></p>
                        <p class="text-xs text-gray-500"><?= e((string)($occ['email'] ?? '')) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa có cư dân nào được gán vào phòng.</p>
            <?php endif; ?>
        </div>

        <div class="mt-6">
            <h4 class="font-bold text-sm text-gray-700 mb-2">Tiện ích trong phòng</h4>
            <?php if ($amenities): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($amenities as $am): ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?= e($am) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa cập nhật tiện ích.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5">
            <h4 class="font-bold text-sm text-gray-700 mb-2">Dịch vụ phòng đã đăng ký</h4>
            <?php if (!empty($roomServices)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($roomServices as $s): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-sm"><?= e((string)($s['icon'] ?? 'settings')) ?></span>
                    <?= e((string)($s['name'] ?? '')) ?><?= ((int)($s['quantity'] ?? 1) > 1) ? ' ×' . (int)$s['quantity'] : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa đăng ký dịch vụ phòng nào.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5">
            <h4 class="font-bold text-sm text-gray-700 mb-2">Dịch vụ cá nhân đã đăng ký</h4>
            <?php if (!empty($myServices)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($myServices as $s): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">
                    <span class="material-symbols-outlined text-sm"><?= e((string)($s['icon'] ?? 'settings')) ?></span>
                    <?= e((string)($s['name'] ?? '')) ?><?= ((int)($s['quantity'] ?? 1) > 1) ? ' ×' . (int)$s['quantity'] : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Bạn chưa đăng ký dịch vụ cá nhân nào. <a href="<?= BASE_URL ?>?page=tenant-services" class="text-primary font-semibold hover:underline">Xem & đăng ký</a></p>
            <?php endif; ?>
        </div>

        <div class="mt-6 p-4 rounded-xl <?= $canAdd ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' ?>">
            <?php if ($canAdd): ?>
            <p class="text-sm text-green-800 mb-3">Phòng còn <?= $freeSlots ?> chỗ trống (<?= $occupants ?>/<?= $maxOcc ?> người). Bạn có thể mời người ở ghép để chia sẻ chi phí.</p>
            <a href="<?= BASE_URL ?>?page=tenant-roommate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold hover:opacity-90 transition">
                <span class="material-symbols-outlined text-base">group_add</span> Tìm người ở ghép
            </a>
            <?php else: ?>
            <p class="text-sm text-gray-600">Phòng đã đạt giới hạn <?= $maxOcc ?> người ở (<?= $occupants ?>/<?= $maxOcc ?>) — không thể thêm người ở ghép.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function toggleOccupantList() {
    var el = document.getElementById('occupantList');
    var ch = document.getElementById('occupantChevron');
    if (!el) return;
    var isHidden = el.classList.contains('hidden');
    el.classList.toggle('hidden');
    if (ch) { ch.textContent = isHidden ? 'expand_less' : 'expand_more'; }
}
</script>
<?php else: ?>
<!-- No room assigned -->
<div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">home_work</span>
    <h2 class="text-2xl font-bold mb-2">Bạn chưa được gán vào phòng nào</h2>
    <p class="text-gray-500 mb-6">Vui lòng liên hệ chủ trọ để được gán vào phòng của bạn.</p>
    <div class="bg-blue-50 p-4 rounded-xl text-sm text-gray-600 max-w-md mx-auto">
        <p class="font-semibold mb-2">Liên hệ hỗ trợ:</p>
        <p>Hotline: <?= e($supportPhone) ?></p>
        <p>Email: <?= e($supportEmail) ?></p>
    </div>
</div>
<?php endif; ?>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>