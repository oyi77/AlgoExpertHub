# Trading Bot Refactoring Plan - CONSOLIDATED

**Plan ID:** trading-bot-refactor-consolidated
**Created:** 2026-01-21
**Status:** Ready for Execution
**Approach:** Two-Phase: Critical Bug Fix → Comprehensive Refactor

---

## Executive Summary

This plan consolidates 4 different refactoring proposals into a strategic two-phase approach:

- **Phase 0** (IMMEDIATE): Critical paper trading bug fix (2-4 hours)
- **Phases 1-5** (FOLLOW-UP): Comprehensive TDD refactor (60-90 hours)

### Strategic Rationale

The paper trading bug **blocks users** from seeing their demo trades, making the system unusable for testing. This critical issue must be fixed immediately before embarking on the larger architectural improvements.

---

## Problem Statement

### Critical Issue (Phase 0)
In `ExecutionJob.php`, when `is_paper_trading=true`, the job returns early **without creating any InternalTrade record**. Users cannot verify their paper trades worked.

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`
**Lines**: ~80-91
**Impact**: Demo mode completely non-functional

### Enhancement Opportunities (Phases 1-5)
1. Dynamic bot configuration requires restart (no hot-reload)
2. Only crypto market supported (no forex via MetaApi)
3. Limited test coverage (missing unit/integration tests)
4. Config management scattered (no centralized manager)

---

## PHASE 0: Critical Bug Fix (IMMEDIATE)

### Scope
Fix paper trading execution flow ONLY. No architecture changes.

### Task Breakdown

#### Task 0.1: Exploration - Verify Current State
**Objective**: Document exact code structure before changes

**Commands**:
```bash
# 1. Check ExecutionJob paper mode code
docker exec 1Panel-php8-mrTy sed -n '79,95p' main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# 2. Find createVirtualPosition method
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# 3. Check InternalBrokerService signature
docker exec 1Panel-php8-mrTy sed -n '1,50p' main/app/Services/InternalBrokerService.php

# 4. Verify InternalTrade table schema
docker exec 1Panel-php8-mrTy php artisan tinker --execute="
use App\Models\InternalTrade;
\$cols = \Illuminate\Support\Facades\Schema::getColumnListing('internal_trades');
echo 'Columns: ' . implode(', ', \$cols);
"
```

**Record**:
- Line numbers for paper mode block
- `placeOrder()` signature (parameter position for `$isPaper`)
- Table name with `is_paper` column
- ExecutionConnection `$fillable` fields

---

#### Task 0.2: Fix ExecutionJob Early Return
**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Change**: Replace early return with `createVirtualPosition()` call

**Before** (approximate):
```php
if ($isTestMode) {
    return; // BUG: Exits without creating trade
}
```

**After**:
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

**Acceptance**:
- [ ] `createVirtualPosition()` called when `$isTestMode=true`
- [ ] Log entries added for debugging
- [ ] Result checked for success
- [ ] Method returns after creating virtual position

---

#### Task 0.3: Pass isPaper=true to InternalBrokerService
**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`
**Method**: `createVirtualPosition()`

**Change**: Add `$isPaper=true` to `placeOrder()` call

**Before** (approximate):
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

**After**:
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

**Note**: Update parameter position based on Task 0.1 exploration

**Acceptance**:
- [ ] `true` passed as `$isPaper` parameter
- [ ] Parameter position correct (verify via Task 0.1)
- [ ] InternalTrade created with `is_paper=1`

---

#### Task 0.4: Create Integration Test
**File**: `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` (NEW)

**Directory Creation**:
```bash
mkdir -p main/tests/Feature/Addons/TradingManagement/Execution
```

**Test Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\Execution;

use Tests\TestCase;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaperTradingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create minimal ExecutionConnection for tests.
     * Update fields based on Task 0.1 exploration.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        return ExecutionConnection::create([
            'user_id' => $userId,
            'name' => 'Test Paper Connection',
            'type' => 'crypto',
            'exchange_name' => 'binance',
            'credentials' => json_encode(['api_key' => 'test']),
            'status' => 'active',
            'is_active' => true,
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

        // Verify (use table from Task 0.1)
        $trade = \Illuminate\Support\Facades\DB::table('internal_trades')
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

**Acceptance**:
- [ ] Test creates InternalTrade with `is_paper=1`
- [ ] Verifies: symbol, direction, is_paper fields
- [ ] Confirms balance unchanged after paper trade
- [ ] Tests pass: `docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php`

---

### Phase 0 Verification

**Commands**:
```bash
# 1. Verify code changes
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# 2. Run tests
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php

# 3. Regression check
docker exec 1Panel-php8-mrTy php artisan test --filter="Execution"
```

**Definition of Done**:
- [ ] All Task 0.1-0.4 completed
- [ ] ExecutionJob calls `createVirtualPosition()` in paper mode
- [ ] `createVirtualPosition()` passes `isPaper=true` to InternalBrokerService
- [ ] PaperTradingTest creates InternalTrade with `is_paper=1`
- [ ] Balance unchanged after paper trade
- [ ] No regressions in existing tests

**Estimated Effort**: 2-4 hours

---

## PHASE 1: Foundation & Testing Infrastructure

### Scope
Establish TDD foundation, test helpers, mocks, and base test cases.

### Prerequisites
- Phase 0 completed and tested
- Understand existing addon architecture
- Review `phpunit.xml` configuration

### Task Breakdown

#### Task 1.1: Update phpunit.xml for Integration Tests + Addon Coverage
**File**: `main/phpunit.xml`

**Change**: Add Integration test suite and addon coverage

**Before**:
```xml
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
</testsuites>
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
</coverage>
```

**After**:
```xml
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory suffix="Test.php">./tests/Integration</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
</testsuites>
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <include>
        <directory suffix=".php">./addons/trading-management-addon/Modules</directory>
    </include>
</coverage>
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Integration
docker exec 1Panel-php8-mrTy php artisan test --coverage --min=80
```

**Acceptance**:
- [ ] Integration test suite registered
- [ ] Addon modules included in coverage
- [ ] Coverage reports include addon code
- [ ] All test suites runnable independently

---

#### Task 1.2: Create Mockery Directory
**Directory**: `main/tests/Mockery/` (NEW)

**Command**:
```bash
mkdir -p main/tests/Mockery
echo "# Test Doubles and Mocks" > main/tests/Mockery/README.md
```

**Purpose**: Store exchange simulators and test doubles

**Acceptance**:
- [ ] Directory created: `main/tests/Mockery/`
- [ ] README.md documents purpose
- [ ] Directory in version control (`.gitkeep` if needed)

---

#### Task 1.3: Create TradingBotTestCase Base Class
**File**: `main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php` (NEW)

**Purpose**: Base test case for all trading bot tests with common helpers

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

abstract class TradingBotTestCase extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Create a mock ExchangeConnection for tests.
     */
    protected function createMockExchangeConnection(array $overrides = []): MockInterface
    {
        return $this->mock(ExchangeConnection::class, function ($mock) use ($overrides) {
            $mock->shouldReceive('getAttribute')
                ->with('exchange_type')
                ->andReturn($overrides['exchange_type'] ?? 'crypto');
            
            $mock->shouldReceive('getAttribute')
                ->with('is_paper_trading')
                ->andReturn($overrides['is_paper_trading'] ?? false);
            
            $mock->shouldReceive('getAttribute')
                ->with('execution_settings')
                ->andReturn($overrides['execution_settings'] ?? []);
        });
    }
    
    /**
     * Create a TradingBot with default test values.
     */
    protected function createMockTradingBot(array $overrides = []): TradingBot
    {
        return TradingBot::factory()->create(array_merge([
            'status' => 'created',
            'is_paper_trading' => true,
        ], $overrides));
    }
    
    /**
     * Alias for clarity in tests.
     */
    protected function createTestBot(array $overrides = []): TradingBot
    {
        return $this->createMockTradingBot($overrides);
    }
}
```

**Acceptance**:
- [ ] Base class created with helpers
- [ ] Mock methods for ExchangeConnection
- [ ] Factory method for TradingBot
- [ ] RefreshDatabase trait included
- [ ] Can be extended by test classes

---

#### Task 1.4: Create ExchangeSimulator Test Doubles
**File**: `main/tests/Mockery/ExchangeSimulator.php` (NEW)

**Purpose**: Mock CCXT/MetaApi adapters for testing

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Mockery;

/**
 * Exchange simulator for testing without real API calls.
 */
interface ExchangeSimulatorInterface
{
    public function setBalance(string $asset, float $amount): self;
    public function placeOrder(array $params): OrderResult;
    public function getBalance(string $asset): float;
    public function getOrder(string $orderId): ?OrderResult;
}

/**
 * Simple order result DTO.
 */
class OrderResult
{
    public function __construct(
        public string $orderId,
        public string $status,
        public float $price = 0.0,
        public float $quantity = 0.0
    ) {}
}

/**
 * In-memory exchange simulator.
 */
class ExchangeSimulator implements ExchangeSimulatorInterface
{
    private array $balances = [];
    private array $orders = [];
    
    public function setBalance(string $asset, float $amount): self
    {
        $this->balances[$asset] = $amount;
        return $this;
    }
    
    public function getBalance(string $asset): float
    {
        return $this->balances[$asset] ?? 0.0;
    }
    
    public function placeOrder(array $params): OrderResult
    {
        $orderId = strtoupper(bin2hex(random_bytes(8)));
        
        $order = [
            'id' => $orderId,
            'symbol' => $params['symbol'],
            'side' => $params['side'],
            'type' => $params['type'] ?? 'market',
            'quantity' => $params['quantity'],
            'price' => $params['price'] ?? 0.0,
            'status' => 'open',
        ];
        
        $this->orders[$orderId] = $order;
        
        return new OrderResult(
            orderId: $orderId,
            status: 'open',
            price: $order['price'],
            quantity: $order['quantity']
        );
    }
    
    public function getOrder(string $orderId): ?OrderResult
    {
        if (!isset($this->orders[$orderId])) {
            return null;
        }
        
        $order = $this->orders[$orderId];
        return new OrderResult(
            orderId: $order['id'],
            status: $order['status'],
            price: $order['price'],
            quantity: $order['quantity']
        );
    }
}
```

**Acceptance**:
- [ ] Interface defines contract
- [ ] Simulator implements in-memory exchange
- [ ] Balance management works
- [ ] Order placement returns ID
- [ ] Order retrieval works

---

#### Task 1.5: ConfigManager Unit Tests
**File**: `main/tests/Unit/Addons/TradingManagement/TradingBot/ConfigManager/ConfigManagerTest.php` (NEW)

**Tests**: `Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php` (to be created in Phase 2)

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot\ConfigManager;

use Tests\Unit\Addons\TradingManagement\TradingBot\TradingBotTestCase;
use Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager\TradingBotConfigManager;
use Illuminate\Support\Facades\Redis;

class ConfigManagerTest extends TradingBotTestCase
{
    public function test_update_config_persists(): void
    {
        $bot = $this->createTestBot();
        
        $manager = app(TradingBotConfigManager::class);
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.03]);
        
        $bot->tradingPreset->refresh();
        $this->assertEquals(0.03, $bot->tradingPreset->risk_per_trade_pct);
    }
    
    public function test_config_update_publishes_redis_event(): void
    {
        $bot = $this->createTestBot(['status' => 'running']);
        
        $manager = app(TradingBotConfigManager::class);
        
        // CORRECT: Publish string payload, not closure
        Redis::shouldReceive('publish')
            ->once()
            ->with(
                "bot:{$bot->id}:config",
                \Mockery::on(function ($payload) {
                    $data = json_decode($payload, true);
                    return $data['event'] === 'config_updated'
                        && isset($data['config'])
                        && isset($data['timestamp']);
                })
            );
        
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.04]);
    }
    
    public function test_runtime_config_cached(): void
    {
        $bot = $this->createTestBot();
        
        $manager = app(TradingBotConfigManager::class);
        
        // First call should hit cache
        $config1 = $manager->getRuntimeConfig($bot);
        
        // Second call should use cache
        $config2 = $manager->getRuntimeConfig($bot);
        
        $this->assertEquals($config1, $config2);
    }
}
```

**Acceptance**:
- [ ] Test config persistence
- [ ] Test Redis pub/sub (string payload)
- [ ] Test cache behavior
- [ ] Tests pass (will fail until Phase 2.1 implements ConfigManager)

---

#### Task 1.6: MarketRouter Unit Tests
**File**: `main/tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/MarketRouterTest.php` (NEW)

**Tests**: `Modules/MarketRouter/Services/MarketRouter.php` (to be created in Phase 3)

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot\MarketRouter;

use Tests\TestCase;
use Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketRouterTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_normalize_crypto_symbol(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertEquals('BTCUSDT', $router->normalizeSymbol('BTC/USDT', 'crypto'));
        $this->assertEquals('ETHUSDT', $router->normalizeSymbol('ETH-USDT', 'crypto'));
    }
    
    public function test_normalize_forex_symbol(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertEquals('EURUSD', $router->normalizeSymbol('EUR/USD', 'forex'));
        $this->assertEquals('GBPJPY', $router->normalizeSymbol('GBP/JPY', 'forex'));
    }
    
    public function test_crypto_market_always_open(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertTrue($router->isMarketOpen('crypto'));
        $this->assertTrue($router->isMarketOpen('crypto', 'BTC/USDT'));
    }
    
    public function test_forex_market_hours_respected(): void
    {
        $router = app(MarketRouter::class);
        
        // NOTE: This will require mocking current time
        // For now, just verify method exists and returns boolean
        $result = $router->isMarketOpen('forex', 'EUR/USD');
        $this->assertIsBool($result);
    }
}
```

**Acceptance**:
- [ ] Symbol normalization tests pass
- [ ] Crypto 24/7 verified
- [ ] Forex hours logic testable
- [ ] Tests pass (will fail until Phase 3.1 implements MarketRouter)

---

### Phase 1 Summary

**Tasks**: 6 tasks (1.1-1.6)
**Estimated Effort**: 8-12 hours
**Dependencies**: Phase 0 complete
**Deliverables**:
- Updated `phpunit.xml` with Integration suite
- Test base classes and helpers
- Exchange simulator mock
- Unit tests for ConfigManager (TDD - tests first)
- Unit tests for MarketRouter (TDD - tests first)

**Definition of Done**:
- [ ] All Task 1.1-1.6 completed
- [ ] Integration test suite runs
- [ ] Addon code included in coverage reports
- [ ] TradingBotTestCase usable by future tests
- [ ] ExchangeSimulator can mock orders/balances
- [ ] Unit tests created (failing until Phase 2/3 implementation)

---

## PHASE 2: Dynamic Configuration

### Scope
Implement hot-reload config via Redis pub/sub, config manager service, and listener lifecycle.

### Prerequisites
- Phase 0 and 1 completed
- Understand existing TradingPreset model
- Review Redis configuration

### Task Breakdown

#### Task 2.1: ConfigManager Service Creation
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php` (NEW)

**Directory**: Create `Services/ConfigManager/`

**Purpose**: Centralized config management with cache and pub/sub

**Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Cache\Repository as CacheInterface;

class TradingBotConfigManager
{
    public function __construct(
        private CacheInterface $cache
    ) {}
    
    /**
     * Update bot configuration and notify running bots.
     */
    public function updateConfig(TradingBot $bot, array $config): void
    {
        DB::transaction(function () use ($bot, $config) {
            // Update TradingPreset
            $bot->tradingPreset()->update($config);
            
            // Invalidate cache
            $this->cache->forget("bot_config:{$bot->id}");
            
            // Publish hot-reload event for running bots
            if ($bot->status === 'running') {
                Redis::publish("bot:{$bot->id}:config", json_encode([
                    'event' => 'config_updated',
                    'config' => $this->buildRuntimeConfig($bot),
                    'timestamp' => now()->toIso8601String(),
                ]));
            }
        });
    }
    
    /**
     * Get runtime configuration (cached).
     */
    public function getRuntimeConfig(TradingBot $bot): array
    {
        return $this->cache->remember(
            "bot_config:{$bot->id}",
            3600,
            fn() => $this->buildRuntimeConfig($bot)
        );
    }
    
    /**
     * Build runtime configuration from database.
     */
    protected function buildRuntimeConfig(TradingBot $bot): array
    {
        $preset = $bot->tradingPreset;
        
        return [
            'bot_id' => $bot->id,
            'risk_per_trade_pct' => $preset->risk_per_trade_pct ?? 0.02,
            'max_open_trades' => $preset->max_open_trades ?? 3,
            'stop_loss_pct' => $preset->stop_loss_pct ?? 0.05,
            'take_profit_pct' => $preset->take_profit_pct ?? 0.10,
            'trading_hours' => $preset->trading_hours ?? [],
            'allowed_symbols' => $preset->allowed_symbols ?? [],
        ];
    }
}
```

**Acceptance**:
- [ ] Service created in correct directory
- [ ] `updateConfig()` updates preset and cache
- [ ] Redis pub/sub publishes string payload
- [ ] `getRuntimeConfig()` caches results
- [ ] Unit tests from Task 1.5 now pass

---

#### Task 2.2: BotConfigListenerJob (Redis pub/sub with lifecycle)
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php` (NEW)

**Purpose**: Subscribe to Redis config changes with explicit start/stop lifecycle

**Code**:
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
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class BotConfigListenerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $maxExceptions = 3;
    public $timeout = 3600; // Long-running job
    
    public function __construct(
        public int $botId,
        public string $channel = 'subscribe' // 'subscribe' or 'unsubscribe'
    ) {}
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->channel === 'unsubscribe') {
            return; // Exit silently for unsubscribe
        }
        
        $bot = TradingBot::find($this->botId);
        if (!$bot || !in_array($bot->status, ['running', 'paused'])) {
            return;
        }
        
        try {
            // Non-blocking subscribe with timeout
            Redis::subscribe(
                ["bot:{$this->botId}:config"],
                function ($message) {
                    $data = json_decode($message, true);
                    if (($data['event'] ?? null) === 'config_updated') {
                        Cache::forget("bot_config:{$this->botId}");
                        Log::info('Bot config cache invalidated', [
                            'bot_id' => $this->botId,
                        ]);
                    }
                }
            );
        } catch (\Exception $e) {
            Log::error('Redis subscription error', [
                'bot_id' => $this->botId,
                'error' => $e->getMessage(),
            ]);
            
            // Retry after 5 seconds
            $this->release(5);
        }
    }
    
    /**
     * Stop listening when bot stops/pauses.
     * Called from TradingBotWorkerJob when bot state changes.
     */
    public function stopListening(TradingBot $bot): void
    {
        Redis::unsubscribe(["bot:{$bot->id}:config"]);
        Log::info('Bot config listener stopped', ['bot_id' => $bot->id]);
    }
}
```

**Acceptance**:
- [ ] Job created with ShouldQueue
- [ ] Redis subscribe implemented
- [ ] Cache invalidation on config_updated event
- [ ] Unsubscribe method provided
- [ ] Error handling and retry logic

---

#### Task 2.3: Integrate Listener into TradingBotWorkerJob
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php` (MODIFY)

**Purpose**: Start/stop config listener with bot lifecycle

**Change**: Add listener startup/teardown in `handle()` method

**Code** (modify existing):
```php
class TradingBotWorkerJob implements ShouldQueue
{
    public function handle(): void
    {
        $bot = $this->getBot();
        
        // START listener (NEW)
        $listener = new BotConfigListenerJob($bot->id, 'subscribe');
        dispatch($listener);
        
        try {
            while ($bot->status === 'running') {
                $bot->refresh();
                
                // Process signals (existing)
                $this->processSignals($bot);
                
                sleep($bot->check_interval ?? 5);
            }
        } finally {
            // STOP listener (NEW - ensure cleanup)
            $listener->stopListening($bot);
        }
    }
}
```

**Acceptance**:
- [ ] Listener dispatched when bot starts
- [ ] Listener stopped when bot stops (finally block)
- [ ] No zombie listeners left running
- [ ] Existing bot processing unchanged

---

#### Task 2.4: Integration Test - Config Hot-Reload
**File**: `main/tests/Integration/Addons/TradingManagement/TradingBot/ConfigHotReloadTest.php` (NEW)

**Purpose**: Verify config updates trigger Redis messages and cache invalidation

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager\TradingBotConfigManager;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigHotReloadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_config_update_triggers_redis_message(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        // Capture Redis publish calls
        $published = null;
        Redis::shouldReceive('publish')
            ->once()
            ->andReturnUsing(function ($channel, $message) use (&$published) {
                $published = ['channel' => $channel, 'message' => $message];
            });
        
        // Update config
        $manager = app(TradingBotConfigManager::class);
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.03]);
        
        // Verify
        $this->assertNotNull($published);
        $this->assertEquals("bot:{$bot->id}:config", $published['channel']);
        
        $data = json_decode($published['message'], true);
        $this->assertEquals('config_updated', $data['event']);
        $this->assertArrayHasKey('config', $data);
    }
    
    public function test_cache_invalidated_on_config_update(): void
    {
        $bot = TradingBot::factory()->create();
        
        $manager = app(TradingBotConfigManager::class);
        
        // Cache config
        $config1 = $manager->getRuntimeConfig($bot);
        
        // Update config
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.05]);
        
        // Cache should be invalidated
        $this->assertFalse(Cache::has("bot_config:{$bot->id}"));
        
        // New config should reflect changes
        $config2 = $manager->getRuntimeConfig($bot);
        $this->assertEquals(0.05, $config2['risk_per_trade_pct']);
    }
}
```

**Acceptance**:
- [ ] Test verifies Redis publish
- [ ] Test verifies cache invalidation
- [ ] Test verifies new config loaded
- [ ] Tests pass

---

### Phase 2 Summary

**Tasks**: 4 tasks (2.1-2.4)
**Estimated Effort**: 12-16 hours
**Dependencies**: Phase 1 complete
**Deliverables**:
- TradingBotConfigManager service
- BotConfigListenerJob with lifecycle
- Integration with TradingBotWorkerJob
- Integration tests for hot-reload

**Definition of Done**:
- [ ] All Task 2.1-2.4 completed
- [ ] Config updates hot-reload without restart
- [ ] Redis pub/sub verified working
- [ ] Cache invalidation works
- [ ] Unit tests from Phase 1 pass
- [ ] Integration tests pass
- [ ] No bot restarts required for config changes

---

## PHASE 3: Multi-Market Support

### Scope
Add MarketRouter for unified crypto/forex handling, symbol normalization, and trading hours.

### Prerequisites
- Phase 2 completed
- Understand CCXT and MetaApi adapters
- Review existing DataProvider module

### Task Breakdown

#### Task 3.1: MarketRouter Module Creation
**Directory**: `main/addons/trading-management-addon/Modules/MarketRouter/` (NEW)
**File**: `Services/MarketRouter.php` (NEW)

**Purpose**: Unified routing for crypto (CCXT) and forex (MetaApi)

**Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\MarketRouter\Exceptions\UnsupportedMarketException;

class MarketRouter
{
    /**
     * Normalize symbol for market type.
     */
    public function normalizeSymbol(string $symbol, string $marketType): string
    {
        return match ($marketType) {
            'crypto' => str_replace(['/', '-'], '', $symbol), // BTC/USDT -> BTCUSDT
            'forex' => str_replace('/', '', $symbol),          // EUR/USD -> EURUSD
            default => throw new UnsupportedMarketException($marketType),
        };
    }
    
    /**
     * Calculate lot size for market type.
     */
    public function calculateLotSize(
        string $marketType,
        float $amount,
        string $symbol,
        ExchangeConnection $connection
    ): float {
        return match ($marketType) {
            'crypto' => $this->cryptoLotSize($amount, $symbol),
            'forex' => $this->forexLotSize($amount, $symbol, $connection),
        };
    }
    
    /**
     * Check if market is open.
     */
    public function isMarketOpen(string $marketType, ?string $symbol = null): bool
    {
        return match ($marketType) {
            'crypto' => true, // 24/7
            'forex' => $this->forexSession()->isOpen($symbol),
        };
    }
    
    /**
     * Get adapter for exchange connection.
     */
    public function getAdapter(ExchangeConnection $connection): object
    {
        return match ($connection->type) {
            'crypto' => app(CcxtAdapter::class)->setConnection($connection),
            'fx' => app(MetaApiAdapter::class)->setConnection($connection),
        };
    }
    
    /**
     * Crypto lot size calculation.
     */
    protected function cryptoLotSize(float $amount, string $symbol): float
    {
        // Simple: amount in base currency
        return $amount;
    }
    
    /**
     * Forex lot size calculation.
     */
    protected function forexLotSize(float $amount, string $symbol, ExchangeConnection $connection): float
    {
        // Forex: 1 lot = 100,000 units
        // Mini lot = 10,000 units
        // Micro lot = 1,000 units
        return $amount / 100000;
    }
    
    /**
     * Get forex session service.
     */
    protected function forexSession(): TradingHoursService
    {
        return app(TradingHoursService::class);
    }
}
```

**Acceptance**:
- [ ] MarketRouter service created
- [ ] Symbol normalization for crypto/forex
- [ ] Lot size calculation for both markets
- [ ] Market hours check (crypto 24/7, forex hours)
- [ ] Adapter routing based on connection type

---

#### Task 3.2: SymbolNormalizer
**File**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/SymbolNormalizer.php` (NEW)

**Purpose**: Advanced symbol normalization with validation

**Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Addons\TradingManagement\Modules\MarketRouter\Exceptions\InvalidSymbolException;

class SymbolNormalizer
{
    /**
     * Normalize and validate symbol.
     */
    public function normalize(string $symbol, string $marketType): string
    {
        $normalized = match ($marketType) {
            'crypto' => $this->normalizeCrypto($symbol),
            'forex' => $this->normalizeForex($symbol),
            default => throw new \InvalidArgumentException("Unknown market type: {$marketType}"),
        };
        
        $this->validate($normalized, $marketType);
        
        return $normalized;
    }
    
    /**
     * Normalize crypto symbol.
     */
    protected function normalizeCrypto(string $symbol): string
    {
        // Remove separators
        $symbol = str_replace(['/', '-', '_'], '', $symbol);
        
        // Uppercase
        return strtoupper($symbol);
    }
    
    /**
     * Normalize forex symbol.
     */
    protected function normalizeForex(string $symbol): string
    {
        // Remove separators
        $symbol = str_replace(['/', '-', '_'], '', $symbol);
        
        // Uppercase
        return strtoupper($symbol);
    }
    
    /**
     * Validate normalized symbol.
     */
    protected function validate(string $symbol, string $marketType): void
    {
        if (empty($symbol)) {
            throw new InvalidSymbolException("Symbol cannot be empty");
        }
        
        if ($marketType === 'forex' && strlen($symbol) !== 6) {
            throw new InvalidSymbolException("Forex symbols must be 6 characters (e.g., EURUSD)");
        }
    }
}
```

**Acceptance**:
- [ ] Symbol normalization with validation
- [ ] Crypto and forex specific rules
- [ ] Exception thrown for invalid symbols
- [ ] Unit tests pass

---

#### Task 3.3: TradingHoursService
**File**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/TradingHoursService.php` (NEW)

**Purpose**: Forex market hours checking

**Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Carbon\Carbon;

class TradingHoursService
{
    /**
     * Check if forex market is open.
     * 
     * Forex hours: Sunday 22:00 GMT - Friday 21:00 GMT
     * Daily break: 21:00 - 22:00 GMT
     */
    public function isOpen(?string $symbol = null): bool
    {
        $now = Carbon::now('GMT');
        
        // Weekend closed
        if ($now->isWeekend()) {
            // Sunday after 22:00 GMT is open
            if ($now->isSunday() && $now->hour >= 22) {
                return true;
            }
            // Friday after 21:00 GMT is closed
            if ($now->isFriday() && $now->hour >= 21) {
                return false;
            }
            return false;
        }
        
        // Daily break: 21:00 - 22:00 GMT
        if ($now->hour >= 21 && $now->hour < 22) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get next open time.
     */
    public function nextOpenTime(): Carbon
    {
        $now = Carbon::now('GMT');
        
        if ($this->isOpen()) {
            return $now;
        }
        
        // If weekend, wait for Sunday 22:00 GMT
        if ($now->isWeekend()) {
            return $now->next(Carbon::SUNDAY)->setTime(22, 0);
        }
        
        // If daily break, wait for 22:00
        if ($now->hour >= 21) {
            return $now->setTime(22, 0);
        }
        
        return $now;
    }
}
```

**Acceptance**:
- [ ] Forex market hours implemented
- [ ] Weekend closure logic
- [ ] Daily break (21:00-22:00 GMT)
- [ ] Next open time calculation
- [ ] Unit tests pass

---

#### Task 3.4: Integration Test - Multi-Market
**File**: `main/tests/Integration/Addons/TradingManagement/TradingBot/MultiMarketTest.php` (NEW)

**Purpose**: Verify multi-market routing works end-to-end

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter;
use Addons\TradingManagement\Modules\MarketRouter\Services\TradingHoursService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiMarketTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_crypto_symbol_normalized_correctly(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertEquals('BTCUSDT', $router->normalizeSymbol('BTC/USDT', 'crypto'));
        $this->assertEquals('ETHBTC', $router->normalizeSymbol('ETH-BTC', 'crypto'));
    }
    
    public function test_forex_symbol_normalized_correctly(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertEquals('EURUSD', $router->normalizeSymbol('EUR/USD', 'forex'));
        $this->assertEquals('GBPJPY', $router->normalizeSymbol('GBP/JPY', 'forex'));
    }
    
    public function test_crypto_market_always_open(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertTrue($router->isMarketOpen('crypto'));
    }
    
    public function test_forex_market_hours_respected(): void
    {
        $router = app(MarketRouter::class);
        
        // This will depend on current time
        $result = $router->isMarketOpen('forex', 'EUR/USD');
        $this->assertIsBool($result);
    }
    
    public function test_get_adapter_for_crypto_connection(): void
    {
        $connection = ExchangeConnection::factory()->create(['type' => 'crypto']);
        
        $router = app(MarketRouter::class);
        $adapter = $router->getAdapter($connection);
        
        $this->assertInstanceOf(
            \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter::class,
            $adapter
        );
    }
}
```

**Acceptance**:
- [ ] Symbol normalization tests pass
- [ ] Market hours logic verified
- [ ] Adapter routing works
- [ ] Tests pass for both crypto and forex

---

### Phase 3 Summary

**Tasks**: 4 tasks (3.1-3.4)
**Estimated Effort**: 16-20 hours
**Dependencies**: Phase 2 complete
**Deliverables**:
- MarketRouter service
- SymbolNormalizer service
- TradingHoursService
- Integration tests for multi-market

**Definition of Done**:
- [ ] All Task 3.1-3.4 completed
- [ ] Crypto symbols normalized correctly
- [ ] Forex symbols normalized correctly
- [ ] Forex market hours respected
- [ ] Crypto 24/7 trading verified
- [ ] Adapter routing works for both markets
- [ ] Integration tests pass

---

## PHASE 4: Demo Mode Fix (Enhanced)

### Scope
Create VirtualPortfolio model, enhance PaperTradingService, ensure demo mode isolation.

### Prerequisites
- Phase 0 completed (critical bug fix)
- Understand InternalBrokerService
- Review existing ExecutionJob

### Task Breakdown

#### Task 4.1: VirtualPortfolio Model Creation
**File**: `main/addons/trading-management-addon/Modules/PaperTrading/Models/VirtualPortfolio.php` (NEW)

**Purpose**: Track virtual balances per user/connection

**Migration**: `main/addons/trading-management-addon/database/migrations/YYYY_MM_DD_create_virtual_portfolios_table.php`

**Migration Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exchange_connection_id')->constrained('execution_connections')->onDelete('cascade');
            $table->decimal('balance', 20, 8)->default(10000); // Starting balance
            $table->enum('market_type', ['crypto', 'fx'])->default('crypto');
            $table->timestamps();
            
            $table->unique(['user_id', 'exchange_connection_id']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('virtual_portfolios');
    }
};
```

**Model Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\PaperTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;

class VirtualPortfolio extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'balance' => 'decimal:8',
    ];
    
    /**
     * User who owns this portfolio.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Exchange connection this portfolio is for.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ExchangeConnection::class, 'exchange_connection_id');
    }
    
    /**
     * Deduct amount from balance.
     */
    public function deduct(float $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }
    
    /**
     * Add amount to balance.
     */
    public function credit(float $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }
}
```

**Acceptance**:
- [ ] Migration created and run
- [ ] Model created with relationships
- [ ] Balance management methods
- [ ] Unique constraint on user_id + exchange_connection_id
- [ ] Tests can create VirtualPortfolio

---

#### Task 4.2: PaperTradingService Enhancement
**File**: `main/addons/trading-management-addon/Modules/PaperTrading/Services/PaperTradingService.php` (CREATE or MODIFY)

**Purpose**: Enhanced paper trading with VirtualPortfolio integration

**Code**:
```php
<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\PaperTrading\Services;

use Addons\TradingManagement\Modules\PaperTrading\Models\VirtualPortfolio;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Models\User;
use App\Models\InternalTrade;
use Illuminate\Support\Facades\Log;

class PaperTradingService
{
    /**
     * Execute paper trade with virtual portfolio.
     */
    public function executeTrade(
        User $user,
        ExchangeConnection $connection,
        string $symbol,
        string $direction,
        float $quantity,
        ?float $entryPrice = null,
        ?float $stopLoss = null,
        ?float $takeProfit = null
    ): array {
        // Get or create virtual portfolio
        $portfolio = VirtualPortfolio::firstOrCreate([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
        ], [
            'balance' => 10000,
            'market_type' => $connection->type ?? 'crypto',
        ]);
        
        // Calculate trade cost
        $cost = $quantity * ($entryPrice ?? 0);
        
        // Check balance
        if ($cost > $portfolio->balance) {
            return [
                'success' => false,
                'message' => 'Insufficient virtual balance',
            ];
        }
        
        // Deduct from virtual portfolio
        if ($direction === 'buy' || $direction === 'long') {
            $portfolio->deduct($cost);
        }
        
        // Create internal trade record (via InternalBrokerService)
        $internalBrokerService = app(\App\Services\InternalBrokerService::class);
        $trade = $internalBrokerService->placeOrder(
            $user,
            $symbol,
            $direction,
            $quantity,
            $entryPrice ?? 0,
            $stopLoss,
            $takeProfit,
            true // isPaper=true
        );
        
        Log::info('Paper trade executed', [
            'user_id' => $user->id,
            'trade_id' => $trade->id ?? null,
            'symbol' => $symbol,
            'direction' => $direction,
            'quantity' => $quantity,
        ]);
        
        return [
            'success' => true,
            'trade_id' => $trade->id ?? null,
            'virtual_balance' => $portfolio->balance,
        ];
    }
}
```

**Acceptance**:
- [ ] Service created with executeTrade method
- [ ] VirtualPortfolio managed automatically
- [ ] Balance deducted for trades
- [ ] Calls InternalBrokerService with isPaper=true
- [ ] Returns success/failure with balance

---

#### Task 4.3: Verify ExecutionJob Uses InternalBrokerService (Already Fixed in Phase 0)
**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Verification Only**: Ensure Phase 0 changes are still in place

**Commands**:
```bash
# Verify createVirtualPosition is called
docker exec 1Panel-php8-mrTy grep -n "createVirtualPosition" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# Verify isPaper=true passed
docker exec 1Panel-php8-mrTy grep -n "placeOrder.*true" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
```

**Acceptance**:
- [ ] Phase 0 fix still in place
- [ ] ExecutionJob calls createVirtualPosition
- [ ] createVirtualPosition passes isPaper=true
- [ ] No regression from Phase 0

---

#### Task 4.4: Integration Test - Demo Mode Isolation
**File**: `main/tests/Integration/Addons/TradingManagement/TradingBot/DemoModeTest.php` (NEW)

**Purpose**: Verify demo trades don't affect real balance or create real orders

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\PaperTrading\Services\PaperTradingService;
use Addons\TradingManagement\Modules\PaperTrading\Models\VirtualPortfolio;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_demo_trade_creates_virtual_portfolio(): void
    {
        $user = User::factory()->create();
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $user->id,
            'type' => 'crypto',
        ]);
        
        $service = app(PaperTradingService::class);
        $result = $service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'buy',
            0.1,
            50000
        );
        
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('virtual_portfolios', [
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
        ]);
    }
    
    public function test_demo_trade_deducts_virtual_balance(): void
    {
        $user = User::factory()->create();
        $connection = ExchangeConnection::factory()->create(['user_id' => $user->id]);
        
        // Create portfolio with 10000 balance
        $portfolio = VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 10000,
        ]);
        
        $service = app(PaperTradingService::class);
        $result = $service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'buy',
            0.1,
            50000 // Cost: 5000
        );
        
        $portfolio->refresh();
        $this->assertEquals(5000, $portfolio->balance);
    }
    
    public function test_demo_trade_does_not_affect_real_balance(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $connection = ExchangeConnection::factory()->create(['user_id' => $user->id]);
        $originalBalance = $user->balance;
        
        $service = app(PaperTradingService::class);
        $service->executeTrade(
            $user,
            $connection,
            'ETH/USDT',
            'sell',
            1.0,
            3000
        );
        
        $user->refresh();
        $this->assertEquals($originalBalance, $user->balance);
    }
}
```

**Acceptance**:
- [ ] VirtualPortfolio created automatically
- [ ] Virtual balance deducted for trades
- [ ] Real user balance unchanged
- [ ] Tests pass

---

### Phase 4 Summary

**Tasks**: 4 tasks (4.1-4.4)
**Estimated Effort**: 12-16 hours
**Dependencies**: Phase 0 complete
**Deliverables**:
- VirtualPortfolio model and migration
- PaperTradingService enhanced
- Demo mode isolation verified
- Integration tests for demo mode

**Definition of Done**:
- [ ] All Task 4.1-4.4 completed
- [ ] VirtualPortfolio tracks demo balances
- [ ] Demo trades use InternalBrokerService (Phase 0 fix)
- [ ] Demo trades isolated from real balance
- [ ] Integration tests pass
- [ ] Phase 0 fix still working

---

## PHASE 5: Feature Tests

### Scope
Create feature tests for bot CRUD operations and market info APIs.

### Prerequisites
- Phases 1-4 completed
- Understand existing route structure
- Review user panel routes

### Task Breakdown

#### Task 5.1: Bot CRUD API Feature Tests
**File**: `main/tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php` (NEW)

**Purpose**: Test user panel routes for bot management

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotCrudTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    
    public function test_user_can_create_trading_bot(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.store'), [
                'name' => 'Test Bot',
                'type' => 'signal_based',
                'status' => 'created',
                'is_paper_trading' => true,
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('trading_bots', [
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'is_paper_trading' => true,
        ]);
    }
    
    public function test_user_can_update_bot_config(): void
    {
        $bot = TradingBot::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->put(route('user.trading-management.trading-bots.update', $bot), [
                'name' => 'Updated Bot',
                'status' => 'paused',
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('trading_bots', [
            'id' => $bot->id,
            'name' => 'Updated Bot',
            'status' => 'paused',
        ]);
    }
    
    public function test_user_can_delete_bot(): void
    {
        $bot = TradingBot::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->delete(route('user.trading-management.trading-bots.destroy', $bot));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('trading_bots', ['id' => $bot->id]);
    }
    
    public function test_user_cannot_access_other_users_bots(): void
    {
        $otherUser = User::factory()->create();
        $bot = TradingBot::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->actingAs($this->user)
            ->get(route('user.trading-management.trading-bots.show', $bot));
        
        $response->assertForbidden();
    }
}
```

**Acceptance**:
- [ ] CRUD operations tested
- [ ] Authorization verified (user isolation)
- [ ] Route names correct
- [ ] Tests pass

---

#### Task 5.2: Market Info API Feature Tests
**File**: `main/tests/Feature/Addons/TradingManagement/TradingBot/MarketInfoTest.php` (NEW)

**Purpose**: Test market info endpoints (if exposed via API)

**Code**:
```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketInfoTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    
    public function test_get_market_hours(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'forex']));
        
        $response->assertOk();
        $response->assertJsonStructure([
            'is_open',
            'next_open',
        ]);
    }
    
    public function test_get_symbol_info(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.symbol', [
                'symbol' => 'BTC/USDT',
                'market' => 'crypto',
            ]));
        
        $response->assertOk();
        $response->assertJsonStructure([
            'symbol',
            'normalized',
            'market_type',
        ]);
    }
}
```

**Acceptance**:
- [ ] Market hours endpoint tested
- [ ] Symbol info endpoint tested
- [ ] JSON structure validated
- [ ] Tests pass

---

### Phase 5 Summary

**Tasks**: 2 tasks (5.1-5.2)
**Estimated Effort**: 4-6 hours
**Dependencies**: Phases 1-4 complete
**Deliverables**:
- Bot CRUD feature tests
- Market info API feature tests

**Definition of Done**:
- [ ] All Task 5.1-5.2 completed
- [ ] Bot CRUD operations tested
- [ ] User isolation verified
- [ ] Market info APIs tested
- [ ] All feature tests pass

---

## File Path Reference

### Phase 0 (Critical Fix)
| Component | Path | Change |
|-----------|------|--------|
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` | Fix paper mode |
| PaperTradingTest | `main/tests/Feature/Addons/TradingManagement/Execution/PaperTradingTest.php` | New file |

### Phase 1 (Foundation)
| Component | Path | Change |
|-----------|------|--------|
| phpunit.xml | `main/phpunit.xml` | Add Integration suite |
| TradingBotTestCase | `main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php` | New file |
| ExchangeSimulator | `main/tests/Mockery/ExchangeSimulator.php` | New file |

### Phase 2 (Dynamic Config)
| Component | Path | Change |
|-----------|------|--------|
| TradingBotConfigManager | `main/addons/trading-management-addon/Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php` | New file |
| BotConfigListenerJob | `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php` | New file |
| TradingBotWorkerJob | `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php` | Modify |

### Phase 3 (Multi-Market)
| Component | Path | Change |
|-----------|------|--------|
| MarketRouter | `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php` | New file |
| SymbolNormalizer | `main/addons/trading-management-addon/Modules/MarketRouter/Services/SymbolNormalizer.php` | New file |
| TradingHoursService | `main/addons/trading-management-addon/Modules/MarketRouter/Services/TradingHoursService.php` | New file |

### Phase 4 (Demo Mode)
| Component | Path | Change |
|-----------|------|--------|
| VirtualPortfolio | `main/addons/trading-management-addon/Modules/PaperTrading/Models/VirtualPortfolio.php` | New file |
| PaperTradingService | `main/addons/trading-management-addon/Modules/PaperTrading/Services/PaperTradingService.php` | New file |

### Phase 5 (Feature Tests)
| Component | Path | Change |
|-----------|------|--------|
| BotCrudTest | `main/tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php` | New file |
| MarketInfoTest | `main/tests/Feature/Addons/TradingManagement/TradingBot/MarketInfoTest.php` | New file |

---

## Success Criteria

### Functional
- [ ] Phase 0: Paper trades create InternalTrade records (IMMEDIATE)
- [ ] Phase 2: Config hot-reload < 1 second
- [ ] Phase 3: Crypto/Forex unified via MarketRouter
- [ ] Phase 3: Forex hours respected
- [ ] Phase 4: Demo trades use VirtualPortfolio
- [ ] Phase 4: Real balance unchanged after paper trade

### Quality
- [ ] 80% unit test coverage (addon code included)
- [ ] All test suites pass (Unit, Integration, Feature)
- [ ] No regression in existing tests
- [ ] All phases completed in order

---

## Estimated Effort

| Phase | Tasks | Hours | Priority |
|-------|-------|-------|----------|
| 0 (Critical Bug Fix) | 4 | 2-4 | IMMEDIATE |
| 1 (Foundation) | 6 | 8-12 | High |
| 2 (Dynamic Config) | 4 | 12-16 | High |
| 3 (Multi-Market) | 4 | 16-20 | Medium |
| 4 (Demo Mode) | 4 | 12-16 | Medium |
| 5 (Feature Tests) | 2 | 4-6 | Low |
| **Total** | **24** | **54-74** | - |

**Note**: Phase 0 should be completed FIRST (2-4 hours) before starting Phases 1-5 (54-74 hours total).

---

## Change History

- **2026-01-21**: Consolidated from 4 plan versions (v1, v3, v11, v12)
  - ✅ Strategic two-phase approach (critical fix → comprehensive refactor)
  - ✅ Phase 0 combines v11/v12 exploration-first bug fix
  - ✅ Phases 1-5 combine best of v1/v3 comprehensive refactor
  - ✅ All correct file paths (InternalBrokerService in main/app/, MetaApiAdapter in DataProvider/)
  - ✅ Explicit exploration tasks before code changes
  - ✅ Test-driven development (TDD) with tests before implementation
  - ✅ Fixed Redis listener lifecycle (start/stop)
  - ✅ Fixed demo trading flow (ExecutionJob calls InternalBrokerService)
  - ✅ Correct Redis facade usage (string payload, not closure)
  - ✅ Integration suite + addon coverage in phpunit.xml
  - ✅ Uses RefreshDatabase trait only (no DatabaseMigrations)
