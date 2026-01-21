# Trading Bot Refactoring Plan (TDD Approach) - VERSION 12

**Plan ID:** trading-bot-refactor-tdd-v12
**Created:** 2026-01-21
**Status:** Ready for Momus Review
**Approach**: Exploration-First, Conservative Fix

**Scope**: CRITICAL paper trading bug fix ONLY

---

## Context & Problem Statement

### Current Failure Mode
In `ExecutionJob.php` (~lines 80-91), when `is_paper_trading=true`, the job returns early without creating any trade record.

### Goal
Paper trades must create visible `InternalTrade` records with `is_paper=1`.

### DB Prefix Note
`main/config/database.php` sets `'prefix' => 'sp_'`. 
- Use base table name `internal_trades` in Laravel API
- Laravel automatically applies `sp_` prefix
- DO NOT use `sp_internal_trades` (would double-prefix)

---

## Phase 1: Verify Schema

### Task 1.1: Check InternalTrade Table Schema

**Command**:
```bash
docker exec 1Panel-php8-mrTy php artisan tinker --execute="
use App\Models\InternalTrade;
\$cols = \Illuminate\Support\Facades\Schema::getColumnListing('internal_trades');
echo 'Columns: ' . implode(', ', \$cols);
echo PHP_EOL;
echo 'Has is_paper: ' . (in_array('is_paper', \$cols) ? 'YES' : 'NO');
"
```

**Action**:
- If `is_paper` column EXISTS: Proceed to Phase 2
- If `is_paper` column MISSING: Create migration to add it (see Task 1.1a)

**Task 1.1a (if needed): Create is_paper Migration**
```bash
docker exec 1Panel-php8-mrTy php artisan make:migration add_is_paper_to_internal_trades_table --path=main/database/migrations
```

**Content**:
```php
public function up(): void
{
    Schema::table('internal_trades', function (Blueprint $table) {
        $table->boolean('is_paper')->default(false)->after('status');
    });
}

public function down(): void
{
    Schema::table('internal_trades', function (Blueprint $table) {
        $table->dropColumn('is_paper');
    });
}
```

**Run migration**:
```bash
docker exec 1Panel-php8-mrTy php artisan migrate --path=main/database/migrations
```

---

## Phase 2: Fix Paper Trading Bug

### Task 2.1: Fix ExecutionJob Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Location**: Lines ~80-91 (verified from codebase)

**Current Code**:
```php
// Validate market status before execution (skip in paper trading mode)
$isTestMode = $this->executionData['is_paper_trading'] ?? false;

// Paper trading mode: Use virtual positions created via InternalBrokerService
if ($isTestMode) {
    Log::warning('Paper trading mode: ...');
    return;  // <-- BUG: Early return
}
```

**Required Modification** (REPLACE lines 84-90):
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
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php | head -3
# Expected: Call after paper mode check
```

---

### Task 2.2: Modify createVirtualPosition to Pass isPaper=true

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Location**: Inside `createVirtualPosition()` method (~line 740)

**Current Code** (verified from codebase):
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

**Required Modification** (ADD true as 8th argument):
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

**Note**: `InternalBrokerService::placeOrder()` signature is:
```php
placeOrder(User $user, string $symbol, string $direction, float $quantity, float $currentPrice, ?float $slPrice = null, ?float $tpPrice = null, bool $isPaper = false): InternalTrade
```

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

class PaperTradingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create minimal ExecutionConnection for tests.
     * NOTE: paper mode is driven by ExecutionJob input, not connection model.
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

        // Verify - use base table name (Laravel handles sp_ prefix)
        $trade = InternalTrade::where('user_id', $user->id)
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
- ✅ Uses `RefreshDatabase` only
- ✅ Paper mode driven by `executionData['is_paper_trading']`, not connection model
- ✅ Uses base table name `internal_trades` (Laravel applies prefix)

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
# Expected: PASS (2 tests, 0 failures)
```

---

## Verification Commands

```bash
# Phase 1: Schema
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo in_array('is_paper', \\Illuminate\\Support\\Facades\\Schema::getColumnListing('internal_trades')) ? 'has is_paper' : 'MISSING is_paper';"

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

- [ ] `is_paper` column exists in `internal_trades` table (or was added via migration)
- [ ] ExecutionJob paper mode calls `createVirtualPosition()`
- [ ] `createVirtualPosition()` passes `true` to `placeOrder()` as `$isPaper`
- [ ] Test creates `InternalTrade` record with `is_paper=1`
- [ ] Test verifies: symbol, direction, is_paper fields
- [ ] Balance unchanged after paper trade
- [ ] No regressions in existing Execution tests

---

## File Path Reference

| Component | Path | Change |
|-----------|------|--------|
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Lines ~80-91, ~740 |
| InternalBrokerService | `main/app/Services/InternalBrokerService.php` | Lines ~19-28 (reference) |
| PaperTradingTest | `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` | New file |

---

## Change History

- **2026-01-21 v12**: Final iteration - addresses all Momus feedback
  - ✅ DB prefix handled (use base table name)
  - ✅ Schema verification with migration fallback
  - ✅ Removed is_paper_trading from ExecutionConnection (driven by job input)
  - ✅ Uses verified line numbers (80-91, 740, 19-28)
  - ✅ Acceptance criteria matches test assertions
  - ✅ Single trait (RefreshDatabase only)
