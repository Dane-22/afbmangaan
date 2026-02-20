-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 12, 2026 at 03:36 AM
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
-- Database: `afb_mangaan_db.sql`
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
  `status` enum('Present','Absent') COLLATE utf8mb4_unicode_ci DEFAULT 'Present',
  `log_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logged_by` int DEFAULT NULL,
  `method` enum('Manual','QR Scan','Search') COLLATE utf8mb4_unicode_ci DEFAULT 'Manual',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `fullname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('Youth','Adult','Senior','Child') COLLATE utf8mb4_unicode_ci DEFAULT 'Adult',
  `contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique QR code identifier',
  `status` enum('Active','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
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
  `event_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Sunday Service','Midweek Service','Special Event','Meeting','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Sunday Service',
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Upcoming',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_date`, `event_time`, `location`, `type`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Sunday Worship Service', '2026-02-05', '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Completed', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'Midweek Prayer Meeting', '2026-02-09', '19:00:00', 'Fellowship Hall', 'Midweek Service', 'Completed', 'Wednesday prayer and Bible study', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(3, 'Youth Fellowship Night', '2026-02-11', '18:00:00', 'Youth Room', 'Special Event', 'Completed', 'Monthly youth gathering', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(4, 'Sunday Worship Service', '2026-02-12', '09:00:00', 'Main Sanctuary', 'Sunday Service', 'Ongoing', 'Regular Sunday worship service', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(5, 'Christmas Special', '2026-02-26', '18:00:00', 'Main Sanctuary', 'Special Event', 'Upcoming', 'Christmas celebration and dinner', 1, '2026-02-12 03:34:56', '2026-02-12 03:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MD5 hashed password',
  `fullname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','viewer') COLLATE utf8mb4_unicode_ci DEFAULT 'operator',
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56'),
(2, 'operator', '5f4dcc3b5aa765d61d8327deb882cf99', 'Default Operator', 'operator', 'Active', '2026-02-12 03:34:56', '2026-02-12 03:34:56');

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
