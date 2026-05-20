# AlgoExpertHub

## Purpose

Laravel-based subscription trading platform for distributing trading signals across multiple asset markets (Forex, Crypto, Stocks). Features automated signal ingestion from multiple channels, AI-powered market analysis, automated trade execution via exchange APIs, and a modular addon architecture.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 10.x |
| Language | PHP 8.1+ |
| Database | MySQL 5.7+ |
| Cache/Queue | Redis |
| Frontend | Blade, Bootstrap 4, jQuery |
| Real-time | WebSocket (Soketi) |
| Asset Build | Laravel Mix / Webpack |
| Testing | PHPUnit |
| Task Tracking | bd (beads) |

## Key Directories

| Directory | Purpose |
|-----------|---------|
| `main/` | Laravel application root (app, config, routes, database, resources, tests) |
| `main/app/` | Core application code: Services, Models, Controllers, Jobs, Events |
| `main/addons/` | Modular feature packages (trading-management, ai-connection, copy-trading, etc.) |
| `main/config/` | Laravel configuration files |
| `main/database/` | Migrations, seeders, factories |
| `main/resources/` | Blade views, JS, CSS, lang files |
| `main/routes/` | Route definitions (web, admin, api, console) |
| `main/tests/` | PHPUnit feature and unit tests |
| `docs/` | Project documentation (architecture, API, deployment, development guides) |
| `docker/` | Docker configuration (nginx, mysql, entrypoint, supervisord) |
| `install/` | Web-based installation wizard (database setup, permissions, seeding) |
| `scripts/` | Shell utility scripts (deployment, cleanup, monitoring) |
| `openspec/` | OpenSpec change proposals and feature specifications |
| `public/` | Publicly accessible static assets (CSS) |

## AI Agent Instructions

### Project-Wide Rules
- This is a **Laravel 10 application** with PHP 8.1+. Follow PSR-12 coding standards.
- Business logic belongs in **Service classes** under `main/app/Services/`, never in controllers.
- Controllers are thin HTTP handlers that delegate to services.
- Use `declare(strict_types=1)` in all PHP files.
- Validate input via Form Requests (`main/app/Http/Requests/`).
- Queue any operation that takes >2 seconds as a Job (`main/app/Jobs/`).

### Issue Tracking
Use **bd (beads)** for all task tracking. Do NOT use markdown TODOs or task lists.
```bash
bd ready --json          # Check ready work
bd create "Title" -t bug|feature|task -p 0-4 --json
bd close bd-42 --reason "Done" --json
```

### Naming Conventions
- Models: Singular PascalCase (`User`, `TradingSignal`)
- Tables: Plural snake_case (`users`, `trading_signals`)
- Foreign keys: `{table}_id` (`user_id`, `signal_id`)
- Controllers: PascalCase + `Controller` suffix
- Services: PascalCase + `Service` suffix

### Security
- Encrypt sensitive data (API keys, credentials) using `encrypt()`
- Validate ALL input through Form Requests
- Use middleware for auth, permissions, 2FA, KYC
- Never commit `.env` files or API keys

### Trading-Specific Rules
- Financial calculations must use precise decimal arithmetic
- Signals must be published before distribution
- One active subscription per user (`is_current=1`)
- All financial activities logged in transactions table
- Demo mode prevents destructive actions

### Documentation
- Architecture: `docs/architecture/overview.md`
- Installation: `docs/deployment/general-guide.md`
- Docker: `docs/deployment/docker.md`
- API Reference: `docs/api/reference.md`
- Developer Onboarding: `docs/development/onboarding.md`
- Addon Development: `docs/development/Addon Development.md`

## Dependencies

### External Packages
- **Laravel 10.x** — Core framework
- **CCXT** — Cryptocurrency exchange trading library
- **MetaApi** — MetaTrader integration
- **OpenAI / Gemini / OpenRouter** — AI-powered market analysis
- **Horizon** — Redis queue dashboard
- **Laravel Octane** — High-performance server
- **Google 2FA** — Two-factor authentication
- **Scribe** — API documentation generation
