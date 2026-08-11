<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'services';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý dịch vụ, cách tính giá và lịch sử đổi giá';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$isEditing = !empty($formService['id']);
$billingModeMeta = [];
foreach ($serviceBillingModes as $mode) { $billingModeMeta[$mode['value']] = $mode; }
$iconOptions = ['wifi','bolt','power','lightbulb','ac_unit','air','water_drop','waves','shower','bathtub','hot_tub','water_heater','delete','recycling','cleaning','pest_control','soap','iron','local_laundry_service','local_parking','garage','electric_car','ev_station','directions_car','directions_bike','motorcycle','bed','chair','door_front','key','lock','home','apartment','elevator','kitchen','restaurant','local_cafe','local_grocery_store','shopping_cart','fitness_center','pool','yard','pets','tv','security','settings','package','local_shipping'];
$priceHistories = $priceHistories ?? [];
$pendingDeleteByService = $pendingDeleteByService ?? [];
$roomsUsingByService = $roomsUsingByService ?? [];
$pendingDeactivateByService = $pendingDeactivateByService ?? [];
$pendingDeactivateByService = $pendingDeactivateByService ?? [];
$roomCountByService = $roomCountByService ?? [];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
<div>
<h2 class="text-3xl font-bold">Quản lý Dịch vụ</h2>
<p class="text-gray-500 mt-2">Dịch vụ bắt buộc (điện/nước/rác) tự áp cho mọi phòng. Giá/cách tính mới luôn áp dụng từ tháng kế tiếp.</p>
</div>
<button type="button" id="service-drawer-open" class="px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition inline-flex items-center gap-2">
<span class="material-symbols-outlined text-base">add</span> Thêm dịch vụ mới
</button>
</div>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Tổng dịch vụ</p><p class="text-xl font-bold"><?= count($services ?? []) ?></p></div>
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Đang kinh doanh</p><p class="text-xl font-bold text-green-600"><?= count(array_filter($services ?? [], static fn($i) => (int)($i['is_active'] ?? 0) === 1)) ?></p></div>
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Bắt buộc</p><p class="text-xl font-bold text-amber-600"><?= count(array_filter($services ?? [], static fn($i) => (int)($i['is_required'] ?? 0) === 1)) ?></p></div>
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Chờ tắt / Chờ xóa</p><p class="text-xl font-bold text-rose-600"><?= count(array_filter($services ?? [], static fn($i) => ServiceModel::isPendingDeactivate($i) || ServiceModel::isPendingDelete($i))) ?></p></div>
</div>
<?php if (!empty($serviceMessage)): ?><div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2"><span class="material-symbols-outlined">check_circle</span><?= e($serviceMessage) ?></div><?php endif; ?>
<?php if (!empty($serviceError)): ?><div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2"><span class="material-symbols-outlined">error</span><?= e($serviceError) ?></div><?php endif; ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input type="text" id="service-search-input" placeholder="Tìm kiếm dịch vụ theo tên..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div>
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100"><h3 class="font-bold text-lg">Danh sách dịch vụ</h3></div>
<?php if (empty($services)): ?>
<div class="px-6 py-10 text-center text-gray-500">Chưa có dịch vụ nào.</div>
<?php else: ?>
<div class="overflow-x-auto"><table class="w-full">
<thead class="bg-gray-50"><tr>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dịch vụ</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giá</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cách tính</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng dùng</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($services as $item): ?>
<?php
$itemId = (int)($item['id'] ?? 0);
$mode = $billingModeMeta[$item['billing_mode'] ?? 'fixed'] ?? $billingModeMeta['fixed'];
$pendDel = !empty($pendingDeleteByService[$itemId]);
$pendDeac = !empty($pendingDeactivateByService[$itemId]);
$pendDeac = !empty($pendingDeactivateByService[$itemId]);
$hist = $priceHistories[$itemId] ?? [];
$roomCount = (int)($roomCountByService[$itemId] ?? 0);
$hasPendingPrice = false; $hasPendingMode = false;
$pendingPriceInfo = null; $pendingModeInfo = null;
foreach ($hist as $h) {
if (!$hasPendingPrice && abs((float)($h['new_price'] ?? 0) - (float)($item['price'] ?? 0)) > 0.001) { $hasPendingPrice = true; $pendingPriceInfo = $h; }
if (!$hasPendingMode && !empty($h['new_billing_mode']) && $h['new_billing_mode'] !== ($item['billing_mode'] ?? 'fixed')) { $hasPendingMode = true; $pendingModeInfo = $h; }
}
$hasAnyPending = $hasPendingPrice || $hasPendingMode || $pendDeac;
?>
<tr class="hover:bg-gray-50 transition service-row" data-service-name="<?= e($item['name'] ?? '') ?>">
<td class="px-6 py-4"><div class="flex items-start gap-4">
<div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center"><span class="material-symbols-outlined text-3xl"><?= e($item['icon'] ?? 'settings') ?></span></div>
<div><div class="flex flex-wrap items-center gap-2">
<p class="font-semibold text-gray-900"><?= e($item['name'] ?? '') ?></p>
<?php if ($pendDel): ?><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700"><span class="material-symbols-outlined text-sm">delete_forever</span>Sẽ xóa <?= str_pad((string)(int)($item['delete_month'] ?? 0),2,'0',STR_PAD_LEFT) ?>/<?= (int)($item['delete_year'] ?? 0) ?></span><?php endif; ?>
<?php if ($pendDeac): ?><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700"><span class="material-symbols-outlined text-sm">timer_off</span>Sẽ tắt <?= str_pad((string)(int)($item['deactivate_month'] ?? 0),2,'0',STR_PAD_LEFT) ?>/<?= (int)($item['deactivate_year'] ?? 0) ?></span><?php endif; ?>
</div><p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($item['description'] ?? '', 'Chưa có mô tả.')) ?></p></div>
</div></td>
<!-- CỘT GIÁ -->
<td class="px-6 py-4">
<p class="font-semibold text-gray-900"><?= (float)($item['price'] ?? 0) > 0 ? number_format((float)$item['price']) . ' ₫' : 'Miễn phí' ?> /<?= e($item['unit'] ?? 'tháng') ?><?php if ($hasPendingPrice): ?> <span class="text-xs font-normal text-gray-400">(Hiện tại)</span><?php endif; ?></p>
<?php if ($hasPendingPrice && $pendingPriceInfo): ?>
<div class="mt-1.5 inline-flex items-start gap-1.5 px-3 py-2 rounded-lg bg-blue-50 border border-blue-100 max-w-[240px]">
<span class="material-symbols-outlined text-sm text-blue-600 mt-0.5">schedule</span>
<span class="text-xs font-medium text-blue-700">Giá mới <?= number_format((float)$pendingPriceInfo['new_price']) ?> ₫ áp dụng từ tháng <?= str_pad((string)(int)$pendingPriceInfo['effective_month'],2,'0',STR_PAD_LEFT) ?>/<?= (int)$pendingPriceInfo['effective_year'] ?></span>
</div>
<?php endif; ?>
</td>
<!-- CỘT CÁCH TÍNH -->
<td class="px-6 py-4">
<span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= e($mode['badge_class']) ?>"><?= e($mode['label']) ?></span><?php if ($hasPendingMode): ?> <span class="text-xs text-gray-400">(Hiện tại)</span><?php endif; ?>
<?php if ($hasPendingMode && $pendingModeInfo): ?>
<p class="mt-1.5 text-xs font-medium text-purple-600 flex items-start gap-1"><span class="material-symbols-outlined text-sm mt-0.5">schedule</span>Tính theo <?= e($billingModeMeta[$pendingModeInfo['new_billing_mode']]['label'] ?? $pendingModeInfo['new_billing_mode']) ?> áp dụng từ tháng <?= str_pad((string)(int)$pendingModeInfo['effective_month'],2,'0',STR_PAD_LEFT) ?>/<?= (int)$pendingModeInfo['effective_year'] ?></p>
<?php endif; ?>
</td>
<!-- CỘT TRẠNG THÁI -->
<td class="px-6 py-4"><?php if ((int)($item['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($item['kind'] ?? 'other')): ?><span class="text-sm font-semibold text-gray-700 inline-flex items-center gap-1" title="Dịch vụ bắt buộc - áp dụng cho tất cả phòng đang thuê"><span class="material-symbols-outlined text-base">meeting_room</span>Tất cả</span><?php else: ?><?php $uc = count($roomsUsingByService[$itemId] ?? []); ?><button type="button" data-open-usage="usage-modal-<?= $itemId ?>" class="text-sm font-semibold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1"><span class="material-symbols-outlined text-base">meeting_room</span><?= $uc ?> phòng</button><?php endif; ?></td>
<td class="px-6 py-4">
<?php if ((int)($item['is_active'] ?? 0) === 1 && !$pendDeac): ?>
<span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700">Đang mở</span>
<?php elseif ((int)($item['is_active'] ?? 0) === 1 && $pendDeac): ?>
<span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700">Đang mở</span> <span class="text-xs text-gray-400">(Hiện tại)</span>
<p class="mt-1.5 text-xs font-medium text-orange-600 flex items-start gap-1"><span class="material-symbols-outlined text-sm mt-0.5">schedule</span>Sẽ tắt từ tháng <?= str_pad((string)(int)($item['deactivate_month'] ?? 0),2,'0',STR_PAD_LEFT) ?>/<?= (int)($item['deactivate_year'] ?? 0) ?></p>
<?php else: ?>
<span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">Đang ẩn</span>
<?php endif; ?>
</td>
<!-- CỘT HÀNH ĐỘNG -->
<td class="px-6 py-4"><div class="flex flex-wrap items-center gap-3">
<?php if ($pendDel): ?>
<form method="POST" action="<?= BASE_URL ?>?page=admin-undo-delete-service&id=<?= $itemId ?>"><?= csrf_field() ?><button type="submit" class="text-green-700 hover:text-green-900 font-semibold text-sm">Hoàn tác xóa</button></form>
<?php else: ?>
<a href="<?= BASE_URL ?>?page=admin-services&edit=<?= $itemId ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Sửa</a>
<button type="button" data-open-history="history-modal-<?= $itemId ?>" class="text-purple-600 hover:text-purple-800 font-semibold text-sm inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">history</span>Lịch sử giá</button>
<button type="button" data-open-usage="usage-modal-<?= $itemId ?>" class="text-teal-600 hover:text-teal-800 font-semibold text-sm inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">meeting_room</span>Xem phòng</button>
<?php if ($pendDeac): ?>
<form method="POST" action="<?= BASE_URL ?>?page=admin-undo-deactivate-service&id=<?= $itemId ?>"><?= csrf_field() ?><button type="submit" class="text-amber-600 hover:text-amber-800 font-semibold text-sm">Hoàn tác tắt</button></form>
<?php endif; ?>
<?php if ((int)($item['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($item['kind'] ?? 'other')): ?>
<span class="inline-flex items-center gap-1 text-sm font-semibold text-gray-400 cursor-not-allowed opacity-70"><span class="material-symbols-outlined text-sm">lock</span>Không xóa</span>
<?php elseif ($hasAnyPending): ?>
<button type="button" data-open-delete="delete-modal-<?= $itemId ?>" class="text-red-600 hover:text-red-800 font-semibold text-sm">Xóa</button>
<?php else: ?>
<a href="<?= BASE_URL ?>?page=admin-delete-service&id=<?= $itemId ?>" data-confirm="Bạn chắc chắn muốn xóa dịch vụ này?" class="text-red-600 hover:text-red-800 font-semibold text-sm">Xóa</a>
<?php endif; ?>
<?php endif; ?>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
</div>
<!-- MODAL XÁC NHẬN XÓA (cho dịch vụ có thay đổi chờ) -->
<?php foreach ($services as $item): ?>
<?php
$iid = (int)($item['id'] ?? 0);
$hist2 = $priceHistories[$iid] ?? [];
$pendDeac2 = !empty($pendingDeactivateByService[$iid]);
$hasPending2 = !empty($hist2) || $pendDeac2;
if (!$hasPending2 || (int)($item['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($item['kind'] ?? 'other')) continue;
$rc = (int)($roomCountByService[$iid] ?? 0);
?>
<div id="delete-modal-<?= $iid ?>" class="fixed inset-0 z-50 hidden">
<div class="absolute inset-0 bg-gray-900/50" data-close-delete="delete-modal-<?= $iid ?>"></div>
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
<h4 class="font-bold text-red-600">Xác nhận xóa dịch vụ</h4>
<button type="button" data-close-delete="delete-modal-<?= $iid ?>" class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center"><span class="material-symbols-outlined">close</span></button>
</div>
<div class="p-6 space-y-4">
<p class="text-sm text-gray-700">Dịch vụ <strong>"<?= e($item['name'] ?? '') ?>"</strong> đang có các thay đổi chờ áp dụng:</p>
<div class="space-y-2">
<?php foreach ($hist2 as $h2): ?>
<div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
Giá mới: <?= number_format((float)$h2['new_price']) ?> ₫<?= !empty($h2['new_billing_mode']) ? ' · Cách tính: ' . e($billingModeMeta[$h2['new_billing_mode']]['label'] ?? $h2['new_billing_mode']) : '' ?> — áp dụng từ <?= str_pad((string)(int)$h2['effective_month'],2,'0',STR_PAD_LEFT) ?>/<?= (int)$h2['effective_year'] ?>
</div>
<?php endforeach; ?>
<?php if ($pendDeac2): ?>
<div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
Dịch vụ sẽ được tắt từ tháng <?= str_pad((string)(int)($item['deactivate_month'] ?? 0),2,'0',STR_PAD_LEFT) ?>/<?= (int)($item['deactivate_year'] ?? 0) ?>
</div>
<?php endif; ?>
</div>
<p class="text-sm text-gray-600">Xác nhận xóa sẽ <strong>hủy tất cả thay đổi trên</strong> và <?= $rc > 0 ? 'lên lịch xóa vào tháng sau (đang có ' . $rc . ' phòng sử dụng).' : 'xóa ngay lập tức.' ?></p>
<div class="flex gap-3">
<button type="button" data-close-delete="delete-modal-<?= $iid ?>" class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">Hủy</button>
<form method="POST" action="<?= BASE_URL ?>?page=admin-delete-service&id=<?= $iid ?>" class="flex-1">
<?= csrf_field() ?>
<button type="submit" class="w-full py-3 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition">Xác nhận xóa</button>
</form>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
<!-- DRAWER THÊM/SỬA -->
<div id="service-drawer-backdrop" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-[2px] hidden"></div>
<aside id="service-drawer" class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl overflow-y-auto transition-transform duration-300 translate-x-full hidden">
<div class="p-6 space-y-5">
<div class="flex items-center justify-between gap-3">
<h3 class="text-lg font-bold"><?= $isEditing ? 'Sửa dịch vụ' : 'Thêm dịch vụ mới' ?></h3>
<button type="button" id="service-drawer-close" class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center" aria-label="Đóng form"><span class="material-symbols-outlined">close</span></button>
</div>
<form method="POST" action="<?= BASE_URL ?>?page=admin-save-service" class="space-y-4">
<?= csrf_field() ?>
<?php if ($isEditing): ?><input type="hidden" name="id" value="<?= (int)($formService['id'] ?? 0) ?>"><?php endif; ?>
<div><label class="block text-sm font-semibold mb-2">Tên dịch vụ *</label>
<input type="text" name="name" required value="<?= e($formService['name'] ?? '') ?>" placeholder="Ví dụ: Wifi tầng 2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"></div>
<div class="grid grid-cols-2 gap-4">
<div><label class="block text-sm font-semibold mb-2">Giá</label>
<input type="number" min="0" step="0.01" name="price" value="<?= e($formService['price'] ?? 0) ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"></div>
<?php if ($isEditing): ?>
<div><label class="block text-sm font-semibold mb-2">Tháng áp dụng</label>
<div class="grid grid-cols-2 gap-2">
<select name="effective_month" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<option value="0">Tháng sau</option>
<?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>">T<?= $m ?></option><?php endfor; ?>
</select>
<input type="number" name="effective_year" min="<?= (int)date('Y') ?>" max="<?= (int)date('Y') + 5 ?>" value="<?= (int)date('Y') ?>" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div></div>
<?php endif; ?>
</div>
<div><label class="block text-sm font-semibold mb-2">Cách tính giá</label>
<?php $formKind = $formService['kind'] ?? 'other'; ?>
<?php $allowedModeValues = (isset($kindBillingModes) && is_array($kindBillingModes) && array_key_exists($formKind, $kindBillingModes)) ? $kindBillingModes[$formKind] : array_column($serviceBillingModes ?? [], 'value'); ?>
<select name="billing_mode" id="svc-billing-mode" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<?php foreach (($serviceBillingModes ?? []) as $mode): ?>
<?php if (in_array($mode['value'], $allowedModeValues, true) && ($mode['value'] !== 'fixed' || ($isEditing && ($formService['billing_mode'] ?? '') === 'fixed'))): ?>
<option value="<?= e($mode['value']) ?>" <?= ($formService['billing_mode'] ?? 'fixed') === $mode['value'] ? 'selected' : '' ?>><?= e($mode['label']) ?></option>
<?php endif; ?>
<?php endforeach; ?>
</select>
<?php if (ServiceModel::isLockedKind($formKind)): ?><p class="text-xs text-amber-700 mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span>Cách tính đã khóa theo loại dịch vụ.</p><?php endif; ?></div>
<div id="unit-wrap" class="<?= (($formService['billing_mode'] ?? 'fixed') === 'meter') ? '' : 'hidden' ?>">
<label class="block text-sm font-semibold mb-2">Đơn vị tính</label>
<input type="text" name="unit" id="svc-unit-input" value="<?= e($formService['unit'] ?? '') ?>" placeholder="VD: kWh, m3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"></div>
<div><label class="block text-sm font-semibold mb-2">Icon dịch vụ</label>
<input type="hidden" name="icon" id="svc-icon-input" value="<?= e($formService['icon'] ?? 'settings') ?>">
<button type="button" id="icon-picker-toggle" class="w-full px-4 py-3 border border-gray-200 rounded-xl flex items-center gap-3 hover:border-primary transition text-left bg-white">
<span class="material-symbols-outlined text-2xl text-primary max-w-[140px] truncate" id="icon-preview"><?= e($formService['icon'] ?? 'settings') ?></span>
<span class="text-sm text-gray-600 flex-1 truncate" id="icon-preview-name"><?= e($formService['icon'] ?? 'settings') ?></span>
<span class="material-symbols-outlined text-gray-400">arrow_drop_down</span>
</button>
<div id="icon-picker-panel" class="hidden mt-2 p-3 border border-gray-200 rounded-xl bg-white max-h-56 overflow-y-auto grid grid-cols-6 gap-2">
<?php foreach ($iconOptions as $ic): ?>
<button type="button" data-icon-option="<?= e($ic) ?>" title="<?= e($ic) ?>" class="p-2 h-10 w-10 shrink-0 overflow-hidden rounded-lg hover:bg-primary/10 hover:text-primary flex items-center justify-center text-gray-600"><span class="material-symbols-outlined text-xl leading-none whitespace-nowrap"><?= e($ic) ?></span></button>
<?php endforeach; ?>
</div></div>
<div><label class="block text-sm font-semibold mb-2">Mô tả</label>
<textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"><?= e($formService['description'] ?? '') ?></textarea></div>
<?php if (ServiceModel::isLockedKind($formKind)): ?>
<input type="hidden" name="is_required" value="1"><input type="hidden" name="is_active" value="1">
<?php else: ?>
<label class="inline-flex w-full items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50">
<div><p class="text-sm font-semibold text-gray-800">Đang kinh doanh</p><p class="text-xs text-gray-500">Tắt đi để ẩn khỏi danh sách đăng ký. Nếu có phòng đang dùng sẽ tắt từ tháng sau.</p></div>
<input type="checkbox" name="is_active" value="1" <?= !empty($formService['is_active']) ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
</label>
<?php endif; ?>
<button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition"><?= $isEditing ? 'Cập nhật dịch vụ' : 'Thêm dịch vụ' ?></button>
<?php if ($isEditing): ?><a href="<?= BASE_URL ?>?page=admin-services" class="block w-full py-3 text-center text-gray-600 hover:text-primary">Hủy chỉnh sửa</a><?php endif; ?>
</form>
</div>
</aside>
<!-- HISTORY MODALS -->
<?php foreach ($services as $item): ?>
<?php $iid = (int)($item['id'] ?? 0); $hist = $priceHistories[$iid] ?? []; ?>
<div id="history-modal-<?= $iid ?>" class="fixed inset-0 z-50 hidden">
<div class="absolute inset-0 bg-gray-900/50" data-close-history="history-modal-<?= $iid ?>"></div>
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"><h4 class="font-bold">Lịch sử đổi giá: <?= e($item['name'] ?? '') ?></h4>
<button type="button" data-close-history="history-modal-<?= $iid ?>" class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center"><span class="material-symbols-outlined">close</span></button></div>
<div class="p-6 space-y-3 max-h-[60vh] overflow-y-auto">
<?php if (empty($hist)): ?><p class="text-sm text-gray-500 text-center py-6">Chưa có lịch đổi giá đang chờ.</p><?php endif; ?>
<?php foreach ($hist as $h): ?>
<div class="rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-3">
<div><p class="text-sm font-semibold">Giá mới: <?= number_format((float)$h['new_price']) ?> ₫<?= !empty($h['new_billing_mode']) ? ' · ' . e($billingModeMeta[$h['new_billing_mode']]['label'] ?? $h['new_billing_mode']) : '' ?></p>
<p class="text-xs text-gray-500 mt-1">Áp dụng từ 01/<?= str_pad((string)(int)$h['effective_month'],2,'0',STR_PAD_LEFT) ?>/<?= (int)$h['effective_year'] ?> · Tạo lúc <?= e(!empty($h['created_at']) ? date('d/m/Y H:i', strtotime((string)$h['created_at'])) : '—') ?></p></div>
<form method="POST" action="<?= BASE_URL ?>?page=admin-cancel-price-change&id=<?= (int)$h['id'] ?>"><?= csrf_field() ?><button type="submit" data-confirm="Hủy lịch đổi giá này?" class="text-red-600 font-semibold text-sm">Hủy</button></form>
</div>
<?php endforeach; ?>
</div></div></div>
<?php endforeach; ?>
<?php foreach ($services as $item): ?>
<?php $iid = (int)($item['id'] ?? 0); $usageRooms = $roomsUsingByService[$iid] ?? []; ?>
<div id="usage-modal-<?= $iid ?>" class="fixed inset-0 z-50 hidden">
<div class="absolute inset-0 bg-gray-900/50" data-close-usage="usage-modal-<?= $iid ?>"></div>
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
<h4 class="font-bold">Phòng sử dụng: <?= e($item['name'] ?? '') ?></h4>
<button type="button" data-close-usage="usage-modal-<?= $iid ?>" class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center"><span class="material-symbols-outlined">close</span></button>
</div>
<div class="p-6 max-h-[60vh] overflow-y-auto">
<?php if ((int)($item['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($item['kind'] ?? 'other')): ?>
<div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800 flex items-center gap-2">
<span class="material-symbols-outlined text-base">info</span>
Dịch vụ bắt buộc — tự động áp dụng cho tất cả phòng đang thuê.
</div>
<?php endif; ?>
<?php if (empty($usageRooms)): ?>
<p class="text-sm text-gray-500 text-center py-6">Chưa có phòng nào sử dụng dịch vụ này.</p>
<?php else: ?>
<p class="text-sm text-gray-600 mb-3">Tổng cộng: <strong><?= count($usageRooms) ?></strong> phòng</p>
<table class="w-full">
<thead class="bg-gray-50"><tr>
<th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
<th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Khu / Tầng</th>
<th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">SL</th>
<th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Đăng ký</th>
<th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($usageRooms as $ur): ?>
<tr>
<td class="px-4 py-3 font-semibold text-gray-900"><?= e($ur['room_name'] ?? '') ?></td>
<td class="px-4 py-3 text-sm text-gray-600"><?= e($ur['area_name'] ?? '') ?> · <?= e($ur['floor_name'] ?? '') ?></td>
<td class="px-4 py-3 text-sm text-gray-600"><?= (int)($ur['quantity'] ?? 1) ?></td>
<td class="px-4 py-3 text-sm text-gray-600"><?= !empty($ur['registered_at']) ? date('d/m/Y H:i', strtotime((string)$ur['registered_at'])) : 'Tự động' ?></td>
<td class="px-4 py-3">
<?php $rs = $ur['room_status'] ?? ''; ?>
<?php if ($rs === 'rented'): ?><span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Đang thuê</span>
<?php elseif ($rs === 'available'): ?><span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Trống</span>
<?php elseif ($rs === 'maintenance'): ?><span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Bảo trì</span>
<?php else: ?><span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600"><?= e($rs) ?></span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
<script>
(function(){
var drawer = document.getElementById('service-drawer');
var backdrop = document.getElementById('service-drawer-backdrop');
if (!drawer || !backdrop) { return; }
function openDrawer(){ drawer.classList.remove('hidden'); backdrop.classList.remove('hidden'); void drawer.offsetWidth; drawer.classList.remove('translate-x-full'); }
function closeDrawer(){ drawer.classList.add('translate-x-full'); setTimeout(function(){ drawer.classList.add('hidden'); backdrop.classList.add('hidden'); }, 300); }
var btnOpen = document.getElementById('service-drawer-open');
var btnClose = document.getElementById('service-drawer-close');
if (btnOpen) btnOpen.addEventListener('click', openDrawer);
if (btnClose) btnClose.addEventListener('click', closeDrawer);
backdrop.addEventListener('click', closeDrawer);
document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeDrawer(); } });
var bm = document.getElementById('svc-billing-mode');
var unitWrap = document.getElementById('unit-wrap');
if (bm && unitWrap) { var sync = function(){ unitWrap.classList.toggle('hidden', bm.value !== 'meter'); }; bm.addEventListener('change', sync); sync(); }
var iconToggle = document.getElementById('icon-picker-toggle');
var iconPanel = document.getElementById('icon-picker-panel');
var iconInput = document.getElementById('svc-icon-input');
var iconPreview = document.getElementById('icon-preview');
var iconPreviewName = document.getElementById('icon-preview-name');
if (iconToggle && iconPanel && iconInput) {
iconToggle.addEventListener('click', function(){ iconPanel.classList.toggle('hidden'); });
iconPanel.querySelectorAll('[data-icon-option]').forEach(function(btn){
btn.addEventListener('click', function(){
var val = btn.getAttribute('data-icon-option');
iconInput.value = val;
iconPreview.textContent = val;
iconPreviewName.textContent = val;
iconPanel.classList.add('hidden');
});
});
}
function normalizeVN(str) {
return str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
}
var searchInput = document.getElementById('service-search-input');
if (searchInput) {
searchInput.addEventListener('input', function(){
var q = normalizeVN(this.value.trim());
var keywords = q.split(/\s+/).filter(function(k){ return k !== ''; });
document.querySelectorAll('.service-row').forEach(function(row){
var name = normalizeVN(row.getAttribute('data-service-name') || '');
var match = keywords.length === 0 || keywords.every(function(k){ return name.indexOf(k) !== -1; });
row.style.display = match ? '' : 'none';
});
});
}
document.querySelectorAll('[data-open-history]').forEach(function(btn){
btn.addEventListener('click', function(){ var m = document.getElementById(btn.getAttribute('data-open-history')); if(m) m.classList.remove('hidden'); });
});
document.querySelectorAll('[data-close-history]').forEach(function(el){
el.addEventListener('click', function(){ var m = document.getElementById(el.getAttribute('data-close-history')); if(m) m.classList.add('hidden'); });
});
document.querySelectorAll('[data-open-delete]').forEach(function(btn){
btn.addEventListener('click', function(){ var m = document.getElementById(btn.getAttribute('data-open-delete')); if(m) m.classList.remove('hidden'); });
});
document.querySelectorAll('[data-close-delete]').forEach(function(el){
el.addEventListener('click', function(){ var m = document.getElementById(el.getAttribute('data-close-delete')); if(m) m.classList.add('hidden'); });
});
document.querySelectorAll('[data-open-usage]').forEach(function(btn){
btn.addEventListener('click', function(){
var m = document.getElementById(btn.getAttribute('data-open-usage'));
if(m) m.classList.remove('hidden');
});
});
document.querySelectorAll('[data-close-usage]').forEach(function(el){
el.addEventListener('click', function(){
var m = document.getElementById(el.getAttribute('data-close-usage'));
if(m) m.classList.add('hidden');
});
});
<?php if ($isEditing): ?>openDrawer();<?php endif; ?>
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>