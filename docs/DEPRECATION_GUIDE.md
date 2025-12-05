# Deprecation Guide - Old Addons Migration

**Version**: 1.0  
**Date**: 2025-12-05  
**Status**: Active

---

## Overview

This guide documents the deprecation of old fragmented addons in favor of the unified Trading Management Addon.

## Deprecated Addons

The following addons are **DEPRECATED** and should be migrated:

1. **filter-strategy-addon** → Migrated to `trading-management-addon/Modules/FilterStrategy`
2. **ai-trading-addon** → Migrated to `trading-management-addon/Modules/AiAnalysis`
3. **trading-preset-addon** → Migrated to `trading-management-addon/Modules/RiskManagement`
4. **smart-risk-management-addon** → Migrated to `trading-management-addon/Modules/RiskManagement`
5. **trading-execution-engine-addon** → Migrated to `trading-management-addon/Modules/Execution` + `PositionMonitoring`
6. **copy-trading-addon** → Migrated to `trading-management-addon/Modules/CopyTrading`
7. **openrouter-integration-addon** → Migrated to `ai-connection-addon`

## Migration Status

| Addon | Status | Replacement | Migration Required |
|-------|--------|-------------|-------------------|
| filter-strategy-addon | ✅ Migrated | trading-management-addon | Data: None (compatible) |
| ai-trading-addon | ✅ Migrated | trading-management-addon | Data: Optional (ai_connection_id) |
| trading-preset-addon | ✅ Migrated | trading-management-addon | Data: None (compatible) |
| smart-risk-management-addon | ✅ Migrated | trading-management-addon | Data: None (compatible) |
| trading-execution-engine-addon | ✅ Migrated | trading-management-addon | Data: None (compatible) |
| copy-trading-addon | ✅ Migrated | trading-management-addon | Data: None (compatible) |
| openrouter-integration-addon | ✅ Migrated | ai-connection-addon | Data: Manual migration |

## Migration Steps

### Step 1: Backup

```bash
# Backup database
mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup addons directory
cp -r main/addons main/addons_backup_$(date +%Y%m%d_%H%M%S)
```

### Step 2: Verify New Addon is Active

Check that `trading-management-addon` is active:
- Admin panel: `/admin/addons`
- Or check `AppServiceProvider.php` registration

### Step 3: Run Migration Scripts

See `docs/migration-scripts/` directory for specific migration scripts.

### Step 4: Test Functionality

1. Test filter strategies
2. Test AI model profiles
3. Test risk presets
4. Test execution connections
5. Test copy trading

### Step 5: Deactivate Old Addons

Via admin panel or manually in `AppServiceProvider.php`

### Step 6: Monitor for 30 Days

Keep old addons disabled but not deleted for 30 days to ensure stability.

### Step 7: Cleanup (After 30 Days)

Move old addon folders to `addons/_deprecated/` or delete if confident.

## URL Redirects

Old URLs automatically redirect to new URLs. See `trading-management-addon/routes/admin.php` for redirect mappings.

## Breaking Changes

### Namespace Changes

**Old**:
```php
use Addons\FilterStrategyAddon\App\Services\FilterStrategyEvaluator;
```

**New**:
```php
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
```

### Service Changes

Most services maintain same interface. Check individual migration guides for details.

## Support

For migration issues, see:
- `docs/trading-management-addon/MIGRATION-GUIDE.md`
- `docs/trading-management-consolidation-analysis.md`

---

**Last Updated**: 2025-12-05
