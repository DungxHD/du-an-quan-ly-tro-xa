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


-- Dumping database structure for nhatroa_db
CREATE DATABASE IF NOT EXISTS `nhatroa_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `nhatroa_db`;

-- Dumping structure for table nhatroa_db.amenities
CREATE TABLE IF NOT EXISTS `amenities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.amenities: ~8 rows (approximately)
INSERT INTO `amenities` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
	(1, 'wifi', 'Wifi cáp quang', 'Tốc độ 200Mbps, không gián đoạn', 1, 1),
	(2, 'security', 'An ninh 24/7', 'Camera giám sát & vân tay ra vào', 2, 1),
	(3, 'local_parking', 'Chỗ để xe rộng', 'Miễn phí, có mái che an toàn', 3, 1),
	(4, 'local_laundry_service', 'Máy giặt Free', 'Sử dụng không giới hạn', 4, 1),
	(5, 'ac_unit', 'Điều hòa mát lạnh', 'Tiết kiệm điện, bảo trì định kỳ', 5, 1),
	(6, 'kitchen', 'Bếp chung hiện đại', 'Đầy đủ dụng cụ nấu nướng', 6, 1),
	(7, 'elevator', 'Thang máy', 'Di chuyển thuận tiện, an toàn', 7, 1),
	(8, 'water_heater', 'Nóng lạnh 24/7', 'Máy nước nóng năng lượng mặt trời', 8, 1);

-- Dumping structure for table nhatroa_db.buildings
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('zone','block','building','floor') COLLATE utf8mb4_unicode_ci DEFAULT 'building',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.buildings: ~3 rows (approximately)
INSERT INTO `buildings` (`id`, `name`, `type`, `address`, `description`, `image`, `sort_order`, `created_at`) VALUES
	(1, 'Tòa A - Khu Sinh Viên', 'building', '123 Đường ABC, Quận 9', 'Gần FPT University, an ninh tốt, giờ giấc tự do', NULL, 1, '2026-07-20 03:29:59'),
	(2, 'Tòa B - Khu Cao Cấp', 'building', '123 Đường ABC, Quận 9', 'Dành cho người đi làm, có thang máy, đầy đủ tiện nghi', NULL, 2, '2026-07-20 03:29:59'),
	(3, 'Dãy C - Khu Tiết Kiệm', 'block', '125 Đường ABC, Quận 9', 'Phòng giá rẻ, phù hợp sinh viên', NULL, 3, '2026-07-20 03:29:59'),
	(4, 'Tòa D', 'floor', 'Phòng trọ của Hà, Phan Đình Phùng, Thái Nguyên', 'gdhbzdf', 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=900', 1, '2026-07-20 06:07:58');

-- Dumping structure for table nhatroa_db.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint DEFAULT '5',
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comment_room` (`room_id`),
  KEY `fk_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.comments: ~2 rows (approximately)
INSERT INTO `comments` (`id`, `room_id`, `user_id`, `content`, `rating`, `status`, `created_at`) VALUES
	(1, 1, 2, 'Phòng rất tuyệt vời! An ninh tốt, chủ nhà nhiệt tình.', 5, 1, '2026-07-20 03:29:59');

-- Dumping structure for table nhatroa_db.rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `area` decimal(5,2) DEFAULT '0.00',
  `max_occupancy` tinyint DEFAULT '2',
  `description` text COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800',
  `status` enum('available','rented','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `notice_given` tinyint(1) DEFAULT '0',
  `expected_vacant_date` date DEFAULT NULL,
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_building` (`building_id`),
  CONSTRAINT `fk_room_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.rooms: ~6 rows (approximately)
INSERT INTO `rooms` (`id`, `building_id`, `name`, `floor`, `price`, `area`, `max_occupancy`, `description`, `thumbnail`, `status`, `notice_given`, `expected_vacant_date`, `views`, `created_at`) VALUES
	(1, 1, 'Phòng A101 - Ban Công', 1, 3500000.00, 25.00, 2, 'Phòng có ban công thoáng mát, đầy đủ nội thất cơ bản.', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'rented', 0, NULL, 150, '2026-07-20 03:29:59'),
	(3, 1, 'Phòng A201 - View Đẹp', 2, 4000000.00, 28.00, 2, 'Phòng tầng 2, view nhìn ra công viên, yên tĩnh.', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'available', 0, NULL, 121, '2026-07-20 03:29:59'),
	(4, 2, 'Phòng B101 - Premium', 1, 5500000.00, 35.00, 2, 'Căn hộ mini cao cấp, có bếp riêng, máy lạnh.', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'available', 0, NULL, 321, '2026-07-20 03:29:59'),
	(5, 2, 'Phòng B201 - Deluxe', 2, 6000000.00, 40.00, 3, 'Phòng rộng, phù hợp gia đình nhỏ hoặc nhóm bạn.', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'rented', 1, '2026-08-04', 203, '2026-07-20 03:29:59'),
	(6, 3, 'Phòng C101 - Giá Rẻ', 1, 2000000.00, 15.00, 1, 'Phòng nhỏ, đầy đủ tiện nghi cơ bản, tiết kiệm.', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'available', 0, NULL, 91, '2026-07-20 03:29:59'),
	(7, 4, 'Phong 208', 1, 1000000.00, 20.00, 2, '', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=900', 'available', 0, NULL, 0, '2026-07-21 02:53:13');

-- Dumping structure for table nhatroa_db.room_services
CREATE TABLE IF NOT EXISTS `room_services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `quantity` int DEFAULT '1',
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rs_room` (`room_id`),
  KEY `fk_rs_service` (`service_id`),
  CONSTRAINT `fk_rs_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.room_services: ~4 rows (approximately)
INSERT INTO `room_services` (`id`, `room_id`, `service_id`, `quantity`, `registered_at`) VALUES
	(1, 1, 1, 1, '2026-07-20 03:29:59'),
	(2, 1, 2, 1, '2026-07-20 03:29:59'),
	(3, 1, 3, 1, '2026-07-20 03:29:59'),
	(4, 1, 4, 1, '2026-07-21 02:50:57');

-- Dumping structure for table nhatroa_db.services
CREATE TABLE IF NOT EXISTS `services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'tháng',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'settings',
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.services: ~4 rows (approximately)
INSERT INTO `services` (`id`, `name`, `price`, `unit`, `icon`, `description`) VALUES
	(1, 'Wifi cáp quang', 100000.00, 'tháng', 'wifi', 'Internet tốc độ cao 200Mbps'),
	(2, 'Tiền rác', 30000.00, 'tháng', 'delete', 'Phí thu gom rác sinh hoạt'),
	(3, 'Giữ xe máy', 100000.00, 'tháng/xe', 'two_wheeler', 'Bãi giữ xe có mái che'),
	(4, 'Giặt ủi', 500000.00, 'tháng', 'local_laundry_service', 'Máy giặt chung không giới hạn');

-- Dumping structure for table nhatroa_db.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.settings: ~15 rows (approximately)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
	('contact_address', '123 Đường ABC, Quận 9, TP.HCM', 'contact', '2026-07-20 03:29:59'),
	('contact_email', 'contact@nhatroa.vn', 'contact', '2026-07-20 03:29:59'),
	('contact_phone', '0901 234 567', 'contact', '2026-07-20 03:29:59'),
	('contact_zalo', '0901234567', 'contact', '2026-07-20 03:29:59'),
	('hero_headline_1', 'Không Gian Sống', 'hero', '2026-07-20 03:29:59'),
	('hero_headline_2', 'Chuẩn Mực', 'hero', '2026-07-20 03:29:59'),
	('hero_image', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1600', 'hero', '2026-07-20 03:29:59'),
	('hero_subheadline', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'hero', '2026-07-20 03:29:59'),
	('site_description', 'Trải nghiệm hệ thống trọ cao cấp dành riêng cho sinh viên FPT và giới trẻ hiện đại.', 'brand', '2026-07-20 03:29:59'),
	('site_name', 'Nhà trọ Xanh', 'brand', '2026-07-20 03:39:02'),
	('site_slogan', 'Hệ thống trọ cao cấp #1 tại Quận 9', 'brand', '2026-07-20 03:29:59'),
	('stat_1_label', 'Giá cả sinh viên', 'stats', '2026-07-20 03:29:59'),
	('stat_1_value', 'Hợp lý', 'stats', '2026-07-20 03:29:59'),
	('stat_2_label', 'Dịch vụ tiện ích', 'stats', '2026-07-20 03:29:59'),
	('stat_2_value', '20+', 'stats', '2026-07-20 03:29:59');

-- Dumping structure for table nhatroa_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1: Admin, 0: Tenant',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `room_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `fk_user_room` (`room_id`),
  CONSTRAINT `fk_user_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table nhatroa_db.users: ~3 rows (approximately)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `avatar`, `role`, `status`, `room_id`, `created_at`) VALUES
	(1, 'Nguyễn Văn An (Chủ trọ)', 'admin@nhatroa.vn', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 1, 1, NULL, '2026-07-20 03:29:59'),
	(2, 'Trần Văn Bình', 'tenant1@gmail.com', '0912345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, 1, 1, '2026-07-20 03:29:59'),
	(3, 'Lê Thị Chi', 'tenant2@gmail.com', '0923456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.png', 0, 1, NULL, '2026-07-20 03:29:59');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
