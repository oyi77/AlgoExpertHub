# Migration Guide - From Old Addons to Trading Management Addon

**Version**: 2.0.0  
**Date**: 2025-12-04  
**Status**: Ready for Migration

---

## Overview

This guide helps you migrate from the old fragmented addons to the new unified Trading Management Addon.

**Old Addons** (To be deprecated):
- filter-strategy-addon
- ai-trading-addon
- trading-preset-addon
- smart-risk-management-addon
- trading-execution-engine-addon
- copy-trading-addon

**New Addon**:
- trading-management-addon (with 9 modules)

---

## Migration Strategy

### Phase 1: Preparation

1. **Backup Everything**
   ```bash
   # Backup database
   mysqldump -u root -p database_name > backup_pre_migration.sql
   
   # Backup code
   cp -r main/addons main/addons_backup
   ```

2. **Test Environment First**
   - Never migrate directly on production
   - Test on staging/development first
   - Verify all functionality works

### Phase 2: Install New Addon

1. **Already installed** in `main/addons/trading-management-addon/`

2. **Run migrations**:
   ```bash
   cd main
   php artisan migrate --path=addons/trading-management-addon/database/migrations
   ```

3. **Register addon**:
   - Already registered in `AppServiceProvider.php`
   - Activate via admin panel: `/admin/addons` (if addon management exists)

### Phase 3: Data Migration

**Good News**: Most tables are compatible!

#### 3.1 Filter Strategies (No Changes Needed)

Table `filter_strategies` is identical in both addons.

**Action**: None required. Data automatically available in new addon.

#### 3.2 AI Model Profiles (Minor Changes)

New addon adds `ai_connection_id` field (nullable).

**Action**: Optional - link existing profiles to ai-connection-addon:
```sql
-- Optional: Link to centralized AI connections
UPDATE ai_model_profiles 
SET ai_connection_id = (SELECT id FROM ai_connections WHERE provider = ai_model_profiles.provider LIMIT 1)
WHERE ai_connection_id IS NULL;
```

#### 3.3 Trading Presets (New Fields Added)

New addon adds smart risk fields (all nullable, backward compatible).

**Action**: None required. Existing presets work as-is.

Optional - enable smart risk:
```sql
-- Enable smart risk for specific presets
UPDATE trading_presets 
SET smart_risk_enabled = 1,
    smart_risk_min_score = 60
WHERE id IN (1, 2, 3); -- Your preset IDs
```

#### 3.4 Execution Connections (New Fields Added)

New addon adds:
- `preset_id` (link to trading_presets)
- `data_connection_id` (link to data_connections)

**Action**: Optional - link existing connections:
```sql
-- Link to default preset
UPDATE execution_connections 
SET preset_id = 1 -- Your default preset ID
WHERE preset_id IS NULL;

-- Optionally link to data connection
-- (if you create a matching data connection for market data)
```

#### 3.5 Copy Trading (No Changes Needed)

Tables compatible.

**Action**: Update foreign key reference (already handled in migration).

---

### Phase 4: Test New Addon

1. **Access new UI**:
   - URL: `/admin/trading-management`
   - Verify 5 submenus visible
   - Click each submenu, verify tabs work

2. **Test Data Connections**:
   - Go to Trading Configuration → Data Connections
   - Create mtapi.io connection
   - Test connection
   - Activate

3. **Test Execution Connections**:
   - Go to Trading Operations → Connections tab (Phase 7+ UI)
   - Verify existing connections visible
   - Test connection

4. **Test Risk Presets**:
   - Verify existing presets visible
   - Create new preset
   - Enable smart risk (toggle)

5. **Test Filter Strategies**:
   - Verify existing strategies visible

6. **Test Copy Trading**:
   - Verify subscriptions visible

---

### Phase 5: Gradual Switchover

**Recommended**: Run both addons in parallel for 1-2 weeks

1. **Keep old addons active** (for now)
2. **Start using new addon** for new features
3. **Monitor** for issues
4. **After verification**, deactivate old addons

---

### Phase 6: Deactivate Old Addons

**After** verifying new addon works:

1. **Deactivate via admin panel** (if addon management exists):
   - `/admin/addons`
   - Disable old addons one by one

2. **Or manually** in `AppServiceProvider.php`:
   ```php
   // Comment out old addon registrations
   // 'filter-strategy-addon' => \Addons\FilterStrategyAddon\AddonServiceProvider::class,
   // 'ai-trading-addon' => \Addons\AiTradingAddon\AddonServiceProvider::class,
   // etc.
   ```

3. **Clear cache**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

---

### Phase 7: Cleanup (Optional)

**After** 30 days of stable operation:

1. **Remove old addon folders** (backup first!):
   ```bash
   mv addons/filter-strategy-addon addons/_deprecated/
   mv addons/ai-trading-addon addons/_deprecated/
   mv addons/trading-preset-addon addons/_deprecated/
   mv addons/smart-risk-management-addon addons/_deprecated/
   mv addons/trading-execution-engine-addon addons/_deprecated/
   mv addons/copy-trading-addon addons/_deprecated/
   ```

2. **Remove from composer** (if listed):
   - Edit `composer.json`
   - Remove old addon references
   - Run `composer dump-autoload`

---

## Feature Mapping

### Old → New

| Old Addon Feature | New Location |
|-------------------|--------------|
| filter-strategy-addon → Filter Strategies | Trading Strategy submenu → Filter Strategies tab |
| ai-trading-addon → AI Model Profiles | Trading Strategy submenu → AI Model Profiles tab |
| trading-preset-addon → Risk Presets | Trading Configuration submenu → Risk Presets tab |
| smart-risk-management-addon → Smart Risk | Trading Configuration submenu → Smart Risk Settings tab |
| trading-execution-engine-addon → Connections | Trading Operations submenu → Connections tab |
| trading-execution-engine-addon → Executions | Trading Operations submenu → Executions tab |
| trading-execution-engine-addon → Positions | Trading Operations submenu → Open/Closed Positions tabs |
| trading-execution-engine-addon → Analytics | Trading Operations submenu → Analytics tab |
| copy-trading-addon → Copy Trading | Copy Trading submenu → All tabs |

---

## URL Changes

### Old URLs → New URLs

| Old | New |
|-----|-----|
| `/admin/filter-strategies` | `/admin/trading-management/strategy?tab=filters` |
| `/admin/ai-model-profiles` | `/admin/trading-management/strategy?tab=ai-models` |
| `/admin/risk-presets` | `/admin/trading-management/config/risk-presets` |
| `/admin/execution-connections` | `/admin/trading-management/operations?tab=connections` |
| `/admin/executions` | `/admin/trading-management/operations?tab=executions` |
| `/admin/positions` | `/admin/trading-management/operations?tab=open` |
| `/admin/copy-trading` | `/admin/trading-management/copy-trading` |

**Note**: Old URLs will redirect to new URLs (Phase 7+ redirects).

---

## Breaking Changes

### None for Data

All database tables are compatible. Existing data works in new addon.

### API Changes

If you're using addon services programmatically:

**Old**:
```php
use Addons\FilterStrategyAddon\App\Services\FilterStrategyEvaluator;
$evaluator = app(FilterStrategyEvaluator::class);
```

**New**:
```php
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
$evaluator = app(FilterStrategyEvaluator::class);
```

**Solution**: Update namespace imports in your custom code.

---

## Benefits After Migration

✅ **Unified UI**: 5 submenus instead of 12 separate menus  
✅ **Better Performance**: Centralized market data (90% fewer API calls)  
✅ **Easier Maintenance**: Update 1 addon instead of 7  
✅ **Clearer Architecture**: Modular design with clear dependencies  
✅ **Unified Risk**: Toggle between manual and AI risk in same preset  
✅ **Better Data Flow**: Clear pipeline (data → filter → AI → risk → execution)  

---

## Support During Migration

### If Issues Occur

1. **Rollback**:
   ```bash
   php artisan migrate:rollback --path=addons/trading-management-addon/database/migrations
   # Restore from backup
   ```

2. **Check logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Create bd issue**:
   ```bash
   bd create "Migration issue: [description]" -t bug --deps discovered-from:AlgoExpertHub-0my
   ```

---

## Timeline

- **Week 1**: Install new addon, run migrations
- **Week 2**: Test in staging, parallel run
- **Week 3**: Switchover on production
- **Week 4**: Monitor and verify
- **Week 5+**: Deactivate old addons

**Total**: 4-5 weeks for safe migration

---

**Status**: ✅ Migration guide complete  
**Complexity**: Low (backward compatible)  
**Risk**: Low (data structure compatible)

