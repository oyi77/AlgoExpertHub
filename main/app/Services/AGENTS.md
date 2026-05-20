<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Services

## Purpose
Business logic layer containing service classes that encapsulate core application operations. Services handle signal management, user administration, payment processing, trading operations, caching, security, analytics, and monitoring. All services extend `BaseService` which provides standardized response formats, transaction handling, caching helpers, and logging.

## Key Files

| File | Purpose |
|---|---|
| `BaseService.php` | Abstract base class; provides `successResponse()`, `errorResponse()`, `executeInTransaction()`, `cacheResult()`, `invalidateCache()`, `sanitizeInput()`, `applySearchFilters()` |
| `SignalService.php` | CRUD for trading signals, multi-TP support, signal distribution via queue jobs, SMS/Telegram/WhatsApp delivery |
| `SignalModificationService.php` | Detects changes to published signals (SL/TP/price) and triggers modification notifications |
| `AutoSignalService.php` | Auto-creates signals from channel sources (RSS, Telegram, web scrape) |
| `PaymentService.php` | Payment processing, gateway integration, transaction recording |
| `WithdrawService.php` | User withdrawal requests, admin approval flow, gateway dispatch |
| `UserManagementService.php` | Admin CRUD for users, status toggles, bulk operations |
| `UserRegistration.php` | New user registration, referral linking, email verification dispatch |
| `UserLogin.php` | Authentication logic, 2FA checks, login security |
| `UserProfileService.php` | Profile updates, avatar handling, KYC status management |
| `UserPlanService.php` | Plan subscription management, expiry checks, plan upgrades |
| `UserDashboardService.php` | Dashboard data aggregation for authenticated users |
| `BacktestingService.php` | Runs strategy backtests, records trades, calculates performance metrics |
| `MarketDataService.php` | Market data retrieval, caching, real-time price feeds |
| `ConfigurationService.php` | Site configuration management (admin settings CRUD) |
| `GlobalConfigurationService.php` | Global app config reads with caching |
| `TelegramChannelService.php` | Telegram bot/channel integration for signal broadcasting |
| `TranslationService.php` | Multi-language translation management |
| `LanguageService.php` | Language/locale CRUD |
| `PageService.php` | CMS page management |
| `PlanService.php` | Subscription plan CRUD, pricing, feature management |
| `PlanManagementService.php` | Plan lifecycle: activation, renewal, expiration |
| `CacheManager.php` | Tag-based cache invalidation, cache warming |
| `ApiResponseService.php` | Standardized API response formatting |
| `DatabaseBackupService.php` | Database backup operations |
| `EmailVerification.php` | Email verification token generation and validation |
| `LogRotationService.php` | Log file rotation and cleanup |
| `QueueOptimizer.php` | Queue health monitoring, worker scaling, batch dispatch |
| `PerformanceOptimizationService.php` | Query optimization, memory management |
| `QueryOptimizationService.php` | Database query performance tuning |
| `DataLoadingOptimizationService.php` | Eager loading and data prefetch strategies |
| `RealTimeFeedbackService.php` | Real-time user feedback/notification delivery |
| `ResponsiveDesignService.php` | Responsive layout configuration |
| `MenuConfigService.php` | Dynamic menu configuration |
| `SectionManagerService.php` | Page section ordering and management |
| `ThemeManager.php` | Theme switching and asset management |
| `UserTicketService.php` | Support ticket CRUD and assignment |
| `UserWithdrawService.php` | User-facing withdrawal request logic |
| `UserMoneyTransferService.php` | Internal money transfers between users |
| `UserOnboardingService.php` | Onboarding step tracking and completion |
| `ManualGatewayService.php` | Manual payment gateway configuration |
| `InternalBrokerService.php` | Internal broker for simulated trading positions |

## Subdirectories

| Directory | Purpose |
|---|---|
| `Addons/` | Addon lifecycle management (`AddonManager.php`) - install, activate, deactivate, module toggles |
| `Analytics/` | `AnalyticsEngine.php` (event tracking, report generation), `MetricsCollector.php` (system/app metrics) |
| `Gateway/` | Payment gateway adapters (Stripe, PayPal, Paystack, Razorpay, Mercadopago, Mollie, CoinPayments, NowPayments, PerfectMoney, Paytm, Paghiper, Vougepay, Gourl, Manual) - all extend `BaseAdapter` |
| `Monitoring/` | `SystemMonitor.php` - CPU, memory, disk, queue health, database connection monitoring |
| `Security/` | `SecurityManager.php` (threat detection, IP blocking), `RateLimiter.php` (request throttling by IP/user) |
| `Trading/` | `MarketDataService.php` (live price feeds, historical data), `RiskManagementService.php` (position sizing, drawdown limits), `ConnectionHealthMonitor.php` (exchange connectivity checks) |

## For AI Agents

### Working In This Directory
- All service classes should extend `BaseService` for consistent response format (`successResponse` / `errorResponse`)
- Use `executeInTransaction()` for any multi-step database operations
- Use `cacheResult()` and `invalidateCache()` for caching; tag-based invalidation with `['signals', 'plans', 'users', 'config']`
- Return arrays with `type` (success/error), `message`, and optional `data`/`errors` keys
- Dispatch heavy work to Jobs in `App\Jobs\` rather than processing inline
- Use `logOperation()` for audit trails on state-changing operations
- Gateway adapters in `Gateway/` extend `BaseAdapter` and implement `process()` and `verify()` methods

### Common Patterns
- Constructor injection: services receive dependencies via Laravel's container (`CacheManager`, `QueueOptimizer`, etc.)
- Input validation via `validateRequired()` and `sanitizeInput()` from BaseService
- Eager loading to prevent N+1 queries: `->with(['relation:id,name'])`
- Cache keys follow pattern: `{entity}-{id}` or `{entity}-{scope}-{userId}-{hash}`
- Signal distribution: `SignalService::sent()` -> `DistributeSignalJob` -> `SendSignalNotificationJob` (batched per 1000 users)
- Payment flow: controller -> `PaymentService` -> gateway adapter -> `Transaction` model

## Dependencies

### Internal
- `App\Models\*` - Eloquent models for all entities
- `App\Jobs\*` - Async job dispatch (signal distribution, notifications, backtests)
- `App\Helpers\Helper` - File paths, image saving, config retrieval, pagination
- `App\Support\AddonRegistry` - Addon status checks before registering addon services

### External
- `illuminate/support`, `illuminate/cache`, `illuminate/database`, `illuminate/log`
- `telegram-bot/laravel` - Telegram Bot API integration
- `vonage/client` - SMS delivery (Nexmo)
- `ultramsg` - WhatsApp messaging (via cURL)
- Payment SDKs per gateway adapter (Stripe PHP, PayPal SDK, Razorpay, etc.)
