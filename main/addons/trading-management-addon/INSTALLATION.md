# Trading Management Addon - Installation Guide

**Version**: 2.0.0  
**Status**: Production Ready (Phases 1-7 Complete)

---

## Prerequisites

- PHP 8.0+
- Laravel 9.x
- MySQL 5.7+
- Composer
- Queue worker (supervisor or similar)
- Cron job for scheduler

---

## Installation Steps

### Step 1: Addon Already Installed

The addon is located in `main/addons/trading-management-addon/`

Verify structure:
```bash
cd main/addons
ls -la trading-management-addon/
```

### Step 2: Install Dependencies

```bash
cd main
composer require guzzlehttp/guzzle
# composer require ccxt/ccxt (optional, for crypto exchanges)
```

### Step 3: Environment Configuration

Copy addon env example:
```bash
cat addons/trading-management-addon/.env.example >> .env
```

Edit `.env` and configure:
```env
# mtapi.io Settings (required for FX data)
MTAPI_API_KEY=your_actual_api_key_here
MTAPI_ACCOUNT_ID=your_mt_account_id_here
MTAPI_BASE_URL=https://api.mtapi.io

# Data Settings
TM_FETCH_INTERVAL=5
TM_DATA_RETENTION_DAYS=365
TM_CACHE_TTL=300
```

### Step 4: Run Migrations

```bash
php artisan migrate
```

**Expected Output**:
```
Migrating: 2025_12_04_100000_create_data_connections_table
Migrated:  2025_12_04_100000_create_data_connections_table
... (14 migrations total)
```

**Tables Created**:
- data_connections
- market_data
- data_connection_logs
- filter_strategies
- ai_model_profiles
- trading_presets
- srm_signal_provider_metrics
- execution_connections
- execution_logs
- execution_positions
- execution_analytics
- copy_trading_subscriptions
- copy_trading_executions
- (+ 1 more if backtesting enabled)

### Step 5: Verify Addon Registration

Check `config/app.php` or `App\Providers\AppServiceProvider.php`:

Should contain:
```php
'trading-management-addon' => \Addons\TradingManagement\AddonServiceProvider::class,
```

### Step 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 7: Verify Queue Worker

Ensure queue worker is running:
```bash
php artisan queue:work --tries=3
```

Or with supervisor (production):
```ini
[program:trading-management-worker]
command=php /path/to/main/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
```

### Step 8: Verify Scheduler

Ensure cron job is configured:
```bash
* * * * * cd /path/to/main && php artisan schedule:run >> /dev/null 2>&1
```

Verify scheduled tasks:
```bash
php artisan schedule:list
```

**Expected**:
- `trading-management:fetch-market-data` (every 5 minutes)
- `trading-management:cleanup-market-data` (daily at 02:00)

---

## Verification

### Test Installation

```bash
php artisan tinker
```

```php
// Test models load
$dc = new Addons\TradingManagement\Modules\DataProvider\Models\DataConnection();
echo get_class($dc); // Should output class name

// Test services
$service = app(Addons\TradingManagement\Modules\MarketData\Services\MarketDataService::class);
echo get_class($service);

// Check tables exist
DB::select('SHOW TABLES LIKE "%trading%"');
DB::select('SHOW TABLES LIKE "%execution%"');

exit
```

### Access Admin Panel

Navigate to: `/admin/trading-management`

**Expected**: Dashboard with 5 submenu cards

---

## Configuration

### mtapi.io Setup

1. Get API key from https://mtapi.io/
2. Add to `.env`:
   ```env
   MTAPI_API_KEY=your_key_here
   ```
3. Create connection in admin panel:
   - Go to: `/admin/trading-management/config/data-connections`
   - Click "Create Connection"
   - Fill in details
   - Test connection
   - Activate

### Optional: Publish Config

```bash
php artisan vendor:publish --tag=trading-management-config
```

Edit `config/trading-management.php` to customize settings.

---

## Troubleshooting

### Issue: Migrations Fail

**Solution**:
```bash
# Check if tables already exist
php artisan tinker
>>> DB::select('SHOW TABLES');

# Drop old tables if migrating from old addons
# Backup first!
php artisan migrate:fresh --path=addons/trading-management-addon/database/migrations
```

### Issue: Routes Not Found

**Solution**:
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep trading-management
```

### Issue: Views Not Found

**Solution**:
```bash
php artisan view:clear
php artisan config:clear
```

### Issue: Jobs Not Running

**Solution**:
```bash
# Check queue connection
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Post-Installation

### 1. Create First Data Connection

- Navigate to `/admin/trading-management/config/data-connections`
- Create mtapi.io connection
- Test and activate

### 2. Monitor Data Fetching

Wait 5 minutes, then check:
```bash
php artisan tinker
>>> DB::table('market_data')->count();
>>> DB::table('data_connection_logs')->latest()->first();
```

### 3. Create Trading Presets

- Navigate to `/admin/trading-management/config/risk-presets` (Phase 7+ UI)
- Or create via tinker for now

### 4. Explore UI

Check all 5 submenus:
- Trading Configuration
- Trading Operations
- Trading Strategy
- Copy Trading
- Trading Test

---

## Uninstallation

**Warning**: This will delete ALL trading data!

```bash
# Drop all tables
php artisan migrate:rollback --path=addons/trading-management-addon/database/migrations

# Remove addon registration from AppServiceProvider

# Delete addon folder
rm -rf addons/trading-management-addon/
```

---

## Support

- Documentation: `docs/` folder
- bd Issues: `bd show AlgoExpertHub-0my`
- Addon README: `addons/trading-management-addon/README.md`

---

**Status**: ✅ Ready for production use (with testing)

