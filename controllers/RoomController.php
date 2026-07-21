<?php
class RoomController extends BaseController {
    /**
     * Trang chi tiết chỉ chuẩn bị dữ liệu cần hiển thị để view tập trung vào giao diện.
     */
    public function detail($id) {
        $room = RoomModel::getById($id);
        if (!$room) { header('Location: ' . BASE_URL . '?page=home'); exit; }
        
        RoomModel::incrementViews($id);
        $comments = RoomModel::getCommentsByRoom($id);
        $pageTitle = $room['name'] . ' - ' . RoomModel::getSetting('site_name', 'NhaTroA');

        $this->renderPublic('views/pages/detail.php', compact('room', 'comments'), 'detail', $pageTitle);
    }
}
