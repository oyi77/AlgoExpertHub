# Project Context

## Purpose

**AlgoExpertHub** is a comprehensive Laravel-based subscription platform for distributing trading signals across multiple asset markets (Forex, Crypto, Stocks). The platform enables administrators to create, manage, and distribute trading signals to subscribers through various channels, with support for:

- **Multi-Plan Subscription System** - Flexible subscription plans with payment gateway integration
- **Signal Management** - Manual and automated signal creation, publishing, and distribution
- **Multi-Channel Signal Ingestion** - Automatically import signals from Telegram, APIs, RSS feeds, and web scraping
- **AI-Powered Analysis** - Market confirmation and signal validation using AI models (OpenAI, Gemini, OpenRouter)
- **Automated Trading** - Execute trades automatically on connected exchanges/brokers (CCXT, MT4/MT5 via mtapi.io)
- **Copy Trading** - Social trading where users can copy other traders' signals
- **Risk Management** - Trading presets with position sizing, stop loss, take profit configurations
- **Admin Panel** - Comprehensive admin interface with role-based permissions (Spatie)
- **User Dashboard** - Feature-rich user dashboard with wallet, referral system, and support tickets

## Tech Stack

### Backend
- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL 5.7+
- **Queue**: Database (or Redis)
- **Cache**: File (or Redis)

### Frontend
- **Templating**: Blade Templates
- **CSS Framework**: Bootstrap 4
- **JavaScript**: jQuery
- **Icons**: Feather Icons, Font Awesome
- **Asset Build**: Laravel Mix (Webpack)

### Key Packages
- **Authentication**: Laravel Sanctum, Socialite (Facebook, Google OAuth)
- **Permissions**: Spatie Laravel Permission
- **2FA**: Google2FA Laravel (pragmarx/google2fa-laravel)
- **Image Processing**: Intervention Image
- **Payment Gateways**: Stripe, PayPal, Coinpayments, Paystack, Paytm, Mollie, Mercadopago, Paghiper, Gourl, Nowpayments
- **Telegram**: MadelineProto (MTProto), Telegram Bot API
- **Queue**: Laravel Queue (database driver), Laravel Horizon (optional)
- **Notifications**: Laravel Notifications (database, email, Telegram)
- **AI Integration**: OpenAI, Google Gemini, OpenRouter (400+ models)
- **Trading**: CCXT (crypto exchanges), MetaAPI Cloud SDK (MT4/MT5)
- **Utilities**: Purifier (HTML sanitization), Laravel Share, Location detection

## Project Conventions

### Code Style

- **PSR-12**: Follow PHP coding standards
- **Strict Types**: Always declare `declare(strict_types=1);` at top of files
- **Type Hints**: Always use parameter and return type declarations
- **Documentation**: PHPDoc for all public methods and classes
- **Naming Conventions**:
  - **Models**: Singular PascalCase (User, PlanSubscription, TradingSignal)
  - **Tables**: Plural snake_case (users, plan_subscriptions, trading_signals)
  - **Controllers**: PascalCase with suffix (UserController, SignalController)
  - **Services**: PascalCase with suffix (UserService, SignalService)
  - **Methods**: camelCase, descriptive verbs (createSignal, processPayment)
  - **Variables**: camelCase, meaningful names
  - **Constants**: UPPER_SNAKE_CASE

### Architecture Patterns

- **Service Layer Pattern**: ALL business logic in `app/Services/` directory
  - Controllers are thin HTTP handlers only
  - Services handle: validation, data transformation, database operations, external API calls
  - Return format: `['type' => 'success|error', 'message' => '...', 'data' => ...]`
- **MVC with Service Layer**: Clear separation between HTTP layer, business logic, and data access
- **Modular Addon System**: Self-contained addons in `main/addons/{addon-name}/`
  - Each addon has own namespace: `Addons\{AddonName}`
  - Service provider pattern for registration
  - No core file modifications (use events/observers for integration)
- **Repository Pattern**: Eloquent models for data access
- **Event-Driven**: Use Laravel events/listeners for loose coupling
- **Queue Pattern**: Queue long operations (>2 seconds): external APIs, emails, file processing
- **Dependency Injection**: Constructor injection for services and dependencies

### Testing Strategy

- **Framework**: PHPUnit 10.x
- **Test Structure**:
  - **Unit Tests**: `tests/Unit/` - Test individual methods and services
  - **Feature Tests**: `tests/Feature/` - Test complete workflows and HTTP endpoints
- **Test Configuration**: `phpunit.xml` with separate test suites
- **Database**: Use `RefreshDatabase` trait for feature tests
- **Mocking**: Mock external APIs and services (payment gateways, Telegram, AI APIs)
- **Coverage**: Critical trading logic must have 100% test coverage
- **Running Tests**:
  ```bash
  php artisan test                    # All tests
  php artisan test --filter SignalTest  # Specific test
  ./vendor/bin/phpunit tests/Unit/SignalTest.php
  ```

### Git Workflow

- **Branching**: Standard Laravel project structure
- **Commit Conventions**: Descriptive commit messages
- **Ignored Files**: Standard Laravel `.gitignore`:
  - `/vendor/`, `/node_modules/`
  - `.env`, `.env.*` (except `.env.example`)
  - `/storage/logs`, `/storage/framework/`
  - IDE files (`.idea/`, `.vscode/`)
  - OS files (`.DS_Store`, `Thumbs.db`)
- **Version Control**: Git with standard Laravel conventions

## Domain Context

### Core Business Entities

- **User**: Platform subscribers with wallet balance, subscriptions, KYC status, 2FA
- **Admin**: Platform administrators (super admin or staff with role-based permissions)
- **Plan**: Subscription plans (limited or lifetime) with pricing and duration
- **Signal**: Trading signals (buy/sell recommendations) with currency pair, timeframe, market, prices (entry, SL, TP)
- **PlanSubscription**: User subscriptions to plans with expiry dates
- **Payment/Deposit**: Financial transactions via payment gateways
- **Gateway**: Payment gateway configurations (manual or automated)
- **Transaction**: Audit log of all financial activities

### Key Business Rules

- **Plan Exclusivity**: One active subscription per user (`is_current=1`)
- **Signal Publishing**: Signals MUST be published (`is_published=1`) before distribution
- **Auto-Created Signals**: Start as DRAFTS for admin review
- **Payment Flow**: Payment approval triggers subscription creation
- **Financial Transactions**: All activities logged in transactions table
- **Signal Assignment**: Signals assigned to plans (many-to-many), only subscribed users receive signals

### Trading Domain

- **Markets**: Forex, Crypto, Stocks, Commodities
- **Currency Pairs**: Trading pairs (e.g., EUR/USD, BTC/USDT)
- **Timeframes**: Trading timeframes (1H, 4H, 1D, etc.)
- **Signal Direction**: buy, sell, long, short
- **Risk Management**: Position sizing, stop loss, take profit
- **Execution**: Automated execution on exchanges/brokers via CCXT or MT4/MT5 APIs

### Addon System

- **Trading Management Addon**: Unified trading system (data provider, filtering, AI analysis, risk management, execution, backtesting)
- **Multi-Channel Signal Addon**: Signal ingestion from Telegram, API, RSS, web scraping
- **AI Connection Addon**: Centralized AI provider management (OpenAI, Gemini, OpenRouter)
- **Page Builder Addon**: Drag-and-drop page builder
- **OpenRouter Integration Addon**: Access to 400+ AI models
- **Trading Bot Signal Addon**: Firebase integration for bot notifications

## Important Constraints

### Technical Constraints

- **PHP Version**: Must be 8.1+ (Laravel 10 requirement)
- **Database**: MySQL 5.7+ required
- **Queue Processing**: Long operations must be queued (database or Redis driver)
- **Memory**: Large message processing requires chunking
- **API Rate Limits**: External APIs (Telegram, OpenAI, payment gateways) have rate limits
- **Encryption**: Sensitive data (API keys, credentials) must be encrypted using Laravel's `encrypt()`

### Business Constraints

- **Financial Transactions**: All payments must be logged and auditable
- **Signal Immutability**: Published signals cannot be unpublished (immutable)
- **Subscription Expiry**: Limited plans expire after duration, lifetime plans never expire
- **KYC Compliance**: KYC verification required for certain actions (configurable)
- **2FA Security**: Optional 2FA via Google Authenticator
- **Demo Mode**: Demo mode middleware prevents destructive actions

### Security Constraints

- **Input Validation**: ALL user input must be validated using Form Requests
- **Output Sanitization**: Use Blade `{{ }}` for auto-escaping, Purifier for HTML
- **CSRF Protection**: Enabled by default for all POST/PUT/PATCH/DELETE routes
- **SQL Injection Prevention**: Use Eloquent ORM, never raw SQL with user input
- **XSS Prevention**: Blade auto-escaping, sanitize HTML with Purifier
- **Authentication**: Separate guards for users (`web`) and admins (`admin`)
- **Authorization**: Role-based permissions via Spatie (super admin bypasses all checks)
- **Encryption**: Gateway credentials, API keys stored encrypted
- **Rate Limiting**: Throttle middleware for sensitive routes

### Regulatory Constraints

- **Payment Processing**: PCI DSS compliance (never store card details)
- **Data Privacy**: GDPR compliance (data retention, privacy)
- **Financial Records**: Maintain audit trail for all transactions
- **Terms of Service**: Refund policies, chargeback handling

## External Dependencies

### Payment Gateways

- **PayPal**: REST API for payment processing
- **Stripe**: Payment Intents API
- **Paystack**: Nigeria, Ghana, South Africa
- **Paytm**: India
- **Mollie**: European payment methods (iDEAL, credit card)
- **Mercadopago**: Latin America
- **Coinpayments**: Cryptocurrency payments (BTC, ETH, LTC, etc.)
- **Nowpayments**: 100+ cryptocurrencies
- **Gourl**: Bitcoin gateway
- **Paghiper**: Brazil (Boleto, PIX)

### Communication Services

- **Telegram Bot API**: Signal notifications, channel monitoring
- **Telegram MTProto**: MadelineProto library for direct Telegram client access
- **Email Services**: SMTP, Mailgun, Postmark, AWS SES
- **SMS**: Vonage (Nexmo) for SMS verification

### AI Services

- **OpenAI API**: GPT models for AI-powered signal parsing and market analysis
- **Google Gemini API**: Alternative AI provider for market confirmation
- **OpenRouter API**: Access to 400+ AI models via unified API

### Trading Services

- **CCXT**: Cryptocurrency exchange library (Binance, Coinbase, etc.)
- **MetaAPI Cloud**: MT4/MT5 broker integration via mtapi.io
- **Firebase**: Real-time notifications for trading bot signals

### Infrastructure

- **Redis**: Optional for caching and queue processing
- **Pusher**: Real-time broadcasting (optional)
- **Laravel Horizon**: Redis queue dashboard (optional)
- **Laravel Octane**: High-performance application server (optional)

### Development Tools

- **Laravel Sail**: Docker development environment
- **Laravel Telescope**: Debug and monitor application (dev only)
- **Scribe**: API documentation generation
