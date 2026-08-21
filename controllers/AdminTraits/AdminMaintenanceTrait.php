<?php
/**
 * AdminMaintenanceTrait - Quản lý bảo trì phòng
 */
trait AdminMaintenanceTrait
{
    // ==========================================
    // MAINTENANCE LIST
    // ==========================================

    public function maintenance(): void
    {
        MaintenanceRequestModel::activateDue();
        $statusFilter = trim($_GET['status'] ?? 'pending');
        $allowed = ['pending', 'active', 'rejected', 'completed'];
        if (!in_array($statusFilter, $allowed, true)) $statusFilter = 'pending';

        $requests = MaintenanceRequestModel::getAll(['status' => $statusFilter]);

        $roomsMap = [];
        foreach (RoomModel::getAll() as $roomRow) $roomsMap[(int)($roomRow['id'] ?? 0)] = $roomRow;

        foreach ($requests as &$row) {
            $roomInfo = $roomsMap[(int)($row['room_id'] ?? 0)] ?? null;
            $row['room_name'] = (string)($roomInfo['name'] ?? '');
            $row['area_name'] = (string)($roomInfo['area_name'] ?? '');
        }
        unset($row);

        $rentedRooms = RoomModel::getAll(['status' => 'rented']);
        $message = pullFlash('maintenance_admin_message', '');
        $error = pullFlash('maintenance_admin_error', '');
        $pageTitle = 'Bảo trì - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/maintenance.php';
    }

    // ==========================================
    // PROPOSE MAINTENANCE
    // ==========================================

    public function proposeMaintenance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-maintenance');
        verify_csrf();

        $roomId = (int)($_POST['room_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $durationDays = (int)($_POST['duration_days'] ?? 1);
        $startDate = trim($_POST['start_date'] ?? '');

        $room = RoomModel::getById($roomId);
        if (!$room) { setFlash('maintenance_admin_error', 'Phòng không tồn tại.'); redirectTo('admin-maintenance'); }
        if (($room['status'] ?? '') !== 'rented') { setFlash('maintenance_admin_error', 'Chỉ đề xuất bảo trì cho phòng đang thuê.'); redirectTo('admin-maintenance'); }
        if ($reason === '') { setFlash('maintenance_admin_error', 'Lý do bảo trì bắt buộc.'); redirectTo('admin-maintenance'); }
        if ($durationDays < 1) { setFlash('maintenance_admin_error', 'Số ngày bảo trì > 0.'); redirectTo('admin-maintenance'); }
        if ($startDate === '' || strtotime($startDate) === false) { setFlash('maintenance_admin_error', 'Ngày bắt đầu không hợp lệ.'); redirectTo('admin-maintenance'); }
        if (MaintenanceRequestModel::getPendingByRoom($roomId)) { setFlash('maintenance_admin_error', 'Phòng này đã có đề xuất đang chờ.'); redirectTo('admin-maintenance'); }

        MaintenanceRequestModel::create([
            'room_id' => $roomId,
            'admin_id' => (int)($_SESSION['user_id'] ?? 0),
            'reason' => $reason,
            'duration_days' => $durationDays,
            'start_date' => $startDate,
        ]);

        $occupants = array_filter(UserModel::getAll(), fn($u) => (int)($u['room_id'] ?? 0) === $roomId && (int)($u['role'] ?? 1) === 0);
        foreach ($occupants as $occ) {
            NotificationModel::create([
                'user_id' => (int)$occ['id'],
                'type' => 'general',
                'title' => 'Đề xuất bảo trì phòng',
                'content' => 'Phòng ' . ($room['name'] ?? '') . ' dự kiến bảo trì từ ' . date('d/m/Y', strtotime($startDate)) . ' trong ' . $durationDays . ' ngày. Lý do: ' . $reason . '. Nếu không đồng ý, vào mục "Bảo trì" để từ chối trước ngày bắt đầu.',
            ]);
        }
        setFlash('maintenance_admin_message', 'Đã gửi đề xuất bảo trì tới ' . count($occupants) . ' cư dân.');
        redirectTo('admin-maintenance');
    }

    // ==========================================
    // COMPLETE MAINTENANCE
    // ==========================================

    public function completeMaintenance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-maintenance');
        verify_csrf();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $request = MaintenanceRequestModel::getById($requestId);
        if (!$request || (string)$request['status'] !== 'active') {
            setFlash('maintenance_admin_error', 'Đề xuất không hợp lệ hoặc chưa diễn ra.');
            redirectTo('admin-maintenance');
        }

        $roomId = (int)$request['room_id'];
        MaintenanceRequestModel::markCompleted($requestId);
        $nextStatus = RoomModel::countOccupants($roomId) > 0 ? 'rented' : 'available';
        RoomModel::updateStatus($roomId, $nextStatus);

        $occupants = array_filter(UserModel::getAll(), fn($u) => (int)($u['room_id'] ?? 0) === $roomId && (int)($u['role'] ?? 1) === 0);
        foreach ($occupants as $occ) {
            NotificationModel::create([
                'user_id' => (int)$occ['id'],
                'type' => 'general',
                'title' => 'Bảo trì hoàn tất',
                'content' => 'Phòng của bạn đã hoàn tất bảo trì và trở lại sử dụng bình thường.',
            ]);
        }
        setFlash('maintenance_admin_message', 'Đã hoàn tất bảo trì và khôi phục trạng thái phòng.');
        redirectTo('admin-maintenance');
    }
}