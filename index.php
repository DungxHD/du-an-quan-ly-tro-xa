<?php

/**
 * ==============================================================================
 * FILE: index.php
 * DESCRIPTION: Entry point chính của ứng dụng (Front Controller).
 *              Xử lý khởi tạo môi trường, autoloading, helper functions và routing.
 * AUTHOR: Qwen Coder (Hỗ trợ Dũng)
 * VERSION: 2.0 - Optimized & Modern Style
 * ==============================================================================
 */

// 1. CẤU HÌNH MÔI TRƯỜNG & HẰNG SỐ
// ------------------------------------------------------------------------------
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Xác định đường dẫn gốc và Base URL linh hoạt (không hard-code localhost)
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = trim($scriptName, '/');
define('BASE_URL', $baseUrl !== '' ? '/' . $baseUrl . '/' : '/');

// 2. HỆ THỐNG TỰ ĐỘNG NẠP CLASS (AUTOLOADING)
// ------------------------------------------------------------------------------
// Giúp giảm thiểu việc require_once thủ công, code sạch và dễ mở rộng hơn.
spl_autoload_register(function ($class) {
    // Danh sách thư mục chứa class tương ứng với hậu tố tên class
    $map = [
        'Controller' => 'controllers/',
        'Model'      => 'models/',
    ];
    foreach ($map as $suffix => $dir) {
        if (strpos($class, $suffix) !== false) {
            $file = BASE_PATH . $dir . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// 3. CÁC HÀM HELPER TIỆN ÍCH (GLOBAL HELPERS)
// ------------------------------------------------------------------------------

/**
 * Escape output chống XSS cơ bản.
 * @param mixed $str Dữ liệu cần escape.
 * @return string Chuỗi đã được mã hóa HTML.
 */
function e($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Trả về text mặc định nếu giá trị rỗng.
 * @param mixed $value Giá trị kiểm tra.
 * @param string $default Text mặc định.
 * @return string
 */
function fallbackText($value, $default = 'Chưa có dữ liệu')
{
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $default;
}

/**
 * Điều hướng sang trang khác.
 * @param string $page Tên page (route).
 * @param array $params Các tham số GET kèm theo.
 */
function redirectTo($page, $params = [])
{
    $query = array_merge(['page' => $page], $params);
    header('Location: ' . BASE_URL . '?' . http_build_query($query));
    exit;
}

/**
 * Kiểm tra đăng nhập, nếu chưa thì chuyển về login.
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        redirectTo('login');
    }
}

/**
 * Kiểm tra quyền Admin (role = 1).
 */
function requireAdmin()
{
    requireLogin();
    if (($_SESSION['role'] ?? 0) != 1) {
        // Có thể hiển thị trang 403 Forbidden ở đây nếu muốn chuyên nghiệp hơn
        redirectTo('home');
    }
}

/**
 * Kiểm tra quyền Tenant (role != 1).
 */
function requireTenant()
{
    requireLogin();
    if (($_SESSION['role'] ?? 1) == 1) {
        redirectTo('admin');
    }
}

/**
 * Sinh menu điều hướng cho Panel Admin hoặc Tenant.
 * @param string $role 'admin' hoặc 'tenant'.
 * @param string $active ID của menu đang active.
 * @return array Danh sách menu.
 */
function getPanelNavigation($role, $active = '')
{
    $menus = [
        'admin' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'url' => BASE_URL . '?page=admin'],
            ['id' => 'buildings', 'label' => 'Khu/Tòa nhà', 'icon' => 'apartment', 'url' => BASE_URL . '?page=admin-buildings'],
            ['id' => 'rooms', 'label' => 'Phòng trọ', 'icon' => 'meeting_room', 'url' => BASE_URL . '?page=admin-rooms'],
            ['id' => 'tenants', 'label' => 'Người thuê', 'icon' => 'group', 'url' => BASE_URL . '?page=admin-tenants'],
            ['id' => 'stats', 'label' => 'Thống kê', 'icon' => 'analytics', 'url' => BASE_URL . '?page=admin-stats'],
        ],
        'tenant' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'url' => BASE_URL . '?page=tenant'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=tenant-services'],
            ['id' => 'profile', 'label' => 'Hồ sơ', 'icon' => 'person', 'url' => BASE_URL . '?page=tenant-profile'],
            ['id' => 'rooms', 'label' => 'Tìm phòng', 'icon' => 'search', 'url' => BASE_URL . '?page=rooms'],
        ],
    ];

    return array_map(static function ($item) use ($active) {
        $item['active'] = $item['id'] === $active;
        return $item;
    }, $menus[$role] ?? []);
}

// 4. ROUTING CHÍNH (ĐIỀU PHỐI YÊU CẦU)
// ------------------------------------------------------------------------------
try {
    // Lấy thông tin route từ URL (param 'page')
    $page = $_GET['page'] ?? 'home';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Bảng ánh xạ Route -> Controller/Method (Dễ dàng thêm mới ở đây)
    // Cấu trúc: 'tên-route' => ['controller' => TênClass, 'action' => tenMethod, 'auth' => 'admin'|'tenant'|null]
    $routes = [
        // Public Routes
        'home'       => ['controller' => 'HomeController', 'action' => 'index'],
        'intro'      => ['controller' => 'HomeController', 'action' => 'intro'],
        'rooms'      => ['controller' => 'HomeController', 'action' => 'rooms'],
        'detail'     => ['controller' => 'RoomController', 'action' => 'detail', 'param' => 'id'],
        'login'      => ['controller' => 'AuthController', 'action' => 'login'],
        'register'   => ['controller' => 'AuthController', 'action' => 'register'],
        'logout'     => ['controller' => 'AuthController', 'action' => 'logout'],

        // Admin Routes
        'admin'                 => ['controller' => 'AdminController', 'action' => 'dashboard', 'auth' => 'admin'],
        'admin-settings'        => ['controller' => 'HomeController', 'action' => 'saveSettings', 'auth' => 'admin'], // Redirect logic cũ
        'admin-save-settings'   => ['controller' => 'HomeController', 'action' => 'saveSettings', 'auth' => 'admin'],
        'admin-buildings'       => ['controller' => 'AdminController', 'action' => 'buildings', 'auth' => 'admin'],
        'admin-save-building'   => ['controller' => 'AdminController', 'action' => 'saveBuilding', 'auth' => 'admin'],
        'admin-delete-building' => ['controller' => 'AdminController', 'action' => 'deleteBuilding', 'auth' => 'admin', 'param' => 'id'],
        'admin-rooms'           => ['controller' => 'AdminController', 'action' => 'rooms', 'auth' => 'admin'],
        'admin-save-room'       => ['controller' => 'AdminController', 'action' => 'saveRoom', 'auth' => 'admin'],
        'admin-delete-room'     => ['controller' => 'AdminController', 'action' => 'deleteRoom', 'auth' => 'admin', 'param' => 'id'],
        'admin-tenants'         => ['controller' => 'AdminController', 'action' => 'tenants', 'auth' => 'admin'],
        'admin-add-tenant'      => ['controller' => 'AdminController', 'action' => 'addTenant', 'auth' => 'admin'],
        'admin-stats'           => ['controller' => 'AdminController', 'action' => 'stats', 'auth' => 'admin'],

        // Tenant Routes
        'tenant'                => ['controller' => 'TenantController', 'action' => 'dashboard', 'auth' => 'tenant'],
        'tenant-services'       => ['controller' => 'TenantController', 'action' => 'services', 'auth' => 'tenant'],
        'tenant-profile'        => ['controller' => 'TenantController', 'action' => 'profile', 'auth' => 'tenant'],
        'tenant-register-service' => ['controller' => 'TenantController', 'action' => 'registerService', 'auth' => 'tenant'],
        'tenant-add-comment'    => ['controller' => 'TenantController', 'action' => 'addComment', 'auth' => 'tenant'],
    ];

    // Xử lý route
    if (array_key_exists($page, $routes)) {
        $route = $routes[$page];

        // Kiểm tra phân quyền nếu có
        if (isset($route['auth'])) {
            if ($route['auth'] === 'admin') {
                requireAdmin();
            } elseif ($route['auth'] === 'tenant') {
                requireTenant();
            }
        }

        // Khởi tạo Controller và gọi Action
        $controllerName = $route['controller'];
        $actionName = $route['action'];

        if (class_exists($controllerName)) {
            $controller = new $controllerName();

            // Chuẩn bị tham số (nếu có, ví dụ: id)
            $params = [];
            if (isset($route['param']) && $route['param'] === 'id') {
                $params[] = $id;
            }

            // Gọi method tương ứng
            if (method_exists($controller, $actionName)) {
                call_user_func_array([$controller, $actionName], $params);
            } else {
                throw new Exception("Action '$actionName' không tồn tại trong $controllerName.");
            }
        } else {
            throw new Exception("Controller '$controllerName' không tìm thấy.");
        }
    } else {
        // Route không tồn tại -> Về trang chủ hoặc 404
        // Ở đây mình cho về home để giống code cũ, có thể đổi thành error 404
        (new HomeController())->index();
    }
} catch (Exception $e) {
    // Xử lý lỗi tập trung
    // Trong môi trường production, bạn nên ghi log thay vì hiển thị trực tiếp
    echo "<div style='padding: 20px; background: #ffe6e6; border: 1px solid red; color: red;'>";
    echo "<h3>Có lỗi xảy ra trong hệ thống:</h3>";
    echo "<p><strong>Lỗi:</strong> " . e($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . e($e->getFile()) . " (Dòng " . $e->getLine() . ")</p>";
    echo "</div>";
    // Có thể thêm: error_log($e->getMessage());
}
