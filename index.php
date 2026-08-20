<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// [DEV-QWEN-A][FIX][2026-08-20] Chống trang trắng: exception/fatal luôn được ghi log
// và hiển thị trang lỗi tối giản thay vì body rỗng (display_errors=0).
$GLOBALS['__error_handled'] = false;
set_exception_handler(static function (Throwable $e) {
    $GLOBALS['__error_handled'] = true;
    error_log('[FATAL] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Đã xảy ra lỗi</title></head>'
        . '<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#334155;font-family:system-ui,sans-serif">'
        . '<div style="text-align:center;padding:2rem;max-width:560px">'
        . '<h1 style="margin:0 0 .5rem;font-size:1.25rem">Đã xảy ra lỗi hệ thống</h1>'
        . '<p style="margin:0 0 1rem;color:#64748b">Chi tiết lỗi đã được ghi vào nhật ký. Vui lòng thử lại sau.</p>'
        . '<p style="font-size:.8rem;color:#94a3b8;word-break:break-word">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div></body></html>';
    exit;
});
register_shutdown_function(static function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true) && !$GLOBALS['__error_handled']) {
        error_log('[FATAL] ' . $error['message'] . ' | ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
            echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Đã xảy ra lỗi</title></head>'
                . '<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#334155;font-family:system-ui,sans-serif">'
                . '<div style="text-align:center;padding:2rem;max-width:560px">'
                . '<h1 style="margin:0 0 .5rem;font-size:1.25rem">Đã xảy ra lỗi hệ thống</h1>'
                . '<p style="margin:0;color:#64748b">Chi tiết lỗi đã được ghi vào nhật ký. Vui lòng thử lại sau.</p>'
                . '</div></body></html>';
        }
    }
});
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
// Auto-apply pending room price changes khi admin login
if (isset($_SESSION['role']) && $_SESSION['role'] == 1 && Database::hasConnection()) {
    try {
        $applied = RoomPriceChangeModel::applyDueChanges();
        if ($applied > 0) {
            // Optional: log hoặc flash message
        }
    } catch (Exception $e) {
        // Silent fail - không block user
    }
}

// Đồng bộ session room_id với DB toàn cục — khi admin duyệt yêu cầu thuê/ở ghép,
// người dùng sẽ thấy giao diện tenant ngay tại request tiếp theo (không cần logout/reload).
if (isset($_SESSION['user_id']) && Database::hasConnection()) {
    try {
        $freshUser = UserModel::getById((int)$_SESSION['user_id']);
        if ($freshUser) {
            $dbRoomId = (int)($freshUser['room_id'] ?? 0);
            $sessionRoomId = (int)($_SESSION['room_id'] ?? 0);
            if ($sessionRoomId !== $dbRoomId) {
                $_SESSION['room_id'] = $dbRoomId;
            }
        }
    } catch (Throwable $e) {
        // Silent fail - không block user
    }
}

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
            ['id' => 'rent-requests', 'label' => 'Yêu cầu thuê & ở ghép', 'icon' => 'inbox', 'url' => BASE_URL . '?page=admin-rent-requests'],
            ['id' => 'settings', 'label' => 'Cấu hình hệ thống', 'icon' => 'tune', 'url' => BASE_URL . '?page=admin-settings'],
            ['id' => 'areas', 'label' => 'Quản lý khu', 'icon' => 'apartment', 'url' => BASE_URL . '?page=admin-areas'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=admin-services'],
            ['id' => 'meter-readings', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=admin-meter-readings'],
            ['id' => 'comments', 'label' => 'Đánh giá', 'icon' => 'star', 'url' => BASE_URL . '?page=admin-comments'],
            ['id' => 'feedbacks', 'label' => 'Phản ánh', 'icon' => 'flag', 'url' => BASE_URL . '?page=admin-feedbacks'],
            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=admin-notifications'],
            ['id' => 'accounts', 'label' => 'Quản lý tài khoản', 'icon' => 'manage_accounts', 'url' => BASE_URL . '?page=admin-accounts'],
        ],
        'tenant' => [
            ['id' => 'dashboard', 'label' => 'Thông tin phòng', 'icon' => 'dashboard', 'url' => BASE_URL . '?page=tenant'],
            ['id' => 'services', 'label' => 'Dịch vụ', 'icon' => 'room_service', 'url' => BASE_URL . '?page=tenant-services'],
            ['id' => 'meter', 'label' => 'Điện nước', 'icon' => 'speed', 'url' => BASE_URL . '?page=tenant-meter'],
            ['id' => 'invoice', 'label' => 'Hóa đơn', 'icon' => 'receipt_long', 'url' => BASE_URL . '?page=tenant-invoice'],
            ['id' => 'notifications', 'label' => 'Thông báo', 'icon' => 'notifications', 'url' => BASE_URL . '?page=tenant-notifications'],
            ['id' => 'profile', 'label' => 'Hồ sơ', 'icon' => 'person', 'url' => BASE_URL . '?page=tenant-profile'],
            ['id' => 'roommate', 'label' => 'Ở ghép', 'icon' => 'group_add', 'url' => BASE_URL . '?page=tenant-roommate'],
            ['id' => 'feedback', 'label' => 'Phản ánh', 'icon' => 'flag', 'url' => BASE_URL . '?page=tenant-feedback'],

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
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

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
    case 'api-rooms-filter':
        (new HomeController())->roomsFilterApi();
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
    case 'tenant-paid-rent-request':
        (new RoomController())->paidRentRequest();
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
    case 'verify-otp':
        (new AuthController())->verifyOtp();
        break;
    case 'reset-password':
        (new AuthController())->resetPassword();
        break;
    case 'resend-otp':
        (new AuthController())->resendOtp();
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
        (new AdminController())->deleteArea((int)($_POST['id'] ?? 0));
        break;
    case 'admin-delete-floor-top':
        requireAdmin();
        (new AdminController())->deleteHighestFloor((int)($_POST['id'] ?? 0));
        break;
    case 'admin-rooms':
        requireAdmin();
        (new AdminController())->rooms();
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
    case 'admin-save-amenity-order':
        requireAdmin();
        (new AdminController())->saveAmenityOrder();
        break;
    case 'admin-delete-service':
        requireAdmin();
        (new AdminController())->deleteService($id);
        break;
    case 'admin-undo-delete-service':
        requireAdmin();
        (new AdminController())->undoDeleteService($id);
        break;
    case 'admin-confirm-delete-service':
        requireAdmin();
        (new AdminController())->confirmDeleteService($id);
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
    case 'api-admin-rent-requests':
        requireAdmin();
        (new AdminController())->rentRequestsFilterApi();
        break;
    case 'admin-approve-rent-request':
        requireAdmin();
        (new AdminController())->approveRentRequest();
        break;
    case 'admin-reject-rent-request':
        requireAdmin();
        (new AdminController())->rejectRentRequest();
        break;
    case 'admin-confirm-rent-request':
        requireAdmin();
        (new AdminController())->confirmRentRequest();
        break;
    case 'admin-cancel-rent-request':
        requireAdmin();
        (new AdminController())->cancelRentRequestAdmin();
        break;
    case 'admin-paid-rent-request':
        requireAdmin();
        (new AdminController())->paidRentRequest();
        break;
    case 'tenant-roommate':
        (new TenantController())->roommate();
        break;
    case 'tenant-send-roommate-request':
        (new TenantController())->sendRoommateRequest();
        break;
    case 'tenant-cancel-roommate-request':
        (new TenantController())->cancelRoommateRequest();
        break;
    case 'admin-roommate-requests':
        requireAdmin();
        (new AdminController())->roommateRequests();
        break;
    case 'admin-approve-roommate':
        requireAdmin();
        (new AdminController())->approveRoommate();
        break;
    case 'admin-reject-roommate':
        requireAdmin();
        (new AdminController())->rejectRoommate();
        break;
    case 'admin-veto-roommate':
        requireAdmin();
        (new AdminController())->vetoRoommate();
        break;
    case 'tenant-feedback':
        requireTenant();
        (new TenantController())->feedback();
        break;
    case 'tenant-send-feedback':
        requireTenant();
        (new TenantController())->sendFeedback();
        break;
    case 'admin-maintenance':
        requireAdmin();
        (new AdminController())->maintenance();
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
    case 'admin-notifications':
        requireAdmin();
        (new AdminController())->notifications();
        break;
    case 'admin-comments':
        requireAdmin();
        (new AdminController())->comments();
        break;
    case 'admin-feedbacks':
        requireAdmin();
        (new AdminController())->feedbacks();
        break;
    case 'admin-save-feedback':
        requireAdmin();
        (new AdminController())->saveFeedback();
        break;
    case 'admin-resolve-feedback':
        requireAdmin();
        (new AdminController())->resolveFeedback();
        break;
    case 'admin-invoices':
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
    case 'admin-accounts':
        requireAdmin();
        (new AdminController())->accounts();
        break;
    case 'api-admin-accounts-filter':
        requireAdmin();
        (new AdminController())->accountsFilterApi();
        break;
    case 'admin-save-account':
        requireAdmin();
        (new AdminController())->saveAccount();
        break;
    case 'admin-update-account':
        requireAdmin();
        (new AdminController())->updateAccount();
        break;
    case 'admin-delete-account':
        requireAdmin();
        (new AdminController())->deleteAccount($id);
        break;
    case 'admin-toggle-comment':
        requireAdmin();
        (new AdminController())->toggleComment();
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
    case 'tenant-comment-moderation':
        requireTenant();
        (new TenantController())->commentModeration();
        break;
    case 'tenant-comment-rewrite':
        requireTenant();
        (new TenantController())->commentRewrite();
        break;
    case 'tenant-comment-cancel':
        requireTenant();
        (new TenantController())->commentCancel();
        break;
    default:
        (new HomeController())->index();
}
