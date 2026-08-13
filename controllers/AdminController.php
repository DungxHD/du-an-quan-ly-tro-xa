<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] AdminController gọn: chỉ khai báo traits. Mọi method nằm trong controllers/AdminTraits/.
// Model load qua autoloader của index.php như trước; RoomPriceChangeModel được dùng ở savePriceChange.
require_once BASE_PATH . 'models/room/RoomPriceChangeModel.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminHelperTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminSystemTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminRoomTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminMaintenanceTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminBillingTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminTenantTrait.php';
require_once BASE_PATH . 'controllers/AdminTraits/AdminModerationTrait.php';

class AdminController
{

    /**
     * Upload ảnh theo slot: home | area_new | area_{id}.
     * File được đặt vào thư mục con tương ứng trong .uploads và đổi tên theo ngữ cảnh.
     */
    public function uploadImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ.']);
            exit;
        }
        verify_csrf();

        $file = $_FILES['image'] ?? null;
        if (empty($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Chưa chọn được tệp ảnh hợp lệ.']);
            exit;
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Ảnh vượt quá 5MB.']);
            exit;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.']);
            exit;
        }

        $slot = trim((string)($_POST['slot'] ?? 'home'));
        $subFolder = 'image_page_home';
        $filePrefix = 'home-hero';
        if ($slot === 'area_new') {
            $subFolder = 'image_khu_new';
            $filePrefix = 'khu-new';
        } elseif (preg_match('/^area_(\d+)$/', $slot, $slotMatch)) {
            $subFolder = 'image_khu_' . (int)$slotMatch[1];
            $filePrefix = 'khu-' . (int)$slotMatch[1];
        } elseif ($slot === 'room_new') {
            $subFolder = 'image_phong_new';
            $filePrefix = 'phong-new';
        } elseif (preg_match('/^room_(\d+)$/', $slot, $slotMatch)) {
            $subFolder = 'image_phong_' . (int)$slotMatch[1];
            $filePrefix = 'phong-' . (int)$slotMatch[1];
        }

        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $fileName = $filePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.']);
            exit;
        }

        echo json_encode(['ok' => true, 'url' => BASE_URL . '.uploads/' . $subFolder . '/' . $fileName]);
        exit;
    }

    use AdminHelperTrait,
        AdminSystemTrait,
        AdminRoomTrait,
        AdminMaintenanceTrait,
        AdminBillingTrait,
        AdminTenantTrait,
        AdminModerationTrait;
}
