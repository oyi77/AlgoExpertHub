# Trading Bot Refactoring Tasks

**System**: Beads (bd) / Cursor Tasks  
**Priority**: P0  
**Estimated Duration**: 60-90 hours

---

## Test Pyramid Overview

| Layer | Coverage Target | Location |
|-------|----------------|----------|
| Unit Tests | 80% | `tests/Unit/TradingBot/` |
| Integration Tests | 15% | `tests/Integration/Trading/` |
| Feature Tests | 5% | `tests/Feature/Trading/` |

---

## PHASE 1: Foundation & Testing Infrastructure

### Task 1.1: Setup PHPUnit Configuration
**Description**: Configure PHPUnit for trading bot tests with proper bootstrapping
**Test Type**: Configuration verification
**Dependencies**: None
**Estimate**: 1-2 hours

**Tests to Write**:
```php
// tests/Unit/TradingBot/TradingBotTestCase.php
abstract class TradingBotTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareForTests();
    }
    
    protected function createMockExchangeConnection(): MockObject
    {
        return $this->mock(ExchangeConnection::class, function ($mock) {
            $mock->shouldReceive('getAttribute')
                ->with('exchange_type')
                ->andReturn('crypto');
            $mock->shouldReceive('getAttribute')
                ->with('is_paper_trading')
                ->andReturn(false);
        });
    }
}
```

**Acceptance Criteria**:
- [ ] PHPUnit configuration valid
- [ ] Test database configured
- [ ] Mockery available for exchange mocking
- [ ] Base test case created

---

### Task 1.2: Create Test Doubles for CCXT/MetaApi
**Description**: Create mock implementations untuk testing exchange connections without real API calls
**Test Type**: Mock objects
**Dependencies**: Task 1.1
**Estimate**: 2-3 hours

**Tests to Write**:
```php
// tests/Mockery/ExchangeSimulator.php
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
        ];
        
        return new OrderResult(
            orderId: $orderId,
            status: 'open',
            filledAmount: 0,
            averagePrice: null,
        );
    }
    
    public function fetchOrder(string $orderId): array
    {
        return $this->orders[$orderId] ?? throw new OrderNotFoundException();
    }
}
```

**Acceptance Criteria**:
- [ ] Crypto exchange simulator created
- [ ] Forex broker simulator created
- [ ] Balance management implemented
- [ ] Order placement and fetching works
- [ ] Error simulation (insufficient balance, etc.)

---

### Task 1.3: TradingBot Model Unit Tests
**Description**: Write unit tests untuk TradingBot model CRUD operations
**Test Type**: Unit Tests
**Dependencies**: Task 1.1
**Estimate**: 2-3 hours

**Tests to Write**:
```php
// tests/Unit/TradingBot/Models/TradingBotTest.php

class TradingBotTest extends TradingBotTestCase
{
    public function test_can_create_bot_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Bot',
            'user_id' => 1,
            'exchange_connection_id' => 1,
            'trading_preset_id' => 1,
            'status' => 'created',
        ];
        
        $bot = TradingBot::create($data);
        
        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('Test Bot', $bot->name);
        $this->assertEquals('created', $bot->status);
    }
    
    public function test_cannot_create_bot_without_required_fields(): void
    {
        $this->expectException(ValidationException::class);
        
        TradingBot::create([]);
    }
    
    public function test_belongs_to_user(): void
    {
        $bot = TradingBot::factory()->create();
        
        $this->assertInstanceOf(User::class, $bot->user);
    }
    
    public function test_belongs_to_exchange_connection(): void
    {
        $bot = TradingBot::factory()->create();
        
        $this->assertInstanceOf(ExchangeConnection::class, $bot->exchangeConnection);
    }
    
    public function test_has_many_positions(): void
    {
        $bot = TradingBot::factory()->create();
        $position = TradingBotPosition::factory()->create(['bot_id' => $bot->id]);
        
        $this->assertCount(1, $bot->positions);
        $this->assertInstanceOf(TradingBotPosition::class, $bot->positions->first());
    }
}
```

**Acceptance Criteria**:
- [ ] CRUD tests pass (8+ tests)
- [ ] Relationship tests pass (4+ tests)
- [ ] Validation tests pass (3+ tests)
- [ ] Model coverage > 80%

---

### Task 1.4: RiskCalculator Unit Tests
**Description**: Test risk calculation logic (SL/TP, position sizing)
**Test Type**: Unit Tests
**Dependencies**: Task 1.1
**Estimate**: 2-3 hours

**Tests to Write**:
```php
// tests/Unit/TradingBot/RiskManagement/RiskCalculatorTest.php

class RiskCalculatorTest extends TradingBotTestCase
{
    public function test_calculate_stop_loss_percentage(): void
    {
        $entryPrice = 50000.0;
        $stopLossPrice = 49000.0;
        
        $slPct = RiskCalculator::calculateStopLossPercentage(
            $entryPrice,
            $stopLossPrice,
            'long'
        );
        
        $this->assertEquals(2.0, $slPct);
    }
    
    public function test_calculate_position_size_for_crypto(): void
    {
        $accountBalance = 10000.0;
        $riskPerTrade = 0.02; // 2%
        $stopLossPct = 1.0;
        $symbol = 'BTCUSDT';
        
        $positionSize = RiskCalculator::calculatePositionSize(
            $accountBalance,
            $riskPerTrade,
            $stopLossPct,
            $symbol,
            'crypto'
        );
        
        $this->assertGreaterThan(0, $positionSize);
        $this->assertLessThanOrEqual($accountBalance, $positionSize);
    }
    
    public function test_calculate_position_size_for_forex(): void
    {
        $accountBalance = 10000.0;
        $riskPerTrade = 0.02;
        $stopLossPct = 0.5;
        $symbol = 'EURUSD';
        
        $positionSize = RiskCalculator::calculatePositionSize(
            $accountBalance,
            $riskPerTrade,
            $stopLossPct,
            $symbol,
            'forex'
        );
        
        // Forex uses lot sizes (100,000 units)
        $this->assertGreaterThan(0, $positionSize);
    }
    
    public function test_trailing_stop_calculation(): void
    {
        $currentPrice = 52000.0;
        $entryPrice = 50000.0;
        $trailingStopPct = 1.0;
        $trailingStopPrice = 48000.0;
        
        $newTrailingStop = RiskCalculator::updateTrailingStop(
            $currentPrice,
            $entryPrice,
            $trailingStopPct,
            $trailingStopPrice,
            'long'
        );
        
        $this->assertGreaterThan($trailingStopPrice, $newTrailingStop);
    }
    
    public function test_risk_exceeds_balance_throws_exception(): void
    {
        $this->expectException(InsufficientBalanceException::class);
        
        RiskCalculator::calculatePositionSize(
            100.0,      // Very small balance
            0.10,       // 10% risk
            50.0,       // 50% SL (would require 50% of account)
            'BTCUSDT',
            'crypto'
        );
    }
}
```

**Acceptance Criteria**:
- [ ] SL/TP calculation tests pass (4+ tests)
- [ ] Position sizing tests pass (4+ tests)
- [ ] Trailing stop tests pass (2+ tests)
- [ ] Error handling tests pass (2+ tests)
- [ ] Crypto vs forex differentiation tests pass

---

## PHASE 2: Dynamic Configuration System

### Task 2.1: TradingBotConfigManager Implementation
**Description**: Implement hot-reload config management service
**Test Type**: TDD (Write tests first)
**Dependencies**: Task 1.3
**Estimate**: 4-6 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/ConfigManagement/TradingBotConfigManagerTest.php

class TradingBotConfigManagerTest extends TradingBotTestCase
{
    public function test_update_config_persists_to_database(): void
    {
        $bot = TradingBot::factory()->create();
        $preset = $bot->preset;
        
        $configManager = app(TradingBotConfigManager::class);
        $configManager->updateConfig($bot, [
            'risk_per_trade' => 0.03,
            'stop_loss_pct' => 1.5,
        ]);
        
        $preset->refresh();
        $this->assertEquals(0.03, $preset->risk_per_trade);
        $this->assertEquals(1.5, $preset->stop_loss_pct);
    }
    
    public function test_config_update_for_running_bot_triggers_publish(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        $configManager = app(TradingBotConfigManager::class);
        
        $this->expectsEvents(ConfigUpdated::class);
        
        $configManager->updateConfig($bot, ['risk_per_trade' => 0.04]);
    }
    
    public function test_get_runtime_config_returns_cached_config(): void
    {
        $bot = TradingBot::factory()->create();
        
        $configManager = app(TradingBotConfigManager::class);
        $config1 = $configManager->getRuntimeConfig($bot);
        $config2 = $configManager->getRuntimeConfig($bot);
        
        $this->assertEquals($config1, $config2);
        $this->assertArrayHasKey('risk_per_trade', $config1);
    }
    
    public function test_config_update_invalidates_cache(): void
    {
        $bot = TradingBot::factory()->create();
        
        $configManager = app(TradingBotConfigManager::class);
        
        // Get initial config (cached)
        $config1 = $configManager->getRuntimeConfig($bot);
        
        // Update config
        $configManager->updateConfig($bot, ['risk_per_trade' => 0.05]);
        
        // Get new config
        $config2 = $configManager->getRuntimeConfig($bot);
        
        $this->assertNotEquals($config1['risk_per_trade'], $config2['risk_per_trade']);
        $this->assertEquals(0.05, $config2['risk_per_trade']);
    }
    
    public function test_concurrent_config_updates_are_atomic(): void
    {
        $bot = TradingBot::factory()->create();
        
        $configManager = app(TradingBotConfigManager::class);
        
        // Simulate concurrent updates
        $results = [];
        
        foreach (range(1, 5) as $i) {
            $results[] = DB::transaction(function () use ($bot, $configManager, $i) {
                return $configManager->updateConfig($bot, [
                    'risk_per_trade' => 0.01 * $i,
                ]);
            });
        }
        
        // Only one should succeed
        $uniqueValues = array_unique($results);
        $this->assertCount(1, $uniqueValues);
    }
}
```

**Implementation Tasks**:
- [ ] Create `TradingBotConfigManager` class
- [ ] Implement `updateConfig()` method
- [ ] Implement `getRuntimeConfig()` method
- [ ] Implement Redis pub/sub publishing
- [ ] Implement cache management
- [ ] Add atomic transaction handling

---

### Task 2.2: BotConfigListenerJob Implementation
**Description**: Implement Redis subscriber untuk real-time config changes
**Test Type**: TDD (Write tests first)
**Dependencies**: Task 2.1
**Estimate**: 3-4 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/Jobs/BotConfigListenerJobTest.php

class BotConfigListenerJobTest extends TradingBotTestCase
{
    public function test_job_subscribes_to_redis_channel(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        $job = new BotConfigListenerJob($bot->id);
        
        Redis::shouldReceive('subscribe')
            ->once()
            ->with(
                "bot:{$bot->id}:config",
                \Closure::that(function ($callback) {
                    $testMessage = json_encode([
                        'event' => 'config_updated',
                        'config' => ['risk_per_trade' => 0.03],
                    ]);
                    
                    // Simulate receiving message
                    $callback($testMessage);
                    return true;
                })
            );
        
        $job->handle();
    }
    
    public function test_job_does_nothing_for_stopped_bot(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'stopped']);
        
        $job = new BotConfigListenerJob($bot->id);
        
        Redis::shouldReceive('subscribe')->never();
        
        $job->handle();
    }
    
    public function test_config_update_received_and_applied(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        $job = new BotConfigListenerJob($bot->id);
        
        $this->expectsEvents(ConfigApplied::class);
        
        $job->handle();
    }
}
```

**Implementation Tasks**:
- [ ] Create `BotConfigListenerJob` class
- [ ] Implement Redis subscription logic
- [ ] Implement config change handler
- [ ] Add job retry logic
- [ ] Add proper error handling

---

### Task 2.3: BotStateManager Implementation
**Description**: Implement state machine untuk bot transitions
**Test Type**: TDD (Write tests first)
**Dependencies**: Task 1.3
**Estimate**: 2-3 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/Services/BotStateManagerTest.php

class BotStateManagerTest extends TradingBotTestCase
{
    public function test_can_transition_from_created_to_running(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'created']);
        
        $manager = app(BotStateManager::class);
        
        $this->assertTrue($manager->canTransition($bot, 'running'));
    }
    
    public function test_can_transition_from_running_to_paused(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        $manager = app(BotStateManager::class);
        
        $this->assertTrue($manager->canTransition($bot, 'paused'));
    }
    
    public function test_cannot_transition_from_running_to_created(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);
        
        $manager = app(BotStateManager::class);
        
        $this->assertFalse($manager->canTransition($bot, 'created'));
    }
    
    public function test_transition_triggers_event(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'created']);
        
        $manager = app(BotStateManager::class);
        
        $this->expectsEvents(BotStatusChanged::class);
        
        $manager->transitionTo($bot, 'running');
        
        $this->assertEquals('running', $bot->status);
    }
    
    public function test_invalid_transition_throws_exception(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'error']);
        
        $manager = app(BotStateManager::class);
        
        $this->expectException(InvalidStateTransitionException::class);
        
        $manager->transitionTo($bot, 'created');
    }
    
    public function test_all_valid_transitions(): void
    {
        $validTransitions = [
            'created' => ['running', 'archived'],
            'running' => ['paused', 'stopped', 'error'],
            'paused' => ['running', 'stopped'],
            'stopped' => ['running', 'archived'],
            'error' => ['stopped', 'paused'],
        ];
        
        foreach ($validTransitions as $from => $toList) {
            foreach ($toList as $to) {
                $bot = TradingBot::factory()->create(['status' => $from]);
                $manager = app(BotStateManager::class);
                
                $this->assertTrue(
                    $manager->canTransition($bot, $to),
                    "Transition from {$from} to {$to} should be valid"
                );
            }
        }
    }
}
```

**Implementation Tasks**:
- [ ] Create `BotStateManager` class
- [ ] Define valid state transitions
- [ ] Implement `canTransition()` method
- [ ] Implement `transitionTo()` method
- [ ] Add `BotStatusChanged` event

---

## PHASE 3: Multi-Market Support

### Task 3.1: MarketRouter Implementation
**Description**: Implement unified market router untuk crypto + forex
**Test Type**: TDD (Write tests first)
**Dependencies**: Task 1.2
**Estimate**: 4-6 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/MarketRouter/MarketRouterTest.php

class MarketRouterTest extends TradingBotTestCase
{
    public function test_normalize_crypto_symbol(): void
    {
        $router = app(MarketRouter::class);
        
        $normalized = $router->normalizeSymbol('BTC/USDT', 'crypto');
        
        $this->assertEquals('BTCUSDT', $normalized);
    }
    
    public function test_normalize_forex_symbol(): void
    {
        $router = app(MarketRouter::class);
        
        $normalized = $router->normalizeSymbol('EUR/USD', 'forex');
        
        $this->assertEquals('EURUSD', $normalized);
    }
    
    public function test_crypto_is_always_open(): void
    {
        $router = app(MarketRouter::class);
        
        $this->assertTrue($router->isMarketOpen('crypto'));
    }
    
    public function test_forex_market_hours_respected(): void
    {
        $router = app(MarketRouter::class);
        
        // During trading hours (assuming UTC 10:00)
        $this->assertTrue($router->isMarketOpen('forex', 'EURUSD'));
        
        // During market break
        $this->assertFalse($router->isMarketOpen('forex', 'EURUSD'));
    }
    
    public function test_crypto_lot_size_calculation(): void
    {
        $router = app(MarketRouter::class);
        
        $lotSize = $router->calculateLotSize(
            'crypto',
            1000.0, // $1000
            'BTCUSDT',
            $this->createMockExchangeConnection()
        );
        
        $this->assertGreaterThan(0, $lotSize);
        $this->assertLessThan(1, $lotSize); // Less than 1 BTC for $1000
    }
    
    public function test_forex_lot_size_calculation(): void
    {
        $router = app(MarketRouter::class);
        
        $lotSize = $router->calculateLotSize(
            'forex',
            10000.0, // $10,000
            'EURUSD',
            $this->createMockExchangeConnection()
        );
        
        // Forex uses standard lots (100,000 units)
        $this->assertGreaterThan(0, $lotSize);
        $this->assertLessThanOrEqual(0.1, $lotSize); // 0.1 lots = $10,000
    }
    
    public function test_get_crypto_adapter(): void
    {
        $router = app(MarketRouter::class);
        $connection = $this->createMockExchangeConnection();
        $connection->exchange_type = 'crypto';
        
        $adapter = $router->getAdapter($connection);
        
        $this->assertInstanceOf(CryptoExchangeAdapter::class, $adapter);
    }
    
    public function test_get_forex_adapter(): void
    {
        $router = app(MarketRouter::class);
        $connection = $this->createMockExchangeConnection();
        $connection->exchange_type = 'fx';
        
        $adapter = $router->getAdapter($connection);
        
        $this->assertInstanceOf(ForexBrokerAdapter::class, $adapter);
    }
}
```

**Implementation Tasks**:
- [ ] Create `MarketRouter` class
- [ ] Implement symbol normalization for crypto
- [ ] Implement symbol normalization for forex
- [ ] Implement market hours detection for forex
- [ ] Implement lot size calculations (crypto vs forex)
- [ ] Create exchange adapter interfaces

---

### Task 3.2: TradingHoursService Implementation
**Description**: Implement forex trading hours detection
**Test Type**: Unit Tests
**Dependencies**: Task 3.1
**Estimate**: 2-3 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/MarketRouter/TradingHoursServiceTest.php

class TradingHoursServiceTest extends TradingBotTestCase
{
    public function test_forex_session_monday_to_friday(): void
    {
        $service = app(TradingHoursService::class);
        
        // Monday 10:00 UTC - should be open
        $monday = Carbon::create(2026, 1, 19, 10, 0, 0, 'UTC');
        $this->assertTrue($service->isForexOpen('EURUSD', $monday));
        
        // Friday 20:00 UTC - should be open
        $friday = Carbon::create(2026, 1, 23, 20, 0, 0, 'UTC');
        $this->assertTrue($service->isForexOpen('EURUSD', $friday));
        
        // Friday 21:00 UTC - market break starts
        $fridayBreak = Carbon::create(2026, 1, 23, 21, 0, 0, 'UTC');
        $this->assertFalse($service->isForexOpen('EURUSD', $fridayBreak));
    }
    
    public function test_forex_closed_on_weekend(): void
    {
        $service = app(TradingHoursService::class);
        
        // Saturday - should be closed
        $saturday = Carbon::create(2026, 1, 24, 12, 0, 0, 'UTC');
        $this->assertFalse($service->isForexOpen('EURUSD', $saturday));
        
        // Sunday - should be closed
        $sunday = Carbon::create(2026, 1, 25, 12, 0, 0, 'UTC');
        $this->assertFalse($service->isForexOpen('EURUSD', $sunday));
    }
    
    public function test_get_next_session_change(): void
    {
        $service = app(TradingHoursService::class);
        
        $nextChange = $service->getNextSessionChange('EURUSD');
        
        $this->assertNotNull($nextChange);
        $this->assertInstanceOf(Carbon::class, $nextChange);
        $this->assertGreaterThan(now(), $nextChange);
    }
}
```

**Implementation Tasks**:
- [ ] Create `TradingHoursService` class
- [ ] Implement forex session detection
- [ ] Implement market break handling
- [ ] Add weekend detection
- [ ] Add cache for session info

---

### Task 3.3: SymbolNormalizer Implementation
**Description**: Implement symbol normalization for cross-market compatibility
**Test Type**: Unit Tests
**Dependencies**: Task 3.1
**Estimate**: 1-2 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/MarketRouter/SymbolNormalizerTest.php

class SymbolNormalizerTest extends TradingBotTestCase
{
    public function test_extract_crypto_parts(): void
    {
        $normalizer = app(SymbolNormalizer::class);
        
        $parts = $normalizer->extractParts('BTC/USDT', 'crypto');
        
        $this->assertEquals('BTC', $parts['base']);
        $this->assertEquals('USDT', $parts['quote']);
    }
    
    public function test_extract_forex_parts(): void
    {
        $normalizer = app(SymbolNormalizer::class);
        
        $parts = $normalizer->extractParts('EUR/USD', 'forex');
        
        $this->assertEquals('EUR', $parts['base']);
        $this->assertEquals('USD', $parts['quote']);
    }
    
    public function test_normalize_various_formats(): void
    {
        $normalizer = app(SymbolNormalizer::class);
        
        $this->assertEquals('BTCUSDT', $normalizer->normalizeCryptoSymbol('BTC/USDT'));
        $this->assertEquals('BTCUSDT', $normalizer->normalizeCryptoSymbol('BTC-USDT'));
        $this->assertEquals('BTCUSDT', $normalizer->normalizeCryptoSymbol('btcusdt'));
        
        $this->assertEquals('EURUSD', $normalizer->normalizeForexSymbol('EUR/USD'));
        $this->assertEquals('EURUSD', $normalizer->normalizeForexSymbol('EURUSD'));
    }
}
```

**Implementation Tasks**:
- [ ] Create `SymbolNormalizer` class
- [ ] Implement crypto symbol normalization
- [ ] Implement forex symbol normalization
- [ ] Implement part extraction

---

## PHASE 4: Demo/Testnet/Production Modes

### Task 4.1: PaperTradingService Implementation
**Description**: Implement demo mode simulation service
**Test Type**: TDD (Write tests first)
**Dependencies**: Task 1.2
**Estimate**: 4-6 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/PaperTrading/PaperTradingServiceTest.php

class PaperTradingServiceTest extends TradingBotTestCase
{
    public function test_execute_virtual_trade_with_sufficient_balance(): void
    {
        $bot = TradingBot::factory()->create(['is_paper_trading' => true]);
        $portfolio = VirtualPortfolio::factory()->create([
            'user_id' => $bot->user_id,
            'balance' => 10000.0,
        ]);
        
        $service = app(PaperTradingService::class);
        $order = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.1,
            price: 50000.0,
        );
        
        $result = $service->executeVirtualTrade($bot, $order);
        
        $this->assertInstanceOf(VirtualTradeResult::class, $result);
        $this->assertTrue($result->isPaper);
        $this->assertEquals(50000.0, $result->executionPrice);
    }
    
    public function test_virtual_trade_fails_with_insufficient_balance(): void
    {
        $bot = TradingBot::factory()->create(['is_paper_trading' => true]);
        $portfolio = VirtualPortfolio::factory()->create([
            'user_id' => $bot->user_id,
            'balance' => 100.0, // Very low balance
        ]);
        
        $service = app(PaperTradingService::class);
        $order = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 1.0, // Would cost $50,000
            price: 50000.0,
        );
        
        $this->expectException(InsufficientVirtualFundsException::class);
        
        $service->executeVirtualTrade($bot, $order);
    }
    
    public function test_slippage_is_simulated(): void
    {
        $bot = TradingBot::factory()->create(['is_paper_trading' => true]);
        $portfolio = VirtualPortfolio::factory()->create([
            'user_id' => $bot->user_id,
            'balance' => 10000.0,
        ]);
        
        $service = app(PaperTradingService::class);
        $order = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.1,
            price: 50000.0,
        );
        
        $result = $service->executeVirtualTrade($bot, $order);
        
        // Slippage should be applied
        $this->assertNotEquals(50000.0, $result->executionPrice);
        $this->assertGreaterThan(0, $result->slippage);
    }
    
    public function test_virtual_portfolio_balance_updated(): void
    {
        $bot = TradingBot::factory()->create(['is_paper_trading' => true]);
        $portfolio = VirtualPortfolio::factory()->create([
            'user_id' => $bot->user_id,
            'balance' => 10000.0,
        ]);
        
        $service = app(PaperTradingService::class);
        $order = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.1,
            price: 50000.0,
        );
        
        $service->executeVirtualTrade($bot, $order);
        
        $portfolio->refresh();
        $this->assertLessThan(10000.0, $portfolio->balance);
    }
}
```

**Implementation Tasks**:
- [ ] Create `PaperTradingService` class
- [ ] Implement virtual trade execution
- [ ] Implement slippage simulation
- [ ] Implement balance management
- [ ] Implement fee simulation

---

### Task 4.2: VirtualPortfolioManager Implementation
**Description**: Implement virtual portfolio management
**Test Type**: Unit Tests
**Dependencies**: Task 4.1
**Estimate**: 2-3 hours

**Tests to Write FIRST**:
```php
// tests/Unit/TradingBot/PaperTrading/VirtualPortfolioManagerTest.php

class VirtualPortfolioManagerTest extends TradingBotTestCase
{
    public function test_get_or_create_portfolio(): void
    {
        $manager = app(VirtualPortfolioManager::class);
        
        $portfolio = $manager->getOrCreate(
            userId: 1,
            connectionId: 1
        );
        
        $this->assertInstanceOf(VirtualPortfolio::class, $portfolio);
        $this->assertEquals(10000.0, $portfolio->balance); // Default balance
    }
    
    public function test_returns_existing_portfolio(): void
    {
        $existing = VirtualPortfolio::factory()->create([
            'user_id' => 1,
            'exchange_connection_id' => 1,
            'balance' => 5000.0,
        ]);
        
        $manager = app(VirtualPortfolioManager::class);
        
        $portfolio = $manager->getOrCreate(1, 1);
        
        $this->assertEquals($existing->id, $portfolio->id);
        $this->assertEquals(5000.0, $portfolio->balance);
    }
    
    public function test_update_after_trade(): void
    {
        $portfolio = VirtualPortfolio::factory()->create([
            'user_id' => 1,
            'exchange_connection_id' => 1,
            'balance' => 10000.0,
        ]);
        
        $manager = app(VirtualPortfolioManager::class);
        
        $tradeResult = new VirtualTradeResult(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.1,
            executionPrice: 50000.0,
            fees: 5.0,
            slippage: 0.001,
            spread: 0.0002,
            executedAt: now(),
            isPaper: true,
        );
        
        $manager->updateAfterTrade($portfolio, $tradeResult);
        
        $portfolio->refresh();
        $expectedBalance = 10000.0 - (0.1 * 50000.0) - 5.0;
        $this->assertEquals($expectedBalance, $portfolio->balance);
    }
}
```

**Implementation Tasks**:
- [ ] Create `VirtualPortfolioManager` class
- [ ] Implement `getOrCreate()` method
- [ ] Implement `updateAfterTrade()` method
- [ ] Implement balance calculations
- [ ] Add default balance configuration

---

### Task 4.3: Integration Tests - Bot Lifecycle
**Description**: Write integration tests untuk complete bot lifecycle
**Test Type**: Integration Tests
**Dependencies**: Tasks 2.1, 2.3, 4.1
**Estimate**: 4-6 hours

**Tests to Write**:
```php
// tests/Integration/Trading/BotLifecycleTest.php

class BotLifecycleTest extends TradingBotTestCase
{
    use RefreshDatabase;
    
    public function test_complete_bot_lifecycle(): void
    {
        // 1. Create bot
        $bot = TradingBot::factory()->create([
            'status' => 'created',
            'is_paper_trading' => true,
        ]);
        
        $this->assertEquals('created', $bot->status);
        
        // 2. Start bot
        $stateManager = app(BotStateManager::class);
        $stateManager->transitionTo($bot, 'running');
        
        $this->assertEquals('running', $bot->status);
        
        // 3. Update config dynamically
        $configManager = app(TradingBotConfigManager::class);
        $configManager->updateConfig($bot, ['risk_per_trade' => 0.03]);
        
        $bot->refresh();
        $this->assertEquals(0.03, $bot->preset->risk_per_trade);
        
        // 4. Execute virtual trade
        $paperService = app(PaperTradingService::class);
        $order = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.05,
            price: 50000.0,
        );
        
        $result = $paperService->executeVirtualTrade($bot, $order);
        
        $this->assertTrue($result->isPaper);
        $this->assertEquals('long', $result->direction);
        
        // 5. Stop bot
        $stateManager->transitionTo($bot, 'stopped');
        
        $this->assertEquals('stopped', $bot->status);
    }
    
    public function test_demo_mode_is_isolated_from_production(): void
    {
        // Create demo bot
        $demoBot = TradingBot::factory()->create([
            'is_paper_trading' => true,
            'status' => 'running',
        ]);
        
        // Create production bot
        $prodBot = TradingBot::factory()->create([
            'is_paper_trading' => false,
            'status' => 'running',
        ]);
        
        // Execute demo trade
        $paperService = app(PaperTradingService::class);
        $demoOrder = new OrderRequest(
            symbol: 'BTCUSDT',
            direction: 'long',
            quantity: 0.1,
            price: 50000.0,
        );
        
        $demoResult = $paperService->executeVirtualTrade($demoBot, $demoOrder);
        
        // Production should NOT be affected
        // No virtual trade should be created for production bot
        $this->assertTrue($demoResult->isPaper);
    }
}
```

**Acceptance Criteria**:
- [ ] Create → Start → Trade → Stop flow works
- [ ] Config hot-reload works during trading
- [ ] Demo mode is isolated
- [ ] All state transitions work
- [ ] No cross-contamination between modes

---

## PHASE 5: End-to-End Feature Tests

### Task 5.1: Bot CRUD API Feature Tests
**Description**: Write feature tests untuk complete API CRUD operations
**Test Type**: Feature Tests
**Dependencies**: Tasks 2.1, 3.1
**Estimate**: 3-4 hours

**Tests to Write**:
```php
// tests/Feature/Trading/BotCrudTest.php

class BotCrudTest extends TradingBotTestCase
{
    public function test_user_can_create_bot(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/user/trading-bots', [
                'name' => 'My Bot',
                'exchange_connection_id' => 1,
                'trading_preset_id' => 1,
                'trading_mode' => 'demo',
            ]);
        
        $response->assertCreated();
        $this->assertDatabaseHas('trading_bots', [
            'name' => 'My Bot',
            'trading_mode' => 'demo',
        ]);
    }
    
    public function test_user_can_update_config_dynamically(): void
    {
        $bot = TradingBot::factory()->create([
            'status' => 'running',
            'is_paper_trading' => true,
        ]);
        
        $response = $this->actingAs($bot->user)
            ->patchJson("/api/user/trading-bots/{$bot->id}/config", [
                'risk_per_trade' => 0.04,
                'take_profit_pct' => 3.0,
            ]);
        
        $response->assertOk();
        $this->assertDatabaseHas('trading_presets', [
            'id' => $bot->trading_preset_id,
            'risk_per_trade' => 0.04,
            'take_profit_pct' => 3.0,
        ]);
    }
    
    public function test_user_cannot_update_config_of_other_user(): void
    {
        $bot = TradingBot::factory()->create();
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->patchJson("/api/user/trading-bots/{$bot->id}/config", [
                'risk_per_trade' => 0.99,
            ]);
        
        $response->assertForbidden();
    }
    
    public function test_get_full_config_endpoint(): void
    {
        $bot = TradingBot::factory()->create();
        
        $response = $this->actingAs($bot->user)
            ->getJson("/api/user/trading-bots/{$bot->id}/full-config");
        
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'bot' => ['id', 'name', 'status', 'trading_mode'],
                'config' => ['risk_per_trade', 'stop_loss_pct', 'take_profit_pct'],
                'exchange' => ['id', 'exchange_name', 'exchange_type'],
            ],
        ]);
    }
}
```

---

### Task 5.2: Market Info API Feature Tests
**Description**: Test market info endpoints
**Test Type**: Feature Tests
**Dependencies**: Tasks 3.1, 3.2
**Estimate**: 2-3 hours

**Tests to Write**:
```php
// tests/Feature/Trading/MarketInfoTest.php

class MarketInfoTest extends TradingBotTestCase
{
    public function test_market_info_returns_all_markets(): void
    {
        $response = $this->getJson('/api/market/info');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'crypto' => [
                    'symbols' => [],
                    'trading_hours',
                ],
                'forex' => [
                    'symbols' => [],
                    'trading_hours',
                ],
            ],
        ]);
    }
    
    public function test_forex_market_status_reflects_trading_hours(): void
    {
        $response = $this->getJson('/api/market/info');
        
        $response->assertOk();
        $data = $response->json('data');
        
        // Forex should indicate if market is open
        $this->assertArrayHasKey('is_open', $data['forex']['trading_hours']);
    }
}
```

---

## Test Execution Commands

```bash
# Run all trading bot tests
docker exec 1Panel-php8-mrTy php artisan test --filter=TradingBot

# Run unit tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/TradingBot

# Run integration tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Integration/Trading

# Run feature tests only
docker exec 1Panel-php8-mrTy php artisan test tests/Feature/Trading

# Run with coverage
docker exec 1Panel-php8-mrTy php artisan test --coverage --min=80

# Run specific test class
docker exec 1Panel-php8-mrTy php artisan test tests/Unit/TradingBot/RiskManagement/RiskCalculatorTest.php
```

---

## Task Summary

| Phase | Tasks | Total Hours | Test Coverage Target |
|-------|-------|-------------|---------------------|
| 1 | 4 tasks | 8-12 hours | 25% |
| 2 | 3 tasks | 9-13 hours | 40% |
| 3 | 3 tasks | 8-11 hours | 55% |
| 4 | 3 tasks | 9-13 hours | 75% |
| 5 | 2 tasks | 5-7 hours | 100% |
| **Total** | **15 tasks** | **60-90 hours** | **80% unit, 15% integration, 5% feature** |

---

## Change History
- 2026-01-19: Initial task breakdown with TDD approach
