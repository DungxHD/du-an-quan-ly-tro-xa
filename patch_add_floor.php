<?php
// === BACKUP ===
$backup_dir = "backups/" . date('Ymd_His');
mkdir($backup_dir, 0755, true);
copy('views/admin/rooms/rooms.php', $backup_dir . '/rooms.php');
copy('controllers/AdminController.php', $backup_dir . '/AdminController.php');
echo "✓ Backed up to: $backup_dir\n\n";

// === 1. PATCH rooms.php: Thêm nút "Thêm tầng" ===
$v = file_get_contents('views/admin/rooms/rooms.php');

// Tìm vị trí nút "Thêm phòng"
$add_room_pos = strpos($v, 'id="btn-add-room"');
if ($add_room_pos === false) {
    die("ERROR: Không tìm thấy nút Thêm phòng trong rooms.php\n");
}

// Tìm thẻ div cha chứa nút
$div_start = strrpos(substr($v, 0, $add_room_pos), '<div class="flex items-end">');
if ($div_start === false) {
    die("ERROR: Không tìm thấy div cha của nút Thêm phòng\n");
}

// Thêm điều kiện $hasArea (đã chọn khu cụ thể)
$has_area_check = '<?php $hasArea = ($selectedAreaId > 0); ?>';
$old_filter_start = '        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">';
$new_filter_start = $old_filter_start . "\n" . str_repeat(' ', 12) . $has_area_check;

if (strpos($v, $has_area_check) === false) {
    $v = str_replace($old_filter_start, $new_filter_start, $v);
    echo "✓ Added \$hasArea variable check\n";
}

// Thêm nút "Thêm tầng" trước nút "Thêm phòng"
$old_btn_block = <<<'EOT'
            <div class="flex items-end">
                <?php if ($hasFloor): ?>
                    <button type="button" id="btn-add-room" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                        <span class="material-symbols-outlined text-base">add_home</span> Thêm phòng vào <?= e($selectedFloor['name'] ?? 'tầng') ?>
                    </button>
                <?php else: ?>
                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một tầng để hiện nút "Thêm phòng".</p>
                <?php endif; ?>
            </div>
EOT;

$new_btn_block = <<<'EOT'
            <div class="flex items-end">
                <?php if ($hasFloor): ?>
                    <button type="button" id="btn-add-room" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                        <span class="material-symbols-outlined text-base">add_home</span> Thêm phòng vào <?= e($selectedFloor['name'] ?? 'tầng') ?>
                    </button>
                <?php elseif ($hasArea): ?>
                    <button type="button" id="btn-add-floor" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2 font-semibold text-white transition hover:bg-opacity-90">
                        <span class="material-symbols-outlined text-base">add_business</span> Thêm tầng vào <?= e($areas[array_search($selectedAreaId, array_column($areas, 'id'))]['name'] ?? 'khu') ?>
                    </button>
                <?php else: ?>
                    <p class="w-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs text-gray-500">Chọn một khu để hiện nút "Thêm tầng".</p>
                <?php endif; ?>
            </div>
EOT;

$v = str_replace($old_btn_block, $new_btn_block, $v);
echo "✓ Added 'Thêm tầng' button with condition: \$hasArea && !\$hasFloor\n";

// Thêm modal cho nút Thêm tầng (trước thẻ đóng </body>)
$add_floor_modal = <<<'EOT'

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnAddFloor = document.getElementById('btn-add-floor');
            const modalAddFloor = document.getElementById('modal-add-floor');
            const btnCancelAddFloor = document.getElementById('btn-cancel-add-floor');
            
            if (btnAddFloor) {
                btnAddFloor.addEventListener('click', function() {
                    modalAddFloor.classList.remove('hidden');
                    modalAddFloor.classList.add('flex');
                });
            }
            
            if (btnCancelAddFloor) {
                btnCancelAddFloor.addEventListener('click', function() {
                    modalAddFloor.classList.add('hidden');
                    modalAddFloor.classList.remove('flex');
                });
            }
            
            if (modalAddFloor) {
                modalAddFloor.addEventListener('click', function(e) {
                    if (e.target === modalAddFloor) {
                        modalAddFloor.classList.add('hidden');
                        modalAddFloor.classList.remove('flex');
                    }
                });
            }
        });
    </script>
EOT;

$body_close = '</body>';
$v = str_replace($body_close, $add_floor_modal . "\n" . $body_close, $v);
echo "✓ Added modal for 'Thêm tầng' button\n";

file_put_contents('views/admin/rooms/rooms.php', $v);
echo "✓ Saved rooms.php\n\n";

// === 2. PATCH AdminController.php: Sửa redirect của addFloorQuick() ===
$ac = file_get_contents('controllers/AdminController.php');

// Tìm method addFloorQuick
$start = strpos($ac, 'function addFloorQuick(');
if ($start === false) {
    die("ERROR: Không tìm thấy method addFloorQuick()\n");
}

// Tìm redirect cũ
$old_redirect = "redirectTo('admin-areas', ['area' => \$areaId]);";
$new_redirect = "redirectTo('admin-rooms', ['area_id' => \$areaId, 'floor_id' => 0]);";

if (strpos($ac, $old_redirect) === false) {
    die("ERROR: Không tìm thấy redirect cũ trong addFloorQuick()\n");
}

$ac = str_replace($old_redirect, $new_redirect, $ac);
echo "✓ Updated addFloorQuick() redirect: admin-areas → admin-rooms with area_id & floor_id=0\n";

// Sửa flash message key từ admin_area_message sang admin_room_message
$old_flash = "setFlash('admin_area_message',";
$new_flash = "setFlash('admin_room_message',";
$ac = str_replace($old_flash, $new_flash, $ac);
echo "✓ Updated flash message key: admin_area_message → admin_room_message\n";

file_put_contents('controllers/AdminController.php', $ac);
echo "✓ Saved AdminController.php\n\n";

echo "✅ Patch completed successfully!\n";
