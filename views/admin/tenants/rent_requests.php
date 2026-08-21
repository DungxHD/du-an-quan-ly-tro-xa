<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'admin';
$panelActive = 'rent-requests';
$panelTitle = $siteName . ' Admin';
$panelSubtitle = 'Quản lý yêu cầu thuê phòng và yêu cầu ở ghép từ người dùng';
$panelTopLink = ['label' => 'Xem website', 'url' => BASE_URL . '?page=home'];
require BASE_PATH . 'views/layouts/panel_header.php';
?>
<div class="space-y-6">
    <div>
        <h2 class="text-3xl font-bold">Yêu cầu thuê & ở ghép</h2>
        <p class="text-gray-500 mt-2">Quản lý và xử lý các yêu cầu thuê phòng và yêu cầu ở ghép từ người dùng.</p>
    </div>

    <?php if (!empty($message)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($roommateMessage)): ?>
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        <?= e($roommateMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($roommateError)): ?>
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center gap-2">
        <span class="material-symbols-outlined">error</span>
        <?= e($roommateError) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <!-- ================= CỘT 1: YÊU CẦU THUÊ PHÒNG ================= -->
        <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-900">Yêu cầu thuê phòng</h3>
                <p class="text-sm text-gray-500 mt-1">Người dùng muốn thuê một phòng cụ thể.</p>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 space-y-4">
                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4" data-rent-ajax-form data-filter-group="rent">
                    <input type="hidden" name="page" value="admin-rent-requests">
                    <input type="hidden" name="roommate_filter" value="<?= e($roommateFilter) ?>">
                    <input type="hidden" name="roommate_keyword" value="<?= e($roommateKeyword) ?>">

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Bộ lọc trạng thái</p>
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Bộ lọc trạng thái yêu cầu thuê">
                            <button type="submit" name="rent_filter" value="all"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? 'all') === 'all' ? 'bg-primary text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                                data-active-class="bg-primary text-white shadow-md"
                                data-inactive-class="bg-gray-100 text-gray-700 hover:bg-gray-200"
                                aria-pressed="<?= ($rentFilter ?? 'all') === 'all' ? 'true' : 'false' ?>">
                                Tất cả
                            </button>
                            <button type="submit" name="rent_filter" value="pending"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 relative
                                    <?= ($rentFilter ?? '') === 'pending' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
                                data-active-class="bg-amber-600 text-white shadow-md"
                                data-inactive-class="bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200"
                                aria-pressed="<?= ($rentFilter ?? '') === 'pending' ? 'true' : 'false' ?>">
                                Cần xử lý
                                <?php if (!empty($pendingRentCount)): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-600 text-white text-xs font-bold rounded-full flex items-center justify-center"><?= $pendingRentCount > 99 ? '99+' : $pendingRentCount ?></span>
                                <?php endif; ?>
                            </button>
                            <button type="submit" name="rent_filter" value="approved"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? '') === 'approved' ? 'bg-green-600 text-white shadow-md' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' ?>"
                                data-active-class="bg-green-600 text-white shadow-md"
                                data-inactive-class="bg-green-50 text-green-700 hover:bg-green-100 border border-green-200"
                                aria-pressed="<?= ($rentFilter ?? '') === 'approved' ? 'true' : 'false' ?>">
                                Đã duyệt
                            </button>
                            <button type="submit" name="rent_filter" value="rejected"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($rentFilter ?? '') === 'rejected' ? 'bg-red-600 text-white shadow-md' : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' ?>"
                                data-active-class="bg-red-600 text-white shadow-md"
                                data-inactive-class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200"
                                aria-pressed="<?= ($rentFilter ?? '') === 'rejected' ? 'true' : 'false' ?>">
                                Từ chối
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-base text-gray-400">search</span>
                        <input
                            type="text"
                            name="rent_keyword"
                            value="<?= e($rentKeyword ?? '') ?>"
                            placeholder="Tìm theo tên phòng, tên người gửi, email, số điện thoại..."
                            class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                            data-search-input="rent"
                        >
                    </div>
                </form>
            </div>

            <div id="rent-requests-container">
                <div id="rent-requests-list" class="divide-y divide-gray-100">
                    <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $req): ?>
                    <?php require BASE_PATH . 'views/admin/tenants/partials/rent_request_item.php'; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="rent-requests-empty" class="px-6 py-12 text-center text-gray-500 <?= empty($requests) ? '' : 'hidden' ?>">
                    Không có yêu cầu thuê phòng nào khớp bộ lọc hiện tại.
                </div>
                <div id="rent-load-more-wrap" class="px-6 py-4 text-center <?= count($requests) > 5 ? '' : 'hidden' ?>">
                    <button type="button" id="rent-load-more" data-load-more="rent"
                        class="px-6 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">
                        Xem thêm
                    </button>
                </div>
            </div>
        </section>

        <!-- ================= CỘT 2: YÊU CẦU Ở GHÉP ================= -->
        <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-900">Yêu cầu ở ghép</h3>
                <p class="text-sm text-gray-500 mt-1">Người thuê xin ở ghép cùng người đang có phòng. Admin có thể duyệt hoặc từ chối. Yêu cầu đã duyệt không thể gỡ.</p>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 space-y-4">
                <form method="GET" action="<?= BASE_URL ?>" class="space-y-4" data-rent-ajax-form data-filter-group="roommate">
                    <input type="hidden" name="page" value="admin-rent-requests">
                    <input type="hidden" name="rent_filter" value="<?= e($rentFilter) ?>">
                    <input type="hidden" name="rent_keyword" value="<?= e($rentKeyword) ?>">

                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Bộ lọc trạng thái</p>
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Bộ lọc trạng thái yêu cầu ở ghép">
                            <button type="submit" name="roommate_filter" value="all"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? 'all') === 'all' ? 'bg-primary text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                                data-active-class="bg-primary text-white shadow-md"
                                data-inactive-class="bg-gray-100 text-gray-700 hover:bg-gray-200"
                                aria-pressed="<?= ($roommateFilter ?? 'all') === 'all' ? 'true' : 'false' ?>">
                                Tất cả
                            </button>
                            <button type="submit" name="roommate_filter" value="pending_admin"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 relative
                                    <?= ($roommateFilter ?? '') === 'pending_admin' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
                                data-active-class="bg-amber-600 text-white shadow-md"
                                data-inactive-class="bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'pending_admin' ? 'true' : 'false' ?>">
                                Cần xử lý
                                <?php if (!empty($pendingRoommateCount)): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-600 text-white text-xs font-bold rounded-full flex items-center justify-center"><?= $pendingRoommateCount > 99 ? '99+' : $pendingRoommateCount ?></span>
                                <?php endif; ?>
                            </button>
                            <button type="submit" name="roommate_filter" value="approved"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? '') === 'approved' ? 'bg-green-600 text-white shadow-md' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' ?>"
                                data-active-class="bg-green-600 text-white shadow-md"
                                data-inactive-class="bg-green-50 text-green-700 hover:bg-green-100 border border-green-200"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'approved' ? 'true' : 'false' ?>">
                                Đã duyệt
                            </button>
                            <button type="submit" name="roommate_filter" value="rejected"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200
                                    <?= ($roommateFilter ?? '') === 'rejected' ? 'bg-red-600 text-white shadow-md' : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' ?>"
                                data-active-class="bg-red-600 text-white shadow-md"
                                data-inactive-class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200"
                                aria-pressed="<?= ($roommateFilter ?? '') === 'rejected' ? 'true' : 'false' ?>">
                                Từ chối
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-base text-gray-400">search</span>
                        <input
                            type="text"
                            name="roommate_keyword"
                            value="<?= e($roommateKeyword ?? '') ?>"
                            placeholder="Tìm theo tên phòng, tên người gửi/người nhận, email, số điện thoại..."
                            class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary"
                            data-search-input="roommate"
                        >
                    </div>
                </form>
            </div>

            <div id="roommate-requests-container">
                <div id="roommate-requests-list" class="divide-y divide-gray-100">
                    <?php if (!empty($roommateRequests)): ?>
                    <?php foreach ($roommateRequests as $rr): ?>
                    <?php require BASE_PATH . 'views/admin/tenants/partials/roommate_request_item.php'; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="roommate-requests-empty" class="px-6 py-12 text-center text-gray-500 <?= empty($roommateRequests) ? '' : 'hidden' ?>">
                    Không có yêu cầu ở ghép nào khớp bộ lọc hiện tại.
                </div>
                <div id="roommate-load-more-wrap" class="px-6 py-4 text-center <?= count($roommateRequests) > 5 ? '' : 'hidden' ?>">
                    <button type="button" id="roommate-load-more" data-load-more="roommate"
                        class="px-6 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:border-primary hover:text-primary transition">
                        Xem thêm
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
(function () {
    if (window.rentRequestsAjaxReady) return;
    window.rentRequestsAjaxReady = true;

    var BASE = window.BASE_URL || '<?= BASE_URL ?>';
    var REVEAL_STEP = 5;
    var state = { rent: { shown: REVEAL_STEP }, roommate: { shown: REVEAL_STEP } };
    var debounceTimers = {};

    function formFor(group) {
        return document.querySelector('form[data-filter-group="' + group + '"]');
    }

    function searchValue(group) {
        var form = formFor(group);
        if (!form) return '';
        var input = form.querySelector('input[data-search-input]');
        return input ? input.value.trim() : '';
    }

    function activeFilter(group) {
        var form = formFor(group);
        if (!form) return 'all';
        var btn = form.querySelector('button[aria-pressed="true"]');
        return btn ? btn.value : 'all';
    }

    function syncButtonClass(btn, activeClass, inactiveClass, isActive) {
        var tokens = btn.className.split(/\s+/);
        var activeSet = (activeClass || '').split(/\s+/);
        var inactiveSet = (inactiveClass || '').split(/\s+/);
        var kept = tokens.filter(function (c) {
            return activeSet.indexOf(c) === -1 && inactiveSet.indexOf(c) === -1 && c !== 'hidden';
        });
        var target = isActive ? activeSet : inactiveSet;
        var out = kept.concat(target.filter(function (c) { return c; }));
        btn.className = out.join(' ');
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    }

    function setActiveButton(group, value) {
        var form = formFor(group);
        if (!form) return;
        form.querySelectorAll('button[data-active-class]').forEach(function (btn) {
            syncButtonClass(btn, btn.dataset.activeClass, btn.dataset.inactiveClass, btn.value === value);
        });
    }

    function applyPagination(group) {
        var list = document.getElementById(group + '-requests-list');
        var wrap = document.getElementById(group + '-load-more-wrap');
        if (!list || !wrap) return;
        var items = list.querySelectorAll('[data-' + group + '-item]');
        var shown = state[group].shown;
        items.forEach(function (el, i) {
            el.classList.toggle('hidden', i >= shown);
        });
        wrap.classList.toggle('hidden', items.length <= shown);
    }

    function applyList(group, payload) {
        var list = document.getElementById(group + '-requests-list');
        var empty = document.getElementById(group + '-requests-empty');
        var total = payload.total;
        list.innerHTML = payload.html;
        empty.classList.toggle('hidden', total > 0);
        state[group].shown = REVEAL_STEP;
        applyPagination(group);
    }

    function fetchLists() {
        var params = new URLSearchParams();
        params.set('page', 'api-admin-rent-requests');
        params.set('rent_filter', activeFilter('rent'));
        params.set('rent_keyword', searchValue('rent'));
        params.set('roommate_filter', activeFilter('roommate'));
        params.set('roommate_keyword', searchValue('roommate'));

        fetch(BASE + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.success !== true) return;
            applyList('rent', data.rent);
            applyList('roommate', data.roommate);
        })
        .catch(function () {});
    }

    document.querySelectorAll('form[data-rent-ajax-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var group = form.getAttribute('data-filter-group');
            var submitter = e.submitter;
            if (submitter && submitter.name === group + '_filter') {
                setActiveButton(group, submitter.value);
            }
            fetchLists();
        });
    });

    document.querySelectorAll('input[data-search-input]').forEach(function (input) {
        input.addEventListener('input', function () {
            var group = input.getAttribute('data-search-input');
            clearTimeout(debounceTimers[group]);
            debounceTimers[group] = setTimeout(fetchLists, 400);
        });
    });

    document.querySelectorAll('[data-load-more]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.getAttribute('data-load-more');
            state[group].shown += REVEAL_STEP;
            applyPagination(group);
        });
    });

    applyPagination('rent');
    applyPagination('roommate');
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>