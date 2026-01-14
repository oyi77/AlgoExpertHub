# PROJECT KNOWLEDGE BASE

**Generated:** 2025-01-10
**Commit:** (latest)
**Branch:** (current)

## OVERVIEW
AlgoExpertHub is a Laravel 10-based trading signal platform for distributing trading signals across Forex, Crypto, and Stock markets. Features include multi-plan subscription system, automated signal ingestion, AI-powered analysis, and automated trade execution.

## STRUCTURE
```
./
├── main/                    # Core Laravel application
│   ├── app/                # Application logic
│   │   ├── Services/        # Business logic (Service Layer Pattern)
│   │   ├── Models/          # Eloquent models
│   │   └── Http/           # Controllers, middleware, requests
│   ├── addons/              # Modular addon packages
│   │   ├── multi-channel-signal-addon/
│   │   ├── trading-management-addon/
│   │   └── ai-connection-addon/
│   ├── database/
│   │   └── migrations/      # Database schema
│   ├── config/              # Configuration files
│   └── resources/
│       └── views/           # Blade templates
├── openspec/               # Spec-driven development
├── docs/                   # Documentation
└── scripts/
    └── deployment/          # Deployment scripts
```

## OpenSpec & Spec-Driven Development (SDD)

This project strictly follows **Spec-Driven Development (SDD)** using the **OpenSpec** framework. Every non-trivial change MUST start with a specification before any code is written.

### 🔄 Three-Stage Lifecycle
1.  **Proposal**: Create a change proposal in `openspec/changes/{change-id}/`.
2.  **Implementation**: Execute tasks sequentially from `tasks.md` after approval.
3.  **Archival**: Move completed changes to `openspec/changes/archive/` and update canonical specs in `openspec/specs/`.

### 📝 Core Workflow
- **File Structure**: `openspec/changes/{name}/{proposal,design,tasks}.md`.
- **Spec Deltas**: Using `## ADDED Requirements`, `## MODIFIED Requirements`.
- **Validation**: Run `openspec validate [change-id] --strict`.
- **Oh-My-OpenCode**: Integrated with `.opencode/` agents for automated analysis.
- **Reference**: See `openspec/AGENTS.md` for detailed instructions.

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| Business logic | `main/app/Services/` | ALL business logic here, never in controllers |
| Data models | `main/app/Models/` | Eloquent models with relationships |
| Controllers | `main/app/Http/Controllers/` | Thin HTTP handlers |
| Migrations | `main/database/migrations/` | Database schema changes |
| Addon features | `main/addons/{addon-name}/app/` | Self-contained packages |
| Payment gateways | `main/app/Services/Gateway/` | Gateway service implementations |
| AI Connections | `AiConnectionService::execute()` | Centralized AI provider management |
| Queue jobs | `main/app/Jobs/` | Async operations |
| Tests | `main/tests/` | Unit and feature tests |
| Views | `main/resources/views/` | Blade templates (theming) |

## CONVENTIONS (Deviations from Standard)

### Architecture
- **Service Layer Pattern**: Controllers delegate ALL business logic to Services
- **Repository Pattern**: Data access layer in `app/Repositories/`
- **Nested Structure**: Core Laravel app in `/main/`, root handles deployment/installation
- **Addon System**: Modular architecture, no core modifications for features
- **Entry Point Proxy**: Root `/index.php` proxies to `/main/bootstrap/app.php`

### PHP Standards
- **Strict Typing**: All files require `declare(strict_types=1);`
- **Type Hints**: Always use parameter and return type declarations
- **PSR-12**: Coding style compliance
- **PHP 8.1+**: Minimum version requirement

### Laravel 10 Specific
- **Queue All Long Operations**: Anything >2 seconds goes to jobs
- **Form Requests**: Validation in `app/Http/Requests/`
- **Response Format**: `['type' => 'success|error', 'message' => '...']`
- **Theme System**: Dynamic views via `Helper::theme()`

### Database
- **Table Names**: Plural snake_case
- **Model Names**: Singular PascalCase
- **Foreign Keys**: `{table}_id` format
- **Timestamps**: Always include `created_at`, `updated_at`
- **Random IDs**: Generated in model boot methods

### Naming
- **Models**: Singular PascalCase (User, PlanSubscription)
- **Controllers**: PascalCase with suffix (UserController, SignalController)
- **Services**: PascalCase with suffix (UserService, SignalService)
- **Methods**: camelCase, descriptive verbs
- **Constants**: UPPER_SNAKE_CASE

## ANTI-PATTERNS (THIS PROJECT)

### NEVER
- Put business logic in controllers
- Commit `.env` files or API keys
- Modify core files for addon development
- Suppress type errors (`as any`, `@ts-ignore`, `@ts-expect-error`)
- Initialize MadelineProto during GET requests (AdminChannelController.php)
- Clear `$_POST` before validation (removes CSRF tokens)
- Create markdown TODO lists (use `bd` instead)
- Use `env()` directly in code (use `config()` instead)

### MUST
- Validate ALL input using Form Requests
- Queue operations >2 seconds
- Use transactions for multi-step DB operations
- Encrypt sensitive data (API keys, credentials)
- Use `bd` for ALL task tracking with `--json` flag
- Store AI planning docs in `history/` directory
- Log errors and important events

## IMPORTANT BUSINESS RULES
- One active subscription per user (`is_current=1`)
- Signals MUST be published (`is_published=1`) before distribution
- Auto-created signals start as drafts (`auto_created=1`)
- Payment approval triggers subscription creation
- All financial activities logged in transactions table
- Demo mode prevents destructive actions

## COMMANDS

### Laravel Application (main/ directory)
```bash
cd main

# Dependencies
composer install
npm install

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Tests
php artisan test                    # All tests
php artisan test --filter SignalTest  # Single test file
./vendor/bin/phpunit tests/Unit/SignalTest.php

# Test Types
# Unit Tests: Services/Repositories in isolation (mock dependencies)
# Feature Tests: Full HTTP flows (Controller -> Service -> DB)
# Property Tests: System invariants via Eris (N+1, API compliance)

# Assets
npm run dev                        # Development build
npm run prod                       # Production build
npm run watch                      # Watch for changes

# Queue
php artisan queue:work
php artisan horizon                 # Redis queue dashboard
```

### Docker Environment
```bash
# All PHP commands MUST use docker exec
docker exec 1Panel-php8-mrTy php artisan <command>
docker exec 1Panel-php8-mrTy composer <command>
```

### Task Tracking
```bash
bd ready --json              # Check for ready work
bd create "Issue title" -t bug|feature|task -p 0-4 --json
bd close bd-42 --reason "Completed" --json
```

## NOTES

### Gotchas
- Root `/index.php` has custom bootstrapping logic (installation checks)
- Use `Helper::theme()` for view paths (multiple themes support)
- Addon namespaces: `Addons\{AddonName}\`
- Addon tables prefixed with addon identifier
- Queue workers must be running for async operations
- Use `config()` helper, never `env()` directly in code

### External Integrations
- **AI Connection Manager**: Centralized AI provider management (OpenAI, Gemini, OpenRouter) via `AiConnectionService::execute()`
- Payment gateways: PayPal, Stripe, Paystack, Coinpayments, etc.
- Trading APIs: CCXT (crypto), MetaApi (forex)
- Telegram: MadelineProto (MTProto), Bot API

### Security
- CSRF protection enabled by default
- SQL injection prevention via Eloquent
- XSS prevention via Blade auto-escaping
- Rate limiting on sensitive routes
- Encrypt gateway credentials: `encrypt(json_encode($credentials))`
