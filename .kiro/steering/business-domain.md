---
inclusion: always
---

# Business Domain Rules

## Application Purpose
**AlgoExpertHub Trading Signal Platform** - A subscription-based platform for distributing trading signals to users across multiple asset markets (Forex, Crypto, Stocks). Admins create and publish signals, users subscribe to plans to receive signals, and the platform supports automated signal distribution, multi-channel signal ingestion, automated trading execution, and copy trading.

## Core Business Entities

### 1. User
- **Model**: `App\Models\User`
- **Purpose**: Platform users who subscribe to plans and receive signals
- **Key Fields**: 
  - `id`, `username`, `email`, `password`
  - `balance` (wallet balance)
  - `ref_id` (referrer user ID)
  - `status` (active/inactive)
  - `is_email_verified`, `email_verified_at`
  - `kyc_information`, `kyc_status`
  - `address` (JSON: country, city, zip, etc.)
- **Relationships**:
  - `subscriptions()` - User's plan subscriptions (hasMany PlanSubscription)
  - `currentplan()` - Active subscription (hasMany where is_current=1)
  - `deposits()`, `withdraws()`, `payments()` - Financial transactions
  - `refferals()` - Users referred by this user
  - `refferedBy()` - User who referred this user
  - `tickets()` - Support tickets
  - `transactions()` - All transactions
  - `dashboardSignal()` - Signals on user dashboard
  - `trades()` - Executed trades
- **Authentication**: Laravel's default `Authenticatable`, guard `web`
- **2FA**: Via `loginSecurity()` relationship
- **KYC**: Status tracked, documents in `kyc_information` JSON

### 2. Admin
- **Model**: `App\Models\Admin`
- **Purpose**: Platform administrators who manage system and create signals
- **Types**: `super` (full access) or staff (role-based permissions)
- **Key Fields**: `id`, `username`, `email`, `password`, `type`
- **Authentication**: Guard `admin`
- **Authorization**: Spatie Permission package (`spatie/laravel-permission`)
  - Super admins bypass all permission checks (Gate::before)
  - Staff admins have role-based permissions

### 3. Plan
- **Model**: `App\Models\Plan`
- **Purpose**: Subscription plans that users purchase to receive signals
- **Key Fields**:
  - `id`, `name`, `description`, `price`
  - `plan_type`: `limited` (expires after duration) or `lifetime`
  - `duration` (days, for limited plans)
  - `status` (active/inactive)
- **Relationships**:
  - `signals()` - Signals included in this plan (belongsToMany via plan_signals)
  - `subscriptions()` - User subscriptions to this plan
- **Business Logic**:
  - Users purchase plans via payment gateways
  - Plan expiry calculated: `now()->addDays($plan->duration)` or `now()->addYear(50)` for lifetime
  - Active plan determined by `is_current` flag on subscriptions

### 4. Signal
- **Model**: `App\Models\Signal`
- **Purpose**: Trading signals (buy/sell recommendations) created by admins
- **Key Fields**:
  - `id` (random 7-9 digit, generated in boot)
  - `title`, `description` (rich text, Summernote editor)
  - `currency_pair_id` (FK to CurrencyPair)
  - `time_frame_id` (FK to TimeFrame)
  - `market_id` (FK to Market)
  - `open_price`, `sl` (stop loss), `tp` (take profit)
  - `direction` (buy/sell/long/short)
  - `is_published` (0=draft, 1=published)
  - `published_date` (timestamp when published)
  - `image` (optional chart image)
  - `auto_created` (1 if auto-created from channel, 0 if manual)
  - `channel_source_id` (FK to ChannelSource if auto-created)
  - `message_hash` (for duplicate detection)
- **Relationships**:
  - `plans()` - Plans this signal is assigned to (belongsToMany via plan_signals)
  - `pair()` - Currency pair (belongsTo CurrencyPair)
  - `time()` - Timeframe (belongsTo TimeFrame)
  - `market()` - Market (belongsTo Market)
  - `channelSource()` - Channel that created this signal (belongsTo ChannelSource)
- **Scopes**:
  - `autoCreated()` - Filter auto-created signals
  - `byChannelSource($id)` - Filter by channel source
- **Lifecycle**:
  1. Admin creates signal (draft: is_published=0)
  2. Admin assigns signal to plans
  3. Admin publishes signal (is_published=1, published_date=now())
  4. `SignalService::sent()` distributes to users via Telegram/email/dashboard
  5. Execution Engine (addon) auto-executes on connected exchanges

### 5. CurrencyPair
- **Model**: `App\Models\CurrencyPair`
- **Purpose**: Trading pairs (e.g., EUR/USD, BTC/USDT)
- **Key Fields**: `id`, `name`, `status`
- **Usage**: Associated with signals, filterable by users

### 6. TimeFrame
- **Model**: `App\Models\TimeFrame`
- **Purpose**: Trading timeframes (e.g., 1H, 4H, 1D)
- **Key Fields**: `id`, `name`, `status`
- **Usage**: Associated with signals

### 7. Market
- **Model**: `App\Models\Market`
- **Purpose**: Asset markets (Forex, Crypto, Stocks, Commodities)
- **Key Fields**: `id`, `name`, `status`
- **Usage**: Categorizes signals by market type

### 8. PlanSubscription
- **Model**: `App\Models\PlanSubscription`
- **Purpose**: Tracks user subscriptions to plans
- **Key Fields**:
  - `id`, `user_id`, `plan_id`
  - `start_date`, `end_date`
  - `is_current` (1 for active subscription)
  - `status` (active/expired/cancelled)
- **Business Logic**:
  - Created when user completes payment
  - Expiry enforced: limited plans expire after duration, lifetime never expire
  - Only ONE subscription can have `is_current=1` per user

### 9. Payment & Deposit
- **Models**: `App\Models\Payment`, `App\Models\Deposit`
- **Purpose**: 
  - `Payment` - Plan subscription payments
  - `Deposit` - Wallet deposits
- **Key Fields**:
  - `trx` (unique transaction ID, 16 chars uppercase)
  - `gateway_id` (FK to Gateway)
  - `user_id`, `plan_id` (for Payment)
  - `amount`, `rate`, `charge`, `total`
  - `status` (0=pending, 1=approved, 2=rejected)
- **Workflow**:
  1. User selects plan/gateway
  2. `PaymentService::payNow()` creates Payment/Deposit with trx
  3. User redirected to gateway
  4. Gateway callback updates status
  5. If approved, create PlanSubscription (for payments)

### 10. Gateway & WithdrawGateway
- **Models**: `App\Models\Gateway`, `App\Models\WithdrawGateway`
- **Purpose**: Payment gateway configurations
- **Supported Gateways**:
  - **Manual**: Bank transfer, instructions provided
  - **Automated**: PayPal, Stripe, Paystack, Paytm, Mollie, Mercadopago, Coinpayments, Nowpayments, Paghiper, Gourl (crypto)
- **Key Fields**:
  - `id`, `name`, `type` (0=manual, 1=automated)
  - `parameter` (JSON: gateway credentials)
  - `rate`, `charge`, `status`
- **Service Pattern**: Each gateway has service class `App\Services\Gateway\{Name}Service`
- **Processing**: `PaymentController::gatewayRedirect()` routes to appropriate service

## Core Business Workflows

### Workflow 1: User Registration & Onboarding
1. User registers via `RegistrationController`
2. Email verification sent (if enabled)
3. User verifies email
4. Optional: KYC verification
5. User browses plans, subscribes
6. Default preset assigned (Trading Preset Addon)

### Workflow 2: Plan Subscription
1. User selects plan (`PlanController::index`)
2. User selects payment gateway
3. `PaymentService::payNow()` creates Payment record
4. User redirected to gateway
5. Gateway processes payment
6. Callback updates Payment status
7. If approved:
   - Create `PlanSubscription` record
   - Set `is_current=1`, calculate expiry
   - Send notifications
   - User gains access to plan signals

### Workflow 3: Signal Creation & Distribution (Manual)
1. Admin creates signal via `Backend\SignalController::create`
2. Admin fills: title, pair, timeframe, market, prices, direction, description, image
3. Admin assigns to plans (many-to-many)
4. Admin saves as draft (is_published=0)
5. Admin publishes (is_published=1)
6. `SignalService::sent($signalId)` called:
   - Notifies all users in assigned plans via Telegram
   - Sends email notifications (optional)
   - Adds to user dashboards (`DashboardSignal`)
   - Creates `UserSignal` records
7. Execution Engine (addon) detects published signal, auto-executes on connected exchanges

### Workflow 4: Signal Creation & Distribution (Auto via Multi-Channel Addon)
1. Channel message received (Telegram, API, RSS, Web scrape)
2. `ProcessChannelMessage` job dispatched
3. Message parsed by parsers (Regex, AI, Pattern templates)
4. Signal created as DRAFT (is_published=0, auto_created=1)
5. Linked to `channel_source_id`, `message_hash` stored
6. Admin reviews auto-created signals
7. Admin edits (if needed) and publishes
8. Distribution flow same as manual workflow

### Workflow 5: Automated Trade Execution (Execution Engine Addon)
1. Signal published (is_published=1)
2. `SignalObserver` detects publish event
3. `ExecuteSignalJob` dispatched for each active ExecutionConnection
4. `SignalExecutionService` validates signal, calculates position size
5. Order placed on exchange/broker via CCXT or MT4/MT5 API
6. `ExecutionPosition` created, tracked
7. `MonitorPositionsJob` (runs every minute) updates position, checks SL/TP
8. On SL/TP hit, position closed, analytics updated
9. Notifications sent to user

## Domain Services

### SignalService
- **Location**: `App\Services\SignalService`
- **Responsibilities**:
  - Create signals (`create($request)`)
  - Update signals (`update($request, $signal)`)
  - Publish signals (`sent($signalId)`) - triggers distribution
  - Delete signals (`delete($signal)`)
  - Handle image uploads (Summernote editor)
  - Send Telegram notifications
  - Create dashboard signals for users
- **Key Methods**:
  - `sent($signalId)` - Distribute signal to users, send Telegram messages
  - `create($request)` - Validate, process images, create signal record

### PaymentService
- **Location**: `App\Services\PaymentService`
- **Responsibilities**:
  - Process payments (`payNow($request)`)
  - Handle gateway callbacks
  - Create payment/deposit records
  - Generate unique transaction IDs
  - Calculate totals (amount * rate + charge)
- **Flow**: Gateway selection → Payment creation → Redirect → Callback → Subscription creation

### PlanService
- **Location**: `App\Services\PlanService`
- **Responsibilities**:
  - Create/update/delete plans
  - Manage plan status
  - Calculate pricing

### UserPlanService
- **Location**: `App\Services\UserPlanService`
- **Responsibilities**:
  - Create subscriptions
  - Handle plan expiry
  - Switch user plans
  - Cancel subscriptions

### TelegramChannelService
- **Location**: `App\Services\TelegramChannelService`
- **Responsibilities**:
  - Send signal notifications via Telegram
  - Manage Telegram bot integration
  - Format messages for Telegram

## Important Business Rules

### Rule 1: Plan Exclusivity
- A user can only have ONE active subscription at a time (`is_current=1`)
- When user subscribes to new plan, old subscription's `is_current` set to 0
- Expiry date enforced: expired plans lose `is_current` status

### Rule 2: Signal Assignment
- Signals are assigned to plans (many-to-many via `plan_signals`)
- Only users subscribed to assigned plans receive the signal
- Changing signal's plan assignments does NOT affect already-distributed signals

### Rule 3: Signal Publishing
- Signals MUST be published (is_published=1) to be distributed
- Draft signals (is_published=0) are not sent to users
- Published signals get `published_date` timestamp
- Once published, cannot be "unpublished" (immutable)

### Rule 4: Auto-Created Signals
- Auto-created signals (auto_created=1) start as DRAFTS
- Admin MUST review and approve before publishing
- `message_hash` prevents duplicate signal creation from same message
- Linked to `channel_source_id` for tracking origin

### Rule 5: Payment Flow
- Payment status: 0=pending, 1=approved, 2=rejected
- Only approved payments create subscriptions
- Transaction ID (`trx`) is unique and used for tracking
- Gateway callbacks MUST validate signature/authenticity

### Rule 6: User Balance
- Users have wallet balance (`users.balance`)
- Can deposit via payment gateways
- Can withdraw via withdraw gateways
- Balance used for internal transactions (referral commissions, etc.)

### Rule 7: Referral System
- Users can refer others (`users.ref_id`)
- Referral commissions tracked in `referral_commissions` table
- Commission paid when referred user subscribes

### Rule 8: KYC Verification
- KYC status: `unverified`, `pending`, `approved`, `rejected`
- Required for certain actions (configurable)
- Documents stored in `users.kyc_information` JSON
- Admin reviews and approves/rejects KYC

### Rule 9: 2FA Security
- Optional 2FA via Google Authenticator
- Configured in `login_securities` table
- Middleware `2fa` enforces on protected routes

### Rule 10: Demo Mode
- Demo mode middleware prevents destructive actions
- Used for showcasing platform without data modification
- Admin can enable/disable via config

## Notifications
- **Database Notifications**: Laravel's notifications table
- **Email Notifications**: Queued jobs (`SendEmailJob`)
- **Telegram Notifications**: Via Telegram Bot API
- **Types**:
  - Signal published (to users)
  - Payment approved (to user & admin)
  - Subscription created/expired (to user & admin)
  - Ticket created/replied (to admin)
  - KYC status change (to user)
  - Withdrawal request (to admin)

## Transactions & Financial Records
- **Transaction Model**: `App\Models\Transaction`
- **Purpose**: Log ALL financial activities (deposits, withdrawals, referral commissions, subscriptions)
- **Types**: deposit, withdraw, referral_commission, subscription
- **Fields**: `user_id`, `type`, `amount`, `charge`, `description`, `trx`, `status`

## Referral System
- **Model**: `App\Models\Referral`, `App\Models\ReferralCommission`
- **Flow**:
  1. User A refers User B (User B registers with ref link: `/register/userA_id`)
  2. User B's `ref_id` = User A's id
  3. When User B subscribes, commission calculated
  4. `ReferralCommission` created for User A
  5. User A's balance increased
- **Commission**: Configurable percentage of subscription amount

## Support Tickets
- **Models**: `App\Models\Ticket`, `App\Models\TicketReply`
- **Flow**:
  1. User creates ticket (`TicketController::store`)
  2. Admin notified
  3. Admin/user reply to ticket
  4. Status: pending, answered, closed
  5. Notifications sent on replies

## Dashboard & User Signals
- **DashboardSignal**: Signals shown on user dashboard
- **UserSignal**: Historical signals received by user
- Created when signal distributed to user
- User can view signal history, analytics

## Multi-Tenancy Pattern
- **Admin-owned data**: Can be assigned to users/plans globally
- **User-owned data**: Private to user, cannot be shared
- Examples:
  - Admin-owned channel sources can be assigned to plans
  - User-owned channel sources are private
  - Admin-owned presets can be shared, user presets are private

## Permissions (Spatie Package)
- **Permissions**:
  - `manage-plan`, `signal`, `manage-user`, `manage-gateway`, `manage-addon`, etc.
- **Roles**: Admin roles with permission sets
- **Usage**: Middleware `permission:permission-name,admin`
- **Super Admin**: Bypasses all permission checks

