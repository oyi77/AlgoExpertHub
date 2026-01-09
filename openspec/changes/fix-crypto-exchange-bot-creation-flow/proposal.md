# Proposal: Fix Crypto Exchange Bot Creation Flow

## Why
- Crypto-focused users report frequent failures when creating automated bots tied to Binance/Bybit/OKX connections.
- Validation gaps allow incomplete credential payloads which later crash CCXT jobs and leak partial secrets into logs.
- UX does not guide users through linking execution connections, resulting in orphaned bot records and support tickets.

## What Changes
- Audit and document the existing creation pipeline across user controllers, services, and execution connection bindings.
- Introduce strict validation + encryption for API keys, secrets, and passphrases before persisting bots.
- Ensure every crypto bot references a healthy execution connection (auto-create or block with actionable guidance).
- Modernize the creation form to show exchange-specific requirements and surface connection health badges.
- Expand feature tests + manuals to cover Binance/Bybit happy paths and the top regression cases.

## Impact
- **Specs impacted**: `exchange-connection`, `security`, `trading-bot`
- **Code impacted**:
  - `main/addons/trading-management-addon/Modules/TradingBot/Controllers/*`
  - `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`
  - `main/addons/trading-management-addon/Modules/Execution/Services/ExecutionConnectionService.php`
  - `resources/views/user/trading-bot/*`
  - `tests/Feature/TradingBot/*`

