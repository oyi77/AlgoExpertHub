# AI Smart Risk Management (SRM) - Feature Brief

## Overview
Implementasi AI Smart Risk Management (SRM) sebagai Learning Engine yang adaptif dan terus belajar untuk mengoptimalkan risiko trading secara dinamis, bukan hanya mengikuti instruksi Stop Loss (SL) statis.

## User Story
Sebagai **pengguna copy trading**, saya ingin platform secara otomatis menyesuaikan risiko trading berdasarkan pembelajaran dari data historis, sehingga:
- Lot size disesuaikan dinamis berdasarkan performa Signal Provider
- Stop Loss dibuffer secara otomatis untuk mengantisipasi slippage
- Sinyal berkualitas rendah difilter otomatis sebelum eksekusi
- Platform terus belajar dan meningkatkan akurasi prediksi dari waktu ke waktu

## Current State Analysis

### ✅ Yang Sudah Ada di Sistem Existing

1. **Execution Tracking**
   - `ExecutionLog` - mencatat eksekusi sinyal (entry_price, sl_price, tp_price, status)
   - `ExecutionPosition` - tracking posisi terbuka dengan PnL calculation
   - `ExecutionAnalytic` - analytics dasar (win rate, profit factor, max_drawdown)

2. **Position Sizing**
   - Trading Preset Addon - position sizing (FIXED, RISK_PERCENT)
   - Copy Trading - risk multiplier, quantity calculation
   - Basic risk calculation berdasarkan equity dan SL distance

3. **Signal Analytics**
   - Multi-Channel Addon - SignalAnalytics untuk tracking sinyal
   - Basic metrics tracking

### ❌ Yang Belum Ada (Gap Analysis)

#### Fase 1: Data Acquisition & Normalisasi
- ❌ **Slippage Tracking** - tidak ada field `slippage` di ExecutionLog
- ❌ **Market Context Data** - tidak ada tracking:
  - ATR (Average True Range) saat eksekusi
  - Trading Session (Tokyo/London/New York)
  - Day of Week
  - Volatility metrics
- ❌ **Signal Provider Performance History** - tidak ada tracking per-SP metrics

#### Fase 2: Model Pembelajaran & Analisis
- ❌ **Slippage Prediction Engine** - tidak ada ML model untuk prediksi slippage
- ❌ **Performance Score Engine** - tidak ada dynamic scoring untuk Signal Provider
- ❌ **Risk Optimization Engine** - tidak ada ML-based lot optimization

#### Fase 3: Mekanisme Adaptif & Real-Time Adjustment
- ❌ **Adaptive SL Buffering** - tidak ada dynamic SL adjustment berdasarkan slippage prediction
- ❌ **Dynamic Lot Adjustment** - tidak ada penyesuaian lot berdasarkan performance score
- ❌ **Signal Quality Filtering** - tidak ada dynamic filtering berdasarkan performance score
- ❌ **Max Drawdown Control** - tidak ada auto-stop saat drawdown threshold tercapai

#### Fase 4: Umpan Balik & Peningkatan
- ❌ **Automated Retraining Pipeline** - tidak ada scheduled ML retraining
- ❌ **A/B Testing Framework** - tidak ada framework untuk testing SRM logic baru
- ❌ **Model Drift Detection** - tidak ada monitoring akurasi model

## Technical Requirements

### Phase 1: Data Acquisition & Normalization

#### 1.1 Extend ExecutionLog Model
```php
// Add to execution_logs table:
- slippage (decimal) - actual slippage in pips
- latency_ms (integer) - time from signal received to execution
- market_atr (decimal) - ATR value at execution time
- trading_session (enum) - TOKYO, LONDON, NEW_YORK, ASIAN
- day_of_week (integer) - 1-7
- volatility_index (decimal) - calculated volatility metric
```

#### 1.2 Create SignalProviderMetrics Model
```php
// New table: signal_provider_metrics
- signal_provider_id (string) - identifier (channel_source_id or user_id)
- period_start (date)
- period_end (date)
- total_signals (integer)
- win_rate (decimal)
- avg_slippage (decimal)
- max_drawdown (decimal)
- reward_to_risk_ratio (decimal)
- sl_compliance_rate (decimal) - how often SP respects SL
- performance_score (decimal) - 0-100 dynamic score
```

#### 1.3 Market Context Service
- Service untuk fetch ATR dari exchange/broker
- Service untuk determine trading session berdasarkan waktu
- Service untuk calculate volatility metrics

### Phase 2: Learning Engine Models

#### 2.1 Slippage Prediction Engine
- **Input**: Symbol, Trading Session, ATR, Signal Provider, Day of Week
- **Output**: Predicted slippage (pips)
- **Model Type**: Time Series Regression atau Linear Regression
- **Training Data**: Historical ExecutionLog dengan slippage actual
- **Retraining**: Weekly automated retraining

#### 2.2 Performance Score Engine
- **Input**: Win Rate, Max Drawdown, Reward-to-Risk Ratio, SL Compliance Rate
- **Output**: Performance Score (0-100)
- **Model Type**: Bayesian Ranking atau Weighted Scoring
- **Update Frequency**: Real-time setelah setiap trade closed
- **Decay Factor**: Score turun lebih cepat saat loss streak

#### 2.3 Risk Optimization Engine
- **Input**: User Equity, Risk Tolerance, Predicted Slippage, SL Distance, Performance Score
- **Output**: Optimal Lot Size
- **Formula**: 
  ```
  Base Lot = (Equity * Risk%) / SL_Distance
  Adjusted Lot = Base Lot * Performance_Score_Multiplier * Slippage_Buffer_Factor
  ```
- **Constraints**: Min/Max lot, Max position size

### Phase 3: Adaptive Mechanisms

#### 3.1 SL Buffering Service
- Automatically add buffer (1-3 pips) to SL jika predicted slippage tinggi
- Buffer calculation berdasarkan:
  - Predicted slippage dari Modul 1
  - Symbol volatility (ATR)
  - Trading session

#### 3.2 Dynamic Lot Adjustment
- Reduce lot jika Performance Score < threshold (e.g., 60)
- Reduce lot jika predicted slippage > threshold
- Increase lot jika Performance Score tinggi dan slippage rendah

#### 3.3 Signal Quality Filter
- Reject signal jika Performance Score SP < threshold
- Reject signal jika predicted slippage > max allowed
- Reject signal jika entry price sudah expired (slippage > 5 pips)

#### 3.4 Max Drawdown Control
- Monitor total floating loss per connection
- Auto-stop semua copy trading jika drawdown > threshold (e.g., 20% equity)
- Close all open positions jika emergency stop triggered

### Phase 4: Learning Loop

#### 4.1 Automated Retraining Pipeline
- Scheduled job (weekly, Sunday after market close)
- Retrain all 3 ML models dengan data terbaru
- Validate model accuracy
- Deploy new models jika accuracy improved

#### 4.2 A/B Testing Framework
- Assign users to pilot group (10-20% users)
- Apply new SRM logic to pilot group
- Compare performance (P/L, Drawdown) vs control group
- Deploy to all users jika improvement significant

#### 4.3 Model Drift Detection
- Monitor prediction accuracy
- Alert jika accuracy drop below threshold (e.g., < 50%)
- Trigger manual investigation

## Database Schema Changes

### New Tables

1. **signal_provider_metrics**
   - Track performance metrics per Signal Provider
   - Period-based (daily, weekly, monthly)

2. **srm_predictions**
   - Store ML model predictions
   - Track prediction accuracy

3. **srm_model_versions**
   - Track ML model versions
   - Model performance metrics

4. **srm_ab_tests**
   - A/B testing experiments
   - Group assignments and results

### Modified Tables

1. **execution_logs**
   - Add slippage, latency_ms, market_atr, trading_session, day_of_week, volatility_index

2. **execution_positions**
   - Add predicted_slippage, performance_score_at_entry, srm_adjusted_lot

## Integration Points

### With Existing Addons

1. **Trading Execution Engine Addon**
   - Extend `SignalExecutionService` untuk apply SRM logic
   - Modify `ExecuteSignalJob` untuk collect market context
   - Update `ExecutionLog` creation dengan slippage calculation

2. **Copy Trading Addon**
   - Extend `TradeCopyService` untuk apply SRM filtering
   - Modify lot calculation dengan Performance Score
   - Add SL buffering sebelum eksekusi

3. **Trading Preset Addon**
   - Integrate SRM dengan preset-based position sizing
   - Allow SRM to override preset settings jika diperlukan

4. **Multi-Channel Signal Addon**
   - Link Signal Provider metrics dengan ChannelSource
   - Track performance per channel source

## Implementation Phases

### Phase 1: Foundation (Week 1-2)
- Extend ExecutionLog dengan market context fields
- Create SignalProviderMetrics model
- Build Market Context Service
- Implement slippage calculation

### Phase 2: Learning Engine (Week 3-4)
- Build Slippage Prediction Engine (simple regression first)
- Build Performance Score Engine (weighted scoring)
- Build Risk Optimization Engine (formula-based)
- Create training data pipeline

### Phase 3: Adaptive Mechanisms (Week 5-6)
- Implement SL Buffering Service
- Implement Dynamic Lot Adjustment
- Implement Signal Quality Filter
- Implement Max Drawdown Control

### Phase 4: Learning Loop (Week 7-8)
- Build Automated Retraining Pipeline
- Build A/B Testing Framework
- Implement Model Drift Detection
- Create monitoring dashboard

## Success Metrics

1. **Accuracy Metrics**
   - Slippage Prediction Accuracy: > 70%
   - Performance Score Correlation: > 0.8 dengan actual performance

2. **Performance Metrics**
   - Average Drawdown Reduction: 20-30%
   - Win Rate Improvement: 5-10%
   - Slippage Reduction: 15-25%

3. **User Experience**
   - Reduced Stop-Outs: 30-40%
   - User Retention: +15%
   - Premium Subscription Conversion: +20%

## Technical Stack

- **ML Framework**: PHP-ML atau Python microservice (via API)
- **Time Series**: For slippage prediction
- **Database**: MySQL dengan JSON fields untuk model parameters
- **Queue**: Laravel Queue untuk async ML training
- **Scheduler**: Laravel Scheduler untuk retraining jobs

## Quick Start

1. Start with Phase 1: Data collection
2. Build simple regression model untuk slippage prediction
3. Implement basic Performance Score (weighted formula)
4. Add SL buffering based on predicted slippage
5. Iterate and improve models

## Notes

- Start simple, iterate complex
- Focus on data quality first
- ML models can start with simple formulas, evolve to ML later
- A/B testing is critical for validating improvements
- User transparency builds trust (show why lot was adjusted)

