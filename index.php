<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('BASE_PATH', __DIR__ . '/');
$baseUrl = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
define('BASE_URL', $baseUrl !== '' ? '/' . $baseUrl . '/' : '/');

// Autoload tối giản cho model/controller để không phải nối dài `require_once` mỗi khi thêm module mới.
spl_autoload_register(static function ($className) {
    $directories = [
        BASE_PATH . 'models/',
        BASE_PATH . 'controllers/',
    ];

    foreach ($directories as $directory) {
        $filePath = $directory . $className . '.php';
        if (is_file($filePath)) {
            require_once $filePath;
            return;
        }
    }
});

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
/**
 * Flash message dùng 1 lần sau redirect để UX đăng nhập/đăng ký liền mạch hơn.
 */
function setFlash($key, $value)
{
    $_SESSION['_flash'][$key] = $value;
}
function pullFlash($key, $default = null)
{
    if (!isset($_SESSION['_flash'][$key])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
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
            ['id' => 'areas', 'label' => 'Khu nhà', 'icon' => 'location_city', 'url' => BASE_URL . '?page=admin-areas'],
            ['id' => 'floors', 'label' => 'Tầng', 'icon' => 'stairs_2', 'url' => BASE_URL . '?page=admin-floors'],
            ['id' => 'rooms', 'label' => 'Phòng trọ', 'icon' => 'meeting_room', 'url' => BASE_URL . '?page=admin-rooms'],
            ['id' => 'amenities', 'label' => 'Tiện ích', 'icon' => 'apps', 'url' => BASE_URL . '?page=admin-amenities'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=admin-services'],
            ['id' => 'price-changes', 'label' => 'Đổi giá', 'icon' => 'price_change', 'url' => BASE_URL . '?page=admin-price-changes'],
            ['id' => 'meter-readings', 'label' => 'Chỉ số điện nước', 'icon' => 'speed', 'url' => BASE_URL . '?page=admin-meter-readings'],
            ['id' => 'tenants', 'label' => 'Người thuê', 'icon' => 'group', 'url' => BASE_URL . '?page=admin-tenants'],
            ['id' => 'contracts', 'label' => 'Hợp đồng', 'icon' => 'description', 'url' => BASE_URL . '?page=admin-contracts'],
            ['id' => 'invoices', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=admin-invoices'],
            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=admin-notifications'],
            ['id' => 'comments', 'label' => 'Đánh giá', 'icon' => 'star', 'url' => BASE_URL . '?page=admin-comments'],
            ['id' => 'banned-words', 'label' => 'Từ cấm', 'icon' => 'gpp_bad', 'url' => BASE_URL . '?page=admin-banned-words'],
            ['id' => 'comment-reports', 'label' => 'Báo cáo', 'icon' => 'flag', 'url' => BASE_URL . '?page=admin-comment-reports'],
            ['id' => 'stats', 'label' => 'Thống kê', 'icon' => 'analytics', 'url' => BASE_URL . '?page=admin-stats'],
        ],
        'tenant' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'url' => BASE_URL . '?page=tenant'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=tenant-services'],
            ['id' => 'meter', 'label' => 'Điện nước', 'icon' => 'speed', 'url' => BASE_URL . '?page=tenant-meter'],
            ['id' => 'invoice', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=tenant-invoice'],
            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=tenant-notifications'],
            ['id' => 'profile', 'label' => 'Hồ sơ', 'icon' => 'person', 'url' => BASE_URL . '?page=tenant-profile'],
            ['id' => 'contract', 'label' => 'Hợp đồng', 'icon' => 'description', 'url' => BASE_URL . '?page=tenant-contract'],
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
        (new AdminController())->saveSettings();
        break;
    case 'admin-buildings':
        requireAdmin();
        (new AdminController())->buildings();
        break;
    case 'admin-areas':
        requireAdmin();
        (new AdminController())->areas();
        break;
    case 'admin-save-area':
        requireAdmin();
        (new AdminController())->saveArea();
        break;
    case 'admin-delete-area':
        requireAdmin();
        (new AdminController())->deleteArea($id);
        break;
    case 'admin-floors':
        requireAdmin();
        (new AdminController())->floors();
        break;
    case 'admin-save-floor':
        requireAdmin();
        (new AdminController())->saveFloor();
        break;
    case 'admin-delete-floor':
        requireAdmin();
        (new AdminController())->deleteFloor($id);
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
    case 'admin-amenities':
        requireAdmin();
        (new AdminController())->amenities();
        break;
    case 'admin-services':
        requireAdmin();
        (new AdminController())->services();
        break;
    case 'admin-price-changes':
        requireAdmin();
        (new AdminController())->priceChanges();
        break;
    case 'admin-meter-readings':
        requireAdmin();
        (new AdminController())->meterReadings();
        break;
    case 'admin-save-amenity':
        requireAdmin();
        (new AdminController())->saveAmenity();
        break;
    case 'admin-save-service':
        requireAdmin();
        (new AdminController())->saveService();
        break;
    case 'admin-save-price-change':
        requireAdmin();
        (new AdminController())->savePriceChange();
        break;
    case 'admin-save-meter-readings':
        requireAdmin();
        (new AdminController())->saveMeterReadings();
        break;
    case 'admin-delete-amenity':
        requireAdmin();
        (new AdminController())->deleteAmenity($id);
        break;
    case 'admin-delete-service':
        requireAdmin();
        (new AdminController())->deleteService($id);
        break;
    case 'admin-save-room':
        requireAdmin();
        (new AdminController())->saveRoom();
        break;
    case 'admin-assign-service-to-room':
        requireAdmin();
        (new AdminController())->assignServiceToRoom();
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
    case 'admin-contracts':
        requireAdmin();
        (new AdminController())->contracts();
        break;
    case 'admin-notifications':
        requireAdmin();
        (new AdminController())->notifications();
        break;
    case 'admin-comments':
        requireAdmin();
        (new AdminController())->comments();
        break;
    case 'admin-banned-words':
        requireAdmin();
        (new AdminController())->bannedWords();
        break;
    case 'admin-save-banned-word':
        requireAdmin();
        (new AdminController())->saveBannedWord();
        break;
    case 'admin-comment-reports':
        requireAdmin();
        (new AdminController())->commentReports();
        break;
    case 'admin-resolve-report':
        requireAdmin();
        (new AdminController())->resolveReport();
        break;
    case 'admin-invoices':
        requireAdmin();
        (new AdminController())->invoices();
        break;
    case 'admin-view-contract':
        requireAdmin();
        (new AdminController())->viewContract($id);
        break;
    case 'admin-generate-invoice':
        requireAdmin();
        (new AdminController())->generateInvoice();
        break;
    case 'admin-confirm-payment':
        requireAdmin();
        (new AdminController())->confirmPayment();
        break;
    case 'admin-send-notification':
        requireAdmin();
        (new AdminController())->sendNotification();
        break;
    case 'admin-toggle-comment':
        requireAdmin();
        (new AdminController())->toggleComment();
        break;
    case 'admin-terminate-contract':
        requireAdmin();
        (new AdminController())->terminateContract($id);
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
    case 'tenant-meter':
        requireTenant();
        (new TenantController())->viewMeterReadings();
        break;
    case 'tenant-invoice':
        requireTenant();
        (new TenantController())->viewInvoice();
        break;
    case 'tenant-notifications':
        requireTenant();
        (new TenantController())->viewNotifications();
        break;
    case 'tenant-profile':
        requireTenant();
        (new TenantController())->profile();
        break;
    case 'tenant-contract':
        requireTenant();
        (new TenantController())->contract();
        break;
    case 'tenant-register-service':
        requireTenant();
        (new TenantController())->registerService();
        break;
    case 'tenant-pay-invoice':
        requireTenant();
        (new TenantController())->payInvoice();
        break;
    case 'tenant-mark-notification-read':
        requireTenant();
        (new TenantController())->markNotificationRead();
        break;
    case 'tenant-add-comment':
        requireTenant();
        (new TenantController())->addComment();
        break;
    case 'tenant-edit-comment':
        requireTenant();
        (new TenantController())->editComment();
        break;
    case 'tenant-delete-comment':
        requireTenant();
        (new TenantController())->deleteComment();
        break;
    case 'tenant-report-comment':
        requireTenant();
        (new TenantController())->reportComment();
        break;
    default:
        (new HomeController())->index();
}
