# OpenMemory Guide

## Overview
- AlgoExpertHub is a Laravel 10 / PHP 8.1+ trading-signal SaaS delivering Forex, Crypto, and Stocks signals with automation for ingestion, AI analysis, execution, and copy trading.
- Core tree: `main/` (Laravel app, addons, configs, tests), `docs/` (architecture + domain knowledge), `openspec/` (spec-driven workflow), `docker/` (container stack).
- Key infrastructure: MySQL primary DB, database/Redis queues, Soketi WebSocket layer, Docker deployment with PHP container `1Panel-php8-mrTy`.

## Architecture
- Layered stack: Blade/Javascript presentation → controllers/middleware/form requests → service layer (business logic) → jobs/observers → Eloquent/data stores.
- Modular addon system under `main/addons/` with manifests (`addon.json`), dedicated service providers, module toggles, and dependency declarations managed by `AddonRegistry`.
- Major addons:
  - `trading-management-addon`: data providers, filtering, AI analysis, risk, execution, monitoring, copy trading, backtesting, exchange connections, marketplace, trading bot.
  - `multi-channel-signal-addon`: admin/user UIs plus processing pipeline for Telegram, MTProto, API, Web, RSS sources with parsing + draft signal creation.
  - `ai-connection-addon`: centralized provider registry (OpenAI, Gemini, OpenRouter) with rotation, rate limiting, usage tracking.
  - Additional: page-builder, openrouter integration, trading-bot signal, algoexpert plus.
- Specs + planning: `.cursor/rules` enforce architecture, `.kiro/steering` and `.sdd` docs govern spec-driven development, while `openspec/` houses active proposals (`changes/`) and canonical capabilities (`specs/`).

## Components
- Services pattern: controllers delegate to `App\Services\*` for signal publishing, payments, plan management, Telegram messaging, etc.
- Queue + job ecosystem: background parsing (`ProcessChannelMessage`), execution engine jobs (including `ExecutionJob` for signal/bot execution with paper trading support via `createVirtualPosition`), email + subscriber sends, with scheduled tasks defined in `app/Console/Kernel.php`.
- Payments subsystem: `PaymentService`, `Gateway` models, manual + automated gateways (PayPal, Stripe, Paystack, Coinpayments, Nowpayments, Paghiper, etc.), wallet + subscription activation flow, transaction logging.
- Authentication/authorization: dual guards (`web`, `admin`), Spatie permissions, middleware stack enforcing inactivity, email verification, 2FA, KYC, demo mode restrictions.
- Notifications: database, email, Telegram channels, plus addon-specific push (execution, tickets, KYC).

## Patterns
- Follow service-layer discipline, keep controllers thin, perform validation via Form Requests, queue anything >2s, and never modify core when extending addons.
- Specs-first workflow: create/validate OpenSpec proposals (`openspec list`, `openspec spec list`) before implementation; tasks tracked via beads (`bd`) or Cursor fallback; `.sdd` and `.kiro` tooling orchestrate requirements/design/tasks.
- Security defaults: encrypt credentials, log financial activity, enforce CSRF/XSS protections, run artisan commands inside Docker (`docker exec 1Panel-php8-mrTy php artisan ...`), avoid secrets in repo.
- Testing expectations: `php artisan test`, focus on trading-critical flows, leverage factories/seeders for addon isolation.

## User Defined Namespaces
- (none defined yet)

