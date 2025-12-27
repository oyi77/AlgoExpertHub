# AlgoExpertHub Trading Signal Platform

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/oyi77/AlgoExpertHub)

## Overview

**AlgoExpertHub** is a comprehensive Laravel-based subscription platform for distributing trading signals across multiple asset markets (Forex, Crypto, Stocks). It features automated signal ingestion, AI-powered analysis, and automated trade execution.

For detailed documentation, please visit the **[Documentation Index](./docs/README.md)**.

## Key Features

- **Multi-Plan Subscription System**
- **Automated Signal Ingestion** (Telegram, API, RSS, Web)
- **AI-Powered Market Analysis** (OpenAI, Gemini, OpenRouter)
- **Automated Trade Execution** (CCXT, MetaApi)
- **Copy Trading System**
- **Risk Management Presets**
- **Modular Addon Architecture**

## Quick Links

- **[Architecture Overview](./docs/architecture/overview.md)**
- **[Installation Guide](./docs/deployment/general-guide.md)**
- **[Docker Deployment](./docs/deployment/docker.md)**
- **[Developer Onboarding](./docs/development/onboarding.md)**
- **[API Reference](./docs/api/reference.md)**

## Technology Stack

- **Backend**: Laravel 10.x, PHP 8.1+
- **Database**: MySQL 5.7+
- **Queue**: Database/Redis
- **Frontend**: Blade, Bootstrap 4, jQuery
- **Real-time**: WebSocket (Soketi)

## Project Structure

The project follows a modular structure with core application code in `main/app` and features encapsulated in `main/addons`.

```
public_html/
├── main/
│   ├── app/               # Core application logic
│   ├── addons/            # Modular feature packages
│   ├── config/            # Configuration
│   └── ...
├── docs/                  # Detailed documentation
├── docker/                # Docker configuration
└── specs/                 # Feature specifications
```

## Documentation

We maintain comprehensive documentation in the `docs/` directory:

- 🏗️ **[Architecture](./docs/architecture/overview.md)**
- ⚙️ **[Core Workflows](./docs/core/end-to-end-trading-flow.md)**
- 🧩 **[Addons](./docs/addons/trading-management/user-guide.md)**
- 💻 **[Development](./docs/development/onboarding.md)**
- 🚀 **[Deployment](./docs/deployment/docker.md)**

## Support

- **Issues**: Open an issue in the repository
- **Wiki**: See the [Documentation Index](./docs/README.md)

---

**Built with ❤️ using Laravel**
