# Trading Data Feeding Addon - Task Breakdown

## Epic Overview
Create a trading data feeding addon to centralize market data ingestion from FX brokers (mtapi.io priority) and crypto exchanges, refactor connection handling, and establish an algorithmic trading pipeline.

---

## Phase 1: Foundation & Architecture (Week 1)

### Task 1.1: Addon Scaffold Setup
**Priority**: P0 (Critical)  
**Effort**: 0.5 day  
**Dependencies**: None

**Subtasks**:
- [ ] Create addon directory structure: `main/addons/trading-data-feeding-addon/`
- [ ] Create `addon.json` manifest with modules (admin_ui, user_ui, data_feeding)
- [ ] Create `AddonServiceProvider.php` with boot/register methods
- [ ] Set up routes: `routes/admin.php`, `routes/user.php`, `routes/api.php`
- [ ] Create placeholder controllers, models, services folders
- [ ] Register addon in `App\Providers\AppServiceProvider::registerAddonServiceProviders()`

**Acceptance Criteria**:
- Addon loads without errors
- Routes registered (empty but accessible)
- Service provider boots correctly

---

### Task 1.2: Database Schema Design & Migrations
**Priority**: P0 (Critical)  
**Effort**: 1 day  
**Dependencies**: Task 1.1

**Subtasks**:
- [ ] Create migration: `create_data_connections_table.php`
  - Columns: id, user_id, admin_id, name, type, provider, credentials, config, status, is_active, is_admin_owned, last_connected_at, last_error, settings
  - Foreign keys: user_id → users.id, admin_id → admins.id
  - Indexes: user_id, admin_id, type, status, is_active
- [ ] Create migration: `create_market_data_table.php`
  - Columns: id, data_connection_id, symbol, timeframe, timestamp, open, high, low, close, volume, source_type
  - Unique constraint: (data_connection_id, symbol, timeframe, timestamp)
  - Indexes: (symbol, timeframe, timestamp), data_connection_id
- [ ] Create migration: `create_data_connection_logs_table.php`
  - Columns: id, data_connection_id, action, status, message, metadata, created_at
  - Index: data_connection_id, created_at
- [ ] Create migration: `create_pipeline_executions_table.php` (optional for Phase 3)
  - Columns: id, data_connection_id, symbol, timeframe, stage, status, input_data, output_data, error_message, execution_time_ms
- [ ] Run migrations, verify schema

**Acceptance Criteria**:
- All migrations run successfully
- Tables created with correct columns and constraints
- No foreign key errors
- Can rollback migrations cleanly

---

### Task 1.3: Core Models Creation
**Priority**: P0 (Critical)  
**Effort**: 1 day  
**Dependencies**: Task 1.2

**Subtasks**:
- [ ] Create `DataConnection` model
  - Namespace: `Addons\TradingDataFeedingAddon\App\Models`
  - Traits: HasFactory, Searchable
  - Fillable fields, casts (credentials encrypted, settings as array)
  - Relationships: user(), admin(), marketData(), logs()
  - Scopes: active(), byType(), byUser(), adminOwned(), userOwned()
  - Methods: setCredentialsAttribute() (encrypt), getCredentialsAttribute() (decrypt), isActive(), markAsError(), markAsActive(), updateLastConnected()
- [ ] Create `MarketData` model
  - Fillable, casts (timestamp as datetime)
  - Relationships: dataConnection()
  - Scopes: bySymbol(), byTimeframe(), betweenDates()
  - Methods: getLatestCandle(), getCandles()
- [ ] Create `DataConnectionLog` model
  - Fillable, relationships
  - Scopes: byAction(), byStatus(), recent()
- [ ] Create `PipelineExecution` model (Phase 3, optional now)

**Acceptance Criteria**:
- Models can be instantiated
- Relationships work (test in tinker)
- Encryption/decryption of credentials works
- Scopes return correct results

---

### Task 1.4: Connection Architecture Refactoring Analysis
**Priority**: P1 (High)  
**Effort**: 0.5 day  
**Dependencies**: Task 1.3

**Subtasks**:
- [ ] Analyze current `ExecutionConnection` model in trading-execution-engine-addon
- [ ] Document differences between `DataConnection` and `ExecutionConnection`
- [ ] Design shared traits/interfaces:
  - `HasEncryptedCredentials` trait (for both models)
  - `ConnectionHealthCheck` trait (test, reconnect logic)
  - `ConnectionInterface` (optional, for polymorphism)
- [ ] Create trait: `HasEncryptedCredentials` in `Addons\TradingDataFeedingAddon\App\Traits`
- [ ] Create trait: `ConnectionHealthCheck`
- [ ] Document migration strategy (keep ExecutionConnection as-is for now, add DataConnection separately)

**Acceptance Criteria**:
- Traits created and documented
- Clear separation of concerns (data vs execution)
- Plan for future: optionally merge or link connections

---

## Phase 2: mtapi.io Integration (Week 2)

### Task 2.1: mtapi.io API Research & Setup
**Priority**: P0 (Critical)  
**Effort**: 0.5 day  
**Dependencies**: Task 1.3

**Subtasks**:
- [ ] Review mtapi.io API documentation (https://docs.mtapi.io/)
- [ ] Create test account on mtapi.io (or use existing)
- [ ] Document required credentials (API key, MT account ID)
- [ ] Document key endpoints:
  - `GET /account` - Account info
  - `GET /history` - Historical bars
  - `GET /prices` - Real-time prices
  - `GET /positions` - Open positions
- [ ] Test API calls with Postman/cURL
- [ ] Store test credentials in `.env` (MTAPI_API_KEY, MTAPI_ACCOUNT_ID)

**Acceptance Criteria**:
- API documentation reviewed
- Test account working
- Sample API calls successful
- Credentials stored securely

---

### Task 2.2: mtapi.io Adapter Implementation
**Priority**: P0 (Critical)  
**Effort**: 2 days  
**Dependencies**: Task 2.1

**Subtasks**:
- [ ] Create `DataProviderInterface` in `Addons\TradingDataFeedingAddon\App\Contracts`
  - Methods: connect(), fetchOHLCV(), fetchTicks(), getAccountInfo(), isConnected(), disconnect()
- [ ] Create `MtapiAdapter` implementing `DataProviderInterface`
  - Namespace: `Addons\TradingDataFeedingAddon\App\Adapters`
  - Use Guzzle HTTP client for API calls
  - Methods:
    - `connect(array $credentials): bool` - Test connection
    - `fetchOHLCV(string $symbol, string $timeframe, int $limit): array` - Get OHLCV bars
    - `fetchTicks(string $symbol, int $limit): array` - Get tick data
    - `getAccountInfo(): array` - Account balance, equity
    - `isConnected(): bool` - Check connection status
  - Handle API errors, rate limits, timeouts
  - Return normalized data format (consistent structure)
- [ ] Create `AdapterFactory` to instantiate correct adapter based on connection type
- [ ] Unit tests for MtapiAdapter (mock API responses)

**Acceptance Criteria**:
- MtapiAdapter can connect to mtapi.io
- fetchOHLCV returns valid OHLCV data
- API errors handled gracefully (log, throw custom exceptions)
- Unit tests pass (>80% coverage)

---

### Task 2.3: Data Connection Service
**Priority**: P0 (Critical)  
**Effort**: 1.5 days  
**Dependencies**: Task 2.2

**Subtasks**:
- [ ] Create `DataConnectionService` in `Addons\TradingDataFeedingAddon\App\Services`
  - Methods:
    - `create(array $data): DataConnection` - Create new connection
    - `update(DataConnection $connection, array $data): DataConnection` - Update connection
    - `delete(DataConnection $connection): bool` - Delete connection
    - `test(DataConnection $connection): array` - Test connection (call adapter's connect())
    - `activate(DataConnection $connection): bool` - Activate connection
    - `deactivate(DataConnection $connection): bool` - Deactivate connection
    - `getAdapter(DataConnection $connection): DataProviderInterface` - Get adapter instance
- [ ] Implement connection testing logic (validate credentials, check API response)
- [ ] Log connection actions to `data_connection_logs` table
- [ ] Handle errors, return result arrays `['type' => 'success|error', 'message' => '...']`

**Acceptance Criteria**:
- Can create, update, delete connections via service
- Test connection validates credentials correctly
- Logs created for all actions
- Service methods follow project conventions (return format)

---

### Task 2.4: Market Data Storage Service
**Priority**: P0 (Critical)  
**Effort**: 1 day  
**Dependencies**: Task 2.2, Task 2.3

**Subtasks**:
- [ ] Create `MarketDataService` in `Addons\TradingDataFeedingAddon\App\Services`
  - Methods:
    - `store(DataConnection $connection, array $ohlcvData): int` - Store OHLCV bars (batch insert)
    - `getLatest(string $symbol, string $timeframe, int $limit): Collection` - Get latest candles
    - `getRange(string $symbol, string $timeframe, $startDate, $endDate): Collection` - Get candles in date range
    - `exists(DataConnection $connection, string $symbol, string $timeframe, $timestamp): bool` - Check if candle exists
    - `cleanup(int $retentionDays): int` - Delete old data beyond retention period
- [ ] Implement batch insert for performance (insert 1000+ rows efficiently)
- [ ] Handle duplicate prevention (unique constraint, use INSERT IGNORE or upsert)
- [ ] Normalize data format (ensure consistent structure across providers)

**Acceptance Criteria**:
- Can store OHLCV data from mtapi.io
- Batch insert handles 1000+ rows without performance issues
- Duplicates prevented (no constraint errors)
- Cleanup method removes old data correctly

---

## Phase 3: Background Jobs & Data Fetching (Week 3)

### Task 3.1: Fetch Market Data Job
**Priority**: P0 (Critical)  
**Effort**: 1.5 days  
**Dependencies**: Task 2.4

**Subtasks**:
- [ ] Create `FetchMarketDataJob` in `Addons\TradingDataFeedingAddon\App\Jobs`
  - Implement ShouldQueue interface
  - Properties: $dataConnectionId, $symbols, $timeframes
  - Handle method:
    - Get DataConnection by ID
    - Get adapter via DataConnectionService
    - Loop through symbols/timeframes
    - Fetch OHLCV data via adapter
    - Store data via MarketDataService
    - Update connection's last_connected_at
    - Log success/failure
  - Error handling (try-catch, log errors, mark connection as error)
  - Retry logic (3 attempts, exponential backoff)
- [ ] Register job in service provider (if needed)
- [ ] Test job execution (dispatch, verify data stored)

**Acceptance Criteria**:
- Job fetches data from mtapi.io successfully
- Data stored in market_data table
- Errors logged and connection marked as error
- Retry logic works (test by mocking API failure)

---

### Task 3.2: Backfill Historical Data Job
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 3.1

**Subtasks**:
- [ ] Create `BackfillHistoricalDataJob`
  - Properties: $dataConnectionId, $symbol, $timeframe, $startDate, $endDate
  - Handle method:
    - Fetch historical data in chunks (e.g., 1000 bars per request)
    - Store data via MarketDataService
    - Log progress (e.g., "Fetched 5000/50000 bars")
    - Handle rate limits (sleep between requests)
  - Support for long-running backfills (split into smaller jobs if needed)
- [ ] Create command: `php artisan data-feeding:backfill {connection_id} {symbol} {timeframe} {start_date} {end_date}`
- [ ] Test backfill for 1 year of H1 data

**Acceptance Criteria**:
- Job fetches historical data correctly
- Large backfills don't timeout (chunked requests)
- Rate limits respected
- Command works, can trigger backfill manually

---

### Task 3.3: Scheduled Data Updates
**Priority**: P0 (Critical)  
**Effort**: 0.5 day  
**Dependencies**: Task 3.1

**Subtasks**:
- [ ] Add scheduled job to service provider's boot method:
  - `$schedule->job(new FetchMarketDataJob($connectionId, $symbols, $timeframes))->everyFiveMinutes();`
  - Or create command and schedule it
- [ ] Create `FetchAllActiveConnectionsJob` that dispatches FetchMarketDataJob for each active connection
- [ ] Schedule: `$schedule->job(new FetchAllActiveConnectionsJob())->everyMinute();`
- [ ] Configure different intervals based on timeframe (M1 every minute, H1 every hour)
- [ ] Test scheduler (run `php artisan schedule:run`)

**Acceptance Criteria**:
- Scheduler runs every minute (or configured interval)
- FetchMarketDataJob dispatched for all active connections
- Data updated in real-time (or near real-time)
- No duplicate jobs (check queue for duplicates)

---

### Task 3.4: Data Cleanup Job
**Priority**: P2 (Medium)  
**Effort**: 0.5 day  
**Dependencies**: Task 2.4

**Subtasks**:
- [ ] Create `CleanOldMarketDataJob`
  - Handle method:
    - Call MarketDataService::cleanup($retentionDays)
    - Log number of rows deleted
- [ ] Schedule daily: `$schedule->job(new CleanOldMarketDataJob())->dailyAt('02:00');`
- [ ] Make retention days configurable (config file or per connection)
- [ ] Test cleanup (insert old data, run job, verify deletion)

**Acceptance Criteria**:
- Old data deleted based on retention policy
- Logs show number of rows deleted
- Runs daily without issues

---

## Phase 4: Admin & User UI (Week 4)

### Task 4.1: Admin Connection Management UI
**Priority**: P0 (Critical)  
**Effort**: 2 days  
**Dependencies**: Task 2.3

**Subtasks**:
- [ ] Create routes in `routes/admin.php`:
  - `GET /admin/data-connections` - List all connections
  - `GET /admin/data-connections/create` - Create form
  - `POST /admin/data-connections` - Store connection
  - `GET /admin/data-connections/{id}/edit` - Edit form
  - `PUT /admin/data-connections/{id}` - Update connection
  - `DELETE /admin/data-connections/{id}` - Delete connection
  - `POST /admin/data-connections/{id}/test` - Test connection
  - `POST /admin/data-connections/{id}/activate` - Activate
  - `POST /admin/data-connections/{id}/deactivate` - Deactivate
- [ ] Create `Backend\DataConnectionController`
  - Inject DataConnectionService
  - Methods: index, create, store, edit, update, destroy, test, activate, deactivate
  - Use Form Request for validation
  - Return views with success/error messages
- [ ] Create views in `resources/views/backend/`:
  - `data-connections/index.blade.php` - List with actions (test, edit, delete)
  - `data-connections/create.blade.php` - Form (name, type, provider, credentials, settings)
  - `data-connections/edit.blade.php` - Edit form
- [ ] Add menu item in admin sidebar (use addon service provider)
- [ ] Apply middleware: `admin`, `demo`, `permission:manage-data-connections,admin`

**Acceptance Criteria**:
- Admin can create, edit, delete connections
- Test connection button works (validates credentials)
- Activate/deactivate buttons work
- Credentials encrypted in database
- UI follows platform theme (Bootstrap, existing admin styles)

---

### Task 4.2: User Connection Management UI
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 4.1

**Subtasks**:
- [ ] Create routes in `routes/user.php`:
  - `GET /user/data-connections` - List user's connections
  - `GET /user/data-connections/create` - Create form
  - `POST /user/data-connections` - Store connection
  - `GET /user/data-connections/{id}/edit` - Edit form
  - `PUT /user/data-connections/{id}` - Update connection
  - `DELETE /user/data-connections/{id}` - Delete connection
  - `POST /user/data-connections/{id}/test` - Test connection
- [ ] Create `User\DataConnectionController`
  - Similar to admin controller but scoped to user
  - Filter connections by user_id (user-owned only)
- [ ] Create views in `resources/views/user/`:
  - `data-connections/index.blade.php`
  - `data-connections/create.blade.php`
  - `data-connections/edit.blade.php`
- [ ] Add menu item in user dashboard
- [ ] Apply middleware: `auth`, `inactive`, `is_email_verified`, `2fa`, `kyc`

**Acceptance Criteria**:
- Users can manage their own connections (CRUD)
- Cannot see/edit admin-owned connections
- Test connection works
- UI matches user dashboard theme

---

### Task 4.3: Market Data Viewer (Admin & User)
**Priority**: P2 (Medium)  
**Effort**: 1 day  
**Dependencies**: Task 2.4

**Subtasks**:
- [ ] Create route: `GET /admin/data-connections/{id}/market-data`
- [ ] Create route: `GET /user/data-connections/{id}/market-data`
- [ ] Create controller method: `showMarketData(DataConnection $connection)`
  - Fetch latest market data for connection (last 100 candles)
  - Support filtering by symbol, timeframe
  - Paginate results
- [ ] Create view: `data-connections/market-data.blade.php`
  - Table showing OHLCV data
  - Filters (symbol, timeframe, date range)
  - Optional: Chart visualization (Chart.js or similar)
- [ ] Add "View Data" button in connection list

**Acceptance Criteria**:
- Can view market data for each connection
- Filters work (symbol, timeframe)
- Data displayed in table format
- Optional: Chart renders (candlestick chart)

---

### Task 4.4: Connection Logs Viewer
**Priority**: P2 (Medium)  
**Effort**: 0.5 day  
**Dependencies**: Task 2.3

**Subtasks**:
- [ ] Create route: `GET /admin/data-connections/{id}/logs`
- [ ] Create controller method: `showLogs(DataConnection $connection)`
  - Fetch logs for connection (latest 100, paginated)
- [ ] Create view: `data-connections/logs.blade.php`
  - Table: timestamp, action, status, message
- [ ] Add "View Logs" button in connection list

**Acceptance Criteria**:
- Logs displayed correctly
- Pagination works
- Shows recent actions (connect, fetch_data, error)

---

## Phase 5: Pipeline Foundation (Week 5-6)

### Task 5.1: Pipeline Events Design
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 3.1

**Subtasks**:
- [ ] Create events in `Addons\TradingDataFeedingAddon\App\Events`:
  - `DataReceived` event (properties: dataConnectionId, symbol, timeframe, candles)
  - `DataCleaned` event (properties: cleanedData)
  - `DataFiltered` event (properties: filteredData)
  - `SignalGenerated` event (properties: signal, confidenceScore)
  - `PipelineStageCompleted` event (properties: stage, data, executionTimeMs)
- [ ] Dispatch `DataReceived` event in FetchMarketDataJob after storing data
- [ ] Document event payloads

**Acceptance Criteria**:
- Events defined with correct properties
- DataReceived event dispatched when data fetched
- Other addons can listen to events (test with dummy listener)

---

### Task 5.2: Pipeline Stage Interfaces
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 5.1

**Subtasks**:
- [ ] Create interfaces in `Addons\TradingDataFeedingAddon\App\Contracts`:
  - `DataCleanerInterface` (method: clean(array $rawData): array)
  - `DataFilterInterface` (method: filter(array $data): array)
  - `IndicatorCalculatorInterface` (method: calculate(array $data): array)
  - `SignalGeneratorInterface` (method: generate(array $data): ?Signal)
  - `ConfidenceEvaluatorInterface` (method: evaluate(Signal $signal, array $data): int)
- [ ] Document each interface (purpose, input/output format)
- [ ] Create example implementations (dummy/sample):
  - `SimpleDataCleaner` (removes outliers)
  - `TimeframeFilter` (filters by timeframe)

**Acceptance Criteria**:
- Interfaces defined with clear contracts
- Example implementations work
- Other addons can implement these interfaces

---

### Task 5.3: Pipeline Service
**Priority**: P1 (High)  
**Effort**: 2 days  
**Dependencies**: Task 5.2

**Subtasks**:
- [ ] Create `PipelineService` in `Addons\TradingDataFeedingAddon\App\Services`
  - Methods:
    - `registerCleaner(DataCleanerInterface $cleaner)` - Register data cleaner
    - `registerFilter(DataFilterInterface $filter)` - Register filter
    - `registerIndicatorCalculator(IndicatorCalculatorInterface $calculator)` - Register indicator
    - `registerSignalGenerator(SignalGeneratorInterface $generator)` - Register signal generator
    - `registerConfidenceEvaluator(ConfidenceEvaluatorInterface $evaluator)` - Register confidence evaluator
    - `execute(DataConnection $connection, array $marketData): void` - Execute full pipeline
  - Flow:
    1. Clean data (call registered cleaners)
    2. Filter data (call registered filters)
    3. Calculate indicators (call registered calculators)
    4. Generate signals (call registered generators)
    5. Evaluate confidence (call registered evaluators)
    6. Dispatch events at each stage
    7. Log pipeline execution to `pipeline_executions` table
- [ ] Singleton service (register in service provider)
- [ ] Create `ProcessPipelineJob` that calls PipelineService::execute()
- [ ] Dispatch ProcessPipelineJob when DataReceived event fired

**Acceptance Criteria**:
- Pipeline executes all stages in order
- Events dispatched at each stage
- Pipeline executions logged
- Other addons can register their implementations

---

### Task 5.4: Pipeline Integration with Other Addons
**Priority**: P1 (High)  
**Effort**: 2 days  
**Dependencies**: Task 5.3

**Subtasks**:
- [ ] **Filter Strategy Addon Integration**:
  - Create listener in filter-strategy-addon: `DataReceivedListener`
  - Implement `DataFilterInterface` in FilterStrategyService
  - Register filter with PipelineService
  - Test filtering (apply user-defined filters)
- [ ] **AI Trading Addon Integration**:
  - Create listener: `DataFilteredListener`
  - Implement `IndicatorCalculatorInterface` and `SignalGeneratorInterface`
  - Register with PipelineService
  - Test AI signal generation with confidence score
- [ ] **Trading Execution Engine Integration**:
  - Create listener: `SignalGeneratedListener`
  - Check confidence threshold (e.g., >= 70%)
  - Dispatch ExecuteSignalJob if threshold met
  - Test end-to-end flow (data → signal → execution)
- [ ] Document integration points for addon developers

**Acceptance Criteria**:
- Filter addon receives data and filters it
- AI addon generates signals with confidence scores
- Execution engine executes high-confidence signals
- Full pipeline tested (data feeding → execution)

---

## Phase 6: Testing & Optimization (Week 7)

### Task 6.1: Unit Tests
**Priority**: P0 (Critical)  
**Effort**: 2 days  
**Dependencies**: All previous tasks

**Subtasks**:
- [ ] Create tests in `tests/Unit/`:
  - `DataConnectionModelTest` - Model methods, relationships
  - `MarketDataModelTest` - Model methods, scopes
  - `MtapiAdapterTest` - Mock API responses, test adapter methods
  - `DataConnectionServiceTest` - Test CRUD operations
  - `MarketDataServiceTest` - Test data storage, retrieval
  - `PipelineServiceTest` - Test stage registration, execution
- [ ] Use PHPUnit, mock external APIs (Guzzle mocks)
- [ ] Aim for >80% code coverage
- [ ] Run tests: `php artisan test`

**Acceptance Criteria**:
- All unit tests pass
- Code coverage >80%
- No failed assertions

---

### Task 6.2: Feature Tests
**Priority**: P0 (Critical)  
**Effort**: 1.5 days  
**Dependencies**: Task 6.1

**Subtasks**:
- [ ] Create tests in `tests/Feature/`:
  - `DataConnectionCRUDTest` - Test admin/user CRUD operations
  - `ConnectionTestTest` - Test connection testing
  - `FetchMarketDataJobTest` - Test job execution, data storage
  - `BackfillHistoricalDataJobTest` - Test backfill job
  - `PipelineIntegrationTest` - Test full pipeline flow
- [ ] Use database transactions (RefreshDatabase trait)
- [ ] Test error scenarios (API failures, invalid credentials)
- [ ] Run tests: `php artisan test --filter=Feature`

**Acceptance Criteria**:
- All feature tests pass
- Error scenarios handled correctly
- Database state clean after tests

---

### Task 6.3: Performance Optimization
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 6.2

**Subtasks**:
- [ ] Optimize market data queries (add indexes if missing)
- [ ] Batch insert optimization (use DB::transaction, bulk insert)
- [ ] Cache frequently accessed data (connection configs, symbols list)
- [ ] Profile slow queries (Laravel Telescope or Debugbar)
- [ ] Optimize job dispatch (avoid dispatching thousands of jobs at once)
- [ ] Test performance with large datasets (1M+ rows)

**Acceptance Criteria**:
- Batch insert handles 10,000+ rows in <5 seconds
- Queries return in <100ms (for typical datasets)
- No N+1 query issues (use eager loading)
- Job queue doesn't grow unbounded

---

### Task 6.4: Error Handling & Monitoring
**Priority**: P1 (High)  
**Effort**: 1 day  
**Dependencies**: Task 6.3

**Subtasks**:
- [ ] Add try-catch blocks in all critical methods
- [ ] Log errors to Laravel log (Log::error())
- [ ] Create notification for admin when connection fails (database notification)
- [ ] Add retry logic for transient errors (network timeouts)
- [ ] Create health check endpoint: `GET /api/data-connections/health`
  - Returns status of all connections (active/error)
- [ ] Document error codes and troubleshooting steps

**Acceptance Criteria**:
- All errors logged with context (connection ID, error message)
- Admin notified of critical failures
- Health check endpoint works
- Retry logic prevents temporary failures from breaking system

---

## Phase 7: Documentation & Deployment (Week 8)

### Task 7.1: README & Documentation
**Priority**: P0 (Critical)  
**Effort**: 1 day  
**Dependencies**: All previous tasks

**Subtasks**:
- [ ] Create `README.md` in addon root:
  - Overview, features, installation, configuration
  - mtapi.io setup guide (get API key, configure connection)
  - Usage examples (create connection, fetch data)
  - Integration guide for other addons
- [ ] Create `INTEGRATION_GUIDE.md`:
  - How to implement pipeline interfaces
  - Event payloads
  - Example implementations
- [ ] Create `API_DOCUMENTATION.md`:
  - Document all API endpoints (if any)
- [ ] Update main project's README with addon info

**Acceptance Criteria**:
- README complete with setup instructions
- Integration guide clear for addon developers
- Examples provided

---

### Task 7.2: Configuration & Environment Setup
**Priority**: P0 (Critical)  
**Effort**: 0.5 day  
**Dependencies**: Task 7.1

**Subtasks**:
- [ ] Create config file: `config/data-feeding.php`
  - Default settings (retention days, fetch intervals)
  - API timeout, retry settings
- [ ] Add environment variables to `.env.example`:
  - MTAPI_API_KEY, MTAPI_ACCOUNT_ID
  - DATA_RETENTION_DAYS, FETCH_INTERVAL
- [ ] Document all config options in README

**Acceptance Criteria**:
- Config file created with sensible defaults
- Environment variables documented
- Config can be published: `php artisan vendor:publish --tag=data-feeding-config`

---

### Task 7.3: Admin Permissions Setup
**Priority**: P0 (Critical)  
**Effort**: 0.5 day  
**Dependencies**: Task 7.2

**Subtasks**:
- [ ] Create migration for permission: `manage-data-connections`
- [ ] Seed permission in seeder
- [ ] Grant to super admin role
- [ ] Apply permission middleware to admin routes
- [ ] Test permission enforcement (staff admin without permission denied)

**Acceptance Criteria**:
- Permission created and seeded
- Super admin can access all routes
- Staff admin denied without permission

---

### Task 7.4: Deployment & Testing on Production
**Priority**: P0 (Critical)  
**Effort**: 1 day  
**Dependencies**: Task 7.3

**Subtasks**:
- [ ] Upload addon to production server
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed permissions: `php artisan db:seed --class=DataFeedingPermissionSeeder`
- [ ] Create test connection (mtapi.io)
- [ ] Run FetchMarketDataJob manually, verify data stored
- [ ] Enable scheduler (cron job)
- [ ] Monitor logs for 24 hours (check for errors)
- [ ] Test full pipeline (data → signal → execution)

**Acceptance Criteria**:
- Addon deployed without errors
- Data fetching works in production
- Scheduler runs jobs on time
- No critical errors in logs

---

### Task 7.5: Post-Deployment Monitoring
**Priority**: P1 (High)  
**Effort**: Ongoing (1 week)  
**Dependencies**: Task 7.4

**Subtasks**:
- [ ] Monitor queue health (queue:work running, no stuck jobs)
- [ ] Monitor database size (market_data table growth)
- [ ] Monitor API rate limits (mtapi.io)
- [ ] Check connection health (all active connections working)
- [ ] Review logs daily (search for errors)
- [ ] Gather user feedback (admin/user experience)
- [ ] Create support documentation based on feedback

**Acceptance Criteria**:
- No critical issues reported
- Queue processing smoothly
- Database size within expectations
- User feedback positive

---

## Optional Enhancements (Future Phases)

### Task 8.1: Websocket Streaming Support
**Priority**: P2 (Medium)  
**Effort**: 2 days  

**Subtasks**:
- [ ] Research websocket support for mtapi.io (if available)
- [ ] Create websocket listener service
- [ ] Switch from polling to websockets for real-time data
- [ ] Test latency improvement

---

### Task 8.2: Crypto Exchange Support (CCXT)
**Priority**: P2 (Medium)  
**Effort**: 3 days  

**Subtasks**:
- [ ] Create `CcxtAdapter` implementing `DataProviderInterface`
- [ ] Support major exchanges (Binance, Coinbase, Kraken)
- [ ] Test data fetching from crypto exchanges
- [ ] Add exchange selection in admin UI

---

### Task 8.3: Advanced Pipeline Features
**Priority**: P3 (Low)  
**Effort**: 3 days  

**Subtasks**:
- [ ] Pipeline visualization UI (show data flow)
- [ ] Pipeline performance metrics (avg execution time per stage)
- [ ] Pipeline replay (re-run pipeline on historical data)
- [ ] A/B testing (test different pipeline configurations)

---

## Summary

**Total Estimated Effort**: ~25 days (5 weeks)  
**Team Size**: 1-2 developers  
**Critical Path**: Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 6 → Phase 7  

**Key Milestones**:
1. **Week 1**: Addon scaffold, database, models ✅
2. **Week 2**: mtapi.io integration working ✅
3. **Week 3**: Data fetching automated ✅
4. **Week 4**: Admin/User UI complete ✅
5. **Week 5-6**: Pipeline foundation ✅
6. **Week 7**: Testing & optimization ✅
7. **Week 8**: Documentation & deployment ✅

**Success Criteria**:
- mtapi.io connection working
- Real-time data streaming
- Pipeline architecture functional
- At least 2 addons integrated (execution + AI)
- Admin/User UI complete
- Tests passing (>80% coverage)
- Production deployment successful

