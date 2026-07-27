-- AFB Mangaan Attendance System - Database Schema Migration & Optimization
-- Safe execution script for MySQL / phpMyAdmin

USE `afb_mangaan_db`;

-- 1. Modify category column to VARCHAR(50) to support Pastors, Leaders, Ministers, etc.
ALTER TABLE `attendees` 
  MODIFY `category` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'WMO';

-- 2. Add ministry column to attendees if it does not exist
SET @dbname = DATABASE();
SET @tablename = "attendees";
SET @columnname = "ministry";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `attendees` ADD COLUMN `ministry` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `category`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Safely drop duplicate index idx_qr_token if it exists
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'attendees' AND INDEX_NAME = 'idx_qr_token'
  ) > 0,
  "ALTER TABLE `attendees` DROP INDEX `idx_qr_token`",
  "SELECT 1"
));
PREPARE dropIdxIfExists FROM @preparedStatement;
EXECUTE dropIdxIfExists;
DEALLOCATE PREPARE dropIdxIfExists;

-- 4. Safely add composite index idx_church_status_name if it does not exist
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'attendees' AND INDEX_NAME = 'idx_church_status_name'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `attendees` ADD INDEX `idx_church_status_name` (`church`, `status`, `fullname`)"
));
PREPARE addIdx1 FROM @preparedStatement;
EXECUTE addIdx1;
DEALLOCATE PREPARE addIdx1;

-- 5. Safely add composite index idx_church_start_date on events if it does not exist
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'events' AND INDEX_NAME = 'idx_church_start_date'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `events` ADD INDEX `idx_church_start_date` (`church`, `start_date`, `status`)"
));
PREPARE addIdx2 FROM @preparedStatement;
EXECUTE addIdx2;
DEALLOCATE PREPARE addIdx2;

-- 6. Safely add composite index idx_event_status on attendance_logs if it does not exist
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'attendance_logs' AND INDEX_NAME = 'idx_event_status'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `attendance_logs` ADD INDEX `idx_event_status` (`event_id`, `status`)"
));
PREPARE addIdx3 FROM @preparedStatement;
EXECUTE addIdx3;
DEALLOCATE PREPARE addIdx3;

-- 7. Create categories table for dynamic category management
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `church` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AFB Mangaan',
  `name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_church_category` (`church`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`church`, `name`) VALUES
('AFB Mangaan', 'MCYO'),
('AFB Mangaan', 'WMO'),
('AFB Mangaan', 'CCMO'),
('AFB Mangaan', 'KIDS'),
('AFB Mangaan', 'Visitors'),
('AFB Mangaan', 'Pastors'),
('AFB Mangaan', 'Leaders'),
('AFB Mangaan', 'Ministers'),
('AFB Mangaan', 'Other'),
('AFB Lettac Sur', 'MCYO'),
('AFB Lettac Sur', 'WMO'),
('AFB Lettac Sur', 'CCMO'),
('AFB Lettac Sur', 'KIDS'),
('AFB Lettac Sur', 'Visitors'),
('AFB Lettac Sur', 'Pastors'),
('AFB Lettac Sur', 'Leaders'),
('AFB Lettac Sur', 'Ministers'),
('AFB Lettac Sur', 'Other');

