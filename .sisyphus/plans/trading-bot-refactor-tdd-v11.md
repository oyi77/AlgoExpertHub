# Trading Bot Refactoring Plan (TDD Approach) - VERSION 11

**Plan ID:** trading-bot-refactor-tdd-v11
**Created:** 2026-01-21
**Status:** Ready for Momus Review
**Approach**: Exploration-First, Conservative Fix

**Scope**: CRITICAL paper trading bug fix ONLY

---

## Context & Problem Statement

### Current Failure Mode
In `ExecutionJob.php`, when `is_paper_trading=true`, the job returns early without creating any trade record.

### Goal
Paper trades must create visible `InternalTrade` records with `is_paper=1`.

---

## Phase 1: Exploration (Must Complete First)

### Task 1.1: Explore ExecutionJob Paper Mode Code

**Command**:
```bash
docker exec 1Panel-php8-mrTy sed -n '79,95p' main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
```

**Record**:
- Line ___: `$isTestMode = ...`
- Lines ___: Paper mode handling (early return)
- Line ___: `createVirtualPosition()` exists

**Action**: Record exact line numbers for Task 1.2

---

### Task 1.2: Explore createVirtualPosition Method

**Command**:
```bash
docker exec 1Panel-php8-mrTy grep -n "placeOrder" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php | head -5
```

**Record**:
- Line ___: `placeOrder()` call in `createVirtualPosition()`
- How many arguments currently passed? ___

---

### Task 1.3: Explore InternalBrokerService Signature

**Command**:
```bash
docker exec 1Panel-php8-mrTy sed -n '1,35p' main/app/Services/InternalBrokerService.php
```

**Record**:
- Line ___: `placeOrder()` method signature
- Is `$isPaper` parameter present? YES/NO
- Position of `$isPaper`: ___ (e.g., 8th parameter)

---

### Task 1.4: Explore InternalTrade Table Schema

**Commands**:
```bash
# Check if internal_trades table has is_paper column
docker exec 1Panel-php8-mrTy php artisan tinker --execute="
use App\Models\InternalTrade;
\$cols = \Illuminate\Support\Facades\Schema::getColumnListing('internal_trades');
echo 'internal_trades columns: ' . implode(', ', \$cols);
"

# Check if sp_internal_trades table exists
docker exec 1Panel-php8-mrTy php artisan tinker --execute="
try {
    \$cols = \Illuminate\Support\Facades\Schema::getColumnListing('sp_internal_trades');
    echo 'sp_internal_trades columns: ' . implode(', ', \$cols);
} catch (\Exception \$e) {
    echo 'sp_internal_trades does not exist';
}
"
```

**Record**:
- Which table has `is_paper` column? ___
- Table to use for assertions: ___

---

### Task 1.5: Explore ExecutionConnection Model

**Command**:
```bash
docker exec 1Panel-php8-mrTy sed -n '1,50p' main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php
```

**Record**:
- `$fillable` fields: ___
- `$casts` fields: ___
- Does `is_paper_trading` exist in fillable? YES/NO
- Required for `canExecuteTrades()`: `status='active'`, `is_active=true`

---

## Phase 2: Fix Paper Trading Bug

### Task 2.1: Fix ExecutionJob Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Target**: Replace paper mode block (from Task 1.1)

**After Task 1.1, UPDATE with actual line numbers and code**:

**Code to use** (update line numbers after exploration):
```php
// Paper trading mode: Create virtual position
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
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Call to createVirtualPosition after paper mode check
```

---

### Task 2.2: Modify createVirtualPosition to Pass isPaper=true

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Target**: `placeOrder()` call inside `createVirtualPosition()` (from Task 1.2)

**After Task 1.2 and 1.3, UPDATE**:

Current (Task 1.2):
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit
);
```

Update to:
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit,
    true  // Force paper mode
);
```

**Note**: If Task 1.3 shows `$isPaper` is not the 8th parameter, adjust position accordingly.

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Line with "true" as last argument
```

---

## Phase 3: Create Integration Test

### Task 3.1: Create Test Directory

**Command**:
```bash
mkdir -p main/tests/Feature/Addons/TradingManagement/Execution
```

**Verification**:
```bash
ls -la main/tests/Feature/Addons/TradingManagement/Execution/
# Expected: Directory exists
```

---

### Task 3.2: Create PaperTradingTest

**File**: `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php`

**Content** (using fields from Task 1.5):
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

class PaperTradingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create minimal ExecutionConnection for tests.
     * Fields from Task 1.5 exploration.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        // NOTE: Update fields based on Task 1.5 $fillable exploration
        $fields = [
            'user_id' => $userId,
            'name' => 'Test Paper',
            'type' => 'crypto',              // From Task 1.5
            'exchange_name' => 'binance',    // From Task 1.5
            'credentials' => json_encode(['api_key' => 'test']),
            'status' => 'active',            // Required for canExecuteTrades()
            'is_active' => true,             // Required for canExecuteTrades()
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // REMOVE is_paper_trading if not in fillable (Task 1.5)
        // if (in_array('is_paper_trading', (new \ReflectionClass(ExecutionConnection::class))->getDefaultProperties()['fillable'] ?? [])) {
        //     $fields['is_paper_trading'] = true;
        // }
        
        return ExecutionConnection::create($fields);
    }

    public function test_paper_trade_creates_internal_trade_record(): void
    {
        // Setup
        $user = User::factory()->create(['balance' => 100000]);
        $connection = $this->createPaperConnection($user->id);

        // Paper trading execution data
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

        // Execute
        $job = new ExecutionJob($executionData);
        $job->handle();

        // Verify - use table from Task 1.4
        $table = 'internal_trades';  // Update based on Task 1.4
        
        $trade = \Illuminate\Support\Facades\DB::table($table)
            ->where('user_id', $user->id)
            ->where('is_paper', true)
            ->latest()
            ->first();

        $this->assertNotNull($trade, 'Paper trade should be created');
        $this->assertEquals('BTC/USDT', $trade->symbol);
        $this->assertEquals('buy', $trade->direction);
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
- ✅ Uses `RefreshDatabase` only (no DatabaseMigrations)
- ✅ Notes to update based on exploration results
- ✅ Uses table from Task 1.4 exploration

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
# Expected: PASS (2 tests, 0 failures)
```

---

## Verification Commands

```bash
# Phase 1: Exploration
# Run all Tasks 1.1-1.5 and record results

# Phase 2: Fix
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# Phase 3: Test
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php

# Regression
docker exec 1Panel-php8-mrTy php artisan test --filter="Execution"
```

---

## Acceptance Criteria

After execution, verify ALL:

- [ ] Task 1.1-1.5 completed with recorded line numbers/fields
- [ ] ExecutionJob paper mode calls `createVirtualPosition()`
- [ ] `createVirtualPosition()` passes `true` to `placeOrder()` as `$isPaper`
- [ ] Test creates `InternalTrade` record with `is_paper=1`
- [ ] Test verifies: symbol, direction, status, is_paper fields
- [ ] Balance unchanged after paper trade
- [ ] No regressions in existing Execution tests

---

## File Path Reference

| Component | Path | Change |
|-----------|------|--------|
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Fix paper mode |
| PaperTradingTest | `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` | New file |

---

## Change History

- **2026-01-21 v11**: Exploration-first approach
  - ✅ Explicit exploration tasks before code changes
  - ✅ Placeholders for actual line numbers/fields
  - ✅ Notes to update code based on exploration
  - ✅ Test directory creation explicit
  - ✅ Uses only `RefreshDatabase` trait
  - ✅ Honest about table name ambiguity with remediation
