---
inclusion: always
---

# Cursor Rules Index - AlgoExpertHub Trading Signal Platform

## Overview
This directory contains comprehensive cursor rules that codify the technical architecture, business logic, and development patterns for the trading signal platform. These rules serve as persistent memory for AI-assisted development.

## Platform Summary
**AlgoExpertHub** is a Laravel-based subscription platform for distributing trading signals across Forex, Crypto, and Stock markets. The platform features:
- Multi-plan subscription system with payment gateway integration
- Signal creation, publishing, and distribution (manual & automated)
- Multi-channel signal ingestion (Telegram, API, Web scraping, RSS)
- Automated trade execution on exchanges/brokers
- Copy trading and preset trading configurations
- Admin panel with role-based permissions
- User dashboard with wallet, referral system, and support tickets

## Rule Files

### 1. laravel-architecture.mdc
**Purpose**: Core Laravel framework patterns and conventions

**Contents**:
- Framework version (Laravel 9, PHP 8.0+)
- Directory structure and organization
- Service layer pattern (business logic in services)
- Controller conventions (backend, user, API)
- Routing patterns and middleware stack
- View theming system (multiple frontend themes)
- Helper class usage (`Helper::theme()`, file operations)
- Form requests & validation
- Queue & jobs architecture
- Database conventions (migrations, models, relationships)
- Model conventions (Eloquent, traits, casts)
- Configuration management
- Dependency injection patterns
- Error handling & logging
- Asset management
- Testing setup
- Artisan commands
- Best practices (thin controllers, resource routes, eager loading, etc.)

**Key Patterns**:
- Service layer for ALL business logic
- Controllers only handle HTTP requests/responses
- Return format: `['type' => 'success|error', 'message' => '...']`
- Theme-based views: `Helper::theme()` + view path
- Queue long operations (external APIs, emails)

---

### 2. business-domain.mdc
**Purpose**: Business domain models, workflows, and core application logic

**Contents**:
- Application purpose and overview
- Core business entities:
  - User (subscriptions, balance, KYC, 2FA, referrals)
  - Admin (super admin, staff with permissions)
  - Plan (subscription plans: limited/lifetime)
  - Signal (trading signals: manual/auto-created)
  - CurrencyPair, TimeFrame, Market (signal metadata)
  - PlanSubscription (user subscriptions)
  - Payment & Deposit (financial transactions)
  - Gateway (payment gateways)
  - Withdraws, Transactions, Referrals
  - Tickets (support system)
- Core business workflows:
  - User registration & onboarding
  - Plan subscription flow (payment → subscription activation)
  - Signal creation & distribution (manual & automated)
  - Automated trade execution (via Execution Engine Addon)
- Domain services:
  - SignalService (create, publish, distribute signals)
  - PaymentService (process payments, handle callbacks)
  - PlanService, UserPlanService, TelegramChannelService
- Important business rules:
  - Plan exclusivity (one active subscription per user)
  - Signal assignment (to plans, many-to-many)
  - Signal publishing (draft → published, immutable)
  - Auto-created signals (drafts for review)
  - Payment flow (pending → approved → subscription)
  - User balance & wallet system
  - Referral commissions
  - KYC verification, 2FA, demo mode
- Notifications (database, email, Telegram)
- Transactions & financial records
- Multi-tenancy pattern (admin-owned vs user-owned)

**Key Rules**:
- One active subscription per user (`is_current=1`)
- Signals MUST be published to distribute
- Auto-created signals start as drafts
- Payment approval triggers subscription creation
- All financial activities logged in transactions table

---

### 3. addon-system.mdc
**Purpose**: Addon system architecture and development guidelines

**Contents**:
- Addon overview (modular architecture)
- Directory structure (addon self-contained packages)
- Addon manifest (`addon.json` format and module declarations)
- Addon service provider template
- Addon registration (conditional in AppServiceProvider)
- AddonRegistry service (methods, status management)
- Addon development rules:
  - Namespace convention (`Addons\{AddonName}`)
  - Database conventions (tables, migrations, foreign keys)
  - Core integration (NO core file modifications)
  - Route naming, view naming, configuration
  - Permissions, queue jobs, scheduled tasks
- Installed addons:
  - **Multi-Channel Signal Addon**: Signal ingestion from Telegram, API, RSS, web scraping
  - **Trading Execution Engine Addon**: Auto-execute trades on exchanges/brokers
  - **Trading Preset Addon**: Risk management presets (position sizing, SL/TP, etc.)
  - **Trading Bot Signal Addon**: Firebase integration for bot notifications
  - **Copy Trading Addon**: Social trading (basic structure)
- Addon management (admin interface: upload, enable/disable, modules)
- Testing addons
- Best practices (loose coupling, database isolation, error handling, security)

**Key Patterns**:
- Addons are self-contained in `main/addons/{addon-name}/`
- Each addon has `addon.json` with modules (admin_ui, user_ui, processing)
- Service provider conditionally registered based on status
- Use events/observers to hook into core (NOT direct modification)

---

### 4. authentication-authorization.mdc
**Purpose**: Authentication, authorization, security patterns

**Contents**:
- Authentication guards (web for users, admin for admins)
- Authentication flows:
  - User registration (with referral, email verification)
  - Login (credentials, remember me)
  - Email verification (enforced by middleware)
  - Password reset (via email or SMS)
  - 2FA (Google Authenticator via LoginSecurity model)
  - Social login (Facebook, Google via Socialite)
- Admin authentication (separate guard, login/logout)
- Authorization (Spatie Permission package):
  - Super admin (bypasses all checks)
  - Staff admin (role-based permissions)
  - Permission middleware (`permission:permission-name,admin`)
  - Core permissions (manage-addon, manage-plan, signal, etc.)
  - Permission checks in controllers (via middleware or authorization)
- Security middleware:
  - `auth`, `admin`, `guest`, `admin.guest`
  - `demo` (prevent destructive actions)
  - `inactive` (check user status)
  - `is_email_verified` (enforce email verification)
  - `2fa` (enforce 2FA check)
  - `kyc` (enforce KYC approval)
  - `reg_off` (disable registration)
- Password policies (bcrypt, reset tokens)
- Session management (lifetime, remember me)
- API authentication (Laravel Sanctum)
- Security best practices:
  - Never trust user input (validation, sanitization)
  - CSRF protection (enabled by default)
  - SQL injection prevention (Eloquent, parameterized queries)
  - XSS prevention (Blade auto-escaping)
  - Encrypt sensitive data (credentials, API keys)
  - Rate limiting (throttle middleware)
  - Secure configuration (APP_KEY, HTTPS, .env)
  - Audit logging (UserLog model)
- User account status (active/inactive, email verified, KYC status)

**Key Rules**:
- Two guards: `web` (users) and `admin` (admins)
- Super admin bypasses all permission checks (Gate::before)
- 2FA, email verification, KYC enforced via middleware
- Always encrypt gateway credentials and API keys
- CSRF, SQL injection, XSS protections enabled by default

---

### 5. database-models.mdc
**Purpose**: Database schema, model relationships, data patterns

**Contents**:
- Core tables with full schema:
  - users, admins, plans, signals
  - currency_pairs, time_frames, markets
  - plan_subscriptions, plan_signals (pivot)
  - payments, deposits, withdraws
  - gateways, withdraw_gateways
  - transactions, referrals, referral_commissions
  - tickets, ticket_replies
  - dashboard_signals, user_signals
  - login_securities (2FA)
  - configurations (system config)
  - user_logs (audit log)
  - jobs (queue)
  - notifications (database notifications)
- Addon-specific tables:
  - channel_sources, channel_messages (Multi-Channel Addon)
  - execution_connections, execution_logs, execution_positions (Execution Engine Addon)
- Model conventions:
  - Eloquent conventions (table/model naming)
  - Relationship naming (hasOne singular, hasMany plural)
  - Casting ($casts for dates, JSON, booleans)
  - Mass assignment ($guarded or $fillable)
  - Custom primary keys (random IDs in boot method)
  - Searchable trait (custom trait for search)
  - Scopes (reusable query methods)
- Query optimization:
  - Eager loading (prevent N+1)
  - Chunking (process large datasets)
  - Pagination
  - Indexes (foreign keys, queried columns)
  - Caching (expensive queries)
- Data integrity:
  - Foreign key constraints (cascade, set null)
  - Unique constraints
  - Default values
  - JSON fields

**Key Patterns**:
- Table names: plural, snake_case (users, plan_subscriptions)
- Model names: singular, PascalCase (User, PlanSubscription)
- Foreign keys: `{table}_id` (user_id, signal_id)
- Timestamps: `created_at`, `updated_at` (automatic)
- Always index foreign keys and frequently queried columns

---

### 6. payments-gateways.mdc
**Purpose**: Payment gateway integration patterns and financial workflows

**Contents**:
- Payment flow architecture (6 steps):
  1. Gateway selection
  2. Payment initiation
  3. Transaction creation (trx ID, calculate total)
  4. Gateway processing (redirect to gateway)
  5. Gateway callback (validate, update status)
  6. Subscription activation (create PlanSubscription)
- Supported payment gateways:
  - Manual (bank transfer, admin approval)
  - PayPal, Stripe, Paystack, Paytm (regional)
  - Mollie, Mercadopago (Latin America)
  - Coinpayments, Nowpayments, Gourl (crypto)
  - Paghiper (Brazil: Boleto, PIX)
- Gateway service pattern:
  - Consistent interface (`process()`, `success()` methods)
  - Service classes: `App\Services\Gateway\{Name}Service`
  - Configuration in `gateways.parameter` JSON (encrypted)
- Gateway configuration:
  - Database schema (gateways table)
  - Parameter JSON structure (custom per gateway)
  - Security: encrypt credentials
- Transaction management:
  - Transaction model (log ALL financial activities)
  - Create transaction on payment approval
- Wallet system:
  - User balance (users.balance)
  - Deposit flow, withdraw flow, referral commission flow
- Payment validation & security:
  - Validate gateway status
  - Validate callback signatures (webhook verification)
  - Idempotency (prevent duplicate processing)
  - Rate limiting
- Error handling (API failures, callback failures, notifications)
- Testing gateways (sandbox mode, manual testing, automated tests)
- Admin gateway management (CRUD operations)
- Best practices:
  - Always log transactions
  - Encrypt gateway credentials
  - Validate callbacks (verify signatures)
  - Handle failures gracefully
  - Idempotency
  - Rate limiting
  - Reconciliation
  - User communication
  - Gateway diversification
  - Compliance (PCI DSS, GDPR, tax)

**Key Patterns**:
- Gateway service: `{Name}Service::process()` → returns redirect URL
- Callback: `{Name}Service::success()` → validates and updates status
- Transaction ID (trx): Unique 16-char uppercase string
- Payment status: 0=pending, 1=approved, 2=rejected
- Always encrypt credentials: `encrypt(json_encode($credentials))`

---

### 7. queues-jobs-notifications.mdc
**Purpose**: Queue system, background jobs, notifications, event-driven patterns

**Contents**:
- Queue system architecture:
  - Queue driver (database, Redis)
  - Jobs table schema
  - Queue workers (artisan commands, supervisor)
- Job classes:
  - Base job structure (Queueable, ShouldQueue traits)
  - Job properties (queue, tries, timeout, delay)
  - Dispatching jobs (immediate, delayed, queue-specific, chain, batch)
- Core jobs:
  - **ProcessChannelMessage**: Parse incoming messages (Multi-Channel Addon)
  - **SendEmailJob**: Send emails asynchronously
  - **SendSubscriberMail**: Send newsletter emails
- Addon-specific jobs:
  - Multi-Channel: FetchTelegramUpdates, ScrapeWebSource, FetchRssFeed
  - Execution Engine: ExecuteSignalJob, MonitorPositionsJob, UpdateAnalyticsJob
  - Trading Bot: ProcessTradingBotNotification, SyncFirebaseDataJob
- Scheduled jobs (Kernel schedule):
  - MonitorPositionsJob (every minute)
  - UpdateAnalyticsJob (daily at midnight)
  - Expire subscriptions (hourly)
- Notification system:
  - Notification channels (database, email, Telegram, SMS)
  - Notification classes (structure, via(), toArray(), toTelegram())
  - Sending notifications (single user, multiple users, queued)
  - Core notifications:
    - SignalPublished, TicketNotification, PlanSubscriptionNotification
    - DepositNotification, WithdrawNotification, KycUpdateNotification
  - Database notifications (retrieve, mark as read, display in UI)
  - Telegram notifications (config, user setup, sending via service)
- Event-driven architecture:
  - Laravel events (event-listener mapping)
  - Model events (observers, e.g., SignalObserver for execution)
- Job monitoring & debugging:
  - Failed jobs table (retry, flush)
  - Horizon (Redis queue dashboard, optional)
  - Job logging
- Best practices:
  - Always queue long operations
  - Handle job failures (failed() method, log, notify)
  - Job idempotency (safe to run multiple times)
  - Timeout & retries (set appropriately)
  - Queue priorities (high, default, low)
  - Batch operations (chunks)
  - Job tags (for monitoring)
  - Graceful failures (don't crash system)
  - Test jobs (unit tests, mocks)
  - Monitor queue health (size, failed jobs, execution time)

**Key Patterns**:
- Queue long operations (>2 seconds): External APIs, emails, file processing
- Job structure: `use Queueable, ShouldQueue; public function handle() { ... }`
- Dispatch: `dispatch(new JobClass($data))`
- Notifications: `$user->notify(new NotificationClass($data))`
- Observers: Hook into model events (e.g., Signal updated → dispatch ExecuteSignalJob)

---

## Rule Application

### How Rules Are Applied
- **Global Rules** (`alwaysApply: true`): Applied to all files in workspace
- **Scoped Rules** (`globs: ["path/**/*"]`): Applied to specific directories/files
- **Rule Layering**: More specific rules override general rules

### When to Reference Rules
1. **Creating new features**: Check business-domain.mdc for workflows
2. **Adding models**: Check database-models.mdc for conventions
3. **Creating controllers**: Check laravel-architecture.mdc for patterns
4. **Implementing auth**: Check authentication-authorization.mdc
5. **Adding payment gateways**: Check payments-gateways.mdc
6. **Creating background jobs**: Check queues-jobs-notifications.mdc
7. **Developing addons**: Check addon-system.mdc

### Updating Rules
- When core architecture changes, update laravel-architecture.mdc
- When business logic changes, update business-domain.mdc
- When new addons added, update addon-system.mdc
- When database schema changes, update database-models.mdc
- Keep rules synchronized with codebase

## Development Workflow

### Using Rules for Development
1. **Read relevant rule file** before starting work
2. **Follow patterns** documented in rules
3. **Reference existing code** that follows patterns
4. **Update rules** if patterns evolve
5. **Review rules** periodically to ensure accuracy

### Quick Reference
- **Service Pattern**: Business logic → Services, Controllers → thin HTTP handlers
- **Addon Pattern**: Self-contained packages, no core modifications, use events/observers
- **Security Pattern**: Validate input, sanitize output, encrypt credentials, use middleware
- **Database Pattern**: Eloquent ORM, eager loading, indexes, foreign keys
- **Queue Pattern**: Queue long operations, handle failures, idempotency
- **Notification Pattern**: Database + Email + Telegram channels

## Architecture Principles

### 1. Separation of Concerns
- Controllers: HTTP requests/responses
- Services: Business logic
- Models: Data access
- Jobs: Async processing
- Notifications: User communication

### 2. Modularity
- Addons for feature extensions
- Service providers for bootstrapping
- Events/observers for loose coupling

### 3. Security First
- Authentication guards (web, admin)
- Authorization (permissions, roles)
- Encryption (credentials, API keys)
- Validation & sanitization
- Middleware (auth, 2FA, KYC, demo)

### 4. Performance
- Queue long operations
- Eager loading (N+1 prevention)
- Caching (queries, config)
- Indexes (database)
- Pagination

### 5. Maintainability
- Consistent naming conventions
- Clear directory structure
- Comprehensive documentation
- Tests (unit, feature)
- Logging & monitoring

## Technology Stack Summary

### Core Technologies
- **Framework**: Laravel 9.x
- **PHP**: 8.0.2+
- **Database**: MySQL
- **Queue**: Database (or Redis)
- **Cache**: File (or Redis)
- **Frontend**: Blade templates, Bootstrap
- **Assets**: Mix (Webpack)

### Key Packages
- **Authentication**: Laravel Sanctum, Socialite
- **Permissions**: Spatie Laravel Permission
- **2FA**: pragmarx/google2fa-laravel
- **Payments**: Various gateway SDKs (PayPal, Stripe, Coinpayments, etc.)
- **Notifications**: Telegram Bot SDK
- **Utilities**: Intervention Image, Laravel Share, Purifier
- **Addons**: CCXT (crypto exchanges), MadelineProto (Telegram)

### External Services
- Payment Gateways (PayPal, Stripe, Coinpayments, etc.)
- Telegram Bot API
- OpenAI API (Multi-Channel Addon: AI parsing)
- Google Gemini API (Multi-Channel Addon: AI parsing)
- Firebase (Trading Bot Addon)
- CCXT-supported exchanges (Execution Engine Addon)
- MT4/MT5 brokers via mtapi.io (Execution Engine Addon)

## Specifications Location
Full feature specifications with user stories, requirements, and implementation details are in:
- `specs/active/multi-channel-signal-addon/` - Multi-Channel Signal Addon
- `specs/active/admin-global-channels/` - Admin global channels
- `specs/active/ai-market-confirmation-trading-flow/` - AI market confirmation
- `specs/active/trading-preset/` - Trading presets

## Continuous Improvement
These rules are living documents. As the codebase evolves:
1. Update rules to reflect architecture changes
2. Add new rule files for new major features
3. Refactor rules for clarity and accuracy
4. Remove outdated rules
5. Keep rules synchronized with implementation

---

**Last Updated**: 2025-12-02
**Version**: 1.0
**Maintained By**: AI-Assisted Development Team
