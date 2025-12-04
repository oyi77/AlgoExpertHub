# Trading Management Addon

**Version**: 2.0.0  
**Status**: In Development (Phase 1 Complete)

---

## Overview

Unified trading management system consolidating 7 fragmented addons into one modular addon with clear data pipeline and improved UX.

### Key Features

- 🔌 **Data Provider**: Fetch market data from mtapi.io, CCXT exchanges
- 💾 **Market Data Storage**: Centralized OHLCV storage with caching
- 🔍 **Filter Strategy**: Technical indicator filtering (EMA, RSI, PSAR)
- 🤖 **AI Analysis**: AI-powered market confirmation (OpenAI, Gemini)
- 📊 **Risk Management**: Manual presets + AI adaptive risk
- ⚡ **Trade Execution**: Execute on exchanges/brokers
- 📈 **Position Monitoring**: Track SL/TP, calculate analytics
- 👥 **Copy Trading**: Social trading features
- 🧪 **Backtesting**: Test strategies on historical data

---

## Installation

### Step 1: Addon is Pre-installed

The addon is located in `main/addons/trading-management-addon/`

### Step 2: Register Service Provider

Add to `config/app.php` providers array (or `AppServiceProvider`):

```php
\Addons\TradingManagement\AddonServiceProvider::class,
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Configure

Publish config (optional):

```bash
php artisan vendor:publish --tag=trading-management-config
```

Edit `.env`:

```env
# mtapi.io Settings
MTAPI_API_KEY=your_api_key_here
MTAPI_BASE_URL=https://api.mtapi.io

# Data Settings
TM_FETCH_INTERVAL=5
TM_DATA_RETENTION_DAYS=365
TM_CACHE_TTL=300

# Risk Settings
TM_DEFAULT_RISK_PERCENT=1.0
TM_MAX_RISK_PERCENT=5.0
```

---

## Menu Structure

### Admin Panel

```
📊 Trading Management ▼
   ├── 🔧 Trading Configuration
   │   └── Tabs: Data Connections | Risk Presets | Smart Risk Settings
   ├── ⚡ Trading Operations
   │   └── Tabs: Connections | Executions | Positions | Analytics
   ├── 🎯 Trading Strategy
   │   └── Tabs: Filter Strategies | AI Models | Decision Logs
   ├── 👤 Copy Trading
   │   └── Tabs: Traders | Subscriptions | Analytics
   └── 🧪 Trading Test
       └── Tabs: Create | Results | Reports
```

### User Panel

Same structure, scoped to user's own data.

---

## Module System

### 9 Modules (Enable/Disable in addon.json)

| Module | Status | Phase | Description |
|--------|--------|-------|-------------|
| data_provider | ✅ Planned | Phase 2 | Data connections (mtapi.io, CCXT) |
| market_data | ✅ Planned | Phase 2 | Storage & caching |
| filter_strategy | ✅ Planned | Phase 3 | Technical filtering |
| ai_analysis | ✅ Planned | Phase 3 | AI confirmation |
| risk_management | ✅ Planned | Phase 4 | Presets + Smart Risk |
| execution | ✅ Planned | Phase 5 | Trade execution |
| position_monitoring | ✅ Planned | Phase 5 | Position tracking |
| copy_trading | ✅ Planned | Phase 6 | Social trading |
| backtesting | ⏸️ Disabled | Phase 8 | Strategy testing |

---

## Data Pipeline

```
Data Fetching (mtapi.io, CCXT)
  ↓ DataReceived Event
Market Data Storage (OHLCV + cache)
  ↓ DataStored Event
Technical Filtering (EMA, RSI)
  ↓ DataFiltered Event (pass/fail)
AI Analysis (OpenAI/Gemini)
  ↓ SignalAnalyzed Event (confidence)
Risk Calculation (Preset OR Smart Risk)
  ↓ RiskCalculated Event (lot size)
Trade Execution (CCXT/mtapi.io)
  ↓ TradeExecuted Event
Position Monitoring (SL/TP)
  ↓ PositionClosed Event
Analytics (Win rate, profit factor)
```

---

## Development Progress

### ✅ Phase 1: Foundation (Complete)

- [x] Addon structure created
- [x] Shared contracts (DataProviderInterface, ExchangeAdapterInterface, RiskCalculatorInterface)
- [x] Shared traits (HasEncryptedCredentials, ConnectionHealthCheck)
- [x] DTOs (MarketDataDTO, TradeExecutionDTO)
- [x] Events (DataReceived, TradeExecuted)
- [x] Service Provider with module system
- [x] Routes structure (admin + user)
- [x] Configuration file

### 🟡 Phase 2: Data Layer (Next)

- [ ] data-provider module
- [ ] market-data module
- [ ] mtapi.io adapter
- [ ] CCXT adapter
- [ ] Background jobs

### 🟡 Remaining Phases

- Phase 3: Analysis Layer (filter + AI)
- Phase 4: Risk Layer (merge presets + smart risk)
- Phase 5: Execution Layer (execution + monitoring)
- Phase 6: Social Layer (copy trading)
- Phase 7: UI Consolidation (tabbed interface)
- Phase 8: Backtesting (new feature)
- Phase 9: Testing & Optimization
- Phase 10: Deprecation & Migration

---

## Shared Components

### Contracts (Interfaces)

- `DataProviderInterface`: Data fetching from providers
- `ExchangeAdapterInterface`: Trade execution on exchanges
- `RiskCalculatorInterface`: Position sizing and risk management

### Traits

- `HasEncryptedCredentials`: Automatic credential encryption/decryption
- `ConnectionHealthCheck`: Connection monitoring and status management

### DTOs

- `MarketDataDTO`: Standardized market data format
- `TradeExecutionDTO`: Standardized trade execution data

### Events

- `DataReceived`: New market data fetched
- `TradeExecuted`: Trade successfully executed

---

## Routes

### Admin Routes

- `/admin/trading-management` - Dashboard
- `/admin/trading-management/config` - Trading Configuration
- `/admin/trading-management/operations` - Trading Operations
- `/admin/trading-management/strategy` - Trading Strategy
- `/admin/trading-management/copy-trading` - Copy Trading
- `/admin/trading-management/test` - Trading Test

### User Routes

- `/user/trading-management` - Dashboard
- `/user/trading-management/config` - My Configuration
- `/user/trading-management/operations` - Auto Trading
- `/user/trading-management/strategy` - My Strategies
- `/user/trading-management/copy-trading` - Copy Trading
- `/user/trading-management/test` - Backtesting

---

## Architecture

### Design Principles

1. **Modular**: Each module can be enabled/disabled independently
2. **Event-Driven**: Modules communicate via Laravel events
3. **Loosely Coupled**: Shared interfaces, no direct dependencies
4. **Testable**: Contracts allow easy mocking
5. **Scalable**: Easy to add new modules

### Benefits

- ✅ 30% code reduction (centralized services)
- ✅ Better UX (1 main menu, 5 submenus, tabbed interface)
- ✅ Clear pipeline (explicit data flow)
- ✅ Easier maintenance (update once, not 7 times)
- ✅ Scalability (easy to add new modules)

---

## Dependencies

```json
{
    "ccxt/ccxt": "^4.0",
    "guzzlehttp/guzzle": "^7.0"
}
```

Install via:

```bash
composer require ccxt/ccxt guzzlehttp/guzzle
```

---

## Documentation

- [Full Analysis](../../../docs/trading-management-consolidation-analysis.md)
- [Final Structure](../../../docs/trading-management-final-structure.md)
- [UI Organization](../../../docs/trading-management-ui-organization.md)
- [Changelog](../../../docs/CHANGELOG-trading-management.md)

---

## Support

For issues or questions:
- Create bd issue: `bd create "Issue title" -t bug --deps discovered-from:AlgoExpertHub-0my`
- Check documentation in `docs/` folder
- Review `.cursor/rules/` for development guidelines

---

## License

Proprietary - AlgoExpertHub

---

**Status**: Phase 1 Complete ✅ | Next: Phase 2 (Data Layer)

