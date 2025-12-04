## Scope & Objectives
- Enable end-to-end demo: login → connect exchange → create/copy bots → stream OHLCV → indicators/filters → open/close trades → SL/TP monitoring → backtests → user/admin panels.
- Use existing Laravel addons: trading-management, execution engine, filter strategy, data provider, copy trading, backtesting.

## Backend Auth & Panels
- Verify user login via `routes/web.php` and `Auth\LoginController`; ensure 2FA/KYC gates are optional for demo.
- Verify admin login via `routes/admin.php` and `Backend\Auth\LoginController`.
- Confirm menus and dashboard views render for both panels.

## Exchange Connections
- Use unified `ExchangeConnection` CRUD in admin to add:
  - Crypto (CCXT): e.g., `binance` with `api_key`, `api_secret`, optional passphrase.
  - FX (mtapi): `api_key`, `account_id`, optional `base_url`.
- Ensure credentials encryption (`encrypted:array` / trait) and test fetch routes (candles/tickers) work.

## Market Data & Streaming
- Activate `DataConnection` and run fetch jobs to populate OHLCV (`FetchMarketDataJob`, `FetchAllActiveConnectionsJob`).
- Implement adapter integration in `ProcessMarketStreamBotWorker` to pull OHLCV via `DataConnectionService` on a loop with timeframe control and health timestamps.
- Persist live candles through `MarketDataService`; dispatch `DataReceived` events.

## Indicators & Filter Strategies
- Use `TechnicalAnalysisService` for EMA/RSI/MACD/BB/STOCH.
- Apply `FilterStrategyEvaluator` rules to latest OHLCV for bot/filter gating.
- Ensure strategy CRUD and assignment to bots are available in admin.

## Bots (Create/Copy)
- Create bots via admin/user controllers (`TradingBotController`) with modes:
  - `SIGNAL_BASED`: reacts to published signals.
  - `MARKET_STREAM_BASED`: runs looped OHLCV analysis.
- Implement copy-from-template and clone existing bot configs (name, connections, preset, strategy).

## Execution & Position Management
- Use `ExecutionConnection` plus `CryptoExchangeAdapter`/`FxBrokerAdapter` for order placement.
- Support paper trading toggle for safe demo; persist `ExecutionLog` and `ExecutionPosition`.
- Link bot-level `TradingBotPosition` to execution positions when available.

## SL/TP Monitoring
- Run `PositionMonitoringService` for bot positions and `PositionService` for execution positions.
- Periodically update current price, evaluate SL/TP, close and log events; sync external closes.

## Copy Trading
- Seed `CopyTradingSubscription` for a follower; enable `CopyTradingSetting`.
- Ensure `TradeCopyService` replicates trader opens/closes and `CopyTradeJob`/listeners fire.

## Backtesting
- Use `BacktestController` to create jobs; confirm `BacktestEngine` loads OHLCV via `MarketDataService`.
- Store results in `BacktestResult`; render curves and metrics in admin views.

## Frontend (User Panel)
- Ensure login, dashboard, bot list/create, basic strategy assignment, and position overview render.
- Expose exchange connection UI for users if required (or keep admin-only for demo).

## Demo Flow Script
- Admin: login → add CCXT/mtapi connections → test data fetch → create filter strategy → create two bots (signal-based and market-stream) → enable paper trading → start workers (market-stream) → watch positions open/close and SL/TP events → run backtest and view results.
- User: login → view/copy bot → track positions and P/L cards.

## Environment & Operations
- Configure `.env` for queues and cache; set scheduler: `php artisan schedule:work`.
- Start queue workers: `php artisan queue:work` with appropriate `--queue` names for jobs.
- Ensure `artisan serve` is running for web; verify storage/permissions for logs.

## Validation & Telemetry
- Add health/timestamps updates for connections, workers, and monitoring services.
- Use admin views for logs (`execution_logs`, data connection logs) and position counts.
- Confirm SL/TP hits are recorded; verify backtest completion status and result charts.

## Deliverables
- Working admin/user panels.
- Exchange connections tested.
- Bots running with OHLCV streaming and filters.
- Positions opening/closing with SL/TP.
- Backtests producing results.
- Demo script + quickstart checklist for stakeholders.
