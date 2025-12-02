# AlgoExpertHub Trading Signal Platform

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Technology Stack](#technology-stack)
4. [Architecture](#architecture)
5. [Project Structure](#project-structure)
6. [Database Schema](#database-schema)
7. [Core Workflows](#core-workflows)
8. [Addon System](#addon-system)
9. [Installation](#installation)
10. [Configuration](#configuration)
11. [Development Guide](#development-guide)
12. [API Documentation](#api-documentation)

---

## Overview

**AlgoExpertHub** is a comprehensive Laravel-based subscription platform for distributing trading signals across multiple asset markets (Forex, Crypto, Stocks). The platform enables administrators to create, manage, and distribute trading signals to subscribers through various channels, with support for automated signal ingestion, AI-powered market confirmation, automated trade execution, and copy trading capabilities.

### Key Capabilities

- **Multi-Plan Subscription System** - Flexible subscription plans with payment gateway integration
- **Signal Management** - Manual and automated signal creation, publishing, and distribution
- **Multi-Channel Signal Ingestion** - Automatically import signals from Telegram, APIs, RSS feeds, and web scraping
- **AI-Powered Analysis** - Market confirmation and signal validation using AI models
- **Automated Trading** - Execute trades automatically on connected exchanges/brokers
- **Copy Trading** - Social trading where users can copy other traders' signals
- **Risk Management** - Trading presets with position sizing, stop loss, take profit configurations
- **Admin Panel** - Comprehensive admin interface with role-based permissions
- **User Dashboard** - Feature-rich user dashboard with wallet, referral system, and support tickets

---

## Features

### Core Features

- ✅ User authentication and authorization (2FA, KYC)
- ✅ Plan subscription management
- ✅ Signal creation and distribution
- ✅ Payment gateway integration (15+ gateways)
- ✅ Wallet system (deposits, withdrawals)
- ✅ Referral system with commission tracking
- ✅ Support ticket system
- ✅ Multi-language support
- ✅ Email notifications
- ✅ Telegram notifications

### Advanced Features (Addons)

- ✅ **Multi-Channel Signal Addon** - Automatic signal ingestion from external sources
- ✅ **Trading Execution Engine** - Automated trade execution on exchanges/brokers
- ✅ **Trading Preset Addon** - Risk management presets and position sizing
- ✅ **AI Trading Addon** - AI-powered market analysis and signal confirmation
- ✅ **Filter Strategy Addon** - Technical indicator-based signal filtering
- ✅ **Copy Trading Addon** - Social trading capabilities
- ✅ **Trading Bot Signal Addon** - Integration with external trading bots

---

## Technology Stack

### Backend

- **Framework**: Laravel 9.x
- **PHP**: 8.0.2+
- **Database**: MySQL 5.7+
- **Queue**: Database (or Redis)
- **Cache**: File (or Redis)

### Frontend

- **Templating**: Blade Templates
- **CSS Framework**: Bootstrap 4
- **JavaScript**: jQuery
- **Icons**: Feather Icons, Font Awesome

### Key Packages

- **Authentication**: Laravel Sanctum, Socialite
- **Permissions**: Spatie Laravel Permission
- **2FA**: Google2FA Laravel
- **Image Processing**: Intervention Image
- **Payment Gateways**: Stripe, PayPal, Coinpayments, Paystack, Paytm, Mollie, Mercadopago, Paghiper, Gourl, Nowpayments
- **Telegram**: MadelineProto (MTProto), Telegram Bot API
- **Queue**: Laravel Queue (database driver)
- **Notifications**: Laravel Notifications

### External Services

- Payment Gateways (PayPal, Stripe, Coinpayments, etc.)
- Telegram Bot API
- OpenAI API (AI Trading Addon)
- Google Gemini API (AI Trading Addon)
- OpenRouter API (400+ AI models)
- Firebase (Trading Bot Signal Addon)
- CCXT-supported exchanges (Execution Engine)
- MT4/MT5 brokers via mtapi.io (Execution Engine)

---

## Architecture

### System Architecture

![diagram](./README-1.svg)

### Layered Architecture

![diagram](./README-2.svg)

---

## Project Structure

```
public_html/
├── main/                          # Laravel application root
│   ├── app/                       # Application code
│   │   ├── Adapters/              # External service adapters
│   │   ├── Console/               # Artisan commands
│   │   ├── Contracts/             # Interfaces
│   │   ├── DTOs/                  # Data Transfer Objects
│   │   ├── Exceptions/            # Custom exceptions
│   │   ├── Helpers/               # Helper classes
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Backend/       # Admin controllers
│   │   │   │   ├── User/          # User controllers
│   │   │   │   └── Api/           # API controllers
│   │   │   ├── Middleware/        # Custom middleware
│   │   │   └── Requests/          # Form request validation
│   │   ├── Jobs/                  # Queue jobs
│   │   ├── Mail/                  # Mailable classes
│   │   ├── Models/                # Eloquent models
│   │   ├── Notifications/         # Notification classes
│   │   ├── Parsers/               # Message parsers
│   │   ├── Providers/             # Service providers
│   │   ├── Services/              # Business logic services
│   │   ├── Support/               # Support classes (AddonRegistry)
│   │   ├── Traits/                # Reusable traits
│   │   └── Utility/               # Utility classes
│   ├── addons/                    # Modular addon packages
│   │   ├── multi-channel-signal-addon/
│   │   ├── trading-execution-engine-addon/
│   │   ├── trading-preset-addon/
│   │   ├── ai-trading-addon/
│   │   ├── filter-strategy-addon/
│   │   ├── copy-trading-addon/
│   │   ├── trading-bot-signal-addon/
│   │   └── openrouter-integration-addon/
│   ├── bootstrap/                 # Application bootstrap
│   ├── config/                    # Configuration files
│   ├── database/
│   │   ├── migrations/            # Database migrations
│   │   ├── seeders/               # Database seeders
│   │   └── sql/                   # SQL scripts
│   ├── resources/
│   │   └── views/
│   │       ├── backend/           # Admin views
│   │       ├── frontend/          # User views (with themes)
│   │       └── emails/            # Email templates
│   ├── routes/
│   │   ├── web.php                # Web routes
│   │   ├── admin.php              # Admin routes
│   │   ├── api.php                # API routes
│   │   └── console.php            # Console routes
│   ├── storage/                   # Application storage
│   └── vendor/                    # Composer dependencies
├── asset/                         # Public assets
│   ├── backend/                   # Admin assets
│   └── frontend/                  # User assets (by theme)
├── specs/                         # Feature specifications
│   └── active/                    # Active feature specs
├── .cursor/                       # Cursor IDE rules
│   └── rules/                     # Development rules
└── README.md                      # This file
```

---

## Database Schema

### Core Tables ER Diagram

![diagram](./README-3.svg)

### Addon Tables ER Diagram

![diagram](./README-4.svg)

---

## Core Workflows

### Signal Creation and Distribution Flow

![diagram](./README-5.svg)

### Payment and Subscription Flow

![diagram](./README-6.svg)

### Multi-Channel Signal Ingestion Flow

![diagram](./README-7.svg)

### AI Market Confirmation Flow

![diagram](./README-8.svg)

### Automated Trade Execution Flow

![diagram](./README-9.svg)

---

## Addon System

### Addon Architecture

![diagram](./README-10.svg)

### Addon Registration Flow

![diagram](./README-11.svg)

### Installed Addons

| Addon | Description | Key Features |
|-------|-------------|--------------|
| **Multi-Channel Signal Addon** | Automatic signal ingestion | Telegram, API, RSS, Web scraping, Message parsing, Auto-signal creation |
| **Trading Execution Engine** | Automated trade execution | CCXT exchanges, MT4/MT5 brokers, Position monitoring, Analytics |
| **Trading Preset Addon** | Risk management presets | Position sizing, SL/TP, Multi-TP, Trailing stop, Break-even |
| **AI Trading Addon** | AI-powered analysis | OpenAI, Gemini, Market confirmation, Risk adjustment |
| **Filter Strategy Addon** | Technical indicator filters | RSI, MACD, Moving averages, Custom strategies |
| **Copy Trading Addon** | Social trading | Copy other traders, Subscription management, Analytics |
| **Trading Bot Signal Addon** | External bot integration | Firebase integration, Bot notifications |
| **OpenRouter Integration** | Unified AI gateway | 400+ AI models, Signal parsing, Market analysis |

---

## Installation

### Prerequisites

- PHP 8.0.2 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Composer
- Node.js and NPM (for asset compilation)
- Web server (Apache/Nginx)

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd public_html
```

### Step 2: Install Dependencies

```bash
cd main
composer install
npm install
```

### Step 3: Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your configuration:

```env
APP_NAME="AlgoExpertHub"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

### Step 4: Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### Step 5: Storage and Permissions

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 6: Queue Worker Setup

Create a supervisor configuration file:

```ini
[program:algotrad-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/main/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### Step 7: Cron Job Setup

Add to crontab:

```bash
* * * * * cd /path/to/main && php artisan schedule:run >> /dev/null 2>&1
```

---

## Configuration

### Payment Gateways

Configure payment gateways in Admin Panel: `/admin/payment`

Supported gateways:
- Manual (Bank Transfer)
- PayPal
- Stripe
- Paystack
- Paytm
- Mollie
- Mercadopago
- Coinpayments
- Nowpayments
- Paghiper
- Gourl

### Telegram Configuration

1. Create Telegram Bot via @BotFather
2. Get Bot Token
3. Configure in Admin Panel or `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token
```

### AI Services Configuration

#### OpenAI

```env
OPENAI_API_KEY=your_api_key
```

#### Google Gemini

```env
GEMINI_API_KEY=your_api_key
```

#### OpenRouter

Configure in Admin Panel: `/admin/openrouter/configurations`

### Email Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Exchange/Broker Configuration

Configure in User/Admin Panel for Trading Execution Engine:

- **Crypto Exchanges**: Use CCXT library (Binance, Coinbase, etc.)
- **FX Brokers**: Use MT4/MT5 via mtapi.io

---

## Development Guide

### Coding Standards

- Follow PSR-12 coding standards
- Use Service Layer pattern for business logic
- Controllers should be thin (only HTTP handling)
- All business logic in Services

### Project Structure Conventions

```
app/
├── Services/          # Business logic (REQUIRED for all operations)
├── Http/
│   ├── Controllers/   # HTTP request/response handling only
│   └── Requests/      # Form validation
├── Models/            # Eloquent models
└── Jobs/              # Queue jobs for async processing
```

### Service Pattern Example

```php
// Controller
public function store(Request $request, SignalService $service)
{
    $result = $service->create($request);
    return redirect()->back()->with($result['type'], $result['message']);
}

// Service
public function create($request): array
{
    // Business logic here
    return ['type' => 'success', 'message' => 'Created successfully'];
}
```

### Addon Development

1. Create addon directory: `main/addons/your-addon-name/`
2. Create `addon.json` manifest
3. Create `AddonServiceProvider.php`
4. Register in `AppServiceProvider.php`
5. Follow addon structure conventions

See `.cursor/rules/addon-system.mdc` for detailed guidelines.

### Database Migrations

Always create migrations for schema changes:

```bash
php artisan make:migration create_example_table
```

### Queue Jobs

Use queue for:
- External API calls
- Email sending
- File processing
- Long-running operations

```php
dispatch(new YourJob($data));
```

---

## API Documentation

### Authentication

All API requests require authentication via Laravel Sanctum.

#### Login

```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 1,
        "username": "john_doe",
        "email": "user@example.com"
    }
}
```

#### Authenticated Requests

Include token in header:

```http
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Endpoints

#### Get Signals

```http
GET /api/signals
Authorization: Bearer {token}
```

#### Get User Subscriptions

```http
GET /api/subscriptions
Authorization: Bearer {token}
```

#### Create Deposit

```http
POST /api/deposits
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 100.00,
    "gateway_id": 1
}
```

---

## Support

For issues, questions, or contributions:

- **Documentation**: See `specs/` directory for feature specifications
- **Rules**: See `.cursor/rules/` for development guidelines
- **Issues**: Open an issue in the repository

---

## License

[Add your license information here]

---

## Version History

- **v1.0.0** - Initial release with core features
- **v1.1.0** - Added Multi-Channel Signal Addon
- **v1.2.0** - Added Trading Execution Engine
- **v1.3.0** - Added AI Trading Addon
- **v1.4.0** - Added Copy Trading Addon

---

**Built with ❤️ using Laravel**
