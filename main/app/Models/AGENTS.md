<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Models

## Purpose
Eloquent model layer defining database table mappings, relationships, scopes, casts, and accessors. All models use `Searchable` trait for query filtering. The application domain centers on trading signals, user subscriptions, payments, and backtesting.

## Key Files

| File | Purpose |
|---|---|
| `User.php` | Extends `Authenticatable`; uses `HasFactory`, `Searchable`, `HasApiTokens`. Relationships: `subscriptions`, `payments`, `deposits`, `withdraws`, `trades`, `tickets`, `commissions`, `dashboardSignal`, `onboardingProgress`. Scopes: `active`, `inactive`, `emailVerified`, `kycApproved`, `withActiveSubscription`, `registeredToday/Week/Month`, `searchByName`. |
| `Signal.php` | Trading signal with multi-TP support. Relationships: `plans` (many-to-many), `pair`, `time`, `market`, `channelSource`, `takeProfits`, `aiDecision`. Scopes: `published`, `draft`, `autoCreated`, `byMarket`, `byCurrencyPair`, `byDirection`, `recent`, `publishedToday/ThisWeek`, `withDisplayData`. Auto-generates random ID on creation. |
| `SignalTakeProfit.php` | Multiple take-profit levels per signal (level, price, percentage, lot percentage). |
| `Plan.php` | Subscription plan definition with pricing, features, channel toggles (telegram, whatsapp, email, sms). |
| `PlanSubscription.php` | User-to-plan link with `is_current`, `end_date`, `plan_expired_at`. |
| `Payment.php` | Payment records linked to users and gateways. |
| `Transaction.php` | Financial transaction log (deposits, withdrawals, payments, transfers). |
| `Backtest.php` | Backtest configuration and results (status, total_return, error_message). |
| `BacktestTrade.php` | Individual trades within a backtest run. |
| `Trade.php` | Live/simulated trade records linked to users. |
| `InternalTrade.php` | Internal broker trade positions. |
| `Configuration.php` | Singleton site-wide settings (app name, Telegram token, feature flags). |
| `GlobalConfiguration.php` | Additional global configuration key-value store. |
| `Gateway.php` | Payment gateway configuration records. |
| `WithdrawGateway.php` | Withdrawal gateway settings and limits. |
| `ChannelSource.php` | External channel sources (RSS, Telegram, web scrape) for auto-signals. |
| `ChannelMessage.php` | Messages received from channel sources before signal parsing. |
| `Subscriber.php` | Newsletter/notification subscriber records. |
| `DashboardSignal.php` | Per-user signal visibility on dashboard (pivot: user_id, signal_id). |
| `UserSignal.php` | User signal tracking for delivery confirmation. |
| `Market.php` | Market categories (Forex, Crypto, Stocks, Commodities). |
| `CurrencyPair.php` | Tradeable instrument pairs (EUR/USD, BTC/USD, etc.). |
| `TimeFrame.php` | Signal timeframes (M1, M5, H1, D1, etc.). |
| `Page.php` | CMS pages with sections. |
| `PageSection.php` | Page content sections with ordering. |
| `Content.php` | Generic content storage. |
| `Template.php` | Notification/message templates. |
| `Language.php` | Available languages. |
| `TranslationSetting.php` | Translation key-value pairs per language. |
| `Ticket.php` | Support ticket with status, priority, assignment. |
| `TicketReply.php` | Ticket conversation replies. |
| `Deposit.php` | User deposit requests. |
| `Withdraw.php` | User withdrawal requests. |
| `MoneyTransfer.php` | Inter-user money transfers. |
| `Referral.php` | Referral relationship records. |
| `ReferralCommission.php` | Commission earned from referrals. |
| `AuditLog.php` | Admin/user action audit trail. |
| `UserLog.php` | User activity logging. |
| `AnalyticsEvent.php` | Tracked analytics events. |
| `SystemMetric.php` | System performance metric snapshots. |
| `LoginSecurity.php` | 2FA/OTP settings per user. |
| `UserBehaviorAnalytic.php` | User behavior tracking data. |
| `UserOnboardingProgress.php` | Onboarding step completion per user. |
| `FrontendMedia.php` | Uploaded frontend media assets. |
| `Admin.php` | Admin user model (separate guard). |
| `AdminPasswordReset.php` | Admin password reset tokens. |
| `PlanSignal.php` | Pivot: plan-to-signal many-to-many. |

## For AI Agents

### Working In This Directory
- All models use `HasFactory` and `Searchable` trait from `App\Traits\Searchable`
- `$fillable` is defined for mass-assignment protection; use it for `create()`/`update()`
- `$casts` defined for JSON/array/object fields (e.g., `address` -> object, `kyc_information` -> array)
- Relationship naming: singular for `belongsTo` (e.g., `pair()`, `time()`, `market()`), plural for `hasMany`/`belongsToMany` (e.g., `plans()`, `subscriptions()`)
- Scopes follow pattern: `scope{Action}($query, ...$params)` - use for reusable query logic
- Signal ID is auto-generated as random int (1111111-99999999) in `booted()` callback
- `Model::unguard()` is set globally in `AppServiceProvider::boot()` - all models are mass-assignable

### Common Patterns
- Relationship eager loading: always specify minimal columns `->with('relation:id,name')`
- Scope chaining for complex queries: `Signal::published()->byMarket($id)->withDisplayData()`
- Accessor pattern: `get{Attribute}Attribute()` for computed properties (e.g., `getCurrentSubscriptionAttribute`)
- Pivot tables: `plan_signals`, `plan_subscriptions`, `dashboard_signals`, `user_signals`
- Soft deletes are NOT used; records are hard-deleted
- Timestamps: `created_at`, `updated_at` on all models; some have additional (`published_date`, `end_date`, `completed_at`)

## Dependencies

### Internal
- `App\Traits\Searchable` - Query filtering/sorting trait used by all models
- `App\Traits\HasFactory` (Laravel) - Factory support for testing/seeding
- Cross-model relationships reference addon models (e.g., `Addons\MultiChannelSignalAddon\App\Models\ChannelSource`, `Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision`)

### External
- `illuminate/database` - Eloquent ORM base classes
- `illuminate\Foundation\Auth\User` - Base authenticatable class
- `laravel/sanctum` - `HasApiTokens` trait on User model
