<?php
// [DEV-QWEN-A][FIX][2026-08-20] Row dùng chung cho trang admin-accounts và API tìm kiếm tức thì (accountsFilterApi).
$userRow = $userRow ?? [];
$isRenting = ($userRow['account_status'] ?? '') === 'renting';
$userName = (string)($userRow['full_name'] ?? '');
?>
<tr class="hover:bg-gray-50 transition">
<td class="px-6 py-4"><div class="flex items-center gap-3">
<div class="w-11 h-11 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold uppercase"><?= e(mb_substr(trim($userName), 0, 1)) ?></div>
<div><p class="font-semibold text-gray-900"><?= e($userName) ?></p>
<p class="text-sm text-gray-500 mt-0.5"><?= e(!empty($userRow['created_at']) ? 'Đăng ký: ' . date('d/m/Y', strtotime((string)$userRow['created_at'])) : '') ?></p></div>
</div></td>
<td class="px-6 py-4 text-sm text-gray-600"><?= e($userRow['phone'] ?? '—') ?></td>
<td class="px-6 py-4 text-sm text-gray-600"><?= e($userRow['email'] ?? '—') ?></td>
<td class="px-6 py-4">
<?php if ($isRenting): ?>
<span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700 inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">meeting_room</span>Đang thuê</span>
<?php if (!empty($userRow['room_name'])): ?>
<p class="text-xs text-gray-500 mt-1"><?= e($userRow['room_name']) ?></p>
<?php endif; ?>
<?php else: ?>
<span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">Chưa thuê phòng</span>
<?php endif; ?>
</td>
<td class="px-6 py-4">
<?php if ($isRenting): ?>
<span class="inline-flex items-center gap-1 text-sm font-semibold text-gray-400 cursor-not-allowed opacity-70" title="Người đang thuê phòng không thể xóa. Hãy chuyển người này ra khỏi phòng trước."><span class="material-symbols-outlined text-sm">lock</span>Không xóa</span>
<?php else: ?>
<form method="POST" action="<?= BASE_URL ?>?page=admin-delete-account&id=<?= (int)$userRow['id'] ?>" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa tài khoản "<?= e($userName) ?>"? Toàn bộ dữ liệu liên quan sẽ bị xóa.');">
<?= csrf_field() ?>
<button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm">Xóa</button>
</form>
<?php endif; ?>
<button type="button" data-edit-account='<?= e(json_encode($userRow, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>' class="ml-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">Sửa</button>
</td>
</tr>