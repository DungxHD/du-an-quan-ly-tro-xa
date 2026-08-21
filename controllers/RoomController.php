<?php
/**
 * RoomController - Trang công khai liên quan phòng: chi tiết, yêu cầu thuê, hủy yêu cầu
 */
class RoomController extends BaseController
{
    // ==========================================
    // PUBLIC ACTIONS
    // ==========================================

    /**
     * Trang chi tiết phòng công khai
     * - Admin & tenant của phòng xem được mọi trạng thái
     * - Khách chỉ xem phòng available
     * - Tự động tăng lượt xem, load comment bundle
     */
    public function detail(int $id): void
    {
        $viewerUserId = (int)($_SESSION['user_id'] ?? 0);
        $viewerRole   = (int)($_SESSION['role'] ?? -1);

        // Admin & tenant của phòng bypass public_only
        $canBypass = $viewerRole === 1
            || ($viewerRole === 0 && CommentModel::canUserAccessRoomDetail($viewerUserId, $id));

        $room = RoomModel::getById($id, ['public_only' => !$canBypass]);
        if (!$room) {
            header('Location: ' . BASE_URL . '?page=home');
            exit;
        }

        RoomModel::incrementViews($id);
        $room['views'] = (int)($room['views'] ?? 0) + 1;

        $commentBundle     = CommentModel::getRoomDetailComments($id, $viewerUserId);
        $commentEligibility = $viewerRole === 0
            ? CommentModel::validateCreatePermission($viewerUserId, $id)
            : ['allowed' => false, 'message' => ''];

        $commentMessage = pullFlash('comment_message', '');
        $commentError   = pullFlash('comment_error', '');
        $commentWarning = pullFlash('comment_warning', '');
        $pageTitle      = $room['name'] . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic(
            'views/pages/detail.php',
            compact('room', 'commentBundle', 'commentEligibility', 'commentMessage', 'commentError', 'commentWarning'),
            'detail',
            $pageTitle
        );
    }

    /**
     * Trang gửi yêu cầu thuê phòng (tenant đã login, chưa có phòng)
     */
    public function requestRent(int $id): void
    {
        $this->requireTenant();
        $userId = (int)$_SESSION['user_id'];
        $room   = RoomModel::getById($id);
        if (!$room) redirectTo('rooms');

        $pendingRequest = RentalRequestModel::getPendingByUser($userId);
        $message = pullFlash('rent_message', '');
        $error   = pullFlash('rent_error', '');
        $pageTitle = 'Yêu cầu thuê ' . ($room['name'] ?? '') . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic(
            'views/pages/request_rent.php',
            compact('room', 'pendingRequest', 'message', 'error'),
            'detail',
            $pageTitle
        );
    }

    /**
     * Xử lý POST gửi yêu cầu thuê
     * - Validate room available, user chưa có phòng, chưa có pending request
     * - Validate move_in_date (hôm nay -> +30 ngày)
     * - Tạo request, notify admin
     */
    public function submitRentRequest(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('request-rent', ['id' => $id]);
        verify_csrf();
        $this->requireTenant();

        $userId = (int)$_SESSION['user_id'];
        $room   = RoomModel::getById($id);
        if (!$room || ($room['status'] ?? '') !== 'available') {
            setFlash('rent_error', 'Phòng này hiện không còn trống để gửi yêu cầu thuê.');
            redirectTo('detail', ['id' => $id]);
        }

        // User đã có phòng -> không được thuê thêm
        $currentUser = UserModel::getById($userId);
        if (!empty($currentUser['room_id'])) {
            setFlash('rent_error', 'Tài khoản của bạn đang thuê phòng khác, không thể gửi yêu cầu thuê thêm phòng.');
            redirectTo('rooms');
        }

        // Đã có pending request khác
        if ($pending = RentalRequestModel::getPendingByUser($userId)) {
            $pendingRoom = RoomModel::getById((int)($pending['room_id'] ?? 0));
            setFlash('rent_error', 'Bạn đang có yêu cầu thuê phòng "' . ($pendingRoom['name'] ?? '') . '" đang chờ xét duyệt. Hãy hủy yêu cầu đó trước nếu muốn đổi phòng.');
            redirectTo('request-rent', ['id' => $id]);
        }

        $moveInDate = trim($_POST['move_in_date'] ?? '');
        $gender     = trim($_POST['gender'] ?? 'other');

        if ($moveInDate === '' || strtotime($moveInDate) === false) {
            setFlash('rent_error', 'Ngày vào ở không hợp lệ.');
            redirectTo('request-rent', ['id' => $id]);
        }
        $ts = strtotime($moveInDate);
        if ($ts < strtotime(date('Y-m-d')) || $ts > strtotime('+30 days')) {
            setFlash('rent_error', 'Ngày dự kiến vào ở phải từ hôm nay đến tối đa 30 ngày kể từ hôm nay.');
            redirectTo('request-rent', ['id' => $id]);
        }

        RentalRequestModel::create([
            'user_id'       => $userId,
            'room_id'       => $id,
            'move_in_date'  => $moveInDate,
            'gender'        => $gender,
            'occupant_count'=> 1,
        ]);

        // Notify all admins
        foreach (UserModel::getAll() as $admin) {
            if ((int)($admin['role'] ?? 1) === 1) {
                NotificationModel::create([
                    'user_id' => (int)$admin['id'],
                    'type'    => 'rental_request',
                    'title'   => 'Yêu cầu thuê phòng mới',
                    'content' => ($currentUser['full_name'] ?? 'Khách') . ' đã gửi yêu cầu thuê phòng "' . ($room['name'] ?? '') . '", ngày dự kiến vào ở ' . date('d/m/Y', $ts) . '. Cần admin xét duyệt.',
                    'link'    => '?page=admin-rent-requests&rent_filter=pending',
                ]);
            }
        }

        setFlash('rent_message', 'Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" đã được gửi, vui lòng chờ admin xét duyệt.');
        redirectTo('request-rent', ['id' => $id]);
    }

    /**
     * Tenant xác nhận đã thanh toán tiền cọc (self-confirm)
     * - Request phải pending + payment_status = confirmed
     * - Check sức chứa phòng, user chưa có phòng
     * - Assign user to room, mark request paid, notify
     */
    public function paidRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('rooms');
        verify_csrf();
        $this->requireTenant();

        $userId = (int)$_SESSION['user_id'];
        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RentalRequestModel::getById($requestId);

        if (!$request || (int)($request['user_id'] ?? 0) !== $userId) {
            setFlash('rent_error', 'Yêu cầu không tồn tại hoặc không thuộc về tài khoản của bạn.');
            redirectTo('rooms');
        }
        if (($request['status'] ?? '') !== 'pending' || ($request['payment_status'] ?? '') !== 'confirmed') {
            setFlash('rent_error', 'Yêu cầu này chưa được admin xác nhận hoặc đã được xử lý trước đó.');
            redirectTo('request-rent', ['id' => (int)($request['room_id'] ?? 0)]);
        }

        $roomId = (int)($request['room_id'] ?? 0);
        $room = RoomModel::getById($roomId);
        if (!$room) {
            setFlash('rent_error', 'Phòng trong yêu cầu không còn tồn tại.');
            redirectTo('rooms');
        }
        if (!empty(UserModel::getById($userId)['room_id'])) {
            setFlash('rent_error', 'Bạn đã có phòng đang thuê, không thể xác nhận thêm phòng.');
            redirectTo('rooms');
        }

        $currentOccupants = RoomModel::countOccupants($roomId);
        $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants + 1 > $maxOcc) {
            setFlash('rent_error', "Phòng đã đủ sức chứa ($currentOccupants/$maxOcc), không thể vào ở.");
            redirectTo('rooms');
        }

        $moveInDate = trim($request['move_in_date'] ?? '') ?: date('Y-m-d');
        $deposit = (float)($request['deposit'] ?? 0) ?: (float)($room['price'] ?? 0);

        try {
            UserModel::assignToRoom($userId, $roomId);
            Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $userId]);
            RentalRequestModel::markPaid($requestId);

            $currentUser = UserModel::getById($userId);
            $userName    = $currentUser['full_name'] ?? 'Người thuê';
            $roomName    = $room['name'] ?? '';

            // Notify admins
            foreach (UserModel::getAll() as $admin) {
                if ((int)($admin['role'] ?? 1) === 1) {
                    NotificationModel::create([
                        'user_id' => (int)$admin['id'],
                        'type' => 'rental_request',
                        'title' => 'Tiền cọc đã được thanh toán',
                        'content' => "$userName đã thanh toán tiền cọc " . number_format($deposit, 0, ',', '.') . "đ thành công cho phòng \"$roomName\".",
                        'link' => '?page=admin-rent-requests&rent_filter=approved',
                    ]);
                }
            }

            // Notify tenant
            NotificationModel::create([
                'user_id' => $userId,
                'type' => 'general',
                'title' => "Chào mừng đến với phòng $roomName",
                'content' => "Bạn đã thanh toán tiền cọc " . number_format($deposit, 0, ',', '.') . "đ thành công và chính thức là người thuê phòng \"$roomName\". Ngày vào ở: " . date('d/m/Y', strtotime($moveInDate)) . ".",
            ]);

            setFlash('rent_message', "Chúc mừng! Bạn đã thanh toán tiền cọc thành công và chính thức vào thuê phòng \"$roomName\".");
        } catch (Throwable $e) {
            setFlash('rent_error', 'Không xác nhận thanh toán được: ' . $e->getMessage());
        }
        redirectTo('rooms');
    }

    /**
     * Tenant tự hủy yêu cầu đang chờ (để đổi phòng khác)
     */
    public function cancelRentRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('home');
        verify_csrf();
        $this->requireTenant();

        $userId = (int)$_SESSION['user_id'];
        $requestId = (int)($_POST['request_id'] ?? 0);
        $returnRoomId = (int)($_POST['room_id'] ?? 0);

        RentalRequestModel::cancelByUser($requestId, $userId);
        setFlash('rent_message', 'Đã hủy yêu cầu thuê. Bạn có thể tạo yêu cầu mới.');
        redirectTo($returnRoomId > 0 ? 'request-rent' : 'rooms', ['id' => $returnRoomId]);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Yêu cầu user là tenant (role=0), redirect nếu không
     */
    private function requireTenant(): void
    {
        if (empty($_SESSION['user_id']) || (int)($_SESSION['role'] ?? -1) === 1) {
            redirectTo(empty($_SESSION['user_id']) ? 'login' : 'home');
        }
    }
}