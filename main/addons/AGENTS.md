<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Addons / Module System

## Purpose
Self-contained feature modules that extend the core application. Each addon follows a mini-app structure with its own models, routes, migrations, services, and views. Addons are registered via `App\Support\AddonRegistry` and can be conditionally activated. The `addon.json` manifest in each addon defines metadata and dependencies.

## Key Files

| File | Purpose |
|------|---------|
| `addon.json` (per addon) | Addon manifest: name, version, description, dependencies |
| `AddonServiceProvider.php` (per addon) | Registers addon routes, views, migrations in the container |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `ai-connection-addon/` | AI provider connections -- manage API keys, model profiles, usage analytics |
| `algoexpert-plus-addon/` | Premium subscription tier features |
| `multi-channel-signal-addon/` | Multi-source signal ingestion: Telegram channels, channel sources, message parsing, signal distribution |
| `openrouter-integration-addon/` | OpenRouter LLM gateway integration with shared models |
| `page-builder-addon/` | Drag-and-drop page/landing page builder |
| `trading-bot-signal-addon/` | Automated trading bot signal generation |
| `trading-management-addon/` | Full trading management: AI analysis, backtesting, execution, copy trading, marketplace, filter strategies, prebuilt bots. Has `Modules/` sub-architecture |
| `_deprecated/` | Retired addons kept for reference |

## For AI Agents

### Working In This Directory
- Each addon is a self-contained Laravel package -- treat it like a mini-app
- Check `addon.json` before modifying an addon to understand its version and dependencies
- Addons are registered in `App\Support\AddonRegistry::active('addon-name')` -- check this before assuming an addon is available
- The `trading-management-addon` uses a `Modules/` sub-architecture (modular monolith pattern)
- Namespace convention: `Addons\{AddonName}\...`
- Addon database tables are in addon-specific migration directories
- Conditional seeder usage in `DatabaseSeeder.php` wraps addon seeders in try/catch with `class_exists()` + `AddonRegistry::active()`

### Common Patterns
- Each addon has: `app/`, `database/`, `routes/`, `resources/`, `config/` (mirroring Laravel structure)
- Service providers auto-discover via package auto-discovery or manual registration
- Migration files prefixed with addon name to avoid collisions
- Addon routes typically prefixed with addon-specific path segments

## Dependencies

### Internal
- `app/Support/AddonRegistry.php` -- Activation/registration system
- `app/Models/` -- Core models (Signal, Plan, User) are extended or referenced by addons
- `app/Services/Addons/` -- Addon-related core services
- `database/seeders/DatabaseSeeder.php` -- Conditional addon seeder invocation

### External
- `laravel/framework` -- Service provider, facades, routing
- Individual addon dependencies vary (e.g., Telegram MTProto SDK, OpenRouter API)
