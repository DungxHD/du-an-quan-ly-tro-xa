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
        $allServices = ServiceModel::getAll();
        $myServices = ServiceModel::getByRoom($user['room_id']);
        $myServiceIds = array_column($myServices, 'id');
        
        $pageTitle = 'Dịch vụ - NhaTroA';
        require_once BASE_PATH . 'views/tenant/services.php';
    }
    
    public function profile() {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->getAuthenticatedTenant();
        $success = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 6) {
                    $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
                } else {
                    $data['password'] = $_POST['new_password'];
                }
            }
            if (empty($error)) {
                UserModel::update($userId, $data);
                $_SESSION['full_name'] = $data['full_name'];
                $success = 'Cập nhật thông tin thành công!';
                $user = UserModel::getById($userId);
            }
        }
        
        $pageTitle = 'Hồ sơ cá nhân - NhaTroA';
        require_once BASE_PATH . 'views/tenant/profile.php';
    }
    
    public function registerService() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->getAuthenticatedTenant();
            if ($user && $user['room_id']) {
                $serviceId = (int)$_POST['service_id'];
                ServiceModel::registerForRoom($user['room_id'], $serviceId);
            }
        }
        redirectTo('tenant-services');
    }
    
    public function addComment() {
        $roomId = (int)($_POST['room_id'] ?? $_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->getAuthenticatedTenant();
            $content = trim($_POST['content'] ?? '');
            $rating = (int)($_POST['rating'] ?? 5);
            
            if (!empty($content)) {
                Database::insert('comments', [
                    'room_id' => $roomId,
                    'user_id' => $user['id'],
                    'content' => $content,
                    'rating' => $rating,
                    'status' => 1
                ]);
            }
        }
        redirectTo('detail', ['id' => $roomId]);
    }
}
