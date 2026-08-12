<?php
$v = file_get_contents('views/admin/rooms/rooms.php');

$old_modal = <<<'EOT'
    <!-- Modal Thêm Tầng -->
    <div id="modal-add-floor" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold mb-4">Thêm tầng mới</h3>
            <form id="form-add-floor" method="POST" action="<?= BASE_URL ?>?page=admin-add-floor">
                <input type="hidden" name="area_id" value="<?= e($selectedAreaId) ?>">
                <input type="hidden" name="floor_id" value="0">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Tên tầng *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="VD: Tầng 3">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Giá thuê mặc định (VNĐ) *</label>
                    <input type="number" name="default_price" required min="0" step="100000" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="VD: 3000000">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Diện tích mặc định (m²)</label>
                    <input type="number" name="default_area" min="0" step="0.1" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="VD: 20">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Mô tả</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Mô tả ngắn về tầng..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" id="btn-cancel-add-floor" class="flex-1 rounded-xl border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">
                        Hủy
                    </button>
                    <button type="submit" class="flex-1 rounded-xl bg-primary px-4 py-2 font-semibold text-white hover:bg-opacity-90">
                        Thêm tầng
                    </button>
                </div>
            </form>
        </div>
    </div>
EOT;

$new_modal = <<<'EOT'
    <!-- Modal Thêm Tầng -->
    <div id="modal-add-floor" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold mb-4">Thêm tầng mới vào <?= e($areas[array_search($selectedAreaId, array_column($areas, 'id'))]['name'] ?? 'khu') ?></h3>
            <form id="form-add-floor" method="POST" action="<?= BASE_URL ?>?page=admin-add-floor">
                <input type="hidden" name="area_id" value="<?= e($selectedAreaId) ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Số phòng nháp cần tạo</label>
                    <input type="number" name="room_count" min="0" max="50" value="0" class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="0 = không tạo phòng">
                    <p class="text-xs text-gray-500 mt-1">Hệ thống sẽ tự đặt tên tầng (VD: Tầng 3, Tầng 4...). Nhập 0 nếu chỉ muốn tạo tầng trống.</p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" id="btn-cancel-add-floor" class="flex-1 rounded-xl border border-gray-300 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">
                        Hủy
                    </button>
                    <button type="submit" class="flex-1 rounded-xl bg-primary px-4 py-2 font-semibold text-white hover:bg-opacity-90">
                        Thêm tầng
                    </button>
                </div>
            </form>
        </div>
    </div>
EOT;

$v = str_replace($old_modal, $new_modal, $v);
file_put_contents('views/admin/rooms/rooms.php', $v);
echo "✓ Fixed modal form: chỉ còn trường room_count (phù hợp schema)\n";
