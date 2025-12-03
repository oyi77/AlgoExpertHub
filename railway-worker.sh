#!/bin/bash
set -e

echo "👷 Starting Railway queue worker..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Set APP_BASE_PATH if not set
export APP_BASE_PATH="${APP_BASE_PATH:-main}"

cd "$APP_BASE_PATH"

echo -e "${YELLOW}⏳ Waiting for database connection...${NC}"
# Wait for database to be ready (Railway auto-provisions)
for i in {1..30}; do
    if php artisan db:show > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Database connected!${NC}"
        break
    fi
    echo "Attempt $i/30: Waiting for database..."
    sleep 2
done

echo -e "${YELLOW}👷 Starting queue worker...${NC}"

# Start queue worker with auto-restart
exec php artisan queue:work \
    --queue=default,high,low \
    --tries=3 \
    --timeout=120 \
    --sleep=3 \
    --max-time=3600 \
    --max-jobs=1000 \
    --stop-when-empty=false
