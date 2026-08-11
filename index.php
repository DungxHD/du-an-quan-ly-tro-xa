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
        BASE_PATH . 'models/core/',
        BASE_PATH . 'models/room/',
        BASE_PATH . 'models/user/',
        BASE_PATH . 'models/billing/',
        BASE_PATH . 'models/communication/',
        BASE_PATH . 'models/moderation/',
        BASE_PATH . 'models/content/',
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
function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['_csrf_token'] ?? '';
    if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token không hợp lệ. Vui lòng quay lại và thử lại.');
    }
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
            ['id' => 'rent-requests', 'label' => 'Yêu cầu thuê', 'icon' => 'inbox', 'url' => BASE_URL . '?page=admin-rent-requests'],
            ['id' => 'roommate-requests', 'label' => 'Yêu cầu ở ghép', 'icon' => 'group_add', 'url' => BASE_URL . '?page=admin-roommate-requests'],

            [
                'id' => 'group-settings', 'label' => 'Cấu hình hệ thống', 'icon' => 'tune',
                'children' => [
                    ['id' => 'settings', 'label' => 'Cấu hình chung', 'icon' => 'settings', 'url' => BASE_URL . '?page=admin-settings'],
                    ['id' => 'amenities', 'label' => 'Tiện ích', 'icon' => 'apps', 'url' => BASE_URL . '?page=admin-amenities'],
                ],
            ],
            ['id' => 'areas', 'label' => 'Khu nhà', 'icon' => 'location_city', 'url' => BASE_URL . '?page=admin-areas'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=admin-services'],
            ['id' => 'meter-readings', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=admin-meter-readings'],
            [
                'id' => 'group-tenants', 'label' => 'Khách thuê', 'icon' => 'group',
                'children' => [
                    ['id' => 'tenants', 'label' => 'Người thuê', 'icon' => 'badge', 'url' => BASE_URL . '?page=admin-tenants'],
                    ['id' => 'contracts', 'label' => 'Hợp đồng', 'icon' => 'description', 'url' => BASE_URL . '?page=admin-contracts'],
                ],
            ],

            [
                'id' => 'group-community', 'label' => 'Cộng đồng & Kiểm duyệt', 'icon' => 'star',
                'children' => [
                    ['id' => 'comments', 'label' => 'Đánh giá', 'icon' => 'star', 'url' => BASE_URL . '?page=admin-comments'],
                    ['id' => 'banned-words', 'label' => 'Từ cấm', 'icon' => 'gpp_bad', 'url' => BASE_URL . '?page=admin-banned-words'],
                    ['id' => 'comment-reports', 'label' => 'Báo cáo', 'icon' => 'flag', 'url' => BASE_URL . '?page=admin-comment-reports'],
                ],
            ],

            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=admin-notifications'],
        ],
        'tenant' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'url' => BASE_URL . '?page=tenant'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=tenant-services'],
            ['id' => 'meter', 'label' => 'Điện nước', 'icon' => 'speed', 'url' => BASE_URL . '?page=tenant-meter'],
            ['id' => 'invoice', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=tenant-invoice'],
            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=tenant-notifications'],
            ['id' => 'profile', 'label' => 'Hồ sơ', 'icon' => 'person', 'url' => BASE_URL . '?page=tenant-profile'],
            ['id' => 'contract', 'label' => 'Hợp đồng', 'icon' => 'description', 'url' => BASE_URL . '?page=tenant-contract'],
            ['id' => 'roommate', 'label' => 'Ở ghép', 'icon' => 'group_add', 'url' => BASE_URL . '?page=tenant-roommate'],
            ['id' => 'maintenance', 'label' => 'Bảo trì', 'icon' => 'build', 'url' => BASE_URL . '?page=tenant-maintenance'],

            ['id' => 'rooms', 'label' => 'Tìm phòng', 'icon' => 'search', 'url' => BASE_URL . '?page=rooms'],
        ],
    ];

    return array_map(static function ($item) use ($active) {
        if (!empty($item['children'])) {
            foreach ($item['children'] as $key => $child) {
                $item['children'][$key]['active'] = ($child['id'] ?? '') === $active;
            }
            $item['active'] = false;
            $item['has_active_child'] = in_array(true, array_column($item['children'], 'active'), true);
        } else {
            $item['active'] = ($item['id'] ?? '') === $active;
            $item['has_active_child'] = false;
        }
        return $item;
    }, $menus[$role] ?? []);
}
// [CMS-GUEST-PREVIEW] Khung xem trước cấu hình luôn render như khách chưa đăng nhập.
$GLOBALS['cmsPreviewAdmin'] = isset($_GET['cms_preview']) && (int)($_SESSION['role'] ?? -1) === 1;
if (!empty($GLOBALS['cmsPreviewAdmin'])) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close(); // Giữ nguyên phiên đăng nhập thật; thay đổi bên dưới không bị lưu
    }
    unset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['email'], $_SESSION['role'], $_SESSION['room_id']);
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
    case 'request-rent':
        (new RoomController())->requestRent($id);
        break;
    case 'submit-rent-request':
        (new RoomController())->submitRentRequest($id);
        break;
    case 'cancel-rent-request':
        (new RoomController())->cancelRentRequest();
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
        (new AdminController())->settingsEditor();
        break;
    case 'admin-upload-image':
        requireAdmin();
        (new AdminController())->uploadImage();
        break;
    case 'admin-save-settings':
        requireAdmin();
        (new AdminController())->saveSettings();
        break;
    case 'admin-areas':
        requireAdmin();
        (new AdminController())->areas();
        break;
    case 'admin-save-area':
        requireAdmin();
        (new AdminController())->saveArea();
        break;
    case 'admin-add-floor':
        requireAdmin();
        (new AdminController())->addFloorQuick();
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
    case 'admin-undo-delete-service':
        requireAdmin();
        (new AdminController())->undoDeleteService($id);
        break;
    case 'admin-undo-deactivate-service':
        requireAdmin();
        (new AdminController())->undoDeactivateService($id);
        break;
    case 'admin-cancel-price-change':
        requireAdmin();
        (new AdminController())->cancelPriceChange($id);
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
    case 'admin-rent-requests':
        requireAdmin();
        (new AdminController())->rentRequests();
        break;
    case 'admin-approve-rent-request':
        requireAdmin();
        (new AdminController())->approveRentRequest();
        break;
    case 'admin-reject-rent-request':
        requireAdmin();
        (new AdminController())->rejectRentRequest();
        break;
    case 'tenant-roommate':
        (new TenantController())->roommate();
        break;
    case 'tenant-send-roommate-request':
        (new TenantController())->sendRoommateRequest();
        break;
    case 'tenant-approve-roommate':
        (new TenantController())->approveRoommate();
        break;
    case 'tenant-reject-roommate':
        (new TenantController())->rejectRoommate();
        break;
    case 'admin-roommate-requests':
        requireAdmin();
        (new AdminController())->roommateRequests();
        break;
    case 'admin-veto-roommate':
        requireAdmin();
        (new AdminController())->vetoRoommate();
        break;
    case 'tenant-maintenance':
        redirectTo('tenant');
        break;
    case 'tenant-reject-maintenance':
        (new TenantController())->rejectMaintenance();
        break;
    case 'admin-maintenance':
        requireAdmin();
        redirectTo('admin');
        break;
    case 'admin-propose-maintenance':
        requireAdmin();
        (new AdminController())->proposeMaintenance();
        break;
    case 'admin-complete-maintenance':
        requireAdmin();
        (new AdminController())->completeMaintenance();
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
        redirectTo('admin-meter-readings');
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
        redirectTo('admin');
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
