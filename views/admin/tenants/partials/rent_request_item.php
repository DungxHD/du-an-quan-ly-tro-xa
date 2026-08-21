<?php
// [RENDER] 1 item yêu cầu thuê phòng — dùng chung cho trang admin-rent-requests và API AJAX.
// Biến cần có: $req (1 dòng từ RentalRequestModel::getAllWithDetails)
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
<div class="px-6 py-4 hover:bg-gray-50 transition" data-rent-item>
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
                Giới tính: <?= e(['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$req['gender'] ?? 'other'] ?? 'Khác') ?>
                · Ngày vào: <?= e(!empty($req['move_in_date']) ? date('d/m/Y', strtotime((string)$req['move_in_date'])) : 'N/A') ?>
                · <?= (int)($req['occupant_count'] ?? 1) ?> người
            </p>
            <?php if (!empty($req['deposit']) && (float)$req['deposit'] > 0): ?>
            <p class="text-xs text-gray-500 mt-1">Tiền cọc: <span class="font-semibold text-gray-700"><?= number_format((float)$req['deposit'], 0, ',', '.') ?>đ</span> — giữ phòng đến hết ngày dự kiến vào ở.</p>
            <?php endif; ?>
            <?php if (!empty($req['admin_note'])): ?>
            <p class="text-xs text-gray-500 mt-1 italic">Ghi chú: <?= e($req['admin_note']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
            <?php if ($reqStatus === 'pending' && (string)($req['payment_status'] ?? 'pending') !== 'confirmed'): ?>
            <div class="flex flex-col gap-2 w-56 shrink-0">
                <form method="POST" action="<?= BASE_URL ?>?page=admin-confirm-rent-request" class="bg-gray-50 border border-gray-200 rounded-lg p-2.5 space-y-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $reqId ?>">
                    <label class="block text-[11px] font-semibold text-gray-600">Tiền cọc giữ phòng (đ) *</label>
                    <input
                        type="number"
                        name="deposit"
                        required
                        min="1000"
                        step="1000"
                        placeholder="VD: <?= number_format((float)($req['room_price'] ?? 0), 0, ',', '.') ?>"
                        class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-sm outline-none focus:ring-2 focus:ring-primary"
                    >
                    <p class="text-[10px] text-gray-500">Giữ phòng cho người thuê đến hết ngày dự kiến vào ở.</p>
                    <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg font-semibold text-sm hover:opacity-90 transition w-full">Xác nhận & tạo mã QR</button>
                </form>
                <form method="POST" action="<?= BASE_URL ?>?page=admin-reject-rent-request" onsubmit="return confirm('Xác nhận từ chối yêu cầu thuê này? Người thuê sẽ được thông báo.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $reqId ?>">
                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition w-full">Từ chối</button>
                </form>
            </div>
            <?php elseif ($reqStatus === 'pending' && (string)($req['payment_status'] ?? 'pending') === 'confirmed'): ?>
            <div class="text-center bg-gray-50 border border-gray-200 rounded-lg p-3 w-48">
                <?php
                $qrAmount = (float)($req['deposit'] ?? 0) > 0 ? (float)$req['deposit'] : (float)($req['room_price'] ?? 0);
                $qrBank = RoomModel::getSetting('bank_name', 'Vietcombank');
                $qrAccount = RoomModel::getSetting('bank_account_number', '');
                $qrHolder = RoomModel::getSetting('bank_account_holder', '');
                $qrText = 'Chuyen khoan thue phong ' . ($req['room_name'] ?? '')
                    . ' - So tien: ' . number_format($qrAmount, 0, ',', '.') . ' VND'
                    . ' - Ngan hang: ' . $qrBank
                    . ' - STK: ' . $qrAccount
                    . ' - Chu TK: ' . $qrHolder;
                ?>
                <p class="text-xs font-bold text-gray-700 mb-2">Mã QR chuyển tiền cọc</p>
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= e(urlencode($qrText)) ?>"
                    alt="Mã QR chuyển tiền cọc"
                    class="mx-auto w-36 h-36 rounded-lg bg-white p-1"
                    loading="lazy"
                >
                <p class="mt-2 text-xs text-gray-600 font-semibold"><?= e($req['room_name'] ?? '') ?> · <?= number_format($qrAmount, 0, ',', '.') ?>đ</p>
                <p class="text-[11px] text-gray-500"><?= e($qrBank) ?> · <?= e($qrAccount) ?> · <?= e($qrHolder) ?></p>
                <div class="mt-2 flex flex-col gap-1.5">
                    <form method="POST" action="<?= BASE_URL ?>?page=admin-paid-rent-request" onsubmit="return confirm('Xác nhận người thuê đã thanh toán tiền cọc thành công? Người thuê sẽ được thêm vào phòng và trạng thái chuyển sang đang thuê.');">
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
