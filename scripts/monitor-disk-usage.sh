#!/bin/bash
# Monitor disk usage and log directory size
# Sends warnings if thresholds are exceeded

DISK_THRESHOLD=80
LOG_SIZE_THRESHOLD_MB=500
LOG_DIR="/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/main/storage/logs"

echo "[$(date)] Starting disk usage monitoring..."

# Check overall disk usage
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')

if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    echo "[$(date)] WARNING: Disk usage is ${DISK_USAGE}% (threshold: ${DISK_THRESHOLD}%)"
    echo "Disk usage details:"
    df -h /
    echo ""
    echo "Top 10 largest directories in /opt/1panel:"
    du -sh /opt/1panel/apps/openresty/openresty/www/sites/*/index/main/storage/logs 2>/dev/null | sort -rh | head -10
else
    echo "[$(date)] Disk usage is healthy: ${DISK_USAGE}%"
fi

# Check log directory size
LOG_SIZE=$(du -sm ${LOG_DIR} 2>/dev/null | awk '{print $1}')
if [ "$LOG_SIZE" -gt "$LOG_SIZE_THRESHOLD_MB" ]; then
    echo "[$(date)] WARNING: Log directory is ${LOG_SIZE}MB (threshold: ${LOG_SIZE_THRESHOLD_MB}MB)"
    echo "Largest log files:"
    find ${LOG_DIR} -type f -name "laravel-*.log*" -exec du -h {} \; | sort -rh | head -10
else
    echo "[$(date)] Log directory size is healthy: ${LOG_SIZE}MB"
fi

echo "[$(date)] Monitoring completed!"
