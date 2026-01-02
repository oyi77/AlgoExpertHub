# Performance Playbook

## Runtime Configuration
- Set `APP_DEBUG=false`
- Set `QUEUE_CONNECTION=database` or `redis`
- Set `CACHE_DRIVER=redis` if Redis available
- Run `php artisan config:cache`, `route:cache`, `view:cache`

## Queue Workers
- Start `notifications` queue workers via Supervisor
- Recommended: `--sleep=3 --tries=3`

## Database
- Ensure migration applied adding indexes and uniqueness
- Monitor slow queries and add covering indexes as needed

## Distribution Pipeline
- Signal publish dispatches `DistributeSignalJob`
- Fan-out per channel using `SendChannelMessageJob`
- Idempotent inserts protect against duplicates

## Observability
- Add queue/job metrics, failed job monitoring
- Enable error tracking for delivery failures

