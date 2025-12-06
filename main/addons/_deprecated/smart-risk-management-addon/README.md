# AI Smart Risk Management (SRM) Addon

## Overview
AI-powered adaptive risk management system that learns from historical data to optimize trading risk dynamically. The SRM system continuously learns and adapts to market conditions and signal provider performance.

## Features

### Phase 1: Data Acquisition & Normalization
- Market context tracking (ATR, trading sessions, volatility)
- Slippage calculation and storage
- Signal provider metrics tracking

### Phase 2: Learning Engine
- Performance Score Engine (weighted scoring for signal providers)
- Slippage Prediction Engine (predicts slippage based on historical data)
- Risk Optimization Engine (calculates optimal lot size)

### Phase 3: Adaptive Mechanisms
- SL Buffering (automatic stop loss adjustment based on predicted slippage)
- Signal Quality Filtering (rejects low-quality signals)
- Dynamic Lot Adjustment (adjusts lot size based on performance score)
- Max Drawdown Control (emergency stop on excessive drawdown)

### Phase 4: Learning Loop
- Automated model retraining
- A/B testing framework
- Model drift detection

## Installation

1. The addon is automatically registered when present in `addons/` directory
2. Run migrations: `php artisan migrate`
3. Enable addon in admin panel: `/admin/addons`

## Configuration

SRM settings can be configured in admin panel: `/admin/srm/settings`

## Usage

### For Users
- View SRM dashboard: `/user/srm`
- View adjustments history: `/user/srm/adjustments`
- View performance insights: `/user/srm/insights`

### For Admins
- View signal provider metrics: `/admin/srm/signal-providers`
- View predictions: `/admin/srm/predictions`
- Manage ML models: `/admin/srm/models`
- Manage A/B tests: `/admin/srm/ab-tests`
- Configure settings: `/admin/srm/settings`

## Integration

SRM integrates with:
- **Trading Execution Engine Addon**: Applies SRM logic during signal execution
- **Copy Trading Addon**: Applies SRM logic during trade copying
- **Trading Preset Addon**: Works alongside preset-based position sizing

## Documentation

See `specs/active/ai-smart-risk-management/` for detailed specifications, architecture, and task breakdown.

