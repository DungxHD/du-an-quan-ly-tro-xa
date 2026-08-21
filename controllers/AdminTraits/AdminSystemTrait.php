<?php
/**
 * AdminSystemTrait - Quản lý hệ thống: dashboard, stats, settings, settings editor
 */
trait AdminSystemTrait
{
    // ==========================================
    // DASHBOARD
    // ==========================================

    /**
     * Trang tổng quan admin: stats, recent rooms/tenants, revenue, occupancy
     */
    public function dashboard(): void
    {
        $areaStats = RoomModel::getStatsByArea();
        $availableRooms = array_values(array_filter(RoomModel::getAll(['status' => 'available']), fn($r) => ($r['status'] ?? '') === 'available'));
        usort($availableRooms, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        $tenantRows = array_values(array_filter(UserModel::getAll(), fn($u) => ($u['role'] ?? 1) === 0));

        $stats = [
            'total_areas'      => count($areaStats),
            'total_rooms'      => RoomModel::count(),
            'available_rooms'  => RoomModel::countByStatus('available'),
            'rented_rooms'     => RoomModel::countByStatus('rented'),
            'draft_rooms'      => RoomModel::countByStatus('draft'),
            'total_tenants'    => RoomModel::countTotalOccupantsInRentedRooms(),
            'total_revenue'    => RoomModel::getTotalRevenue(),
        ];

        $recentRooms   = array_slice($availableRooms, 0, 6);
        $recentTenants = array_slice($tenantRows, 0, 6);
        $allRooms      = RoomModel::getAll();

        // Occupants per rented room
        $rentedRoomOccupants = [];
        foreach ($allRooms as $room) {
            if (($room['status'] ?? '') === 'rented') {
                $rentedRoomOccupants[$room['id']] = RoomModel::countOccupants($room['id']);
            }
        }

        // Occupants list (tenants in rented rooms)
        $rentedRoomIdSet = [];
        foreach ($allRooms as $room) {
            if (($room['status'] ?? '') === 'rented') $rentedRoomIdSet[(int)$room['id']] = true;
        }
        $occupantsList = array_values(array_filter(
            $tenantRows,
            fn($u) => !empty($u['room_id']) && isset($rentedRoomIdSet[(int)($u['room_id'] ?? 0)])
        ));
        foreach ($occupantsList as &$occ) $occ['area_name'] = $occ['building_name'] ?? null;
        unset($occ);

        $tenantsWithRooms = array_values(array_filter($tenantRows, fn($u) => !empty($u['room_id'])));

        $settingSections      = $this->populateAdminSettingSections();
        $heroImagePreview     = $this->getSettingFieldValue($settingSections, 'hero_image');
        $dashboardMessage     = pullFlash('admin_dashboard_message');
        $dashboardError       = pullFlash('admin_dashboard_error');

        // Stats tab data
        $selectedAreaId = (int)($_GET['area_id'] ?? 0);
        $selectedYear   = max(2000, (int)($_GET['year'] ?? date('Y')));
        $areas          = AreaModel::getAllWithStats();
        $areaStats      = RoomModel::getStatsByArea($selectedAreaId);
        $selectedArea   = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $revenueStats   = PaymentModel::getRevenueByMonth($selectedYear);

        $statsSummary = $this->buildStatsSummary($areaStats, $revenueStats, $selectedYear);
        $pageTitle = 'Admin Dashboard - NhaTroA';

        require_once BASE_PATH . 'views/admin/dashboard.php';
    }

    /**
     * Trang thống kê chi tiết
     */
    public function stats(): void
    {
        $selectedAreaId = (int)($_GET['area_id'] ?? 0);
        $selectedYear   = max(2000, (int)($_GET['year'] ?? date('Y')));
        $areas          = AreaModel::getAllWithStats();
        $areaStats      = RoomModel::getStatsByArea($selectedAreaId);
        $selectedArea   = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $revenueStats   = PaymentModel::getRevenueByMonth($selectedYear);

        $statsSummary = $this->buildStatsSummary($areaStats, $revenueStats, $selectedYear);
        $pageTitle = 'Thống kê - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/stats.php';
    }

    /**
     * Xây dựng summary stats dùng chung cho dashboard & stats page
     */
    private function buildStatsSummary(array $areaStats, array $revenueStats, int $year): array
    {
        $summary = [
            'tracked_areas'           => count($areaStats),
            'tracked_rooms'           => array_sum(array_map(fn($r) => (int)($r['total_rooms'] ?? 0), $areaStats)),
            'tracked_available_rooms' => array_sum(array_map(fn($r) => (int)($r['available_rooms'] ?? 0), $areaStats)),
            'tracked_draft_rooms'     => array_sum(array_map(fn($r) => (int)($r['draft_rooms'] ?? 0), $areaStats)),
            'tracked_occupancy_rate'  => 0,
            'year_total'              => (float)($revenueStats['year_total'] ?? 0),
            'paid_invoice_count'      => (int)($revenueStats['paid_invoice_count'] ?? 0),
        ];

        $knownRooms = $summary['tracked_rooms'] - $summary['tracked_draft_rooms'];
        if ($knownRooms > 0) {
            $summary['tracked_occupancy_rate'] = round(
                (($summary['tracked_rooms'] - $summary['tracked_draft_rooms'] - $summary['tracked_available_rooms']) / $knownRooms) * 100,
                1
            );
        }
        return $summary;
    }

    // ==========================================
    // SETTINGS
    // ==========================================

    /**
     * Lưu cấu hình setting (UPSERT qua SettingModel)
     * Validate từng field theo type, cleanup ảnh hero cũ
     */
    public function saveSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectTo('admin-settings');
        verify_csrf();

        $fieldMap       = $this->getAdminSettingFieldMap();
        $submittedValues = $_POST['settings'] ?? [];
        $clearFlags      = $_POST['settings_clear'] ?? [];
        $currentSettings = SettingModel::loadAll();

        foreach ($fieldMap as $key => $field) {
            $type = $field['type'] ?? 'text';

            if ($type === 'password') {
                $typedValue = trim((string)($submittedValues[$key] ?? ''));
                $value = !empty($clearFlags[$key]) ? ''
                    : ($typedValue !== '' ? $typedValue : (string)($currentSettings[$key] ?? ($field['default'] ?? '')));
            } elseif ($type === 'toggle') {
                $value = !empty($submittedValues[$key]) ? '1' : '0';
            } else {
                $value = trim((string)($submittedValues[$key] ?? ($field['default'] ?? '')));
            }

            $validationError = $this->validateSettingField($field, $value);
            if ($validationError !== null) {
                setFlash('admin_dashboard_error', $validationError);
                redirectTo('admin-settings');
            }

            SettingModel::set($key, $value, $field['group'] ?? null);
        }

        // Cleanup ảnh hero cũ
        $this->cleanupAppliedSlotImage(trim((string)($submittedValues['hero_image'] ?? '')), 'image_page_home');

        RoomModel::resetSettingsCache();
        SettingModel::loadAll();
        setFlash('admin_dashboard_message', 'Đã lưu cấu hình thành công.');
        redirectTo('admin-settings');
    }

    /**
     * Trang cấu hình hệ thống: preview, amenities drag-drop
     */
    public function settingsEditor(): void
    {
        $settingSections    = $this->populateAdminSettingSections();
        $amenities          = AmenityModel::getAll();
        $amenityIcons       = $this->getAmenityIconOptions();
        $amenityMessage     = pullFlash('admin_amenity_message');
        $amenityError       = pullFlash('admin_amenity_error');
        $amenityOld         = pullFlash('admin_amenity_old');
        $dashboardMessage   = pullFlash('admin_dashboard_message');
        $dashboardError     = pullFlash('admin_dashboard_error');
        $pageTitle = 'Cấu hình hệ thống - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/settings_editor.php';
    }

    /**
     * Dọn ảnh cũ trong thư mục slot sau khi áp dụng ảnh mới
     */
    private function cleanupAppliedSlotImage(string $imageUrl, string $folderName): void
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== $folderName) return;

        $dir = dirname($local);
        $keep = basename($local);
        foreach (scandir($dir) as $entry) {
            if (in_array($entry, ['.', '..', $keep], true)) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) @unlink($path);
        }
    }

    /**
     * Gắn giá trị setting hiện tại vào schema để view render
     */
    private function populateAdminSettingSections(): array
    {
        $settings = SettingModel::loadAll();
        $sections = $this->buildAdminSettingSections();

        foreach ($sections as $si => $section) {
            foreach ($section['fields'] as $fi => $field) {
                $value = $settings[$field['key']] ?? ($field['default'] ?? '');
                $sections[$si]['fields'][$fi]['value']     = (string)$value;
                $sections[$si]['fields'][$fi]['has_value'] = trim((string)$value) !== '';
            }
        }
        return $sections;
    }

    /**
     * Map setting_key -> field schema (dùng cho validate & save)
     */
    private function getAdminSettingFieldMap(): array
    {
        $map = [];
        foreach ($this->buildAdminSettingSections() as $section) {
            foreach ($section['fields'] as $field) $map[$field['key']] = $field;
        }
        return $map;
    }

    /**
     * Lấy giá trị field từ sections đã populate
     */
    private function getSettingFieldValue(array $sections, string $key): string
    {
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if (($field['key'] ?? '') === $key) return (string)($field['value'] ?? '');
            }
        }
        return '';
    }

    /**
     * Validate setting field theo type
     */
    private function validateSettingField(array $field, string &$value): ?string
    {
        $label = $field['label'] ?? ($field['key'] ?? 'Trường cấu hình');
        $type  = $field['type'] ?? 'text';

        if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "$label không đúng định dạng email.";
        }

        if ($type === 'url' && $value !== '') {
            $urlOk = filter_var($value, FILTER_VALIDATE_URL);
            if (!$urlOk) {
                $encoded = preg_replace_callback('/[^\x20-\x7E]+/', fn($m) => rawurlencode($m[0]), $value);
                $urlOk = filter_var($encoded, FILTER_VALIDATE_URL);
            }
            if (!$urlOk && strpos($value, BASE_URL) !== 0) {
                return "$label phải là URL hợp lệ.";
            }
        }

        if ($type === 'number') {
            if ($value === '' || !is_numeric($value)) return "$label phải là số nguyên hợp lệ.";
            $value = (string)(int)$value;
            if (isset($field['min']) && (int)$value < (int)$field['min']) return "$label không được nhỏ hơn " . (int)$field['min'] . '.';
            if (isset($field['max']) && (int)$value > (int)$field['max']) return "$label không được lớn hơn " . (int)$field['max'] . '.';
        }

        if ($type === 'decimal') {
            if ($value === '' || !is_numeric($value)) return "$label phải là số hợp lệ.";
            $number = (float)$value;
            if (isset($field['min']) && $number < (float)$field['min']) return "$label không được nhỏ hơn " . $field['min'] . '.';
            if (isset($field['max']) && $number > (float)$field['max']) return "$label không được lớn hơn " . $field['max'] . '.';
            $value = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        }

        if ($type === 'toggle') $value = $value === '1' ? '1' : '0';

        return null;
    }
}