# Trading Bot Refactoring Plan (TDD Approach) - VERSION 8

**Plan ID:** trading-bot-refactor-tdd-v8
**Created:** 2026-01-21
**Status:** Ready for Momus Review
**Approach:** Test-Driven Development (TDD)

---

## Context & Problem Statement

### Current Failure Mode
The trading bot's paper trading mode in `ExecutionJob.php` (lines 79-91) has a critical bug: when `is_paper_trading=true`, the job returns early without creating any virtual position record. Users testing strategies receive no feedback, making the demo mode unusable.

### Done Looks Like
- ✅ Paper trades create visible `InternalTrade` records with `is_paper=1`
- ✅ Dynamic configuration changes reload without restarting the bot
- ✅ Multi-market support via unified MarketRouter interface
- ✅ 80%+ unit test coverage for trading bot components (measured via `--min=80`)

---

## Phase 0: Infrastructure Setup

### Task 0.1: Create TradingBot Factory Directory

**Directory**: `main/addons/trading-management-addon/Modules/TradingBot/database/factories/`

**Steps**:
```bash
mkdir -p main/addons/trading-management-addon/Modules/TradingBot/database/factories
mkdir -p main/addons/trading-management-addon/Modules/MarketRouter/Services
```

**Verification**:
```bash
ls -la main/addons/trading-management-addon/Modules/TradingBot/database/
# Expected: factories/ directory exists
```

---

### Task 0.2: Update TradingBot Model for Factory Discovery

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Current State** (verified from codebase):
```php
<?php

namespace Addons\TradingManagement\Modules\TradingBot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBot extends Model
{
    use HasFactory;
    // ... existing code
}
```

**Required Addition** (ADD to model class):
```php
use Illuminate\Database\Eloquent\Factories\Factory;
use Addons\TradingManagement\Modules\TradingBot\Database\Factories\TradingBotFactory;

/**
 * Factory class for this model.
 * Required for Laravel to discover addon model factories.
 */
protected static function newFactory(): Factory
{
    return TradingBotFactory::new();
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::factory()->create()->id;"
# Expected: Creates bot and outputs ID (factory properly wired)
```

**Key Fix**: ✅ Correct namespace `Addons\TradingManagement\Modules\TradingBot\Models\TradingBot`
**Key Fix**: ✅ Added `use Illuminate\Database\Eloquent\Factories\Factory;` import

---

### Task 0.3: Create TradingBotFactory

**File**: `main/addons/trading-management-addon/Modules/TradingBot/database/factories/TradingBotFactory.php`

**Exact Content**:
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
            'description' => $this->faker->sentence,
            'status' => 'created',
            'is_paper_trading' => true,
            'position_monitoring_interval' => 5,
            'worker_last_heartbeat' => null,
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

    public function paused(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function stopped(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
        ]);
    }
}
```

**Key Fix**: ✅ Added `declare(strict_types=1);` per project conventions

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan tinker --execute="
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
\$bot = TradingBot::factory()->create();
echo 'Created bot ID: ' . \$bot->id;
echo ' Status: ' . \$bot->status;
"
# Expected: Creates bot with status 'created'
```

---

### Task 0.4: Update phpunit.xml for Addon Coverage

**File**: `main/phpunit.xml`

**Current Structure** (verified from codebase):
```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
</coverage>
```

**Required Change**:
```xml
    <include>
        <directory suffix=".php">./app</directory>
        <directory suffix=".php">./addons/trading-management-addon/Modules</directory>
    </include>
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test --coverage --min=80 --filter=TradingBot 2>&1 | tail -20
# Should show coverage metrics for TradingBot components
```

---

## Phase 1: Testing Infrastructure

### Task 1.1: Create TradingBotTestCase Base Class

**File**: `main/tests/Feature/Addons/TradingManagement/TradingBotTestCase.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base test case for Trading Bot addon tests.
 * 
 * Provides:
 * - Application bootstrapping via CreatesApplication
 * - Database transactions via RefreshDatabase (real DB)
 * - TradingBot factory helper methods
 */
abstract class TradingBotTestCase extends TestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * Create a mock TradingBot with minimal required fields.
     */
    protected function createMockTradingBot(array $overrides = []): TradingBot
    {
        return TradingBot::factory()->create(array_merge([
            'status' => 'created',
            'is_paper_trading' => true,
        ], $overrides));
    }

    /**
     * Create a running TradingBot for integration tests.
     */
    protected function createRunningBot(): TradingBot
    {
        return TradingBot::factory()->running()->create();
    }

    /**
     * Create a paused TradingBot.
     */
    protected function createPausedBot(): TradingBot
    {
        return TradingBot::factory()->paused()->create();
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/TradingBotTestCase.php
# Expected: "No tests executed" (abstract class with no tests)
```

---

### Task 1.2: Create TradingBot Factory Test

**File**: `main/tests/Feature/Addons/TradingManagement/TradingBot/FactoryTest.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\TradingBot;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Tests\Feature\Addons\TradingManagement\TradingBotTestCase;

class FactoryTest extends TradingBotTestCase
{
    public function test_factory_creates_trading_bot_with_defaults(): void
    {
        $bot = TradingBot::factory()->create();

        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('created', $bot->status);
        $this->assertTrue($bot->is_paper_trading);
        $this->assertNull($bot->worker_last_heartbeat);
    }

    public function test_factory_creates_user_relationship(): void
    {
        $bot = TradingBot::factory()->create();

        $this->assertInstanceOf(\App\Models\User::class, $bot->user);
        $this->assertNotNull($bot->user->id);
    }

    public function test_running_state_sets_status_to_running(): void
    {
        $bot = TradingBot::factory()->running()->create();

        $this->assertEquals('running', $bot->status);
    }

    public function test_paused_state_sets_status_to_paused(): void
    {
        $bot = TradingBot::factory()->paused()->create();

        $this->assertEquals('paused', $bot->status);
    }

    public function test_stopped_state_sets_status_to_stopped(): void
    {
        $bot = TradingBot::factory()->stopped()->create();

        $this->assertEquals('stopped', $bot->status);
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/TradingBot/FactoryTest.php
# Expected: PASS (5 tests, 0 failures)
```

---

## Phase 2: Fix Paper Trading (CRITICAL)

### Task 2.1: Fix ExecutionJob Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Current Code** (lines 79-91, VERIFIED):
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

    // VERIFICATION: Ensure paper trade was created with is_paper=1
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
docker exec 1Panel-php8-mrTy grep -n -A20 "Paper trading mode:" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Updated code with createVirtualPosition call
```

---

### Task 2.2: Modify createVirtualPosition() to Pass isPaper=true

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Location**: Around line 741 (inside `createVirtualPosition()` method)

**Current Code** (VERIFIED):
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit
    // NOTE: $isPaper NOT being passed currently!
);
```

**Required Modification** (ADD $isPaper parameter):
```php
$internalTrade = $internalBrokerService->placeOrder(
    $user,
    $symbol,
    $direction,
    $quantity,
    $entryPrice ?? 0,
    $stopLoss,
    $takeProfit,
    true  // <-- ADD: Force paper mode for paper trading
);
```

**Note**: The `placeOrder()` method signature is:
```php
placeOrder(User $user, string $symbol, string $direction, float $quantity, float $currentPrice, ?float $slPrice = null, ?float $tpPrice = null, bool $isPaper = false): InternalTrade
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Line with true passed as 8th argument
```

---

### Task 2.3: Create Paper Trading Integration Test

**File**: `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\Execution;

use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExecutionConnection;
use App\Models\User;
use App\Models\InternalTrade;
use Tests\Feature\Addons\TradingManagement\TradingBotTestCase;

class PaperTradingTest extends TradingBotTestCase
{
    /**
     * Helper to create minimal ExecutionConnection for paper trading tests.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        return ExecutionConnection::factory()->create([
            'user_id' => $userId,
            'is_paper_trading' => true,
            'is_active' => true,
        ]);
    }

    public function test_execution_job_creates_paper_trade_when_paper_mode_enabled(): void
    {
        // Setup: Create user with sufficient balance for margin check
        $user = User::factory()->create(['balance' => 100000]);

        // Setup: Create execution connection for paper trading
        $connection = $this->createPaperConnection($user->id);

        // Setup: Create minimal execution data for paper trading
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

        // Execute the job
        $job->handle();

        // VERIFICATION: Check that a paper trade was created with is_paper=1
        $paperTrade = InternalTrade::where('user_id', $user->id)
            ->where('is_paper', true)
            ->latest()
            ->first();

        $this->assertNotNull($paperTrade, 'Paper trade should be created');
        $this->assertEquals('BTC/USDT', $paperTrade->symbol);
        $this->assertEquals('buy', $paperTrade->direction);
        $this->assertEquals(0.1, $paperTrade->quantity);
        $this->assertEquals('open', $paperTrade->status);
        $this->assertTrue($paperTrade->is_paper, 'Trade must be marked as paper');
    }

    public function test_paper_trade_is_marked_as_paper_in_database(): void
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

        // Verify is_paper flag is set in database
        $this->assertDatabaseHas('internal_trades', [
            'user_id' => $user->id,
            'symbol' => 'ETH/USDT',
            'is_paper' => true,
        ]);
    }

    public function test_paper_trade_does_not_affect_user_balance(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $connection = $this->createPaperConnection($user->id);
        $originalBalance = $user->balance;

        $executionData = [
            'user_id' => $user->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'quantity' => 0.1,
            'entry_price' => 50000,
            'is_paper_trading' => true,
            'connection_id' => $connection->id,
        ];

        $job = new ExecutionJob($executionData);
        $job->handle();

        // Balance should remain unchanged for paper trades
        $user->refresh();
        $this->assertEquals($originalBalance, $user->balance);
    }
}
```

**Key Fixes from Momus Feedback**:
- ✅ Creates `ExecutionConnection` via factory in test setup
- ✅ Uses `$connection->id` in execution data (not hardcoded `1`)
- ✅ Verifies `is_paper=1` in database assertions
- ✅ Verifies balance unchanged (paper trading characteristic)

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
# Expected: PASS (3 tests, 0 failures)
```

---

## Phase 3: Dynamic Configuration

### Task 3.1: Create BotConfigListenerJob with Pub/Sub Design

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Listens for bot configuration changes via Redis pub/sub.
 * 
 * Design: Uses non-blocking polling approach with Redis lists (not pub/sub channels).
 * This allows the job to check for stop signals and exit gracefully.
 */
class BotConfigListenerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $botId;
    public int $timeout = 60; // Poll for 60 seconds max

    /**
     * Queue configuration for listener jobs.
     * Uses dedicated 'listeners' queue to avoid starving normal jobs.
     */
    public function __construct(int $botId)
    {
        $this->botId = $botId;
        $this->onQueue('listeners');
        $this->onConnection('redis');
    }

    public function handle(): void
    {
        $channel = "bot:{$this->botId}:config";
        $redis = Redis::connection()->client();
        $startTime = time();

        Log::info('BotConfigListenerJob: Starting config listener', [
            'bot_id' => $this->botId,
            'channel' => $channel,
        ]);

        // Non-blocking poll loop using Redis lists (LPUSH to publish, BRPOP to consume)
        // This is consistent: both publish() and brpop() work with lists
        while ((time() - $startTime) < $this->timeout) {
            // Check for config update messages (blocking pop with 1s timeout)
            $message = $redis->brPop([$channel], 1);

            if ($message !== null && isset($message[1])) {
                $data = json_decode($message[1], true);
                
                if (($data['event'] ?? null) === 'config_updated') {
                    // Invalidate config cache to force refresh
                    Cache::forget("bot_config:{$this->botId}");
                    Log::info('Bot config cache invalidated via listener', [
                        'bot_id' => $this->botId,
                    ]);
                }
                
                if (($data['event'] ?? null) === 'stop_listening') {
                    Log::info('BotConfigListenerJob: Received stop signal', [
                        'bot_id' => $this->botId,
                    ]);
                    break;
                }
            }

            // Check if bot is still in valid state
            $bot = TradingBot::find($this->botId);
            if (!$bot || !in_array($bot->status, ['running', 'paused'])) {
                Log::info('BotConfigListenerJob: Bot no longer active', [
                    'bot_id' => $this->botId,
                    'status' => $bot->status ?? 'not found',
                ]);
                break;
            }
        }

        Log::info('BotConfigListenerJob: Listener timeout reached, exiting', [
            'bot_id' => $this->botId,
        ]);
    }

    /**
     * Trigger stop signal from worker.
     * Called in finally block of TradingBotWorkerJob.
     */
    public static function stopListening(int $botId): void
    {
        // Use Redis list LPUSH for consistent list-based messaging
        Redis::connection()->client()->lpush(
            "bot:{$botId}:config",
            json_encode(['event' => 'stop_listening'])
        );
    }

    /**
     * Trigger config update signal.
     * Called when admin updates bot config.
     */
    public static function notifyConfigChange(int $botId): void
    {
        // Use Redis list LPUSH for consistent list-based messaging
        Redis::connection()->client()->lpush(
            "bot:{$botId}:config",
            json_encode(['event' => 'config_updated'])
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BotConfigListenerJob failed', [
            'bot_id' => $this->botId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Key Fix from Momus Feedback**:
- ✅ Consistent Redis list-based design: `lpush()` for publishing, `brPop()` for consuming
- ✅ No mixing of pub/sub with list operations
- ✅ Added all imports: `use Illuminate\Support\Facades\Log;`

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php -l main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php
# Expected: No syntax errors
```

---

### Task 3.2: Integrate Listener into TradingBotWorkerJob

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php`

**Required Addition** (ADD near top of file, after imports):

```php
use Addons\TradingManagement\Modules\TradingBot\Jobs\BotConfigListenerJob;
```

**Required Modification** (ADD at START of the try/finally structure):

```php
// START listener - dispatch config listener job
Log::info('TradingBotWorkerJob: Starting config listener', ['bot_id' => $bot->id]);
dispatch(new BotConfigListenerJob($bot->id))->onQueue('listeners');

try {
    // Main worker loop (existing code unchanged)
    $iteration = 0;
    $shouldExit = false;

    while (!$shouldExit) {
        // ... existing loop code ...
    }
} finally {
    // STOP listener - send stop signal via Redis list
    BotConfigListenerJob::stopListening($bot->id);
    Log::info('TradingBotWorkerJob: Stop signal sent to config listener', [
        'bot_id' => $bot->id,
    ]);
}
```

**Key Fix from Momus Feedback**:
- ✅ Added actual `dispatch()` call
- ✅ Added import statement
- ✅ Uses `stopListening()` static method for clean shutdown

**Verification**:
```bash
docker exec 1Panel-php8-mrTy grep -n "dispatch(new BotConfigListenerJob" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php
# Expected: Line with dispatch call

docker exec 1Panel-php8-mrTy grep -n "BotConfigListenerJob::stopListening" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php
# Expected: Line with stopListening call
```

---

## Phase 4: Market Router

### Task 4.1: Create MarketRouter Service

**File**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * MarketRouter provides unified interface for checking market status
 * across different market types (Crypto, Forex, Stocks, Commodities).
 */
class MarketRouter
{
    public function __construct(
        private ExchangeConnectionService $connectionService
    ) {}

    /**
     * Get the appropriate adapter for a connection.
     * Wraps ExchangeConnectionService::getAdapter().
     */
    public function getAdapter(object $connection): object
    {
        return $this->connectionService->getAdapter($connection);
    }

    /**
     * Check if a market is currently open for trading.
     *
     * @param string $marketType crypto|fx|stock|commodity
     * @param string|null $symbol Trading pair (optional, for forex session rules)
     * @return bool True if market is open
     */
    public function isMarketOpen(string $marketType, ?string $symbol = null): bool
    {
        // Validate market type
        $validator = Validator::make(
            ['market_type' => $marketType],
            ['market_type' => 'in:crypto,fx,stock,commodity,other']
        );

        if ($validator->fails()) {
            return $this->handleUnknownMarket($marketType);
        }

        return match ($marketType) {
            'crypto' => $this->isCryptoOpen(),
            'fx' => $this->isForexOpen($symbol),
            'stock' => $this->isStockOpen(),
            'commodity' => $this->isCommodityOpen(),
            'other' => $this->handleUnknownMarket($marketType),
        };
    }

    /**
     * Crypto markets are 24/7.
     */
    private function isCryptoOpen(): bool
    {
        return true;
    }

    /**
     * Forex market hours: 22:00 GMT Sunday to 21:00 GMT Friday.
     * Daily break: 21:00-22:00 GMT.
     */
    private function isForexOpen(?string $symbol): bool
    {
        $now = now()->utc();
        $hour = $now->hour;
        $day = $now->dayOfWeek;

        // Weekend: Saturday all day, Sunday before 22:00 GMT
        if ($day === Carbon::SATURDAY) {
            return false;
        }

        if ($day === Carbon::SUNDAY && $hour < 22) {
            return false;
        }

        // Daily break: 21:00-22:00 GMT
        if ($hour >= 21 && $hour < 22) {
            return false;
        }

        // Outside daily break on weekdays
        if ($hour >= 0 && $hour < 21) {
            return true;
        }

        // After 22:00 GMT on weekdays
        return $hour >= 22;
    }

    /**
     * Stock markets: Simplified US market hours (9:30 AM - 4:00 PM ET).
     */
    private function isStockOpen(): bool
    {
        $now = now()->timezone('America/New_York');
        $hour = $now->hour;
        $day = $now->dayOfWeek;

        // Weekend closed
        if ($day === Carbon::SATURDAY || $day === Carbon::SUNDAY) {
            return false;
        }

        // Market hours: 9:30 AM - 4:00 PM ET
        return $hour >= 9 && $hour < 16;
    }

    /**
     * Commodities: Simplified NY market hours (24 hours Sunday - Friday).
     */
    private function isCommodityOpen(): bool
    {
        $now = now()->utc();
        $day = $now->dayOfWeek;

        // Closed Saturday
        if ($day === Carbon::SATURDAY) {
            return false;
        }

        return true;
    }

    /**
     * Handle unknown market type.
     * Logs warning and returns false (closed by default).
     */
    private function handleUnknownMarket(string $marketType): bool
    {
        Log::warning('Unknown market type, defaulting to closed', [
            'market_type' => $marketType,
        ]);

        return false;
    }
}
```

**Key Fixes from Momus Feedback**:
- ✅ Added `use Carbon\Carbon;` import
- ✅ Added `use Illuminate\Support\Facades\Log;` import
- ✅ Added `use Illuminate\Support\Facades\Validator;` import
- ✅ Added `declare(strict_types=1);` per project conventions

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php -l main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php
# Expected: No syntax errors
```

---

### Task 4.2: Create MarketRouter Unit Tests

**File**: `main/tests/Feature/Addons/TradingManagement/MarketRouter/MarketRouterTest.php`

**Exact Content**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\MarketRouter;

use Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Tests\Feature\Addons\TradingManagement\TradingBotTestCase;

class MarketRouterTest extends TradingBotTestCase
{
    private MarketRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create router with mocked exchange connection service
        $mockService = $this->createMock(ExchangeConnectionService::class);
        $this->router = new MarketRouter($mockService);
    }

    public function test_crypto_market_is_always_open(): void
    {
        // Crypto is 24/7, always open
        $result = $this->router->isMarketOpen('crypto');
        $this->assertTrue($result, 'Crypto market should always be open');
    }

    public function test_crypto_with_symbol_is_open(): void
    {
        $result = $this->router->isMarketOpen('crypto', 'BTC/USDT');
        $this->assertTrue($result);
    }

    public function test_unknown_market_returns_false(): void
    {
        // Unknown markets should be closed by default
        $result = $this->router->isMarketOpen('unknown_market');
        $this->assertFalse($result, 'Unknown market should return false');
    }

    public function test_forex_market_returns_boolean(): void
    {
        // Forex open/closed depends on current time
        $result = $this->router->isMarketOpen('fx', 'EUR/USD');
        $this->assertIsBool($result);
    }

    public function test_stock_market_returns_boolean(): void
    {
        $result = $this->router->isMarketOpen('stock', 'AAPL');
        $this->assertIsBool($result);
    }

    public function test_commodity_market_returns_boolean(): void
    {
        $result = $this->router->isMarketOpen('commodity', 'GOLD');
        $this->assertIsBool($result);
    }

    public function test_all_valid_market_types_return_boolean(): void
    {
        $types = ['crypto', 'fx', 'stock', 'commodity'];
        
        foreach ($types as $type) {
            $result = $this->router->isMarketOpen($type);
            $this->assertIsBool($result, "Market type '{$type}' should return boolean");
        }
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/MarketRouter/MarketRouterTest.php
# Expected: PASS (8 tests, 0 failures)
```

---

## Phase 5: Coverage Verification

### Task 5.1: Run Full Coverage Report

**Command**:
```bash
docker exec 1Panel-php8-mrTy php artisan test --coverage --min=80 --filter=TradingBot
```

**Acceptance Criteria**:
- Coverage >= 80% for trading bot components
- All tests pass
- No failing assertions

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/ --coverage --min=80 2>&1 | tail -30
# Expected: Coverage meets threshold, all tests pass
```

---

## All Verification Commands

```bash
# Phase 0: Infrastructure
docker exec 1Panel-php8-mrTy ls -la main/addons/trading-management-addon/Modules/TradingBot/database/
docker exec 1Panel-php8-mrTy php artisan tinker --execute="echo Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::factory()->create()->id;"

# Phase 0: Coverage
docker exec 1Panel-php8-mrTy php artisan test --coverage --min=80 --filter=TradingBot

# Phase 1: Test Infrastructure  
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/TradingBot/FactoryTest.php

# Phase 2: Paper Trading Fix (CRITICAL)
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# Phase 3: Dynamic Config
docker exec 1Panel-php8-mrTy grep -n "dispatch(new BotConfigListenerJob" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php
docker exec 1Panel-php8-mrTy grep -n "BotConfigListenerJob::stopListening" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php

# Phase 4: Market Router
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/MarketRouter/MarketRouterTest.php
docker exec 1Panel-php8-mrTy php -l main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php

# Full Test Suite
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/
```

---

## File Path Reference

| Component | Path | Status |
|-----------|------|--------|
| TradingBotWorkerJob | `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php` | Exists, needs dispatch + stopListening |
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Exists, needs isPaper fix |
| InternalBrokerService | `main/app/Services/InternalBrokerService.php` | Verified exists |
| TradingBot Model | `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php` | Exists, needs newFactory() |
| phpunit.xml | `main/phpunit.xml` | Exists, needs addon coverage |
| Factory path | `main/addons/trading-management-addon/Modules/TradingBot/database/factories/` | Created by Task 0.1 |
| MarketRouter path | `main/addons/trading-management-addon/Modules/MarketRouter/Services/` | Created by Task 0.1 |

---

## Change History

- **2026-01-21 v8**: Fixed all Momus v7 feedback
  - ✅ Correct namespace in verification commands (`Addons\TradingManagement\Modules\TradingBot\Models\TradingBot`)
  - ✅ Added `use Illuminate\Database\Eloquent\Factories\Factory;` import
  - ✅ Added `declare(strict_types=1);` to all new PHP files
  - ✅ Paper trading tests create `ExecutionConnection` via factory
  - ✅ Redis listener uses consistent list-based design (lpush/brPop)
  - ✅ Worker integration actually calls `dispatch()` with `onQueue('listeners')`
  - ✅ Worker uses `BotConfigListenerJob::stopListening()` static method
  - ✅ Added all missing imports (Log, Validator, Carbon) to all new files
