# Decisions - AI Bot Trading Completion

## Migration Decisions

### Schema Migration Priority (Decided 2025-01-15)
- **Decision**: Migrations M1, M2, M3 must be created and run FIRST before any implementation tasks
- **Rationale**: Tasks 4-10 depend on these schema changes. Implementing code first would cause failures.
- **Action**: Create migrations directory and run migrations before starting Phase 1-5 tasks.

## Architectural Decisions

### Twelve Data Provider Selection (Decided by User - in plan)
- **Provider**: Twelve Data (https://twelvedata.com/)
- **Reasoning**:
  - Free tier: 800 credits/day (not 100/day)
  - Credit-based system: Most endpoints cost 1 credit
  - Comprehensive coverage: FX, stocks, indices, commodities, crypto
  - Websocket support for real-time data
- **Rate Limit Strategy**:
  - Landing Page Cache: 15-minute TTL (MarketDataService::$cacheTtl = 900)
  - Scheduled Background Refresh: Run every 15 minutes via Laravel Scheduler
  - Expected Usage: ~96 credits/day (12 asset classes × 8 refreshes)
  - Safe margin: ~88% headroom

### Paper Trading Flag Normalization (Decided 2025-01-15)
- **Problem**: Code uses `test_mode` in 7 files, should use `is_paper_trading`
- **Decision**: Normalize all references to `is_paper_trading` during Tasks 4, 5, 8
- **Affected Files**:
  1. FilterAnalysisJob.php:50
  2. ExecutionJob.php:80
  3. RiskManagementJob.php:137,151,156
  4. TradeDecisionEngine.php:34,45
  5. TechnicalAnalysisService.php:201
  6. MarketStatusChecker.php:141 (output only)
  7. TestFilterStrategySeeder.php:29
- **Contract**:
  - Bot model flag: `bot->is_paper_trading` (authoritative)
  - InternalTrade tracking: `is_paper` column
  - Pipeline flow:
    1. TradingBotStrategyWorker sets `decision['is_paper_trading']`
    2. FilterAnalysisJob checks `decision['is_paper_trading']`
    3. ExecutionJob checks `executionData['is_paper_trading']`
    4. createVirtualPosition() sets `is_paper = 1` when flag is true

### Fail-Open AI Behavior (Decided by User - in plan)
- **Definition**: "Fail-open" means trading pipeline continues without AI veto
- **Behavior**:
  - When AI unavailable:
    - Create `AiDecision` record with `action='HOLD'`, `confidence=0`
    - `reasoning='AI unavailable - fail-open'`
    - Execution pipeline proceeds as if AI said "no opinion"
    - Downstream filters/risk checks still apply (circuit breaker, market status)
  - NOT automatically execute trades - pipeline continues but AI has not approved
- **Record**: Always create `ai_decisions` record to track attempt (even when AI fails)

## Trade-offs Considered

### Circuit Breaker Implementation
- **Option A**: Per-connection circuit breaker (CHOSEN)
  - Pros: Fine-grained control, easier to manage
  - Cons: More configuration required
- **Option B**: Global circuit breaker (NOT CHOSEN)
  - Pros: Simpler to implement
  - Cons: Single connection failures affect all trading

### AI Decision Storage
- **Option A**: Create comprehensive audit trail (CHOSEN)
  - Stores: prompt hash, confidence, reasoning, model used, analysis data
  - Pros: Full transparency, debugging support
  - Cons: Larger storage requirements
- **Option B**: Minimal tracking (NOT CHOSEN)
  - Stores: Only action and decision ID
  - Pros: Lightweight
  - Cons: Limited debugging capability

