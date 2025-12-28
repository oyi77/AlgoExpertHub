## ADDED Requirements

### Requirement: Historical Data Collection
The system SHALL collect and store historical OHLCV (Open, High, Low, Close, Volume) data from exchanges for backtesting.

#### Scenario: Historical data is fetched and stored
- **WHEN** historical data collection runs
- **THEN** OHLCV data SHALL be fetched from exchange APIs (CCXT)
- **AND** data SHALL be stored in `historical_ohlcv` table
- **AND** at least 1 year of data SHALL be available for major trading pairs

#### Scenario: Historical data is updated daily
- **WHEN** scheduled task runs daily
- **THEN** new OHLCV data SHALL be fetched for all tracked pairs
- **AND** existing data SHALL be updated with latest candles
- **AND** data gaps SHALL be filled if missing

### Requirement: Backtesting Engine
The system SHALL execute trading strategies on historical data to simulate performance.

#### Scenario: User runs a backtest
- **WHEN** a user configures and runs a backtest
- **THEN** the system SHALL load historical data for specified symbol and timeframe
- **AND** the system SHALL execute strategy logic on historical data
- **AND** the system SHALL simulate trades (entry, exit, profit/loss)
- **AND** the system SHALL track account balance over time

#### Scenario: Backtest calculates performance metrics
- **WHEN** a backtest completes
- **THEN** the system SHALL calculate total return
- **AND** the system SHALL calculate win rate
- **AND** the system SHALL calculate max drawdown
- **AND** the system SHALL calculate profit factor
- **AND** metrics SHALL be stored in database

### Requirement: Backtesting Configuration UI
The system SHALL provide a user interface to configure and run backtests.

#### Scenario: User accesses backtesting page
- **WHEN** a user navigates to backtesting page
- **THEN** the page SHALL show form to configure backtest
- **AND** form SHALL include symbol selector, timeframe selector, date range picker
- **AND** form SHALL include initial balance input
- **AND** form SHALL have "Run Backtest" button

#### Scenario: User runs backtest
- **WHEN** a user fills form and clicks "Run Backtest"
- **THEN** backtest SHALL be queued for execution
- **AND** user SHALL see "Running" status
- **AND** backtest SHALL execute asynchronously
- **AND** user SHALL be notified when complete

### Requirement: Backtest Results Display
The system SHALL display backtest results with metrics, charts, and trade list.

#### Scenario: User views backtest results
- **WHEN** a backtest completes
- **THEN** results page SHALL display performance metrics (return, win rate, max drawdown)
- **AND** results page SHALL display equity curve chart
- **AND** results page SHALL display trade list with all trades
- **AND** user SHALL be able to export trade list to CSV

#### Scenario: Equity curve is displayed
- **WHEN** backtest results are displayed
- **THEN** equity curve chart SHALL show account balance over time
- **AND** chart SHALL be a simple line chart
- **AND** chart SHALL be readable and accurate

