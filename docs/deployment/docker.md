# AlgoExpertHub - Complete Docker & Deployment Guide

> **Comprehensive guide for deploying AlgoExpertHub trading platform using Docker with automated CI/CD pipelines**

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Docker Setup](#docker-setup)
4. [Services Architecture](#services-architecture)
5. [Configuration](#configuration)
6. [Deployment Methods](#deployment-methods)
7. [CI/CD Integration](#cicd-integration)
8. [1Panel Integration](#1panel-integration)
9. [Common Commands](#common-commands)
10. [Troubleshooting](#troubleshooting)
11. [Production Deployment](#production-deployment)

---

## Overview

AlgoExpertHub is a comprehensive Laravel-based trading signal platform that requires multiple services to run effectively. This guide covers the complete Docker containerization and automated deployment setup.

### What's Included

✅ **Multi-container Docker setup** with 7 services  
✅ **Automated deployment scripts** with SSH and health checks  
✅ **CI/CD pipelines** for GitHub Actions  
✅ **1Panel integration** for easy deployment  
✅ **Development and production** environments  
✅ **Comprehensive documentation** and troubleshooting  

### Services

| Service | Purpose | Port |
|---------|---------|------|
| **Laravel Octane (Swoole)** | High-performance application server | 8000 |
| **Laravel Horizon** | Queue management dashboard | - |
| **Queue Workers** | Background job processing (4 workers) | - |
| **Scheduler** | Cron job replacement | - |
| **MySQL 8.0** | Primary database | 3306 |
| **Redis 7** | Cache and queue backend | 6379 |
| **Soketi** | WebSocket server | 6001 |
| **Nginx** (optional) | Reverse proxy | 80, 443 |

---

## Quick Start

### Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- 4GB RAM, 2 CPU cores
- 10GB disk space

### 5-Minute Setup

```bash
# 1. Navigate to project
cd /opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index

# 2. Copy environment template
cp docker/.env.docker .env

# 3. Generate APP_KEY
docker run --rm -v $(pwd)/main:/app composer/composer:latest \
  php artisan key:generate --show

# 4. Update .env with generated key and passwords
nano .env

# 5. Build and start
docker-compose build
docker-compose up -d

# 6. Run migrations
docker-compose exec app php artisan migrate --force

# 7. Access application
open http://localhost:8000
```

---

## Docker Setup

### File Structure

```
index/
├── Dockerfile                          # Multi-stage production image
├── docker-compose.yml                  # Service orchestration
├── .dockerignore                       # Build optimization
└── docker/
    ├── entrypoint.sh                  # Container initialization
    ├── supervisord.conf               # Process management
    ├── .env.docker                    # Environment template
    ├── mysql/
    │   └── my.cnf                     # MySQL configuration
    └── nginx/
        ├── nginx.conf                 # Main Nginx config
        └── conf.d/
            └── default.conf           # Site configuration
```

### Dockerfile Features

**Multi-stage build:**
- Stage 1: Composer dependencies
- Stage 2: NPM asset compilation
- Stage 3: Production image

**PHP Extensions installed:**
- swoole (Laravel Octane)
- redis (Cache/Queue)
- grpc (Trading APIs)
- pdo_mysql, pdo_pgsql (Databases)
- gd, bcmath, sockets, pcntl

**Optimizations:**
- OPcache enabled
- 512MB memory limit
- Production PHP settings
- Optimized autoloader

### Docker Compose Services

```yaml
services:
  app:        # Laravel with Octane, Horizon, Workers, Scheduler
  mysql:      # MySQL 8.0 database
  redis:      # Redis 7 cache & queue
  soketi:     # WebSocket server
  nginx:      # Reverse proxy (production profile)
  mailhog:    # Email testing (development profile)
```

**Features:**
- Health checks for all services
- Automatic dependency ordering
- Named volumes for persistence
- Development/Production profiles

---

## Services Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Nginx (Port 80/443)                  │
│              Reverse Proxy & Static Files               │
└────────────────┬───────────────────────┬────────────────┘
                 │                       │
        ┌────────▼────────┐     ┌───────▼────────┐
        │  Laravel App    │     │     Soketi     │
        │  (Octane)       │     │   WebSocket    │
        │  Port: 8000     │     │   Port: 6001   │
        └────────┬────────┘     └────────────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
┌───▼───┐   ┌───▼───┐   ┌───▼────┐
│ MySQL │   │ Redis │   │Supervisor│
│  DB   │   │ Cache │   │  - Octane│
│ 3306  │   │ 6379  │   │  - Horizon│
└───────┘   └───────┘   │  - Workers│
                        │  - Scheduler│
                        └──────────┘
```

### Supervisor Configuration

Manages 4 services:

1. **Octane (Swoole)** - 4 workers, auto-restart
2. **Horizon** - Queue management, 3600s graceful shutdown
3. **Queue Workers** - 4 parallel processes
4. **Scheduler** - Runs every 60 seconds

---

## Configuration

### Environment Variables

All configuration in `.env`:

```env
# Application
APP_NAME=AlgoExpertHub
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=mysql
DB_DATABASE=algoexpert
DB_USERNAME=algoexpert
DB_PASSWORD=secure_password

# Redis & Queue
REDIS_HOST=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# WebSocket
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_WS_HOST=socket.yourdomain.com
```

### Container Initialization

Control startup behavior:

```env
# Run migrations automatically
RUN_MIGRATIONS=true

# Run seeders (only for initial setup)
RUN_SEEDERS=false
```

### Development vs Production

**Development:**
```bash
# Start with Mailhog
docker-compose --profile development up -d

# In .env:
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

**Production:**
```bash
# Start with Nginx
docker-compose --profile production up -d

# In .env:
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
OCTANE_HTTPS=true
```

---

## Deployment Methods

### Method 1: Automated Script (Recommended)

**Setup:**
```bash
# Copy configuration template
cp scripts/deployment/deploy.config.example scripts/deployment/deploy.config.production

# Edit configuration
nano scripts/deployment/deploy.config.production
```

**Required settings:**
```bash
DEPLOY_SERVER="your-server-ip"
DEPLOY_USER="root"
DEPLOY_PATH="/opt/algoexpert"
APP_URL="https://yourdomain.com"
DB_PASSWORD="secure_password"
DB_ROOT_PASSWORD="root_password"
```

**Deploy:**
```bash
chmod +x scripts/deployment/deploy.sh
./scripts/deployment/deploy.sh production
```

**Features:**
- ✅ SSH to remote server
- ✅ Auto-generate .env
- ✅ Build Docker images
- ✅ Run migrations
- ✅ Health checks
- ✅ Automatic backup
- ✅ Cleanup old images

**9-Step Process:**
1. Prerequisites Check
2. Prepare Directory & Backup
3. Upload Application Files
4. Setup Environment
5. Build & Deploy Containers
6. Run Migrations
7. Optimize Application
8. Health Check
9. Cleanup

### Method 2: CI/CD Pipeline (GitHub Actions)

**Setup Secrets:**

Go to GitHub Repository → Settings → Secrets and add:

```
SSH_PRIVATE_KEY          # Your SSH private key
DEPLOY_SERVER            # Server IP/domain
DEPLOY_USER              # SSH user (root)
DEPLOY_PATH              # /opt/algoexpert
APP_URL                  # https://yourdomain.com
DB_PASSWORD              # Database password
DB_ROOT_PASSWORD         # Root password
MAIL_HOST                # SMTP host
MAIL_USERNAME            # SMTP username
MAIL_PASSWORD            # SMTP password
PUSHER_WS_HOST           # socket.yourdomain.com
SLACK_WEBHOOK (optional) # For notifications
```

**Workflows:**

Two workflows are configured:

1. **Production** (`.github/workflows/deploy-production.yml`)
   - Triggers on push to `main` branch
   - Auto-deploys to production server

2. **Staging** (`.github/workflows/deploy-staging.yml`)
   - Triggers on push to `develop` branch
   - Auto-deploys to staging server

**Usage:**
```bash
# Deploy to staging
git checkout develop
git push origin develop

# Deploy to production
git checkout main
git merge develop
git push origin main
```

### Method 3: 1Panel Quick Deploy

**From 1Panel Terminal:**
```bash
cd /opt/algoexpert
./scripts/deployment/deploy-1panel.sh
```

**Interactive prompts for:**
- Deployment path
- Domain name
- Database passwords
- Automatic .env generation

**Then configure in 1Panel:**
1. Set up reverse proxy: `yourdomain.com` → `http://localhost:8000`
2. Set up WebSocket proxy: `socket.yourdomain.com` → `http://localhost:6001`
3. Configure SSL certificates

### Method 4: Manual Docker Compose

```bash
# Copy environment
cp docker/.env.docker .env
nano .env

# Build and start
docker-compose build
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

---

## CI/CD Integration

### GitHub Actions Workflow

**Deployment Flow:**

```
┌─────────────────────────────────────────┐
│         Git Push (main/develop)         │
└────────────────┬────────────────────────┘
                 │
        ┌────────▼────────┐
        │  GitHub Actions │
        │     Trigger     │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │   Setup SSH     │
        │  Create Config  │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │ Run deploy.sh   │
        │  - Upload files │
        │  - Build images │
        │  - Deploy       │
        │  - Migrate      │
        │  - Health check │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │   Deployment    │
        │    Complete!    │
        └─────────────────┘
```

**Benefits:**
- ✅ Automated on git push
- ✅ No manual intervention
- ✅ Consistent deployments
- ✅ Slack notifications
- ✅ Deployment history

---

## 1Panel Integration

### Option 1: Terminal Deployment

```bash
# Access 1Panel Terminal
cd /opt/algoexpert
./scripts/deployment/deploy-1panel.sh
```

### Option 2: Docker Compose UI

1. In 1Panel Dashboard → Container → Compose
2. Click "Create"
3. Upload `docker-compose.yml` and `.env`
4. Click "Start"

### Option 3: Webhook Integration

1. **Create Webhook in 1Panel:**
   - Go to Container → Webhooks
   - Create new webhook
   - Copy webhook URL

2. **Add to GitHub:**
   - Repository Settings → Webhooks
   - Add webhook URL
   - Select "Push" events

3. **Configure Script:**
   ```bash
   #!/bin/bash
   cd /opt/algoexpert
   git pull origin main
   ./deploy.sh production
   ```

---

## Common Commands

### Container Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart a service
docker-compose restart app

# View logs
docker-compose logs -f app

# Check status
docker-compose ps

# Container stats
docker stats
```

### Laravel Commands

```bash
# Run artisan
docker-compose exec app php artisan <command>

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan horizon:status
docker-compose exec app php artisan tinker
```

### Database Operations

```bash
# MySQL CLI
docker-compose exec mysql mysql -u root -p

# Backup database
docker-compose exec mysql mysqldump -u root -p algoexpert > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -p algoexpert < backup.sql

# Run migrations
docker-compose exec app php artisan migrate --force

# Rollback migrations
docker-compose exec app php artisan migrate:rollback
```

### Queue & Horizon

```bash
# Check Horizon status
docker-compose exec app php artisan horizon:status

# Restart Horizon
docker-compose exec app supervisorctl restart horizon

# Monitor queue
docker-compose exec app php artisan queue:monitor

# Clear failed jobs
docker-compose exec app php artisan queue:flush
```

### Cache Management

```bash
# Clear all cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Rebuild cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

---

## Troubleshooting

### Application Won't Start

**Check logs:**
```bash
docker-compose logs -f app
```

**Common issues:**

1. **APP_KEY not set**
   ```bash
   docker run --rm -v $(pwd)/main:/app composer/composer:latest \
     php artisan key:generate --show
   ```

2. **Database connection failed**
   ```bash
   docker-compose ps mysql
   docker-compose exec app php artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **Permission errors**
   ```bash
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   ```

### Queue Jobs Not Processing

```bash
# Check Horizon status
docker-compose exec app php artisan horizon:status

# Check supervisor
docker-compose exec app supervisorctl status

# Restart Horizon
docker-compose exec app supervisorctl restart horizon

# Check logs
docker-compose logs -f app | grep horizon
```

### WebSocket Not Working

```bash
# Check Soketi is running
curl http://localhost:6001

# Verify environment variables
docker-compose exec app env | grep PUSHER

# Check browser console for errors
# Verify PUSHER_WS_HOST matches your domain
```

### High Memory Usage

```bash
# Check container stats
docker stats

# Restart Octane workers
docker-compose exec app supervisorctl restart octane

# Reduce worker count in docker/supervisord.conf
# Change --workers=4 to --workers=2
```

### Deployment Script Fails

```bash
# Enable debug mode
bash -x deploy.sh production

# Test SSH connection
ssh -v root@your-server-ip

# Check SSH key permissions
chmod 600 ~/.ssh/id_rsa
```

### Database Migration Fails

```bash
# Check database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();

# Run migrations manually
docker-compose exec app php artisan migrate --force

# Check migration status
docker-compose exec app php artisan migrate:status

# Rollback and retry
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan migrate --force
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Server has Docker and Docker Compose installed
- [ ] SSH key added to server
- [ ] Deployment configuration created
- [ ] Database passwords set (strong passwords)
- [ ] Mail credentials configured
- [ ] Domain DNS configured
- [ ] SSL certificates ready
- [ ] Firewall configured
- [ ] Backup strategy in place

### Production Configuration

**In `.env`:**
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
OCTANE_HTTPS=true
```

**Security Settings:**
- Use strong database passwords
- Enable HTTPS
- Configure firewall rules
- Set up SSL certificates
- Use secrets management for CI/CD
- Restrict SSH access

### SSL Certificate Setup

**Option 1: 1Panel SSL Manager**
1. Go to 1Panel → Website → SSL
2. Add your domain
3. Generate Let's Encrypt certificate
4. Configure in Nginx

**Option 2: Manual Certbot**
```bash
# Install certbot
apt-get install certbot

# Generate certificate
certbot certonly --standalone -d yourdomain.com

# Update nginx config
# Add SSL certificate paths
```

### Monitoring & Logging

**View logs:**
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app

# Deployment logs
tail -f /tmp/deploy.log
```

**Monitor resources:**
```bash
# Container stats
docker stats

# Disk usage
df -h

# Memory usage
free -h
```

**Health checks:**
```bash
# Application
curl http://localhost:8000/api/health

# Horizon
docker-compose exec app php artisan horizon:status

# Queue
docker-compose exec app php artisan queue:monitor
```

### Backup & Restore

**Backup:**
```bash
# Database
docker-compose exec mysql mysqldump -u root -p algoexpert > backup-$(date +%Y%m%d).sql

# Storage files
docker-compose exec app tar -czf /tmp/storage.tar.gz storage/app
docker cp algoexpert_app:/tmp/storage.tar.gz ./storage-backup-$(date +%Y%m%d).tar.gz

# Full backup (automated)
docker-compose exec app php artisan backup:run
```

**Restore:**
```bash
# Database
docker-compose exec -T mysql mysql -u root -p algoexpert < backup-20231215.sql

# Storage files
docker cp storage-backup-20231215.tar.gz algoexpert_app:/tmp/
docker-compose exec app tar -xzf /tmp/storage-backup-20231215.tar.gz -C /var/www/html/
```

### Scaling

**Increase Workers:**

Edit `docker/supervisord.conf`:
```ini
[program:queue-worker]
numprocs=8  # Increase from 4 to 8
```

**Increase Octane Workers:**

Edit `docker/supervisord.conf`:
```ini
[program:octane]
command=php /var/www/html/artisan octane:start --workers=8
```

**External Services:**

Use external MySQL/Redis for better performance:
```env
DB_HOST=your-mysql-host.com
REDIS_HOST=your-redis-host.com
```

### Performance Optimization

```bash
# Optimize application
docker-compose exec app php artisan optimize

# Clear and rebuild cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Database optimization
docker-compose exec mysql mysqlcheck -u root -p --optimize --all-databases
```

---

## Deployment Comparison

| Feature | deploy.sh | GitHub Actions | 1Panel Script | Manual |
|---------|-----------|----------------|---------------|--------|
| **Automation** | ✅ Full | ✅ Full | ⚠️ Semi | ❌ Manual |
| **SSH Required** | ✅ Yes | ✅ Yes | ❌ No | ✅ Yes |
| **Backup** | ✅ Auto | ✅ Auto | ❌ No | ❌ Manual |
| **Health Check** | ✅ Yes | ✅ Yes | ⚠️ Basic | ❌ No |
| **Rollback** | ✅ Easy | ⚠️ Manual | ❌ Hard | ❌ Hard |
| **Multi-Env** | ✅ Yes | ✅ Yes | ❌ No | ⚠️ Manual |
| **Best For** | DevOps | Teams | Quick Start | Development |

---

## Summary

This guide covers the complete Docker and deployment setup for AlgoExpertHub:

✅ **22 files created** (12 Docker + 10 CI/CD)  
✅ **4 deployment methods** available  
✅ **7 services** properly orchestrated  
✅ **Automated CI/CD** with GitHub Actions  
✅ **1Panel integration** for easy deployment  
✅ **Production-ready** configuration  

### Quick Links

- **Quick Start**: [5-Minute Setup](#5-minute-setup)
- **Deployment**: [Deployment Methods](#deployment-methods)
- **CI/CD**: [GitHub Actions](#cicd-integration)
- **Troubleshooting**: [Common Issues](#troubleshooting)
- **Production**: [Production Deployment](#production-deployment)

### Support

For issues or questions:
- Check logs: `docker-compose logs -f`
- Review troubleshooting section
- Verify configuration files
- Test SSH connection
- Check server resources

---

**Happy Deploying! 🚀**
