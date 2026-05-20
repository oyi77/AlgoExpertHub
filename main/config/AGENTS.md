<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Configuration

## Purpose
All Laravel configuration files defining application behavior, service connections, feature toggles, and third-party integrations. 31 config files covering core framework settings plus trading-specific, payment, AI, monitoring, and addon configurations.

## Key Files

| File | Purpose |
|------|---------|
| `app.php` | Core app config: name, env, debug, timezone, locale, cipher, providers, aliases |
| `auth.php` | Authentication guards, providers, passwords, 2FA settings |
| `database.php` | Database connections (MySQL primary), Redis config, migration settings |
| `queue.php` | Queue connections (Redis default for Horizon), failed job table |
| `session.php` | Session driver, lifetime, cookie settings |
| `mail.php` | Mail transport, SMTP settings, from address |
| `cache.php` | Cache drivers (Redis/file), prefix, TTL defaults |
| `sanctum.php` | API token authentication settings |
| `horizon.php` | Laravel Horizon queue worker configuration |
| `trading.php` | Trading-specific settings (pairs, timeframes, signal defaults) |
| `permission.php` | Spatie permission config (role/permission models, caching) |
| `laravel-crypto-payment-gateway.php` | Crypto payment gateway configuration |
| `google2fa.php` | Two-factor authentication settings |
| `health.php` | Application health check configuration |
| `monitoring.php` | Performance monitoring settings |
| `installer.php` | First-run installer configuration |
| `scribe.php` | API documentation generation settings |
| `octane.php` | Laravel Octane high-performance server config |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| _(none)_ | All config files are flat in this directory |

## For AI Agents

### Working In This Directory
- Config values are accessed via `config('file.key')` throughout the app
- Environment-specific overrides go in `.env` -- config files use `env()` helper
- Addon-specific configs may live in `addons/*/config/` and be merged via service providers
- `installer.php` controls first-run setup wizard behavior
- `health.php` and `monitoring.php` control uptime/performance checks referenced by the `/health` route

### Common Patterns
- All configs return PHP arrays with `env()` calls for environment override
- Nested config accessed via dot notation: `config('database.default')`
- Feature toggles often check `env('FEATURE_X', false)` pattern
- Sensitive values (API keys, secrets) always use `env()` and never hardcoded

## Dependencies

### Internal
- `.env` / `.env.example` -- Environment variables referenced by all config files
- `app/Providers/` -- Service providers read and register config bindings
- `addons/*/config/` -- Addon configs may extend or override core configs

### External
- `laravel/framework` -- Core config infrastructure
- `spatie/laravel-permission` -- Permission config format
- `laravel/horizon` -- Horizon config format
- `laravel/octane` -- Octane config format
- Various third-party packages read their own config keys
