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
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Yêu cầu thuê & ở ghép</h2>
            <p class="text-gray-500 mt-2">Quản lý và xử lý các yêu cầu thuê phòng và yêu cầu ở ghép từ người dùng.</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>" class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-end gap-3 shadow-sm">
            <input type="hidden" name="page" value="admin-rent-requests">
            <div>
                <label for="rent-status" class="block text-sm font-semibold mb-2">Trạng thái thuê</label>
                <select id="rent-status" name="status" onchange="this.form.submit()" class="w-full sm:w-48 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php foreach (['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', 'cancelled' => 'Đã hủy'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($statusFilter ?? 'pending') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="roommate-status" class="block text-sm font-semibold mb-2">Trạng thái ở ghép</label>
                <select id="roommate-status" name="rstatus" onchange="this.form.submit()" class="w-full sm:w-48 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <?php foreach (['pending_admin' => 'Chờ admin duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', 'cancelled' => 'Đã hủy', 'admin_rejected' => 'Admin gỡ bỏ'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($roommateStatusFilter ?? 'pending_admin') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
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

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg text-gray-900">Yêu cầu thuê phòng</h3>
            <p class="text-sm text-gray-500 mt-1">Người dùng muốn thuê một phòng cụ thể.</p>
        </div>
        <?php if (empty($requests)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Không có yêu cầu thuê phòng nào với trạng thái "<?= e($statusFilter ?? 'pending') ?>".
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người thuê</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu / Tầng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày vào</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số người</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ghi chú</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($requests as $req): ?>
                    <?php
                    $reqId = (int)($req['id'] ?? 0);
                    $reqStatus = $req['status'] ?? 'pending';
                    $statusMeta = [
                        'pending'  => ['label' => 'Chờ duyệt', 'class' => 'bg-amber-100 text-amber-700'],
                        'approved' => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-700'],
                        'rejected' => ['label' => 'Đã từ chối', 'class' => 'bg-red-100 text-red-700'],
                        'cancelled'=> ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700'],
                    ];
                    $meta = $statusMeta[$reqStatus] ?? ['label' => ucfirst($reqStatus), 'class' => 'bg-slate-100 text-slate-700'];
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 align-top font-mono text-sm text-gray-500">#<?= $reqId ?></td>
                        <td class="px-6 py-4 align-top">
                            <p class="font-semibold text-gray-900"><?= e($req['user_name'] ?? 'N/A') ?></p>
                            <p class="text-sm text-gray-500 mt-1"><?= e($req['user_email'] ?? '') ?></p>
                            <?php if (!empty($req['user_phone'])): ?>
                            <p class="text-xs text-gray-400 mt-1"><?= e($req['user_phone']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 align-top font-medium text-gray-900"><?= e($req['room_name'] ?? 'N/A') ?></td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm text-gray-700"><?= e($req['area_name'] ?? 'N/A') ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= e($req['floor_name'] ?? '') ?></p>
                        </td>
                        <td class="px-6 py-4 align-top text-sm text-gray-700"><?= e(!empty($req['move_in_date']) ? date('d/m/Y', strtotime((string)$req['move_in_date'])) : 'N/A') ?></td>
                        <td class="px-6 py-4 align-top text-sm text-gray-700"><?= (int)($req['occupant_count'] ?? 1) ?> người</td>
                        <td class="px-6 py-4 align-top">
                            <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm text-gray-600 max-w-xs truncate" title="<?= e($req['admin_note'] ?? '') ?>"><?= e($req['admin_note'] ?? '—') ?></p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <?php if ($reqStatus === 'pending'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-approve-rent-request" class="inline" onsubmit="return confirm('Xác nhận duyệt yêu cầu này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                <input type="hidden" name="redirect_page" value="admin-rent-requests">
                                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                                <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 transition">Duyệt</button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-reject-rent-request" class="inline mt-2" onsubmit="return confirm('Xác nhận từ chối yêu cầu này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                <input type="hidden" name="redirect_page" value="admin-rent-requests">
                                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                                <input type="text" name="admin_note" placeholder="Lý do từ chối" class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary w-full sm:w-48 mb-2" required>
                                <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition w-full sm:w-48">Từ chối</button>
                            </form>
                            <?php else: ?>
                            <span class="text-sm text-gray-400">Đã xử lý</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-lg text-gray-900">Yêu cầu ở ghép</h3>
            <p class="text-sm text-gray-500 mt-1">Người thuê xin ở ghép cùng người đang có phòng. Admin có thể từ chối hoặc veto & gỡ khỏi phòng.</p>
        </div>
        <?php if (empty($roommateRequests)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            Không có yêu cầu ở ghép nào với trạng thái "<?= e($roommateStatusFilter ?? 'pending') ?>".
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người gửi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người nhận</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giới tính</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
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
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 align-top font-mono text-sm text-gray-500">#<?= $rrId ?></td>
                        <td class="px-6 py-4 align-top">
                            <p class="font-semibold text-gray-900"><?= e($rr['requester_name'] ?? 'N/A') ?></p>
                            <p class="text-xs text-gray-400 mt-1">ID: <?= (int)($rr['requester_id'] ?? 0) ?></p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="font-semibold text-gray-900"><?= e($rr['host_name'] ?? 'N/A') ?></p>
                            <p class="text-xs text-gray-400 mt-1">ID: <?= (int)($rr['host_user_id'] ?? 0) ?></p>
                        </td>
                        <td class="px-6 py-4 align-top font-medium text-gray-900"><?= e($rr['room_name'] ?? 'N/A') ?></td>
                        <td class="px-6 py-4 align-top text-sm text-gray-700"><?= $rrGender ?></td>
                        <td class="px-6 py-4 align-top">
                            <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $rmeta['class'] ?>"><?= $rmeta['label'] ?></span>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <?php if ($rrStatus === 'pending_admin' || $rrStatus === 'pending'): ?>
                            <div class="flex flex-col gap-2">
                                <form method="POST" action="<?= BASE_URL ?>?page=admin-approve-roommate" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= $rrId ?>">
                                    <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 transition">Duyệt & xếp phòng</button>
                                </form>
                                <form method="POST" action="<?= BASE_URL ?>?page=admin-reject-roommate" class="inline" onsubmit="return confirm('Xác nhận từ chối yêu cầu ở ghép này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= $rrId ?>">
                                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition">Từ chối</button>
                                </form>
                            </div>
                            <?php elseif ($rrStatus === 'approved'): ?>
                            <form method="POST" action="<?= BASE_URL ?>?page=admin-veto-roommate" class="inline" onsubmit="return confirm('Xác nhận veto và gỡ người ở ghép khỏi phòng?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= $rrId ?>">
                                <button type="submit" class="px-3 py-2 bg-orange-600 text-white rounded-lg font-semibold text-sm hover:bg-orange-700 transition">Veto & gỡ</button>
                            </form>
                            <?php else: ?>
                            <span class="text-sm text-gray-400">Đã xử lý</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
