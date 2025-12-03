# AI Smart Risk Management (SRM) - Technical Architecture Plan

## Overview
Technical architecture and implementation plan for AI Smart Risk Management (SRM) Learning Engine. This plan covers database design, service architecture, ML integration, API design, and web UI implementation.

## Architecture Principles

1. **Modular Design**: SRM sebagai addon terpisah yang dapat diintegrasikan dengan Execution Engine dan Copy Trading
2. **Data-Driven**: Semua keputusan berdasarkan data historis dan ML predictions
3. **Real-Time Adaptation**: Penyesuaian risiko secara real-time berdasarkan performance score dan slippage prediction
4. **Learning Loop**: Automated retraining dan continuous improvement
5. **Transparency**: User dapat melihat alasan penyesuaian lot/SL (builds trust)

## Technology Stack

### Core Technologies
- **Framework**: Laravel 9.x (existing)
- **Database**: MySQL dengan JSON fields untuk model parameters
- **Queue**: Laravel Queue (database driver) untuk async ML training
- **Scheduler**: Laravel Scheduler untuk retraining jobs
- **Cache**: Laravel Cache untuk model predictions caching

### ML Framework Options

#### Option 1: PHP-ML (Recommended for MVP)
- **Pros**: Native PHP, no external dependencies, easy integration
- **Cons**: Limited ML algorithms, slower for complex models
- **Use Case**: Simple regression, weighted scoring (Phase 2)

#### Option 2: Python Microservice (Recommended for Production)
- **Pros**: Rich ML libraries (scikit-learn, pandas), better performance
- **Cons**: Requires separate service, API communication overhead
- **Use Case**: Complex models, time series analysis (Phase 3+)

#### Option 3: Hybrid Approach (Recommended)
- **Phase 2**: PHP-ML untuk simple models (Performance Score, basic regression)
- **Phase 3+**: Python microservice untuk complex models (Slippage Prediction, advanced optimization)
- **Migration Path**: Start with PHP-ML, migrate to Python when needed

**Decision**: Start with PHP-ML for MVP, plan migration to Python microservice for Phase 3.

## Database Architecture

### New Tables

#### 1. `srm_signal_provider_metrics`
```sql
CREATE TABLE srm_signal_provider_metrics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    signal_provider_id VARCHAR(255) NOT NULL COMMENT 'channel_source_id or user_id',
    signal_provider_type ENUM('channel_source', 'user') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    
    -- Metrics
    total_signals INT UNSIGNED DEFAULT 0,
    winning_signals INT UNSIGNED DEFAULT 0,
    losing_signals INT UNSIGNED DEFAULT 0,
    win_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentage',
    avg_slippage DECIMAL(8,4) DEFAULT 0.0000 COMMENT 'In pips',
    max_slippage DECIMAL(8,4) DEFAULT 0.0000,
    avg_latency_ms INT UNSIGNED DEFAULT 0,
    max_drawdown DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentage',
    reward_to_risk_ratio DECIMAL(8,4) DEFAULT 0.0000,
    sl_compliance_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentage - how often SP respects SL',
    
    -- Performance Score
    performance_score DECIMAL(5,2) DEFAULT 50.00 COMMENT '0-100 dynamic score',
    performance_score_previous DECIMAL(5,2) DEFAULT 50.00,
    score_trend ENUM('up', 'down', 'stable') DEFAULT 'stable',
    
    -- Metadata
    calculated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_signal_provider (signal_provider_id, signal_provider_type),
    INDEX idx_period (period_start, period_end, period_type),
    INDEX idx_performance_score (performance_score),
    UNIQUE KEY uk_provider_period (signal_provider_id, signal_provider_type, period_start, period_end, period_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. `srm_predictions`
```sql
CREATE TABLE srm_predictions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    execution_log_id BIGINT UNSIGNED NULL COMMENT 'FK to execution_logs',
    signal_id BIGINT UNSIGNED NULL COMMENT 'FK to signals',
    connection_id BIGINT UNSIGNED NULL COMMENT 'FK to execution_connections',
    
    -- Prediction Type
    prediction_type ENUM('slippage', 'performance_score', 'lot_optimization') NOT NULL,
    
    -- Input Features
    symbol VARCHAR(50) NULL,
    trading_session ENUM('TOKYO', 'LONDON', 'NEW_YORK', 'ASIAN', 'OVERLAP') NULL,
    day_of_week TINYINT NULL COMMENT '1-7',
    market_atr DECIMAL(10,4) NULL,
    volatility_index DECIMAL(8,4) NULL,
    signal_provider_id VARCHAR(255) NULL,
    
    -- Prediction Output
    predicted_value DECIMAL(10,4) NOT NULL COMMENT 'Predicted slippage (pips) or performance score or lot size',
    confidence_score DECIMAL(5,2) DEFAULT 0.00 COMMENT '0-100',
    
    -- Actual Result (for accuracy tracking)
    actual_value DECIMAL(10,4) NULL COMMENT 'Actual slippage or performance after execution',
    accuracy DECIMAL(5,2) NULL COMMENT 'Percentage accuracy if actual_value available',
    
    -- Model Info
    model_version VARCHAR(50) NULL COMMENT 'Version of ML model used',
    model_type VARCHAR(50) NULL COMMENT 'regression, weighted_scoring, etc.',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_execution_log (execution_log_id),
    INDEX idx_signal (signal_id),
    INDEX idx_connection (connection_id),
    INDEX idx_prediction_type (prediction_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (execution_log_id) REFERENCES execution_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (signal_id) REFERENCES signals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. `srm_model_versions`
```sql
CREATE TABLE srm_model_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    model_type ENUM('slippage_prediction', 'performance_score', 'risk_optimization') NOT NULL,
    version VARCHAR(50) NOT NULL,
    status ENUM('training', 'active', 'deprecated', 'testing') DEFAULT 'training',
    
    -- Model Parameters (JSON)
    parameters JSON NULL COMMENT 'Model hyperparameters, weights, etc.',
    training_data_count INT UNSIGNED DEFAULT 0,
    training_date_start TIMESTAMP NULL,
    training_date_end TIMESTAMP NULL,
    
    -- Performance Metrics
    accuracy DECIMAL(5,2) NULL COMMENT 'Overall accuracy percentage',
    mse DECIMAL(10,6) NULL COMMENT 'Mean Squared Error (for regression)',
    r2_score DECIMAL(5,4) NULL COMMENT 'R² score (for regression)',
    
    -- Validation Metrics
    validation_accuracy DECIMAL(5,2) NULL,
    validation_mse DECIMAL(10,6) NULL,
    
    -- Deployment
    deployed_at TIMESTAMP NULL,
    deprecated_at TIMESTAMP NULL,
    
    -- Metadata
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_model_type (model_type, status),
    INDEX idx_version (version),
    UNIQUE KEY uk_model_version (model_type, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. `srm_ab_tests`
```sql
CREATE TABLE srm_ab_tests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('draft', 'running', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    
    -- Test Configuration
    pilot_group_percentage DECIMAL(5,2) DEFAULT 10.00 COMMENT 'Percentage of users in pilot group',
    test_duration_days INT UNSIGNED DEFAULT 14,
    
    -- SRM Logic Variants
    control_logic JSON NULL COMMENT 'Control group SRM logic (current production)',
    pilot_logic JSON NULL COMMENT 'Pilot group SRM logic (new logic to test)',
    
    -- Results
    start_date DATE NULL,
    end_date DATE NULL,
    pilot_group_size INT UNSIGNED DEFAULT 0,
    control_group_size INT UNSIGNED DEFAULT 0,
    
    -- Performance Comparison
    pilot_avg_pnl DECIMAL(10,2) NULL,
    control_avg_pnl DECIMAL(10,2) NULL,
    pilot_avg_drawdown DECIMAL(5,2) NULL,
    control_avg_drawdown DECIMAL(5,2) NULL,
    pilot_win_rate DECIMAL(5,2) NULL,
    control_win_rate DECIMAL(5,2) NULL,
    
    -- Statistical Significance
    p_value DECIMAL(8,6) NULL COMMENT 'Statistical significance test result',
    is_significant BOOLEAN DEFAULT FALSE,
    
    -- Decision
    decision ENUM('deploy', 'reject', 'extend') NULL,
    decision_notes TEXT NULL,
    
    -- Metadata
    created_by_admin_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. `srm_ab_test_assignments`
```sql
CREATE TABLE srm_ab_test_assignments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ab_test_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'FK to users',
    connection_id BIGINT UNSIGNED NULL COMMENT 'FK to execution_connections',
    group_type ENUM('pilot', 'control') NOT NULL,
    
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ab_test (ab_test_id, group_type),
    INDEX idx_user (user_id),
    INDEX idx_connection (connection_id),
    FOREIGN KEY (ab_test_id) REFERENCES srm_ab_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (connection_id) REFERENCES execution_connections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Modified Tables

#### 1. `execution_logs` (Add SRM fields)
```sql
ALTER TABLE execution_logs ADD COLUMN slippage DECIMAL(8,4) NULL COMMENT 'Actual slippage in pips' AFTER entry_price;
ALTER TABLE execution_logs ADD COLUMN latency_ms INT UNSIGNED NULL COMMENT 'Time from signal received to execution' AFTER slippage;
ALTER TABLE execution_logs ADD COLUMN market_atr DECIMAL(10,4) NULL COMMENT 'ATR value at execution time' AFTER latency_ms;
ALTER TABLE execution_logs ADD COLUMN trading_session ENUM('TOKYO', 'LONDON', 'NEW_YORK', 'ASIAN', 'OVERLAP') NULL AFTER market_atr;
ALTER TABLE execution_logs ADD COLUMN day_of_week TINYINT NULL COMMENT '1-7' AFTER trading_session;
ALTER TABLE execution_logs ADD COLUMN volatility_index DECIMAL(8,4) NULL COMMENT 'Calculated volatility metric' AFTER day_of_week;
ALTER TABLE execution_logs ADD COLUMN signal_provider_id VARCHAR(255) NULL COMMENT 'channel_source_id or user_id' AFTER volatility_index;
ALTER TABLE execution_logs ADD COLUMN signal_provider_type ENUM('channel_source', 'user') NULL AFTER signal_provider_id;

-- Indexes
ALTER TABLE execution_logs ADD INDEX idx_slippage (slippage);
ALTER TABLE execution_logs ADD INDEX idx_trading_session (trading_session);
ALTER TABLE execution_logs ADD INDEX idx_signal_provider (signal_provider_id, signal_provider_type);
```

#### 2. `execution_positions` (Add SRM fields)
```sql
ALTER TABLE execution_positions ADD COLUMN predicted_slippage DECIMAL(8,4) NULL COMMENT 'Predicted slippage at entry' AFTER entry_price;
ALTER TABLE execution_positions ADD COLUMN performance_score_at_entry DECIMAL(5,2) NULL COMMENT 'SP performance score at entry' AFTER predicted_slippage;
ALTER TABLE execution_positions ADD COLUMN srm_adjusted_lot DECIMAL(10,4) NULL COMMENT 'SRM-adjusted lot size' AFTER quantity;
ALTER TABLE execution_positions ADD COLUMN srm_sl_buffer DECIMAL(8,4) NULL COMMENT 'SRM-added SL buffer in pips' AFTER sl_price;
ALTER TABLE execution_positions ADD COLUMN srm_adjustment_reason TEXT NULL COMMENT 'Reason for SRM adjustment (JSON)' AFTER srm_sl_buffer;
```

## Service Architecture

### Core Services

#### 1. `MarketContextService`
**Location**: `Addons\SmartRiskManagement\App\Services\MarketContextService`

**Responsibilities**:
- Fetch ATR from exchange/broker adapter
- Determine trading session based on timestamp
- Calculate volatility metrics
- Get market conditions (news events, high volatility periods)

**Methods**:
```php
public function getATR(string $symbol, ?Carbon $timestamp = null): float
public function getTradingSession(?Carbon $timestamp = null): string
public function getDayOfWeek(?Carbon $timestamp = null): int
public function calculateVolatilityIndex(string $symbol, ?Carbon $timestamp = null): float
public function getMarketContext(string $symbol, ?Carbon $timestamp = null): array
```

#### 2. `SlippageCalculationService`
**Location**: `Addons\SmartRiskManagement\App\Services\SlippageCalculationService`

**Responsibilities**:
- Calculate actual slippage from execution log
- Store slippage data
- Normalize slippage to pips

**Methods**:
```php
public function calculateSlippage(ExecutionLog $log, float $signalEntryPrice, float $executedPrice): float
public function storeSlippage(ExecutionLog $log, float $slippage): void
```

#### 3. `SlippagePredictionService`
**Location**: `Addons\SmartRiskManagement\App\Services\SlippagePredictionService`

**Responsibilities**:
- Predict slippage using ML model
- Cache predictions
- Update model accuracy

**Methods**:
```php
public function predictSlippage(string $symbol, string $tradingSession, float $atr, ?string $signalProviderId = null): float
public function getPredictionWithConfidence(string $symbol, array $context): array
public function updateModelAccuracy(): void
```

#### 4. `PerformanceScoreService`
**Location**: `Addons\SmartRiskManagement\App\Services\PerformanceScoreService`

**Responsibilities**:
- Calculate performance score for Signal Provider
- Update score in real-time
- Track score trends

**Methods**:
```php
public function calculatePerformanceScore(string $signalProviderId, string $type, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): float
public function updatePerformanceScore(string $signalProviderId, string $type): void
public function getPerformanceScore(string $signalProviderId, string $type): float
public function getScoreTrend(string $signalProviderId, string $type): string
```

#### 5. `RiskOptimizationService`
**Location**: `Addons\SmartRiskManagement\App\Services\RiskOptimizationService`

**Responsibilities**:
- Calculate optimal lot size based on SRM logic
- Apply performance score multiplier
- Apply slippage buffer factor
- Enforce constraints (min/max lot, max position size)

**Methods**:
```php
public function calculateOptimalLot(
    float $equity,
    float $riskTolerance,
    float $slDistance,
    float $predictedSlippage,
    float $performanceScore,
    array $constraints = []
): float

public function getAdjustmentReason(
    float $baseLot,
    float $adjustedLot,
    float $performanceScore,
    float $predictedSlippage
): array
```

#### 6. `SlBufferingService`
**Location**: `Addons\SmartRiskManagement\App\Services\SlBufferingService`

**Responsibilities**:
- Calculate SL buffer based on predicted slippage
- Apply buffer to SL price
- Return adjustment reason

**Methods**:
```php
public function calculateSlBuffer(float $predictedSlippage, string $symbol, string $tradingSession): float
public function applySlBuffer(float $slPrice, float $buffer, string $direction): float
```

#### 7. `SignalQualityFilterService`
**Location**: `Addons\SmartRiskManagement\App\Services\SignalQualityFilterService`

**Responsibilities**:
- Filter signals based on performance score
- Filter signals based on predicted slippage
- Filter expired signals (slippage > threshold)

**Methods**:
```php
public function shouldRejectSignal(Signal $signal, string $signalProviderId, float $currentPrice): array
public function isSignalExpired(Signal $signal, float $currentPrice, float $maxSlippage = 5.0): bool
```

#### 8. `MaxDrawdownControlService`
**Location**: `Addons\SmartRiskManagement\App\Services\MaxDrawdownControlService`

**Responsibilities**:
- Monitor floating loss per connection
- Trigger emergency stop if threshold exceeded
- Close all positions on emergency stop

**Methods**:
```php
public function checkDrawdown(ExecutionConnection $connection): array
public function triggerEmergencyStop(ExecutionConnection $connection, string $reason): void
public function closeAllPositions(ExecutionConnection $connection): void
```

#### 9. `ModelTrainingService`
**Location**: `Addons\SmartRiskManagement\App\Services\ModelTrainingService`

**Responsibilities**:
- Train ML models (slippage prediction, performance score, risk optimization)
- Validate model accuracy
- Deploy new models
- Track model versions

**Methods**:
```php
public function trainSlippagePredictionModel(array $trainingData): array
public function trainPerformanceScoreModel(array $trainingData): array
public function validateModel(string $modelType, string $version): array
public function deployModel(string $modelType, string $version): bool
```

#### 10. `AbTestingService`
**Location**: `Addons\SmartRiskManagement\App\Services\AbTestingService`

**Responsibilities**:
- Create A/B tests
- Assign users to pilot/control groups
- Compare performance
- Generate statistical analysis

**Methods**:
```php
public function createTest(string $name, array $pilotLogic, float $pilotPercentage = 10.0): AbTest
public function assignUserToGroup(int $userId, int $abTestId): string
public function compareResults(int $abTestId): array
public function calculateStatisticalSignificance(int $abTestId): float
```

## ML Model Architecture

### Model 1: Slippage Prediction

**Type**: Regression (Linear Regression or Time Series)

**Features**:
- Symbol (encoded)
- Trading Session (one-hot encoded)
- ATR (normalized)
- Day of Week (encoded)
- Signal Provider ID (if available)
- Historical Average Slippage for Symbol

**Target**: Slippage in pips

**Training Data**: Historical ExecutionLog dengan actual slippage

**Prediction Flow**:
1. Extract features from signal context
2. Load trained model
3. Predict slippage
4. Cache prediction
5. Store in `srm_predictions` table

### Model 2: Performance Score

**Type**: Weighted Scoring (Phase 2) → Bayesian Ranking (Phase 3+)

**Features**:
- Win Rate (0-100%)
- Max Drawdown (0-100%)
- Reward-to-Risk Ratio
- SL Compliance Rate (0-100%)
- Recent Performance Trend (last 7 days)

**Formula (Phase 2 - Simple)**:
```php
$score = (
    $winRate * 0.35 +
    (100 - $maxDrawdown) * 0.25 +
    min($rewardToRisk * 20, 100) * 0.20 +
    $slComplianceRate * 0.15 +
    $recentTrendScore * 0.05
);
```

**Update Frequency**: Real-time setelah setiap trade closed

### Model 3: Risk Optimization

**Type**: Formula-based (Phase 2) → Optimization Algorithm (Phase 3+)

**Formula (Phase 2)**:
```php
$baseLot = ($equity * $riskTolerance) / $slDistance;

// Performance Score Multiplier (0.5x to 1.5x)
$scoreMultiplier = 0.5 + ($performanceScore / 100) * 1.0;

// Slippage Buffer Factor (reduce lot if high slippage)
$slippageFactor = 1.0 - min($predictedSlippage / 10, 0.3); // Max 30% reduction

$adjustedLot = $baseLot * $scoreMultiplier * $slippageFactor;

// Apply constraints
$adjustedLot = max($minLot, min($adjustedLot, $maxLot));
```

## Integration Architecture

### With Trading Execution Engine Addon

**Modification Points**:

1. **SignalExecutionService::executeSignal()**
   - Before execution: Get market context, predict slippage, check signal quality
   - During execution: Calculate actual slippage
   - After execution: Store slippage, update performance score

2. **ExecuteSignalJob::handle()**
   - Collect market context before execution
   - Apply SRM logic (SL buffering, lot adjustment)
   - Store predictions

3. **ExecutionLog Creation**
   - Add market context fields
   - Calculate and store slippage
   - Link to signal provider

### With Copy Trading Addon

**Modification Points**:

1. **TradeCopyService::copyTrade()**
   - Get performance score for trader
   - Apply dynamic lot adjustment
   - Apply SL buffering
   - Filter if performance score too low

2. **CopyTradingExecution Model**
   - Store SRM adjustment details
   - Link to performance score

### With Trading Preset Addon

**Integration**:
- SRM can override preset settings if needed
- SRM respects preset constraints (min/max lot)
- SRM works alongside preset-based position sizing

## Web UI Architecture

### Admin Panel Routes

**File**: `addons/smart-risk-management-addon/routes/admin.php`

```php
Route::prefix('srm')->name('srm.')->middleware(['permission:signal,admin'])->group(function () {
    // Signal Provider Metrics
    Route::get('signal-providers', [SignalProviderMetricsController::class, 'index'])->name('signal-providers.index');
    Route::get('signal-providers/{id}', [SignalProviderMetricsController::class, 'show'])->name('signal-providers.show');
    
    // Predictions
    Route::get('predictions', [PredictionController::class, 'index'])->name('predictions.index');
    Route::get('predictions/{id}', [PredictionController::class, 'show'])->name('predictions.show');
    
    // Model Management
    Route::get('models', [ModelController::class, 'index'])->name('models.index');
    Route::get('models/{id}', [ModelController::class, 'show'])->name('models.show');
    Route::post('models/{id}/retrain', [ModelController::class, 'retrain'])->name('models.retrain');
    Route::post('models/{id}/deploy', [ModelController::class, 'deploy'])->name('models.deploy');
    
    // A/B Testing
    Route::resource('ab-tests', AbTestController::class);
    Route::post('ab-tests/{id}/start', [AbTestController::class, 'start'])->name('ab-tests.start');
    Route::post('ab-tests/{id}/stop', [AbTestController::class, 'stop'])->name('ab-tests.stop');
    Route::get('ab-tests/{id}/results', [AbTestController::class, 'results'])->name('ab-tests.results');
    
    // Settings
    Route::get('settings', [SrmSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SrmSettingsController::class, 'update'])->name('settings.update');
});
```

### User Panel Routes

**File**: `addons/smart-risk-management-addon/routes/user.php`

```php
Route::prefix('srm')->name('srm.')->group(function () {
    // My SRM Dashboard
    Route::get('/', [SrmDashboardController::class, 'index'])->name('dashboard');
    
    // SRM Adjustments History
    Route::get('adjustments', [SrmAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('adjustments/{id}', [SrmAdjustmentController::class, 'show'])->name('adjustments.show');
    
    // Performance Insights
    Route::get('insights', [SrmInsightController::class, 'index'])->name('insights.index');
});
```

### Controllers

#### Admin Controllers

1. **SignalProviderMetricsController**
   - `index()`: List all signal providers with metrics
   - `show($id)`: Show detailed metrics for one provider

2. **PredictionController**
   - `index()`: List predictions with filters
   - `show($id)`: Show prediction details and accuracy

3. **ModelController**
   - `index()`: List all model versions
   - `show($id)`: Show model details and performance
   - `retrain($id)`: Trigger manual retraining
   - `deploy($id)`: Deploy model to production

4. **AbTestController**
   - `index()`: List A/B tests
   - `create()`: Create new A/B test
   - `store()`: Save A/B test
   - `show($id)`: Show test details
   - `start($id)`: Start A/B test
   - `stop($id)`: Stop A/B test
   - `results($id)`: Show test results

5. **SrmSettingsController**
   - `index()`: Show SRM settings
   - `update()`: Update SRM settings

#### User Controllers

1. **SrmDashboardController**
   - `index()`: Show user's SRM dashboard with insights

2. **SrmAdjustmentController**
   - `index()`: List SRM adjustments for user's connections
   - `show($id)`: Show adjustment details and reason

3. **SrmInsightController**
   - `index()`: Show performance insights and recommendations

### Views Structure

#### Admin Views
```
resources/views/backend/
├── signal-providers/
│   ├── index.blade.php
│   └── show.blade.php
├── predictions/
│   ├── index.blade.php
│   └── show.blade.php
├── models/
│   ├── index.blade.php
│   └── show.blade.php
├── ab-tests/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── results.blade.php
└── settings/
    └── index.blade.php
```

#### User Views
```
resources/views/user/
├── dashboard.blade.php
├── adjustments/
│   ├── index.blade.php
│   └── show.blade.php
└── insights/
    └── index.blade.php
```

### View Components

#### Admin: Signal Provider Metrics Index
- Table dengan columns: Provider Name, Type, Total Signals, Win Rate, Avg Slippage, Performance Score, Trend
- Filters: Type, Date Range, Performance Score Range
- Search functionality
- Export to CSV

#### Admin: Predictions Index
- Table dengan columns: Type, Symbol, Predicted Value, Actual Value, Accuracy, Confidence, Created At
- Filters: Type, Date Range, Accuracy Range
- Charts: Prediction Accuracy Over Time

#### Admin: Model Management
- Cards untuk each model type (Slippage Prediction, Performance Score, Risk Optimization)
- Show: Current Version, Accuracy, Last Training Date, Status
- Actions: Retrain, Deploy, View History

#### Admin: A/B Testing
- List of tests dengan status, dates, results
- Create test form: Name, Description, Pilot Logic (JSON), Pilot Percentage
- Results view: Comparison charts, Statistical significance, Decision recommendation

#### User: SRM Dashboard
- Cards: Total Adjustments, Avg Performance Score, Slippage Reduction, Drawdown Reduction
- Chart: Performance Score Over Time
- Recent Adjustments table
- Insights section

#### User: Adjustments History
- Table: Date, Connection, Signal, Adjustment Type (Lot/SL), Reason, Impact
- Filter by connection, date range
- Show adjustment details: Before/After values, reason explanation

## Security Considerations

1. **Model Access Control**: Only admins can retrain/deploy models
2. **A/B Test Permissions**: Only super admins can create A/B tests
3. **Data Privacy**: User-specific metrics only visible to that user
4. **API Rate Limiting**: ML prediction endpoints should be rate-limited
5. **Input Validation**: All prediction inputs must be validated

## Performance Optimization

1. **Caching**: Cache predictions for 5 minutes (market conditions change quickly)
2. **Queue Jobs**: ML training should be queued (long-running)
3. **Database Indexing**: Index all foreign keys and frequently queried columns
4. **Eager Loading**: Use `with()` to prevent N+1 queries
5. **Pagination**: Paginate all list views

## Testing Strategy

1. **Unit Tests**: Test each service method independently
2. **Integration Tests**: Test SRM integration with Execution Engine
3. **ML Model Tests**: Validate model accuracy on test dataset
4. **A/B Test Validation**: Ensure proper group assignment and result calculation

## Deployment Strategy

1. **Phase 1**: Deploy data collection (no ML yet)
2. **Phase 2**: Deploy simple models (formula-based)
3. **Phase 3**: Deploy ML models (after sufficient training data)
4. **Phase 4**: Deploy A/B testing framework
5. **Rollback Plan**: Feature flag to disable SRM if issues occur

## Monitoring & Alerting

1. **Model Accuracy Monitoring**: Alert if accuracy drops below threshold
2. **Prediction Latency**: Monitor prediction response time
3. **Training Job Failures**: Alert on training job failures
4. **A/B Test Anomalies**: Alert on unexpected results

## Future Enhancements

1. **Real-time ML**: Stream processing for real-time predictions
2. **Advanced ML Models**: Deep learning for complex patterns
3. **Multi-asset Optimization**: Optimize across multiple symbols
4. **Sentiment Analysis**: Incorporate market sentiment into predictions
5. **Reinforcement Learning**: Self-improving models

