# Multi-stage Dockerfile for AlgoExpertHub Trading Platform
# Optimized for Laravel 10.x with Octane (Swoole), Horizon, and Queue Workers

# ============================================================================
# Stage 1: Composer Dependencies
# ============================================================================
FROM composer:2 AS composer-dependencies

WORKDIR /app

# Copy composer files
COPY main/composer.json main/composer.lock ./

# Install dependencies (no dev dependencies for production)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# ============================================================================
# Stage 2: NPM Dependencies and Asset Compilation
# ============================================================================
FROM node:18-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY main/package.json main/package-lock.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for compilation
COPY main/webpack.mix.js ./
COPY main/resources ./resources
COPY main/public ./public

# Build assets
RUN npm run production

# ============================================================================
# Stage 3: Production Image
# ============================================================================
FROM php:8.1-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    cron \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets \
    opcache

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Swoole extension (required for Laravel Octane)
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Install gRPC extension (required by some trading APIs)
RUN pecl install grpc \
    && docker-php-ext-enable grpc

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "upload_max_filesize=50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size=50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "max_execution_time=300" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "opcache.memory_consumption=256" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "opcache.interned_strings_buffer=16" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY main/ /var/www/html/

# Copy vendor from composer stage
COPY --from=composer-dependencies /app/vendor /var/www/html/vendor

# Copy compiled assets from node stage
COPY --from=node-builder /app/public /var/www/html/public

# Copy Docker configuration files
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create directories for logs
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage/logs

# Expose ports
# 8000: Laravel Octane (Swoole)
# 9000: Horizon Dashboard (accessible via Octane)
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Default command (can be overridden)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
