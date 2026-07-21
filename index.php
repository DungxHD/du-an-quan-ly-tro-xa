<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__ . '/');
$baseUrl = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
define('BASE_URL', $baseUrl !== '' ? '/' . $baseUrl . '/' : '/');

// Load Models: nạp đủ để router gọi trang nào cũng không thiếu class.
require_once BASE_PATH . 'models/Database.php';
require_once BASE_PATH . 'models/SettingModel.php';
require_once BASE_PATH . 'models/AmenityModel.php';
require_once BASE_PATH . 'models/BuildingModel.php';
require_once BASE_PATH . 'models/RoomModel.php';
require_once BASE_PATH . 'models/ServiceModel.php';
require_once BASE_PATH . 'models/UserModel.php';

// Load Controllers: tách dữ liệu khỏi giao diện để luồng xử lý dễ đọc hơn.
require_once BASE_PATH . 'controllers/BaseController.php';
require_once BASE_PATH . 'controllers/HomeController.php';
require_once BASE_PATH . 'controllers/RoomController.php';
require_once BASE_PATH . 'controllers/AuthController.php';
require_once BASE_PATH . 'controllers/AdminController.php';
require_once BASE_PATH . 'controllers/TenantController.php';

// Cache settings sớm để header/footer/public view dùng ổn định kể cả khi thiếu DB.
RoomModel::loadSettings();

// Helper hiển thị và điều hướng dùng chung.
function e($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function fallbackText($value, $default = 'Chưa có dữ liệu')
{
    $text = trim((string)($value ?? ''));
    return $text !== '' ? $text : $default;
}
function redirectTo($page, $params = [])
{
    $query = array_merge(['page' => $page], $params);
    header('Location: ' . BASE_URL . '?' . http_build_query($query));
    exit;
}
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        redirectTo('login');
    }
}
function requireAdmin()
{
    requireLogin();
    if (($_SESSION['role'] ?? 0) != 1) {
        redirectTo('home');
    }
}
function requireTenant()
{
    requireLogin();
    if (($_SESSION['role'] ?? 1) == 1) {
        redirectTo('admin');
    }
}
/**
 * Trả menu điều hướng cho các panel đăng nhập để mọi view dùng cùng một cấu trúc.
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

// Router chính: ưu tiên page ngắn, view-first, dễ nối route mới.
$page = $_GET['page'] ?? 'home';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($page) {
    case 'home':
        (new HomeController())->index();
        break;
    case 'intro':
        (new HomeController())->intro();
        break;
    case 'rooms':
        (new HomeController())->rooms();
        break;
    case 'detail':
        (new RoomController())->detail($id);
        break;
    case 'login':
        (new AuthController())->login();
        break;
    case 'register':
        (new AuthController())->register();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'admin':
        requireAdmin();
        (new AdminController())->dashboard();
        break;
    case 'admin-settings':
        requireAdmin();
        redirectTo('admin');
        break;
    case 'admin-save-settings':
        requireAdmin();
        (new HomeController())->saveSettings();
        break;
    case 'admin-buildings':
        requireAdmin();
        (new AdminController())->buildings();
        break;
    case 'admin-save-building':
        requireAdmin();
        (new AdminController())->saveBuilding();
        break;
    case 'admin-delete-building':
        requireAdmin();
        (new AdminController())->deleteBuilding($id);
        break;
    case 'admin-rooms':
        requireAdmin();
        (new AdminController())->rooms();
        break;
    case 'admin-save-room':
        requireAdmin();
        (new AdminController())->saveRoom();
        break;
    case 'admin-delete-room':
        requireAdmin();
        (new AdminController())->deleteRoom($id);
        break;
    case 'admin-tenants':
        requireAdmin();
        (new AdminController())->tenants();
        break;
    case 'admin-add-tenant':
        requireAdmin();
        (new AdminController())->addTenant();
        break;
    case 'admin-stats':
        requireAdmin();
        (new AdminController())->stats();
        break;
    case 'tenant':
        requireTenant();
        (new TenantController())->dashboard();
        break;
    case 'tenant-services':
        requireTenant();
        (new TenantController())->services();
        break;
    case 'tenant-profile':
        requireTenant();
        (new TenantController())->profile();
        break;
    case 'tenant-register-service':
        requireTenant();
        (new TenantController())->registerService();
        break;
    case 'tenant-add-comment':
        requireTenant();
        (new TenantController())->addComment();
        break;
    default:
        (new HomeController())->index();
}

<!-- Qwen Coder là tôi đây -->
