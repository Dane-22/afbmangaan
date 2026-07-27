-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 26, 2026 at 12:33 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `afb_mangaan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `attendee_id` int NOT NULL,
  `status` enum('Present','Absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Present',
  `log_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logged_by` int DEFAULT NULL,
  `method` enum('Manual','QR Scan','Search') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Manual',
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`event_id`,`attendee_id`),
  KEY `attendee_id` (`attendee_id`),
  KEY `logged_by` (`logged_by`),
  KEY `idx_log_time` (`log_time`),
  KEY `idx_status` (`status`),
  KEY `idx_event_status` (`event_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendees`
--

DROP TABLE IF EXISTS `attendees`;
CREATE TABLE IF NOT EXISTS `attendees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `church` enum('AFB Mangaan','AFB Lettac Sur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'WMO',
  `ministry` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique QR code identifier',
  `status` enum('Active','Archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `idx_church` (`church`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_church_status_name` (`church`,`status`,`fullname`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendees`
--

INSERT INTO `attendees` (`id`, `church`, `fullname`, `category`, `contact`, `email`, `qr_token`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AFB Mangaan', 'Juan Dela Cruz', 'WMO', '09123456789', 'juan@email.com', 'AFB001001', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'AFB Mangaan', 'Maria Santos', 'WMO', '09187654321', 'maria@email.com', 'AFB001002', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'AFB Mangaan', 'Pedro Penduko', 'WMO', '09111222333', 'pedro@email.com', 'AFB001003', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'AFB Mangaan', 'Ana Makiling', 'WMO', '09444555666', 'ana@email.com', 'AFB001004', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(5, 'AFB Mangaan', 'Diego Silang', 'WMO', '09777888999', 'diego@email.com', 'AFB001005', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `sender_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply_to_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room` (`room_id`),
  KEY `idx_reply` (`reply_to_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room_id`, `sender_id`, `sender_name`, `message`, `reply_to_id`, `created_at`) VALUES
(1, 1, 0, 'System', 'Welcome to the AFB Mangaan General Group Chat! Use this space for team communication and announcements.', NULL, '2026-07-25 11:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `chat_reactions`
--

DROP TABLE IF EXISTS `chat_reactions`;
CREATE TABLE IF NOT EXISTS `chat_reactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emoji` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_emoji` (`message_id`,`user_id`,`emoji`),
  KEY `idx_msg` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

DROP TABLE IF EXISTS `chat_rooms`;
CREATE TABLE IF NOT EXISTS `chat_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('group','direct') COLLATE utf8mb4_unicode_ci DEFAULT 'group',
  `created_by` int NOT NULL DEFAULT '1',
  `church` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_rooms`
--

INSERT INTO `chat_rooms` (`id`, `name`, `type`, `created_by`, `church`, `created_at`) VALUES
(1, 'General Church Chat', 'group', 1, 'AFB Mangaan', '2026-07-25 11:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `church` enum('AFB Mangaan','AFB Lettac Sur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `event_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Sunday Service','Midweek Service','Special Event','Meeting','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Sunday Service',
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Upcoming',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_church` (`church`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_church_start_date` (`church`,`start_date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `church`, `event_name`, `start_date`, `end_date`, `event_time`, `location`, `type`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'AFB Mangaan', 'Sunday Worship Service', '2026-02-05', NULL, '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Completed', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'AFB Mangaan', 'Midweek Prayer Meeting', '2026-02-09', NULL, '19:00:00', 'Fellowship Hall', 'Midweek Service', 'Completed', 'Wednesday prayer and Bible study', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'AFB Mangaan', 'Youth Fellowship Night', '2026-02-11', NULL, '18:00:00', 'Youth Room', 'Special Event', 'Completed', 'Monthly youth gathering', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'AFB Mangaan', 'Sunday Worship Service', '2026-02-12', NULL, '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Ongoing', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(5, 'AFB Mangaan', 'Christmas Special', '2026-02-26', '2026-02-28', '18:00:00', 'Main Sanctuary', 'Special Event', 'Upcoming', 'Christmas celebration and dinner', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `church` enum('AFB Mangaan','AFB Lettac Sur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','viewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'operator',
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `must_change_password` tinyint(1) DEFAULT '0' COMMENT 'Force password change on next login',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_church` (`church`,`username`),
  KEY `idx_church` (`church`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `church`, `username`, `password`, `fullname`, `role`, `status`, `must_change_password`, `created_at`, `updated_at`) VALUES
(1, 'AFB Mangaan', 'admin', '$2y$10$pCHnPcELGAog/L/m35uT8.YIPwkYFuTLpIz5iNc/k7h53qPmzF.Fm', 'System Admin', 'admin', 'Active', 0, '2026-07-23 06:38:30', '2026-07-23 06:38:30'),
(2, 'AFB Mangaan', 'operator', '$2y$10$el4MBjzlFrv4qeg84lelu.VyToq63kMRilDwAM4vYt129l.Ptllp.', 'Mangaan Operator', 'operator', 'Active', 0, '2026-07-23 06:38:30', '2026-07-23 06:38:30'),
(3, 'AFB Lettac Sur', 'admin', '$2y$10$pCHnPcELGAog/L/m35uT8.YIPwkYFuTLpIz5iNc/k7h53qPmzF.Fm', 'System Admin', 'admin', 'Active', 0, '2026-07-23 06:38:30', '2026-07-23 06:38:30'),
(4, 'AFB Lettac Sur', 'operator', '$2y$10$el4MBjzlFrv4qeg84lelu.VyToq63kMRilDwAM4vYt129l.Ptllp.', 'Lettac Sur Operator', 'operator', 'Active', 0, '2026-07-23 06:38:30', '2026-07-23 06:38:30');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_ibfk_2` FOREIGN KEY (`attendee_id`) REFERENCES `attendees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_logs_ibfk_3` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
