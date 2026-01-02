<<<<<<< HEAD
# Documentation Index

Welcome to the AlgoExpertHub documentation. This guide will help you navigate the comprehensive documentation available for the platform.

## 🏗️ Architecture
- **[Architecture Overview](./architecture/overview.md)** - High-level system design, layered architecture, and tech stack.
- **[Addon System](../.cursor/rules/addon-system.mdc)** - Guide to the modular addon architecture.
- **[Database Schema](./development/database-schema.md)** - Database structure and relationships.

## ⚙️ Core Workflows
- **[End-to-End Trading Flow](./core/end-to-end-trading-flow.md)** - Complete lifecycle of a trade from signal to execution.
- **[Trading Execution Flow](./core/trading-execution-flow.md)** - Detailed automated execution process.
- **[Multi-Channel Signal Ingestion](./core/multi-channel-signal-ingestion.md)** - How signals are imported from external sources.
- **[Payment & Subscriptions](./core/payment-gateway-integration.md)** - Payment processing and subscription management.
- **[Market Closed Handling](./core/market-closed-handling.md)** - How the system handles market hours.

## 🧩 Addons & Features
- **[Trading Management](./addons/trading-management/user-guide.md)** - Unified trading system guide.
- **[AI Trading](./addons/ai-trading.md)** - AI-powered market analysis and confirmation.
- **[AI Connections](./addons/ai-connection.md)** - Managing AI providers (OpenAI, Gemini).
- **[OpenRouter Integration](./addons/openrouter-integration.md)** - Using OpenRouter for AI models.
- **[Copy Trading](./addons/copy-trading.md)** - Social trading system.
- **[Trading Presets](./addons/trading-presets.md)** - Risk management and position sizing.
- **[Filter Strategies](./addons/filter-strategy.md)** - Technical indicator filtering.
- **[Page Builder](./addons/page-builder.md)** - Using the visual page builder.

## 💻 Development
- **[Developer Onboarding](./development/onboarding.md)** - Quick start guide for new developers.
- **[Theme Development](./development/theme-development.md)** - Creating custom frontend themes.
- **[Performance Optimization](./development/performance-optimization.md)** - Implementation details for performance.
- **[Performance Playbook](./development/performance-playbook.md)** - Performance best practices and playbook.
- **[Coding Standards](./development/coding-standards.md)** - Code style and standards guidelines.
- **[Security Practices](./development/security-practices.md)** - Security best practices.
- **[Testing Strategy](./development/testing-strategy.md)** - Testing approach and guidelines.
- **[Database Schema](./development/database-schema.md)** - Reference for database tables.
- **[Livewire Components](./development/livewire-components.md)** - Livewire component documentation.
- **[Package Implementation Status](./development/package-implementation-status.md)** - Status of package implementations.
- **[Implementation Summary](./development/implementation-summary.md)** - Summary of implementations.
- **[Language Management](./development/language-management-improvements.md)** - Language management improvements.
- **[Environment Configuration](./development/env-configuration-translation.md)** - Environment configuration guide.
- **[Troubleshooting](./development/troubleshooting.md)** - Common issues and solutions.

## 🚀 Deployment
- **[Docker Deployment](./deployment/docker.md)** - Complete guide to containerized deployment.
- **[General Deployment Guide](./deployment/general-guide.md)** - Manual deployment on VPS/Shared hosting.
- **[Wiki Deployment](./deployment/wiki.md)** - Deploying the documentation wiki.
- **[GitHub Wiki](./deployment/github-wiki.md)** - Syncing with GitHub Wiki.

## 🔌 API Reference
- **[API Reference](./api/reference.md)** - Comprehensive API documentation.
- **[Trading Management API](./api/trading-management.md)** - Specific endpoints for trading management.

## 👥 User Guides
- **[User Guide Overview](./user-guides/overview.md)** - General platform usage guide.

## 🔄 Migration
- **[Laravel 10 Upgrade](./migration/laravel-10-upgrade.md)** - Summary of the upgrade to Laravel 10.
- **[Laravel Notify Migration](./migration/laravel-notify-migration.md)** - Complete migration guide for Laravel Notify.
- **[Laravel Notify Migration Audit](./migration/laravel-notify-migration-audit.md)** - Detailed audit report.
- **[Laravel Notify Migration Summary](./migration/laravel-notify-migration-summary.md)** - Final migration summary.
- **[Deprecated Addons](./migration/deprecated-addons.md)** - Guide for migrating from deprecated addons.

## 📚 Archives
- **[Trading Refactor 2025](./archive/trading-refactor-2025/)** - Analysis and design docs for the trading system refactor.
=======
# AlgoExpertHub Documentation

Welcome to the AlgoExpertHub documentation. This guide will help you navigate through all available documentation.

## 📚 Documentation Structure

### 🐳 Docker & Deployment

**Location:** `docker-deployment/`

- **[Docker Deployment Guide](docker-deployment/DOCKER_DEPLOYMENT_GUIDE.md)** - Complete guide for Docker setup, deployment methods, CI/CD, and production deployment

### 📈 Trading System

**Location:** `trading-system/`

- **[Trading Execution Flow](trading-system/trading-execution-flow.md)** - Complete automated trade execution guide
- **[Trading Presets](trading-system/trading-presets.md)** - Risk management and position sizing
- **[Copy Trading System](trading-system/copy-trading-system.md)** - Social trading implementation
- **[Filter Strategy Guide](trading-system/filter-strategy-guide.md)** - Technical indicator filtering
- **[Trading Bot Signal Addon](trading-system/trading-bot-signal-addon.md)** - Firebase signal integration
- **[Trading Management](trading-system/trading-management-*.md)** - Trading addon consolidation docs

### 🔌 API & Integration

**Location:** `api-integration/`

- **[Multi-Channel Signal Ingestion](api-integration/multi-channel-signal-ingestion.md)** - Automatic signal import from Telegram, APIs, RSS
- **[OpenRouter Integration](api-integration/openrouter-integration.md)** - AI model integration (400+ models)
- **[API Documentation](api-integration/api-*.md)** - REST API, WebSocket, and Webhook docs

### 💻 Development Guides

**Location:** `development-guides/`

- **[AlgoExpert++ System](development-guides/algoexpert-plus-addon.md)** - SEO, Queues, Backups, Health monitoring
- **[Theme Development](development-guides/theme-development.md)** - Creating custom themes
- **[Database Schema Reference](development-guides/database-schema-reference.md)** - Complete database structure
- **[Troubleshooting Guide](development-guides/troubleshooting-guide.md)** - Common issues and solutions
- **[Deployment Guide](development-guides/deployment-guide.md)** - Traditional deployment methods

### 📦 Archived Documentation

**Location:** `archived/`

Historical documentation, session summaries, and analysis documents for reference.

---

## 🚀 Quick Start Guides

### For Deployment

1. **Docker Deployment (Recommended)**
   - Read: [Docker Deployment Guide](docker-deployment/DOCKER_DEPLOYMENT_GUIDE.md)
   - Quick start: 5 minutes with `docker-compose up -d`
   - Includes: Automated scripts, CI/CD, 1Panel integration

2. **Traditional Deployment**
   - Read: [Deployment Guide](development-guides/deployment-guide.md)
   - Manual setup with PHP, MySQL, Redis

### For Development

1. **Understanding the System**
   - Start with: [README.md](../README.md) in root
   - Architecture: [Database Schema](development-guides/database-schema-reference.md)
   - Trading flow: [Trading Execution Flow](trading-system/trading-execution-flow.md)

2. **Customization**
   - Themes: [Theme Development](development-guides/theme-development.md)
   - APIs: [API Integration](api-integration/)
   - Trading: [Trading System](trading-system/)

---

## 📖 Documentation by Use Case

### I want to deploy the application

→ **[Docker Deployment Guide](docker-deployment/DOCKER_DEPLOYMENT_GUIDE.md)**

Choose your method:
- Automated script: `./scripts/deployment/deploy.sh production`
- CI/CD: Push to GitHub (auto-deploy)
- 1Panel: `./scripts/deployment/deploy-1panel.sh`
- Manual: `docker-compose up -d`

### I want to understand trading features

→ **[Trading System Documentation](trading-system/)**

Key docs:
- [Trading Execution Flow](trading-system/trading-execution-flow.md)
- [Trading Presets](trading-system/trading-presets.md)
- [Copy Trading](trading-system/copy-trading-system.md)

### I want to integrate external signals

→ **[Multi-Channel Signal Ingestion](api-integration/multi-channel-signal-ingestion.md)**

Supports:
- Telegram channels (MTProto)
- REST APIs
- RSS feeds
- Web scraping

### I want to customize the UI

→ **[Theme Development](development-guides/theme-development.md)**

Learn how to:
- Create custom themes
- Modify layouts
- Add custom pages

### I'm having issues

→ **[Troubleshooting Guide](development-guides/troubleshooting-guide.md)**

Common issues:
- Database connection
- Queue not processing
- WebSocket errors
- Performance issues

---

## 🌐 GitHub Wiki Deployment

This documentation is structured for easy deployment to GitHub Wiki:

### Structure Mapping

```
docs/
├── Home.md (this file)
├── Docker-Deployment/
│   └── Complete-Guide.md
├── Trading-System/
│   ├── Execution-Flow.md
│   ├── Presets.md
│   └── Copy-Trading.md
├── API-Integration/
│   ├── Multi-Channel-Signals.md
│   └── OpenRouter.md
└── Development/
    ├── Theme-Development.md
    ├── Database-Schema.md
    └── Troubleshooting.md
```

### Deployment Script

Use `scripts/deploy-wiki.sh` to automatically deploy documentation to GitHub Wiki.

---

## 📱 Admin Panel Documentation

Documentation is also available in the admin panel:

**Access:** `/admin/documentation`

Categories:
- Docker & Deployment
- Trading System
- API Integration
- Development Guides

---

## 🔍 Search & Navigation

### By Topic

- **Docker**: `docker-deployment/`
- **Trading**: `trading-system/`
- **APIs**: `api-integration/`
- **Development**: `development-guides/`

### By Skill Level

- **Beginner**: Start with README.md and Quick Start guides
- **Intermediate**: Explore Trading System and API Integration
- **Advanced**: Development Guides and Database Schema

---

## 📝 Contributing to Documentation

When adding new documentation:

1. Place in appropriate category folder
2. Update this index (README.md)
3. Follow markdown formatting guidelines
4. Include code examples where applicable
5. Add to GitHub Wiki if public-facing

---

## 🆘 Support

- **Documentation Issues**: Open an issue on GitHub
- **Questions**: Check Troubleshooting Guide first
- **Feature Requests**: Submit via GitHub Issues

---

**Last Updated:** 2025-12-15  
**Version:** 2.0 (Reorganized Structure)
>>>>>>> main
