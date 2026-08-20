<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminModerationTrait
{
/**
     * Quản lý tiện ích đã được gộp vào trang Cấu hình hệ thống (admin-settings).
     */
    public function amenities()
    {
        redirectTo('admin-settings');
    }
public function services()
    {
        PriceChangeModel::applyDueChanges();
        $searchKeyword = trim((string)($_GET['search'] ?? ''));
        $services = ServiceModel::getAll(['search' => $searchKeyword]);
        $rooms = array_map(static function ($room) {
            $room['occupant_count'] = RoomModel::countOccupants((int)($room['id'] ?? 0));
            return $room;
        }, RoomModel::getAll());
        $selectedRoomId = (int)($_GET['room_id'] ?? 0);
        if ($selectedRoomId <= 0 && !empty($rooms[0]['id'])) {
            $selectedRoomId = (int)$rooms[0]['id'];
        }
        $selectedRoom = $selectedRoomId > 0 ? RoomModel::getById($selectedRoomId) : null;
        if ($selectedRoom) {
            $selectedRoom['occupant_count'] = RoomModel::countOccupants($selectedRoomId);
        }
        $roomAssignments = $selectedRoom ? ServiceModel::getAssignmentsByRoom($selectedRoomId) : [];
        $roomAssignableServices = ServiceModel::getAll([
            'applies_to' => 'room',
            'active_only' => true,
            'exclude_required' => true,
        ]);
        $requiredRoomServices = ServiceModel::getAll([
            'applies_to' => 'room',
            'required_only' => true,
        ]);
        $editId = (int)($_GET['edit'] ?? 0);
        $editService = $editId > 0 ? ServiceModel::getById($editId) : null;
        $oldServiceInput = pullFlash('admin_service_old');
        $formService = is_array($oldServiceInput) ? $oldServiceInput : ($editService ?? [
            'id' => 0,
            'name' => '',
            'price' => 0,
            'unit' => 'tháng',
            'icon' => 'settings',
            'description' => '',
            'is_required' => 0,
            'billing_mode' => 'per_person',
            'applies_to' => 'room',
            'is_active' => 1,
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
        $priceHistories = [];
        $pendingDeleteByService = [];
        $pendingDeactivateByService = [];
        $roomCountByService = [];
        $roomsUsingByService = [];
        foreach ($services as $svc) {
            $svcId = (int)($svc['id'] ?? 0);
            $priceHistories[$svcId] = PriceChangeModel::getPendingHistoryByService($svcId);
            $pendingDeleteByService[$svcId] = ServiceModel::isPendingDelete($svc);
            $pendingDeactivateByService[$svcId] = ServiceModel::isPendingDeactivate($svc);
            $roomCountByService[$svcId] = ServiceModel::countRoomsUsing($svcId);
            $roomsUsingByService[$svcId] = ServiceModel::getRoomsUsingService($svcId);
        }
        $iconOptions = [
            ['key' => 'settings', 'label' => 'Settings'],
            ['key' => 'bolt', 'label' => 'Bolt (Điện)'],
            ['key' => 'water_drop', 'label' => 'Water Drop (Nước)'],
            ['key' => 'delete', 'label' => 'Delete (Rác)'],
            ['key' => 'wifi', 'label' => 'Wifi'],
            ['key' => 'local_parking', 'label' => 'Parking (Giữ xe)'],
            ['key' => 'ev_station', 'label' => 'EV Station (Sạc xe)'],
            ['key' => 'local_laundry_service', 'label' => 'Laundry (Máy giặt)'],
            ['key' => 'fitness_center', 'label' => 'Gym'],
            ['key' => 'pool', 'label' => 'Pool'],
            ['key' => 'kitchen', 'label' => 'Kitchen'],
            ['key' => 'ac_unit', 'label' => 'AC'],
            ['key' => 'security', 'label' => 'Security'],
            ['key' => 'elevator', 'label' => 'Elevator'],
            ['key' => 'water_heater', 'label' => 'Water Heater'],
        ];
        $isEditing = !empty($formService['id']);
        require_once BASE_PATH . 'views/admin/billing/services.php';
    }
public function priceChanges()
    {
        PriceChangeModel::applyDueChanges();
        $searchKeyword = trim((string)($_GET['search'] ?? ''));
        $services = ServiceModel::getAll(['search' => $searchKeyword]);
        $selectedServiceId = (int)($_GET['service_id'] ?? 0);
        if ($selectedServiceId <= 0 && !empty($services[0]['id'])) {
            $selectedServiceId = (int)$services[0]['id'];
        }

        $selectedService = $selectedServiceId > 0 ? ServiceModel::getById($selectedServiceId) : null;
        $priceChangeOld = pullFlash('admin_price_change_old');
        $priceChangeForm = array_merge([
            'service_id' => $selectedServiceId,
            'new_price' => $selectedService['price'] ?? '',
            'effective_month' => (int)date('n') + 1 > 12 ? 1 : (int)date('n') + 1,
            'effective_year' => (int)date('n') === 12 ? (int)date('Y') + 1 : (int)date('Y'),
        ], is_array($priceChangeOld) ? $priceChangeOld : []);

        $priceChangePreviewService = !empty($priceChangeForm['service_id']) ? ServiceModel::getById((int)$priceChangeForm['service_id']) : $selectedService;
        $priceChangeHistory = PriceChangeModel::getAll([
            'service_id' => $selectedServiceId > 0 ? $selectedServiceId : 0,
        ]);
        $priceChangeMessage = pullFlash('admin_price_change_message');
        $priceChangeError = pullFlash('admin_price_change_error');
        $pageTitle = 'Đổi giá Dịch vụ - NhaTroA';
        require_once BASE_PATH . 'views/admin/billing/price_changes.php';
    }
/**
     * Lưu lịch sử đổi giá và tự phát sinh thông báo broadcast cho tenant.
     */
    public function savePriceChange()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-price-changes');
        }
        verify_csrf();
        $payload = [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'new_price' => trim((string)($_POST['new_price'] ?? '')),
            'effective_month' => (int)($_POST['effective_month'] ?? 0),
            'effective_year' => (int)($_POST['effective_year'] ?? 0),
        ];
        setFlash('admin_price_change_old', $payload);
        try {
            PriceChangeModel::scheduleServiceChange(
                $payload['service_id'],
                (float)$payload['new_price'],
                null,
                $payload['effective_month'],
                $payload['effective_year'],
                (int)($_SESSION['user_id'] ?? 0)
            );
            setFlash('admin_price_change_message', 'Đã lên lịch đổi giá và gửi thông báo cho tenant.');
        } catch (Throwable $exception) {
            setFlash('admin_price_change_error', $exception->getMessage());
        }
        redirectTo('admin-price-changes', [
            'service_id' => $payload['service_id'] > 0 ? $payload['service_id'] : null,
        ]);
    }
public function notifications()
    {
        $tenants = array_values(array_filter(
            UserModel::getAll(),
            static fn($user) => (int)($user['role'] ?? 0) === 0
        ));

        $notificationFilters = [
            'category' => trim((string)($_GET['category'] ?? '')),
        ];
        $notificationOld = pullFlash('admin_notification_old');
        $notificationForm = array_merge([
            'title' => '',
            'content' => '',
        ], is_array($notificationOld) ? $notificationOld : []);

        $notificationHistory = NotificationModel::getAdminHistory($notificationFilters);
        $notificationCategories = NotificationModel::getAdminCategories();
        $selectedNotificationCategory = $notificationFilters['category'];
        $notificationMessage = pullFlash('admin_notification_message');
        $notificationError = pullFlash('admin_notification_error');
        
        // Admin notification bell - recent sent notifications
        $adminNotificationRecent = array_slice($notificationHistory, 0, 5);
        $adminNotificationUnreadCount = count(array_filter(
            $notificationHistory,
            static fn($n) => (int)($n['is_read'] ?? 0) === 0
        ));

        $pageTitle = 'Quản lý Thông báo - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/notifications.php';
    }
/**
     * Quản lý toàn bộ đánh giá phòng, gồm cả đánh giá đang bị ẩn.
     */
    public function comments()
    {
        $commentFilters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'keyword' => trim((string)($_GET['keyword'] ?? '')),
        ];
        $comments = CommentModel::getAdminComments($commentFilters);
        $commentStats = CommentModel::getAdminStats($comments);
        $commentMessage = pullFlash('admin_comment_message');
        $commentError = pullFlash('admin_comment_error');
        $pageTitle = 'Quản lý Đánh giá - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/comments.php';
    }
/**
     * Trang quản lý Phản ánh từ người thuê (feedback tenant -> chủ trọ).
     */
    public function feedbacks()
    {
        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'keyword' => trim((string)($_GET['keyword'] ?? '')),
        ];
        $feedbacks = FeedbackModel::getAdminFeedbacks($filters);
        $feedbackStats = FeedbackModel::getAdminStats($feedbacks);
        $editId = (int)($_GET['edit'] ?? 0);
        $editFeedback = $editId > 0 ? FeedbackModel::getById($editId) : null;
        $feedbackMessage = pullFlash('admin_feedback_message');
        $feedbackError = pullFlash('admin_feedback_error');
        $pageTitle = 'Quản lý Phản ánh - NhaTroA';
        require_once BASE_PATH . 'views/admin/moderation/feedbacks.php';
    }
/**
     * Lưu hoặc cập nhật phản ánh (admin xử lý).
     */
    public function saveFeedback()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-feedbacks');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $action = trim((string)($_POST['form_action'] ?? 'save'));
        $redirectParams = array_filter([
            'status' => trim((string)($_POST['return_status'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
        ], static fn($value) => $value !== null && $value !== '');

        if ($action === 'delete') {
            try {
                FeedbackModel::delete($id);
                setFlash('admin_feedback_message', 'Đã xóa phản ánh thành công.');
            } catch (Throwable $exception) {
                setFlash('admin_feedback_error', $exception->getMessage());
            }
            redirectTo('admin-feedbacks', $redirectParams);
        }

        $payload = [
            'admin_note' => trim((string)($_POST['admin_note'] ?? '')),
            'admin_reply' => trim((string)($_POST['admin_reply'] ?? '')),
        ];

        try {
            $savedId = FeedbackModel::save($payload, $id > 0 ? $id : null);
            setFlash('admin_feedback_message', $id > 0 ? 'Đã cập nhật phản ánh thành công.' : 'Đã thêm phản ánh thành công.');
            redirectTo('admin-feedbacks', array_merge($redirectParams, ['edit' => $savedId]));
        } catch (Throwable $exception) {
            setFlash('admin_feedback_old', array_merge($payload, ['id' => $id]));
            setFlash('admin_feedback_error', $exception->getMessage());
            redirectTo('admin-feedbacks', array_merge($redirectParams, $id > 0 ? ['edit' => $id] : []));
        }
    }
/**
     * Admin giải quyết phản ánh.
     */
    public function resolveFeedback()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-feedbacks');
        }
        verify_csrf();

        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        $action = trim((string)($_POST['resolve_action'] ?? ''));
        $redirectParams = array_filter([
            'status' => trim((string)($_POST['return_status'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $result = FeedbackModel::resolve($feedbackId, $action);
            setFlash(
                'admin_feedback_message',
                ($result['action'] ?? '') === 'resolved'
                    ? 'Đã đánh dấu phản ánh là đã xử lý.'
                    : 'Đã bác bỏ phản ánh này.'
            );
        } catch (Throwable $exception) {
            setFlash('admin_feedback_error', $exception->getMessage());
        }

        redirectTo('admin-feedbacks', $redirectParams);
    }
/**
     * Bật/tắt hiển thị comment theo quyết định của admin.
     */
    public function toggleComment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-comments');
        }
        verify_csrf();

        $commentId = (int)($_POST['comment_id'] ?? 0);
        $targetStatus = isset($_POST['target_status']) ? (int)$_POST['target_status'] : null;
        $redirectParams = array_filter([
            'status' => trim((string)($_POST['return_status'] ?? '')) ?: null,
            'keyword' => trim((string)($_POST['return_keyword'] ?? '')) ?: null,
        ], static fn($value) => $value !== null && $value !== '');

        try {
            $comment = CommentModel::toggleStatus($commentId, $targetStatus);
            $statusText = (int)($comment['status'] ?? 0) === 1 ? 'hiện' : 'ẩn';
            setFlash('admin_comment_message', 'Đã cập nhật trạng thái đánh giá sang ' . $statusText . '.');
        } catch (Throwable $exception) {
            setFlash('admin_comment_error', $exception->getMessage());
        }

        redirectTo('admin-comments', $redirectParams);
    }
/**
     * Gửi thông báo broadcast cho toàn bộ tenant.
     */
    public function sendNotification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-notifications');
        }
        verify_csrf();

        $payload = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'content' => trim((string)($_POST['content'] ?? '')),
        ];
        setFlash('admin_notification_old', $payload);

        try {
            if ($payload['title'] === '') {
                throw new RuntimeException('Tiêu đề thông báo là bắt buộc.');
            }
            if ($payload['content'] === '') {
                throw new RuntimeException('Nội dung thông báo là bắt buộc.');
            }

            NotificationModel::create([
                'user_id' => null,
                'title' => $payload['title'],
                'content' => $payload['content'],
                'type' => 'general',
            ]);

            setFlash('admin_notification_message', 'Đã gửi thông báo đến tất cả tenant thành công.');
        } catch (Throwable $exception) {
            setFlash('admin_notification_error', $exception->getMessage());
        }
        redirectTo('admin-notifications');
    }
/**
     * Tạo mới hoặc cập nhật cấu hình dịch vụ.
     */
    public function saveService()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $returnRoomId = (int)($_POST['return_room_id'] ?? 0);
        $data = $this->normalizeServiceInput($_POST);
        $existing = $id > 0 ? ServiceModel::getById($id) : null;
        $kind = (string)($existing['kind'] ?? 'other');
        $redirectParams = array_filter([
            'edit' => $id > 0 ? $id : null,
            'room_id' => $returnRoomId > 0 ? $returnRoomId : null,
        ], static fn($value) => $value !== null && $value !== '');
        if ($id > 0 && !$existing) {
            setFlash('admin_service_error', 'Dịch vụ cần cập nhật không tồn tại.');
            redirectTo('admin-services');
        }
        if ($data['name'] === '') {
            setFlash('admin_service_error', 'Tên dịch vụ là bắt buộc.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services');
        }
        // Kiểm tra tên trùng
        $allServices = ServiceModel::getAll();
        foreach ($allServices as $svc) {
            if ((int)($svc['id'] ?? 0) !== $id && trim($svc['name'] ?? '') === trim($data['name'])) {
                setFlash('admin_service_error', 'Tên dịch vụ "' . e($data['name']) . '" đã tồn tại. Vui lòng chọn tên khác.');
                setFlash('admin_service_old', array_merge($data, ['id' => $id]));
                redirectTo('admin-services');
            }
        }
        if ($data['price'] < 0) {
            setFlash('admin_service_error', 'Giá dịch vụ không được nhỏ hơn 0.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services');
        }
        $allowedModes = ServiceModel::getKindBillingModesMap()[$kind] ?? array_keys(ServiceModel::getBillingModeOptions());
        if (!in_array($data['billing_mode'], $allowedModes, true)) {
            setFlash('admin_service_error', 'Cách tính giá không hợp lệ cho loại dịch vụ này. Chấp nhận: ' . implode(', ', $allowedModes) . '.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services');
        }
        if (!in_array($data['applies_to'], $this->getAllowedServiceAppliesTo(), true)) {
            setFlash('admin_service_error', 'Đối tượng áp dụng không hợp lệ.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services');
        }
        // Đối tượng áp dụng bị khóa theo cách tính giá: theo người → người, theo số lượng → phòng.
        $appliesToByBillingMode = ['per_person' => 'person', 'per_unit' => 'room'];
        if (isset($appliesToByBillingMode[$data['billing_mode']])) {
            $data['applies_to'] = $appliesToByBillingMode[$data['billing_mode']];
        }
        if (ServiceModel::isLockedKind($kind)) {
            $data['applies_to'] = 'room';
            $data['is_required'] = 1;
            $data['is_active'] = 1;
        }
        // Xu ly tat dich vu theo lich: co phong dung thi tat thang sau
        if ($existing && !ServiceModel::isLockedKind($kind)) {
            $wasActive = (int)($existing['is_active'] ?? 1);
            $nowInactive = (int)($data['is_active'] ?? 1) === 0;
            if ($wasActive === 1 && $nowInactive) {
                $usingCount = ServiceModel::countRoomsUsing($id);
                if ($usingCount > 0) {
                    $dm = (int)date('n') + 1;
                    $dy = (int)date('Y');
                    if ($dm > 12) {
                        $dm = 1;
                        $dy++;
                    }
                    ServiceModel::scheduleDeactivate($id, $dm, $dy);
                    $data['is_active'] = 1;
                    $affectedRoomIds = array_values(array_filter(
                        array_map(static fn($roomRow) => (int)($roomRow['room_id'] ?? 0), ServiceModel::getRoomsUsingService($id)),
                        static fn($roomId) => $roomId > 0
                    ));
                    NotificationModel::createForRoomUsers($affectedRoomIds, [
                        'title' => 'Dịch vụ ' . trim((string)($existing['name'] ?? '')) . ' sẽ ngừng hoạt động',
                        'content' => 'Dịch vụ ' . trim((string)($existing['name'] ?? '')) . ' sẽ ngừng được sử dụng từ tháng ' . str_pad((string)$dm, 2, '0', STR_PAD_LEFT) . '/' . $dy . '.',
                        'type' => 'service',
                        'link' => '?page=tenant-services',
                    ]);
                    setFlash('admin_service_message', 'Dich vu dang co ' . $usingCount . ' phong su dung. Se tat tu thang ' . str_pad((string)$dm, 2, '0', STR_PAD_LEFT) . '/' . $dy . '.');
                    redirectTo('admin-services');
                } else {
                    // Không có phòng sử dụng → tắt ngay
                    $updateData = $data;
                    $updateData['is_active'] = 0;
                    ServiceModel::save($updateData, $id);
                    setFlash('admin_service_message', 'Đã tắt dịch vụ ngay lập tức (không có phòng sử dụng).');
                    redirectTo('admin-services');
                }
            }
            if ($wasActive === 1 && !$nowInactive && ServiceModel::isPendingDeactivate($existing)) {
                ServiceModel::undoDeactivate($id);
            }
        }
        if ($data['is_required'] === 1 && $data['applies_to'] !== 'room') {
            setFlash('admin_service_error', 'Dịch vụ bắt buộc chỉ được áp dụng theo phòng.');
            setFlash('admin_service_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-services');
        }
        if ($existing) {
            $submittedPrice = (float)$data['price'];
            $submittedMode = $data['billing_mode'];
            $core = $data;
            $core['price'] = (float)$existing['price'];
            $core['billing_mode'] = (string)$existing['billing_mode'];
            $core['applies_to'] = (string)($existing['applies_to'] ?? 'room');
            $core['unit'] = ServiceModel::deriveUnit((string)($existing['kind'] ?? 'other'), (string)$existing['billing_mode']);
            $core['kind'] = (string)($existing['kind'] ?? 'other');
            ServiceModel::save($core, $id);
            $priceChanged = abs($submittedPrice - (float)$existing['price']) > 0.001;
            $modeChanged = $submittedMode !== (string)$existing['billing_mode'];
            if ($priceChanged || $modeChanged) {
                $usingCount = ServiceModel::countRoomsUsing($id);
                if ($usingCount === 0) {
                    // Không có phòng sử dụng → áp dụng ngay
                    ServiceModel::clearAllPendingChanges($id);
                    $updateData = $data;
                    $updateData['price'] = $submittedPrice;
                    $updateData['billing_mode'] = $submittedMode;
                    $updateData['unit'] = ServiceModel::deriveUnit((string)($existing['kind'] ?? 'other'), $submittedMode);
                    $updateData['kind'] = (string)($existing['kind'] ?? 'other');
                    ServiceModel::save($updateData, $id);
                    setFlash('admin_service_message', 'Đã cập nhật giá/cách tính ngay lập tức (không có phòng sử dụng).');
                } else {
                    // Có phòng sử dụng → schedule
                    try {
                        $em = (int)($_POST['effective_month'] ?? 0);
                        $ey = (int)($_POST['effective_year'] ?? 0);
                        $curOrder = ((int)date('Y') * 100) + (int)date('n');
                        if ($em >= 1 && $em <= 12 && $ey >= (int)date('Y') && ($ey * 100 + $em) > $curOrder) {
                            $nextMonth = $em;
                            $nextYear = $ey;
                        } else {
                            $nextMonth = (int)date('n') + 1;
                            $nextYear = (int)date('Y');
                            if ($nextMonth > 12) {
                                $nextMonth = 1;
                                $nextYear++;
                            }
                        }
                        PriceChangeModel::scheduleServiceChange($id, $submittedPrice, $submittedMode, $nextMonth, $nextYear, (int)($_SESSION['user_id'] ?? 0));
                        setFlash('admin_service_message', 'Đã cập nhật dịch vụ. Giá/cách tính mới áp dụng từ tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . ' (có ' . $usingCount . ' phòng sử dụng).');
                    } catch (Throwable $exception) {
                        setFlash('admin_service_error', $exception->getMessage());
                    }
                }
            } else {
                setFlash('admin_service_message', 'Đã cập nhật dịch vụ thành công.');
            }
            redirectTo('admin-services');
        }
        $data['kind'] = 'other';
        $savedId = ServiceModel::save($data, null);
        setFlash('admin_service_message', 'Đã thêm dịch vụ mới thành công.');
        redirectTo('admin-services');
    }
public function undoDeleteService($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $service = ServiceModel::getById((int)$id);
        if ($service && ServiceModel::isPendingDelete($service)) {
            ServiceModel::undoDelete((int)$id);
            setFlash('admin_service_message', 'Đã hoàn tác xóa. Dịch vụ "' . ($service['name'] ?? '') . '" tiếp tục hoạt động.');
        } else {
            setFlash('admin_service_error', 'Dịch vụ không tồn tại hoặc không ở trạng thái chờ xóa.');
        }
        redirectTo('admin-services');
    }
public function cancelPriceChange($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        try {
            PriceChangeModel::cancelPendingChange((int)$id);
            setFlash('admin_service_message', 'Đã hủy lịch thay đổi giá/cách tính.');
        } catch (Throwable $exception) {
            setFlash('admin_service_error', $exception->getMessage());
        }
        redirectTo('admin-services');
    }
/**
     * [DEV-QWEN-A] Xac nhan xoa dich vu (huy pending changes + xoa hoac len lich xoa thang sau)
     */
    public function confirmDeleteService($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $serviceId = (int)$id;
        $service = ServiceModel::getById($serviceId);
        if (!$service) {
            setFlash('admin_service_error', 'Dich vu khong ton tai.');
            redirectTo('admin-services');
        }
        $locked = (int)($service['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($service['kind'] ?? 'other');
        if ($locked) {
            setFlash('admin_service_error', 'Dich vu bat buoc khong the xoa.');
            redirectTo('admin-services');
        }

        ServiceModel::clearAllPendingChanges($serviceId);

        $using = ServiceModel::countRoomsUsing($serviceId);
        $roomsUsing = ServiceModel::getRoomsUsingService($serviceId);
        $affectedRoomIds = array_values(array_filter(
            array_map(static fn($roomRow) => (int)($roomRow['room_id'] ?? 0), $roomsUsing),
            static fn($roomId) => $roomId > 0
        ));
        if ($using > 0) {
            $nextMonth = (int)date('n') + 1;
            $nextYear  = (int)date('Y');
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            ServiceModel::scheduleDelete($serviceId, $nextMonth, $nextYear);
            NotificationModel::createForRoomUsers($affectedRoomIds, [
                'title' => 'Dịch vụ ' . trim((string)($service['name'] ?? '')) . ' sẽ bị xóa',
                'content' => 'Dịch vụ ' . trim((string)($service['name'] ?? '')) . ' sẽ bị xóa khỏi phòng của bạn kể từ tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '.',
                'type' => 'service',
                'link' => '?page=tenant-services',
            ]);
            setFlash('admin_service_message', 'Da huy moi thay doi cho. Dich vu se bi xoa vao thang ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '.');
        } else {
            try {
                ServiceModel::delete($serviceId);
                setFlash('admin_service_message', 'Da huy moi thay doi cho va xoa dich vu thanh cong.');
            } catch (Throwable $e) {
                setFlash('admin_service_error', $e->getMessage());
            }
        }
        redirectTo('admin-services');
    }
public function undoDeactivateService($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $service = ServiceModel::getById((int)$id);
        if ($service && ServiceModel::isPendingDeactivate($service)) {
            ServiceModel::undoDeactivate((int)$id);
            setFlash('admin_service_message', 'Đã hoàn tác tắt. Dịch vụ "' . ($service['name'] ?? '') . '" tiếp tục hoạt động.');
        } else {
            setFlash('admin_service_error', 'Dịch vụ không tồn tại hoặc không ở trạng thái chờ tắt.');
        }
        redirectTo('admin-services');
    }
public function deleteService($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();
        $service = ServiceModel::getById((int)$id);
        if ($service) {
            $locked = (int)($service['is_required'] ?? 0) === 1 || ServiceModel::isLockedKind($service['kind'] ?? 'other');
            if ($locked) {
                setFlash('admin_service_error', 'Dịch vụ bắt buộc (điện/nước/rác) không thể xóa.');
                redirectTo('admin-services');
            }
            // Hủy mọi thay đổi chờ trước khi xử lý xóa
            ServiceModel::clearAllPendingChanges((int)$id);
            $using = ServiceModel::countRoomsUsing((int)$id);
            if ($using > 0) {
                $em = (int)($_POST['effective_month'] ?? 0);
                $ey = (int)($_POST['effective_year'] ?? 0);
                $curOrder = ((int)date('Y') * 100) + (int)date('n');
                if ($em >= 1 && $em <= 12 && $ey >= (int)date('Y') && ($ey * 100 + $em) > $curOrder) {
                    $nextMonth = $em;
                    $nextYear = $ey;
                } else {
                    $nextMonth = (int)date('n') + 1;
                    $nextYear = (int)date('Y');
                    if ($nextMonth > 12) {
                        $nextMonth = 1;
                        $nextYear++;
                    }
                }
                ServiceModel::scheduleDelete((int)$id, $nextMonth, $nextYear);
                setFlash('admin_service_message', 'Dịch vụ đang có ' . $using . ' phòng sử dụng. Sẽ bị xóa khi sang tháng ' . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT) . '/' . $nextYear . '. Bạn có thể Hoàn tác trước thời điểm đó.');
                redirectTo('admin-services');
            }
        }
        $redirectParams = [];
        if ((int)($_POST['room_id'] ?? 0) > 0) {
            $redirectParams['room_id'] = (int)$_POST['room_id'];
        }

        try {
            ServiceModel::delete((int)$id);
            setFlash('admin_service_message', 'Đã xóa dịch vụ thành công.');
        } catch (Throwable $exception) {
            setFlash('admin_service_error', $exception->getMessage());
        }

        redirectTo('admin-services', $redirectParams);
    }
/**
     * Gán hoặc gỡ dịch vụ theo phòng từ màn quản lý dịch vụ.
     */
    public function assignServiceToRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-services');
        }
        verify_csrf();

        $roomId = (int)($_POST['room_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $assignmentAction = trim((string)($_POST['assignment_action'] ?? 'assign'));

        if ($roomId <= 0) {
            setFlash('admin_service_error', 'Vui lòng chọn phòng cần gán dịch vụ.');
            redirectTo('admin-services');
        }

        try {
            if ($assignmentAction === 'remove') {
                ServiceModel::removeFromRoom($roomId, $serviceId);
                setFlash('admin_service_message', 'Đã gỡ dịch vụ khỏi phòng.');
            } else {
                $result = ServiceModel::assignToRoom($roomId, $serviceId, $quantity);
                setFlash(
                    'admin_service_message',
                    $result === 'updated'
                        ? 'Dịch vụ đã tồn tại, hệ thống đã cập nhật lại số lượng.'
                        : 'Đã gán dịch vụ cho phòng thành công.'
                );
            }
        } catch (Throwable $exception) {
            setFlash('admin_service_error', $exception->getMessage());
        }

        redirectTo('admin-services', ['room_id' => $roomId]);
    }
/**
     * Tạo mới hoặc cập nhật tiện ích hiển thị ngoài landing page.
     */
    public function saveAmenity()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-settings');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $data = $this->normalizeAmenityInput($_POST);
        $iconKeys = $this->getAllowedAmenityIconKeys();

        if ($id <= 0 && count(AmenityModel::getAll()) >= 10) {
            setFlash('admin_amenity_error', 'Đã đạt giới hạn tối đa 10 tiện ích. Hãy xóa bớt tiện ích cũ trước khi thêm mới.');
            redirectTo('admin-settings');
        }

        if ($id > 0 && !AmenityModel::getById($id)) {
            setFlash('admin_amenity_error', 'Tiện ích cần cập nhật không tồn tại.');
            redirectTo('admin-settings');
        }

        if ($data['title'] === '') {
            setFlash('admin_amenity_error', 'Tên tiện ích là bắt buộc.');
            setFlash('admin_amenity_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-settings');
        }

        if (!in_array($data['icon'], $iconKeys, true)) {
            setFlash('admin_amenity_error', 'Icon tiện ích không hợp lệ.');
            setFlash('admin_amenity_old', array_merge($data, ['id' => $id]));
            redirectTo('admin-settings');
        }

        if (!empty($data['is_active']) && AmenityModel::countActive() >= 8) {
            $existing = $id > 0 ? AmenityModel::getById($id) : null;
            if (!$existing || empty($existing['is_active'])) {
                setFlash('admin_amenity_error', 'Tối đa 8 tiện ích được hiển thị trên website. Hãy ẩn bớt tiện ích khác trước.');
                setFlash('admin_amenity_old', array_merge($data, ['id' => $id]));
                redirectTo('admin-settings');
            }
        }

        $savedId = AmenityModel::save($data, $id > 0 ? $id : null);
        setFlash('admin_amenity_message', $id > 0 ? 'Đã cập nhật tiện ích thành công.' : 'Đã thêm tiện ích mới thành công.');
        redirectTo('admin-settings');
    }
/**
     * Xóa một tiện ích khỏi danh sách quản trị.
     */
    public function deleteAmenity($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-settings');
        }
        verify_csrf();
        $amenity = AmenityModel::getById($id);
        if (!$amenity) {
            setFlash('admin_amenity_error', 'Tiện ích không tồn tại hoặc đã bị xóa trước đó.');
            redirectTo('admin-settings');
        }

        AmenityModel::delete($id);
        setFlash('admin_amenity_message', 'Đã xóa tiện ích thành công.');
        redirectTo('admin-settings');
    }

    /**
     * Lưu thứ tự mới của danh sách tiện ích sau khi kéo thả sắp xếp.
     * Nếu có activate_id: bật hiển thị tiện ích đó (kéo từ danh sách vào bản xem trước).
     */
    public function saveAmenityOrder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-settings');
        }
        verify_csrf();

        $orderedIds = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['ordered_ids'] ?? '')))));
        if (!$orderedIds) {
            setFlash('admin_amenity_error', 'Danh sách thứ tự tiện ích trống.');
            redirectTo('admin-settings');
        }

        $activateId = (int)($_POST['activate_id'] ?? 0);
        $existing = AmenityModel::getAll();
        $existingIds = array_flip(array_map(static fn($item) => (int)$item['id'], $existing));
        $nextOrder = 0;
        $updated = 0;
        foreach ($orderedIds as $amenityId) {
            if (!isset($existingIds[$amenityId])) {
                continue;
            }
            Database::update('amenities', ['sort_order' => $nextOrder], 'id = :id', ['id' => $amenityId]);
            $updated++;
            $nextOrder++;
        }
        if ($activateId > 0 && isset($existingIds[$activateId])) {
            $existingAmenity = AmenityModel::getById($activateId);
            if (empty($existingAmenity['is_active']) && AmenityModel::countActive() >= 8) {
                setFlash('admin_amenity_error', 'Tối đa 8 tiện ích được hiển thị trên website. Hãy gỡ bớt tiện ích khác trước.');
                redirectTo('admin-settings');
            }
            Database::update('amenities', ['is_active' => 1], 'id = :id', ['id' => $activateId]);
        }

        setFlash('admin_amenity_message', $updated > 0 ? 'Đã sắp xếp lại tiện ích thành công.' : 'Không có tiện ích nào được sắp xếp.');
        redirectTo('admin-settings');
    }
/**
     * Bộ icon Material dùng cố định cho tiện ích để admin chọn nhanh và tránh nhập icon sai.
     */
    private function getAmenityIconOptions()
    {
        return [
            ['key' => 'wifi', 'label' => 'Wifi'],
            ['key' => 'security', 'label' => 'An ninh'],
            ['key' => 'local_parking', 'label' => 'Bãi xe'],
            ['key' => 'local_laundry_service', 'label' => 'Giặt sấy'],
            ['key' => 'ac_unit', 'label' => 'Điều hòa'],
            ['key' => 'kitchen', 'label' => 'Bếp chung'],
            ['key' => 'water_heater', 'label' => 'Nóng lạnh'],
            ['key' => 'elevator', 'label' => 'Thang máy'],
            ['key' => 'videocam', 'label' => 'Camera'],
            ['key' => 'fingerprint', 'label' => 'Vân tay'],
            ['key' => 'cleaning_services', 'label' => 'Vệ sinh'],
            ['key' => 'yard', 'label' => 'Sân phơi'],
            ['key' => 'bolt', 'label' => 'Điện ổn định'],
            ['key' => 'apartment', 'label' => 'Tiện ích chung'],
        ];
    }
/**
     * Tạo whitelist icon key để chặn submit icon lạ từ ngoài form.
     */
    private function getAllowedAmenityIconKeys()
    {
        return array_column($this->getAmenityIconOptions(), 'key');
    }
/**
     * Chuẩn hóa dữ liệu tiện ích từ form admin trước khi đưa vào model.
     */
    private function normalizeAmenityInput(array $source)
    {
        return [
            'icon' => trim((string)($source['icon'] ?? 'apartment')),
            'title' => trim((string)($source['title'] ?? '')),
            'description' => trim((string)($source['description'] ?? '')),
            'sort_order' => (int)($source['sort_order'] ?? 0),
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }
/**
     * Trả metadata của từng cách tính để view admin render badge và phần giải thích thống nhất.
     */
    private function getServiceBillingModeOptions()
    {
        return [
            [
                'value' => 'fixed',
                'label' => 'Cố định',
                'badge_class' => 'bg-slate-100 text-slate-700',
                'tooltip' => 'Thu cố định theo chu kỳ, thường dùng cho wifi hoặc phí trọn gói.',
            ],
            [
                'value' => 'per_person',
                'label' => 'Theo người',
                'badge_class' => 'bg-purple-100 text-purple-700',
                'tooltip' => 'Nhân với số người đang ở hoặc người được áp dụng.',
            ],
            [
                'value' => 'per_unit',
                'label' => 'Theo số lượng',
                'badge_class' => 'bg-amber-100 text-amber-700',
                'tooltip' => 'Nhân theo số lượng đăng ký như số xe, số bình, số thiết bị.',
            ],
        ];
    }
/**
     * Trả metadata đối tượng áp dụng để view không phải tự định nghĩa text hiển thị.
     */
    private function getServiceAppliesToOptions()
    {
        return [
            [
                'value' => 'room',
                'label' => 'Theo phòng',
                'tooltip' => 'Một dịch vụ được gán cho cả phòng. Có thể phát sinh số lượng riêng theo từng phòng.',
            ],
            [
                'value' => 'person',
                'label' => 'Theo người',
                'tooltip' => 'Mỗi cư dân tự đăng ký riêng, không ảnh hưởng người ở cùng phòng.',
            ],
        ];
    }
/**
     * Trả whitelist đối tượng áp dụng để chặn submit giá trị lạ.
     */
    private function getAllowedServiceAppliesTo()
    {
        return array_column($this->getServiceAppliesToOptions(), 'value');
    }
/**
     * Chuẩn hóa input dịch vụ từ form admin trước khi chuyển xuống model.
     */
    private function normalizeServiceInput(array $source)
    {
        return [
            'name' => trim((string)($source['name'] ?? '')),
            'price' => (float)($source['price'] ?? 0),
            'unit' => trim((string)($source['unit'] ?? 'tháng')),
            'icon' => trim((string)($source['icon'] ?? 'settings')),
            'description' => trim((string)($source['description'] ?? '')),
            'billing_mode' => trim((string)($source['billing_mode'] ?? 'fixed')),
            'applies_to' => trim((string)($source['applies_to'] ?? 'room')),
            'is_required' => !empty($source['is_required']) ? 1 : 0,
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }

}
