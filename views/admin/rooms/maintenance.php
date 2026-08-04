<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'maintenance';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Đề xuất và quản lý bảo trì phòng đang thuê';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';

$requests = $requests ?? [];
$rentedRooms = $rentedRooms ?? [];
$message = $message ?? '';
$error = $error ?? '';
$currentStatus = $statusFilter ?? trim((string)($_GET['status'] ?? 'pending'));
$statusTabs = [
    'pending' => 'Đang chờ',
    'active' => 'Đang bảo trì',
    'rejected' => 'Từ chối',
    'completed' => 'Hoàn thành',
];
$statusBadge = [
    'pending' => 'bg-amber-100 text-amber-700',
    'active' => 'bg-blue-100 text-blue-700',
    'rejected' => 'bg-rose-100 text-rose-700',
    'completed' => 'bg-green-100 text-green-700',
];
$formatDate = static function ($value) {
    $value = trim((string)($value ?? ''));
    if ($value === '') { return '—'; }
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : $value;
};
?>
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Bảo trì phòng</h2>
            <p class="mt-2 text-gray-500">Đề xuất bảo trì phòng đang thuê. Cư dân im lặng xem như đồng ý; một người từ chối sẽ hủy đề xuất.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($statusTabs as $statusKey => $statusLabel): ?>
            <a href="<?= BASE_URL ?>?page=admin-maintenance&status=<?= $statusKey ?>"
               class="px-4 py-2 rounded-xl text-sm font-semibold transition <?= $currentStatus === $statusKey ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-primary hover:text-primary' ?>">
                <?= e($statusLabel) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">add_circle</span>
            Đề xuất bảo trì mới
        </h3>
        <?php if (empty($rentedRooms)): ?>
        <p class="text-sm text-gray-500">Không có phòng nào đang thuê để đề xuất bảo trì.</p>
        <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>?page=admin-propose-maintenance" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold mb-2">Phòng đang thuê *</label>
                <select name="room_id" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
                    <option value="">-- Chọn phòng --</option>
                    <?php foreach ($rentedRooms as $room): ?>
                    <option value="<?= (int)$room['id'] ?>"><?= e($room['name']) ?> - <?= e($room['area_name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Ngày bắt đầu *</label>
                <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Số ngày bảo trì *</label>
                <input type="number" name="duration_days" min="1" value="1" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2">Lý do bảo trì *</label>
                <textarea name="reason" rows="3" required placeholder="VD: Sửa đường ống nước, sơn lại phòng..." class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                    <span class="material-symbols-outlined text-base">send</span> Gửi đề xuất & thông báo cư dân
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($requests)): ?>
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-gray-500">
        Không có đề xuất bảo trì nào ở trạng thái này.
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($requests as $request): ?>
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900"><?= e($request['room_name'] ?? 'Phòng') ?></h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusBadge[$request['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                            <?= e($statusTabs[$request['status']] ?? ($request['status'] ?? '')) ?>
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500"><?= e($request['area_name'] ?? '') ?></p>
                    <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 text-sm text-gray-600 sm:grid-cols-3">
                        <p><span class="font-semibold">Bắt đầu:</span> <?= e($formatDate($request['start_date'] ?? '')) ?></p>
                        <p><span class="font-semibold">Số ngày:</span> <?= (int)($request['duration_days'] ?? 0) ?></p>
                        <?php if (!empty($request['rejected_by_user_id'])): ?>
                        <p><span class="font-semibold">Từ chối bởi user:</span> #<?= (int)$request['rejected_by_user_id'] ?></p>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 text-sm text-gray-700"><span class="font-semibold">Lý do:</span> <?= e($request['reason'] ?? '') ?></p>
                </div>
                <?php if ((string)($request['status'] ?? '') === 'active'): ?>
                <form method="POST" action="<?= BASE_URL ?>?page=admin-complete-maintenance">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= (int)($request['id'] ?? 0) ?>">
                    <button type="submit" data-confirm="Hoàn tất bảo trì và khôi phục trạng thái phòng?"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 transition">
                        <span class="material-symbols-outlined text-base">check_circle</span> Hoàn thành
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>