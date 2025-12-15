# Deployment Guide

> [!TIP]
> **📚 Detailed Configuration Documentation Available**
> 
> For comprehensive auto-generated configuration documentation, see:
> 
> **[Configuration Guide](../.qoder/repowiki/en/content/Configuration/Configuration.md)** - Complete configuration documentation
>
> Related sections:
> - [Environment Configuration](../.qoder/repowiki/en/content/Configuration/Environment%20Configuration.md) - .env setup and variables
> - [Database, Cache & Queue Configuration](../.qoder/repowiki/en/content/Configuration/Database,%20Cache%20&%20Queue%20Configuration.md) - Infrastructure setup
> - [Service Integration Configuration](../.qoder/repowiki/en/content/Configuration/Service%20Integration%20Configuration.md) - External services
> - [Performance & Security Configuration](../.qoder/repowiki/en/content/Configuration/Performance%20&%20Security%20Configuration.md) - Optimization and hardening

---

Complete guide for deploying AlgoExpertHub Trading Signal Platform to production environments.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Server Requirements](#server-requirements)
- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Installation Steps](#installation-steps)
- [Configuration](#configuration)
- [Queue Workers Setup](#queue-workers-setup)
- [Cron Jobs](#cron-jobs)
- [Performance Optimization](#performance-optimization)
- [Security Hardening](#security-hardening)
- [Monitoring & Logging](#monitoring--logging)
- [Backup Strategy](#backup-strategy)
- [SSL/TLS Setup](#ssltls-setup)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before deploying, ensure you have:

- **PHP**: 8.0.2 or higher
- **Composer**: Latest version
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Node.js**: 14+ (for asset compilation)
- **NPM/Yarn**: For frontend dependencies
- **Supervisor**: For queue workers (recommended)
- **SSL Certificate**: For HTTPS (Let's Encrypt recommended)

---

## Server Requirements

### Minimum Requirements

- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB SSD
- **Bandwidth**: 100Mbps

### Recommended Requirements

- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Storage**: 50GB+ SSD
- **Bandwidth**: 1Gbps

### PHP Extensions Required

```bash
php -m | grep -E "pdo|mbstring|xml|curl|zip|gd|openssl|json|bcmath"
```

Required extensions:
- `pdo_mysql`
- `mbstring`
- `xml`
- `curl`
- `zip`
- `gd` or `imagick`
- `openssl`
- `json`
- `bcmath`
- `fileinfo`
- `tokenizer`

---

## Pre-Deployment Checklist

- [ ] Server meets minimum requirements
- [ ] Domain name configured with DNS
- [ ] SSL certificate obtained
- [ ] Database created and credentials ready
- [ ] Email service configured (SMTP)
- [ ] Payment gateway credentials ready
- [ ] Telegram bot token (if using Telegram features)
- [ ] API keys for external services ready
- [ ] Backup strategy planned

---

## Installation Steps

### 1. Clone Repository

```bash
cd /var/www
git clone <repository-url> algotrad
cd algotrad/main
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install frontend dependencies (if needed)
npm install --production
npm run production
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with production values
nano .env
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --class=DatabaseSeeder

# Or import SQL file
mysql -u username -p database_name < database/sql/database.sql
```

### 5. Storage & Permissions

```bash
# Create storage directories
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Set ownership
chown -R www-data:www-data .
```

### 6. Cache Configuration

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="AlgoExpertHub"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=algotrad_db
DB_USERNAME=algotrad_user
DB_PASSWORD=secure_password

# Queue
QUEUE_CONNECTION=database
# Or use Redis for better performance:
# QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=file
# Or use Redis:
# CACHE_DRIVER=redis

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Redis (if using)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Telegram Bot (if using)
TELEGRAM_BOT_TOKEN=your_bot_token

# Payment Gateways
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# OpenRouter (if using)
OPENROUTER_API_KEY=your_api_key
```

### Web Server Configuration

#### Apache (.htaccess)

Ensure mod_rewrite is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

The `.htaccess` file should already be in the public directory.

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/algotrad/main/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Queue Workers Setup

### Using Supervisor (Recommended)

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/algotrad/main/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/algotrad/main/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Manual Queue Worker

```bash
php artisan queue:work --tries=3 --timeout=60
```

---

## Cron Jobs

Laravel scheduler requires a single cron entry:

```bash
sudo crontab -e
```

Add:

```cron
* * * * * cd /var/www/algotrad/main && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel's scheduled tasks:
- Monitor positions (every minute)
- Update analytics (daily)
- Expire subscriptions (hourly)
- Cleanup old jobs (daily)

---

## Performance Optimization

### 1. OpCache Configuration

Edit `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Database Optimization

```sql
-- Add indexes for frequently queried columns
ALTER TABLE signals ADD INDEX idx_published (is_published, published_date);
ALTER TABLE plan_subscriptions ADD INDEX idx_current (user_id, is_current);
ALTER TABLE payments ADD INDEX idx_status (status, created_at);
```

### 3. Redis Caching

If using Redis:

```bash
# Install Redis
sudo apt-get install redis-server

# Update .env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### 4. CDN for Assets

Configure CDN in `.env`:

```env
ASSET_URL=https://cdn.yourdomain.com
```

### 5. Image Optimization

Use Intervention Image optimization:

```php
// In SignalService or similar
$image->save($path, 80); // 80% quality
```

---

## Security Hardening

### 1. File Permissions

```bash
# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Storage and cache
chmod -R 775 storage bootstrap/cache
```

### 2. Hide Sensitive Files

Ensure `.env` is not accessible:

```nginx
location ~ /\. {
    deny all;
}
```

### 3. Rate Limiting

Configure in `app/Http/Kernel.php`:

```php
'api' => [
    'throttle:60,1',
],
```

### 4. CSRF Protection

Enabled by default. Ensure `@csrf` in all forms.

### 5. SQL Injection Prevention

Always use Eloquent ORM or parameterized queries.

### 6. XSS Protection

Use Blade `{{ }}` for output escaping.

### 7. Encryption

Encrypt sensitive data:

```php
$encrypted = encrypt($value);
$decrypted = decrypt($encrypted);
```

---

## Monitoring & Logging

### 1. Log Rotation

Configure logrotate:

```bash
sudo nano /etc/logrotate.d/laravel
```

```
/var/www/algotrad/main/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 2. Application Monitoring

Monitor:
- Queue size
- Failed jobs
- Response times
- Error rates
- Database connections

### 3. Health Checks

Create health check endpoint:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'queue' => Queue::size(),
    ]);
});
```

---

## Backup Strategy

### 1. Database Backups

```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p database_name > /backups/db_$DATE.sql
gzip /backups/db_$DATE.sql

# Keep last 30 days
find /backups -name "db_*.sql.gz" -mtime +30 -delete
```

### 2. File Backups

```bash
# Backup storage and uploads
tar -czf /backups/files_$DATE.tar.gz storage/app/public asset/uploads
```

### 3. Automated Backups

Use Laravel scheduler:

```php
$schedule->command('backup:run')->daily();
```

Or use `spatie/laravel-backup` package.

---

## SSL/TLS Setup

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx
# or
sudo apt-get install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
# or
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

- Check `storage/logs/laravel.log`
- Verify file permissions
- Check `.env` configuration
- Clear cache: `php artisan config:clear`

#### 2. Queue Not Processing

- Check supervisor status: `sudo supervisorctl status`
- Check queue table: `SELECT COUNT(*) FROM jobs`
- Restart workers: `sudo supervisorctl restart laravel-worker:*`

#### 3. Database Connection Error

- Verify credentials in `.env`
- Check MySQL service: `sudo systemctl status mysql`
- Test connection: `mysql -u username -p database_name`

#### 4. Permission Denied

- Fix ownership: `chown -R www-data:www-data .`
- Fix permissions: `chmod -R 775 storage bootstrap/cache`

#### 5. Slow Performance

- Enable OpCache
- Use Redis for cache/queue
- Optimize database queries
- Add indexes
- Use CDN for assets

---

## Post-Deployment Checklist

- [ ] Application accessible via HTTPS
- [ ] Queue workers running
- [ ] Cron jobs configured
- [ ] Email sending works
- [ ] Payment gateways tested
- [ ] Telegram bot connected (if using)
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Logs rotating
- [ ] Performance optimized

---

## Maintenance

### Regular Tasks

1. **Daily**: Check logs, monitor queue size
2. **Weekly**: Review failed jobs, check disk space
3. **Monthly**: Update dependencies, review security
4. **Quarterly**: Performance audit, backup verification

### Updates

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart workers
sudo supervisorctl restart laravel-worker:*
```

---

**Last Updated**: 2025-12-02
