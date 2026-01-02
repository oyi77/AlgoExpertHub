# Deployment Guide

## Prerequisites

- PHP 8.1+
- MariaDB 10.6+
- Redis 6.0+
- Composer 2.x
- Node.js 18+ (for asset compilation)

## Environment Setup

### 1. Clone Repository

```bash
git clone https://github.com/oyi77/AlgoExpertHub.git
cd AlgoExpertHub/main
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your configuration:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mariadb
DB_HOST=your-mariadb-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### 4. Database Migration

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
```

### 5. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 6. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Queue Workers

### Supervisor Configuration

Create `/etc/supervisor/conf.d/algoexperthub-worker.conf`:

```ini
[program:algoexperthub-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start algoexperthub-worker:*
```

## Scheduled Tasks

Add to crontab:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    root /path/to/public;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Monitoring

### Application Monitoring
- Laravel Telescope (development only)
- Sentry for error tracking
- New Relic for performance monitoring

### Server Monitoring
- CPU, memory, disk usage
- Database connection pool
- Redis memory usage
- Queue worker health

## Backup Strategy

### Database Backups
```bash
# Daily automated backups
0 2 * * * /usr/bin/mysqldump -u user -p'password' database > /backups/db-$(date +\%Y\%m\%d).sql
```

### File Backups
- Daily backups of `storage/app`
- Weekly full server snapshots
- Offsite backup retention: 30 days

## Rollback Procedure

1. Stop queue workers
2. Restore database from backup
3. Checkout previous release
4. Run migrations down if needed
5. Clear caches
6. Restart queue workers
7. Verify application health

## Zero-Downtime Deployment

1. Deploy new code to staging directory
2. Run migrations on production database
3. Symlink switch from old to new release
4. Reload PHP-FPM gracefully
5. Restart queue workers
6. Monitor error rates

## Security Checklist

- [ ] HTTPS enabled with valid certificate
- [ ] Firewall configured (ports 80, 443, 22 only)
- [ ] Database not publicly accessible
- [ ] Redis password protected
- [ ] `.env` file permissions set to 600
- [ ] Debug mode disabled in production
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] Automated security updates enabled
