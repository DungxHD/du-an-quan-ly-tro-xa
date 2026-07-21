<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'buildings';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý khu, dãy và tòa nhà';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Quản lý Khu / Dãy / Tòa nhà</h2>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20">
                    <h3 class="font-bold text-lg mb-4">
                        <?= $editBuilding ? '✏️ Sửa khu/tòa' : '➕ Thêm khu/tòa mới' ?>
                    </h3>
                    <form method="POST" action="<?= BASE_URL ?>?page=admin-save-building" data-validate class="space-y-4">
                        <?php if ($editBuilding): ?>
                        <input type="hidden" name="id" value="<?= $editBuilding['id'] ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Tên khu/tòa *</label>
                            <input type="text" name="name" required 
                                   value="<?= htmlspecialchars($editBuilding['name'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Loại</label>
                            <select name="type" class="w-full px-4 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                                <option value="zone" <?= ($editBuilding['type'] ?? '') == 'zone' ? 'selected' : '' ?>>Khu vực</option>
                                <option value="block" <?= ($editBuilding['type'] ?? '') == 'block' ? 'selected' : '' ?>>Dãy nhà</option>
                                <option value="building" <?= ($editBuilding['type'] ?? '') == 'building' ? 'selected' : '' ?>>Tòa nhà</option>
                                <option value="floor" <?= ($editBuilding['type'] ?? '') == 'floor' ? 'selected' : '' ?>>Tầng</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Địa chỉ</label>
                            <input type="text" name="address" 
                                   value="<?= htmlspecialchars($editBuilding['address'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Mô tả</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"><?= htmlspecialchars($editBuilding['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Thứ tự hiển thị</label>
                            <input type="number" name="sort_order" 
                                   value="<?= htmlspecialchars($editBuilding['sort_order'] ?? 0) ?>"
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition">
                            <?= $editBuilding ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        
                        <?php if ($editBuilding): ?>
                        <a href="<?= BASE_URL ?>?page=admin-buildings" class="block w-full py-3 text-center text-gray-600 hover:text-primary">
                            Hủy
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-bold text-lg">Danh sách (<?= count($buildings) ?>)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tên</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Loại</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Số phòng</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trống</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($buildings as $b): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold"><?= htmlspecialchars($b['name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= e(fallbackText($b['address'] ?? '')) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $typeMap = ['zone' => 'Khu', 'block' => 'Dãy', 'building' => 'Tòa', 'floor' => 'Tầng'];
                                        $typeColor = ['zone' => 'blue', 'block' => 'green', 'building' => 'purple', 'floor' => 'orange'];
                                        $color = $typeColor[$b['type']] ?? 'gray';
                                        ?>
                                        <span class="px-2 py-1 bg-<?= $color ?>-100 text-<?= $color ?>-700 text-xs rounded-full font-semibold">
                                            <?= $typeMap[$b['type']] ?? $b['type'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold"><?= $b['room_count'] ?></td>
                                    <td class="px-6 py-4 text-green-600 font-semibold"><?= $b['available_count'] ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <a href="<?= BASE_URL ?>?page=admin-buildings&edit=<?= $b['id'] ?>" 
                                               class="text-blue-500 hover:text-blue-700 flex items-center gap-1 text-sm">
                                                <span class="material-symbols-outlined text-sm">edit</span> Sửa
                                            </a>
                                            <a href="<?= BASE_URL ?>?page=admin-delete-building&id=<?= $b['id'] ?>" 
                                               data-confirm="Xóa khu/tòa này? Tất cả phòng trong đó cũng sẽ bị xóa!"
                                               class="text-red-500 hover:text-red-700 flex items-center gap-1 text-sm">
                                                <span class="material-symbols-outlined text-sm">delete</span> Xóa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
