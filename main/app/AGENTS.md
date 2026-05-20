<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Application Code

## Purpose
Core Laravel application code containing all business logic, HTTP handling, models, services, jobs, events, and providers. This is the heart of AlgoExpertHub -- a trading signal platform with plan-based subscriptions, payment processing, multi-channel signal distribution, AI integrations, and admin/user management.

## Key Files

| File | Purpose |
|------|---------|
| `Services/BaseService.php` | Abstract base class with shared helpers: `successResponse()`, `errorResponse()`, `executeInTransaction()`, pagination, search filters, caching |
| `Http/Kernel.php` | Middleware stack -- global (security headers, CORS, proxies), web group (CSRF, session, queue/responsive/realtime middleware), API group (throttle, versioning) |
| `Http/Controllers/Controller.php` | Base controller |
| `Models/Signal.php` | Central trading signal model with scopes, multi-TP support, channel source relations |
| `Providers/AppServiceProvider.php` | Application service provider |
| `Providers/RouteServiceProvider.php` | Route service provider |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `Actions/` | Single-responsibility action classes |
| `Adapters/` | External service adapters |
| `Console/` | Artisan commands and console Kernel |
| `Contracts/` | Interfaces and contracts |
| `DTOs/` | Data Transfer Objects |
| `Events/` | Event classes for the event/listener system |
| `Exceptions/` | Custom exception handler and classes |
| `Helpers/` | Helper/utility classes |
| `Http/Controllers/` | ~20 controllers: Admin, Auth, Backend, Frontend, Signal, Payment, Trading, User, API |
| `Http/Middleware/` | Custom middleware: security headers, API versioning, queue monitoring, responsive design, real-time feedback, cache response, onboarding, KYC, 2FA |
| `Http/Requests/` | Form request validation classes |
| `Http/Resources/` | API resource transformers |
| `Http/Traits/` | Shared HTTP traits |
| `Jobs/` | 12 queue jobs: signal distribution, email, translation, backtesting, subscription renewals, position monitoring |
| `Listeners/` | Event listeners |
| `Logging/` | Custom logging channels/handlers |
| `Mail/` | Mailable classes |
| `Models/` | 47 Eloquent models covering signals, plans, payments, users, admins, markets, currencies, backtests, analytics |
| `Notifications/` | Notification classes (database, mail, etc.) |
| `Parsers/` | Signal/text parsers |
| `Providers/` | 7 service providers: App, Auth, Broadcast, Cache, Event, Horizon, Route |
| `Repositories/` | Repository pattern implementations |
| `Services/` | ~45 service classes organized by domain: trading, signals, payments, admin, analytics, security, monitoring, gateway |
| `Support/` | Support classes (e.g., AddonRegistry) |
| `Traits/` | Reusable model/traits (e.g., Searchable) |
| `Utility/` | ElementBuilder, FormBuilder, SectionBuilder for dynamic UI generation |

## For AI Agents

### Working In This Directory
- All services extend `BaseService` -- use its `successResponse()`, `errorResponse()`, `executeInTransaction()` pattern
- Models use `HasFactory` and `Searchable` traits; most have query scopes (e.g., `scopePublished`, `scopeByMarket`)
- Signal model auto-generates random IDs in `booted()` via `rand(1111111, 99999999)`
- Controllers delegate business logic to Services -- do not put DB queries directly in controllers
- Jobs are queued via Laravel Horizon (`supervisor-horizon.conf`)
- Addon models are referenced via `Addons\*` namespace (e.g., `Addons\MultiChannelSignalAddon\App\Models\ChannelSource`)

### Common Patterns
- Services return `['type' => 'success'|'error', 'message' => ..., 'data' => ...]` arrays
- Transaction wrapping via `$this->executeInTransaction(fn() => ...)`
- Cache via `$this->cacheResult($key, $callback, $ttl)`
- Pagination via `$this->getPaginationParams($params)` -- max 100 per page
- Middleware aliases defined in Kernel: `installed`, `admin`, `permission`, `2fa`, `kyc`, `check_onboarding`, `cache.response`, `query.monitor`

## Dependencies

### Internal
- `addons/` -- Addon modules provide extended models, routes, and services referenced via `Addons\*` namespace
- `database/` -- Migrations, factories, seeders
- `config/` -- Configuration values accessed via `config()` helper
- `routes/` -- Route definitions that map to controllers
- `resources/` -- Blade views and language files used by controllers

### External
- `laravel/framework` (v9+) -- Core framework
- `spatie/laravel-permission` -- Role/permission middleware
- `laravel/horizon` -- Queue dashboard and management
- `laravel/octane` -- High-performance server (optional)
- `laravel/sanctum` -- API authentication
- `google2fa` -- Two-factor authentication
- `scribe` -- API documentation generation
- `seotools` -- SEO meta management
