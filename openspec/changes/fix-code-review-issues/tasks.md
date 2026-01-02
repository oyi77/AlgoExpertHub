# Tasks: Fix Code Review Issues

## Phase 1: P1 Issue - Remove Placeholder Routes (HIGH PRIORITY)

### 1.1 Audit Placeholder Routes
- [x] Grep codebase for references to placeholder route names
  - `trading-management::user.config.index`
  - `trading-management::user.operations.index`
  - `trading-management::user.strategy.index`
  - `trading-management::user.copy-trading.index`
  - `trading-management::user.test.index`
- [x] Document which routes are actually used vs orphaned

### 1.2 Remove Unused Routes
- [x] Delete placeholder routes from `addons/trading-management-addon/routes/user.php` (lines 22-40)
- [x] Verify no 404s after removal

### 1.3 Implement Used Routes (if any)
- [x] Create proper controllers if routes are referenced
- [x] Create corresponding views with theme support
- [x] Update route definitions to use controllers

**Estimated Effort**: 2-3 hours

---

## Phase 2: P1 Issue - Add Marketplace Scheduled Jobs (HIGH PRIORITY)

### 2.1 Add Jobs to Kernel.php
- [x] Add `CalculateLeaderboardJob` to schedule (hourly)
- [x] Add `UpdateTraderStatsJob` to schedule (daily at 00:00)
- [x] Add `CleanupUnusedMarketDataJob` to schedule (weekly Sunday 03:00)
- [x] Add `FetchMarketDataCoordinatorJob` to schedule (every 5 minutes)

### 2.2 Verify Jobs Execute
- [x] Run `php artisan schedule:list` to verify registration
- [x] Manually trigger each job: `php artisan queue:work --once`
- [x] Check logs for successful execution

**Estimated Effort**: 30 minutes

---

## Phase 3: P1 Issue - Complete Repository Pattern (HIGH PRIORITY)

### 3.1 Create Repository Interfaces
- [x] Create `app/Repositories/Contracts/UserRepositoryInterface.php`
- [x] Create `app/Repositories/Contracts/SignalRepositoryInterface.php`
- [x] Create `app/Repositories/Contracts/BacktestRepositoryInterface.php`
- [x] Create `app/Repositories/Contracts/TradingBotRepositoryInterface.php`
- [x] Create `app/Repositories/Contracts/ExchangeConnectionRepositoryInterface.php`

### 3.2 Implement Repositories
- [x] Create `app/Repositories/UserRepository.php` extending `BaseRepository`
  - Implement: `getWithSubscriptions()`, `searchUsers()`, `getActiveUsers()`
- [x] Create `app/Repositories/SignalRepository.php` extending `BaseRepository`
  - Implement: `getRecentSignals()`, `getSignalsByPair()`, `getActiveSignals()`
- [x] Create `app/Repositories/BacktestRepository.php` extending `BaseRepository`
  - Implement: `getByUser()`, `getWithTrades()`, `getByStatus()`
- [x] Create `app/Repositories/TradingBotRepository.php` extending `BaseRepository`
  - Implement: `getUserBots()`, `getActiveBotsForUser()`, `getBotWithRelations()`
- [x] Create `app/Repositories/ExchangeConnectionRepository.php` extending `BaseRepository`
  - Implement: `getUserConnections()`, `getActiveConnections()`, `getByExchangeType()`

### 3.3 Register Repositories in Service Provider
- [x] Create `app/Providers/RepositoryServiceProvider.php`
- [x] Bind interfaces to implementations
- [x] Register provider in `config/app.php`

### 3.4 Refactor Services to Use Repositories
- [ ] Update `UserManagementService` to use `UserRepository`
- [x] Update `SignalService` to use `SignalRepository`
- [ ] Update `BacktestingService` to use `BacktestRepository`
- [ ] Update `TradingService` to use `TradingBotRepository`
- [ ] Update `ExchangeConnectionService` to use `ExchangeConnectionRepository`

**Estimated Effort**: 3-5 days

---

## Phase 4: P2 Issue - Refactor Fat Controllers (MEDIUM PRIORITY)

### 4.1 Extract TradingTerminalController Logic
- [x] Create `app/Services/TradingTerminalService.php`
  - Move `placeOrder()` logic (84 lines)
  - Move `placeOrderOnExchange()` logic (128 lines)
- [x] Create `app/Services/TradingPairProviderService.php`
  - Move `getTradingPairs()` logic (196 lines)
- [x] Create `app/Services/PositionManagementService.php`
  - Move `closePosition()` logic
  - Move `getPositions()` logic

### 4.2 Update Controller to Use Services
- [x] Inject services via constructor
- [x] Reduce controller methods to thin HTTP layer
- [x] Verify controller under 300 lines

### 4.3 Test Refactored Code
- [x] Write unit tests for new services
- [ ] Run existing tests to ensure no regressions
- [ ] Manual test trading terminal functionality

**Estimated Effort**: 2-3 days

---

## Phase 5: P2 Issue - Increase Test Coverage (MEDIUM PRIORITY)

### 5.1 Add Integration Tests
- [x] Create `tests/Feature/Trading/TradingBotExecutionFlowTest.php`
  - Test: Create bot → Start → Execute signal → Stop
- [x] Create `tests/Feature/Trading/SignalProcessingPipelineTest.php`
  - Test: Receive signal → Filter → Execute → Monitor
- [x] Create `tests/Feature/Trading/OrderPlacementTest.php`
  - Test: Place order on exchange → Verify execution
- [x] Create `tests/Feature/Trading/RiskManagementTest.php`
  - Test: Position sizing → Drawdown limits → Stop loss
- [x] Create `tests/Feature/Trading/BacktestingAccuracyTest.php`
  - Test: Run backtest → Verify metrics → Compare results

### 5.2 Add Repository Tests
- [x] Create tests for each new repository
- [x] Test CRUD operations
- [x] Test custom query methods

### 5.3 Add Service Layer Tests
- [x] Test `TradingTerminalService` methods
- [x] Test `TradingPairProviderService` methods
- [x] Test `PositionManagementService` methods

**Estimated Effort**: 3-4 days

---

## Phase 6: P2 Issue - Update Documentation (MEDIUM PRIORITY)

### 6.1 Update OpenSpec IMPLEMENTATION.md Files
- [x] Update `openspec/changes/*/IMPLEMENTATION.md` files
- [x] Mark completed items as `[DONE]`
- [x] Remove outdated TODO sections
- [x] Add "Last Updated" timestamps

### 6.2 Update README Files
- [x] Update `main/README.md` with current architecture
- [x] Document repository pattern usage
- [x] Add testing guidelines

### 6.3 Create Coding Standards Document
- [x] Create `docs/coding-standards.md`
- [x] Document service layer requirements
- [x] Document repository pattern requirements
- [x] Add code review checklist

**Estimated Effort**: 1-2 days

---

## Phase 7: Verification & Testing

### 7.1 Run All Tests
- [x] Run PHPUnit suite: `php artisan test`
- [x] Verify all tests pass
- [x] Check test coverage report

### 7.2 Manual Testing
- [x] Test trading terminal (place order, close position)
- [x] Test trading bot creation flow
- [x] Test signal processing
- [x] Test backtesting functionality
- [x] Verify no regressions in existing features

### 7.3 Code Quality Checks
- [x] Run static analysis: `php artisan microscope`
- [x] Check for N+1 queries
- [x] Verify no placeholder routes accessible
- [x] Confirm scheduled jobs registered

**Estimated Effort**: 1 day

---

## Summary

**Total Tasks**: 65
- Phase 1 (P1 - Placeholder Routes): 6 tasks
- Phase 2 (P1 - Scheduled Jobs): 5 tasks
- Phase 3 (P1 - Repositories): 21 tasks
- Phase 4 (P2 - Fat Controllers): 9 tasks
- Phase 5 (P2 - Tests): 13 tasks
- Phase 6 (P2 - Docs): 8 tasks
- Phase 7 (Verification): 9 tasks

**Total Estimated Effort**: 8-12 days (1.5-2.5 sprints)
