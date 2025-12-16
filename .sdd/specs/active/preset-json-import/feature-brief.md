# Feature Brief: Preset JSON Import & Configuration Mapping

**Created:** 2025-12-10  
**Status:** Planning  
**Priority:** Medium

## User Story

As a trader, I want to import complex preset configurations from JSON format so that I can use advanced trading strategies with multiple timeframes, indicators, entry rules, and filters without manually configuring each field in the UI.

## Problem Statement

The current `trading_presets` table structure is designed for basic risk management (position sizing, SL/TP, trailing stop, etc.) but does not support:

1. **Multi-timeframe configurations** - JSON has 7 different timeframes (H4, H1, M15, M5, M30, D1)
2. **Indicator configurations** - JSON defines EMA, Stochastic, RSI, MACD, PSAR, ATR, Bollinger per timeframe
3. **Complex entry rules** - JSON has logic hierarchy, trend requirements, entry signals, instant triggers, price action confirmation
4. **Advanced filters** - JSON has volatility, spread, session, economic events, pair-specific filters
5. **Execution settings** - JSON has order type, slippage tolerance, requote handling, timing
6. **Monitoring & alerts** - JSON has gold/silver ratio, VIX correlation, intraday gap alerts
7. **Adaptive settings** - JSON has aggression levels (conservative, aggressive, neutral)
8. **Backtesting parameters** - JSON has data quality, spread model, commission settings

## Current Database Structure

The `trading_presets` table supports:
- Basic identity (name, description, symbol, timeframe, tags)
- Position sizing (FIXED, RISK_PERCENT)
- Stop Loss (PIPS, R_MULTIPLE, STRUCTURE)
- Take Profit (SINGLE, MULTI with TP1/TP2/TP3)
- Break Even & Trailing Stop
- Layering/Grid & Hedging
- Trading Schedule (hours, timezone, days)
- Weekly Targets
- Filter Strategy & AI Model integration (via foreign keys)

## Required Adjustments

### Option 1: Store as JSON Column (Quick Solution)
Add a `config_json` JSON column to `trading_presets` table to store the full JSON configuration while keeping existing fields for backward compatibility.

**Pros:**
- Quick to implement
- No breaking changes
- Flexible for any JSON structure

**Cons:**
- Not queryable/searchable
- Validation happens in application layer
- Harder to maintain

### Option 2: Normalize to Related Tables (Recommended)
Create separate tables for:
- `preset_timeframes` - Store multiple timeframe configurations
- `preset_indicators` - Store indicator configurations per timeframe
- `preset_entry_rules` - Store entry rule logic
- `preset_filters` - Store filter configurations
- `preset_execution_settings` - Store execution parameters
- `preset_monitoring_settings` - Store monitoring/alerts config
- `preset_adaptive_settings` - Store adaptive behavior config

**Pros:**
- Queryable and searchable
- Type-safe validation
- Better data integrity
- Easier to maintain

**Cons:**
- More complex migration
- More tables to manage
- Requires refactoring existing code

### Option 3: Hybrid Approach (Best for Now)
Keep existing fields for basic risk management, add `config_json` JSON column for advanced features, and create helper methods to parse/merge both.

**Pros:**
- Backward compatible
- Supports both simple and complex presets
- Can migrate to Option 2 later

**Cons:**
- Two sources of truth
- Need merge logic

## Mapping JSON to Database Fields

### Direct Mappings (Already Supported)

| JSON Field | Database Field | Notes |
|------------|---------------|-------|
| `preset_name` | `name` | Direct mapping |
| `description` | `description` | Direct mapping |
| `pairs[0]` | `symbol` | Use first pair, or store all in `tags` |
| `asset_class` | `tags` | Add to tags array |
| `risk_management.position_sizing.max_risk_per_trade` | `risk_per_trade_pct` | Convert to percentage |
| `risk_management.position_sizing.max_position_size_percent` | `max_positions_per_symbol` | Approximate mapping |
| `risk_management.stop_loss.initial.method` | `sl_mode` | Map "atr" → "STRUCTURE" |
| `risk_management.stop_loss.initial.multiplier_xauusd` | `sl_r_multiple` | Use XAUUSD value |
| `risk_management.stop_loss.initial.min_pips_xauusd` | `sl_pips` | Use XAUUSD value |
| `risk_management.stop_loss.trailing.enabled` | `ts_enabled` | Direct mapping |
| `risk_management.stop_loss.trailing.step_pips_xauusd` | `ts_step_pips` | Use XAUUSD value |
| `risk_management.stop_loss.breakeven.enabled` | `be_enabled` | Direct mapping |
| `risk_management.stop_loss.breakeven.activation_multiplier` | `be_trigger_rr` | Direct mapping |
| `risk_management.take_profit.levels` | `tp1_rr`, `tp2_rr`, `tp3_rr` | Map levels array |
| `risk_management.take_profit.partial_exits` | `tp1_close_pct`, `tp2_close_pct`, `tp3_close_pct` | Map partial exits |
| `risk_management.daily_limits.max_trades_xauusd` | `max_positions` | Approximate mapping |
| `filters.session.optimal_sessions` | `trading_hours_start`, `trading_hours_end` | Use first optimal session |
| `filters.session.avoid_sessions` | `trading_days_mask` | Calculate mask from avoid times |

### Fields Requiring JSON Storage

These fields don't have direct database columns and should be stored in `config_json`:

1. **Timeframes Configuration**
   ```json
   {
     "timeframes": {
       "primary_trend": "H4",
       "momentum": "H1",
       "entry_signal": "M15",
       "trigger_execution": "M5",
       "confirmation": "M30",
       "sr_mapping": "D1",
       "exit_management": "M30"
     }
   }
   ```

2. **Indicators Configuration** (per timeframe)
   ```json
   {
     "indicators": {
       "h4": { "ema": {...}, "stochastic": {...}, ... },
       "h1": { "ema": {...}, "rsi": {...}, ... },
       "m15": { "ema": {...}, "volume": {...}, ... },
       "m5": { "ema": {...}, "price_action": {...} }
     }
   }
   ```

3. **Entry Rules**
   ```json
   {
     "entry_rules": {
       "logic_hierarchy": "TOP_DOWN_WITH_CONFIRMATION",
       "trend_requirement": {...},
       "entry_signals": {...},
       "instant_trigger": {...},
       "price_action_confirmation": {...}
     }
   }
   ```

4. **Advanced Filters**
   ```json
   {
     "filters": {
       "volatility": {...},
       "spread": {...},
       "session": {...},
       "economic_events": {...},
       "pair_specific": {...}
     }
   }
   ```

5. **Execution Settings**
   ```json
   {
     "execution": {
       "order_type": "LIMIT",
       "slippage_tolerance_xauusd": 5,
       "requote_handling": "reject",
       "timing": {...}
     }
   }
   ```

6. **Monitoring Settings**
   ```json
   {
     "monitoring": {
       "alerts": {...},
       "performance_tracking": {...}
     }
   }
   ```

7. **Adaptive Settings**
   ```json
   {
     "adaptive_settings": {
       "aggression_levels": {...}
     }
   }
   ```

8. **Backtesting Parameters**
   ```json
   {
     "backtesting_parameters": {...}
   }
   ```

## Implementation Plan

### Phase 1: Database Migration
1. Add `config_json` JSON column to `trading_presets` table
2. Add `version` VARCHAR(10) column for preset version tracking
3. Add index on `config_json` for JSON queries (MySQL 5.7+)

### Phase 2: Import Service
1. Create `PresetJsonImportService` to:
   - Parse JSON input
   - Map direct fields to database columns
   - Store advanced config in `config_json`
   - Validate JSON structure
   - Handle versioning

2. Create `PresetJsonExportService` to:
   - Export preset to JSON format
   - Merge database fields with `config_json`
   - Generate full JSON structure

### Phase 3: Controller & Routes
1. Add `import()` method to `RiskPresetController`
2. Add `export($id)` method to `RiskPresetController`
3. Add routes: `POST /admin/trading-management/config/risk-presets/import`, `GET /admin/trading-management/config/risk-presets/{id}/export`

### Phase 4: UI Integration
1. Add "Import JSON" button in preset list page
2. Add file upload form for JSON import
3. Add "Export JSON" button in preset detail page
4. Show validation errors if JSON structure invalid

### Phase 5: Preset Applicator Updates
1. Update `PresetApplicatorService` to read from both database fields and `config_json`
2. Implement indicator calculation logic from `config_json.indicators`
3. Implement entry rule evaluation from `config_json.entry_rules`
4. Implement filter evaluation from `config_json.filters`

## Validation Rules

### JSON Structure Validation
- Required top-level keys: `preset_name`, `version`, `description`
- Validate `timeframes` object structure
- Validate `indicators` object structure (per timeframe)
- Validate `entry_rules` logic hierarchy
- Validate `risk_management` structure
- Validate `filters` structure

### Data Type Validation
- `version`: String, format "X.Y" (e.g., "3.0")
- `pairs`: Array of strings (symbol names)
- `timeframes.*`: Valid timeframe strings (M1, M5, M15, M30, H1, H4, D1, W1, MN1)
- `indicators.*.*`: Valid indicator names and parameters
- `risk_management.*`: Numeric values within valid ranges

## Example Import Flow

```php
// 1. User uploads JSON file
$json = file_get_contents($request->file('preset_json'));

// 2. Parse and validate
$service = new PresetJsonImportService();
$parsed = $service->parse($json);

// 3. Map to database
$preset = TradingPreset::create([
    'name' => $parsed['preset_name'],
    'description' => $parsed['description'],
    'symbol' => $parsed['pairs'][0] ?? null,
    'tags' => array_merge($parsed['pairs'] ?? [], [$parsed['asset_class'] ?? '']),
    'risk_per_trade_pct' => $parsed['risk_management']['position_sizing']['max_risk_per_trade'],
    'sl_mode' => $service->mapSlMode($parsed['risk_management']['stop_loss']['initial']['method']),
    'sl_pips' => $parsed['risk_management']['stop_loss']['initial']['min_pips_xauusd'] ?? null,
    'ts_enabled' => $parsed['risk_management']['stop_loss']['trailing']['enabled'] ?? false,
    'ts_step_pips' => $parsed['risk_management']['stop_loss']['trailing']['step_pips_xauusd'] ?? null,
    'be_enabled' => $parsed['risk_management']['stop_loss']['breakeven']['enabled'] ?? false,
    'be_trigger_rr' => $parsed['risk_management']['stop_loss']['breakeven']['activation_multiplier'] ?? null,
    'tp_mode' => 'MULTI',
    'tp1_rr' => $parsed['risk_management']['take_profit']['levels'][0] ?? null,
    'tp1_close_pct' => $parsed['risk_management']['take_profit']['partial_exits'][0]['close_percent'] ?? null,
    'tp2_rr' => $parsed['risk_management']['take_profit']['levels'][1] ?? null,
    'tp2_close_pct' => $parsed['risk_management']['take_profit']['partial_exits'][1]['close_percent'] ?? null,
    'tp3_rr' => $parsed['risk_management']['take_profit']['levels'][2] ?? null,
    'tp3_close_pct' => $parsed['risk_management']['take_profit']['partial_exits'][2]['close_percent'] ?? null,
    'config_json' => json_encode([
        'version' => $parsed['version'],
        'timeframes' => $parsed['timeframes'],
        'indicators' => $parsed['indicators'],
        'entry_rules' => $parsed['entry_rules'],
        'filters' => $parsed['filters'],
        'execution' => $parsed['execution'],
        'monitoring' => $parsed['monitoring'],
        'adaptive_settings' => $parsed['adaptive_settings'],
        'backtesting_parameters' => $parsed['backtesting_parameters'],
    ]),
]);
```

## Files to Create/Modify

### New Files
- `main/addons/trading-management-addon/Modules/RiskManagement/Services/PresetJsonImportService.php`
- `main/addons/trading-management-addon/Modules/RiskManagement/Services/PresetJsonExportService.php`
- `main/addons/trading-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_add_config_json_to_trading_presets.php`
- `main/addons/trading-management-addon/resources/views/backend/trading-management/config/risk-presets/import.blade.php`

### Modified Files
- `main/addons/trading-management-addon/Modules/RiskManagement/Controllers/Backend/RiskPresetController.php` (add import/export methods)
- `main/addons/trading-management-addon/Modules/RiskManagement/Models/TradingPreset.php` (add `config_json` to fillable, add accessor/mutator)
- `main/addons/trading-management-addon/resources/views/backend/trading-management/config/risk-presets/index.blade.php` (add import button)
- `main/addons/trading-management-addon/resources/views/backend/trading-management/config/risk-presets/show.blade.php` (add export button)

## Testing Checklist

- [ ] Import JSON with all fields populated
- [ ] Import JSON with minimal fields (backward compatible)
- [ ] Export preset to JSON format
- [ ] Validate JSON structure on import
- [ ] Handle invalid JSON gracefully
- [ ] Map direct fields correctly
- [ ] Store advanced config in `config_json`
- [ ] Preset applicator reads from both sources
- [ ] UI shows import/export buttons
- [ ] Error messages are user-friendly

## Notes

- The JSON structure is very comprehensive and designed for advanced trading strategies
- Not all features in JSON may be immediately usable (e.g., indicator calculations, entry rule evaluation)
- Consider implementing features incrementally:
  1. Phase 1: Import/export JSON (storage only)
  2. Phase 2: Basic indicator support
  3. Phase 3: Entry rule evaluation
  4. Phase 4: Advanced filters
  5. Phase 5: Adaptive settings
- The `config_json` approach allows flexibility while maintaining backward compatibility with existing presets

