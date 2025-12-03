# AI Smart Risk Management (SRM) - Task Breakdown

## Overview
Detailed task breakdown for implementing AI Smart Risk Management (SRM) Learning Engine. Tasks are organized by phase and include estimates, dependencies, and acceptance criteria.

## Task Status Legend
- **TODO**: Not started
- **IN_PROGRESS**: Currently working
- **REVIEW**: Ready for review
- **DONE**: Completed

---

## Phase 1: Foundation (Data Acquisition & Normalization)

### Task 1.1: Create Addon Structure
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create addon directory structure and basic files for Smart Risk Management addon.

**Acceptance Criteria:**
- [ ] Create `addons/smart-risk-management-addon/` directory
- [ ] Create `addon.json` with proper metadata
- [ ] Create `AddonServiceProvider.php`
- [ ] Register addon in `AppServiceProvider`
- [ ] Add namespace to `composer.json` autoload
- [ ] Run `composer dump-autoload`

**Files to Create:**
- `addons/smart-risk-management-addon/addon.json`
- `addons/smart-risk-management-addon/AddonServiceProvider.php`
- `addons/smart-risk-management-addon/README.md`

**Dependencies:**
- None

**Technical Notes:**
- Follow existing addon structure pattern
- Namespace: `Addons\SmartRiskManagement`
- Addon slug: `smart-risk-management-addon`

---

### Task 1.2: Create Database Migrations - Signal Provider Metrics
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create migration for `srm_signal_provider_metrics` table.

**Acceptance Criteria:**
- [ ] Migration file created with timestamp
- [ ] Table structure matches plan.md schema
- [ ] All indexes created
- [ ] Foreign keys properly defined
- [ ] Migration runs successfully

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_srm_signal_provider_metrics_table.php`

**Dependencies:**
- Task 1.1

**Technical Notes:**
- Use `Schema::create()` for table creation
- Add proper comments to columns
- Use `$table->timestamps()` for created_at/updated_at

---

### Task 1.3: Create Database Migrations - Predictions Table
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create migration for `srm_predictions` table.

**Acceptance Criteria:**
- [ ] Migration file created
- [ ] Table structure matches plan.md schema
- [ ] Foreign keys to execution_logs and signals
- [ ] All indexes created
- [ ] Migration runs successfully

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_srm_predictions_table.php`

**Dependencies:**
- Task 1.1

---

### Task 1.4: Create Database Migrations - Model Versions Table
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** MEDIUM

**Description:**
Create migration for `srm_model_versions` table.

**Acceptance Criteria:**
- [ ] Migration file created
- [ ] Table structure matches plan.md schema
- [ ] JSON field for parameters
- [ ] Migration runs successfully

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_srm_model_versions_table.php`

**Dependencies:**
- Task 1.1

---

### Task 1.5: Create Database Migrations - A/B Testing Tables
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1.5 hours  
**Priority:** MEDIUM

**Description:**
Create migrations for `srm_ab_tests` and `srm_ab_test_assignments` tables.

**Acceptance Criteria:**
- [ ] Both migration files created
- [ ] Table structures match plan.md schema
- [ ] Foreign keys properly defined
- [ ] Migrations run successfully

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_srm_ab_tests_table.php`
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_create_srm_ab_test_assignments_table.php`

**Dependencies:**
- Task 1.1

---

### Task 1.6: Extend ExecutionLog Migration
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create migration to add SRM fields to `execution_logs` table.

**Acceptance Criteria:**
- [ ] Migration file created
- [ ] All fields added: slippage, latency_ms, market_atr, trading_session, day_of_week, volatility_index, signal_provider_id, signal_provider_type
- [ ] Indexes added
- [ ] Migration runs successfully (check if table exists first)

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_add_srm_fields_to_execution_logs_table.php`

**Dependencies:**
- Task 1.1
- Trading Execution Engine Addon must be active

**Technical Notes:**
- Use `Schema::hasColumn()` to check if columns exist
- Use `Schema::hasTable()` to check if table exists
- Handle case where Execution Engine addon might not be active

---

### Task 1.7: Extend ExecutionPosition Migration
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create migration to add SRM fields to `execution_positions` table.

**Acceptance Criteria:**
- [ ] Migration file created
- [ ] All fields added: predicted_slippage, performance_score_at_entry, srm_adjusted_lot, srm_sl_buffer, srm_adjustment_reason
- [ ] Migration runs successfully

**Files to Create:**
- `addons/smart-risk-management-addon/database/migrations/YYYY_MM_DD_HHMMSS_add_srm_fields_to_execution_positions_table.php`

**Dependencies:**
- Task 1.1
- Trading Execution Engine Addon must be active

---

### Task 1.8: Create Eloquent Models
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create Eloquent models for all SRM tables.

**Acceptance Criteria:**
- [ ] SignalProviderMetrics model created
- [ ] SrmPrediction model created
- [ ] SrmModelVersion model created
- [ ] AbTest model created
- [ ] AbTestAssignment model created
- [ ] All relationships defined
- [ ] All scopes defined (if needed)
- [ ] All casts defined

**Files to Create:**
- `addons/smart-risk-management-addon/app/Models/SignalProviderMetrics.php`
- `addons/smart-risk-management-addon/app/Models/SrmPrediction.php`
- `addons/smart-risk-management-addon/app/Models/SrmModelVersion.php`
- `addons/smart-risk-management-addon/app/Models/AbTest.php`
- `addons/smart-risk-management-addon/app/Models/AbTestAssignment.php`

**Dependencies:**
- Tasks 1.2, 1.3, 1.4, 1.5

**Technical Notes:**
- Follow existing model patterns
- Use `HasFactory` trait
- Define relationships to ExecutionLog, Signal, User, etc.

---

### Task 1.9: Create MarketContextService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create service to fetch market context data (ATR, trading session, volatility).

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `getATR()` method implemented
- [ ] `getTradingSession()` method implemented
- [ ] `getDayOfWeek()` method implemented
- [ ] `calculateVolatilityIndex()` method implemented
- [ ] `getMarketContext()` method implemented (combines all)
- [ ] Integration with Execution Engine adapter for ATR
- [ ] Trading session logic based on timezone/time

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/MarketContextService.php`

**Dependencies:**
- Task 1.1
- Trading Execution Engine Addon

**Technical Notes:**
- Trading sessions: Tokyo (00:00-09:00 UTC), London (08:00-17:00 UTC), New York (13:00-22:00 UTC), Asian (22:00-00:00 UTC)
- ATR: Fetch from exchange adapter if available, or calculate from price history
- Volatility Index: Can be simple standard deviation or ATR-based

---

### Task 1.10: Create SlippageCalculationService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create service to calculate and store slippage data.

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `calculateSlippage()` method implemented (converts to pips)
- [ ] `storeSlippage()` method implemented
- [ ] Handles different symbol types (forex, crypto, stocks)
- [ ] Pip calculation correct for different symbols

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/SlippageCalculationService.php`

**Dependencies:**
- Task 1.8
- Task 1.6

**Technical Notes:**
- Forex: 1 pip = 0.0001 for most pairs, 0.01 for JPY pairs
- Crypto: Use percentage or fixed decimal places
- Stocks: Use percentage or cents

---

### Task 1.11: Integrate Market Context into ExecutionLog Creation
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Modify Execution Engine to collect and store market context when creating ExecutionLog.

**Acceptance Criteria:**
- [ ] MarketContextService called before ExecutionLog creation
- [ ] Market context data stored in ExecutionLog
- [ ] Slippage calculated and stored after execution
- [ ] Signal provider ID linked to ExecutionLog
- [ ] No breaking changes to existing Execution Engine functionality

**Files to Modify:**
- `addons/trading-execution-engine-addon/app/Services/SignalExecutionService.php`
- `addons/trading-execution-engine-addon/app/Jobs/ExecuteSignalJob.php`

**Dependencies:**
- Tasks 1.9, 1.10
- Trading Execution Engine Addon

**Technical Notes:**
- Use service provider to inject SRM services
- Check if SRM addon is active before applying logic
- Store market context in ExecutionLog after execution

---

## Phase 2: Learning Engine Models

### Task 2.1: Create PerformanceScoreService (Simple Formula)
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create service to calculate performance score using weighted formula (Phase 2 - simple approach).

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `calculatePerformanceScore()` method implemented
- [ ] Formula: Win Rate (35%) + Max Drawdown (25%) + Reward-to-Risk (20%) + SL Compliance (15%) + Recent Trend (5%)
- [ ] `updatePerformanceScore()` method implemented (real-time update)
- [ ] `getPerformanceScore()` method implemented (with caching)
- [ ] `getScoreTrend()` method implemented
- [ ] Score stored in SignalProviderMetrics table

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/PerformanceScoreService.php`

**Dependencies:**
- Tasks 1.8, 1.11

**Technical Notes:**
- Start with simple weighted formula
- Cache scores for 5 minutes
- Update score after each trade closed
- Score range: 0-100

---

### Task 2.2: Create SlippagePredictionService (Simple Regression)
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 5 hours  
**Priority:** HIGH

**Description:**
Create service to predict slippage using simple regression (PHP-ML or formula-based).

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `predictSlippage()` method implemented
- [ ] Simple formula-based prediction (average historical slippage per symbol/session)
- [ ] `getPredictionWithConfidence()` method implemented
- [ ] Predictions cached for 5 minutes
- [ ] Predictions stored in srm_predictions table
- [ ] Integration with MarketContextService

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/SlippagePredictionService.php`

**Dependencies:**
- Tasks 1.9, 1.11

**Technical Notes:**
- Phase 2: Use simple average or weighted average
- Formula: `avg_slippage = AVG(historical_slippage WHERE symbol=X AND session=Y)`
- Phase 3: Upgrade to ML model

---

### Task 2.3: Create RiskOptimizationService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create service to calculate optimal lot size based on SRM logic.

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `calculateOptimalLot()` method implemented
- [ ] Formula: Base Lot * Performance Score Multiplier * Slippage Factor
- [ ] Constraints applied (min/max lot, max position size)
- [ ] `getAdjustmentReason()` method implemented
- [ ] Returns adjustment reason as array (for transparency)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/RiskOptimizationService.php`

**Dependencies:**
- Tasks 2.1, 2.2

**Technical Notes:**
- Base Lot = (Equity * Risk%) / SL_Distance
- Performance Score Multiplier: 0.5 + (score/100) * 1.0 (range 0.5x to 1.5x)
- Slippage Factor: 1.0 - min(predicted_slippage/10, 0.3) (max 30% reduction)

---

### Task 2.4: Create ModelTrainingService (Basic)
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create service to train and manage ML models (basic structure for Phase 2).

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `trainSlippagePredictionModel()` method structure (can be placeholder for Phase 2)
- [ ] `trainPerformanceScoreModel()` method structure
- [ ] `validateModel()` method structure
- [ ] `deployModel()` method structure
- [ ] Model version tracking

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/ModelTrainingService.php`

**Dependencies:**
- Task 1.8

**Technical Notes:**
- Phase 2: Basic structure, actual training in Phase 4
- Store model parameters in JSON field
- Track model versions in srm_model_versions table

---

## Phase 3: Adaptive Mechanisms

### Task 3.1: Create SlBufferingService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create service to calculate and apply SL buffer based on predicted slippage.

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `calculateSlBuffer()` method implemented
- [ ] Buffer calculation: 1-3 pips based on predicted slippage
- [ ] `applySlBuffer()` method implemented
- [ ] Buffer applied correctly for BUY/SELL directions
- [ ] Buffer reason stored

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/SlBufferingService.php`

**Dependencies:**
- Task 2.2

**Technical Notes:**
- Buffer = min(predicted_slippage * 1.5, 3.0) pips
- For BUY: SL = SL - buffer
- For SELL: SL = SL + buffer

---

### Task 3.2: Create SignalQualityFilterService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create service to filter signals based on quality (performance score, slippage, expiration).

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `shouldRejectSignal()` method implemented
- [ ] Reject if performance score < threshold (e.g., 40)
- [ ] Reject if predicted slippage > max allowed (e.g., 10 pips)
- [ ] `isSignalExpired()` method implemented (slippage > 5 pips from entry)
- [ ] Returns rejection reason

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/SignalQualityFilterService.php`

**Dependencies:**
- Tasks 2.1, 2.2

---

### Task 3.3: Create MaxDrawdownControlService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create service to monitor and control max drawdown per connection.

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `checkDrawdown()` method implemented
- [ ] Calculate floating loss per connection
- [ ] `triggerEmergencyStop()` method implemented
- [ ] `closeAllPositions()` method implemented
- [ ] Threshold configurable (default 20% equity)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/MaxDrawdownControlService.php`

**Dependencies:**
- Task 1.8
- Trading Execution Engine Addon

**Technical Notes:**
- Monitor in scheduled job (every minute)
- Store emergency stop status in connection settings
- Send notification to user on emergency stop

---

### Task 3.4: Integrate SRM into SignalExecutionService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Integrate SRM logic into Execution Engine's signal execution flow.

**Acceptance Criteria:**
- [ ] SignalQualityFilterService called before execution
- [ ] SlippagePredictionService called before execution
- [ ] SlBufferingService applied to SL
- [ ] RiskOptimizationService applied to lot size
- [ ] SRM adjustments stored in ExecutionPosition
- [ ] Adjustment reasons stored for transparency

**Files to Modify:**
- `addons/trading-execution-engine-addon/app/Services/SignalExecutionService.php`
- `addons/trading-execution-engine-addon/app/Jobs/ExecuteSignalJob.php`

**Dependencies:**
- Tasks 3.1, 3.2, 3.3, 2.3

**Technical Notes:**
- Check if SRM addon is active
- Apply SRM logic only if enabled in connection settings
- Store all adjustments for audit trail

---

### Task 3.5: Integrate SRM into Copy Trading
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Integrate SRM logic into Copy Trading addon's trade copying flow.

**Acceptance Criteria:**
- [ ] PerformanceScoreService called to get trader's score
- [ ] Dynamic lot adjustment applied based on performance score
- [ ] SL buffering applied
- [ ] Signal quality filter applied
- [ ] SRM adjustments stored in CopyTradingExecution

**Files to Modify:**
- `addons/copy-trading-addon/app/Services/TradeCopyService.php`

**Dependencies:**
- Tasks 3.1, 3.2, 2.3
- Copy Trading Addon

**Technical Notes:**
- Check if SRM addon is active
- Apply SRM logic only if enabled in subscription settings
- Respect user's risk multiplier and max position size

---

## Phase 4: Learning Loop & Web UI

### Task 4.1: Create Scheduled Job for Performance Score Update
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** MEDIUM

**Description:**
Create scheduled job to update performance scores periodically.

**Acceptance Criteria:**
- [ ] Job class created
- [ ] Job runs every hour
- [ ] Updates performance scores for all signal providers
- [ ] Job registered in Kernel schedule

**Files to Create:**
- `addons/smart-risk-management-addon/app/Jobs/UpdatePerformanceScoresJob.php`

**Dependencies:**
- Task 2.1

---

### Task 4.2: Create Scheduled Job for Drawdown Monitoring
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create scheduled job to monitor drawdown and trigger emergency stops.

**Acceptance Criteria:**
- [ ] Job class created
- [ ] Job runs every minute
- [ ] Checks drawdown for all active connections
- [ ] Triggers emergency stop if threshold exceeded
- [ ] Sends notifications

**Files to Create:**
- `addons/smart-risk-management-addon/app/Jobs/MonitorDrawdownJob.php`

**Dependencies:**
- Task 3.3

---

### Task 4.3: Create Admin Routes
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create admin routes for SRM management.

**Acceptance Criteria:**
- [ ] Routes file created
- [ ] All routes defined: signal-providers, predictions, models, ab-tests, settings
- [ ] Middleware applied (permission:signal,admin)
- [ ] Routes registered in AddonServiceProvider

**Files to Create:**
- `addons/smart-risk-management-addon/routes/admin.php`

**Dependencies:**
- Task 1.1

**Technical Notes:**
- Follow existing route patterns
- Use `Route::prefix('srm')->name('srm.')`

---

### Task 4.4: Create User Routes
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Create user routes for SRM dashboard and insights.

**Acceptance Criteria:**
- [ ] Routes file created
- [ ] All routes defined: dashboard, adjustments, insights
- [ ] Middleware applied (auth, inactive, is_email_verified, 2fa, kyc)
- [ ] Routes registered in AddonServiceProvider

**Files to Create:**
- `addons/smart-risk-management-addon/routes/user.php`

**Dependencies:**
- Task 1.1

---

### Task 4.5: Create Admin Controllers - SignalProviderMetricsController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create controller for managing signal provider metrics in admin panel.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: List all signal providers with metrics
- [ ] `show($id)` method: Show detailed metrics for one provider
- [ ] Filters: Type, Date Range, Performance Score Range
- [ ] Search functionality
- [ ] Pagination
- [ ] Export to CSV (optional)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/Backend/SignalProviderMetricsController.php`

**Dependencies:**
- Tasks 1.8, 4.3

**Technical Notes:**
- Follow existing controller patterns
- Use `$data['title']` for breadcrumb
- Return view with compact data

---

### Task 4.6: Create Admin Controllers - PredictionController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create controller for viewing predictions and accuracy.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: List predictions with filters
- [ ] `show($id)` method: Show prediction details and accuracy
- [ ] Filters: Type, Date Range, Accuracy Range
- [ ] Charts: Prediction Accuracy Over Time (optional)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/Backend/PredictionController.php`

**Dependencies:**
- Tasks 1.8, 4.3

---

### Task 4.7: Create Admin Controllers - ModelController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create controller for managing ML models.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: List all model versions
- [ ] `show($id)` method: Show model details and performance
- [ ] `retrain($id)` method: Trigger manual retraining (POST)
- [ ] `deploy($id)` method: Deploy model to production (POST)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/Backend/ModelController.php`

**Dependencies:**
- Tasks 1.8, 2.4, 4.3

---

### Task 4.8: Create Admin Controllers - AbTestController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 5 hours  
**Priority:** MEDIUM

**Description:**
Create controller for A/B testing management.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: List A/B tests
- [ ] `create()` method: Show create form
- [ ] `store()` method: Save A/B test
- [ ] `show($id)` method: Show test details
- [ ] `start($id)` method: Start A/B test (POST)
- [ ] `stop($id)` method: Stop A/B test (POST)
- [ ] `results($id)` method: Show test results with comparison

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/Backend/AbTestController.php`

**Dependencies:**
- Tasks 1.8, 4.3

---

### Task 4.9: Create Admin Controllers - SrmSettingsController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** MEDIUM

**Description:**
Create controller for SRM settings management.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: Show settings form
- [ ] `update()` method: Update settings (POST)
- [ ] Settings: Performance Score Threshold, Max Slippage Allowed, Drawdown Threshold, etc.

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/Backend/SrmSettingsController.php`

**Dependencies:**
- Task 4.3

---

### Task 4.10: Create User Controllers - SrmDashboardController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create controller for user SRM dashboard.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: Show user's SRM dashboard
- [ ] Display: Total Adjustments, Avg Performance Score, Slippage Reduction, Drawdown Reduction
- [ ] Chart: Performance Score Over Time
- [ ] Recent Adjustments table

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/User/SrmDashboardController.php`

**Dependencies:**
- Tasks 1.8, 4.4

**Technical Notes:**
- Use user layout: `Config::theme() . 'layout.auth'`
- Filter data by user's connections only

---

### Task 4.11: Create User Controllers - SrmAdjustmentController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create controller for viewing SRM adjustments history.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: List SRM adjustments for user's connections
- [ ] `show($id)` method: Show adjustment details and reason
- [ ] Filters: Connection, Date Range, Adjustment Type
- [ ] Show: Before/After values, reason explanation

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/User/SrmAdjustmentController.php`

**Dependencies:**
- Tasks 1.8, 4.4

---

### Task 4.12: Create User Controllers - SrmInsightController
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** MEDIUM

**Description:**
Create controller for performance insights and recommendations.

**Acceptance Criteria:**
- [ ] Controller created
- [ ] `index()` method: Show performance insights
- [ ] Display: Recommendations, Performance Trends, Risk Warnings

**Files to Create:**
- `addons/smart-risk-management-addon/app/Http/Controllers/User/SrmInsightController.php`

**Dependencies:**
- Tasks 1.8, 4.4

---

### Task 4.13: Create Admin Views - Signal Provider Metrics Index
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create admin view for listing signal provider metrics.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Table with columns: Provider Name, Type, Total Signals, Win Rate, Avg Slippage, Performance Score, Trend
- [ ] Filters: Type, Date Range, Performance Score Range
- [ ] Search functionality
- [ ] Pagination
- [ ] Export to CSV button (optional)
- [ ] Responsive design

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/signal-providers/index.blade.php`

**Dependencies:**
- Task 4.5

**Technical Notes:**
- Follow existing admin view patterns
- Use Bootstrap table classes
- Show trend with up/down arrows or badges

---

### Task 4.14: Create Admin Views - Signal Provider Metrics Show
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create admin view for showing detailed metrics for one signal provider.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Display: Provider Info, Metrics Cards, Performance Chart, Recent Signals Table
- [ ] Show performance score history
- [ ] Show slippage distribution

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/signal-providers/show.blade.php`

**Dependencies:**
- Task 4.5

---

### Task 4.15: Create Admin Views - Predictions Index
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create admin view for listing predictions.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Table with columns: Type, Symbol, Predicted Value, Actual Value, Accuracy, Confidence, Created At
- [ ] Filters: Type, Date Range, Accuracy Range
- [ ] Chart: Prediction Accuracy Over Time (optional, using Chart.js)

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/predictions/index.blade.php`

**Dependencies:**
- Task 4.6

---

### Task 4.16: Create Admin Views - Models Index
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create admin view for listing ML model versions.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Cards for each model type (Slippage Prediction, Performance Score, Risk Optimization)
- [ ] Show: Current Version, Accuracy, Last Training Date, Status
- [ ] Actions: Retrain, Deploy, View History buttons

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/models/index.blade.php`

**Dependencies:**
- Task 4.7

---

### Task 4.17: Create Admin Views - A/B Tests Index
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create admin view for listing A/B tests.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Table with columns: Name, Status, Start Date, End Date, Pilot Group Size, Control Group Size, Results
- [ ] Actions: Create, Start, Stop, View Results buttons

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/ab-tests/index.blade.php`

**Dependencies:**
- Task 4.8

---

### Task 4.18: Create Admin Views - A/B Tests Create/Show
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** MEDIUM

**Description:**
Create admin views for creating and viewing A/B test details.

**Acceptance Criteria:**
- [ ] Create view: Form with fields: Name, Description, Pilot Logic (JSON editor), Pilot Percentage
- [ ] Show view: Test details, comparison charts, statistical significance, decision recommendation
- [ ] Results view: Side-by-side comparison, charts, p-value display

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/ab-tests/create.blade.php`
- `addons/smart-risk-management-addon/resources/views/backend/ab-tests/show.blade.php`
- `addons/smart-risk-management-addon/resources/views/backend/ab-tests/results.blade.php`

**Dependencies:**
- Task 4.8

**Technical Notes:**
- Use JSON editor for pilot logic (CodeMirror or similar)
- Use Chart.js for comparison charts

---

### Task 4.19: Create Admin Views - Settings
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** MEDIUM

**Description:**
Create admin view for SRM settings.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends backend layout
- [ ] Form with settings: Performance Score Threshold, Max Slippage Allowed, Drawdown Threshold, etc.
- [ ] Save button

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/backend/settings/index.blade.php`

**Dependencies:**
- Task 4.9

---

### Task 4.20: Create User Views - Dashboard
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create user view for SRM dashboard.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends user layout: `Config::theme() . 'layout.auth'`
- [ ] Cards: Total Adjustments, Avg Performance Score, Slippage Reduction, Drawdown Reduction
- [ ] Chart: Performance Score Over Time (Chart.js)
- [ ] Recent Adjustments table
- [ ] Insights section
- [ ] Responsive design
- [ ] Follow user UI patterns (row gy-4, sp_site_card)

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/user/dashboard.blade.php`

**Dependencies:**
- Task 4.10

**Technical Notes:**
- Use same wrapper structure as dashboard: `<div class="row gy-4"><div class="col-12">`
- Use `sp_site_card` class for cards

---

### Task 4.21: Create User Views - Adjustments Index
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create user view for listing SRM adjustments.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends user layout
- [ ] Table: Date, Connection, Signal, Adjustment Type (Lot/SL), Reason, Impact
- [ ] Filters: Connection, Date Range
- [ ] Pagination
- [ ] Responsive design

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/user/adjustments/index.blade.php`

**Dependencies:**
- Task 4.11

---

### Task 4.22: Create User Views - Adjustments Show
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create user view for showing adjustment details.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends user layout
- [ ] Display: Adjustment Details, Before/After Values, Reason Explanation, Impact Analysis

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/user/adjustments/show.blade.php`

**Dependencies:**
- Task 4.11

---

### Task 4.23: Create User Views - Insights
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create user view for performance insights.

**Acceptance Criteria:**
- [ ] View file created
- [ ] Extends user layout
- [ ] Display: Recommendations, Performance Trends, Risk Warnings
- [ ] Charts for trends

**Files to Create:**
- `addons/smart-risk-management-addon/resources/views/user/insights/index.blade.php`

**Dependencies:**
- Task 4.12

---

### Task 4.24: Add SRM Menu to Admin Sidebar
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Add SRM menu items to admin sidebar.

**Acceptance Criteria:**
- [ ] Menu items added to admin sidebar
- [ ] Items: Signal Providers, Predictions, Models, A/B Tests, Settings
- [ ] Icons appropriate
- [ ] Conditional display based on addon status

**Files to Modify:**
- `resources/views/backend/layout/sidebar.blade.php` (or similar)

**Dependencies:**
- Task 4.3

---

### Task 4.25: Add SRM Menu to User Sidebar
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Add SRM menu items to user sidebar.

**Acceptance Criteria:**
- [ ] Menu items added to user sidebar
- [ ] Items: SRM Dashboard, Adjustments, Insights
- [ ] Icons appropriate
- [ ] Conditional display based on addon status and user permissions

**Files to Modify:**
- `resources/views/frontend/{theme}/layout/user_sidebar.blade.php`

**Dependencies:**
- Task 4.4

**Technical Notes:**
- Check if addon is active: `AddonRegistry::active('smart-risk-management-addon')`
- Check if user module enabled: `AddonRegistry::moduleEnabled('smart-risk-management-addon', 'user_ui')`

---

### Task 4.26: Create AbTestingService
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 5 hours  
**Priority:** MEDIUM

**Description:**
Create service for A/B testing functionality.

**Acceptance Criteria:**
- [ ] Service class created
- [ ] `createTest()` method implemented
- [ ] `assignUserToGroup()` method implemented
- [ ] `compareResults()` method implemented
- [ ] `calculateStatisticalSignificance()` method implemented (p-value calculation)
- [ ] Group assignment logic (pilot vs control)

**Files to Create:**
- `addons/smart-risk-management-addon/app/Services/AbTestingService.php`

**Dependencies:**
- Tasks 1.8, 2.3

**Technical Notes:**
- Use consistent hashing for group assignment (same user always in same group for same test)
- Statistical significance: Use t-test or chi-square test

---

### Task 4.27: Create Automated Retraining Job
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 4 hours  
**Priority:** MEDIUM

**Description:**
Create scheduled job for automated ML model retraining.

**Acceptance Criteria:**
- [ ] Job class created
- [ ] Job runs weekly (Sunday after market close)
- [ ] Retrains all 3 models (slippage, performance score, risk optimization)
- [ ] Validates model accuracy
- [ ] Deploys new model if accuracy improved
- [ ] Sends notification to admin on completion/failure

**Files to Create:**
- `addons/smart-risk-management-addon/app/Jobs/RetrainModelsJob.php`

**Dependencies:**
- Tasks 2.4, 4.1

---

## Testing & Documentation

### Task 5.1: Write Unit Tests for Services
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 8 hours  
**Priority:** MEDIUM

**Description:**
Write unit tests for all SRM services.

**Acceptance Criteria:**
- [ ] Tests for MarketContextService
- [ ] Tests for SlippageCalculationService
- [ ] Tests for PerformanceScoreService
- [ ] Tests for SlippagePredictionService
- [ ] Tests for RiskOptimizationService
- [ ] Tests for SlBufferingService
- [ ] Tests for SignalQualityFilterService
- [ ] Tests for MaxDrawdownControlService
- [ ] All tests pass

**Files to Create:**
- `addons/smart-risk-management-addon/tests/Unit/Services/*Test.php`

**Dependencies:**
- All service tasks

---

### Task 5.2: Write Integration Tests
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 6 hours  
**Priority:** MEDIUM

**Description:**
Write integration tests for SRM integration with Execution Engine and Copy Trading.

**Acceptance Criteria:**
- [ ] Tests for Execution Engine integration
- [ ] Tests for Copy Trading integration
- [ ] Tests for end-to-end SRM flow
- [ ] All tests pass

**Files to Create:**
- `addons/smart-risk-management-addon/tests/Integration/*Test.php`

**Dependencies:**
- Tasks 3.4, 3.5

---

### Task 5.3: Create API Documentation
**Status:** TODO  
**Assignee:** Backend Developer  
**Estimate:** 2 hours  
**Priority:** LOW

**Description:**
Create API documentation for SRM services and endpoints.

**Acceptance Criteria:**
- [ ] Service method documentation
- [ ] API endpoint documentation (if any)
- [ ] Usage examples

**Files to Create:**
- `addons/smart-risk-management-addon/API.md`

**Dependencies:**
- All service tasks

---

## Summary

### Total Estimated Hours
- **Phase 1 (Foundation)**: ~20 hours
- **Phase 2 (Learning Engine)**: ~16 hours
- **Phase 3 (Adaptive Mechanisms)**: ~17 hours
- **Phase 4 (Learning Loop & Web UI)**: ~60 hours
- **Testing & Documentation**: ~16 hours

**Grand Total**: ~129 hours (~16 days for 1 developer, or ~8 days for 2 developers)

### Critical Path
1. Phase 1: Foundation (Tasks 1.1-1.11)
2. Phase 2: Learning Engine (Tasks 2.1-2.3)
3. Phase 3: Adaptive Mechanisms (Tasks 3.1-3.5)
4. Phase 4: Web UI (Tasks 4.3-4.25)

### Dependencies Map
- **Phase 1** → **Phase 2** → **Phase 3** → **Phase 4**
- **Web UI** depends on all previous phases
- **Testing** depends on all implementation tasks

### Risk Mitigation
- Start with simple formulas (Phase 2), upgrade to ML later
- Feature flag to disable SRM if issues occur
- Gradual rollout: Test on small user group first
- Monitor model accuracy and alert on drift

