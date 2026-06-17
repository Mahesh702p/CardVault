#!/bin/bash

# --- CardVault Backup Script ---
# This script backs up the database and the uploaded card images.

# Get current script directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

# 1. Load database credentials from .env
if [ -f .env ]; then
    # Read variables from .env, ignoring comments and empty lines
    export $(grep -v '^#' .env | xargs)
else
    echo "Error: .env file not found. Please run this script in the CardVault project root."
    exit 1
fi

# Set default host if not set
DB_HOST=${DB_HOST:-localhost}

# Define backup directories
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql"
UPLOADS_BACKUP_FILE="${BACKUP_DIR}/uploads_backup_${TIMESTAMP}.zip"

# Create backups directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "=========================================="
echo "Starting CardVault Data Backup..."
echo "Timestamp: $(date)"
echo "=========================================="

# 2. Backup MySQL Database
echo "Running mysqldump for database: $DB_NAME..."
mysqldump --no-tablespaces -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Database backup saved to: $DB_BACKUP_FILE"
else
    echo "✗ Error: Database backup failed!"
    exit 1
fi

# 3. Backup Uploads Directory (Card Images)
echo "Archiving card images from public/uploads/..."
if [ -d "public/uploads" ]; then
    zip -r "$UPLOADS_BACKUP_FILE" public/uploads/ > /dev/null
    if [ $? -eq 0 ]; then
        echo "✓ Card images archived to: $UPLOADS_BACKUP_FILE"
    else
        echo "✗ Error: Card images archival failed! (Is 'zip' installed? run: sudo apt install zip)"
    fi
else
    echo "! Warning: public/uploads directory does not exist yet (no cards scanned)."
fi

# 4. Clean up backups older than 7 days to save disk space
echo "Cleaning up backups older than 7 days..."
find "$BACKUP_DIR" -type f -mtime +7 -name "*.sql" -delete
find "$BACKUP_DIR" -type f -mtime +7 -name "*.zip" -delete

echo "=========================================="
echo "Backup Completed Successfully!"
echo "=========================================="
