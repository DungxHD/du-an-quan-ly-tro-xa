<?php
class TenantController {
    /**
     * Lấy thông tin cư dân đang đăng nhập.
     * Nếu session không còn hợp lệ thì tự đưa người dùng về trang đăng nhập.
     */
    private function getAuthenticatedTenant() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = UserModel::getById($userId);

        if (!$user) {
            session_destroy();
            redirectTo('login');
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
            $services = ServiceModel::getByRoom($user['room_id']);
            $serviceCost = ServiceModel::getTotalServiceCost($user['room_id']);
            $totalBill = $room['price'] + $serviceCost;
        } else {
            $room = null;
            $services = [];
            $serviceCost = 0;
            $totalBill = 0;
        }
        
        $pageTitle = 'Dashboard - NhaTroA';
        require_once BASE_PATH . 'views/tenant/dashboard.php';
    }
    
    public function services() {
        $user = $this->getAuthenticatedTenant();
        
        if (!$user['room_id']) {
            redirectTo('tenant');
        }
        
        $room = RoomModel::getById($user['room_id']);
        $myServices = ServiceModel::getByUser((int)($user['id'] ?? 0));
        $availableServices = ServiceModel::getAvailablePersonalServices((int)($user['id'] ?? 0));
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

        $tenantNotifications = NotificationModel::getForUser((int)($user['id'] ?? 0), [
            'order' => 'asc',
        ]);

        $pageTitle = 'Thông báo - NhaTroA';
        require_once BASE_PATH . 'views/tenant/notifications.php';
    }
    
    public function profile() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->getAuthenticatedTenant();
        $success = pullFlash('tenant_profile_message', '');
        $error = pullFlash('tenant_profile_error', '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $user = $this->getAuthenticatedTenant();
        if (!$user || !$user['room_id']) {
            setFlash('tenant_service_error', 'Bạn cần được gán phòng trước khi đăng ký dịch vụ cá nhân.');
            redirectTo('tenant-services');
        }

        $serviceId = (int)($_POST['service_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $serviceAction = trim((string)($_POST['service_action'] ?? 'register'));

        try {
            if ($serviceAction === 'cancel') {
                ServiceModel::unregisterForUser((int)($user['id'] ?? 0), $serviceId);
                setFlash('tenant_service_message', 'Đã hủy đăng ký dịch vụ cá nhân.');
            } else {
                if ($quantity <= 0) {
                    throw new RuntimeException('Số lượng đăng ký phải lớn hơn 0.');
                }

                $result = ServiceModel::registerForUser((int)($user['id'] ?? 0), $serviceId, $quantity);
                setFlash(
                    'tenant_service_message',
                    $result === 'updated'
                        ? 'Dịch vụ đã đăng ký trước đó, hệ thống đã cập nhật lại số lượng.'
                        : 'Đăng ký dịch vụ thành công.'
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
}
