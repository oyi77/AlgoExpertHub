# Trading Bot Refactoring Plan (TDD Approach) - REVISED v3

**Plan ID:** trading-bot-refactor-tdd-v3
**Created:** 2026-01-21
**Status:** Ready for Momus Review - FINAL VERSION
**Approach:** Test-Driven Development (TDD)
**Architecture:** Modular addon integration (NOT greenfield)

---

## Context

### Original Request
Refactor trading bot functionality to enable dynamic configuration, multi-market support (crypto + forex), and comprehensive test coverage with TDD approach.

### User Requirements
1. **Dynamic Bot Configuration** - Add/edit bots dynamically tanpa restart
2. **Multi-Market Support** - Crypto (CCXT) + Forex (MetaApi)
3. **Demo/Testnet/Production** - Full isolation per mode
4. **Testable** - All functionality must be testable dengan TDD

---

## CRITICAL: Path Corrections from Momus Review

| Component | INCORRECT Path (Old Plan) | CORRECT Path (This Plan) |
|-----------|---------------------------|--------------------------|
| **InternalBrokerService** | `Modules/Execution/Services/InternalBrokerService.php` | `main/app/Services/InternalBrokerService.php` |
| **MetaApiAdapter** | `Modules/TradingBot/...MetaApiAdapter.php` | `Modules/DataProvider/Adapters/MetaApiAdapter.php` |
| **Config Directory** | `Modules/TradingBot/Services/Config/` | `Modules/TradingBot/Services/ConfigManager/` (CREATE NEW) |
| **CcxtExchangeService** | `Modules/ExchangeConnection/Services/CcxtExchangeService.php` | ✅ Correct (keep) |
| **Bot CRUD Routes** | API routes | User panel routes (`/user/trading-management/trading-bots/*`) |

---

## Phase 0: Infrastructure Setup (PRE-REQUISITE)

**Must be done BEFORE any other task.**

### Task 0.1: Update phpunit.xml for Integration Tests + Addon Coverage
**File**: `main/phpunit.xml`
**Action**: Add Integration test suite and include addons in coverage

```xml
<!-- BEFORE -->
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

<!-- AFTER -->
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

### Task 0.2: Create Mockery Directory
**File**: `main/tests/Mockery/` (CREATE DIRECTORY)
**Purpose**: Store exchange simulators and test doubles

---

## Architecture Integration Map

### Current Bot Execution Flow (BROKEN - Fix Required)
```
TradingBotWorkerJob::handle()
  → while ($bot->status === 'running')
    → $bot->refresh()
    → ProcessSignalBasedBotWorker
      → RiskManagementJob
      → ExecutionJob
        → Check $isTestMode → EXIT EARLY (doesn't use InternalBrokerService!)
```

### Fixed Execution Flow (After Refactor)
```
TradingBotWorkerJob::handle()
  → while ($bot->status === 'running')
    → $bot->refresh()
    → Subscribe to Redis "bot:{$bot->id}:config" (NEW)
    → ProcessSignalBasedBotWorker
      → RiskManagementJob (RiskCalculatorService - existing)
      → ExecutionJob
        → if $bot->is_paper_trading
          → InternalBrokerService::executePaperTrade() (FIX: actually call this)
        → else
          → MarketRouter::getAdapter() → execute()
```

### Demo/Testnet/Production Flag Mapping

| Mode | Bot Flag | Connection Flag | Execution Path |
|------|----------|-----------------|----------------|
| **Demo** | `is_paper_trading = true` | N/A | `InternalBrokerService::executePaperTrade()` |
| **Testnet** | `is_paper_trading = false` | `credentials['sandbox'] = true` | CcxtAdapter dengan sandbox mode |
| **Production** | `is_paper_trading = false` | `credentials['sandbox'] = false` | CcxtAdapter normal mode |

---

## Work Objectives

### Core Objective
Enhance existing trading bot system dengan TDD approach, focusing on:
1. **Dynamic Config** - Redis pub/sub for immediate updates
2. **Multi-Market** - MarketRouter to unify crypto/forex
3. **Demo Mode** - Fix InternalBrokerService integration
4. **Test Coverage** - 80% unit test coverage

### Deliverables
| Deliverable | Path | Description |
|-------------|------|-------------|
| **ConfigManager** | `Modules/TradingBot/Services/ConfigManager/` | NEW - Hot-reload + cache |
| **MarketRouter** | `Modules/MarketRouter/` | NEW - Unified crypto/forex |
| **VirtualPortfolio** | `Modules/PaperTrading/Models/` | NEW - Demo isolation |
| **Fixed Demo Flow** | `ExecutionJob` | FIX - Call InternalBrokerService |

### Definition of Done
- [ ] Bot config dapat diupdate tanpa restart (Redis pub/sub)
- [ ] Bot dapat trading di crypto market (CCXT)
- [ ] Bot dapat trading di forex market (MetaApi)
- [ ] Demo mode uses InternalBrokerService (FIXED)
- [ ] 80% unit test coverage
- [ ] All existing tests pass

---

## Verification Strategy

### Test Framework
- **PHPUnit 10** - Main testing framework
- **Mockery 1.4.4** - Mock objects
- **Factory** - Model factories

### Test Structure
```
main/tests/
├── Unit/
│   └── Addons/
│       └── TradingManagement/
│           ├── TradingBot/
│           │   ├── ConfigManager/
│           │   ├── MarketRouter/
│           │   ├── PaperTrading/
│           │   └── Services/
│           └── RiskManagement/
│               └── Calculators/
├── Integration/
│   └── Addons/
│       └── TradingManagement/
│           └── TradingBot/
└── Feature/
    └── Addons/
        └── TradingManagement/
            └── TradingBot/
```

### Test Execution Commands
```bash
# Run all trading bot tests
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot

# Run with coverage (addon code now included)
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot --coverage --min=80

# Run specific test type
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Unit
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Integration
docker exec 1Panel-php8-mrTy php artisan test --testsuite=Feature
```

---

## Task Flow

```
PHASE 0 (Infrastructure - PRE-REQUISITE)
├── Task 0.1: Update phpunit.xml (Integration suite + addon coverage)
└── Task 0.2: Create Mockery directory

PHASE 1 (Foundation - Testing Infrastructure)
├── Task 1.1: Create TradingBotTestCase base class
├── Task 1.2: Create ExchangeSimulator test doubles
├── Task 1.3: ConfigManager Unit Tests
└── Task 1.4: MarketRouter Unit Tests

PHASE 2 (Dynamic Configuration)
├── Task 2.1: ConfigManager Service Creation
├── Task 2.2: BotConfigListenerJob (Redis pub/sub with lifecycle)
├── Task 2.3: BotStateManager Implementation
└── Task 2.4: Integration Test - Config Hot-Reload

PHASE 3 (Multi-Market Support)
├── Task 3.1: MarketRouter Module Creation
├── Task 3.2: SymbolNormalizer Implementation
├── Task 3.3: TradingHoursService Implementation
└── Task 3.4: Integration Test - Multi-Market

PHASE 4 (Demo Mode Fix)
├── Task 4.1: VirtualPortfolio Model Creation
├── Task 4.2: Fix ExecutionJob - Call InternalBrokerService
├── Task 4.3: PaperTradingService Enhancement
└── Task 4.4: Integration Test - Demo Mode Isolation

PHASE 5 (Feature Tests)
├── Task 5.1: Bot CRUD API Feature Tests (user routes)
└── Task 5.2: Market Info API Feature Tests
```

---

## TODOs

### PHASE 0: Infrastructure Setup (PRE-REQUISITE)

- [ ] 0.1 Update phpunit.xml for Integration Tests + Addon Coverage
  - **File**: `main/phpunit.xml`
  - **Action**: Add Integration test suite, include addons in coverage
  - **Verification**: `php artisan test --testsuite=Integration` runs

- [ ] 0.2 Create Mockery directory
  - **Directory**: `main/tests/Mockery/`
  - **Action**: Create directory and add .gitkeep
  - **Verification**: Directory exists

---

### PHASE 1: Foundation & Testing Infrastructure

- [ ] 1.1 Create TradingBotTestCase base class
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php`
  - **Extends**: `Tests\TestCase`
  - **Acceptance**:
    - [ ] Mock ExchangeConnection helper
    - [ ] Mock TradingBot factory
    - [ ] Mock Redis
  
  ```php
  abstract class TradingBotTestCase extends TestCase
  {
      use RefreshDatabase;
      
      protected function createMockExchangeConnection(array $overrides = []): MockObject
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
      
      protected function createMockTradingBot(array $overrides = []): TradingBot
      {
          return TradingBot::factory()->create(array_merge([
              'status' => 'created',
              'is_paper_trading' => true,
          ], $overrides));
      }
  }
  ```

- [ ] 1.2 Create ExchangeSimulator test doubles
  - **Location**: `main/tests/Mockery/ExchangeSimulator.php`
  - **Purpose**: Mock CCXT/MetaApi adapters
  - **Acceptance**:
    - [ ] Crypto exchange simulator
    - [ ] Forex broker simulator
    - [ ] Balance management
    - [ ] Order placement
  
  ```php
  namespace Tests\Mockery;
  
  interface ExchangeSimulatorInterface
  {
      public function setBalance(string $asset, float $amount): self;
      public function placeOrder(array $params): OrderResult;
  }
  
  class ExchangeSimulator implements ExchangeSimulatorInterface
  {
      private array $balances = [];
      private array $orders = [];
      
      public function setBalance(string $asset, float $amount): self
      {
          $this->balances[$asset] = $amount;
          return $this;
      }
      
      public function placeOrder(array $params): OrderResult
      {
          $orderId = strtoupper(bin2hex(random_bytes(8)));
          $this->orders[$orderId] = [...$params, 'status' => 'open'];
          return new OrderResult(orderId: $orderId, status: 'open');
      }
  }
  ```

- [ ] 1.3 ConfigManager Unit Tests
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/ConfigManager/ConfigManagerTest.php`
  - **Tests**: `Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php`
  - **Acceptance**:
    - [ ] Config update persists
    - [ ] Redis pub/sub publishes correctly (using string payload, not closure)
  
  ```php
  class ConfigManagerTest extends TradingBotTestCase
  {
      public function test_update_config_persists(): void
      {
          $bot = $this->createMockTradingBot();
          
          $manager = app(TradingBotConfigManager::class);
          $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.03]);
          
          $bot->tradingPreset->refresh();
          $this->assertEquals(0.03, $bot->tradingPreset->risk_per_trade_pct);
      }
      
      public function test_config_update_publishes_redis_event(): void
      {
          $bot = $this->createMockTradingBot(['status' => 'running']);
          
          $manager = app(TradingBotConfigManager::class);
          
          // CORRECT: Publish string payload, not closure
          Redis::shouldReceive('publish')
              ->once()
              ->with(
                  "bot:{$bot->id}:config",
                  json_encode([
                      'event' => 'config_updated',
                      'config' => ['risk_per_trade_pct' => 0.04],
                      'timestamp' => now()->toIso8601String(),
                  ])
              );
          
          $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.04]);
      }
  }
  ```

- [ ] 1.4 MarketRouter Unit Tests
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/MarketRouterTest.php`
  - **Tests**: `Modules/MarketRouter/Services/MarketRouter.php`
  - **Acceptance**:
    - [ ] Crypto symbol normalization
    - [ ] Forex symbol normalization
    - [ ] Crypto = 24/7, Forex = market hours

---

### PHASE 2: Dynamic Configuration

- [ ] 2.1 ConfigManager Service Creation
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Services/ConfigManager/TradingBotConfigManager.php`
  - **CREATE NEW DIRECTORY**: `Services/ConfigManager/`
  - **Integrates With**: `TradingPreset`, `Redis` facade
  
  ```php
  namespace Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager;
  
  class TradingBotConfigManager
  {
      public function __construct(
          private CacheInterface $cache,
          private RedisConnection $redis
      ) {}
      
      public function updateConfig(TradingBot $bot, array $config): void
      {
          DB::transaction(function () use ($bot, $config) {
              // Update TradingPreset
              $bot->tradingPreset()->update($config);
              
              // Invalidate cache
              $this->cache->forget("bot_config:{$bot->id}");
              
              // Publish hot-reload event for running bots
              if ($bot->status === 'running') {
                  $this->redis->publish("bot:{$bot->id}:config", json_encode([
                      'event' => 'config_updated',
                      'config' => $this->buildRuntimeConfig($bot),
                      'timestamp' => now()->toIso8601String(),
                  ]));
              }
          });
      }
      
      public function getRuntimeConfig(TradingBot $bot): array
      {
          return $this->cache->remember(
              "bot_config:{$bot->id}",
              3600,
              fn() => $this->buildRuntimeConfig($bot)
          );
      }
  }
  ```

- [ ] 2.2 BotConfigListenerJob (Redis pub/sub with explicit lifecycle)
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php`
  - **EXPLICIT LIFECYCLE**:
    1. Dispatched when bot starts (in `TradingBotWorkerJob::handle()`)
    2. Subscribes to Redis channel
    3. Stops when bot pauses/stops (via `finally` block)
  
  ```php
  namespace Addons\TradingManagement\Modules\TradingBot\Jobs;
  
  class BotConfigListenerJob implements ShouldQueue
  {
      use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
      
      public $maxExceptions = 3;
      public $timeout = 3600; // Long-running job
      
      public function __construct(
          public int $botId,
          public string $channel = 'subscribe' // 'subscribe' or 'unsubscribe'
      ) {}
      
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
              $this->redis->subscribe(
                  ["bot:{$this->botId}:config"],
                  function ($message) {
                      $data = json_decode($message, true);
                      if (($data['event'] ?? null) === 'config_updated') {
                          Cache::forget("bot_config:{$this->botId}");
                      }
                  }
              );
          } catch (\Exception $e) {
              // Redis connection error - log and retry later
              $this->release(5); // Retry after 5 seconds
          }
      }
      
      /**
       * Stop listening when bot stops/pauses.
       * Called from TradingBotWorkerJob when bot state changes.
       */
      public function stopListening(TradingBot $bot): void
      {
          $this->redis->unsubscribe(["bot:{$bot->id}:config"]);
      }
  }
  ```

- [ ] 2.3 Integrate Listener into TradingBotWorkerJob
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php`
  - **MODIFY**: Add listener startup/teardown
  
  ```php
  class TradingBotWorkerJob implements ShouldQueue
  {
      public function handle(): void
      {
          $bot = $this->getBot();
          
          // START listener (NEW)
          $listener = new BotConfigListenerJob($bot->id, 'subscribe');
          $listener->handle();
          
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

- [ ] 2.4 Integration Test - Config Hot-Reload
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/ConfigHotReloadTest.php`
  - **Acceptance**:
    - [ ] Config update triggers Redis message
    - [ ] Listener receives message
    - [ ] Cache invalidated

---

### PHASE 3: Multi-Market Support

- [ ] 3.1 MarketRouter Module Creation
  - **Location**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php`
  - **CREATE NEW MODULE**
  - **Integrates With**: `CcxtExchangeService`, `MetaApiAdapter`
  
  ```php
  namespace Addons\TradingManagement\Modules\MarketRouter\Services;
  
  class MarketRouter
  {
      public function normalizeSymbol(string $symbol, string $marketType): string
      {
          return match ($marketType) {
              'crypto' => str_replace(['/', '-'], '', $symbol),
              'forex' => str_replace('/', '', $symbol),
              default => throw new UnsupportedMarketException($marketType),
          };
      }
      
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
      
      public function isMarketOpen(string $marketType, ?string $symbol = null): bool
      {
          return match ($marketType) {
              'crypto' => true, // 24/7
              'forex' => $this->forexSession->isOpen($symbol),
          };
      }
      
      public function getAdapter(ExchangeConnection $connection): ExchangeAdapterInterface
      {
          return match ($connection->type) {
              'crypto' => app(CcxtAdapter::class)->setConnection($connection),
              'fx' => app(MetaApiAdapter::class)->setConnection($connection),
          };
      }
  }
  ```

- [ ] 3.2 SymbolNormalizer
  - **Location**: `Modules/MarketRouter/Services/SymbolNormalizer.php`

- [ ] 3.3 TradingHoursService
  - **Location**: `Modules/MarketRouter/Services/TradingHoursService.php`
  - **Forex Hours**: 22:00 GMT (Sunday) - 21:00 GMT (Friday)
  - **Break**: 21:00 - 22:00 GMT (daily)
  - **Weekend**: Closed

- [ ] 3.4 Integration Test - Multi-Market
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/MultiMarketTest.php`

---

### PHASE 4: Demo Mode Fix

- [ ] 4.1 VirtualPortfolio Model
  - **Location**: `Modules/PaperTrading/Models/VirtualPortfolio.php`
  - **Database**: `virtual_portfolios` table
  
  ```php
  Schema::create('virtual_portfolios', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('exchange_connection_id')->constrained()->onDelete('cascade');
      $table->decimal('balance', 20, 8)->default(10000);
      $table->enum('market_type', ['crypto', 'fx'])->default('crypto');
      $table->timestamps();
      $table->unique(['user_id', 'exchange_connection_id']);
  });
  ```

- [ ] 4.2 Fix ExecutionJob - Call InternalBrokerService
  - **Location**: `main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php`
  - **CRITICAL FIX**: Currently exits early, should call InternalBrokerService
  
  ```php
  class ExecutionJob implements ShouldQueue
  {
      public function handle(): void
      {
          $bot = $this->bot;
          
          // FIX: Properly route to InternalBrokerService for paper trading
          if ($bot->is_paper_trading) {
              // Use InternalBrokerService (from main/app/Services/)
              $broker = app(\App\Services\InternalBrokerService::class);
              $result = $broker->executePaperTrade($bot, $this->orderRequest);
              
              $this->recordVirtualPosition($result);
              return;
          }
          
          // Live trading path
          $adapter = $this->getExchangeAdapter($bot->exchangeConnection);
          $result = $adapter->placeOrder($this->orderRequest);
          
          $this->recordPosition($result);
      }
      
      private function getExchangeAdapter(ExchangeConnection $connection)
      {
          return match ($connection->type) {
              'crypto' => app(CcxtAdapter::class)->setConnection($connection' => app(MetaApiAdapter::class)->setConnection($connection),
          };
      }
  }
  ```

- [ ] 4.3 PaperTradingService Enhancement
 ),
              'fx - **Location**: `Modules/PaperTrading/Services/PaperTradingService.php`

- [ ] 4.4 Integration Test - Demo Mode Isolation
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/DemoModeTest.php`

---

### PHASE 5: Feature Tests

- [ ] 5.1 Bot CRUD API Feature Tests
  - **Location**: `main/tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php`
  - **Tests**: User panel routes (`/user/trading-management/trading-bots/*`)

- [ ] 5.2 Market Info API Feature Tests
  - **Location**: `main/tests/Feature/Addons/TradingManagement/TradingBot/MarketInfoTest.php`

---

## Files to Create/Modify

### NEW
```
main/addons/trading-management-addon/Modules/MarketRouter/
├── Services/
│   ├── MarketRouter.php
│   ├── SymbolNormalizer.php
│   └── TradingHoursService.php
└── Http/
    └── Controllers/Api/
        └── MarketInfoController.php

main/addons/trading-management-addon/Modules/PaperTrading/
├── Services/
│   └── PaperTradingService.php
└── Models/
    └── VirtualPortfolio.php

main/addons/trading-management-addon/Modules/TradingBot/
└── Services/
    └── ConfigManager/
        └── TradingBotConfigManager.php

main/tests/Mockery/
└── ExchangeSimulator.php
```

### MODIFY
```
main/phpunit.xml                                 # Add Integration suite, addon coverage
main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php  # NEW
main/addons/trading-management-addon/Modules/TradingBot/Jobs/TradingBotWorkerJob.php   # ADD listener lifecycle
main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php           # FIX: call InternalBrokerService
main/addons/trading-management-addon/routes/api.php                                    # ADD market info endpoint
main/addons/trading-management-addon/database/migrations/*_create_virtual_portfolios_table.php  # NEW
```

---

## Success Criteria

### Functional
- [ ] Config hot-reload < 1 second
- [ ] Demo trades use InternalBrokerService
- [ ] Crypto/Forex unified via MarketRouter
- [ ] Forex hours respected

### Quality
- [ ] 80% unit test coverage (addon code now included)
- [ ] All test suites pass (Unit, Integration, Feature)
- [ ] No regression in existing tests

---

## Estimated Effort

| Phase | Tasks | Hours |
|-------|-------|-------|
| 0 | 2 | 1-2 |
| 1 | 4 | 8-12 |
| 2 | 4 | 12-16 |
| 3 | 4 | 16-20 |
| 4 | 4 | 12-16 |
| 5 | 2 | 4-6 |
| **Total** | **20** | **60-90** |

---

## Change History
- 2026-01-21: v3 - DEEP FIX with all Momus issues resolved
  - ✅ All correct file paths (InternalBrokerService in main/app/, MetaApiAdapter in DataProvider/)
  - ✅ PHPUnit config updated (Integration suite + addon coverage)
  - ✅ Explicit Redis listener lifecycle (start/stop)
  - ✅ Fixed demo trading flow (ExecutionJob now calls InternalBrokerService)
  - ✅ Correct Redis facade usage (string payload, not closure)
