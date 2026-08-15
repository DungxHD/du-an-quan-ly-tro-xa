<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'roommate';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Mời bạn bè ở ghép cùng bạn tại phòng hiện tại';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';

$searchQuery = $searchQuery ?? '';
$inviteCandidate = $inviteCandidate ?? null;
$myRequests = $myRequests ?? [];
$myRoom = $myRoom ?? null;
$message = $message ?? '';
$error = $error ?? '';
$genderLabels = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Mời ở ghép</h2>
        <p class="mt-2 text-gray-500">Nhập số điện thoại hoặc email của người bạn muốn mời ở ghép. Nếu họ đã có tài khoản, hệ thống sẽ tìm thấy và bạn có thể gửi yêu cầu lên admin duyệt.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Thông tin phòng hiện tại -->
    <?php if ($myRoom): ?>
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
        <h3 class="font-semibold text-blue-800 flex items-center gap-2">
            <span class="material-symbols-outlined">meeting_room</span>
            Phòng hiện tại: <?= e($myRoom['name']) ?>
        </h3>
        <p class="mt-1 text-sm text-blue-700">
            Sức chứa: <span class="font-semibold"><?= (int)$myRoom['occupants'] ?? 0 ?>/<?= (int)$myRoom['max_occupancy'] ?? 1 ?></span> người
            <?= ((int)($myRoom['occupants'] ?? 0) >= (int)($myRoom['max_occupancy'] ?? 1)) ? ' <span class="text-red-600">(Đã đủ)</span>' : '' ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Form tìm kiếm người để mời -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tìm người để mời ở ghép</h3>
        <form method="GET" action="<?= BASE_URL ?>" class="flex flex-col gap-3 sm:flex-row">
            <input type="hidden" name="page" value="tenant-roommate">
            <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Nhập số điện thoại hoặc email người bạn muốn mời..." class="flex-1 px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                <span class="material-symbols-outlined text-base">search</span> Tìm kiếm
            </button>
        </form>

        <?php if ($searchQuery !== ''): ?>
            <?php if ($inviteCandidate): ?>
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-4">
                <p class="font-semibold text-green-800">Tìm thấy tài khoản:</p>
                <div class="mt-2 grid gap-2 text-sm">
                    <p><span class="font-semibold">Họ tên:</span> <?= e($inviteCandidate['name']) ?></p>
                    <p><span class="font-semibold">Email:</span> <?= e($inviteCandidate['email']) ?></p>
                    <p><span class="font-semibold">Số điện thoại:</span> <?= e($inviteCandidate['phone']) ?></p>
                </div>
                <form method="POST" action="<?= BASE_URL ?>?page=tenant-send-roommate-request" class="mt-4 grid gap-3 sm:grid-cols-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="guest_user_id" value="<?= (int)$inviteCandidate['id'] ?>">
                    <select name="gender" class="px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                    <input type="text" name="relationship" placeholder="Mối quan hệ (VD: bạn bè, anh chị em)" class="px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-primary">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                        <span class="material-symbols-outlined text-base">send</span> Gửi yêu cầu mời ở ghép
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="mt-4 rounded-xl border border-dashed border-gray-200 bg-white px-6 py-8 text-center text-gray-500">
                Không tìm thấy tài khoản phù hợp. Người này có thể chưa đăng ký tài khoản, đã có phòng, hoặc nhập sai thông tin.
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Danh sách yêu cầu đã gửi -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Yêu cầu mời ở ghép của bạn</h3>
        <?php if (empty($myRequests)): ?>
        <p class="text-center text-gray-500 py-8">Chưa có yêu cầu nào.</p>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($myRequests as $req): ?>
            <?php 
                $status = $req['status'] ?? 'pending_admin';
                $statusClass = $status === 'pending_admin' ? 'bg-amber-100 text-amber-800' : ($status === 'approved' ? 'bg-green-100 text-green-800' : ($status === 'rejected' || $status === 'admin_rejected' ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-800'));
                $statusText = $status === 'pending_admin' ? 'Chờ admin duyệt' : ($status === 'approved' ? 'Đã duyệt' : ($status === 'rejected' ? 'Admin từ chối' : ($status === 'admin_rejected' ? 'Admin gỡ bỏ' : ($status === 'cancelled' ? 'Đã hủy' : $status))));
            ?>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-gray-900"><?= e($req['requester_name'] ?? 'Người được mời') ?></h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            <?= e($req['requester_email'] ?? '') ?><?= !empty($req['requester_phone']) ? ' · ' . e($req['requester_phone']) : '' ?>
                        </div>
                        <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 text-sm text-gray-600 sm:grid-cols-3">
                            <p><span class="font-semibold">Phòng:</span> <?= e($req['room_name'] ?? '') ?></p>
                            <p><span class="font-semibold">Giới tính:</span> <?= e($genderLabels[$req['gender'] ?? 'other'] ?? 'Khác') ?></p>
                            <p><span class="font-semibold">Mối quan hệ:</span> <?= e(fallbackText($req['relationship'] ?? '', 'Chưa rõ')) ?></p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                        <?php if ($status === 'pending_admin'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=tenant-cancel-roommate-request">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= (int)($req['id'] ?? 0) ?>">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition" onclick="return confirm('Bạn chắc chắn muốn hủy yêu cầu này?')">
                                    <span class="material-symbols-outlined text-base">cancel</span> Hủy yêu cầu
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>