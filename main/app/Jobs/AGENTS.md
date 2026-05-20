<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Jobs

## Purpose
Queued jobs for asynchronous and background processing. Handles signal distribution to subscribers, notification delivery across channels, backtest execution, subscription renewal, channel message processing, email sending, language translation, and internal position monitoring. Jobs are dispatched from Services and processed by Laravel Horizon.

## Key Files

| File | Purpose |
|---|---|
| `OptimizedJob.php` | Abstract base class for queue jobs; extends standard Laravel job with priority support, tagging, batch dispatch, and health monitoring hooks |
| `DistributeSignalJob.php` | Distributes published signals to eligible subscribers. Finds users via plan subscription joins, batches notification dispatch (1000 users/batch), bulk-inserts dashboard_signals and user_signals records. Extends `OptimizedJob`. 3 tries, 300s timeout. |
| `SendSignalNotificationJob.php` | Delivers individual signal notifications to a single user across enabled channels |
| `SendChannelMessageJob.php` | Routes signal delivery to specific channel (dashboard, whatsapp, telegram, email, sms) per user-plan combo. Queued on `notifications` queue. |
| `RunBacktestJob.php` | Executes backtest via `BacktestingService::runBacktest()`. Implements `ShouldQueue` directly (not OptimizedJob). 1 try, 600s timeout. Updates Backtest model status on completion/failure. |
| `ProcessChannelMessage.php` | Processes incoming messages from channel sources (RSS, Telegram, web scrape), parses signal data, and creates auto-signals |
| `ProcessSubscriptionRenewalsJob.php` | Checks for expiring subscriptions and triggers renewal reminders or auto-renewal |
| `MonitorSignalQualityJob.php` | Monitors signal performance metrics and quality scores |
| `MonitorInternalPositions.php` | Monitors internal broker positions every 30 seconds (scheduled via Kernel). Checks SL/TP hit, updates trade status. |
| `SendEmailJob.php` | Generic email dispatch job with template rendering |
| `SendSubscriberMail.php` | Newsletter/marketing email to subscriber lists |
| `TranslateLanguageJob.php` | Async translation of content to target languages via translation API |

## For AI Agents

### Working In This Directory
- New jobs should extend `OptimizedJob` for priority, tagging, and monitoring support; only use plain `ShouldQueue` for simple single-purpose jobs (like `RunBacktestJob`)
- Set `$tries`, `$timeout`, and `$priority` as class properties
- Implement `process()` method in `OptimizedJob` subclasses (not `handle()`)
- Implement `onFailure()` for permanent failure handling (logging, admin notification)
- Always log start, progress, and completion with structured context (`Log::info/error` with arrays)
- Use bulk inserts with chunking (500-1000 rows) for high-volume writes to avoid query size limits
- Dispatch child jobs via `QueueOptimizer::dispatchBatch()` for managed batch execution

### Common Patterns
- Job constructor receives IDs (not models) for serialization efficiency: `int $signalId`, not `Signal $signal`
- Database queries use raw `DB::table()` for performance in bulk operations; Eloquent for single-record operations
- Queue routing: `->onQueue('notifications')` for user-facing, default queue for background processing
- Failure handling: update model status to 'failed', log error with full trace, optionally dispatch admin notification
- Batch pattern: `array_chunk()` + loop with `insertOrIgnore()` for idempotent bulk writes
- Tags for monitoring: `$this->tags = ['signal-distribution', 'high-priority']`

## Dependencies

### Internal
- `App\Services\*` - Business logic delegated from jobs (`BacktestingService`, `QueueOptimizer`)
- `App\Models\*` - Eloquent models for data access (`Signal`, `User`, `Backtest`, `PlanSubscription`)
- `App\Services\QueueOptimizer` - Batch dispatch, health monitoring, worker scaling
- `App\Services\CacheManager` - Cache invalidation after data changes

### External
- `illuminate/queue` - Laravel queue infrastructure (ShouldQueue, InteractsWithQueue, SerializesModels)
- `illuminate/bus` - Bus dispatch and batch support
- `illuminate/support` - Facades (Log, DB, Cache)
- Laravel Horizon - Queue dashboard and supervisor (configured in `HorizonServiceProvider`)
