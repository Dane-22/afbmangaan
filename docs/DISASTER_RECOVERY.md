# Disaster Recovery Plan

This document outlines the recovery procedures for the AFB Mangaan Attendance System.

## Recovery Point Objective (RPO)
- Maximum acceptable data loss: 24 hours.
- Automated backups run daily at midnight.

## Recovery Time Objective (RTO)
- Maximum acceptable downtime: 4 hours.

## Backup Process
Backups are orchestrated via `scripts/backup.sh`.
1. The script performs a `mysqldump` of the `afb_mangaan_db` database.
2. It packages the SQL dump along with any user-uploaded files into a compressed `tar.gz` archive.
3. Archives are saved in the `backups/` directory (which should be synced to an offsite location).

## Restoration Procedure

### 1. Database Restoration
If the database gets corrupted, restore the latest SQL dump:
```bash
mysql -u root -p afb_mangaan_db < backups/db_YYYYMMDD_HHMMSS.sql
```

### 2. File Restoration
Extract the compressed archive to restore application uploads:
```bash
tar -xzf backups/full_YYYYMMDD_HHMMSS.tar.gz -C /path/to/webroot/
```

### 3. Verification
- Verify database connectivity using the `/api/health.php` endpoint.
- Attempt a login with administrator credentials.
- Verify attendance logs correspond to the backup date.
