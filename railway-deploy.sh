#!/bin/bash
set -e

echo "🚀 Starting Railway deployment script..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Set APP_BASE_PATH if not set
export APP_BASE_PATH="${APP_BASE_PATH:-main}"

echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
cd "$APP_BASE_PATH"
composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

echo -e "${YELLOW}🔧 Setting up environment...${NC}"
# Copy .env.example if .env doesn't exist (Railway provides env vars)
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "Created .env from .env.example"
    fi
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo -e "${YELLOW}🔑 Generating application key...${NC}"
    php artisan key:generate --force
fi

echo -e "${YELLOW}💾 Setting up storage...${NC}"
# Create storage directories
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache

echo -e "${YELLOW}🔗 Creating storage symlink...${NC}"
php artisan storage:link || echo "Storage link already exists"

echo -e "${YELLOW}🗄️ Running database migrations...${NC}"
# Wait for database to be ready
echo "Waiting for database connection..."
for i in {1..30}; do
    if php artisan db:show > /dev/null 2>&1; then
        echo "Database connected!"
        break
    fi
    echo "Attempt $i/30: Database not ready, waiting..."
    sleep 2
done

php artisan migrate --force || echo "Migrations failed or already run"

echo -e "${YELLOW}⚡ Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd ..

echo -e "${GREEN}✅ Deployment setup complete!${NC}"
echo -e "${GREEN}Starting PHP server on port ${PORT}...${NC}"

# Railway uses PORT environment variable
# Use built-in PHP server pointing to root index.php
exec php -S 0.0.0.0:${PORT} -t . index.php
