#!/bin/bash
################################################################################
# AlgoExpertHub - Automated Deployment Script
################################################################################
# This script automates the deployment of AlgoExpertHub to a remote server
# It handles SSH connection, environment setup, and Docker deployment
#
# Usage:
#   ./deploy.sh [environment]
#
# Environments: development, staging, production
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENVIRONMENT="${1:-production}"
CONFIG_FILE="${SCRIPT_DIR}/deploy.config.${ENVIRONMENT}"

# Load environment-specific configuration
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}Error: Configuration file not found: $CONFIG_FILE${NC}"
    echo "Please create it from deploy.config.example"
    exit 1
fi

# Validate required variables
REQUIRED_VARS=(
    "DEPLOY_SERVER"
    "DEPLOY_USER"
    "DEPLOY_PATH"
    "APP_NAME"
)

for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var:-}" ]; then
        echo -e "${RED}Error: Required variable $var is not set in $CONFIG_FILE${NC}"
        exit 1
    fi
done

################################################################################
# Helper Functions
################################################################################

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Execute command on remote server
remote_exec() {
    ssh -o StrictHostKeyChecking=no "${DEPLOY_USER}@${DEPLOY_SERVER}" "$@"
}

# Copy file to remote server
remote_copy() {
    scp -o StrictHostKeyChecking=no "$1" "${DEPLOY_USER}@${DEPLOY_SERVER}:$2"
}

# Copy directory to remote server
remote_copy_dir() {
    rsync -avz --exclude-from="${SCRIPT_DIR}/.deployignore" \
        -e "ssh -o StrictHostKeyChecking=no" \
        "$1" "${DEPLOY_USER}@${DEPLOY_SERVER}:$2"
}

################################################################################
# Deployment Steps
################################################################################

step_check_prerequisites() {
    print_header "Step 1: Checking Prerequisites"
    
    # Check if SSH key exists
    if [ ! -f "${SSH_KEY_PATH:-$HOME/.ssh/id_rsa}" ]; then
        log_error "SSH key not found at ${SSH_KEY_PATH:-$HOME/.ssh/id_rsa}"
        exit 1
    fi
    
    # Test SSH connection
    log_info "Testing SSH connection to ${DEPLOY_USER}@${DEPLOY_SERVER}..."
    if remote_exec "echo 'SSH connection successful'"; then
        log_success "SSH connection established"
    else
        log_error "Failed to connect to server"
        exit 1
    fi
    
    # Check if Docker is installed on remote server
    log_info "Checking Docker installation on remote server..."
    if remote_exec "command -v docker &> /dev/null"; then
        DOCKER_VERSION=$(remote_exec "docker --version")
        log_success "Docker is installed: $DOCKER_VERSION"
    else
        log_error "Docker is not installed on remote server"
        log_info "Please install Docker first: curl -fsSL https://get.docker.com | sh"
        exit 1
    fi
    
    # Check if Docker Compose is installed
    log_info "Checking Docker Compose installation..."
    if remote_exec "command -v docker-compose &> /dev/null || docker compose version &> /dev/null"; then
        log_success "Docker Compose is installed"
    else
        log_error "Docker Compose is not installed on remote server"
        exit 1
    fi
}

step_prepare_deployment_directory() {
    print_header "Step 2: Preparing Deployment Directory"
    
    log_info "Creating deployment directory: ${DEPLOY_PATH}"
    remote_exec "mkdir -p ${DEPLOY_PATH}"
    
    log_info "Creating backup of current deployment (if exists)..."
    remote_exec "
        if [ -d '${DEPLOY_PATH}/current' ]; then
            BACKUP_DIR='${DEPLOY_PATH}/backups/\$(date +%Y%m%d_%H%M%S)'
            mkdir -p \$BACKUP_DIR
            cp -r ${DEPLOY_PATH}/current/* \$BACKUP_DIR/ || true
            echo 'Backup created at: \$BACKUP_DIR'
        fi
    "
    
    log_success "Deployment directory prepared"
}

step_upload_application() {
    print_header "Step 3: Uploading Application Files"
    
    log_info "Syncing application files to server..."
    
    # Create temporary directory for deployment
    TEMP_DIR=$(mktemp -d)
    trap "rm -rf $TEMP_DIR" EXIT
    
    # Copy necessary files to temp directory
    cp -r "${PROJECT_ROOT}/main" "$TEMP_DIR/"
    cp "${PROJECT_ROOT}/Dockerfile" "$TEMP_DIR/"
    cp "${PROJECT_ROOT}/docker-compose.yml" "$TEMP_DIR/"
    cp "${PROJECT_ROOT}/.dockerignore" "$TEMP_DIR/"
    cp -r "${PROJECT_ROOT}/docker" "$TEMP_DIR/"
    
    # Upload to server
    remote_copy_dir "$TEMP_DIR/" "${DEPLOY_PATH}/current/"
    
    log_success "Application files uploaded"
}

step_setup_environment() {
    print_header "Step 4: Setting Up Environment"
    
    log_info "Generating environment configuration..."
    
    # Generate APP_KEY if not provided
    if [ -z "${APP_KEY:-}" ]; then
        log_info "Generating new APP_KEY..."
        APP_KEY=$(openssl rand -base64 32)
    fi
    
    # Create .env file from template
    cat > /tmp/deploy.env << EOF
# Application
APP_NAME=${APP_NAME}
APP_ENV=${ENVIRONMENT}
APP_KEY=base64:${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

# Redis
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=pusher

# Horizon
HORIZON_NAME=${APP_NAME}
HORIZON_PATH=horizon
HORIZON_USE_SYSTEM_SUPERVISOR=true

# Octane
OCTANE_SERVER=swoole
OCTANE_HTTPS=${OCTANE_HTTPS:-false}

# Pusher/Soketi
PUSHER_APP_ID=${PUSHER_APP_ID:-app-id}
PUSHER_APP_KEY=${PUSHER_APP_KEY:-app-key}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-app-secret}
PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-mt1}
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_WS_HOST=${PUSHER_WS_HOST:-localhost}

# Mail
MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=${LOG_LEVEL:-warning}

# Container Settings
RUN_MIGRATIONS=${RUN_MIGRATIONS:-true}
RUN_SEEDERS=${RUN_SEEDERS:-false}

# Ports
APP_PORT=${APP_PORT:-8000}
DB_PORT=3306
REDIS_PORT=6379
NGINX_HTTP_PORT=${NGINX_HTTP_PORT:-80}
NGINX_HTTPS_PORT=${NGINX_HTTPS_PORT:-443}
EOF

    # Upload .env file
    remote_copy "/tmp/deploy.env" "${DEPLOY_PATH}/current/.env"
    rm /tmp/deploy.env
    
    log_success "Environment configuration created"
}

step_build_and_deploy() {
    print_header "Step 5: Building and Deploying Containers"
    
    log_info "Building Docker images..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose build --no-cache"
    
    log_info "Stopping existing containers..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose down || true"
    
    log_info "Starting new containers..."
    if [ "$ENVIRONMENT" = "production" ]; then
        remote_exec "cd ${DEPLOY_PATH}/current && docker-compose --profile production up -d"
    else
        remote_exec "cd ${DEPLOY_PATH}/current && docker-compose --profile development up -d"
    fi
    
    log_success "Containers deployed"
}

step_run_migrations() {
    print_header "Step 6: Running Database Migrations"
    
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        log_info "Waiting for database to be ready..."
        sleep 10
        
        log_info "Running migrations..."
        remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan migrate --force"
        
        log_success "Migrations completed"
    else
        log_warning "Skipping migrations (RUN_MIGRATIONS=false)"
    fi
}

step_optimize_application() {
    print_header "Step 7: Optimizing Application"
    
    log_info "Clearing cache..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan cache:clear"
    
    log_info "Optimizing configuration..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan config:cache"
    
    log_info "Optimizing routes..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan route:cache"
    
    log_info "Optimizing views..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan view:cache"
    
    log_success "Application optimized"
}

step_health_check() {
    print_header "Step 8: Running Health Checks"
    
    log_info "Checking container status..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose ps"
    
    log_info "Waiting for application to be ready..."
    sleep 5
    
    log_info "Testing application endpoint..."
    if remote_exec "curl -f http://localhost:${APP_PORT:-8000}/api/health || curl -f http://localhost:${APP_PORT:-8000}"; then
        log_success "Application is responding"
    else
        log_warning "Application health check failed (this might be normal if /api/health doesn't exist yet)"
    fi
    
    log_info "Checking Horizon status..."
    remote_exec "cd ${DEPLOY_PATH}/current && docker-compose exec -T app php artisan horizon:status || true"
    
    log_success "Health checks completed"
}

step_cleanup() {
    print_header "Step 9: Cleanup"
    
    log_info "Removing old Docker images..."
    remote_exec "docker image prune -f"
    
    log_info "Keeping last 5 backups..."
    remote_exec "
        cd ${DEPLOY_PATH}/backups 2>/dev/null || exit 0
        ls -t | tail -n +6 | xargs -r rm -rf
    "
    
    log_success "Cleanup completed"
}

################################################################################
# Main Deployment Flow
################################################################################

main() {
    print_header "AlgoExpertHub Deployment - ${ENVIRONMENT}"
    
    log_info "Deployment Configuration:"
    echo "  Server: ${DEPLOY_USER}@${DEPLOY_SERVER}"
    echo "  Path: ${DEPLOY_PATH}"
    echo "  Environment: ${ENVIRONMENT}"
    echo "  App Name: ${APP_NAME}"
    echo ""
    
    # Confirm deployment
    if [ "${AUTO_CONFIRM:-false}" != "true" ]; then
        read -p "Continue with deployment? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_warning "Deployment cancelled"
            exit 0
        fi
    fi
    
    # Execute deployment steps
    step_check_prerequisites
    step_prepare_deployment_directory
    step_upload_application
    step_setup_environment
    step_build_and_deploy
    step_run_migrations
    step_optimize_application
    step_health_check
    step_cleanup
    
    print_header "Deployment Complete!"
    log_success "AlgoExpertHub has been successfully deployed to ${ENVIRONMENT}"
    echo ""
    echo "Access your application at: ${APP_URL}"
    echo "Horizon Dashboard: ${APP_URL}/horizon"
    echo ""
    echo "Useful commands:"
    echo "  View logs: ssh ${DEPLOY_USER}@${DEPLOY_SERVER} 'cd ${DEPLOY_PATH}/current && docker-compose logs -f'"
    echo "  Restart: ssh ${DEPLOY_USER}@${DEPLOY_SERVER} 'cd ${DEPLOY_PATH}/current && docker-compose restart'"
    echo "  Shell: ssh ${DEPLOY_USER}@${DEPLOY_SERVER} 'cd ${DEPLOY_PATH}/current && docker-compose exec app bash'"
    echo ""
}

# Run main function
main
