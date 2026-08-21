<?php
// [DEV-QWEN-A][FIX][2026-08-20] Phân trang dùng chung cho trang admin-accounts và API tìm kiếm tức thì.
// Cần: $page, $totalPages, $buildUrl (closure tạo URL trang).
$page = max(1, (int)($page ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$buildUrl = $buildUrl ?? static fn($pageNumber) => BASE_URL . '?page=admin-accounts';
?>
<?php if ($totalPages > 1): ?>
<div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
<p class="text-sm text-gray-500">Trang <?= (int)$page ?> / <?= (int)$totalPages ?></p>
<div class="flex items-center gap-2">
<?php if ($page > 1): ?><a href="<?= e($buildUrl($page - 1)) ?>" data-account-page="<?= (int)($page - 1) ?>" class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:bg-gray-50 transition">Trước</a><?php endif; ?>
<?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
<a href="<?= e($buildUrl($pageNumber)) ?>" data-account-page="<?= (int)$pageNumber ?>" class="px-4 py-2 rounded-xl text-sm font-semibold transition <?= $pageNumber === $page ? 'bg-primary text-white' : 'border border-gray-200 text-gray-700 hover:bg-gray-50' ?>"><?= (int)$pageNumber ?></a>
<?php endfor; ?>
<?php if ($page < $totalPages): ?><a href="<?= e($buildUrl($page + 1)) ?>" data-account-page="<?= (int)($page + 1) ?>" class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:bg-gray-50 transition">Sau</a><?php endif; ?>
</div>
</div>
<?php endif; ?>