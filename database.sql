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


-- Dumping database structure for manage
CREATE DATABASE IF NOT EXISTS `manage` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `manage`;

-- Dumping structure for table manage.amenities
CREATE TABLE IF NOT EXISTS `amenities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.amenities: ~8 rows (approximately)
INSERT INTO `amenities` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
	(1, 'wifi', 'Wifi cáp quang', '', 7, 1),
	(2, 'security', 'An ninh 24/7', 'Camera giám sát HD, hệ thống vân tay ra vào, bảo vệ trực đêm', 6, 1),
	(3, 'local_parking', 'Chỗ để xe rộng', 'Bãi xe có mái che, sức chứa 50 xe máy, miễn phí', 5, 1),
	(4, 'local_laundry_service', 'Máy giặt Free', 'Máy giặt công nghiệp, sử dụng không giới hạn, có máy sấy', 4, 1),
	(5, 'ac_unit', 'Điều hòa mát lạnh', 'Điều hòa Inverter tiết kiệm điện, bảo trì định kỳ 3 tháng/lần', 3, 1),
	(6, 'kitchen', 'Bếp chung hiện đại', 'Bếp từ, lò vi sóng, tủ lạnh, đầy đủ dụng cụ nấu nướng', 2, 1),
	(7, 'elevator', 'Thang máy', 'Thang máy tải trọng 450kg, di chuyển thuận tiện, an toàn', 2, 1),
	(8, 'water_heater', 'Nóng lạnh 24/7', 'Máy nước nóng năng lượng mặt trời, tiết kiệm điện', 0, 0);

-- Dumping structure for table manage.areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã khu',
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên khu',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Địa chỉ khu',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả khu',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn ảnh khu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.areas: ~2 rows (approximately)
INSERT INTO `areas` (`id`, `name`, `address`, `description`, `image`, `created_at`) VALUES
	(1, 'Khu A - Sinh viên Cao cấp', '123 Đường ABC, Quận 9, TP.HCM', 'Khu cao cấp dành cho sinh viên FPT, an ninh tuyệt đối, đầy đủ tiện nghi, gần trường học và các tiện ích. Có 5 tầng với 15 phòng.', '/.uploads/image_khu_1/khu-20260813-051846-bd5f4c57.jpg', '2026-01-01 01:00:00'),
	(2, 'Khu B - Tiết kiệm', '125 Đường ABC, Quận 9, TP.HCM', 'Khu nhà giá hợp lý, phòng diện tích vừa phải, có sân để xe và không gian sinh hoạt chung.', '/.uploads/image_khu_2/khu-20260813-051834-f82edd68.jpg', '2026-01-01 01:00:00');

-- Dumping structure for table manage.banned_words
CREATE TABLE IF NOT EXISTS `banned_words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Từ/cụm từ cấm (ĐÃ CHUẨN HÓA)',
  `type` enum('word','phrase','abbreviation') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'word',
  `replacement` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '***',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.banned_words: ~10 rows (approximately)
INSERT INTO `banned_words` (`id`, `word`, `type`, `replacement`, `is_active`, `created_at`) VALUES
	(1, 'đụ', 'word', '***', 1, '2026-01-01 01:00:00'),
	(2, 'đéo', 'word', '***', 1, '2026-01-01 01:00:00'),
	(3, 'địt', 'word', '***', 1, '2026-01-01 01:00:00'),
	(4, 'mẹ', 'word', '***', 1, '2026-01-01 01:00:00'),
	(5, 'cha', 'word', '***', 1, '2026-01-01 01:00:00'),
	(6, 'chó', 'word', '***', 1, '2026-01-01 01:00:00'),
	(7, 'ngu', 'word', '***', 1, '2026-01-01 01:00:00'),
	(8, 'đần', 'word', '***', 1, '2026-01-01 01:00:00'),
	(9, 'lừa đảo', 'phrase', '***', 1, '2026-01-01 01:00:00'),
	(10, 'scam', 'word', '***', 1, '2026-01-01 01:00:00');

-- Dumping structure for table manage.comments
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comments: ~6 rows (approximately)
INSERT INTO `comments` (`id`, `room_id`, `user_id`, `content`, `rating`, `toxicity_score`, `is_spam`, `flagged_words`, `status`, `edited_at`, `created_at`) VALUES
	(1, 3, 2, 'Phòng rất tuyệt vời! Không gian rộng rãi, nội thất cao cấp, an ninh tốt. Chủ nhà nhiệt tình, hỗ trợ nhanh chóng. Sẽ giới thiệu cho bạn bè.', 5, 0.05, 0, NULL, 1, NULL, '2026-02-20 03:00:00'),
	(2, 5, 3, 'Phòng sạch sẽ, thoáng mát, giá cả hợp lý. Vị trí thuận tiện, gần trường học. Dịch vụ tốt, chủ nhà thân thiện.', 4, 0.08, 0, NULL, 1, NULL, '2026-02-25 07:00:00'),
	(3, 8, 4, 'Phòng gia đình rất phù hợp, không gian rộng cho 3 người. Tiện nghi đầy đủ, an ninh đảm bảo. Rất hài lòng!', 5, 0.03, 0, NULL, 1, NULL, '2026-03-05 02:00:00'),
	(4, 11, 2, 'Phòng tiêu chuẩn, đủ tiện nghi cơ bản. Giá cả phải chăng, phù hợp cho sinh viên. Chủ nhà tốt bụng.', 4, 0.06, 0, NULL, 1, NULL, '2026-03-10 04:00:00'),
	(5, 15, 3, 'Phòng rộng rãi, view đẹp từ tầng 5. Nội thất hiện đại, sạch sẽ. Dịch vụ tuyệt vời!', 5, 0.04, 0, NULL, 1, NULL, '2026-03-15 08:00:00'),
	(6, 17, 4, 'Phòng giá mềm, phù hợp với ngân sách sinh viên. Sạch sẽ, an ninh tốt. Chủ nhà nhiệt tình.', 4, 0.07, 0, NULL, 1, NULL, '2026-03-20 03:00:00');

-- Dumping structure for table manage.comment_moderation
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comment_moderation: ~0 rows (approximately)

-- Dumping structure for table manage.comment_reports
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comment_reports: ~0 rows (approximately)

-- Dumping structure for table manage.contracts
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.contracts: ~7 rows (approximately)
INSERT INTO `contracts` (`id`, `user_id`, `room_id`, `move_in_date`, `move_out_date`, `rent_price`, `deposit_amount`, `initial_electricity_index`, `initial_water_index`, `status`, `contract_date`, `created_at`) VALUES
	(1, 2, 3, '2026-01-15', NULL, 5500000.00, 11000000.00, 1000.00, 48.00, 'active', '2026-01-10', '2026-01-10 03:00:00'),
	(2, 3, 5, '2026-01-20', NULL, 3600000.00, 7200000.00, 1200.00, 52.00, 'active', '2026-01-15', '2026-01-15 07:00:00'),
	(3, 4, 8, '2026-02-01', NULL, 5000000.00, 10000000.00, 800.00, 36.00, 'active', '2026-01-25', '2026-01-25 02:00:00'),
	(4, 2, 11, '2026-02-10', NULL, 3600000.00, 7200000.00, 1500.00, 68.00, 'active', '2026-02-05', '2026-02-05 04:00:00'),
	(5, 3, 15, '2026-02-15', NULL, 5500000.00, 11000000.00, 900.00, 42.00, 'active', '2026-02-10', '2026-02-10 08:00:00'),
	(6, 4, 17, '2026-03-01', NULL, 2800000.00, 5600000.00, 600.00, 28.00, 'active', '2026-02-25', '2026-02-25 03:00:00'),
	(7, 2, 20, '2026-03-05', NULL, 3000000.00, 6000000.00, 1100.00, 50.00, 'active', '2026-03-01', '2026-03-01 02:00:00'),
	(8, 3, 24, '2026-03-10', NULL, 3100000.00, 6200000.00, 750.00, 34.00, 'active', '2026-03-05', '2026-03-05 07:00:00'),
	(19, 82, 13, '2026-11-11', NULL, 6500000.00, 0.00, NULL, NULL, 'active', '2026-08-15', '2026-08-15 08:31:18'),
	(20, 83, 13, '2026-08-15', NULL, 6500000.00, 0.00, NULL, NULL, 'active', '2026-08-15', '2026-08-15 09:03:08');

-- Dumping structure for table manage.feedbacks
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.feedbacks: ~0 rows (approximately)
INSERT INTO `feedbacks` (`id`, `user_id`, `room_id`, `subject`, `content`, `image`, `admin_note`, `admin_reply`, `status`, `created_at`) VALUES
	(2, 83, NULL, 'ddddd', 'đgggzsf', '/.uploads/image_feedback/feedback-20260815-110225-361af466.png', '', 'OKkkkk', 'resolved', '2026-08-15 11:02:25');

-- Dumping structure for table manage.floors
CREATE TABLE IF NOT EXISTS `floors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã tầng',
  `area_id` int unsigned NOT NULL COMMENT 'FK → areas',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor_number` int NOT NULL DEFAULT '1' COMMENT '0 = Tầng 1',
  `room_limit` int NOT NULL DEFAULT '0' COMMENT 'Giới hạn số phòng (0 = không giới hạn)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_floor_area` (`area_id`),
  CONSTRAINT `fk_floor_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.floors: ~8 rows (approximately)
INSERT INTO `floors` (`id`, `area_id`, `name`, `floor_number`, `room_limit`, `created_at`) VALUES
	(1, 1, 'Tầng 1 Khu A', 1, 0, '2026-01-01 01:00:00'),
	(2, 1, 'Tầng 2 Khu A', 2, 0, '2026-01-01 01:00:00'),
	(3, 1, 'Tầng 3 Khu A', 3, 0, '2026-01-01 01:00:00'),
	(4, 1, 'Tầng 4 Khu A', 4, 0, '2026-01-01 01:00:00'),
	(5, 1, 'Tầng 5 Khu A', 5, 0, '2026-01-01 01:00:00'),
	(6, 2, 'Tầng 1 Khu B', 0, 0, '2026-01-01 01:00:00'),
	(7, 2, 'Tầng 2 Khu B', 2, 0, '2026-01-01 01:00:00'),
	(8, 2, 'Tầng 3 Khu B', 3, 0, '2026-01-01 01:00:00');

-- Dumping structure for table manage.maintenance_requests
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.maintenance_requests: ~0 rows (approximately)

-- Dumping structure for table manage.meter_readings
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.meter_readings: ~3 rows (approximately)
INSERT INTO `meter_readings` (`id`, `room_id`, `service_id`, `month`, `year`, `old_index`, `new_index`, `created_at`) VALUES
	(1, 1, 1, 7, 2026, 1000.00, 1100.00, '2026-08-04 00:34:48'),
	(2, 1, 2, 7, 2026, 50.00, 60.00, '2026-08-04 00:34:48'),
	(3, 1, 1, 8, 2026, 1100.00, 1101.00, '2026-08-08 08:35:20'),
	(5, 3, 1, 8, 2026, 0.00, 1.00, '2026-08-13 04:34:39'),
	(6, 13, 1, 8, 2026, 0.00, 150.00, '2026-08-15 10:36:37');

-- Dumping structure for table manage.notifications
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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notifications: ~38 rows (approximately)
INSERT INTO `notifications` (`id`, `user_id`, `title`, `content`, `link`, `type`, `is_read`, `created_at`) VALUES
	(1, NULL, 'Thay đổi giá dịch vụ', 'Sạc xe điện: 100.000đ → 150.000đ/xe, áp dụng từ tháng 08/2026.', NULL, 'price_change', 0, '2026-08-04 00:34:48'),
	(2, NULL, 'Thay đổi giá điện', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-04 00:34:48'),
	(3, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 10/2026.', NULL, 'price_change', 0, '2026-08-09 04:08:40'),
	(4, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 50.000đ → 30.000đ/người, áp dụng từ tháng 10/2026.', NULL, 'price_change', 0, '2026-08-09 04:09:09'),
	(5, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 4.000đ → 9.000đ/kwh, áp dụng từ tháng 11/2026.', NULL, 'price_change', 0, '2026-08-09 04:18:18'),
	(6, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-09 09:28:46'),
	(7, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-09 09:59:17'),
	(8, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 30.000đ/người, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-09 10:00:45'),
	(9, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-09 10:32:51'),
	(10, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 06:44:51'),
	(11, NULL, 'Thay đổi giá dịch vụ', 'Sạc xe điện: 100.000đ → 10.000đ/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 06:46:30'),
	(12, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 10.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 08:17:18'),
	(13, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 10.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 10:18:38'),
	(14, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 10:23:29'),
	(15, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 10:54:21'),
	(16, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 51.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 10:54:49'),
	(17, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 51.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-10 13:35:28'),
	(18, NULL, 'Thay đổi giá dịch vụ', 'Lương Văn Dũng: 0đ → 1.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-11 15:45:57'),
	(19, NULL, 'Thay đổi giá dịch vụ', 'Tiền rác: 20.000đ → 30.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-11 15:46:52'),
	(20, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 35.000đ/người/tháng, áp dụng từ tháng 09/2026.', NULL, 'price_change', 0, '2026-08-11 16:11:37'),
	(21, 1, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Điều hòa', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:15:31'),
	(22, 2, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Điều hòa', '?page=admin-feedbacks', 'feedback', 1, '2026-08-13 01:15:31'),
	(23, 3, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Điều hòa', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:15:31'),
	(24, 4, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Điều hòa', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:15:31'),
	(25, 1, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: fsdf', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:20:22'),
	(26, 2, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: fsdf', '?page=admin-feedbacks', 'feedback', 1, '2026-08-13 01:20:22'),
	(27, 3, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: fsdf', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:20:22'),
	(28, 4, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: fsdf', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:20:22'),
	(29, 1, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Đây', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:37:47'),
	(30, 2, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Đây', '?page=admin-feedbacks', 'feedback', 1, '2026-08-13 01:37:47'),
	(31, 3, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Đây', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:37:47'),
	(32, 4, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: Đây', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 01:37:47'),
	(33, 2, 'Chủ trọ đã phản hồi phản ánh của bạn', 'Phản ánh "Đây": Oke', '?page=tenant-feedback', 'feedback', 1, '2026-08-13 01:39:13'),
	(38, 4, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: audit-test tiêu đề', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 15:47:04'),
	(39, 3, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: audit-test tiêu đề', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 15:47:04'),
	(40, 2, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: audit-test tiêu đề', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 15:47:04'),
	(41, 1, 'Phản ánh mới từ người thuê', 'Trần Văn Bình đã gửi phản ánh: audit-test tiêu đề', '?page=admin-feedbacks', 'feedback', 0, '2026-08-13 15:47:04'),
	(45, 1, 'Yêu cầu ở ghép mới', 'Test Tenant A mời Test Tenant B ở ghép tại phòng Phòng A201 - Economy. Cần admin duyệt.', '', 'general', 0, '2026-08-15 08:11:02'),
	(50, 1, 'Yêu cầu ở ghép mới', 'Test Tenant A mời Test Tenant B ở ghép tại phòng Phòng A201 - Economy. Cần admin duyệt.', '', 'general', 0, '2026-08-15 08:15:03'),
	(54, 2, 'Yêu cầu thuê phòng bị từ chối', 'Yêu cầu thuê phòng "Phòng A401 - Premium" của bạn đã bị từ chối. Lý do: Không. Bạn có thể gửi yêu cầu cho phòng khác.', '', 'general', 0, '2026-08-15 08:21:13'),
	(55, 82, 'Yêu cầu thuê phòng đã được duyệt', 'Chúc mừng! Yêu cầu thuê phòng "Phòng A501 - Penthouse" của bạn đã được admin duyệt. Ngày vào ở: 11/11/2026.', '', 'general', 0, '2026-08-15 08:31:18'),
	(56, 1, 'Yêu cầu ở ghép mới', 'Lương Văn Dũng mời Trần Văn Bình ở ghép tại phòng Phòng A501 - Penthouse. Cần admin duyệt.', '', 'general', 0, '2026-08-15 08:32:47'),
	(57, 2, 'Lời mời ở ghép', 'Lương Văn Dũng đã mời bạn ở ghép tại phòng Phòng A501 - Penthouse. Yêu cầu đang chờ admin duyệt.', '', 'general', 0, '2026-08-15 08:32:47'),
	(58, 2, 'Yêu cầu ở ghép bị từ chối', 'Admin đã từ chối yêu cầu ở ghép của bạn.', '', 'general', 0, '2026-08-15 08:54:40'),
	(59, 82, 'Yêu cầu mời ở ghép bị từ chối', 'Admin đã từ chối yêu cầu mời ở ghép tại phòng Phòng A501 - Penthouse.', '', 'general', 0, '2026-08-15 08:54:40'),
	(60, 1, 'Yêu cầu ở ghép mới', 'Lương Văn Dũng mời Huyền ở ghép tại phòng Phòng A501 - Penthouse. Cần admin duyệt.', '', 'general', 0, '2026-08-15 09:03:01'),
	(61, 83, 'Lời mời ở ghép', 'Lương Văn Dũng đã mời bạn ở ghép tại phòng Phòng A501 - Penthouse. Yêu cầu đang chờ admin duyệt.', '', 'general', 1, '2026-08-15 09:03:01'),
	(62, 83, 'Yêu cầu ở ghép đã được duyệt', 'Admin đã duyệt yêu cầu ở ghép của bạn tại phòng Phòng A501 - Penthouse.', '', 'general', 1, '2026-08-15 09:03:08'),
	(63, 82, 'Yêu cầu mời ở ghép được duyệt', 'Admin đã duyệt yêu cầu mời Huyền ở ghép tại phòng Phòng A501 - Penthouse.', '', 'general', 0, '2026-08-15 09:03:08'),
	(66, 1, 'Yêu cầu ở ghép mới', 'Lương Văn Dũng mời Phạm Đăng Khoa ở ghép tại phòng Phòng A501 - Penthouse. Cần admin duyệt.', '', 'general', 0, '2026-08-15 10:20:10'),
	(67, 4, 'Lời mời ở ghép', 'Lương Văn Dũng đã mời bạn ở ghép tại phòng Phòng A501 - Penthouse. Yêu cầu đang chờ admin duyệt.', '', 'general', 0, '2026-08-15 10:20:10'),
	(68, 83, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 1, '2026-08-15 11:02:25'),
	(69, 82, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 0, '2026-08-15 11:02:25'),
	(70, 4, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 0, '2026-08-15 11:02:25'),
	(71, 3, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 0, '2026-08-15 11:02:25'),
	(72, 2, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 0, '2026-08-15 11:02:25'),
	(73, 1, 'Phản ánh mới từ người thuê', 'Huyền đã gửi phản ánh: ddddd', '?page=admin-feedbacks', 'feedback', 0, '2026-08-15 11:02:25'),
	(74, 83, 'Chủ trọ đã phản hồi phản ánh của bạn', 'Phản ánh "ddddd": OKkkkk', '?page=tenant-feedback', 'feedback', 1, '2026-08-15 11:03:11'),
	(75, 4, 'Yêu cầu ở ghép bị từ chối', 'Admin đã từ chối yêu cầu ở ghép của bạn.', '', 'general', 0, '2026-08-15 11:14:55'),
	(76, 82, 'Yêu cầu mời ở ghép bị từ chối', 'Admin đã từ chối yêu cầu mời ở ghép tại phòng Phòng A501 - Penthouse.', '', 'general', 0, '2026-08-15 11:14:55'),
	(77, 1, 'Yêu cầu ở ghép mới', 'Lương Văn Dũng mời Trần Văn Bình ở ghép tại phòng Phòng A501 - Penthouse. Cần admin duyệt.', '', 'general', 0, '2026-08-15 11:16:27'),
	(78, 2, 'Lời mời ở ghép', 'Lương Văn Dũng đã mời bạn ở ghép tại phòng Phòng A501 - Penthouse. Yêu cầu đang chờ admin duyệt.', '', 'general', 0, '2026-08-15 11:16:27'),
	(79, 2, 'Yêu cầu ở ghép bị từ chối', 'Admin đã từ chối yêu cầu ở ghép của bạn.', '', 'general', 0, '2026-08-15 13:02:40'),
	(80, 82, 'Yêu cầu mời ở ghép bị từ chối', 'Admin đã từ chối yêu cầu mời ở ghép tại phòng Phòng A501 - Penthouse.', '', 'general', 0, '2026-08-15 13:02:40');

-- Dumping structure for table manage.notification_reads
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notification_reads: ~22 rows (approximately)
INSERT INTO `notification_reads` (`id`, `notification_id`, `user_id`, `read_at`) VALUES
	(1, 22, 2, '2026-08-13 01:15:39'),
	(2, 26, 2, '2026-08-13 01:30:37'),
	(3, 20, 2, '2026-08-13 01:30:44'),
	(4, 19, 2, '2026-08-13 01:30:44'),
	(5, 18, 2, '2026-08-13 01:30:44'),
	(6, 17, 2, '2026-08-13 01:30:44'),
	(7, 16, 2, '2026-08-13 01:30:44'),
	(8, 15, 2, '2026-08-13 01:30:44'),
	(9, 14, 2, '2026-08-13 01:30:44'),
	(10, 13, 2, '2026-08-13 01:30:44'),
	(11, 12, 2, '2026-08-13 01:30:44'),
	(12, 11, 2, '2026-08-13 01:30:44'),
	(13, 10, 2, '2026-08-13 01:30:44'),
	(14, 9, 2, '2026-08-13 01:30:44'),
	(15, 8, 2, '2026-08-13 01:30:44'),
	(16, 7, 2, '2026-08-13 01:30:44'),
	(17, 6, 2, '2026-08-13 01:30:44'),
	(18, 5, 2, '2026-08-13 01:30:44'),
	(19, 4, 2, '2026-08-13 01:30:44'),
	(20, 3, 2, '2026-08-13 01:30:44'),
	(21, 2, 2, '2026-08-13 01:30:44'),
	(22, 1, 2, '2026-08-13 01:30:44'),
	(23, 30, 2, '2026-08-13 01:39:31'),
	(24, 33, 2, '2026-08-13 01:39:38'),
	(26, 62, 83, '2026-08-15 09:25:43'),
	(27, 61, 83, '2026-08-15 09:25:43'),
	(28, 20, 83, '2026-08-15 09:25:43'),
	(29, 19, 83, '2026-08-15 09:25:43'),
	(30, 18, 83, '2026-08-15 09:25:43'),
	(31, 17, 83, '2026-08-15 09:25:43'),
	(32, 16, 83, '2026-08-15 09:25:43'),
	(33, 15, 83, '2026-08-15 09:25:43'),
	(34, 14, 83, '2026-08-15 09:25:43'),
	(35, 13, 83, '2026-08-15 09:25:43'),
	(36, 12, 83, '2026-08-15 09:25:43'),
	(37, 11, 83, '2026-08-15 09:25:43'),
	(38, 10, 83, '2026-08-15 09:25:43'),
	(39, 9, 83, '2026-08-15 09:25:43'),
	(40, 8, 83, '2026-08-15 09:25:43'),
	(41, 7, 83, '2026-08-15 09:25:43'),
	(42, 6, 83, '2026-08-15 09:25:43'),
	(43, 5, 83, '2026-08-15 09:25:43'),
	(44, 4, 83, '2026-08-15 09:25:43'),
	(45, 3, 83, '2026-08-15 09:25:43'),
	(46, 2, 83, '2026-08-15 09:25:43'),
	(47, 1, 83, '2026-08-15 09:25:43'),
	(48, 68, 83, '2026-08-15 11:02:30'),
	(50, 74, 83, '2026-08-15 11:03:47');

-- Dumping structure for table manage.password_reset_otps
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.password_reset_otps: ~0 rows (approximately)

-- Dumping structure for table manage.password_reset_send_attempts
CREATE TABLE IF NOT EXISTS `password_reset_send_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_send_user_ip_time` (`user_id`,`ip`,`sent_at`),
  CONSTRAINT `fk_send_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.password_reset_send_attempts: ~0 rows (approximately)

-- Dumping structure for table manage.payments
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payments: ~0 rows (approximately)
INSERT INTO `payments` (`id`, `room_id`, `contract_id`, `user_id`, `month`, `year`, `amount`, `status`, `paid_at`, `created_at`) VALUES
	(2, 13, 20, 82, 8, 2026, 7125000.00, 'paid', '2026-08-15 03:38:21', '2026-08-15 10:37:22');

-- Dumping structure for table manage.payment_items
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payment_items: ~0 rows (approximately)
INSERT INTO `payment_items` (`id`, `payment_id`, `service_id`, `item_name`, `unit_price`, `quantity`, `amount`, `billing_mode`, `created_at`) VALUES
	(8, 2, NULL, 'Tiền phòng', 6500000.00, 1.00, 6500000.00, 'fixed', '2026-08-15 10:37:22'),
	(9, 2, 2, 'Tiền nước', 30000.00, 2.00, 60000.00, 'per_person', '2026-08-15 10:37:22'),
	(10, 2, 3, 'Tiền rác', 20000.00, 2.00, 40000.00, 'per_person', '2026-08-15 10:37:22'),
	(11, 2, 1, 'Tiền điện', 3500.00, 150.00, 525000.00, 'meter', '2026-08-15 10:37:22');

-- Dumping structure for table manage.price_changes
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.price_changes: ~0 rows (approximately)

-- Dumping structure for table manage.rental_requests
CREATE TABLE IF NOT EXISTS `rental_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `room_id` int unsigned NOT NULL,
  `move_in_date` date NOT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupant_count` tinyint NOT NULL DEFAULT '1',
  `status` enum('pending','approved','rejected','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  KEY `idx_rr_room` (`room_id`),
  KEY `idx_rr_status` (`status`),
  CONSTRAINT `fk_rr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rental_requests: ~0 rows (approximately)
INSERT INTO `rental_requests` (`id`, `user_id`, `room_id`, `move_in_date`, `gender`, `occupant_count`, `status`, `admin_note`, `created_at`, `updated_at`) VALUES
	(5, 2, 10, '2026-11-11', 'male', 1, 'rejected', 'Không', '2026-08-15 07:23:26', '2026-08-15 08:21:13'),
	(15, 82, 13, '2026-11-11', 'male', 1, 'approved', 'Yêu cầu đã được duyệt.', '2026-08-15 08:31:09', '2026-08-15 08:31:18');

-- Dumping structure for table manage.roommate_requests
CREATE TABLE IF NOT EXISTS `roommate_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` int unsigned NOT NULL COMMENT 'Người B — gửi yêu cầu',
  `host_user_id` int unsigned NOT NULL COMMENT 'Người A — đang ở phòng',
  `room_id` int unsigned NOT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.roommate_requests: ~0 rows (approximately)
INSERT INTO `roommate_requests` (`id`, `requester_id`, `host_user_id`, `room_id`, `gender`, `relationship`, `status`, `created_at`, `updated_at`) VALUES
	(3, 2, 82, 13, 'male', 'Ai biết', 'rejected', '2026-08-15 08:32:47', '2026-08-15 08:54:40'),
	(4, 83, 82, 13, 'female', 'Ai biết', 'approved', '2026-08-15 09:03:01', '2026-08-15 09:03:08'),
	(5, 4, 82, 13, 'other', 'ban be', 'rejected', '2026-08-15 10:20:10', '2026-08-15 11:14:55'),
	(6, 2, 82, 13, 'male', 'bạn bè', 'rejected', '2026-08-15 11:16:27', '2026-08-15 13:02:40');

-- Dumping structure for table manage.rooms
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rooms: ~25 rows (approximately)
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `position`, `price`, `area`, `max_occupancy`, `description`, `amenities`, `thumbnail`, `status`, `notice_given`, `expected_vacant_date`, `views`, `created_at`) VALUES
	(1, 1, 'Phòng A101 - Studio Deluxe', 1, 4500000.00, 30.00, 2, 'Phòng studio cao cấp 30m² với ban công rộng, view đẹp. Thiết kế hiện đại với nội thất đầy đủ: giường đôi, tủ quần áo âm tường, bàn học, ghế ergonomic. Sàn gỗ cao cấp, tường sơn nước chống thấm. Nhà vệ sinh khép kín với thiết bị vệ sinh cao cấp Toto. Có máy lạnh Inverter, bình nóng lạnh. Phù hợp cho cặp đôi hoặc người đi làm muốn không gian riêng tư, tiện nghi.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 256, '2026-01-01 01:00:00'),
	(2, 1, 'Phòng A102 - Standard Plus', 2, 3800000.00, 25.00, 2, 'Phòng tiêu chuẩn 25m² với cửa sổ lớn đón ánh sáng tự nhiên. Nội thất cơ bản: giường đơn, tủ quần áo, bàn học. Sàn gạch men sạch sẽ, tường sơn trắng sáng. Nhà vệ sinh khép kín. Có quạt trần, ổ cắm đầy đủ. Không gian thoáng mát, yên tĩnh, phù hợp cho sinh viên hoặc người đi làm.', 'dieu_hoa, nong_lanh, tu_lanh, giuong, ban_ghe', '/.uploads/image_phong_2/phong-2-20260813-052047-88c08763.jpg', 'available', 0, NULL, 192, '2026-01-01 01:00:00'),
	(3, 1, 'Phòng A103 - Premium Suite', 3, 5500000.00, 35.00, 3, 'Phòng suite cao cấp 35m² với ban công rộng và view công viên. Thiết kế sang trọng với nội thất cao cấp: giường king size, sofa, tủ quần áo lớn, bàn làm việc rộng. Sàn gỗ óc chó, tường ốp gỗ trang trí. Nhà vệ sinh rộng với bồn tắm đứng. Có máy lạnh, bình nóng lạnh, tủ lạnh mini. Phù hợp cho gia đình nhỏ hoặc nhóm bạn.', 'dieu_hoa, nong_lanh, tu_lanh, giuong, ban_ghe', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 312, '2026-01-01 01:00:00'),
	(4, 2, 'Phòng A201 - Economy', 1, 3200000.00, 22.00, 2, 'Phòng tiết kiệm 22m², phù hợp cho sinh viên có ngân sách hạn chế. Nội thất cơ bản: giường tầng, tủ cá nhân, bàn học chung. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín. Có quạt trần, đèn LED tiết kiệm điện. Không gian gọn gàng, sạch sẽ.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 159, '2026-01-01 01:00:00'),
	(5, 2, 'Phòng A202 - Standard', 2, 3600000.00, 25.00, 2, 'Phòng tiêu chuẩn 25m² với cửa sổ thoáng. Nội thất: giường đôi, tủ quần áo, bàn học. Sàn gạch men cao cấp, tường sơn nước. Nhà vệ sinh khép kín với bình nóng lạnh. Có máy lạnh, quạt trần. Không gian yên tĩnh, phù hợp học tập và làm việc.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 201, '2026-01-01 01:00:00'),
	(6, 2, 'Phòng A203 - Deluxe', 3, 4200000.00, 28.00, 2, 'Phòng deluxe 28m² với ban công nhỏ. Thiết kế hiện đại, nội thất đầy đủ: giường đôi, tủ quần áo âm tường, bàn học, ghế xoay. Sàn gỗ, tường sơn pastel. Nhà vệ sinh khép kín cao cấp. Có máy lạnh Inverter, bình nóng lạnh. Không gian thoáng đãng, view đẹp.', '["wifi","security","local_parking","local_laundry_service","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 180, '2026-01-01 01:00:00'),
	(7, 3, 'Phòng A301 - Studio', 1, 4000000.00, 28.00, 2, 'Phòng studio 28m² với thiết kế mở, không gian rộng rãi. Nội thất: giường đôi, tủ quần áo, bàn làm việc. Sàn gỗ, tường sơn trắng. Nhà vệ sinh khép kín. Có máy lạnh, bình nóng lạnh. Cửa sổ lớn đón ánh sáng tự nhiên.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 168, '2026-01-01 01:00:00'),
	(8, 3, 'Phòng A302 - Family', 2, 5000000.00, 32.00, 3, 'Phòng gia đình 32m², phù hợp cho 3 người. Nội thất: giường đôi + giường đơn, tủ quần áo lớn, bàn học. Sàn gỗ cao cấp, tường trang trí. Nhà vệ sinh rộng. Có máy lạnh, bình nóng lạnh, tủ lạnh. Không gian ấm cúng, tiện nghi.', '["wifi","security","local_parking","local_laundry_service","ac_unit","kitchen","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 234, '2026-01-01 01:00:00'),
	(9, 3, 'Phòng A303 - Standard Plus', 3, 3800000.00, 25.00, 2, 'Phòng tiêu chuẩn 25m² với ban công. Nội thất đầy đủ: giường đôi, tủ, bàn học. Sàn gạch men, tường sơn nước. Nhà vệ sinh khép kín. Có máy lạnh, quạt trần. Không gian thoáng mát, yên tĩnh.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 146, '2026-01-01 01:00:00'),
	(10, 4, 'Phòng A401 - Premium', 1, 4800000.00, 30.00, 2, 'Phòng premium 30m² với view toàn cảnh thành phố. Thiết kế sang trọng, nội thất cao cấp: giường king, sofa, tủ âm tường. Sàn gỗ óc chó, tường ốp gỗ. Nhà vệ sinh cao cấp với bồn tắm. Có máy lạnh, bình nóng lạnh, tủ lạnh mini.', '["wifi","security","local_parking","local_laundry_service","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 277, '2026-01-01 01:00:00'),
	(11, 4, 'Phòng A402 - Standard', 2, 3600000.00, 25.00, 2, 'Phòng tiêu chuẩn 25m², thiết kế đơn giản nhưng đầy đủ tiện nghi. Nội thất: giường đôi, tủ, bàn học. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín. Có máy lạnh, bình nóng lạnh. Phù hợp cho sinh viên.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 189, '2026-01-01 01:00:00'),
	(12, 4, 'Phòng A403 - Deluxe Plus', 3, 4500000.00, 28.00, 2, 'Phòng deluxe 28m² với ban công rộng. Nội thất cao cấp: giường đôi, tủ âm tường, bàn làm việc. Sàn gỗ, tường trang trí. Nhà vệ sinh khép kín cao cấp. Có máy lạnh Inverter, bình nóng lạnh. Không gian thoáng đãng.', '["wifi","security","local_parking","local_laundry_service","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 158, '2026-01-01 01:00:00'),
	(13, 5, 'Phòng A501 - Penthouse', 1, 6500000.00, 40.00, 4, 'Phòng penthouse 40m² trên tầng cao nhất, view toàn cảnh. Thiết kế luxury với nội thất cao cấp: 2 giường đôi, sofa lớn, tủ quần áo walk-in. Sàn gỗ cao cấp, tường ốp đá trang trí. Nhà vệ sinh rộng với bồn tắm và vòi sen riêng. Có máy lạnh, bình nóng lạnh, tủ lạnh, lò vi sóng. Không gian sang trọng, riêng tư.', '["wifi","security","local_parking","local_laundry_service","ac_unit","kitchen","elevator","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 410, '2026-01-01 01:00:00'),
	(14, 5, 'Phòng A502 - Studio View', 2, 4800000.00, 30.00, 2, 'Phòng studio 30m² với view đẹp từ tầng 5. Nội thất đầy đủ: giường đôi, tủ, bàn làm việc. Sàn gỗ, tường sơn pastel. Nhà vệ sinh khép kín. Có máy lạnh, bình nóng lạnh. Không gian yên tĩnh, phù hợp làm việc.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 201, '2026-01-01 01:00:00'),
	(15, 5, 'Phòng A503 - Family Plus', 3, 5500000.00, 35.00, 3, 'Phòng gia đình 35m², phù hợp cho 3-4 người. Nội thất: giường đôi + giường đơn, tủ lớn, bàn học. Sàn gỗ cao cấp, tường trang trí. Nhà vệ sinh rộng. Có máy lạnh, bình nóng lạnh, tủ lạnh. Không gian rộng rãi, tiện nghi.', '["wifi","security","local_parking","local_laundry_service","ac_unit","kitchen","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 278, '2026-01-01 01:00:00'),
	(16, 6, 'Phòng B001 - Budget', 1, 2500000.00, 18.00, 2, 'Phòng tiết kiệm 18m² ở Tầng 1, giá mềm. Nội thất cơ bản: giường tầng, tủ cá nhân. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín đơn giản. Có quạt trần, ổ cắm. Phù hợp cho sinh viên có ngân sách hạn chế.', '["wifi","security","local_parking"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 134, '2026-01-01 01:00:00'),
	(17, 6, 'Phòng B002 - Economy', 2, 2800000.00, 20.00, 2, 'Phòng economy 20m², thiết kế gọn gàng. Nội thất: giường đôi, tủ nhỏ. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín. Có quạt trần, bình nóng lạnh. Không gian sạch sẽ, thoáng mát.', 'nong_lanh, giuong, ban_ghe, ["wifi", "security", "local_parking", "water_heater"]', '/.uploads/image_phong_17/phong-17-20260813-051949-f66cce68.jpg', 'rented', 0, NULL, 167, '2026-01-01 01:00:00'),
	(18, 6, 'Phòng B003 - Standard', 3, 3000000.00, 22.00, 2, 'Phòng tiêu chuẩn 22m² với sân nhỏ phía trước. Nội thất: giường đôi, tủ quần áo, bàn học. Sàn gạch men, tường sơn nước. Nhà vệ sinh khép kín. Có quạt trần, bình nóng lạnh. Không gian thoáng đãng, có chỗ phơi đồ.', 'dieu_hoa, nong_lanh, giuong, ban_ghe', '/.uploads/image_phong_18/phong-18-20260813-155542-25b9dccd.jpg', 'available', 0, NULL, 147, '2026-01-01 01:00:00'),
	(19, 7, 'Phòng B201 - Budget Plus', 1, 2700000.00, 20.00, 2, 'Phòng budget 20m² ở tầng 2. Nội thất cơ bản: giường đôi, tủ nhỏ, bàn học. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín. Có quạt trần, bình nóng lạnh. Không gian yên tĩnh, phù hợp học tập.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 125, '2026-01-01 01:00:00'),
	(20, 7, 'Phòng B202 - Economy Plus', 2, 3000000.00, 22.00, 2, 'Phòng economy 22m² với ban công nhỏ. Nội thất: giường đôi, tủ quần áo, bàn học. Sàn gạch men, tường sơn nước. Nhà vệ sinh khép kín. Có quạt trần, bình nóng lạnh. Không gian thoáng mát, view sân vườn.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 156, '2026-01-01 01:00:00'),
	(21, 7, 'Phòng B203 - Standard', 3, 3200000.00, 24.00, 2, 'Phòng tiêu chuẩn 24m², thiết kế đơn giản. Nội thất: giường đôi, tủ, bàn học. Sàn gạch men cao cấp, tường sơn nước. Nhà vệ sinh khép kín. Có máy lạnh, bình nóng lạnh. Không gian gọn gàng, sạch sẽ.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 136, '2026-01-01 01:00:00'),
	(22, 8, 'Phòng B301 - Economy', 1, 2900000.00, 21.00, 2, 'Phòng economy 21m² ở tầng 3. Nội thất: giường đôi, tủ nhỏ. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín. Có quạt trần, bình nóng lạnh. Không gian yên tĩnh, thoáng mát.', '["wifi","security","local_parking","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 112, '2026-01-01 01:00:00'),
	(23, 8, 'Phòng B302 - Budget', 2, 2600000.00, 19.00, 2, 'Phòng budget 19m², giá tiết kiệm. Nội thất cơ bản: giường tầng, tủ cá nhân. Sàn gạch men, tường sơn trắng. Nhà vệ sinh khép kín đơn giản. Có quạt trần. Phù hợp cho sinh viên.', '["wifi","security","local_parking"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'available', 0, NULL, 98, '2026-01-01 01:00:00'),
	(24, 8, 'Phòng B303 - Standard Plus', 3, 3100000.00, 23.00, 2, 'Phòng tiêu chuẩn 23m² với ban công. Nội thất: giường đôi, tủ quần áo, bàn học. Sàn gạch men, tường sơn nước. Nhà vệ sinh khép kín. Có máy lạnh, bình nóng lạnh. Không gian thoáng đãng, view đẹp.', '["wifi","security","local_parking","ac_unit","water_heater"]', '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 'rented', 0, NULL, 145, '2026-01-01 01:00:00'),
	(37, 7, 'B111', 4, 2500000.00, 30.00, 2, 'aaaa', 'dieu_hoa, nong_lanh', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900', 'available', 0, NULL, 0, '2026-08-13 06:08:13');

-- Dumping structure for table manage.room_images
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
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_images: ~74 rows (approximately)
INSERT INTO `room_images` (`id`, `room_id`, `image_url`, `is_primary`, `sort_order`, `created_at`) VALUES
	(1, 1, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-3.jpg', 1, 0, '2026-01-01 01:00:00'),
	(2, 1, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-4.jpg', 0, 1, '2026-01-01 01:00:00'),
	(3, 1, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-5.jpg', 0, 2, '2026-01-01 01:00:00'),
	(10, 4, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-6.jpg', 1, 0, '2026-01-01 01:00:00'),
	(11, 4, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-7.jpg', 0, 1, '2026-01-01 01:00:00'),
	(12, 4, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-8.jpg', 0, 2, '2026-01-01 01:00:00'),
	(13, 5, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-7.jpg', 1, 0, '2026-01-01 01:00:00'),
	(14, 5, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-8.jpg', 0, 1, '2026-01-01 01:00:00'),
	(15, 5, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-9.jpg', 0, 2, '2026-01-01 01:00:00'),
	(16, 6, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-8.jpg', 1, 0, '2026-01-01 01:00:00'),
	(17, 6, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-9.jpg', 0, 1, '2026-01-01 01:00:00'),
	(18, 6, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-10.jpg', 0, 2, '2026-01-01 01:00:00'),
	(19, 7, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-9.jpg', 1, 0, '2026-01-01 01:00:00'),
	(20, 7, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-10.jpg', 0, 1, '2026-01-01 01:00:00'),
	(21, 7, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-11.jpg', 0, 2, '2026-01-01 01:00:00'),
	(22, 8, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-10.jpg', 1, 0, '2026-01-01 01:00:00'),
	(23, 8, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-11.jpg', 0, 1, '2026-01-01 01:00:00'),
	(24, 8, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-12.jpg', 0, 2, '2026-01-01 01:00:00'),
	(25, 9, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-11.jpg', 1, 0, '2026-01-01 01:00:00'),
	(26, 9, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-12.jpg', 0, 1, '2026-01-01 01:00:00'),
	(27, 9, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-13.jpg', 0, 2, '2026-01-01 01:00:00'),
	(28, 10, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-12.jpg', 1, 0, '2026-01-01 01:00:00'),
	(29, 10, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-13.jpg', 0, 1, '2026-01-01 01:00:00'),
	(30, 10, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-14.jpg', 0, 2, '2026-01-01 01:00:00'),
	(31, 11, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-13.jpg', 1, 0, '2026-01-01 01:00:00'),
	(32, 11, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-14.jpg', 0, 1, '2026-01-01 01:00:00'),
	(33, 11, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-15.jpg', 0, 2, '2026-01-01 01:00:00'),
	(34, 12, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-14.jpg', 1, 0, '2026-01-01 01:00:00'),
	(35, 12, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-15.jpg', 0, 1, '2026-01-01 01:00:00'),
	(36, 12, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-16.jpg', 0, 2, '2026-01-01 01:00:00'),
	(37, 13, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-15.jpg', 1, 0, '2026-01-01 01:00:00'),
	(38, 13, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-16.jpg', 0, 1, '2026-01-01 01:00:00'),
	(39, 13, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-17.jpg', 0, 2, '2026-01-01 01:00:00'),
	(40, 14, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-16.jpg', 1, 0, '2026-01-01 01:00:00'),
	(41, 14, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-17.jpg', 0, 1, '2026-01-01 01:00:00'),
	(42, 14, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-18.jpg', 0, 2, '2026-01-01 01:00:00'),
	(43, 15, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-17.jpg', 1, 0, '2026-01-01 01:00:00'),
	(44, 15, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-18.jpg', 0, 1, '2026-01-01 01:00:00'),
	(45, 15, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-19.jpg', 0, 2, '2026-01-01 01:00:00'),
	(46, 16, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-18.jpg', 1, 0, '2026-01-01 01:00:00'),
	(47, 16, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-19.jpg', 0, 1, '2026-01-01 01:00:00'),
	(48, 16, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-20.jpg', 0, 2, '2026-01-01 01:00:00'),
	(55, 19, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-21.jpg', 1, 0, '2026-01-01 01:00:00'),
	(56, 19, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-22.jpg', 0, 1, '2026-01-01 01:00:00'),
	(57, 19, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-23.jpg', 0, 2, '2026-01-01 01:00:00'),
	(58, 20, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-22.jpg', 1, 0, '2026-01-01 01:00:00'),
	(59, 20, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-23.jpg', 0, 1, '2026-01-01 01:00:00'),
	(60, 20, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-24.jpg', 0, 2, '2026-01-01 01:00:00'),
	(61, 21, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-23.jpg', 1, 0, '2026-01-01 01:00:00'),
	(62, 21, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-24.jpg', 0, 1, '2026-01-01 01:00:00'),
	(63, 21, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-25.jpg', 0, 2, '2026-01-01 01:00:00'),
	(64, 22, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-24.jpg', 1, 0, '2026-01-01 01:00:00'),
	(65, 22, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-25.jpg', 0, 1, '2026-01-01 01:00:00'),
	(66, 22, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-26.jpg', 0, 2, '2026-01-01 01:00:00'),
	(67, 23, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-25.jpg', 1, 0, '2026-01-01 01:00:00'),
	(68, 23, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-26.jpg', 0, 1, '2026-01-01 01:00:00'),
	(69, 23, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-1.jpg', 0, 2, '2026-01-01 01:00:00'),
	(70, 24, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-26.jpg', 1, 0, '2026-01-01 01:00:00'),
	(71, 24, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-1.jpg', 0, 1, '2026-01-01 01:00:00'),
	(72, 24, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-2.jpg', 0, 2, '2026-01-01 01:00:00'),
	(77, 17, '/.uploads/image_phong_17/phong-17-20260813-051949-f66cce68.jpg', 1, 0, '2026-08-13 05:19:58'),
	(78, 17, '/.uploads/image_phong_17/phong-17-20260813-051951-f9c5b3de.jpg', 0, 1, '2026-08-13 05:19:58'),
	(79, 17, '/.uploads/image_phong_17/phong-17-20260813-051954-dc66c3d7.jpg', 0, 2, '2026-08-13 05:19:58'),
	(80, 17, '/.uploads/image_phong_17/phong-17-20260813-051957-81fa6043.jpg', 0, 3, '2026-08-13 05:19:58'),
	(84, 2, '/.uploads/image_phong_2/phong-2-20260813-052047-88c08763.jpg', 1, 0, '2026-08-13 05:21:50'),
	(85, 2, 'https://xaydungaau.com/wp-content/uploads/2021/05/thiet-ke-noi-that-phong-tro-dep-5.jpg', 0, 1, '2026-08-13 05:21:50'),
	(86, 2, '/.uploads/image_phong_2/phong-2-20260813-052146-fe821e35.jpg', 0, 2, '2026-08-13 05:21:50'),
	(91, 3, '/.uploads/image_phong_3/phong-3-20260813-052016-ea3e3628.jpg', 1, 0, '2026-08-13 05:27:32'),
	(92, 3, '/.uploads/image_phong_3/phong-3-20260813-052020-52fd4df6.jpg', 0, 1, '2026-08-13 05:27:32'),
	(93, 3, '/.uploads/image_phong_3/phong-3-20260813-052023-32596745.jpg', 0, 2, '2026-08-13 05:27:32'),
	(94, 3, '/.uploads/image_phong_3/phong-3-20260813-052731-b9fe5bed.jpg', 0, 3, '2026-08-13 05:27:32'),
	(99, 18, '/.uploads/image_phong_18/phong-18-20260813-155542-25b9dccd.jpg', 1, 0, '2026-08-13 15:56:21'),
	(100, 18, '/.uploads/image_phong_18/phong-18-20260813-051928-39389a3d.jpg', 0, 1, '2026-08-13 15:56:21'),
	(101, 18, '/.uploads/image_phong_18/phong-18-20260813-051931-11b54732.jpg', 0, 2, '2026-08-13 15:56:21'),
	(102, 18, '/.uploads/image_phong_18/phong-18-20260813-051934-2640b12b.jpg', 0, 3, '2026-08-13 15:56:21');

-- Dumping structure for table manage.room_price_changes
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_price_changes: ~0 rows (approximately)

-- Dumping structure for table manage.room_services
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_services: ~41 rows (approximately)
INSERT INTO `room_services` (`id`, `room_id`, `service_id`, `quantity`, `registered_at`) VALUES
	(1, 3, 1, 1, '2026-01-15 03:00:00'),
	(2, 3, 2, 3, '2026-01-15 03:00:00'),
	(3, 3, 3, 3, '2026-01-15 03:00:00'),
	(7, 5, 1, 1, '2026-01-20 07:00:00'),
	(8, 5, 2, 2, '2026-01-20 07:00:00'),
	(9, 5, 3, 2, '2026-01-20 07:00:00'),
	(12, 8, 1, 1, '2026-02-01 02:00:00'),
	(13, 8, 2, 3, '2026-02-01 02:00:00'),
	(14, 8, 3, 3, '2026-02-01 02:00:00'),
	(18, 11, 1, 1, '2026-02-10 04:00:00'),
	(19, 11, 2, 2, '2026-02-10 04:00:00'),
	(20, 11, 3, 2, '2026-02-10 04:00:00'),
	(22, 15, 1, 1, '2026-02-15 08:00:00'),
	(23, 15, 2, 3, '2026-02-15 08:00:00'),
	(24, 15, 3, 3, '2026-02-15 08:00:00'),
	(28, 17, 1, 1, '2026-03-01 03:00:00'),
	(29, 17, 2, 2, '2026-03-01 03:00:00'),
	(30, 17, 3, 2, '2026-03-01 03:00:00'),
	(32, 20, 1, 1, '2026-03-05 02:00:00'),
	(33, 20, 2, 2, '2026-03-05 02:00:00'),
	(34, 20, 3, 2, '2026-03-05 02:00:00'),
	(37, 24, 1, 1, '2026-03-10 07:00:00'),
	(38, 24, 2, 2, '2026-03-10 07:00:00'),
	(39, 24, 3, 2, '2026-03-10 07:00:00');

-- Dumping structure for table manage.services
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.services: ~7 rows (approximately)
INSERT INTO `services` (`id`, `name`, `price`, `unit`, `icon`, `description`, `is_required`, `billing_mode`, `kind`, `applies_to`, `is_active`, `delete_year`, `delete_month`, `deactivate_month`, `deactivate_year`) VALUES
	(1, 'Tiền điện', 3500.00, 'kWh', 'bolt', 'Tính theo chỉ số công tơ, giá nhà nước', 1, 'meter', 'electricity', 'room', 1, NULL, NULL, NULL, NULL),
	(2, 'Tiền nước', 30000.00, 'người/tháng', 'water_drop', 'Mặc định theo đầu người', 1, 'per_person', 'water', 'room', 1, NULL, NULL, NULL, NULL),
	(3, 'Tiền rác', 20000.00, 'người/tháng', 'delete', 'Phí thu gom rác theo đầu người', 1, 'per_person', 'trash', 'room', 1, NULL, NULL, NULL, NULL),
	(4, 'Wifi', 51000.00, 'người/tháng', 'wifi', 'Internet cáp quang tốc độ cao 200Mbps', 0, 'per_person', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(5, 'Giữ xe', 100000.00, 'xe/tháng', 'two_wheeler', 'Phí giữ xe máy có mái che', 0, 'fixed', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(6, 'Vệ sinh', 50000.00, 'phòng/tháng', 'cleaning_services', 'Dọn vệ sinh hành lang, cầu thang 2 lần/tuần', 0, 'fixed', 'other', 'person', 1, NULL, NULL, NULL, NULL),
	(7, 'Máy giặt', 50000.00, 'người/tháng', 'local_laundry_service', 'Máy giặt chung, sử dụng không giới hạn', 0, 'per_person', 'other', 'person', 1, NULL, NULL, NULL, NULL);

-- Dumping structure for table manage.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.settings: ~39 rows (approximately)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
	('comment_edit_hours', '24', 'moderation', '2026-01-01 01:00:00'),
	('comment_lock_hours', '24', 'moderation', '2026-01-01 01:00:00'),
	('contact_address', '123 Đường ABC, Quận 9, TP.HCM', 'contact', '2026-01-01 01:00:00'),
	('contact_email', 'contact@nhatroa.vn', 'contact', '2026-01-01 01:00:00'),
	('contact_phone', '0901 234 567', 'contact', '2026-01-01 01:00:00'),
	('contact_zalo', '0901234567', 'contact', '2026-01-01 01:00:00'),
	('enable_comment_moderation', '1', 'moderation', '2026-08-13 04:41:09'),
	('enable_gemini_moderation', '0', 'moderation', '2026-08-13 04:41:09'),
	('forgot_password_no_email_message', 'Tài khoản của bạn chưa đăng ký email. Vui lòng liên hệ chủ trọ để được cấp lại mật khẩu.', 'auth', '2026-08-14 04:34:14'),
	('gemini_api_key', '', 'moderation', '2026-08-13 04:41:09'),
	('hero_headline', 'Tìm phòng trọ mơ ước của bạn', 'hero', '2026-01-01 01:00:00'),
	('hero_headline_1', 'Xem Phòng Rõ', 'hero', '2026-08-13 04:41:09'),
	('hero_headline_2', 'Chọn Chỗ Ở Dễ', 'hero', '2026-08-13 04:41:09'),
	('hero_image', '/.uploads/image_page_home/home-hero-20260813-155451-f7859cd4.jpg', 'hero', '2026-08-13 15:55:02'),
	('hero_subheadline', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'hero', '2026-01-01 01:00:00'),
	('max_comment_attempts', '3', 'moderation', '2026-01-01 01:00:00'),
	('min_days_to_review', '15', 'moderation', '2026-01-01 01:00:00'),
	('otp_length', '4', 'auth', '2026-08-14 04:34:14'),
	('otp_max_send_per_24h', '5', 'auth', '2026-08-14 04:34:14'),
	('otp_max_verify_attempts', '5', 'auth', '2026-08-14 04:34:14'),
	('otp_resend_seconds', '60', 'auth', '2026-08-14 04:34:14'),
	('otp_ttl_minutes', '2', 'auth', '2026-08-14 04:34:14'),
	('site_description', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'brand', '2026-01-01 01:00:00'),
	('site_name', 'Nhà trọ Xanh', 'brand', '2026-01-01 01:00:00'),
	('site_slogan', 'Hệ thống trọ cao cấp #1 tại Quận 9', 'brand', '2026-01-01 01:00:00'),
	('smtp_encryption', 'tls', 'email', '2026-08-14 04:34:14'),
	('smtp_from_email', 'no-reply@example.com', 'email', '2026-08-14 04:34:14'),
	('smtp_from_name', 'NhaTroA', 'email', '2026-08-14 04:34:14'),
	('smtp_host', 'smtp.example.com', 'email', '2026-08-14 04:34:14'),
	('smtp_password', 'your_smtp_password', 'email', '2026-08-14 04:34:14'),
	('smtp_port', '587', 'email', '2026-08-14 04:34:14'),
	('smtp_username', 'your_email@example.com', 'email', '2026-08-14 04:34:14'),
	('stat_1_label', 'Giá cả sinh viên', 'stats', '2026-01-01 01:00:00'),
	('stat_1_value', 'Hợp lý', 'stats', '2026-01-01 01:00:00'),
	('stat_2_label', 'Dịch vụ tiện ích', 'stats', '2026-01-01 01:00:00'),
	('stat_2_value', '20+', 'stats', '2026-01-01 01:00:00'),
	('stat_3_label', 'Hỗ trợ cư dân', 'stats', '2026-01-01 01:00:00'),
	('stat_3_value', '24/7', 'stats', '2026-01-01 01:00:00'),
	('toxicity_threshold', '0.7', 'moderation', '2026-08-13 04:41:09');

-- Dumping structure for table manage.settings_backup_auth_20260815
CREATE TABLE IF NOT EXISTS `settings_backup_auth_20260815` (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.settings_backup_auth_20260815: ~26 rows (approximately)
INSERT INTO `settings_backup_auth_20260815` (`setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
	('comment_edit_hours', '24', 'moderation', '2026-01-01 01:00:00'),
	('comment_lock_hours', '24', 'moderation', '2026-01-01 01:00:00'),
	('contact_address', '123 Đường ABC, Quận 9, TP.HCM', 'contact', '2026-01-01 01:00:00'),
	('contact_email', 'contact@nhatroa.vn', 'contact', '2026-01-01 01:00:00'),
	('contact_phone', '0901 234 567', 'contact', '2026-01-01 01:00:00'),
	('contact_zalo', '0901234567', 'contact', '2026-01-01 01:00:00'),
	('enable_comment_moderation', '1', 'moderation', '2026-08-13 04:41:09'),
	('enable_gemini_moderation', '0', 'moderation', '2026-08-13 04:41:09'),
	('gemini_api_key', '', 'moderation', '2026-08-13 04:41:09'),
	('hero_headline', 'Tìm phòng trọ mơ ước của bạn', 'hero', '2026-01-01 01:00:00'),
	('hero_headline_1', 'Xem Phòng Rõ', 'hero', '2026-08-13 04:41:09'),
	('hero_headline_2', 'Chọn Chỗ Ở Dễ', 'hero', '2026-08-13 04:41:09'),
	('hero_image', '/.uploads/image_page_home/home-hero-20260813-155451-f7859cd4.jpg', 'hero', '2026-08-13 15:55:02'),
	('hero_subheadline', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'hero', '2026-01-01 01:00:00'),
	('max_comment_attempts', '3', 'moderation', '2026-01-01 01:00:00'),
	('min_days_to_review', '15', 'moderation', '2026-01-01 01:00:00'),
	('site_description', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'brand', '2026-01-01 01:00:00'),
	('site_name', 'Nhà trọ Xanh', 'brand', '2026-01-01 01:00:00'),
	('site_slogan', 'Hệ thống trọ cao cấp #1 tại Quận 9', 'brand', '2026-01-01 01:00:00'),
	('stat_1_label', 'Giá cả sinh viên', 'stats', '2026-01-01 01:00:00'),
	('stat_1_value', 'Hợp lý', 'stats', '2026-01-01 01:00:00'),
	('stat_2_label', 'Dịch vụ tiện ích', 'stats', '2026-01-01 01:00:00'),
	('stat_2_value', '20+', 'stats', '2026-01-01 01:00:00'),
	('stat_3_label', 'Hỗ trợ cư dân', 'stats', '2026-01-01 01:00:00'),
	('stat_3_value', '24/7', 'stats', '2026-01-01 01:00:00'),
	('toxicity_threshold', '0.7', 'moderation', '2026-08-13 04:41:09');

-- Dumping structure for table manage.users
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
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.users: ~4 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `avatar`, `role`, `room_id`, `date_of_birth`, `permanent_address`, `identity_number`, `identity_issue_date`, `identity_issue_place`, `created_at`) VALUES
	(1, 'Nguyễn Minh Anh', 'admin@nhatroxanh.vn', '0901000001', '$2y$10$ukdr.763CdVbd0W3AqJLfuo.Syd/8Tq4dATM3V/U5AiWgp8hjhi5y', 'default.png', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-01 01:00:00'),
	(2, 'Phạm Gia Bảo', 'tenant01@example.com', '0912000001', '$2y$10$ukdr.763CdVbd0W3AqJLfuo.Syd/8Tq4dATM3V/U5AiWgp8hjhi5y', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-05 03:00:00'),
	(3, 'Nguyễn Thùy Linh', 'tenant02@example.com', '0912000002', '$2y$10$ukdr.763CdVbd0W3AqJLfuo.Syd/8Tq4dATM3V/U5AiWgp8hjhi5y', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 07:00:00'),
	(4, 'Phạm Đăng Khoa', 'tenant03@example.com', '0912000003', '$2y$10$ukdr.763CdVbd0W3AqJLfuo.Syd/8Tq4dATM3V/U5AiWgp8hjhi5y', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 02:00:00'),
	(82, 'Lương Văn Dũng', 'giagiong001@gmail.com', '0328528757', '$2y$10$KK2dmN2Mf6aEnHmbdLj0LOsLMiyJgzniMxiztiRjwbX1s4qCCdm9q', 'default.png', 0, 13, NULL, NULL, NULL, NULL, NULL, '2026-08-15 08:30:53'),
	(83, 'Huyền', 'dungls2k7@gmail.com', '0397149601', '$2y$10$a9cNmhvDPasad7looUFu3uzva6mUJVtkU2LEwdxBvnVyc89jBMJgO', 'default.png', 0, 13, 'ENC:d6jx2SmgJJ0Of2tOixzSmJozEUwRmyCrCNz8URRoPhw=', 'ENC:D6bPb8AnHcpVZIB0IlMtzSrN0XqinE7bYX0zlGSO4xo=', 'ENC:YiEZXVgxJxQe3bUO0uAF1pNMqk2D/AGk5KrQRXPML4c=', 'ENC:DV2bHP2+HuQO0/aGzZCAlPWgWoTZtZ7Cfyh86UIaDzg=', 'ENC:Lb0/gnHY+1W0bAAGVpM0NiksKmVVhKAO6UYh/XxehPo=', '2026-08-15 09:02:30');

-- Dumping structure for table manage.users_backup_auth_20260815
CREATE TABLE IF NOT EXISTS `users_backup_auth_20260815` (
  `id` int unsigned NOT NULL DEFAULT '0',
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.users_backup_auth_20260815: ~6 rows (approximately)
INSERT INTO `users_backup_auth_20260815` (`id`, `full_name`, `email`, `phone`, `password`, `avatar`, `role`, `room_id`, `date_of_birth`, `permanent_address`, `identity_number`, `identity_issue_date`, `identity_issue_place`, `created_at`) VALUES
	(1, 'Nguyễn Văn An (Chủ trọ)', 'admin@nhatroa.vn', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-01 01:00:00'),
	(2, 'Trần Văn Bình', 'tenant1@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-05 03:00:00'),
	(3, 'Lê Thị Cẩm', 'tenant2@gmail.com', '0923456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 07:00:00'),
	(4, 'Phạm Đăng Khoa', 'tenant3@gmail.com', '0934567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 02:00:00'),
	(5, 'Lương Văn Dũng', 'giagiong001@gmail.com', '0328528757', '$2y$10$R.mlIDcq93jIGTlXmkkOSuH.MZw6l3NZoIy80myolIUMSa/GHQs2G', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 05:11:21'),
	(6, 'Lương Văn Dũng', 'giagiong0001@gmail.com', '0328528757', '$2y$10$NZHHs40wzTNXks9AmrQmEeHoEp68uF.BoHfgp8i8cvLFkOITuccIi', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-13 05:57:15');

-- Dumping structure for table manage.user_services
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.user_services: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
