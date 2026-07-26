-- AFB Mangaan Attendance System - Database Schema Migration & Optimization
-- Run this script in MySQL / phpMyAdmin to apply performance indexes and fix ENUM issues

USE `afb_mangaan_db`;

-- 1. Fix category ENUM to prevent data truncation when adding Visitors / Other
ALTER TABLE `attendees` 
  MODIFY `category` enum('MCYO','WMO','CCMO','KIDS','Visitors','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'WMO';

-- 2. Drop duplicate index on qr_token (UNIQUE KEY qr_token already exists)
ALTER TABLE `attendees` DROP INDEX `idx_qr_token`;

-- 3. Add composite index for multi-tenant attendee queries (church + status + fullname)
ALTER TABLE `attendees` ADD INDEX `idx_church_status_name` (`church`, `status`, `fullname`);

-- 4. Add composite index for event date range queries (church + start_date + status)
ALTER TABLE `events` ADD INDEX `idx_church_start_date` (`church`, `start_date`, `status`);

-- 5. Add composite index for event attendance logs lookup (event_id + status)
ALTER TABLE `attendance_logs` ADD INDEX `idx_event_status` (`event_id`, `status`);
