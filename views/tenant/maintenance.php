<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'maintenance';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Theo dõi đề xuất bảo trì phòng của bạn';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';

$pendingRequest = $pendingRequest ?? null;
$activeRequest = $activeRequest ?? null;
$message = $message ?? '';
$error = $error ?? '';
$supportPhone = $supportPhone ?? '';
$hasRoom = !empty($user['room_id']);
$formatDate = static function ($value) {
    $value = trim((string)($value ?? ''));
    if ($value === '') { return '—'; }
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : $value;
};
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Bảo trì phòng</h2>
        <p class="mt-2 text-gray-500">Xem đề xuất bảo trì cho phòng của bạn. Nếu không đồng ý, hãy từ chối trước ngày bắt đầu.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$hasRoom): ?>
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-gray-500">
        Bạn chưa được gán phòng nên chưa có đề xuất bảo trì nào.
    </div>

    <?php elseif ($pendingRequest): ?>
    <div class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-amber-500">build</span>
            <h3 class="text-lg font-bold text-gray-900">Đề xuất bảo trì đang chờ</h3>
        </div>
        <div class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm text-gray-600 sm:grid-cols-3">
            <p><span class="font-semibold">Ngày bắt đầu:</span> <?= e($formatDate($pendingRequest['start_date'] ?? '')) ?></p>
            <p><span class="font-semibold">Số ngày:</span> <?= (int)($pendingRequest['duration_days'] ?? 0) ?> ngày</p>
            <p><span class="font-semibold">Trạng thái:</span> Đang chờ phản hồi</p>
        </div>
        <p class="mt-3 text-sm text-gray-700"><span class="font-semibold">Lý do:</span> <?= e($pendingRequest['reason'] ?? '') ?></p>
        <p class="mt-2 text-xs text-gray-500">Nếu bạn không phản hồi trước ngày bắt đầu, hệ thống xem như bạn đồng ý và phòng sẽ chuyển sang bảo trì.</p>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="<?= BASE_URL ?>?page=tenant-reject-maintenance">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" value="<?= (int)($pendingRequest['id'] ?? 0) ?>">
                <button type="submit" data-confirm="Từ chối đề xuất bảo trì này? Phòng sẽ giữ trạng thái đang thuê."
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-500 text-white font-semibold hover:bg-rose-600 transition">
                    <span class="material-symbols-outlined text-base">cancel</span> Từ chối bảo trì
                </button>
            </form>
            <?php if ($supportPhone !== ''): ?>
            <a href="tel:<?= e(preg_replace('/\s+/', '', $supportPhone)) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                <span class="material-symbols-outlined text-base">call</span> Liên hệ admin: <?= e($supportPhone) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($activeRequest): ?>
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-blue-600">engineering</span>
            <h3 class="text-lg font-bold text-blue-800">Phòng đang trong thời gian bảo trì</h3>
        </div>
        <p class="text-sm text-blue-700">Bắt đầu từ <?= e($formatDate($activeRequest['start_date'] ?? '')) ?> trong <?= (int)($activeRequest['duration_days'] ?? 0) ?> ngày.</p>
        <p class="mt-2 text-sm text-blue-700"><span class="font-semibold">Lý do:</span> <?= e($activeRequest['reason'] ?? '') ?></p>
    </div>

    <?php else: ?>
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-gray-500">
        Hiện không có đề xuất bảo trì nào cho phòng của bạn.
    </div>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>