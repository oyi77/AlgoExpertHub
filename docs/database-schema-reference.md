# Database Schema Reference

Complete database schema documentation for AlgoExpertHub Trading Signal Platform.

## Table of Contents

- [Overview](#overview)
- [Core Tables](#core-tables)
- [Financial Tables](#financial-tables)
- [Signal Tables](#signal-tables)
- [Multi-Channel Addon Tables](#multi-channel-addon-tables)
- [Execution Engine Tables](#execution-engine-tables)
- [OpenRouter Tables](#openrouter-tables)
- [Relationships](#relationships)
- [Indexes](#indexes)
- [Constraints](#constraints)

---

## Overview

The platform uses **MySQL 5.7+** or **MariaDB 10.3+** as the database engine. All tables use InnoDB storage engine with UTF-8 character set.

**Naming Conventions**:
- Table names: plural, snake_case (e.g., `users`, `plan_subscriptions`)
- Column names: snake_case (e.g., `user_id`, `created_at`)
- Foreign keys: `{table}_id` (e.g., `user_id`, `plan_id`)
- Timestamps: `created_at`, `updated_at` (automatic)

---

## Core Tables

### users

Platform users who subscribe to plans and receive signals.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| ref_id | bigint | NO | | Referrer user ID (self-reference) |
| username | varchar(255) | NO | | Unique username |
| email | varchar(255) | NO | | Unique email address |
| phone | varchar(255) | NO | | Unique phone number |
| address | text | YES | NULL | User address (JSON) |
| password | varchar(255) | NO | | Hashed password |
| balance | decimal(28,8) | NO | 0.00000000 | Wallet balance |
| image | varchar(255) | YES | NULL | Profile image path |
| is_email_verified | boolean | NO | false | Email verification status |
| is_sms_verified | boolean | NO | false | SMS verification status |
| is_kyc_verified | boolean | NO | false | KYC verification status |
| email_verification_code | varchar(255) | YES | NULL | Email verification code |
| sms_verification_code | varchar(255) | YES | NULL | SMS verification code |
| login_at | datetime | NO | | Last login timestamp |
| kyc_information | text | YES | NULL | KYC documents (JSON) |
| facebook_id | varchar(255) | YES | NULL | Facebook OAuth ID |
| google_id | varchar(255) | YES | NULL | Google OAuth ID |
| status | boolean | NO | | Account status (1=active, 0=inactive) |
| remember_token | varchar(100) | YES | NULL | Remember me token |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`username`)
- UNIQUE (`email`)
- UNIQUE (`phone`)
- INDEX (`ref_id`)
- INDEX (`status`)

**Relationships**:
- `hasMany` PlanSubscription (subscriptions)
- `hasMany` Payment (payments)
- `hasMany` Deposit (deposits)
- `hasMany` Withdraw (withdrawals)
- `belongsTo` User (referred by via ref_id)
- `hasMany` User (referrals via ref_id)
- `hasMany` Ticket (support tickets)
- `hasMany` Transaction (all transactions)
- `hasMany` DashboardSignal (signals on dashboard)
- `hasMany` Trade (executed trades)

---

### admins

System administrators.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| username | varchar(255) | NO | | Unique username |
| email | varchar(255) | NO | | Unique email address |
| password | varchar(255) | NO | | Hashed password |
| type | enum | NO | | Admin type ('super', 'staff') |
| image | varchar(255) | YES | NULL | Profile image path |
| remember_token | varchar(100) | YES | NULL | Remember me token |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`username`)
- UNIQUE (`email`)
- INDEX (`type`)

**Relationships**:
- Spatie Permission: `hasRoles`, `hasPermissions`

---

### plans

Subscription plans.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Plan name |
| slug | varchar(255) | NO | | URL-friendly slug |
| price | decimal(28,8) | NO | | Plan price |
| duration | integer | NO | | Duration in days |
| plan_type | varchar(255) | NO | | Type ('limited', 'lifetime') |
| price_type | varchar(255) | NO | | Price type |
| feature | text | YES | NULL | Plan features (JSON) |
| whatsapp | boolean | NO | | WhatsApp notifications enabled |
| telegram | boolean | NO | | Telegram notifications enabled |
| email | boolean | NO | | Email notifications enabled |
| sms | boolean | NO | | SMS notifications enabled |
| dashboard | boolean | NO | | Dashboard access enabled |
| status | boolean | NO | | Plan status (1=active, 0=inactive) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`name`)
- UNIQUE (`slug`)
- INDEX (`status`, `plan_type`)

**Relationships**:
- `belongsToMany` Signal (via plan_signals pivot)
- `hasMany` PlanSubscription (subscriptions)
- `hasMany` Payment (payments for this plan)

---

### plan_subscriptions

User subscriptions to plans.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | NO | | Foreign key to users.id |
| plan_id | bigint | NO | | Foreign key to plans.id |
| start_date | date | NO | | Subscription start date |
| end_date | date | NO | | Subscription end date |
| is_current | tinyint | NO | 0 | Active subscription flag (1=active, 0=inactive) |
| status | enum | NO | | Status ('active', 'expired', 'cancelled') |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`user_id`, `is_current`)
- INDEX (`plan_id`, `status`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE

**Relationships**:
- `belongsTo` User (user)
- `belongsTo` Plan (plan)

**Business Rules**:
- Only ONE subscription per user can have `is_current = 1`
- Expiry enforced: `end_date < now()` → status = 'expired', is_current = 0

---

## Signal Tables

### signals

Trading signals.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key (random 7-9 digit) |
| title | varchar(255) | NO | | Signal title |
| time_frame_id | bigint | NO | | Foreign key to time_frames.id |
| currency_pair_id | bigint | NO | | Foreign key to currency_pairs.id |
| market_id | bigint | NO | | Foreign key to markets.id |
| open_price | decimal(28,8) | NO | | Entry price |
| sl | decimal(28,8) | NO | | Stop loss price |
| tp | decimal(28,8) | NO | | Take profit price |
| image | varchar(255) | YES | NULL | Chart image path |
| description | longtext | YES | NULL | Signal description (rich text) |
| direction | varchar(255) | NO | | Direction ('buy', 'sell', 'long', 'short') |
| is_published | boolean | NO | | Published status (0=draft, 1=published) |
| published_date | timestamp | YES | NULL | Publication timestamp |
| status | boolean | NO | | Signal status (1=active, 0=inactive) |
| auto_created | tinyint | NO | 0 | Auto-created flag (1=auto, 0=manual) |
| channel_source_id | bigint | YES | NULL | Foreign key to channel_sources.id |
| message_hash | varchar(255) | YES | NULL | Message hash for duplicate detection |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`is_published`, `published_date`)
- INDEX (`currency_pair_id`, `time_frame_id`, `market_id`)
- INDEX (`channel_source_id`, `auto_created`)
- UNIQUE (`message_hash`)
- FOREIGN KEY (`time_frame_id`) REFERENCES `time_frames`(`id`)
- FOREIGN KEY (`currency_pair_id`) REFERENCES `currency_pairs`(`id`)
- FOREIGN KEY (`market_id`) REFERENCES `markets`(`id`)
- FOREIGN KEY (`channel_source_id`) REFERENCES `channel_sources`(`id`) ON DELETE SET NULL

**Relationships**:
- `belongsToMany` Plan (via plan_signals pivot)
- `belongsTo` CurrencyPair (pair)
- `belongsTo` TimeFrame (time)
- `belongsTo` Market (market)
- `belongsTo` ChannelSource (channelSource)

---

### currency_pairs

Trading pairs (e.g., EUR/USD, BTC/USDT).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Pair name (unique) |
| status | boolean | NO | | Status (1=active, 0=inactive) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`name`)
- INDEX (`status`)

---

### time_frames

Trading timeframes (e.g., 1H, 4H, 1D).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Timeframe name (unique) |
| status | boolean | NO | | Status (1=active, 0=inactive) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`name`)
- INDEX (`status`)

---

### markets

Asset markets (Forex, Crypto, Stocks, Commodities).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Market name (unique) |
| status | boolean | NO | | Status (1=active, 0=inactive) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`name`)
- INDEX (`status`)

---

### plan_signals

Pivot table linking plans and signals (many-to-many).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| plan_id | bigint | NO | | Foreign key to plans.id |
| signal_id | bigint | NO | | Foreign key to signals.id |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`plan_id`, `signal_id`)
- INDEX (`signal_id`)
- FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`signal_id`) REFERENCES `signals`(`id`) ON DELETE CASCADE

---

### dashboard_signals

Signals displayed on user dashboard.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | NO | | Foreign key to users.id |
| signal_id | bigint | NO | | Foreign key to signals.id |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`user_id`, `signal_id`)
- INDEX (`user_id`, `signal_id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`signal_id`) REFERENCES `signals`(`id`) ON DELETE CASCADE

---

### user_signals

Historical signals received by users.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | NO | | Foreign key to users.id |
| signal_id | bigint | NO | | Foreign key to signals.id |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`user_id`, `signal_id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`signal_id`) REFERENCES `signals`(`id`) ON DELETE CASCADE

---

## Financial Tables

### payments

Plan subscription payments.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| trx | varchar(255) | NO | | Unique transaction ID (16 chars uppercase) |
| plan_id | bigint | NO | | Foreign key to plans.id |
| user_id | bigint | NO | | Foreign key to users.id |
| gateway_id | bigint | NO | | Foreign key to gateways.id |
| amount | decimal(28,8) | NO | | Plan price |
| rate | decimal(28,8) | NO | | Gateway conversion rate |
| charge | decimal(28,8) | NO | | Gateway charge |
| total | decimal(28,8) | NO | | Total amount (amount * rate + charge) |
| status | tinyint | NO | 0 | Status (0=pending, 1=approved, 2=rejected) |
| plan_expired_at | timestamp | YES | NULL | Subscription expiry |
| detail | text | YES | NULL | Gateway response (JSON) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`trx`)
- INDEX (`user_id`, `status`)
- INDEX (`gateway_id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`gateway_id`) REFERENCES `gateways`(`id`)

---

### deposits

Wallet deposits.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| trx | varchar(255) | NO | | Unique transaction ID |
| user_id | bigint | NO | | Foreign key to users.id |
| gateway_id | bigint | NO | | Foreign key to gateways.id |
| amount | decimal(28,8) | NO | | Deposit amount |
| rate | decimal(28,8) | NO | | Gateway conversion rate |
| charge | decimal(28,8) | NO | | Gateway charge |
| total | decimal(28,8) | NO | | Total amount |
| status | tinyint | NO | 0 | Status (0=pending, 1=approved, 2=rejected) |
| type | tinyint | NO | | Type (1=deposit, 2=withdraw) |
| detail | text | YES | NULL | Gateway response (JSON) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`trx`)
- INDEX (`user_id`, `status`, `type`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`gateway_id`) REFERENCES `gateways`(`id`)

---

### gateways

Payment gateway configurations.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Gateway name (unique) |
| type | tinyint | NO | | Type (0=manual, 1=automated) |
| parameter | json | YES | NULL | Gateway credentials (encrypted) |
| rate | decimal(28,8) | NO | | Conversion rate |
| charge | decimal(28,8) | NO | | Fixed or percentage charge |
| currency | varchar(255) | NO | | Gateway currency |
| status | tinyint | NO | 1 | Status (1=active, 0=inactive) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`name`)
- INDEX (`status`, `type`)

---

### transactions

Log all financial activities.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | NO | | Foreign key to users.id |
| type | enum | NO | | Type ('deposit', 'withdraw', 'referral_commission', 'subscription', etc.) |
| amount | decimal(28,8) | NO | | Transaction amount |
| charge | decimal(28,8) | YES | NULL | Fees/charges |
| description | text | NO | | Human-readable description |
| trx | varchar(255) | YES | NULL | Related payment/deposit trx ID |
| status | tinyint | NO | | Status (0=pending, 1=approved, 2=rejected) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`user_id`, `type`, `status`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE

---

## Multi-Channel Addon Tables

### channel_sources

Signal sources (Telegram, API, RSS, Web scrape).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | NO | | Foreign key to users.id (nullable if admin-owned) |
| name | varchar(255) | NO | | Channel source name |
| type | enum | NO | 'telegram' | Type ('telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss') |
| config | json | NO | | Connection credentials (encrypted) |
| status | enum | NO | 'active' | Status ('active', 'paused', 'error') |
| last_processed_at | timestamp | YES | NULL | Last message processed timestamp |
| error_count | integer | NO | 0 | Consecutive error count |
| last_error | text | YES | NULL | Last error message |
| auto_publish_confidence_threshold | integer | NO | 90 | Auto-publish threshold (0-100) |
| default_plan_id | bigint | YES | NULL | Default plan for auto-created signals |
| default_market_id | bigint | YES | NULL | Default market |
| default_timeframe_id | bigint | YES | NULL | Default timeframe |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`user_id`)
- INDEX (`status`)
- INDEX (`type`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`default_plan_id`) REFERENCES `plans`(`id`) ON DELETE SET NULL
- FOREIGN KEY (`default_market_id`) REFERENCES `markets`(`id`) ON DELETE SET NULL
- FOREIGN KEY (`default_timeframe_id`) REFERENCES `time_frames`(`id`) ON DELETE SET NULL

---

### channel_messages

Messages received from channel sources.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| channel_source_id | bigint | NO | | Foreign key to channel_sources.id |
| message_id | varchar(255) | YES | NULL | External message ID |
| raw_message | text | NO | | Original message text |
| message_hash | varchar(255) | NO | | Hash for duplicate detection |
| parsed_data | json | YES | NULL | Parsed signal data (JSON) |
| signal_id | bigint | YES | NULL | Foreign key to signals.id (if signal created) |
| status | enum | NO | 'pending' | Status ('pending', 'processed', 'failed', 'duplicate') |
| confidence_score | integer | YES | NULL | Parsing confidence (0-100) |
| error_message | text | YES | NULL | Error message if parsing failed |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`message_hash`)
- INDEX (`channel_source_id`, `status`)
- INDEX (`signal_id`)
- FOREIGN KEY (`channel_source_id`) REFERENCES `channel_sources`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`signal_id`) REFERENCES `signals`(`id`) ON DELETE SET NULL

---

## Execution Engine Tables

### execution_connections

Trading connections (exchanges/brokers).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| user_id | bigint | YES | NULL | Foreign key to users.id |
| admin_id | bigint | YES | NULL | Foreign key to admins.id |
| name | varchar(255) | NO | | Connection name |
| exchange_type | enum | NO | | Type ('crypto', 'fx') |
| exchange_name | varchar(255) | NO | | Exchange name (Binance, Coinbase, MT4, etc.) |
| credentials | json | NO | | Encrypted API keys |
| position_sizing_strategy | enum | NO | | Strategy ('fixed', 'percentage', 'fixed_amount') |
| position_sizing_value | decimal(28,8) | NO | | Position sizing value |
| is_active | tinyint | NO | 1 | Active status (1=active, 0=inactive) |
| is_paper_trading | tinyint | NO | 0 | Demo mode (1=demo, 0=live) |
| preset_id | bigint | YES | NULL | Foreign key to trading_presets.id |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`user_id`, `admin_id`, `is_active`)
- INDEX (`preset_id`)
- FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`preset_id`) REFERENCES `trading_presets`(`id`) ON DELETE SET NULL

---

## OpenRouter Tables

### openrouter_configurations

OpenRouter API configurations.

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| name | varchar(255) | NO | | Configuration name |
| model_id | varchar(255) | NO | | Foreign key to openrouter_models.model_id |
| api_key | text | NO | | Encrypted API key |
| enabled | boolean | NO | false | Enabled status |
| use_for_parsing | boolean | NO | false | Use for signal parsing |
| use_for_analysis | boolean | NO | false | Use for market analysis |
| max_tokens | integer | YES | NULL | Max tokens per request |
| temperature | decimal(3,2) | YES | NULL | Temperature (0-2) |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- INDEX (`model_id`)
- INDEX (`enabled`)
- FOREIGN KEY (`model_id`) REFERENCES `openrouter_models`(`model_id`)

---

### openrouter_models

Available OpenRouter models (synced from API).

| Column | Type | Nullable | Default | Description |
|--------|-----|----------|---------|-------------|
| id | bigint | NO | AUTO_INCREMENT | Primary key |
| model_id | varchar(255) | NO | | Model identifier (unique) |
| name | varchar(255) | NO | | Model name |
| provider | varchar(255) | NO | | Provider name |
| context_length | integer | YES | NULL | Context length |
| pricing_prompt | decimal(10,8) | YES | NULL | Prompt pricing |
| pricing_completion | decimal(10,8) | YES | NULL | Completion pricing |
| created_at | timestamp | YES | NULL | Creation timestamp |
| updated_at | timestamp | YES | NULL | Update timestamp |

**Indexes**:
- PRIMARY KEY (`id`)
- UNIQUE (`model_id`)
- INDEX (`provider`)

---

## Relationships

### Entity Relationship Diagram

```
users
  ├── plan_subscriptions (hasMany)
  ├── payments (hasMany)
  ├── deposits (hasMany)
  ├── withdraws (hasMany)
  ├── transactions (hasMany)
  ├── tickets (hasMany)
  ├── dashboard_signals (hasMany)
  ├── user_signals (hasMany)
  └── channel_sources (hasMany)

plans
  ├── plan_subscriptions (hasMany)
  ├── payments (hasMany)
  └── signals (belongsToMany via plan_signals)

signals
  ├── plans (belongsToMany via plan_signals)
  ├── currency_pair (belongsTo)
  ├── time_frame (belongsTo)
  ├── market (belongsTo)
  ├── channel_source (belongsTo)
  ├── dashboard_signals (hasMany)
  └── user_signals (hasMany)

channel_sources
  ├── user (belongsTo)
  ├── channel_messages (hasMany)
  └── signals (hasMany)

channel_messages
  ├── channel_source (belongsTo)
  └── signal (belongsTo)
```

---

## Indexes

### Performance Indexes

Key indexes for query optimization:

- `users`: `ref_id`, `status`
- `plans`: `status`, `plan_type`
- `signals`: `is_published`, `published_date`, `currency_pair_id`, `time_frame_id`, `market_id`
- `plan_subscriptions`: `user_id`, `is_current`, `plan_id`, `status`
- `payments`: `trx` (unique), `user_id`, `status`
- `channel_sources`: `user_id`, `status`, `type`
- `channel_messages`: `message_hash` (unique), `channel_source_id`, `status`

---

## Constraints

### Foreign Key Constraints

All foreign keys use:
- `ON DELETE CASCADE` for dependent records (e.g., user subscriptions deleted when user deleted)
- `ON DELETE SET NULL` for optional references (e.g., signal channel_source_id set to NULL when channel deleted)

### Unique Constraints

- `users`: `username`, `email`, `phone`
- `plans`: `name`, `slug`
- `signals`: `message_hash`
- `payments`: `trx`
- `deposits`: `trx`
- `plan_signals`: (`plan_id`, `signal_id`)
- `dashboard_signals`: (`user_id`, `signal_id`)

---

## Data Types

### Decimal Precision

Financial amounts use `decimal(28,8)`:
- 28 total digits
- 8 decimal places
- Supports very large amounts with high precision

### JSON Fields

JSON fields store structured data:
- `users.address`: User address details
- `users.kyc_information`: KYC documents
- `gateways.parameter`: Gateway credentials (encrypted)
- `channel_sources.config`: Channel configuration
- `channel_messages.parsed_data`: Parsed signal data

### Enum Fields

- `plan_subscriptions.status`: 'active', 'expired', 'cancelled'
- `channel_sources.status`: 'active', 'paused', 'error'
- `channel_sources.type`: 'telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss'
- `channel_messages.status`: 'pending', 'processed', 'failed', 'duplicate'
- `transactions.type`: 'deposit', 'withdraw', 'referral_commission', 'subscription', etc.

---

## Migration Files

All migrations are in `main/database/migrations/`:

- Core tables: `2023_02_*` to `2023_04_*`
- Multi-Channel Addon: `2025_01_27_*`
- OpenRouter Addon: `2025_12_02_*`

Run migrations:
```bash
php artisan migrate
```

---

**Last Updated**: 2025-12-02
