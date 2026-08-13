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
    use AdminHelperTrait,
        AdminSystemTrait,
        AdminRoomTrait,
        AdminMaintenanceTrait,
        AdminBillingTrait,
        AdminTenantTrait,
        AdminModerationTrait;
}
