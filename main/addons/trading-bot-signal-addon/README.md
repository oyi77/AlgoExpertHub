# Trading Bot Signal Addon

PHP implementation of the trading bot signal fetcher - replicates the Python `signals_trading_bot` functionality.

## Features

- **Firebase Integration**: Fetches signals and notifications from Firebase Firestore
- **Real-time Processing**: Listens for new signals and processes them automatically
- **Multiple Listeners**: Supports NotificationListener, SpotSignalListener, and FuturesSignalListener
- **Background Worker**: Runs as a continuous service to fetch and process signals
- **Signal Integration**: Automatically converts Firebase signals into channel messages for the signal system

## Installation

1. **Install Firebase PHP SDK**:
```bash
composer require kreait/firebase-php
```

2. **Configure Firebase**:
Add to your `.env`:
```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
# OR
FIREBASE_CREDENTIALS_JSON={"type":"service_account",...}
```

3. **Dump autoload**:
```bash
composer dump-autoload
```

4. **Enable the addon**:
The addon should be automatically detected. Enable it via Admin → Addons.

## Usage

### Run Worker (Continuous Mode)
```bash
php artisan trading-bot:worker
```

### Run Worker Once
```bash
php artisan trading-bot:worker --once
```

### Sync All Data (One-time)
```bash
php artisan trading-bot:sync --all
```

### Sync Only Notifications
```bash
php artisan trading-bot:sync --notifications
```

### Sync Only Signals
```bash
php artisan trading-bot:sync --signals
```

## Production Deployment

For production, run the worker as a daemon using Supervisor or systemd:

### Supervisor Configuration
```ini
[program:trading-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan trading-bot:worker --interval=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/logs/trading-bot-worker.log
```

### Systemd Service
```ini
[Unit]
Description=Trading Bot Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php artisan trading-bot:worker --interval=90
Restart=always

[Install]
WantedBy=multi-user.target
```

## Configuration

Configuration is in `config/trading-bot.php` and can be overridden via `.env`:

- `TRADING_BOT_POLLING_INTERVAL`: Polling interval in seconds (default: 90)
- `TRADING_BOT_BATCH_SIZE`: Number of items to fetch per batch (default: 300)
- `FIREBASE_NOTIFICATIONS_COLLECTION`: Firebase collection name for notifications (default: 'notifications')
- `FIREBASE_SIGNALS_COLLECTION`: Firebase collection name for signals (default: 'signals')
- `TRADING_BOT_LISTENERS_ENABLED`: Enable/disable listeners (default: true)

## Architecture

- **FirebaseService**: Handles Firebase connection and data fetching
- **SignalProcessorService**: Processes signals and converts them to channel messages
- **Listeners**: Handle different types of signals (NotificationListener, SpotSignalListener, FuturesSignalListener)
- **Commands**: Console commands for running the worker and syncing data

## Integration

The addon automatically creates a `trading_bot` channel source and integrates with the multi-channel signal addon. Signals fetched from Firebase are converted to channel messages and processed through the existing signal parsing pipeline.

## License

Same as main application.

