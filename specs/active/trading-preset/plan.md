# Technical Plan: Trading Preset Addon

**Created:** 2025-01-29
**Last Updated:** 2025-01-29
**Status:** ACTIVE
**Version:** 1.0

## Architecture Overview

The Trading Preset Addon provides a comprehensive preset system for trading configurations that can be applied to signals, trading bots, and copy trading executions. The architecture follows a modular, service-oriented design that integrates seamlessly with existing execution systems while maintaining decoupling through well-defined interfaces.

### Core Principles
- **Preset-Driven Configuration**: All trading parameters are stored in reusable presets
- **Multi-Target Application**: Presets can be applied to signals, execution connections, copy trading subscriptions, and trading bots
- **Hierarchical Override**: System-level defaults → User presets → Connection-specific overrides
- **Validation & Safety**: Comprehensive validation ensures trading safety parameters
- **Template System**: Default presets serve as templates for new users
- **Marketplace Integration**: Public presets can be shared via marketplace (future-ready)

### System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                    PRESET MANAGEMENT LAYER                       │
│  TradingPreset Model | PresetService | PresetController        │
│  Admin & User UI | Preset Validation | Preset Cloning          │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│              PRESET APPLICATION LAYER                             │
│  PresetApplicatorService | PresetResolverService                │
│  Applies presets to: Signals | Connections | Copy Trading      │
└──────────────────────┬──────────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   SIGNAL     │ │  EXECUTION   │ │ COPY TRADING │
│  EXECUTION   │ │  CONNECTION  │ │  SUBSCRIPTION│
│   ENGINE     │ │   SETTINGS   │ │   SETTINGS   │
└──────────────┘ └──────────────┘ └──────────────┘
```

### Component Responsibilities

**1. TradingPreset Model**
- Stores all preset configuration data
- Handles relationships (user ownership, cloning, marketplace)
- Manages visibility and access control
- Provides scopes for filtering (user, public, default)

**2. PresetService**
- CRUD operations for presets
- Preset validation and sanitization
- Preset cloning and templating
- Default preset seeding

**3. PresetApplicatorService**
- Applies preset configurations to execution contexts
- Resolves preset hierarchy (default → user → connection)
- Merges preset settings with connection-specific overrides
- Validates preset compatibility with execution context

**4. PresetResolverService**
- Determines which preset to use for a given execution
- Handles preset selection logic (signal-based, connection-based, subscription-based)
- Resolves conflicts and priority rules

**5. Integration Points**
- **SignalExecutionService**: Enhanced to use preset configurations
- **CopyTradingService**: Applies presets to copied trades
- **TradingBotService**: Uses presets for bot configurations
- **ExecutionConnection**: Can reference a preset for default settings

## Technology Stack

### Backend
- **Framework**: Laravel 8+ (existing)
- **Database**: MySQL/MariaDB (existing)
- **Justification**: Consistent with existing codebase, leverages Laravel's Eloquent ORM and validation system
- **Alternatives Considered**: None - must align with existing stack

### Frontend
- **Technology**: Blade templates (existing)
- **Justification**: Consistent with existing admin and user interfaces
- **UI Framework**: Bootstrap (existing) or Tailwind if already in use
- **JavaScript**: Vanilla JS or Alpine.js for interactive components

### Database
- **Technology**: MySQL/MariaDB
- **Schema Changes**: 
  - New `trading_presets` table
  - New `execution_connection_preset` pivot table (optional, for direct assignment)
  - New `copy_trading_subscription_preset` pivot table (optional)
  - Migration to add `preset_id` to `execution_connections` (nullable)
  - Migration to add `preset_id` to `copy_trading_subscriptions` (nullable)

### Services/APIs
- **PresetService**: Core business logic for preset management
- **PresetApplicatorService**: Applies presets to execution contexts
- **PresetResolverService**: Resolves which preset to use
- **PresetValidationService**: Validates preset configurations
- **PresetSeederService**: Seeds default presets

## Database Schema

### trading_presets Table

```sql
CREATE TABLE trading_presets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Identity & Market
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    symbol VARCHAR(50) NULL COMMENT 'Logical symbol (e.g., XAUUSD)',
    timeframe VARCHAR(10) NULL COMMENT 'M1, M5, M15, H1, etc.',
    enabled BOOLEAN DEFAULT TRUE,
    tags JSON NULL COMMENT 'Array of tags: ["scalping", "xau", "layering"]',
    
    -- Position & Risk
    position_size_mode ENUM('FIXED', 'RISK_PERCENT') DEFAULT 'RISK_PERCENT',
    fixed_lot DECIMAL(10, 2) NULL,
    risk_per_trade_pct DECIMAL(5, 2) NULL COMMENT 'Percentage of equity',
    max_positions INT UNSIGNED DEFAULT 1,
    max_positions_per_symbol INT UNSIGNED DEFAULT 1,
    
    -- Dynamic Equity
    equity_dynamic_mode ENUM('NONE', 'LINEAR', 'STEP') DEFAULT 'NONE',
    equity_base DECIMAL(15, 2) NULL COMMENT 'Base equity amount',
    equity_step_factor DECIMAL(5, 2) NULL COMMENT 'Multiplier for step mode',
    risk_min_pct DECIMAL(5, 2) NULL,
    risk_max_pct DECIMAL(5, 2) NULL,
    
    -- Stop Loss
    sl_mode ENUM('PIPS', 'R_MULTIPLE', 'STRUCTURE') DEFAULT 'PIPS',
    sl_pips INT NULL,
    sl_r_multiple DECIMAL(5, 2) NULL COMMENT 'R multiple (e.g., 1.5R)',
    
    -- Take Profit
    tp_mode ENUM('DISABLED', 'SINGLE', 'MULTI') DEFAULT 'SINGLE',
    
    -- TP1
    tp1_enabled BOOLEAN DEFAULT TRUE,
    tp1_rr DECIMAL(5, 2) NULL COMMENT 'Risk:Reward ratio',
    tp1_close_pct DECIMAL(5, 2) NULL COMMENT 'Percentage to close at TP1',
    
    -- TP2
    tp2_enabled BOOLEAN DEFAULT FALSE,
    tp2_rr DECIMAL(5, 2) NULL,
    tp2_close_pct DECIMAL(5, 2) NULL,
    
    -- TP3
    tp3_enabled BOOLEAN DEFAULT FALSE,
    tp3_rr DECIMAL(5, 2) NULL,
    tp3_close_pct DECIMAL(5, 2) NULL,
    close_remaining_at_tp3 BOOLEAN DEFAULT FALSE,
    
    -- Break Even
    be_enabled BOOLEAN DEFAULT FALSE,
    be_trigger_rr DECIMAL(5, 2) NULL COMMENT 'Trigger BE when this RR is reached',
    be_offset_pips INT NULL COMMENT 'Offset from entry (can be negative)',
    
    -- Trailing Stop
    ts_enabled BOOLEAN DEFAULT FALSE,
    ts_mode ENUM('STEP_PIPS', 'STEP_ATR', 'CHANDELIER') DEFAULT 'STEP_PIPS',
    ts_trigger_rr DECIMAL(5, 2) NULL COMMENT 'Start trailing after this RR',
    ts_step_pips INT NULL,
    ts_atr_period INT NULL COMMENT 'For ATR mode',
    ts_atr_multiplier DECIMAL(5, 2) NULL,
    ts_update_interval_sec INT NULL COMMENT 'Update frequency',
    
    -- Layering / Grid
    layering_enabled BOOLEAN DEFAULT FALSE,
    max_layers_per_symbol INT UNSIGNED DEFAULT 3,
    layer_distance_pips INT NULL,
    layer_martingale_mode ENUM('NONE', 'MULTIPLY', 'ADD') DEFAULT 'NONE',
    layer_martingale_factor DECIMAL(5, 2) NULL,
    layer_max_total_risk_pct DECIMAL(5, 2) NULL,
    
    -- Hedging
    hedging_enabled BOOLEAN DEFAULT FALSE,
    hedge_trigger_drawdown_pct DECIMAL(5, 2) NULL,
    hedge_distance_pips INT NULL,
    hedge_lot_factor DECIMAL(5, 2) NULL COMMENT 'Multiplier for hedge lot size',
    
    -- Exit Per Candle
    auto_close_on_candle_close BOOLEAN DEFAULT FALSE,
    auto_close_timeframe VARCHAR(10) NULL COMMENT 'M5, M15, etc.',
    hold_max_candles INT NULL,
    
    -- Trading Schedule
    trading_hours_start TIME NULL COMMENT 'HH:MM format',
    trading_hours_end TIME NULL,
    trading_timezone VARCHAR(50) DEFAULT 'SERVER',
    trading_days_mask INT UNSIGNED DEFAULT 127 COMMENT 'Bitmask: 1=Mon, 2=Tue, 4=Wed, 8=Thu, 16=Fri, 32=Sat, 64=Sun',
    session_profile ENUM('ASIA', 'LONDON', 'NY', 'CUSTOM') DEFAULT 'CUSTOM',
    only_trade_in_session BOOLEAN DEFAULT FALSE,
    
    -- Weekly Target
    weekly_target_enabled BOOLEAN DEFAULT FALSE,
    weekly_target_profit_pct DECIMAL(5, 2) NULL,
    weekly_reset_day TINYINT UNSIGNED NULL COMMENT '1=Monday, 7=Sunday',
    auto_stop_on_weekly_target BOOLEAN DEFAULT FALSE,
    
    -- Meta
    created_by_user_id BIGINT UNSIGNED NULL,
    is_default_template BOOLEAN DEFAULT FALSE,
    clonable BOOLEAN DEFAULT TRUE,
    visibility ENUM('PRIVATE', 'PUBLIC_MARKETPLACE') DEFAULT 'PRIVATE',
    
    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    -- Indexes
    INDEX idx_user_id (created_by_user_id),
    INDEX idx_visibility (visibility),
    INDEX idx_enabled (enabled),
    INDEX idx_is_default (is_default_template),
    INDEX idx_symbol (symbol),
    INDEX idx_timeframe (timeframe),
    
    -- Foreign Keys
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Integration Tables

```sql
-- Link execution connections to presets
ALTER TABLE execution_connections 
ADD COLUMN preset_id BIGINT UNSIGNED NULL AFTER settings,
ADD INDEX idx_preset_id (preset_id),
ADD FOREIGN KEY (preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;

-- Link copy trading subscriptions to presets
ALTER TABLE copy_trading_subscriptions 
ADD COLUMN preset_id BIGINT UNSIGNED NULL,
ADD INDEX idx_preset_id (preset_id),
ADD FOREIGN KEY (preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;

-- Link trading bots to presets
ALTER TABLE trading_bots
ADD COLUMN preset_id BIGINT UNSIGNED NULL,
ADD INDEX idx_preset_id (preset_id),
ADD FOREIGN KEY (preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;

-- Add default preset to users table (for new user onboarding)
ALTER TABLE users
ADD COLUMN default_preset_id BIGINT UNSIGNED NULL,
ADD INDEX idx_default_preset_id (default_preset_id),
ADD FOREIGN KEY (default_preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;
```

### Multi-TP Support in ExecutionPosition

**Decision**: Multi-TP stored in ExecutionPosition, not Signal model (see Decisions section)

```sql
-- Add multi-TP fields to execution_positions table
ALTER TABLE execution_positions
ADD COLUMN tp1_price DECIMAL(20, 8) NULL AFTER tp_price,
ADD COLUMN tp2_price DECIMAL(20, 8) NULL,
ADD COLUMN tp3_price DECIMAL(20, 8) NULL,
ADD COLUMN tp1_close_pct DECIMAL(5, 2) NULL COMMENT 'Percentage to close at TP1 (0-100)',
ADD COLUMN tp2_close_pct DECIMAL(5, 2) NULL,
ADD COLUMN tp3_close_pct DECIMAL(5, 2) NULL,
ADD COLUMN tp1_closed_at TIMESTAMP NULL,
ADD COLUMN tp2_closed_at TIMESTAMP NULL,
ADD COLUMN tp3_closed_at TIMESTAMP NULL,
ADD COLUMN tp1_closed_qty DECIMAL(20, 8) NULL COMMENT 'Quantity closed at TP1',
ADD COLUMN tp2_closed_qty DECIMAL(20, 8) NULL,
ADD COLUMN tp3_closed_qty DECIMAL(20, 8) NULL;

-- Add index for querying open multi-TP positions
CREATE INDEX idx_multi_tp_open ON execution_positions(status, tp1_price, tp2_price, tp3_price) 
WHERE status = 'open' AND (tp1_price IS NOT NULL OR tp2_price IS NOT NULL OR tp3_price IS NOT NULL);
```

### Structure-Based SL Support

**Decision**: v1.0 uses manual structure price (see Decisions section)

```sql
-- Add structure SL price to signals table (optional, for structure-based SL)
ALTER TABLE signals
ADD COLUMN structure_sl_price DECIMAL(28, 8) NULL AFTER tp,
ADD INDEX idx_structure_sl (structure_sl_price);
```

## Implementation Approach

### Phase 1: Foundation & Database (Week 1)
**Tasks:**
- Create addon directory structure
- Create `addon.json` manifest
- Create `AddonServiceProvider`
- Create database migrations
- Create `TradingPreset` model with relationships
- Create model factories and seeders

**Deliverables:**
- Addon structure in place
- Database schema implemented
- Model with all relationships
- Basic CRUD operations working

**Timeline:** 3-5 days

### Phase 2: Core Services (Week 1-2)
**Tasks:**
- Implement `PresetService` (CRUD, validation)
- Implement `PresetValidationService` (comprehensive validation)
- Implement `PresetApplicatorService` (apply presets to contexts)
- Implement `PresetResolverService` (resolve preset selection)
- Create DTOs for preset data transfer
- Unit tests for services

**Deliverables:**
- All core services implemented
- Validation logic complete
- Preset application logic working
- Unit test coverage >80%

**Timeline:** 5-7 days

### Phase 3: Default Presets Seeding (Week 2)
**Tasks:**
- Design default preset templates:
  1. **Conservative Scalper** - Low risk, quick profits (set as system default)
  2. **Swing Trader** - Medium risk, multiple TPs
  3. **Aggressive Day Trader** - Higher risk, layering enabled
  4. **Safe Long-Term** - Very conservative, weekly targets
  5. **Grid Trading** - Layering with martingale
  6. **Breakout Trader** - Structure-based SL, trailing stop
- Create seeder with all default presets
- Set `is_default_template = true` for all seeded presets
- Set `visibility = 'PUBLIC_MARKETPLACE'` for default presets (users can clone)
- Test preset configurations
- Create user onboarding logic:
  - Set `users.default_preset_id` to Conservative Scalper for new users
  - Auto-assign preset to new connections: `connection.preset_id = user.default_preset_id`

**Deliverables:**
- 6+ default presets seeded
- All presets validated and tested
- Documentation for each preset
- User default preset assignment working
- New connection auto-assignment working

**Timeline:** 2-3 days

### Phase 4: Admin Interface (Week 2-3)
**Tasks:**
- Create admin routes
- Create `Backend\PresetController`
- Create admin views (list, create, edit, clone)
- Implement preset management UI
- Add admin menu items
- Implement preset preview/validation UI

**Deliverables:**
- Full admin CRUD interface
- Preset management working
- Admin can create/edit/clone presets
- Validation feedback in UI

**Timeline:** 4-5 days

### Phase 5: User Interface (Week 3)
**Tasks:**
- Create user routes
- Create `User\PresetController`
- Create user views (list, create, edit, clone, apply)
- Implement preset selection UI for connections
- Add user menu items
- Implement preset marketplace view (if visibility=public)

**Deliverables:**
- User can manage own presets
- User can select presets for connections
- User can clone public/default presets
- User-friendly preset configuration forms

**Timeline:** 4-5 days

### Phase 6: Integration with Execution Engine (Week 3-4)
**Tasks:**
- Enhance `SignalExecutionService` to use presets via `PresetResolverService`
- Modify `calculatePositionSize` to use preset settings (RISK_PERCENT, FIXED, dynamic equity)
- Implement multi-TP order placement:
  - Create multiple TP orders when `tp_mode = MULTI`
  - Store TP prices in `ExecutionPosition` (tp1_price, tp2_price, tp3_price)
  - Implement partial close logic (tp1_close_pct, tp2_close_pct, tp3_close_pct)
  - Track closed quantities per TP
- Implement break-even logic:
  - Monitor position P/L
  - Move SL to BE when `be_trigger_rr` is reached
  - Apply `be_offset_pips` offset
- Implement trailing stop logic:
  - STEP_PIPS mode: Update SL by fixed pips
  - STEP_ATR mode: Calculate ATR and update SL
  - CHANDELIER mode: Calculate Chandelier stop
  - Update at `ts_update_interval_sec` intervals
- Implement layering/grid logic:
  - Open additional positions at `layer_distance_pips`
  - Apply martingale (multiply or add) based on `layer_martingale_mode`
  - Enforce `max_layers_per_symbol` and `layer_max_total_risk_pct`
- Implement hedging logic:
  - Monitor drawdown
  - Open hedge position when `hedge_trigger_drawdown_pct` reached
  - Use `hedge_distance_pips` and `hedge_lot_factor`
- Implement trading schedule checks:
  - Validate `trading_hours_start` and `trading_hours_end`
  - Check `trading_days_mask` (bitmask for days of week)
  - Handle timezone conversion (`trading_timezone`)
  - Session profile validation (ASIA, LONDON, NY, CUSTOM)
- Implement weekly target tracking:
  - Track weekly P/L per connection (not per user)
  - Calculate from `weekly_reset_day` to next reset day
  - Block new trades when `weekly_target_profit_pct` reached (if `auto_stop_on_weekly_target = true`)
  - Reset tracking on `weekly_reset_day`
- Implement structure-based SL:
  - Check if `sl_mode = STRUCTURE`
  - Use `signal.structure_sl_price` or `signal.meta['structure_sl']`
  - Fallback to calculated SL if structure price not provided
- Update `ExecutionConnection` model to support preset assignment
- Create `WeeklyTargetTracker` service for tracking weekly P/L

**Deliverables:**
- Execution engine fully integrated with presets
- All preset features working in execution
- Position sizing respects preset settings (RISK_PERCENT, FIXED, dynamic equity)
- Multi-TP working with partial closes
- Break-even, trailing stop, layering, hedging working
- Trading schedule enforcement working
- Weekly target tracking and auto-stop working
- Structure-based SL working (manual price)

**Timeline:** 7-10 days

### Phase 7: Integration with Copy Trading (Week 4)
**Tasks:**
- Enhance `CopyTradingService` to use presets
- Modify `TradeCopyService` to apply preset settings
- Implement preset-based position sizing for copied trades
- Update copy trading subscription model
- Add preset selection to copy trading UI

**Deliverables:**
- Copy trading respects preset configurations
- Copied trades use preset settings
- UI updated for preset selection

**Timeline:** 3-4 days

### Phase 8: Integration with Trading Bots (Week 4-5)
**Tasks:**
- Enhance `TradingBotService` to use presets
- Add preset selection to bot configuration
- Implement preset-based bot execution
- Update bot UI for preset selection

**Deliverables:**
- Trading bots use preset configurations
- Bot execution respects preset settings

**Timeline:** 2-3 days

### Phase 9: Advanced Features (Week 5)
**Tasks:**
- Implement dynamic equity calculation
- Implement structure-based SL detection (if applicable)
- Implement ATR-based trailing stop
- Implement Chandelier trailing stop
- Implement candle-based exit logic
- Implement session-based trading restrictions
- Implement weekly target tracking and auto-stop

**Deliverables:**
- All advanced features working
- Comprehensive testing completed

**Timeline:** 5-7 days

### Phase 10: Testing & Documentation (Week 5-6)
**Tasks:**
- Integration tests for all execution paths
- E2E tests for preset application
- Performance testing
- Documentation updates
- User guide creation
- API documentation (if needed)

**Deliverables:**
- Comprehensive test coverage
- Documentation complete
- Ready for production

**Timeline:** 3-5 days

## Security Considerations

- **Input Validation**: All preset fields must be validated with strict rules
  - Risk percentages: 0.01% - 100% (with warnings for >10%)
  - Position sizes: Positive values only
  - Time values: Valid time formats
  - Enum values: Strict enum validation
  
- **Access Control**: 
  - Users can only edit/delete their own presets (unless admin)
  - Public presets are read-only for non-owners
  - Default presets are read-only for all users
  
- **Data Sanitization**:
  - All user inputs sanitized
  - SQL injection prevention (using Eloquent)
  - XSS prevention in UI
  
- **Preset Application Security**:
  - Validate preset compatibility before application
  - Prevent applying invalid presets to connections
  - Log all preset applications for audit

- **Financial Safety**:
  - Maximum risk limits enforced
  - Position size limits enforced
  - Weekly target limits enforced
  - Validation warnings for high-risk configurations

## Performance Requirements

- **Response Time**: 
  - Preset CRUD operations: <200ms
  - Preset application: <100ms
  - Preset resolution: <50ms
  
- **Throughput**:
  - Support 1000+ presets per user
  - Support 100+ concurrent preset applications
  
- **Scalability**:
  - Database indexes on frequently queried fields
  - Caching of default presets
  - Efficient preset resolution algorithm

## Testing Strategy

### Unit Tests
- **PresetService**: CRUD operations, validation, cloning
- **PresetValidationService**: All validation rules
- **PresetApplicatorService**: Preset application logic
- **PresetResolverService**: Preset resolution logic
- **Model Tests**: Relationships, scopes, accessors

### Integration Tests
- **Preset → Execution**: Test preset application to signal execution
- **Preset → Copy Trading**: Test preset application to copy trading
- **Preset → Trading Bot**: Test preset application to trading bots
- **Preset Hierarchy**: Test preset resolution priority

### E2E Tests
- **User Flow**: Create preset → Apply to connection → Execute signal → Verify settings
- **Admin Flow**: Create default preset → User clones → User applies → Verify
- **Marketplace Flow**: User creates public preset → Other user clones → Applies

### Performance Tests
- **Preset Resolution**: Test with 1000+ presets
- **Concurrent Applications**: Test 100+ simultaneous preset applications
- **Database Queries**: Ensure efficient queries with proper indexes

## Deployment

### Environment Setup
- Laravel 8+ required
- MySQL 5.7+ or MariaDB 10.3+
- PHP 7.4+ or 8.0+
- Queue system configured (for async preset applications if needed)

### Deployment Steps
1. Run database migrations
2. Seed default presets
3. Register addon service provider (if not auto-registered)
4. Clear cache: `php artisan config:clear`, `php artisan cache:clear`
5. Run tests to verify installation
6. Monitor logs for any errors

### Rollback Plan
- Database migrations are reversible
- Addon can be disabled via `addon.json` status
- Previous execution logic remains intact (preset integration is additive)

## Monitoring & Observability

- **Metrics to Track**:
  - Number of presets created per user
  - Most used presets
  - Preset application success/failure rates
  - Execution performance with presets vs without
  
- **Logging**:
  - All preset CRUD operations
  - Preset application events
  - Preset validation failures
  - Preset resolution decisions
  
- **Alerts**:
  - Preset validation failures
  - Preset application errors
  - High-risk preset configurations (optional warning)

## Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Preset conflicts with existing settings | High | Medium | Clear override hierarchy, validation before application |
| Performance degradation with many presets | Medium | Low | Proper indexing, caching, efficient queries |
| Invalid preset configurations causing losses | High | Low | Comprehensive validation, safety limits, warnings |
| Integration complexity with existing systems | High | Medium | Phased integration, extensive testing, backward compatibility |
| User confusion with preset options | Medium | Medium | Clear UI, tooltips, default presets, documentation |
| Database migration issues | Medium | Low | Tested migrations, rollback plan, backup before migration |

## Integration Points & Potential Conflicts

### Existing Systems Analysis

**1. SignalExecutionService**
- **Current**: Uses `connection->settings` for position sizing
- **Integration**: Preset settings will override/enhance connection settings
- **Conflict Risk**: Low - Preset can be merged with connection settings
- **Solution**: Preset settings take priority, connection settings as fallback

**2. ExecutionConnection Model**
- **Current**: Has `settings` JSON field
- **Integration**: Add `preset_id` field, preset settings merge with connection settings
- **Conflict Risk**: Low - Additive change
- **Solution**: When preset is assigned, merge preset settings with connection settings

**3. CopyTradingService**
- **Current**: Uses subscription `risk_multiplier` and connection settings
- **Integration**: Preset can be assigned to subscription or connection
- **Conflict Risk**: Medium - Need to resolve priority
- **Solution**: Subscription preset > Connection preset > Connection settings > Default

**4. Signal Model**
- **Current**: Has single `sl` and `tp` fields
- **Integration**: Preset supports multi-TP, but signal model has single TP
- **Conflict Risk**: High - Signal model limitation
- **Solution** (DECIDED): 
  - Store multi-TP in `ExecutionPosition` model (add `tp1_price`, `tp2_price`, `tp3_price`)
  - Signal model remains unchanged for backward compatibility (v1.0)
  - Future (v2.0+): Can add `tps_json` or `signal_take_profits` table if needed
  - Add optional `structure_sl_price` field to signals for structure-based SL

**5. TradingBotService**
- **Current**: Unknown implementation details
- **Integration**: Preset can be assigned to bot configuration
- **Conflict Risk**: Medium - Need to understand bot structure
- **Solution** (DECIDED):
  - Add `preset_id` to `trading_bots` table
  - Bot preset takes priority in resolver hierarchy
  - Resolver: Bot preset > Connection preset > Connection settings > Default preset
  - Investigate bot structure during implementation, then integrate

### Recommended Conflict Resolution Strategy

1. **Preset Priority Hierarchy** (DECIDED):
   ```
   Bot preset (if trading_bots.preset_id exists)
   > Subscription preset (copy_trading_subscriptions.preset_id)
   > Connection preset (execution_connections.preset_id)
   > Connection settings (legacy, for non-preset fields only)
   > User default preset (users.default_preset_id)
   > System default preset (is_default_template = true)
   ```
   Note: Per-signal preset not in v1.0, but hierarchy ready for future

2. **Backward Compatibility**:
   - All existing functionality remains unchanged
   - Presets are optional - connections work without presets
   - Existing `connection->settings` continue to work
   - Preset fields take priority, connection settings only for non-preset fields

3. **Multi-TP Handling** (DECIDED):
   - Store additional TPs in `ExecutionPosition` model
   - Add fields: `tp1_price`, `tp2_price`, `tp3_price`, `tp1_close_pct`, `tp2_close_pct`, `tp3_close_pct`
   - Add tracking: `tp1_closed_at`, `tp2_closed_at`, `tp3_closed_at`, `tp1_closed_qty`, `tp2_closed_qty`, `tp3_closed_qty`
   - Modify position service to handle partial closes
   - Signal model remains unchanged (single `tp` field)

4. **Preset Override Behavior** (DECIDED):
   - Preset fields take full priority over connection settings
   - Connection settings only override for fields NOT in preset (legacy/non-preset fields)
   - Clear UX: "If connection uses preset, follow preset"

5. **Migration Strategy**:
   - Phase 1: Add preset system (non-breaking)
   - Phase 2: Seed default presets, assign to new users
   - Phase 3: Migrate existing connection settings to presets (optional, user-initiated)
   - Phase 4: Enable preset features gradually

## Default Presets Design

### 1. Conservative Scalper
- **Target**: Beginners, low-risk traders
- **Settings**:
  - Position: RISK_PERCENT (0.5% per trade)
  - SL: 20 pips
  - TP: Single TP at 1.5R
  - Max positions: 1
  - No layering, no hedging
  - Trading hours: 08:00-18:00 (server time)
  - Weekly target: 2% profit, auto-stop enabled

### 2. Swing Trader
- **Target**: Medium-term traders
- **Settings**:
  - Position: RISK_PERCENT (1% per trade)
  - SL: Structure-based or 50 pips
  - TP: Multi-TP (TP1: 2R at 30%, TP2: 3R at 40%, TP3: 5R at 30%)
  - Break-even: Enabled at 1.5R
  - Trailing stop: Enabled (STEP_PIPS, 20 pips)
  - Max positions: 3
  - Trading hours: Full day

### 3. Aggressive Day Trader
- **Target**: Experienced traders, higher risk tolerance
- **Settings**:
  - Position: RISK_PERCENT (2% per trade)
  - SL: 30 pips
  - TP: Multi-TP (TP1: 1.5R at 50%, TP2: 2.5R at 30%, TP3: 4R at 20%)
  - Layering: Enabled (3 layers, 20 pips distance, multiply 1.5x)
  - Trailing stop: Enabled (STEP_ATR, 1.5x multiplier)
  - Max positions: 5
  - Weekly target: 5% profit

### 4. Safe Long-Term
- **Target**: Conservative, long-term traders
- **Settings**:
  - Position: RISK_PERCENT (0.25% per trade)
  - SL: 100 pips
  - TP: Single TP at 3R
  - Break-even: Enabled at 2R
  - Max positions: 1
  - Trading hours: London + NY sessions only
  - Weekly target: 1% profit, auto-stop enabled

### 5. Grid Trading
- **Target**: Grid/martingale traders
- **Settings**:
  - Position: FIXED (0.01 lot)
  - SL: 50 pips
  - TP: Single TP at 1R
  - Layering: Enabled (5 layers, 15 pips distance, multiply 2x)
  - Max total risk: 5%
  - Hedging: Enabled (trigger at -2% drawdown)
  - Weekly target: 3% profit

### 6. Breakout Trader
- **Target**: Breakout/volatility traders
- **Settings**:
  - Position: RISK_PERCENT (1.5% per trade)
  - SL: STRUCTURE (price structure-based)
  - TP: Multi-TP (TP1: 2R at 40%, TP2: 4R at 40%, TP3: 6R at 20%)
  - Trailing stop: CHANDELIER mode
  - Break-even: Enabled at 1R
  - Max positions: 2
  - Trading hours: High volatility sessions (London open, NY open)

## Future Considerations

- **Preset Marketplace**: Allow users to share and sell presets
- **Preset Analytics**: Track performance of presets
- **AI-Powered Preset Suggestions**: Suggest presets based on trading history
- **Preset Versioning**: Track preset changes over time
- **Preset Backtesting**: Backtest preset configurations
- **Preset Templates Library**: Expand default preset library
- **Mobile App Integration**: API for mobile app preset management
- **Preset Inheritance**: Child presets that inherit from parent presets
- **Conditional Presets**: Presets that change based on market conditions
- **Preset Groups**: Organize presets into groups/categories

## Technical Debt to Address

- **Signal Model Enhancement**: 
  - v1.0: Not needed (per-signal preset deferred)
  - v2.0+: Consider adding `preset_id` to signals table if use case emerges
- **Multi-TP in Signal Model**: 
  - v1.0: Multi-TP in ExecutionPosition only (decision made)
  - v2.0+: Consider `tps_json` or `signal_take_profits` table if end-to-end multi-TP needed
- **Structure-Based SL Auto-Detection**: 
  - v1.0: Manual structure price only
  - v2.0+: Create `MarketStructureService` for auto-detection (can be separate addon)
- **Preset Caching**: Implement caching for frequently accessed presets (performance optimization)
- **Preset Validation Rules**: May need to expand validation as new features are added
- **Weekly Target Per-User**: 
  - v1.0: Per connection/subscription only (decision made)
  - v2.0+: Consider global user-level weekly target if needed
- **Preset Marketplace Monetization**: 
  - v1.0: Basic sharing only (decision made)
  - v2.0+: Payment, revenue share, ratings, reviews
- **Documentation**: Keep documentation updated as features evolve

## Decisions & Design Choices

### 1. Signal Model Multi-TP

**Decision: v1.0 - Multi-TP in Execution Layer Only**

- **Signal Model**: Remains simple with single `sl` and `tp` fields (backward compatible)
- **Multi-TP Storage**: Stored in `ExecutionPosition` model with fields:
  - `tp1_price`, `tp2_price`, `tp3_price`
  - `tp1_close_pct`, `tp2_close_pct`, `tp3_close_pct`
  - `tp1_closed_at`, `tp2_closed_at`, `tp3_closed_at`
- **Rationale**: 
  - Maintains backward compatibility with existing signal flows
  - Many signal providers only send single TP
  - Preset intelligently translates single TP to multi-TP at execution layer
  - Defers schema changes to signal model until v2.0

**Future (v2.0+)**:
- Option 1: Add `tps_json` field to signals table (array of TPs)
- Option 2: Create `signal_take_profits` table (1-to-many relationship)
- Only implement if end-to-end multi-TP signal standard is needed

### 2. Preset Assignment Granularity

**Decision: v1.0 - Three Levels Only**

- **Connection Level**: `execution_connections.preset_id`
- **Copy Trading Subscription**: `copy_trading_subscriptions.preset_id`
- **Trading Bot**: `trading_bots.preset_id`

**Per-Signal Preset**: Not for v1.0
- Use case is more advanced (e.g., different presets for normal vs high-risk signals)
- Future-ready: Add `preset_id` nullable to signals table when needed
- Resolver hierarchy already prepared:
  ```
  Signal.preset_id > Subscription preset > Connection preset > Connection settings > Default preset
  ```

**Rationale**:
- Reduces complexity in UI and resolver for MVP
- Connection + Subscription + Bot levels provide sufficient power
- Per-signal preset can be added when real use case emerges

### 3. Preset Override Behavior

**Decision: Preset Takes Full Priority for Preset Fields**

**Merge Strategy**:
1. **Preset as Baseline**: Load full preset configuration as baseline
2. **Connection Settings Override**: Only for fields NOT covered by preset (legacy/non-preset fields)
3. **Preset Fields Win**: For fields that exist in both preset and connection settings, preset wins

**Rationale**:
- Clear user experience: "If connection uses preset, follow preset"
- Prevents confusion: User changes TP in preset, but old connection setting still applies
- Consistent with "Preset-Driven Configuration" concept

**Future Override Options**:
- Field-level override flag: `local_override: true` per field
- Clone preset specifically for connection with custom overrides

### 4. Default Preset Selection for New Users

**Decision: Combination Approach**

**On User Creation**:
- Seed default presets (Conservative Scalper, Swing Trader, etc.)
- Set safe default: `users.default_preset_id` (e.g., Conservative Scalper)

**On New Connection Creation**:
- Auto-fill: `connection.preset_id = user.default_preset_id`
- Show dropdown in UI to allow change

**Optional Onboarding Wizard**:
- Simple wizard: "Choose trading style: Super Safe / Swing / Aggressive"
- Behind the scenes: Sets `user.default_preset_id`

**Benefits**:
- New users can start immediately without configuration
- Educational moment: Users understand system uses presets
- Flexibility: Users can change preset per connection

### 5. Preset Marketplace Timeline

**Decision: v1.0 - Pre-wired Only, Full Marketplace in v2.0+**

**v1.0 Features (Basic Sharing)**:
- `visibility` field: `PRIVATE` | `PUBLIC_MARKETPLACE`
- `clonable` flag
- Query scope for public presets
- Users can:
  - View public presets (read-only)
  - Clone to their own account

**v2.0+ Features (Full Marketplace)**:
- Payment/revenue share
- Rating, review, ranking
- Curated listings
- Monetization features

**Rationale**:
- v1.0 = "Public Preset Library" (free basic sharing)
- v2.0+ = "Full Marketplace" (monetization, ratings, etc.)

### 6. Trading Bot Integration

**Decision: Bot as Execution Context (Same Pattern as Connection/Subscription)**

**Database Changes**:
```sql
ALTER TABLE trading_bots
  ADD COLUMN preset_id BIGINT UNSIGNED NULL,
  ADD INDEX idx_preset_id (preset_id),
  ADD FOREIGN KEY (preset_id) REFERENCES trading_presets(id) ON DELETE SET NULL;
```

**Service Integration**:
- `TradingBotService` uses `PresetResolverService` when opening positions
- Resolver call: `$preset = $presetResolver->resolveForBot($bot, $connection, $user);`

**Resolver Hierarchy (Bot Context)**:
```
Bot preset > Connection preset > Connection settings > Default preset (user/system)
```

**v1.0 Implementation**:
- Add `preset_id` to `trading_bots` table
- Add preset dropdown in bot edit form
- In bot execution logic: Use `PresetApplicatorService` for position sizing, SL, TP calculations

### 7. Structure-Based SL

**Decision: v1.0 - Manual Structure Price, Auto-Detection in v2.0+**

**v1.0 Behavior**:
- `sl_mode = STRUCTURE` means: Execution expects "structure price" to be provided
- Source options:
  - `signal.structure_sl_price` (new field, nullable)
  - Or via `signal.meta['structure_sl']` (metadata)
- Preset only specifies: "If mode = STRUCTURE, use this price, don't calculate fixed pips from entry"

**v2.0+ Auto-Detection (Future)**:
- Create `MarketStructureService`:
  - Pull OHLC from broker/price feed
  - Calculate swing high/low, last fractal, etc.
- Add preset field: `structure_mode` (LAST_SWING, SESSION_LOW, etc.)
- Can be separate addon, not required for v1.0

**Rationale**:
- Auto market structure detection is complex (support/resistance, swing high/low, FVG, etc.)
- v1.0 focuses on preset system, structure detection can be separate feature
- Manual structure price provides flexibility without complexity

### 8. Weekly Target Tracking

**Decision: v1.0 - Per Connection/Subscription Level**

**Enforcement Level (Hard Limit)**:
- Track and enforce at:
  - `execution_connection` level (when preset used on connection)
  - `copy_trading_subscription` level (when preset used on subscription)
- Logic: Calculate weekly P/L for that connection/subscription → if target reached, block new trades

**User Level (Analytics Only)**:
- Per-user weekly target: Analytics/dashboard only (total P/L across all connections)
- Not enforced as hard limit in v1.0
- Future: Can add `user.weekly_global_target_pct` for global cap (advanced feature)

**Rationale**:
- Each connection/subscription can have:
  - Different broker
  - Different leverage
  - Different risk profile
- Risk management: Safest to track weekly target per connection/subscription
- Per-user global cap is complex (multiple accounts, copy trading, bots) → defer to future

**Implementation**:
- Add weekly P/L tracking to `ExecutionConnection` or separate `WeeklyTargetTracker` service
- Check on each trade attempt: If weekly target reached → reject trade
- Reset on `weekly_reset_day` (1=Monday, 7=Sunday)

---

**Next Steps:**
1. ✅ All design decisions confirmed
2. Create specification document (if not exists)
3. Begin Phase 1 implementation
4. Update database schema based on decisions above

