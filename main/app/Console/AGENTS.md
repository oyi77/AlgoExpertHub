<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Console

## Purpose
Artisan console commands and scheduled task definitions. The `Kernel` defines the application's cron schedule (cache warming, queue monitoring, log rotation, RSS/web-scrape channel processing, Horizon management, trading bot workers, position monitoring). Commands in `Commands/` provide CLI tools for system administration, monitoring, and data processing.

## Key Files

| File | Purpose |
|---|---|
| `Kernel.php` | Schedule definition and command registration. Loads all commands from `Commands/` directory. Defines scheduled tasks with addon-aware conditional scheduling via `AddonRegistry::active()` and `AddonRegistry::moduleEnabled()`. Includes `scheduleCommandSafe()` helper for graceful handling of missing commands. |

## Subdirectories

| Directory | Purpose |
|---|---|
| `Commands/` | 14 Artisan commands for system administration and processing |

## Command Reference

| Command File | Signature | Purpose |
|---|---|---|
| `QueueManagementCommand.php` | `queue:manage {action}` | Queue monitoring, scaling, health checks, metrics, and cache clearing. Actions: monitor, scale, health, metrics, clear. Supports `--queue`, `--workers`, `--json` options. |
| `CacheStatsCommand.php` | `cache:stats` | Display cache hit/miss rates, memory usage, and key distribution |
| `WarmCacheCommand.php` | `cache:warm` | Pre-populate cache with frequently accessed data (plans, configs, signals) |
| `CleanupMetricsCommand.php` | `metrics:cleanup` | Purge old system metrics and analytics data |
| `MonitorSystemCommand.php` | `system:monitor` | CPU, memory, disk, and database health check with alerting |
| `MonitorTradingConnectionsCommand.php` | `trading:monitor-connections` | Test connectivity to configured exchange/broker endpoints |
| `InstallDatabase.php` | `db:install` | Run migrations, seeders, and initial configuration setup |
| `RotateLogsCommand.php` | `logs:rotate` | Rotate and compress log files. Supports `--max-lines` option. |
| `HorizonSupervisor.php` | `horizon:supervisor` | Custom Horizon supervisor for queue worker process management |
| `ProcessRssChannels.php` | `channel:process-rss` | Fetch and parse RSS feeds from configured channel sources |
| `ProcessWebScrapeChannels.php` | `channel:process-web-scrape` | Scrape web pages for signal data from configured sources |
| `CleanupDuplicateSections.php` | `sections:cleanup-duplicates` | Remove duplicate page sections |
| `FindDuplicateSections.php` | `sections:find-duplicates` | Report duplicate page sections without deleting |

## Schedule Overview (from Kernel.php)

| Frequency | Command | Condition |
|---|---|---|
| Hourly | `cache:warm` | Always |
| Daily 02:00 | `cache:clear` | Always |
| Every 5 min | `queue:manage monitor` | Always |
| Every 10 min | `queue:manage scale` | Always |
| Hourly | `logs:rotate --max-lines=1000` | Always |
| Every 10 min | `channel:process-rss` | `multi-channel-signal-addon` active |
| Every 1 min | `channel:process-web-scrape` | `multi-channel-signal-addon` active |
| Every 5 min | `channel:process-telegram-mtproto` | `multi-channel-signal-addon` active + MTProto class exists |
| Every 2 min | `channel:process-trading-bot` | `multi-channel-signal-addon` active |
| Every 1 min | Trading bot worker monitoring | `trading-management-addon` active |
| Every 5 min | Exchange connection health | `trading-management-addon` active |
| Every 5 min | `horizon:snapshot` | Redis queue + Horizon available |
| Every 3 min | `horizon:monitor` | Horizon cron supervisor enabled |
| Daily 02:00 | `backup:run` | `algoexpert-plus-addon` active + Spatie Backup |
| Weekly Mon 03:00 | `backup:clean` | `algoexpert-plus-addon` active + Spatie Backup |
| Every 5 min | Stream health monitoring | `trading-management-addon` + data_provider module |
| Every 1 min | Position monitoring | `trading-management-addon` + execution module |
| Daily 00:00 | Analytics update | `trading-management-addon` + execution module |
| Daily 01:00 | Performance scores update | `trading-management-addon` + risk_management module |
| Every 5 min | Drawdown monitoring | `trading-management-addon` + risk_management module |
| Weekly Sun 03:00 | Model retraining | `trading-management-addon` + risk_management module |
| Every 30 sec | Internal position monitoring | `MonitorInternalPositions` class exists |

## For AI Agents

### Working In This Directory
- New commands should extend `Illuminate\Console\Command` and implement `handle(): int` (return 0 for success, 1 for failure)
- Place new commands in `Commands/` directory; they are auto-discovered via `$this->load(__DIR__.'/Commands')` in Kernel
- Schedule new commands in `Kernel::schedule()` with addon-aware guards using `AddonRegistry::active()` and `class_exists()` checks
- Use `scheduleCommandSafe()` for commands that may not be installed in all environments
- Commands that interact with addons must verify addon activation before executing

### Common Patterns
- Command signature: `artisan:verb-noun {argument} {--option}` (e.g., `queue:manage {action}`, `logs:rotate --max-lines=1000`)
- JSON output support: check `$this->option('json')` and output via `$this->line(json_encode(...))`
- Table display: use `$this->table($headers, $rows)` for formatted CLI output
- Progress feedback: `$this->info()`, `$this->warn()`, `$this->error()` for colored terminal output
- Dependency injection: receive services via constructor (e.g., `QueueOptimizer` in `QueueManagementCommand`)
- Return codes: `0` = success, `1` = failure/usage error

## Dependencies

### Internal
- `App\Services\QueueOptimizer` - Queue health monitoring and worker scaling
- `App\Services\BacktestingService` - Backtest execution (via RunBacktestJob)
- `App\Support\AddonRegistry` - Addon activation and module toggle checks
- `App\Jobs\*` - Jobs dispatched by scheduled tasks (MonitorInternalPositions, trading bot workers)
- `Addons\*\App\Console\Commands\*` - Addon-specific commands loaded conditionally

### External
- `illuminate/console` - Command base class, Schedule builder
- `illuminate/support` - Cache facade, Log facade
- `laravel/horizon` - Horizon commands (`horizon:snapshot`, `horizon:monitor`)
- `spatie/laravel-backup` - Backup commands (`backup:run`, `backup:clean`)
