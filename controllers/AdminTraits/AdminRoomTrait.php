<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminRoomTrait
{
/** Suy ra mã khu: ưu tiên mã admin nhập; nếu không lấy chữ HOA đơn lập đầu tiên ("Khu A - ..." => "A") */
    private function deriveAreaCode($name, $override = '')
    {
        $override = mb_strtoupper(trim((string)$override));
        if ($override !== '') {
            $clean = preg_replace('/[^A-Z0-9]/u', '', $override);
            if ($clean !== '') {
                return mb_substr($clean, 0, 1);
            }
        }
        if (preg_match('/\b([A-Z])\b/u', (string)$name, $m)) {
            return $m[1];
        }
        $first = mb_substr(trim((string)$name), 0, 1);
        return mb_strtoupper($first) ?: 'K';
    }
/** Phòng đã đủ dữ liệu để đăng web chưa? */
    private function roomIsComplete(array $room)
    {
        return (float)($room['price'] ?? 0) > 0
            && (float)($room['area'] ?? 0) > 0
            && trim((string)($room['description'] ?? '')) !== '';
    }
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Tạo sẵn N phòng NHÁP đặt tên theo vị trí: 01, 02, 03...
     * Thay đổi: Bỏ areaCode khỏi tên, chỉ dùng số thứ tự 2 chữ số.
     */
    private function createRoomSlots($floorId, $floorNumber, $roomCount)
    {
        $created = 0;
        $existing = count(RoomModel::getAll(['floor_id' => (int)$floorId]));

        for ($i = 1; $i <= $roomCount; $i++) {
            $position = $existing + $i;
            RoomModel::save([
                'floor_id'      => (int)$floorId,
                'name'          => str_pad((string)$position, 2, '0', STR_PAD_LEFT),
                'position'      => $position,
                'price'         => 0,
                'area'          => 0,
                'max_occupancy' => 2,
                'description'   => '',
                'amenities'     => '',
                'status'        => 'draft',
            ], null);
            $created++;
        }
        return $created;
    }
/**
     * Quản lý khu theo schema mới `areas`.
     */
    public function areas()
    {
        $areas = AreaModel::getAllWithStats();
        $areaTree = AreaModel::getTree();
        $editId = (int)($_GET['edit'] ?? 0);
        $expandedAreaId = (int)($_GET['area'] ?? ($editId ?: 0));
        $editArea = $editId > 0 ? AreaModel::getById($editId) : null;
        $pageTitle = 'Quản lý Khu - NhaTroA';
        // [DEV-QWEN-A][NHOM-2][2026-08-13] Xử lý flash message popup xóa bị chặn
        $deleteBlocked = pullFlash('admin_delete_blocked');
        $areaMessage = pullFlash('admin_area_message');
        $areaError = pullFlash('admin_area_error');
        require_once BASE_PATH . 'views/admin/rooms/areas.php';
    }
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-14]
     * Xóa tầng CAO NHẤT (floor_number lớn nhất) của một khu.
     * Hiển thị số tầng cụ thể trong thông báo.
     */
    public function deleteHighestFloor($areaId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();

        $areaId = (int)$areaId;
        $area = AreaModel::getById($areaId);
        if (!$area) {
            setFlash('admin_area_error', 'Khu không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-areas');
        }

        $floors = FloorModel::getByAreaId($areaId);
        if (empty($floors)) {
            setFlash('admin_area_message', 'Khu "' . ($area['name'] ?? '') . '" chưa có tầng nào để xóa.');
            redirectTo('admin-areas', ['area' => $areaId]);
        }

        // Tìm tầng có floor_number LỚN NHẤT (tầng cao nhất)
        $highestFloor = null;
        $maxFloorNumber = 0;
        foreach ($floors as $floor) {
            $floorNumber = (int)($floor['floor_number'] ?? 0);
            if ($floorNumber > $maxFloorNumber) {
                $maxFloorNumber = $floorNumber;
                $highestFloor = $floor;
            }
        }

        if (!$highestFloor) {
            setFlash('admin_area_error', 'Không tìm thấy tầng cao nhất để xóa.');
            redirectTo('admin-areas', ['area' => $areaId]);
        }

        $highestFloorId = (int)($highestFloor['id'] ?? 0);
        $highestFloorNumber = (int)($highestFloor['floor_number'] ?? 0);
        $rentedCount = 0;
        foreach ($floors as $floor) {
            if ((int)($floor['id'] ?? 0) === $highestFloorId) {
                $rentedCount = (int)($floor['rented_count'] ?? 0);
                break;
            }
        }

        if ($rentedCount > 0) {
            setFlash('admin_delete_blocked', [
                'type' => 'top_floor',
                'area_name' => $area['name'] ?? '',
                'floor_name' => $highestFloor['name'] ?? '',
                'floor_number' => $highestFloorNumber,
                'rented_count' => $rentedCount,
                'return_url' => BASE_URL . '?page=admin-areas&area=' . $areaId,
                'message' => 'Tầng "' . ($highestFloor['name'] ?? '') . '" (Tầng ' . $highestFloorNumber . ') của khu "' . ($area['name'] ?? '') . '" đang có ' . $rentedCount . ' phòng đang thuê. Không thể xóa tầng này khi còn phòng đang thuê.',
            ]);
            redirectTo('admin-areas', ['area' => $areaId]);
        }

        FloorModel::delete($highestFloorId);
        setFlash('admin_area_message', 'Đã xóa Tầng ' . $highestFloorNumber . ' (tầng cao nhất) của khu "' . ($area['name'] ?? '') . '".');
        redirectTo('admin-areas', ['area' => $areaId]);
    }
/**
     * Danh sách phòng admin theo schema mới `areas -> floors -> rooms`.
     * Màn hình này gom cả filter, form thêm/sửa và bảng thao tác nhanh trạng thái.
     */
    public function rooms()
    {
        $filters = $this->getRoomAdminFilters($_GET);
        $areas = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $selectedFloor = $filters['floor_id'] > 0 ? FloorModel::getById($filters['floor_id']) : null;

        // [DEV-QWEN-A][NHOM-2][2026-08-13] Logic bộ lọc cải tiến:
        // 1. Khi chọn "Tất cả khu" (area_id = 0) thì floor_id luôn bị reset về 0 và bị khóa
        // 2. Khi chọn một khu cụ thể thì floor_id sẽ được phép chọn và chỉ hiển thị tầng của khu đó
        // 3. Khi chuyển đổi khu (từ khu A sang khu B), floor_id tự động reset về 0 ("Tất cả tầng")
        if ($filters['area_id'] <= 0) {
            // Đang ở "Tất cả khu" → ép floor về 0 và disable
            $filters['floor_id'] = 0;
            $selectedFloor = null;
        }

        if ($selectedFloor) {
            $floorAreaId = (int)($selectedFloor['area_id'] ?? 0);
            // Bảo vệ URL: nếu floor thuộc khu khác với area_id đang chọn, reset floor
            if ($filters['area_id'] > 0 && $filters['area_id'] !== $floorAreaId) {
                $filters['floor_id'] = 0;
                $selectedFloor = null;
            } else {
                $filters['area_id'] = $floorAreaId;
            }
        }

        $rooms = array_map(static function ($room) {
            $room['occupant_count'] = RoomModel::countOccupants($room['id'] ?? 0);
            return $room;
        }, RoomModel::getAll($filters));
        $pendingRoomPriceChanges = [];
        foreach ($rooms as $room) {
            $roomId = (int)($room['id'] ?? 0);
            if ($roomId <= 0 || ($room['status'] ?? '') !== 'rented') {
                continue;
            }
            $pendingChanges = RoomPriceChangeModel::getPendingByRoom($roomId);
            if (!empty($pendingChanges)) {
                $pendingRoomPriceChanges[$roomId] = $pendingChanges[0];
            }
        }

        $editId = (int)($_GET['edit'] ?? 0);
        $editRoom = $editId > 0 ? RoomModel::getById($editId) : null;
        $oldRoomInput = pullFlash('admin_room_old');
        $formRoom = is_array($oldRoomInput) ? $oldRoomInput : ($editRoom ?? null);

        $selectedAreaId = $filters['area_id'];
        // [DEV-QWEN-A][FIX-FILTER][2026-08-13] BỎ fallback ép chọn khu đầu tiên
        // Không ép area_id khi user chọn "Tất cả khu"

        $formAreaId = (int)($formRoom['area_id'] ?? ($editRoom['area_id'] ?? $selectedAreaId));
        if ($formAreaId <= 0 && !empty($areas[0]['id'])) {
            $formAreaId = (int)$areas[0]['id'];
        }

        // [DEV-QWEN-A][NHOM-2][2026-08-13] Chỉ load floors khi có khu được chọn
        $filterFloors = $selectedAreaId > 0 ? FloorModel::getByAreaId($selectedAreaId) : [];
        $formFloors = $formAreaId > 0 ? FloorModel::getByAreaId($formAreaId) : [];
        $roomMessage = pullFlash('admin_room_message');
        $roomError = pullFlash('admin_room_error');
        // [DEV-QWEN-A][NHOM-2][2026-08-13] Xử lý flash message popup xóa bị chặn
        $deleteBlocked = pullFlash('admin_delete_blocked');
        $pageTitle = 'Quản lý Phòng - NhaTroA';
        $drawerOpenFlag = pullFlash('admin_room_drawer_open');
        require_once BASE_PATH . 'views/admin/rooms/rooms.php';
    }
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Chức năng: Tạo/sửa khu.
     * Thay đổi:
     *   - Bỏ area_code, tự suy mã từ tên
     *   - Ảnh khu: xử lý file upload thay vì URL text
     *   - Floor builder mới: nhận floor_rooms[N] từ hidden inputs
     */
    public function saveArea()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $amenityValues = array_values(array_unique(array_filter(array_map(
            static fn($value) => trim((string)$value),
            explode(',', (string)($_POST['amenities'] ?? ''))
        ), static fn($value) => $value !== '')));
        $amenityValues = array_slice(array_map(static fn($value) => mb_substr($value, 0, 80), $amenityValues), 0, 20);

        $data = [
            'name'        => trim((string)($_POST['name'] ?? '')),
            'address'     => trim((string)($_POST['address'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'image'       => '',
        ];

        $returnParams = $id > 0 ? ['edit' => $id] : [];
        if ($id > 0 && !AreaModel::getById($id)) {
            setFlash('admin_area_error', 'Khu cần cập nhật không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-areas');
        }
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 120) {
            setFlash('admin_area_error', 'Tên khu phải có từ 2 đến 120 ký tự.');
            redirectTo('admin-areas', $returnParams);
        }
        if (mb_strlen($data['address']) < 5 || mb_strlen($data['address']) > 255) {
            setFlash('admin_area_error', 'Địa chỉ khu phải có từ 5 đến 255 ký tự.');
            redirectTo('admin-areas', $returnParams);
        }
        if (mb_strlen($data['description']) > 2000) {
            setFlash('admin_area_error', 'Mô tả khu không được vượt quá 2.000 ký tự.');
            redirectTo('admin-areas', $returnParams);
        }

        // === XỬ LÝ UPLOAD ẢNH KHU ===
        $uploadedImageUrl = $this->handleAreaImageUpload($id);
        if ($uploadedImageUrl === false) {
            redirectTo('admin-areas', $returnParams);
        }
        if ($uploadedImageUrl !== null) {
            $data['image'] = $uploadedImageUrl;
        } elseif ($id > 0) {
            // Khi sửa, nếu không upload ảnh mới thì giữ ảnh cũ
            $existingArea = AreaModel::getById($id);
            $data['image'] = $existingArea['image'] ?? '';
        }

        // ==== CHỨC NĂNG SỬA: chỉ cập nhật thông tin khu ====
        if ($id > 0) {
            AreaModel::save($data, $id);
            setFlash('admin_area_message', 'Đã cập nhật thông tin khu.');
            redirectTo('admin-areas', ['area' => $id]);
        }

        // ==== CHỨC NĂNG TẠO MỚI: khu -> tầng -> phòng nháp ====
        $rawFloorCount = filter_var($_POST['floor_count'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 50],
        ]);
        if ($rawFloorCount === false) {
            setFlash('admin_area_error', 'Số tầng phải là số nguyên từ 1 đến 50.');
            redirectTo('admin-areas');
        }
        $floorCount = (int)$rawFloorCount;
        $floorRooms = is_array($_POST['floor_rooms'] ?? null) ? $_POST['floor_rooms'] : [];
        $roomLimits = [];
        for ($n = 1; $n <= $floorCount; $n++) {
            if (!array_key_exists($n, $floorRooms)) {
                $roomLimits[$n] = 0;
                continue;
            }
            $roomLimit = filter_var($floorRooms[$n], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 50],
            ]);
            if ($roomLimit === false) {
                setFlash('admin_area_error', 'Số phòng của mỗi tầng phải là số nguyên từ 0 đến 50.');
                redirectTo('admin-areas');
            }
            $roomLimits[$n] = (int)$roomLimit;
        }

        $areaId = (int)AreaModel::save($data, null);
        $createdRooms = 0;

        for ($n = 1; $n <= $floorCount; $n++) {
            $roomLimit = $roomLimits[$n];
            $floorId = (int)FloorModel::save([
                'area_id'      => $areaId,
                'name'         => 'Tầng ' . $n,
                'floor_number' => $n,
                'room_limit'   => $roomLimit,
            ], null);
            $createdRooms += $this->createRoomSlots($floorId, $n, $roomLimit);
        }

        setFlash(
            'admin_area_message',
            "Đã tạo khu với {$floorCount} tầng và {$createdRooms} phòng chưa có thông tin. " .
                "Hệ thống đã chuyển sang Quản lý Phòng — hoàn thiện từng phòng để đăng lên website."
        );
        redirectTo('admin-rooms', ['area_id' => $areaId]);
    }
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-07]
     * Xử lý upload ảnh khu từ file. Trả về URL, null nếu không có file, false nếu file không hợp lệ.
     */
    private function handleAreaImageUpload($areaId = 0)
    {
        $file = $_FILES['area_image'] ?? null;
        if (empty($file)) {
            return null;
        }
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            setFlash('admin_area_error', 'Tải ảnh khu lên không thành công. Vui lòng thử lại.');
            return false;
        }
        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            setFlash('admin_area_error', 'Ảnh khu vượt quá 5MB.');
            return false;
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowedMimes[$mime])) {
            setFlash('admin_area_error', 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
            return false;
        }

        $subFolder = $areaId > 0 ? 'image_khu_' . (int)$areaId : 'image_khu_new';
        $uploadDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $fileName = 'khu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            setFlash('admin_area_error', 'Không lưu được tệp ảnh. Kiểm tra thư mục .uploads.');
            return false;
        }

        return BASE_URL . '.uploads/' . $subFolder . '/' . $fileName;
    }
/** Thêm nhanh 1 tầng (kèm tùy chọn tạo sẵn phòng), vẫn tôn trọng room_limit */
    public function addFloorQuick()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();
        $areaId = (int)($_POST['area_id'] ?? 0);
        $area = $areaId > 0 ? AreaModel::getById($areaId) : null;
        if (!$area) {
            setFlash('admin_area_error', 'Khu không tồn tại.');
            redirectTo('admin-areas');
        }
        $next = 1;
        foreach (FloorModel::getByAreaId($areaId) as $floor) {
            $next = max($next, (int)($floor['floor_number'] ?? 0) + 1);
        }
        $roomLimit = max(0, min(50, (int)($_POST['room_count'] ?? 0)));
        $areaCode = $this->deriveAreaCode($area['name'] ?? '', '');
        $floorId = (int)FloorModel::save([
            'area_id' => $areaId,
            'name' => 'Tầng ' . $next,
            'floor_number' => $next,
            'room_limit' => $roomLimit,
        ], null);
        $created = $this->createRoomSlots($floorId, $next, $roomLimit);
        setFlash('admin_room_message', "Đã thêm Tầng {$next}" . ($created > 0 ? " với {$created} phòng chưa có thông tin." : '.'));
        redirectTo('admin-rooms', ['area_id' => $areaId, 'floor_id' => 0]);
    }
/**
     * Xóa khu theo schema mới. DB sẽ tự cascade tầng và phòng liên quan.
     */
    public function deleteArea($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-areas');
        }
        verify_csrf();
        $areaId = (int)$id;
        $area = $areaId > 0 ? AreaModel::getById($areaId) : null;
        if (!$area) {
            setFlash('admin_area_error', 'Khu không tồn tại hoặc đã bị xóa.');
            redirectTo('admin-areas');
        }

        $roomCount = 0;
        $rentedCount = 0;
        foreach (FloorModel::getByAreaId($areaId) as $floor) {
            $roomCount += (int)($floor['room_count'] ?? 0);
            $rentedCount += (int)($floor['rented_count'] ?? 0);
        }
        if ($rentedCount > 0) {
            // [DEV-QWEN-A][NHOM-2][2026-08-13]
            // Cải thiện popup: lưu message + trạng thái để view hiển thị modal popup có button "Quay lại"
            setFlash('admin_delete_blocked', [
                'type' => 'area',
                'name' => $area['name'] ?? '',
                'rented_count' => $rentedCount,
                'return_url' => BASE_URL . '?page=admin-areas&area=' . $areaId,
                'message' => 'Khu "' . ($area['name'] ?? '') . '" đang có ' . $rentedCount . ' phòng đang thuê. Không thể xóa khu này.',
            ]);
            redirectTo('admin-areas');
        }

        AreaModel::delete($areaId);
        setFlash('admin_area_message', 'Đã xóa khu ' . ($area['name'] ?? '') . ($roomCount > 0 ? ' cùng ' . $roomCount . ' phòng chưa thuê.' : '.'));

        redirectTo('admin-areas');
    }
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
/** PHÒNG MỚI: chuyển ảnh từng upload vào image_phong_new sang image_phong_{id}. */
    private function finalizeNewRoomImage($roomId, $imageUrl)
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== 'image_phong_new') {
            return $imageUrl;
        }
        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_' . (int)$roomId;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        $fileName = basename($local);
        $dest = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@rename($local, $dest)) {
            return $imageUrl;
        }
        return BASE_URL . '.uploads/image_phong_' . (int)$roomId . '/' . $fileName;
    }
/** Dọn ảnh nháp bỏ dở trong image_phong_new sau khi đã finalize cho phòng mới. */
    private function cleanupDraftRoomImages()
    {
        $dir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_new';
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
public function saveRoom()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-rooms');
        }
        verify_csrf();
        $redirectParams = $this->getRoomAdminFilters($_POST);
        $id = (int)($_POST['id'] ?? 0);
        $editRoom = $id > 0 ? RoomModel::getById($id) : null;
        $status = $this->normalizeRoomStatus($_POST['status'] ?? 'draft', 'draft');

        if (!empty($_POST['quick_status_update'])) {
            $room = RoomModel::getById($id);
            if (!$room) {
                setFlash('admin_room_error', 'Phòng không tồn tại.');
                redirectTo('admin-rooms', $redirectParams);
            }
            if (RoomModel::countOccupants($id) > 0) {
                setFlash('admin_room_error', 'Phòng đang có người thuê — trạng thái do hệ thống quản lý.');
                redirectTo('admin-rooms', $redirectParams);
            }
            if ($status === 'available' && !$this->roomIsComplete($room)) {
                setFlash('admin_room_error', 'Phòng chưa đủ thông tin nên không thể chuyển sang Còn trống.');
                redirectTo('admin-rooms', $redirectParams);
            }
            RoomModel::updateStatus($id, $status);
            setFlash('admin_room_message', 'Đã cập nhật trạng thái phòng.');
            redirectTo('admin-rooms', $redirectParams);
        }

        $data = [
            'floor_id'      => (int)($_POST['floor_id'] ?? 0),
            'name'          => trim((string)($_POST['name'] ?? '')),
            'position'      => (int)($_POST['position'] ?? 0),
            'price'         => (float)($_POST['price'] ?? 0),
            'area'          => (float)($_POST['area'] ?? 0),
            'max_occupancy' => (int)($_POST['max_occupancy'] ?? 2),
            'description'   => trim((string)($_POST['description'] ?? '')),
        ];

        // [DEV-QWEN-A][FIX][2026-08-14] Khóa giá hoàn toàn đối với phòng đang thuê:
        // giữ nguyên giá cũ dù request có cố tình gửi giá mới.
        if ($id > 0 && $editRoom && ($editRoom['status'] ?? '') === 'rented') {
            $data['price'] = (float)($editRoom['price'] ?? 0);
        }

        // [DEV-QWEN-A][NHOM-2][2026-08-13] Fix: khai báo $amenityValues trước khi dùng implode
        $amenityValues = array_values(array_unique(array_filter(array_map(
            static fn($value) => trim((string)$value),
            is_array($_POST['amenities'] ?? null) ? $_POST['amenities'] : explode(',', (string)($_POST['amenities'] ?? ''))
        ), static fn($value) => $value !== '')));
        $amenityValues = array_slice(array_map(static fn($value) => mb_substr($value, 0, 80), $amenityValues), 0, 20);
        $data['amenities'] = implode(', ', $amenityValues);

        $formState = array_merge($data, ['id' => $id, 'area_id' => (int)($_POST['area_id'] ?? 0)]);

        $floor = RoomModel::floorExists($data['floor_id']) ? FloorModel::getById($data['floor_id']) : null;
        if (!$floor) {
            setFlash('admin_room_error', 'Tầng không hợp lệ.');
            setFlash('admin_room_old', $formState);
            redirectTo('admin-rooms', $redirectParams);
        }

        $missing = [];
        if ($data['price'] <= 0) {
            $missing[] = 'Giá thuê (phải > 0)';
        }
        if ($data['area'] <= 0) {
            $missing[] = 'Diện tích (phải > 0)';
        }
        if ($data['max_occupancy'] <= 0) {
            $missing[] = 'Sức chứa tối đa (phải >= 1)';
        }
        if ($data['description'] === '') {
            $missing[] = 'Mô tả';
        }
        if (!empty($missing)) {
            setFlash('admin_room_error', 'Không thể lưu phòng. Thiếu: ' . implode(', ', $missing) . '.');
            setFlash('admin_room_old', $formState);
            redirectTo('admin-rooms', $redirectParams);
        }

        $primaryImage = trim((string)($_POST['primary_image'] ?? $_POST['thumbnail'] ?? ''));
        $submittedGalleryImages = array_slice(array_map(
            static fn($value) => trim((string)$value),
            (array)($_POST['gallery_images'] ?? [])
        ), 0, 3);
        $galleryImages = array_values(array_filter($submittedGalleryImages, static fn($value) => $value !== ''));

        // Form sửa không có thao tác xóa ảnh. Nếu browser không gửi lại một slot ảnh,
        // giữ nguyên ảnh hiện có thay vì sync gallery rỗng làm mất dữ liệu.
        if ($id > 0 && $editRoom) {
            $existingImages = RoomImageModel::getByRoom($id);
            $existingPrimary = '';
            $existingGallery = [];
            foreach ($existingImages as $image) {
                $url = trim((string)($image['image_url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                if ((int)($image['is_primary'] ?? 0) === 1) {
                    $existingPrimary = $url;
                } else {
                    $existingGallery[] = $url;
                }
            }
            if ($primaryImage === '') {
                $primaryImage = $existingPrimary ?: trim((string)($editRoom['thumbnail'] ?? ''));
            }
            $galleryImages = [];
            for ($index = 0; $index < 3; $index++) {
                $url = trim((string)($submittedGalleryImages[$index] ?? ''));
                if ($url === '') {
                    $url = trim((string)($existingGallery[$index] ?? ''));
                }
                if ($url !== '') {
                    $galleryImages[] = $url;
                }
            }
        }

        if ($id === 0) {
            $limit = (int)($floor['room_limit'] ?? 0);
            $currentCount = count(RoomModel::getAll(['floor_id' => (int)$floor['id']]));
            if ($limit > 0 && $currentCount >= $limit) {
                if (!empty($_POST['extend_limit'])) {
                    Database::update('floors', ['room_limit' => $limit + 1], 'id = :id', ['id' => (int)$floor['id']]);
                } else {
                    setFlash('admin_room_error', "Tầng này đã đạt giới hạn {$limit} phòng.");
                    setFlash('admin_room_old', $formState);
                    redirectTo('admin-rooms', $redirectParams);
                }
            }
            if ($data['position'] <= 0) {
                $data['position'] = $currentCount + 1;
            }
            if ($data['name'] === '') {
                $area = AreaModel::getById((int)$floor['area_id']);
                $code = $this->deriveAreaCode($area['name'] ?? '', '');
                $data['name'] = $code . (int)$floor['floor_number'] . str_pad((string)$data['position'], 2, '0', STR_PAD_LEFT);
            }
        }

        $occupants = $id > 0 ? RoomModel::countOccupants($id) : 0;
        $data['status'] = $occupants > 0 ? 'rented' : (in_array($status, ['draft', 'available', 'maintenance'], true) ? $status : 'draft');
        if ($primaryImage !== '') {
            $data['thumbnail'] = $primaryImage;
        }

        $savedRoomId = (int)RoomModel::save($data, $id > 0 ? $id : null);
        // === PRICE CHANGE LOGIC ===
        $pendingPriceMessage = '';
        if ($id > 0 && $editRoom) {
            $oldPrice = (float)($editRoom['price'] ?? 0);
            $newPrice = (float)($data['price'] ?? 0);
            $priceChanged = abs($oldPrice - $newPrice) > 0.01;

            // Nếu phòng đang rented và giá thay đổi → schedule change
            if ($editRoom['status'] === 'rented' && $priceChanged) {
                $effectiveMonth = (int)($_POST['price_effective_month'] ?? 0);
                $effectiveYear = (int)($_POST['price_effective_year'] ?? 0);

                // Validate tháng áp dụng (min = tháng sau)
                $currentOrder = ((int)date('Y') * 100) + (int)date('n');
                $minOrder = $currentOrder + 1;

                if ($effectiveYear === 0 || $effectiveMonth === 0) {
                    // Mặc định: tháng sau
                    $effectiveMonth = (int)date('n') + 1;
                    $effectiveYear = (int)date('Y');
                    if ($effectiveMonth > 12) {
                        $effectiveMonth = 1;
                        $effectiveYear++;
                    }
                }

                $order = ($effectiveYear * 100) + $effectiveMonth;
                if ($order < $minOrder) {
                    setFlash('admin_room_error', 'Tháng áp dụng giá mới phải từ tháng sau trở đi.');
                    setFlash('admin_room_old', $formState);
                    redirectTo('admin-rooms', $redirectParams);
                }

                // Schedule price change (ghi đè bản cũ nếu có)
                $deleted = RoomPriceChangeModel::scheduleChange(
                    $savedRoomId,
                    $oldPrice,
                    $newPrice,
                    $effectiveMonth,
                    $effectiveYear,
                    (int)($_SESSION['user_id'] ?? 0)
                );

                // Revert price về giá cũ (chưa áp dụng ngay)
                Database::update('rooms', ['price' => $oldPrice], 'id = :id', ['id' => $savedRoomId]);

                $msg = 'Giá mới ' . number_format($newPrice, 0, ',', '.') . 'đ sẽ áp dụng từ tháng '
                    . str_pad((string)$effectiveMonth, 2, '0', STR_PAD_LEFT) . '/' . $effectiveYear . '.';
                if ($deleted > 0) {
                    $msg .= ' (Đã hủy ' . $deleted . ' lịch thay đổi giá trước đó.)';
                }
                $pendingPriceMessage = $msg;
            }

            // Nếu status chuyển từ rented → available/draft/maintenance → apply pending price ngay
            if ($editRoom['status'] === 'rented' && $status !== 'rented') {
                $applied = RoomPriceChangeModel::applyPendingImmediately($savedRoomId);
                if ($applied > 0) {
                    $pendingPriceMessage = 'Đã áp dụng ' . $applied . ' thay đổi giá chờ cho phòng.';
                }
            }
        }

        // Dời ảnh từ image_phong_new -> image_phong_{id}
        $movedPrimary = $this->finalizeNewRoomImage($savedRoomId, $primaryImage);
        foreach ($galleryImages as $index => $url) {
            $galleryImages[$index] = $this->finalizeNewRoomImage($savedRoomId, $url);
        }
        $primaryImage = $movedPrimary;   // QUAN TRỌNG: gán lại để sync lưu URL đã dời
        if ($primaryImage !== '') {
            Database::update('rooms', ['thumbnail' => $primaryImage], 'id = :id', ['id' => $savedRoomId]);
        }
        $this->cleanupDraftRoomImages();

        RoomImageModel::syncForRoom($savedRoomId, $primaryImage, $galleryImages);

        setFlash('admin_room_message', $pendingPriceMessage !== ''
            ? $pendingPriceMessage
            : ($data['status'] === 'draft' ? 'Đã lưu phòng CHƯA CÓ THÔNG TIN — chưa hiển thị web.' : 'Đã lưu phòng và đăng lên website.'));
        // [DEV-QWEN-A][FIX-FILTER][2026-08-14] Giữ nguyên bộ lọc đang dùng (return_* từ form) khi redirect
        // sau khi lưu, thay vì ép về khu/tầng của phòng vừa sửa.
        redirectTo('admin-rooms', $redirectParams);
    }
/**
     * [DEV-QWEN-A][NHOM-2][2026-08-08]
     * FIX: chặn xóa phòng khi status = 'rented' (ngoài chặn cũ khi còn người ở).
     * Backend là chốt chặn cuối — dù view có bị bypass cũng không xóa được.
     */
    public function deleteRoom($id)
    {
        $redirectParams = $this->getRoomAdminFilters($_GET);
        $room = RoomModel::getById($id);
        if (!$room) {
            setFlash('admin_room_error', 'Phòng không tồn tại hoặc đã bị xóa trước đó.');
            redirectTo('admin-rooms', $redirectParams);
        }
        if (RoomModel::hasActiveOccupants($id)) {
            setFlash('admin_room_error', 'Phòng đang có người ở! Không thể xóa.');
            redirectTo('admin-rooms', $redirectParams);
        }
        if ((string)($room['status'] ?? '') === 'rented') {
            setFlash('admin_room_error', 'Phòng "' . ($room['name'] ?? '') . '" đang ở trạng thái đã thuê — hệ thống chặn xóa. Hãy kết thúc hợp đồng hoặc chuyển trạng thái trước.');
            redirectTo('admin-rooms', $redirectParams);
        }
        RoomImageModel::deleteByRoom($id);
        RoomModel::delete($id);
        setFlash('admin_room_message', 'Đã xóa phòng thành công.');
        redirectTo('admin-rooms', $redirectParams);
    }
/**
     * Chuẩn hóa bộ filter cho trang admin-rooms để cả GET lẫn POST dùng chung một nguồn.
     */
    private function getRoomAdminFilters(array $source)
    {
        $status = $this->normalizeRoomStatus($source['return_status'] ?? ($source['status'] ?? ''), '');

        return [
            'area_id' => (int)($source['return_area_id'] ?? ($source['area_id'] ?? 0)),
            'floor_id' => (int)($source['return_floor_id'] ?? ($source['floor_id'] ?? 0)),
            'status' => $status,
        ];
    }
/**
     * Chỉ cho phép các trạng thái hợp lệ của phòng để tránh update sai enum.
     */
    private function normalizeRoomStatus($status, $default = '')
    {
        $allowedStatuses = ['draft', 'available', 'rented', 'maintenance'];
        return in_array($status, $allowedStatuses, true) ? $status : $default;
    }

}
