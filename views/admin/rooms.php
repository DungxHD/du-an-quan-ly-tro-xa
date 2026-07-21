<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'rooms';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý danh sách phòng và trạng thái hiển thị';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <h2 class="text-3xl font-bold mb-6">Quản lý Phòng trọ</h2>
        
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="xl:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    <h3 class="font-bold text-lg mb-4">
                        <?= $editRoom ? '✏️ Sửa phòng' : '➕ Thêm phòng mới' ?>
                    </h3>
                    <form method="POST" action="<?= BASE_URL ?>?page=admin-save-room" data-validate class="space-y-3">
                        <?php if ($editRoom): ?>
                        <input type="hidden" name="id" value="<?= $editRoom['id'] ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Tòa nhà *</label>
                            <select name="building_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                                <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($editRoom['building_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Tên phòng *</label>
                            <input type="text" name="name" required 
                                   value="<?= htmlspecialchars($editRoom['name'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Tầng</label>
                                <input type="number" name="floor" value="<?= htmlspecialchars($editRoom['floor'] ?? 1) ?>"
                                       class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Diện tích (m²)</label>
                                <input type="number" step="0.1" name="area" value="<?= htmlspecialchars($editRoom['area'] ?? 20) ?>"
                                       class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Giá thuê (VNĐ) *</label>
                            <input type="number" name="price" required value="<?= htmlspecialchars($editRoom['price'] ?? 3000000) ?>"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Số người tối đa</label>
                            <input type="number" name="max_occupancy" value="<?= htmlspecialchars($editRoom['max_occupancy'] ?? 2) ?>"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">URL ảnh</label>
                            <input type="url" name="thumbnail" value="<?= htmlspecialchars($editRoom['thumbnail'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                                <option value="available" <?= ($editRoom['status'] ?? '') == 'available' ? 'selected' : '' ?>>Còn trống</option>
                                <option value="rented" <?= ($editRoom['status'] ?? '') == 'rented' ? 'selected' : '' ?>>Đã thuê</option>
                                <option value="maintenance" <?= ($editRoom['status'] ?? '') == 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">Mô tả</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-primary"><?= htmlspecialchars($editRoom['description'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90">
                            <?= $editRoom ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        
                        <?php if ($editRoom): ?>
                        <a href="<?= BASE_URL ?>?page=admin-rooms" class="block w-full py-3 text-center text-gray-600 hover:text-primary">Hủy</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- List -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-bold text-lg">Danh sách phòng (<?= count($rooms) ?>)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Phòng</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tòa</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giá</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hành động</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($rooms as $r): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img src="<?= $r['thumbnail'] ?>" class="w-12 h-12 rounded-lg object-cover">
                                            <div>
                                                <p class="font-semibold"><?= htmlspecialchars($r['name']) ?></p>
                                                <p class="text-xs text-gray-500"><?= $r['area'] ?>m² - Tầng <?= $r['floor'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars($r['building_name']) ?></td>
                                    <td class="px-4 py-3 text-primary font-semibold"><?= number_format($r['price']/1000000, 1) ?>M</td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        $statusMap = ['available' => ['Còn trống', 'green'], 'rented' => ['Đã thuê', 'gray'], 'maintenance' => ['Bảo trì', 'orange']];
                                        $s = $statusMap[$r['status']] ?? ['Không xác định', 'gray'];
                                        ?>
                                        <span class="px-2 py-1 bg-<?= $s[1] ?>-100 text-<?= $s[1] ?>-700 text-xs rounded-full font-semibold">
                                            <?= $s[0] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="<?= BASE_URL ?>?page=admin-rooms&edit=<?= $r['id'] ?>" 
                                               class="text-blue-500 hover:text-blue-700 text-sm">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </a>
                                            <a href="<?= BASE_URL ?>?page=admin-delete-room&id=<?= $r['id'] ?>" 
                                               data-confirm="Xóa phòng này?"
                                               class="text-red-500 hover:text-red-700 text-sm">
                                                <span class="material-symbols-outlined text-sm">delete</span>
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
