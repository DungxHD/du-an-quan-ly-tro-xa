<?php
/**
 * AdminRoomTrait - Quản lý phòng & khu: areas, floors, rooms, image upload, price changes
 */
trait AdminRoomTrait
{
    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Suy ra mã khu: ưu tiên override; nếu không lấy chữ HOA đầu tiên ("Khu A - ..." => "A")
     */
    private function deriveAreaCode(string $name, string $override = ''): string
    {
        $override = mb_strtoupper(trim((string)$override));
        if ($override !== '') {
            $clean = preg_replace('/[^A-Z0-9]/u', '', $override);
            if ($clean !== '') return mb_substr($clean, 0, 1);
        }
        if (preg_match('/\b([A-Z])\b/u', (string)$name, $m)) return $m[1];
        $first = mb_substr(trim((string)$name), 0, 1);
        return mb_strtoupper($first) ?: 'K';
    }

    /**
     * Kiểm tra phòng đã đủ dữ liệu để đăng web chưa
     */
    private function roomIsComplete(array $room): bool
    {
        return (float)($room['price'] ?? 0) > 0
            && (float)($room['area'] ?? 0) > 0
            && trim((string)($room['description'] ?? '')) !== '';
    }

    /**
     * Tạo N phòng nháp theo vị trí: 01, 02, 03...
     */
    private function createRoomSlots(int $floorId, int $floorNumber, int $roomCount): int
    {
        $created = 0;
        $existing = count(RoomModel::getAll(['floor_id' => $floorId]));

        for ($i = 1; $i <= $roomCount; $i++) {
            $position = $existing + $i;
            RoomModel::save([
                'floor_id'      => $floorId,
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

    // ==========================================
    // AREAS
    // ==========================================

    /**
     * Quản lý khu: list, edit form, floor builder
     */
    public function areas(): void
    {
        $areas          = AreaModel::getAllWithStats();
        $areaTree       = AreaModel::getTree();
        $editId         = (int)($_GET['edit'] ?? 0);
        $expandedAreaId = (int)($_GET['area'] ?? ($editId ?: 0));
        $editArea       = $editId > 0 ? AreaModel::getById($editId) : null;
        $deleteBlocked  = pullFlash('admin_delete_blocked');
        $areaMessage    = pullFlash('admin_area_message');
        $areaError      = pullFlash('admin_area_error');
        $pageTitle      = 'Quản lý Khu - NhaTroA';
        require_once BASE_PATH . 'views/admin/rooms/areas.php';
    }

    /**
     * Xóa tầng CAO NHẤT của một khu (chặn nếu có phòng đang thuê)
     */
    public function deleteHighestFloor(int $areaId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-areas');
        verify_csrf();

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

        // Tìm tầng cao nhất (floor_number max)
        $highestFloor = null;
        $maxFloorNum   = 0;
        foreach ($floors as $floor) {
            $fn = (int)($floor['floor_number'] ?? 0);
            if ($fn > $maxFloorNum) {
                $maxFloorNum = $fn;
                $highestFloor = $floor;
            }
        }

        if (!$highestFloor) {
            setFlash('admin_area_error', 'Không tìm thấy tầng cao nhất để xóa.');
            redirectTo('admin-areas', ['area' => $areaId]);
        }

        $highestFloorId   = (int)$highestFloor['id'];
        $highestFloorNum  = (int)$highestFloor['floor_number'];
        $rentedCount      = 0;
        foreach ($floors as $floor) {
            if ((int)$floor['id'] === $highestFloorId) {
                $rentedCount = (int)($floor['rented_count'] ?? 0);
                break;
            }
        }

        if ($rentedCount > 0) {
            setFlash('admin_delete_blocked', [
                'type' => 'top_floor',
                'area_name'     => $area['name'] ?? '',
                'floor_name'    => $highestFloor['name'] ?? '',
                'floor_number'  => $highestFloorNum,
                'rented_count'  => $rentedCount,
                'return_url'    => BASE_URL . '?page=admin-areas&area=' . $areaId,
                'message'       => 'Tầng "' . ($highestFloor['name'] ?? '') . '" (Tầng ' . $highestFloorNum . ') của khu "' . ($area['name'] ?? '') . '" đang có ' . $rentedCount . ' phòng đang thuê. Không thể xóa tầng này khi còn phòng đang thuê.',
            ]);
            redirectTo('admin-areas', ['area' => $areaId]);
        }

        FloorModel::delete($highestFloorId);
        setFlash('admin_area_message', 'Đã xóa Tầng ' . $highestFloorNum . ' (tầng cao nhất) của khu "' . ($area['name'] ?? '') . '".');
        redirectTo('admin-areas', ['area' => $areaId]);
    }

    // ==========================================
    // ROOMS
    // ==========================================

    /**
     * Danh sách phòng admin: filter, form thêm/sửa, bảng thao tác nhanh
     */
    public function rooms(): void
    {
        $filters = $this->getRoomAdminFilters($_GET);
        $areas   = AreaModel::getAllWithStats();
        $allFloors = FloorModel::getAll();
        $selectedFloor = $filters['floor_id'] > 0 ? FloorModel::getById($filters['floor_id']) : null;

        // Filter logic: area=0 -> floor disabled; floor thuộc khu khác -> reset floor
        if ($filters['area_id'] <= 0) {
            $filters['floor_id'] = 0;
            $selectedFloor = null;
        } elseif ($selectedFloor) {
            $floorAreaId = (int)($selectedFloor['area_id'] ?? 0);
            if ($filters['area_id'] > 0 && $filters['area_id'] !== $floorAreaId) {
                $filters['floor_id'] = 0;
                $selectedFloor = null;
            } else {
                $filters['area_id'] = $floorAreaId;
            }
        }

        $rooms = array_map(fn($r) => ['occupant_count' => RoomModel::countOccupants($r['id'] ?? 0)] + $r, RoomModel::getAll($filters));

        // Pending price changes per room
        $pendingRoomPriceChanges = [];
        foreach ($rooms as $room) {
            $rid = (int)($room['id'] ?? 0);
            if ($rid <= 0 || ($room['status'] ?? '') !== 'rented') continue;
            $pending = RoomPriceChangeModel::getPendingByRoom($rid);
            if (!empty($pending)) $pendingRoomPriceChanges[$rid] = $pending[0];
        }

        $editId       = (int)($_GET['edit'] ?? 0);
        $editRoom     = $editId > 0 ? RoomModel::getById($editId) : null;
        $oldRoomInput = pullFlash('admin_room_old');
        $formRoom     = is_array($oldRoomInput) ? $oldRoomInput : ($editRoom ?? null);
        $selectedAreaId = $filters['area_id'];
        $filterFloors   = $selectedAreaId > 0 ? FloorModel::getByAreaId($selectedAreaId) : [];

        // Form area/floor
        $formAreaId = (int)($formRoom['area_id'] ?? ($editRoom['area_id'] ?? $selectedAreaId));
        if ($formAreaId <= 0 && !empty($areas[0]['id'])) $formAreaId = (int)$areas[0]['id'];
        $formFloors = $formAreaId > 0 ? FloorModel::getByAreaId($formAreaId) : [];

        $roomMessage    = pullFlash('admin_room_message');
        $roomError      = pullFlash('admin_room_error');
        $deleteBlocked  = pullFlash('admin_delete_blocked');
        $drawerOpenFlag = pullFlash('admin_room_drawer_open');
        $pageTitle = 'Quản lý Phòng - NhaTroA';

        require_once BASE_PATH . 'views/admin/rooms/rooms.php';
    }

    // ==========================================
    // SAVE AREA
    // ==========================================

    /**
     * Tạo/Cập nhật khu + floor builder + tạo phòng nháp
     */
    public function saveArea(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-areas');
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $amenityValues = $this->normalizeAmenities($_POST['amenities'] ?? '');

        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'address'     => trim($_POST['address'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'image'       => '',
        ];

        $returnParams = $id > 0 ? ['edit' => $id] : [];
        if ($id > 0 && !AreaModel::getById($id)) {
            setFlash('admin_area_error', 'Khu cần cập nhật không tồn tại.');
            redirectTo('admin-areas');
        }
        $this->validateAreaData($data, $returnParams);

        // Upload ảnh
        $uploadedImageUrl = $this->handleAreaImageUpload($id);
        if ($uploadedImageUrl === false) redirectTo('admin-areas', $returnParams);
        if ($uploadedImageUrl !== null) $data['image'] = $uploadedImageUrl;
        elseif ($id > 0) $data['image'] = AreaModel::getById($id)['image'] ?? '';

        // Update only
        if ($id > 0) {
            AreaModel::save($data, $id);
            setFlash('admin_area_message', 'Đã cập nhật thông tin khu.');
            redirectTo('admin-areas', ['area' => $id]);
        }

        // Create new: khu -> floors -> rooms
        $floorCount = (int)filter_var($_POST['floor_count'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
        if ($floorCount === false) { setFlash('admin_area_error', 'Số tầng phải là số nguyên từ 1 đến 50.'); redirectTo('admin-areas'); }

        $floorRooms = is_array($_POST['floor_rooms'] ?? null) ? $_POST['floor_rooms'] : [];
        $roomLimits = [];
        for ($n = 1; $n <= $floorCount; $n++) {
            $limit = filter_var($floorRooms[$n] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]]);
            if ($limit === false) { setFlash('admin_area_error', 'Số phòng mỗi tầng phải là 0-50.'); redirectTo('admin-areas'); }
            $roomLimits[$n] = (int)$limit;
        }

        $areaId = (int)AreaModel::save($data, null);
        $createdRooms = 0;
        for ($n = 1; $n <= $floorCount; $n++) {
            $floorId = (int)FloorModel::save([
                'area_id'      => $areaId,
                'name'         => 'Tầng ' . $n,
                'floor_number' => $n,
                'room_limit'   => $roomLimits[$n],
            ], null);
            $createdRooms += $this->createRoomSlots($floorId, $n, $roomLimits[$n]);
        }

        setFlash('admin_area_message', "Đã tạo khu với {$floorCount} tầng và {$createdRooms} phòng chưa có thông tin. Hệ thống đã chuyển sang Quản lý Phòng — hoàn thiện từng phòng để đăng lên website.");
        redirectTo('admin-rooms', ['area_id' => $areaId]);
    }

    private function validateAreaData(array &$data, array $returnParams): void
    {
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 120) { setFlash('admin_area_error', 'Tên khu 2-120 ký tự.'); redirectTo('admin-areas', $returnParams); }
        if (mb_strlen($data['address']) < 5 || mb_strlen($data['address']) > 255) { setFlash('admin_area_error', 'Địa chỉ khu 5-255 ký tự.'); redirectTo('admin-areas', $returnParams); }
        if (mb_strlen($data['description']) > 2000) { setFlash('admin_area_error', 'Mô tả khu ≤ 2000 ký tự.'); redirectTo('admin-areas', $returnParams); }
    }

    // ==========================================
    // IMAGE HANDLERS (Area)
    // ==========================================

    private function handleAreaImageUpload(int $areaId = 0): ?string
    {
        $file = $_FILES['area_image'] ?? null;
        if (empty($file)) return null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) { setFlash('admin_area_error', 'Tải ảnh thất bại.'); return false; }
        if ((int)$file['size'] > 5 * 1024 * 1024) { setFlash('admin_area_error', 'Ảnh > 5MB.'); return false; }

        $mime = $this->getMimeType($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($allowed[$mime])) { setFlash('admin_area_error', 'Chỉ JPG, PNG, WEBP, GIF.'); return false; }

        $subFolder = $areaId > 0 ? 'image_khu_' . $areaId : 'image_khu_new';
        $dir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $fileName = 'khu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $target = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $target)) { setFlash('admin_area_error', 'Không lưu được ảnh.'); return false; }

        return BASE_URL . '.uploads/' . $subFolder . '/' . $fileName;
    }

    private function getMimeType(string $tmpPath): string
    {
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
        if ($finfo) finfo_close($finfo);
        return $mime;
    }

    // ==========================================
    // QUICK FLOOR
    // ==========================================

    public function addFloorQuick(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-areas');
        verify_csrf();

        $areaId = (int)($_POST['area_id'] ?? 0);
        $area = $areaId > 0 ? AreaModel::getById($areaId) : null;
        if (!$area) { setFlash('admin_area_error', 'Khu không tồn tại.'); redirectTo('admin-areas'); }

        $next = 1;
        foreach (FloorModel::getByAreaId($areaId) as $floor) $next = max($next, (int)($floor['floor_number'] ?? 0) + 1);

        $roomLimit = max(0, min(50, (int)($_POST['room_count'] ?? 0)));
        $floorId = (int)FloorModel::save([
            'area_id'      => $areaId,
            'name'         => 'Tầng ' . $next,
            'floor_number' => $next,
            'room_limit'   => $roomLimit,
        ], null);

        $created = $this->createRoomSlots($floorId, $next, $roomLimit);
        setFlash('admin_room_message', "Đã thêm Tầng {$next}" . ($created > 0 ? " với {$created} phòng nháp." : '.'));
        redirectTo('admin-rooms', ['area_id' => $areaId, 'floor_id' => 0]);
    }

    // ==========================================
    // DELETE AREA
    // ==========================================

    public function deleteArea(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-areas');
        verify_csrf();

        $area = AreaModel::getById($id);
        if (!$area) { setFlash('admin_area_error', 'Khu không tồn tại.'); redirectTo('admin-areas'); }

        $roomCount = $rentedCount = 0;
        foreach (FloorModel::getByAreaId($id) as $floor) {
            $roomCount += (int)($floor['room_count'] ?? 0);
            $rentedCount += (int)($floor['rented_count'] ?? 0);
        }
        if ($rentedCount > 0) {
            setFlash('admin_delete_blocked', [
                'type' => 'area', 'name' => $area['name'] ?? '',
                'rented_count' => $rentedCount,
                'return_url' => BASE_URL . '?page=admin-areas&area=' . $id,
                'message' => 'Khu "' . ($area['name'] ?? '') . '" đang có ' . $rentedCount . ' phòng đang thuê. Không thể xóa.',
            ]);
            redirectTo('admin-areas');
        }

        AreaModel::delete($id);
        setFlash('admin_area_message', 'Đã xóa khu ' . ($area['name'] ?? '') . ($roomCount > 0 ? ' cùng ' . $roomCount . ' phòng nháp.' : '.'));
        redirectTo('admin-areas');
    }

    // ==========================================
    // UPLOAD IMAGE (Shared)
    // ==========================================

    public function uploadImage(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method not allowed']); exit; }
        verify_csrf();

        $file = $_FILES['image'] ?? null;
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $this->jsonError(400, 'Chưa chọn ảnh.');
        if ($file['size'] > 5 * 1024 * 1024) $this->jsonError(400, 'Ảnh > 5MB.');

        $mime = $this->getMimeType($file['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!isset($allowed[$mime])) $this->jsonError(400, 'Chỉ JPG, PNG, WEBP, GIF.');

        $slot = trim($_POST['slot'] ?? 'home');
        [$subFolder, $filePrefix] = $this->resolveSlot($slot);

        $dir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $fileName = $filePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . $subFolder . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $target)) $this->jsonError(500, 'Không lưu được ảnh.');

        echo json_encode(['ok'=>true,'url'=>BASE_URL.'.uploads/'.$subFolder.'/'.$fileName]);
        exit;
    }

    private function resolveSlot(string $slot): array
    {
        $map = ['home'=>['image_page_home','home-hero'],'area_new'=>['image_khu_new','khu-new'],'room_new'=>['image_phong_new','phong-new']];
        if (preg_match('/^area_(\d+)$/',$slot,$m)) return ['image_khu_'.$m[1],'khu-'.$m[1]];
        if (preg_match('/^room_(\d+)$/',$slot,$m)) return ['image_phong_'.$m[1],'phong-'.$m[1]];
        return $map[$slot] ?? $map['home'];
    }

    // ==========================================
    // ROOM IMAGE FINALIZE
    // ==========================================

    private function finalizeNewRoomImage(int $roomId, string $imageUrl): string
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local===null || basename(dirname($local))!=='image_phong_new') return $imageUrl;
        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_' . $roomId;
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
        $fileName = basename($local);
        $dest = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@rename($local, $dest)) return $imageUrl;
        return BASE_URL . '.uploads/image_phong_' . $roomId . '/' . $fileName;
    }

    private function cleanupDraftRoomImages(): void
    {
        $dir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_new';
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $e) { if ($e!=='.' && $e!=='..') @unlink($dir . DIRECTORY_SEPARATOR . $e); }
    }

    // ==========================================
    // SAVE ROOM
    // ==========================================

    public function saveRoom(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-rooms');
        verify_csrf();

        $redirectParams = $this->getRoomAdminFilters($_POST);
        $id = (int)($_POST['id'] ?? 0);
        $editRoom = $id > 0 ? RoomModel::getById($id) : null;
        $status = $this->normalizeRoomStatus($_POST['status'] ?? 'draft', 'draft');

        // Quick status update
        if (!empty($_POST['quick_status_update'])) {
            $room = RoomModel::getById($id);
            if (!$room || RoomModel::countOccupants($id) > 0 || ($status==='available' && !$this->roomIsComplete($room))) {
                $err = !$room ? 'Phòng không tồn tại.' : (RoomModel::countOccupants($id)>0 ? 'Phòng đang có người.' : 'Phòng chưa đủ thông tin.');
                setFlash('admin_room_error', $err);
                redirectTo('admin-rooms', $redirectParams);
            }
            RoomModel::updateStatus($id, $status);
            setFlash('admin_room_message', 'Đã cập nhật trạng thái.');
            redirectTo('admin-rooms', $redirectParams);
        }

        $data = [
            'floor_id'      => (int)($_POST['floor_id'] ?? 0),
            'name'          => trim($_POST['name'] ?? ''),
            'position'      => (int)($_POST['position'] ?? 0),
            'price'         => (float)($_POST['price'] ?? 0),
            'area'          => (float)($_POST['area'] ?? 0),
            'max_occupancy' => (int)($_POST['max_occupancy'] ?? 2),
            'description'   => trim($_POST['description'] ?? ''),
        ];

        // Lock price for rented rooms
        if ($id > 0 && $editRoom && ($editRoom['status'] ?? '') === 'rented') $data['price'] = (float)($editRoom['price'] ?? 0);

        $data['amenities'] = implode(', ', array_slice(array_map(fn($v)=>mb_substr(trim($v),0,80), array_filter(array_unique(array_map('trim', is_array($_POST['amenities']??null)?$_POST['amenities']:explode(',',$_POST['amenities']??''))))),0,20));

        $formState = array_merge($data, ['id'=>$id,'area_id'=>(int)($_POST['area_id']??0)]);

        $floor = RoomModel::floorExists($data['floor_id']) ? FloorModel::getById($data['floor_id']) : null;
        if (!$floor) { setFlash('admin_room_error','Tầng không hợp lệ.'); setFlash('admin_room_old',$formState); redirectTo('admin-rooms',$redirectParams); }

        // Validation
        $missing = [];
        if ($data['price'] <= 0) $missing[] = 'Giá thuê (>0)';
        if ($data['area'] <= 0) $missing[] = 'Diện tích (>0)';
        if ($data['max_occupancy'] <= 0) $missing[] = 'Sức chứa (≥1)';
        if ($data['description'] === '') $missing[] = 'Mô tả';
        if ($missing) { setFlash('admin_room_error','Thiếu: '.implode(', ',$missing).'.'); setFlash('admin_room_old',$formState); redirectTo('admin-rooms',$redirectParams); }

        // DB limits
        if ($data['area'] > 999.99) $missing[] = 'Diện tích ≤999.99m²';
        if ($data['price'] > 99999999.99) $missing[] = 'Giá ≤99.999.999đ';
        if ($missing) { setFlash('admin_room_error','Vượt giới hạn: '.implode(', ',$missing).'.'); setFlash('admin_room_old',$formState); redirectTo('admin-rooms',$redirectParams); }

        // Column limits
        $data['name'] = mb_substr($data['name'], 0, 100);
        $data['max_occupancy'] = min(255, $data['max_occupancy']);
        $data['position'] = max(0, min(9999, $data['position']));

        // Images
        $primaryImage = trim($_POST['primary_image'] ?? $_POST['thumbnail'] ?? '');
        $galleryImages = array_slice(array_map('trim', (array)($_POST['gallery_images'] ?? [])), 0, 3);
        $galleryImages = array_values(array_filter($galleryImages, fn($v)=>$v!==''));

        // Preserve existing images on edit
        if ($id > 0 && $editRoom) {
            $existing = RoomImageModel::getByRoom($id);
            $existingPrimary = ''; $existingGallery = [];
            foreach ($existing as $img) {
                $url = trim($img['image_url'] ?? '');
                if ($url==='') continue;
                if ((int)($img['is_primary']??0)===1) $existingPrimary=$url;
                else $existingGallery[]=$url;
            }
            if ($primaryImage==='') $primaryImage = $existingPrimary ?: trim($editRoom['thumbnail']??'');
            for ($i=0;$i<3;$i++) {
                $url = trim($submittedGalleryImages[$i] ?? '');
                if ($url==='') $url = trim($existingGallery[$i] ?? '');
                if ($url!=='') $galleryImages[]=$url;
            }
        }

        // New room checks
        if ($id === 0) {
            $limit = (int)($floor['room_limit'] ?? 0);
            $currentCount = count(RoomModel::getAll(['floor_id'=>(int)$floor['id']]));
            if ($limit>0 && $currentCount>=$limit) {
                if (!empty($_POST['extend_limit'])) Database::update('floors',['room_limit'=>$limit+1],'id=:id',['id'=>(int)$floor['id']]);
                else { setFlash('admin_room_error',"Tầng đã đạt giới hạn {$limit} phòng."); setFlash('admin_room_old',$formState); redirectTo('admin-rooms',$redirectParams); }
            }
            if ($data['position']<=0) $data['position'] = $currentCount+1;
            if ($data['name']==='') {
                $area = AreaModel::getById((int)$floor['area_id']);
                $data['name'] = $this->deriveAreaCode($area['name']??'','').(int)$floor['floor_number'].str_pad($data['position'],2,'0',STR_PAD_LEFT);
            }
        }

        $occupants = $id>0 ? RoomModel::countOccupants($id) : 0;
        $data['status'] = $occupants>0 ? 'rented' : (in_array($status,['draft','available','maintenance'],true)?$status:'draft');
        if ($primaryImage!=='') $data['thumbnail'] = $primaryImage;

        $pendingPriceMessage = '';
        try {
            $savedRoomId = (int)RoomModel::save($data, $id>0?$id:null);

            // Price change logic
            if ($id>0 && $editRoom) {
                $oldPrice = (float)($editRoom['price']??0); $newPrice = (float)($data['price']??0);
                $priceChanged = abs($oldPrice-$newPrice)>0.01;

                if ($editRoom['status']==='rented' && $priceChanged) {
                    $em = (int)($_POST['price_effective_month']??0);
                    $ey = (int)($_POST['price_effective_year']??0);
                    $curOrder = (int)date('Y')*100 + (int)date('n');
                    if ($em===0||$ey===0) { $em=(int)date('n')+1; $ey=(int)date('Y'); if($em>12){$em=1;$ey++;} }
                    if (($ey*100+$em) < ($curOrder+1)) { setFlash('admin_room_error','Tháng áp dụng phải từ tháng sau.'); setFlash('admin_room_old',$formState); redirectTo('admin-rooms',$redirectParams); }
                    $deleted = RoomPriceChangeModel::scheduleChange($savedRoomId,$oldPrice,$newPrice,$em,$ey,(int)($_SESSION['user_id']??0));
                    Database::update('rooms',['price'=>$oldPrice],'id=:id',['id'=>$savedRoomId]);
                    $msg = 'Giá mới '.number_format($newPrice,0,',','.').'đ áp dụng từ '.str_pad($em,2,'0',STR_PAD_LEFT).'/'.$ey.'.';
                    if($deleted>0) $msg.=' (Đã hủy '.$deleted.' lịch cũ.)';
                    $pendingPriceMessage = $msg;
                }
                if ($editRoom['status']==='rented' && $status!=='rented') {
                    $applied = RoomPriceChangeModel::applyPendingImmediately($savedRoomId);
                    if ($applied>0) $pendingPriceMessage = 'Đã áp dụng '.$applied.' thay đổi giá chờ.';
                }
            }

            // Move images
            $movedPrimary = $this->finalizeNewRoomImage($savedRoomId, $primaryImage);
            foreach ($galleryImages as $i=>$url) $galleryImages[$i] = $this->finalizeNewRoomImage($savedRoomId, $url);
            $primaryImage = $movedPrimary;
            if ($primaryImage!=='') Database::update('rooms',['thumbnail'=>$primaryImage],'id=:id',['id'=>$savedRoomId]);
            $this->cleanupDraftRoomImages();

            RoomImageModel::syncForRoom($savedRoomId, $primaryImage, $galleryImages);
        } catch (Throwable $e) {
            error_log('[saveRoom] '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            setFlash('admin_room_error','Lỗi hệ thống: '.$e->getMessage());
            setFlash('admin_room_old',$formState);
            redirectTo('admin-rooms',$redirectParams);
        }

        setFlash('admin_room_message', $pendingPriceMessage!=='' ? $pendingPriceMessage : ($data['status']==='draft'?'Đã lưu phòng CHƯA CÓ THÔNG TIN — chưa hiển thị web.':'Đã lưu phòng và đăng lên website.'));
        redirectTo('admin-rooms', $redirectParams);
    }

    // ==========================================
    // DELETE ROOM
    // ==========================================

    public function deleteRoom(int $id): void
    {
        $redirectParams = $this->getRoomAdminFilters($_GET);
        $room = RoomModel::getById($id);
        if (!$room) { setFlash('admin_room_error','Phòng không tồn tại.'); redirectTo('admin-rooms',$redirectParams); }
        if (RoomModel::hasActiveOccupants($id)) { setFlash('admin_room_error','Phòng đang có người.'); redirectTo('admin-rooms',$redirectParams); }
        if (($room['status']??'')==='rented') { setFlash('admin_room_error','Phòng "'.($room['name']??'').'" đang thuê — chặn xóa.'); redirectTo('admin-rooms',$redirectParams); }

        RoomImageModel::deleteByRoom($id);
        RoomModel::delete($id);
        setFlash('admin_room_message','Đã xóa phòng.');
        redirectTo('admin-rooms',$redirectParams);
    }

    // ==========================================
    // FILTERS & STATUS
    // ==========================================

    private function getRoomAdminFilters(array $src): array
    {
        $status = $this->normalizeRoomStatus($src['return_status']??($src['status']??''), '');
        return [
            'area_id' => (int)($src['return_area_id']??($src['area_id']??0)),
            'floor_id' => (int)($src['return_floor_id']??($src['floor_id']??0)),
            'status' => $status,
        ];
    }

    private function normalizeRoomStatus(string $status, string $default=''): string
    {
        $allowed = ['draft','available','rented','maintenance'];
        return in_array($status,$allowed,true)?$status:$default;
    }
}