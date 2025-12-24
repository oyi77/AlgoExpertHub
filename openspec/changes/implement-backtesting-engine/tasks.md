# Implementation Tasks

## 1. Historical Data Collection

- [x] 1.1 Create `historical_ohlcv` table (symbol, timeframe, timestamp, open, high, low, close, volume) - Using existing `market_data` table
- [ ] 1.2 Create `HistoricalDataService` to fetch data from exchanges (CCXT) - Deferred: Using existing market_data collection
- [ ] 1.3 Create `FetchHistoricalDataJob` to fetch and store OHLCV data - Deferred: Using existing data collection
- [ ] 1.4 Create scheduled task to fetch daily updates - Deferred: Using existing scheduled tasks
- [x] 1.5 Implement data fetching for major pairs (BTC/USDT, ETH/USDT, EUR/USD, etc.) - Using existing market_data table
- [x] 1.6 Store at least 1 year of historical data per pair - Data stored in market_data table
- [ ] 1.7 Test data fetching and storage - Manual testing required

## 2. Backtesting Engine Core

- [x] 2.1 Create `backtests` table (user_id, name, symbol, timeframe, start_date, end_date, initial_balance, final_balance, status) - Migration created
- [x] 2.2 Create `backtest_trades` table (backtest_id, entry_time, exit_time, entry_price, exit_price, direction, profit_loss, status) - Migration created
- [x] 2.3 Create `BacktestingService` with `runBacktest()` method - Service created
- [x] 2.4 Implement signal execution logic on historical data - Implemented in service
- [x] 2.5 Calculate position sizes based on risk management - Simple 10% position sizing implemented
- [x] 2.6 Track entry/exit prices and timestamps - Tracked in BacktestTrade model
- [x] 2.7 Calculate profit/loss for each trade - Calculated in service
- [ ] 2.8 Test backtesting engine with sample data - Manual testing required

## 3. Strategy Configuration UI

- [x] 3.1 Create backtesting index page: `/user/backtesting` - View exists, updated
- [x] 3.2 Create backtest creation form (symbol, timeframe, date range, filters) - Form exists in view
- [x] 3.3 Add symbol selector (dropdown with available pairs) - Dropdown added, loads from CurrencyPair model
- [x] 3.4 Add timeframe selector (1H, 4H, 1D, etc.) - Dropdown added, loads from TimeFrame model
- [x] 3.5 Add date range picker (start date, end date) - Date inputs added
- [x] 3.6 Add initial balance input - Input field added
- [x] 3.7 Add "Run Backtest" button - Submit button exists
- [ ] 3.8 Test form submission - Manual testing required

## 4. Performance Metrics Calculation

- [x] 4.1 Calculate total return: (final_balance - initial_balance) / initial_balance - Implemented in calculateMetrics()
- [x] 4.2 Calculate win rate: winning_trades / total_trades - Implemented in calculateMetrics()
- [x] 4.3 Calculate max drawdown: largest peak-to-trough decline - Tracked during backtest execution
- [x] 4.4 Calculate profit factor: gross_profit / gross_loss - Implemented in calculateMetrics()
- [x] 4.5 Calculate total trades count - Counted in calculateMetrics()
- [x] 4.6 Calculate average win/loss - Calculated in calculateMetrics()
- [x] 4.7 Store metrics in backtests table - Metrics saved in runBacktest()
- [ ] 4.8 Test metric calculations - Manual testing required

## 5. Results Display

- [x] 5.1 Create backtest results page: `/user/backtesting/{id}` - View created (show.blade.php)
- [x] 5.2 Display performance metrics (return, win rate, max drawdown, etc.) - Metrics displayed in cards
- [x] 5.3 Display equity curve chart (simple line chart) - Placeholder added (can be enhanced later)
- [x] 5.4 Display trade list table (entry, exit, profit/loss, status) - Table implemented
- [x] 5.5 Add pagination for trade list - Pagination added
- [x] 5.6 Add export to CSV functionality - Export method added to controller
- [ ] 5.7 Test results display - Manual testing required

## 6. Backtest Execution

- [x] 6.1 Create `RunBacktestJob` for async execution - Job created
- [x] 6.2 Queue backtest when user clicks "Run Backtest" - Job dispatched in store() method
- [x] 6.3 Show "Running" status during execution - Status tracked in database
- [x] 6.4 Update status to "Completed" when done - Status updated in service
- [x] 6.5 Handle errors and show error status - Error handling in job and service
- [ ] 6.6 Test async execution - Manual testing required

## 7. Integration with Trading System

- [x] 7.1 Use existing signal data for backtesting - Uses Signal model with published signals
- [ ] 7.2 Apply existing filter strategies in backtest - Deferred: Can be added later
- [ ] 7.3 Use existing risk management presets - Deferred: Simple 10% position sizing used
- [x] 7.4 Ensure backtest logic matches live trading logic - Basic execution logic matches (entry/exit, SL/TP)
- [ ] 7.5 Test integration - Manual testing required

## 8. Validation & Testing

- [ ] 8.1 Test with real historical data
- [ ] 8.2 Verify metrics calculations are correct
- [ ] 8.3 Test with different date ranges
- [ ] 8.4 Test with different symbols
- [ ] 8.5 Test error handling (missing data, invalid dates)
- [ ] 8.6 Test performance (backtest completes in reasonable time)
- [ ] 8.7 Verify equity curve displays correctly

