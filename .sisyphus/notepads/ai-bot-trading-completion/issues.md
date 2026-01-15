# Issues - AI Bot Trading Completion

## Blockers

### None Currently

## Known Issues

### Paper Trading Flag Inconsistency
- **Severity**: HIGH
- **Description**: Code uses `test_mode` in 7 files, but TradingBot model has `is_paper_trading` field
- **Impact**: Paper trading logic is inconsistent, may cause unexpected behavior
- **Status**: To be fixed in Tasks 4, 5, 8
- **Files Affected**:
  - FilterAnalysisJob.php:50
  - ExecutionJob.php:80
  - RiskManagementJob.php:137,151,156
  - TradeDecisionEngine.php:34,45
  - TechnicalAnalysisService.php:201
  - MarketStatusChecker.php:141
  - TestFilterStrategySeeder.php:29

### AI Routing Using Deprecated Addon
- **Severity**: HIGH
- **Description**: BotSignalObserver and FilterAnalysisJob may reference deprecated `AiTradingAddon`
- **Impact**: AI routing may fail or use outdated implementation
- **Status**: To be fixed in Tasks 4, 5
- **Expected Fix**: Use `ai-connection-addon` (`AiConnectionService`)

### Circuit Breaker Missing Failure Tracking
- **Severity**: MEDIUM
- **Description**: ExecutionConnection has `circuit_breaker_enabled` and `max_consecutive_failures` but no failure tracking columns
- **Impact**: Circuit breaker cannot enforce consecutive failure limit
- **Status**: To be fixed in Task 6 (migration M3)
- **Required**: `consecutive_failures` (int) and `last_failure_at` (datetime) columns

### InternalTrade Missing Paper Trading Flag
- **Severity**: MEDIUM
- **Description**: `internal_trades` table doesn't have `is_paper` column to track paper vs live trades
- **Impact**: Cannot distinguish paper trades from live trades in reporting
- **Status**: To be fixed in Task 8 (migration M1)
- **Required**: `is_paper` (boolean, default false) column

### ExecutionLogs Missing AI Decision Link
- **Severity**: MEDIUM
- **Description**: `execution_logs` table doesn't link to AI decisions
- **Impact**: No audit trail linking trades to AI decision process
- **Status**: To be fixed in Task 4, 5 (migration M2)
- **Required**: `ai_decision_id` (nullable FK) column

## Technical Debt

### Market Data Provider Inconsistency
- **Description**: Landing page data uses mixed sources - real crypto (CoinGecko) but simulated for other assets
- **Impact**: Landing page may show misleading pricing data
- **Status**: To be fixed in Tasks 1-2 with Twelve Data integration

### Position Sizing Using Fixed Balance
- **Description**: TradeDecisionEngine uses `balance = 1000` placeholder
- **Impact**: Position sizing doesn't reflect actual account balance
- **Status**: To be fixed in Task 9

## External Dependencies

### Twelve Data API Key Required
- **Dependency**: TWELVE_DATA_API_KEY in .env
- **Impact**: Without key, market data will fall back to simulated
- **Action**: User must obtain API key from twelvedata.com

## Performance Concerns

### Cache Strategy for Market Data
- **Description**: Need to implement 15-minute cache to prevent rate limit exhaustion
- **Impact**: Without caching, may exceed 800 credits/day free tier
- **Status**: To be implemented in Tasks 1-3

## Security Considerations

### Gateway Credentials Encryption
- **Status**: Already implemented (per project conventions)
- **Note**: Verify all gateway credentials use Laravel's `encrypt()`/`decrypt()`

