<?php
class HomeController extends BaseController {
    /**
     * Gom sẵn nội dung marketing tại controller để view chỉ còn nhiệm vụ render.
     */
    private function getMarketingContent() {
        return [
            'heroBadges' => [
                ['icon' => 'verified_user', 'label' => 'An ninh kiểm soát'],
                ['icon' => 'cleaning_services', 'label' => 'Vệ sinh định kỳ'],
                ['icon' => 'support_agent', 'label' => 'Hỗ trợ nhanh 24/7'],
            ],
            'marketingHighlights' => [
                [
                    'icon' => 'shield_with_house',
                    'title' => 'Thông tin rõ để dễ chọn',
                    'text' => 'Website trình bày rõ phòng, giá thuê, tiện ích và thời điểm có thể vào ở để người xem so sánh nhanh hơn.',
                ],
                [
                    'icon' => 'contract',
                    'title' => 'Xem phòng rồi liên hệ trực tiếp',
                    'text' => 'Người thuê có thể xem trước khu nhà phù hợp rồi mới liên hệ với chủ trọ, thay vì phải hỏi từng thông tin nhỏ lẻ.',
                ],
                [
                    'icon' => 'hotel_class',
                    'title' => 'Quản lý gọn, trải nghiệm thật',
                    'text' => 'Từ hình ảnh, tiện ích đến hỗ trợ cư dân đều được trình bày thực tế và nhất quán để tạo cảm giác chuyên nghiệp, dễ tin.',
                ],
            ],
            'livingSteps' => [
                [
                    'step' => '01',
                    'title' => 'Xem phòng trực quan',
                    'text' => 'Xem hình ảnh, mức giá, khu nhà và trạng thái phòng ngay trên website trước khi quyết định.',
                ],
                [
                    'step' => '02',
                    'title' => 'Đặt lịch nhanh',
                    'text' => 'Liên hệ hoặc tạo tài khoản để được giữ chỗ, tư vấn khu phù hợp và nhận lịch xem trong ngày.',
                ],
                [
                    'step' => '03',
                    'title' => 'Vào ở yên tâm',
                    'text' => 'Nhận hỗ trợ về thủ tục, dịch vụ và thông tin cư dân trong cùng một hệ thống thống nhất.',
                ],
            ],
            'testimonials' => [
                ['name' => 'Minh Tuấn', 'role' => 'Sinh viên IT', 'text' => 'Điều mình thích nhất là cảm giác an tâm. Mọi thứ từ wifi, an ninh đến hỗ trợ đều rất rõ ràng và nhanh gọn.'],
                ['name' => 'Linh Chi', 'role' => 'Designer', 'text' => 'Website cho cảm giác rất chuyên nghiệp, xem phòng dễ, hình ảnh sát thực tế và không bị mập mờ về chi phí.'],
                ['name' => 'Hoàng Hải', 'role' => 'Nhân viên văn phòng', 'text' => 'Không gian sống sạch sẽ, chủ động hỗ trợ tốt và khu trọ tạo cảm giác uy tín hơn hẳn nhiều nơi khác.'],
            ],
            'faqItems' => [
                [
                    'question' => 'Có thể xem phòng trước khi quyết định không?',
                    'answer' => 'Có. Bạn có thể xem thông tin online trước, sau đó đặt lịch để được hỗ trợ xem phòng thực tế nhanh chóng.',
                ],
                [
                    'question' => 'Thông tin giá thuê có minh bạch không?',
                    'answer' => 'Website ưu tiên hiển thị giá, trạng thái phòng và mô tả rõ ràng để người thuê dễ so sánh và lựa chọn.',
                ],
                [
                    'question' => 'Người thuê có được hỗ trợ sau khi vào ở không?',
                    'answer' => 'Có. Khu trọ hướng tới trải nghiệm cư dân lâu dài nên phần hỗ trợ, dịch vụ và cập nhật thông tin luôn được ưu tiên.',
                ],
            ],
            'buildingTypeMap' => [
                'zone' => ['Khu vực', 'bg-blue-500', 'map'],
                'block' => ['Dãy nhà', 'bg-green-500', 'view_in_ar'],
                'building' => ['Tòa nhà', 'bg-purple-500', 'apartment'],
                'floor' => ['Tầng', 'bg-orange-500', 'layers'],
            ],
            'introStory' => [
                'title' => 'Website này là trang giới thiệu chính thức của khu trọ, không chỉ là nơi đăng phòng trống.',
                'text' => 'Khi người tìm trọ truy cập vào website, điều họ cần là thông tin rõ ràng, hình ảnh dễ xem và cách liên hệ nhanh. Vì vậy phần trang chủ được tổ chức theo hướng giới thiệu và marketing cho chính khu trọ này, để khách mới vẫn có thể vào xem và thuê trực tiếp.',
            ],
            'introValues' => [
                [
                    'icon' => 'gpp_good',
                    'title' => 'Giới thiệu đúng trọng tâm',
                    'text' => 'Nội dung tập trung vào khu trọ này, giúp khách mới hiểu nhanh nơi ở, giá thuê và cách liên hệ.',
                ],
                [
                    'icon' => 'auto_awesome',
                    'title' => 'Hình ảnh và thông tin đồng nhất',
                    'text' => 'Ảnh phòng, tiện ích, trạng thái trống và lời giới thiệu được giữ cùng một cách trình bày để nhìn gọn và đáng tin hơn.',
                ],
                [
                    'icon' => 'account_tree',
                    'title' => 'Chuẩn MVC để dễ mở rộng',
                    'text' => 'Nội dung marketing được chuẩn hóa ở controller, giúp view gọn hơn và dễ phát triển tiếp.',
                ],
            ],
        ];
    }

    /**
     * Trang chủ ưu tiên trải nghiệm xem phòng nhanh, ít logic tại view.
     */
    public function index() {
        $featuredRaw = RoomModel::getAvailableOrUpcoming(6);
        $buildings = RoomModel::getBuildings();
        $amenities = RoomModel::getAmenities();
        $siteName = RoomModel::getSetting('site_name', 'NhaTroA');
        $hero = [
            // Dùng fallback sát ngữ cảnh "website chính thức của một khu trọ" thay vì slogan chung chung.
            'siteSlogan' => RoomModel::getSetting('site_slogan', 'Trang chính thức của khu trọ'),
            'siteDescription' => RoomModel::getSetting(
                'hero_subheadline',
                RoomModel::getSetting('site_description', 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ.')
            ),
            'heroImage' => RoomModel::getSetting('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600'),
            'headline1' => RoomModel::getSetting('hero_headline_1', 'Xem Phòng Rõ'),
            'headline2' => RoomModel::getSetting('hero_headline_2', 'Chọn Chỗ Ở Dễ'),
        ];

        $heroStats = [
            ['value' => RoomModel::countByStatus('available'), 'suffix' => '+', 'label' => 'Phòng đang sẵn sàng'],
            ['value' => count($buildings), 'suffix' => '', 'label' => 'Khu nhà được vận hành'],
            ['value' => count($amenities), 'suffix' => '+', 'label' => 'Tiện ích cho cư dân'],
        ];

        $quickStats = [
            [
                'icon' => 'local_fire_department',
                'wrapperClass' => 'bg-red-100 text-red-600',
                'value' => RoomModel::countByStatus('available'),
                'label' => 'Phòng đang trống',
                'useCounter' => true,
            ],
            [
                'icon' => 'payments',
                'wrapperClass' => 'bg-primary/10 text-primary',
                'value' => fallbackText(RoomModel::getSetting('stat_1_value', 'Hợp lý')),
                'label' => fallbackText(RoomModel::getSetting('stat_1_label', 'Giá cả sinh viên')),
                'useCounter' => false,
                'valueClass' => 'text-primary',
            ],
            [
                'icon' => 'school',
                'wrapperClass' => 'bg-secondary/10 text-secondary',
                'value' => fallbackText(RoomModel::getSetting('stat_2_value', '20+')),
                'label' => fallbackText(RoomModel::getSetting('stat_2_label', 'Dịch vụ tiện ích')),
                'useCounter' => false,
                'valueClass' => 'text-secondary',
            ],
        ];

        $featured = array_map(static function ($room) {
            $isUpcoming = ($room['notice_given'] ?? 0) == 1 && ($room['status'] ?? '') === 'rented';
            $daysLeft = null;
            $expectedVacantText = '';
            if ($isUpcoming && !empty($room['expected_vacant_date'])) {
                $daysLeft = RoomModel::getDaysUntilVacant($room['expected_vacant_date']);
                $expectedVacantText = date('d/m/Y', strtotime($room['expected_vacant_date']));
            }

            $room['isUpcoming'] = $isUpcoming;
            $room['daysLeft'] = $daysLeft;
            $room['expectedVacantText'] = $expectedVacantText;
            return $room;
        }, $featuredRaw);

        extract($this->getMarketingContent());
        $pageTitle = 'Trang chủ - ' . $siteName;

        $this->renderPublic('views/pages/home.php', compact(
            'siteName',
            'amenities',
            'featured',
            'buildings',
            'hero',
            'heroStats',
            'quickStats',
            'heroBadges',
            'marketingHighlights',
            'livingSteps',
            'testimonials',
            'faqItems',
            'buildingTypeMap'
        ), 'home', $pageTitle);
    }

    public function intro() {
        $siteName = RoomModel::getSetting('site_name', 'NhaTroA');
        $introStats = [
            ['value' => 5, 'suffix' => '+', 'label' => 'Năm kinh nghiệm vận hành'],
            ['value' => BuildingModel::count(), 'suffix' => '', 'label' => 'Khu nhà đang quản lý'],
            ['value' => RoomModel::count(), 'suffix' => '+', 'label' => 'Không gian lưu trú'],
            ['value' => UserModel::countByRole(0), 'suffix' => '+', 'label' => 'Cư dân trong hệ thống'],
        ];
        $introJourney = [
            ['title' => 'Khởi đầu từ nhu cầu thực', 'text' => 'Ý tưởng hình thành từ mong muốn tạo ra nơi ở gọn gàng, an toàn và thân thiện cho sinh viên, người đi làm.'],
            ['title' => 'Chuẩn hóa trải nghiệm', 'text' => 'Không chỉ vận hành khu trọ, hệ thống còn tập trung trình bày thông tin rõ ràng để người thuê cảm thấy an tâm ngay từ online.'],
            ['title' => 'Tối ưu để mở rộng', 'text' => 'Mô hình MVC và giao diện tách lớp giúp website dễ tiếp tục nâng cấp tính năng sau giai đoạn hoàn thiện hình ảnh thương hiệu.'],
        ];
        extract($this->getMarketingContent());
        $pageTitle = 'Giới thiệu - ' . $siteName;

        $this->renderPublic('views/pages/intro.php', compact(
            'siteName',
            'introStats',
            'introJourney',
            'introStory',
            'introValues',
            'heroBadges',
            'marketingHighlights',
            'livingSteps',
            'testimonials',
            'faqItems'
        ), 'intro', $pageTitle);
    }

    public function rooms() {
        // Trang public chỉ nhận những bộ lọc thực sự hữu ích cho người đi thuê.
        $filters = RoomModel::normalizePublicFilters([
            'building_id' => $_GET['building_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'services' => $_GET['services'] ?? [],
        ]);

        $rooms = RoomModel::getPublicCatalog($filters);
        $buildings = BuildingModel::getAll();
        $featureOptions = RoomModel::getPublicFeatureOptions();
        $selectedBuilding = !empty($filters['building_id']) ? BuildingModel::getById($filters['building_id']) : null;
        $siteName = RoomModel::getSetting('site_name', 'NhaTroA');
        $pageTitle = 'Danh sách phòng - ' . $siteName;

        $this->renderPublic(
            'views/pages/rooms.php',
            compact('rooms', 'buildings', 'filters', 'siteName', 'featureOptions', 'selectedBuilding'),
            'rooms',
            $pageTitle
        );
    }
    
    public function admin() {
        $brandSettings = RoomModel::getSettingsByGroup('brand');
        $heroSettings = RoomModel::getSettingsByGroup('hero');
        $contactSettings = RoomModel::getSettingsByGroup('contact');
        $pageTitle = 'Cấu hình giao diện - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        
        require_once BASE_PATH . 'views/admin/dashboard.php';
    }
    
    public function saveSettings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $realKey = substr($key, 8);
                    RoomModel::saveSetting($realKey, $value);
                }
            }
        }
        header('Location: ' . BASE_URL . '?page=admin&saved=1');
        exit;
    }
}
