#!/bin/bash
# Unified Railway Deployment & Startup Script
# Detects environment and runs appropriate operations:
# - Local: Railway CLI deployment operations
# - Railway: Application startup and configuration

set +e  # Don't exit on error - we want graceful failures

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Detect environment
if [[ -d "/app" ]] && [[ -f "/app/index.php" ]]; then
    # Running inside Railway container
    MODE="startup"
    APP_ROOT="/app"
    IS_RAILWAY=true
else
    # Running locally - check if Railway CLI is available
    if command -v railway &> /dev/null; then
        MODE="deploy"
        IS_RAILWAY=false
    else
        # No Railway CLI and not in Railway - probably local development
        MODE="startup"
        APP_ROOT="$(pwd)"
        IS_RAILWAY=false
    fi
fi

# Parse arguments
SERVICE_NAME=""
VERBOSE=false
ADD_MYSQL=false
SKIP_MYSQL_CHECK=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --service)
            SERVICE_NAME="$2"
            shift 2
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --add-mysql)
            ADD_MYSQL=true
            shift
            ;;
        --skip-mysql-check)
            SKIP_MYSQL_CHECK=true
            shift
            ;;
        --mode)
            MODE="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Usage: $0 [--service <name>] [--verbose|-v] [--add-mysql] [--skip-mysql-check] [--mode deploy|startup]"
            exit 1
            ;;
    esac
done

# ============================================================================
# DEPLOYMENT MODE (Local - Railway CLI operations)
# ============================================================================
if [[ "$MODE" == "deploy" ]]; then
    echo -e "${BLUE}🚀 Railway Deployment Mode${NC}"
    
    # Check if Railway CLI is installed
    if ! command -v railway &> /dev/null; then
        echo -e "${RED}❌ Railway CLI not found. Install it with: npm i -g @railway/cli${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Railway CLI found: $(railway --version)${NC}"
    
    # Check if logged in
    if ! railway whoami &> /dev/null; then
        echo -e "${YELLOW}⚠️  Not logged in. Please login...${NC}"
        railway login
    fi
    
    echo -e "${GREEN}✅ Logged in as: $(railway whoami)${NC}"
    
    # Check if project is linked
    if ! railway status &> /dev/null; then
        echo -e "${YELLOW}⚠️  No project linked. Please link to a project...${NC}"
        railway link
    fi
    
    echo -e "${GREEN}✅ Project linked${NC}"
    
    # Check for MySQL database - IMPROVED: Check for actual MySQL service and variables
    if [[ "$SKIP_MYSQL_CHECK" == "false" ]]; then
        echo -e "${BLUE}🔍 Checking for MySQL database...${NC}"
        
        # Check for MySQL variables (primary check - most reliable)
        MYSQL_VARS=$(railway variables 2>&1 | grep -iE "MYSQL_(HOST|DATABASE|USER|PASSWORD)" || echo "")
        
        # Check if MySQL service exists in project (secondary check)
        MYSQL_SERVICE_EXISTS=false
        SERVICE_LIST=$(railway service list 2>/dev/null || echo "")
        if echo "$SERVICE_LIST" | grep -qiE "mysql|database|postgres"; then
            MYSQL_SERVICE_EXISTS=true
        fi
        
        # Determine if MySQL already exists
        MYSQL_EXISTS=false
        if [[ -n "$MYSQL_VARS" ]]; then
            MYSQL_EXISTS=true
        elif [[ "$MYSQL_SERVICE_EXISTS" == "true" ]]; then
            MYSQL_EXISTS=true
        fi
        
        # Handle MySQL creation
        if [[ "$ADD_MYSQL" == "true" ]]; then
            # User explicitly wants to add MySQL
            if [[ "$MYSQL_EXISTS" == "true" ]]; then
                echo -e "${YELLOW}⚠️  MySQL database already exists. Skipping creation.${NC}"
                echo -e "${YELLOW}   Found: MySQL variables or service already configured${NC}"
                echo -e "${YELLOW}   Use --skip-mysql-check to skip this check entirely.${NC}"
            else
                echo -e "${YELLOW}📦 Adding MySQL database to Railway...${NC}"
                if railway add --database mysql; then
                    echo -e "${GREEN}✅ MySQL database added successfully!${NC}"
                    echo -e "${YELLOW}📋 Database connection variables are automatically set in Railway.${NC}"
                else
                    echo -e "${YELLOW}⚠️  Failed to add MySQL database. It may already exist.${NC}"
                fi
            fi
        elif [[ "$MYSQL_EXISTS" == "true" ]]; then
            # MySQL exists, just confirm
            echo -e "${GREEN}✅ MySQL database already configured${NC}"
        else
            # No MySQL found, inform user
            echo -e "${YELLOW}⚠️  No MySQL database found.${NC}"
            echo -e "${YELLOW}   Use --add-mysql to add one, or --skip-mysql-check to skip.${NC}"
        fi
    fi
    
    # Check if service is linked
    SERVICE_STATUS=$(railway status 2>&1 | grep -i "Service:" || echo "")
    CURRENT_SERVICE=$(echo "$SERVICE_STATUS" | awk '{print $2}' 2>/dev/null || echo "")
    
    # Handle service selection
    if [[ "$CURRENT_SERVICE" == "None" ]] || [[ -z "$CURRENT_SERVICE" ]]; then
        if [[ -n "$SERVICE_NAME" ]]; then
            echo -e "${YELLOW}🔗 Linking to service: $SERVICE_NAME${NC}"
            railway service "$SERVICE_NAME" || {
                echo -e "${RED}❌ Failed to link service. Please check the service name.${NC}"
                exit 1
            }
            CURRENT_SERVICE="$SERVICE_NAME"
        else
            echo -e "${YELLOW}⚠️  No service linked. Attempting to create a new service...${NC}"
            
            # Try to create a new service first
            CREATE_OUTPUT=$(railway add --service 2>&1)
            CREATE_EXIT_CODE=$?
            
            if [[ $CREATE_EXIT_CODE -eq 0 ]]; then
                sleep 1
                NEW_SERVICE=$(railway status 2>&1 | grep -i "Service:" | awk '{print $2}' 2>/dev/null || echo "")
                if [[ -n "$NEW_SERVICE" ]] && [[ "$NEW_SERVICE" != "None" ]]; then
                    echo -e "${GREEN}✅ Service created and linked: $NEW_SERVICE${NC}"
                    CURRENT_SERVICE="$NEW_SERVICE"
                fi
            fi
            
            # If service creation failed or service not linked, try to link existing service
            if [[ "$CURRENT_SERVICE" == "None" ]] || [[ -z "$CURRENT_SERVICE" ]]; then
                echo -e "${YELLOW}📋 Attempting to link existing service...${NC}"
                echo -e "${YELLOW}   Tip: Use --service flag to skip this prompt${NC}"
                if railway service; then
                    CURRENT_SERVICE=$(railway status 2>&1 | grep -i "Service:" | awk '{print $2}' || echo "")
                    if [[ -n "$CURRENT_SERVICE" ]] && [[ "$CURRENT_SERVICE" != "None" ]]; then
                        echo -e "${GREEN}✅ Service linked: $CURRENT_SERVICE${NC}"
                    else
                        echo -e "${RED}❌ Failed to link service.${NC}"
                        exit 1
                    fi
                else
                    echo -e "${RED}❌ Service selection cancelled or failed.${NC}"
                    exit 1
                fi
            fi
        fi
    fi
    
    # Check project size before deployment
    echo -e "${YELLOW}📊 Checking project size...${NC}"
    PROJECT_SIZE=$(du -sm . 2>/dev/null | awk '{print $1}' || echo "0")
    if [[ $PROJECT_SIZE -gt 500 ]]; then
        echo -e "${YELLOW}⚠️  Project size is ${PROJECT_SIZE}MB (large). This may cause timeout.${NC}"
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}Deployment cancelled.${NC}"
            exit 0
        fi
    fi
    
    # Deploy with service specified (detached mode) with retry logic
    echo -e "${YELLOW}📦 Deploying to Railway (detached mode)...${NC}"
    
    MAX_RETRIES=3
    RETRY_COUNT=0
    DEPLOY_SUCCESS=false
    
    while [[ $RETRY_COUNT -lt $MAX_RETRIES ]] && [[ "$DEPLOY_SUCCESS" == "false" ]]; do
        if [[ $RETRY_COUNT -gt 0 ]]; then
            echo -e "${YELLOW}🔄 Retry attempt $RETRY_COUNT of $MAX_RETRIES...${NC}"
            sleep 2
        fi
        
        DEPLOY_CMD="railway up --detach"
        if [[ -n "$CURRENT_SERVICE" ]] && [[ "$CURRENT_SERVICE" != "None" ]]; then
            DEPLOY_CMD="railway up --service $CURRENT_SERVICE --detach"
        fi
        
        if [[ "$VERBOSE" == "true" ]]; then
            DEPLOY_CMD="$DEPLOY_CMD --verbose"
        fi
        
        $DEPLOY_CMD
        DEPLOY_EXIT_CODE=$?
        
        if [[ $DEPLOY_EXIT_CODE -eq 0 ]]; then
            DEPLOY_SUCCESS=true
            echo -e "${GREEN}✅ Deployment initiated successfully!${NC}"
        else
            RETRY_COUNT=$((RETRY_COUNT + 1))
            if [[ $RETRY_COUNT -lt $MAX_RETRIES ]]; then
                echo -e "${YELLOW}⚠️  Deployment failed (exit code: $DEPLOY_EXIT_CODE). Retrying...${NC}"
            else
                echo -e "${RED}❌ Deployment failed after $MAX_RETRIES attempts.${NC}"
                exit 1
            fi
        fi
    done
    
    if [[ "$DEPLOY_SUCCESS" == "true" ]]; then
        echo -e "${GREEN}✅ Deployment complete! Check Railway dashboard for build status.${NC}"
    fi
    
    exit 0
fi

# ============================================================================
# STARTUP MODE (Railway container or local development)
# ============================================================================
echo -e "${BLUE}🚀 Application Startup Mode${NC}"

# Set APP_BASE_PATH if not set
export APP_BASE_PATH="${APP_BASE_PATH:-main}"

# Set APP_ROOT if not already set
if [[ -z "$APP_ROOT" ]]; then
    APP_ROOT="$(pwd)"
fi

LARAVEL_ROOT="$APP_ROOT/$APP_BASE_PATH"

# Set VIEW_COMPILED_PATH environment variable to ensure Blade cache path is correct
# Use absolute path to avoid realpath() issues
VIEW_CACHE_DIR="$LARAVEL_ROOT/storage/framework/views"
export VIEW_COMPILED_PATH="${VIEW_COMPILED_PATH:-$VIEW_CACHE_DIR}"

# Create view cache directory IMMEDIATELY (before any Laravel operations)
mkdir -p "$VIEW_CACHE_DIR" 2>/dev/null || true
chmod 775 "$VIEW_CACHE_DIR" 2>/dev/null || true

echo -e "${YELLOW}📦 Setting up Laravel application...${NC}"
echo -e "${YELLOW}   Environment: $([ "$IS_RAILWAY" == "true" ] && echo "Railway" || echo "Local")${NC}"
echo -e "${YELLOW}   App root: $APP_ROOT${NC}"
echo -e "${YELLOW}   Laravel root: $LARAVEL_ROOT${NC}"

# Ensure we're in the app root
cd "$APP_ROOT" || {
    echo -e "${RED}❌ Cannot access app root: $APP_ROOT${NC}"
    exit 1
}

# Ensure storage directories exist
if [[ ! -d "$LARAVEL_ROOT" ]]; then
    echo -e "${RED}❌ Laravel root not found: $LARAVEL_ROOT${NC}"
    exit 1
fi

# Create storage directories with proper structure
mkdir -p "$LARAVEL_ROOT/storage/app/public" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/storage/framework/cache/data" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/storage/framework/sessions" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/storage/framework/views" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/storage/framework/testing" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/storage/logs" 2>/dev/null || true
mkdir -p "$LARAVEL_ROOT/bootstrap/cache" 2>/dev/null || true

# Ensure view cache directory exists and is writable (critical for Blade)
if [[ ! -d "$LARAVEL_ROOT/storage/framework/views" ]]; then
    echo -e "${YELLOW}⚠️  Creating view cache directory...${NC}"
    mkdir -p "$LARAVEL_ROOT/storage/framework/views"
fi

# Set proper permissions for storage directories
chmod -R 775 "$LARAVEL_ROOT/storage" 2>/dev/null || true
chmod -R 775 "$LARAVEL_ROOT/bootstrap/cache" 2>/dev/null || true

# Verify view cache directory is accessible
if [[ ! -w "$LARAVEL_ROOT/storage/framework/views" ]]; then
    echo -e "${YELLOW}⚠️  View cache directory is not writable. Fixing permissions...${NC}"
    chmod 775 "$LARAVEL_ROOT/storage/framework/views" 2>/dev/null || true
fi

# Check if application is installed
if [[ ! -f "$LARAVEL_ROOT/storage/LICENCE.txt" ]]; then
    echo -e "${YELLOW}⚠️  Application not installed yet.${NC}"
    
    DB_NAME="${MYSQL_DATABASE:-${DB_DATABASE}}"
    
    if [[ "${AUTO_INSTALL:-false}" == "true" ]] && [[ -n "$DB_NAME" ]]; then
        echo -e "${YELLOW}📦 Auto-installing database...${NC}"
        cd "$LARAVEL_ROOT"
        
        if php artisan install:database --skip-errors 2>&1 | head -50; then
            echo "installed" > "$LARAVEL_ROOT/storage/LICENCE.txt"
            echo -e "${GREEN}✅ Database imported and LICENCE.txt created${NC}"
        else
            echo -e "${YELLOW}⚠️  Auto-install failed.${NC}"
        fi
        cd "$APP_ROOT"
    else
        echo -e "${YELLOW}   To install:${NC}"
        echo -e "${YELLOW}   1. Set ALLOW_INSTALLER=true and access /install/index.php${NC}"
        echo -e "${YELLOW}   2. Or set AUTO_INSTALL=true to auto-import database${NC}"
        echo "Application installation pending" > "$LARAVEL_ROOT/storage/LICENCE.txt"
    fi
else
    echo -e "${GREEN}✅ Application already installed${NC}"
fi

# Set permissions (redundant but ensures they're set after any operations)
chmod -R 775 "$LARAVEL_ROOT/storage" "$LARAVEL_ROOT/bootstrap/cache" 2>/dev/null || true

echo -e "${YELLOW}🔗 Creating storage symlink...${NC}"
cd "$LARAVEL_ROOT"
php artisan storage:link 2>&1 | grep -v "does not exist" || echo "Storage link already exists or skipped"
cd "$APP_ROOT"

# Only check database and run migrations in Railway, not locally
if [[ "$IS_RAILWAY" == "true" ]]; then
    echo -e "${YELLOW}🗄️ Checking database connection (with 5s timeout)...${NC}"
    cd "$LARAVEL_ROOT"
    
    if [[ -z "${DB_DATABASE:-${MYSQL_DATABASE}}" ]]; then
        echo -e "${YELLOW}⚠️  Database not configured. Skipping database check.${NC}"
        DB_EXIT=1
    else
        if command -v timeout &> /dev/null; then
            DB_CHECK=$(timeout 5 bash -c "php artisan db:show 2>&1" 2>&1)
            DB_EXIT=$?
        elif command -v gtimeout &> /dev/null; then
            DB_CHECK=$(gtimeout 5 bash -c "php artisan db:show 2>&1" 2>&1)
            DB_EXIT=$?
        else
            echo -e "${YELLOW}   Checking database (max 5 seconds)...${NC}"
            php artisan db:show > /tmp/db_check.log 2>&1 &
            DB_PID=$!
            sleep 5
            if kill -0 $DB_PID 2>/dev/null; then
                kill $DB_PID 2>/dev/null
                echo -e "${YELLOW}⚠️  Database check timed out. Skipping.${NC}"
                DB_EXIT=1
            else
                DB_CHECK=$(cat /tmp/db_check.log 2>/dev/null || echo "")
                wait $DB_PID
                DB_EXIT=$?
            fi
            rm -f /tmp/db_check.log 2>/dev/null || true
        fi
    fi
    
    if [[ $DB_EXIT -eq 0 ]] && [[ -n "$DB_CHECK" ]] && ! echo "$DB_CHECK" | grep -qi "error\|exception\|failed"; then
        echo -e "${GREEN}✅ Database connected!${NC}"
        
        MIGRATE_OUTPUT=$(php artisan migrate:status 2>&1)
        DB_IS_EMPTY=1
        if echo "$MIGRATE_OUTPUT" | grep -q "No migrations found\|Nothing to migrate\|migrations table does not exist"; then
            DB_IS_EMPTY=0
        elif echo "$MIGRATE_OUTPUT" | grep -q "Pending\|Ran"; then
            DB_IS_EMPTY=1
        fi
        
        if [[ "${AUTO_INSTALL:-false}" == "true" ]] && [[ "$DB_IS_EMPTY" == "0" ]]; then
            echo -e "${YELLOW}📦 Database appears empty. Importing full database from SQL file...${NC}"
            IMPORT_RESULT=$(php artisan install:database --skip-errors 2>&1)
            if echo "$IMPORT_RESULT" | grep -qi "success\|imported\|completed"; then
                echo -e "${GREEN}✅ Database imported successfully!${NC}"
                echo "installed" > "$LARAVEL_ROOT/storage/LICENCE.txt" 2>/dev/null || true
                HAS_TABLES="1"
            else
                echo -e "${YELLOW}⚠️  SQL import had issues. Falling back to migrations...${NC}"
                echo "$IMPORT_RESULT" | head -20
                HAS_TABLES="0"
            fi
        else
            if [[ "$DB_IS_EMPTY" == "0" ]]; then
                HAS_TABLES="0"
            else
                HAS_TABLES="1"
            fi
        fi
        
        if [[ "$HAS_TABLES" == "0" ]]; then
            echo -e "${YELLOW}Running migrations (database is empty)...${NC}"
            if command -v timeout &> /dev/null; then
                timeout 30 php artisan migrate --force 2>&1 | head -20 || echo "Migrations completed or skipped"
            else
                php artisan migrate --force 2>&1 | head -20 || echo "Migrations completed or skipped"
            fi
        else
            if php artisan migrate:status 2>&1 | grep -q "Pending"; then
                echo -e "${YELLOW}Running pending migrations...${NC}"
                php artisan migrate --force 2>&1 | head -20 || echo "Migrations completed"
            else
                echo -e "${GREEN}✅ Database is up to date.${NC}"
            fi
        fi
        
        if [[ "${RUN_SEEDERS:-false}" == "true" ]] && [[ "${AUTO_INSTALL:-false}" != "true" ]]; then
            echo -e "${YELLOW}🌱 Running database seeders...${NC}"
            php artisan db:seed --force 2>&1 | head -20 || echo "Seeders completed or skipped"
        fi
    else
        echo -e "${YELLOW}⚠️  Database not ready or not configured. App will start but database operations may fail.${NC}"
    fi
    cd "$APP_ROOT"
else
    echo -e "${YELLOW}ℹ️  Running locally - skipping database check and migrations${NC}"
fi

echo -e "${YELLOW}⚡ Caching configuration (skipping if errors)...${NC}"
cd "$LARAVEL_ROOT"

# Ensure view cache directory exists before caching (critical!)
if [[ ! -d "$LARAVEL_ROOT/storage/framework/views" ]]; then
    mkdir -p "$LARAVEL_ROOT/storage/framework/views"
    chmod 775 "$LARAVEL_ROOT/storage/framework/views" 2>/dev/null || true
fi

# Cache config, routes, and views
php artisan config:cache 2>&1 | grep -v "does not exist" || echo "Config cache skipped"
php artisan route:cache 2>&1 | grep -v "does not exist" || echo "Route cache skipped"

# View cache - only if directory exists and is writable
if [[ -d "$LARAVEL_ROOT/storage/framework/views" ]] && [[ -w "$LARAVEL_ROOT/storage/framework/views" ]]; then
    php artisan view:cache 2>&1 | grep -v "does not exist" || echo "View cache skipped"
else
    echo -e "${YELLOW}⚠️  Skipping view cache - directory not writable${NC}"
fi

cd "$APP_ROOT"

echo -e "${GREEN}✅ Deployment setup complete!${NC}"

# Start PHP server
if [[ "$IS_RAILWAY" == "true" ]]; then
    if [[ -z "$PORT" ]]; then
        echo -e "${RED}❌ PORT environment variable is not set!${NC}"
        exit 1
    fi
    SERVER_PORT="$PORT"
else
    SERVER_PORT="${PORT:-8000}"
    echo -e "${YELLOW}⚠️  Running locally. Using port ${SERVER_PORT}${NC}"
fi

cd "$APP_ROOT" || {
    echo -e "${RED}❌ Cannot access app root: $APP_ROOT${NC}"
    exit 1
}

echo -e "${GREEN}Starting PHP server on port ${SERVER_PORT}...${NC}"
exec php -S 0.0.0.0:${SERVER_PORT} -t "$APP_ROOT" 2>&1

