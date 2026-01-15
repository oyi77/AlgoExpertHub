# Accumulated Wisdom - AI Bot Trading Completion

## Project Facts

### Database Schema
- Tables exist: `internal_trades`, `execution_logs`, `execution_connections`
- AI decisions table: `ai_decisions` (already exists)
- Required migrations M1, M2, M3 must be created first

### Architecture Patterns
- Service layer pattern: All business logic in Services
- Job-based execution: TradingBotStrategyWorker, FilterAnalysisJob, ExecutionJob
- AI routing: Uses `ai-connection-addon` (AiConnectionService)
- Market data: Two systems - Landing Page (MarketDataService) and Trading Pipeline (MarketDataService in addon)

### Current State
- Landing page market data: Crypto real (CoinGecko), FX/Indices/Commodities/Stocks simulated
- AI routing: Mixed - some using `ai-connection-addon`, some deprecated `AiTradingAddon`
- Paper trading: Flag is `is_paper_trading` on TradingBot model, but code uses `test_mode` in 7 files
- Circuit breaker: Partially implemented - has `circuit_breaker_enabled` and `max_consecutive_failures` but no failure tracking

## Conventions Discovered

### File Locations
- Main app: `main/app/`
- Trading addon: `main/addons/trading-management-addon/`
- AI addon: `main/addons/ai-connection-addon/`
- Multi-channel addon: `main/addons/multi-channel-signal-addon/`

### Namespace Conventions
- Core: `App\Models\`, `App\Services\`, `App\Jobs\`
- Trading addon: `Addons\TradingManagement\Modules\{Module}\Models\`
- AI addon: `Addons\AiConnectionAddon\App\Services\`

## Successful Approaches

## Failed Approaches to Avoid

## Technical Gotchas

### Paper Trading Flag Normalization
- Current issue: `test_mode` used in 7 files, should be `is_paper_trading`
- Affected files:
  1. FilterAnalysisJob.php:50
  2. ExecutionJob.php:80
  3. RiskManagementJob.php:137,151,156
  4. TradeDecisionEngine.php:34,45
  5. TechnicalAnalysisService.php:201
  6. MarketStatusChecker.php:141 (output only)
  7. TestFilterStrategySeeder.php:29

### Twelve Data Integration
- Free tier: 800 credits/day
- Rate limit strategy: 15-minute cache (900 seconds TTL)
- Background refresh: Laravel Scheduler every 15 minutes
- API endpoints: fx_pair for FX, quote for stocks/indices/commodities

### Circuit Breaker Enhancement
- Needs: `consecutive_failures` (int, default 0) and `last_failure_at` (datetime, nullable)
- Cooldown: 15 minutes (hardcoded)
- Reset condition: Successful execution resets counter to 0

## Correct Commands

### Docker Commands
All PHP commands MUST use: `docker exec 1Panel-php8-mrTy php ...`

### Migration Verification
```bash
# Verify M1
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'is_paper column: ' . (Schema::hasColumn('internal_trades', 'is_paper') ? 'EXISTS' : 'MISSING');"

# Verify M2
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'ai_decision_id column: ' . (Schema::hasColumn('execution_logs', 'ai_decision_id') ? 'EXISTS' : 'MISSING');"

# Verify M3
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'consecutive_failures: ' . (Schema::hasColumn('execution_connections', 'consecutive_failures') ? 'EXISTS' : 'MISSING');"
```

## Key Decisions Made

### Twelve Data Provider Choice
- Chosen over other APIs due to:
  - 800 credits/day free tier (not 100/day)
  - Comprehensive coverage (FX, stocks, indices, commodities, crypto)
  - Websocket support for real-time data
  - Reasonable rate limits for landing page use case

### Fail-Open AI Behavior
- When AI unavailable:
  - Create AiDecision with action='HOLD', confidence=0
  - Trading pipeline continues as if AI said "no opinion"
  - Downstream filters/risk checks still apply
  - Always create ai_decisions record for audit

## Unresolved Questions

## Problems

