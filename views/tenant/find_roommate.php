<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'roommate';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Tìm người ở ghép để chia sẻ phòng và chi phí';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';

$searchQuery = $searchQuery ?? '';
$hosts = $hosts ?? [];
$pendingRequest = $pendingRequest ?? null;
$message = $message ?? '';
$error = $error ?? '';
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Tìm người ở ghép</h2>
        <p class="mt-2 text-gray-500">Nhập số điện thoại hoặc email của người đang có phòng để gửi yêu cầu ở ghép.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($pendingRequest): ?>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5">
        <p class="flex items-center gap-2 font-semibold text-amber-800">
            <span class="material-symbols-outlined">hourglass_top</span>
            Bạn đang có yêu cầu ở ghép chờ duyệt
        </p>
        <p class="mt-1 text-sm text-amber-700">Vui lòng chờ người ở ghép phản hồi trước khi gửi yêu cầu khác.</p>
    </div>
    <?php else: ?>
    <form method="GET" action="<?= BASE_URL ?>" class="flex flex-col gap-3 sm:flex-row">
        <input type="hidden" name="page" value="tenant-roommate">
        <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Nhập số điện thoại hoặc email người ở ghép..." class="flex-1 px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
            <span class="material-symbols-outlined text-base">search</span> Tìm kiếm
        </button>
    </form>

    <?php if ($searchQuery !== ''): ?>
        <?php if (empty($hosts)): ?>
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center text-gray-500">
            Không tìm thấy người ở ghép phù hợp. Hãy chắc chắn người đó đã đăng ký tài khoản, đang có phòng và phòng còn chỗ trống.
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($hosts as $host): ?>
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900"><?= e($host['name']) ?></h3>
                        <p class="mt-1 text-sm text-gray-500">Đang ở: <span class="font-semibold"><?= e($host['room_name']) ?></span></p>
                        <p class="mt-1 text-sm text-gray-500">Sức chứa: <?= (int)$host['occupants'] ?>/<?= (int)$host['max_occupancy'] ?> người</p>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>?page=tenant-send-roommate-request" class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-2/3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="host_user_id" value="<?= (int)$host['id'] ?>">
                        <select name="gender" class="px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                        <input type="text" name="relationship" placeholder="Mối quan hệ (VD: bạn bè)" class="px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                            <span class="material-symbols-outlined text-base">send</span> Gửi yêu cầu
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>