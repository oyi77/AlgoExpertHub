# Deprecated Addons Migration Guide

## Overview

This guide helps you migrate from the deprecated individual trading addons to the new unified **Trading Management Addon**.

## Migration Timeline

- **Deprecated Date**: 2025-12-04
- **Support End Date**: 2026-03-01
- **Removal Date**: 2026-06-01

## Deprecated Addons

The following addons have been consolidated into Trading Management Addon:

1. **ai-trading-addon** → `ai_analysis` module
2. **copy-trading-addon** → `copy_trading` module
3. **filter-strategy-addon** → `filter_strategy` module
4. **smart-risk-management-addon** → `risk_management` module
5. **trading-execution-engine-addon** → `execution` module
6. **trading-preset-addon** → `risk_management` module

## Why Migrate?

### Benefits of Trading Management Addon
- **Unified System**: All trading features in one place
- **Better Performance**: Shared data, reduced API calls (90% reduction)
- **Event-Driven**: Efficient pipeline architecture
- **New Features**: Backtesting, marketplace, enhanced analytics
- **Easier Maintenance**: Single codebase, consistent updates

### Deprecated Addon Issues
- **Fragmented**: 7 separate systems
- **Duplicate Code**: 30% code duplication
- **Data Silos**: No data sharing between addons
- **Complex Setup**: Configure each addon separately
- **High API Usage**: Each addon fetches market data independently

---

## Migration Steps

### Step 1: Backup Data

**CRITICAL**: Backup your database before migrating!

```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup .env file
cp .env .env.backup
```

### Step 2: Install Trading Management Addon

```bash
cd main
composer update
php artisan migrate
```

The Trading Management Addon is already included in the codebase.

### Step 3: Run Migration Script

```bash
php artisan trading-management:migrate
```

This script will:
1. Migrate data from old addons to new tables
2. Convert configurations to new format
3. Update relationships and references
4. Preserve all historical data

### Step 4: Verify Migration

```bash
php artisan trading-management:verify-migration
```

This checks:
- All data migrated successfully
- No data loss
- Configurations converted correctly
- Relationships intact

### Step 5: Test Functionality

1. **Test Connections**: Verify exchange/broker connections work
2. **Test Execution**: Execute a test signal
3. **Test Positions**: Check position monitoring
4. **Test Analytics**: Review performance metrics

### Step 6: Disable Old Addons

Once verified, disable deprecated addons:

```bash
php artisan addon:disable ai-trading-addon
php artisan addon:disable copy-trading-addon
php artisan addon:disable filter-strategy-addon
php artisan addon:disable smart-risk-management-addon
php artisan addon:disable trading-execution-engine-addon
php artisan addon:disable trading-preset-addon
```

---

## Data Migration Details

### Execution Connections

**Old Table**: `execution_connections`
**New Table**: `tm_execution_connections`

**Migration**:
- All connections migrated with same credentials
- Connection types preserved (crypto/MT4/MT5)
- Health status recalculated
- Assigned to same users

**Verification**:
```sql
SELECT COUNT(*) FROM execution_connections;
SELECT COUNT(*) FROM tm_execution_connections;
-- Counts should match
```

### Risk Presets

**Old Tables**: `trading_presets`, `smart_risk_presets`
**New Table**: `tm_risk_presets`

**Migration**:
- Both preset types merged
- Settings normalized to common format
- User assignments preserved
- Default presets recreated

**Changes**:
- Unified configuration format
- Enhanced multi-TP support
- New AI adaptive risk features

### Filter Strategies

**Old Table**: `filter_strategies`
**New Table**: `tm_filter_strategies`

**Migration**:
- All strategies migrated
- Indicator configurations preserved
- User assignments maintained

**Enhancements**:
- More indicators available
- Better backtesting integration
- Improved performance

### Execution Positions

**Old Table**: `execution_positions`
**New Table**: `tm_execution_positions`

**Migration**:
- All positions (open and closed) migrated
- P&L history preserved
- Signal references updated

**Note**: Position IDs will change, but all data preserved.

### AI Configurations

**Old Table**: `ai_trading_configs`
**New Table**: `tm_ai_configs`

**Migration**:
- AI settings migrated
- Now uses AI Connection Addon for credentials
- Enhanced analysis features

**Action Required**:
- Configure AI connections in AI Connection Addon
- Link to Trading Management

### Copy Trading

**Old Tables**: `copy_traders`, `copy_followers`
**New Tables**: `tm_copy_traders`, `tm_copy_followers`

**Migration**:
- Trader profiles migrated
- Follower relationships preserved
- Performance history maintained

---

## Configuration Changes

### Environment Variables

**Old**:
```env
# Separate configs for each addon
EXECUTION_ENGINE_ENABLED=true
AI_TRADING_ENABLED=true
FILTER_STRATEGY_ENABLED=true
```

**New**:
```env
# Single addon config
TRADING_MANAGEMENT_ENABLED=true
```

### Module Configuration

Enable/disable modules in Trading Management settings:

```php
// config/trading-management.php
'modules' => [
    'execution' => true,
    'ai_analysis' => true,
    'filter_strategy' => true,
    'risk_management' => true,
    'copy_trading' => true,
    'backtesting' => true,
    // ...
]
```

---

## API Changes

### Endpoint Updates

**Old Endpoints** (Deprecated):
```
/api/execution-engine/connections
/api/ai-trading/analyze
/api/filter-strategy/strategies
/api/trading-preset/presets
```

**New Endpoints**:
```
/api/trading-management/connections
/api/trading-management/ai-analysis
/api/trading-management/filter-strategies
/api/trading-management/presets
```

### Response Format

Response format remains the same:
```json
{
    "data": {...},
    "message": "Success"
}
```

### Authentication

No changes - still uses Laravel Sanctum tokens.

---

## Code Changes

### Service Calls

**Old**:
```php
use App\Services\ExecutionEngine\SignalExecutionService;

$service = app(SignalExecutionService::class);
$result = $service->execute($signal, $connection);
```

**New**:
```php
use Addons\TradingManagement\Modules\Execution\Services\ExecutionService;

$service = app(ExecutionService::class);
$result = $service->executeSignal($signal, $connection);
```

### Event Listeners

**Old**:
```php
// Listen to execution engine events
Event::listen(SignalExecuted::class, function ($event) {
    // ...
});
```

**New**:
```php
// Listen to trading management events
Event::listen(\Addons\TradingManagement\Events\SignalExecuted::class, function ($event) {
    // ...
});
```

---

## Rollback Procedure

If you need to rollback:

### 1. Restore Database
```bash
mysql -u username -p database_name < backup_YYYYMMDD.sql
```

### 2. Re-enable Old Addons
```bash
php artisan addon:enable ai-trading-addon
php artisan addon:enable copy-trading-addon
# ... etc
```

### 3. Disable Trading Management
```bash
php artisan addon:disable trading-management-addon
```

### 4. Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Troubleshooting

### Migration Failed

**Problem**: Migration script errors

**Solutions**:
1. Check database connection
2. Verify sufficient disk space
3. Review error logs: `storage/logs/laravel.log`
4. Run migration with verbose flag: `php artisan trading-management:migrate --verbose`
5. Contact support with error details

### Data Missing

**Problem**: Some data not migrated

**Solutions**:
1. Run verification script: `php artisan trading-management:verify-migration`
2. Check migration logs
3. Manually migrate missing data (see SQL scripts below)
4. Restore from backup if needed

### Connections Not Working

**Problem**: Exchange connections failing after migration

**Solutions**:
1. Test connections: `/admin/trading-management/connections/{id}/test`
2. Verify API credentials
3. Check connection health status
4. Re-enter credentials if needed

### Positions Not Updating

**Problem**: Position monitoring not working

**Solutions**:
1. Verify queue worker running: `php artisan queue:work`
2. Check scheduled tasks: `php artisan schedule:list`
3. Manually trigger monitoring: `php artisan trading-management:monitor-positions`
4. Review position logs

---

## Manual Migration (If Needed)

### Migrate Connections Manually

```sql
INSERT INTO tm_execution_connections (
    user_id, name, type, exchange, credentials, status, created_at, updated_at
)
SELECT 
    user_id, name, type, exchange, credentials, status, created_at, updated_at
FROM execution_connections
WHERE user_id = YOUR_USER_ID;
```

### Migrate Presets Manually

```sql
INSERT INTO tm_risk_presets (
    user_id, name, risk_type, risk_amount, settings, created_at, updated_at
)
SELECT 
    user_id, name, risk_type, risk_amount, settings, created_at, updated_at
FROM trading_presets
WHERE user_id = YOUR_USER_ID;
```

---

## Support

### Getting Help

- **Migration Issues**: Submit support ticket with error logs
- **Data Questions**: Contact support with user ID
- **Technical Problems**: Include Laravel logs and migration output

### Migration Assistance

We offer free migration assistance:
- **Email**: support@yoursite.com
- **Live Chat**: Available during business hours
- **Scheduled Call**: Book a migration assistance call

---

## FAQ

**Q: Will I lose any data?**
A: No, all data is migrated. Old tables preserved for 6 months.

**Q: How long does migration take?**
A: 5-15 minutes depending on data volume.

**Q: Can I migrate gradually?**
A: No, it's all-or-nothing. Test on staging first.

**Q: What if migration fails?**
A: Rollback procedure restores everything. No data lost.

**Q: Do I need to reconfigure everything?**
A: No, configurations are migrated automatically.

**Q: Will my API integrations break?**
A: Update endpoints, but authentication and format unchanged.

**Q: Can I still access old addon data?**
A: Yes, old tables preserved (read-only) for 6 months.

---

**Last Updated**: 2025-12-22
**Migration Script Version**: 2.0.0
