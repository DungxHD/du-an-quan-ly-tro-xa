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
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.amenities: ~8 rows (approximately)
INSERT INTO `amenities` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
	(1, 'wifi', 'Wifi cáp quang', 'Tốc độ 200Mbps, không gián đoạn', 1, 1),
	(2, 'security', 'An ninh 24/7', 'Camera giám sát & vân tay ra vào', 2, 1),
	(3, 'local_parking', 'Chỗ để xe rộng', 'Miễn phí, có mái che an toàn', 3, 1),
	(4, 'local_laundry_service', 'Máy giặt Free', 'Sử dụng không giới hạn', 4, 1),
	(5, 'ac_unit', 'Điều hòa mát lạnh', 'Tiết kiệm điện, bảo trì định kỳ', 5, 1),
	(6, 'kitchen', 'Bếp chung hiện đại', 'Đầy đủ dụng cụ nấu nướng', 6, 1),
	(7, 'elevator', 'Thang máy', 'Di chuyển thuận tiện, an toàn', 7, 1),
	(8, 'water_heater', 'Nóng lạnh 24/7', 'Máy nước nóng năng lượng mặt trời', 8, 1);

-- Dumping structure for table manage.areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã khu',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên khu',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Địa chỉ khu',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Mô tả khu',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn ảnh khu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.areas: ~3 rows (approximately)
INSERT INTO `areas` (`id`, `name`, `address`, `description`, `image`, `created_at`) VALUES
	(1, 'Khu A - Sinh viên', '123 Đường ABC, Quận 9', 'Khu gần FPT, an ninh tốt, có 2 tầng.', 'uploads/areas/khu-a.jpg', '2026-08-04 00:34:48'),
	(2, 'Khu B - Tiết kiệm', '125 Đường ABC, Quận 9', 'Khu nhà trệt, giá mềm, phòng nằm ngang.', 'uploads/areas/khu-b.jpg', '2026-08-04 00:34:48'),
	(3, 'Realme GT Neo2 5G', 'Phòng trọ của Hà, Phan Đình Phùng, Thái Nguyên', 'wesfgse', '/.uploads/image_khu_new/khu-20260808-075603-54421673.png', '2026-08-08 07:56:03');

-- Dumping structure for table manage.banned_words
CREATE TABLE IF NOT EXISTS `banned_words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Từ/cụm từ cấm (ĐÃ CHUẨN HÓA)',
  `type` enum('word','phrase','abbreviation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'word',
  `replacement` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '***',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.banned_words: ~74 rows (approximately)
INSERT INTO `banned_words` (`id`, `word`, `type`, `replacement`, `is_active`, `created_at`) VALUES
	(1, 'dit me', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(2, 'dit cu', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(3, 'du ma', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(4, 'du me', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(5, 'to su', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(6, 'tien su', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(7, 'tong mon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(8, 'me may', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(9, 'me kiep', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(10, 'cha may', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(11, 'cai lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(12, 'cai loz', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(13, 'con cac', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(14, 'cai buoi', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(15, 'dau buoi', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(16, 'vai lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(17, 'vai l', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(18, 'ham lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(19, 'ham loz', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(20, 'xao lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(21, 'xam lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(22, 'ngu lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(23, 'mat loz', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(24, 'liem lon', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(25, 'nhu cuc', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(26, 'nhu cac', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(27, 'an cut', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(28, 'boc cut', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(29, 'oc cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(30, 'nao cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(31, 'cho de', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(32, 'thang cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(33, 'con cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(34, 'lu cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(35, 'suc sinh', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(36, 'suc vat', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(37, 'thieu nang', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(38, 'bai nao', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(39, 'vo hoc', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(40, 'can ba', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(41, 're rach', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(42, 'mat day', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(43, 'do mat day', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(44, 'thang ngu', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(45, 'ngu hoc', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(46, 'benh hoan', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(47, 'thang dien', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(48, 'con dien', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(49, 'cho chet', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(50, 'diem thu', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(51, 'lam pho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(52, 'do cave', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(53, 'con diem', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(54, 'gai goi', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(55, 'call girl', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(56, 'pho di', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(57, 'di ghe', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(58, 'bong cho', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(59, 'be de', 'word', '***', 1, '2026-08-04 00:34:48'),
	(60, 'lua dao', 'phrase', '***', 1, '2026-08-04 00:34:48'),
	(61, 'dm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(62, 'dcm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(63, 'dkm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(64, 'dmm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(65, 'dkmm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(66, 'dcmm', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(67, 'vkl', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(68, 'vcl', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(69, 'vl', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(70, 'clgt', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(71, 'cmn', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(72, 'cmnl', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(73, 'sml', 'abbreviation', '***', 1, '2026-08-04 00:34:48'),
	(74, 'wtf', 'abbreviation', '***', 1, '2026-08-04 00:34:48');

-- Dumping structure for table manage.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `rating` tinyint NOT NULL DEFAULT '5',
  `toxicity_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `is_spam` tinyint(1) NOT NULL DEFAULT '0',
  `flagged_words` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint NOT NULL DEFAULT '1',
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_room` (`user_id`,`room_id`),
  KEY `idx_room_rating` (`room_id`,`rating`),
  KEY `idx_room_status` (`room_id`,`status`,`is_spam`),
  CONSTRAINT `fk_comment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.comments: ~2 rows (approximately)
INSERT INTO `comments` (`id`, `room_id`, `user_id`, `content`, `rating`, `toxicity_score`, `is_spam`, `flagged_words`, `status`, `edited_at`, `created_at`) VALUES
	(1, 1, 2, 'Phòng rất tuyệt vời! An ninh tốt, chủ nhà nhiệt tình.', 5, 0.05, 0, NULL, 1, NULL, '2026-08-04 00:34:48'),
	(2, 1, 3, 'Ở 2 người rất thoải mái, tiện nghi đầy đủ.', 5, 0.08, 0, NULL, 1, NULL, '2026-08-04 00:34:48');

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
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','resolved','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comment_user` (`comment_id`,`user_id`),
  KEY `idx_status` (`status`),
  KEY `fk_cr_user` (`user_id`),
  CONSTRAINT `fk_cr_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('active','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `contract_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contract_user` (`user_id`),
  KEY `idx_contract_room` (`room_id`,`status`),
  KEY `idx_contract_active` (`user_id`,`move_out_date`),
  CONSTRAINT `fk_contract_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contract_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.contracts: ~2 rows (approximately)
INSERT INTO `contracts` (`id`, `user_id`, `room_id`, `move_in_date`, `move_out_date`, `rent_price`, `deposit_amount`, `initial_electricity_index`, `initial_water_index`, `status`, `contract_date`, `created_at`) VALUES
	(1, 2, 1, '2026-01-01', NULL, 1750000.00, 500000.00, 1000.00, 48.00, 'active', '2026-01-01', '2026-08-04 00:34:48'),
	(2, 3, 1, '2026-02-01', NULL, 1750000.00, 500000.00, 1000.00, 48.00, 'active', '2026-02-01', '2026-08-04 00:34:48');

-- Dumping structure for table manage.floors
CREATE TABLE IF NOT EXISTS `floors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã tầng',
  `area_id` int unsigned NOT NULL COMMENT 'FK → areas',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor_number` int NOT NULL DEFAULT '1' COMMENT '0 = tầng trệt',
  `room_limit` int NOT NULL DEFAULT '0' COMMENT 'Giới hạn số phòng (0 = không giới hạn)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_floor_area` (`area_id`),
  CONSTRAINT `fk_floor_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.floors: ~6 rows (approximately)
INSERT INTO `floors` (`id`, `area_id`, `name`, `floor_number`, `room_limit`, `created_at`) VALUES
	(1, 1, 'Tầng 1', 1, 0, '2026-08-04 00:34:48'),
	(2, 1, 'Tầng 2', 2, 0, '2026-08-04 00:34:48'),
	(3, 2, 'Tầng trệt', 0, 0, '2026-08-04 00:34:48'),
	(4, 3, 'Tầng 1', 1, 0, '2026-08-08 07:56:03'),
	(5, 3, 'Tầng 2', 2, 0, '2026-08-08 07:56:03'),
	(6, 3, 'Tầng 3', 3, 0, '2026-08-08 07:56:03');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.meter_readings: ~3 rows (approximately)
INSERT INTO `meter_readings` (`id`, `room_id`, `service_id`, `month`, `year`, `old_index`, `new_index`, `created_at`) VALUES
	(1, 1, 1, 7, 2026, 1000.00, 1100.00, '2026-08-04 00:34:48'),
	(2, 1, 2, 7, 2026, 50.00, 60.00, '2026-08-04 00:34:48'),
	(3, 1, 1, 8, 2026, 1100.00, 1200.00, '2026-08-08 08:35:20');

-- Dumping structure for table manage.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT 'NULL = broadcast tất cả',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `type` enum('price_change','payment','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_noti_user` (`user_id`),
  CONSTRAINT `fk_noti_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notifications: ~12 rows (approximately)
INSERT INTO `notifications` (`id`, `user_id`, `title`, `content`, `type`, `is_read`, `created_at`) VALUES
	(1, NULL, 'Thay đổi giá dịch vụ', 'Sạc xe điện: 100.000đ → 150.000đ/xe, áp dụng từ tháng 08/2026.', 'price_change', 0, '2026-08-04 00:34:48'),
	(2, NULL, 'Thay đổi giá điện', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-04 00:34:48'),
	(3, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 10/2026.', 'price_change', 0, '2026-08-09 04:08:40'),
	(4, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 50.000đ → 30.000đ/người, áp dụng từ tháng 10/2026.', 'price_change', 0, '2026-08-09 04:09:09'),
	(5, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 4.000đ → 9.000đ/kwh, áp dụng từ tháng 11/2026.', 'price_change', 0, '2026-08-09 04:18:18'),
	(6, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-09 09:28:46'),
	(7, NULL, 'Thay đổi giá dịch vụ', 'Tiền điện: 3.500đ → 4.000đ/kwh, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-09 09:59:17'),
	(8, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 30.000đ/người, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-09 10:00:45'),
	(9, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-09 10:32:51'),
	(10, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 06:44:51'),
	(11, NULL, 'Thay đổi giá dịch vụ', 'Sạc xe điện: 100.000đ → 10.000đ/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 06:46:30'),
	(12, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 10.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 08:17:18'),
	(13, NULL, 'Thay đổi giá dịch vụ', 'Tiền nước: 30.000đ → 10.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 10:18:38'),
	(14, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 10:23:29'),
	(15, NULL, 'Thay đổi giá dịch vụ', 'Máy giặt: 50.000đ → 50.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 10:54:21'),
	(16, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 51.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 10:54:49'),
	(17, NULL, 'Thay đổi giá dịch vụ', 'Wifi: 51.000đ → 51.000đ/người/tháng, áp dụng từ tháng 09/2026.', 'price_change', 0, '2026-08-10 13:35:28');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.notification_reads: ~0 rows (approximately)

-- Dumping structure for table manage.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `contract_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_period` (`room_id`,`month`,`year`),
  KEY `fk_pay_user` (`user_id`),
  KEY `fk_pay_contract` (`contract_id`),
  CONSTRAINT `fk_pay_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pay_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payments: ~3 rows (approximately)
INSERT INTO `payments` (`id`, `room_id`, `contract_id`, `user_id`, `month`, `year`, `amount`, `status`, `paid_at`, `created_at`) VALUES
	(1, 1, 1, 2, 7, 2026, 4290000.00, 'paid', '2026-07-05 03:30:00', '2026-08-04 00:34:48'),
	(2, 4, NULL, NULL, 7, 2026, 2300000.00, 'unpaid', NULL, '2026-08-04 00:34:48'),
	(3, 1, NULL, NULL, 8, 2026, 4252000.00, 'unpaid', NULL, '2026-08-09 09:32:07');

-- Dumping structure for table manage.payment_items
CREATE TABLE IF NOT EXISTS `payment_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` int unsigned NOT NULL,
  `service_id` int unsigned DEFAULT NULL,
  `item_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `billing_mode` enum('fixed','meter','per_person','per_unit') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pi_payment` (`payment_id`),
  KEY `fk_pi_service` (`service_id`),
  CONSTRAINT `fk_pi_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.payment_items: ~15 rows (approximately)
INSERT INTO `payment_items` (`id`, `payment_id`, `service_id`, `item_name`, `unit_price`, `quantity`, `amount`, `billing_mode`, `created_at`) VALUES
	(1, 1, NULL, 'Tiền phòng', 3500000.00, 1.00, 3500000.00, 'fixed', '2026-08-04 00:34:48'),
	(2, 1, 1, 'Tiền điện', 3500.00, 100.00, 350000.00, 'meter', '2026-08-04 00:34:48'),
	(3, 1, 2, 'Tiền nước', 50000.00, 2.00, 100000.00, 'per_person', '2026-08-04 00:34:48'),
	(4, 1, 3, 'Tiền rác', 20000.00, 2.00, 40000.00, 'per_person', '2026-08-04 00:34:48'),
	(5, 1, 4, 'Wifi', 50000.00, 2.00, 100000.00, 'per_person', '2026-08-04 00:34:48'),
	(6, 1, 7, 'Máy giặt', 50000.00, 2.00, 100000.00, 'per_person', '2026-08-04 00:34:48'),
	(7, 1, NULL, 'Sạc xe điện', 100000.00, 1.00, 100000.00, 'per_unit', '2026-08-04 00:34:48'),
	(8, 2, NULL, 'Tiền phòng', 2000000.00, 1.00, 2000000.00, 'fixed', '2026-08-04 00:34:48'),
	(9, 3, NULL, 'Tiền phòng', 3500000.00, 1.00, 3500000.00, 'fixed', '2026-08-09 09:32:07'),
	(10, 3, 7, 'Máy giặt', 50000.00, 2.00, 100000.00, 'per_person', '2026-08-09 09:32:07'),
	(11, 3, 2, 'Tiền nước', 30000.00, 2.00, 60000.00, 'per_person', '2026-08-09 09:32:07'),
	(12, 3, 3, 'Tiền rác', 20000.00, 2.00, 40000.00, 'per_person', '2026-08-09 09:32:07'),
	(13, 3, 1, 'Tiền điện', 3500.00, 100.00, 350000.00, 'meter', '2026-08-09 09:32:07'),
	(14, 3, 4, 'Wifi', 51000.00, 2.00, 102000.00, 'per_person', '2026-08-09 09:32:07'),
	(15, 3, NULL, 'Sạc xe điện - Lê Thị Chi', 100000.00, 1.00, 100000.00, 'per_unit', '2026-08-09 09:32:07');

-- Dumping structure for table manage.price_changes
CREATE TABLE IF NOT EXISTS `price_changes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int unsigned NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `old_billing_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_billing_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.price_changes: ~2 rows (approximately)

-- Dumping structure for table manage.rental_requests
CREATE TABLE IF NOT EXISTS `rental_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `room_id` int unsigned NOT NULL,
  `move_in_date` date NOT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupant_count` tinyint NOT NULL DEFAULT '1',
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  KEY `idx_rr_room` (`room_id`),
  KEY `idx_rr_status` (`status`),
  CONSTRAINT `fk_rr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rental_requests: ~0 rows (approximately)

-- Dumping structure for table manage.roommate_requests
CREATE TABLE IF NOT EXISTS `roommate_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` int unsigned NOT NULL COMMENT 'Người B — gửi yêu cầu',
  `target_user_id` int unsigned NOT NULL COMMENT 'Người A — đang ở phòng',
  `room_id` int unsigned NOT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','admin_rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_requester` (`requester_id`),
  KEY `idx_rm_target` (`target_user_id`),
  KEY `idx_rm_room` (`room_id`),
  CONSTRAINT `fk_rm_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_host` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.roommate_requests: ~0 rows (approximately)

-- Dumping structure for table manage.rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Mã phòng',
  `floor_id` int unsigned NOT NULL COMMENT 'FK → floors',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0' COMMENT 'Số thứ tự phòng trong tầng',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `area` decimal(5,2) DEFAULT '0.00' COMMENT 'Diện tích m²',
  `max_occupancy` tinyint DEFAULT '2',
  `description` text COLLATE utf8mb4_unicode_ci,
  `amenities` text COLLATE utf8mb4_unicode_ci COMMENT 'Tiện nghi phòng (JSON array)',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','available','rented','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notice_given` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = tenant da bao chuyen di',
  `expected_vacant_date` date DEFAULT NULL COMMENT 'Ngay du kien trong (khi notice_given=1)',
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_floor` (`floor_id`),
  KEY `idx_room_status` (`status`),
  CONSTRAINT `fk_room_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.rooms: ~5 rows (approximately)
INSERT INTO `rooms` (`id`, `floor_id`, `name`, `position`, `price`, `area`, `max_occupancy`, `description`, `amenities`, `thumbnail`, `status`, `views`, `created_at`) VALUES
	(1, 1, 'Phòng A1', 1, 3500000.00, 25.00, 2, 'Phòng có ban công, đầy đủ nội thất.', NULL, 'uploads/rooms/a1.jpg', 'rented', 150, '2026-08-04 00:34:48'),
	(2, 1, 'Phòng A2', 2, 3200000.00, 22.00, 2, 'Phòng thoáng mát, cửa sổ lớn.', NULL, 'uploads/rooms/a2.jpg', 'available', 120, '2026-08-04 00:34:48'),
	(3, 2, 'Phòng A3', 1, 4000000.00, 28.00, 3, 'Phòng rộng tầng 2, view công viên.', NULL, 'uploads/rooms/a3.jpg', 'available', 95, '2026-08-04 00:34:48'),
	(4, 3, 'Phòng B1', 1, 2000000.00, 15.00, 1, 'Phòng giá mềm, tiện nghi cơ bản.', NULL, 'uploads/rooms/b1.jpg', 'rented', 88, '2026-08-04 00:34:48'),
	(5, 3, 'Phòng B2', 2, 2200000.00, 16.00, 2, 'Phòng có gác lửng.', NULL, 'uploads/rooms/b2.jpg', 'available', 60, '2026-08-04 00:34:48');

-- Dumping structure for table manage.room_images
CREATE TABLE IF NOT EXISTS `room_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ri_room` (`room_id`),
  CONSTRAINT `fk_ri_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_images: ~0 rows (approximately)

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.room_services: ~2 rows (approximately)
INSERT INTO `room_services` (`id`, `room_id`, `service_id`, `quantity`, `registered_at`) VALUES
	(1, 1, 4, 1, '2026-08-04 00:34:48'),
	(2, 1, 7, 1, '2026-08-04 00:34:48');

-- Dumping structure for table manage.services
CREATE TABLE IF NOT EXISTS `services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'tháng',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'settings',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `billing_mode` enum('fixed','meter','per_person','per_unit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `kind` enum('other','electricity','water','trash') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `applies_to` enum('room','person') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'room',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `delete_year` smallint DEFAULT NULL,
  `delete_month` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.services: ~5 rows (approximately)
INSERT INTO `services` (`id`, `name`, `price`, `unit`, `icon`, `description`, `is_required`, `billing_mode`, `kind`, `applies_to`, `is_active`, `delete_year`, `delete_month`, `deactivate_month`, `deactivate_year`) VALUES
	(1, 'Tiền điện', 3500.00, 'kWh', 'bolt', 'Tính theo chỉ số công tơ', 1, 'meter', 'electricity', 'room', 1, NULL, NULL, NULL, NULL),
	(2, 'Tiền nước', 30000.00, 'người/tháng', 'water_drop', 'Mặc định theo người', 1, 'per_person', 'water', 'room', 1, NULL, NULL, NULL, NULL),
	(3, 'Tiền rác', 20000.00, 'người', 'delete', 'Phí thu gom rác theo đầu người', 1, 'per_person', 'trash', 'room', 1, NULL, NULL, NULL, NULL),
	(4, 'Wifi', 51000.00, 'người/tháng', 'wifi', 'Internet tốc độ cao', 0, 'per_person', 'other', 'room', 1, NULL, NULL, NULL, NULL),
	(7, 'Máy giặt', 50000.00, 'người/tháng', 'local_laundry_service', 'Máy giặt chung', 0, 'per_person', 'other', 'room', 1, 2026, 9, NULL, NULL);

-- Dumping structure for table manage.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.settings: ~25 rows (approximately)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
	('comment_edit_hours', '24', 'moderation', '2026-08-04 00:34:48'),
	('comment_lock_hours', '24', 'moderation', '2026-08-04 00:34:48'),
	('contact_address', '123 Đường ABC, Quận 9, TP.HCM', 'contact', '2026-08-04 00:34:48'),
	('contact_email', 'contact@nhatroa.vn', 'contact', '2026-08-04 00:34:48'),
	('contact_phone', '0901 234 567', 'contact', '2026-08-04 00:34:48'),
	('contact_zalo', '0901234567', 'contact', '2026-08-04 00:34:48'),
	('enable_comment_moderation', '1', 'moderation', '2026-08-04 00:34:48'),
	('enable_gemini_moderation', '0', 'moderation', '2026-08-04 00:34:48'),
	('gemini_api_key', '', 'moderation', '2026-08-04 00:34:48'),
	('hero_headline_1', 'Không Gian Sống', 'hero', '2026-08-04 00:34:48'),
	('hero_headline_2', 'Chuẩn Mực', 'hero', '2026-08-04 00:34:48'),
	('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600', 'hero', '2026-08-04 00:34:48'),
	('hero_subheadline', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'hero', '2026-08-04 00:34:48'),
	('max_comment_attempts', '3', 'moderation', '2026-08-04 00:34:48'),
	('min_days_to_review', '15', 'moderation', '2026-08-04 00:34:48'),
	('site_description', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'brand', '2026-08-04 00:34:48'),
	('site_name', 'Nhà trọ Xanh', 'brand', '2026-08-04 00:34:48'),
	('site_slogan', 'Hệ thống trọ cao cấp #1 tại Quận 9', 'brand', '2026-08-04 00:34:48'),
	('stat_1_label', 'Giá cả sinh viên', 'stats', '2026-08-04 00:34:48'),
	('stat_1_value', 'Hợp lý', 'stats', '2026-08-04 00:34:48'),
	('stat_2_label', 'Dịch vụ tiện ích', 'stats', '2026-08-04 00:34:48'),
	('stat_2_value', '20+', 'stats', '2026-08-04 00:34:48'),
	('stat_3_label', 'Hỗ trợ cư dân', 'stats', '2026-08-04 00:34:48'),
	('stat_3_value', '24/7', 'stats', '2026-08-04 00:34:48'),
	('toxicity_threshold', '0.7', 'moderation', '2026-08-04 00:34:48');

-- Dumping structure for table manage.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'HASH bcrypt',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Admin, 0=Tenant',
  `room_id` int unsigned DEFAULT NULL COMMENT 'Phòng đang ở',
  `date_of_birth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `permanent_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_issue_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `identity_issue_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã hóa AES',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `fk_user_room` (`room_id`),
  CONSTRAINT `fk_user_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.users: ~4 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `avatar`, `role`, `room_id`, `date_of_birth`, `permanent_address`, `identity_number`, `identity_issue_date`, `identity_issue_place`, `created_at`) VALUES
	(1, 'Nguyễn Văn An (Chủ trọ)', 'admin@nhatroa.vn', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 00:34:48'),
	(2, 'Trần Văn Bình', 'tenant1@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, 1, '2003-05-12', 'Thái Nguyên', '012345678901', '2020-01-10', 'Công an Thái Nguyên', '2026-08-04 00:34:48'),
	(3, 'Lê Thị Chi', 'tenant2@gmail.com', '0923456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, 1, '2004-08-20', 'Hà Nội', '098765432109', '2021-03-15', 'Công an Hà Nội', '2026-08-04 00:34:48'),
	(4, 'Phạm Đăng Ký Mới', 'tenant3@gmail.com', '0933333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 00:34:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table manage.user_services: ~0 rows (approximately)

-- Dumping structure for table manage.maintenance_requests
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `reason` text NOT NULL,
  `duration_days` int unsigned NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `status` enum('pending','active','rejected','completed') NOT NULL DEFAULT 'pending',
  `rejected_by_user_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mr_room` (`room_id`),
  KEY `idx_mr_status` (`status`),
  KEY `idx_mr_start` (`start_date`),
  CONSTRAINT `fk_mnt_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mnt_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mnt_rejected_by` FOREIGN KEY (`rejected_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
