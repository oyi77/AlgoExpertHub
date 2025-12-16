#!/bin/bash
################################################################################
# 1Panel Quick Deploy Script for AlgoExpertHub
################################################################################
# This script simplifies deployment directly from 1Panel terminal
################################################################################

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=========================================="
echo "AlgoExpertHub - 1Panel Quick Deploy"
echo -e "==========================================${NC}"
echo ""

# Check if running in 1Panel environment
if [ ! -d "/opt/1panel" ]; then
    echo -e "${YELLOW}Warning: This doesn't appear to be a 1Panel server${NC}"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
fi

# Get deployment path
read -p "Deployment path [/opt/algoexpert]: " DEPLOY_PATH
DEPLOY_PATH=${DEPLOY_PATH:-/opt/algoexpert}

# Get domain
read -p "Your domain (e.g., algoexpert.com): " DOMAIN
if [ -z "$DOMAIN" ]; then
    echo "Domain is required!"
    exit 1
fi

# Get database password
read -sp "Database password: " DB_PASSWORD
echo ""
if [ -z "$DB_PASSWORD" ]; then
    echo "Database password is required!"
    exit 1
fi

# Get database root password
read -sp "Database root password: " DB_ROOT_PASSWORD
echo ""
if [ -z "$DB_ROOT_PASSWORD" ]; then
    echo "Database root password is required!"
    exit 1
fi

echo ""
echo -e "${BLUE}Creating deployment directory...${NC}"
mkdir -p "$DEPLOY_PATH"
cd "$DEPLOY_PATH"

echo -e "${BLUE}Copying application files...${NC}"
# Assuming we're running from the project directory
if [ -f "docker-compose.yml" ]; then
    echo "Files already in place"
else
    echo "Please ensure all files are in $DEPLOY_PATH"
    exit 1
fi

echo -e "${BLUE}Generating environment configuration...${NC}"

# Generate APP_KEY
APP_KEY=$(openssl rand -base64 32)

# Create .env file
cat > .env << EOF
# Application
APP_NAME=AlgoExpertHub
APP_ENV=production
APP_KEY=base64:${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=algoexpert
DB_USERNAME=algoexpert
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

# Redis
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=pusher

# Horizon
HORIZON_NAME=AlgoExpert
HORIZON_PATH=horizon
HORIZON_USE_SYSTEM_SUPERVISOR=true

# Octane
OCTANE_SERVER=swoole
OCTANE_HTTPS=true

# Pusher/Soketi
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_WS_HOST=socket.${DOMAIN}

# Mail (configure later)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@${DOMAIN}
MAIL_FROM_NAME="AlgoExpertHub"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning

# Container Settings
RUN_MIGRATIONS=true
RUN_SEEDERS=false

# Ports
APP_PORT=8000
DB_PORT=3306
REDIS_PORT=6379
NGINX_HTTP_PORT=80
NGINX_HTTPS_PORT=443
EOF

echo -e "${GREEN}✓ Environment file created${NC}"

echo ""
echo -e "${BLUE}Building Docker images...${NC}"
docker-compose build

echo ""
echo -e "${BLUE}Starting containers...${NC}"
docker-compose --profile production up -d

echo ""
echo -e "${BLUE}Waiting for services to be ready...${NC}"
sleep 15

echo ""
echo -e "${BLUE}Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

echo ""
echo -e "${BLUE}Optimizing application...${NC}"
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo ""
echo -e "${GREEN}=========================================="
echo "Deployment Complete!"
echo -e "==========================================${NC}"
echo ""
echo "Your application is now running!"
echo ""
echo "Next steps:"
echo "1. Configure your domain DNS to point to this server"
echo "2. Set up SSL certificate (use 1Panel's SSL manager)"
echo "3. Configure reverse proxy in 1Panel:"
echo "   - Domain: ${DOMAIN}"
echo "   - Proxy to: http://localhost:8000"
echo "4. Configure WebSocket proxy:"
echo "   - Domain: socket.${DOMAIN}"
echo "   - Proxy to: http://localhost:6001"
echo ""
echo "Access points:"
echo "  Application: https://${DOMAIN}"
echo "  Horizon: https://${DOMAIN}/horizon"
echo ""
echo "Useful commands:"
echo "  View logs: docker-compose logs -f"
echo "  Restart: docker-compose restart"
echo "  Stop: docker-compose down"
echo "  Shell: docker-compose exec app bash"
echo ""
echo -e "${GREEN}Happy Trading! 📈${NC}"
