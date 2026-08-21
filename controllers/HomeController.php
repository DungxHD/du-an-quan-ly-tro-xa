<?php
/**
 * HomeController - Điều khiển trang chủ, giới thiệu, danh sách phòng (public)
 */
class HomeController extends BaseController
{
    // ==========================================
    // MARKETING CONTENT (Static data for landing page)
    // ==========================================

    /**
     * Trả về nội dung marketing tĩnh dùng cho trang chủ & giới thiệu
     * Dữ liệu tách ra để view chỉ lo render, không hard-code
     */
    private function getMarketingContent(): array
    {
        return [
            'heroBadges' => [
                ['icon' => 'verified_user', 'label' => 'An ninh kiểm soát'],
                ['icon' => 'cleaning_services', 'label' => 'Vệ sinh định kỳ'],
                ['icon' => 'support_agent', 'label' => 'Hỗ trợ nhanh 24/7'],
            ],
            'marketingHighlights' => [
                ['icon' => 'shield_with_house', 'title' => 'Thông tin rõ để dễ chọn', 'text' => 'Website trình bày rõ phòng, giá thuê, tiện ích và thời điểm có thể vào ở để người xem so sánh nhanh hơn.'],
                ['icon' => 'contact_phone', 'title' => 'Xem phòng rồi liên hệ trực tiếp', 'text' => 'Người thuê có thể xem trước khu nhà phù hợp rồi mới liên hệ với chủ trọ, thay vì phải hỏi từng thông tin nhỏ lẻ.'],
                ['icon' => 'hotel_class', 'title' => 'Quản lý gọn, trải nghiệm thật', 'text' => 'Từ hình ảnh, tiện ích đến hỗ trợ cư dân đều được trình bày thực tế và nhất quán để tạo cảm giác chuyên nghiệp, dễ tin.'],
            ],
            'livingSteps' => [
                ['step' => '01', 'title' => 'Xem phòng trực quan', 'text' => 'Xem hình ảnh, mức giá, khu nhà và trạng thái phòng ngay trên website trước khi quyết định.'],
                ['step' => '02', 'title' => 'Đặt lịch nhanh', 'text' => 'Liên hệ hoặc tạo tài khoản để được giữ chỗ, tư vấn khu phù hợp và nhận lịch xem trong ngày.'],
                ['step' => '03', 'title' => 'Vào ở yên tâm', 'text' => 'Nhận hỗ trợ về thủ tục, dịch vụ và thông tin cư dân trong cùng một hệ thống thống nhất.'],
            ],
            'faqItems' => [
                ['question' => 'Có thể xem phòng trước khi quyết định không?', 'answer' => 'Có. Bạn có thể xem thông tin online trước, sau đó đặt lịch để được hỗ trợ xem phòng thực tế nhanh chóng.'],
                ['question' => 'Thông tin giá thuê có minh bạch không?', 'answer' => 'Website ưu tiên hiển thị giá, trạng thái phòng và mô tả rõ ràng để người thuê dễ so sánh và lựa chọn.'],
                ['question' => 'Người thuê có được hỗ trợ sau khi vào ở không?', 'answer' => 'Có. Khu trọ hướng tới trải nghiệm cư dân lâu dài nên phần hỗ trợ, dịch vụ và cập nhật thông tin luôn được ưu tiên.'],
            ],
            'buildingTypeMap' => [
                'zone' => ['Khu vực', 'bg-blue-500', 'map'],
                'block' => ['Dãy nhà', 'bg-green-500', 'view_in_ar'],
                'building' => ['Tòa nhà', 'bg-purple-500', 'apartment'],
                'floor' => ['Tầng', 'bg-orange-500', 'layers'],
            ],
            'introStory' => [
                'eyebrow' => 'Giới thiệu khu trọ',
                'title' => 'Một website gọn gàng để người tìm trọ hiểu đúng về nơi ở trước khi liên hệ.',
                'text' => 'Khu trọ này được xây dựng theo hướng minh bạch: có khu rõ ràng, tầng rõ ràng, phòng rõ ràng và tiện ích được công khai ngay từ đầu. Mục tiêu không phải nói quá nhiều, mà là giúp người thuê nhìn vào là biết nơi này có phù hợp hay không.',
            ],
            'introValues' => [
                ['icon' => 'gpp_good', 'title' => 'Minh bạch từ thông tin cơ bản', 'text' => 'Khách mới có thể xem khu, tầng, phòng, giá thuê và tiện ích mà không phải hỏi từng mẩu thông tin rời rạc.'],
                ['icon' => 'auto_awesome', 'title' => 'Trải nghiệm xem phòng mạch lạc', 'text' => 'Ảnh đại diện, trạng thái phòng, số lượt xem và lối đi tới danh sách phòng được giữ thống nhất để người xem thao tác nhanh hơn.'],
                ['icon' => 'diversity_3', 'title' => 'Tập trung vào cư dân thực tế', 'text' => 'Mọi nội dung đều xoay quanh một khu trọ duy nhất, ưu tiên cảm giác tin cậy, dễ ở và dễ liên hệ lâu dài.'],
            ],
        ];
    }

    // ==========================================
    // DATA HELPERS
    // ==========================================

    /**
     * Đếm số cư dân (role = 0)
     */
    private function countResidents(): int
    {
        if (Database::hasConnection()) {
            return (int)(Database::fetchOne('SELECT COUNT(*) AS total FROM users WHERE role = ?', [0])['total'] ?? 0);
        }
        return count(array_filter(Database::getTable('users'), fn($u) => (int)($u['role'] ?? -1) === 0));
    }

    /**
     * Lấy phòng nổi bật cho trang chủ: chỉ available, sắp xếp theo views desc
     */
    private function getFeaturedAvailableRooms(int $limit = 6): array
    {
        $rooms = RoomModel::getAll(['status' => 'available']);
        usort($rooms, fn($a, $b) => ($b['views'] ?? 0) <=> ($a['views'] ?? 0) ?: ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
        return array_slice($rooms, 0, $limit);
    }

    /**
     * Xây dựng dữ liệu showcase khu nhà cho landing page
     * Gom metrics (upcoming, views) theo area từ danh sách phòng
     */
    private function buildAreaShowcase(array $areas, array $rooms): array
    {
        $metricsByArea = [];
        foreach ($rooms as $room) {
            $areaId = (int)($room['area_id'] ?? 0);
            if ($areaId <= 0) continue;

            $metricsByArea[$areaId] ??= ['upcoming' => 0, 'views' => 0];
            $metricsByArea[$areaId]['views'] += (int)($room['views'] ?? 0);

            if ((int)($room['notice_given'] ?? 0) === 1
                && ($room['status'] ?? '') === 'rented'
                && !empty($room['expected_vacant_date'])) {
                $metricsByArea[$areaId]['upcoming']++;
            }
        }

        return array_map(function ($area) use ($metricsByArea) {
            $areaId = (int)($area['id'] ?? 0);
            $floors = $area['floors'] ?? [];
            usort($floors, fn($a, $b) => ($a['floor_number'] ?? 0) <=> ($b['floor_number'] ?? 0));

            $extra = $metricsByArea[$areaId] ?? ['upcoming' => 0, 'views' => 0];
            $area['upcoming_count']   = $extra['upcoming'];
            $area['open_room_count']  = ($area['available_count'] ?? 0) + $extra['upcoming'];
            $area['views']            = $extra['views'];
            $area['rooms_url']        = BASE_URL . '?page=rooms&area_id=' . $areaId;
            $area['image']            = $area['image'] ?: SettingModel::get('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600');
            $area['floor_labels']     = array_map(fn($f) => $f['name'] ?? '', array_slice($floors, 0, 4));

            return $area;
        }, $areas);
    }

    /**
     * Áp dụng override CMS preview cho admin (ghi đè setting qua query string)
     */
    private function applyCmsOverrides(array &$data, string $prefix = ''): void
    {
        if (empty($GLOBALS['cmsPreviewAdmin'])) return;
        $ov = is_array($_GET['ov'] ?? null) ? $_GET['ov'] : [];
        foreach ($ov as $key => $val) {
            $fullKey = $prefix . $key;
            if (isset($data[$fullKey]) && trim((string)$val) !== '') {
                $data[$fullKey] = trim((string)$val);
            }
        }
    }

    // ==========================================
    // PUBLIC ACTIONS
    // ==========================================

    /**
     * Trang chủ - landing page
     */
    public function index(): void
    {
        $siteName = SettingModel::get('site_name', 'NhaTroA');

        // Dữ liệu marketing
        $marketing = $this->getMarketingContent();

        // Dữ liệu động
        $featured    = $this->getFeaturedAvailableRooms(6);
        $areas       = AreaModel::getTree();
        $allRooms    = RoomModel::getAll();
        $areaShowcase = $this->buildAreaShowcase($areas, $allRooms);
        $residentCount = $this->countResidents();

        // Hero section
        $hero = [
            'siteSlogan'     => SettingModel::get('site_slogan', 'Trang chính thức của khu trọ'),
            'siteDescription'=> SettingModel::get('hero_subheadline', SettingModel::get('site_description', 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ.')),
            'heroImage'      => SettingModel::get('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600'),
            'headline1'      => SettingModel::get('hero_headline_1', 'Xem Phòng Rõ'),
            'headline2'      => SettingModel::get('hero_headline_2', 'Chọn Chỗ Ở Dễ'),
        ];

        // CMS preview overrides
        if (!empty($GLOBALS['cmsPreviewAdmin'])) {
            $ov = is_array($_GET['ov'] ?? null) ? $_GET['ov'] : [];
            $siteName = $ov['site_name'] ?? $siteName;
            $hero['siteDescription'] = $ov['hero_subheadline'] ?? $hero['siteDescription'];
            $hero['heroImage']       = $ov['hero_image'] ?? $hero['heroImage'];
            $hero['siteSlogan']      = $ov['site_slogan'] ?? $hero['siteSlogan'];
        }

        // Hero stats (configurable từ settings)
        $heroStats = [
            ['value' => SettingModel::get('stat_1_value', RoomModel::countByStatus('available') . '+'), 'suffix' => '', 'label' => SettingModel::get('stat_1_label', 'Phòng đang trống')],
            ['value' => SettingModel::get('stat_2_value', (string)count($areas)), 'suffix' => '', 'label' => SettingModel::get('stat_2_label', 'Khu đang vận hành')],
            ['value' => SettingModel::get('stat_3_value', count($marketing['heroBadges']) . '+'), 'suffix' => '', 'label' => SettingModel::get('stat_3_label', 'Tiện ích đang mở')],
        ];

        // Quick stats cards
        $quickStats = [
            ['icon' => 'apartment', 'wrapperClass' => 'bg-primary/10 text-primary', 'value' => count($areas), 'label' => 'Khu lưu trú rõ ràng', 'useCounter' => true],
            ['icon' => 'meeting_room', 'wrapperClass' => 'bg-green-100 text-green-600', 'value' => RoomModel::countByStatus('available'), 'label' => 'Phòng có thể xem ngay', 'useCounter' => true],
            ['icon' => 'groups', 'wrapperClass' => 'bg-secondary/10 text-secondary', 'value' => $residentCount, 'label' => 'Cư dân đang sinh hoạt', 'useCounter' => true],
        ];

        extract($marketing);
        $pageTitle = 'Trang chủ - ' . $siteName;

        $this->renderPublic('views/pages/home.php', compact(
            'siteName', 'amenities', 'featured', 'areas', 'areaShowcase', 'hero', 'heroStats', 'quickStats',
            'heroBadges', 'marketingHighlights', 'livingSteps', 'faqItems'
        ), 'home', $pageTitle);
    }

    /**
     * Trang giới thiệu khu trọ
     */
    public function intro(): void
    {
        $siteName = SettingModel::get('site_name', 'NhaTroA');
        $areas = AreaModel::getTree();
        $areaCount = count($areas);
        $roomCount = RoomModel::count();
        $residentCount = $this->countResidents();

        // Hero image fallback
        $introImage = SettingModel::get('hero_image', '');
        if ($introImage === '') {
            $introImage = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600';
            foreach ($areas as $area) {
                if (!empty($area['image'])) {
                    $introImage = $area['image'];
                    break;
                }
            }
        }

        // CMS preview
        if (!empty($GLOBALS['cmsPreviewAdmin'])) {
            $ov = is_array($_GET['ov'] ?? null) ? $_GET['ov'] : [];
            $siteName = $ov['site_name'] ?? $siteName;
            $introImage = $ov['hero_image'] ?? $introImage;
        }

        $introStats = [
            ['value' => $areaCount, 'suffix' => '', 'label' => 'Khu đang vận hành', 'note' => 'Mỗi khu có tầng và danh sách phòng riêng.'],
            ['value' => $roomCount, 'suffix' => '+', 'label' => 'Phòng đang quản lý', 'note' => 'Tất cả phòng đều được tổ chức theo `areas -> floors -> rooms`.'],
            ['value' => $residentCount, 'suffix' => '+', 'label' => 'Cư dân trong hệ thống', 'note' => 'Thống kê lấy trực tiếp từ nhóm tài khoản cư dân.'],
        ];

        $introJourney = [
            ['title' => 'Bắt đầu từ nhu cầu ở thật', 'text' => 'Người tìm trọ thường mất thời gian vì thông tin rời rạc. Khu trọ này được giới thiệu theo cách ngắn gọn, xem là hiểu.'],
            ['title' => 'Chuẩn hóa theo từng khu và tầng', 'text' => 'Mỗi khu đều có địa chỉ, ảnh đại diện, mô tả và số liệu phòng riêng để người xem nắm được bố cục tổng thể.'],
            ['title' => 'Ưu tiên sự tin cậy lâu dài', 'text' => 'Mục tiêu không dừng ở việc lấp phòng trống, mà là xây dựng một nơi ở có trải nghiệm rõ ràng và ổn định cho cư dân.'],
        ];

        $areasPreview = array_slice($areas, 0, 3);
        extract($this->getMarketingContent());
        $pageTitle = 'Giới thiệu - ' . $siteName;

        $this->renderPublic('views/pages/intro.php', compact(
            'siteName', 'areas', 'areasPreview', 'introImage', 'introStats', 'introJourney',
            'introStory', 'introValues', 'heroBadges', 'marketingHighlights'
        ), 'intro', $pageTitle);
    }

    /**
     * Danh sách phòng công khai (có filter, pagination)
     */
    public function rooms(): void
    {
        $filters = RoomModel::normalizePublicFilters([
            'area_id'   => $_GET['area_id'] ?? ($_GET['building_id'] ?? ''),
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'amenities' => $_GET['amenities'] ?? ($_GET['services'] ?? []),
        ]);

        $rooms = RoomModel::getPublicCatalog($filters);
        $areas = AreaModel::getAllWithStats();
        $featureOptions = RoomModel::getPublicFeatureOptions();
        $selectedArea = !empty($filters['area_id']) ? AreaModel::getById($filters['area_id']) : null;
        $siteName = RoomModel::getSetting('site_name', 'NhaTroA');
        $pageTitle = 'Danh sách phòng - ' . $siteName;

        $this->renderPublic(
            'views/pages/rooms.php',
            compact('rooms', 'areas', 'filters', 'siteName', 'featureOptions', 'selectedArea'),
            'rooms',
            $pageTitle
        );
    }

    /**
     * API filter phòng (AJAX) - trả về HTML cards + total
     */
    public function roomsFilterApi(): void
    {
        header('Content-Type: application/json');

        $filters = RoomModel::normalizePublicFilters([
            'area_id'   => $_GET['area_id'] ?? '',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'amenities' => $_GET['amenities'] ?? [],
        ]);

        $rooms = RoomModel::getPublicCatalog($filters);

        $roomsHtml = '';
        foreach ($rooms as $room) {
            $roomsHtml .= RoomModel::renderRoomCardHtml($room);
        }

        echo json_encode([
            'success' => true,
            'rooms'   => $roomsHtml,
            'total'   => count($rooms),
            'messages'=> $filters['messages'] ?? [],
        ]);
        exit;
    }
}