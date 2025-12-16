#!/bin/bash
# Fix Laravel storage permissions
# Run this daily or after log rotation

cd "$(dirname "$0")" || exit 1

# Fix logs directory
chmod -R 777 storage/logs/
chown -R 1panel:1panel storage/logs/

# Fix other storage directories
chmod -R 777 storage/framework/ 2>/dev/null || true
chmod -R 777 storage/app/ 2>/dev/null || true
chown -R 1panel:1panel storage/framework/ 2>/dev/null || true
chown -R 1panel:1panel storage/app/ 2>/dev/null || true

# Fix language files
chmod -R 777 resources/lang/ 2>/dev/null || true
chown -R 1panel:1panel resources/lang/ 2>/dev/null || true

echo "Storage permissions fixed at $(date)"
