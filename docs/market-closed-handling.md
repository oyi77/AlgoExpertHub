# Market Closed Error Handling - Implementation Summary

## Problem Statement

The trading system was experiencing market closed errors (`TRADE_RETCODE_MARKET_CLOSED`) that were:
1. Only logging to the main Laravel log, not trading bot logs
2. Not validating market data freshness before attempting trades
3. Occurring in production when market was closed or data was stale
4. Not providing actionable information to diagnose the root cause

## Root Cause

When market data (last candle timestamp) is far from the current timestamp, it indicates:
- Market is closed (e.g., weekends, holidays)
- Data stream is disconnected or stale
- Bot attempting to trade on outdated information

This should **never happen in production** - trades should only execute when fresh market data is available.

## Solution Implemented

### 1. Created MarketStatusChecker Service

**File**: `main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php`

**Features**:
- Validates market data freshness based on timeframe-specific thresholds
- Checks last candle age against maximum allowed age:
  - 1m chart: < 5 minutes old
  - 5m chart: < 15 minutes old
  - 15m chart: < 30 minutes old
  - 1h chart: < 2 hours old
  - 4h chart: < 8 hours old
  - 1d chart: < 24 hours old
- Provides detailed validation results with reason and status
- Skips validation in test mode (immediate execution)

**Key Methods**:
```php
// Check if market data is fresh
checkMarketDataFreshness($symbol, $timeframe, $accountId, $botId): array

// Validate if trade should proceed
validateTradeExecution($executionData, $accountId, $isTestMode): array
```

### 2. Updated ExecutionJob

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Changes**:

#### a) Added Bot-Specific Logging
- Logs now written to `storage/logs/trading-bot-{id}.log`
- Market closed errors visible in bot-specific log, not just Laravel log
- Makes debugging easier for individual bots

```php
protected function setupBotLogger(int $botId): void
{
    // Configure bot-specific log channel
    // Writes to storage/logs/trading-bot-{id}.log
}
```

#### b) Pre-Execution Market Validation
- Validates market data freshness **before** attempting trade
- Prevents unnecessary API calls to broker when market is closed
- Only runs in production (skipped in test mode)

```php
// In handle() method
if (!$isTestMode) {
    $marketChecker = app(MarketStatusChecker::class);
    $validation = $marketChecker->validateTradeExecution(...);
    
    if (!$validation['should_proceed']) {
        $this->logMarketClosedError($validation, $connection, $botId);
        return; // Abort execution
    }
}
```

#### c) Enhanced Market Closed Error Logging
- Separate log method for market closed/stale data errors
- Includes detailed diagnostic information:
  - Data age in minutes
  - Last candle timestamp
  - Maximum allowed age for timeframe
  - Actionable recommendations
- Creates execution log record for tracking

```php
protected function logMarketClosedError(array $validation, ExecutionConnection $connection, ?int $botId): void
{
    Log::error('ExecutionJob: Trade rejected - Market closed or data stale', [
        'reason' => $validation['reason'],
        'data_age_minutes' => $freshnessCheck['age_minutes'],
        'recommendation' => 'Wait for market to open or check data stream connection',
        // ... more diagnostic info
    ]);
    
    // Create failed execution log for tracking
    ExecutionLog::create([...]);
}
```

#### d) Improved Error Detection in executeTrade()
- Detects market closed errors specifically
- Provides context-aware error messages
- Differentiates between market closed vs other errors

```php
$isMarketClosedError = stripos($errorMessage, 'MARKET_CLOSED') !== false || 
                        stripos($errorMessage, 'market is closed') !== false;

if ($isMarketClosedError) {
    Log::error('ExecutionJob: Market closed error during execution', [
        'recommendation' => 'Market is closed. Trade will retry when market reopens.',
        'note' => 'This error indicates the broker rejected the trade because the market is currently closed.',
    ]);
}
```

### 3. Updated RiskManagementJob

**File**: `main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php`

**Changes**:
- Added `timeframe` to execution data (required for market validation)
- Added `test_mode` flag to execution data
- These fields used by MarketStatusChecker to determine validation strategy

```php
$executionData = [
    // ... existing fields
    'timeframe' => $this->marketData[0]['timeframe'] ?? '1h',
    'test_mode' => $isTestMode,
];
```

## Benefits

### 1. Proactive Error Prevention
- Validates market status **before** attempting trade
- Prevents API calls when market is known to be closed
- Reduces failed trade attempts

### 2. Better Diagnostics
- Logs to bot-specific log file (easier debugging)
- Detailed information about data staleness
- Actionable recommendations for resolution

### 3. Test Mode Support
- Test mode bypasses validation (immediate execution for testing)
- Production mode enforces strict validation
- Clear separation of concerns

### 4. Comprehensive Error Context
- Knows **why** trade was rejected (stale data vs market closed)
- Includes data age metrics
- Provides troubleshooting guidance

## Log Output Examples

### Before (Old Behavior)
```
[2025-12-13 14:11:14] local.ERROR: ExecutionJob: Trade execution failed 
{"connection_id":6,"bot_id":1,"symbol":"XAUUSDc","direction":"buy",
"error":"Market is closed (Code: TRADE_RETCODE_MARKET_CLOSED)","result":{"success":false}}
```
- Generic error
- No context on WHY market is closed
- No actionable information
- Only in Laravel log (not bot log)

### After (New Behavior)

**In Bot Log** (`storage/logs/trading-bot-1.log`):
```
[2025-12-13 14:11:14] ERROR: ExecutionJob: Trade rejected - Market closed or data stale 
{
    "bot_id":1,
    "connection_id":6,
    "symbol":"XAUUSDc",
    "direction":"buy",
    "reason":"Market data is too old (185.3 minutes old, max 120 minutes) - market likely closed",
    "market_status":"stale",
    "data_age_minutes":185.3,
    "max_age_minutes":120,
    "last_candle_timestamp":1702476120000,
    "recommendation":"Wait for market to open or check data stream connection"
}
```

**If error still occurs** (bypassed validation or race condition):
```
[2025-12-13 14:11:14] ERROR: ExecutionJob: Market closed error during execution 
{
    "bot_id":1,
    "symbol":"XAUUSDc",
    "direction":"buy",
    "error":"Market is closed (Code: TRADE_RETCODE_MARKET_CLOSED)",
    "recommendation":"Market is closed. Trade will retry when market reopens.",
    "note":"This error indicates the broker rejected the trade because the market is currently closed."
}
```

## Testing Strategy

### Test Mode
```php
// Bot in test mode - validation skipped
$isTestMode = true;
$validation = $marketChecker->validateTradeExecution($executionData, $accountId, $isTestMode);
// Returns: ['should_proceed' => true, 'reason' => 'Test mode - validation skipped']
```

### Production Mode
```php
// Bot in production - validation enforced
$isTestMode = false;
$validation = $marketChecker->validateTradeExecution($executionData, $accountId, $isTestMode);
// Checks data freshness, returns detailed validation result
```

## Configuration

Maximum data age thresholds can be adjusted in `MarketStatusChecker`:

```php
protected const MAX_DATA_AGE_MINUTES = [
    '1m' => 5,      // Adjust as needed
    '5m' => 15,
    '15m' => 30,
    '1h' => 120,
    '4h' => 480,
    '1d' => 1440,
];
```

## Integration Points

### Automatic Integration
- No changes needed to existing bot configurations
- Works automatically for all MARKET_STREAM_BASED bots
- Validates using Redis-cached market data from MetaAPI streams

### Manual Integration (for custom execution flows)
```php
use Addons\TradingManagement\Modules\Execution\Services\MarketStatusChecker;

$checker = app(MarketStatusChecker::class);
$validation = $checker->validateTradeExecution($executionData, $accountId, $isTestMode);

if (!$validation['should_proceed']) {
    // Handle rejection
    Log::error($validation['reason']);
    return;
}

// Proceed with execution
```

## Future Improvements

1. **Market Hours Awareness**: Add exchange-specific market hours database
2. **Dynamic Thresholds**: Adjust thresholds based on market volatility
3. **Auto-Recovery**: Automatically retry when data becomes fresh
4. **Alert System**: Notify admins when data streams are stale
5. **Dashboard Widget**: Display market status in bot dashboard

## Files Changed

1. **New**:
   - `main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php`

2. **Modified**:
   - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`
   - `main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php`

## Rollback Instructions

If issues arise, remove or disable validation:

```php
// In ExecutionJob::handle(), comment out validation section
// if (!$isTestMode) {
//     $marketChecker = app(MarketStatusChecker::class);
//     $validation = $marketChecker->validateTradeExecution(...);
//     
//     if (!$validation['should_proceed']) {
//         $this->logMarketClosedError($validation, $connection, $botId);
//         return;
//     }
// }
```

Or temporarily bypass by forcing test mode in execution data.

## Conclusion

This implementation provides:
- ✅ **Proactive validation** - Prevents trades when market is closed
- ✅ **Better logging** - Bot-specific logs with detailed context
- ✅ **Actionable errors** - Clear reasons and recommendations
- ✅ **Test mode support** - Bypass validation for testing
- ✅ **Production safety** - Strict validation in production

The system now **gracefully handles** market closed conditions and provides **comprehensive diagnostics** for troubleshooting.

