<?php
// [RENDER] 1 item yêu cầu ở ghép — dùng chung cho trang admin-rent-requests và API AJAX.
// Biến cần có: $rr (1 dòng từ RoommateRequestModel::getAll)
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
<div class="px-6 py-4 hover:bg-gray-50 transition" data-roommate-item>
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
            <?php if (!empty($rr['admin_note'])): ?>
            <p class="text-xs text-gray-500 mt-1 italic">Lý do từ chối: <?= e($rr['admin_note']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
            <?php if ($rrStatus === 'pending_admin' || $rrStatus === 'pending'): ?>
            <form method="POST" action="<?= BASE_URL ?>?page=admin-approve-roommate" onsubmit="return confirm('Xác nhận duyệt yêu cầu ở ghép này?');">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" value="<?= $rrId ?>">
                <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 transition w-full">Duyệt & xếp phòng</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>?page=admin-reject-roommate" class="bg-red-50 border border-red-200 rounded-lg p-2.5 space-y-2" onsubmit="return confirm('Xác nhận từ chối yêu cầu ở ghép này? Lý do từ chối sẽ được gửi về người đang thuê trong phòng.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $rrId ?>">
                    <textarea
                        name="admin_note"
                        rows="2"
                        maxlength="500"
                        placeholder="Lý do từ chối (gửi về người đang thuê trong phòng)..."
                        class="w-full px-2.5 py-1.5 border border-red-200 bg-white rounded-md text-sm outline-none focus:ring-2 focus:ring-red-300 resize-y"
                    ></textarea>
                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition w-full">Từ chối kèm lý do</button>
                </form>
            <?php elseif ($rrStatus === 'approved'): ?>
            <span class="px-3 py-2 bg-green-50 text-green-700 rounded-lg font-semibold text-sm text-center">Đã duyệt - không thể gỡ</span>
            <?php else: ?>
            <span class="text-sm text-gray-400 text-center py-2">Đã xử lý</span>
            <?php endif; ?>
        </div>
    </div>
</div>
