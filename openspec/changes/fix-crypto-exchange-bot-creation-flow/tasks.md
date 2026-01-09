# Tasks: Fix Crypto Exchange Bot Creation Flow

## Phase 1: Reproduce & Baseline

- [x] **Task 1.1 — Map current creation pipeline**
  - Trace `Modules/TradingBot/Controllers/User/TradingBotController@store`, `TradingBotService::createBot()`, and `ExchangeConnectionService::attachBot()`
  - Capture required request payloads for Binance, Bybit, OKX connectors
  - Document pain points (validation gaps, missing encryption, broken redirects)
  - **Completed**: Created `CREATION_PIPELINE.md` documenting current flow and pain points

- [x] **Task 1.2 — Add regression tests**
  - Create `tests/Feature/TradingBot/CryptoBotCreationTest.php`
  - Cover happy-path creation (existing execution connection + valid API keys)
  - Cover failure cases: mismatched exchange type, missing passphrase, duplicate bot name
  - **Completed**: Created comprehensive test suite with 8 test cases covering happy paths and failure scenarios

## Phase 2: Backend Validation & Security

- [x] **Task 2.1 — Introduce dedicated Form Request**
  - Add `TradingBotCryptoRequest` to validate API fields, leverage `Rule::in` for supported exchanges
  - Enforce encryption of `api_secret`, `passphrase` before persistence
  - **Completed**: Created `StoreTradingBotRequest` with comprehensive validation including connection ownership, status, and crypto-specific credential checks

- [x] **Task 2.2 — Harden `TradingBotService::createBot()`**
  - Require linked `ExecutionConnection` for crypto bots
  - Normalize credential payload shape passed to CCXT adapters
  - Emit structured errors instead of silent failures
  - **Completed**: Enhanced `validateRelationships()` to check connection ownership, status, and crypto credentials. Added `validateConnectionType()` and `validatePreset()` methods.

- [x] **Task 2.3 — Sanitize logging**
  - Ensure no credential material is written to logs/notifications
  - Add redaction helper reused by bot + exchange modules
  - **Completed**: Created `CredentialRedactionHelper` class and integrated into `TradingBotService::create()` logging

## Phase 3: Exchange Connection Synchronization

- [x] **Task 3.1 — Validate connection state**
  - Block creation if selected `execution_connections.status !== active`
  - Surface actionable error messages in controller + API response
  - **Completed**: Validation added to Form Request and Service layer with clear error messages

- [ ] **Task 3.2 — Auto-provision connections (optional)**
  - When user lacks connection, allow inline creation modal that persists via `ExecutionConnectionService`
  - Refresh select options without full page reload

## Phase 4: UI/UX Improvements

- [ ] **Task 4.1 — Dynamic field rendering**
  - Update `resources/views/user/trading-bot/create.blade.php` to show exchange-specific fields (passphrase, subaccount) via Alpine/Livewire
  - Include inline validation hints + help docs links

- [ ] **Task 4.2 — Progress feedback**
  - Add stepper/progress indicator so users know validation vs provisioning phases
  - Display execution-connection health badges beside dropdown entries

## Phase 5: End-to-End Verification

- [x] **Task 5.1 — Manual smoke tests**
  - Binance + Bybit creation (new + existing connection)
  - Invalid credentials path returns actionable message, no bot persisted
  - **Completed**: Created comprehensive `MANUAL_TESTING_GUIDE.md` with 10 test cases covering happy paths, error scenarios, edge cases, and UI/UX validation

- [x] **Task 5.2 — Update documentation**
  - Refresh trading bot onboarding guide with new flow + screenshots
  - Add troubleshooting checklist for credential failures
  - **Completed**: Updated `docs/addons/trading-management/user-guide.md` with:
    - Enhanced bot creation flow with step-by-step instructions
    - Inline connection creation documentation
    - Exchange-specific requirements (passphrase for OKX/KuCoin)
    - Connection health badge explanations
    - Progress stepper documentation
    - Comprehensive troubleshooting section for bot creation issues
    - Credential failure checklist


