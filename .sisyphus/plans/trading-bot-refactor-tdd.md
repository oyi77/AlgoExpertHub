# Trading Bot Refactoring Plan (TDD Approach) - REVISED

**Plan ID:** trading-bot-refactor-tdd-v2
**Created:** 2026-01-20
**Status:** Ready for Momus Review
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

### Critical Discovery: Existing Architecture

After deep analysis of `main/addons/trading-management-addon/Modules/`, the system **already has** substantial functionality:

| Component | Location | Status | Notes |
|-----------|----------|--------|-------|
| **TradingBot Worker** | `Modules/TradingBot/Jobs/TradingBotWorkerJob.php` | ✅ Exists | Already refreshes `$bot` each loop (DB-based config pick-up) |
| **RiskCalculator** | `Modules/RiskManagement/Services/RiskCalculatorService.php` | ✅ Exists | Preset + Smart calculators available |
| **Paper Trading** | `Modules/TradingBot/Services/BotExecutionService.php` | ⚠️ Partial | Uses `is_paper_trading` flag + `InternalBrokerService` |
| **Exchange Connections** | `Modules/ExchangeConnection/` | ✅ Exists | `is_testnet` in credentials, `is_paper_trading` in `execution_settings` |
| **Trailing Stop** | `TradingPreset` model + `PositionMonitoringService` | ⚠️ Partial | Fields exist (`ts_enabled`), logic may need verification |

**KEY INSIGHT**: The plan must **extend and enhance** existing components, NOT replace them.

---

## Work Objectives

### Core Objective
Enhance existing trading bot system dengan TDD approach, focusing on:
1. **Dynamic Config** - Enhance existing DB-based config with Redis pub/sub for immediate updates
2. **Multi-Market** - Build MarketRouter to unify crypto/forex handling
3. **Demo Mode** - Strengthen paper trading isolation and VirtualPortfolio support
4. **Test Coverage** - Achieve 80% unit test coverage for new/enhanced functionality

### Deliverables (Grounded in Existing Architecture)

| Deliverable | Path | Description |
|-------------|------|-------------|
| **ConfigManager Enhancement** | `Modules/TradingBot/Services/Config/` | Extends existing `TradingBotService` with hot-reload |
| **MarketRouter Module** | `Modules/MarketRouter/` | NEW - Unified crypto/forex interface |
| **VirtualPortfolio Model** | `Modules/PaperTrading/Models/` | NEW - Strengthens demo mode isolation |
| **Test Infrastructure** | `main/tests/Unit/Addons/TradingManagement/` | Unit tests for enhanced components |

### Definition of Done
- [ ] Bot config dapat diupdate tanpa restart (Redis pub/sub)
- [ ] Bot dapat trading di crypto market (existing CCXT)
- [ ] Bot dapat trading di forex market (existing MetaApi)
- [ ] Demo mode uses virtual balance (enhanced VirtualPortfolio)
- [ ] 80% unit test coverage for new/enhanced code
- [ ] All existing tests pass (no regression)

---

## Verification Strategy

### Test Framework
- **PHPUnit 10** - Main testing framework (existing)
- **Mockery 1.4.4** - Mock objects for exchange APIs (existing)
- **Factory** - Model factories for test data (existing)

### Test Structure (CORRECT PATHS - from deep analysis)
```
main/tests/
├── Unit/
│   └── Addons/
│       └── TradingManagement/
│           ├── TradingBot/
│           │   ├── ConfigManagement/
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

# Run with coverage
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot --coverage --min=80

# Run specific test class
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/ConfigManagement/TradingBotConfigManagerTest.php
```

---

## Existing Architecture Integration Map

### Current TradingBot Worker Flow
```
TradingBotWorkerJob::handle()
  → while ($bot->status === 'running')
    → $bot->refresh()  // Already picks up DB changes!
    → ProcessSignalBasedBotWorker
      → RiskManagementJob (RiskCalculatorService)
      → ExecutionJob (CcxtAdapter / MetaApiAdapter)
    → PositionMonitoringService
```

### Enhanced Flow (After Refactor)
```
TradingBotWorkerJob::handle()
  → while ($bot->status === 'running')
    → $bot->refresh()  // DB-based config (existing)
    → Subscribe to Redis channel "bot:{$bot->id}:config"  // NEW: Immediate updates
    → ProcessSignalBasedBotWorker
      → RiskManagementJob (RiskCalculatorService - existing)
        → Enhanced with SmartRiskCalculator (existing)
      → ExecutionJob
        → MarketRouter::getAdapter()  // NEW: Unified interface
          → CcxtAdapter (crypto - existing)
          → MetaApiAdapter (forex - existing)
    → PositionMonitoringService (existing)
```

---

## Task Flow

```
PHASE 1 (Foundation - Testing Infrastructure)
├── Task 1.1: Create TradingBotTestCase base class
├── Task 1.2: Create ExchangeSimulator test doubles
├── Task 1.3: ConfigManager Unit Tests (extends existing)
└── Task 1.4: MarketRouter Unit Tests (new module)

PHASE 2 (Dynamic Configuration - Enhancement)
├── Task 2.1: TradingBotConfigManager Enhancement
├── Task 2.2: BotConfigListenerJob (Redis pub/sub)
├── Task 2.3: BotStateManager Implementation (NEW)
└── Task 2.4: Integration Test - Config Hot-Reload

PHASE 3 (Multi-Market Support - NEW Module)
├── Task 3.1: MarketRouter Module Creation
├── Task 3.2: SymbolNormalizer Implementation
├── Task 3.3: TradingHoursService Implementation
└── Task 3.4: Integration Test - Multi-Market Execution

PHASE 4 (Demo Mode Enhancement)
├── Task 4.1: VirtualPortfolio Model Creation
├── Task 4.2: PaperTradingService Enhancement
├── Task 4.3: Integration Test - Demo Mode Isolation
└── Task 4.4: Test Existing RiskCalculator Integration

PHASE 5 (Feature Tests - End-to-End)
├── Task 5.1: Bot CRUD API Feature Tests (existing routes)
└── Task 5.2: Market Info API Feature Tests (new endpoint)
```

---

## Parallelization

| Group | Tasks | Reason |
|-------|-------|--------|
| A | 1.1, 1.2 | Independent setup tasks |
| B | 1.3, 1.4 | Both unit tests |
| C | 2.1, 2.2 | Config management related |
| D | 3.1, 3.2 | Market routing related |
| E | 4.1, 4.2 | Demo mode related |

| Task | Depends On | Reason |
|------|------------|--------|
| 2.1 | 1.3 | Requires test patterns |
| 2.2 | 2.1 | Requires ConfigManager |
| 2.3 | 1.1 | Requires base test case |
| 2.4 | 2.1, 2.2 | Requires both components |
| 3.1 | 1.4 | Requires test patterns |
| 3.3 | 3.1 | Requires MarketRouter |
| 3.4 | 3.1, 3.3 | Requires all market components |
| 4.1 | 1.2 | Requires VirtualPortfolio model |
| 4.2 | 4.1 | Requires VirtualPortfolio |
| 4.3 | 4.2 | Requires PaperTradingService |
| 4.4 | 1.1, existing RiskCalculator | Requires existing integration |
| 5.1 | 2.1, 2.3 | Requires Config + StateManager |
| 5.2 | 3.1, 3.3 | Requires MarketRouter |

---

## TODOs

### PHASE 1: Foundation & Testing Infrastructure

- [ ] 1.1 Create TradingBotTestCase base class
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php`
  - **Purpose**: Base test class for all TradingBot tests
  - **Extends**: `Tests\TestCase` (existing)
  - **Acceptance**:
    - [ ] Mock ExchangeConnection helper method
    - [ ] Mock TradingBot factory
    - [ ] Mock Redis connection
    - [ ] Inherits CreatesApplication trait
  
  **Test to Write First**:
  ```php
  // main/tests/Unit/Addons/TradingManagement/TradingBot/TradingBotTestCase.php
  abstract class TradingBotTestCase extends TestCase
  {
      use RefreshDatabase;
      
      protected function setUp(): void
      {
          parent::setUp();
          $this->prepareForTests();
      }
      
      protected function createMockExchangeConnection(array $overrides = []): MockObject
      {
          $mock = $this->mock(ExchangeConnection::class, function ($mock) use ($overrides) {
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
          
          return $mock;
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
  - **Purpose**: Mock CCXT/MetaApi/MT5 adapters for testing
  - **Acceptance**:
    - [ ] Crypto exchange simulator (CCXT)
    - [ ] Forex broker simulator (MetaApi/MT5)
    - [ ] Balance management
    - [ ] Order placement and fetching
    - [ ] Error simulation (insufficient balance, etc.)
  
  **Test to Write First**:
  ```php
  // main/tests/Mockery/ExchangeSimulator.php
  namespace Tests\Mockery;
  
  interface ExchangeSimulatorInterface
  {
      public function setBalance(string $asset, float $amount): self;
      public function placeOrder(array $params): OrderResult;
      public function fetchOrder(string $orderId): array;
      public function closePosition(string $positionId, float $quantity): OrderResult;
  }
  
  class ExchangeSimulator implements ExchangeSimulatorInterface
  {
      private array $balances = [];
      private array $orders = [];
      private array $positions = [];
      private string $marketType;
      
      public function __construct(string $marketType = 'crypto')
      {
          $this->marketType = $marketType;
      }
      
      public function setBalance(string $asset, float $amount): self
      {
          $this->balances[$asset] = $amount;
          return $this;
      }
      
      public function placeOrder(array $params): OrderResult
      {
          $orderId = $this->generateOrderId();
          $this->orders[$orderId] = [
              'id' => $orderId,
              'symbol' => $params['symbol'],
              'type' => $params['type'],
              'side' => $params['side'],
              'amount' => $params['amount'],
              'price' => $params['price'] ?? null,
              'status' => 'open',
              'filled' => 0,
              'market_type' => $this->marketType,
          ];
          
          return new OrderResult(
              orderId: $orderId,
              status: 'open',
              filledAmount: 0,
              averagePrice: null,
          );
      }
      
      private function generateOrderId(): string
      {
          return strtoupper(bin2hex(random_bytes(8)));
      }
  }
  ```

- [ ] 1.3 ConfigManager Unit Tests (Extends Existing)
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/ConfigManagement/TradingBotConfigManagerTest.php`
  - **Tests Existing**: `Modules/TradingBot/Services/Config/TradingBotConfigManager.php`
  - **Acceptance**:
    - [ ] Config update persists to database
    - [ ] Redis pub/sub publishing works
    - [ ] Cache management works
    - [ ] Atomic transaction handling
  
  **Test to Write First**:
  ```php
  // main/tests/Unit/Addons/TradingManagement/TradingBot/ConfigManagement/TradingBotConfigManagerTest.php
  class TradingBotConfigManagerTest extends TradingBotTestCase
  {
      public function test_update_config_persists_to_database(): void
      {
          $bot = $this->createMockTradingBot();
          $preset = $bot->tradingPreset;
          
          $configManager = app(\Addons\TradingManagement\Modules\TradingBot\Services\Config\TradingBotConfigManager::class);
          $configManager->updateConfig($bot, [
              'risk_per_trade_pct' => 0.03,
              'sl_mode' => 'PIPS',
              'sl_pips' => 15,
          ]);
          
          $preset->refresh();
          $this->assertEquals(0.03, $preset->risk_per_trade_pct);
          $this->assertEquals('PIPS', $preset->sl_mode);
      }
      
      public function test_config_update_for_running_bot_triggers_publish(): void
      {
          $bot = $this->createMockTradingBot(['status' => 'running']);
          
          $configManager = app(\Addons\TradingManagement\Modules\TradingBot\Services\Config\TradingBotConfigManager::class);
          
          Redis::shouldReceive('publish')
              ->once()
              ->with(
                  "bot:{$bot->id}:config",
                  \Closure::that(function ($callback) {
                      $testMessage = json_encode([
                          'event' => 'config_updated',
                          'data' => ['risk_per_trade_pct' => 0.04],
                      ]);
                      $callback($testMessage);
                      return true;
                  })
              );
          
          $configManager->updateConfig($bot, ['risk_per_trade_pct' => 0.04]);
      }
      
      public function test_get_runtime_config_returns_cached_config(): void
      {
          $bot = $this->createMockTradingBot();
          
          $configManager = app(\Addons\TradingManagement\Modules\TradingBot\Services\Config\TradingBotConfigManager::class);
          $config1 = $configManager->getRuntimeConfig($bot);
          $config2 = $configManager->getRuntimeConfig($bot);
          
          $this->assertEquals($config1, $config2);
          $this->assertArrayHasKey('risk_per_trade_pct', $config1);
      }
  }
  ```

- [ ] 1.4 MarketRouter Unit Tests (NEW Module)
  - **Location**: `main/tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/MarketRouterTest.php`
  - **Tests**: NEW `Modules/MarketRouter/Services/MarketRouter.php`
  - **Acceptance**:
    - [ ] Crypto symbol normalization works
    - [ ] Forex symbol normalization works
    - [ ] Market hours detection works (crypto = 24/7, forex = market hours)
    - [ ] Lot size calculations work (crypto raw amount, forex lots)
  
  **Test to Write First**:
  ```php
  // main/tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/MarketRouterTest.php
  class MarketRouterTest extends TradingBotTestCase
  {
      public function test_normalize_crypto_symbol(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          
          $normalized = $router->normalizeSymbol('BTC/USDT', 'crypto');
          $this->assertEquals('BTCUSDT', $normalized);
          
          $normalized2 = $router->normalizeSymbol('ETH-USDT', 'crypto');
          $this->assertEquals('ETHUSDT', $normalized2);
      }
      
      public function test_normalize_forex_symbol(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          
          $normalized = $router->normalizeSymbol('EUR/USD', 'forex');
          $this->assertEquals('EURUSD', $normalized);
      }
      
      public function test_crypto_is_always_open(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          
          $this->assertTrue($router->isMarketOpen('crypto', 'BTCUSDT'));
          $this->assertTrue($router->isMarketOpen('crypto', 'ANYPAIR'));
      }
      
      public function test_forex_market_hours_respected(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          
          // During trading hours (assuming UTC weekday)
          $this->assertTrue($router->isMarketOpen('forex', 'EURUSD'));
          
          // Weekend should be closed
          $this->assertFalse($router->isMarketOpen('forex', 'EURUSD', new Carbon('2026-01-25 12:00:00'))); // Saturday
      }
      
      public function test_crypto_lot_size_calculation(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          $connection = $this->createMockExchangeConnection(['exchange_type' => 'crypto']);
          
          $lotSize = $router->calculateLotSize(
              'crypto',
              1000.0, // $1000
              'BTCUSDT',
              $connection
          );
          
          $this->assertGreaterThan(0, $lotSize);
          $this->assertLessThan(1, $lotSize); // Less than 1 BTC for $1000
      }
      
      public function test_forex_lot_size_calculation(): void
      {
          $router = app(\Addons\TradingManagement\Modules\MarketRouter\Services\MarketRouter::class);
          $connection = $this->createMockExchangeConnection(['exchange_type' => 'fx']);
          
          $lotSize = $router->calculateLotSize(
              'forex',
              10000.0, // $10,000
              'EURUSD',
              $connection
          );
          
          // Forex uses standard lots (100,000 units)
          $this->assertGreaterThan(0, $lotSize);
          $this->assertLessThanOrEqual(0.1, $lotSize); // 0.1 lots = $10,000
      }
  }
  ```

---

### PHASE 2: Dynamic Configuration System (Enhancement)

- [ ] 2.1 TradingBotConfigManager Enhancement
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Services/Config/TradingBotConfigManager.php`
  - **Purpose**: Enhance existing service dengan hot-reload capability
  - **Integrates With**: Existing `TradingBotService`, `TradingPreset` model
  - **Acceptance**:
    - [ ] Update TradingPreset via config
    - [ ] Invalidate cache
    - [ ] Publish Redis event for running bots
  
  **Key Integration Points**:
  ```php
  // Extends existing service or creates new one in Config/ subdirectory
  namespace Addons\TradingManagement\Modules\TradingBot\Services\Config;
  
  class TradingBotConfigManager
  {
      public function updateConfig(TradingBot $bot, array $config): void
      {
          DB::transaction(function () use ($bot, $config) {
              // Update TradingPreset (existing model)
              $bot->tradingPreset()->update($config);
              
              // Invalidate runtime config cache
              Cache::forget("bot_config:{$bot->id}");
              
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
      
      public function getRuntimeConfig(TradingBot $bot): array
      {
          return Cache::remember(
              "bot_config:{$bot->id}",
              3600,
              fn() => $this->buildRuntimeConfig($bot)
          );
      }
  }
  ```

- [ ] 2.2 BotConfigListenerJob (Redis pub/sub)
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Jobs/BotConfigListenerJob.php`
  - **Purpose**: Subscribe to Redis channel for real-time config updates
  - **Integrates With**: Existing `TradingBotWorkerJob`
  - **Acceptance**:
    - [ ] Redis subscription works
    - [ ] Config changes applied tanpa restart
    - [ ] Job retry logic works
  
  **Key Integration Points**:
  ```php
  namespace Addons\TradingManagement\Modules\TradingBot\Jobs;
  
  class BotConfigListenerJob implements ShouldQueue
  {
      use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
      
      public function __construct(
          public int $botId
      ) {}
      
      public function handle(): void
      {
          $bot = TradingBot::find($this->botId);
          if (!$bot || $bot->status !== 'running') {
              return;
          }
          
          // Subscribe to Redis channel for this bot
          Redis::subscribe("bot:{$this->botId}:config", function ($message) use ($bot) {
              $data = json_decode($message, true);
              
              if ($data['event'] === 'config_updated') {
                  $this->handleConfigUpdate($bot, $data['config']);
              }
          });
      }
      
      private function handleConfigUpdate(TradingBot $bot, array $config): void
      {
          // Invalidate cache - next loop picks up new config
          Cache::forget("bot_config:{$bot->id}");
          
          // Log the config change
          BotExecutionLog::log($bot, 'config_hot_reload', [
              'changes' => $config,
              'timestamp' => now(),
          ]);
      }
  }
  ```

- [ ] 2.3 BotStateManager Implementation
  - **Location**: `main/addons/trading-management-addon/Modules/TradingBot/Services/BotStateManager.php`
  - **Purpose**: State machine for bot transitions
  - **Acceptance**:
    - [ ] Valid state transitions work
    - [ ] Invalid transitions throw exception
    - [ ] Events fired
  
  **Key Integration Points**:
  ```php
  namespace Addons\TradingManagement\Modules\TradingBot\Services;
  
  class BotStateManager
  {
      private const ALLOWED_TRANSITIONS = [
          'created' => ['running', 'archived'],
          'running' => ['paused', 'stopped', 'error'],
          'paused' => ['running', 'stopped'],
          'stopped' => ['running', 'archived'],
          'error' => ['stopped', 'paused'],
      ];
      
      public function canTransition(TradingBot $bot, string $newStatus): bool
      {
          return in_array($newStatus, self::ALLOWED_TRANSITIONS[$bot->status] ?? []);
      }
      
      public function transitionTo(TradingBot $bot, string $newStatus): void
      {
          if (!$this->canTransition($bot, $newStatus)) {
              throw new InvalidStateTransitionException(
                  "Cannot transition from {$bot->status} to {$newStatus}"
              );
          }
          
          $oldStatus = $bot->status;
          $bot->status = $newStatus;
          $bot->save();
          
          event(new BotStatusChanged($bot, $oldStatus, $newStatus));
      }
  }
  ```

- [ ] 2.4 Integration Test - Config Hot-Reload
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/ConfigHotReloadTest.php`
  - **Acceptance**:
    - [ ] Config update applied tanpa bot restart
    - [ ] Redis message received
    - [ ] Cache invalidated

---

### PHASE 3: Multi-Market Support (NEW Module)

- [ ] 3.1 MarketRouter Module Creation
  - **Location**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/MarketRouter.php`
  - **Purpose**: Unified interface untuk crypto (CCXT) dan forex (MetaApi) markets
  - **Acceptance**:
    - [ ] Symbol normalization works (BTC/USDT ↔ EURUSD)
    - [ ] Market hours detection works
    - [ ] Lot size calculations work (crypto raw, forex lots)
    - [ ] Adapter selection works
  
  **Key Classes**:
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
      
      public function isMarketOpen(string $marketType, ?string $symbol = null, ?Carbon $time = null): bool
      {
          return match ($marketType) {
              'crypto' => true, // 24/7
              'forex' => $this->forexSession->isOpen($symbol, $time),
          };
      }
      
      public function getAdapter(ExchangeConnection $connection): ExchangeAdapterInterface
      {
          return match ($connection->type) {
              'crypto' => app(CryptoExchangeAdapter::class)->setConnection($connection),
              'fx' => app(ForexBrokerAdapter::class)->setConnection($connection),
          };
      }
  }
  ```

- [ ] 3.2 SymbolNormalizer Implementation
  - **Location**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/SymbolNormalizer.php`
  - **Acceptance**:
    - [ ] Crypto symbol normalization
    - [ ] Forex symbol normalization
    - [ ] Part extraction (base/quote)

- [ ] 3.3 TradingHoursService Implementation
  - **Location**: `main/addons/trading-management-addon/Modules/MarketRouter/Services/TradingHoursService.php`
  - **Acceptance**:
    - [ ] Forex session detection (22:00 GMT - 21:00 GMT)
    - [ ] Market break handling (21:00 - 22:00 GMT)
    - [ ] Weekend detection

- [ ] 3.4 Integration Test - Multi-Market Execution
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/MultiMarketExecutionTest.php`
  - **Acceptance**:
    - [ ] Crypto bot executes via CCXT
    - [ ] Forex bot executes via MetaApi
    - [ ] Symbol normalization works both directions

---

### PHASE 4: Demo Mode Enhancement

- [ ] 4.1 VirtualPortfolio Model Creation
  - **Location**: `main/addons/trading-management-addon/Modules/PaperTrading/Models/VirtualPortfolio.php`
  - **Purpose**: Track virtual balance per user/connection
  - **Database**: NEW table `virtual_portfolios`
  - **Acceptance**:
    - [ ] Model created with proper table
    - [ ] User/connection relationship
    - [ ] Balance tracking
  
  **Migration**:
  ```php
  // In addon's database/migrations/
  Schema::create('virtual_portfolios', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('exchange_connection_id')->constrained()->onDelete('cascade');
      $table->decimal('balance', 20, 8)->default(10000); // Default demo balance
      $table->enum('market_type', ['crypto', 'fx'])->default('crypto');
      $table->timestamps();
      
      $table->unique(['user_id', 'exchange_connection_id']);
  });
  ```

- [ ] 4.2 PaperTradingService Enhancement
  - **Location**: `main/addons/trading-management-addon/Modules/PaperTrading/Services/PaperTradingService.php`
  - **Enhances**: Existing `InternalBrokerService` integration
  - **Acceptance**:
    - [ ] Virtual trade execution
    - [ ] Slippage simulation
    - [ ] Fee simulation
    - [ ] Balance updates
  
  **Key Integration**:
  ```php
  namespace Addons\TradingManagement\Modules\PaperTrading\Services;
  
  class PaperTradingService
  {
      private VirtualPortfolioManager $portfolioManager;
      
      public function executeVirtualTrade(
          TradingBot $bot,
          OrderRequest $request
      ): VirtualTradeResult {
          $portfolio = $this->portfolioManager->getOrCreate(
              $bot->user_id,
              $bot->exchange_connection_id
          );
          
          if (!$this->hasEnoughBalance($portfolio, $request)) {
              throw new InsufficientVirtualFundsException();
          }
          
          $execution = $this->simulateExecution($request, $portfolio);
          $this->portfolioManager->updateAfterTrade($portfolio, $execution);
          
          return $execution;
      }
  }
  ```

- [ ] 4.3 Integration Test - Demo Mode Isolation
  - **Location**: `main/tests/Integration/Addons/TradingManagement/TradingBot/DemoModeIsolationTest.php`
  - **Acceptance**:
    - [ ] Demo trades don't affect real balance
    - [ ] Real trades don't affect virtual balance
    - [ ] Mode isolation verified

- [ ] 4.4 Test Existing RiskCalculator Integration
  - **Location**: `main/tests/Unit/Addons/TradingManagement/RiskManagement/RiskCalculatorIntegrationTest.php`
  - **Tests**: Existing `RiskCalculatorService`
  - **Acceptance**:
    - [ ] PresetRiskCalculator works correctly
    - [ ] SmartRiskCalculator integration verified

---

### PHASE 5: End-to-End Feature Tests

- [ ] 5.1 Bot CRUD API Feature Tests
  - **Location**: `main/tests/Feature/Addons/TradingManagement/TradingBot/BotCrudTest.php`
  - **Tests**: Existing routes in `addon/routes/user.php` (not api.php - bots are user-facing)
  - **Acceptance**:
    - [ ] Create bot works via user panel routes
    - [ ] Update config works
    - [ ] Get full config works
    - [ ] Authorization works
  
  **Note**: Bot CRUD uses user panel routes (`/user/trading-management/trading-bots/*`), not API routes.
  Feature tests should use Laravel's `$this->actingAs()` to test authenticated access.

- [ ] 5.2 Market Info API Feature Tests
  - **Location**: `main/tests/Feature/Addons/TradingManagement/TradingBot/MarketInfoTest.php`
  - **Tests**: NEW endpoint for market status
  - **Acceptance**:
    - [ ] Market info returns all markets
    - [ ] Forex market status reflects trading hours

---

## Files to Create/Modify

### NEW Modules to Create
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
│   ├── PaperTradingService.php
│   └── VirtualPortfolioManager.php
└── Models/
    └── VirtualPortfolio.php

main/addons/trading-management-addon/Modules/TradingBot/
└── Services/
    └── Config/
        └── TradingBotConfigManager.php  (NEW - or extends existing)
```

### Files to Modify
```
main/addons/trading-management-addon/Modules/TradingBot/
├── Jobs/
│   └── BotConfigListenerJob.php         (NEW - subscribes to Redis)
├── Services/
│   └── BotStateManager.php              (NEW - state machine)
└── Controllers/User/
    └── TradingBotController.php         (ADD: Config update endpoints)

main/addons/trading-management-addon/Modules/ExchangeConnection/
└── Services/
    └── ExchangeConnectionService.php    (ENHANCE: Integration with MarketRouter)

main/addons/trading-management-addon/routes/
└── api.php                              (ADD: Market info endpoints)

main/addons/trading-management-addon/database/migrations/
└── *_create_virtual_portfolios_table.php (NEW)

main/addons/trading-management-addon/config/
└── trading-management.php               (ADD: MarketRouter config)
```

### Test Files to Create
```
main/tests/Unit/Addons/TradingManagement/
├── TradingBot/
│   ├── TradingBotTestCase.php
│   ├── ConfigManagement/
│   │   └── TradingBotConfigManagerTest.php
│   ├── MarketRouter/
│   │   ├── MarketRouterTest.php
│   │   ├── SymbolNormalizerTest.php
│   │   └── TradingHoursServiceTest.php
│   └── Services/
│       └── BotStateManagerTest.php
└── PaperTrading/
    ├── PaperTradingServiceTest.php
    └── VirtualPortfolioManagerTest.php

main/tests/Integration/Addons/TradingManagement/
└── TradingBot/
    ├── ConfigHotReloadTest.php
    ├── MultiMarketExecutionTest.php
    ├── DemoModeIsolationTest.php
    └── BotLifecycleTest.php

main/tests/Feature/Addons/TradingManagement/
└── TradingBot/
    ├── BotCrudTest.php
    └── MarketInfoTest.php

main/tests/Mockery/
└── ExchangeSimulator.php
```

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1.1 | `test(trading): create base test case` | TradingBotTestCase.php | `php artisan test --filter=TradingBotTestCase` |
| 1.2 | `test(trading): create exchange simulator` | ExchangeSimulator.php | `php artisan test --filter=ExchangeSimulatorTest` |
| 1.4 | `test(trading): market router unit tests` | MarketRouterTest.php | `php artisan test --filter=MarketRouterTest` |
| 2.1 | `feat(trading): config manager enhancement` | Config/TradingBotConfigManager.php | `php artisan test --filter=ConfigManagerTest` |
| 2.3 | `feat(trading): bot state machine` | BotStateManager.php | `php artisan test --filter=BotStateManagerTest` |
| 3.1 | `feat(trading): market router module` | MarketRouter.php | `php artisan test --filter=MarketRouterTest` |
| 4.1 | `feat(trading): virtual portfolio model` | VirtualPortfolio.php + migration | `php artisan test --filter=VirtualPortfolioTest` |
| 4.2 | `feat(trading): paper trading service` | PaperTradingService.php | `php artisan test --filter=PaperTradingServiceTest` |
| 5.1 | `test(trading): bot CRUD API` | BotCrudTest.php | `php artisan test --filter=BotCrudTest` |

---

## Success Criteria

### Functional Criteria
- [ ] Bot config dapat diupdate tanpa restart (Redis pub/sub)
- [ ] Bot dapat trading di crypto market (CCXT - existing)
- [ ] Bot dapat trading di forex market (MetaApi - existing)
- [ ] Demo mode uses virtual balance (VirtualPortfolio)
- [ ] Testnet uses real exchange (credentials['sandbox'])
- [ ] Production uses live credentials

### Quality Criteria
- [ ] 80% unit test coverage for new/enhanced code
- [ ] 15% integration test coverage
- [ ] 5% feature test coverage
- [ ] Zero new type errors (PHPStan level 5)
- [ ] All existing tests pass (no regression)

### Performance Criteria
- [ ] Config hot-reload < 1 second
- [ ] Signal processing < 100ms
- [ ] Order execution < 500ms

---

## Risk Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Breaking existing bot configs | High | Low | Backward compatibility layer (TradingPreset unchanged) |
| Race conditions saat hot-reload | High | Medium | Atomic operations + locking |
| Forex market hours handling | Medium | Medium | TradingHoursService with session awareness |
| Test coverage tidak tercapai | Medium | Low | Incremental approach + CI enforcement |
| Duplication dengan existing code | Medium | Medium | Explicit integration map, extend not replace |

---

## Estimated Effort

| Phase | Tasks | Estimated Time | Dependencies |
|-------|-------|----------------|--------------|
| 1 | 4 tasks | 8-12 hours | None |
| 2 | 4 tasks | 12-16 hours | Phase 1 |
| 3 | 4 tasks | 16-20 hours | Phase 1 |
| 4 | 4 tasks | 12-16 hours | Phase 1 + 2 |
| 5 | 2 tasks | 4-6 hours | Phase 2 + 4 |
| **Total** | **18 tasks** | **60-90 hours** | Sequential with parallelization |

---

## Validation Commands

```bash
# Run all trading bot tests
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot

# Run with coverage
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot --coverage --min=80

# Run unit tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot

# Run integration tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Integration/Addons/TradingManagement/TradingBot

# Run feature tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Addons/TradingManagement/TradingBot

# Run specific test class
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/Addons/TradingManagement/TradingBot/MarketRouter/MarketRouterTest.php
```

---

## Integration with Existing Components

### Existing Components (DO NOT REPLACE)
| Component | Path | Usage |
|-----------|------|-------|
| **TradingBotWorkerJob** | `Modules/TradingBot/Jobs/TradingBotWorkerJob.php` | Main worker loop |
| **TradingBotService** | `Modules/TradingBot/Services/TradingBotService.php` | Bot lifecycle |
| **TradingBot Model** | `Modules/TradingBot/Models/TradingBot.php` | Bot entity |
| **TradingPreset Model** | `Modules/RiskManagement/Models/TradingPreset.php` | Risk config |
| **RiskCalculatorService** | `Modules/RiskManagement/Services/RiskCalculatorService.php` | Risk calc |
| **CcxtExchangeService** | `Modules/ExchangeConnection/Services/CcxtExchangeService.php` | Crypto execution |
| **ExchangeConnection Model** | `Modules/ExchangeConnection/Models/ExchangeConnection.php` | Connection entity |
| **InternalBrokerService** | `Modules/Execution/Services/InternalBrokerService.php` | Paper trading |

### New Components (ENHANCE not REPLACE)
| New Component | Integrates With | Purpose |
|---------------|-----------------|---------|
| **TradingBotConfigManager** | TradingPreset, Redis | Hot-reload config |
| **MarketRouter** | CcxtExchangeService, MetaApi | Unified market interface |
| **VirtualPortfolio** | InternalBrokerService | Demo balance tracking |
| **BotStateManager** | TradingBot | State machine |
| **TradingHoursService** | MarketRouter | Forex sessions |

---

## Notes

### Critical Integration Points
1. **Config Hot-Reload**: Existing `TradingBotWorkerJob` already refreshes `$bot` each loop. New Redis pub/sub provides **immediate** updates vs **next loop** updates.
2. **Demo Mode**: Existing `is_paper_trading` flag on `TradingBot` + `InternalBrokerService`. New `VirtualPortfolio` strengthens isolation.
3. **Forex Trading**: Existing `MetaApiAdapter` handles MetaTrader accounts. New `TradingHoursService` prevents trading during market close.
4. **Risk Management**: Existing `RiskCalculatorService` handles all calculations. Tests verify integration, not replacement.

### Path Corrections (from Momus Review - ALL FIXED)
- ✅ Tests: `main/tests/Unit/Addons/TradingManagement/` (NOT `tests/Unit/TradingBot/`)
- ✅ Config: `main/addons/trading-management-addon/config/trading-management.php`
- ✅ Controllers: `Modules/TradingBot/Controllers/User/TradingBotController.php`
- ✅ Worker: `TradingBotWorkerJob.php` (not `BotWorkerJob.php`)

### Why Redis pub/sub (Not Just DB Refresh)?
- **DB Refresh**: Bot picks up changes on next loop iteration (5-30 seconds depending on config)
- **Redis pub/sub**: Bot receives config changes immediately (<1 second)
- **Use Case**: Critical for active trading where config changes need instant effect

---

## Change History
- 2026-01-20: Initial plan (REJECTED by Momus)
- 2026-01-21: REVISED plan v2 with:
  - ✅ Deep analysis of existing architecture (6 parallel explore agents)
  - ✅ Explicit integration map (existing components + new components)
  - ✅ Correct file paths (main/tests/Unit/Addons/TradingManagement/)
  - ✅ Extension not replacement approach
  - ✅ Detailed test code examples
  - ✅ Key integration code examples
