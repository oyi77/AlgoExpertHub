# Trading Bot Refactoring Plan (TDD Approach) - VERSION 9

**Plan ID:** trading-bot-refactor-tdd-v9
**Created:** 2026-01-21
**Status:** Ready for Momus Review
**Approach:** Test-Driven Development (TDD)

**Scope**: Focus on CRITICAL paper trading bug fix + minimal infrastructure. Dynamic config and MarketRouter deferred to Phase 2.

---

## Context & Problem Statement

### Current Failure Mode
The trading bot's paper trading mode in `ExecutionJob.php` (lines 79-91) has a critical bug: when `is_paper_trading=true`, the job returns early without creating any virtual position record. Users testing strategies receive no feedback.

### Done Looks Like (Phase 1 - CRITICAL)
- ✅ Paper trades create visible `InternalTrade` records with `is_paper=1`
- ✅ Fix verified with integration tests
- ✅ No breaking changes to existing functionality

### Done Looks Like (Phase 2 - Future)
- Dynamic configuration reload without bot restart
- Multi-market support via unified MarketRouter interface

---

## Phase 1: Fix Paper Trading (CRITICAL)

### Task 1.1: Verify TradingBot Model Fields

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Required Exploration**:
```bash
# Check fillable fields
docker exec 1Panel-php8-mrTy grep -n "fillable" main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php

# Check if HasFactory already present
docker exec 1Panel-php8-mrTy grep -n "HasFactory\|newFactory" main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php
```

**Based on exploration, UPDATE TradingBot model**:
```php
// If HasFactory present but newFactory() missing, add:
protected static function newFactory(): Factory
{
    return \Addons\TradingManagement\Modules\TradingBot\Database\Factories\TradingBotFactory::new();
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo class_exists('Addons\\TradingManagement\\Modules\\TradingBot\\Database\\Factories\\TradingBotFactory');"
# Expected: true (or class not found if factory doesn't exist yet)
```

---

### Task 1.2: Create TradingBotFactory (Deferred - Use Manual Creation for Now)

For Phase 1, we'll create test bots manually to avoid factory discovery issues:

**In tests, use**:
```php
// Instead of TradingBot::factory()->create()
$bot = TradingBot::create([
    'user_id' => $user->id,
    'name' => 'Test Bot',
    'status' => 'created',
    'is_paper_trading' => true,
]);
```

**Phase 2 will address factory creation after model structure verified**

---

### Task 1.3: Verify ExecutionConnection Model Fields

**File**: `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php`

**Required Exploration**:
```bash
# Check fillable fields
docker exec 1Panel-php8-mrTy grep -n "fillable" main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php

# Check canExecuteTrades method
docker exec 1Panel-php8-mrTy grep -n -A10 "canExecuteTrades" main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php
```

**Note**: Record exact required fields for test setup.

---

### Task 1.4: Create ExecutionConnection Factory (Deferred)

For Phase 1, create connections manually in tests using verified fields:

**In tests, use**:
```php
$connection = ExecutionConnection::create([
    'user_id' => $user->id,
    'name' => 'Test Connection',
    'exchange_type' => 'crypto',
    'exchange_name' => 'binance',
    // ... required fields from exploration
    'status' => 'active',  // Required for canExecuteTrades
    'is_active' => true,   // Required for canExecuteTrades
]);
```

---

### Task 1.5: Fix ExecutionJob Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Current Code** (lines 79-91, verified from codebase):
```php
// Validate market status before execution (skip in paper trading mode)
$isTestMode = $this->executionData['is_paper_trading'] ?? false;

// Paper trading mode: Use virtual positions created via InternalBrokerService
if ($isTestMode) {
    Log::warning('Paper trading mode: ...');
    return;  // <-- PROBLEM: Early return, doesn't execute
}
```

**Required Modification** (REPLACE lines 84-90):
```php
// Paper trading mode: Use InternalBrokerService with isPaper=true
if ($isTestMode) {
    Log::info('Paper trading mode: Creating virtual position', [
        'symbol' => $this->executionData['symbol'] ?? 'unknown',
        'direction' => $this->executionData['direction'] ?? 'unknown',
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
        Log::info('Paper trade executed successfully', [
            'trade_id' => $result['trade_id'] ?? null,
            'is_paper' => true,
        ]);
    } else {
        Log::error('Paper trade failed', $result);
    }
    return;
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php | head -5
# Expected: Call to createVirtualPosition around line 85-95
```

---

### Task 1.6: Modify createVirtualPosition() to Pass isPaper=true

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Location**: Inside `createVirtualPosition()` method, find the `placeOrder()` call

**Current Code** (verified):
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit
    // $isPaper NOT passed
);
```

**Required Modification**:
```php
$internalBrokerService->internalTrade = $placeOrder(
    $user,
    $symbol,
    $direction,
    $entryPrice ?? $quantity,
    0,
    $stopLoss,
    $takeProfit,
    true  // Force paper mode for paper trading
);
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Line with true as 8th argument
```

---

### Task 1.7: Create Paper Trading Integration Test

**File**: `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php`

**Exact Content** (using manual creation to avoid factory issues):
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\Execution;

use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use App\Models\InternalTrade;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaperTradingTest extends TestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * Create minimal ExecutionConnection for paper trading tests.
     * Fields based on actual ExecutionConnection model requirements.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        return ExecutionConnection::create([
            'user_id' => $userId,
            'name' => 'Test Paper Connection',
            'exchange_type' => 'crypto',
            'exchange_name' => 'binance',
            'credentials' => json_encode(['api_key' => 'test']),
            'status' => 'active',        // Required for canExecuteTrades
            'is_active' => true,         // Required for canExecuteTrades
            'is_paper_trading' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_execution_job_creates_paper_trade_when_paper_mode_enabled(): void
    {
        // Setup: Create user with sufficient balance
        $user = User::factory()->create(['balance' => 100000]);

        // Setup: Create execution connection
        $connection = $this->createPaperConnection($user->id);

        // Setup: Create execution data for paper trading
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

        $job = new ExecutionJob($executionData);
        $job->handle();

        // Verify: Paper trade was created with is_paper=1
        $paperTrade = InternalTrade::where('user_id', $user->id)
            ->where('is_paper', true)
            ->latest()
            ->first();

        $this->assertNotNull($paperTrade, 'Paper trade should be created');
        $this->assertEquals('BTC/USDT', $paperTrade->symbol);
        $this->assertEquals('buy', $paperTrade->direction);
        $this->assertTrue($paperTrade->is_paper, 'Trade must be marked as paper');
    }

    public function test_paper_trade_is_marked_in_database(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $connection = $this->createPaperConnection($user->id);

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

        $this->assertDatabaseHas('internal_trades', [
            'user_id' => $user->id,
            'symbol' => 'ETH/USDT',
            'is_paper' => true,
        ]);
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
# Expected: PASS (2 tests, 0 failures)
```

---

## Phase 2: Infrastructure & Future Features (Deferred)

### Task 2.1: Create TradingBotFactory (After Task 1.1 verification)

**Directory**: `main/addons/trading-management-addon/Modules/TradingBot/Database/Factories/`

**Note**: Created AFTER verifying TradingBot model fillable fields

**Content**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Database\Factories;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradingBotFactory extends Factory
{
    protected $model = TradingBot::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'status' => 'created',
            'is_paper_trading' => true,
            'position_monitoring_interval' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function running(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
        ]);
    }
}
```

---

### Task 2.2: Create MarketRouter Service (After Task 1.1 verification)

**File**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php`

**Note**: Created AFTER verifying project patterns

**Content**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MarketRouter
{
    public function isMarketOpen(string $marketType, ?string $symbol = null): bool
    {
        $validator = Validator::make(
            ['market_type' => $marketType],
            ['market_type' => 'in:crypto,fx,stock,commodity,other']
        );

        if ($validator->fails()) {
            return $this->handleUnknownMarket($marketType);
        }

        return match ($marketType) {
            'crypto' => true,
            'fx' => $this->isForexOpen($symbol),
            'stock' => $this->isStockOpen(),
            'commodity' => $this->isCommodityOpen(),
            'other' => $this->handleUnknownMarket($marketType),
        };
    }

    private function isForexOpen(?string $symbol): bool
    {
        $now = now()->utc();
        $hour = $now->hour;
        $day = $now->dayOfWeek;

        if ($day === Carbon::SATURDAY) return false;
        if ($day === Carbon::SUNDAY && $hour < 22) return false;
        if ($hour >= 21 && $hour < 22) return false;

        return true;
    }

    private function isStockOpen(): bool
    {
        $now = now()->timezone('America/New_York');
        $day = $now->dayOfWeek;
        if ($day === Carbon::SATURDAY || $day === Carbon::SUNDAY) return false;
        return $now->hour >= 9 && $now->hour < 16;
    }

    private function isCommodityOpen(): bool
    {
        $now = now()->utc();
        return $now->dayOfWeek !== Carbon::SATURDAY;
    }

    private function handleUnknownMarket(string $marketType): bool
    {
        Log::warning('Unknown market type, defaulting to closed', ['market_type' => $marketType]);
        return false;
    }
}
```

---

## Queue Prerequisites (Documented)

### Required Configuration

**1. Queue Connection for Listener**:
- `BotConfigListenerJob` uses `$this->onConnection('redis')`
- Requires `QUEUE_CONNECTION=redis` in `.env`
- Requires Redis server running

**2. Listener Queue Worker**:
```bash
# Start worker consuming 'listeners' queue
docker exec 1Panel-php8-mrTy php artisan queue:work redis --queue=listeners --sleep=3
```

**3. PHPUnit Queue Configuration**:
- Current: `QUEUE_CONNECTION=sync` in `phpunit.xml`
- Tests run synchronously (no actual queue)
- Integration tests verify behavior, not async delivery

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep "QUEUE_CONNECTION" main/phpunit.xml
# Expected: sync (for tests) or redis (for runtime)
```

---

## All Verification Commands

```bash
# Phase 1: Paper Trading Fix (CRITICAL)
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# Phase 2: Factories (after verification)
docker exec 1Panel-php8-mrTy ls -la main/addons/trading-management-addon/Modules/TradingBot/Database/Factories/
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::factory()->create()->id;"

# Queue Prerequisites
docker exec 1Panel-php8-mrTy grep "QUEUE_CONNECTION" main/phpunit.xml
```

---

## File Path Reference

| Component | Path | Status |
|-----------|------|--------|
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Exists, needs isPaper fix |
| InternalBrokerService | `main/app/Services/InternalBrokerService.php` | Verified exists |
| ExecutionConnection | `main/addons/trading-management-addon/Modules/Execution/Models/ExecutionConnection.php` | Exists, needs verification |
| TradingBot Model | `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php` | Exists, needs newFactory() |
| PaperTradingTest | `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` | New file |

---

## Change History

- **2026-01-21 v9**: Simplified to focus on CRITICAL paper trading fix
  - ✅ Uses manual creation in tests to avoid factory discovery issues
  - ✅ Deferred factory creation to Phase 2 after model verification
  - ✅ Documented queue prerequisites for listener
  - ✅ Deferred MarketRouter to Phase 2
  - ✅ Removed assumptions about model fillable fields
