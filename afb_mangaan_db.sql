-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 16, 2026 at 07:31 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

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
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendees`
--

DROP TABLE IF EXISTS `attendees`;
CREATE TABLE IF NOT EXISTS `attendees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `church` enum('AFB Mangaan','AFB Lettac Sur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('MCYO','WMO','CCMO','KIDS') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'WMO',
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
  KEY `idx_qr_token` (`qr_token`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendees`
--

INSERT INTO `attendees` (`id`, `fullname`, `category`, `contact`, `email`, `qr_token`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'Adult', '09123456789', 'juan@email.com', 'AFB001001', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'Maria Santos', 'Youth', '09187654321', 'maria@email.com', 'AFB001002', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'Pedro Penduko', 'Senior', '09111222333', 'pedro@email.com', 'AFB001003', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'Ana Makiling', 'Adult', '09444555666', 'ana@email.com', 'AFB001004', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(5, 'Diego Silang', 'Youth', '09777888999', 'diego@email.com', 'AFB001005', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56');

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
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `start_date`, `end_date`, `event_time`, `location`, `type`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Sunday Worship Service', '2026-02-05', NULL, '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Completed', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'Midweek Prayer Meeting', '2026-02-09', NULL, '19:00:00', 'Fellowship Hall', 'Midweek Service', 'Completed', 'Wednesday prayer and Bible study', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'Youth Fellowship Night', '2026-02-11', NULL, '18:00:00', 'Youth Room', 'Special Event', 'Completed', 'Monthly youth gathering', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'Sunday Worship Service', '2026-02-12', NULL, '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Ongoing', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(5, 'Christmas Special', '2026-02-26', '2026-02-28', '18:00:00', 'Main Sanctuary', 'Special Event', 'Upcoming', 'Christmas celebration and dinner', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1, 1, 'LOGIN', 'User admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-12 03:46:02'),
(2, 1, 'LOGIN', 'User admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 01:29:26'),
(3, 1, 'LOGOUT', 'User admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 03:01:50'),
(4, 1, 'LOGIN', 'User admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 03:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `church` enum('AFB Mangaan','AFB Lettac Sur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MD5 hashed password',
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','viewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'operator',
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_church` (`church`, `username`),
  KEY `idx_church` (`church`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `church`, `username`, `password`, `fullname`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AFB Mangaan', 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'AFB Mangaan', 'operator', '5f4dcc3b5aa765d61d8327deb882cf99', 'Default Operator', 'operator', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'AFB Lettac Sur', 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'AFB Lettac Sur', 'operator', '5f4dcc3b5aa765d61d8327deb882cf99', 'Default Operator', 'operator', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56');

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
