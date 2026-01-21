# Trading Bot Refactoring Plan (TDD Approach) - VERSION 10

**Plan ID:** trading-bot-refactor-tdd-v10
**Created:** 2026-01-21
**Status:** Ready for Momus Review
**Approach:** Test-Driven Development (TDD)
**Scope**: CRITICAL paper trading bug fix ONLY

---

## Context & Problem Statement

### Current Failure Mode
In `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`:
- Lines 84-90: When `is_paper_trading=true`, the job **returns early without executing**
- Paper traders see no feedback, no virtual positions created
- `createVirtualPosition()` method exists but is never called in paper mode

### Root Cause
```php
// Current code (lines 84-90)
if ($isTestMode) {
    Log::warning('Paper trading mode: ...');
    return;  // BUG: Early return, creates nothing!
}
```

### Fix Required
```php
// Required code (replace lines 84-90)
if ($isTestMode) {
    $result = $this->createVirtualPosition(...);  // Call existing method
    return;
}
```

### Done Looks Like
- ✅ `internal_trades` table gets new row with `is_paper=1` for paper trades
- ✅ Test verifies: `user_id`, `symbol`, `direction`, `status`, `is_paper=1`
- ✅ No breaking changes to live trading flow
- ✅ All existing tests pass

---

## Phase 1: Fix Paper Trading Bug

### Task 1.1: Read Current ExecutionJob Code (EXPLORATION)

**Command**:
```bash
docker exec 1Panel-php8-mrTy sed -n '70,100p' main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
```

**Record**:
- Line 79-81: `$isTestMode = $this->executionData['is_paper_trading'] ?? false;`
- Lines 84-90: Paper mode handling (the BUG)
- Line 730+: `createVirtualPosition()` method signature

**Note**: If exploration reveals different line numbers, adjust Task 1.2 accordingly.

---

### Task 1.2: Fix Paper Mode Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Current Code** (from exploration, expect ~lines 84-90):
```php
// Paper trading mode: Use virtual positions created via InternalBrokerService
if ($isTestMode) {
    Log::warning('Paper trading mode: ...');
    return;
}
```

**Required Modification** (REPLACE with):
```php
// Paper trading mode: Use InternalBrokerService to create virtual position
if ($isTestMode) {
    Log::info('Paper trading mode: Creating virtual position', [
        'symbol' => $this->executionData['symbol'] ?? 'unknown',
    ]);

    $result = $this->createVirtualPosition(
        $this->executionData['symbol'],
        $this->executionData['direction'],
        $this->executionData['quantity'],
        $this->executionData['entry_price'] ?? null,
        $this->executionData['stop_loss'] ?? null,
        $this->executionData['take_profit'] ?? null,
        $this->executionData['connection_id'] ?? null
    );

    if ($result['success']) {
        Log::info('Paper trade executed', ['trade_id' => $result['trade_id'] ?? null]);
    }
    return;
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php | head -3
# Expected: Call at ~line 87-95 (the new paper mode code)
```

---

### Task 1.3: Verify createVirtualPosition Passes isPaper=true

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Exploration**:
```bash
docker exec 1Panel-php8-mrTy grep -n -A5 "placeOrder" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php | head -15
```

**Expected Current State**:
```php
// Inside createVirtualPosition(), expect:
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit
    // NOTE: No 8th argument currently!
);
```

**Required Modification** (if 8th argument not present):
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit,
    true  // ADD: Force paper mode for paper trading
);
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Line with "true" as last argument
```

---

### Task 1.4: Create Paper Trading Integration Test

**File**: `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php`

**Content**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\Execution;

use Tests\TestCase;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use App\Models\InternalTrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaperTradingTest extends TestCase
{
    use RefreshDatabase, DatabaseMigrations;

    /**
     * Create minimal ExecutionConnection for paper trading tests.
     * Based on actual ExecutionConnection model requirements.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        return ExecutionConnection::create([
            'user_id' => $userId,
            'name' => 'Test Paper',
            'type' => 'crypto',
            'exchange_name' => 'binance',
            'credentials' => json_encode(['api_key' => 'test']),
            'status' => 'active',       // Required for canExecuteTrades()
            'is_active' => true,        // Required for canExecuteTrades()
            'is_paper_trading' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_paper_trade_creates_internal_trade_record(): void
    {
        // Setup: Create user with balance
        $user = User::factory()->create(['balance' => 100000]);

        // Setup: Create connection (required by ExecutionJob)
        $connection = $this->createPaperConnection($user->id);

        // Setup: Paper trading execution data
        $executionData = [
            'bot_id' => 1,
            'user_id' => $user->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'quantity' => 0.1,
            'entry_price' => 50000,
            'stop_loss' => 49000,
            'take_profit' => 52000,
            'is_paper_trading' => true,
            'connection_id' => $connection->id,
        ];

        // Execute: Run the job
        $job = new ExecutionJob($executionData);
        $job->handle();

        // Verify: InternalTrade was created with is_paper=1
        $trade = InternalTrade::where('user_id', $user->id)
            ->where('is_paper', true)
            ->latest()
            ->first();

        $this->assertNotNull($trade, 'Paper trade should be created');
        $this->assertEquals('BTC/USDT', $trade->symbol);
        $this->assertEquals('buy', $trade->direction);
        $this->assertEquals('open', $trade->status);
        $this->assertTrue($trade->is_paper);
    }

    public function test_paper_trade_does_not_affect_balance(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $connection = $this->createPaperConnection($user->id);
        $originalBalance = $user->balance;

        $executionData = [
            'user_id' => $user->id,
            'symbol' => 'ETH/USDT',
            'direction' => 'sell',
            'quantity' => 1.0,
            'is_paper_trading' => true,
            'connection_id' => $connection->id,
        ];

        $job = new ExecutionJob($executionData);
        $job->handle();

        $user->refresh();
        $this->assertEquals($originalBalance, $user->balance);
    }
}
```

**Key Points**:
- ✅ Extends `Tests\TestCase` (project standard)
- ✅ Uses `ExecutionConnection::create()` with model fields
- ✅ Sets `status='active'` and `is_active=true` (required for canExecuteTrades)
- ✅ Uses `type` and `exchange_name` (verified model fields)
- ✅ Verifies exact internal_trades fields

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
# Expected: PASS (2 tests, 0 failures)
```

---

## Verification Commands

```bash
# 1. Verify ExecutionJob was modified
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# 2. Verify placeOrder passes isPaper=true
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# 3. Run paper trading tests
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php

# 4. Verify no regressions
docker exec 1Panel-php8-mrTy php artisan test --filter="Execution"
```

---

## Expected Outcome

**Before Fix**:
```
User initiates paper trade
  ↓
ExecutionJob handles request
  ↓
is_paper_trading=true detected
  ↓
EARLY RETURN - no action taken
  ↓
User sees nothing happened ❌
```

**After Fix**:
```
User initiates paper trade
  ↓
ExecutionJob handles request
  ↓
is_paper_trading=true detected
  ↓
createVirtualPosition() called
  ↓
InternalBrokerService::placeOrder(..., isPaper=true)
  ↓
InternalTrade created with is_paper=1 ✅
  ↓
User sees virtual position in dashboard ✅
```

---

## File Path Reference

| Component | Path | Change |
|-----------|------|--------|
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Fix paper mode early return + pass isPaper=true |
| PaperTradingTest | `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` | New file |

---

## Change History

- **2026-01-21 v10**: Conservative approach - only critical paper trading fix
  - ✅ No factory assumptions (uses manual `create()`)
  - ✅ Verified field names (`type`, `exchange_name`, `status`, `is_active`)
  - ✅ Extends `Tests\TestCase` (project standard)
  - ✅ Clear acceptance criteria (exact fields verified)
  - ✅ No deferred complexity - single focused phase
