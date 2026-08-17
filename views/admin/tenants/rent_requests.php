<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'rent-requests';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý yêu cầu thuê phòng và yêu cầu ở ghép từ người dùng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold">Yêu cầu thuê & ở ghép</h2>
        <p class="text-gray-500 mt-2">Quản lý và xử lý các yêu cầu thuê phòng và yêu cầu ở ghép từ người dùng.</p>
    </div>

    <?php if (!empty($message)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($roommateMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($roommateMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($roommateError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($roommateError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <!-- ================= CỘT 1: YÊU CẦU THUÊ PHÒNG ================= -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-900">Yêu cầu thuê phòng</h3>
                <p class="text-sm text-gray-500 mt-1">Người dùng muốn thuê một phòng cụ thể.</p>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 space-y-4">
                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4">
                    <input type="hidden" name="page" value="admin-rent-requests">
                    <input type="hidden" name="roommate_filter" value="<?= e($roommateFilter) ?>">
                    <input type="hidden" name="roommate_keyword" value="<?= e($roommateKeyword) ?>">

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Bộ lọc trạng thái</p>
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Bộ lọc trạng thái yêu cầu thuê">
                            <button type="submit" name="rent_filter" value="all"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? 'all') === 'all' ? 'bg-primary text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                                aria-pressed="<?= ($rentFilter ?? 'all') === 'all' ? 'true' : 'false' ?>">
                                Tất cả
                            </button>
                            <button type="submit" name="rent_filter" value="pending"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 relative
                                    <?= ($rentFilter ?? '') === 'pending' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
                                aria-pressed="<?= ($rentFilter ?? '') === 'pending' ? 'true' : 'false' ?>">
                                Cần xử lý
                                <?php if (!empty($pendingRentCount)): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-600 text-white text-xs font-bold rounded-full flex items-center justify-center"><?= $pendingRentCount > 99 ? '99+' : $pendingRentCount ?></span>
                                <?php endif; ?>
                            </button>
                            <button type="submit" name="rent_filter" value="approved"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? '') === 'approved' ? 'bg-green-600 text-white shadow-md' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' ?>"
                                aria-pressed="<?= ($rentFilter ?? '') === 'approved' ? 'true' : 'false' ?>">
                                Đã duyệt
                            </button>
                            <button type="submit" name="rent_filter" value="rejected"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? '') === 'rejected' ? 'bg-red-600 text-white shadow-md' : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' ?>"
                                aria-pressed="<?= ($rentFilter ?? '') === 'rejected' ? 'true' : 'false' ?>">
                                Từ chối
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-base text-gray-400">search</span>
                        <input
                            type="text"
                            name="rent_keyword"
                            value="<?= e($rentKeyword ?? '') ?>"
                            placeholder="Tìm theo tên phòng, tên người gửi, email, số điện thoại..."
                            class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                </form>
            </div>

            <?php if (empty($requests)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                Không có yêu cầu thuê phòng nào khớp bộ lọc hiện tại.
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($requests as $req): ?>
                <?php
                $reqId = (int)($req['id'] ?? 0);
                $reqStatus = $req['status'] ?? 'pending';
                $reqPaymentStatus = (string)($req['payment_status'] ?? 'pending');
                if ($reqStatus === 'pending' && $reqPaymentStatus === 'confirmed') {
                    $statusMeta = [
                        'pending'  => ['label' => 'Chờ thanh toán', 'class' => 'bg-sky-100 text-sky-700'],
                        'approved' => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-700'],
                        'rejected' => ['label' => 'Đã từ chối', 'class' => 'bg-red-100 text-red-700'],
                        'cancelled'=> ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700'],
                    ];
                } else {
                    $statusMeta = [
                        'pending'  => ['label' => 'Chờ duyệt', 'class' => 'bg-amber-100 text-amber-700'],
                        'approved' => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-700'],
                        'rejected' => ['label' => 'Đã từ chối', 'class' => 'bg-red-100 text-red-700'],
                        'cancelled'=> ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700'],
                    ];
                }
                $meta = $statusMeta[$reqStatus] ?? ['label' => ucfirst($reqStatus), 'class' => 'bg-slate-100 text-slate-700'];
                ?>
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-900"><?= e($req['user_name'] ?? 'N/A') ?></p>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-gray-700">
                                <span>Email: <span class="font-medium"><?= e($req['user_email'] ?? '') ?></span></span>
                                <span>SĐT: <span class="font-medium"><?= e($req['user_phone'] ?? 'Chưa cập nhật') ?></span></span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1.5">
                                Phòng <span class="font-medium text-gray-900"><?= e($req['room_name'] ?? 'N/A') ?></span>
                                <?php if (!empty($req['area_name'])): ?> · <?= e($req['area_name']) ?><?php endif; ?>
                                <?php if (!empty($req['floor_name'])): ?> / <?= e($req['floor_name']) ?><?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Ngày vào: <?= e(!empty($req['move_in_date']) ? date('d/m/Y', strtotime((string)$req['move_in_date'])) : 'N/A') ?>
                                · <?= (int)($req['occupant_count'] ?? 1) ?> người
                            </p>
                            <?php if (!empty($req['admin_note'])): ?>
                            <p class="text-xs text-gray-500 mt-1 italic">Ghi chú: <?= e($req['admin_note']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            <?php if ($reqStatus === 'pending' && (string)($req['payment_status'] ?? 'pending') !== 'confirmed'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-confirm-rent-request" onsubmit="return confirm('Xác nhận yêu cầu thuê này? Mã QR chuyển tiền sẽ được hiển thị.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg font-semibold text-sm hover:opacity-90 transition w-full">Xác nhận & tạo mã QR</button>
                            </form>
                            <?php elseif ($reqStatus === 'pending' && (string)($req['payment_status'] ?? 'pending') === 'confirmed'): ?>
                            <div class="text-center bg-gray-50 border border-gray-200 rounded-lg p-3 w-48">
                                <?php
                                $qrAmount = (float)($req['room_price'] ?? 0);
                                $qrBank = RoomModel::getSetting('bank_name', 'Vietcombank');
                                $qrAccount = RoomModel::getSetting('bank_account_number', '');
                                $qrHolder = RoomModel::getSetting('bank_account_holder', '');
                                $qrText = 'Chuyen khoan thue phong ' . ($req['room_name'] ?? '')
                                    . ' - So tien: ' . number_format($qrAmount, 0, ',', '.') . ' VND'
                                    . ' - Ngan hang: ' . $qrBank
                                    . ' - STK: ' . $qrAccount
                                    . ' - Chu TK: ' . $qrHolder;
                                ?>
                                <p class="text-xs font-bold text-gray-700 mb-2">Mã QR chuyển tiền</p>
                                <img
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= e(urlencode($qrText)) ?>"
                                    alt="Mã QR chuyển tiền"
                                    class="mx-auto w-36 h-36 rounded-lg bg-white p-1"
                                    loading="lazy"
                                >
                                <p class="mt-2 text-xs text-gray-600 font-semibold"><?= e($req['room_name'] ?? '') ?> · <?= number_format($qrAmount, 0, ',', '.') ?>đ</p>
                                <p class="text-[11px] text-gray-500"><?= e($qrBank) ?> · <?= e($qrAccount) ?> · <?= e($qrHolder) ?></p>
                                <div class="mt-2 flex flex-col gap-1.5">
                                    <form method="POST" action="<?= BASE_URL ?>?page=admin-paid-rent-request" onsubmit="return confirm('Xác nhận người thuê đã thanh toán thành công? Người thuê sẽ được thêm vào phòng và trạng thái chuyển sang đang thuê.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                        <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 transition w-full">Đã thanh toán</button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>?page=admin-cancel-rent-request" onsubmit="return confirm('Xác nhận hủy yêu cầu này? Người thuê sẽ KHÔNG được thêm vào phòng và phòng vẫn ở trạng thái trống.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                        <button type="submit" class="px-3 py-2 bg-rose-500 text-white rounded-lg font-semibold text-sm hover:bg-rose-600 transition w-full">Hủy</button>
                                    </form>
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="text-sm text-gray-400 text-center py-2">Đã xử lý</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- ================= CỘT 2: YÊU CẦU Ở GHÉP ================= -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-900">Yêu cầu ở ghép</h3>
                <p class="text-sm text-gray-500 mt-1">Người thuê xin ở ghép cùng người đang có phòng. Admin có thể duyệt hoặc từ chối. Yêu cầu đã duyệt không thể gỡ.</p>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 space-y-4">
                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4">
                    <input type="hidden" name="page" value="admin-rent-requests">
                    <input type="hidden" name="rent_filter" value="<?= e($rentFilter) ?>">
                    <input type="hidden" name="rent_keyword" value="<?= e($rentKeyword) ?>">

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Bộ lọc trạng thái</p>
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Bộ lọc trạng thái yêu cầu ở ghép">
                            <button type="submit" name="roommate_filter" value="all"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? 'all') === 'all' ? 'bg-primary text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                                aria-pressed="<?= ($roommateFilter ?? 'all') === 'all' ? 'true' : 'false' ?>">
                                Tất cả
                            </button>
                            <button type="submit" name="roommate_filter" value="pending_admin"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 relative
                                    <?= ($roommateFilter ?? '') === 'pending_admin' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'pending_admin' ? 'true' : 'false' ?>">
                                Cần xử lý
                                <?php if (!empty($pendingRoommateCount)): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-600 text-white text-xs font-bold rounded-full flex items-center justify-center"><?= $pendingRoommateCount > 99 ? '99+' : $pendingRoommateCount ?></span>
                                <?php endif; ?>
                            </button>
                            <button type="submit" name="roommate_filter" value="approved"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? '') === 'approved' ? 'bg-green-600 text-white shadow-md' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' ?>"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'approved' ? 'true' : 'false' ?>">
                                Đã duyệt
                            </button>
                            <button type="submit" name="roommate_filter" value="rejected"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? '') === 'rejected' ? 'bg-red-600 text-white shadow-md' : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' ?>"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'rejected' ? 'true' : 'false' ?>">
                                Từ chối
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-base text-gray-400">search</span>
                        <input
                            type="text"
                            name="roommate_keyword"
                            value="<?= e($roommateKeyword ?? '') ?>"
                            placeholder="Tìm theo tên phòng, tên người gửi/người nhận, email, số điện thoại..."
                            class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>
                </form>
            </div>

            <?php if (empty($roommateRequests)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                Không có yêu cầu ở ghép nào khớp bộ lọc hiện tại.
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($roommateRequests as $rr): ?>
                <?php
                $rrId = (int)($rr['id'] ?? 0);
                $rrStatus = (string)($rr['status'] ?? 'pending_admin');
                $rrMeta = [
                    'pending_admin'  => ['label' => 'Chờ admin duyệt', 'class' => 'bg-amber-100 text-amber-700'],
                    'pending'        => ['label' => 'Chờ admin duyệt', 'class' => 'bg-amber-100 text-amber-700'],
                    'approved'       => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-700'],
                    'rejected'       => ['label' => 'Đã từ chối', 'class' => 'bg-red-100 text-red-700'],
                    'cancelled'      => ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700'],
                    'admin_rejected' => ['label' => 'Admin từ chối', 'class' => 'bg-slate-100 text-slate-700'],
                ];
                $rmeta = $rrMeta[$rrStatus] ?? ['label' => $rrStatus, 'class' => 'bg-slate-100 text-slate-700'];
                $rrGenderMap = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
                $rrGender = $rrGenderMap[$rr['gender'] ?? 'other'] ?? 'Khác';
                ?>
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-900"><?= e($rr['requester_name'] ?? 'N/A') ?></p>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $rmeta['class'] ?>"><?= $rmeta['label'] ?></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-gray-700">
                                <span>Email: <span class="font-medium"><?= e($rr['requester_email'] ?? '') ?></span></span>
                                <span>SĐT: <span class="font-medium"><?= e($rr['requester_phone'] ?? 'Chưa cập nhật') ?></span></span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1.5">
                                Người nhận: <span class="font-medium text-gray-900"><?= e($rr['host_name'] ?? 'N/A') ?></span>
                                <span class="text-gray-400 mx-2">|</span>
                                Email: <span class="font-medium"><?= e($rr['host_email'] ?? '') ?></span>
                                <span class="text-gray-400 mx-2">|</span>
                                SĐT: <span class="font-medium"><?= e($rr['host_phone'] ?? 'Chưa cập nhật') ?></span>
                            </p>
                            <p class="text-sm text-gray-700">
                                Phòng <span class="font-medium text-gray-900"><?= e($rr['room_name'] ?? 'N/A') ?></span>
                                · <?= $rrGender ?>
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            <?php if ($rrStatus === 'pending_admin' || $rrStatus === 'pending'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-approve-roommate" onsubmit="return confirm('Xác nhận duyệt yêu cầu ở ghép này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $rrId ?>">
                                <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 transition w-full">Duyệt & xếp phòng</button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-reject-roommate" onsubmit="return confirm('Xác nhận từ chối yêu cầu ở ghép này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $rrId ?>">
                                <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition w-full">Từ chối</button>
                            </form>
                            <?php elseif ($rrStatus === 'approved'): ?>
                            <span class="px-3 py-2 bg-green-50 text-green-700 rounded-lg font-semibold text-sm text-center">Đã duyệt - không thể gỡ</span>
                            <?php else: ?>
                            <span class="text-sm text-gray-400 text-center py-2">Đã xử lý</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>