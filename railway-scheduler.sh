#!/bin/bash
set -e

echo "⏰ Starting Railway scheduler (cron replacement)..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Set APP_BASE_PATH if not set
export APP_BASE_PATH="${APP_BASE_PATH:-main}"

cd "$APP_BASE_PATH"

echo -e "${YELLOW}⏳ Waiting for database connection...${NC}"
# Wait for database to be ready
for i in {1..30}; do
    if php artisan db:show > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Database connected!${NC}"
        break
    fi
    echo "Attempt $i/30: Waiting for database..."
    sleep 2
done

echo -e "${YELLOW}⏰ Starting Laravel scheduler...${NC}"

# Run Laravel scheduler every minute
# Railway will handle the cron-like execution
while true; do
    php artisan schedule:run --verbose --no-interaction || true
    sleep 60
done
