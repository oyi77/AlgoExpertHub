# AlgoExpertHub — AI-Powered Trading Signal Platform

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/oyi77/AlgoExpertHub)

## Overview

Laravel-based subscription platform for distributing trading signals across Forex, Crypto, and Stock markets. Features automated signal ingestion from Telegram/API/RSS, AI-powered analysis (OpenAI, Gemini), and automated trade execution via CCXT and MetaApi.

## Architecture

```
├── main/                  # Laravel application
│   ├── app/               # Core logic (Services, Models, Controllers)
│   ├── addons/            # Modular feature packages (8 addons)
│   ├── config/            # Configuration
│   ├── database/          # Migrations, seeders, factories
│   ├── routes/            # Web, API, console routes
│   ├── resources/         # Blade views, assets, lang
│   └── tests/             # Unit, Feature, Property tests
├── docs/                  # Documentation (architecture, API, deployment)
├── docker/                # Docker config (MySQL, Nginx, PHP)
├── install/               # Installation wizard
├── scripts/               # Deployment & utility scripts
├── openspec/              # OpenSpec change proposals
├── .omc/                  # Improvement plans & state
│   ├── plans/             # IMPROVEMENT_PLAN.md
│   └── state/             # improvement.json
└── AGENTS.md              # Hierarchical AI-readable docs (25 files)
```

## Key Features

| Feature | Description |
|---------|-------------|
| Signal Ingestion | Telegram, API, RSS, Web scraping via webhook endpoints |
| AI Parsing | Regex → OCR → AI (OpenAI/Gemini) parsing pipeline |
| Trade Execution | CCXT (crypto), MetaApi (MT4/MT5 forex) |
| Bot System | SIGNAL_BASED + MARKET_STREAM_BASED modes |
| Risk Management | Circuit breakers, position sizing, AI-adaptive risk |
| Multi-Channel Distribution | Dashboard, Telegram, WhatsApp, SMS, Email |
| Subscription Tiers | Free → Enterprise with Stripe billing |
| Addon Architecture | 8 toggleable modules (trading, AI, signals, etc.) |

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 10, PHP 8.1+ |
| Database | MySQL 8, Redis (queues + cache) |
| Frontend | Blade, Bootstrap 4, jQuery |
| Real-time | Soketi (WebSocket) |
| Deployment | Docker, Railway, traditional hosting |

## Quick Start

```bash
# Docker
docker-compose up -d

# Traditional
composer install
php artisan migrate --seed
php artisan serve
```

## Improvement Plan

A comprehensive audit was conducted on 2026-05-21. See [.omc/plans/IMPROVEMENT_PLAN.md](.omc/plans/IMPROVEMENT_PLAN.md) for the full 8-week plan covering:

| Phase | Focus | Severity |
|-------|-------|----------|
| Week 1 | Critical Security (SQL injection, mass assignment, webhook auth) | CRITICAL |
| Week 2 | High Security (XSS, CSP, OAuth password, debug mode) | HIGH |
| Week 3-4 | Trading Logic (rate limiting, price fetcher, circuit breakers, idempotency) | HIGH |
| Week 5-6 | Architecture (dedup services, addon cleanup, indexes, error handling) | MEDIUM |
| Week 7 | Testing & CI (GitHub Actions, PHPStan, coverage) | HIGH |
| Week 8 | Monitoring (audit trail, health dashboard, alerting) | MEDIUM |

## Documentation

- [Architecture Overview](docs/architecture/overview.md)
- [Installation Guide](docs/deployment/general-guide.md)
- [Docker Deployment](docs/deployment/docker.md)
- [Developer Onboarding](docs/development/onboarding.md)
- [API Reference](docs/api/reference.md)
- [Core Workflows](docs/core/end-to-end-trading-flow.md)
- [Addon Guide](docs/addons/trading-management/user-guide.md)
- [AGENTS.md Hierarchy](AGENTS.md) — AI-readable codebase documentation

## Support

- **Issues**: Open an issue in the repository
- **Wiki**: See the [Documentation Index](docs/README.md)

---

**Built with Laravel**
