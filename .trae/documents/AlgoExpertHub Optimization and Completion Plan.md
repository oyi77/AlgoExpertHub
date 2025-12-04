## Current State Highlights
- Laravel 9 app with modular addons; heavy signal distribution and multi‑channel ingestion.
- Performance bottlenecks identified in `SignalService`:
  - Synchronous external calls per user: `ipapi` and SMS in `sendText` `main/app/Services/SignalService.php:344`.
  - Telegram polling via `getUpdates` and per‑chat cURL sends `main/app/Services/SignalService.php:359`.
  - Repeated config DB reads `Configuration::first()` `main/app/Services/SignalService.php:247,332`.
- Runtime defaults suggest non‑optimized production:
  - Queue default `sync` `main/config/queue.php:16`.
  - Cache default `file` `main/config/cache.php:18`.
- Autoload includes consolidated Trading Management addon, but directory is absent; consolidation status needs alignment with code.

## Performance Optimizations (Phase 1–2)
- Runtime configuration and caching
  - Set `APP_DEBUG=false`, enable OPCache, `php artisan config:cache`, `route:cache`, `view:cache`.
  - Switch to `QUEUE_CONNECTION=database` (or Redis) with Supervisor workers; separate queues: `notifications`, `ingestion`, `execution`.
  - Switch `CACHE_DRIVER` to `redis` (if available); otherwise keep `file` but add granular caches.
  - Reduce log overhead (`logging.php` channel `daily`, level `warning`).
- Async distribution pipeline
  - Extract `sendSignalToUser` into queued jobs: `DistributeSignalJob(plan_id, signal_id)` → chunk subscriptions (`chunkById(1000)`) and fan‑out `SendChannelMessageJob` per channel.
  - Add retry/backoff, idempotency (dedupe by `user_id/signal_id`). Use `insertOrIgnore` for dashboard/user signals.
- Telegram optimization
  - Replace `getUpdates` polling with stored `chat_id` on user; add `users.telegram_chat_id` and bot webhook to record IDs; send via SDK with batch sends.
- Phone/SMS/WhatsApp optimization
  - Remove `ipapi` calls; store `phone_country_code` and normalize E.164 at registration; reuse a single client; queue all sends.
- Config caching
  - Introduce `ConfigurationRepository` that caches `Configuration::first()` via `cache()->remember('config', ttl)`.
- Database performance
  - Add indexes: `sp_signals(is_published,published_date)`, `sp_plan_subscriptions(user_id,is_current,plan_expired_at)`, `sp_user_signals(user_id,signal_id)`, `sp_dashboard_signals(user_id,signal_id)`, `sp_payments(user_id,status)`.
  - Replace per‑row existence checks `dashboardSignal()->where('signal_id')` with bulk `insertOrIgnore`.
- Eloquent and N+1 fixes
  - Audit controllers/services for `with()` loading; enforce DTOs for read paths; prefer `select()` minimal columns.
- Frontend/asset delivery
  - Use `webpack.mix.js` for minification/versioning; HTTP/2, gzip/brotli; defer non‑critical JS; lazy‑load images; serve assets via CDN.
- Optional high‑throughput runtime
  - Consider Laravel Octane (Swoole/RoadRunner) for ingestion/execution queues and API endpoints.

## Architecture Cleanup (Phase 3)
- Align addon autoload mappings with actual directories; complete or remove unused mappings.
- Consolidate into domain modules with clear boundaries:
  - Signal Ingestion → Adapters (`TelegramAdapter`, `ApiAdapter`, `RssAdapter`, `WebScrapeAdapter`).
  - Parsing/Normalization → `ParsingPipeline`, `RegexMessageParser` → `DTOs/ParsedSignalData.php`.
  - AI Analysis → model routing (OpenRouter/Gemini/OpenAI) with throttling and caching.
  - Risk & Presets → centralized position sizing.
  - Execution → exchange/broker adapters, job orchestration.
  - Copy Trading → follower/leader mapping, scaling, fees.
  - Backtesting → historical data runner with result storage.
  - Marketplace → listings, purchases, licensing.
- Event‑driven pipeline
  - Domain events: `SignalPublished`, `SignalIngested`, `TradeExecuted`, `BacktestCompleted` → listeners dispatch jobs.

## Feature Completions (Phase 4–6)
- Trading Bots
  - Models: `Bot`, `BotVersion`, `BotConnection`; execution hooks and schedule; CCXT/MT5 integration.
- Trading Bots Marketplace
  - Listings, pricing, trial, licensing; purchase flow via existing gateways.
- AI Powered Trading
  - Strategy evaluators, ensemble voting; cache analyses; fallback models.
- Copy Trading
  - Implement follower portfolios, risk multipliers, fees; real‑time distribution and reconciliation.
- Manual Trading Executions
  - Dashboard to place market/limit orders with SL/TP; unified across adapters.
- AI Model Marketplace
  - `Model`, `InferencePlan`, metering via OpenRouter; rating and reviews.
- Signals Parser & Aggregator
  - Expand regex patterns; source deduplication; priority weighting; confidence scores.
- Data Trading Fetcher
  - Price feeds & OHLCV cache; providers with fallback; rate‑limit.
- Backtest Tools
  - Batch runner jobs; parameter sweeps; metrics (win rate, PF, max DD) stored.
- AI/ML Training Tools
  - Offline pipelines (specs only here): dataset curation, feature engineering, model training, export to inference.
- Buy/Sell Marketplaces
  - Product types: Signals, Indicators, Bots, Models; unify purchase + entitlement + delivery.

## Security & Compliance
- Secrets in `.env`; strict validation; KYC and GDPR alignment; 2FA; rate limiting; audit logs.
- Sanitize user HTML via `mews/purifier`; avoid double heavy cleans; cache sanitized fragments when feasible.

## Observability & Quality
- Add APM/Error tracking (Sentry/Bugsnag), DB slow query logs; queue metrics.
- Synthetic probes for ingestion and distribution; health endpoints.
- Automated tests: unit for adapters/parsers; integration for pipeline; performance smoke tests.

## Milestones & KPIs
- Phase 1 (1–2 days): Config/cache/queue, Telegram/phone fixes; p95 page TTFB ↓40%.
- Phase 2 (3–5 days): Job‑based distribution; message throughput ↑10x; failed jobs <0.1%.
- Phase 3 (3–5 days): DB indexes/N+1 fixes; signal publish → user delivery p95 <2s.
- Phase 4–6 (rolling): Feature completions; backtest throughput, marketplace launch.

## Risks & Rollback
- Each change gated behind feature flags; blue/green deploy; DB migrations with safe defaults; clear rollback scripts.

## Next Steps
- Proceed with Phase 1: apply runtime config, implement queued distribution, remove synchronous external calls, add critical indexes.
- After approval, I will implement changes, verify with test jobs and provide a performance baseline report.

Please confirm to proceed with Phase 1. 