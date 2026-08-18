<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'dashboard';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Thông tin phòng của bạn';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
$supportPhone = RoomModel::getSetting('contact_phone', 'Chưa có dữ liệu');
$supportEmail = RoomModel::getSetting('contact_email', 'Chưa có dữ liệu');
$roomExtra = $roomExtra ?? null;
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<?php if ($room): ?>
<?php
$occupantList = (array)($roomExtra['occupants_list'] ?? []);
$occupants = count($occupantList);
$maxOcc = max(1, (int)($roomExtra['max'] ?? ($room['max_occupancy'] ?? 1)));
$freeSlots = max(0, $maxOcc - $occupants);
$canAdd = $occupants < $maxOcc;
$amenities = (array)($roomExtra['amenities'] ?? []);
?>
<!-- [DEV-QWEN-A][NHOM-2][ROOM-INFO-V2] Chi tiết mở sẵn; ô số người bấm để xem danh sách cư dân -->
<div class="tenant-room-hero bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
    <div class="p-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-primary text-4xl">door_open</span>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Thông tin phòng: <?= e($room['name']) ?></h2>
                <p class="text-sm text-gray-500 mt-1"><?= e((string)($roomExtra['area_name'] ?? '')) ?> · <?= e((string)($roomExtra['floor_name'] ?? '')) ?> · <?= number_format((float)$room['price']) ?> ₫/tháng</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($canAdd): ?>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 whitespace-nowrap">Còn <?= $freeSlots ?> chỗ trống</span>
            <?php else: ?>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-600 whitespace-nowrap">Đã đủ <?= $maxOcc ?> người</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-t border-gray-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <img src="<?= e((string)($room['thumbnail'] ?? '')) ?>" class="w-full rounded-xl aspect-video object-cover bg-gray-100" alt="<?= e($room['name']) ?>">
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">home</span> <?= e($room['name']) ?></div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">square_foot</span> <?= (float)$room['area'] ?> m²</div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">payments</span> <?= number_format((float)$room['price']) ?> ₫/tháng</div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">layers</span> <?= e((string)($roomExtra['floor_name'] ?? '')) ?></div>
                <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"><span class="material-symbols-outlined text-primary">apartment</span> <?= e((string)($roomExtra['area_name'] ?? '')) ?></div>
                <button type="button" onclick="toggleOccupantList()" title="Bấm để xem ai đang ở" class="flex items-center gap-2 bg-primary/10 rounded-xl px-3 py-2 hover:bg-primary/20 transition text-left cursor-pointer">
                    <span class="material-symbols-outlined text-primary">group</span>
                    <span class="font-semibold text-primary"><?= $occupants ?>/<?= $maxOcc ?> người</span>
                    <span id="occupantChevron" class="material-symbols-outlined text-primary text-base ml-auto">expand_more</span>
                </button>
            </div>
        </div>

        <div id="occupantList" class="hidden mt-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
            <h5 class="text-xs font-bold text-gray-500 uppercase mb-3">Cư dân đang ở phòng này</h5>
            <?php if ($occupantList): ?>
            <div class="space-y-2">
                <?php foreach ($occupantList as $occ): ?>
                <div class="flex items-center gap-3 bg-white rounded-xl px-3 py-2 border border-gray-100">
                    <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm"><?= e(mb_strtoupper(mb_substr(trim((string)($occ['full_name'] ?? '?')), 0, 1, 'UTF-8'))) ?></span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= e((string)($occ['full_name'] ?? '')) ?></p>
                        <p class="text-xs text-gray-500"><?= e((string)($occ['email'] ?? '')) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa có cư dân nào được gán vào phòng.</p>
            <?php endif; ?>
        </div>

        <div class="mt-6">
            <h4 class="font-bold text-sm text-gray-700 mb-2">Tiện ích trong phòng</h4>
            <?php if ($amenities): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($amenities as $am): ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary"><?= e($am) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa cập nhật tiện ích.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5">
            <h4 class="font-bold text-sm text-gray-700 mb-2">Dịch vụ phòng đã đăng ký</h4>
            <?php if (!empty($roomServices)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($roomServices as $s): ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-sm"><?= e((string)($s['icon'] ?? 'settings')) ?></span>
                    <?= e((string)($s['name'] ?? '')) ?><?= ((int)($s['quantity'] ?? 1) > 1) ? ' ×' . (int)$s['quantity'] : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Chưa đăng ký dịch vụ phòng nào.</p>
            <?php endif; ?>
        </div>

        <div class="mt-6 p-4 rounded-xl <?= $canAdd ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' ?>">
            <?php if ($canAdd): ?>
            <p class="text-sm text-green-800 mb-3">Phòng còn <?= $freeSlots ?> chỗ trống (<?= $occupants ?>/<?= $maxOcc ?> người). Bạn có thể mời người ở ghép để chia sẻ chi phí.</p>
            <a href="<?= BASE_URL ?>?page=tenant-roommate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white text-sm font-bold hover:opacity-90 transition">
                <span class="material-symbols-outlined text-base">group_add</span> Tìm người ở ghép
            </a>
            <?php else: ?>
            <p class="text-sm text-gray-600">Phòng đã đạt giới hạn <?= $maxOcc ?> người ở (<?= $occupants ?>/<?= $maxOcc ?>) — không thể thêm người ở ghép.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Đánh giá phòng -->
<?php
$ownerComment = $commentBundle['owner_comment'] ?? null;
$publicComments = $commentBundle['public_comments'] ?? [];
$canReview = empty($ownerComment) && !empty($commentEligibility['allowed']);
$blockedReason = empty($ownerComment) && empty($commentEligibility['allowed'])
    ? trim((string)($commentEligibility['message'] ?? ''))
    : '';
?>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
    <div class="p-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-primary text-4xl">star</span>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Đánh giá phòng</h2>
                <p class="text-sm text-gray-500 mt-1">Chấm sao 1-5 và chia sẻ trải nghiệm về phòng <?= e($room['name']) ?>. Mỗi người chỉ đánh giá một lần.</p>
            </div>
        </div>
        <a href="<?= BASE_URL ?>?page=detail&id=<?= (int)$room['id'] ?>" class="px-5 py-3 rounded-xl border border-gray-200 bg-white font-semibold text-gray-700 hover:bg-gray-50 transition">
            Xem phòng & các đánh giá khác
        </a>
    </div>

    <div class="border-t border-gray-100 p-6">
        <?php if (!empty($commentMessage)): ?>
        <div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= e($commentMessage) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($commentWarning)): ?>
        <div class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined">warning</span>
            <?= e($commentWarning) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($commentError)): ?>
        <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= e($commentError) ?>
        </div>
        <?php endif; ?>

        <?php if ($ownerComment): ?>
        <article class="rounded-2xl border border-primary/15 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900"><?= e($ownerComment['full_name'] ?? 'Bạn') ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= e($ownerComment['created_at_label'] ?? '') ?></p>
                </div>
                <div class="flex text-yellow-400">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' <?= $i <= (int)($ownerComment['rating'] ?? 0) ? 1 : 0 ?>;">star</span>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if (!empty($ownerComment['visibility_badges'])): ?>
            <div class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($ownerComment['visibility_badges'] as $badge): ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= e($badge['class'] ?? 'bg-slate-100 text-slate-700') ?>">
                    <?= e($badge['label'] ?? '') ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($ownerComment['is_edited'])): ?>
            <p class="mt-3 text-xs font-medium text-gray-500">
                Đã sửa lúc <?= e($ownerComment['edited_at_label'] ?? '') ?>
            </p>
            <?php endif; ?>

            <p class="mt-4 text-gray-700 leading-relaxed">
                <?= $ownerComment['content'] !== null && $ownerComment['content'] !== ''
                    ? nl2br(e($ownerComment['content']))
                    : 'Bạn đã chọn chỉ chấm sao cho phòng này.' ?>
            </p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <?php if (!empty($ownerComment['can_edit'])): ?>
                <a href="<?= BASE_URL ?>?page=tenant-edit-comment&id=<?= (int)($ownerComment['id'] ?? 0) ?>&return_page=tenant" class="px-4 py-2 rounded-xl border border-primary text-primary font-semibold hover:bg-primary/5 transition">
                    Sửa
                </a>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>?page=tenant-delete-comment" onsubmit="return confirm('Bạn chắc chắn muốn xóa đánh giá này? Sau khi xóa không thể khôi phục.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="comment_id" value="<?= (int)($ownerComment['id'] ?? 0) ?>">
                    <input type="hidden" name="room_id" value="<?= (int)($ownerComment['room_id'] ?? 0) ?>">
                    <input type="hidden" name="return_page" value="tenant">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 transition">
                        Xóa
                    </button>
                </form>
            </div>

            <?php if (!empty($ownerComment['can_edit'])): ?>
            <p class="mt-3 text-xs text-gray-500">
                Bạn còn quyền sửa đến <?= e($ownerComment['edit_deadline'] ?? '') ?>. Sau thời gian này chỉ có thể xóa đánh giá.
            </p>
            <?php else: ?>
            <p class="mt-3 text-xs text-gray-500">
                Đã quá 24h kể từ khi gửi, bạn không thể sửa nữa nhưng vẫn có thể xóa đánh giá.
            </p>
            <?php endif; ?>
        </article>
        <?php elseif ($canReview): ?>
        <?php
        // Kiểm tra nếu đang viết lại sau khi bị cảnh báo
        $pending = $_SESSION['pending_comment'] ?? null;
        $isRewrite = $pending && ($pending['action'] ?? 'add') === 'add';
        $prefillContent = $isRewrite ? ($pending['content'] ?? '') : '';
        $prefillRating = $isRewrite ? ($pending['rating'] ?? 0) : 0;
        $prefillReturnPage = $isRewrite ? ($pending['return_page'] ?? 'tenant') : 'tenant';
        $rewriteWarning = $isRewrite ? ($_SESSION['moderation_warning']['reason'] ?? '') : '';
        ?>
        <form method="POST" action="<?= BASE_URL ?>?page=tenant-add-comment" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
            <input type="hidden" name="return_page" value="<?= e($prefillReturnPage) ?>">
            <input type="hidden" name="rating" value="<?= (int)$prefillRating ?>" data-rating-input>

            <?php if ($rewriteWarning): ?>
            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl flex items-start gap-2">
                <span class="material-symbols-outlined mt-0.5">warning</span>
                <div>
                    <p class="font-semibold">Vui lòng viết lại nội dung:</p>
                    <p class="text-sm mt-1"><?= e($rewriteWarning) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-semibold mb-2">Số sao</label>
                <div class="flex items-center gap-1" data-rating-widget>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button
                        type="button"
                        class="rating-star text-yellow-400 transition hover:scale-110"
                        data-rating-value="<?= $i ?>"
                        aria-label="Chọn <?= $i ?> sao">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' <?= $i <= (int)$prefillRating ? 1 : 0 ?>;">star</span>
                    </button>
                    <?php endfor; ?>
                </div>
                <p class="mt-2 text-xs text-gray-500" data-rating-hint><?= (int)$prefillRating > 0 ? 'Đang chọn ' . (int)$prefillRating . '/5 sao' : 'Chưa chọn sao' ?></p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Nội dung</label>
                <textarea
                    name="content"
                    id="commentContent"
                    rows="5"
                    maxlength="150"
                    placeholder="Chia sẻ trải nghiệm của bạn (có thể bao quát khu, tầng, chỗ trọ)..."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none resize-none"><?= e($prefillContent) ?></textarea>
                <p class="mt-2 text-xs text-gray-500 flex justify-between">
                    <span>Bạn có thể chỉ chấm sao mà không cần nhập nội dung.</span>
                    <span id="charCount"><?= mb_strlen((string)$prefillContent, 'UTF-8') ?>/150</span>
                </p>
            </div>

            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-opacity-90 transition">
                Gửi đánh giá
            </button>
        </form>
        <?php else: ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700">
            <?= e($blockedReason !== '' ? $blockedReason : 'Hiện bạn chưa đủ điều kiện để đánh giá phòng này.') ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleOccupantList() {
        var el = document.getElementById('occupantList');
        var ch = document.getElementById('occupantChevron');
        if (!el) return;
        var isHidden = el.classList.contains('hidden');
        el.classList.toggle('hidden');
        if (ch) { ch.textContent = isHidden ? 'expand_less' : 'expand_more'; }
    }
    // Star rating widget
    document.querySelectorAll('[data-rating-widget]').forEach((widget) => {
        const form = widget.closest('form');
        const input = form ? form.querySelector('[data-rating-input]') : null;
        const hint = widget.parentElement.querySelector('[data-rating-hint]');
        const buttons = Array.from(widget.querySelectorAll('[data-rating-value]'));

        const paint = (value) => {
            const numValue = Number(value);
            buttons.forEach((button) => {
                const filled = Number(button.dataset.ratingValue) <= numValue;
                const icon = button.querySelector('.material-symbols-outlined');
                if (icon) {
                    icon.style.fontVariationSettings = filled ? "'FILL' 1" : "'FILL' 0";
                }
            });
            if (hint) {
                hint.textContent = numValue > 0 ? 'Đang chọn ' + numValue + '/5 sao' : 'Chưa chọn sao';
            }
        };

        const current = input ? input.value : 0;
        paint(current);
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                if (input) { input.value = button.dataset.ratingValue; }
                paint(button.dataset.ratingValue);
            });
        });
    });
    // Character counter
    const commentTextarea = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');
    if (commentTextarea && charCount) {
        commentTextarea.addEventListener('input', () => {
            const len = commentTextarea.value.length;
            charCount.textContent = len + '/150';
            charCount.classList.toggle('text-red-500', len > 150);
            charCount.classList.toggle('text-gray-500', len <= 150);
        });
    }
});
</script>
<?php else: ?>
<!-- No room assigned -->
<div class="tenant-empty bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">home_work</span>
    <h2 class="text-2xl font-bold mb-2">Bạn chưa được gán vào phòng nào</h2>
    <p class="text-gray-500 mb-6">Vui lòng liên hệ chủ trọ để được gán vào phòng của bạn.</p>
    <div class="bg-blue-50 p-4 rounded-xl text-sm text-gray-600 max-w-md mx-auto">
        <p class="font-semibold mb-2">Liên hệ hỗ trợ:</p>
        <p>Hotline: <?= e($supportPhone) ?></p>
        <p>Email: <?= e($supportEmail) ?></p>
    </div>
</div>
<?php endif; ?>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>