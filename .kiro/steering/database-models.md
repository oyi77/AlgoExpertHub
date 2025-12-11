---
inclusion: always
---

# Database & Models Rules

## Core Tables & Relationships

### Users Table (`users`)
**Purpose**: Platform users who subscribe to plans and receive signals

**Schema**:
```sql
- id (bigint, primary key)
- username (string, unique)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- is_email_verified (tinyint, default 0)
- password (string, hashed)
- ref_id (bigint, nullable) -- Referrer user ID
- balance (decimal, default 0.00) -- Wallet balance
- status (tinyint, default 1) -- 1=active, 0=inactive
- kyc_status (string, nullable) -- unverified/pending/approved/rejected
- kyc_information (json, nullable) -- KYC documents
- address (json, nullable) -- User address details
- remember_token (string, nullable)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `hasOne` LoginSecurity (2FA)
- `hasMany` PlanSubscription (subscriptions)
- `hasMany` Payment (payments)
- `hasMany` Deposit (deposits)
- `hasMany` Withdraw (withdrawals)
- `hasMany` User (referrals via ref_id)
- `belongsTo` User (referred by via ref_id)
- `hasMany` ReferralCommission (commissions)
- `hasMany` Ticket (support tickets)
- `hasMany` Transaction (all transactions)
- `hasMany` DashboardSignal (signals on dashboard)
- `hasMany` Trade (executed trades)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (username, email)
- INDEX (ref_id, status)

---

### Admins Table (`admins`)
**Purpose**: System administrators

**Schema**:
```sql
- id (bigint, primary key)
- username (string, unique)
- email (string, unique)
- password (string, hashed)
- type (enum: 'super', 'staff') -- Super admin bypasses permissions
- image (string, nullable) -- Profile image
- remember_token (string, nullable)
- created_at, updated_at (timestamps)
```

**Relationships**:
- Spatie Permission: `hasRoles`, `hasPermissions`

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (username, email)
- INDEX (type)

---

### Plans Table (`plans`)
**Purpose**: Subscription plans

**Schema**:
```sql
- id (bigint, primary key)
- name (string)
- description (text, nullable)
- image (string, nullable)
- price (decimal)
- plan_type (enum: 'limited', 'lifetime')
- duration (integer) -- Days (for limited plans)
- status (tinyint, default 1) -- 1=active, 0=inactive
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsToMany` Signal (via plan_signals pivot)
- `hasMany` PlanSubscription (subscriptions)
- `hasMany` Payment (payments for this plan)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (status, plan_type)

---

### Signals Table (`signals`)
**Purpose**: Trading signals

**Schema**:
```sql
- id (bigint, primary key, random 7-9 digit)
- title (string)
- description (text, nullable) -- Rich text from Summernote
- currency_pair_id (bigint, foreign key to currency_pairs.id)
- time_frame_id (bigint, foreign key to time_frames.id)
- market_id (bigint, foreign key to markets.id)
- open_price (decimal)
- sl (decimal) -- Stop loss
- tp (decimal) -- Take profit
- direction (enum: 'buy', 'sell', 'long', 'short')
- is_published (tinyint, default 0) -- 0=draft, 1=published
- published_date (timestamp, nullable)
- image (string, nullable) -- Chart image
- auto_created (tinyint, default 0) -- 1 if created from channel
- channel_source_id (bigint, nullable, foreign key to channel_sources.id)
- message_hash (string, nullable) -- For duplicate detection
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsToMany` Plan (via plan_signals pivot)
- `belongsTo` CurrencyPair (pair)
- `belongsTo` TimeFrame (time)
- `belongsTo` Market (market)
- `belongsTo` ChannelSource (channelSource) -- Multi-Channel Addon

**Scopes**:
- `autoCreated()` - where auto_created = 1
- `byChannelSource($id)` - where channel_source_id = $id

**Indexes**:
- PRIMARY KEY (id)
- INDEX (is_published, published_date)
- INDEX (currency_pair_id, time_frame_id, market_id)
- INDEX (channel_source_id, auto_created)
- UNIQUE (message_hash) -- Prevent duplicate auto-created signals

---

### Currency Pairs Table (`currency_pairs`)
**Purpose**: Trading pairs (e.g., EUR/USD, BTC/USDT)

**Schema**:
```sql
- id (bigint, primary key)
- name (string, unique)
- status (tinyint, default 1)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `hasMany` Signal (signals)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (status)

---

### Time Frames Table (`time_frames`)
**Purpose**: Trading timeframes (e.g., 1H, 4H, 1D)

**Schema**:
```sql
- id (bigint, primary key)
- name (string, unique)
- status (tinyint, default 1)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `hasMany` Signal (signals)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (status)

---

### Markets Table (`markets`)
**Purpose**: Asset markets (Forex, Crypto, Stocks, Commodities)

**Schema**:
```sql
- id (bigint, primary key)
- name (string, unique)
- status (tinyint, default 1)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `hasMany` Signal (signals)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (status)

---

### Plan Subscriptions Table (`plan_subscriptions`)
**Purpose**: User subscriptions to plans

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- plan_id (bigint, foreign key to plans.id)
- start_date (date)
- end_date (date) -- Expiry date
- is_current (tinyint, default 0) -- 1 for active subscription
- status (enum: 'active', 'expired', 'cancelled')
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Plan (plan)

**Business Rules**:
- Only ONE subscription per user can have `is_current = 1`
- Expiry enforced: `end_date < now()` → status = 'expired', is_current = 0

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, is_current)
- INDEX (plan_id, status)

---

### Plan Signals Pivot Table (`plan_signals`)
**Purpose**: Many-to-many relationship between plans and signals

**Schema**:
```sql
- id (bigint, primary key)
- plan_id (bigint, foreign key to plans.id)
- signal_id (bigint, foreign key to signals.id)
- created_at, updated_at (timestamps)
```

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (plan_id, signal_id)
- INDEX (signal_id)

---

### Payments Table (`payments`)
**Purpose**: Plan subscription payments

**Schema**:
```sql
- id (bigint, primary key)
- trx (string, unique) -- Transaction ID (16 chars uppercase)
- plan_id (bigint, foreign key to plans.id)
- user_id (bigint, foreign key to users.id)
- gateway_id (bigint, foreign key to gateways.id)
- amount (decimal) -- Plan price
- rate (decimal) -- Gateway rate
- charge (decimal) -- Gateway charge
- total (decimal) -- amount * rate + charge
- status (tinyint, default 0) -- 0=pending, 1=approved, 2=rejected
- plan_expired_at (timestamp) -- Subscription expiry
- detail (text, nullable) -- Gateway response
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Plan (plan)
- `belongsTo` Gateway (gateway)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (trx)
- INDEX (user_id, status)
- INDEX (gateway_id)

---

### Deposits Table (`deposits`)
**Purpose**: Wallet deposits

**Schema**:
```sql
- id (bigint, primary key)
- trx (string, unique)
- user_id (bigint, foreign key to users.id)
- gateway_id (bigint, foreign key to gateways.id)
- amount (decimal)
- rate (decimal)
- charge (decimal)
- total (decimal)
- status (tinyint, default 0)
- type (tinyint) -- 1=deposit, 2=withdraw
- detail (text, nullable)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Gateway (gateway)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (trx)
- INDEX (user_id, status, type)

---

### Gateways Table (`gateways`)
**Purpose**: Payment gateway configurations

**Schema**:
```sql
- id (bigint, primary key)
- name (string, unique)
- type (tinyint) -- 0=manual, 1=automated
- parameter (json) -- Gateway credentials (encrypted)
- rate (decimal) -- Conversion rate
- charge (decimal) -- Fixed or percentage charge
- currency (string)
- status (tinyint, default 1)
- created_at, updated_at (timestamps)
```

**Supported Gateways**:
- Manual, PayPal, Stripe, Paystack, Paytm, Mollie, Mercadopago, Coinpayments, Nowpayments, Paghiper, Gourl

**Relationships**:
- `hasMany` Payment (payments)
- `hasMany` Deposit (deposits)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (status, type)

---

### Withdraws Table (`withdraws`)
**Purpose**: User withdrawal requests

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- withdraw_gateway_id (bigint, foreign key to withdraw_gateways.id)
- amount (decimal)
- charge (decimal)
- net_amount (decimal) -- amount - charge
- status (tinyint, default 0) -- 0=pending, 1=approved, 2=rejected
- detail (text, nullable) -- Payment details
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` WithdrawGateway (gateway)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, status)

---

### Transactions Table (`transactions`)
**Purpose**: Log all financial activities

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- type (enum: 'deposit', 'withdraw', 'referral_commission', 'subscription', etc.)
- amount (decimal)
- charge (decimal, nullable)
- description (text)
- trx (string, nullable) -- Related transaction ID
- status (tinyint)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, type, status)

---

### Referrals Table (`referrals`)
**Purpose**: Referral tracking

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id) -- Referrer
- referred_user_id (bigint, foreign key to users.id) -- Referred
- status (tinyint)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (referrer via user_id)
- `belongsTo` User (referred via referred_user_id)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, referred_user_id)

---

### Referral Commissions Table (`referral_commissions`)
**Purpose**: Referral commission payouts

**Schema**:
```sql
- id (bigint, primary key)
- commission_to (bigint, foreign key to users.id) -- Referrer
- commission_from (bigint, foreign key to users.id) -- Referred
- amount (decimal)
- type (enum: 'subscription', 'deposit', etc.)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (referrer via commission_to)
- `belongsTo` User (referred via commission_from)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (commission_to, commission_from)

---

### Tickets Table (`tickets`)
**Purpose**: Support tickets

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- subject (string)
- status (enum: 'pending', 'answered', 'closed')
- priority (enum: 'low', 'medium', 'high')
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `hasMany` TicketReply (replies)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, status)

---

### Ticket Replies Table (`ticket_replies`)
**Purpose**: Ticket conversation

**Schema**:
```sql
- id (bigint, primary key)
- ticket_id (bigint, foreign key to tickets.id)
- admin_id (bigint, nullable, foreign key to admins.id)
- user_id (bigint, nullable, foreign key to users.id)
- message (text)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` Ticket (ticket)
- `belongsTo` Admin (admin, nullable)
- `belongsTo` User (user, nullable)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (ticket_id)

---

### Dashboard Signals Table (`dashboard_signals`)
**Purpose**: Signals displayed on user dashboard

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- signal_id (bigint, foreign key to signals.id)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Signal (signal)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, signal_id)
- UNIQUE (user_id, signal_id)

---

### User Signals Table (`user_signals`)
**Purpose**: Historical signals received by users

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- signal_id (bigint, foreign key to signals.id)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Signal (signal)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, signal_id)

---

### Login Securities Table (`login_securities`)
**Purpose**: 2FA (Google Authenticator) settings

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, unique, foreign key to users.id)
- google2fa_secret (string, nullable) -- 2FA secret
- is_enabled (tinyint, default 0) -- 2FA enabled flag
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (user_id)

---

### Configurations Table (`configurations`)
**Purpose**: System-wide configuration

**Schema**:
```sql
- id (bigint, primary key)
- site_name (string)
- site_logo (string, nullable)
- registration (tinyint) -- Enable/disable registration
- email_verification (tinyint) -- Enable/disable email verification
- sms_verification (tinyint) -- Enable/disable SMS verification
- kyc_verification (tinyint) -- Enable/disable KYC
- ... (many other config fields)
- created_at, updated_at (timestamps)
```

**Usage**:
- Single record (ID=1) holds all system config
- Accessed via `Configuration::first()` or helper

**Indexes**:
- PRIMARY KEY (id)

---

### User Logs Table (`user_logs`)
**Purpose**: Audit log for user actions

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key to users.id)
- ip (string)
- browser (string)
- action (string) -- login, logout, password_reset, etc.
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, action)

---

### Jobs Table (`jobs`)
**Purpose**: Laravel queue table (for database queue driver)

**Schema**:
```sql
- id (bigint, primary key)
- queue (string)
- payload (longtext) -- Serialized job
- attempts (tinyint)
- reserved_at (integer, nullable)
- available_at (integer)
- created_at (integer)
```

**Indexes**:
- PRIMARY KEY (id)
- INDEX (queue, reserved_at)

---

### Notifications Table (`notifications`)
**Purpose**: Laravel notifications (database channel)

**Schema**:
```sql
- id (uuid, primary key)
- type (string) -- Notification class
- notifiable_type (string) -- Polymorphic (User, Admin)
- notifiable_id (bigint) -- Polymorphic ID
- data (text) -- JSON notification data
- read_at (timestamp, nullable)
- created_at, updated_at (timestamps)
```

**Indexes**:
- PRIMARY KEY (id)
- INDEX (notifiable_type, notifiable_id, read_at)

---

## Addon-Specific Tables

### Channel Sources Table (`channel_sources`)
**Addon**: Multi-Channel Signal Addon

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, nullable, foreign key to users.id)
- name (string)
- type (enum: 'telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss')
- config (json) -- Connection credentials
- is_admin_owned (tinyint, default 0) -- Admin-owned channel
- scope (enum: 'user', 'plan', 'global', nullable) -- Assignment scope
- status (enum: 'active', 'paused', 'error')
- last_processed_at (timestamp, nullable)
- error_count (integer, default 0)
- last_error (text, nullable)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user, nullable if admin-owned)
- `hasMany` Signal (signals created from this channel)
- `belongsToMany` User (assigned users via channel_source_users pivot)
- `belongsToMany` Plan (assigned plans via channel_source_plans pivot)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, is_admin_owned, status)
- INDEX (type)

---

### Channel Messages Table (`channel_messages`)
**Addon**: Multi-Channel Signal Addon

**Schema**:
```sql
- id (bigint, primary key)
- channel_source_id (bigint, foreign key to channel_sources.id)
- message_id (string) -- External message ID
- raw_message (text) -- Original message
- message_hash (string) -- For duplicate detection
- parsed_data (json, nullable) -- Parsed signal data
- signal_id (bigint, nullable, foreign key to signals.id)
- status (enum: 'pending', 'processed', 'failed', 'duplicate')
- confidence_score (integer, nullable) -- Parsing confidence (0-100)
- error_message (text, nullable)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` ChannelSource (channelSource)
- `belongsTo` Signal (signal, nullable)

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE (message_hash)
- INDEX (channel_source_id, status)
- INDEX (signal_id)

---

### Execution Connections Table (`execution_connections`)
**Addon**: Trading Execution Engine Addon

**Schema**:
```sql
- id (bigint, primary key)
- user_id (bigint, nullable, foreign key to users.id)
- admin_id (bigint, nullable, foreign key to admins.id)
- name (string)
- exchange_type (enum: 'crypto', 'fx')
- exchange_name (string) -- Binance, Coinbase, MT4, etc.
- credentials (json) -- Encrypted API keys
- position_sizing_strategy (enum: 'fixed', 'percentage', 'fixed_amount')
- position_sizing_value (decimal)
- is_active (tinyint, default 1)
- is_paper_trading (tinyint, default 0) -- Demo mode
- preset_id (bigint, nullable, foreign key to trading_presets.id)
- created_at, updated_at (timestamps)
```

**Relationships**:
- `belongsTo` User (user, nullable)
- `belongsTo` Admin (admin, nullable)
- `belongsTo` TradingPreset (preset, nullable)
- `hasMany` ExecutionLog (logs)
- `hasMany` ExecutionPosition (positions)

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, admin_id, is_active)
- INDEX (preset_id)

---

## Model Conventions

### Eloquent Conventions
1. **Table Names**: Plural, snake_case (users, plan_subscriptions)
2. **Model Names**: Singular, PascalCase (User, PlanSubscription)
3. **Primary Key**: `id` (bigint, auto-increment or custom)
4. **Foreign Keys**: `{table}_id` (e.g., user_id, signal_id)
5. **Timestamps**: `created_at`, `updated_at` (automatic)
6. **Soft Deletes**: `deleted_at` (when needed)

### Relationship Naming
- **hasOne**: Singular (loginSecurity)
- **hasMany**: Plural (subscriptions, payments)
- **belongsTo**: Singular (user, plan)
- **belongsToMany**: Plural (signals, plans)

### Casting
Use `$casts` for type casting:
```php
protected $casts = [
    'published_date' => 'datetime',
    'kyc_information' => 'array',
    'address' => 'object',
    'is_email_verified' => 'boolean',
    'balance' => 'decimal:2',
];
```

### Mass Assignment
Use `$guarded = []` (unguarded) OR explicit `$fillable`:
```php
protected $guarded = []; // Allow all
// OR
protected $fillable = ['name', 'email', 'password'];
```

### Custom Primary Keys
For random IDs:
```php
protected static function booted()
{
    static::creating(function ($model) {
        if (!$model->getKey()) {
            $model->id = rand(1111111, 99999999);
        }
    });
}
```

### Searchable Trait
Custom trait `App\Traits\Searchable`:
```php
use Searchable;

public $searchable = ['id', 'username', 'email'];
```

### Scopes
Define query scopes for reusable queries:
```php
public function scopeActive($query)
{
    return $query->where('status', 1);
}

public function scopeAutoCreated($query)
{
    return $query->where('auto_created', 1);
}
```

## Query Optimization

### Eager Loading
Prevent N+1 queries:
```php
$signals = Signal::with('pair', 'time', 'market', 'plans')->get();
```

### Chunking
Process large datasets in chunks:
```php
Signal::chunk(100, function ($signals) {
    // Process batch
});
```

### Pagination
Use pagination for large result sets:
```php
$signals = Signal::paginate(20);
```

### Indexes
Always index:
- Foreign keys
- Frequently queried columns (status, type, etc.)
- Unique constraints
- Composite indexes for multi-column queries

### Caching
Cache expensive queries:
```php
$plans = Cache::remember('plans.active', 3600, function () {
    return Plan::where('status', 1)->get();
});
```

## Data Integrity

### Foreign Key Constraints
Use `onDelete('cascade')` or `onDelete('set null')`:
```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
```

### Unique Constraints
Prevent duplicates:
```php
$table->unique('trx');
$table->unique(['plan_id', 'signal_id']);
```

### Default Values
Set sensible defaults:
```php
$table->tinyInteger('status')->default(1);
$table->decimal('balance', 15, 2)->default(0.00);
```

### JSON Fields
Use JSON for flexible data:
```php
$table->json('config');
$table->json('kyc_information')->nullable();
```

Access in model:
```php
$user->kyc_information['document_type'];
$gateway->parameter->api_key;
```

