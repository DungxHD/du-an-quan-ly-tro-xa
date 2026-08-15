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
        $pageTitle = $room['name'] . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic(
            'views/pages/detail.php',
            compact('room', 'commentBundle', 'commentEligibility', 'commentMessage', 'commentError'),
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

        // Chặn tài khoản đã có phòng/hợp đồng đang thuê không được thuê thêm phòng khác
        $currentUser = UserModel::getById($userId);
        if (!empty($currentUser['room_id']) || ContractModel::getActiveByUserId($userId)) {
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
        $occupantCount = (int)($_POST['occupant_count'] ?? 1);

        if ($moveInDate === '' || strtotime($moveInDate) === false) {
            setFlash('rent_error', 'Ngày vào ở không hợp lệ.');
            redirectTo('request-rent', ['id' => (int)$id]);
        }
        if ($occupantCount < 1) {
            setFlash('rent_error', 'Số người ở phải lớn hơn 0.');
            redirectTo('request-rent', ['id' => (int)$id]);
        }
        $maxOcc = (int)($room['max_occupancy'] ?? 0);
        if ($maxOcc > 0 && $occupantCount > $maxOcc) {
            setFlash('rent_error', 'Số người ở vượt quá sức chứa tối đa của phòng (' . $maxOcc . ' người).');
            redirectTo('request-rent', ['id' => (int)$id]);
        }

        RentalRequestModel::create([
            'user_id' => $userId,
            'room_id' => (int)$id,
            'move_in_date' => $moveInDate,
            'gender' => $gender,
            'occupant_count' => $occupantCount,
        ]);
        setFlash('rent_message', 'Yêu cầu thuê phòng "' . ($room['name'] ?? '') . '" đã được gửi, vui lòng chờ admin xét duyệt.');
        redirectTo('request-rent', ['id' => (int)$id]);
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
