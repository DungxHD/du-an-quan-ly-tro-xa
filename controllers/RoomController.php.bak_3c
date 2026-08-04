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
}
