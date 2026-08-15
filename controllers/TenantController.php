<?php
class TenantController {
    /**
     * Lấy thông tin cư dân đang đăng nhập.
     * Nếu session không còn hợp lệ thì tự đưa người dùng về trang đăng nhập.
     * Đồng bộ session room_id với DB để phản ánh thay đổi ngay khi admin duyệt yêu cầu.
     */
    private function getAuthenticatedTenant() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = UserModel::getById($userId);

        if (!$user) {
            session_destroy();
            redirectTo('login');
        }

        // Đồng bộ session room_id với DB (khi admin duyệt yêu cầu thuê/ở ghép)
        if ((int)($_SESSION['room_id'] ?? -1) !== (int)($user['room_id'] ?? 0)) {
            $_SESSION['room_id'] = (int)($user['room_id'] ?? 0);
        }

        return $user;
    }

    /**
     * Kiểm tra chuỗi ngày có đúng định dạng `Y-m-d` hay không.
     */
    private function isValidDateInput($value) {
        if ($value === '') {
            return true;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public function dashboard() {
        $user = $this->getAuthenticatedTenant();
        
        if ($user['room_id']) {
            $room = RoomModel::getById($user['room_id']);
            $roomServices = ServiceModel::getByRoom($user['room_id']);
            $services = $roomServices;
            $serviceCost = ServiceModel::getTotalServiceCost($user['room_id']);
            $totalBill = $room['price'] + $serviceCost;
        } else {
            $room = null;
            $roomServices = [];
            $services = [];
            $serviceCost = 0;
            $totalBill = 0;
        }
        
        $roomExtra = null;
if ($room) {
    $floorRow = FloorModel::getById((int)($room['floor_id'] ?? 0));
    $areaRow = AreaModel::getById((int)($floorRow['area_id'] ?? 0));
    $occupantList = array_values(array_filter(UserModel::getAll(), static function ($u) use ($room) {
        return (int)($u['role'] ?? -1) === 0 && (int)($u['room_id'] ?? 0) === (int)$room['id'];
    }));
    $occupantsNow = count($occupantList);
    $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
    $amenityLabels = [];
    $rawAm = trim((string)($room['amenities'] ?? ''));
    if ($rawAm !== '') {
        $dec = json_decode($rawAm, true);
        $parts = is_array($dec) ? $dec : explode(',', $rawAm);
        foreach ($parts as $p) { $p = trim((string)$p); if ($p !== '') $amenityLabels[] = $p; }
    }
    $roomExtra = [
        'floor_name' => (string)($floorRow['name'] ?? ((int)($room['floor'] ?? 0) > 0 ? 'Tầng ' . ($room['floor'] ?? '') : 'Tầng 1')),
        'area_name' => (string)($areaRow['name'] ?? ($room['building_name'] ?? '')),
        'occupants' => $occupantsNow,
        'max' => $maxOcc,
        'free' => max(0, $maxOcc - $occupantsNow),
        'can_add' => $occupantsNow < $maxOcc,
        'amenities' => $amenityLabels, 'occupants_list' => $occupantList,
    ];
}
$pageTitle = 'Thông tin phòng - NhaTroA';
        require_once BASE_PATH . 'views/tenant/dashboard.php';
    }
    
    public function services() {
        $user = $this->getAuthenticatedTenant();
        
        if (!$user['room_id']) {
            redirectTo('tenant');
        }
        
        $room = RoomModel::getById($user['room_id']);
        $roomServices = $room ? ServiceModel::getServicesForRoom((int)$room['id']) : [];
        $availableServices = ServiceModel::getAvailableServicesForTenant((int)($user['id'] ?? 0), (int)($user['room_id'] ?? 0));
        $tenantServiceMessage = pullFlash('tenant_service_message', '');
        $tenantServiceError = pullFlash('tenant_service_error', '');
        
        $pageTitle = 'Dịch vụ - NhaTroA';
        require_once BASE_PATH . 'views/tenant/services.php';
    }

    /**
     * Tenant xem chỉ số điện/nước của phòng mình theo từng tháng.
     */
    public function viewMeterReadings() {
        $user = $this->getAuthenticatedTenant();
        $period = MeterReadingModel::normalizePeriod($_GET['month'] ?? null, $_GET['year'] ?? null);

        if (!empty($user['room_id'])) {
            $meterSummary = MeterReadingModel::getTenantMonthlySummary((int)$user['room_id'], $period['month'], $period['year']);
        } else {
            $meterSummary = [
                'period' => $period,
                'room' => null,
                'items' => [],
                'history' => [],
                'has_readings' => false,
            ];
        }

        $pageTitle = 'Chỉ số điện nước - NhaTroA';
        require_once BASE_PATH . 'views/tenant/meter.php';
    }

    /**
     * Tenant xem hóa đơn tháng của phòng mình từ snapshot `payment_items`.
     */
    public function viewInvoice() {
        $user = $this->getAuthenticatedTenant();
        $period = PaymentModel::normalizePeriod($_GET['month'] ?? null, $_GET['year'] ?? null);
        $tenantInvoiceMessage = pullFlash('tenant_invoice_message', '');
        $tenantInvoiceError = pullFlash('tenant_invoice_error', '');
        $invoice = !empty($user['room_id'])
            ? PaymentModel::getInvoiceForRoomAndPeriod((int)$user['room_id'], $period['month'], $period['year'])
            : null;

        $pageTitle = 'Hóa đơn tháng - NhaTroA';
        require_once BASE_PATH . 'views/tenant/invoice.php';
    }

    /**
     * Tenant xem danh sách đầy đủ thông báo của mình.
     */
    public function viewNotifications() {
        $user = $this->getAuthenticatedTenant();
        $tenantNotificationMessage = pullFlash('tenant_notification_message', '');
        $tenantNotificationError = pullFlash('tenant_notification_error', '');
        $selectedNotificationId = (int)($_GET['notification_id'] ?? 0);
        $selectedNotification = null;

        if ($selectedNotificationId > 0) {
            try {
                $selectedNotification = NotificationModel::getByIdForUser($selectedNotificationId, (int)($user['id'] ?? 0));
                if ($selectedNotification && (int)($selectedNotification['is_read'] ?? 0) === 0) {
                    NotificationModel::markAsRead($selectedNotificationId, (int)($user['id'] ?? 0));
                    $selectedNotification = NotificationModel::getByIdForUser($selectedNotificationId, (int)($user['id'] ?? 0));
                }
            } catch (Throwable $exception) {
                $tenantNotificationError = $exception->getMessage();
            }
        }

        $selectedCategory = trim((string)($_GET['category'] ?? ''));
        $tenantNotifications = NotificationModel::getForUser((int)($user['id'] ?? 0), [
            'order' => 'asc',
            'category' => $selectedCategory,
        ]);
        $tenantNotificationCategories = NotificationModel::getTenantCategories();

        $pageTitle = 'Thông báo - NhaTroA';
        require_once BASE_PATH . 'views/tenant/notifications.php';
    }
    
    public function profile() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->getAuthenticatedTenant();
        $success = pullFlash('tenant_profile_message', '');
        $error = pullFlash('tenant_profile_error', '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
            $data = [
                'full_name' => trim((string)($_POST['full_name'] ?? '')),
                'phone' => trim((string)($_POST['phone'] ?? '')),
            ];
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');

            if ($data['full_name'] === '') {
                $error = 'Họ và tên là bắt buộc.';
            } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($newPassword !== '' && $currentPassword === '') {
                $error = 'Vui lòng nhập mật khẩu hiện tại để xác nhận đổi mật khẩu.';
            } elseif ($newPassword !== '' && !password_verify($currentPassword, (string)($user['password'] ?? ''))) {
                $error = 'Mật khẩu hiện tại không đúng.';
            }

            if (empty($error) && $newPassword !== '') {
                $data['password'] = $newPassword;
            }

            if (empty($error)) {
                UserModel::updateProfile($userId, $data);
                $_SESSION['full_name'] = $data['full_name'];
                setFlash('tenant_profile_message', 'Cập nhật thành công.');
                redirectTo('tenant-profile');
            }

            $user = array_merge($user, $data);
        }
        
        $pageTitle = 'Hồ sơ cá nhân - NhaTroA';
        require_once BASE_PATH . 'views/tenant/profile.php';
    }

    /**
     * Tenant khai báo và xem lại thông tin phục vụ hợp đồng thuê.
     * Dữ liệu nhạy cảm được mã hóa AES trong `UserModel` trước khi lưu DB.
     */
    public function contract() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->getAuthenticatedTenant();
        $success = pullFlash('tenant_contract_message', '');
        $error = pullFlash('tenant_contract_error', '');
        $activeContract = ContractModel::getActiveByUserId($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
            $data = [
                'date_of_birth' => trim((string)($_POST['date_of_birth'] ?? '')),
                'permanent_address' => trim((string)($_POST['permanent_address'] ?? '')),
                'identity_number' => preg_replace('/\s+/', '', trim((string)($_POST['identity_number'] ?? ''))),
                'identity_issue_date' => trim((string)($_POST['identity_issue_date'] ?? '')),
                'identity_issue_place' => trim((string)($_POST['identity_issue_place'] ?? '')),
            ];

            if ($data['identity_number'] !== '' && !preg_match('/^(?:\d{9}|\d{12})$/', $data['identity_number'])) {
                $error = 'CCCD/CMND phải gồm đúng 9 hoặc 12 chữ số.';
            } elseif (!$this->isValidDateInput($data['date_of_birth'])) {
                $error = 'Ngày sinh không đúng định dạng.';
            } elseif (!$this->isValidDateInput($data['identity_issue_date'])) {
                $error = 'Ngày cấp CCCD/CMND không đúng định dạng.';
            }

            if (empty($error)) {
                UserModel::updateContractInfo($userId, $data);
                setFlash('tenant_contract_message', 'Đã lưu thông tin hợp đồng.');
                redirectTo('tenant-contract');
            }

            $user = array_merge($user, $data);
        }

        $pageTitle = 'Thông tin hợp đồng - NhaTroA';
        require_once BASE_PATH . 'views/tenant/contract.php';
    }
    
    public function registerService() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('tenant-services');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        if (!$user || !$user['room_id']) {
            setFlash('tenant_service_error', 'Bạn cần được gán phòng trước khi đăng ký dịch vụ cá nhân.');
            redirectTo('tenant-services');
        }

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $serviceAction = trim((string)($_POST['service_action'] ?? 'register'));

        try {
            $service = ServiceModel::getById($serviceId);
            if (!$service) {
                throw new RuntimeException('Dịch vụ không tồn tại.');
            }

            if ($serviceAction === 'cancel') {
                ServiceModel::removeFromRoom((int)($user['room_id'] ?? 0), $serviceId);
                setFlash('tenant_service_message', 'Đã hủy dịch vụ khỏi phòng.');
            } else {
                // Dịch vụ tính theo chỉ số: số lượng luôn là 1, chỉ số sẽ nhập khi lập hóa đơn.
                if (($service['billing_mode'] ?? '') === 'meter') {
                    $quantity = 1;
                }
                if ($quantity <= 0) {
                    throw new RuntimeException('Số lượng đăng ký phải lớn hơn 0.');
                }

                $result = ServiceModel::assignToRoom((int)($user['room_id'] ?? 0), $serviceId, $quantity);
                setFlash(
                    'tenant_service_message',
                    $result === 'updated'
                        ? 'Dịch vụ phòng đã đăng ký trước đó, hệ thống đã cập nhật lại số lượng.'
                        : 'Đăng ký dịch vụ cho phòng thành công.'
                );
            }
        } catch (Throwable $exception) {
            setFlash('tenant_service_error', $exception->getMessage());
        }

        redirectTo('tenant-services');
    }

    /**
     * Tenant tự chốt thanh toán hóa đơn của phòng mình.
     */
    public function payInvoice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('tenant-invoice');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $period = PaymentModel::normalizePeriod($_POST['month'] ?? null, $_POST['year'] ?? null);
        $paymentId = (int)($_POST['payment_id'] ?? 0);

        if (empty($user['room_id'])) {
            setFlash('tenant_invoice_error', 'Bạn chưa được gán phòng nên chưa thể thanh toán hóa đơn.');
            redirectTo('tenant-invoice', ['month' => $period['month'], 'year' => $period['year']]);
        }

        try {
            PaymentModel::payInvoiceAsTenant($paymentId, (int)($user['id'] ?? 0), (int)($user['room_id'] ?? 0));
            setFlash('tenant_invoice_message', 'Đã ghi nhận thanh toán hóa đơn thành công.');
        } catch (Throwable $exception) {
            setFlash('tenant_invoice_error', $exception->getMessage());
        }

        redirectTo('tenant-invoice', ['month' => $period['month'], 'year' => $period['year']]);
    }

    /**
     * Tenant đánh dấu một hoặc tất cả thông báo là đã đọc.
     */
    public function markNotificationRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('tenant-notifications');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        $markAll = !empty($_POST['mark_all']);
        $redirectPage = trim((string)($_POST['redirect_page'] ?? 'tenant-notifications'));
        $redirectAllowed = ['tenant-notifications', 'tenant', 'tenant-services', 'tenant-meter', 'tenant-invoice', 'tenant-profile', 'tenant-contract'];
        if (!in_array($redirectPage, $redirectAllowed, true)) {
            $redirectPage = 'tenant-notifications';
        }

        try {
            if ($markAll) {
                NotificationModel::markAllAsRead((int)($user['id'] ?? 0));
                setFlash('tenant_notification_message', 'Đã đánh dấu tất cả thông báo là đã đọc.');
                redirectTo($redirectPage);
            }

            if ($notificationId <= 0) {
                throw new RuntimeException('Thông báo cần đánh dấu không hợp lệ.');
            }

            NotificationModel::markAsRead($notificationId, (int)($user['id'] ?? 0));
            if ($redirectPage === 'tenant-notifications') {
                redirectTo('tenant-notifications', ['notification_id' => $notificationId]);
            }

            redirectTo($redirectPage);
        } catch (Throwable $exception) {
            setFlash('tenant_notification_error', $exception->getMessage());
            redirectTo('tenant-notifications');
        }
    }
    
    /**
     * Tenant gửi đánh giá mới cho phòng đủ điều kiện.
     */
    public function addComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('rooms');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $roomId = (int)($_POST['room_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));

        try {
            $comment = CommentModel::create((int)($user['id'] ?? 0), $roomId, $rating, $content);
            $message = 'Đã gửi đánh giá thành công.';

            if ((int)($comment['is_spam'] ?? 0) === 1) {
                $message = 'Đánh giá đã lưu nhưng đang ở chế độ riêng tư vì nội dung bị đánh dấu spam.';
            } elseif ((int)($comment['status'] ?? 0) === 0) {
                $message = 'Đánh giá đã lưu nhưng đang tạm ẩn do nội dung cần admin kiểm tra thêm.';
            }

            if (!empty($comment['moderation_notice'])) {
                $message .= ' ' . trim((string)$comment['moderation_notice']);
            }

            setFlash('comment_message', $message);
        } catch (Throwable $exception) {
            setFlash('comment_error', $exception->getMessage());
        }

        redirectTo('detail', ['id' => $roomId]);
    }

    /**
     * Tenant sửa đánh giá của mình trong thời hạn cho phép.
     */
    public function editComment() {
        $user = $this->getAuthenticatedTenant();
        $commentId = (int)($_POST['comment_id'] ?? $_GET['id'] ?? 0);
        $permission = CommentModel::validateOwnerAction($commentId, (int)($user['id'] ?? 0));
        $comment = $permission['comment'] ?? null;
        $roomId = (int)($comment['room_id'] ?? ($_POST['room_id'] ?? 0));

        if (!$permission['allowed']) {
            setFlash('comment_error', $permission['message']);
            if ($roomId > 0) {
                redirectTo('detail', ['id' => $roomId]);
            }

            redirectTo('tenant');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
            try {
                $updatedComment = CommentModel::updateByOwner(
                    $commentId,
                    (int)($user['id'] ?? 0),
                    (int)($_POST['rating'] ?? 0),
                    trim((string)($_POST['content'] ?? ''))
                );

                $message = 'Đã cập nhật đánh giá thành công.';
                if ((int)($updatedComment['is_spam'] ?? 0) === 1) {
                    $message = 'Đánh giá đã được cập nhật nhưng chỉ mình bạn thấy vì nội dung bị đánh dấu spam.';
                } elseif ((int)($updatedComment['status'] ?? 0) === 0) {
                    $message = 'Đánh giá đã được cập nhật nhưng đang tạm ẩn do nội dung cần admin kiểm tra thêm.';
                }

                if (!empty($updatedComment['moderation_notice'])) {
                    $message .= ' ' . trim((string)$updatedComment['moderation_notice']);
                }

                setFlash('comment_message', $message);
                redirectTo('detail', ['id' => (int)($updatedComment['room_id'] ?? $roomId)]);
            } catch (Throwable $exception) {
                setFlash('tenant_comment_error', $exception->getMessage());
                redirectTo('tenant-edit-comment', ['id' => $commentId]);
            }
        }

        $commentWindow = $permission['meta'] ?? [];
        $tenantCommentMessage = pullFlash('tenant_comment_message', '');
        $tenantCommentError = pullFlash('tenant_comment_error', '');
        $pageTitle = 'Sửa đánh giá - NhaTroA';
        require_once BASE_PATH . 'views/tenant/edit_comment.php';
    }

    /**
     * Tenant xóa đánh giá của mình trong 24h đầu.
     */
    public function deleteComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('tenant');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);

        try {
            CommentModel::deleteByOwner($commentId, (int)($user['id'] ?? 0));
            setFlash('comment_message', 'Đã xóa đánh giá thành công.');
        } catch (Throwable $exception) {
            setFlash('comment_error', $exception->getMessage());
        }

        if ($roomId > 0) {
            redirectTo('detail', ['id' => $roomId]);
        }

        redirectTo('tenant');
    }

    /**
     * Tenant báo cáo một đánh giá công khai với lý do cụ thể.
     */
    public function reportComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('rooms');
        }
    verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));

        try {
            CommentReportModel::create($commentId, (int)($user['id'] ?? 0), $reason);
            setFlash('comment_message', 'Đã gửi báo cáo đánh giá. Admin sẽ kiểm tra sớm.');
        } catch (Throwable $exception) {
            setFlash('comment_error', $exception->getMessage());
        }

        if ($roomId > 0) {
            redirectTo('detail', ['id' => $roomId]);
        }

        redirectTo('rooms');
    }

    /**
     * Tìm người A (theo SĐT/email) để gửi yêu cầu ở ghép.
     * Chỉ trả về người A đang có phòng còn chỗ trống.
     */
    private function searchHostCandidates($query) {
        $q = trim((string)$query);
        if ($q === '') { return []; }
        $qPhone = preg_replace('/\s+/', '', $q);
        $qEmail = mb_strtolower($q);
        $results = [];
        foreach (UserModel::getAll() as $candidate) {
            if ((int)($candidate['role'] ?? 1) !== 0 || empty($candidate['room_id'])) { continue; }
            $email = mb_strtolower(trim((string)($candidate['email'] ?? '')));
            $phone = preg_replace('/\s+/', '', (string)($candidate['phone'] ?? ''));
            $matchEmail = $email !== '' && $email === $qEmail;
            $matchPhone = $phone !== '' && $phone === $qPhone;
            if (!$matchEmail && !$matchPhone) { continue; }

            $room = RoomModel::getById((int)$candidate['room_id']);
            if (!$room || (string)($room['status'] ?? '') === 'maintenance') { continue; }
            $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
            $currentOcc = RoomModel::countOccupants((int)$room['id']);
            if ($currentOcc >= $maxOcc) { continue; }

            $results[] = [
                'id' => (int)$candidate['id'],
                'name' => (string)($candidate['full_name'] ?? ''),
                'room_name' => (string)($room['name'] ?? ''),
                'occupants' => $currentOcc,
                'max_occupancy' => $maxOcc,
            ];
        }
        return $results;
    }

    /**
     * Trang "Ở ghép": 
     * - Người A (có phòng) nhập SĐT người B để mời ở ghép
     * - Nếu B đã có tài khoản, hiện thông tin B để xác nhận
     * - Gửi yêu cầu lên admin duyệt
     * - Người A xem danh sách yêu cầu đã gửi
     */
    public function roommate() {
        $user = $this->getAuthenticatedTenant();
        $message = pullFlash('roommate_message', '');
        $error = pullFlash('roommate_error', '');

        if (empty($user['room_id'])) {
            setFlash('roommate_error', 'Bạn cần có phòng mới có thể mời người ở ghép.');
            redirectTo('tenant');
        }

        $myRoom = RoomModel::getById((int)$user['room_id']);
        if (!$myRoom) {
            setFlash('roommate_error', 'Phòng không tồn tại.');
            redirectTo('tenant');
        }
        $myRoom['occupants'] = RoomModel::countOccupants((int)$myRoom['id']);

        $maxOcc = max(1, (int)($myRoom['max_occupancy'] ?? 1));
        $currentOcc = RoomModel::countOccupants((int)$myRoom['id']);
        if ($currentOcc >= $maxOcc) {
            setFlash('roommate_error', 'Phòng đã đủ người, không thể mời thêm.');
            redirectTo('tenant');
        }

        // Lấy danh sách yêu cầu ở ghép đã gửi của người A (host)
        $myRequests = RoommateRequestModel::getByHost((int)$user['id']);
        
        $searchQuery = trim((string)($_GET['q'] ?? ''));
        $inviteCandidate = null;
        if ($searchQuery !== '') {
            $inviteCandidate = $this->findInviteCandidate($searchQuery, $user['id']);
        }

        $pageTitle = 'Mời ở ghép - NhaTroA';
        require_once BASE_PATH . 'views/tenant/invite_roommate.php';
    }

    /**
     * Tìm người B theo SĐT/email để mời ở ghép.
     * Chỉ trả về người B CHƯA CÓ PHÒNG (room_id IS NULL).
     */
    private function findInviteCandidate($query, $hostUserId) {
        $q = trim((string)$query);
        if ($q === '') { return null; }
        
        $qPhone = preg_replace('/\s+/', '', $q);
        $qEmail = mb_strtolower($q);
        
        foreach (UserModel::getAll() as $candidate) {
            if ((int)($candidate['role'] ?? 1) !== 0) { continue; } // chỉ tenant
            if (!empty($candidate['room_id'])) { continue; } // đã có phòng
            if ((int)$candidate['id'] === $hostUserId) { continue; } // không tự mời mình

            $email = mb_strtolower(trim((string)($candidate['email'] ?? '')));
            $phone = preg_replace('/\s+/', '', (string)($candidate['phone'] ?? ''));
            $matchEmail = $email !== '' && $email === $qEmail;
            $matchPhone = $phone !== '' && $phone === $qPhone;
            
            if (!$matchEmail && !$matchPhone) { continue; }

            return [
                'id' => (int)$candidate['id'],
                'name' => (string)($candidate['full_name'] ?? ''),
                'email' => $email,
                'phone' => $phone,
            ];
        }
        return null;
    }

    public function sendRoommateRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('tenant-roommate'); }
        verify_csrf();
        $user = $this->getAuthenticatedTenant();

        if (empty($user['room_id'])) {
            setFlash('roommate_error', 'Bạn cần có phòng mới có thể mời người ở ghép.');
            redirectTo('tenant-roommate');
        }

        $myRoom = RoomModel::getById((int)$user['room_id']);
        if (!$myRoom) {
            setFlash('roommate_error', 'Phòng không tồn tại.');
            redirectTo('tenant-roommate');
        }

        $maxOcc = max(1, (int)($myRoom['max_occupancy'] ?? 1));
        $currentOcc = RoomModel::countOccupants((int)$myRoom['id']);
        if ($currentOcc >= $maxOcc) {
            setFlash('roommate_error', 'Phòng đã đủ người, không thể mời thêm.');
            redirectTo('tenant-roommate');
        }

        // Kiểm tra xem người A đã có yêu cầu pending nào chưa (yêu cầu do A gửi đi)
        foreach (RoommateRequestModel::getByHost((int)$user['id']) as $mr) {
            if ((string)($mr['status'] ?? '') === 'pending_admin') {
                setFlash('roommate_error', 'Bạn đang có yêu cầu mời ở ghép chờ admin duyệt.');
                redirectTo('tenant-roommate');
            }
        }

        $guestId = (int)($_POST['guest_user_id'] ?? 0);
        $guest = UserModel::getById($guestId);
        
        if (!$guest || (int)($guest['role'] ?? 1) !== 0 || !empty($guest['room_id'])) {
            setFlash('roommate_error', 'Người được mời không hợp lệ hoặc đã có phòng.');
            redirectTo('tenant-roommate');
        }
        
        if ((int)$guest['id'] === (int)$user['id']) {
            setFlash('roommate_error', 'Không thể tự mời chính mình.');
            redirectTo('tenant-roommate');
        }

        $room = RoomModel::getById((int)$user['room_id']);
        if (!$room) {
            setFlash('roommate_error', 'Phòng không tồn tại.');
            redirectTo('tenant-roommate');
        }

        // Tạo yêu cầu ở ghép - host_user_id = người A, requester_id = người B
        // Trạng thái: pending_admin (chờ admin duyệt)
        RoommateRequestModel::create([
            'requester_id' => (int)$guest['id'],      // người B
            'host_user_id' => (int)$user['id'],       // người A
            'room_id' => (int)$room['id'],
            'gender' => trim((string)($_POST['gender'] ?? 'other')),
            'relationship' => trim((string)($_POST['relationship'] ?? '')),
            'status' => 'pending_admin', // chờ admin duyệt
        ]);

        // Thông báo cho admin
        foreach (UserModel::getAll() as $admin) {
            if ((int)($admin['role'] ?? 1) === 1) {
                NotificationModel::create([
                    'user_id' => (int)$admin['id'],
                    'type' => 'general',
                    'title' => 'Yêu cầu ở ghép mới',
                    'content' => ($user['full_name'] ?? '') . ' mời ' . ($guest['full_name'] ?? '') . ' ở ghép tại phòng ' . ($room['name'] ?? '') . '. Cần admin duyệt.',
                ]);
            }
        }

        // Thông báo cho người B
        NotificationModel::create([
            'user_id' => (int)$guest['id'],
            'type' => 'general',
            'title' => 'Lời mời ở ghép',
            'content' => ($user['full_name'] ?? '') . ' đã mời bạn ở ghép tại phòng ' . ($room['name'] ?? '') . '. Yêu cầu đang chờ admin duyệt.',
        ]);

        setFlash('roommate_message', 'Đã gửi yêu cầu mời ' . ($guest['full_name'] ?? '') . ' ở ghép. Đang chờ admin duyệt.');
        redirectTo('tenant-roommate');
    }

    /**
     * Người A hủy yêu cầu mời ở ghép (chỉ khi chưa được duyệt).
     */
    public function cancelRoommateRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('tenant-roommate'); }
        verify_csrf();
        $user = $this->getAuthenticatedTenant();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RoommateRequestModel::getById($requestId);

        if (!$request || (int)$request['host_user_id'] !== (int)$user['id'] || (string)$request['status'] !== 'pending_admin') {
            setFlash('roommate_error', 'Yêu cầu không hợp lệ hoặc đã được xử lý.');
            redirectTo('tenant-roommate');
        }

        RoommateRequestModel::setStatus($requestId, 'cancelled');
        $guest = UserModel::getById((int)$request['requester_id']);
        if ($guest) {
            NotificationModel::create([
                'user_id' => (int)$guest['id'],
                'type' => 'general',
                'title' => 'Yêu cầu ở ghép bị hủy',
                'content' => ($user['full_name'] ?? '') . ' đã hủy lời mời ở ghép.',
            ]);
        }
        setFlash('roommate_message', 'Đã hủy yêu cầu mời ở ghép.');
        redirectTo('tenant-roommate');
    }

    /**
     * Trang bảo trì của tenant: xem đề xuất đang chờ/đang diễn ra cho phòng của mình.
     * Chạy lazy date-trigger để kích hoạt đề xuất đã tới hạn.
     */
    public function maintenance() {
        $user = $this->getAuthenticatedTenant();
        MaintenanceRequestModel::activateDue();
        $message = pullFlash('maintenance_message', '');
        $error = pullFlash('maintenance_error', '');
        $pendingRequest = null;
        $activeRequest = null;
        if (!empty($user['room_id'])) {
            $pendingRequest = MaintenanceRequestModel::getPendingByRoom((int)$user['room_id']);
            $activeRequest = MaintenanceRequestModel::getActiveByRoom((int)$user['room_id']);
        }
        $supportPhone = RoomModel::getSetting('contact_phone', '');
        $pageTitle = 'Bảo trì - NhaTroA';
        require_once BASE_PATH . 'views/tenant/maintenance.php';
    }

    /**
     * Tenant từ chối đề xuất bảo trì (im lặng = đồng ý, chỉ cần 1 người từ chối là hủy).
     */
    public function rejectMaintenance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('tenant-maintenance'); }
        verify_csrf();
        $user = $this->getAuthenticatedTenant();
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = MaintenanceRequestModel::getById($requestId);
        if (!$request || (int)$request['room_id'] !== (int)$user['room_id'] || (string)$request['status'] !== 'pending') {
            setFlash('maintenance_error', 'Đề xuất bảo trì không hợp lệ hoặc đã được xử lý.');
            redirectTo('tenant-maintenance');
        }
        MaintenanceRequestModel::rejectByTenant($requestId, (int)$user['id']);
        foreach (UserModel::getAll() as $admin) {
            if ((int)($admin['role'] ?? 1) === 1) {
                NotificationModel::create([
                    'user_id' => (int)$admin['id'],
                    'type' => 'general',
                    'title' => 'Đề xuất bảo trì bị từ chối',
                    'content' => ($user['full_name'] ?? '') . ' đã từ chối đề xuất bảo trì. Phòng giữ trạng thái đang thuê.',
                ]);
            }
        }
        setFlash('maintenance_message', 'Đã từ chối đề xuất bảo trì. Phòng sẽ giữ trạng thái đang thuê.');
        redirectTo('tenant-maintenance');
    }

    /**
     * Trang gửi Phản ánh cho chủ trọ.
     */
    public function feedback() {
        $user = $this->getAuthenticatedTenant();
        $myFeedbacks = FeedbackModel::getForUser((int)$user['id']);
        $message = pullFlash('feedback_message', '');
        $error = pullFlash('feedback_error', '');
        $pageTitle = 'Gửi Phản ánh - NhaTroA';
        require_once BASE_PATH . 'views/tenant/feedback.php';
    }

    /**
     * Tenant gửi Phản ánh mới (không cần điều kiện ở tối thiểu, ảnh tùy chọn).
     */
    public function sendFeedback() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('tenant-feedback');
        }
        verify_csrf();

        $user = $this->getAuthenticatedTenant();
        $subject = trim((string)($_POST['subject'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));

        try {
            $imageUrl = $this->handleFeedbackImageUpload();
            if ($imageUrl === false) {
                redirectTo('tenant-feedback');
            }
            FeedbackModel::create((int)$user['id'], $subject, $content, $imageUrl);
            setFlash('feedback_message', 'Đã gửi phản ánh thành công. Chủ trọ sẽ xem và xử lý sớm nhất.');
        } catch (Throwable $exception) {
            setFlash('feedback_error', $exception->getMessage());
        }

        redirectTo('tenant-feedback');
    }

    /**
     * Xử lý upload ảnh minh họa phản ánh (tùy chọn). Trả về URL, null nếu không có file,
     * false nếu file không hợp lệ.
     */
    private function handleFeedbackImageUpload() {
        $file = $_FILES['feedback_image'] ?? null;
        if (empty($file)) {
            return null;
        }
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            setFlash('feedback_error', 'Tải ảnh lên không thành công. Vui lòng thử lại.');
            return false;
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            setFlash('feedback_error', 'Ảnh minh họa vượt quá 5MB.');
            return false;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            setFlash('feedback_error', 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
            return false;
        }

        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_feedback';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $fileName = 'feedback-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            setFlash('feedback_error', 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.');
            return false;
        }

        return BASE_URL . '.uploads/image_feedback/' . $fileName;
    }
}
