<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'services';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý dịch vụ, cách tính giá và gán dịch vụ theo phòng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$isEditing = !empty($formService['id']);
$billingModeMeta = [];
foreach ($serviceBillingModes as $mode) {
    $billingModeMeta[$mode['value']] = $mode;
}
$appliesToMeta = [];
foreach ($serviceAppliesToOptions as $option) {
    $appliesToMeta[$option['value']] = $option;
}
$assignedServiceIds = array_map(static fn($item) => (int)($item['id'] ?? 0), $roomAssignments ?? []);
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold">Quản lý Dịch vụ</h2>
            <p class="text-gray-500 mt-2">Admin có thể khai báo cách tính giá, bật/tắt đăng ký và gán dịch vụ theo phòng ngay trên một màn hình.</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Tổng dịch vụ</p>
                <p class="text-xl font-bold"><?= count($services ?? []) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Đang kinh doanh</p>
                <p class="text-xl font-bold text-green-600"><?= count(array_filter($services ?? [], static fn($item) => (int)($item['is_active'] ?? 0) === 1)) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Bắt buộc</p>
                <p class="text-xl font-bold text-amber-600"><?= count(array_filter($services ?? [], static fn($item) => (int)($item['is_required'] ?? 0) === 1)) ?></p>
            </div>
            <div class="px-4 py-3 rounded-2xl bg-white border border-gray-200">
                <p class="text-xs text-gray-500">Theo người</p>
                <p class="text-xl font-bold text-purple-600"><?= count(array_filter($services ?? [], static fn($item) => ($item['applies_to'] ?? '') === 'person')) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($serviceMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($serviceMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($serviceError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($serviceError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
        <div class="2xl:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 space-y-5">
                <div>
                    <h3 class="text-lg font-bold"><?= $isEditing ? 'Sửa dịch vụ' : 'Thêm dịch vụ mới' ?></h3>
                    <p class="text-sm text-gray-500 mt-1">Mọi rule quan trọng như đối tượng áp dụng và cách tính giá đều được khóa validate ở controller/model.</p>
                </div>

                <div class="rounded-2xl border border-dashed border-gray-200 p-4 bg-gray-50 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl"><?= e($formService['icon'] ?? 'settings') ?></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900"><?= e($formService['name'] ?: 'Dịch vụ mới') ?></p>
                            <p class="text-sm text-gray-500">
                                <?= ($formService['price'] ?? 0) > 0 ? number_format((float)($formService['price'] ?? 0)) . ' ₫/' . e($formService['unit'] ?? 'tháng') : 'Miễn phí' ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php $previewBilling = $billingModeMeta[$formService['billing_mode'] ?? 'fixed'] ?? $billingModeMeta['fixed']; ?>
                        <?php $previewApplies = $appliesToMeta[$formService['applies_to'] ?? 'room'] ?? $appliesToMeta['room']; ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= e($previewBilling['badge_class'] ?? 'bg-slate-100 text-slate-700') ?>">
                            <?= e($previewBilling['label'] ?? 'Cố định') ?>
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                            <?= e($previewApplies['label'] ?? 'Theo phòng') ?>
                        </span>
                        <?php if (!empty($formService['is_required'])): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">lock</span>
                            Bắt buộc
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-service" class="space-y-4">
<?= csrf_field() ?>
                    <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int)($formService['id'] ?? 0) ?>">
                    <?php endif; ?>
                    <?php if (!empty($selectedRoomId)): ?>
                    <input type="hidden" name="return_room_id" value="<?= (int)$selectedRoomId ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tên dịch vụ *</label>
                        <input
                            type="text"
                            name="name"
                            required
                            value="<?= e($formService['name'] ?? '') ?>"
                            placeholder="Ví dụ: Wifi tầng 2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div>
<label class="block text-sm font-semibold mb-2">Giá</label>
<input type="number" min="0" step="0.01" name="price" value="<?= e($formService['price'] ?? 0) ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div>
<div>
<label class="block text-sm font-semibold mb-2">Tháng áp dụng (giá / cách tính mới)</label>
<div class="grid grid-cols-2 gap-2">
<select name="effective_month" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<option value="0">Tháng sau (mặc định)</option>
<?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>">Tháng <?= $m ?></option><?php endfor; ?>
</select>
<input type="number" name="effective_year" min="<?= (int)date('Y') ?>" max="<?= (int)date('Y') + 5 ?>" value="<?= (int)date('Y') ?>" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div>
<p class="text-xs text-gray-500 mt-1">Giá/cách tính mới không áp dụng cho tháng hiện tại.</p>
</div>
</div><!-- BLOCK-B -->
<div class="grid grid-cols-1 gap-4">
<div>
<label class="block text-sm font-semibold mb-2">Cách tính giá</label>
<?php $formKind = $formService['kind'] ?? 'other'; ?>
<?php $allowedModeValues = (isset($kindBillingModes) && is_array($kindBillingModes) && array_key_exists($formKind, $kindBillingModes)) ? $kindBillingModes[$formKind] : array_column($serviceBillingModes ?? [], 'value'); ?>
<select name="billing_mode" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<?php foreach (($serviceBillingModes ?? []) as $mode): ?>
<?php if (in_array($mode['value'], $allowedModeValues, true)): ?>
<option value="<?= e($mode['value']) ?>" <?= ($formService['billing_mode'] ?? 'fixed') === $mode['value'] ? 'selected' : '' ?>><?= e($mode['label']) ?></option>
<?php endif; ?>
<?php endforeach; ?>
</select>
<?php if (ServiceModel::isLockedKind($formKind)): ?>
<p class="text-xs text-amber-700 mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span>Cách tính đã khóa theo loại dịch vụ bắt buộc.</p>
<?php endif; ?>
</div>
</div><div>
                        <label class="block text-sm font-semibold mb-2">Icon Material</label>
                        <input
                            type="text"
                            name="icon"
                            value="<?= e($formService['icon'] ?? 'settings') ?>"
                            placeholder="Ví dụ: wifi, bolt, electric_bike"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Mô tả</label>
                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Mô tả ngắn, thực tế, đủ để admin và cư dân hiểu dịch vụ này dùng để làm gì."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        ><?= e($formService['description'] ?? '') ?></textarea>
                    </div>

                    <?php if (ServiceModel::isLockedKind($formService['kind'] ?? 'other')): ?>
<div class="px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm flex items-center gap-2">
<span class="material-symbols-outlined text-base">lock</span>
Dịch vụ bắt buộc (điện / nước / rác): luôn bật và luôn bắt buộc — không thể tắt hay xóa.
</div>
<input type="hidden" name="is_required" value="1">
<input type="hidden" name="is_active" value="1">
<?php else: ?>
<label class="inline-flex w-full items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50">
<div>
<p class="text-sm font-semibold text-gray-800">Đang kinh doanh</p>
<p class="text-xs text-gray-500">Tắt đi để ẩn khỏi danh sách đăng ký.</p>
</div>
<input type="checkbox" name="is_active" value="1" <?= !empty($formService['is_active']) ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
</label>
<?php endif; ?>
<button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                        <?= $isEditing ? 'Cập nhật dịch vụ' : 'Thêm dịch vụ' ?>
                    </button>

                    <?php if ($isEditing): ?>
                    <a href="<?= BASE_URL ?>?page=admin-services<?= !empty($selectedRoomId) ? '&room_id=' . (int)$selectedRoomId : '' ?>" class="block w-full py-3 text-center text-gray-600 hover:text-primary">
                        Hủy chỉnh sửa
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="2xl:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-lg">Danh sách dịch vụ</h3>
                    <p class="text-sm text-gray-500 mt-1">Badge màu giúp phân biệt nhanh cách tính và đối tượng áp dụng. Dịch vụ bắt buộc sẽ bị khóa xóa.</p>
                </div>

                <?php if (empty($services)): ?>
                <div class="px-6 py-10 text-center text-gray-500">
                    Chưa có dịch vụ nào. Hãy tạo dịch vụ đầu tiên ở form bên trái.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dịch vụ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giá</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cách tính</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Đối tượng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($services as $item): ?>
                            <?php
                            $mode = $billingModeMeta[$item['billing_mode'] ?? 'fixed'] ?? $billingModeMeta['fixed'];
                            $applies = $appliesToMeta[$item['applies_to'] ?? 'room'] ?? $appliesToMeta['room'];
$pendDel = !empty($pendingDeleteByService[(int)($item['id'] ?? 0)]);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                                            <span class="material-symbols-outlined text-3xl"><?= e($item['icon'] ?? 'settings') ?></span>
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900"><?= e($item['name'] ?? '') ?></p>
                                                <?php if ((int)($item['is_required'] ?? 0) === 1): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                    <span class="material-symbols-outlined text-sm">lock</span>
                                                    Bắt buộc
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($item['description'] ?? '', 'Chưa có mô tả cho dịch vụ này.')) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">
                                        <?= (float)($item['price'] ?? 0) > 0 ? number_format((float)($item['price'] ?? 0)) . ' ₫' : 'Miễn phí' ?>
                                    </p>
                                    <p class="text-sm text-gray-500">/<?= e($item['unit'] ?? 'tháng') ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= e($mode['badge_class']) ?>">
                                        <?= e($mode['label']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">
                                        <?= e($applies['label']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ((int)($item['is_active'] ?? 0) === 1): ?>
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700">Đang mở đăng ký</span>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">Đang ẩn đăng ký</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <?php if (!empty($pendDel)): ?><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Sẽ xóa tháng sau</span> <form method="POST" action="<?= BASE_URL ?>?page=admin-undo-delete-service&id=<?= (int)($item['id'] ?? 0) ?>" style="display:inline"><?= csrf_field() ?><button type="submit" class="text-green-700 hover:text-green-800 font-semibold text-sm">Hoàn tác</button></form><?php endif; ?><a href="<?= BASE_URL ?>?page=admin-services&edit=<?= (int)($item['id'] ?? 0) ?><?= !empty($selectedRoomId) ? '&room_id=' . (int)$selectedRoomId : '' ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                            Sửa
                                        </a>
                                        <?php if ((int)($item['is_required'] ?? 0) === 1): ?>
                                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-gray-400 cursor-not-allowed opacity-70">
                                            <span class="material-symbols-outlined text-sm">lock</span>
                                            Không xóa
                                        </span>
                                        <?php else: ?>
                                        <a
                                            href="<?= BASE_URL ?>?page=admin-delete-service&id=<?= (int)($item['id'] ?? 0) ?><?= !empty($selectedRoomId) ? '&room_id=' . (int)$selectedRoomId : '' ?>"
                                            data-confirm="Bạn chắc chắn muốn xóa dịch vụ này khỏi hệ thống?"
                                            class="text-red-600 hover:text-red-800 font-semibold text-sm"
                                        >
                                            Xóa
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">Gán dịch vụ cho phòng</h3>
                        <p class="text-sm text-gray-500 mt-1">Chỉ hiện dịch vụ `applies_to = room`, đang hoạt động và không bắt buộc.</p>
                    </div>
                    <form method="GET" action="<?= BASE_URL ?>" class="flex items-center gap-3">
                        <input type="hidden" name="page" value="admin-services">
                        <label for="service-room-selector" class="text-sm font-semibold text-gray-700">Chọn phòng</label>
                        <select
                            id="service-room-selector"
                            name="room_id"
                            onchange="this.form.submit()"
                            class="min-w-[240px] px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                        >
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int)($room['id'] ?? 0) ?>" <?= (int)($selectedRoomId ?? 0) === (int)($room['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= e(($room['name'] ?? 'Phòng') . ' - ' . ($room['building_name'] ?? 'Chưa có khu') . ' - ' . ($room['floor_name'] ?? 'Chưa có tầng')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-1 space-y-4">
                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-900">Phòng đang chọn</p>
                                <?php if ($selectedRoom): ?>
                                <p class="text-lg font-bold mt-2"><?= e($selectedRoom['name'] ?? '') ?></p>
                                <p class="text-sm text-slate-600 mt-1"><?= e(($selectedRoom['building_name'] ?? 'Chưa có khu') . ' - ' . ($selectedRoom['floor_name'] ?? 'Chưa có tầng')) ?></p>
                                <p class="text-sm text-slate-600 mt-1">Đang ở: <?= (int)($selectedRoom['occupant_count'] ?? 0) ?> người</p>
                                <?php else: ?>
                                <p class="text-sm text-slate-500 mt-2">Chưa chọn được phòng hợp lệ.</p>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
                                <p class="text-sm font-semibold text-amber-900 mb-3">Dịch vụ bắt buộc tự áp dụng</p>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($requiredRoomServices as $requiredService): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-sm font-semibold">
                                        <span class="material-symbols-outlined text-sm"><?= e($requiredService['icon'] ?? 'lock') ?></span>
                                        <?= e($requiredService['name'] ?? '') ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <form method="POST" action="<?= BASE_URL ?>?page=admin-assign-service-to-room" class="space-y-4 rounded-2xl border border-gray-200 p-4 bg-gray-50">
<?= csrf_field() ?>
                                <input type="hidden" name="room_id" value="<?= (int)($selectedRoomId ?? 0) ?>">
                                <input type="hidden" name="assignment_action" value="assign">

                                <div>
                                    <label class="block text-sm font-semibold mb-2">Dịch vụ áp dụng theo phòng</label>
                                    <select
                                        name="service_id"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                        <?= empty($roomAssignableServices) || empty($selectedRoomId) ? 'disabled' : '' ?>
                                    >
                                        <?php foreach ($roomAssignableServices as $roomService): ?>
                                        <option value="<?= (int)($roomService['id'] ?? 0) ?>">
                                            <?= e($roomService['name'] ?? '') ?><?= in_array((int)($roomService['id'] ?? 0), $assignedServiceIds, true) ? ' - Đã gán' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-2">Số lượng</label>
                                    <input
                                        type="number"
                                        min="1"
                                        name="quantity"
                                        value="1"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                                    >
                                </div>

                                <button
                                    type="submit"
                                    class="w-full py-3 bg-secondary text-white rounded-xl font-semibold hover:bg-opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?= empty($roomAssignableServices) || empty($selectedRoomId) ? 'disabled' : '' ?>
                                >
                                    Gán dịch vụ cho phòng
                                </button>
                            </form>
                        </div>

                        <div class="xl:col-span-2">
                            <?php if (empty($selectedRoomId)): ?>
                            <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                                Chưa có phòng nào để thao tác gán dịch vụ.
                            </div>
                            <?php elseif (empty($roomAssignments)): ?>
                            <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500">
                                Phòng này chưa được gán dịch vụ phòng nào ngoài các dịch vụ bắt buộc.
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <?php foreach ($roomAssignments as $assignedService): ?>
                                <?php $assignedMode = $billingModeMeta[$assignedService['billing_mode'] ?? 'fixed'] ?? $billingModeMeta['fixed']; ?>
                                <div class="rounded-2xl border border-primary/15 bg-white p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3">
                                            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                                                <span class="material-symbols-outlined text-2xl"><?= e($assignedService['icon'] ?? 'settings') ?></span>
                                            </div>
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="font-bold text-gray-900"><?= e($assignedService['name'] ?? '') ?></h4>
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Đã gán</span>
                                                </div>
                                                <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($assignedService['description'] ?? '', 'Chưa có mô tả cho dịch vụ này.')) ?></p>
                                            </div>
                                        </div>
                                        <form method="POST" action="<?= BASE_URL ?>?page=admin-assign-service-to-room">
<?= csrf_field() ?>
                                            <input type="hidden" name="room_id" value="<?= (int)($selectedRoomId ?? 0) ?>">
                                            <input type="hidden" name="service_id" value="<?= (int)($assignedService['id'] ?? 0) ?>">
                                            <input type="hidden" name="assignment_action" value="remove">
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                Gỡ
                                            </button>
                                        </form>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold <?= e($assignedMode['badge_class']) ?>">
                                            <?= e($assignedMode['label']) ?>
                                        </span>
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                            Theo phòng
                                        </span>
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            Số lượng: <?= (int)($assignedService['quantity'] ?? 1) ?>
                                        </span>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                        <p class="font-semibold text-primary">
                                            <?= (float)($assignedService['price'] ?? 0) > 0 ? number_format((float)($assignedService['price'] ?? 0)) . ' ₫/' . e($assignedService['unit'] ?? 'tháng') : 'Miễn phí' ?>
                                        </p>
                                        <p class="text-sm text-gray-500">Tự cập nhật nếu gán lại cùng dịch vụ.</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
