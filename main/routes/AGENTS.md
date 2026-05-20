<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Routes

## Purpose
All HTTP route definitions for the application. Routes are split into logical groups: web routes (split into 6 domain files), API routes, admin routes, broadcasting channels, and console commands. The main `web.php` serves as the entry point that includes grouped route files.

## Key Files

| File | Purpose |
|------|---------|
| `web.php` | Main web entry point -- health check endpoint, admin redirect, includes 6 sub-route files |
| `web/trading.php` | Trading routes (~75KB) -- signal management, backtesting, execution, copy trading, marketplace, bot management |
| `web/auth.php` | Authentication routes -- login, register, password reset, email verification, 2FA |
| `web/user.php` | User dashboard routes -- profile, settings, referrals, notifications |
| `web/payments.php` | Payment routes -- deposits, withdrawals, plan subscriptions, gateway handling |
| `web/onboarding.php` | User onboarding flow routes |
| `web/public.php` | Public-facing routes -- landing pages, documentation, frontend |
| `api.php` | REST API routes (~36KB) -- full API for signals, plans, users, payments, trading |
| `admin.php` | Admin panel routes (~25KB) -- admin CRUD for all entities, configuration, analytics |
| `channels.php` | Broadcasting/Pusher channel authorization routes |
| `console.php` | Artisan console command schedule definitions |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `web/` | Domain-split web route files (auth, onboarding, trading, payments, user, public) |

## For AI Agents

### Working In This Directory
- `web.php` is the entry point -- add new route groups by creating a file in `web/` and requiring it in `web.php`
- Trading routes are the largest file (~75KB) -- exercise caution editing, use targeted searches
- API routes use Sanctum middleware for authentication
- Admin routes are protected by `admin` and `permission` middleware
- Route model binding is used throughout (e.g., `{signal}`, `{plan}`)
- Named routes follow dot notation: `admin.signals.index`, `user.profile.update`

### Common Patterns
- Resource routes for CRUD: `Route::resource('signals', SignalController::class)`
- Middleware group chaining: `->middleware(['auth', 'verified', 'check_onboarding'])`
- Admin routes prefixed with `/admin` and guarded by `admin` middleware alias
- API routes versioned via `ApiVersionMiddleware`
- Route groups use `prefix()`, `name()`, and `middleware()` for organization
- Health check route at `/health` returns JSON with database, queue, cache, and Octane status

## Dependencies

### Internal
- `app/Http/Controllers/` -- All route handlers
- `app/Http/Kernel.php` -- Middleware definitions and aliases
- `app/Providers/RouteServiceProvider.php` -- Route service provider configuration
- `addons/*/routes/` -- Addon routes registered via addon service providers

### External
- `laravel/framework` -- Route facade and routing system
- `laravel/sanctum` -- API middleware
- `spatie/laravel-permission` -- Permission middleware
- `laravel/echo` or Pusher -- Broadcasting channel auth
