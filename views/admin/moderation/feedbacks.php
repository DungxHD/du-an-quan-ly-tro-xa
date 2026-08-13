<?php
/**
 * [DEV-QWEN-A][NHOM-2][2026-08-14]
 * Quản lý Phản ánh từ người thuê.
 */
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'feedbacks';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý phản ánh, khiếu nại từ người thuê';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="flex flex-col gap-6">
    <div>
        <h2 class="text-3xl font-bold">Phản ánh từ người thuê</h2>
        <p class="text-gray-500 mt-2">Danh sách các phản ánh, khiếu nại, đề xuất từ người thuê gửi trực tiếp cho chủ trọ.</p>
    </div>

    <?php if (!empty($feedbackMessage)): ?>
        <div class="alert-dismissible rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 flex items-center justify-between">
            <span><?= e($feedbackMessage) ?></span>
            <button type="button" data-dismiss-alert class="ml-4 text-green-800 hover:text-green-950 font-bold text-lg" aria-label="Đóng thông báo">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (!empty($feedbackError)): ?>
        <div class="alert-dismissible rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
            <p><?= e($feedbackError) ?></p>
            <button type="button" data-dismiss-alert class="mt-3 inline-flex items-center gap-1 rounded-lg bg-rose-200 px-3 py-1.5 text-xs font-bold text-rose-900 hover:bg-rose-300">
                <span class="material-symbols-outlined text-base">arrow_back</span> Quay lại
            </button>
        </div>
    <?php endif; ?>

    <!-- Bộ lọc -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="hidden" name="page" value="admin-feedbacks">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                    <option value="">Tất cả</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                    <option value="resolved" <?= $filters['status'] === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                    <option value="dismissed" <?= $filters['status'] === 'dismissed' ? 'selected' : '' ?>>Đã bác bỏ</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold mb-1">Tìm kiếm</label>
                <input type="text" name="keyword" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Tên người thuê, email, tiêu đề, nội dung, phòng..." class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition"><span class="material-symbols-outlined text-sm">search</span> Lọc</button>
                <a href="<?= BASE_URL ?>?page=admin-feedbacks" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition">Xóa lọc</a>
            </div>
        </form>
    </div>

    <!-- Bảng danh sách -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($feedbacks)): ?>
            <div class="px-6 py-10 text-center text-gray-500">Chưa có phản ánh nào.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Người thuê</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Phòng</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Tiêu đề</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Nội dung</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Trạng thái</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Thời gian</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3"><?= (int)$fb['id'] ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= e($fb['tenant_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($fb['tenant_email']) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($fb['room_name']): ?>
                                        <span class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded"><?= e($fb['room_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">— Không gắn phòng —</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate"><?= e($fb['subject']) ?></td>
                                <td class="px-4 py-3 text-gray-600 max-w-md truncate"><?= e($fb['content']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                        <?= $fb['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($fb['status'] === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') ?>">
                                        <?= e($fb['status_label']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= e($fb['created_at_label']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= BASE_URL ?>?page=admin-feedbacks&edit=<?= (int)$fb['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition">
                                            <span class="material-symbols-outlined text-sm">edit</span> Xử lý
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form xử lý phản ánh (inline edit) -->
    <?php if (isset($editFeedback) && $editFeedback): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="this.remove()">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">Xử lý phản ánh #<?= (int)$editFeedback['id'] ?></h3>
                    <a href="<?= BASE_URL ?>?page=admin-feedbacks" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</a>
                </div>

                <form method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$editFeedback['id'] ?>">
                    <input type="hidden" name="return_status" value="<?= e($filters['status'] ?? '') ?>">
                    <input type="hidden" name="return_keyword" value="<?= e($filters['keyword'] ?? '') ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-1">Người thuê</label>
                            <input type="text" value="<?= e($editFeedback['tenant_name']) ?> (<?= e($editFeedback['tenant_email']) ?>)" class="w-full px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phòng</label>
                            <input type="text" value="<?= $editFeedback['room_name'] ? e($editFeedback['room_name']) : '— Không gắn phòng —' ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-1">Tiêu đề</label>
                            <input type="text" value="<?= e($editFeedback['subject']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-1">Nội dung phản ánh</label>
                            <textarea class="w-full px-4 py-2 border border-gray-200 rounded-xl bg-gray-50 h-32" readonly><?= e($editFeedback['content']) ?></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Ghi chú của admin</label>
                        <textarea name="admin_note" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none" placeholder="Nhập ghi chú, phản hồi, hướng dẫn xử lý..."><?= e($editFeedback['admin_note']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
                            <option value="pending" <?= $editFeedback['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                            <option value="resolved" <?= $editFeedback['status'] === 'resolved' ? 'selected' : '' ?>>Đã xử lý</option>
                            <option value="dismissed" <?= $editFeedback['status'] === 'dismissed' ? 'selected' : '' ?>>Đã bác bỏ</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
                        <a href="<?= BASE_URL ?>?page=admin-feedbacks" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition">Hủy</a>
                        <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-primary text-white font-semibold hover:bg-opacity-90 transition">
                            <span class="material-symbols-outlined text-sm">save</span> Lưu thay đổi
                        </button>
                    </div>
                </form>

                <!-- Nút xóa nhanh -->
                <form method="POST" action="<?= BASE_URL ?>?page=admin-save-feedback" class="inline mt-4" onsubmit="return confirm('Xóa vĩnh viễn phản ánh này?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$editFeedback['id'] ?>">
                    <input type="hidden" name="return_status" value="<?= e($filters['status'] ?? '') ?>">
                    <input type="hidden" name="return_keyword" value="<?= e($filters['keyword'] ?? '') ?>">
                    <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-rose-50 text-rose-700 font-semibold hover:bg-rose-100 transition">
                        <span class="material-symbols-outlined text-sm">delete</span> Xóa phản ánh này
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>