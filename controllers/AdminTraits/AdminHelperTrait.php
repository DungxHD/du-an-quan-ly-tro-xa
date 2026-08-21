<?php
/**
 * AdminHelperTrait - Các helper dùng chung cho admin panel
 * Chứa: xử lý upload ảnh, finalize ảnh, setting schema, validation, icon options
 */
trait AdminHelperTrait
{
    // ==========================================
    // UPLOAD & IMAGE HELPERS
    // ==========================================

    /**
     * Đối chiếu URL ảnh upload về đường dẫn file cục bộ (chặn path traversal)
     */
    private function resolveUploadLocalPath(string $url): ?string
    {
        $prefix = BASE_URL . '.uploads/';
        if (!is_string($url) || strpos($url, $prefix) !== 0) return null;
        $rel = substr($url, strlen($prefix));
        if (strpos($rel, '..') !== false) return null;
        $local = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($local) ? $local : null;
    }

    /**
     * Finalize ảnh khu: chuyển từ image_khu_new -> image_khu_{id}, cập nhật DB, dọn ảnh nháp
     */
    private function finalizeNewAreaImage(int $areaId, string $imageUrl): string
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== 'image_khu_new') return $imageUrl;

        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_khu_' . $areaId;
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        $fileName = basename($local);
        $dest = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@rename($local, $dest)) return $imageUrl;

        $newUrl = BASE_URL . '.uploads/image_khu_' . $areaId . '/' . $fileName;
        Database::update('areas', ['image' => $newUrl], 'id = :id', ['id' => $areaId]);

        // Dọn ảnh nháp còn sót
        $tmpDir = dirname($local);
        foreach (scandir($tmpDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $tmpDir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) @unlink($path);
        }
        return $newUrl;
    }

    /**
     * Finalize ảnh phòng: chuyển từ image_phong_new -> image_phong_{id}
     */
    private function relocateRoomImageUrl(string $url, int $roomId): string
    {
        $prefix = BASE_URL . '.uploads/image_phong_new/';
        if (!is_string($url) || strpos($url, $prefix) !== 0) return $url;

        $fileName = basename((string)(parse_url($url, PHP_URL_PATH) ?: $url));
        $src = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_new' . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($src)) return $url;

        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_' . $roomId;
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        if (!@rename($src, $destDir . DIRECTORY_SEPARATOR . $fileName)) return $url;

        return BASE_URL . '.uploads/image_phong_' . $roomId . '/' . $fileName;
    }

    // ==========================================
    // SETTING SCHEMA & VALIDATION
    // ==========================================

    /**
     * Schema cấu hình setting cho dashboard admin (view render động)
     * Mỗi section chứa: id, title, icon, description, fields[]
     * Mỗi field: key, label, type, group, default, placeholder, tooltip, v.v.
     */
    private function buildAdminSettingSections(): array
    {
        return [
            $this->sectionBrand(),
            $this->sectionHero(),
            $this->sectionContact(),
            $this->sectionStats(),
            $this->sectionModeration(),
            $this->sectionPayment(),
        ];
    }

    private function sectionBrand(): array
    {
        return [
            'id' => 'brand', 'title' => 'Thương hiệu', 'icon' => 'storefront',
            'description' => 'Tên website, slogan và mô tả thương hiệu dùng chung cho header, footer và meta công khai.',
            'fields' => [
                ['key' => 'site_name', 'label' => 'Tên website', 'type' => 'text', 'group' => 'brand', 'default' => 'NhaTroA', 'placeholder' => 'Ví dụ: Nhà trọ Xanh', 'tooltip' => 'Tên hiển thị trên tab trình duyệt, header và panel quản trị.'],
                ['key' => 'site_slogan', 'label' => 'Slogan', 'type' => 'text', 'group' => 'brand', 'default' => 'Trang chính thức của khu trọ', 'placeholder' => 'Một câu ngắn tạo cảm giác tin cậy', 'tooltip' => 'Câu giới thiệu ngắn xuất hiện nổi bật ở phần đầu trang.'],
                ['key' => 'site_description', 'label' => 'Mô tả website', 'type' => 'textarea', 'group' => 'brand', 'default' => 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ với chủ trọ.', 'rows' => 3, 'placeholder' => 'Mô tả ngắn về khu trọ và trải nghiệm người thuê', 'tooltip' => 'Dùng cho phần giới thiệu và meta description cơ bản.'],
            ],
        ];
    }

    private function sectionHero(): array
    {
        return [
            'id' => 'hero', 'title' => 'Hero Banner', 'icon' => 'image',
            'description' => 'Điều khiển tiêu đề lớn, ảnh đầu trang và đoạn mô tả chính của landing page.',
            'fields' => [
                ['key' => 'hero_headline_1', 'label' => 'Headline dòng 1', 'type' => 'text', 'group' => 'hero', 'default' => 'Xem Phòng Rõ', 'placeholder' => 'Ví dụ: Không Gian Sống', 'tooltip' => 'Dòng tiêu đề đầu tiên hiển thị cỡ lớn trên ảnh hero.'],
                ['key' => 'hero_headline_2', 'label' => 'Headline dòng 2', 'type' => 'text', 'group' => 'hero', 'default' => 'Chọn Chỗ Ở Dễ', 'placeholder' => 'Ví dụ: Chuẩn Mực', 'tooltip' => 'Dòng nhấn mạnh bằng gradient để tăng điểm nhấn thị giác.'],
                ['key' => 'hero_subheadline', 'label' => 'Mô tả hero', 'type' => 'textarea', 'group' => 'hero', 'default' => 'Xem phòng trống, tiện ích và mức giá rõ ràng trước khi liên hệ.', 'rows' => 3, 'placeholder' => 'Đoạn mô tả ngắn ngay dưới headline', 'tooltip' => 'Nội dung mô tả chính ngay dưới tiêu đề lớn của trang chủ.'],
                ['key' => 'hero_image', 'label' => 'Ảnh hero (Trang chủ & Giới thiệu)', 'type' => 'url', 'group' => 'hero', 'default' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600', 'placeholder' => 'https://...', 'tooltip' => 'URL ảnh nền đầu trang chủ và ảnh chính trang Giới thiệu. Nên dùng ảnh ngang rõ ánh sáng và không bị vỡ.'],
            ],
        ];
    }

    private function sectionContact(): array
    {
        return [
            'id' => 'contact', 'title' => 'Liên hệ', 'icon' => 'contact_phone',
            'description' => 'Thông tin liên hệ hiển thị ở footer, chi tiết phòng và các điểm CTA công khai.',
            'fields' => [
                ['key' => 'contact_address', 'label' => 'Địa chỉ', 'type' => 'textarea', 'group' => 'contact', 'default' => 'Khu Công nghệ cao, TP. Thủ Đức, TP.HCM', 'rows' => 3, 'placeholder' => 'Địa chỉ đầy đủ của khu trọ', 'tooltip' => 'Thông tin địa chỉ hiển thị ở footer và các khu vực liên hệ.'],
                ['key' => 'contact_phone', 'label' => 'Số điện thoại', 'type' => 'tel', 'group' => 'contact', 'default' => '0901 234 567', 'placeholder' => '0901 234 567', 'tooltip' => 'Số hotline dùng cho nút gọi nhanh và hỗ trợ cư dân.'],
                ['key' => 'contact_email', 'label' => 'Email', 'type' => 'email', 'group' => 'contact', 'default' => 'admin@nhatroa.vn', 'placeholder' => 'admin@nhatroa.vn', 'tooltip' => 'Email hiển thị công khai cho người xem cần liên hệ chi tiết.'],
                ['key' => 'contact_zalo', 'label' => 'Zalo', 'type' => 'text', 'group' => 'contact', 'default' => '0901234567', 'placeholder' => 'Số điện thoại Zalo', 'tooltip' => 'Giúp đồng bộ CTA qua Zalo nếu sau này cần mở rộng.'],
            ],
        ];
    }

    private function sectionStats(): array
    {
        return [
            'id' => 'stats', 'title' => 'Chỉ số nổi bật', 'icon' => 'bar_chart',
            'description' => 'Nhóm giá trị hiển thị nổi bật trên landing page để truyền tải điểm mạnh của khu trọ.',
            'fields' => [
                ['key' => 'stat_1_label', 'label' => 'Nhãn chỉ số 1', 'type' => 'text', 'group' => 'stats', 'default' => 'Phòng đang trống', 'placeholder' => 'Ví dụ: Phòng đang trống', 'tooltip' => 'Mô tả ý nghĩa cho chỉ số đầu tiên ở hero.'],
                ['key' => 'stat_1_value', 'label' => 'Giá trị chỉ số 1', 'type' => 'text', 'group' => 'stats', 'default' => '18+', 'placeholder' => 'Ví dụ: 18+', 'tooltip' => 'Có thể nhập số, text ngắn hoặc dạng 24/7, 20+.'],
                ['key' => 'stat_2_label', 'label' => 'Nhãn chỉ số 2', 'type' => 'text', 'group' => 'stats', 'default' => 'Khu vận hành ổn định', 'placeholder' => 'Ví dụ: Khu đang vận hành', 'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ hai.'],
                ['key' => 'stat_2_value', 'label' => 'Giá trị chỉ số 2', 'type' => 'text', 'group' => 'stats', 'default' => '3 khu', 'placeholder' => 'Ví dụ: 3 khu', 'tooltip' => 'Giá trị hiển thị của chỉ số thứ hai.'],
                ['key' => 'stat_3_label', 'label' => 'Nhãn chỉ số 3', 'type' => 'text', 'group' => 'stats', 'default' => 'Hỗ trợ cư dân', 'placeholder' => 'Ví dụ: Hỗ trợ 24/7', 'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ ba.'],
                ['key' => 'stat_3_value', 'label' => 'Giá trị chỉ số 3', 'type' => 'text', 'group' => 'stats', 'default' => '24/7', 'placeholder' => 'Ví dụ: 24/7', 'tooltip' => 'Giá trị hiển thị của chỉ số thứ ba.'],
            ],
        ];
    }

    private function sectionModeration(): array
    {
        return [
            'id' => 'moderation', 'title' => 'Quy tắc đánh giá', 'icon' => 'shield_lock',
            'description' => 'Thiết lập điều kiện gửi và sửa đánh giá của người thuê.',
            'fields' => [
                ['key' => 'min_days_to_review', 'label' => 'Số ngày ở tối thiểu', 'type' => 'number', 'group' => 'moderation', 'default' => '15', 'min' => 0, 'placeholder' => '15', 'tooltip' => 'Người thuê phải ở tối thiểu bao nhiêu ngày mới được đánh giá phòng.'],
                ['key' => 'comment_edit_hours', 'label' => 'Thời gian được sửa', 'type' => 'number', 'group' => 'moderation', 'default' => '24', 'min' => 1, 'placeholder' => '24', 'suffix' => 'giờ', 'tooltip' => 'Khoảng thời gian sau khi gửi mà người thuê còn được sửa đánh giá. Sau đó chỉ còn quyền xóa (không giới hạn thời gian).'],
            ],
        ];
    }

    private function sectionPayment(): array
    {
        return [
            'id' => 'payment', 'title' => 'Thanh toán QR', 'icon' => 'qr_code_2',
            'description' => 'Thông tin tài khoản ngân hàng dùng để tạo mã QR chuyển tiền khi duyệt yêu cầu thuê phòng.',
            'fields' => [
                ['key' => 'bank_name', 'label' => 'Tên ngân hàng', 'type' => 'text', 'group' => 'payment', 'default' => 'Vietcombank', 'placeholder' => 'Ví dụ: Vietcombank', 'tooltip' => 'Tên ngân hàng nhận tiền thuê phòng.'],
                ['key' => 'bank_account_number', 'label' => 'Số tài khoản', 'type' => 'text', 'group' => 'payment', 'default' => '', 'placeholder' => 'Ví dụ: 0011001234567', 'tooltip' => 'Số tài khoản ngân hàng nhận tiền.'],
                ['key' => 'bank_account_holder', 'label' => 'Chủ tài khoản', 'type' => 'text', 'group' => 'payment', 'default' => '', 'placeholder' => 'Tên người nhận trên tài khoản', 'tooltip' => 'Tên chủ tài khoản ngân hàng.'],
            ],
        ];
    }

    /**
     * Trả whitelist cách tính giá để validate request từ form admin
     */
    private function getAllowedServiceBillingModes(): array
    {
        return array_column($this->getServiceBillingModeOptions(), 'value');
    }

    // ==========================================
    // SERVICE BILLING MODE OPTIONS (Metadata cho view)
    // ==========================================

    /**
     * Metadata các cách tính giá để view render badge, tooltip thống nhất
     */
    private function getServiceBillingModeOptions(): array
    {
        return [
            ['value' => 'meter', 'label' => 'Tính theo chỉ số', 'badge_class' => 'bg-cyan-100 text-cyan-700', 'tooltip' => 'Tính theo công tơ/chỉ số tiêu thụ thực tế như điện hoặc nước. Chỉ số được nhập hàng tháng trước khi lập hóa đơn.'],
            ['value' => 'per_person', 'label' => 'Tính theo cá nhân', 'badge_class' => 'bg-purple-100 text-purple-700', 'tooltip' => 'Nhân với số người đang ở hoặc người được áp dụng.'],
            ['value' => 'per_unit', 'label' => 'Tính theo số lượng', 'badge_class' => 'bg-amber-100 text-amber-700', 'tooltip' => 'Nhân theo số lượng đăng ký như số xe, số bình, số thiết bị.'],
        ];
    }

    /**
     * Metadata đối tượng áp dụng để view render select
     */
    private function getServiceAppliesToOptions(): array
    {
        return [
            ['value' => 'room', 'label' => 'Theo phòng', 'tooltip' => 'Một dịch vụ được gán cho cả phòng. Có thể phát sinh số lượng riêng theo từng phòng.'],
            ['value' => 'person', 'label' => 'Theo người', 'tooltip' => 'Mỗi cư dân tự đăng ký riêng, không ảnh hưởng người ở cùng phòng.'],
        ];
    }

    private function getAllowedServiceAppliesTo(): array
    {
        return array_column($this->getServiceAppliesToOptions(), 'value');
    }

    // ==========================================
    // AMENITY ICON OPTIONS
    // ==========================================

    private function getAmenityIconOptions(): array
    {
        return [
            ['key' => 'wifi', 'label' => 'Wifi'], ['key' => 'security', 'label' => 'An ninh'],
            ['key' => 'local_parking', 'label' => 'Bãi xe'], ['key' => 'local_laundry_service', 'label' => 'Giặt sấy'],
            ['key' => 'ac_unit', 'label' => 'Điều hòa'], ['key' => 'kitchen', 'label' => 'Bếp chung'],
            ['key' => 'water_heater', 'label' => 'Nóng lạnh'], ['key' => 'elevator', 'label' => 'Thang máy'],
            ['key' => 'videocam', 'label' => 'Camera'], ['key' => 'fingerprint', 'label' => 'Vân tay'],
            ['key' => 'cleaning_services', 'label' => 'Vệ sinh'], ['key' => 'yard', 'label' => 'Sân phơi'],
            ['key' => 'bolt', 'label' => 'Điện ổn định'], ['key' => 'apartment', 'label' => 'Tiện ích chung'],
        ];
    }

    private function getAllowedAmenityIconKeys(): array
    {
        return array_column($this->getAmenityIconOptions(), 'key');
    }

    // ==========================================
    // NORMALIZE INPUT HELPERS
    // ==========================================

    private function normalizeAmenityInput(array $source): array
    {
        return [
            'icon' => trim((string)($source['icon'] ?? 'apartment')),
            'title' => trim((string)($source['title'] ?? '')),
            'description' => trim((string)($source['description'] ?? '')),
            'sort_order' => (int)($source['sort_order'] ?? 0),
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }

    private function normalizeServiceInput(array $source): array
    {
        return [
            'name' => trim((string)($source['name'] ?? '')),
            'price' => (float)($source['price'] ?? 0),
            'unit' => trim((string)($source['unit'] ?? 'tháng')),
            'icon' => trim((string)($source['icon'] ?? 'settings')),
            'description' => trim((string)($source['description'] ?? '')),
            'billing_mode' => trim((string)($source['billing_mode'] ?? 'meter')),
            'applies_to' => trim((string)($source['applies_to'] ?? 'room')),
            'is_required' => !empty($source['is_required']) ? 1 : 0,
            'is_active' => !empty($source['is_active']) ? 1 : 0,
        ];
    }

}