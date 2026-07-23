#!/bin/bash
# AFB Mangaan Attendance System - Backup Script
# Usage: ./backup.sh

BACKUP_DIR="../backups"
DB_NAME="afb_mangaan_db"
DB_USER="root"
DB_PASS="" # Add your DB password here or use .my.cnf

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

DATE=$(date +%Y%m%d_%H%M%S)
DB_BACKUP_FILE="$BACKUP_DIR/db_$DATE.sql"
FULL_BACKUP_FILE="$BACKUP_DIR/full_$DATE.tar.gz"

echo "Starting database backup..."
if mysqldump -u "$DB_USER" "$DB_NAME" > "$DB_BACKUP_FILE"; then
    echo "Database backup successful: $DB_BACKUP_FILE"
else
    echo "Database backup failed!"
    exit 1
fi

echo "Creating full archive..."
# Archive the SQL file and any potential uploads (e.g., if there's an uploads/ directory later)
tar -czf "$FULL_BACKUP_FILE" "$DB_BACKUP_FILE"

echo "Backup completed: $FULL_BACKUP_FILE"

# Optional: Clean up old backups (keep last 7 days)
# find "$BACKUP_DIR" -type f -mtime +7 -delete
