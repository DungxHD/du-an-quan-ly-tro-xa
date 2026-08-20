<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'tenants';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý người thuê và tạo hợp đồng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';

$formatDate = static function ($value, $default = 'Chưa có dữ liệu') {
    $dateValue = trim((string)($value ?? ''));
    if ($dateValue === '') {
        return $default;
    }

    $timestamp = strtotime($dateValue);
    return $timestamp ? date('d/m/Y', $timestamp) : $dateValue;
};

$formatMoney = static function ($value) {
    return number_format((float)$value, 0, ',', '.') . 'đ';
};

$tenantCount = count($tenants);
$tenantWithoutRoom = count(array_filter($tenants, static fn($tenant) => empty($tenant['room_id'])));
$activeContractCount = count($activeContractsByUserId);

/**
 * Danh sách tài khoản CHƯA gán phòng (không room_id, không hợp đồng active)
 * để ô tìm kiếm ở form gán phòng chỉ hiện nhóm có thể gán.
 */
$assignableTenants = [];
foreach ($tenants as $assignableTenant) {
    $assignableId = (int)($assignableTenant['id'] ?? 0);
    if (empty($assignableTenant['room_id']) && !isset($activeContractsByUserId[$assignableId])) {
        $assignableTenants[] = [
            'id' => $assignableId,
            'name' => (string)($assignableTenant['full_name'] ?? ''),
            'email' => (string)($assignableTenant['email'] ?? ''),
            'phone' => (string)($assignableTenant['phone'] ?? ''),
        ];
    }
}
?>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Quản lý Người thuê</h2>
                    <p class="text-gray-500 mt-2">Tạo hợp đồng ngay khi gán tenant vào phòng để dữ liệu cư trú và giá thuê luôn đồng bộ.</p>
                </div>
                <a href="<?= BASE_URL ?>?page=admin-contracts"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-base">description</span>
                    Xem toàn bộ hợp đồng
                </a>
            </div>

            <?php if (!empty($tenantMessage)): ?>
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($tenantMessage) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($tenantError)): ?>
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($tenantError) ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Tổng tenant</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= (int)$tenantCount ?></p>
                    <p class="text-sm text-gray-500 mt-2">Chỉ tính tài khoản người thuê có `role = 0`.</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Chưa có phòng</p>
                    <p class="text-3xl font-bold text-amber-600 mt-2"><?= (int)$tenantWithoutRoom ?></p>
                    <p class="text-sm text-gray-500 mt-2">Nhóm này có thể gán phòng và tạo hợp đồng mới ngay.</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Hợp đồng active</p>
                    <p class="text-3xl font-bold text-primary mt-2"><?= (int)$activeContractCount ?></p>
                    <p class="text-sm text-gray-500 mt-2">Tenant đang có hợp đồng hiệu lực sẽ bị khóa khỏi form gán mới.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-gradient-to-r from-primary to-secondary p-6 rounded-3xl shadow-lg text-white">
                    <div class="flex flex-col gap-2 mb-5">
                        <h3 class="font-bold text-xl flex items-center gap-2">
                            <span class="material-symbols-outlined">person_add</span>
                            Gán người thuê vào phòng
                        </h3>
                        <p class="text-white/85 text-sm">Một lần submit sẽ đồng thời tạo hợp đồng `active`, cập nhật `users.room_id` và đồng bộ trạng thái phòng theo sức chứa.</p>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>?page=admin-add-tenant" class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?= csrf_field() ?>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Tìm tài khoản chưa gán phòng</label>
                            <div class="relative mb-2">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                                <input type="text"
                                       id="tenant-search"
                                       placeholder="Tìm theo tên, email hoặc số điện thoại..."
                                       autocomplete="off"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl text-gray-900 bg-white outline-none">
                            </div>
                            <select name="user_id" id="tenant-select" required size="6" class="w-full px-4 py-3 rounded-xl text-gray-900 bg-white outline-none"></select>
                            <p id="tenant-search-count" class="text-xs text-white/75 mt-1"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Phòng trống</label>
                            <select name="room_id" required class="w-full px-4 py-3 rounded-xl text-gray-900 outline-none">
                                <option value="">-- Chọn phòng có thể nhận tenant --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <?php $roomId = (int)($room['id'] ?? 0); ?>
                                <option value="<?= $roomId ?>" <?= (int)($assignmentForm['room_id'] ?? 0) === $roomId ? 'selected' : '' ?>>
                                    <?= e($room['name'] ?? 'Phòng') ?>
                                    - <?= e(fallbackText($room['area_name'] ?? '')) ?>
                                    - <?= e(fallbackText($room['floor_name'] ?? '')) ?>
                                    - Còn <?= (int)($room['available_slots'] ?? 0) ?>/<?= (int)($room['max_occupancy'] ?? 0) ?> chỗ
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Ngày vào ở</label>
                            <input type="date"
                                   name="move_in_date"
                                   value="<?= e($assignmentForm['move_in_date'] ?? '') ?>"
                                   required
                                   class="w-full px-4 py-3 rounded-xl text-gray-900 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Giá thuê trong hợp đồng</label>
                            <input type="number"
                                   name="rent_price"
                                   min="0"
                                   step="1000"
                                   value="<?= e($assignmentForm['rent_price'] ?? '') ?>"
                                   required
                                   placeholder="Ví dụ: 1800000"
                                   class="w-full px-4 py-3 rounded-xl text-gray-900 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Tiền cọc</label>
                            <input type="number"
                                   name="deposit_amount"
                                   min="0"
                                   step="1000"
                                   value="<?= e($assignmentForm['deposit_amount'] ?? '') ?>"
required
                                    placeholder="Ví dụ: 2000000"
                                    class="w-full px-4 py-3 rounded-xl text-gray-900 outline-none">
                         </div>

                         <div class="md:col-span-2 flex flex-col gap-3 md:flex-row md:items-center md:justify-between mt-2">
                            <p class="text-sm text-white/80">Tenant đã có hợp đồng active hoặc đang có phòng sẽ không được phép gán mới để tránh trùng dữ liệu cư trú.</p>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-primary rounded-xl font-bold hover:bg-gray-100 transition">
                                <span class="material-symbols-outlined text-base">library_add</span>
                                Tạo hợp đồng và gán phòng
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-5">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Nguyên tắc gán phòng</h3>
                            <p class="text-sm text-gray-500 mt-1">Luồng này chặn các trạng thái sai phổ biến trước khi ghi DB.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">Auto-check</span>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-900">1. Tenant không được có hợp đồng active trùng</p>
                            <p class="text-gray-500 mt-1">Model chặn ký đè để tránh trường hợp một tenant ở 2 phòng cùng lúc.</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-900">2. Phòng chỉ nhận khi còn chỗ</p>
                            <p class="text-gray-500 mt-1">Số lượng hiện tại được đếm trực tiếp từ `users.room_id` để bám sát dữ liệu thật.</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-900">3. Trạng thái phòng tự đồng bộ</p>
                            <p class="text-gray-500 mt-1">Đủ sức chứa thì chuyển `rented`, còn chỗ thì vẫn giữ `available` để tiếp tục gán người.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Danh sách tenant</h3>
                        <p class="text-sm text-gray-500 mt-1">Bảng này hiển thị trạng thái ở hiện tại và liên kết nhanh sang hợp đồng đang hiệu lực.</p>
                    </div>
                    <span class="text-sm text-gray-500">Tổng <?= (int)$tenantCount ?> tenant</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[960px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tenant</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Liên hệ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng hiện tại</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Khu / Tầng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hợp đồng</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tham gia</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($tenants as $tenant): ?>
                                <?php
                                $tenantId = (int)($tenant['id'] ?? 0);
                                $contract = $activeContractsByUserId[$tenantId] ?? null;
                                $hasActiveContract = $contract !== null;
                                $initial = mb_strtoupper(mb_substr((string)($tenant['full_name'] ?? 'T'), 0, 1));
                                ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center font-bold">
                                            <?= e($initial) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?= e($tenant['full_name'] ?? 'Tenant') ?></p>
                                            <p class="text-xs text-gray-500">ID tenant: #<?= $tenantId ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?= e($tenant['email'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= e(fallbackText($tenant['phone'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($tenant['room_name'])): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold">
                                        <span class="material-symbols-outlined text-sm">meeting_room</span>
                                        <?= e($tenant['room_name'] ?? '') ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs rounded-full font-semibold">
                                        <span class="material-symbols-outlined text-sm">bedroom_child</span>
                                        Chưa có phòng
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <p><?= e(fallbackText($tenant['building_name'] ?? '')) ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= e(fallbackText($tenant['floor_name'] ?? '')) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($hasActiveContract): ?>
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary text-xs rounded-full font-semibold">
                                            <span class="material-symbols-outlined text-sm">description</span>
                                            Hợp đồng active
                                        </span>
                                        <p class="text-xs text-gray-500">Ký ngày <?= e($formatDate($contract['contract_date'] ?? '')) ?></p>
                                    </div>
                                    <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-100 text-amber-700 text-xs rounded-full font-semibold">
                                        <span class="material-symbols-outlined text-sm">pending_actions</span>
                                        Chưa có hợp đồng
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= e($formatDate($tenant['created_at'] ?? '', 'Không rõ')) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($hasActiveContract): ?>
                                    <a href="<?= BASE_URL ?>?page=admin-view-contract&id=<?= (int)($contract['id'] ?? 0) ?>"
                                       class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        Xem hợp đồng
                                    </a>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">Dùng form phía trên để tạo mới</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Chưa có tenant nào trong hệ thống.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-primary">home_work</span>
                    <h3 class="font-bold text-lg text-gray-900">Phòng đang mở cho gán mới</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach ($rooms as $room): ?>
                    <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900"><?= e($room['name'] ?? 'Phòng') ?></p>
                                <p class="text-sm text-gray-500 mt-1"><?= e(fallbackText($room['area_name'] ?? '')) ?> • <?= e(fallbackText($room['floor_name'] ?? '')) ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                Còn <?= (int)($room['available_slots'] ?? 0) ?> chỗ
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="p-3 rounded-xl bg-white border border-gray-100">
                                <p class="text-gray-500">Giá niêm yết</p>
                                <p class="font-semibold text-gray-900 mt-1"><?= e($formatMoney($room['price'] ?? 0)) ?></p>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-100">
                                <p class="text-gray-500">Sức chứa</p>
                                <p class="font-semibold text-gray-900 mt-1"><?= (int)($room['occupant_count'] ?? 0) ?>/<?= (int)($room['max_occupancy'] ?? 0) ?> người</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($rooms)): ?>
                    <div class="md:col-span-2 xl:col-span-3 p-6 rounded-2xl border border-dashed border-gray-200 text-center text-gray-500">
                        Không còn phòng `available` nào có thể nhận tenant mới.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<script>
(() => {
    const assignable = <?= json_encode($assignableTenants, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const searchInput = document.getElementById('tenant-search');
    const select = document.getElementById('tenant-select');
    const countEl = document.getElementById('tenant-search-count');
    const preselected = <?= (int)($assignmentForm['user_id'] ?? 0) ?>;

    if (!searchInput || !select) { return; }

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd');

    const render = (query) => {
        const q = normalize(query);
        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Chọn tài khoản để gán --';
        select.appendChild(placeholder);

        const matches = q === ''
            ? assignable
            : assignable.filter((t) => normalize(t.name).includes(q)
                || normalize(t.email).includes(q)
                || normalize(t.phone).includes(q));

        matches.forEach((t) => {
            const option = document.createElement('option');
            option.value = String(t.id);
            option.textContent = t.name + ' — ' + t.email + (t.phone ? ' — ' + t.phone : '');
            if (t.id === preselected) { option.selected = true; }
            select.appendChild(option);
        });

        if (countEl) {
            countEl.textContent = 'Tìm thấy ' + matches.length + '/' + assignable.length + ' tài khoản chưa gán phòng';
        }
    };

    searchInput.addEventListener('input', () => render(searchInput.value));
    render('');
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
