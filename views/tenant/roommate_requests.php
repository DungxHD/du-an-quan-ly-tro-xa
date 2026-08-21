<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'roommate';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Xem và duyệt yêu cầu ở ghép gửi đến phòng của bạn';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';

$pendingRequests = $pendingRequests ?? [];
$myRoom = $myRoom ?? null;
$message = $message ?? '';
$error = $error ?? '';
$genderLabels = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Yêu cầu ở ghép</h2>
        <p class="mt-2 text-gray-500">
            Những người muốn ở ghép cùng bạn<?= $myRoom ? ' tại phòng ' . e($myRoom['name']) : '' ?>.
        </p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($pendingRequests)): ?>
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-gray-500">
        Chưa có yêu cầu ở ghép nào đang chờ duyệt.
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($pendingRequests as $request): ?>
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900"><?= e($request['requester_name'] ?? 'Người dùng') ?></h3>
                    <p class="mt-1 text-sm text-gray-500"><?= e($request['requester_email'] ?? '') ?><?= !empty($request['requester_phone']) ? ' · ' . e($request['requester_phone']) : '' ?></p>
                    <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 text-sm text-gray-600 sm:grid-cols-2">
                        <p><span class="font-semibold">Phòng:</span> <?= e($request['room_name'] ?? '') ?></p>
                        <p><span class="font-semibold">Giới tính:</span> <?= e($genderLabels[$request['gender'] ?? 'other'] ?? 'Khác') ?></p>
                        <p><span class="font-semibold">Mối quan hệ:</span> <?= e(fallbackText($request['relationship'] ?? '', 'Chưa rõ')) ?></p>
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                    <form method="POST" action="<?= BASE_URL ?>?page=tenant-approve-roommate">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int)($request['id'] ?? 0) ?>">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 transition">
                            <span class="material-symbols-outlined text-base">check_circle</span> Duyệt
                        </button>
                    </form>
                    <form method="POST" action="<?= BASE_URL ?>?page=tenant-reject-roommate">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int)($request['id'] ?? 0) ?>">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-50 text-rose-700 font-semibold hover:bg-rose-100 transition">
                            <span class="material-symbols-outlined text-base">cancel</span> Từ chối
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>