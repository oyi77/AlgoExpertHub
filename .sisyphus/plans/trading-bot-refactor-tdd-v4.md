# Trading Bot Refactoring Plan (TDD Approach) - FINAL VERSION

**Plan ID:** trading-bot-refactor-tdd-v4
**Created:** 2026-01-21
**Status:** Ready for Momus Review - FINAL
**Approach:** Test-Driven Development (TDD)

---

## Executive Summary

This plan addresses ALL issues from Momus v1-v3 reviews with:
1. ✅ Correct file paths (verified against codebase)
2. ✅ Real method names (InternalBrokerService::placeOrder)
3. ✅ Actual TradingBotWorkerJob integration
4. ✅ ExchangeConnectionService::getAdapter() integration
5. ✅ Normalized paths (all use `main/addons/...`)
6. ✅ Concrete verification steps

---

## Phase 0: Infrastructure Setup

### Task 0.1: Update phpunit.xml

**File**: `main/phpunit.xml`

**Exact Changes**:
```xml
<!-- Add Integration test suite -->
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Integration">  <!-- NEW -->
        <directory suffix="Test.php">./tests/Integration</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
</testsuites>

<!-- Add addon coverage -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <include>                                            <!-- NEW -->
        <directory suffix=".php">./addons/trading-management-addon/Modules</directory>
    </include>
</coverage>
```

**Verification Command**:
```bash
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Integration
# Expected: "No tests found in Integration suite" (suite exists but empty)
```

---

## Phase 1: Testing Infrastructure

### Task 1.1: Create TradingBotTestCase

**File**: `main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php`

**Exact Content**:
```php
<?php

namespace Tests\Unit\Addons\TradingManagement\TradingBot;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;
use Tests\RefreshDatabase;

abstract class TradingBotTestCase extends TestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function createMockTradingBot(array $overrides = []): TradingBot
    {
        return TradingBot::factory()->create(array_merge([
            'status' => 'created',
            'is_paper_trading' => true,
        ], $overrides));
    }

    protected function createRunningBot(): TradingBot
    {
        return $this->createMockTradingBot(['status' => 'running']);
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php
# Expected: 1 test (class exists, abstract test)
```

---

## Phase 2: Dynamic Configuration

### Task 2.1: Create ConfigManager Service

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php`

**Note**: CREATE NEW DIRECTORY `Services/ConfigManager/`

**Exact Content**:
```php
<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TradingBotConfigManager
{
    public function updateConfig(TradingBot $bot, array $config): void
    {
        DB::transaction(function () use ($bot, $config) {
            // Update TradingPreset (real relationship: bot->tradingPreset)
            $bot->tradingPreset->update($config);
            
            // Invalidate cache
            Cache::forget("bot_config:{$bot->id}");
            
            // Publish hot-reload event
            if ($bot->status === 'running') {
                Redis::publish("bot:{$bot->id}:config", json_encode([
                    'event' => 'config_updated',
                    'timestamp' => now()->toIso8601String(),
                ]));
            }
        });
    }

    public function getRuntimeConfig(TradingBot $bot): array
    {
        return Cache::remember(
            "bot_config:{$bot->id}",
            3600,
            fn() => $this->buildRuntimeConfig($bot)
        );
    }

    private function buildRuntimeConfig(TradingBot $bot): array
    {
        $preset = $bot->tradingPreset;
        return [
            'risk_per_trade_pct' => $preset->risk_per_trade_pct,
            'sl_mode' => $preset->sl_mode,
            'sl_pips' => $preset->sl_pips,
            'tp_mode' => $preset->tp_mode,
            'tp_pips' => $preset->tp_pips,
        ];
    }
}
```

**Verification**:
```bash
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/ConfigManager/ConfigManagerTest.php
# Expected: All ConfigManager tests pass
```

---

### Task 2.2: Create BotConfigListenerJob

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php`

**Exact Content**:
```php
<?php

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class BotConfigListenerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $botId;
    public string $action; // 'subscribe' or 'unsubscribe'

    public function __construct(int $botId, string $action = 'subscribe')
    {
        $this->botId = $botId;
        $this->action = $action;
    }

    public function handle(): void
    {
        if ($this->action === 'unsubscribe') {
            Redis::unsubscribe(["bot:{$this->botId}:config"]);
            return;
        }

        $bot = TradingBot::find($this->botId);
        if (!$bot || !in_array($bot->status, ['running', 'paused'])) {
            return;
        }

        // Subscribe with callback
        Redis::subscribe(
            ["bot:{$this->botId}:config"],
            function ($message) {
                $data = json_decode($message, true);
                if (($data['event'] ?? null) === 'config_updated') {
                    Cache::forget("bot_config:{$this->botId}");
                }
            }
        );
    }
}
```

---

### Task 2.3: Integrate Listener into TradingBotWorkerJob

**File**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php`

**Current Code** (lines 40-70, simplified):
```php
public function handle(): void
{
    $bot = $this->getBot();
    
    while (!$this->shouldExit()) {  // Real loop condition
        if ($this->isStopped() || $this->isPaused()) {
            $this->handleStopOrPause();
            return;
        }
        
        $bot->refresh();  // Already picks up DB changes
        
        // Process signals using configured worker
        $worker = $this->getConfiguredWorker();
        $worker->execute($bot);
        
        sleep($bot->check_interval ?? 5);
    }
}
```

**Required Modification** (ADD listener lifecycle):
```php
public function handle(): void
{
    $bot = $this->getBot();
    
    // START listener (NEW)
    $listener = new BotConfigListenerJob($bot->id, 'subscribe');
    dispatch($listener)->onQueue('listeners');
    
    try {
        while (!$this->shouldExit()) {
            if ($this->isStopped() || $this->isPaused()) {
                // STOP listener before pausing/stopping (NEW)
                dispatch(new BotConfigListenerJob($bot->id, 'unsubscribe'))->onQueue('listeners');
                $this->handleStopOrPause();
                return;
            }
            
            $bot->refresh();
            $worker = $this->getConfiguredWorker();
            $worker->execute($bot);
            
            sleep($bot->check_interval ?? 5);
        }
    } finally {
        // Ensure listener stops even on error
        dispatch(new BotConfigListenerJob($bot->id, 'unsubscribe'))->onQueue('listeners');
    }
}
```

**Line Reference**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php`

**Verification**:
```bash
# Check that listener job is dispatched
grep -n "BotConfigListenerJob" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php
# Expected: 3 occurrences (dispatch subscribe, dispatch unsubscribe, finally unsubscribe)
```

---

## Phase 3: Multi-Market Support

### Task 3.1: Create MarketRouter (WRAPS existing adapter selection)

**File**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php`

**Key Integration**: WRAPS `ExchangeConnectionService::getAdapter()`, not replaces it.

**Exact Content**:
```php
<?php

namespace Addons\TradingManagement\Modules\MarketRouter\Services;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;

class MarketRouter
{
    public function __construct(
        private ExchangeConnectionService $connectionService,
        private SymbolNormalizer $symbolNormalizer,
        private TradingHoursService $tradingHours
    ) {}

    public function getAdapter(ExchangeConnection $connection): object
    {
        // WRAPS existing adapter selection, adds market-type awareness
        return $this->connectionService->getAdapter($connection);
    }

    public function normalizeSymbol(string $symbol, string $marketType): string
    {
        return $this->symbolNormalizer->normalize($symbol, $marketType);
    }

    public function isMarketOpen(string $marketType, ?string $symbol = null): bool
    {
        return match ($marketType) {
            'crypto' => true, // 24/7
            'fx' => $this->tradingHours->isOpen($symbol),
        };
    }

    public function calculateLotSize(
        string $marketType,
        float $amount,
        string $symbol,
        ExchangeConnection $connection
    ): float {
        // Delegates to existing risk services via TradingPreset
        $preset = $connection->tradingPreset ?? null;
        if ($preset) {
            return $this->calculateRiskBasedLotSize($amount, $symbol, $preset);
        }
        // Fallback: simple lot calculation
        return $amount / $this->getSymbolPrice($symbol);
    }

    private function calculateRiskBasedLotSize(
        float $amount,
        string $symbol,
        object $preset
    ): float {
        // Uses existing RiskCalculatorService
        $calculator = app(\Addons\TradingManagement\Modules\RiskManagement\Services\RiskCalculatorService::class);
        return $calculator->calculateLotSize($amount, $symbol, $preset);
    }
}
```

**Integration Point**: `ExchangeConnectionService::getAdapter()` at `main/addons/trading-management-addon/Modules/ExchangeConnection/Services/ExchangeConnectionService.php:155-216`

---

## Phase 4: Fix Demo Trading

### Task 4.1: Fix ExecutionJob - Remove Early Return

**File**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`

**Current Code** (lines 79-91):
```php
// Validate market status before execution (skip in paper trading mode)
$isTestMode = $this->executionData['is_paper_trading'] ?? false;

// Paper trading mode: Use virtual positions created via InternalBrokerService
// (already works for manual trading via TradingTerminalController)
if ($isTestMode) {
    Log::warning('Paper trading mode: Use virtual positions...');
    return;  // <-- PROBLEM: Early return, doesn't call InternalBrokerService
}
```

**Required Modification** (REPLACE lines 79-91):
```php
// Handle paper trading via InternalBrokerService
$isTestMode = $this->executionData['is_paper_trading'] ?? false;

if ($isTestMode) {
    $user = User::find($this->executionData['user_id']);
    $broker = app(\App\Services\InternalBrokerService::class);
    
    $trade = $broker->placeOrder(
        $user,
        $this->executionData['symbol'],
        $this->executionData['direction'],
        $this->executionData['quantity'],
        $this->executionData['price'],
        $this->executionData['sl_price'] ?? null,
        $this->executionData['tp_price'] ?? null,
        true // isPaper = true
    );
    
    $this->createVirtualPosition($trade);
    return;
}
```

**Line Reference**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php:79-91`

**Verification**:
```bash
grep -n "InternalBrokerService" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php
# Expected: Import at top, and call to placeOrder() in handle()
```

---

## Phase 5: Feature Tests

### Task 5.1: Bot CRUD Test (User Routes)

**File**: `main/tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php`

**Tests**: User panel routes at `main/addons/trading-management-addon/routes/user.php`

**Exact Content**:
```php
<?php

namespace Tests\Feature\Addons\TradingManagement\TradingBot;

use App\Models\User;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Tests\TestCase;

class BotCrudTest extends TestCase
{
    public function test_user_can_view_bot_list(): void
    {
        $user = User::factory()->create();
        TradingBot::factory()->count(3)->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->get('/user/trading-management/trading-bots');
        
        $response->assertOk();
        $response->assertSee('trading-bots');
    }
    
    public function test_user_can_create_bot(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/user/trading-management/trading-bots', [
                'name' => 'Test Bot',
                'exchange_connection_id' => 1,
                'trading_preset_id' => 1,
                'is_paper_trading' => true,
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('trading_bots', [
            'user_id' => $user->id,
            'name' => 'Test Bot',
        ]);
    }
}
```

---

## Verification Commands (Complete)

```bash
# Phase 0: Infrastructure
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Integration
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot --coverage --min=80

# Phase 1: Test Infrastructure
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php

# Phase 2: Dynamic Config
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/ConfigManager/
grep -n "BotConfigListenerJob" main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php

# Phase 3: Multi-Market
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/

# Phase 4: Demo Trading
grep -n "InternalBrokerService" main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php

# Phase 5: Feature Tests
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php

# Full Test Suite
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot --coverage --min=80
```

---

## File Path Reference (All Paths Verified)

| Component | Path |
|-----------|------|
| InternalBrokerService | `main/app/Services/InternalBrokerService.php` |
| TradingBotWorkerJob | `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php` |
| ExecutionJob | `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php` |
| ExchangeConnectionService | `main/addons/trading-management-addon/Modules/ExchangeConnection/Services/ExchangeConnectionService.php` |
| CcxtAdapter | `main/addons/trading-management-addon/Modules/DataProvider/Adapters/CcxtAdapter.php` |
| MetaApiAdapter | `main/addons/trading-management-addon/Modules/DataProvider/Adapters/MetaApiAdapter.php` |
| TradingBot Model | `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php` |
| TradingPreset Model | `main/addons/trading-management-addon/Modules/RiskManagement/Models/TradingPreset.php` |

---

## Change History

- **2026-01-21 v4**: FINAL - All Momus issues resolved with verified paths and exact code modifications
  - ✅ InternalBrokerService::placeOrder() (real method)
  - ✅ TradingBotWorkerJob integration (real loop condition)
  - ✅ ExchangeConnectionService::getAdapter() wrapping
  - ✅ Normalized paths (all `main/addons/...`)
  - ✅ Concrete verification commands
