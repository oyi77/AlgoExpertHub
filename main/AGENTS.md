<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Main (Laravel Application)

## Purpose

The Laravel application root. Contains all core application code, configuration, database schema, frontend assets, routes, and tests. This is the primary working directory for backend and frontend development.

## Key Files

| File | Description |
|------|-------------|
| `artisan` | Laravel CLI entry point |
| `composer.json` | PHP dependency definitions |
| `package.json` | Node.js dependency definitions (frontend build tools) |
| `webpack.mix.js` | Laravel Mix asset compilation configuration |
| `phpunit.xml` | PHPUnit test configuration |
| `.env.example` | Environment variable template |
| `server.php` | Laravel built-in server entry |
| `start-octane.sh` | Laravel Octane startup script |
| `supervisor-horizon.conf` | Supervisor config for Horizon queue worker |
| `supervisor-laravel-worker.conf` | Supervisor config for Laravel queue worker |
| `supervisor-octane.conf` | Supervisor config for Octane server |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `app/` | Core application code — Services, Models, Controllers, Jobs, Events, Listeners, Repositories, DTOs, Console commands |
| `addons/` | Modular feature packages (trading-management, ai-connection, copy-trading, page-builder, etc.) |
| `config/` | Laravel configuration files (app, database, cache, queue, trading, auth, etc.) |
| `database/` | Migrations, seeders, factories, and SQL scripts |
| `resources/` | Blade views, JavaScript, CSS, and language files |
| `routes/` | Route definitions — `web.php`, `admin.php`, `api.php`, `console.php`, `channels.php` |
| `tests/` | PHPUnit Feature and Unit tests |
| `bootstrap/` | Laravel bootstrap and cache |
| `storage/` | Logs, cache, compiled views, file uploads |
| `public/` | Web-accessible entry point (`index.php`) and compiled assets |
| `scripts/` | Laravel-specific utility scripts |
| `docs/` | Inline documentation for the application |

## For AI Agents

### Working In This Directory
- All artisan commands run from this directory: `cd main && php artisan ...`
- Install dependencies: `composer install` and `npm install`
- Run migrations: `php artisan migrate` or `php artisan migrate:fresh --seed`
- Run tests: `php artisan test` or `./vendor/bin/phpunit`
- Build assets: `npm run dev` (development), `npm run prod` (production)

### Architecture: Service Layer Pattern
```
Controller (thin HTTP) → Service (business logic) → Model (data) → Database
```
- Controllers live in `app/Http/Controllers/{Backend|User|Api}/`
- Services live in `app/Services/`
- Models live in `app/Models/`
- Jobs live in `app/Jobs/`
- Form Requests live in `app/Http/Requests/`

### Common Patterns
- **Response format**: `['type' => 'success|error', 'message' => '...', 'data' => [...]]`
- **Config access**: Always use `config()` helper, never `env()` directly
- **Theme system**: Use `Helper::theme()` for dynamic view paths
- **Feature flags**: Use Configuration model for admin-controlled settings
- **Database transactions**: Wrap multi-step operations in `DB::transaction()`
- **Eager loading**: Use `with()` to prevent N+1 queries

### Addon Development
- Addons live in `addons/{name}/` with namespace `Addons\{AddonName}\`
- Each addon has its own Service Provider with conditional registration
- No core file modifications — use events/observers for integration
- Prefix addon database tables with addon identifier

### Testing
- Feature tests: `tests/Feature/` — test complete workflows
- Unit tests: `tests/Unit/` — test individual methods and services
- Use `RefreshDatabase` trait for database tests
- Mock external APIs and services
- Critical trading logic requires 100% test coverage

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules and conventions
- `../CLAUDE.md` — AI development guidelines
- `../docker/` — Docker configuration for local development
- `../install/` — Installation wizard for initial setup

### External
- Laravel 10.x framework
- PHP 8.1+ runtime
- MySQL 5.7+ database
- Redis for caching and queues
- Node.js / npm for frontend asset compilation
