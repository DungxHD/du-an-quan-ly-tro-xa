<?php
/**
 * AdminController - Controller chính cho panel admin
 * Chỉ khai báo trait, mọi method nằm trong controllers/AdminTraits/
 * Model load qua autoloader index.php
 */
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
     * Upload ảnh theo slot: home | area_new | area_{id} | room_new | room_{id}
     * Trả về JSON: {ok: true, url} hoặc {ok: false, message}
     */
    public function uploadImage(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError(405, 'Phương thức không hợp lệ.');
        }
        verify_csrf();

        $file = $_FILES['image'] ?? null;
        if (empty($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError(400, 'Chưa chọn được tệp ảnh hợp lệ.');
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $this->jsonError(400, 'Ảnh vượt quá 5MB.');
        }

        $mime = $this->getMimeType($file['tmp_name']);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            $this->jsonError(400, 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
        }

        $slot = trim((string)($_POST['slot'] ?? 'home'));
        [$subFolder, $filePrefix] = $this->resolveSlot($slot);

        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

        $fileName = $filePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->jsonError(500, 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.');
        }

        echo json_encode(['ok' => true, 'url' => BASE_URL . '.uploads/' . $subFolder . '/' . $fileName]);
        exit;
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    private function jsonError(int $code, string $message): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'message' => $message]);
        exit;
    }

    private function getMimeType(string $tmpPath): string
    {
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : (string)($_FILES['image']['type'] ?? '');
        if ($finfo) finfo_close($finfo);
        return $mime;
    }

    private function resolveSlot(string $slot): array
    {
        $map = [
            'home'         => ['image_page_home', 'home-hero'],
            'area_new'     => ['image_khu_new', 'khu-new'],
            'room_new'     => ['image_phong_new', 'phong-new'],
        ];

        if (preg_match('/^area_(\d+)$/', $slot, $m)) {
            return ['image_khu_' . (int)$m[1], 'khu-' . (int)$m[1]];
        }
        if (preg_match('/^room_(\d+)$/', $slot, $m)) {
            return ['image_phong_' . (int)$m[1], 'phong-' . (int)$m[1]];
        }

        return $map[$slot] ?? $map['home'];
    }

    // ==========================================
    // TRAITS
    // ==========================================

    use AdminHelperTrait,
        AdminSystemTrait,
        AdminRoomTrait,
        AdminMaintenanceTrait,
        AdminBillingTrait,
        AdminTenantTrait,
        AdminModerationTrait;
}