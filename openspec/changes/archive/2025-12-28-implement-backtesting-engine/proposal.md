# Change: Implement Basic Backtesting Engine

## Why

Backtesting is a **critical missing feature** (P0) that users need to validate trading strategies before risking real money. Currently:

- **No backtesting functionality exists** - Users cannot test strategies on historical data
- **No historical data storage** - OHLCV data not collected or stored
- **No strategy configuration UI** - No way to define backtesting parameters
- **No performance metrics** - No way to evaluate strategy performance
- **No visualization** - No equity curve or trade analysis charts

This is a **core feature** for a trading platform - users need confidence in strategies before live trading.

## What Changes

- **Historical data collection** - Fetch and store OHLCV data from exchanges
- **Backtesting engine** - Execute strategy logic on historical data
- **Strategy configuration UI** - Form to configure backtest parameters (pair, timeframe, date range, filters)
- **Performance metrics** - Calculate return, win rate, max drawdown, profit factor
- **Equity curve visualization** - Chart showing account balance over time
- **Trade list and export** - View all trades and export to CSV
- **Basic UI** - Simple interface to run and view backtest results

## Impact

- **Affected specs**: New capability: `backtesting`
- **Affected code**:
  - New: `app/Services/BacktestingService.php`
  - New: `app/Http/Controllers/User/Trading/BacktestingController.php`
  - New: `app/Jobs/FetchHistoricalDataJob.php`
  - New: `app/Jobs/RunBacktestJob.php`
  - Database: New `backtests` and `backtest_trades` tables
  - Views: `resources/views/user/trading/backtesting/*`
  - Routes: `/user/backtesting/*`
- **Breaking changes**: None - new feature
- **User impact**: **HIGH** - Enables strategy validation before live trading

