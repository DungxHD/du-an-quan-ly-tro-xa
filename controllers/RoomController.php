<?php
class RoomController extends BaseController {
    /**
     * Trang chi tiết chỉ chuẩn bị dữ liệu cần hiển thị để view tập trung vào giao diện.
     */
    public function detail($id) {
        $viewerUserId = (int)($_SESSION['user_id'] ?? 0);
        $viewerRole = (int)($_SESSION['role'] ?? -1);
        $canBypassPublicOnly = $viewerRole === 1
            || ($viewerRole === 0 && CommentModel::canUserAccessRoomDetail($viewerUserId, (int)$id));

        $room = RoomModel::getById($id, ['public_only' => !$canBypassPublicOnly]);
        if (!$room) { header('Location: ' . BASE_URL . '?page=home'); exit; }
        
        RoomModel::incrementViews($id);
        $room['views'] = (int)($room['views'] ?? 0) + 1;
        $commentBundle = CommentModel::getRoomDetailComments((int)$id, $viewerUserId);
        $commentEligibility = $viewerRole === 0
            ? CommentModel::validateCreatePermission($viewerUserId, (int)$id)
            : ['allowed' => false, 'message' => ''];
        $commentMessage = pullFlash('comment_message', '');
        $commentError = pullFlash('comment_error', '');
        $commentWarning = pullFlash('comment_warning', '');
        $pageTitle = $room['name'] . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic(
            'views/pages/detail.php',
            compact('room', 'commentBundle', 'commentEligibility', 'commentMessage', 'commentError', 'commentWarning'),
            'detail',
            $pageTitle
        );
    }

    /**
     * Trang gửi yêu cầu thuê phòng (khách đã đăng ký / tenant chưa có phòng).
     */
    public function requestRent($id) {
        if (empty($_SESSION['user_id'])) { redirectTo('login'); }
        if ((int)($_SESSION['role'] ?? -1) === 1) { redirectTo('home'); }
        $userId = (int)$_SESSION['user_id'];
        $room = RoomModel::getById((int)$id);
        if (!$room) { redirectTo('rooms'); }

        $pendingRequest = RentalRequestModel::getPendingByUser($userId);
        $message = pullFlash('rent_message', '');
        $error = pullFlash('rent_error', '');
        $pageTitle = 'Yêu cầu thuê ' . ($room['name'] ?? '') . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic(
            'views/pages/request_rent.php',
            compact('room', 'pendingRequest', 'message', 'error'),
            'detail',
            $pageTitle
        );
    }

    /**
     * Xử lý POST gửi yêu cầu thuê. Chặn 1-yêu-cầu-pending + kiểm tra sức chứa.
     */
    public function submitRentRequest($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('request-rent', ['id' => (int)$id]); }
        verify_csrf();
        if (empty($_SESSION['user_id'])) { redirectTo('login'); }
        if ((int)($_SESSION['role'] ?? -1) === 1) { redirectTo('home'); }
        $userId = (int)$_SESSION['user_id'];

        $room = RoomModel::getById((int)$id);
        if (!$room) { redirectTo('rooms'); }
        if ((string)($room['status'] ?? '') !== 'available') {
            setFlash('rent_error', 'Phòng này hiện không còn trống để gửi yêu cầu thuê.');
            redirectTo('detail', ['id' => (int)$id]);
        }

        // Chặn tài khoản đã có phòng đang thuê không được thuê thêm phòng khác
        $currentUser = UserModel::getById($userId);
        if (!empty($currentUser['room_id'])) {
            setFlash('rent_error', 'Tài khoản của bạn đang thuê phòng khác, không thể gửi yêu cầu thuê thêm phòng.');
            redirectTo('rooms');
        }

        $pending = RentalRequestModel::getPendingByUser($userId);
        if ($pending) {
            $pendingRoom = RoomModel::getById((int)($pending['room_id'] ?? 0));
            setFlash('rent_error', 'Bạn đang có yêu cầu thuê phòng "' . ($pendingRoom['name'] ?? '') . '" đang chờ xét duyệt. Hãy hủy yêu cầu đó trước nếu muốn đổi phòng.');
            redirectTo('request-rent', ['id' => (int)$id]);
        }

        $moveInDate = trim((string)($_POST['move_in_date'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? 'other'));

        if ($moveInDate === '' || strtotime($moveInDate) === false) {
            setFlash('rent_error', 'Ngày vào ở không hợp lệ.');
            redirectTo('request-rent', ['id' => (int)$id]);
        }
        $ts = strtotime($moveInDate);
        $minTs = strtotime(date('Y-m-d'));
        $maxTs = strtotime(date('Y-m-d', strtotime('+30 days')));
        if ($ts < $minTs || $ts > $maxTs) {
            setFlash('rent_error', 'Ngày dự kiến vào ở phải từ hôm nay đến tối đa 30 ngày kể từ hôm nay.');
            redirectTo('request-rent', ['id' => (int)$id]);
        }

        RentalRequestModel::create([
            'user_id' => $userId,
            'room_id' => (int)$id,
            'move_in_date' => $moveInDate,
            'gender' => $gender,
            'occupant_count' => 1,
        ]);
        foreach (UserModel::getAll() as $admin) {
            if ((int)($admin['role'] ?? 1) === 1) {
                NotificationModel::create([
                    'user_id' => (int)$admin['id'],
                    'type' => 'rental_request',
                    'title' => 'Yêu cầu thuê phòng mới',
                    'content' => ($currentUser['full_name'] ?? 'Khách') . ' đã gửi yêu cầu thuê phòng "' . ($room['name'] ?? '') . '", ngày dự kiến vào ở ' . date('d/m/Y', $ts) . '. Cần admin xét duyệt.',
                    'link' => '?page=admin-rent-requests&rent_filter=pending',
                ]);
            }
        }
        setFlash('rent_message', 'Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" đã được gửi, vui lòng chờ admin xét duyệt.');
        redirectTo('request-rent', ['id' => (int)$id]);
    }

    /**
     * Người thuê xác nhận đã thanh toán tiền cọc (dự án trường: tự xác nhận, vào phòng luôn).
     * Yêu cầu phải thuộc về user đang đăng nhập, đang chờ duyệt và đã được admin xác nhận (có QR + tiền cọc).
     */
    public function paidRentRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('rooms'); }
        verify_csrf();
        if (empty($_SESSION['user_id'])) { redirectTo('login'); }
        if ((int)($_SESSION['role'] ?? -1) === 1) { redirectTo('home'); }
        $userId = (int)$_SESSION['user_id'];

        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = RentalRequestModel::getById($requestId);
        if (!$request || (int)($request['user_id'] ?? 0) !== $userId) {
            setFlash('rent_error', 'Yêu cầu không tồn tại hoặc không thuộc về tài khoản của bạn.');
            redirectTo('rooms');
        }
        if ((string)($request['status'] ?? '') !== 'pending' || (string)($request['payment_status'] ?? '') !== 'confirmed') {
            setFlash('rent_error', 'Yêu cầu này chưa được admin xác nhận hoặc đã được xử lý trước đó.');
            redirectTo('request-rent', ['id' => (int)($request['room_id'] ?? 0)]);
        }

        $roomId = (int)($request['room_id'] ?? 0);
        $room = RoomModel::getById($roomId);
        if (!$room) {
            setFlash('rent_error', 'Phòng trong yêu cầu không còn tồn tại.');
            redirectTo('rooms');
        }
        $existingUser = UserModel::getById($userId);
        if (!empty($existingUser['room_id'])) {
            setFlash('rent_error', 'Bạn đã có phòng đang thuê, không thể xác nhận thêm phòng.');
            redirectTo('rooms');
        }

        $currentOccupants = RoomModel::countOccupants($roomId);
        $maxOcc = max(1, (int)($room['max_occupancy'] ?? 1));
        if ($currentOccupants + 1 > $maxOcc) {
            setFlash('rent_error', 'Phòng đã đủ sức chứa (' . $currentOccupants . '/' . $maxOcc . '), không thể vào ở.');
            redirectTo('rooms');
        }

        $moveInDate = trim((string)($request['move_in_date'] ?? '')) ?: date('Y-m-d');
        $deposit = (float)($request['deposit'] ?? 0);
        if ($deposit <= 0) {
            $deposit = (float)($room['price'] ?? 0);
        }
        try {
            UserModel::assignToRoom($userId, $roomId);
            Database::update('users', ['room_id' => $roomId], 'id = :id', ['id' => $userId]);
            RentalRequestModel::markPaid($requestId);

            $currentUser = UserModel::getById($userId);
            $userName = (string)($currentUser['full_name'] ?? 'Người thuê');
            $roomName = (string)($room['name'] ?? '');
            foreach (UserModel::getAll() as $admin) {
                if ((int)($admin['role'] ?? 1) === 1) {
                    NotificationModel::create([
                        'user_id' => (int)$admin['id'],
                        'type' => 'rental_request',
                        'title' => 'Tiền cọc đã được thanh toán',
                        'content' => $userName . ' đã thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ thành công cho phòng "' . $roomName . '".',
                        'link' => '?page=admin-rent-requests&rent_filter=approved',
                    ]);
                }
            }
            NotificationModel::create([
                'user_id' => $userId,
                'type' => 'general',
                'title' => 'Chào mừng đến với phòng ' . $roomName,
                'content' => 'Bạn đã thanh toán tiền cọc ' . number_format($deposit, 0, ',', '.') . 'đ thành công và chính thức là người thuê phòng "' . $roomName . '". Ngày vào ở: ' . date('d/m/Y', strtotime($moveInDate)) . '.',
            ]);
            setFlash('rent_message', 'Chúc mừng! Bạn đã thanh toán tiền cọc thành công và chính thức vào thuê phòng "' . $roomName . '".');
        } catch (Throwable $exception) {
            setFlash('rent_error', 'Không xác nhận thanh toán được: ' . $exception->getMessage());
        }
        redirectTo('rooms');
    }

    /**
     * User tự hủy yêu cầu đang chờ của chính mình (để đổi phòng khác).
     */
    public function cancelRentRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirectTo('home'); }
        verify_csrf();
        if (empty($_SESSION['user_id'])) { redirectTo('login'); }
        $userId = (int)$_SESSION['user_id'];
        $requestId = (int)($_POST['request_id'] ?? 0);
        $returnRoomId = (int)($_POST['room_id'] ?? 0);

        RentalRequestModel::cancelByUser($requestId, $userId);
        setFlash('rent_message', 'Đã hủy yêu cầu thuê. Bạn có thể tạo yêu cầu mới.');
        if ($returnRoomId > 0) { redirectTo('request-rent', ['id' => $returnRoomId]); }
        redirectTo('rooms');
    }
}
