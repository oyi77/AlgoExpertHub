# Trading Execution Engine Addon

A comprehensive addon for executing trades on connected exchanges/brokers based on published signals from the multi-channel-signal-addon.

## Features

- **Connection Management**: Support for crypto exchanges (via ccxt) and FX brokers (via mtapi.io)
- **Automated Execution**: Automatically executes published signals on active connections
- **Position Monitoring**: Real-time position tracking with automatic SL/TP execution
- **Analytics**: Comprehensive performance metrics including win rate, profit factor, drawdown
- **Notifications**: Execution confirmations, position updates, and error alerts
- **Admin & User Support**: Both admins and users can manage their own connections

## Installation

1. The addon is located in `main/addons/trading-execution-engine-addon/`
2. Run migrations: `php artisan migrate`
3. The service provider is automatically registered in `config/app.php`
4. Install ccxt for crypto exchanges: `composer require ccxt/ccxt`

## Configuration

### Supported Exchanges

**Crypto Exchanges** (via ccxt):
- Binance, Coinbase, Kraken, and 100+ other exchanges supported by ccxt
- Configure with: `api_key`, `api_secret`, and optionally `api_passphrase`

**FX Brokers** (via mtapi.io):
- MT4/MT5 brokers
- Configure with: `api_key`, `api_secret`, `account_id`

### Position Sizing Strategies

- `fixed`: Fixed quantity per trade
- `percentage`: Percentage of account balance
- `fixed_amount`: Fixed dollar amount
- `signal_based`: Use quantity from signal (future enhancement)

## Usage

### Admin

1. Go to **Trading Execution > My Connections** to create connections
2. Test and activate connections
3. Monitor executions, positions, and analytics

### Users

1. Go to **Auto Trading** (if plan permits)
2. Create and manage your connections
3. View your personal analytics

## Scheduled Jobs

- **MonitorPositionsJob**: Runs every minute to update position prices and check SL/TP
- **UpdateAnalyticsJob**: Runs daily at midnight to calculate analytics

## Signal Execution Flow

1. Signal is published (via SignalService::sent())
2. SignalObserver detects the published signal
3. ExecuteSignalJob is dispatched for each active connection
4. SignalExecutionService validates and executes the order
5. Position is created and monitored
6. Notifications are sent

## Database Tables

- `execution_connections`: Exchange/broker connections
- `execution_logs`: Execution history
- `execution_positions`: Open and closed positions
- `execution_analytics`: Daily performance metrics
- `execution_notifications`: User/admin notifications

## Security

- Credentials are encrypted using Laravel's encryption
- User isolation: Users can only see their own data
- Admin isolation: Admin connections are separate from user connections
- API keys should have trading permissions only (no withdrawal)

## Dependencies

- `ccxt/ccxt`: For crypto exchange integration
- `guzzlehttp/guzzle`: For HTTP requests (already in Laravel)

## Notes

- For production, ensure queue workers are running for async execution
- Test connections in sandbox/paper trading mode first
- Monitor API rate limits to avoid throttling
- Position monitoring requires active scheduled task runner

