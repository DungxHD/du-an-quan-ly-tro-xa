<?php
class BaseController
{
    protected function buildPublicLayoutData($activePage = '', $pageTitle = '')
    {
        $siteName = RoomModel::getSetting('site_name', 'NhaTroA');
        $defaultTitle = $siteName . ' - Website chính thức của khu trọ';
        $finalTitle = trim((string)$pageTitle) !== '' ? $pageTitle : $defaultTitle;

        return [
            'siteName' => $siteName,
            'pageTitle' => $finalTitle,
            'activePage' => $activePage,
            'urls' => [
                'home' => BASE_URL . '?page=home',
                'rooms' => BASE_URL . '?page=rooms',
                'intro' => BASE_URL . '?page=intro',
                'register' => BASE_URL . '?page=register',
                'login' => BASE_URL . '?page=login',
                'admin' => BASE_URL . '?page=admin',
                'tenant' => BASE_URL . '?page=tenant',
                'logout' => BASE_URL . '?page=logout',
            ],
            'isFallbackMode' => !Database::hasConnection(),
            'meta' => [
                'description' => fallbackText(RoomModel::getSetting('site_description', ''), 'Website chính thức của khu trọ, giúp khách xem phòng trống và liên hệ nhanh với chủ trọ.'),
            ],
            'brand' => [
                // Gom tagline ở đây để layout chỉ render, không cần tự đọc model.
                'tagline' => fallbackText(RoomModel::getSetting('site_slogan', ''), 'Xem phòng rõ ràng, liên hệ dễ dàng'),
            ],
            'contact' => [
                'address' => fallbackText(RoomModel::getSetting('contact_address', '')),
                'phone' => fallbackText(RoomModel::getSetting('contact_phone', '')),
                'email' => fallbackText(RoomModel::getSetting('contact_email', '')),
                'phoneTel' => preg_replace('/\s+/', '', (string)RoomModel::getSetting('contact_phone', '')),
            ],
        ];
    }

    protected function renderPublic($viewFile, array $data = [], $activePage = '', $pageTitle = '')
    {
        $layout = $this->buildPublicLayoutData($activePage, $pageTitle);
        extract($data);

        require_once BASE_PATH . 'views/layouts/header.php';
        require_once BASE_PATH . $viewFile;
        require_once BASE_PATH . 'views/layouts/footer.php';
    }
}

