<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminSystemTrait
{
/**
     * Trang tổng quan admin.
     */
    public function dashboard()
    {
        $areaStats = RoomModel::getStatsByArea();
        $availableRooms = array_values(array_filter(
            RoomModel::getAll(['status' => 'available']),
            static fn($room) => (string)($room['status'] ?? '') === 'available'
        ));
        usort($availableRooms, static fn($left, $right) => (int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0));

        $tenantRows = array_values(array_filter(
            UserModel::getAll(),
            static fn($user) => (int)($user['role'] ?? 1) === 0
        ));

        $stats = [
            'total_areas' => count($areaStats),
            'total_rooms' => RoomModel::count(),
            'available_rooms' => RoomModel::countByStatus('available'),
            'rented_rooms' => RoomModel::countByStatus('rented'),
            'draft_rooms' => RoomModel::countByStatus('draft'),
            'total_tenants' => UserModel::countByRole(0),
            'total_revenue' => RoomModel::getTotalRevenue(),
        ];
        $recentRooms = array_slice($availableRooms, 0, 6);
        $recentTenants = array_slice($tenantRows, 0, 6);
        $allRooms = RoomModel::getAll();
        $tenantsWithRooms = array_values(array_filter(
            $tenantRows,
            static fn($user) => !empty($user['room_id'])
        ));
        $settingSections = $this->populateAdminSettingSections();
        $heroImagePreview = $this->getSettingFieldValue($settingSections, 'hero_image');
        $dashboardMessage = pullFlash('admin_dashboard_message');
        $dashboardError = pullFlash('admin_dashboard_error');
        // [DEV-QWEN-A][NHOM-3] Them stats vao dashboard
        $selectedAreaId = (int)($_GET['area_id'] ?? 0);
        $selectedYear = max(2000, (int)($_GET['year'] ?? date('Y')));
        $areas = AreaModel::getAllWithStats();
        $areaStats = RoomModel::getStatsByArea($selectedAreaId);
        $selectedArea = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $revenueStats = PaymentModel::getRevenueByMonth($selectedYear);
        $statsSummary = [
            'tracked_areas' => count($areaStats),
            'tracked_rooms' => array_sum(array_map(static fn($row) => (int)($row['total_rooms'] ?? 0), $areaStats)),
            'tracked_available_rooms' => array_sum(array_map(static fn($row) => (int)($row['available_rooms'] ?? 0), $areaStats)),
            'tracked_draft_rooms' => array_sum(array_map(static fn($row) => (int)($row['draft_rooms'] ?? 0), $areaStats)),
            'tracked_occupancy_rate' => 0,
            'year_total' => (float)($revenueStats['year_total'] ?? 0),
            'paid_invoice_count' => (int)($revenueStats['paid_invoice_count'] ?? 0),
        ];
        $knownRooms = $statsSummary['tracked_rooms'] - $statsSummary['tracked_draft_rooms'];
        if ($knownRooms > 0) {
            $statsSummary['tracked_occupancy_rate'] = round(
                (($statsSummary['tracked_rooms'] - $statsSummary['tracked_draft_rooms'] - $statsSummary['tracked_available_rooms']) / $knownRooms) * 100,
                1
            );
        }
        $pageTitle = 'Admin Dashboard - NhaTroA';
        require_once BASE_PATH . 'views/admin/dashboard.php';
    }
public function stats()
    {
        $selectedAreaId = (int)($_GET['area_id'] ?? 0);
        $selectedYear = max(2000, (int)($_GET['year'] ?? date('Y')));
        $areas = AreaModel::getAllWithStats();
        $areaStats = RoomModel::getStatsByArea($selectedAreaId);
        $selectedArea = $selectedAreaId > 0 ? AreaModel::getById($selectedAreaId) : null;
        $revenueStats = PaymentModel::getRevenueByMonth($selectedYear);
        $statsSummary = [
            'tracked_areas' => count($areaStats),
            'tracked_rooms' => array_sum(array_map(static fn($row) => (int)($row['total_rooms'] ?? 0), $areaStats)),
            'tracked_available_rooms' => array_sum(array_map(static fn($row) => (int)($row['available_rooms'] ?? 0), $areaStats)),
            'tracked_draft_rooms' => array_sum(array_map(static fn($row) => (int)($row['draft_rooms'] ?? 0), $areaStats)),
            'tracked_occupancy_rate' => 0,
            'year_total' => (float)($revenueStats['year_total'] ?? 0),
            'paid_invoice_count' => (int)($revenueStats['paid_invoice_count'] ?? 0),
        ];
        $knownRooms = $statsSummary['tracked_rooms'] - $statsSummary['tracked_draft_rooms'];
        if ($knownRooms > 0) {
            $statsSummary['tracked_occupancy_rate'] = round(
                (($statsSummary['tracked_rooms'] - $statsSummary['tracked_draft_rooms'] - $statsSummary['tracked_available_rooms']) / $knownRooms) * 100,
                1
            );
        }
        $pageTitle = 'Thống kê - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/stats.php';
    }
/**
     * Lưu cấu hình giao diện và kiểm duyệt đánh giá theo cơ chế UPSERT `settings`.
     */
    public function saveSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectTo('admin-settings');
        }
        verify_csrf();

        $fieldMap = $this->getAdminSettingFieldMap();
        $submittedValues = $_POST['settings'] ?? [];
        $clearFlags = $_POST['settings_clear'] ?? [];
        $currentSettings = SettingModel::loadAll();

        foreach ($fieldMap as $key => $field) {
            if (($field['type'] ?? 'text') === 'password') {
                $typedValue = trim((string)($submittedValues[$key] ?? ''));
                if (!empty($clearFlags[$key])) {
                    $value = '';
                } elseif ($typedValue !== '') {
                    $value = $typedValue;
                } else {
                    $value = (string)($currentSettings[$key] ?? ($field['default'] ?? ''));
                }
            } elseif (($field['type'] ?? 'text') === 'toggle') {
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

        // Don anh hero cu trong image_page_home: chi giu anh dang duoc dung
        $this->cleanupAppliedSlotImage(trim((string)($submittedValues['hero_image'] ?? '')), 'image_page_home');

        RoomModel::resetSettingsCache();
        SettingModel::loadAll();
        setFlash('admin_dashboard_message', 'Đã lưu cấu hình thành công.');
        redirectTo('admin-settings');
    }
/**
     * Trang cấu hình hệ thống: xem trước thu nhỏ website (home + intro) trước khi áp dụng.
     */
    public function settingsEditor()
    {
        $settingSections = $this->populateAdminSettingSections();
        $dashboardMessage = pullFlash('admin_dashboard_message');
        $dashboardError = pullFlash('admin_dashboard_error');
        $pageTitle = 'Cấu hình hệ thống - NhaTroA';
        require_once BASE_PATH . 'views/admin/system/settings_editor.php';
    }
/**
     * Sau khi áp dụng ảnh mới: chỉ giữ ảnh đang dùng, xóa các ảnh khác cùng thư mục slot.
     */
    private function cleanupAppliedSlotImage($imageUrl, $folderName)
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== $folderName) {
            return;
        }
        $dir = dirname($local);
        $keep = basename($local);
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === $keep) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
/**
     * Gắn giá trị hiện tại vào schema field để view admin chỉ render và không tự suy diễn dữ liệu.
     */
    private function populateAdminSettingSections()
    {
        $settings = SettingModel::loadAll();
        $sections = $this->buildAdminSettingSections();

        foreach ($sections as $sectionIndex => $section) {
            foreach ($section['fields'] as $fieldIndex => $field) {
                $value = $settings[$field['key']] ?? ($field['default'] ?? '');
                $sections[$sectionIndex]['fields'][$fieldIndex]['value'] = (string)$value;
                $sections[$sectionIndex]['fields'][$fieldIndex]['has_value'] = trim((string)$value) !== '';
            }
        }

        return $sections;
    }
/**
     * Trả map field theo `setting_key` để validate/lưu settings từ dashboard admin.
     */
    private function getAdminSettingFieldMap()
    {
        $fieldMap = [];

        foreach ($this->buildAdminSettingSections() as $section) {
            foreach ($section['fields'] as $field) {
                $fieldMap[$field['key']] = $field;
            }
        }

        return $fieldMap;
    }
/**
     * Tìm nhanh giá trị của một field trong schema dashboard đã được đổ dữ liệu.
     */
    private function getSettingFieldValue(array $sections, $key)
    {
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if (($field['key'] ?? '') === $key) {
                    return (string)($field['value'] ?? '');
                }
            }
        }

        return '';
    }
/**
     * Validate dữ liệu settings theo loại field để chặn lỗi cấu hình ngay từ admin.
     */
    private function validateSettingField(array $field, &$value)
    {
        $label = $field['label'] ?? ($field['key'] ?? 'Trường cấu hình');
        $type = $field['type'] ?? 'text';

        if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $label . ' không đúng định dạng email.';
        }

        if ($type === 'url' && $value !== '') {
            $urlOk = filter_var($value, FILTER_VALIDATE_URL);
            if (!$urlOk) {
                // Encode ký tự Unicode (tên thư mục dự án tiếng Việt) rồi kiểm tra lại.
                $encodedUrl = preg_replace_callback('/[^\x20-\x7E]+/', static function ($m) {
                    return rawurlencode($m[0]);
                }, $value);
                $urlOk = filter_var($encodedUrl, FILTER_VALIDATE_URL);
            }
            if (!$urlOk && strpos($value, BASE_URL) === 0) {
                // URL nội bộ (ảnh upload vào .uploads/) luôn được chấp nhận.
                $urlOk = true;
            }
            if (!$urlOk) {
                return $label . ' phải là URL hợp lệ.';
            }
        }

        if ($type === 'number') {
            if ($value === '' || !is_numeric($value)) {
                return $label . ' phải là số nguyên hợp lệ.';
            }

            $value = (string)(int)$value;
            if (isset($field['min']) && (int)$value < (int)$field['min']) {
                return $label . ' không được nhỏ hơn ' . (int)$field['min'] . '.';
            }
            if (isset($field['max']) && (int)$value > (int)$field['max']) {
                return $label . ' không được lớn hơn ' . (int)$field['max'] . '.';
            }
        }

        if ($type === 'decimal') {
            if ($value === '' || !is_numeric($value)) {
                return $label . ' phải là số hợp lệ.';
            }

            $number = (float)$value;
            if (isset($field['min']) && $number < (float)$field['min']) {
                return $label . ' không được nhỏ hơn ' . $field['min'] . '.';
            }
            if (isset($field['max']) && $number > (float)$field['max']) {
                return $label . ' không được lớn hơn ' . $field['max'] . '.';
            }

            $value = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        }

        if ($type === 'toggle') {
            $value = $value === '1' ? '1' : '0';
        }

        return null;
    }

}
