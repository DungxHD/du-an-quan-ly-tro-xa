<?php
// [DEV-QWEN-A][REFACTOR][NHOM-6] Tach tu AdminController.php. KHONG require model - autoloader index.php lo.

trait AdminHelperTrait
{
/**
     * Đối chiếu URL ảnh upload về đường dẫn file cục bộ (chặn path traversal).
     */
    private function resolveUploadLocalPath($url)
    {
        $prefix = BASE_URL . '.uploads/';
        if (!is_string($url) || strpos($url, $prefix) !== 0) {
            return null;
        }
        $rel = substr($url, strlen($prefix));
        if (strpos($rel, '..') !== false) {
            return null;
        }
        $local = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($local) ? $local : null;
    }
/**
     * Khu mới tạo: chuyển ảnh từ image_khu_new sang image_khu_{id}, cập nhật DB,
     * rồi dọn ảnh nháp còn sót trong image_khu_new.
     */
    private function finalizeNewAreaImage($areaId, $imageUrl)
    {
        $local = $this->resolveUploadLocalPath($imageUrl);
        if ($local === null || basename(dirname($local)) !== 'image_khu_new') {
            return $imageUrl;
        }
        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_khu_' . (int)$areaId;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        $fileName = basename($local);
        $dest = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!@rename($local, $dest)) {
            return $imageUrl;
        }
        $newUrl = BASE_URL . '.uploads/image_khu_' . (int)$areaId . '/' . $fileName;
        Database::update('areas', ['image' => $newUrl], 'id = :id', ['id' => (int)$areaId]);

        $tmpDir = dirname($local);
        foreach (scandir($tmpDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $tmpDir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        return $newUrl;
    }
/** Dời ảnh upload tạm ở image_phong_new về thư mục riêng của phòng. */
    private function relocateRoomImageUrl($url, $roomId)
    {
        $prefix = BASE_URL . '.uploads/image_phong_new/';
        if (!is_string($url) || strpos($url, $prefix) !== 0) {
            return $url;
        }
        $fileName = basename((string)(parse_url($url, PHP_URL_PATH) ?: $url));
        $src = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_new' . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($src)) {
            return $url;
        }
        $destDir = BASE_PATH . '.uploads' . DIRECTORY_SEPARATOR . 'image_phong_' . (int)$roomId;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        if (!@rename($src, $destDir . DIRECTORY_SEPARATOR . $fileName)) {
            return $url;
        }
        return BASE_URL . '.uploads/image_phong_' . (int)$roomId . '/' . $fileName;
    }
/**
     * Khai báo schema field cho dashboard admin để view render động, không hard-code rải rác.
     */
    private function buildAdminSettingSections()
    {
        return [
            [
                'id' => 'brand',
                'title' => 'Thương hiệu',
                'icon' => 'storefront',
                'description' => 'Tên website, slogan và mô tả thương hiệu dùng chung cho header, footer và meta công khai.',
                'fields' => [
                    [
                        'key' => 'site_name',
                        'label' => 'Tên website',
                        'type' => 'text',
                        'group' => 'brand',
                        'default' => 'NhaTroA',
                        'placeholder' => 'Ví dụ: Nhà trọ Xanh',
                        'tooltip' => 'Tên hiển thị trên tab trình duyệt, header và panel quản trị.',
                    ],
                    [
                        'key' => 'site_slogan',
                        'label' => 'Slogan',
                        'type' => 'text',
                        'group' => 'brand',
                        'default' => 'Trang chính thức của khu trọ',
                        'placeholder' => 'Một câu ngắn tạo cảm giác tin cậy',
                        'tooltip' => 'Câu giới thiệu ngắn xuất hiện nổi bật ở phần đầu trang.',
                    ],
                    [
                        'key' => 'site_description',
                        'label' => 'Mô tả website',
                        'type' => 'textarea',
                        'group' => 'brand',
                        'default' => 'Xem phòng trống, giá thuê và tiện ích rõ ràng trước khi liên hệ với chủ trọ.',
                        'rows' => 3,
                        'placeholder' => 'Mô tả ngắn về khu trọ và trải nghiệm người thuê',
                        'tooltip' => 'Dùng cho phần giới thiệu và meta description cơ bản.',
                    ],
                ],
            ],
            [
                'id' => 'hero',
                'title' => 'Hero Banner',
                'icon' => 'image',
                'description' => 'Điều khiển tiêu đề lớn, ảnh đầu trang và đoạn mô tả chính của landing page.',
                'fields' => [
                    [
                        'key' => 'hero_headline_1',
                        'label' => 'Headline dòng 1',
                        'type' => 'text',
                        'group' => 'hero',
                        'default' => 'Xem Phòng Rõ',
                        'placeholder' => 'Ví dụ: Không Gian Sống',
                        'tooltip' => 'Dòng tiêu đề đầu tiên hiển thị cỡ lớn trên ảnh hero.',
                    ],
                    [
                        'key' => 'hero_headline_2',
                        'label' => 'Headline dòng 2',
                        'type' => 'text',
                        'group' => 'hero',
                        'default' => 'Chọn Chỗ Ở Dễ',
                        'placeholder' => 'Ví dụ: Chuẩn Mực',
                        'tooltip' => 'Dòng nhấn mạnh bằng gradient để tăng điểm nhấn thị giác.',
                    ],
                    [
                        'key' => 'hero_subheadline',
                        'label' => 'Mô tả hero',
                        'type' => 'textarea',
                        'group' => 'hero',
                        'default' => 'Xem phòng trống, tiện ích và mức giá rõ ràng trước khi liên hệ.',
                        'rows' => 3,
                        'placeholder' => 'Đoạn mô tả ngắn ngay dưới headline',
                        'tooltip' => 'Nội dung mô tả chính ngay dưới tiêu đề lớn của trang chủ.',
                    ],
                    [
                        'key' => 'hero_image',
                        'label' => 'Ảnh hero (Trang chủ & Giới thiệu)',
                        'type' => 'url',
                        'group' => 'hero',
                        'default' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600',
                        'placeholder' => 'https://...',
                        'tooltip' => 'URL ảnh nền đầu trang chủ và ảnh chính trang Giới thiệu. Nên dùng ảnh ngang rõ ánh sáng và không bị vỡ.',
                    ],
                ],
            ],
            [
                'id' => 'contact',
                'title' => 'Liên hệ',
                'icon' => 'contact_phone',
                'description' => 'Thông tin liên hệ hiển thị ở footer, chi tiết phòng và các điểm CTA công khai.',
                'fields' => [
                    [
                        'key' => 'contact_address',
                        'label' => 'Địa chỉ',
                        'type' => 'textarea',
                        'group' => 'contact',
                        'default' => 'Khu Công nghệ cao, TP. Thủ Đức, TP.HCM',
                        'rows' => 3,
                        'placeholder' => 'Địa chỉ đầy đủ của khu trọ',
                        'tooltip' => 'Thông tin địa chỉ hiển thị ở footer và các khu vực liên hệ.',
                    ],
                    [
                        'key' => 'contact_phone',
                        'label' => 'Số điện thoại',
                        'type' => 'tel',
                        'group' => 'contact',
                        'default' => '0901 234 567',
                        'placeholder' => '0901 234 567',
                        'tooltip' => 'Số hotline dùng cho nút gọi nhanh và hỗ trợ cư dân.',
                    ],
                    [
                        'key' => 'contact_email',
                        'label' => 'Email',
                        'type' => 'email',
                        'group' => 'contact',
                        'default' => 'admin@nhatroa.vn',
                        'placeholder' => 'admin@nhatroa.vn',
                        'tooltip' => 'Email hiển thị công khai cho người xem cần liên hệ chi tiết.',
                    ],
                    [
                        'key' => 'contact_zalo',
                        'label' => 'Zalo',
                        'type' => 'text',
                        'group' => 'contact',
                        'default' => '0901234567',
                        'placeholder' => 'Số điện thoại Zalo',
                        'tooltip' => 'Giúp đồng bộ CTA qua Zalo nếu sau này cần mở rộng.',
                    ],
                ],
            ],
            [
                'id' => 'stats',
                'title' => 'Chỉ số nổi bật',
                'icon' => 'bar_chart',
                'description' => 'Nhóm giá trị hiển thị nổi bật trên landing page để truyền tải điểm mạnh của khu trọ.',
                'fields' => [
                    [
                        'key' => 'stat_1_label',
                        'label' => 'Nhãn chỉ số 1',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Phòng đang mở xem',
                        'placeholder' => 'Ví dụ: Phòng đang trống',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số đầu tiên ở hero.',
                    ],
                    [
                        'key' => 'stat_1_value',
                        'label' => 'Giá trị chỉ số 1',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '18+',
                        'placeholder' => 'Ví dụ: 18+',
                        'tooltip' => 'Có thể nhập số, text ngắn hoặc dạng 24/7, 20+.',
                    ],
                    [
                        'key' => 'stat_2_label',
                        'label' => 'Nhãn chỉ số 2',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Khu vận hành ổn định',
                        'placeholder' => 'Ví dụ: Khu đang vận hành',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ hai.',
                    ],
                    [
                        'key' => 'stat_2_value',
                        'label' => 'Giá trị chỉ số 2',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '3 khu',
                        'placeholder' => 'Ví dụ: 3 khu',
                        'tooltip' => 'Giá trị hiển thị của chỉ số thứ hai.',
                    ],
                    [
                        'key' => 'stat_3_label',
                        'label' => 'Nhãn chỉ số 3',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => 'Hỗ trợ cư dân',
                        'placeholder' => 'Ví dụ: Hỗ trợ 24/7',
                        'tooltip' => 'Mô tả ý nghĩa cho chỉ số thứ ba.',
                    ],
                    [
                        'key' => 'stat_3_value',
                        'label' => 'Giá trị chỉ số 3',
                        'type' => 'text',
                        'group' => 'stats',
                        'default' => '24/7',
                        'placeholder' => 'Ví dụ: 24/7',
                        'tooltip' => 'Giá trị hiển thị của chỉ số thứ ba.',
                    ],
                ],
            ],
            [
                'id' => 'moderation',
                'title' => 'Kiểm duyệt đánh giá',
                'icon' => 'shield_lock',
                'description' => 'Thiết lập điều kiện gửi/sửa đánh giá và bật tắt kiểm duyệt nội dung bằng Gemini API.',
                'fields' => [
                    [
                        'key' => 'enable_comment_moderation',
                        'label' => 'Bật kiểm duyệt đánh giá',
                        'type' => 'toggle',
                        'group' => 'moderation',
                        'default' => '1',
                        'tooltip' => 'Tắt để bỏ qua lọc từ cấm và các bước kiểm duyệt nội dung khi tenant gửi đánh giá.',
                    ],
                    [
                        'key' => 'min_days_to_review',
                        'label' => 'Số ngày ở tối thiểu',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '15',
                        'min' => 0,
                        'placeholder' => '15',
                        'tooltip' => 'Người thuê phải ở tối thiểu bao nhiêu ngày mới được đánh giá.',
                    ],
                    [
                        'key' => 'comment_edit_hours',
                        'label' => 'Thời gian sửa/xóa',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '24',
                        'min' => 1,
                        'placeholder' => '24',
                        'suffix' => 'giờ',
                        'tooltip' => 'Khoảng thời gian sau khi gửi mà người thuê còn được sửa hoặc xóa đánh giá.',
                    ],
                    [
                        'key' => 'max_comment_attempts',
                        'label' => 'Số lần vi phạm tối đa',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '3',
                        'min' => 1,
                        'placeholder' => '3',
                        'tooltip' => 'Sau số lần vi phạm này, hệ thống sẽ khóa thao tác gửi đánh giá trong thời gian cấu hình.',
                    ],
                    [
                        'key' => 'comment_lock_hours',
                        'label' => 'Thời gian khóa đánh giá',
                        'type' => 'number',
                        'group' => 'moderation',
                        'default' => '24',
                        'min' => 1,
                        'placeholder' => '24',
                        'suffix' => 'giờ',
                        'tooltip' => 'Khoảng thời gian khóa sau khi người dùng vượt ngưỡng vi phạm.',
                    ],
                    [
                        'key' => 'enable_gemini_moderation',
                        'label' => 'Bật Gemini Moderation',
                        'type' => 'toggle',
                        'group' => 'moderation',
                        'default' => '0',
                        'tooltip' => 'Bật để hệ thống gọi Gemini API chấm điểm độc hại cho bình luận.',
                    ],
                    [
                        'key' => 'gemini_api_key',
                        'label' => 'Gemini API Key',
                        'type' => 'password',
                        'group' => 'moderation',
                        'default' => '',
                        'placeholder' => 'Nhập API key mới nếu cần thay đổi',
                        'tooltip' => 'Giữ trống để giữ nguyên khóa cũ. Tích ô xóa nếu muốn reset.',
                    ],
                    [
                        'key' => 'toxicity_threshold',
                        'label' => 'Ngưỡng độc hại',
                        'type' => 'decimal',
                        'group' => 'moderation',
                        'default' => '0.70',
                        'min' => 0,
                        'max' => 1,
                        'step' => '0.01',
                        'placeholder' => '0.70',
                        'tooltip' => 'Giá trị từ 0.00 đến 1.00. Càng thấp thì kiểm duyệt càng chặt.',
                    ],
                ],
            ],
            [
                'id' => 'payment',
                'title' => 'Thanh toán QR',
                'icon' => 'qr_code_2',
                'description' => 'Thông tin tài khoản ngân hàng dùng để tạo mã QR chuyển tiền khi duyệt yêu cầu thuê phòng.',
                'fields' => [
                    [
                        'key' => 'bank_name',
                        'label' => 'Tên ngân hàng',
                        'type' => 'text',
                        'group' => 'payment',
                        'default' => 'Vietcombank',
                        'placeholder' => 'Ví dụ: Vietcombank',
                        'tooltip' => 'Tên ngân hàng nhận tiền thuê phòng.',
                    ],
                    [
                        'key' => 'bank_account_number',
                        'label' => 'Số tài khoản',
                        'type' => 'text',
                        'group' => 'payment',
                        'default' => '',
                        'placeholder' => 'Ví dụ: 0011001234567',
                        'tooltip' => 'Số tài khoản ngân hàng nhận tiền.',
                    ],
                    [
                        'key' => 'bank_account_holder',
                        'label' => 'Chủ tài khoản',
                        'type' => 'text',
                        'group' => 'payment',
                        'default' => '',
                        'placeholder' => 'Tên người nhận trên tài khoản',
                        'tooltip' => 'Tên chủ tài khoản ngân hàng.',
                    ],
                ],
            ],
        ];
    }
/**
     * Trả whitelist cách tính để validate request từ form admin.
     */
    private function getAllowedServiceBillingModes()
    {
        return array_column($this->getServiceBillingModeOptions(), 'value');
    }

}
