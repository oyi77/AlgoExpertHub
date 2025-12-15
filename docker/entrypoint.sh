#!/bin/bash
# Entrypoint script for AlgoExpertHub Docker container
# Handles initialization, migrations, and service startup

set -e

echo "=========================================="
echo "AlgoExpertHub Container Starting"
echo "=========================================="

# Function to wait for service
wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    local max_attempts=30
    local attempt=1

    echo "Waiting for $service to be ready..."
    
    while [ $attempt -le $max_attempts ]; do
        if nc -z "$host" "$port" 2>/dev/null; then
            echo "✓ $service is ready!"
            return 0
        fi
        
        echo "  Attempt $attempt/$max_attempts: $service not ready yet..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    echo "✗ Failed to connect to $service after $max_attempts attempts"
    return 1
}

# Wait for MySQL
if [ -n "$DB_HOST" ]; then
    wait_for_service "$DB_HOST" "${DB_PORT:-3306}" "MySQL"
fi

# Wait for Redis
if [ -n "$REDIS_HOST" ]; then
    wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}" "Redis"
fi

echo ""
echo "=========================================="
echo "Setting up Laravel application"
echo "=========================================="

# Navigate to application directory
cd /var/www/html

# Set proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 775 storage bootstrap/cache

# Create storage directories if they don't exist
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:CHANGEME" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Cache configuration (speeds up application)
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations if enabled
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Run seeders if enabled
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
fi

# Create storage link
if [ ! -L public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Clear any stale cache
echo "Clearing stale cache..."
php artisan cache:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "Application setup complete!"
echo "=========================================="
echo ""
echo "Services starting:"
echo "  - Laravel Octane (Swoole) on port 8000"
echo "  - Laravel Horizon (Queue Management)"
echo "  - Laravel Queue Workers (4 processes)"
echo "  - Laravel Scheduler (Cron)"
echo ""
echo "=========================================="

# Execute the main command (supervisor or custom command)
exec "$@"
