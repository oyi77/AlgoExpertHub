# Deployment

<cite>
**Referenced Files in This Document**   
- [start-octane.sh](file://main/start-octane.sh)
- [supervisor-octane.conf](file://main/supervisor-octane.conf)
- [supervisor-laravel-worker.conf](file://main/supervisor-laravel-worker.conf)
- [supervisor-horizon.conf](file://main/supervisor-horizon.conf)
- [deployment-guide.md](file://docs/deployment-guide.md)
- [Procfile](file://Procfile)
- [railway.json](file://railway.json)
- [composer.json](file://main/composer.json)
- [octane.php](file://main/config/octane.php)
- [horizon.php](file://main/config/horizon.php)
- [queue.php](file://main/config/queue.php)
- [database.php](file://main/config/database.php)
</cite>

## Table of Contents
1. [Infrastructure Requirements](#infrastructure-requirements)
2. [Process Management with Supervisor](#process-management-with-supervisor)
3. [Deployment Strategies](#deployment-strategies)
4. [Scaling Considerations](#scaling-considerations)
5. [Monitoring and Logging](#monitoring-and-logging)
6. [Backup and Disaster Recovery](#backup-and-disaster-recovery)
7. [Security Hardening](#security-hardening)
8. [Performance Optimization](#performance-optimization)
9. [Load Testing Recommendations](#load-testing-recommendations)

## Infrastructure Requirements

The application requires specific infrastructure specifications to ensure optimal performance and reliability in production environments.

### Server Specifications
The system supports deployment on both traditional servers and containerized environments. Minimum requirements include 2 CPU cores, 4GB RAM, and 20GB SSD storage. For production workloads handling high volumes of trading signals and user traffic, recommended specifications are 4+ CPU cores, 8GB+ RAM, and 50GB+ SSD storage.

### Operating System Requirements
The application is compatible with Linux-based operating systems, particularly Ubuntu 20.04 LTS or later. Required system components include:
- PHP 8.0.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Nginx 1.18+ or Apache 2.4+
- Redis 6.0+ (recommended for queue and cache)
- Node.js 14+ (for asset compilation)

### Network Configuration
Production deployment requires proper network configuration including:
- HTTPS via SSL/TLS (Let's Encrypt recommended)
- Port 443 for web traffic
- Port 8000 for Octane server (if used)
- Database port (3306 for MySQL) restricted to internal network
- Redis port (6379) restricted to internal network
- Proper DNS configuration for domain and subdomains

**Section sources**
- [deployment-guide.md](file://docs/deployment-guide.md#L38-L52)
- [composer.json](file://main/composer.json#L8)

## Process Management with Supervisor

The application utilizes Supervisor to manage critical background processes including Laravel workers, Horizon, and Octane servers.

### Laravel Queue Workers Configuration
Supervisor manages Laravel queue workers through the configuration file `supervisor-laravel-worker.conf`. The setup runs four worker processes that handle jobs from multiple queues (notifications, default, high priority). Each worker automatically restarts on failure and logs output to `storage/logs/worker.log`.

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php main/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=notifications,default,high
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=main/storage/logs/worker.log
stopwaitsecs=3600
```

### Horizon Configuration
Laravel Horizon provides a dashboard and configuration system for Redis queues. The Supervisor configuration `supervisor-horizon.conf` ensures the Horizon process runs continuously under the www-data user. This configuration is essential for monitoring queue performance, job throughput, and failed jobs.

**Section sources**
- [supervisor-laravel-worker.conf](file://main/supervisor-laravel-worker.conf)
- [supervisor-horizon.conf](file://main/supervisor-horizon.conf)
- [horizon.php](file://main/config/horizon.php)

### Octane Server Management
Laravel Octane is configured to handle HTTP requests using Swoole server. The `start-octane.sh` script executes Octane within a Docker container, binding to port 8000 with 4 worker processes. Supervisor manages this process through `supervisor-octane.conf`, ensuring automatic restart on failure and logging to `storage/logs/octane.log`.

```bash
#!/bin/bash
CONTAINER_NAME="1Panel-php8-mrTy"
ARTISAN_PATH="/www/sites/aitradepulse.com/index/main/artisan"

docker exec -i ${CONTAINER_NAME} php ${ARTISAN_PATH} octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=4 \
    --max-requests=1000
```

**Diagram sources**
- [start-octane.sh](file://main/start-octane.sh)
- [supervisor-octane.conf](file://main/supervisor-octane.conf)

**Section sources**
- [start-octane.sh](file://main/start-octane.sh)
- [supervisor-octane.conf](file://main/supervisor-octane.conf)
- [octane.php](file://main/config/octane.php)

## Deployment Strategies

The application supports multiple deployment strategies including traditional server deployment, containerization, and cloud platform deployment.

### Traditional Server Deployment
Traditional deployment follows standard Laravel deployment practices:
1. Clone repository to server
2. Install PHP dependencies via `composer install --optimize-autoloader --no-dev`
3. Configure environment variables in `.env` file
4. Run database migrations with `php artisan migrate --force`
5. Set proper file permissions and ownership
6. Configure web server (Nginx/Apache) with SSL
7. Set up Supervisor for queue workers and Octane
8. Configure cron job for Laravel scheduler

### Containerization with Docker
The application can be containerized using Docker. The `railway.json` configuration indicates the use of Nixpacks for building, with custom build and start commands. The build process installs Composer dependencies and caches configuration, while the start command serves the application on the specified port.

```json
{
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "cd main && composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache && php artisan view:cache"
  },
  "deploy": {
    "startCommand": "cd main && php artisan serve --host=0.0.0.0 --port=$PORT",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### Cloud Platform Deployment
The application is configured for deployment on Railway platform using the `Procfile` and `railway.json` configuration files. The architecture separates services into web, worker, and scheduler processes:

```procfile
web: bash railway-deploy.sh
worker: bash railway-worker.sh
scheduler: bash railway-scheduler.sh
```

This separation allows independent scaling of web servers, queue processing, and scheduled tasks. The platform automatically handles environment variables, restart policies, and service discovery.

**Diagram sources**
- [Procfile](file://Procfile)
- [railway.json](file://railway.json)

**Section sources**
- [Procfile](file://Procfile)
- [railway.json](file://railway.json)
- [deployment-guide.md](file://docs/deployment-guide.md#L89-L148)

## Scaling Considerations

The application architecture supports horizontal scaling to handle high volumes of trading signals and user traffic.

### Horizontal Scaling Architecture
The system can be scaled horizontally by adding additional web servers behind a load balancer. Database scaling can be achieved through read replicas for reporting and analytics queries. Queue processing scales independently through additional Horizon workers.

### Database Optimization for High Load
For handling high volumes of trading signals, database optimization is critical. The configuration includes proper indexing strategies for frequently queried tables such as signals, plan_subscriptions, and payments. Redis is recommended for caching frequently accessed data and managing session storage.

```php
// Database configuration for high availability
'mysql' => [
    'read' => [
        'host' => ['192.168.1.1', '192.168.1.2'],
    ],
    'write' => [
        'host' => '192.168.1.1',
    ],
    'sticky' => true,
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'options' => [
        PDO::ATTR_TIMEOUT => 30,
    ],
],
```

### Queue Processing at Scale
The queue system is designed to handle high volumes of trading signals through multiple queue workers and priority queues. The configuration processes jobs from high, default, and notifications queues, allowing critical trading signals to be processed with higher priority.

**Section sources**
- [database.php](file://main/config/database.php)
- [queue.php](file://main/config/queue.php)
- [deployment-guide.md](file://docs/deployment-guide.md#L364-L371)

## Monitoring and Logging

Comprehensive monitoring and logging are implemented using Laravel Telescope, Horizon, and external monitoring tools.

### Laravel Telescope and Horizon
Laravel Telescope provides detailed insights into requests, exceptions, log entries, database queries, mail, notifications, jobs, events, and broadcasts. Laravel Horizon offers monitoring for Redis queues, including metrics on wait time, throughput, and job latency. Both tools are configured in their respective configuration files.

### External Monitoring Integration
The application supports integration with external monitoring tools through health check endpoints and structured logging. The deployment guide includes a sample health check endpoint that verifies database connectivity and queue status.

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'queue' => Queue::size(),
    ]);
});
```

### Log Management
Application logs are stored in `storage/logs/` with proper rotation configuration. The deployment guide includes logrotate configuration to manage log file sizes and retention.

```bash
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

**Section sources**
- [horizon.php](file://main/config/horizon.php)
- [deployment-guide.md](file://docs/deployment-guide.md#L464-L485)

## Backup and Disaster Recovery

Robust backup and disaster recovery procedures are essential for production deployment.

### Automated Backup Strategy
The application implements automated backups through Laravel scheduler or external cron jobs. Database backups are performed daily with retention of the last 30 days. File backups include storage directories and uploaded assets.

```bash
# Daily database backup
mysqldump -u username -p database_name > /backups/db_$(date +%Y%m%d_%H%M%S).sql
gzip /backups/db_*.sql

# File backup
tar -czf /backups/files_$(date +%Y%m%d_%H%M%S).tar.gz storage/app/public asset/uploads
```

### Disaster Recovery Plan
The disaster recovery plan includes:
1. Regular backup verification
2. Off-site backup storage
3. Documented recovery procedures
4. Regular recovery testing
5. Database replication for high availability

The `spatie/laravel-backup` package is included in Composer dependencies, providing comprehensive backup functionality.

**Section sources**
- [deployment-guide.md](file://docs/deployment-guide.md#L511-L542)
- [composer.json](file://main/composer.json#L38)

## Security Hardening

Production environments require comprehensive security hardening measures.

### Server-Level Security
Server security measures include:
- Regular system updates
- Firewall configuration (UFW/iptables)
- SSH key-based authentication
- Disabled root login
- Fail2ban for intrusion prevention
- SELinux or AppArmor for mandatory access control

### Application-Level Security
Application security is implemented through:
- Environment variables for sensitive data
- HTTPS enforcement
- CSRF protection for forms
- Input validation and sanitization
- SQL injection prevention via Eloquent ORM
- XSS protection through Blade templating
- Rate limiting for API endpoints
- Proper file permissions (755 for directories, 644 for files)

### Data Security
Sensitive data is protected through encryption at rest and in transit. The application uses Laravel's built-in encryption for sensitive fields and ensures all data transmission occurs over HTTPS.

**Section sources**
- [deployment-guide.md](file://docs/deployment-guide.md#L406-L462)

## Performance Optimization

Multiple performance optimization techniques are implemented to ensure responsive application performance.

### Caching Strategies
The application utilizes multiple caching layers:
- OpCache for PHP bytecode caching
- Redis for application data caching
- Database query caching
- Route and configuration caching
- View caching

OpCache configuration is optimized for production:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### Database Performance
Database performance is optimized through:
- Proper indexing of frequently queried columns
- Query optimization and eager loading
- Database connection pooling
- Read/write separation
- Query caching

### Asset Optimization
Frontend assets are optimized through:
- Minification of CSS and JavaScript
- Image optimization with 80% quality
- CDN distribution for static assets
- Browser caching headers

**Section sources**
- [deployment-guide.md](file://docs/deployment-guide.md#L348-L404)
- [performance.php](file://main/config/performance.php)

## Load Testing Recommendations

Load testing is critical to ensure the application can handle peak traffic and trading signal volumes.

### Testing Strategy
Load testing should simulate:
- Concurrent user logins and dashboard access
- High-volume trading signal ingestion
- Webhook processing under load
- Report generation and analytics processing
- Payment processing workflows

### Performance Metrics
Key performance metrics to monitor during load testing include:
- Response time under load (target < 500ms)
- Error rate (target < 0.1%)
- Throughput (requests per second)
- Database query performance
- Memory usage
- CPU utilization

### Stress Testing
Stress testing should identify system limits and failure points. Testing should gradually increase load until the system reaches capacity, documenting performance degradation and failure modes.

**Section sources**
- [deployment-guide.md](file://docs/deployment-guide.md#L595-L601)