<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Providers

## Purpose
Service providers bootstrap and configure application services, register bindings, and set up event listeners. The `AppServiceProvider` conditionally registers addon service providers based on their activation status. Providers are registered in `config/app.php` and loaded during application boot.

## Key Files

| File | Purpose |
|---|---|
| `AppServiceProvider.php` | Core provider. Registers addon service providers conditionally via `AddonRegistry::active()`. Singletons: `QueueOptimizer`. Boot: unguards models, enables query logging (when `LOG_QUERIES=true`), global view composer for SEO meta tags, SIGPIPE signal handling for Swoole/Octane. |
| `RouteServiceProvider.php` | Route configuration: defines `web` and `api` route prefixes, middleware groups, namespace bindings, and rate limiting. |
| `EventServiceProvider.php` | Event-listener mappings and observer registrations for model events and application events. |
| `AuthServiceProvider.php` | Authentication policies and gates registration. Defines authorization rules for admin/user access control. |
| `BroadcastServiceProvider.php` | Broadcasting channel authorization for real-time WebSocket events. |
| `CacheServiceProvider.php` | Custom cache configuration, cache store bindings, and tag support registration. |
| `HorizonServiceProvider.php` | Laravel Horizon configuration for Redis queue dashboard, supervisor settings, and queue worker management. |

## For AI Agents

### Working In This Directory
- `AppServiceProvider` is the primary provider; add new singleton bindings in `register()` and boot-time setup in `boot()`
- Addon providers are registered conditionally: only if `class_exists($providerClass)` AND `AddonRegistry::active($addonSlug)`. Follow this pattern for new addons.
- Addon provider map is defined in `registerAddonServiceProviders()` with slug-to-class mapping
- Query logging is opt-in via `LOG_QUERIES` env variable; uses `DB::listen()` with full SQL reconstruction
- Global view composer ensures `$page` variable is always available in Blade views
- `Model::unguard()` is called in boot - all models allow mass assignment globally

### Common Patterns
- Singleton binding: `$this->app->singleton(ClassName::class)` for services shared across the app
- Conditional registration: check `class_exists()` before registering providers that may not be installed
- Addon slug convention: kebab-case matching addon directory names (e.g., `trading-management-addon`)
- Module toggling: `AddonRegistry::moduleEnabled($slug, $module)` for fine-grained feature control within addons
- Safe scheduling: `scheduleCommandSafe()` in Console\Kernel wraps command scheduling in try-catch for graceful handling of missing commands

## Dependencies

### Internal
- `App\Support\AddonRegistry` - Addon activation status and module toggle checks
- `App\Services\QueueOptimizer` - Registered as singleton in AppServiceProvider
- `Addons\*\AddonServiceProvider` - Up to 9 addon providers conditionally registered (AiConnection, MultiChannelSignal, TradingBotSignal, TradingManagement, OpenRouterIntegration, PageBuilder, AlgoExpertPlus)

### External
- `illuminate/support` - ServiceProvider base class
- `illuminate/database` - Model::unguard(), DB query listener
- `illuminate/pagination` - Paginator::useBootstrap()
- `laravel/horizon` - HorizonServiceProvider for queue management
- `artesaos/seotools` - SEO Meta and OpenGraph facades (conditionally loaded)
- `spatie/laravel-backup` - Backup commands (conditionally scheduled)
