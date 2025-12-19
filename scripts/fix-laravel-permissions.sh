#!/bin/bash
# Fix Laravel storage permissions
# This script ensures both root and 1panel users can write to storage directories

APP_DIR="/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/main"
WEB_USER="1panel"
WEB_GROUP="1panel"

echo "[$(date)] Fixing Laravel storage permissions..."

# Fix ownership
chown -R ${WEB_USER}:${WEB_GROUP} ${APP_DIR}/storage
chown -R ${WEB_USER}:${WEB_GROUP} ${APP_DIR}/bootstrap/cache

# Fix directory permissions
find ${APP_DIR}/storage -type d -exec chmod 775 {} \;
find ${APP_DIR}/bootstrap/cache -type d -exec chmod 775 {} \;

# Fix file permissions
find ${APP_DIR}/storage -type f -exec chmod 664 {} \;
find ${APP_DIR}/bootstrap/cache -type f -exec chmod 664 {} \;

# Set ACL for logs directory (allows both root and 1panel to write)
if command -v setfacl &> /dev/null; then
    echo "[$(date)] Setting ACL permissions for logs directory..."
    setfacl -R -m u:${WEB_USER}:rwx ${APP_DIR}/storage/logs
    setfacl -R -m u:root:rwx ${APP_DIR}/storage/logs
    setfacl -R -d -m u:${WEB_USER}:rwx ${APP_DIR}/storage/logs
    setfacl -R -d -m u:root:rwx ${APP_DIR}/storage/logs
    setfacl -R -d -m g:${WEB_GROUP}:rwx ${APP_DIR}/storage/logs
    echo "[$(date)] ACL permissions set successfully!"
else
    echo "[$(date)] WARNING: setfacl not found. ACL permissions not set."
fi

echo "[$(date)] Permissions fixed successfully!"
