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