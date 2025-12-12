# AI Trading Platform - Agent Development Guide

## Issue Tracking with bd (beads)

**IMPORTANT**: This project uses **bd (beads)** for ALL issue tracking. Do NOT use markdown TODOs, task lists, or other tracking methods.

**Check for ready work:**
```bash
bd ready --json
```

**Create new issues:**
```bash
bd create "Issue title" -t bug|feature|task -p 0-4 --json
```

**Complete work:**
```bash
bd close bd-42 --reason "Completed" --json
```

## Build, Test & Development Commands

### Laravel Application (main/ directory)
```bash
# Install dependencies
cd main && composer install
cd main && npm install

# Database migrations
php artisan migrate
php artisan migrate:fresh --seed

# Run tests
php artisan test                    # All tests
php artisan test --filter SignalTest  # Single test file
./vendor/bin/phpunit tests/Unit/SignalTest.php

# Asset compilation
npm run dev                        # Development build
npm run prod                       # Production build
npm run watch                      # Watch for changes

# Queue management
php artisan queue:work
php artisan horizon                 # Redis queue dashboard
```

### Frontend Assets
```bash
cd main
npm run development                # Build assets
npm run watch                      # Watch and rebuild
npm run production                 # Optimized production build
```

## Code Style & Architecture Guidelines

### Laravel Architecture (Service Layer Pattern)
- **Controllers**: Thin HTTP handlers only, delegate to services
- **Services**: ALL business logic in `app/Services/` directory
- **Models**: Eloquent models in `app/Models/` with relationships and casts
- **Jobs**: Async operations in `app/Jobs/`, queue anything >2 seconds
- **Requests**: Form validation in `app/Http/Requests/`

### Naming Conventions
- **Models**: Singular PascalCase (User, PlanSubscription, TradingSignal)
- **Tables**: Plural snake_case (users, plan_subscriptions, trading_signals)
- **Controllers**: PascalCase with suffix (UserController, SignalController)
- **Services**: PascalCase with suffix (UserService, SignalService)
- **Methods**: camelCase, descriptive verbs (createSignal, processPayment)
- **Variables**: camelCase, meaningful names
- **Constants**: UPPER_SNAKE_CASE

### PHP Code Standards
- **PSR-12** coding style
- **Strict types**: Declare `declare(strict_types=1);`
- **Type hints**: Always use parameter and return type declarations
- **Properties**: Declare visibility and types
- **Imports**: Use grouped imports at top of files
- **Error handling**: Use try-catch, return structured responses

### Response Format
```php
// Standard API response format
return [
    'type' => 'success|error',
    'message' => 'User-friendly message',
    'data' => [...], // optional data
];
```

### Database Conventions
- **Foreign keys**: `{table}_id` (user_id, signal_id)
- **Timestamps**: Always include `created_at`, `updated_at`
- **Soft deletes**: Use `deleted_at` when needed
- **Random IDs**: Generate in model boot methods for security
- **Indexes**: Add to foreign keys and frequently queried columns
- **JSON fields**: Use for flexible data structures

### Security Requirements
- **Validate ALL input** using Form Requests
- **Sanitize output** using Laravel's built-in protections
- **Encrypt sensitive data** (API keys, credentials): `encrypt()`
- **Use middleware** for auth, permissions, 2FA, KYC
- **CSRF protection**: Enabled by default
- **SQL injection prevention**: Use Eloquent, parameterized queries

### Trading Platform Specific Rules
- **Financial calculations**: Use precise decimal arithmetic
- **Risk management**: Implement position sizing, stop-loss logic
- **Real-time data**: Use WebSockets, queue processing
- **Payment processing**: Always log transactions, use idempotency
- **User balance**: Update atomically, maintain audit trail
- **Signal publishing**: Draft → Published workflow, immutable when published

### Testing Requirements
- **Feature tests**: Test complete workflows
- **Unit tests**: Test individual methods and services
- **Database transactions**: Use `RefreshDatabase` trait
- **Mocking**: Mock external APIs and services
- **Coverage**: Critical trading logic must have 100% test coverage

### Addon Development
- **Self-contained**: Each addon in `addons/{name}/` directory
- **No core modifications**: Use events/observers for integration
- **Namespacing**: `Addons\{AddonName}\` namespace
- **Service provider**: Conditional registration based on addon status
- **Database isolation**: Prefix addon tables with addon identifier

### Performance Optimizations
- **Eager loading**: Prevent N+1 queries with `with()`
- **Caching**: Cache expensive queries and configurations
- **Pagination**: Use `paginate()` for large datasets
- **Queue long operations**: External APIs, emails, file processing
- **Database indexes**: Add to frequently queried columns
- **Asset optimization**: Use Laravel Mix for production builds

### Important Business Rules
- **One active subscription** per user (`is_current=1`)
- **Signals MUST be published** before distribution
- **Payment approval triggers** subscription creation
- **All financial activities** logged in transactions table
- **Auto-created signals** start as drafts for admin review
- **Demo mode** prevents destructive actions

### File Organization
- **Controllers**: `app/Http/Controllers/{Backend|User|Api}/`
- **Services**: `app/Services/`
- **Models**: `app/Models/`
- **Jobs**: `app/Jobs/`
- **Migrations**: `database/migrations/`
- **Tests**: `tests/{Feature|Unit}/`
- **Views**: `resources/views/{backend|frontend/{theme}/}`

### Configuration Management
- **Environment**: Use `.env` for sensitive data
- **Config files**: Use `config()` helper, never `env()` directly
- **Feature flags**: Use Configuration model for admin-controlled settings
- **Theme system**: Use `Helper::theme()` for dynamic view paths

### Logging & Monitoring
- **Log errors**: `Log::error('Message', ['context' => $data])`
- **Log important events**: Job failures, API errors, security events
- **Audit trail**: Use UserLog model for user actions
- **Performance monitoring**: Track slow queries and API responses

## Quick Reference
- **Service pattern**: Controllers thin, Services handle business logic
- **Always validate**: Form Requests for input validation
- **Queue long operations**: External APIs, emails, file processing
- **Use transactions**: Wrap multi-step DB operations in `DB::transaction()`
- **Eager load relationships**: Prevent N+1 queries
- **Encrypt sensitive data**: API keys, credentials
- **Log everything**: Errors, important events, audit trails

## Important Rules
- ✅ Use bd for ALL task tracking with `--json` flag
- ✅ Store AI planning docs in `history/` directory
- ✅ Follow service layer pattern for business logic
- ✅ Always validate input and sanitize output
- ✅ Use type hints and strict typing
- ✅ Queue operations that take >2 seconds
- ✅ Log errors and important events
- ✅ Write tests for critical trading logic
- ❌ Don't put business logic in controllers
- ❌ Don't commit `.env` files or API keys
- ❌ Don't create markdown TODO lists
- ❌ Don't modify core files for addon development