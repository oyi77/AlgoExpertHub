# Risk Management Overhaul - Documentation

**Version**: 2.1.0  
**Date**: December 2025  
**Status**: Implemented

---

## Overview

This document describes the comprehensive risk management overhaul implemented in December 2025. The overhaul introduces advanced risk management features including accurate pip value calculations, margin management, slippage protection, correlation risk management, position limits, enhanced backtesting, performance metrics, and execution safeguards.

---

## Table of Contents

1. [Summary of Changes](#summary-of-changes)
2. [New Services](#new-services)
3. [Database Changes](#database-changes)
4. [Migration Guide](#migration-guide)
5. [Configuration](#configuration)
6. [Usage Examples](#usage-examples)
7. [Breaking Changes](#breaking-changes)
8. [Testing](#testing)

---

## Summary of Changes

### Phase 1: Core Risk Calculation
- **SymbolSpecService**: Centralized service for accurate pip value, contract size, and pip size calculations across different market types (Forex, Crypto, Commodities, Stocks/Indices)
- **Fixed Risk Calculation**: Fixed lot mode now correctly calculates actual risk amount and percentage
- **Accurate Pip Values**: Replaced hardcoded pip values with symbol-specific calculations

### Phase 2: Leverage & Margin Management
- **MarginManagementService**: Comprehensive margin management including required margin calculation, margin level checks, margin call detection, and liquidation protection
- **Leverage Support**: Added leverage configuration to execution connections
- **Margin Validation**: Margin checks integrated into risk calculators

### Phase 3: Slippage Protection
- **SlippageProtectionService**: Service for calculating, validating, and predicting slippage
- **Execution Price Tracking**: Stores actual execution price from exchanges (may differ from expected price)
- **SL/TP Slippage**: Stop-loss and take-profit execution uses actual execution price with slippage adjustment

### Phase 4: Correlation Risk Management
- **CorrelationRiskService**: Manages correlation risk across positions using a correlation matrix
- **Exposure Calculation**: Calculates total exposure to correlated symbols
- **Trade Prevention**: Prevents trades that would exceed correlation exposure limits

### Phase 5: Position Limits
- **PositionLimitService**: Enforces maximum open positions per connection and per symbol
- **Configurable Limits**: Limits configurable per execution connection

### Phase 6: Enhanced Backtesting
- **BacktestSlippageModel**: Realistic slippage and spread cost modeling in backtests
- **Execution Price Modeling**: Models actual execution prices instead of using trigger prices
- **Configurable Costs**: Slippage and spread costs can be configured per backtest

### Phase 7: Enhanced Performance Metrics
- **EnhancedMetricsService**: Calculates advanced performance metrics:
  - Expectancy
  - Sortino Ratio
  - Maximum Adverse Excursion (MAE)
  - Maximum Favorable Excursion (MFE)
  - Recovery Factor
  - Calmar Ratio

### Phase 8: Execution Safeguards
- **Circuit Breaker**: Prevents cascading failures by halting trading after consecutive failures
- **Market Hours Validation**: Validates market hours and holidays before trade execution

---

## New Services

### SymbolSpecService
**Location**: `Modules/RiskManagement/Services/SymbolSpecService.php`

Centralizes symbol specification calculations:
- Pip size calculation (0.0001 for standard FX, 0.01 for JPY pairs, etc.)
- Contract size retrieval (100,000 for FX, varies for crypto)
- Pip value calculation (account currency-aware)

**Key Methods**:
- `getPipSize(string $symbol, ?string $accountCurrency = null): float`
- `getContractSize(string $symbol, ?string $exchange = null): float`
- `getPipValue(string $symbol, float $lotSize, string $accountCurrency, float $entryPrice): float`
- `getSymbolSpec(string $symbol, ?string $exchange = null, string $accountCurrency = 'USD'): array`

### MarginManagementService
**Location**: `Modules/RiskManagement/Services/MarginManagementService.php`

Comprehensive margin management:
- Required margin calculation (accounting for leverage)
- Margin level checks
- Margin call detection
- Liquidation price calculation
- Trade prevention based on margin requirements

**Key Methods**:
- `calculateRequiredMargin(float $lotSize, float $entryPrice, int $leverage, string $symbol, ?float $contractSize = null): float`
- `checkMarginLevel(array $accountInfo): array`
- `shouldTriggerMarginCall(array $accountInfo, float $threshold = 100.0): bool`
- `shouldPreventTrade(array $accountInfo, float $requiredMargin, array $config = []): array`
- `calculateLiquidationPrice(array $position, array $accountInfo, int $leverage): ?float`

### SlippageProtectionService
**Location**: `Modules/RiskManagement/Services/SlippageProtectionService.php`

Slippage calculation and validation:
- Actual slippage calculation (expected vs executed price)
- Slippage validation (against max allowed)
- Slippage prediction (for market orders)
- Stop-loss adjustment (to account for slippage)

**Key Methods**:
- `calculateSlippage(float $expectedPrice, float $executedPrice, string $direction, string $symbol): float`
- `validateSlippage(float $slippagePips, ?float $maxAllowedSlippage = null): array`
- `predictSlippage(string $symbol, float $lotSize, ?float $volatility = null, ?float $spread = null): float`
- `adjustStopLossForSlippage(float $slPrice, float $slippagePips, string $direction, string $symbol): float`

### CorrelationRiskService
**Location**: `Modules/RiskManagement/Services/CorrelationRiskService.php`

Correlation risk management:
- Correlation matrix for major currency pairs
- Correlated symbol identification
- Exposure calculation
- Trade prevention based on correlation limits

**Key Methods**:
- `getCorrelationMatrix(): array`
- `getCorrelatedSymbols(string $symbol, float $threshold = null): array`
- `calculateExposure(string $symbol, array $existingPositions, float $newPositionValue, float $equity): array`
- `shouldPreventTrade(string $newSymbol, array $existingPositions, float $newPositionValue, float $equity, float $maxCorrelationExposurePct = 50.0): array`

### PositionLimitService
**Location**: `Modules/RiskManagement/Services/PositionLimitService.php`

Position limit enforcement:
- Open positions count (per connection, per symbol)
- Limit checking
- Trade prevention based on limits

**Key Methods**:
- `checkPositionLimit(ExecutionConnection $connection, ?string $symbol = null): array`
- `getOpenPositionsCount(ExecutionConnection $connection, ?string $symbol = null): int`
- `shouldPreventTrade(ExecutionConnection $connection, string $symbol): array`

### BacktestSlippageModel
**Location**: `app/Services/Backtesting/BacktestSlippageModel.php`

Realistic backtesting:
- Slippage calculation and application
- Spread cost calculation
- Execution price modeling

**Key Methods**:
- `calculateSlippage(string $symbol, float $lotSize, ?float $volatility = null, ?float $spread = null): float`
- `applySlippage(float $price, string $direction, float $slippagePips, string $symbol): float`
- `calculateSpreadCost(string $symbol, float $lotSize, ?float $spreadPips = null): float`

### EnhancedMetricsService
**Location**: `Modules/PositionMonitoring/Services/EnhancedMetricsService.php`

Advanced performance metrics:
- Expectancy calculation
- Sortino ratio (downside deviation)
- Maximum Adverse/Favorable Excursion
- Recovery and Calmar ratios

**Key Methods**:
- `calculateExpectancy(array $trades): float`
- `calculateSortinoRatio(array $returns, float $riskFreeRate = 0.0): float`
- `calculateMAE(array $trades): float`
- `calculateMFE(array $trades): float`
- `calculateRecoveryFactor(float $netProfit, float $maxDrawdown): float`
- `calculateCalmarRatio(float $annualReturn, float $maxDrawdown): float`

### MarketHoursService
**Location**: `Modules/Execution/Services/MarketHoursService.php`

Market hours validation:
- Market open/closed status
- Market hours retrieval
- Holiday detection
- Next trading day calculation

**Key Methods**:
- `isMarketOpen(string $symbol, ?string $timezone = null): array`
- `getMarketHours(string $symbol): array`
- `isHoliday(Carbon $date, string $market): bool`
- `getNextTradingDay(Carbon $date, string $market): Carbon`

---

## Database Changes

### New Migrations

All migrations are located in `database/migrations/` and dated `2025_12_29_*`:

1. **2025_12_29_100000_add_margin_fields_to_execution_connections_table.php**
   - Adds: `leverage`, `margin_call_threshold`, `liquidation_threshold`, `max_margin_usage_pct`, `max_open_positions`, `max_positions_per_symbol`, `circuit_breaker_enabled`, `max_consecutive_failures`

2. **2025_12_29_100001_add_slippage_fields_to_execution_positions_table.php**
   - Adds: `slippage_pips`, `execution_price`

3. **2025_12_29_100002_add_correlation_fields_to_trading_presets_table.php**
   - Adds: `max_correlation_exposure_pct`, `correlation_threshold`

4. **2025_12_29_100003_add_backtest_configuration_fields_to_backtests_table.php**
   - Adds: `slippage_model`, `slippage_pips`, `spread_cost_enabled`, `partial_fills_enabled`

5. **2025_12_29_100004_add_enhanced_metrics_to_execution_analytics_table.php**
   - Adds: `expectancy`, `sortino_ratio`, `mae`, `mfe`, `recovery_factor`, `calmar_ratio`

6. **2025_12_29_100005_add_execution_price_and_slippage_to_execution_logs_table.php**
   - Adds: `execution_price`, `slippage_pips`

### Model Updates

The following models have been updated with new fillable fields and casts:

- `ExecutionConnection`: Added margin and circuit breaker fields
- `ExecutionPosition`: Added slippage fields
- `ExecutionLog`: Added execution price and slippage fields
- `TradingPreset`: Added correlation fields
- `Backtest`: Added backtest configuration fields
- `ExecutionAnalytic`: Added enhanced metrics fields

---

## Migration Guide

### Step 1: Backup Database

```bash
# Backup your database before running migrations
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Run Migrations

```bash
cd main
php artisan migrate
```

This will run all 6 new migrations in order.

### Step 3: Update Configuration (Optional)

#### Execution Connections

For existing execution connections, you may want to set default values:

```php
// Example: Update all connections with default leverage
DB::table('execution_connections')->update([
    'leverage' => 100,
    'margin_call_threshold' => 100.0,
    'liquidation_threshold' => 50.0,
    'max_margin_usage_pct' => 80.0,
    'max_open_positions' => 10,
    'max_positions_per_symbol' => 3,
    'circuit_breaker_enabled' => true,
    'max_consecutive_failures' => 5,
]);
```

#### Trading Presets

For existing trading presets, set correlation defaults:

```php
// Example: Update all presets with default correlation limits
DB::table('trading_presets')->update([
    'max_correlation_exposure_pct' => 50.0,
    'correlation_threshold' => 0.7,
]);
```

### Step 4: Verify Migration

```bash
# Check migration status
php artisan migrate:status

# Verify tables have new columns
php artisan tinker
>>> Schema::hasColumn('execution_connections', 'leverage')
>>> Schema::hasColumn('execution_positions', 'slippage_pips')
```

---

## Configuration

### Execution Connection Configuration

**Leverage**: Default leverage for trades on this connection
```php
$connection->leverage = 100; // 1:100 leverage
```

**Margin Thresholds**: Margin call and liquidation thresholds
```php
$connection->margin_call_threshold = 100.0; // Margin call at 100% margin level
$connection->liquidation_threshold = 50.0; // Liquidation at 50% margin level
$connection->max_margin_usage_pct = 80.0; // Prevent trades if margin usage > 80%
```

**Position Limits**: Maximum open positions
```php
$connection->max_open_positions = 10; // Max 10 total open positions
$connection->max_positions_per_symbol = 3; // Max 3 positions per symbol
```

**Circuit Breaker**: Failure protection
```php
$connection->circuit_breaker_enabled = true;
$connection->max_consecutive_failures = 5; // Halt after 5 consecutive failures
```

### Trading Preset Configuration

**Correlation Limits**: Correlation risk management
```php
$preset->max_correlation_exposure_pct = 50.0; // Max 50% exposure to correlated pairs
$preset->correlation_threshold = 0.7; // Consider pairs with correlation > 0.7
```

### Backtest Configuration

**Slippage Model**: Slippage modeling type
```php
$backtest->slippage_model = 'fixed'; // Options: 'none', 'fixed', 'dynamic'
$backtest->slippage_pips = 2.0; // Fixed slippage in pips (for 'fixed' model)
$backtest->spread_cost_enabled = true; // Enable spread cost modeling
$backtest->partial_fills_enabled = false; // Enable partial fills (future feature)
```

---

## Usage Examples

### Using SymbolSpecService

```php
use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;

$service = app(SymbolSpecService::class);

// Get pip value for EUR/USD
$pipValue = $service->getPipValue('EURUSD', 1.0, 'USD', 1.1000);
// Returns: 10.0 (standard lot)

// Get contract size
$contractSize = $service->getContractSize('EURUSD');
// Returns: 100000

// Get complete symbol spec
$spec = $service->getSymbolSpec('EURUSD', null, 'USD');
// Returns: ['pip_size' => 0.0001, 'contract_size' => 100000, 'pip_value' => 10.0, ...]
```

### Using MarginManagementService

```php
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;

$service = app(MarginManagementService::class);

// Calculate required margin
$requiredMargin = $service->calculateRequiredMargin(
    1.0, // 1.0 lot
    1.1000, // Entry price
    100, // 1:100 leverage
    'EURUSD'
);
// Returns: 1100.0 (1 lot * 1.1000 * 100000 / 100)

// Check if trade should be prevented
$check = $service->shouldPreventTrade($accountInfo, $requiredMargin, [
    'max_margin_usage_pct' => 80.0,
]);
// Returns: ['should_prevent' => bool, 'reason' => string|null]
```

### Using SlippageProtectionService

```php
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;

$service = app(SlippageProtectionService::class);

// Calculate slippage
$slippagePips = $service->calculateSlippage(
    1.1000, // Expected price
    1.1002, // Executed price
    'buy',
    'EURUSD'
);
// Returns: 2.0 pips

// Validate slippage
$validation = $service->validateSlippage($slippagePips, 5.0); // Max 5 pips
// Returns: ['acceptable' => bool, 'reason' => string|null]
```

### Using CorrelationRiskService

```php
use Addons\TradingManagement\Modules\RiskManagement\Services\CorrelationRiskService;

$service = app(CorrelationRiskService::class);

// Get correlated symbols
$correlated = $service->getCorrelatedSymbols('EURUSD', 0.7);
// Returns: ['GBPUSD', 'AUDUSD', ...] (pairs with correlation > 0.7)

// Check if trade should be prevented
$check = $service->shouldPreventTrade(
    'GBPUSD', // New symbol
    $existingPositions, // Array of existing positions
    5000.0, // New position value
    10000.0, // Account equity
    50.0 // Max correlation exposure %
);
// Returns: ['should_prevent' => bool, 'reason' => string|null, 'current_exposure_pct' => float]
```

---

## Breaking Changes

### 1. Pip Value Calculation

**Before**: Hardcoded pip values (typically $10 per pip for 1.0 lot)

**After**: Symbol-specific pip value calculation using `SymbolSpecService`

**Impact**: Position sizes may change slightly for non-standard FX pairs (JPY pairs, crypto, etc.)

**Migration**: None required - calculations are automatic. However, you may want to review position sizes for accuracy.

### 2. Fixed Lot Mode Risk Calculation

**Before**: Fixed lot mode didn't calculate actual risk amount

**After**: Fixed lot mode now calculates and reports actual risk amount and percentage

**Impact**: Risk reporting will now be accurate for fixed lot mode

**Migration**: None required - calculation is automatic.

### 3. Execution Price Tracking

**Before**: Only `entry_price` stored (expected price)

**After**: Both `entry_price` (expected) and `execution_price` (actual) stored, plus `slippage_pips`

**Impact**: Position records now include actual execution price and slippage

**Migration**: Historical positions will have NULL for `execution_price` and `slippage_pips` - this is expected.

### 4. Margin Validation

**Before**: No margin validation before trade execution

**After**: Margin checks performed in risk calculators before trade execution

**Impact**: Trades may be rejected if insufficient margin

**Migration**: Configure leverage and margin thresholds on execution connections.

### 5. Correlation Risk Checks

**Before**: No correlation risk management

**After**: Correlation checks performed before trade execution

**Impact**: Trades may be rejected if correlation exposure limits exceeded

**Migration**: Configure `max_correlation_exposure_pct` and `correlation_threshold` on trading presets.

---

## Testing

### Unit Tests

Run unit tests for new services:

```bash
php artisan test --filter SymbolSpecServiceTest
php artisan test --filter MarginManagementServiceTest
php artisan test --filter SlippageProtectionServiceTest
php artisan test --filter CorrelationRiskServiceTest
php artisan test --filter PositionLimitServiceTest
php artisan test --filter EnhancedMetricsServiceTest
```

### Integration Tests

Run integration tests for execution flow:

```bash
php artisan test --filter RiskManagementJobTest
php artisan test --filter ExecutionServiceTest
php artisan test --filter ExecutionJobTest
```

### Manual Testing Checklist

- [ ] Verify pip value calculations for different symbol types (FX, Crypto, Commodities)
- [ ] Test margin calculations with different leverage values
- [ ] Verify slippage tracking on order execution
- [ ] Test correlation risk checks with correlated pairs
- [ ] Verify position limits are enforced
- [ ] Test circuit breaker halts trading after failures
- [ ] Verify market hours validation prevents trades outside market hours
- [ ] Test backtesting with slippage and spread costs enabled
- [ ] Verify enhanced metrics are calculated correctly

---

## Support

For issues or questions:
1. Check this documentation
2. Review service code comments
3. Check migration files for field descriptions
4. Review test files for usage examples

---

**Last Updated**: December 29, 2025

