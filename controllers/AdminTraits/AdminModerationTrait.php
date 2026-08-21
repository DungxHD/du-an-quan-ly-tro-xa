<?php
/**
 * AdminModerationTrait - Quản lý nội dung: services, price changes, notifications, comments, feedbacks
 */
trait AdminModerationTrait
{
    // ==========================================
    // SERVICES
    // ==========================================

    public function services(): void
    {
        PriceChangeModel::applyDueChanges();
        $searchKeyword = trim($_GET['search'] ?? '');
        $services = ServiceModel::getAll(['search' => $searchKeyword]);

        $rooms = array_map(fn($r) => ['occupant_count' => RoomModel::countOccupants($r['id'] ?? 0)] + $r, RoomModel::getAll());
        $selectedRoomId = (int)($_GET['room_id'] ?? 0);
        if ($selectedRoomId <= 0 && !empty($rooms[0]['id'])) $selectedRoomId = (int)$rooms[0]['id'];

        $selectedRoom = $selectedRoomId > 0 ? RoomModel::getById($selectedRoomId) : null;
        if ($selectedRoom) $selectedRoom['occupant_count'] = RoomModel::countOccupants($selectedRoomId);

        $roomAssignments = $selectedRoom ? ServiceModel::getAssignmentsByRoom($selectedRoomId) : [];
        $roomAssignableServices = ServiceModel::getAll(['applies_to' => 'room', 'active_only' => true, 'exclude_required' => true]);
        $requiredRoomServices = ServiceModel::getAll(['applies_to' => 'room', 'required_only' => true]);

        $editId = (int)($_GET['edit'] ?? 0);
        $editService = $editId > 0 ? ServiceModel::getById($editId) : null;
        $oldInput = pullFlash('admin_service_old');
        $formService = is_array($oldInput) ? $oldInput : ($editService ?? [
            'id' => 0, 'name' => '', 'price' => 0, 'unit' => 'tháng', 'icon' => 'settings',
            'description' => '', 'is_required' => 0, 'billing_mode' => 'per_person',
            'applies_to' => 'room', 'is_active' => 1,
        ]);

        $serviceMessage = pullFlash('admin_service_message');
        $serviceError = pullFlash('admin_service_error');
        $serviceBillingModes = $this->getServiceBillingModeOptions();
        $serviceAppliesToOptions = $this->getServiceAppliesToOptions();
        $kindBillingModes = ServiceModel::getKindBillingModesMap();

        $pendingChanges = PriceChangeModel::getPendingByServiceMap();
        $pageTitle = 'Quản lý Dịch vụ - NhaTroA';
        ServiceModel::applyDueDeletes();
        ServiceModel::applyDueDeactivates();

        $priceHistories = []; $pendingDeleteByService = []; $pendingDeactivateByService = [];
        $roomCountByService = []; $roomsUsingByService = [];
        foreach ($services as $svc) {
            $sid = (int)($svc['id'] ?? 0);
            $priceHistories[$sid] = PriceChangeModel::getPendingHistoryByService($sid);
            $pendingDeleteByService[$sid] = ServiceModel::isPendingDelete($svc);
            $pendingDeactivateByService[$sid] = ServiceModel::isPendingDeactivate($svc);
            $roomCountByService[$sid] = ServiceModel::countRoomsUsing($sid);
            $roomsUsingByService[$sid] = ServiceModel::getRoomsUsingService($sid);
        }

        $iconOptions = [
            ['key'=>'settings','label'=>'Settings'],['key'=>'bolt','label'=>'Bolt (Điện)'],
            ['key'=>'water_drop','label'=>'Water Drop (Nước)'],['key'=>'delete','label'=>'Delete (Rác)'],
            ['key'=>'wifi','label'=>'Wifi'],['key'=>'local_parking','label'=>'Parking (Giữ xe)'],
            ['key'=>'ev_station','label'=>'EV Station (Sạc xe)'],['key'=>'local_laundry_service','label'=>'Laundry (Máy giặt)'],
            ['key'=>'fitness_center','label'=>'Gym'],['key'=>'pool','label'=>'Pool'],
            ['key'=>'kitchen','label'=>'Kitchen'],['key'=>'ac_unit','label'=>'AC'],
            ['key'=>'security','label'=>'Security'],['key'=>'elevator','label'=>'Elevator'],
            ['key'=>'water_heater','label'=>'Water Heater'],
        ];

        $isEditing = !empty($formService['id']);
        require_once BASE_PATH . 'views/admin/billing/services.php';
    }

    // ==========================================
    // PRICE CHANGES
    // ==========================================

    public function priceChanges(): void
    {
        PriceChangeModel::applyDueChanges();
        $searchKeyword = trim($_GET['search'] ?? '');
        $services = ServiceModel::getAll(['search' => $searchKeyword]);

        $selectedServiceId = (int)($_GET['service_id'] ?? 0);
        if ($selectedServiceId <= 0 && !empty($services[0]['id'])) $selectedServiceId = (int)$services[0]['id'];

        $selectedService = $selectedServiceId > 0 ? ServiceModel::getById($selectedServiceId) : null;
        $old = pullFlash('admin_price_change_old');
        $form = array_merge([
            'service_id' => $selectedServiceId,
            'new_price' => $selectedService['price'] ?? '',
            'effective_month' => (int)date('n') + 1 > 12 ? 1 : (int)date('n') + 1,
            'effective_year' => (int)date('n') === 12 ? (int)date('Y') + 1 : (int)date('Y'),
        ], is_array($old) ? $old : []);

        $previewService = !empty($form['service_id']) ? ServiceModel::getById((int)$form['service_id']) : $selectedService;
        $history = PriceChangeModel::getAll(['service_id' => $selectedServiceId > 0 ? $selectedServiceId : 0]);
        $msg = pullFlash('admin_price_change_message');
        $err = pullFlash('admin_price_change_error');
        $pageTitle = 'Đổi giá Dịch vụ - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/price_changes.php';
    }

    public function savePriceChange(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-price-changes');
        verify_csrf();

        $payload = [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'new_price' => trim($_POST['new_price'] ?? ''),
            'effective_month' => (int)($_POST['effective_month'] ?? 0),
            'effective_year' => (int)($_POST['effective_year'] ?? 0),
        ];
        setFlash('admin_price_change_old', $payload);

        try {
            PriceChangeModel::scheduleServiceChange($payload['service_id'], (float)$payload['new_price'], null, $payload['effective_month'], $payload['effective_year'], (int)($_SESSION['user_id'] ?? 0));
            setFlash('admin_price_change_message', 'Đã lên lịch đổi giá và gửi thông báo cho tenant.');
        } catch (Throwable $e) {
            setFlash('admin_price_change_error', $e->getMessage());
        }
        redirectTo('admin-price-changes', ['service_id' => $payload['service_id'] > 0 ? $payload['service_id'] : null]);
    }

    public function cancelPriceChange(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-price-changes');
        verify_csrf();
        try {
            PriceChangeModel::cancelPendingChange($id);
            setFlash('admin_price_change_message', 'Đã hủy lịch thay đổi giá.');
        } catch (Throwable $e) {
            setFlash('admin_price_change_error', $e->getMessage());
        }
        redirectTo('admin-price-changes');
    }

    // ==========================================
    // NOTIFICATIONS
    // ==========================================

    public function notifications(): void
    {
        $tenants = array_values(array_filter(UserModel::getAll(), fn($u) => ($u['role'] ?? 0) === 0));

        $filters = ['category' => trim($_GET['category'] ?? '')];
        $old = pullFlash('admin_notification_old');
        $form = array_merge(['title' => '', 'content' => ''], is_array($old) ? $old : []);

        $history = NotificationModel::getAdminHistory($filters);
        $categories = NotificationModel::getAdminCategories();
        $selectedCategory = $filters['category'];

        $msg = pullFlash('admin_notification_message');
        $err = pullFlash('admin_notification_error');

        $recent = array_slice($history, 0, 5);
        $unreadCount = count(array_filter($history, fn($n) => (int)($n['is_read'] ?? 0) === 0));

        $pageTitle = 'Quản lý Thông báo - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/notifications.php';
    }

    public function sendNotification(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-notifications');
        verify_csrf();

        $payload = ['title' => trim($_POST['title'] ?? ''), 'content' => trim($_POST['content'] ?? '')];
        setFlash('admin_notification_old', $payload);

        try {
            if ($payload['title'] === '') throw new RuntimeException('Tiêu đề bắt buộc.');
            if ($payload['content'] === '') throw new RuntimeException('Nội dung bắt buộc.');

            NotificationModel::create(['user_id' => null, 'title' => $payload['title'], 'content' => $payload['content'], 'type' => 'general']);
            setFlash('admin_notification_message', 'Đã gửi thông báo đến tất cả tenant.');
        } catch (Throwable $e) {
            setFlash('admin_notification_error', $e->getMessage());
        }
        redirectTo('admin-notifications');
    }

    public function markNotificationRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-notifications');
        verify_csrf();

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        $markAll = !empty($_POST['mark_all']);
        $redirectPage = trim($_POST['redirect_page'] ?? 'admin-notifications');

        if ($adminUserId <= 0) { setFlash('admin_notification_error', 'Phiên đăng nhập không hợp lệ.'); redirectTo('admin-notifications'); return; }

        try {
            if ($markAll) {
                NotificationModel::markAllAsRead($adminUserId);
                setFlash('admin_notification_message', 'Đã đánh dấu tất cả đã đọc.');
                redirectTo($redirectPage);
            }
            if ($notificationId <= 0) throw new RuntimeException('Thông báo không hợp lệ.');

            NotificationModel::markAsRead($notificationId, $adminUserId);
            if ($redirectPage === 'admin-notifications') redirectTo('admin-notifications', ['notification_id' => $notificationId]);
            redirectTo($redirectPage);
        } catch (Throwable $e) {
            setFlash('admin_notification_error', $e->getMessage());
            redirectTo('admin-notifications');
        }
    }

    // ==========================================
    // COMMENTS
    // ==========================================

    public function comments(): void
    {
        $filters = ['status' => trim($_GET['status'] ?? ''), 'keyword' => trim($_GET['keyword'] ?? '')];
        $comments = CommentModel::getAdminComments($filters);
        $stats = CommentModel::getAdminStats($comments);
        $msg = pullFlash('admin_comment_message');
        $err = pullFlash('admin_comment_error');
        $pageTitle = 'Quản lý Đánh giá - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/comments.php';
    }

    public function toggleComment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-comments');
        verify_csrf();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $targetStatus = isset($_POST['target_status']) ? (int)$_POST['target_status'] : null;

        $redirectParams = array_filter([
            'status' => trim($_POST['return_status'] ?? ''),
            'keyword' => trim($_POST['return_keyword'] ?? ''),
        ], fn($v) => $v !== null && $v !== '');

        try {
            $comment = CommentModel::toggleStatus($commentId, $targetStatus);
            $txt = (int)($comment['status'] ?? 0) === 1 ? 'hiện' : 'ẩn';
            setFlash('admin_comment_message', 'Đã cập nhật trạng thái đánh giá sang ' . $txt . '.');
        } catch (Throwable $e) { setFlash('admin_comment_error', $e->getMessage()); }
        redirectTo('admin-comments', $redirectParams);
    }

    // ==========================================
    // FEEDBACKS
    // ==========================================

    public function feedbacks(): void
    {
        $filters = ['status' => trim($_GET['status'] ?? ''), 'keyword' => trim($_GET['keyword'] ?? '')];
        $feedbacks = FeedbackModel::getAdminFeedbacks($filters);
        $stats = FeedbackModel::getAdminStats($feedbacks);

        $editId = (int)($_GET['edit'] ?? 0);
        $editFeedback = $editId > 0 ? FeedbackModel::getById($editId) : null;

        $msg = pullFlash('admin_feedback_message');
        $err = pullFlash('admin_feedback_error');
        $pageTitle = 'Quản lý Phản ánh - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/feedbacks.php';
    }

    public function saveFeedback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-feedbacks');
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $action = trim($_POST['form_action'] ?? 'save');
        $redirectParams = array_filter([
            'status' => trim($_POST['return_status'] ?? ''),
            'keyword' => trim($_POST['return_keyword'] ?? ''),
        ], fn($v) => $v !== null && $v !== '');

        if ($action === 'delete') {
            try { FeedbackModel::delete($id); setFlash('admin_feedback_message', 'Đã xóa phản ánh.'); }
            catch (Throwable $e) { setFlash('admin_feedback_error', $e->getMessage()); }
            redirectTo('admin-feedbacks', $redirectParams);
        }

        $payload = ['admin_note' => trim($_POST['admin_note'] ?? ''), 'admin_reply' => trim($_POST['admin_reply'] ?? '')];

        try {
            $savedId = FeedbackModel::save($payload, $id > 0 ? $id : null);
            setFlash('admin_feedback_message', $id > 0 ? 'Đã cập nhật phản ánh.' : 'Đã thêm phản ánh.');
            redirectTo('admin-feedbacks', array_merge($redirectParams, ['edit' => $savedId]));
        } catch (Throwable $e) {
            setFlash('admin_feedback_old', array_merge($payload, ['id' => $id]));
            setFlash('admin_feedback_error', $e->getMessage());
            redirectTo('admin-feedbacks', array_merge($redirectParams, $id > 0 ? ['edit' => $id] : []));
        }
    }

    public function resolveFeedback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-feedbacks');
        verify_csrf();

        $fbId = (int)($_POST['feedback_id'] ?? 0);
        $action = trim($_POST['resolve_action'] ?? '');
        $redirectParams = array_filter([
            'status' => trim($_POST['return_status'] ?? ''),
            'keyword' => trim($_POST['return_keyword'] ?? ''),
        ], fn($v) => $v !== null && $v !== '');

        try {
            $result = FeedbackModel::resolve($fbId, $action);
            setFlash('admin_feedback_message', ($result['action'] ?? '') === 'resolved' ? 'Đã đánh dấu đã xử lý.' : 'Đã bác bỏ phản ánh.');
        } catch (Throwable $e) { setFlash('admin_feedback_error', $e->getMessage()); }

        redirectTo('admin-feedbacks', $redirectParams);
    }
}