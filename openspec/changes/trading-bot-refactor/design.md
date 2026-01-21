# Trading Bot Refactoring Design

## Architecture Overview

### Current Architecture
```
trading-management-addon/
├── Modules/
│   ├── TradingBot/          # Bot lifecycle management
│   ├── Execution/           # Trade execution (CCXT/MetaApi)
│   ├── RiskManagement/      # SL/TP, position sizing
│   ├── FilterStrategy/      # Technical indicators
│   ├── AiAnalysis/          # AI confirmation
│   ├── ExchangeConnection/  # API credentials
│   ├── Backtesting/         # Strategy testing
│   └── CopyTrading/         # Social trading
```

### Target Architecture
```
trading-management-addon/
├── Modules/
│   ├── TradingBot/          # Bot lifecycle + Dynamic Config
│   ├── ConfigManagement/    # NEW: Hot-reload config system
│   ├── MarketRouter/        # NEW: Unified crypto/forex interface
│   ├── Execution/           # Trade execution (enhanced)
│   ├── RiskManagement/      # Risk calculations (enhanced)
│   ├── FilterStrategy/      # Dynamic indicators
│   ├── AiAnalysis/          # AI confirmation
│   ├── ExchangeConnection/  # API credentials
│   ├── Backtesting/         # Strategy testing
│   ├── CopyTrading/         # Social trading
│   └── PaperTrading/        # NEW: Demo mode simulation
```

---

## Component Design

### 1. ConfigManagement Module (NEW)

**Purpose**: Handle dynamic bot configuration dengan hot-reload capability

**Files**:
```
Modules/ConfigManagement/
├── Services/
│   └── TradingBotConfigManager.php
├── Jobs/
│   └── ConfigHotReloadJob.php
├── Http/
│   ├── Controllers/Api/
│   │   └── BotConfigController.php
│   └── Requests/
│       └── UpdateBotConfigRequest.php
└── Services/
    └── ConfigCacheService.php
```

**Key Classes**:

```php
// TradingBotConfigManager.php
namespace Addons\TradingManagement\Modules\ConfigManagement\Services;

class TradingBotConfigManager
{
    private Redis $redis;
    
    public function updateConfig(TradingBot $bot, array $config): void
    {
        DB::transaction(function () use ($bot, $config) {
            // Update database
            $bot->preset()->update($config);
            
            // Invalidate cache
            $this->invalidateConfigCache($bot->id);
            
            // Publish config change event
            if ($bot->status === 'running') {
                $this->publishConfigChange($bot, $config);
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
    
    private function publishConfigChange(TradingBot $bot, array $config): void
    {
        Redis::publish("bot:{$bot->id}:config", json_encode([
            'event' => 'config_updated',
            'config' => $config,
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
```

### 2. MarketRouter Module (NEW)

**Purpose**: Unified interface untuk crypto (CCXT) dan forex (MetaApi) markets

**Files**:
```
Modules/MarketRouter/
├── Services/
│   ├── MarketRouter.php
│   ├── SymbolNormalizer.php
│   ├── MarketTypeDetector.php
│   └── TradingHoursService.php
├── Contracts/
│   ├── ExchangeAdapterInterface.php
│   ├── CryptoExchangeAdapter.php
│   └── ForexBrokerAdapter.php
└── Http/
    └── Controllers/Api/
        └── MarketInfoController.php
```

**Key Classes**:

```php
// MarketRouter.php
namespace Addons\TradingManagement\Modules\MarketRouter\Services;

class MarketRouter
{
    public function normalizeSymbol(string $symbol, string $marketType): string
    {
        return match ($marketType) {
            'crypto' => $this->normalizeCryptoSymbol($symbol),
            'forex' => $this->normalizeForexSymbol($symbol),
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
            'crypto' => true,
            'forex' => $this->forexSession->isOpen($symbol),
        };
    }
    
    public function getAdapter(ExchangeConnection $connection): ExchangeAdapterInterface
    {
        return match ($connection->exchange_type) {
            'crypto' => app(CryptoExchangeAdapter::class)->setConnection($connection),
            'fx' => app(ForexBrokerAdapter::class)->setConnection($connection),
        };
    }
}
```

```php
// SymbolNormalizer.php
namespace Addons\TradingManagement\Modules\MarketRouter\Services;

class SymbolNormalizer
{
    // Crypto: BTC/USDT → BTCUSDT
    public function normalizeCryptoSymbol(string $symbol): string
    {
        return str_replace('/', '', $symbol);
    }
    
    // Forex: EUR/USD → EURUSD
    public function normalizeForexSymbol(string $symbol): string
    {
        return str_replace('/', '', $symbol);
    }
    
    // Extract base/quote from symbol
    public function extractParts(string $symbol, string $marketType): array
    {
        return match ($marketType) {
            'crypto' => $this->extractCryptoParts($symbol),
            'forex' => $this->extractForexParts($symbol),
        };
    }
}
```

### 3. PaperTrading Module (NEW)

**Purpose**: Demo mode dengan virtual balance isolation

**Files**:
```
Modules/PaperTrading/
├── Services/
│   ├── PaperTradingService.php
│   └── VirtualPortfolioManager.php
├── Models/
│   └── VirtualPortfolio.php
├── Jobs/
│   └── ExecuteVirtualTradeJob.php
└── Http/
    └── Controllers/Api/
        └── PaperTradingController.php
```

**Key Classes**:

```php
// PaperTradingService.php
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
        
        // Validate virtual balance
        if (!$this->hasEnoughBalance($portfolio, $request)) {
            throw new InsufficientVirtualFundsException();
        }
        
        // Simulate execution dengan realistic slippage
        $execution = $this->simulateExecution($request, $portfolio);
        
        // Update virtual portfolio
        $this->portfolioManager->updateAfterTrade($portfolio, $execution);
        
        return $execution;
    }
    
    private function simulateExecution(
        OrderRequest $request,
        VirtualPortfolio $portfolio
    ): VirtualTradeResult {
        $slippage = $this->calculateSlippage($request, $portfolio->market_type);
        $spread = $this->calculateSpread($request, $portfolio->market_type);
        
        $executionPrice = $request->price * (1 + $slippage);
        $fees = $this->calculateFees($request, $executionPrice, $portfolio->market_type);
        
        return new VirtualTradeResult(
            symbol: $request->symbol,
            direction: $request->direction,
            quantity: $request->quantity,
            executionPrice: $executionPrice,
            fees: $fees,
            slippage: $slippage,
            spread: $spread,
            executedAt: now(),
            isPaper: true,
        );
    }
}
```

### 4. Enhanced TradingBot Module

**Purpose**: Extend existing TradingBot dengan dynamic capabilities

**Files**:
```
Modules/TradingBot/
├── Services/
│   ├── TradingBotService.php           # Enhanced
│   ├── BotStateManager.php             # NEW: State machine
│   └── BotLifecycleOrchestrator.php    # NEW: Lifecycle management
├── Http/
│   ├── Controllers/Api/
│   │   └── TradingBotApiController.php # Enhanced
│   └── Requests/
│       ├── CreateBotRequest.php
│       └── UpdateBotConfigRequest.php
├── Jobs/
│   ├── BotWorkerJob.php                # Enhanced
│   └── BotConfigListenerJob.php        # NEW
└── Services/
    └── ConfigChangeDetector.php        # NEW
```

**Key Classes**:

```php
// BotStateManager.php
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

```php
// BotConfigListenerJob.php
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
        // Reload config without restarting
        Cache::forget("bot_config:{$bot->id}");
        
        // Log the config change
        BotExecutionLog::log($bot, 'config_hot_reload', [
            'changes' => $config,
            'timestamp' => now(),
        ]);
    }
}
```

---

## Database Schema Changes

### New Tables

```sql
-- Virtual portfolios for paper trading
CREATE TABLE virtual_portfolios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    exchange_connection_id BIGINT UNSIGNED NOT NULL,
    balance DECIMAL(20, 8) DEFAULT 0,
    market_type ENUM('crypto', 'fx') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_connection (user_id, exchange_connection_id)
);

-- Config change audit log
CREATE TABLE bot_config_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    changed_by_type ENUM('user', 'system', 'admin') NOT NULL,
    changed_by_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_bot_id (bot_id),
    INDEX idx_created_at (created_at)
);

-- Trading hours cache
CREATE TABLE trading_hours_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    market_type ENUM('crypto', 'fx') NOT NULL,
    symbol VARCHAR(50) NOT NULL,
    is_open TINYINT(1) DEFAULT 1,
    session_start TIME NULL,
    session_end TIME NULL,
    cached_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    UNIQUE KEY unique_market_symbol (market_type, symbol)
);
```

### Modified Tables

```sql
-- Add mode column to trading_bots
ALTER TABLE trading_bots 
ADD COLUMN trading_mode ENUM('demo', 'testnet', 'production') DEFAULT 'production' 
AFTER is_paper_trading;

-- Add last_config_update timestamp
ALTER TABLE trading_bots 
ADD COLUMN last_config_update TIMESTAMP NULL 
AFTER updated_at;

-- Add mode to exchange_connections
ALTER TABLE execution_connections 
ADD COLUMN is_testnet TINYINT(1) DEFAULT 0 
AFTER is_paper_trading;
```

---

## API Contracts

### Enhanced TradingBot API

```php
// Create Bot
POST /api/user/trading-bots
{
    "name": "My Bot",
    "exchange_connection_id": 1,
    "trading_preset_id": 1,
    "filter_strategy_id": 1,
    "trading_mode": "demo", // NEW: demo/testnet/production
    "config": {
        "risk_per_trade": 0.02,
        "stop_loss_pct": 1.0,
        "take_profit_pct": 2.0,
        "trailing_stop_enabled": true,
        "trailing_stop_pct": 0.5
    }
}

// Dynamic Config Update (NEW)
PATCH /api/user/trading-bots/{id}/config
{
    "risk_per_trade": 0.03, // Update without restart
    "take_profit_pct": 3.0
}

// Response - Real-time config reload
{
    "type": "success",
    "message": "Config updated and applied to running bot",
    "data": {
        "bot_id": 123,
        "config": {
            "risk_per_trade": 0.03,
            "take_profit_pct": 3.0
        },
        "applied_at": "2026-01-19T12:00:00Z"
    }
}

// Get Bot with Runtime Config
GET /api/user/trading-bots/{id}/full-config

// Market Info (NEW)
GET /api/market/info
{
    "type": "success",
    "data": {
        "crypto": {
            "symbols": ["BTCUSDT", "ETHUSDT"],
            "trading_hours": "24/7"
        },
        "forex": {
            "symbols": ["EURUSD", "GBPUSD"],
            "trading_hours": {
                "session": "22:00 - 21:00 GMT",
                "break": "21:00 - 22:00 GMT",
                "is_open": true
            }
        }
    }
}

// Paper Trading Balance (NEW)
GET /api/user/trading-bots/{id}/paper-balance
{
    "type": "success",
    "data": {
        "balance": 10000.00,
        "currency": "USD",
        "open_positions_value": 2500.00,
        "total_pnl": 523.45,
        "pnl_percentage": 5.23
    }
}
```

---

## Integration Points

### 1. TradingBotWorker Integration

```php
// In BotWorkerJob.php
class BotWorkerJob implements ShouldQueue
{
    public function handle(): void
    {
        $bot = $this->getBot();
        
        // Subscribe to config changes if running
        if ($bot->status === 'running') {
            $this->subscribeToConfigChanges($bot);
        }
        
        // Main loop
        while ($bot->status === 'running') {
            $this->processSignals($bot);
            $this->checkPositions($bot);
            $this->processConfigChanges($bot);
            
            sleep($bot->config['check_interval'] ?? 5);
        }
    }
}
```

### 2. ExchangeConnection Integration

```php
// In ExchangeConnectionService.php
public function createConnection(array $data, bool $isTestnet = false): ExchangeConnection
{
    $connection = ExchangeConnection::create([
        ...$data,
        'is_testnet' => $isTestnet,
        'is_paper_trading' => $data['trading_mode'] === 'demo',
    ]);
    
    // Validate connection
    if (!$this->testConnection($connection)) {
        throw new ConnectionFailedException();
    }
    
    return $connection;
}
```

### 3. Signal Processing Integration

```php
// In SignalProcessor.php
public function processSignalForBot(Signal $signal, TradingBot $bot): void
{
    // Check market type compatibility
    $marketRouter = app(MarketRouter::class);
    
    if (!$marketRouter->isMarketOpen($bot->exchangeConnection->exchange_type)) {
        Log::warning("Market closed for bot {$bot->id}", [
            'market_type' => $bot->exchangeConnection->exchange_type,
        ]);
        return;
    }
    
    // Process signal
    $this->executeTrade($signal, $bot);
}
```

---

## Configuration

### Environment Variables

```env
# Trading Bot Configuration
TRADING_BOT_CONFIG_CACHE_TTL=3600
TRADING_BOT_REDIS_CHANNEL=bot:config
TRADING_BOT_MAX_CONCURRENT_TRADES=5
TRADING_BOT_DEFAULT_CHECK_INTERVAL=5

# Paper Trading Defaults
PAPER_TRADING_DEFAULT_BALANCE=10000
PAPER_TRADING_SLIPPAGE_SIMULATION=true

# Forex Market Hours (UTC)
FOREX_SESSION_START=22:00
FOREX_SESSION_END=21:00
FOREX_BREAK_START=21:00
FOREX_BREAK_END=22:00
```

### Addon Configuration

```php
// config/trading-management.php
return [
    'trading_bot' => [
        'max_bots_per_user' => 10,
        'allow_demo_mode' => true,
        'allow_testnet' => true,
        'required_config_fields' => [
            'risk_per_trade',
            'stop_loss_pct',
            'take_profit_pct',
        ],
    ],
    
    'market_router' => [
        'supported_markets' => ['crypto', 'forex'],
        'crypto_exchanges' => ['binance', 'coinbase', 'kraken'],
        'forex_brokers' => ['metaapi', 'mt4', 'mt5'],
        'default_slippage' => [
            'crypto' => 0.001, // 0.1%
            'forex' => 0.0002, // 0.02%
        ],
    ],
    
    'paper_trading' => [
        'enabled' => true,
        'default_balance' => 10000,
        'fee_simulation' => true,
        'slippage_simulation' => true,
    ],
];
```

---

## Event Flow Diagrams

### 1. Dynamic Config Update Flow
```
User → API → TradingBotConfigManager 
    → DB Update → Redis Publish
    → Bot Worker (Subscriber) → Cache Invalidate
    → Bot Execution (Hot Reload)
```

### 2. Trade Execution Flow
```
Signal Received → MarketRouter (Check market hours)
    → BotStateManager (Validate state)
    → FilterStrategy (Check conditions)
    → RiskManagement (Validate position size)
    → AiAnalysis (Optional confirmation)
    → Execution (CCXT/MetaApi)
    → PositionMonitoring
    → Notification
```

### 3. Demo Mode Flow
```
User selects Demo mode → VirtualPortfolioManager (Create/Load)
    → PaperTradingService.executeVirtualTrade()
    → Simulate execution dengan slippage
    → Update VirtualPortfolio balance
    → Log to BotExecutionLog (is_paper=true)
```

---

## Security Considerations

### 1. Config Update Security
- Validate config changes via signed requests
- Log all config changes with user identification
- Rate limit config update endpoints

### 2. Demo Mode Isolation
- Virtual portfolios are user-specific
- No interference dengan real trading
- Clear visual indicator of demo mode

### 3. API Security
- Require authentication for all trading endpoints
- Validate exchange credentials before use
- Encrypt sensitive connection data

---

## Performance Considerations

### 1. Caching Strategy
- Bot config cached with 1-hour TTL
- Market hours cached dengan 5-minute TTL
- Symbol specs cached per exchange

### 2. Queue Optimization
- Config changes processed asynchronously
- Demo trades executed in queue
- Batch position updates

### 3. Database Optimization
- Indexes on frequently queried columns
- Composite indexes for bot queries
- Archive old execution logs

---

## Change History
- 2026-01-19: Initial design document
