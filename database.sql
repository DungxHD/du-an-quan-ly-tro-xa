-- --------------------------------------------------------
-- Máy chủ:                      127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Phiên bản:           12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table manage.amenities
DROP TABLE IF EXISTS `amenities`;
CREATE TABLE IF NOT EXISTS `amenities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.amenities: ~8 rows (approximately)
INSERT INTO `amenities` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
	(1, 'wifi', 'Wifi cáp quang', 'Internet cáp quang tốc độ cao, phủ sóng toàn khu.', 1, 1),
	(2, 'security', 'An ninh 24/7', 'Camera giám sát, khóa vân tay và bảo vệ theo ca.', 0, 1),
	(3, 'local_parking', 'Chỗ để xe', 'Khu vực giữ xe có mái che, kiểm soát ra vào.', 5, 1),
	(4, 'local_laundry_service', 'Máy giặt chung', 'Máy giặt và khu phơi đồ dùng chung.', 6, 1),
	(5, 'ac_unit', 'Điều hòa', 'Điều hòa Inverter, bảo trì định kỳ.', 7, 1),
	(6, 'kitchen', 'Bếp chung', 'Bếp từ, tủ lạnh và lò vi sóng dùng chung.', 4, 1),
	(7, 'elevator', 'Thang máy', 'Thang máy phục vụ các tầng, tải trọng phù hợp nhà trọ.', 2, 1),
	(8, 'water_heater', 'Nóng lạnh', 'Bình nóng lạnh cho phòng, sử dụng ổn định.', 3, 1);

-- Dumping structure for table manage.areas
DROP TABLE IF EXISTS `areas`;
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã khu',
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên khu',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Địa chỉ khu',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả khu',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn ảnh khu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.areas: ~2 rows (approximately)
INSERT INTO `areas` (`id`, `name`, `address`, `description`, `image`, `created_at`) VALUES
	(1, 'Khu A - Cao cấp', '123 Đường Nguyễn Xiển, TP. Thủ Đức, TP.HCM', 'Khu nhà 4 tầng, ưu tiên phòng studio và phòng khép kín, phù hợp sinh viên và người đi làm.', '/.uploads/image_khu_1/khu-a.jpg', '2026-01-05 01:00:00'),
	(2, 'Khu B - Tiết kiệm', '125 Đường Nguyễn Xiển, TP. Thủ Đức, TP.HCM', 'Khu nhà giá hợp lý, phòng diện tích vừa phải, có sân để xe và không gian sinh hoạt chung.', '/.uploads/image_khu_2/khu-b.jpg', '2026-01-05 01:10:00');

-- Dumping structure for table manage.banned_words
DROP TABLE IF EXISTS `banned_words`;
CREATE TABLE IF NOT EXISTS `banned_words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Từ/cụm từ cấm (ĐÃ CHUẨN HÓA)',
  `type` enum('word','phrase','abbreviation') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'word',
  `replacement` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '***',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.banned_words: ~10 rows (approximately)
INSERT INTO `banned_words` (`id`, `word`, `type`, `replacement`, `is_active`, `created_at`) VALUES
	(1, 'đụ', 'word', '***', 1, '2026-01-05 01:20:00'),
	(2, 'đéo', 'word', '***', 1, '2026-01-05 01:20:00'),
	(3, 'địt', 'word', '***', 1, '2026-01-05 01:20:00'),
	(4, 'ngu', 'word', '***', 1, '2026-01-05 01:20:00'),
	(5, 'đần', 'word', '***', 1, '2026-01-05 01:20:00'),
	(6, 'lừa đảo', 'phrase', '***', 1, '2026-01-05 01:20:00'),
	(7, 'scam', 'word', '***', 1, '2026-01-05 01:20:00'),
	(8, 'spam', 'word', '***', 1, '2026-01-05 01:20:00'),
	(9, 'ib', 'abbreviation', '***', 1, '2026-01-05 01:20:00'),
	(10, 'ck', 'abbreviation', '***', 1, '2026-01-05 01:20:00');

-- Dumping structure for table manage.comments
DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rating` tinyint NOT NULL DEFAULT '5',
  `toxicity_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `is_spam` tinyint(1) NOT NULL DEFAULT '0',
  `flagged_words` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint NOT NULL DEFAULT '1',
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_room` (`user_id`,`room_id`),
  KEY `idx_room_rating` (`room_id`,`rating`),
  KEY `idx_room_status` (`room_id`,`status`,`is_spam`),
  CONSTRAINT `fk_comment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comments: ~5 rows (approximately)
INSERT INTO `comments` (`id`, `room_id`, `user_id`, `content`, `rating`, `toxicity_score`, `is_spam`, `flagged_words`, `status`, `edited_at`, `created_at`) VALUES
	(2, 2, 4, 'Vị trí thuận tiện, phòng khá yên tĩnh và giá hợp lý.', 4, 0.03, 0, NULL, 0, NULL, '2026-03-22 03:00:00'),
	(3, 3, 5, 'Không gian rộng, máy lạnh hoạt động tốt, phù hợp ở lâu dài.', 5, 0.01, 0, NULL, 1, '2026-03-25 05:00:00', '2026-03-24 03:00:00'),
	(4, 4, 6, 'Phòng vừa túi tiền, khu vực gửi xe thuận tiện.', 4, 0.02, 0, NULL, 1, NULL, '2026-03-28 03:00:00'),
	(5, 5, 7, 'Mình đang cân nhắc phòng này, mong admin cập nhật hình ảnh mới.', 4, 0.01, 0, NULL, 0, NULL, '2026-04-02 03:00:00'),
	(36, 1, 3, 'Oke', 1, 0.00, 0, NULL, 1, NULL, '2026-08-18 01:10:05');

-- Dumping structure for table manage.comment_moderation
DROP TABLE IF EXISTS `comment_moderation`;
CREATE TABLE IF NOT EXISTS `comment_moderation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `attempt_count` int NOT NULL DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comment_moderation: ~2 rows (approximately)
INSERT INTO `comment_moderation` (`id`, `user_id`, `attempt_count`, `locked_until`, `last_attempt_at`, `created_at`, `updated_at`) VALUES
	(1, 3, 0, NULL, '2026-03-20 03:00:00', '2026-01-08 03:00:00', '2026-03-20 03:00:00'),
	(2, 5, 1, NULL, '2026-03-24 03:00:00', '2026-01-10 03:00:00', '2026-03-24 03:00:00');

-- Dumping structure for table manage.comment_reports
DROP TABLE IF EXISTS `comment_reports`;
CREATE TABLE IF NOT EXISTS `comment_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','resolved','dismissed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comment_user` (`comment_id`,`user_id`),
  KEY `idx_status` (`status`),
  KEY `fk_cr_user` (`user_id`),
  CONSTRAINT `fk_cr_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comment_reports: ~2 rows (approximately)
INSERT INTO `comment_reports` (`id`, `comment_id`, `user_id`, `reason`, `status`, `created_at`) VALUES
	(1, 5, 3, 'Nội dung cần cập nhật thêm thông tin hình ảnh.', 'resolved', '2026-04-02 04:00:00'),
	(2, 2, 5, 'Đánh giá có thông tin chưa rõ về tiện ích.', 'dismissed', '2026-03-23 01:00:00');

-- Dumping structure for table manage.contracts
DROP TABLE IF EXISTS `contracts`;
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `room_id` int unsigned NOT NULL,
  `move_in_date` date NOT NULL,
  `move_out_date` date DEFAULT NULL,
  `rent_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `deposit_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `initial_electricity_index` decimal(10,2) DEFAULT NULL,
  `initial_water_index` decimal(10,2) DEFAULT NULL,
  `status` enum('active','terminated') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `contract_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contract_user` (`user_id`),
  KEY `idx_contract_room` (`room_id`,`status`),
  KEY `idx_contract_active` (`user_id`,`move_out_date`),
  CONSTRAINT `fk_contract_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contract_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.contracts: ~8 rows (approximately)
INSERT INTO `contracts` (`id`, `user_id`, `room_id`, `move_in_date`, `move_out_date`, `rent_price`, `deposit_amount`, `initial_electricity_index`, `initial_water_index`, `status`, `contract_date`, `created_at`) VALUES
	(1, 3, 1, '2026-01-15', '2027-01-15', 4500000.00, 4500000.00, 1200.00, 35.00, 'active', '2026-01-10', '2026-01-10 01:00:00'),
	(2, 4, 2, '2026-01-18', '2027-01-18', 3800000.00, 3800000.00, 980.00, 41.00, 'active', '2026-01-13', '2026-01-13 01:00:00'),
	(3, 5, 3, '2026-01-20', '2027-01-20', 5200000.00, 5200000.00, 1500.00, 52.00, 'active', '2026-01-15', '2026-01-15 01:00:00'),
	(4, 6, 4, '2026-02-01', '2027-02-01', 3200000.00, 3200000.00, 730.00, 24.00, 'active', '2026-01-27', '2026-01-27 01:00:00'),
	(5, 7, 5, '2025-08-01', '2025-12-31', 3900000.00, 7800000.00, 420.00, 18.00, 'terminated', '2025-07-25', '2025-07-25 01:00:00'),
	(6, 7, 5, '2026-04-10', '2027-04-10', 4000000.00, 4000000.00, NULL, NULL, 'active', '2026-08-15', '2026-08-15 17:57:20'),
	(8, 17, 8, '2026-08-21', '2027-08-21', 5600000.00, 5600000.00, NULL, NULL, 'active', '2026-08-17', '2026-08-17 19:57:58'),
	(30, 18, 12, '2026-08-25', '2027-08-25', 3500000.00, 250000.00, NULL, NULL, 'active', '2026-08-18', '2026-08-17 22:06:54');

-- Dumping structure for table manage.feedbacks
DROP TABLE IF EXISTS `feedbacks`;
CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `room_id` int unsigned DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_reply` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','resolved','dismissed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_room` (`room_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_fb_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.feedbacks: ~3 rows (approximately)
INSERT INTO `feedbacks` (`id`, `user_id`, `room_id`, `subject`, `content`, `image`, `admin_note`, `admin_reply`, `status`, `created_at`) VALUES
	(1, 3, 1, 'Đề xuất bổ sung móc treo đồ', 'Mong nhà trọ bổ sung thêm móc treo phía sau cửa phòng tắm.', NULL, 'Đã ghi nhận để mua bổ sung trong đợt nâng cấp tiếp theo.', 'Cảm ơn bạn, đề xuất đã được ghi nhận.', 'resolved', '2026-03-21 02:00:00'),
	(2, 7, 5, 'Muốn xem phòng trực tiếp', 'Mình muốn đặt lịch xem phòng A202 vào cuối tuần.', NULL, '', 'Ngáo à?', 'resolved', '2026-04-02 06:00:00'),
	(3, 6, 4, 'Ổ điện bị lỏng', 'Ổ cắm gần bàn học có dấu hiệu lỏng, nhờ kiểm tra giúp.', NULL, 'Đã chuyển yêu cầu sang bộ phận bảo trì.', 'Kỹ thuật sẽ kiểm tra theo lịch đã thông báo.', 'resolved', '2026-04-10 01:00:00');

-- Dumping structure for table manage.floors
DROP TABLE IF EXISTS `floors`;
CREATE TABLE IF NOT EXISTS `floors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã tầng',
  `area_id` int unsigned NOT NULL COMMENT 'FK → areas',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor_number` int NOT NULL DEFAULT '1' COMMENT '0 = tầng trệt',
  `room_limit` int NOT NULL DEFAULT '0' COMMENT 'Giới hạn số phòng (0 = không giới hạn)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_floor_area` (`area_id`),
  CONSTRAINT `fk_floor_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.floors: ~4 rows (approximately)
INSERT INTO `floors` (`id`, `area_id`, `name`, `floor_number`, `room_limit`, `created_at`) VALUES
	(1, 1, 'Tầng 1 Khu A', 1, 3, '2026-01-05 01:30:00'),
	(2, 1, 'Tầng 2 Khu A', 2, 3, '2026-01-05 01:30:00'),
	(3, 1, 'Tầng 3 Khu A', 3, 3, '2026-01-05 01:30:00'),
	(4, 2, 'Tầng 1 Khu B', 1, 3, '2026-01-05 01:40:00');

-- Dumping structure for table manage.maintenance_requests
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_days` int unsigned NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `status` enum('pending','active','rejected','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejected_by_user_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mr_room` (`room_id`),
  KEY `idx_mr_status` (`status`),
  KEY `idx_mr_start` (`start_date`),
  KEY `fk_mnt_admin` (`admin_id`),
  KEY `fk_mnt_rejected_by` (`rejected_by_user_id`),
  CONSTRAINT `fk_mnt_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mnt_rejected_by` FOREIGN KEY (`rejected_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mnt_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.maintenance_requests: ~4 rows (approximately)
INSERT INTO `maintenance_requests` (`id`, `room_id`, `admin_id`, `reason`, `duration_days`, `start_date`, `status`, `rejected_by_user_id`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 'Thay van cấp nước tại khu vực nhà vệ sinh.', 1, '2026-03-18', 'completed', NULL, '2026-03-17 02:00:00', '2026-03-18 10:00:00'),
	(2, 3, 2, 'Bảo trì điều hòa và vệ sinh lưới lọc định kỳ.', 1, '2026-04-08', 'active', NULL, '2026-04-06 03:00:00', '2026-04-08 01:00:00'),
	(3, 4, 1, 'Kiểm tra ổ điện bàn học và thay ổ cắm bị lỏng.', 1, '2026-04-12', 'rejected', 6, '2026-04-10 02:30:00', '2026-04-10 08:00:00'),
	(4, 5, 2, 'Kiểm tra quạt thông gió nhà vệ sinh.', 1, '2026-04-14', 'active', NULL, '2026-04-12 03:00:00', '2026-08-15 18:44:32');

-- Dumping structure for table manage.meter_readings
DROP TABLE IF EXISTS `meter_readings`;
CREATE TABLE IF NOT EXISTS `meter_readings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `old_index` decimal(10,2) NOT NULL DEFAULT '0.00',
  `new_index` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meter_period` (`room_id`,`service_id`,`month`,`year`),
  KEY `fk_mr_service` (`service_id`),
  CONSTRAINT `fk_mr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.meter_readings: ~8 rows (approximately)
INSERT INTO `meter_readings` (`id`, `room_id`, `service_id`, `month`, `year`, `old_index`, `new_index`, `created_at`) VALUES
	(1, 1, 1, 2, 2026, 1200.00, 1320.00, '2026-03-01 01:00:00'),
	(2, 1, 1, 3, 2026, 1320.00, 1448.00, '2026-04-01 01:00:00'),
	(3, 2, 1, 2, 2026, 980.00, 1075.00, '2026-03-01 01:05:00'),
	(4, 2, 1, 3, 2026, 1075.00, 1168.00, '2026-04-01 01:05:00'),
	(5, 3, 1, 2, 2026, 1500.00, 1632.00, '2026-03-01 01:10:00'),
	(6, 3, 1, 3, 2026, 1632.00, 1770.00, '2026-04-01 01:10:00'),
	(7, 4, 1, 2, 2026, 730.00, 812.00, '2026-03-01 01:15:00'),
	(8, 4, 1, 3, 2026, 812.00, 901.00, '2026-04-01 01:15:00');

-- Dumping structure for table manage.notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT 'NULL = broadcast tất cả',
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('price_change','payment','general','feedback','review','service','rental_request','invoice') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_noti_user` (`user_id`),
  CONSTRAINT `fk_noti_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=333 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notifications: ~33 rows (approximately)
INSERT INTO `notifications` (`id`, `user_id`, `title`, `content`, `link`, `type`, `is_read`, `created_at`) VALUES
	(1, 3, 'Hóa đơn tháng 03/2026', 'Hóa đơn tiền phòng và dịch vụ tháng 03 đã được tạo.', '/tenant/payments', 'invoice', 1, '2026-03-01 01:30:00'),
	(2, 4, 'Hóa đơn tháng 03/2026', 'Hóa đơn tiền phòng và dịch vụ tháng 03 đã được tạo.', '/tenant/payments', 'invoice', 1, '2026-03-01 01:35:00'),
	(3, 5, 'Hóa đơn tháng 03/2026', 'Hóa đơn tháng 03 đang chờ thanh toán.', '/tenant/payments', 'payment', 0, '2026-03-01 01:40:00'),
	(5, 7, 'Yêu cầu thuê phòng', 'Yêu cầu thuê phòng A202 đang được quản lý xét duyệt.', '/tenant/rental-requests', 'rental_request', 0, '2026-04-02 02:10:00'),
	(7, NULL, 'Thông báo bảo trì chung', 'Khu A sẽ bảo trì hệ thống nước từ 08:00-10:00 ngày 18/04/2026.', '/notifications', 'general', 0, '2026-04-16 00:00:00'),
	(8, 3, 'Thay đổi bảng giá dịch vụ', 'Giá wifi được điều chỉnh từ tháng 03/2026.', '/tenant/services', 'price_change', 1, '2026-02-16 01:00:00'),
	(9, 7, 'Yêu cầu thuê phòng đã được duyệt', 'Chúc mừng! Yêu cầu thuê phòng "A202 - Standard Plus" của bạn đã được admin duyệt. Ngày vào ở: 10/04/2026.', '', 'general', 0, '2026-08-15 17:57:21'),
	(10, 7, 'Yêu cầu ở ghép bị hủy', 'Phạm Gia Bảo đã hủy lời mời ở ghép.', '', 'general', 0, '2026-08-15 17:57:57'),
	(13, 7, 'Chủ trọ đã phản hồi phản ánh của bạn', 'Phản ánh "Muốn xem phòng trực tiếp": Ngáo à?', '?page=tenant-feedback', 'feedback', 0, '2026-08-15 19:38:42'),
	(17, 17, 'Người thuê đã thanh toán thành công', 'Người thuê Lương Văn Dũng đã thanh toán thành công và chính thức vào thuê phòng "A302 - Family".', '', 'general', 1, '2026-08-17 19:57:58'),
	(18, 17, 'Chào mừng đến với phòng A302 - Family', 'Bạn đã thanh toán thành công và chính thức là người thuê phòng "A302 - Family". Ngày vào ở: 21/08/2026.', '', 'general', 0, '2026-08-17 19:57:58'),
	(19, 2, 'Yêu cầu thuê phòng mới', 'Guest A Test đã gửi yêu cầu thuê phòng "A203 - Deluxe", ngày dự kiến vào ở 25/08/2026. Cần admin xét duyệt.', '?page=admin-rent-requests&rent_filter=pending', 'rental_request', 0, '2026-08-17 20:27:20'),
	(20, 1, 'Yêu cầu thuê phòng mới', 'Guest A Test đã gửi yêu cầu thuê phòng "A203 - Deluxe", ngày dự kiến vào ở 25/08/2026. Cần admin xét duyệt.', '?page=admin-rent-requests&rent_filter=pending', 'rental_request', 0, '2026-08-17 20:27:20'),
	(223, 18, 'Yêu cầu thuê đã được chấp nhận', 'Yêu cầu thuê phòng "B03 - Double" của bạn đã được admin chấp nhận. Vui lòng thanh toán tiền cọc 250.000đ để giữ phòng này cho đến hết ngày dự kiến vào ở (25/08/2026). Mã QR thanh toán đã sẵn sàng.', '?page=request-rent&id=12', 'rental_request', 0, '2026-08-17 22:06:26'),
	(224, 2, 'Tiền cọc đã được thanh toán', 'Lương Văn Dũng đã thanh toán tiền cọc 250.000đ thành công cho phòng "B03 - Double".', '?page=admin-rent-requests&rent_filter=approved', 'rental_request', 0, '2026-08-17 22:06:54'),
	(225, 1, 'Tiền cọc đã được thanh toán', 'Lương Văn Dũng đã thanh toán tiền cọc 250.000đ thành công cho phòng "B03 - Double".', '?page=admin-rent-requests&rent_filter=approved', 'rental_request', 0, '2026-08-17 22:06:54'),
	(226, 18, 'Chào mừng đến với phòng B03 - Double', 'Bạn đã thanh toán tiền cọc 250.000đ thành công và chính thức là người thuê phòng "B03 - Double". Ngày vào ở: 25/08/2026.', '', 'general', 0, '2026-08-17 22:06:54'),
	(317, 18, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(318, 17, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(319, 7, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(320, 6, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(321, 5, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(322, 4, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-17 23:41:40'),
	(323, 2, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=admin-comments', 'review', 0, '2026-08-17 23:41:40'),
	(324, 1, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=admin-comments', 'review', 0, '2026-08-17 23:41:40'),
	(325, 18, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(326, 17, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(327, 7, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(328, 6, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(329, 5, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(330, 4, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=tenant', 'review', 0, '2026-08-18 01:10:05'),
	(331, 2, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=admin-comments', 'review', 0, '2026-08-18 01:10:05'),
	(332, 1, 'Đánh giá phòng mới', 'Phòng "A101 - Studio Deluxe" được đánh giá từ người thuê "Phạm Gia Bảo".', '?page=admin-comments', 'review', 0, '2026-08-18 01:10:05');

-- Dumping structure for table manage.notification_reads
DROP TABLE IF EXISTS `notification_reads`;
CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_user` (`notification_id`,`user_id`),
  KEY `fk_nr_user` (`user_id`),
  CONSTRAINT `fk_nr_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notification_reads: ~5 rows (approximately)
INSERT INTO `notification_reads` (`id`, `notification_id`, `user_id`, `read_at`) VALUES
	(1, 1, 3, '2026-03-02 02:00:00'),
	(2, 2, 4, '2026-03-02 02:10:00'),
	(5, 8, 3, '2026-02-17 01:00:00'),
	(6, 7, 3, '2026-04-16 01:30:00'),
	(7, 17, 17, '2026-08-17 19:58:42');

-- Dumping structure for table manage.password_reset_otps
DROP TABLE IF EXISTS `password_reset_otps`;
CREATE TABLE IF NOT EXISTS `password_reset_otps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `used_at` datetime DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_user_created` (`user_id`,`created_at`),
  KEY `idx_otp_expires` (`expires_at`),
  CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.password_reset_otps: ~11 rows (approximately)
INSERT INTO `password_reset_otps` (`id`, `user_id`, `otp_hash`, `expires_at`, `attempts`, `used_at`, `ip`, `created_at`) VALUES
	(1, 7, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-20 10:02:00', 0, NULL, '192.168.1.20', '2026-04-20 03:00:00'),
	(3, 1, '$2y$10$I7ZjsWKASrjXMFgDUHKmuexubCOjvb6YeJrGQ8yf52pVNXWwr5L9u', '2026-08-16 03:58:17', 0, NULL, '::1', '2026-08-16 03:56:17'),
	(4, 3, '$2y$10$DiwKVtOlUlHPVl0v6/lYxesp2bgwWssrUwm4L.Zbf9z5CB4CInyWC', '2026-08-16 03:58:27', 0, NULL, '::1', '2026-08-16 03:56:27'),
	(5, 18, '$2y$10$.p1/YSIrszYTc68DAeFmB.bGdmZK7t8zztFXNWb9ukTag4jdzQfty', '2026-08-18 09:10:06', 0, NULL, '::1', '2026-08-18 02:08:06'),
	(6, 3, '$2y$10$1adk133/SFcAIHco1xPrNebWHKJSPLcGw8sE05UhUrJexO9MsIkMu', '2026-08-18 09:11:14', 0, NULL, '::1', '2026-08-18 02:09:14'),
	(7, 7, '$2y$10$3W2Jf0epjNjQD44xCX/tleqtGbvaaN3/DnECx.7BjdzAEwYEaBU4.', '2026-08-18 09:11:39', 0, NULL, '::1', '2026-08-18 02:09:39'),
	(8, 17, '$2y$10$ggYKR3HMGyaJcv4w88tdRuKq1ROcx33FYtXCjG8xm.pm05zFP0DrO', '2026-08-18 09:11:48', 0, '2026-08-18 09:10:12', '::1', '2026-08-18 02:09:48'),
	(9, 18, '$2y$10$upUOx4J3xvWQ52ioqn279ekU9Y23rQgChbkAd0Fajy.9vvPwCcnnu', '2026-08-18 09:13:26', 0, NULL, '::1', '2026-08-18 02:11:26'),
	(10, 17, '$2y$10$RR3r.efVawkNB.L6J2zw1eKHRrJn5JWLhofTE2KkMSXhQHdvmJeNa', '2026-08-18 09:17:03', 0, NULL, '::1', '2026-08-18 02:15:03'),
	(11, 18, '$2y$10$7k4/ecy2qoxRiaJAEaCmtuns/QH3Ko.pcm0TCfFKbX18LNhtcd08S', '2026-08-18 09:36:31', 0, NULL, '::1', '2026-08-18 02:34:31'),
	(12, 18, '$2y$10$BOVN1Z0RYUZ6YQKP6lrfoueGKLpvJDVR9sGv1dhAEA1R9JTwDi6wy', '2026-08-18 09:37:57', 0, NULL, '::1', '2026-08-18 02:35:57');

-- Dumping structure for table manage.password_reset_send_attempts
DROP TABLE IF EXISTS `password_reset_send_attempts`;
CREATE TABLE IF NOT EXISTS `password_reset_send_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_send_user_ip_time` (`user_id`,`ip`,`sent_at`),
  CONSTRAINT `fk_send_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.password_reset_send_attempts: ~12 rows (approximately)
INSERT INTO `password_reset_send_attempts` (`id`, `user_id`, `ip`, `sent_at`, `created_at`) VALUES
	(1, 7, '192.168.1.20', '2026-04-20 10:00:00', '2026-04-20 03:00:00'),
	(2, 7, '192.168.1.20', '2026-04-20 10:15:00', '2026-04-20 03:15:00'),
	(4, 1, '::1', '2026-08-16 03:56:17', '2026-08-16 03:56:17'),
	(5, 3, '::1', '2026-08-16 03:56:27', '2026-08-16 03:56:27'),
	(6, 18, '::1', '2026-08-18 09:08:06', '2026-08-18 02:08:06'),
	(7, 3, '::1', '2026-08-18 09:09:14', '2026-08-18 02:09:14'),
	(8, 7, '::1', '2026-08-18 09:09:39', '2026-08-18 02:09:39'),
	(9, 17, '::1', '2026-08-18 09:09:48', '2026-08-18 02:09:48'),
	(10, 18, '::1', '2026-08-18 09:11:26', '2026-08-18 02:11:26'),
	(11, 17, '::1', '2026-08-18 09:15:03', '2026-08-18 02:15:03'),
	(12, 18, '::1', '2026-08-18 09:34:31', '2026-08-18 02:34:31'),
	(13, 18, '::1', '2026-08-18 09:35:57', '2026-08-18 02:35:57');

-- Dumping structure for table manage.payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `contract_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_period` (`room_id`,`month`,`year`),
  KEY `fk_pay_user` (`user_id`),
  KEY `fk_pay_contract` (`contract_id`),
  CONSTRAINT `fk_pay_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pay_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payments: ~5 rows (approximately)
INSERT INTO `payments` (`id`, `room_id`, `contract_id`, `user_id`, `month`, `year`, `amount`, `status`, `paid_at`, `created_at`) VALUES
	(1, 1, 1, 3, 3, 2026, 621000.00, 'paid', '2026-03-05 03:00:00', '2026-03-01 01:00:00'),
	(2, 2, 2, 4, 3, 2026, 462500.00, 'paid', '2026-03-06 03:30:00', '2026-03-01 01:05:00'),
	(3, 3, 3, 5, 3, 2026, 701000.00, 'unpaid', NULL, '2026-03-01 01:10:00'),
	(4, 4, 4, 6, 3, 2026, 417000.00, 'paid', '2026-03-07 02:00:00', '2026-03-01 01:15:00'),
	(5, 1, 1, 3, 4, 2026, 649000.00, 'unpaid', NULL, '2026-04-01 01:00:00');

-- Dumping structure for table manage.payment_items
DROP TABLE IF EXISTS `payment_items`;
CREATE TABLE IF NOT EXISTS `payment_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` int unsigned NOT NULL,
  `service_id` int unsigned DEFAULT NULL,
  `item_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `billing_mode` enum('fixed','meter','per_person','per_unit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pi_payment` (`payment_id`),
  KEY `fk_pi_service` (`service_id`),
  CONSTRAINT `fk_pi_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payment_items: ~25 rows (approximately)
INSERT INTO `payment_items` (`id`, `payment_id`, `service_id`, `item_name`, `unit_price`, `quantity`, `amount`, `billing_mode`, `created_at`) VALUES
	(1, 1, 1, 'Tiền điện', 3500.00, 120.00, 420000.00, 'meter', '2026-03-01 01:01:00'),
	(2, 1, 2, 'Tiền nước', 30000.00, 1.00, 30000.00, 'per_person', '2026-03-01 01:01:00'),
	(3, 1, 3, 'Tiền rác', 20000.00, 1.00, 20000.00, 'per_person', '2026-03-01 01:01:00'),
	(4, 1, 4, 'Wifi', 51000.00, 1.00, 51000.00, 'per_person', '2026-03-01 01:01:00'),
	(5, 1, 5, 'Giữ xe', 100000.00, 1.00, 100000.00, 'fixed', '2026-03-01 01:01:00'),
	(6, 2, 1, 'Tiền điện', 3500.00, 95.00, 332500.00, 'meter', '2026-03-01 01:06:00'),
	(7, 2, 2, 'Tiền nước', 30000.00, 1.00, 30000.00, 'per_person', '2026-03-01 01:06:00'),
	(8, 2, 3, 'Tiền rác', 20000.00, 1.00, 20000.00, 'per_person', '2026-03-01 01:06:00'),
	(9, 2, 4, 'Wifi', 51000.00, 1.00, 51000.00, 'per_person', '2026-03-01 01:06:00'),
	(10, 2, 5, 'Giữ xe', 29000.00, 1.00, 29000.00, 'fixed', '2026-03-01 01:06:00'),
	(11, 3, 1, 'Tiền điện', 3500.00, 132.00, 462000.00, 'meter', '2026-03-01 01:11:00'),
	(12, 3, 2, 'Tiền nước', 30000.00, 2.00, 60000.00, 'per_person', '2026-03-01 01:11:00'),
	(13, 3, 3, 'Tiền rác', 20000.00, 2.00, 40000.00, 'per_person', '2026-03-01 01:11:00'),
	(14, 3, 4, 'Wifi', 51000.00, 2.00, 102000.00, 'per_person', '2026-03-01 01:11:00'),
	(15, 3, 6, 'Vệ sinh', 50000.00, 1.00, 50000.00, 'fixed', '2026-03-01 01:11:00'),
	(16, 4, 1, 'Tiền điện', 3500.00, 82.00, 287000.00, 'meter', '2026-03-01 01:16:00'),
	(17, 4, 2, 'Tiền nước', 30000.00, 1.00, 30000.00, 'per_person', '2026-03-01 01:16:00'),
	(18, 4, 3, 'Tiền rác', 20000.00, 1.00, 20000.00, 'per_person', '2026-03-01 01:16:00'),
	(19, 4, 4, 'Wifi', 51000.00, 1.00, 51000.00, 'per_person', '2026-03-01 01:16:00'),
	(20, 4, 6, 'Vệ sinh', 29000.00, 1.00, 29000.00, 'fixed', '2026-03-01 01:16:00'),
	(21, 5, 1, 'Tiền điện', 3500.00, 128.00, 448000.00, 'meter', '2026-04-01 01:01:00'),
	(22, 5, 2, 'Tiền nước', 30000.00, 1.00, 30000.00, 'per_person', '2026-04-01 01:01:00'),
	(23, 5, 3, 'Tiền rác', 20000.00, 1.00, 20000.00, 'per_person', '2026-04-01 01:01:00'),
	(24, 5, 4, 'Wifi', 51000.00, 1.00, 51000.00, 'per_person', '2026-04-01 01:01:00'),
	(25, 5, 6, 'Vệ sinh', 50000.00, 1.00, 50000.00, 'fixed', '2026-04-01 01:01:00');

-- Dumping structure for table manage.price_changes
DROP TABLE IF EXISTS `price_changes`;
CREATE TABLE IF NOT EXISTS `price_changes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int unsigned NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `old_billing_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_billing_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_month` tinyint NOT NULL,
  `effective_year` smallint NOT NULL,
  `applied` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pc_service` (`service_id`),
  KEY `fk_pc_user` (`created_by`),
  CONSTRAINT `fk_pc_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.price_changes: ~2 rows (approximately)
INSERT INTO `price_changes` (`id`, `service_id`, `old_price`, `new_price`, `old_billing_mode`, `new_billing_mode`, `effective_month`, `effective_year`, `applied`, `created_by`, `created_at`) VALUES
	(1, 1, 3200.00, 3500.00, 'meter', 'meter', 2, 2026, 1, 1, '2026-01-20 02:00:00'),
	(2, 4, 49000.00, 51000.00, 'per_person', 'per_person', 3, 2026, 1, 2, '2026-02-15 02:00:00');

-- Dumping structure for table manage.rental_requests
DROP TABLE IF EXISTS `rental_requests`;
CREATE TABLE IF NOT EXISTS `rental_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `room_id` int unsigned NOT NULL,
  `move_in_date` date NOT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit` decimal(10,2) DEFAULT NULL COMMENT 'Tien coc admin an dinh khi duyet',
  `occupant_count` tinyint NOT NULL DEFAULT '1',
  `status` enum('pending','approved','rejected','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','confirmed','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  KEY `idx_rr_room` (`room_id`),
  KEY `idx_rr_status` (`status`),
  CONSTRAINT `fk_rr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rental_requests: ~4 rows (approximately)
INSERT INTO `rental_requests` (`id`, `user_id`, `room_id`, `move_in_date`, `gender`, `deposit`, `occupant_count`, `status`, `payment_status`, `admin_note`, `created_at`, `updated_at`) VALUES
	(1, 7, 5, '2026-04-10', 'male', NULL, 1, 'approved', 'pending', 'Yêu cầu đã được duyệt.', '2026-04-02 02:00:00', '2026-08-15 17:57:20'),
	(3, 7, 10, '2026-04-20', 'male', NULL, 1, 'rejected', 'pending', 'Phòng đã được ưu tiên cho một hồ sơ khác.', '2026-04-03 04:00:00', '2026-04-04 01:30:00'),
	(7, 17, 8, '2026-08-21', 'male', NULL, 1, 'approved', 'paid', '', '2026-08-17 19:57:00', '2026-08-17 19:57:58'),
	(8, 18, 12, '2026-08-25', 'male', 250000.00, 1, 'approved', 'paid', '', '2026-08-17 20:14:50', '2026-08-17 22:06:54');

-- Dumping structure for table manage.roommate_requests
DROP TABLE IF EXISTS `roommate_requests`;
CREATE TABLE IF NOT EXISTS `roommate_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` int unsigned NOT NULL COMMENT 'Người B — gửi yêu cầu',
  `host_user_id` int unsigned NOT NULL COMMENT 'Người A — đang ở phòng',
  `room_id` int unsigned NOT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ly do tu choi',
  `status` enum('pending','pending_admin','approved','rejected','cancelled','admin_rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_requester` (`requester_id`),
  KEY `idx_rm_host` (`host_user_id`),
  KEY `idx_rm_room` (`room_id`),
  CONSTRAINT `fk_rm_host` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.roommate_requests: ~1 rows (approximately)
INSERT INTO `roommate_requests` (`id`, `requester_id`, `host_user_id`, `room_id`, `gender`, `relationship`, `admin_note`, `status`, `created_at`, `updated_at`) VALUES
	(1, 7, 3, 1, 'male', 'Bạn học cùng lớp', NULL, 'cancelled', '2026-04-03 07:00:00', '2026-08-15 17:57:57');

-- Dumping structure for table manage.rooms
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã phòng',
  `floor_id` int unsigned NOT NULL COMMENT 'FK → floors',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0' COMMENT 'Số thứ tự phòng trong tầng',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `area` decimal(5,2) DEFAULT '0.00' COMMENT 'Diện tích m²',
  `max_occupancy` tinyint DEFAULT '2',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `amenities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Tiện nghi phòng (JSON array)',
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','available','rented','maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notice_given` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = tenant da bao chuyen di',
  `expected_vacant_date` date DEFAULT NULL,
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_floor` (`floor_id`),
  KEY `idx_room_status` (`status`),
  CONSTRAINT `fk_room_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rooms: ~12 rows (approximately)
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `position`, `price`, `area`, `max_occupancy`, `description`, `amenities`, `thumbnail`, `status`, `notice_given`, `expected_vacant_date`, `views`, `created_at`) VALUES
	(1, 1, 'A101 - Studio Deluxe', 1, 4500000.00, 30.00, 2, 'Phòng studio khép kín 30m², có ban công, giường đôi, tủ quần áo, bàn làm việc và máy lạnh Inverter.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_1/a101.jpg', 'rented', 0, NULL, 342, '2026-01-06 01:00:00'),
	(2, 1, 'A102 - Standard', 2, 3800000.00, 25.00, 2, 'Phòng tiêu chuẩn 25m², cửa sổ lớn, nội thất cơ bản, vệ sinh khép kín.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_2/a102.jpg', 'rented', 0, NULL, 281, '2026-01-06 01:05:00'),
	(3, 1, 'A103 - Premium', 3, 5200000.00, 34.00, 3, 'Phòng premium 34m², ban công rộng, nội thất mới, phù hợp 2-3 người.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/a103.jpg', 'rented', 0, NULL, 411, '2026-01-06 01:10:00'),
	(4, 2, 'A201 - Economy', 1, 3200000.00, 22.00, 2, 'Phòng tiết kiệm dành cho sinh viên, thiết kế gọn gàng và dễ sử dụng.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_4/a201.jpg', 'rented', 0, NULL, 190, '2026-01-06 01:15:00'),
	(5, 2, 'A202 - Standard Plus', 2, 4000000.00, 26.00, 2, 'Phòng sáng, thoáng, có bàn học và tủ quần áo lớn.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_5/a202.jpg', 'maintenance', 0, NULL, 156, '2026-01-06 01:20:00'),
	(6, 2, 'A203 - Deluxe', 3, 4400000.00, 29.00, 2, 'Phòng deluxe có ban công nhỏ, máy lạnh Inverter và nội thất hoàn thiện.', '["wifi","security","local_parking","local_laundry_service","ac_unit","water_heater"]', '/.uploads/image_phong_6/a203.jpg', 'available', 0, NULL, 215, '2026-01-06 01:25:00'),
	(7, 3, 'A301 - Studio', 1, 4200000.00, 28.00, 2, 'Studio khép kín, nhiều ánh sáng tự nhiên, phù hợp một người hoặc cặp đôi.', '["wifi","security","ac_unit","water_heater"]', '/.uploads/image_phong_7/a301.jpg', 'available', 0, NULL, 178, '2026-01-06 01:30:00'),
	(8, 3, 'A302 - Family', 2, 5600000.00, 35.00, 3, 'Phòng gia đình rộng, có khu bếp nhỏ và nhiều không gian lưu trữ.', '["wifi","security","local_parking","ac_unit","kitchen","water_heater"]', '/.uploads/image_phong_8/a302.jpg', 'rented', 0, NULL, 269, '2026-01-06 01:35:00'),
	(9, 3, 'A303 - Standard', 3, 3900000.00, 25.00, 2, 'Phòng tiêu chuẩn yên tĩnh, cửa sổ thoáng và đầy đủ tiện ích cơ bản.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_9/a303.jpg', 'available', 0, NULL, 151, '2026-01-06 01:40:00'),
	(10, 4, 'B01 - Compact', 1, 2900000.00, 20.00, 2, 'Phòng tiết kiệm 20m², phù hợp sinh viên ngân sách vừa phải.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_10/b01.jpg', 'available', 0, NULL, 134, '2026-01-06 01:45:00'),
	(11, 4, 'B02 - Standard', 2, 3100000.00, 23.00, 2, 'Phòng tiêu chuẩn thoáng, có bàn học và khu vệ sinh riêng.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_11/b02.jpg', 'available', 0, NULL, 118, '2026-01-06 01:50:00'),
	(12, 4, 'B03 - Double', 3, 3500000.00, 26.00, 3, 'Phòng rộng cho 2-3 người, thích hợp nhóm bạn ở chung.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_12/b03.jpg', 'rented', 0, NULL, 210, '2026-01-06 01:55:00');

-- Dumping structure for table manage.room_images
DROP TABLE IF EXISTS `room_images`;
CREATE TABLE IF NOT EXISTS `room_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ri_room` (`room_id`),
  CONSTRAINT `fk_ri_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_images: ~17 rows (approximately)
INSERT INTO `room_images` (`id`, `room_id`, `image_url`, `is_primary`, `sort_order`, `created_at`) VALUES
	(1, 1, '/.uploads/image_phong_1/a101.jpg', 1, 1, '2026-01-06 02:00:00'),
	(2, 1, '/.uploads/image_phong_1/a101-bep.jpg', 0, 2, '2026-01-06 02:00:00'),
	(3, 2, '/.uploads/image_phong_2/a102.jpg', 1, 1, '2026-01-06 02:05:00'),
	(4, 2, '/.uploads/image_phong_2/a102-giuong.jpg', 0, 2, '2026-01-06 02:05:00'),
	(5, 3, '/.uploads/image_phong_3/a103.jpg', 1, 1, '2026-01-06 02:10:00'),
	(6, 3, '/.uploads/image_phong_3/a103-bancong.jpg', 0, 2, '2026-01-06 02:10:00'),
	(7, 4, '/.uploads/image_phong_4/a201.jpg', 1, 1, '2026-01-06 02:15:00'),
	(8, 4, '/.uploads/image_phong_4/a201-ban.jpg', 0, 2, '2026-01-06 02:15:00'),
	(9, 5, '/.uploads/image_phong_5/a202.jpg', 1, 1, '2026-01-06 02:20:00'),
	(10, 6, '/.uploads/image_phong_6/a203.jpg', 1, 1, '2026-01-06 02:25:00'),
	(11, 7, '/.uploads/image_phong_7/a301.jpg', 1, 1, '2026-01-06 02:30:00'),
	(12, 8, '/.uploads/image_phong_8/a302.jpg', 1, 1, '2026-01-06 02:35:00'),
	(13, 8, '/.uploads/image_phong_8/a302-bep.jpg', 0, 2, '2026-01-06 02:35:00'),
	(14, 9, '/.uploads/image_phong_9/a303.jpg', 1, 1, '2026-01-06 02:40:00'),
	(15, 10, '/.uploads/image_phong_10/b01.jpg', 1, 1, '2026-01-06 02:45:00'),
	(16, 11, '/.uploads/image_phong_11/b02.jpg', 1, 1, '2026-01-06 02:50:00'),
	(17, 12, '/.uploads/image_phong_12/b03.jpg', 1, 1, '2026-01-06 02:55:00');

-- Dumping structure for table manage.room_price_changes
DROP TABLE IF EXISTS `room_price_changes`;
CREATE TABLE IF NOT EXISTS `room_price_changes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `effective_month` tinyint NOT NULL,
  `effective_year` smallint NOT NULL,
  `applied` tinyint NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rpc_room` (`room_id`),
  KEY `idx_rpc_effective` (`effective_year`,`effective_month`),
  CONSTRAINT `fk_rpc_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_price_changes: ~2 rows (approximately)
INSERT INTO `room_price_changes` (`id`, `room_id`, `old_price`, `new_price`, `effective_month`, `effective_year`, `applied`, `created_by`, `created_at`) VALUES
	(1, 1, 4300000.00, 4500000.00, 1, 2026, 1, 1, '2025-12-20 03:00:00'),
	(2, 4, 3000000.00, 3200000.00, 2, 2026, 1, 2, '2026-01-20 03:00:00');

-- Dumping structure for table manage.room_services
DROP TABLE IF EXISTS `room_services`;
CREATE TABLE IF NOT EXISTS `room_services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_service` (`room_id`,`service_id`),
  KEY `fk_rs_service` (`service_id`),
  CONSTRAINT `fk_rs_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_services: ~26 rows (approximately)
INSERT INTO `room_services` (`id`, `room_id`, `service_id`, `quantity`, `registered_at`) VALUES
	(6, 2, 1, 1, '2026-01-18 02:00:00'),
	(7, 2, 2, 1, '2026-01-18 02:00:00'),
	(8, 2, 3, 1, '2026-01-18 02:00:00'),
	(10, 3, 1, 1, '2026-01-20 02:00:00'),
	(11, 3, 2, 1, '2026-01-20 02:00:00'),
	(12, 3, 3, 1, '2026-01-20 02:00:00'),
	(15, 4, 1, 1, '2026-02-01 02:00:00'),
	(16, 4, 2, 1, '2026-02-01 02:00:00'),
	(17, 4, 3, 1, '2026-02-01 02:00:00'),
	(19, 5, 1, 1, '2026-02-10 02:00:00'),
	(20, 5, 2, 1, '2026-02-10 02:00:00'),
	(21, 5, 3, 1, '2026-02-10 02:00:00'),
	(23, 6, 1, 1, '2026-02-11 02:00:00'),
	(24, 6, 2, 1, '2026-02-11 02:00:00'),
	(25, 6, 3, 1, '2026-02-11 02:00:00'),
	(26, 10, 1, 1, '2026-02-12 02:00:00'),
	(27, 10, 2, 1, '2026-02-12 02:00:00'),
	(28, 11, 1, 1, '2026-02-12 02:00:00'),
	(29, 11, 2, 1, '2026-02-12 02:00:00'),
	(30, 12, 1, 1, '2026-02-12 02:00:00'),
	(31, 12, 2, 1, '2026-02-12 02:00:00'),
	(36, 2, 4, 1, '2026-01-18 02:10:00'),
	(37, 3, 4, 1, '2026-01-20 02:10:00'),
	(38, 3, 5, 1, '2026-01-20 02:10:00'),
	(39, 3, 6, 1, '2026-01-20 02:10:00'),
	(40, 4, 4, 1, '2026-02-01 02:10:00');

-- Dumping structure for table manage.services
DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'tháng',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'settings',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `billing_mode` enum('fixed','meter','per_person','per_unit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `kind` enum('other','electricity','water','trash') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `applies_to` enum('room','person') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'room',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `delete_year` smallint DEFAULT NULL,
  `delete_month` tinyint DEFAULT NULL,
  `deactivate_month` tinyint DEFAULT NULL,
  `deactivate_year` smallint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.services: ~7 rows (approximately)
INSERT INTO `services` (`id`, `name`, `price`, `unit`, `icon`, `description`, `is_required`, `billing_mode`, `kind`, `applies_to`, `is_active`, `delete_year`, `delete_month`, `deactivate_month`, `deactivate_year`) VALUES
	(1, 'Tiền điện', 3500.00, 'kWh', 'bolt', 'Tính theo chỉ số công tơ điện thực tế của từng phòng.', 1, 'meter', 'electricity', 'room', 1, NULL, NULL, NULL, NULL),
	(2, 'Tiền nước', 30000.00, 'người/tháng', 'water_drop', 'Tính theo số người đang ở trong phòng.', 1, 'per_person', 'water', 'room', 1, NULL, NULL, NULL, NULL),
	(3, 'Tiền rác', 20000.00, 'người/tháng', 'delete', 'Phí thu gom rác sinh hoạt.', 1, 'per_person', 'trash', 'room', 1, NULL, NULL, NULL, NULL),
	(4, 'Wifi', 51000.00, 'người/tháng', 'wifi', 'Internet cáp quang dùng chung toàn khu.', 0, 'per_person', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(5, 'Giữ xe', 100000.00, 'xe/tháng', 'two_wheeler', 'Phí gửi xe máy có mái che.', 0, 'fixed', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(6, 'Vệ sinh', 50000.00, 'phòng/tháng', 'cleaning_services', 'Vệ sinh hành lang, cầu thang và khu sinh hoạt chung.', 0, 'fixed', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(7, 'Máy giặt', 50000.00, 'người/tháng', 'local_laundry_service', 'Sử dụng máy giặt chung không giới hạn theo tháng.', 0, 'per_person', 'other', 'person', 1, NULL, NULL, NULL, NULL);

-- Dumping structure for table manage.settings
DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.settings: ~38 rows (approximately)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
	('bank_account_holder', 'Nguyen Minh Anh', 'general', '2026-08-17 18:41:56'),
	('bank_account_number', '0011001234567', 'general', '2026-08-17 18:41:56'),
	('bank_name', 'Vietcombank', 'general', '2026-08-17 18:41:56'),
	('comment_edit_hours', '24', 'moderation', '2026-01-05 02:00:00'),
	('contact_address', '123 Đường Nguyễn Xiển, TP. Thủ Đức, TP.HCM', 'contact', '2026-01-05 02:00:00'),
	('contact_email', 'contact@nhatroxanh.vn', 'contact', '2026-01-05 02:00:00'),
	('contact_phone', '0901 000 001', 'contact', '2026-01-05 02:00:00'),
	('contact_zalo', '0901000001', 'contact', '2026-01-05 02:00:00'),
	('forgot_password_no_email_message', 'Tài khoản chưa đăng ký email. Vui lòng liên hệ quản lý để được hỗ trợ.', 'auth', '2026-01-05 02:00:00'),
	('gemini_api_key', 'AQ.Ab8RN6LMQF5wDAHkyWRf4PC6bn7spcxx1kxK2xfInVLYo6Zggg', 'moderation', '2026-08-17 22:32:40'),
	('hero_headline', 'Tìm phòng phù hợp với nhu cầu của bạn', 'hero', '2026-01-05 02:00:00'),
	('hero_headline_1', 'Xem Phòng Rõ', 'hero', '2026-01-05 02:00:00'),
	('hero_headline_2', 'Chọn Chỗ Ở Dễ', 'hero', '2026-01-05 02:00:00'),
	('hero_image', '/.uploads/image_page_home/home-hero-20260813-155451-f7859cd4.jpg', 'hero', '2026-08-15 17:10:14'),
	('hero_subheadline', 'Thông tin phòng, giá thuê và dịch vụ được cập nhật rõ ràng.', 'hero', '2026-01-05 02:00:00'),
	('min_days_to_review', '15', 'moderation', '2026-01-05 02:00:00'),
	('otp_length', '4', 'auth', '2026-01-05 02:00:00'),
	('otp_max_send_per_24h', '5', 'auth', '2026-01-05 02:00:00'),
	('otp_max_verify_attempts', '5', 'auth', '2026-01-05 02:00:00'),
	('otp_resend_seconds', '60', 'auth', '2026-01-05 02:00:00'),
	('otp_ttl_minutes', '2', 'auth', '2026-01-05 02:00:00'),
	('resend_api_key', 're_8rGXXvMj_8N7VB8fanWc6beCaSp9G1gsM', 'email', '2026-08-21 00:30:00'),
	('site_description', 'Hệ thống quản lý nhà trọ dành cho sinh viên và người đi làm tại TP.HCM.', 'brand', '2026-01-05 02:00:00'),
	('site_name', 'Nhà trọ Xanh', 'brand', '2026-01-05 02:00:00'),
	('site_slogan', 'Không gian sống tiện nghi, an toàn và minh bạch.', 'brand', '2026-01-05 02:00:00'),
	('smtp_encryption', 'tls', 'email', '2026-01-05 02:00:00'),
	('smtp_from_email', 'no-reply@nhatroxanh.vn', 'email', '2026-01-05 02:00:00'),
	('smtp_from_name', 'NhaTroXanh', 'email', '2026-01-05 02:00:00'),
	('smtp_host', 'smtp.example.com', 'email', '2026-01-05 02:00:00'),
	('smtp_password', 'your_smtp_password', 'email', '2026-01-05 02:00:00'),
	('smtp_port', '587', 'email', '2026-01-05 02:00:00'),
	('smtp_username', 'your_email@example.com', 'email', '2026-01-05 02:00:00'),
	('stat_1_label', 'Phòng đang mở xem', 'stats', '2026-08-15 17:18:59'),
	('stat_1_value', '18+', 'stats', '2026-08-15 17:18:59'),
	('stat_2_label', 'Khu vận hành ổn định', 'stats', '2026-08-15 17:18:59'),
	('stat_2_value', '3 khu', 'stats', '2026-08-15 17:18:59'),
	('stat_3_label', 'Hỗ trợ cư dân', 'stats', '2026-01-05 02:00:00'),
	('stat_3_value', '24/7', 'stats', '2026-01-05 02:00:00');

-- Dumping structure for table manage.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'HASH bcrypt',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Admin, 0=Tenant',
  `room_id` int unsigned DEFAULT NULL COMMENT 'Phòng đang ở',
  `date_of_birth` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `permanent_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_issue_date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_issue_place` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `fk_user_room` (`room_id`),
  CONSTRAINT `fk_user_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.users: ~9 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `avatar`, `role`, `room_id`, `date_of_birth`, `permanent_address`, `identity_number`, `identity_issue_date`, `identity_issue_place`, `created_at`) VALUES
	(1, 'Nguyễn Minh Anh', 'admin@nhatroxanh.vn', '0901000001', '$2y$10$jxpuLl0bJGI6Zor8PcekH.J2hcqtNMJ1GRITyo/c1yGYBfJmHb2iy', 'default.png', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-05 02:00:00'),
	(2, 'Trần Quốc Huy', 'manager@nhatroxanh.vn', '0901000002', '$2y$10$1.VoSqP8VJEtMUmFMwj1Iu7j./IuoZZptrwlYBQVXB55Y/eWDszhO', 'default.png', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-05 02:05:00'),
	(3, 'Phạm Gia Bảo', 'tenant01@example.com', '0912000001', '$2y$10$w67nsPC8.rgazBKwq/mS5.Z5G2fiPAJuKNXWkpZt/KSi6/7hhdSYq', 'default.png', 0, 1, NULL, NULL, NULL, NULL, NULL, '2026-01-08 03:00:00'),
	(4, 'Nguyễn Thùy Linh', 'tenant02@example.com', '0912000002', '$2y$10$1.VoSqP8VJEtMUmFMwj1Iu7j./IuoZZptrwlYBQVXB55Y/eWDszhO', 'default.png', 0, 2, NULL, NULL, NULL, NULL, NULL, '2026-01-09 03:00:00'),
	(5, 'Lê Hoàng Nam', 'tenant03@example.com', '0912000003', '$2y$10$1.VoSqP8VJEtMUmFMwj1Iu7j./IuoZZptrwlYBQVXB55Y/eWDszhO', 'default.png', 0, 3, NULL, NULL, NULL, NULL, NULL, '2026-01-10 03:00:00'),
	(6, 'Đỗ Khánh Vy', 'tenant04@example.com', '0912000004', '$2y$10$1.VoSqP8VJEtMUmFMwj1Iu7j./IuoZZptrwlYBQVXB55Y/eWDszhO', 'default.png', 0, 4, NULL, NULL, NULL, NULL, NULL, '2026-01-11 03:00:00'),
	(7, 'Vũ Đức Long', 'tenant05@example.com', '0912000005', '$2y$10$qGAR8FMJYgK4IdKkvkyZcuXba.z0jAN/7EUZOAu/dcZWHFMx65O7e', 'default.png', 0, 5, NULL, NULL, NULL, NULL, NULL, '2026-01-12 03:00:00'),
	(17, 'Lương Văn Dũng', 'dungls2k7@gmail.com', '0328528757', '$2y$10$cnKr3wWrDfTzOLaomr4q/.UCwlszc8VQ5a8KSgMk/Tcd54WEm1khS', 'default.png', 0, 8, NULL, NULL, NULL, NULL, NULL, '2026-08-17 19:55:24'),
	(18, 'Lương Văn Dũng', 'giagiong001@gmail.com', '0328528758', '$2y$10$YqWw1foeQYfUuNbJYWb3mOU/.fyUEVuV7E78AafMTTvBXIYcwe1oC', 'default.png', 0, 12, NULL, NULL, NULL, NULL, NULL, '2026-08-17 20:14:16');

-- Dumping structure for table manage.user_services
DROP TABLE IF EXISTS `user_services`;
CREATE TABLE IF NOT EXISTS `user_services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_service` (`user_id`,`service_id`),
  KEY `fk_us_service` (`service_id`),
  CONSTRAINT `fk_us_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.user_services: ~2 rows (approximately)
INSERT INTO `user_services` (`id`, `user_id`, `service_id`, `quantity`, `registered_at`) VALUES
	(8, 7, 4, 1, '2025-08-01 02:10:00'),
	(9, 7, 5, 1, '2025-08-01 02:10:00');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
