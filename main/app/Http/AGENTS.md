<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# HTTP

## Purpose
HTTP layer handling all incoming requests. Contains the middleware pipeline, request validation, controller logic, API resource transformations, and reusable traits. Routes are processed through `Kernel.php` middleware groups (web, api) before reaching controllers.

## Key Files

| File | Purpose |
|---|---|
| `Kernel.php` | Middleware stack registration: global middleware, `web` group (session, CSRF, logging, frontend optimization), `api` group (throttle, Sanctum, versioning). Registers route middleware aliases. |

## Subdirectories

| Directory | Purpose |
|---|---|
| `Controllers/` | Route handlers organized by domain. Root: `Controller.php` (base with locale init), `SignalController`, `PaymentController`, `CryptoTradeController`, `TradingTerminalController`, `TicketController`, `UserController`, `FrontendController`, `PWAController`, `DocumentationController`, `LogController`, `LoginSecurityController`, `MoneyTransferController`, `DepositController`, `PlanController`, `KycController`, `PayoutController` |
| `Controllers/Api/` | JSON API controllers for mobile/external consumers |
| `Controllers/Auth/` | Authentication controllers (login, register, password reset, email verification) |
| `Controllers/Backend/` | Admin panel controllers (signals, users, plans, settings, analytics, pages, gateways, tickets, translations, backtesting, monitoring) |
| `Controllers/User/` | User dashboard controllers (profile, subscriptions, signals, withdrawals, deposits, trades) |
| `Middleware/` | 30+ middleware classes. Key groups: security (`SecurityHeaders`, `ApiSecurityMiddleware`, `ApiRateLimitMiddleware`), auth (`Authenticate`, `RedirectIfNotAdmin`, `KycMiddleware`, `LoginSecurityMiddleware`, `isEmailVerified`), performance (`CacheResponseMiddleware`, `OptimizeFrontendMiddleware`, `QueryMonitoringMiddleware`, `QueueMonitoringMiddleware`, `ResponsiveDesignMiddleware`), app logic (`CheckOnboarding`, `DemoMiddleware`, `Inactive`, `RegistrationOff`, `IsInstalled`, `InjectPageVariable`) |
| `Requests/` | Form request validation classes extending `BaseFormRequest`. Covers admin, user, signals, plans, payments, deposits, withdrawals, configuration, pages, sections, backtests, tickets, translations. Sub-dir `Trading/` for trading-specific requests. |
| `Resources/` | API resource transformers extending `BaseResource`: `SignalResource`, `UserResource`, `PlanResource`, `PlanSubscriptionResource`, `CurrencyPairResource`, `MarketResource`, `TimeFrameResource`, `ChannelSourceResource` |
| `Traits/` | `ApiResponseTrait.php` - shared response formatting (`successResponse`, `errorResponse`, `paginatedResponse`) used by API controllers |

## For AI Agents

### Working In This Directory
- Controllers should be thin: validate input via Form Requests, delegate to Services, return responses via Resources or `ApiResponseTrait`
- Base `Controller.php` initializes locale from session; do not duplicate this logic
- Use `ApiResponseTrait` in API controllers for consistent JSON responses
- Form Requests in `Requests/` handle all validation; never validate in controllers directly
- Middleware aliases are registered in `Kernel.php` `$routeMiddleware`; use these names in route definitions
- API versioning handled by `ApiVersionMiddleware`; check `Accept` header or URL prefix

### Common Patterns
- Admin routes use `admin` middleware alias (`RedirectIfNotAdmin`)
- User routes use `auth` + `is_email_verified` + `check_onboarding` middleware chain
- Demo mode blocked by `DemoMiddleware` for write operations
- Cache responses via `cache.response` middleware for public-facing pages
- Request logging via `LogRequests` middleware in web group
- Security headers applied globally via `SecurityHeaders` in global middleware stack
- All form requests extend `BaseFormRequest` which provides `failedValidation()` override for consistent error format

## Dependencies

### Internal
- `App\Services\*` - Business logic delegated from controllers
- `App\Models\*` - Eloquent models (direct access discouraged; prefer services)
- `App\Http\Requests\BaseFormRequest` - Base validation class
- `App\Http\Resources\BaseResource` - Base API resource class
- `App\Http\Traits\ApiResponseTrait` - Shared API response methods
- `App\Support\AddonRegistry` - Checked by middleware for addon-gated features

### External
- `illuminate/routing`, `illuminate/http`, `illuminate/validation`
- `laravel/sanctum` - API token authentication
- `spatie/permission` - Role/permission middleware (`permission` alias)
- `laravel/framework` built-in middleware (CSRF, session, throttle, CORS)
