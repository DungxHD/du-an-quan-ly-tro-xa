-- =====================================================================
-- NHATROA - DATABASE HOÀN CHỈNH TỐI ƯU (bản cuối cùng sau phản biện)
-- Tên database: manage | MySQL 8.4
--
-- 19 BẢNG:
--   Phần 1 (Phòng trọ):   areas, floors, rooms
--   Phần 2 (Người dùng):  users
--   Phần 3 (Dịch vụ/TT):  services, room_services, user_services,
--                         meter_readings, contracts, payments,
--                         payment_items, price_changes, notifications
--   Phần 4 (Đánh giá):    settings, amenities, banned_words, comments,
--                         comment_moderation, comment_reports
--
-- CÁC QUYẾT ĐỊNH ĐÃ CHỐT (phản ánh trong bản này):
--   • rooms: BỎ area_id, floor_id NOT NULL (truy xuất khu qua JOIN floors)
--   • Mỗi khu buộc có ≥1 tầng (khu nhà trệt → "Tầng trệt", floor_number=0)
--   • users: BỎ status + BỎ move_in/out (SSOT: chuyển sang contracts)
--   • users: GIỮ 5 cột thông tin cá nhân (mã hóa AES) + room_id (con trỏ nhanh)
--   • contracts: thay room_history, mở rộng (rent_price, cọc, initial index)
--   • payment_items: snapshot hóa đơn (audit trail)
--   • banned_words: GIỮ is_active để admin bật/tắt mềm không cần xóa dữ liệu
--   • settings: THÊM enable_comment_moderation (công tắc tổng cho admin)
-- =====================================================================
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

DROP DATABASE IF EXISTS `manage`;
CREATE DATABASE `manage` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `manage`;

-- =====================================================================
-- PHẦN 1: PHÒNG TRỌ (Khu → Tầng → Phòng)
-- =====================================================================

-- 1. AREAS (Khu)
CREATE TABLE `areas` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Mã khu',
  `name`         VARCHAR(150) NOT NULL COMMENT 'Tên khu (VD: Khu A - Sinh viên)',
  `address`      VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ khu',
  `description`  TEXT COMMENT 'Mô tả khu',
  `image`        VARCHAR(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh khu',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. FLOORS (Tầng) — mỗi khu buộc có ≥1 tầng (khu nhà trệt tạo "Tầng trệt", floor_number=0)
CREATE TABLE `floors` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Mã tầng',
  `area_id`       INT UNSIGNED NOT NULL COMMENT 'FK → areas: tầng thuộc khu nào',
  `name`          VARCHAR(100) NOT NULL COMMENT 'Tên tầng (VD: Tầng 1, Tầng trệt)',
  `floor_number`  INT NOT NULL DEFAULT 1 COMMENT 'Số tầng (0 = tầng trệt)',
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_floor_area` (`area_id`),
  CONSTRAINT `fk_floor_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ROOMS (Phòng) — BỎ area_id, floor_id NOT NULL (DB tự chặn lệch khu/tầng)
CREATE TABLE `rooms` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Mã phòng',
  `floor_id`       INT UNSIGNED NOT NULL COMMENT 'FK → floors: phòng thuộc tầng nào (NOT NULL)',
  `name`           VARCHAR(100) NOT NULL COMMENT 'Tên phòng (VD: Phòng A1, B2)',
  `price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá thuê/tháng (VNĐ)',
  `area`           DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Diện tích (m²)',
  `max_occupancy`  TINYINT DEFAULT 2 COMMENT 'Số người ở tối đa',
  `description`    TEXT COMMENT 'Mô tả phòng',
  `thumbnail`      VARCHAR(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh phòng',
  `status`         ENUM('available','rented','maintenance') DEFAULT 'available',
  `views`          INT DEFAULT 0 COMMENT 'Lượt xem phòng',
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_floor` (`floor_id`),
  KEY `idx_room_status` (`status`),
  CONSTRAINT `fk_room_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE
  -- CODE MỞ: ALTER TABLE rooms ADD COLUMN area_id INT UNSIGNED GENERATED ALWAYS AS (...);
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PHẦN 2: NGƯỜI DÙNG
-- =====================================================================

-- 4. USERS — BỎ status, BỎ move_in/out (sang contracts), GIỮ thông tin cá nhân (AES) + room_id
CREATE TABLE `users` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`              VARCHAR(100) NOT NULL COMMENT 'Họ và tên',
  `email`                  VARCHAR(150) NOT NULL COMMENT 'Email đăng nhập (duy nhất)',
  `phone`                  VARCHAR(20) DEFAULT NULL COMMENT 'Số điện thoại',
  `password`               VARCHAR(255) NOT NULL COMMENT 'HASH bcrypt (KHÔNG phải AES)',
  `avatar`                 VARCHAR(255) DEFAULT 'default.png' COMMENT 'Ảnh đại diện',
  `role`                   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Admin, 0=Tenant',
  `room_id`                INT UNSIGNED DEFAULT NULL COMMENT 'Phòng đang ở (con trỏ nhanh, NULL nếu chưa gán)',
  -- THÔNG TIN CÁ NHÂN (MÃ HÓA AES ở tầng PHP, DB lưu chuỗi mã hóa)
  `date_of_birth`          VARCHAR(255) DEFAULT NULL COMMENT 'Ngày sinh (mã hóa AES)',
  `permanent_address`      VARCHAR(255) DEFAULT NULL COMMENT 'Nơi ĐKHK thường trú (mã hóa AES)',
  `identity_number`        VARCHAR(255) DEFAULT NULL COMMENT 'Số CMND/CCCD (mã hóa AES)',
  `identity_issue_date`    VARCHAR(255) DEFAULT NULL COMMENT 'Ngày cấp CMND (mã hóa AES)',
  `identity_issue_place`   VARCHAR(255) DEFAULT NULL COMMENT 'Nơi cấp CMND (mã hóa AES)',
  `created_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `fk_user_room` (`room_id`),
  KEY `idx_user_role` (`role`),
  CONSTRAINT `fk_user_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PHẦN 3: DỊCH VỤ & THANH TOÁN
-- =====================================================================

-- 5. SERVICES
CREATE TABLE `services` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Mã dịch vụ',
  `name`           VARCHAR(100) NOT NULL COMMENT 'Tên dịch vụ',
  `price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Đơn giá hiện hành',
  `unit`           VARCHAR(20) DEFAULT 'tháng' COMMENT 'Đơn vị: tháng/người/xe/kwh/m³',
  `icon`           VARCHAR(50) DEFAULT 'settings' COMMENT 'Icon Material',
  `description`    TEXT COMMENT 'Mô tả dịch vụ',
  `is_required`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = bắt buộc (điện/nước), KHÔNG xóa được',
  `billing_mode`   ENUM('fixed','meter','per_person','per_unit') NOT NULL DEFAULT 'fixed'
                   COMMENT 'Cách tính: fixed=cố định | meter=chỉ số | per_person=theo người | per_unit=theo đơn vị',
  `applies_to`     ENUM('room','person') NOT NULL DEFAULT 'room'
                   COMMENT 'Áp dụng cho phòng (room_services) hay người (user_services)',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = đang kinh doanh',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ROOM_SERVICES (Dịch vụ THEO PHÒNG — N-N)
CREATE TABLE `room_services` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`        INT UNSIGNED NOT NULL COMMENT 'FK → rooms',
  `service_id`     INT UNSIGNED NOT NULL COMMENT 'FK → services (applies_to=room)',
  `quantity`       INT NOT NULL DEFAULT 1 COMMENT 'Số lượng',
  `registered_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_service` (`room_id`, `service_id`),
  KEY `fk_rs_service` (`service_id`),
  CONSTRAINT `fk_rs_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. USER_SERVICES (Dịch vụ CÁ NHÂN theo người)
CREATE TABLE `user_services` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL COMMENT 'FK → users (người đăng ký)',
  `service_id`     INT UNSIGNED NOT NULL COMMENT 'FK → services (applies_to=person)',
  `quantity`       INT NOT NULL DEFAULT 1 COMMENT 'Số lượng (VD: 2 xe điện)',
  `registered_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_service` (`user_id`, `service_id`),
  KEY `fk_us_service` (`service_id`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_us_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. METER_READINGS (Chỉ số điện/nước theo tháng)
CREATE TABLE `meter_readings` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED NOT NULL COMMENT 'FK → rooms',
  `service_id`   INT UNSIGNED NOT NULL COMMENT 'FK → services (điện/nước)',
  `month`        TINYINT NOT NULL COMMENT 'Tháng (1-12)',
  `year`         SMALLINT NOT NULL COMMENT 'Năm',
  `old_index`    DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Chỉ số cũ',
  `new_index`    DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Chỉ số mới',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meter_period` (`room_id`, `service_id`, `month`, `year`),
  KEY `fk_mr_service` (`service_id`),
  CONSTRAINT `fk_mr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. CONTRACTS (Hợp đồng thuê — SSOT cho move_in/out + snapshot giá + chỉ số ban đầu)
--    1 người = 1 hợp đồng; nhiều người cùng phòng = nhiều hợp đồng
CREATE TABLE `contracts` (
  `id`                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                    INT UNSIGNED NOT NULL COMMENT 'FK → users: người thuê',
  `room_id`                    INT UNSIGNED NOT NULL COMMENT 'FK → rooms: phòng thuê',
  `move_in_date`               DATE NOT NULL COMMENT 'Ngày vào ở',
  `move_out_date`              DATE DEFAULT NULL COMMENT 'Ngày chuyển đi (NULL = đang ở)',
  `rent_price`                 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Phần giá thuê mỗi người chịu (snapshot lúc ký)',
  `deposit_amount`             DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tiền cọc (hoàn trả khi chuyển đi)',
  `initial_electricity_index`  DECIMAL(10,2) DEFAULT NULL COMMENT 'Chỉ số điện công tơ lúc vào ở (mốc tính tháng đầu)',
  `initial_water_index`        DECIMAL(10,2) DEFAULT NULL COMMENT 'Chỉ số nước công tơ lúc vào ở (mốc tính tháng đầu)',
  `status`                     ENUM('active','terminated') NOT NULL DEFAULT 'active' COMMENT 'active=đang thuê | terminated=đã kết thúc',
  `contract_date`              DATE DEFAULT NULL COMMENT 'Ngày ký hợp đồng',
  `created_at`                 TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contract_user` (`user_id`),
  KEY `idx_contract_room` (`room_id`, `status`),
  KEY `idx_contract_active` (`user_id`, `move_out_date`),
  CONSTRAINT `fk_contract_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contract_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
  -- CODE MỞ: ALTER TABLE contracts ADD COLUMN terms TEXT COMMENT 'Điều khoản hợp đồng';
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PAYMENTS (Hóa đơn tổng theo tháng)
CREATE TABLE `payments` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED NOT NULL COMMENT 'FK → rooms (phòng cần trả)',
  `contract_id`  INT UNSIGNED DEFAULT NULL COMMENT 'FK → contracts (hợp đồng liên kết, tùy chọn)',
  `user_id`      INT UNSIGNED DEFAULT NULL COMMENT 'FK → users (ai đã trả, NULL nếu chưa)',
  `month`        TINYINT NOT NULL COMMENT 'Tháng (1-12)',
  `year`         SMALLINT NOT NULL COMMENT 'Năm',
  `amount`       DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền (= tổng payment_items)',
  `status`       ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'Trạng thái thanh toán',
  `paid_at`      TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm thanh toán',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_period` (`room_id`, `month`, `year`),
  KEY `fk_pay_user` (`user_id`),
  KEY `fk_pay_contract` (`contract_id`),
  CONSTRAINT `fk_pay_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pay_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PAYMENT_ITEMS (Chi tiết hóa đơn — SNAPSHOT giá + cách tính tại thời điểm chốt)
--     Audit trail: hóa đơn cũ không đổi khi giá/cách tính thay đổi
CREATE TABLE `payment_items` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id`    INT UNSIGNED NOT NULL COMMENT 'FK → payments',
  `service_id`    INT UNSIGNED DEFAULT NULL COMMENT 'FK → services (NULL nếu là tiền phòng)',
  `item_name`     VARCHAR(100) NOT NULL COMMENT 'Snapshot tên (VD: Tiền điện, Tiền phòng)',
  `unit_price`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Snapshot đơn giá tại thời điểm chốt',
  `quantity`      DECIMAL(10,2) NOT NULL DEFAULT 1 COMMENT 'Số lượng (số kwh / số người / 1...)',
  `amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Thành tiền = unit_price × quantity',
  `billing_mode`  ENUM('fixed','meter','per_person','per_unit') DEFAULT NULL COMMENT 'Snapshot cách tính tại thời điểm chốt',
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pi_payment` (`payment_id`),
  KEY `fk_pi_service` (`service_id`),
  CONSTRAINT `fk_pi_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PRICE_CHANGES (Lịch sử đổi giá — áp dụng từ THÁNG SAU)
CREATE TABLE `price_changes` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id`       INT UNSIGNED NOT NULL COMMENT 'FK → services',
  `old_price`        DECIMAL(10,2) NOT NULL COMMENT 'Giá cũ',
  `new_price`        DECIMAL(10,2) NOT NULL COMMENT 'Giá mới',
  `effective_month`  TINYINT NOT NULL COMMENT 'Tháng bắt đầu áp dụng giá mới',
  `effective_year`   SMALLINT NOT NULL COMMENT 'Năm bắt đầu áp dụng',
  `created_by`       INT UNSIGNED DEFAULT NULL COMMENT 'FK → users (admin thực hiện)',
  `created_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pc_service` (`service_id`),
  KEY `fk_pc_user` (`created_by`),
  CONSTRAINT `fk_pc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. NOTIFICATIONS (Thông báo cho người thuê) — user_id=NULL → gửi tất cả
CREATE TABLE `notifications` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED DEFAULT NULL COMMENT 'FK → users (NULL = tất cả)',
  `title`        VARCHAR(150) NOT NULL COMMENT 'Tiêu đề thông báo',
  `content`      TEXT COMMENT 'Nội dung chi tiết',
  `type`         ENUM('price_change','payment','general') NOT NULL DEFAULT 'general' COMMENT 'Loại thông báo',
  `is_read`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = chưa đọc',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_noti_user` (`user_id`),
  CONSTRAINT `fk_noti_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13.1 NOTIFICATION_READS (Trạng thái đọc riêng theo tenant cho cả broadcast và thông báo cá nhân)
--      Bảng này vá hạn chế của `notifications.is_read` khi `user_id = NULL` vì broadcast cần theo dõi đã đọc riêng từng tenant.
CREATE TABLE `notification_reads` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` INT UNSIGNED NOT NULL COMMENT 'FK → notifications',
  `user_id`         INT UNSIGNED NOT NULL COMMENT 'FK → users (tenant đã đọc)',
  `read_at`         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tenant đọc thông báo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_user` (`notification_id`, `user_id`),
  KEY `fk_nr_user` (`user_id`),
  CONSTRAINT `fk_nr_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PHẦN 4: ĐÁNH GIÁ + COMMENT + SETTINGS + AMENITIES
-- =====================================================================

-- 14. SETTINGS (Cấu hình động)
CREATE TABLE `settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `setting_group` VARCHAR(50) DEFAULT 'general',
  `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. AMENITIES (Tiện ích trang chủ)
CREATE TABLE `amenities` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon`         VARCHAR(50) NOT NULL,
  `title`        VARCHAR(100) NOT NULL,
  `description`  VARCHAR(255) DEFAULT NULL,
  `sort_order`   INT DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
  -- CODE MỞ: ALTER TABLE amenities ADD COLUMN area_id INT UNSIGNED DEFAULT NULL;
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. BANNED_WORDS (Danh sách từ cấm — admin quản lý thêm/sửa/xóa/bật-tắt)
CREATE TABLE `banned_words` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `word`         VARCHAR(100) NOT NULL COMMENT 'Từ/cụm từ cấm (ĐÃ CHUẨN HÓA)',
  `type`         ENUM('word','phrase','abbreviation') NOT NULL DEFAULT 'word'
                 COMMENT 'word=từ đơn | phrase=cụm từ | abbreviation=viết tắt',
  `replacement`  VARCHAR(20) NOT NULL DEFAULT '***' COMMENT 'Chuỗi thay thế khi mã hóa',
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=đang bật bộ lọc | 0=tắt mềm',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_word` (`word`)
  -- CODE MỞ: ALTER TABLE banned_words ADD COLUMN category VARCHAR(50) DEFAULT 'profanity';
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. COMMENTS (Đánh giá — với kiểm duyệt nội dung)
--     LOGIC HIỂN THỊ: công khai WHERE status=1 AND is_spam=0; chủ comment thấy của mình; admin thấy tất cả
--     LOGIC SẮP XẾP: ORDER BY rating DESC, is_spam ASC, toxicity_score ASC, created_at DESC
CREATE TABLE `comments` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`         INT UNSIGNED NOT NULL COMMENT 'FK → rooms: phòng được đánh giá',
  `user_id`         INT UNSIGNED NOT NULL COMMENT 'FK → users: người đánh giá',
  `content`         TEXT COMMENT 'Nội dung ĐÃ MÃ HÓA (từ phản cảm thay bằng ***). NULL nếu chỉ chấm sao',
  `rating`          TINYINT NOT NULL DEFAULT 5 COMMENT 'Đánh giá sao (1-5)',
  `toxicity_score`  DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT 'Điểm độc hại từ Gemini API (0.00-1.00)',
  `is_spam`         TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = sau mã hóa chỉ còn *** (ẩn với công khai)',
  `flagged_words`   TEXT COMMENT 'Danh sách từ bị mã hóa (JSON array)',
  `status`          TINYINT NOT NULL DEFAULT 1 COMMENT '1=hiển thị, 0=ẩn (admin kiểm soát)',
  `edited_at`       TIMESTAMP NULL DEFAULT NULL COMMENT 'Lần sửa cuối (NULL = chưa sửa)',
  `created_at`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Lúc gửi (để tính 24h sửa/xóa)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_room` (`user_id`, `room_id`) COMMENT 'Mỗi người 1 đánh giá/phòng',
  KEY `idx_room_rating` (`room_id`, `rating`),
  KEY `idx_room_status` (`room_id`, `status`, `is_spam`),
  KEY `fk_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  -- CODE MỞ: thêm sentiment, admin_reviewed, parent_comment_id (reply)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. COMMENT_MODERATION (Chống spam — lưu DB, chống bypass)
CREATE TABLE `comment_moderation` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL COMMENT 'FK → users: user bị track',
  `attempt_count`    INT NOT NULL DEFAULT 0 COMMENT 'Số lần all_bad liên tiếp (đủ 3 → khóa)',
  `locked_until`     TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm hết khóa (NULL = không khóa)',
  `last_attempt_at`  TIMESTAMP NULL DEFAULT NULL COMMENT 'Lần vi phạm cuối',
  `created_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`) COMMENT 'Mỗi user 1 bản ghi',
  CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  -- CODE MỞ: thêm total_violations, is_permanently_banned
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. COMMENT_REPORTS (Báo cáo comment xấu — cơ chế xử lý sự cố bị động)
CREATE TABLE `comment_reports` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id`   INT UNSIGNED NOT NULL COMMENT 'FK → comments: comment bị báo cáo',
  `user_id`      INT UNSIGNED NOT NULL COMMENT 'FK → users: người báo cáo',
  `reason`       VARCHAR(255) DEFAULT NULL COMMENT 'Lý do báo cáo',
  `status`       ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending'
                 COMMENT 'pending=chờ xử lý | resolved=đã xử lý | dismissed=bác bỏ',
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comment_user` (`comment_id`, `user_id`) COMMENT 'Mỗi người báo cáo 1 comment 1 lần',
  KEY `idx_status` (`status`),
  KEY `fk_cr_user` (`user_id`),
  CONSTRAINT `fk_cr_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DỮ LIỆU MẪU (nhất quán giữa các phần)
-- =====================================================================

-- ===== PHẦN 1: PHÒNG TRỌ =====
-- Khu A: 2 tầng; Khu B: 1 tầng ("Tầng trệt" — khu nhà trệt giờ buộc có tầng)
INSERT INTO `areas` (`id`, `name`, `address`, `description`, `image`) VALUES
(1, 'Khu A - Sinh viên', '123 Đường ABC, Quận 9', 'Khu gần FPT, an ninh tốt, có 2 tầng.', 'uploads/areas/khu-a.jpg'),
(2, 'Khu B - Tiết kiệm', '125 Đường ABC, Quận 9', 'Khu nhà trệt, giá mềm, phòng nằm ngang.', 'uploads/areas/khu-b.jpg');

INSERT INTO `floors` (`id`, `area_id`, `name`, `floor_number`) VALUES
(1, 1, 'Tầng 1', 1),
(2, 1, 'Tầng 2', 2),
(3, 2, 'Tầng trệt', 0);  -- Khu B (nhà trệt) có 1 tầng mặc định

-- Phòng: floor_id NOT NULL (Khu B dùng "Tầng trệt" id=3), KHÔNG có area_id
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `price`, `area`, `max_occupancy`, `description`, `thumbnail`, `status`, `views`) VALUES
(1, 1, 'Phòng A1', 3500000.00, 25.00, 2, 'Phòng có ban công, đầy đủ nội thất.', 'uploads/rooms/a1.jpg', 'rented', 150),
(2, 1, 'Phòng A2', 3200000.00, 22.00, 2, 'Phòng thoáng mát, cửa sổ lớn.', 'uploads/rooms/a2.jpg', 'available', 120),
(3, 2, 'Phòng A3', 4000000.00, 28.00, 3, 'Phòng rộng tầng 2, view công viên.', 'uploads/rooms/a3.jpg', 'available', 95),
(4, 3, 'Phòng B1', 2000000.00, 15.00, 1, 'Phòng giá mềm, tiện nghi cơ bản.', 'uploads/rooms/b1.jpg', 'rented', 88),
(5, 3, 'Phòng B2', 2200000.00, 16.00, 2, 'Phòng có gác lửng.', 'uploads/rooms/b2.jpg', 'available', 60);

-- ===== PHẦN 2: NGƯỜI DÙNG =====
-- Mật khẩu demo: 123456 (hash bcrypt). Thông tin cá nhân PLAIN TEXT để DEMO (code PHP mã hóa AES khi dùng thật)
-- users KHÔNG còn move_in/out (đã chuyển sang contracts)
INSERT INTO `users`
(`id`, `full_name`, `email`, `phone`, `password`, `role`, `room_id`,
 `date_of_birth`, `permanent_address`, `identity_number`, `identity_issue_date`, `identity_issue_place`) VALUES
(1, 'Nguyễn Văn An (Chủ trọ)', 'admin@nhatroa.vn', '0901234567',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL,
 NULL, NULL, NULL, NULL, NULL),
(2, 'Trần Văn Bình', 'tenant1@gmail.com', '0912345678',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1,
 '2003-05-12', 'Thái Nguyên', '012345678901', '2020-01-10', 'Công an Thái Nguyên'),
(3, 'Lê Thị Chi', 'tenant2@gmail.com', '0923456789',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1,
 '2004-08-20', 'Hà Nội', '098765432109', '2021-03-15', 'Công an Hà Nội'),
(4, 'Phạm Đăng Ký Mới', 'tenant3@gmail.com', '0933333333',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NULL,
 NULL, NULL, NULL, NULL, NULL);

-- ===== PHẦN 3: DỊCH VỤ & THANH TOÁN =====
INSERT INTO `services` (`id`, `name`, `price`, `unit`, `icon`, `description`, `is_required`, `billing_mode`, `applies_to`) VALUES
(1, 'Tiền điện',   3500.00,   'kwh',   'bolt',                  'Tính theo chỉ số công tơ',                              1, 'meter',      'room'),
(2, 'Tiền nước',   50000.00,  'người', 'water_drop',            'Mặc định theo người. Admin đổi sang theo khối → billing_mode=meter', 1, 'per_person', 'room'),
(3, 'Tiền rác',    20000.00,  'người', 'delete',                'Phí thu gom rác theo đầu người',                        1, 'per_person', 'room'),
(4, 'Wifi',        50000.00,  'người', 'wifi',                  'Internet tốc độ cao, tính theo đầu người',              0, 'per_person', 'room'),
(5, 'Giữ xe máy',  0.00,      'xe',    'two_wheeler',           'Miễn phí, đăng ký để quản lý số xe',                    0, 'per_unit',   'person'),
(6, 'Sạc xe điện', 100000.00, 'xe',    'electric_bike',         'Phí sạc xe điện theo đầu xe',                           0, 'per_unit',   'person'),
(7, 'Máy giặt',    50000.00,  'người', 'local_laundry_service', 'Máy giặt chung, tính theo đầu người',                   0, 'per_person', 'room');

INSERT INTO `room_services` (`room_id`, `service_id`, `quantity`) VALUES
(1, 4, 1),  -- Phòng 1: Wifi
(1, 7, 1);  -- Phòng 1: Máy giặt

INSERT INTO `user_services` (`user_id`, `service_id`, `quantity`) VALUES
(2, 5, 1),  -- Bình: Giữ xe máy (miễn phí)
(3, 6, 1);  -- Chi: Sạc xe điện (100k/xe)

INSERT INTO `meter_readings` (`room_id`, `service_id`, `month`, `year`, `old_index`, `new_index`) VALUES
(1, 1, 7, 2026, 1000.00, 1100.00),  -- Điện: tiêu thụ 100 kwh
(1, 2, 7, 2026, 50.00,   60.00);    -- Nước: 10 m³ (dùng khi nước chuyển sang meter)

-- CONTRACTS (SSOT move_in/out + snapshot giá + chỉ số ban đầu)
-- Phòng A1 giá 3.5tr, 2 người ở → mỗi người chịu 1.75tr; cọc 1tr → mỗi người 500k
INSERT INTO `contracts`
(`id`, `user_id`, `room_id`, `move_in_date`, `move_out_date`, `rent_price`, `deposit_amount`,
 `initial_electricity_index`, `initial_water_index`, `status`, `contract_date`) VALUES
(1, 2, 1, '2026-01-01', NULL, 1750000.00, 500000.00, 1000.00, 48.00, 'active', '2026-01-01'),  -- Bình
(2, 3, 1, '2026-02-01', NULL, 1750000.00, 500000.00, 1000.00, 48.00, 'active', '2026-02-01');  -- Chi

-- PAYMENTS (hóa đơn tổng tháng 7/2026)
INSERT INTO `payments` (`id`, `room_id`, `contract_id`, `user_id`, `month`, `year`, `amount`, `status`, `paid_at`) VALUES
(1, 1, 1, 2, 7, 2026, 4290000.00, 'paid', '2026-07-05 10:30:00'),  -- Phòng 1: Bình trả → Chi thấy đã trả
(2, 4, NULL, NULL, 7, 2026, 2300000.00, 'unpaid', NULL);            -- Phòng 4: chưa ai trả

-- PAYMENT_ITEMS (chi tiết hóa đơn Phòng 1 tháng 7 — SNAPSHOT giá + cách tính)
-- Tổng: 3.5tr + 350k + 100k + 40k + 100k + 100k + 100k = 4,290,000 ✓
INSERT INTO `payment_items` (`payment_id`, `service_id`, `item_name`, `unit_price`, `quantity`, `amount`, `billing_mode`) VALUES
(1, NULL, 'Tiền phòng',  3500000.00, 1,   3500000.00, 'fixed'),
(1, 1,    'Tiền điện',   3500.00,    100, 350000.00,  'meter'),       -- 100 kwh × 3.500đ
(1, 2,    'Tiền nước',   50000.00,   2,   100000.00,  'per_person'),  -- 2 người × 50.000đ
(1, 3,    'Tiền rác',    20000.00,   2,   40000.00,   'per_person'),  -- 2 người × 20.000đ
(1, 4,    'Wifi',        50000.00,   2,   100000.00,  'per_person'),  -- 2 người × 50.000đ
(1, 7,    'Máy giặt',    50000.00,   2,   100000.00,  'per_person'),  -- 2 người × 50.000đ
(1, 6,    'Sạc xe điện', 100000.00,  1,   100000.00,  'per_unit');    -- 1 xe × 100.000đ (của Chi)

-- PRICE_CHANGES (lịch sử đổi giá, áp dụng từ tháng sau)
INSERT INTO `price_changes` (`service_id`, `old_price`, `new_price`, `effective_month`, `effective_year`, `created_by`) VALUES
(6, 100000.00, 150000.00, 8, 2026, 1),  -- Sạc xe điện: 100k → 150k, từ 08/2026
(1, 3500.00,   4000.00,   9, 2026, 1);  -- Tiền điện: 3.500 → 4.000 đ/kwh, từ 09/2026

-- NOTIFICATIONS (thông báo đổi giá)
INSERT INTO `notifications` (`user_id`, `title`, `content`, `type`, `is_read`) VALUES
(NULL, 'Thay đổi giá dịch vụ', 'Sạc xe điện: 100.000đ → 150.000đ/xe, áp dụng từ tháng 08/2026.', 'price_change', 0),
(NULL, 'Thay đổi giá điện',    'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.',       'price_change', 0);

-- ===== PHẦN 4: ĐÁNH GIÁ + COMMENT + SETTINGS + AMENITIES =====
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
-- Nhóm brand
('site_name', 'Nhà trọ Xanh', 'brand'),
('site_slogan', 'Hệ thống trọ cao cấp #1 tại Quận 9', 'brand'),
('site_description', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'brand'),
-- Nhóm hero
('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600', 'hero'),
('hero_headline_1', 'Không Gian Sống', 'hero'),
('hero_headline_2', 'Chuẩn Mực', 'hero'),
('hero_subheadline', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'hero'),
-- Nhóm contact
('contact_address', '123 Đường ABC, Quận 9, TP.HCM', 'contact'),
('contact_phone', '0901 234 567', 'contact'),
('contact_email', 'contact@nhatroa.vn', 'contact'),
('contact_zalo', '0901234567', 'contact'),
-- Nhóm stats
('stat_1_label', 'Giá cả sinh viên', 'stats'),
('stat_1_value', 'Hợp lý', 'stats'),
('stat_2_label', 'Dịch vụ tiện ích', 'stats'),
('stat_2_value', '20+', 'stats'),
('stat_3_label', 'Hỗ trợ cư dân', 'stats'),
('stat_3_value', '24/7', 'stats'),
-- Nhóm moderation (cấu hình kiểm duyệt đánh giá)
('enable_comment_moderation', '1', 'moderation'),   -- CÔNG TẮC TỔNG (admin thấy): 1=bật bộ lọc, 0=tắt
('min_days_to_review', '15', 'moderation'),         -- Số ngày ở tối thiểu để đánh giá
('comment_edit_hours', '24', 'moderation'),         -- Thời gian cho sửa/xóa (giờ)
('max_comment_attempts', '3', 'moderation'),        -- Số lần all_bad trước khi khóa
('comment_lock_hours', '24', 'moderation'),         -- Thời gian khóa sau vi phạm (giờ)
-- Cấu hình nội bộ DEV (ẩn khỏi admin)
('enable_gemini_moderation', '0', 'moderation'),    -- 0=tắt, 1=bật Gemini (dev kiểm soát)
('gemini_api_key', '', 'moderation'),               -- API key Gemini (dev điền)
('toxicity_threshold', '0.7', 'moderation');        -- Ngưỡng độc hại (0.0-1.0)

INSERT INTO `amenities` (`icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
('wifi', 'Wifi cáp quang', 'Tốc độ 200Mbps, không gián đoạn', 1, 1),
('security', 'An ninh 24/7', 'Camera giám sát & vân tay ra vào', 2, 1),
('local_parking', 'Chỗ để xe rộng', 'Miễn phí, có mái che an toàn', 3, 1),
('local_laundry_service', 'Máy giặt Free', 'Sử dụng không giới hạn', 4, 1),
('ac_unit', 'Điều hòa mát lạnh', 'Tiết kiệm điện, bảo trì định kỳ', 5, 1),
('kitchen', 'Bếp chung hiện đại', 'Đầy đủ dụng cụ nấu nướng', 6, 1),
('elevator', 'Thang máy', 'Di chuyển thuận tiện, an toàn', 7, 1),
('water_heater', 'Nóng lạnh 24/7', 'Máy nước nóng năng lượng mặt trời', 8, 1);

-- BANNED_WORDS (dev/admin quản lý — ĐÃ CHUẨN HÓA: bỏ dấu, chữ thường)
INSERT INTO `banned_words` (`word`, `type`, `replacement`, `is_active`) VALUES
('mat day', 'phrase', '***', 1),
('do mat day', 'phrase', '***', 1),
('me may', 'phrase', '***', 1),
('me kiep', 'phrase', '***', 1),
('cho chet', 'phrase', '***', 1),
('thang ngu', 'phrase', '***', 1),
('con diem', 'phrase', '***', 1),
('lua dao', 'phrase', '***', 1),
('suc vat', 'phrase', '***', 1),
('dm', 'abbreviation', '***', 1),
('vcl', 'abbreviation', '***', 1),
('vl', 'abbreviation', '***', 1),
('dkm', 'abbreviation', '***', 1),
('wtf', 'abbreviation', '***', 1);

-- COMMENTS (đánh giá mẫu)
INSERT INTO `comments` (`room_id`, `user_id`, `content`, `rating`, `toxicity_score`, `is_spam`, `flagged_words`, `status`) VALUES
(1, 2, 'Phòng rất tuyệt vời! An ninh tốt, chủ nhà nhiệt tình.', 5, 0.05, 0, NULL, 1),
(1, 3, 'Ở 2 người rất thoải mái, tiện nghi đầy đủ.', 5, 0.08, 0, NULL, 1);

-- COMMENT_MODERATION & COMMENT_REPORTS: để trống (có dữ liệu khi phát sinh)

/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
