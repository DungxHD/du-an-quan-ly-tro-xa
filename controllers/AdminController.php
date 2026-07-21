<?php
class AdminController {
    public function dashboard() {
        // Gom dữ liệu dashboard + cấu hình để admin landing page có đủ thông tin thao tác.
        $stats = [
            'total_buildings' => BuildingModel::count(),
            'total_rooms' => RoomModel::count(),
            'available_rooms' => RoomModel::countByStatus('available'),
            'rented_rooms' => RoomModel::countByStatus('rented'),
            'total_tenants' => UserModel::countByRole(0),
            'total_revenue' => RoomModel::getTotalRevenue()
        ];
        $recentRooms = RoomModel::getAll(['status' => 'available']);
        $recentTenants = UserModel::getAll();
        $brandSettings = RoomModel::getSettingsByGroup('brand');
        $heroSettings = RoomModel::getSettingsByGroup('hero');
        $contactSettings = RoomModel::getSettingsByGroup('contact');
        $pageTitle = 'Admin Dashboard - NhaTroA';
        require_once BASE_PATH . 'views/admin/dashboard.php';
    }
    
    public function buildings() {
        $buildings = BuildingModel::getAll();
        $editId = $_GET['edit'] ?? 0;
        $editBuilding = $editId ? BuildingModel::getById($editId) : null;
        $pageTitle = 'Quản lý Khu/Dãy/Tòa - NhaTroA';
        require_once BASE_PATH . 'views/admin/buildings.php';
    }
    
    public function rooms() {
        $rooms = RoomModel::getAll();
        $buildings = BuildingModel::getAll();
        $editId = $_GET['edit'] ?? 0;
        $editRoom = $editId ? RoomModel::getById($editId) : null;
        $pageTitle = 'Quản lý Phòng - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms.php';
    }
    
    public function tenants() {
        $tenants = UserModel::getAll();
        $rooms = RoomModel::getAll(['status' => 'available']);
        $pageTitle = 'Quản lý Người thuê - NhaTroA';
        require_once BASE_PATH . 'views/admin/tenants.php';
    }
    
    public function stats() {
        $buildings = BuildingModel::getAll();
        $pageTitle = 'Thống kê - NhaTroA';
        require_once BASE_PATH . 'views/admin/stats.php';
    }
    
    public function saveBuilding() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'type' => $_POST['type'] ?? 'building',
                'address' => trim($_POST['address'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            $id = $_POST['id'] ?? null;
            BuildingModel::save($data, $id ?: null);
        }
        header('Location: ' . BASE_URL . '?page=admin-buildings');
        exit;
    }
    
    public function deleteBuilding($id) {
        BuildingModel::delete($id);
        header('Location: ' . BASE_URL . '?page=admin-buildings');
        exit;
    }
    
    public function saveRoom() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'building_id' => (int)$_POST['building_id'],
                'name' => trim($_POST['name'] ?? ''),
                'floor' => (int)($_POST['floor'] ?? 1),
                'price' => (float)($_POST['price'] ?? 0),
                'area' => (float)($_POST['area'] ?? 0),
                'max_occupancy' => (int)($_POST['max_occupancy'] ?? 2),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'available'
            ];
            if (!empty($_POST['thumbnail'])) {
                $data['thumbnail'] = $_POST['thumbnail'];
            }
            $id = $_POST['id'] ?? null;
            RoomModel::save($data, $id ?: null);
        }
        header('Location: ' . BASE_URL . '?page=admin-rooms');
        exit;
    }
    
    public function deleteRoom($id) {
        RoomModel::delete($id);
        header('Location: ' . BASE_URL . '?page=admin-rooms');
        exit;
    }
    
    public function addTenant() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)$_POST['user_id'];
            $roomId = (int)$_POST['room_id'];
            UserModel::assignToRoom($userId, $roomId);
        }
        header('Location: ' . BASE_URL . '?page=admin-tenants');
        exit;
    }
}
