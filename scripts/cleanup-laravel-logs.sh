#!/bin/bash
# Cleanup old Laravel log files
# This script deletes old logs, compresses large logs, and monitors disk usage

LOG_DIR="/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/main/storage/logs"
RETENTION_DAYS=7
COMPRESSION_AGE_DAYS=1
COMPRESSED_RETENTION_DAYS=30
MAX_LOG_SIZE_MB=100

echo "[$(date)] Starting Laravel log cleanup..."

# Delete uncompressed log files older than retention period
DELETED_COUNT=$(find ${LOG_DIR} -name "laravel-*.log" -type f -mtime +${RETENTION_DAYS} -delete -print | wc -l)
echo "[$(date)] Deleted ${DELETED_COUNT} log files older than ${RETENTION_DAYS} days"

# Compress large log files older than compression age
COMPRESSED_COUNT=0
while IFS= read -r file; do
    if [ -f "$file" ]; then
        gzip "$file"
        ((COMPRESSED_COUNT++))
    fi
done < <(find ${LOG_DIR} -name "laravel-*.log" -type f -mtime +${COMPRESSION_AGE_DAYS} -size +${MAX_LOG_SIZE_MB}M)
echo "[$(date)] Compressed ${COMPRESSED_COUNT} logs larger than ${MAX_LOG_SIZE_MB}MB and older than ${COMPRESSION_AGE_DAYS} day(s)"

# Delete compressed logs older than extended retention
DELETED_GZ_COUNT=$(find ${LOG_DIR} -name "laravel-*.log.gz" -type f -mtime +${COMPRESSED_RETENTION_DAYS} -delete -print | wc -l)
echo "[$(date)] Deleted ${DELETED_GZ_COUNT} compressed logs older than ${COMPRESSED_RETENTION_DAYS} days"

# Show current disk usage
CURRENT_SIZE=$(du -sh ${LOG_DIR} | awk '{print $1}')
echo "[$(date)] Current log directory size: ${CURRENT_SIZE}"

# List remaining log files
LOG_COUNT=$(find ${LOG_DIR} -name "laravel-*.log*" -type f | wc -l)
echo "[$(date)] Total log files remaining: ${LOG_COUNT}"

echo "[$(date)] Log cleanup completed successfully!"
