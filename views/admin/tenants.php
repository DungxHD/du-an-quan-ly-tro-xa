<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'tenants';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý cư dân và phân bổ phòng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <h2 class="text-3xl font-bold mb-6">Quản lý Người thuê</h2>
        
        <!-- Assign Room Form -->
        <div class="bg-gradient-to-r from-primary to-secondary p-6 rounded-2xl shadow-lg mb-6 text-white">
            <h3 class="font-bold text-lg mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">person_add</span>
                Gán người thuê vào phòng
            </h3>
            <form method="POST" action="<?= BASE_URL ?>?page=admin-add-tenant" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="user_id" required class="px-3 py-2 rounded-lg text-gray-900 outline-none">
                    <option value="">-- Chọn người thuê --</option>
                    <?php foreach ($tenants as $t): if($t['role'] == 0): ?>
                    <option value="<?= $t['id'] ?>">
                        <?= htmlspecialchars($t['full_name']) ?> (<?= htmlspecialchars($t['email']) ?>)
                        <?= $t['room_name'] ? ' - Đang ở: ' . htmlspecialchars($t['room_name']) : '' ?>
                    </option>
                    <?php endif; endforeach; ?>
                </select>
                <select name="room_id" required class="px-3 py-2 rounded-lg text-gray-900 outline-none">
                    <option value="">-- Chọn phòng trống --</option>
                    <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> - <?= htmlspecialchars($r['building_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-6 py-2 bg-white text-primary rounded-lg font-bold hover:bg-gray-100 transition">
                    Gán phòng
                </button>
            </form>
        </div>
        
        <!-- List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-bold text-lg">Danh sách người thuê</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Người thuê</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email/SĐT</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tòa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ngày tham gia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tenants as $t): if($t['role'] == 0): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold">
                                        <?= mb_substr($t['full_name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?= htmlspecialchars($t['full_name']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm"><?= htmlspecialchars($t['email']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($t['phone'] ?? '') ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($t['room_name']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold">
                                    <?= htmlspecialchars($t['room_name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">Chưa có phòng</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= e(fallbackText($t['building_name'] ?? '')) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
                        </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
