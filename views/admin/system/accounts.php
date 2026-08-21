<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'accounts';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý tài khoản quản trị và người dùng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
$admins = $admins ?? [];
$pagedUsers = $pagedUsers ?? [];
$totalUsers = (int)($totalUsers ?? 0);
$totalPages = max(1, (int)($totalPages ?? 1));
$page = max(1, (int)($page ?? 1));
$perPage = 10;
$keyword = trim((string)($keyword ?? ''));
$statusFilter = in_array(($statusFilter ?? 'all'), ['all', 'renting', 'not_renting'], true) ? $statusFilter : 'all';
$statusCounts = [
    'all' => $totalUsers,
    'renting' => 0,
    'not_renting' => 0,
];
foreach (($allUsersStatus ?? []) as $statusRow) {
    if (($statusRow['account_status'] ?? '') === 'renting') {
        $statusCounts['renting']++;
    } elseif (($statusRow['account_status'] ?? '') === 'not_renting') {
        $statusCounts['not_renting']++;
    }
}
$buildAccountPageUrl = $buildAccountPageUrl ?? static fn($pageNumber) => BASE_URL . '?page=admin-accounts';
$accountForm = array_merge(['full_name' => '', 'phone' => '', 'email' => ''], is_array($accountForm ?? null) ? $accountForm : []);
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
<div>
<h2 class="text-3xl font-bold">Quản lý tài khoản</h2>
<p class="text-gray-500 mt-2">Quản lý tài khoản quản trị viên và người dùng đã đăng ký (người thuê, khách vãng lai).</p>
</div>
<button type="button" id="account-drawer-open" class="px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition inline-flex items-center gap-2">
<span class="material-symbols-outlined text-base">person_add</span> Thêm tài khoản người dùng
</button>
</div>

<?php if (!empty($accountMessage)): ?><div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2"><span class="material-symbols-outlined">check_circle</span><?= e($accountMessage) ?></div><?php endif; ?>
<?php if (!empty($accountError)): ?><div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2"><span class="material-symbols-outlined">error</span><?= e($accountError) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Tổng người dùng</p><p class="text-xl font-bold"><?= (int)$statusCounts['all'] ?></p></div>
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Đang thuê phòng</p><p class="text-xl font-bold text-green-600"><?= (int)$statusCounts['renting'] ?></p></div>
<div class="px-4 py-3 rounded-2xl bg-white border border-gray-200"><p class="text-xs text-gray-500">Chưa thuê phòng</p><p class="text-xl font-bold text-gray-600"><?= (int)$statusCounts['not_renting'] ?></p></div>
</div>

<!-- TÀI KHOẢN QUẢN TRỊ -->
<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
<h3 class="font-bold text-lg">Tài khoản quản trị</h3>
<span class="text-xs font-semibold text-gray-500 inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span>Không thể thêm hoặc xóa tài khoản quản trị</span>
</div>
<?php if (empty($admins)): ?>
<div class="px-6 py-10 text-center text-gray-500">Không có tài khoản quản trị nào.</div>
<?php else: ?>
<div class="overflow-x-auto"><table class="w-full">
<thead class="bg-gray-50"><tr>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Quản trị viên</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số điện thoại</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
</tr></thead>
<tbody class="divide-y divide-gray-100">
<?php foreach ($admins as $adminRow): ?>
<tr class="hover:bg-gray-50 transition">
<td class="px-6 py-4"><div class="flex items-center gap-3">
<div class="w-11 h-11 rounded-full bg-secondary/10 text-secondary flex items-center justify-center font-bold uppercase"><?= e(mb_substr(trim((string)($adminRow['full_name'] ?? '')), 0, 1)) ?></div>
<div><p class="font-semibold text-gray-900"><?= e($adminRow['full_name'] ?? '') ?></p>
<p class="text-sm text-gray-500 mt-0.5"><?= e($adminRow['email'] ?? '') ?></p></div>
</div></td>
<td class="px-6 py-4 text-sm text-gray-600"><?= e($adminRow['phone'] ?? '—') ?></td>
<td class="px-6 py-4 text-sm text-gray-600"><?= e($adminRow['email'] ?? '—') ?></td>
<td class="px-6 py-4"><span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-secondary/10 text-secondary">Quản trị viên</span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>

<!-- TÀI KHOẢN NGƯỜI DÙNG -->
<div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-4">
<form method="GET" action="<?= BASE_URL ?>?page=admin-accounts" id="account-search-form" class="flex flex-col lg:flex-row lg:items-center gap-3">
<div class="relative flex-1">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input type="text" name="search" id="account-search-input" value="<?= e($keyword) ?>" placeholder="Tìm kiếm theo tên người dùng..." autocomplete="off" class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<span id="accounts-loading" class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hidden animate-spin">progress_activity</span>
</div>
<div class="flex items-center gap-2" id="account-status-bar">
<a href="<?= e($buildAccountPageUrl(1, 'all')) ?>" data-status="all" class="px-4 py-3 rounded-xl text-sm font-semibold transition <?= $statusFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Hiển thị tất cả người dùng</a>
<a href="<?= e($buildAccountPageUrl(1, 'renting')) ?>" data-status="renting" class="px-4 py-3 rounded-xl text-sm font-semibold transition <?= $statusFilter === 'renting' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Đang thuê phòng</a>
<a href="<?= e($buildAccountPageUrl(1, 'not_renting')) ?>" data-status="not_renting" class="px-4 py-3 rounded-xl text-sm font-semibold transition <?= $statusFilter === 'not_renting' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Chưa thuê phòng</a>
</div>
</form>
</div>

<div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
<h3 class="font-bold text-lg">Tài khoản người dùng</h3>
<span id="accounts-total-label" class="text-xs text-gray-500"><?= (int)$totalUsers ?> tài khoản · <?= (int)$perPage ?> tài khoản/trang</span>
</div>
<div id="accounts-empty" class="px-6 py-10 text-center text-gray-500 <?= empty($pagedUsers) ? '' : 'hidden' ?>">Không có tài khoản phù hợp.</div>
<div class="overflow-x-auto"><table class="w-full">
<thead class="bg-gray-50"><tr>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người dùng</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số điện thoại</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
</tr></thead>
<tbody id="accounts-tbody" class="divide-y divide-gray-100">
<?php foreach ($pagedUsers as $userRow): ?>
<?php require BASE_PATH . 'views/admin/system/partials/account_row.php'; ?>
<?php endforeach; ?>
</tbody></table></div>
<div id="accounts-pagination">
<?php $buildUrl = $buildAccountPageUrl; ?>
<?php require BASE_PATH . 'views/admin/system/partials/account_pagination.php'; ?>
</div>
</div>
</div>

<!-- DRAWER THÊM TÀI KHOẢN NGƯỜI DÙNG -->
<div id="account-drawer-backdrop" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-[2px] hidden"></div>
<aside id="account-drawer" class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl overflow-y-auto transition-transform duration-300 translate-x-full hidden">
<div class="p-6 space-y-5">
<div class="flex items-center justify-between gap-3">
<h3 class="text-lg font-bold">Thêm tài khoản người dùng</h3>
<button type="button" id="account-drawer-close" class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center" aria-label="Đóng form"><span class="material-symbols-outlined">close</span></button>
</div>
<p class="text-sm text-gray-500 -mt-3">Tài khoản tạo ra là người dùng thường (người thuê / khách vãng lai). Không thể tạo thêm tài khoản quản trị.</p>
<form method="POST" action="<?= BASE_URL ?>?page=admin-save-account" class="space-y-4" data-admin-add-form novalidate>
<?= csrf_field() ?>
<div class="admin-field">
    <label for="add_full_name" class="block text-sm font-semibold mb-2">Họ và tên <span class="text-red-500">*</span></label>
    <input type="text" name="full_name" id="add_full_name" required value="<?= e($accountForm['full_name'] ?? '') ?>" placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="add_full_name_error">
    <p id="add_full_name_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
</div>
<div class="admin-field">
    <label for="add_phone" class="block text-sm font-semibold mb-2">Số điện thoại <span class="text-red-500">*</span></label>
    <input type="tel" name="phone" id="add_phone" required value="<?= e($accountForm['phone'] ?? '') ?>" placeholder="0xxxxxxxxx" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="add_phone_error" inputmode="tel" autocomplete="tel">
    <p id="add_phone_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
    <p class="field-hint mt-1 text-xs text-gray-500">Chỉ số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.</p>
</div>
<div class="admin-field">
    <label for="add_email" class="block text-sm font-semibold mb-2">Email (Không bắt buộc)</label>
    <input type="email" name="email" id="add_email" value="<?= e($accountForm['email'] ?? '') ?>" placeholder="example@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="add_email_error">
    <p id="add_email_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
    <p class="field-hint mt-1 text-xs text-gray-500">Email không bắt buộc. Nếu nhập, phải đúng định dạng.</p>
</div>
<div class="admin-field">
    <label for="add_password" class="block text-sm font-semibold mb-2">Mật khẩu <span class="text-red-500">*</span></label>
    <div class="relative">
        <input type="password" name="password" id="add_password" required minlength="6" placeholder="Ít nhất 6 ký tự" class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="add_password_error">
        <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors" aria-label="Hiện mật khẩu" aria-pressed="false">
            <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/><path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
        </button>
    </div>
    <p id="add_password_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
</div>
<button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">Thêm tài khoản</button>
</form>
</div>
</aside>

<!-- DRAWER SỬA TÀI KHOẢN NGƯỜI DÙNG -->
<div id="account-edit-drawer-backdrop" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-[2px] hidden"></div>
<aside id="account-edit-drawer" class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl overflow-y-auto transition-transform duration-300 translate-x-full hidden">
<div class="p-6 space-y-5">
<div class="flex items-center justify-between gap-3">
<h3 class="text-lg font-bold">Sửa tài khoản người dùng</h3>
<button type="button" id="account-edit-drawer-close" class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center" aria-label="Đóng form"><span class="material-symbols-outlined">close</span></button>
</div>
<p class="text-sm text-gray-500 -mt-3">Admin có thể đổi mật khẩu người dùng mà không cần mật khẩu cũ hoặc xác thực OTP.</p>
<form method="POST" action="<?= BASE_URL ?>?page=admin-update-account" class="space-y-4" data-admin-edit-form novalidate>
<?= csrf_field() ?>
<input type="hidden" name="id" id="edit-account-id">
<div class="admin-field">
    <label for="edit-full_name" class="block text-sm font-semibold mb-2">Họ và tên <span class="text-red-500">*</span></label>
    <input type="text" name="full_name" id="edit-full_name" required placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="edit_full_name_error">
    <p id="edit_full_name_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
</div>
<div class="admin-field">
    <label for="edit-phone" class="block text-sm font-semibold mb-2">Số điện thoại <span class="text-red-500">*</span></label>
    <input type="tel" name="phone" id="edit-phone" required placeholder="0xxxxxxxxx" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="edit_phone_error" inputmode="tel" autocomplete="tel">
    <p id="edit_phone_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
    <p class="field-hint mt-1 text-xs text-gray-500">Chỉ số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.</p>
</div>
<div class="admin-field">
    <label for="edit-email" class="block text-sm font-semibold mb-2">Email (Không bắt buộc)</label>
    <input type="email" name="email" id="edit-email" placeholder="example@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="edit_email_error">
    <p id="edit_email_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
    <p class="field-hint mt-1 text-xs text-gray-500">Email không bắt buộc. Nếu nhập, phải đúng định dạng.</p>
</div>
<div class="admin-field">
    <label for="edit-password" class="block text-sm font-semibold mb-2">Mật khẩu mới (để trống nếu không đổi)</label>
    <div class="relative">
        <input type="password" name="password" id="edit-password" minlength="6" placeholder="Ít nhất 6 ký tự" class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition" aria-describedby="edit_password_error">
        <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors" aria-label="Hiện mật khẩu" aria-pressed="false">
            <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/><path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
        </button>
    </div>
    <p id="edit_password_error" class="field-error mt-2 text-sm text-red-600 hidden"></p>
</div>
<button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">Cập nhật</button>
</form>
</div>
</aside>
<script src="<?= BASE_URL ?>assets/js/account-validators.js"></script>
<script>
(function(){
var drawer = document.getElementById('account-drawer');
var backdrop = document.getElementById('account-drawer-backdrop');
if (!drawer || !backdrop) return;
function openDrawer(){ drawer.classList.remove('hidden'); backdrop.classList.remove('hidden'); void drawer.offsetWidth; drawer.classList.remove('translate-x-full'); }
function closeDrawer(){ drawer.classList.add('translate-x-full'); setTimeout(function(){ drawer.classList.add('hidden'); backdrop.classList.add('hidden'); }, 300); }
var btnOpen = document.getElementById('account-drawer-open');
var btnClose = document.getElementById('account-drawer-close');
if (btnOpen) btnOpen.addEventListener('click', openDrawer);
if (btnClose) btnClose.addEventListener('click', closeDrawer);
backdrop.addEventListener('click', closeDrawer);
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeDrawer(); });
<?php if (!empty($accountError)): ?>openDrawer();<?php endif; ?>

// Edit drawer
var editDrawer = document.getElementById('account-edit-drawer');
var editBackdrop = document.getElementById('account-edit-drawer-backdrop');
if (editDrawer && editBackdrop) {
    function openEditDrawer(user) {
        document.getElementById('edit-account-id').value = user.id || '';
        document.getElementById('edit-full_name').value = user.full_name || '';
        document.getElementById('edit-phone').value = user.phone || '';
        document.getElementById('edit-email').value = user.email || '';
        document.getElementById('edit-password').value = '';
        editDrawer.classList.remove('hidden');
        editBackdrop.classList.remove('hidden');
        void editDrawer.offsetWidth;
        editDrawer.classList.remove('translate-x-full');
    }
    function closeEditDrawer() {
        editDrawer.classList.add('translate-x-full');
        setTimeout(function(){ editDrawer.classList.add('hidden'); editBackdrop.classList.add('hidden'); }, 300);
    }
    var editBtnClose = document.getElementById('account-edit-drawer-close');
    if (editBtnClose) editBtnClose.addEventListener('click', closeEditDrawer);
    editBackdrop.addEventListener('click', closeEditDrawer);
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeEditDrawer(); });
    // [DEV-QWEN-A][FIX][2026-08-20] Dùng event delegation để nút "Sửa" hoạt động
    // cả với các dòng do tìm kiếm tức thì (AJAX) render lại.
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-edit-account]');
        if (!btn) return;
        var user = JSON.parse(btn.getAttribute('data-edit-account'));
        openEditDrawer(user);
    });
}
<?php if (!empty($accountError) && !empty($oldAccountInput['id'])): ?>openEditDrawer(<?= json_encode($oldAccountInput, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);<?php endif; ?>

// Admin Add User Form Validation
(function() {
    var form = document.querySelector('[data-admin-add-form]');
    if (!form) return;

    var fullNameInput = document.getElementById('add_full_name');
    var phoneInput = document.getElementById('add_phone');
    var emailInput = document.getElementById('add_email');
    var passwordInput = document.getElementById('add_password');

    function setFieldError(input, message) {
        if (!input) return;
        var box = document.getElementById(input.id + '_error');
        if (!box) return;

        box.textContent = message;
        box.classList.toggle('hidden', !message);
        input.classList.toggle('border-red-300', !!message);
        input.classList.toggle('bg-red-50', !!message);
    }

    function validateField(input) {
        if (!input) return;

        if (input.id === 'add_full_name') {
            setFieldError(input, validateFullName(input.value));
        } else if (input.id === 'add_email') {
            if (input.value.trim()) {
                var result = validateEmailStrict(input.value);
                setFieldError(input, result.valid ? '' : result.message);
            } else {
                setFieldError(input, '');
            }
        } else if (input.id === 'add_phone') {
            if (!input.value.trim()) {
                setFieldError(input, 'Vui lòng nhập số điện thoại.');
            } else {
                var normalized = normalizePhoneInput(input.value);
                if (!normalized) {
                    var raw = input.value.trim();
                    if (!/^[0-9\s+]+$/.test(raw)) {
                        setFieldError(input, 'Số điện thoại chỉ được chứa số, khoảng trắng và dấu +.');
                    } else if (raw.startsWith('+') && raw.indexOf('+') !== 0) {
                        setFieldError(input, 'Dấu + chỉ được phép ở đầu số.');
                    } else if (raw.startsWith('+84')) {
                        var suffix = raw.substring(3);
                        if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                            setFieldError(input, 'Số điện thoại +84 không hợp lệ. Phải có 9 số sau +84 và số đầu không được là 0.');
                        }
                    } else if (raw.startsWith('84') && !raw.startsWith('+')) {
                        var suffix = raw.substring(2);
                        if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                            setFieldError(input, 'Số điện thoại 84 không hợp lệ. Phải có 9 số sau 84 và số đầu không được là 0.');
                        }
                    } else if (raw.startsWith('0')) {
                        if (raw.length !== 10 || !/^\d+$/.test(raw)) {
                            setFieldError(input, 'Số điện thoại 0xxxxxxxxx phải có đúng 10 chữ số.');
                        }
                    } else {
                        setFieldError(input, 'Số điện thoại phải bắt đầu bằng 0, +84 hoặc 84.');
                    }
                } else {
                    setFieldError(input, '');
                }
            }
        } else if (input.id === 'add_password') {
            setFieldError(input, validatePassword(input.value));
        }
    }

    // Input event listeners for real-time validation with debounce
    [fullNameInput, emailInput, phoneInput, passwordInput].forEach(function(input) {
        if (input) {
            var debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    validateField(input);
                }, 500);
            });
            input.addEventListener('blur', function() {
                clearTimeout(debounceTimer);
                validateField(input);
            });
        }
    });

    // Toggle password visibility
    var toggleBtn = form.querySelector('.toggle-password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            var wasHidden = passwordInput.type === 'password';
            passwordInput.type = wasHidden ? 'text' : 'password';
            toggleBtn.querySelector('.icon-eye').classList.toggle('hidden', wasHidden);
            toggleBtn.querySelector('.icon-eye-off').classList.toggle('hidden', !wasHidden);
            toggleBtn.setAttribute('aria-pressed', String(wasHidden));
            toggleBtn.setAttribute('aria-label', wasHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    }

    // Form submit validation
    form.addEventListener('submit', function(event) {
        var hasError = false;

        [fullNameInput, emailInput, phoneInput, passwordInput].forEach(function(input) {
            if (!input) return;
            setFieldError(input, '');
        });

        var fullNameError = validateFullName(fullNameInput ? fullNameInput.value : '');
        if (fullNameError) {
            if (fullNameInput) setFieldError(fullNameInput, fullNameError);
            hasError = true;
        }

        if (emailInput && emailInput.value.trim()) {
            var emailResult = validateEmailStrict(emailInput.value);
            if (!emailResult.valid) {
                setFieldError(emailInput, emailResult.message);
                hasError = true;
            }
        }

        if (phoneInput && !phoneInput.value.trim()) {
            setFieldError(phoneInput, 'Vui lòng nhập số điện thoại.');
            hasError = true;
        } else if (phoneInput && phoneInput.value.trim()) {
            var normalized = normalizePhoneInput(phoneInput.value);
            if (!normalized) {
                var raw = phoneInput.value.trim();
                if (!/^[0-9\s+]+$/.test(raw)) {
                    setFieldError(phoneInput, 'Số điện thoại chỉ được chứa số, khoảng trắng và dấu +.');
                } else if (raw.startsWith('+') && raw.indexOf('+') !== 0) {
                    setFieldError(phoneInput, 'Dấu + chỉ được phép ở đầu số.');
                } else if (raw.startsWith('+84')) {
                    var suffix = raw.substring(3);
                    if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                        setFieldError(phoneInput, 'Số điện thoại +84 không hợp lệ. Phải có 9 số sau +84 và số đầu không được là 0.');
                    }
                } else if (raw.startsWith('84') && !raw.startsWith('+')) {
                    var suffix = raw.substring(2);
                    if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                        setFieldError(phoneInput, 'Số điện thoại 84 không hợp lệ. Phải có 9 số sau 84 và số đầu không được là 0.');
                    }
                } else if (raw.startsWith('0')) {
                    if (raw.length !== 10 || !/^\d+$/.test(raw)) {
                        setFieldError(phoneInput, 'Số điện thoại 0xxxxxxxxx phải có đúng 10 chữ số.');
                    }
                } else {
                    setFieldError(phoneInput, 'Số điện thoại phải bắt đầu bằng 0, +84 hoặc 84.');
                }
                hasError = true;
            }
        }

        if (passwordInput) {
            var passwordError = validatePassword(passwordInput.value);
            if (passwordError) {
                setFieldError(passwordInput, passwordError);
                hasError = true;
            }
        }

        if (hasError) {
            event.preventDefault();
        }
    });
})();

// Admin Edit User Form Validation
(function() {
    var form = document.querySelector('[data-admin-edit-form]');
    if (!form) return;

    var fullNameInput = document.getElementById('edit-full_name');
    var phoneInput = document.getElementById('edit-phone');
    var emailInput = document.getElementById('edit-email');
    var passwordInput = document.getElementById('edit-password');

    function setFieldError(input, message) {
        if (!input) return;
        var box = document.getElementById(input.id + '_error');
        if (!box) return;

        box.textContent = message;
        box.classList.toggle('hidden', !message);
        input.classList.toggle('border-red-300', !!message);
        input.classList.toggle('bg-red-50', !!message);
    }

    function validateField(input) {
        if (!input) return;

        if (input.id === 'edit-full_name') {
            setFieldError(input, validateFullName(input.value));
        } else if (input.id === 'edit-email') {
            if (input.value.trim()) {
                var result = validateEmailStrict(input.value);
                setFieldError(input, result.valid ? '' : result.message);
            } else {
                setFieldError(input, '');
            }
        } else if (input.id === 'edit-phone') {
            if (!input.value.trim()) {
                setFieldError(input, 'Vui lòng nhập số điện thoại.');
            } else {
                var normalized = normalizePhoneInput(input.value);
                if (!normalized) {
                    var raw = input.value.trim();
                    if (!/^[0-9\s+]+$/.test(raw)) {
                        setFieldError(input, 'Số điện thoại chỉ được chứa số, khoảng trắng và dấu +.');
                    } else if (raw.startsWith('+') && raw.indexOf('+') !== 0) {
                        setFieldError(input, 'Dấu + chỉ được phép ở đầu số.');
                    } else if (raw.startsWith('+84')) {
                        var suffix = raw.substring(3);
                        if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                            setFieldError(input, 'Số điện thoại +84 không hợp lệ. Phải có 9 số sau +84 và số đầu không được là 0.');
                        }
                    } else if (raw.startsWith('84') && !raw.startsWith('+')) {
                        var suffix = raw.substring(2);
                        if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                            setFieldError(input, 'Số điện thoại 84 không hợp lệ. Phải có 9 số sau 84 và số đầu không được là 0.');
                        }
                    } else if (raw.startsWith('0')) {
                        if (raw.length !== 10 || !/^\d+$/.test(raw)) {
                            setFieldError(input, 'Số điện thoại 0xxxxxxxxx phải có đúng 10 chữ số.');
                        }
                    } else {
                        setFieldError(input, 'Số điện thoại phải bắt đầu bằng 0, +84 hoặc 84.');
                    }
                } else {
                    setFieldError(input, '');
                }
            }
        } else if (input.id === 'edit-password') {
            if (input.value) {
                setFieldError(input, validatePassword(input.value));
            } else {
                setFieldError(input, '');
            }
        }
    }

    // Input event listeners for real-time validation with debounce
    [fullNameInput, emailInput, phoneInput, passwordInput].forEach(function(input) {
        if (input) {
            var debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    validateField(input);
                }, 500);
            });
            input.addEventListener('blur', function() {
                clearTimeout(debounceTimer);
                validateField(input);
            });
        }
    });

    // Toggle password visibility
    var toggleBtn = form.querySelector('.toggle-password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            var wasHidden = passwordInput.type === 'password';
            passwordInput.type = wasHidden ? 'text' : 'password';
            toggleBtn.querySelector('.icon-eye').classList.toggle('hidden', wasHidden);
            toggleBtn.querySelector('.icon-eye-off').classList.toggle('hidden', !wasHidden);
            toggleBtn.setAttribute('aria-pressed', String(wasHidden));
            toggleBtn.setAttribute('aria-label', wasHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    }

    // Form submit validation
    form.addEventListener('submit', function(event) {
        var hasError = false;

        [fullNameInput, emailInput, phoneInput, passwordInput].forEach(function(input) {
            if (!input) return;
            setFieldError(input, '');
        });

        var fullNameError = validateFullName(fullNameInput ? fullNameInput.value : '');
        if (fullNameError) {
            if (fullNameInput) setFieldError(fullNameInput, fullNameError);
            hasError = true;
        }

        if (emailInput && emailInput.value.trim()) {
            var emailResult = validateEmailStrict(emailInput.value);
            if (!emailResult.valid) {
                setFieldError(emailInput, emailResult.message);
                hasError = true;
            }
        }

        if (phoneInput && !phoneInput.value.trim()) {
            setFieldError(phoneInput, 'Vui lòng nhập số điện thoại.');
            hasError = true;
        } else if (phoneInput && phoneInput.value.trim()) {
            var normalized = normalizePhoneInput(phoneInput.value);
            if (!normalized) {
                var raw = phoneInput.value.trim();
                if (!/^[0-9\s+]+$/.test(raw)) {
                    setFieldError(phoneInput, 'Số điện thoại chỉ được chứa số, khoảng trắng và dấu +.');
                } else if (raw.startsWith('+') && raw.indexOf('+') !== 0) {
                    setFieldError(phoneInput, 'Dấu + chỉ được phép ở đầu số.');
                } else if (raw.startsWith('+84')) {
                    var suffix = raw.substring(3);
                    if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                        setFieldError(phoneInput, 'Số điện thoại +84 không hợp lệ. Phải có 9 số sau +84 và số đầu không được là 0.');
                    }
                } else if (raw.startsWith('84') && !raw.startsWith('+')) {
                    var suffix = raw.substring(2);
                    if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
                        setFieldError(phoneInput, 'Số điện thoại 84 không hợp lệ. Phải có 9 số sau 84 và số đầu không được là 0.');
                    }
                } else if (raw.startsWith('0')) {
                    if (raw.length !== 10 || !/^\d+$/.test(raw)) {
                        setFieldError(phoneInput, 'Số điện thoại 0xxxxxxxxx phải có đúng 10 chữ số.');
                    }
                } else {
                    setFieldError(phoneInput, 'Số điện thoại phải bắt đầu bằng 0, +84 hoặc 84.');
                }
                hasError = true;
            }
        }

        if (passwordInput && passwordInput.value) {
            var passwordError = validatePassword(passwordInput.value);
            if (passwordError) {
                setFieldError(passwordInput, passwordError);
                hasError = true;
            }
        }

        if (hasError) {
            event.preventDefault();
        }
    });
})();
})();
</script>
<script>
// [DEV-QWEN-A][FIX][2026-08-20] Instant search / search-as-you-type cho admin-accounts:
// kết quả cập nhật ngay sau mỗi ký tự gõ (debounce 300ms) qua API accountsFilterApi,
// không tải lại trang; vẫn giữ URL đồng bộ để refresh/back vẫn đúng bộ lọc.
(function () {
    var searchForm = document.getElementById('account-search-form');
    var searchInput = document.getElementById('account-search-input');
    var statusBar = document.getElementById('account-status-bar');
    var tbody = document.getElementById('accounts-tbody');
    var emptyBox = document.getElementById('accounts-empty');
    var totalLabel = document.getElementById('accounts-total-label');
    var paginationBox = document.getElementById('accounts-pagination');
    var loadingIcon = document.getElementById('accounts-loading');
    if (!searchInput || !tbody) return;

    var BASE = window.BASE_URL || '<?= BASE_URL ?>';
    var seq = 0;

    function activeStatus() {
        if (!statusBar) return 'all';
        var active = statusBar.querySelector('.bg-primary');
        return active ? (active.getAttribute('data-status') || 'all') : 'all';
    }

    function currentSearch() {
        return searchInput.value.trim();
    }

    function buildApiUrl(state) {
        var params = new URLSearchParams();
        params.set('page', 'api-admin-accounts-filter');
        params.set('search', state.search);
        params.set('status', state.status);
        if (state.page > 1) params.set('p', String(state.page));
        return BASE + '?' + params.toString();
    }

    function syncUrl(state) {
        var params = new URLSearchParams();
        params.set('page', 'admin-accounts');
        if (state.search) params.set('search', state.search);
        if (state.status !== 'all') params.set('status', state.status);
        if (state.page > 1) params.set('p', String(state.page));
        try { history.replaceState(null, '', BASE + '?' + params.toString()); } catch (e) {}
    }

    function fetchResults(state) {
        var requestId = ++seq;
        if (loadingIcon) loadingIcon.classList.remove('hidden');
        fetch(buildApiUrl(state), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (requestId !== seq || !data || data.success !== true) return;
                tbody.innerHTML = data.rowsHtml || '';
                if (emptyBox) emptyBox.classList.toggle('hidden', data.total > 0);
                if (totalLabel) totalLabel.textContent = data.total + ' tài khoản · 10 tài khoản/trang';
                if (paginationBox) paginationBox.innerHTML = data.paginationHtml || '';
            })
            .catch(function () {})
            .then(function () {
                if (requestId === seq && loadingIcon) loadingIcon.classList.add('hidden');
            });
    }

    function applyState(status, page) {
        var state = { search: currentSearch(), status: status, page: page };
        fetchResults(state);
        syncUrl(state);
    }

    var debounceTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            applyState(activeStatus(), 1);
        }, 300);
    });

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) { e.preventDefault(); });
    }

    if (statusBar) {
        statusBar.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-status]');
            if (!btn) return;
            e.preventDefault();
            statusBar.querySelectorAll('[data-status]').forEach(function (b) {
                var isActive = b === btn;
                b.classList.toggle('bg-primary', isActive);
                b.classList.toggle('text-white', isActive);
                b.classList.toggle('bg-gray-100', !isActive);
                b.classList.toggle('text-gray-700', !isActive);
            });
            applyState(btn.getAttribute('data-status') || 'all', 1);
        });
    }

    if (paginationBox) {
        paginationBox.addEventListener('click', function (e) {
            var link = e.target.closest('[data-account-page]');
            if (!link) return;
            e.preventDefault();
            applyState(activeStatus(), parseInt(link.getAttribute('data-account-page'), 10) || 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>