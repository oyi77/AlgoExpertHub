# Trading Bot Signal Addon

**Version:** 1.0.0

The Trading Bot Signal Addon is a PHP implementation that replicates the functionality of the Python-based `signals_trading_bot`. It bridges the gap between Firebase-based signals and the AlgoExpertHub signal processing pipeline.

## Features

- **Firebase Integration**: Directly fetches signals and notifications from Firebase Firestore.
- **Real-time Processing**: Continuously listens for new signals.
- **Multiple Listeners**:
    - `NotificationListener`: Handles general notifications.
    - `SpotSignalListener`: Processes spot market signals.
    - `FuturesSignalListener`: Processes futures market signals.
- **Background Worker**: Designed to run as a daemon (via Supervisor/Systemd).
- **Auto-Integration**: Automatically converts Firebase signals into internal channel messages for the `multi-channel-signal-addon`.

## Configuration

Configuration is located in `config/trading-bot.php` and managed via environment variables:

| Environment Variable | Description | Default |
|----------------------|-------------|---------|
| `TRADING_BOT_POLLING_INTERVAL` | Seconds between polls | `90` |
| `TRADING_BOT_BATCH_SIZE` | Items to fetch per batch | `300` |
| `FIREBASE_PROJECT_ID` | Firebase Project ID | - |
| `FIREBASE_CREDENTIALS_PATH` | Path to JSON credentials | - |
| `FIREBASE_NOTIFICATIONS_COLLECTION` | Firestore collection for notifications | `notifications` |
| `FIREBASE_SIGNALS_COLLECTION` | Firestore collection for signals | `signals` |

## Architecture

1.  **FirebaseService**: Manages authenticated connections to Google Firebase.
2.  **SignalProcessorService**: The core logic that transforms raw Firestore documents into standardized internal Signal objects.
3.  **Listeners**: Specialized classes that handle specific signal types (Spot vs Futures).
4.  **Commands**: Artisan commands to control the worker process.

## Usage

### Continuous Worker (Production)

The worker should be run as a background process.

```bash
php artisan trading-bot:worker
```

**Supervisor Configuration Example:**

```ini
[program:trading-bot-worker]
command=php /path/to/artisan trading-bot:worker --interval=90
autostart=true
autorestart=true
user=www-data
numprocs=1
```

### Manual Sync (Development/Debugging)

Sync all data once:
```bash
php artisan trading-bot:sync --all
```

Sync only specific types:
```bash
php artisan trading-bot:sync --notifications
php artisan trading-bot:sync --signals
```

## Integration Flow

1.  **Ingestion**: Worker fetches new documents from Firestore.
2.  **Transformation**: Documents are normalized into a standard array format.
3.  **Dispatch**: The addon dispatches these signals to the `MultiChannelSignal` system.
4.  **Execution**: The main trading system picks up these signals for execution (if auto-trading is enabled).
