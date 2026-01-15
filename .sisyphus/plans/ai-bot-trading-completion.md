# AI-Powered Automated Bot Trading Platform - Completion Plan

## Context

### Original Request
Evolve AlgoExpertHub into a production-grade automated bot trading platform powered with AI.

### User Confirmed Guardrails
- **Asset Classes**: ALL - Crypto + Forex + Indices + Commodities + Stocks
- **Paper Trading**: Must work end-to-end for testing
- **AI Behavior**: Fail-open (continue trading if AI unavailable)
- **High Accuracy Mode**: Enabled - Momus review required

---

## Work Objectives

### Core Objective
Implement all missing components to achieve a production-grade AI-powered automated bot trading platform supporting Crypto, Forex, Indices, Commodities, and Stocks with end-to-end paper trading capability.

### Concrete Deliverables
1. Real market data providers for FX, Indices, Commodities, Stocks (for landing page UI)
2. Fix AI decision wiring in TradingBot execution flow using `ai-connection-addon`
3. Implement circuit breaker enforcement with failure tracking
4. Wire market status checker into execution flow with CCXT DB support
5. Working end-to-end paper trading for bots (with `is_paper` tracking)
6. Real position sizing based on actual account balance
7. Admin UI visibility for circuit breaker status
8. Post-trade reconciliation audit trail

### Definition of Done
- [ ] All asset class data indicates real vs simulated source
- [ ] Bot AI decisions in TradingBot flow route through `ai-connection-addon`
- [ ] Circuit breaker blocks execution after N consecutive failures
- [ ] Orders rejected when market closed or data stale
- [ ] Paper trading creates virtual positions with `is_paper=1` flag
- [ ] Position sizing uses real balance from exchange
- [ ] Admin can view circuit breaker status per connection
- [ ] AI decision metadata stored in `ai_decisions` table
- [ ] Execution logs linked to AI decisions via `ai_decision_id`

### Must Have
- Real data for ALL asset classes (Crypto + Forex + Indices + Commodities + Stocks)
- AI decision pipeline via `ai-connection-addon` (TradingBot flow only)
- Circuit breaker enforcement with failure tracking
- Market status validation before order placement
- Working paper trading with proper tracking
- Real position sizing

### Must NOT Have (Guardrails)
- NO trading when circuit breaker is tripped
- NO order placement when market is closed
- NO paper trading that doesn't create virtual positions with tracking
- NO new references to deprecated AiTradingAddon in TradingBot execution flow

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES (PHPUnit, Laravel Testbench)
- **User wants tests**: YES - TDD approach for critical trading logic
- **Framework**: PHPUnit with Laravel feature tests
- **Execution**: All artisan commands via Docker: `docker exec 1Panel-php8-mrTy php artisan test`

### Verification Approach
Each TODO will include:
1. **Unit tests** for service logic (position sizing, circuit breaker, AI routing)
2. **Feature tests** for end-to-end execution flow (paper + live)
3. **Manual verification steps** using Docker commands

---

## Task Flow

```
Phase 1: Market Data (Landing Page) → Phase 2: AI Wiring → Phase 3: Safety → Phase 4: Paper Trading → Phase 5: Admin UI
```

## Parallelization

| Group | Tasks | Reason |
|-------|-------|--------|
| A | 1, 2, 3 | Independent - different data providers |
| B | 4, 5 | Can be done in parallel - both are AI routing fixes |
| C | 6, 7 | Sequential - circuit breaker needs failure tracking fields first |
| D | 8 | Depends on 6 (circuit breaker must work first) |
| E | 9, 10 | Sequential - paper trading needs position sizing |

| Task | Depends On | Reason |
|------|------------|--------|
| 6 | 1-3 | Circuit breaker needs schema updates |
| 7 | 4, 5 | Admin UI needs circuit breaker data |
| 8 | 6, 7 | Position sizing needs real balance from exchange |
| 9 | 1-8 | Paper trading needs all safety checks working |
| 10 | 6, 9 | Audit trail needs execution flow complete |

---

## Architectural Clarifications (Critical)

### Market Data Scope
There are TWO separate market data systems in this repo:

| System | Location | Purpose | Current State |
|--------|----------|---------|---------------|
| **Landing Page Data** | `main/app/Services/Trading/MarketDataService.php` | Display prices on marketing pages | Crypto real, FX/Indices/Commodities/Stocks simulated |
| **Trading Pipeline Data** | `main/addons/trading-management-addon/Modules/MarketData/Services/MarketDataService.php` | Store/retrieve OHLCV for bot decisions | Stores data from DataConnection adapters |

**This plan targets the Landing Page Data system** (first row) for real data. The Trading Pipeline already gets real data from DataConnection adapters.

### External Provider Decision: Twelve Data (EXPLICIT)

**Chosen Provider**: [Twelve Data](https://twelvedata.com/) (unified API for FX, stocks, indices, commodities)

**Why Twelve Data**:
- Free tier: **800 API calls/day** (not 100/day)
- Credit-based system: Most endpoints cost 1 credit, some cost more
- Good rate limits for landing page use case
- Comprehensive coverage: FX, stocks, indices, commodities, crypto
- Reliable websocket support for real-time data

**Rate Limit Strategy** (based on Twelve Data free tier - 800 credits/day):
- **Landing Page Cache**: 15-minute TTL (`MarketDataService::$cacheTtl`)
- **Scheduled Background Refresh**: Run every 15 minutes via Laravel Scheduler
- **User Page Loads**: Serve from cache (never hit API directly)
- **Expected Usage**:
  - Background job: 96 requests/day (12 asset classes × 8 refreshes per day)
  - Each request ~1 credit → 96 credits/day
  - **Within free tier (800/day)!**
  - Safe margin: ~88% headroom

**Note**: Some endpoints (e.g., time series with many data points) can cost more than 1 credit. For landing page single-quote data, each call is 1 credit.

**Configuration**:
```env
TWELVE_DATA_API_KEY=your_api_key
TWELVE_DATA_BASE_URL=https://api.twelvedata.com/v1
MARKET_DATA_CACHE_TTL=900  # 15 minutes in seconds
```

**Fallback**: If Twelve Data API fails, use simulated data with `source: 'simulated'` indicator.

### Simulated Data Policy
- **Goal**: Indicate data source clearly (real vs simulated)
- **Implementation**: Add `source` field: `'api'` for real data, `'simulated'` for fallback
- **On API failure**: Use simulated data BUT clearly indicate it's simulated via UI badge
- **Rationale**: Better to show stale/simulated data with clear indicator than show nothing

### Paper Trading Flag Contract
- **Authoritative flag**: `bot->is_paper_trading` on `TradingBot` model
- **InternalTrade tracking**: Add `is_paper` column to `internal_trades` table
- **Flow**:
  1. `TradingBotStrategyWorker` reads `$this->bot->is_paper_trading`
  2. Sets `decision['is_paper_trading']`
  3. `FilterAnalysisJob` checks `decision['is_paper_trading']` (NOT `test_mode`)
  4. `ExecutionJob` checks `executionData['is_paper_trading']` (NOT `test_mode`)
  5. `createVirtualPosition()` sets `is_paper = 1` when flag is true

### Required Fixes for Flag Normalization

**CRITICAL**: The `test_mode` flag is used in **7 files** across the codebase. All must be updated:

| File | Line(s) | Current Usage | Required Change |
|------|---------|---------------|-----------------|
| `FilterAnalysisJob.php` | 50 | `$this->decision['test_mode']` | → `$this->decision['is_paper_trading']` |
| `ExecutionJob.php` | 80 | `$executionData['test_mode']` | → `$executionData['is_paper_trading']` |
| `RiskManagementJob.php` | 137, 151, 156 | `$this->decision['test_mode']` | → `$this->decision['is_paper_trading']` |
| `TradeDecisionEngine.php` | 34, 45 | `$analysis['test_mode']` | → `$analysis['is_paper_trading']` |
| `TechnicalAnalysisService.php` | 201 | `'test_mode' => true` | → `'is_paper_trading' => true` |
| `MarketStatusChecker.php` | 141 | `'status' => 'test_mode'` | → `'status' => 'paper_trading'` (output only) |
| `TestFilterStrategySeeder.php` | 29 | `'test_mode' => true` | → `'is_paper_trading' => true` |

**Normalization Tasks** (embedded in Tasks 4, 5, 8):
1. Update all 7 files listed above
2. Verify no remaining `test_mode` references: `grep -r "test_mode" main/addons/trading-management-addon/`
3. Add explicit acceptance criteria: `docker exec 1Panel-php8-mrTy php artisan tinker --execute="App\Models\TradingBot::where('is_paper_trading', true)->exists() || echo 'no paper bots'"`

### Paper Trading User ID Source
- **Problem**: `createVirtualPosition()` derives `user_id` from `executionData['user_id']` or `auth()->id()`, but in a queued job `auth()->id()` is null
- **Solution**: `user_id` MUST be in `executionData['user_id']` - set by dispatcher from `TradingBot` owner
- **Implementation**: Ensure `FilterAnalysisJob` and downstream jobs include `bot->user_id` in payload

### Balance Fetching API
- **Method**: `Adapter::getAccountInfo()` returns `['free' => ..., 'used' => ..., 'total' => ..., 'info' => ...]`
- **Used by**: `CcxtAdapter::getAccountInfo()` internally calls `$this->exchange->fetch_balance()`
- **Reference**: `main/addons/trading-management-addon/Modules/DataProvider/Adapters/CcxtAdapter.php:120-137`

### MarketStatusChecker Wiring (CCXT Enhancement Required)
- **Current state**: `MarketStatusChecker` only checks Redis when `accountId` provided, returns `no_data` otherwise
- **Required enhancement**: Modify `MarketStatusChecker::checkMarketDataFreshness()` to:
  - Accept a DataConnection parameter
  - For CCXT connections: query `market_data` table via `MarketDataService` for last candle timestamp
  - For MetaAPI connections: use existing Redis stream
- **Config key unification**: Use `config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream')` consistently
- **Call site in ExecutionJob**: Add check AFTER adapter creation, BEFORE `executeTrade()` call (around line 137 in current code)
- **Inputs required from executionData**: `symbol`, `timeframe`, `connection_id`

### Circuit Breaker Rules
- **Failure conditions**: API error, timeout, rejected order, connection failure
- **Failure tracking**: Add `consecutive_failures` (int, default 0) and `last_failure_at` (datetime, nullable) columns
- **Cooldown**: Hardcoded 15 minutes (configurable via settings in future)
- **Reset conditions**: Successful trade execution resets counter to 0
- **Threshold**: Use existing `max_consecutive_failures` field
- **Reference**: `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php`

### AI Routing Contract
- **Scope**: Only TradingBot execution flow (BotSignalObserver, FilterAnalysisJob)
- **Service**: `Addons\AiConnectionAddon\App\Services\AiConnectionService`
- **Connection selection**: Use bot's configured `ai_connection_id` or default connection
- **Options**: Map bot's AI settings to service options

### AI Decision Logging Schema Mapping

**Target Table**: `ai_decisions` (existing schema)

**Field Mapping**:
| ai_decisions Column | Source | Notes |
|---------------------|--------|-------|
| `signal_id` | `$signal->id` | Nullable if no signal |
| `symbol` | `$signal->pair->name` or `executionData['symbol']` | REQUIRED |
| `timeframe` | `$signal->time->name` or `executionData['timeframe']` | REQUIRED |
| `action` | AI response mapped to BUY/SELL/HOLD/NEUTRAL | Enum |
| `confidence` | AI confidence score (0-100) | Default 0 |
| `reasoning` | AI explanation text | Nullable |
| `prompt_used` | Hash of prompt for audit | Nullable |
| `analysis_data` | JSON with entry/sl/tp recommendations | Nullable |
| `ai_connection_id` | Bot's ai_connection_id | Nullable |
| `model_used` | e.g., "gpt-4", "gemini-pro" | Nullable |

**Fail-Open Path**: When AI is unavailable:
- **Definition**: "Fail-open" means the trading pipeline continues without AI veto
- **Behavior**:
  - Create `AiDecision` record with `action='HOLD'`, `confidence=0`
  - `reasoning='AI unavailable - fail-open'`
  - Execution pipeline proceeds as if AI said "no opinion"
  - Downstream filters/risk checks still apply (circuit breaker, market status)
- **NOT**: Do NOT automatically execute trades. The pipeline continues but AI has not approved the trade.
- **Record**: Always create `ai_decisions` record to track the attempt (even when AI fails)

### AI Decision ID Linking Through Pipeline

**ExecutionData Contract** (required fields for Tasks 4, 5, 8):

```php
$executionData = [
    'bot_id' => (int) $bot->id,
    'user_id' => (int) $bot->user_id,  // REQUIRED for paper trading
    'connection_id' => (int) $connection->id,
    'signal_id' => (int|null) $signal?->id,
    'ai_decision_id' => (int|null) $aiDecision->id,  // NEW - from Task 4/5
    'symbol' => (string) $symbol,
    'timeframe' => (string) $timeframe,  // REQUIRED for MarketStatusChecker
    'direction' => (string) 'buy'|'sell',
    'quantity' => (float) $quantity,
    'entry_price' => (float|null) $entryPrice,
    'stop_loss' => (float|null) $slPrice,
    'take_profit' => (float|null) $tpPrice,
    'is_paper_trading' => (bool) $bot->is_paper_trading,  // REQUIRED
    'created_at' => now()->toISOString(),
];
```

**Pipeline Flow**:
1. `BotSignalObserver` → creates `AiDecision` → gets `ai_decision_id` → dispatches with `executionData`
2. `FilterAnalysisJob` → may update/create AI decision → propagates `ai_decision_id`
3. `ExecutionJob` → receives `executionData` with `ai_decision_id` → creates `ExecutionLog` with `ai_decision_id`

### Paper Trading Persistence Through InternalBrokerService

**Current State**: `InternalBrokerService::placeOrder()` creates `InternalTrade` without `is_paper` field

**Required Changes**:
1. **Migration**: Add `is_paper` (boolean, default false) to `internal_trades` table
2. **InternalBrokerService**: Extend `placeOrder()` signature:
   ```php
   public function placeOrder(
       User $user,
       string $symbol,
       string $direction,
       float $quantity,
       float $currentPrice,
       ?float $slPrice = null,
       ?float $tpPrice = null,
       bool $isPaper = false  // NEW PARAMETER
   ): InternalTrade
   ```
3. **ExecutionJob::createVirtualPosition()**: Pass `$this->executionData['is_paper_trading']` to `InternalBrokerService::placeOrder()`
4. **SL/TP Monitoring**: Query `internal_trades` where `status='open'` (no separate handling needed - paper trades tracked in same table)

---

## Schema Migrations Required (Must Run First)

**These migrations are REQUIRED before implementing tasks. They add missing columns:**

| Migration | File | Columns Added | Required By |
|-----------|------|---------------|-------------|
| M1 | `main/database/migrations/*_add_is_paper_to_internal_trades.php` | `is_paper` (boolean, default false) | Task 8 |
| M2 | `main/addons/trading-management-addon/database/migrations/*_add_ai_decision_id_to_execution_logs.php` | `ai_decision_id` (nullable FK) | Tasks 4, 5, 12 |
| M3 | `main/addons/trading-management-addon/database/migrations/*_add_circuit_breaker_tracking_to_execution_connections.php` | `consecutive_failures` (int, default 0), `last_failure_at` (datetime, nullable) | Tasks 6, 10 |

**Verification Commands (run AFTER migrations)**:
```bash
# Verify M1
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'is_paper column: ' . (Schema::hasColumn('internal_trades', 'is_paper') ? 'EXISTS' : 'MISSING');"

# Verify M2
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'ai_decision_id column: ' . (Schema::hasColumn('execution_logs', 'ai_decision_id') ? 'EXISTS' : 'MISSING');"

# Verify M3
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'consecutive_failures column: ' . (Schema::hasColumn('execution_connections', 'consecutive_failures') ? 'EXISTS' : 'MISSING'); echo ' | last_failure_at column: ' . (Schema::hasColumn('execution_connections', 'last_failure_at') ? 'EXISTS' : 'MISSING');"
```

**Migration must pass before tasks**: If any migration fails, stop and fix schema before proceeding.

---

## TODOs

---

### Phase 1: Market Data - Landing Page (P0 - Critical)

- [ ] 1. Implement Real FX Market Data Provider (Twelve Data)

  **What to do**:
  - Add Twelve Data FX data provider using `fx_pair` endpoint
  - Update `main/app/Services/Trading/MarketDataService.php:getForexData()` to use Twelve Data
  - Implement 15-minute cache to prevent quota exhaustion (800 credits/day free tier)
  - On API failure: return simulated data BUT set `source: 'simulated'` and log warning
  - Add unit tests for rate fetching and fallback behavior

  **Must NOT do**:
  - Remove crypto support (CoinGecko continues working)
  - Hide simulated data (must show with clear indicator)
  - Call API directly on every page load (use cache)

  **Parallelizable**: YES (with 2, 3)

  **References**:

  **Pattern References** (existing code to follow):
  - `main/app/Services/Trading/MarketDataService.php:136-143` - `getForexData()` method structure, caching pattern (30-second cache at line 13)
  - `main/app/Services/Trading/MarketDataService.php:93-131` - `getCryptoData()` showing real API integration with CoinGecko

  **API/Type References** (contracts to implement against):
  - `main/app/Services/Trading/MarketDataService.php:getForexData($limit = 10)` - Method signature
  - Twelve Data API: `https://twelvedata.com/docs/api-reference/fx-pair`

  **Configuration**:
  ```env
  TWELVE_DATA_API_KEY=your_api_key
  MARKET_DATA_CACHE_TTL=900  # 15 minutes in seconds
  ```

  **Implementation Details**:
  - Create new service: `App\Services\Trading\TwelveDataService`
  - Method: `getExchangeRate(string $from, string $to): array{price: float, source: string, timestamp: string}`
  - Update `MarketDataService::getForexData()` to call TwelveDataService with caching
  - Rate limit handling: If API returns 429 or quota exceeded, log and fall back to simulated

  **Test References** (testing patterns to follow):
  - `main/tests/Unit/Services/TradingTerminalServiceTest.php` - Test file pattern for services

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Services/FxMarketDataProviderTest.php`
  - [ ] Test covers: real rate fetch via Twelve Data, API failure sets source='simulated', timeout handling, cache hit/miss
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Services/FxMarketDataProviderTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Using Laravel tinker via Docker:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$svc = app(\App\Services\Trading\MarketDataService::class); \$data = \$svc->getForexData(5); echo json_encode([\$data[0]['source'] ?? 'no source', \$data[0]['price'] ?? 'no price'], JSON_PRETTY_PRINT);"
    ```
  - [ ] Verify `source` field is 'api' (success) or 'simulated' (API failed)
  - [ ] Verify response is not empty

  **Evidence Required**:
  - [ ] Command output showing source field and price

- [ ] 2. Implement Real Indices/Commodities/Stocks Data Provider (Twelve Data)

  **What to do**:
  - Use Twelve Data `quote` endpoint for stocks, indices, and commodities
  - Implement 15-minute cache to prevent quota exhaustion (800 credits/day free tier)
  - Update `getIndicesData()`, `getCommoditiesData()`, `getStocksData()` methods
  - On API failure: return simulated data with `source: 'simulated'`
  - Add unit tests

  **Must NOT do**:
  - Remove existing crypto/forex support
  - Hide simulated data
  - Call API directly on page loads

  **Parallelizable**: YES (with 1, 3)

  **References**:

  **Pattern References** (existing code to follow):
  - `main/app/Services/Trading/MarketDataService.php:145-179` - Index/commodity/stock methods with simulated fallback

  **Implementation Details**:
  - Extend `TwelveDataService` with `getQuote(string $symbol): array{price: float, change: float, ...}`
  - Handle commodity symbols (e.g., 'XAUUSD' for Gold, 'CL=F' for Oil)
  - Map Twelve Data response to existing data structure

  **External References** (libraries and frameworks):
  - Twelve Data docs: `https://twelvedata.com/docs/api-reference/quote`

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Services/EquityCommodityDataProviderTest.php`
  - [ ] Test covers: real price fetch, API failure sets source='simulated', cache behavior
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Services/EquityCommodityDataProviderTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Using Laravel tinker via Docker:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$svc = app(\App\Services\Trading\MarketDataService::class); \$data = \$svc->getStocksData(3); echo json_encode([\$data[0]['source'] ?? 'no source', \$data[0]['symbol'] ?? 'no symbol'], JSON_PRETTY_PRINT);"
    ```

  **Evidence Required**:
  - [ ] Command output showing source field for stocks

- [ ] 3. Add Data Freshness Indicator to Landing Page

  **What to do**:
  - Add `last_updated` timestamp tracking to all market data methods
  - Add `source` field ('api' or 'simulated') to indicate data quality
  - Update `getLandingPageData()` to include freshness info
  - Add tests for freshness indicator

  **Must NOT do**:
  - Modify error handling for valid data

  **References**:

  **Pattern References** (existing code to follow):
  - `main/app/Services/Trading/MarketDataService.php:184-199` - `getLandingPageData()` structure

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Services/DataFreshnessIndicatorTest.php`
  - [ ] Test covers: source field correctly set, last_updated is recent
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Services/DataFreshnessIndicatorTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Using Laravel tinker via Docker:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$svc = app(\App\Services\Trading\MarketDataService::class); \$data = \$svc->getLandingPageData(); echo json_encode(['last_updated' => \$data['last_updated'] ?? 'no timestamp', 'source' => \$data['source'] ?? 'no source'], JSON_PRETTY_PRINT);"
    ```
  - [ ] Verify `last_updated` is within last 5 minutes
  - [ ] Verify `source` indicates data quality

  **Evidence Required**:
  - [ ] Command output showing freshness indicators

---

### Phase 2: AI Wiring Fix (P0 - Critical)

- [ ] 4. Fix Bot Signal Observer AI Routing

  **What to do**:
  - Update `main/addons/trading-management-addon/Modules/TradingBot/Observers/BotSignalObserver.php` to use `ai-connection-addon`
  - Replace deprecated `Addons\AiTradingAddon\...` calls with `Addons\AiConnectionAddon\App\Services\AiConnectionService`
  - Maintain fail-open behavior (continue trading if AI unavailable)
  - Create record in `ai_decisions` table with AI decision metadata (see schema mapping above)
  - Return `ai_decision_id` to include in execution pipeline
  - Add tests for AI routing and fallback behavior

  **Must NOT do**:
  - Change fail-open behavior (user requirement)
  - Remove AI decision logging (must use ai_decisions table)

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/TradingBot/Observers/BotSignalObserver.php:1-100` - Observer structure, deprecated AI call at line ~63
  - `main/addons/multi-channel-signal-addon/app/Parsers/AiMessageParser.php` - Correct `ai-connection-addon` usage
  - `main/addons/trading-management-addon/database/migrations/2025_12_11_150000_create_ai_decisions_table.php` - AI decisions schema

  **API/Type References** (contracts to implement against):
  - `main/addons/ai-connection-addon/App/Services/AiConnectionService.php`
  - `main/addons/ai-connection-addon/App/Contracts/AiProviderInterface.php`

  **AI Decision Schema Mapping**:
  ```php
  $aiDecision = AiDecision::create([
      'signal_id' => $signal?->id,
      'symbol' => $signal?->pair?->name ?? $executionData['symbol'],
      'timeframe' => $signal?->time?->name ?? $executionData['timeframe'],
      'action' => $aiResponse['action'] ?? 'HOLD',  // Map AI response to BUY/SELL/HOLD/NEUTRAL
      'confidence' => $aiResponse['confidence'] ?? 0,
      'reasoning' => $aiResponse['reasoning'] ?? 'AI unavailable - fail-open',
      'prompt_used' => hash('sha256', $prompt),  // For audit, not storing full prompt
      'analysis_data' => json_encode($aiResponse['analysis'] ?? []),
      'ai_connection_id' => $bot->ai_connection_id,
      'model_used' => $aiResponse['model'] ?? null,
  ]);
  ```

  **Test References** (testing patterns to follow):
  - `main/tests/Feature/Trading/ConcurrentTradingBotOperationsTest.php`

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Addons/TradingBot/AiRoutingTest.php`
  - [ ] Test covers: AI decision via ai-connection-addon, AI failure fallback, ai_decisions record created, schema mapping
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingBot/AiRoutingTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Trigger bot signal observation
  - [ ] Check logs for AI decision with provider/model info using ai-connection-addon
  - [ ] Execute DB query to verify record exists:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$dec = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision::latest()->first(); echo \$dec ? json_encode(['action' => \$dec->action, 'confidence' => \$dec->confidence, 'model' => \$dec->model_used], JSON_PRETTY_PRINT) : 'no records';"
    ```
  - **PASS**: Query returns record with action, confidence, model_used fields populated
  - **FAIL**: Query returns empty or missing required fields

  **Evidence Required**:
  - [ ] Log output showing AI decision with ai-connection-addon provider
  - [ ] Database query output showing ai_decisions record with action, confidence, model_used

- [ ] 5. Fix FilterAnalysisJob AI Routing

  **What to do**:
  - Update `main/addons/trading-management-addon/Modules/TradingBot/Jobs/FilterAnalysisJob.php` to use `ai-connection-addon`
  - Replace deprecated AI addon references with `Addons\AiConnectionAddon\App\Services\AiConnectionService`
  - Fix: change `decision['test_mode']` check to `decision['is_paper_trading']`
  - Create/update record in `ai_decisions` table for analysis results
  - Propagate `ai_decision_id` in execution pipeline
  - Maintain fail-open behavior
  - Add tests

  **Must NOT do**:
  - Change fail-open behavior
  - Remove analysis result logging (use ai_decisions table)

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/TradingBot/Jobs/FilterAnalysisJob.php:1-100` - Job structure, current AI call around line ~294
  - `main/addons/trading-management-addon/Modules/TradingBot/Workers/TradingBotStrategyWorker.php:183` - Where `is_paper_trading` is set

  **API/Type References** (contracts to implement against):
  - `main/addons/ai-connection-addon/App/Services/AiConnectionService.php`

  **ExecutionData Contract** (must include):
  ```php
  $this->dispatch([
      'bot_id' => $this->bot->id,
      'user_id' => $this->bot->user_id,  // REQUIRED for paper trading
      'connection_id' => $connection->id,
      'signal_id' => $signal?->id,
      'ai_decision_id' => $aiDecision->id,  // NEW
      'symbol' => $symbol,
      'timeframe' => $timeframe,  // REQUIRED for MarketStatusChecker
      'direction' => $direction,
      'quantity' => $quantity,
      'entry_price' => $entryPrice,
      'stop_loss' => $slPrice,
      'take_profit' => $tpPrice,
      'is_paper_trading' => $decision['is_paper_trading'],  // NORMALIZED
      'created_at' => now()->toISOString(),
  ]);
  ```

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Addons/TradingBot/FilterAnalysisAiTest.php`
  - [ ] Test covers: AI via ai-connection-addon, failure handling, ai_decisions record created, is_paper_trading flag check, executionData contract
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingBot/FilterAnalysisAiTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Dispatch FilterAnalysisJob
  - [ ] Check logs for analysis with ai-connection-addon
  - [ ] Execute DB query to verify record exists:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$dec = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision::latest()->first(); echo \$dec ? json_encode(['action' => \$dec->action, 'confidence' => \$dec->confidence, 'reasoning_len' => strlen(\$dec->reasoning ?? '')], JSON_PRETTY_PRINT) : 'no records';"
    ```
  - [ ] Execute DB query to verify ai_decision_id was linked to execution:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$log = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::latest()->first(); echo \$log ? json_encode(['ai_decision_id' => \$log->ai_decision_id, 'id' => \$log->id], JSON_PRETTY_PRINT) : 'no execution logs';"
    ```
  - **PASS**: Query returns record with non-null `ai_decision_id`
  - **FAIL**: Query returns null `ai_decision_id` (propagation failed)
  - **PASS**: Query returns record with confidence > 0 and reasoning text
  - **FAIL**: Query returns empty or confidence = 0 without fail-open reasoning

  **Evidence Required**:
  - [ ] Log output showing AI analysis using ai-connection-addon
  - [ ] Database query output showing ai_decisions record with confidence score

---

### Phase 3: Safety & Risk Controls (P0 - Critical)

- [ ] 6. Implement Circuit Breaker Enforcement

  **What to do**:
  - Create migration: add `consecutive_failures` (int, default 0) and `last_failure_at` (datetime, nullable) columns to `execution_connections` table
  - Update `ExecutionConnection` model `$fillable` and `$casts` for new fields
  - Update `ExecutionConnection::canExecuteTrades()` to:
    - Check `circuit_breaker_enabled` field
    - Check if `consecutive_failures >= max_consecutive_failures`
    - Check if within 15-minute cooldown (if `last_failure_at` is within last 15 min)
  - In `ExecutionJob`: increment `consecutive_failures` and set `last_failure_at` on execution failure (around line 162-165 in current code)
  - In `ExecutionJob`: reset `consecutive_failures` to 0 on successful execution (around line 143-146 in current code)
  - Add admin visibility via existing connection edit view
  - Add tests for circuit breaker behavior

  **Must NOT do**:
  - Modify balance or position sizing logic
  - Change other connection validation rules

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php:37-75` - Model structure, circuit_breaker_enabled, max_consecutive_failures
  - `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php:155-158` - Current `canExecuteTrades()` implementation
  - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:162-165` - Failure handling (update status to 'failed')
  - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:143-146` - Success handling (update status to 'executed')

  **Migration Location**: `main/addons/trading-management-addon/database/migrations/`

  **Implementation Details**:
  ```php
  // In ExecutionJob, on failure:
  $connection->increment('consecutive_failures');
  $connection->update(['last_failure_at' => now()]);
  
  // In ExecutionJob, on success:
  $connection->update(['consecutive_failures' => 0]);
  
  // In ExecutionConnection::canExecuteTrades():
  public function canExecuteTrades(): bool
  {
      if (!$this->is_active || $this->status !== 'active') {
          return false;
      }
      
      if ($this->circuit_breaker_enabled && $this->consecutive_failures >= $this->max_consecutive_failures) {
          // Check cooldown
          if ($this->last_failure_at && $this->last_failure_at->diffInMinutes(now()) < 15) {
              return false; // Still in cooldown
          }
          // Cooldown expired, reset counter
          $this->update(['consecutive_failures' => 0]);
      }
      
      return true;
  }
  ```

  **Test References** (testing patterns to follow):
  - `main/tests/Feature/Trading/ConcurrentTradingBotOperationsTest.php`

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Migration file created in `main/addons/trading-management-addon/database/migrations/`
  - [ ] Test file created: `main/tests/Unit/Addons/Execution/CircuitBreakerTest.php`
  - [ ] Test covers: blocks after N failures, resets on success, cooldown period works
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/Execution/CircuitBreakerTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Verify schema migration added columns:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'consecutive_failures: ' . (Schema::hasColumn('execution_connections', 'consecutive_failures') ? 'EXISTS' : 'MISSING'); echo ' | last_failure_at: ' . (Schema::hasColumn('execution_connections', 'last_failure_at') ? 'EXISTS' : 'MISSING');"
    ```
  - [ ] Simulate 3 consecutive failures on a connection (manually set values):
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$conn = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::first(); \$conn->update(['consecutive_failures' => 3]); echo 'Set failures to 3';"
    ```
  - [ ] Verify 4th attempt is blocked with circuit breaker message in logs:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$conn = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::first(); echo 'canExecuteTrades: ' . (\$conn->canExecuteTrades() ? 'TRUE (allowed)' : 'FALSE (blocked)');"
    ```
  - **PASS**: `canExecuteTrades()` returns FALSE when consecutive_failures >= max_consecutive_failures
  - **FAIL**: Function returns TRUE when it should block
  - [ ] Verify admin can reset circuit breaker:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$conn = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::first(); \$conn->update(['consecutive_failures' => 0, 'last_failure_at' => null]); echo 'Reset circuit breaker';"
    ```

  **Evidence Required**:
  - [ ] Command output showing schema columns exist
  - [ ] Log output showing circuit breaker blocked execution
  - [ ] Command output showing circuit breaker reset works

- [ ] 7. Wire MarketStatusChecker into ExecutionJob (with CCXT DB Support)

  **What to do**:
  - Modify `MarketStatusChecker::checkMarketDataFreshness()` signature to accept optional DataConnection:
    ```php
    public function checkMarketDataFreshness(
        string $symbol,
        string $timeframe,
        ?string $accountId = null,
        ?int $botId = null,
        ?DataConnection $dataConnection = null
    ): array
    ```
  - For CCXT connections (when `$dataConnection` provided, no Redis): query `market_data` table via `MarketDataService` for last candle timestamp
  - For MetaAPI connections (when `$accountId` provided): use existing Redis stream with unified config key
  - Unify Redis prefix: Use `config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream')` consistently
  - In `ExecutionJob::handle()`: Add market status check AFTER adapter creation (line ~116), BEFORE `executeTrade()` call (line ~137)
  - Get `symbol` and `timeframe` from `$this->executionData`
  - Get `dataConnection` from `$connection->dataConnection` relationship
  - Reject orders when data is stale (timestamp older than timeframe threshold)
  - Add logging for market status checks
  - Add tests for market status enforcement (both Redis and DB paths)

  **Must NOT do**:
  - Modify order placement logic itself

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:116-137` - Where to add market check (after adapter created, before executeTrade)
  - `main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php:38-112` - Current implementation (only Redis)
  - `main/addons/trading-management-addon/Modules/MarketData/Services/MarketDataService.php` - DB-based market data storage
  - `main/addons/trading-management-addon/Modules/TradingBot/Workers/TradingBotStrategyWorker.php:251-252` - Redis prefix pattern to unify
  - `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php:96-99` - `dataConnection` relationship

  **Config Key Unification**:
  - Use `config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream')` in BOTH MarketStatusChecker AND TradingBotStrategyWorker

  **Implementation Details**:
  ```php
  // In ExecutionJob::handle(), after adapter creation (~line 116):
  $marketStatus = app(MarketStatusChecker::class)->checkMarketDataFreshness(
      symbol: $this->executionData['symbol'] ?? '',
      timeframe: $this->executionData['timeframe'] ?? '',
      accountId: $connection->dataConnection?->credentials['account_id'] ?? null,
      botId: $this->executionData['bot_id'] ?? null,
      dataConnection: $connection->dataConnection,
  );

  if (!$marketStatus['is_fresh']) {
      Log::warning('ExecutionJob: Market data stale, rejecting trade', [
          'symbol' => $this->executionData['symbol'] ?? 'unknown',
          'age_minutes' => $marketStatus['age_minutes'] ?? 0,
          'max_allowed' => $marketStatus['max_age_minutes'] ?? 0,
      ]);
      return; // Reject trade
  }
  ```

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Addons/Execution/MarketStatusEnforcementTest.php`
  - [ ] Test covers: fresh data accepted (both Redis and DB), stale data rejected, CCXT and MetaAPI paths, config key unification
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/Execution/MarketStatusEnforcementTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Verify schema migration added `is_paper` column:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'is_paper column: ' . (Schema::hasColumn('internal_trades', 'is_paper') ? 'EXISTS' : 'MISSING');"
    ```
  - [ ] Test with simulated stale data (set market_data timestamp to 24h ago):
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$md = \Addons\TradingManagement\Modules\MarketData\Models\MarketData::first(); \$md->update(['timestamp' => now()->subHours(24)]); echo 'Set stale data';"
    ```
  - [ ] Verify order is rejected with "stale data" message in logs:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$status = app(\Addons\TradingManagement\Modules\Execution\Services\MarketStatusChecker::class)->checkMarketDataFreshness(\$md->symbol, '1h'); echo json_encode(\$status, JSON_PRETTY_PRINT);"
    ```
  - [ ] Verify with fresh data (within timeframe threshold) → order proceeds
  - **PASS**: `is_fresh` is FALSE for stale data, TRUE for fresh data
  - **FAIL**: Status check doesn't detect stale data correctly

  **Evidence Required**:
  - [ ] Command output showing market status check correctly identifies stale data
  - [ ] Log output showing order rejection due to market status

---

### Phase 4: Paper Trading & Position Sizing (P1 - Important)

- [ ] 8. Implement End-to-End Paper Trading for Bots

  **What to do**:
  - Create migration: add `is_paper` (boolean, default false) column to `internal_trades` table
  - Update `InternalTrade` model `$fillable` and `$casts` for new field
  - Normalize paper trading flag: ensure all code uses `is_paper_trading` (not `test_mode`)
  - Fix `FilterAnalysisJob`: change `decision['test_mode']` check to `decision['is_paper_trading']`
  - Fix `ExecutionJob`: change `$executionData['test_mode']` check to `$executionData['is_paper_trading']`
  - Remove early return for paper trading in `ExecutionJob::handle()`
  - Ensure `executionData['user_id']` is set (from bot owner) - required for `createVirtualPosition()`
  - Update `InternalBrokerService::placeOrder()` signature to accept `is_paper` parameter
  - Update `createVirtualPosition()` to pass `is_paper` flag to `InternalBrokerService`
  - Ensure paper trading respects same risk checks (circuit breaker, market status)
  - Add tests for paper trading workflow

  **Must NOT do**:
  - Create real positions when paper trading flag is set
  - Skip SL/TP monitoring for paper positions

  **References**:

  **Pattern References** (existing code to follow):
  - `main/app/Models/InternalTrade.php:14-27` - Current schema (no is_paper field)
  - `main/app/Services/InternalBrokerService.php:19-51` - Current `placeOrder()` method
  - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:79-91` - Current early return for test_mode
  - `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:666-669` - `createVirtualPosition()` user_id requirement
  - `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php:36,51` - `is_paper_trading` field and cast
  - `main/addons/trading-management-addon/Modules/TradingBot/Workers/TradingBotStrategyWorker.php:183-195` - `is_paper_trading` passed in decision

  **Migration Location**: `main/database/migrations/`

  **Implementation Details**:
  ```php
  // InternalBrokerService::placeOrder() - NEW SIGNATURE:
  public function placeOrder(
      User $user,
      string $symbol,
      string $direction,
      float $quantity,
      float $currentPrice,
      ?float $slPrice = null,
      ?float $tpPrice = null,
      bool $isPaper = false  // NEW PARAMETER
  ): InternalTrade {
      // ... existing logic ...
      
      $trade = InternalTrade::create([
          'user_id' => $user->id,
          'symbol' => strtoupper($symbol),
          'direction' => strtolower($direction),
          'quantity' => $quantity,
          'entry_price' => $currentPrice,
          'current_price' => $currentPrice,
          'sl_price' => $slPrice,
          'tp_price' => $tpPrice,
          'pnl' => 0,
          'status' => 'open',
          'opened_at' => now(),
          'is_paper' => $isPaper,  // NEW FIELD
      ]);
      
      // ... rest of method ...
  }
  
  // ExecutionJob::createVirtualPosition() - UPDATED:
  protected function createVirtualPosition($connection, $result)
  {
      // ...
      $internalBrokerService = app(InternalBrokerService::class);
      $trade = $internalBrokerService->placeOrder(
          $user,
          $symbol,
          $direction,
          $quantity,
          $entryPrice ?? 0,
          $stopLoss,
          $takeProfit,
          $this->executionData['is_paper_trading'] ?? false  // PASS FLAG
      );
      // ...
  }
  ```

  **Test References** (testing patterns to follow):
  - `main/tests/Feature/Trading/TradingBotExecutionFlowTest.php` - Existing feature test pattern

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Migration created: add `is_paper` to `internal_trades` table
  - [ ] Test file created: `main/tests/Feature/Trading/PaperTradingFlowTest.php` (new file)
  - [ ] Test covers: paper order creates virtual position with is_paper=1, SL/TP monitoring works, is_paper_trading flag normalized, InternalBrokerService signature
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Trading/PaperTradingFlowTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Verify schema migration added `is_paper` column:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'is_paper column: ' . (Schema::hasColumn('internal_trades', 'is_paper') ? 'EXISTS' : 'MISSING');"
    ```
  - [ ] Create trading bot with `is_paper_trading = true`:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::first(); \$bot->update(['is_paper_trading' => true]); echo 'Paper trading enabled for bot ' . \$bot->id;"
    ```
  - [ ] Trigger bot execution (via signal or manual dispatch)
  - [ ] Verify virtual position created in `internal_trades` table with `is_paper = 1`:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$trade = \App\Models\InternalTrade::where('is_paper', true)->latest()->first(); echo \$trade ? json_encode(['id' => \$trade->id, 'symbol' => \$trade->symbol, 'is_paper' => \$trade->is_paper], JSON_PRETTY_PRINT) : 'no paper trades found';"
    ```
  - **PASS**: Query returns trade with `is_paper = 1`
  - **FAIL**: Query returns empty or `is_paper = 0`
  - [ ] Verify SL/TP monitoring updates virtual position (query same trade after monitoring runs)

  **Evidence Required**:
  - [ ] Database query output via Docker showing virtual position with is_paper=1

- [ ] 9. Implement Real Position Sizing

  **What to do**:
  - Update `TradeDecisionEngine.php` to fetch real balance from exchange via adapters
  - Use `Adapter::getAccountInfo()` method to get balance (returns `['free', 'used', 'total']`)
  - Replace `balance = 1000` placeholder with actual balance from exchange connection
  - Implement fallback to fixed lot if balance fetch fails (with warning logged)
  - Add tests for position sizing with real balances

  **Must NOT do**:
  - Use hardcoded balance values
  - Fail silently if balance fetch fails

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/TradingBot/Services/TradeDecisionEngine.php:120-150` - Position sizing with placeholder at line ~126
  - `main/addons/trading-management-addon/Modules/DataProvider/Adapters/CcxtAdapter.php:120-137` - `getAccountInfo()` method returning `['free', 'used', 'total', 'info']`
  - `main/addons/trading-management-addon/Modules/RiskManagement/Services/RiskCalculatorService.php:50-100` - Position size calculation

  **Acceptance Criteria**:

  **If TDD (tests enabled)**:
  - [ ] Test file created: `main/tests/Unit/Addons/TradingBot/PositionSizingTest.php`
  - [ ] Test covers: real balance used, lot size calculated correctly, fallback on failure
  - [ ] Command: `docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingBot/PositionSizingTest.php` → PASS

  **Manual Execution Verification**:
  - [ ] Run bot with live connection
  - [ ] Verify calculated lot size based on real balance:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$engine = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradeDecisionEngine::class); \$bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::first(); \$result = \$engine->calculatePositionSize(\$bot, \$bot->connections->first(), ['symbol' => 'BTC/USDT']); echo json_encode(['quantity' => \$result['quantity'] ?? 'error', 'balance_used' => \$result['balance_used'] ?? 'default'], JSON_PRETTY_PRINT);"
    ```
  - **PASS**: Query returns non-zero quantity and balance_used > 0
  - **FAIL**: Returns default values or errors
  - [ ] Check logs for "fetched balance: X" message

  **Evidence Required**:
  - [ ] Command output showing real balance used and quantity calculated

---

### Phase 5: Admin UI & Observability (P2 - Nice to Have)

- [ ] 10. Add Circuit Breaker Status to Admin UI

  **What to do**:
  - Add circuit breaker status column to execution connections list
  - Add circuit breaker details section to connection edit page
  - Add "Reset Circuit Breaker" button for admins with route/controller
  - Show consecutive failure count and last failure time
  - Add route: `POST /admin/trading/connections/{id}/reset-circuit-breaker`

  **Must NOT do**:
  - Modify any execution logic

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/resources/views/backend/trading-management/operations/connections/index.blade.php` - Connection list
  - `main/addons/trading-management-addon/resources/views/backend/trading-management/operations/connections/edit.blade.php` - Edit form
  - `main/addons/trading-management-addon/Modules/Execution/Controllers/Backend/TradingOperationsController.php` - Controller pattern

  **Acceptance Criteria**:
  - [ ] Admin sees circuit breaker status (ACTIVE/TRIPPED) in connection list
  - [ ] Admin can view circuit breaker details (failure count, last failure, cooldown status)
  - [ ] Admin can reset circuit breaker (resets consecutive_failures to 0, clears last_failure_at)

  **Manual Execution Verification**:
  - [ ] Verify route exists:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan route:list | grep "reset-circuit-breaker"
    ```
  - [ ] Verify route exists and is POST:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan route:list --name="admin.trading.connections.reset-circuit-breaker" --verbose
    ```
    - Expected output shows: POST method, controller action
  - [ ] Verify database columns exist for display:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$conn = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::first(); echo json_encode(['failures' => \$conn->consecutive_failures, 'last_failure' => \$conn->last_failure_at], JSON_PRETTY_PRINT);"
    ```
  - **PASS**: Route exists, DB columns have data, admin can see status
  - **FAIL**: Route 404 or DB columns missing

  **Evidence Required**:
  - [ ] Screenshot of admin UI showing circuit breaker status
  - [ ] Command output showing route exists and DB columns

- [ ] 11. Add AI Decision Logging to Admin UI

  **What to do**:
  - Create admin page to view AI decision logs from `ai_decisions` table
  - Show: timestamp, symbol, action, confidence, model used, reasoning
  - Add filters by date range, symbol, action type
  - Add export to CSV functionality
  - Link to execution logs where AI decisions were used

  **Must NOT do**:
  - Modify any trading logic

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/resources/views/backend/trading-management/strategy/ai-models/index.blade.php` - AI models list pattern
  - `main/addons/trading-management-addon/database/migrations/2025_12_11_150000_create_ai_decisions_table.php` - AI decisions schema

  **Acceptance Criteria**:
  - [ ] Admin can view AI decision logs from ai_decisions table
  - [ ] Logs show action, confidence, model, reasoning
  - [ ] Filters work (date range, symbol, action)
  - [ ] Export to CSV works

  **Manual Execution Verification**:
  - [ ] Verify route exists:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan route:list | grep "ai-decision"
    ```
  - [ ] Verify database has records to display:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$count = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision::count(); echo 'AI decisions: ' . \$count;"
    ```
  - [ ] Navigate to Admin → Trading Management → Strategy → AI Models
  - [ ] Verify AI decision logs displayed with correct information
  - [ ] Test CSV export by clicking export button
  - **PASS**: Route exists, records exist, UI shows data
  - **FAIL**: Route 404 or no records or UI empty

  **Evidence Required**:
  - [ ] Screenshot of AI decision logs page
  - [ ] Command output showing AI decision records exist

- [ ] 12. Add Post-Trade Reconciliation Audit Trail

  **What to do**:
  - Create migration for `execution_audit` table to log all execution events
  - Track: order sent, order filled, position opened, SL/TP hit, position closed, P&L
  - Add comparison between bot-expected P&L and actual P&L from exchange
  - Add `ai_decision_id` column to `execution_logs` table (migration) to link trades with AI decisions
  - Create admin page to view audit trail per connection

  **Must NOT do**:
  - Modify any order placement logic

  **References**:

  **Pattern References** (existing code to follow):
  - `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionLog.php` - Existing execution log
  - Migration pattern: `main/addons/trading-management-addon/database/migrations/*_create_execution_logs_table.php`
  - `main/addons/trading-management-addon/database/migrations/2025_12_11_150000_create_ai_decisions_table.php` - For linking AI decisions

  **Acceptance Criteria**:
  - [ ] Migration creates `execution_audit` table with required columns
  - [ ] Migration adds `ai_decision_id` to `execution_logs` table
  - [ ] Audit records created for each execution event
  - [ ] Admin can view audit trail per connection
  - [ ] P&L comparison shown (expected vs actual)
  - [ ] Execution logs can be linked to AI decisions via ai_decision_id

  **Manual Execution Verification**:
  - [ ] Verify migration ran successfully:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan migrate:status | grep "execution_audit\|execution_logs"
    ```
  - [ ] Verify `execution_audit` table exists:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'execution_audit table: ' . (Schema::hasTable('execution_audit') ? 'EXISTS' : 'MISSING');"
    ```
  - [ ] Verify `ai_decision_id` column exists in `execution_logs`:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo 'ai_decision_id column: ' . (Schema::hasColumn('execution_logs', 'ai_decision_id') ? 'EXISTS' : 'MISSING');"
    ```
  - [ ] Execute a trade (paper or live)
  - [ ] Verify audit record created:
    ```bash
    docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$audit = \Addons\TradingManagement\Modules\Execution\Models\ExecutionAudit::latest()->first(); echo \$audit ? json_encode(['id' => \$audit->id, 'event' => \$audit->event, 'pnl_expected' => \$audit->pnl_expected], JSON_PRETTY_PRINT) : 'no audit records';"
    ```
  - [ ] Navigate to admin audit page, verify complete trade lifecycle visible
  - **PASS**: Both tables exist, audit records created with P&L data
  - **FAIL**: Tables missing or no audit records

  **Evidence Required**:
  - [ ] Command output showing migration status
  - [ ] Database query output showing audit trail

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `feat(market-data): Add FX data provider (Twelve Data with 15-min cache)` | MarketDataService.php, TwelveDataService.php, tests | docker exec ... php artisan test |
| 2 | `feat(market-data): Add equity/commodity data provider (Twelve Data)` | MarketDataService.php, TwelveDataService.php, tests | docker exec ... php artisan test |
| 3 | `feat(market-data): Add data freshness indicator` | MarketDataService.php, tests | docker exec ... php artisan test |
| 4 | `fix(ai): Route bot signals through ai-connection-addon with ai_decisions logging` | BotSignalObserver.php, migration, AiDecision model, tests | docker exec ... php artisan test |
| 5 | `fix(ai): Route filter analysis through ai-connection-addon with ai_decision_id propagation` | FilterAnalysisJob.php, executionData contract, tests | docker exec ... php artisan test |
| 6 | `feat(safety): Enforce circuit breaker on executions` | ExecutionConnection.php, migration, ExecutionJob.php, tests | docker exec ... php artisan test |
| 7 | `feat(safety): Wire market status checker with CCXT DB support` | MarketStatusChecker.php, ExecutionJob.php, tests | docker exec ... php artisan test |
| 8 | `feat(paper-trading): Implement end-to-end paper trading with is_paper tracking` | InternalTrade.php migration, InternalBrokerService.php, FilterAnalysisJob.php, ExecutionJob.php, tests | docker exec ... php artisan test |
| 9 | `feat(position-sizing): Use real balance for sizing` | TradeDecisionEngine.php, tests | docker exec ... php artisan test |
| 10 | `feat(admin): Add circuit breaker UI` | Blade templates, controller methods | Manual verification |
| 11 | `feat(admin): Add AI decision logs UI` | Blade templates, controller methods | Manual verification |
| 12 | `feat(audit): Add post-trade reconciliation + AI linking` | Migration, model, controller, blade, tests | docker exec ... php artisan test + manual |

---

## Success Criteria

### Verification Commands
```bash
# All tests pass
docker exec 1Panel-php8-mrTy php artisan test

# Market data source indicators work
docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$svc = app(\App\Services\Trading\MarketDataService::class); \$data = \$svc->getForexData(5); echo \$data[0]['source'] ?? 'no source';"
docker exec 1Panel-php8-mrTy php artisan tinker --execute="\$svc = app(\App\Services\Trading\MarketDataService::class); \$data = \$svc->getStocksData(3); echo \$data[0]['source'] ?? 'no source';"

# Paper trading creates virtual positions with is_paper=1
# (verified via database query after bot execution)

# Circuit breaker blocks after failures
# (verified via manual test: 3 failures -> 4th blocked)

# Market status rejects orders when stale
# (verified via manual test with stale data)

# AI decisions logged to ai_decisions table
# (verified via database query)

# Execution logs linked to AI decisions via ai_decision_id
# (verified via database query)
```

### Final Checklist
- [ ] All P0 tasks completed (1-7)
- [ ] All P1 tasks completed (8-9)
- [ ] All P2 tasks completed (10-12, optional)
- [ ] All tests pass
- [ ] All asset classes have source indicator (real vs simulated)
- [ ] AI decisions route through ai-connection-addon (TradingBot flow only)
- [ ] AI metadata stored in ai_decisions table with correct schema mapping
- [ ] Execution logs linked to AI decisions via ai_decision_id
- [ ] Circuit breaker enforced with failure tracking
- [ ] Market status validated (both Redis and DB paths)
- [ ] Paper trading works end-to-end with is_paper tracking via InternalBrokerService
- [ ] Position sizing uses real balance
- [ ] Admin UI shows circuit breaker status
- [ ] AI decision logs visible in admin