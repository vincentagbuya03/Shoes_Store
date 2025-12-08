#!/bin/bash
# Database Export Script for Shoes Store
# This exports your local database to SQL file for importing on hosting

# Make sure you have mysqldump installed
# On Windows with Laragon, it's usually at: C:\laragon\bin\mysql\mysql8.0.1\bin\mysqldump.exe

# Set your local database credentials
DB_HOST="localhost"
DB_USER="root"
DB_PASS="vincentagbuya123"
DB_NAME="shoestore"

# Export to file
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="shoestore_backup_$TIMESTAMP.sql"

echo "Exporting database..."
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > "$FILENAME"

if [ $? -eq 0 ]; then
    echo "✓ Database exported successfully to: $FILENAME"
    echo "Now upload this file to your hosting and import it in phpMyAdmin"
else
    echo "✗ Export failed"
    exit 1
fi
