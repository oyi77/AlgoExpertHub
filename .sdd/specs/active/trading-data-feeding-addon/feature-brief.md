# Trading Data Feeding Addon - Feature Brief

**Feature Name**: Trading Data Feeding Addon  
**Type**: Addon  
**Priority**: High  
**Estimated Effort**: 3-4 weeks  

## Overview
A new addon to centralize real-time and historical trading data ingestion from multiple sources (FX brokers, crypto exchanges). Primary focus: **mtapi.io** integration for FX data. This addon will serve as the foundation for the algorithmic trading pipeline.

## Problem Statement
Currently, trading execution and data handling are tightly coupled in the trading-execution-engine-addon. This creates:
- **Tight coupling**: Connection management handles both execution AND data retrieval
- **Limited scalability**: Cannot easily add new data providers
- **No data pipeline**: Missing structured flow for algo trading (data → processing → decision → execution)
- **Duplicate code**: Similar connection logic scattered across addons

## Proposed Solution
Create a dedicated **Trading Data Feeding Addon** that:
1. **Centralizes data ingestion** from multiple providers (mtapi.io priority)
2. **Decouples data from execution** - separate concerns
3. **Enables algo trading pipeline**: Data Feeding → Data Clearing → Filtering → Indicators/AI → Confidence → Execution
4. **Refactors connection handling** to be reusable across addons

## Goals
1. **Add mtapi.io integration** for FX broker data (OHLCV, ticks, account info)
2. **Refactor connection architecture** to separate data connections from execution connections
3. **Design algo trading pipeline** with clear stages and hooks for other addons
4. **Support multiple data providers** (crypto exchanges, FX brokers, market data APIs)

## Algo Trading Pipeline Architecture

```
┌─────────────────────┐
│  Data Feeding       │ ← THIS ADDON (mtapi.io, exchanges, APIs)
│  (Raw Market Data)  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Data Clearing      │ (Remove outliers, handle missing data)
│  (Clean Data)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Data Filtering     │ (Filter by timeframe, pairs, conditions)
│  (Filtered Data)    │ ← filter-strategy-addon can hook here
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Technical/AI Layer │ (Indicators, patterns, AI analysis)
│  (Trading Signals)  │ ← ai-trading-addon, technical indicator addon
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  AI Confidence      │ (AI evaluates signal confidence 0-100%)
│  (Confidence Score) │ ← ai-trading-addon
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Execution Engine   │ (Execute trade if confidence threshold met)
│  (Trade Execution)  │ ← trading-execution-engine-addon
└─────────────────────┘
```

## Key Features

### Phase 1: Core Data Feeding
- **Data Connection Management**
  - Create, configure, test data connections (separate from execution connections)
  - Support multiple connection types (mtapi.io, CCXT, custom APIs)
  - Admin-owned (global) and user-owned (private) data connections
  - Connection health monitoring, auto-reconnect

- **mtapi.io Integration (Priority)**
  - Connect to MT4/MT5 accounts via mtapi.io REST API
  - Fetch real-time tick data, OHLCV bars (M1, M5, H1, H4, D1, etc.)
  - Fetch historical data (backfill for backtesting)
  - Fetch account info (balance, equity, open positions)
  - Support multiple MT accounts per user/admin

- **Data Storage**
  - Store raw market data (OHLCV) in optimized tables
  - Store tick data (optional, for scalping strategies)
  - Efficient indexing (symbol, timeframe, timestamp)
  - Data retention policies (configurable)

- **Data Streaming**
  - Real-time data streaming via websockets (where available)
  - Polling fallback for APIs without websockets
  - Job scheduler for periodic data updates

### Phase 2: Connection Refactoring
- **Extract shared connection logic**
  - Create `DataConnection` model (separate from `ExecutionConnection`)
  - `DataConnection`: For fetching market data
  - `ExecutionConnection`: For executing trades
  - Shared traits: encryption, health checks, testing

- **Migration Strategy**
  - Keep existing `ExecutionConnection` for backward compatibility
  - Add new `DataConnection` model in this addon
  - Future: Optionally merge or link connections (a connection can be both data + execution)

### Phase 3: Pipeline Foundation
- **Pipeline Events**
  - `DataReceived` event: Fired when new market data arrives
  - `DataCleaned` event: After data cleaning
  - `DataFiltered` event: After filtering
  - `SignalGenerated` event: When trading signal created
  - Other addons can listen to these events

- **Pipeline Stages (Interfaces)**
  - `DataCleanerInterface`: Clean raw data
  - `DataFilterInterface`: Filter data by criteria
  - `IndicatorCalculatorInterface`: Calculate technical indicators
  - `SignalGeneratorInterface`: Generate trading signals
  - `ConfidenceEvaluatorInterface`: Evaluate signal confidence

- **Pipeline Service**
  - Orchestrate flow through stages
  - Register stage implementations (from other addons)
  - Execute pipeline for each data update

## Database Schema

### `data_connections` Table
```sql
- id (bigint, PK)
- user_id (bigint, nullable, FK to users) -- User-owned connection
- admin_id (bigint, nullable, FK to admins) -- Admin-owned connection
- name (string) -- Connection name
- type (enum: 'mtapi', 'ccxt_crypto', 'custom_api') -- Provider type
- provider (string) -- Provider identifier (e.g., 'binance', 'mt4_account_123')
- credentials (text, encrypted) -- API keys, tokens
- config (json) -- Provider-specific config
- status (enum: 'active', 'inactive', 'error', 'testing')
- is_active (boolean)
- is_admin_owned (boolean)
- last_connected_at (timestamp, nullable)
- last_error (text, nullable)
- settings (json) -- Data preferences (symbols, timeframes)
- created_at, updated_at
```

### `market_data` Table
```sql
- id (bigint, PK)
- data_connection_id (bigint, FK to data_connections)
- symbol (string) -- Trading pair (EURUSD, BTCUSDT)
- timeframe (enum: 'M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN')
- timestamp (timestamp) -- Candle open time
- open (decimal)
- high (decimal)
- low (decimal)
- close (decimal)
- volume (decimal, nullable)
- source_type (string) -- 'mtapi', 'ccxt', etc.
- created_at, updated_at
- UNIQUE (data_connection_id, symbol, timeframe, timestamp) -- Prevent duplicates
- INDEX (symbol, timeframe, timestamp) -- Fast queries
```

### `data_connection_logs` Table
```sql
- id (bigint, PK)
- data_connection_id (bigint, FK to data_connections)
- action (enum: 'connect', 'disconnect', 'fetch_data', 'error')
- status (enum: 'success', 'failed')
- message (text, nullable)
- metadata (json, nullable) -- Extra info (rows fetched, latency, etc.)
- created_at
```

### `pipeline_executions` Table (Phase 3)
```sql
- id (bigint, PK)
- data_connection_id (bigint, FK to data_connections)
- symbol (string)
- timeframe (string)
- stage (enum: 'feeding', 'cleaning', 'filtering', 'indicators', 'confidence', 'execution')
- status (enum: 'pending', 'processing', 'completed', 'failed')
- input_data (json, nullable) -- Input to stage
- output_data (json, nullable) -- Output from stage
- error_message (text, nullable)
- execution_time_ms (integer) -- Performance tracking
- created_at, updated_at
```

## Technical Approach

### mtapi.io Integration
**API Documentation**: https://docs.mtapi.io/

**Key Endpoints**:
- `GET /account` - Get account info
- `GET /history` - Get historical bars
- `GET /prices` - Get real-time prices
- `GET /positions` - Get open positions

**Implementation**:
```php
class MtapiAdapter implements DataProviderInterface
{
    public function connect(array $credentials): bool;
    public function fetchOHLCV(string $symbol, string $timeframe, int $limit): array;
    public function fetchTicks(string $symbol, int $limit): array;
    public function getAccountInfo(): array;
    public function isConnected(): bool;
}
```

### Job Scheduler
- **FetchMarketDataJob**: Poll data connections for new data (every 1-5 minutes depending on timeframe)
- **BackfillHistoricalDataJob**: Fetch historical data for new connections
- **CleanOldDataJob**: Remove old data based on retention policy (daily)

### Service Layer
- **DataConnectionService**: Manage connections (CRUD, test, activate)
- **MarketDataService**: Store, retrieve, update market data
- **PipelineService**: Orchestrate trading pipeline (Phase 3)
- **MtapiService**: mtapi.io API wrapper

## Integration with Existing Addons

### Trading Execution Engine Addon
- Listen to `SignalGenerated` event → Execute trade if confidence threshold met
- Use `ExecutionConnection` for trade execution
- Use `DataConnection` for market data (separate concerns)

### AI Trading Addon
- Listen to `DataFiltered` event → Apply AI models
- Calculate indicators, pattern recognition
- Generate trading signals with confidence scores

### Filter Strategy Addon
- Implement `DataFilterInterface`
- Filter data by user-defined strategies

### Smart Risk Management Addon
- Use market data for dynamic risk calculations
- Adjust position sizes based on volatility

## Success Criteria
1. ✅ mtapi.io integration working (connect, fetch data)
2. ✅ Real-time data streaming for at least 1 FX broker
3. ✅ Historical data backfill (at least 1 year)
4. ✅ `DataConnection` model separate from `ExecutionConnection`
5. ✅ Pipeline architecture defined with events
6. ✅ At least 2 other addons integrated with pipeline (execution + AI)
7. ✅ Admin UI for connection management
8. ✅ User UI for viewing data feeds
9. ✅ Data retention and cleanup working

## Risks & Mitigation
- **API rate limits**: Cache data, respect rate limits, exponential backoff
- **Data volume**: Optimize storage (use partitioning, compression), cleanup old data
- **Connection failures**: Auto-reconnect, health monitoring, alerts
- **Breaking changes in mtapi.io API**: Version API calls, fallback mechanisms

## Timeline
- **Week 1**: Addon scaffold, database schema, `DataConnection` model
- **Week 2**: mtapi.io integration, `MtapiAdapter`, data fetching
- **Week 3**: Data storage, jobs (FetchMarketDataJob, BackfillHistoricalDataJob)
- **Week 4**: Admin/User UI, connection management, testing
- **Week 5-6**: Pipeline foundation (events, interfaces, services)
- **Week 7**: Integration with execution engine + AI addons
- **Week 8**: Testing, optimization, documentation

## Open Questions
1. Should we support real-time websocket streaming in Phase 1 or defer to Phase 2?
2. Data retention: Default 1 year? Configurable per connection?
3. Should `DataConnection` and `ExecutionConnection` be linked (one connection can do both)?
4. AI confidence threshold: Global setting or per-user/per-connection?
5. Support for crypto exchanges in Phase 1 or Phase 2?

## Next Steps
1. Create detailed spec.md with full requirements
2. Design database migrations
3. Scaffold addon structure
4. Implement mtapi.io adapter
5. Create jobs for data fetching
6. Build admin UI for connection management

