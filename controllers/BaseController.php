<?php
/**
 * BaseController - Lớp cơ sở cho các controller công khai (public pages)
 * Chịu trách nhiệm: xây dựng dữ liệu layout chung, render view công khai
 */
class BaseController
{
    /**
     * Xây dựng dữ liệu layout chung cho tất cả trang công khai
     * Bao gồm: thông tin site, navigation URLs, meta, brand, contact, fallback mode
     * Hỗ trợ chế độ preview cho admin (ghi đè qua query string)
     */
    protected function buildPublicLayoutData(string $activePage = '', string $pageTitle = ''): array
    {
        // Lấy setting từ DB (có fallback mặc định)
        $siteName        = RoomModel::getSetting('site_name', 'NhaTroA');
        $siteDescription = fallbackText(RoomModel::getSetting('site_description', ''), 'Website chính thức của khu trọ, giúp khách xem phòng trống và liên hệ nhanh với chủ trọ.');
        $siteSlogan      = fallbackText(RoomModel::getSetting('site_slogan', ''), 'Xem phòng rõ ràng, liên hệ dễ dàng');
        $contactAddress  = fallbackText(RoomModel::getSetting('contact_address', ''));
        $contactPhone    = fallbackText(RoomModel::getSetting('contact_phone', ''));
        $contactEmail    = fallbackText(RoomModel::getSetting('contact_email', ''));

        // Chế độ preview cho admin: cho phép ghi đè setting qua query string (?ov[site_name]=...)
        if (!empty($GLOBALS['cmsPreviewAdmin'])) {
            $ov = is_array($_GET['ov'] ?? null) ? $_GET['ov'] : [];
            $siteName        = $this->getOverride($ov, 'site_name', $siteName);
            $siteDescription = $this->getOverride($ov, 'site_description', $siteDescription);
            $siteSlogan      = $this->getOverride($ov, 'site_slogan', $siteSlogan);
            $contactAddress  = $this->getOverride($ov, 'contact_address', $contactAddress);
            $contactPhone    = $this->getOverride($ov, 'contact_phone', $contactPhone);
            $contactEmail    = $this->getOverride($ov, 'contact_email', $contactEmail);
        }

        $defaultTitle = $siteName . ' - Website chính thức của khu trọ';
        $finalTitle   = $pageTitle !== '' ? $pageTitle : $defaultTitle;

        return [
            'siteName'       => $siteName,
            'pageTitle'      => $finalTitle,
            'activePage'     => $activePage,
            'urls'           => $this->getPublicUrls($siteName),
            'isFallbackMode' => !Database::hasConnection(),
            'meta'           => ['description' => $siteDescription],
            'brand'          => ['tagline' => $siteSlogan],
            'contact'        => [
                'address' => $contactAddress,
                'phone'   => $contactPhone,
                'email'   => $contactEmail,
                'phoneTel'=> preg_replace('/\s+/', '', (string)$contactPhone),
            ],
        ];
    }

    /**
     * Helper lấy giá trị override từ mảng preview, fallback về giá trị mặc định
     */
    private function getOverride(array $ov, string $key, string $default): string
    {
        if (array_key_exists($key, $ov)) {
            $val = trim((string)$ov[$key]);
            return $val !== '' ? $val : $default;
        }
        return $default;
    }

    /**
     * Danh sách URL công khai dùng cho navigation
     */
    private function getPublicUrls(string $siteName): array
    {
        return [
            'home'     => BASE_URL . '?page=home',
            'rooms'    => BASE_URL . '?page=rooms',
            'intro'    => BASE_URL . '?page=intro',
            'register' => BASE_URL . '?page=register',
            'login'    => BASE_URL . '?page=login',
            'admin'    => BASE_URL . '?page=admin',
            'tenant'   => BASE_URL . '?page=tenant',
            'logout'   => BASE_URL . '?page=logout',
        ];
    }

    /**
     * Render trang công khai: header -> view content -> footer
     * Sử dụng extract() để biến trong $data thành biến cục bộ cho view
     */
    protected function renderPublic(string $viewFile, array $data = [], string $activePage = '', string $pageTitle = ''): void
    {
        $layout = $this->buildPublicLayoutData($activePage, $pageTitle);
        extract($data);

        require_once BASE_PATH . 'views/layouts/header.php';
        require_once BASE_PATH . $viewFile;
        require_once BASE_PATH . 'views/layouts/footer.php';
    }
}