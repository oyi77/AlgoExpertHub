# AlgoExpertHub Improvement Plan

Generated: 2026-05-21
Source: Deep audit (security + trading logic + architecture)

## Executive Summary

AlgoExpertHub is a functional Laravel trading platform with signal ingestion, AI-powered parsing, bot execution, and multi-channel distribution. The architecture is solid (service layer, addon system, queue-based jobs). Critical security vulnerabilities and trading logic gaps need immediate attention before any production scaling.

---

## Phase 1: CRITICAL Security Fixes (Week 1)

### 1.1 SQL Injection Remediation
**Priority: CRITICAL**
**Files:**
- `main/app/Services/DatabaseBackupService.php` — lines 114, 116, 120, 203, 247
- `main/app/Services/DataLoadingOptimizationService.php` — line 194

**Problem:** `addslashes()` and string concatenation in raw SQL. `DB::raw()` with user-controllable `$options['use_index']`.

**Fix:**
- Replace `DB::statement("ALTER USER ...")` with parameterized `DB::statement()` using `?` bindings
- Remove `addslashes()` — use Laravel's query builder or parameterized queries
- Validate `$options['use_index']` against a whitelist of known indexes, or remove raw index hints entirely

### 1.2 Mass Assignment on Financial Models
**Priority: CRITICAL**
**Files:**
- `main/app/Models/Withdraw.php` — line 16 (`$guarded = []`)
- `main/app/Models/Deposit.php` — line 17 (`$guarded = []`)
- `main/app/Models/MoneyTransfer.php` — line 13 (`$guarded = []`)
- `main/app/Models/Gateway.php` — no `$fillable` or `$guarded`
- `main/app/Http/Controllers/Api/Admin/GatewayController.php` — lines 80, 136, 235, 277, 302

**Problem:** `$guarded = []` allows any field to be mass-assigned. `Gateway::create($request->all())` is exploitable.

**Fix:**
- Define explicit `$fillable` arrays on all financial models
- Replace `$request->all()` with `$request->validated()` using Form Request classes
- Audit GatewayController to use validated input only

### 1.3 Webhook Authentication
**Priority: CRITICAL**
**Files:**
- `main/routes/api.php` — lines 542-543
- `main/app/Http/Controllers/Api/ApiWebhookController.php` — line 84
- `main/app/Http/Controllers/Api/TelegramWebhookController.php` — line 42

**Problem:** Unauthenticated webhook endpoints. Signature check is conditional — requests without signature bypass verification.

**Fix:**
- Add mandatory HMAC signature verification middleware on all webhook routes
- Add Telegram secret token verification (`X-Telegram-Bot-Api-Secret-Token`)
- Add replay attack protection (timestamp + nonce validation)
- Add rate limiting (100 req/min per channelSourceId)

### 1.4 Translation File Overwrite
**Priority: CRITICAL**
**Files:**
- `main/app/Http/Controllers/Api/Admin/LanguageApiController.php` — line 89

**Problem:** `array_merge($translations, $request->all())` writes arbitrary keys to JSON files.

**Fix:**
- Whitelist allowed translation keys
- Validate input structure matches expected translation format
- Add file write audit logging

---

## Phase 2: HIGH Security Fixes (Week 2)

### 2.1 Hardcoded OAuth Password
**Files:**
- `main/app/Http/Controllers/Auth/FacebookController.php` — line 38
- `main/app/Http/Controllers/Auth/GoogleController.php` — line 41

**Problem:** `encrypt('123456')` as placeholder password for social-auth users.

**Fix:**
- Generate random 32-char password: `encrypt(Str::random(32))`
- Or use a dedicated `social_auth_password` column that's never used for login

### 2.2 XSS via Raw Blade Output
**Files:** 9 instances of `{!! !!}` across views

**Problem:** Unescaped output allows JavaScript injection.

**Fix:**
- Audit each `{!! !!}` usage
- Replace with `{{ }}` where possible
- For HTML content (page builder), use `Purifier::clean()` or `strip_tags()` with allowed tags whitelist

### 2.3 CSP Hardening
**Files:**
- `main/app/Http/Middleware/SecurityHeaders.php` — line 30

**Problem:** `unsafe-inline` and `unsafe-eval` negate CSP protection.

**Fix:**
- Remove `unsafe-inline` — use nonces for inline scripts
- Remove `unsafe-eval` — refactor code that uses `eval()` or `new Function()`
- Add `report-uri` for CSP violation monitoring

### 2.4 Debug Mode in Production
**Files:**
- `main/composer.json` — line 49 (`barryvdh/laravel-debugbar`)

**Problem:** Debugbar exposes DB queries, session data, env vars if APP_DEBUG=true.

**Fix:**
- Move to `require-dev` only (already there, but verify)
- Add CI check: `grep -r "APP_DEBUG=true" .env*` fails build
- Add middleware to block debugbar routes in production

---

## Phase 3: Trading Logic Improvements (Week 3-4)

### 3.1 Webhook Rate Limiting
**Problem:** No rate limiting on signal webhooks. Vulnerable to spam/DoS.

**Fix:**
- Add Laravel rate limiter: `RateLimiter::for('webhook', fn() => Limit::perMinute(100))`
- Apply to webhook routes in `RouteServiceProvider`

### 3.2 Price Fetcher Expansion
**File:** `main/app/Services/AutoSignalService.php` — `fetchCurrentPrice()`

**Problem:** CryptoCompare-only. Forex/indices silently fail.

**Fix:**
- Add MetaAPI price fetcher for forex pairs
- Add fallback chain: CryptoCompare → MetaAPI → CCXT
- Return meaningful error instead of throwing on unsupported pairs

### 3.3 Circuit Breaker Persistence
**File:** `main/app/Services/RiskManagementService.php`

**Problem:** Circuit breakers in cache only. Cache flush = lost protection.

**Fix:**
- Store circuit breaker state in database (new `circuit_breakers` table)
- Use cache as fast-read layer, DB as source of truth
- Add artisan command to reset circuit breakers manually

### 3.4 Worker Process Management
**File:** `main/app/Services/TradingBotWorkerService.php`

**Problem:** PID-based tracking with `shell_exec('ps -p')`. No heartbeat. Crashed workers leave bots in "running" state.

**Fix:**
- Add heartbeat table: `bot_heartbeats` (bot_id, last_beat, pid)
- Workers write heartbeat every 30 seconds
- Health check cron: if `last_beat > 2 min`, mark bot as stopped
- Consider using Laravel Horizon for queue-based workers instead of `nohup` processes

### 3.5 Execution Idempotency
**File:** `main/app/Jobs/ExecutionJob.php`

**Problem:** Retries (up to 3) could create duplicate orders.

**Fix:**
- Generate idempotency key before execution: `{bot_id}_{signal_id}_{timestamp}`
- Check `execution_logs` for existing key before placing order
- Store idempotency key in `execution_logs` table

### 3.6 Position Size Calculation
**File:** `main/app/Workers/ProcessSignalBasedBotWorker.php`

**Problem:** `calculatePositionSize()` returns hardcoded 0.01 for 'percentage' strategy.

**Fix:**
- Implement percentage-based sizing: `size = (balance * risk_pct) / (entry_price * sl_distance)`
- Use existing `RiskCalculatorService` instead of inline calculation
- Add validation for min/max position sizes per exchange

---

## Phase 4: Architecture Improvements (Week 5-6)

### 4.1 Deduplicate Signal Service
**Problem:** Addon `SignalService` and core `SignalService` share near-identical image processing and message formatting.

**Fix:**
- Extract shared logic into `BaseSignalService` abstract class
- Both services extend base, override only exchange-specific logic
- Move to `main/app/Services/Signal/BaseSignalService.php`

### 4.2 Addon Manager Cleanup
**File:** `main/app/Services/Addons/AddonManager.php` — lines 28-35

**Problem:** Hardcoded "consolidated" addon list.

**Fix:**
- Remove hardcoded list
- Use `addon.json` manifest to determine addon status
- Add `consolidated: true` flag to addon.json for migrated addons

### 4.3 Database Index Optimization
**Problem:** Only 1 migration adds performance indexes.

**Fix:**
- Add indexes to: `signals.created_at`, `signals.pair_id`, `channel_messages.hash`, `execution_logs.bot_id`, `execution_logs.created_at`, `deposits.user_id`, `withdrawals.user_id`
- Create migration: `2026_05_22_add_performance_indexes.php`

### 4.4 Error Handling Cleanup
**Problem:** 6 empty catch blocks silently swallow errors.

**Fix:**
- Add `Log::error()` calls in all empty catch blocks
- Add `report()` for critical failures (trade execution, signal distribution)
- Create custom exception classes: `TradeExecutionException`, `SignalParsingException`

---

## Phase 5: Testing & CI (Week 7)

### 5.1 CI Pipeline
**Files:** `.github/workflows/`

**Fix:**
- Create `ci.yml`: PHP 8.1, MySQL 8, Redis
- Run: `composer install`, `php artisan test`, `phpstan analyse`
- Run on: push to main, PRs

### 5.2 Test Coverage
**Problem:** 57 tests exist but no evidence they pass.

**Fix:**
- Run full test suite, fix failures
- Add tests for: webhook authentication, mass assignment protection, circuit breakers, execution idempotency
- Target: 60% coverage on Services/

### 5.3 Static Analysis
**Fix:**
- Add PHPStan (level 5 minimum)
- Add Laravel Pint for code style
- Run in CI

---

## Phase 6: Monitoring & Observability (Week 8)

### 6.1 Trade Audit Trail
**Fix:**
- Create `trade_audit_logs` table (immutable, append-only)
- Log: bot_id, signal_id, action, amount, price, exchange_response, timestamp
- Never update, only insert

### 6.2 Health Dashboard
**Fix:**
- Add `/health` endpoint with: DB connection, Redis connection, queue depth, active bots, last signal received
- Add Prometheus metrics exporter
- Add Grafana dashboard template

### 6.3 Alerting
**Fix:**
- Alert on: circuit breaker triggered, bot crash, webhook failure rate > 5%, execution error rate > 1%
- Integrate with existing Telegram notification system

---

## Implementation Order

| Week | Phase | Effort | Impact |
|------|-------|--------|--------|
| 1 | Critical Security | High | Critical |
| 2 | High Security | Medium | High |
| 3-4 | Trading Logic | High | High |
| 5-6 | Architecture | Medium | Medium |
| 7 | Testing & CI | Medium | High |
| 8 | Monitoring | Medium | Medium |

---

## Files to Create/Modify

### New Files
- `main/database/migrations/2026_05_22_add_performance_indexes.php`
- `main/database/migrations/2026_05_22_create_circuit_breakers_table.php`
- `main/database/migrations/2026_05_22_create_bot_heartbeats_table.php`
- `main/database/migrations/2026_05_22_create_trade_audit_logs_table.php`
- `main/app/Services/Signal/BaseSignalService.php`
- `main/app/Exceptions/TradeExecutionException.php`
- `main/app/Exceptions/SignalParsingException.php`
- `.github/workflows/ci.yml`

### Files to Modify
- `main/app/Services/DatabaseBackupService.php` — SQL injection fix
- `main/app/Services/DataLoadingOptimizationService.php` — SQL injection fix
- `main/app/Models/Withdraw.php` — mass assignment fix
- `main/app/Models/Deposit.php` — mass assignment fix
- `main/app/Models/MoneyTransfer.php` — mass assignment fix
- `main/app/Models/Gateway.php` — mass assignment fix
- `main/app/Http/Controllers/Api/Admin/GatewayController.php` — validated input
- `main/app/Http/Controllers/Api/ApiWebhookController.php` — auth + rate limit
- `main/app/Http/Controllers/Api/TelegramWebhookController.php` — auth
- `main/app/Http/Controllers/Api/Admin/LanguageApiController.php` — input validation
- `main/app/Http/Controllers/Auth/FacebookController.php` — random password
- `main/app/Http/Controllers/Auth/GoogleController.php` — random password
- `main/app/Http/Middleware/SecurityHeaders.php` — CSP hardening
- `main/app/Services/RiskManagementService.php` — DB-backed circuit breakers
- `main/app/Services/TradingBotWorkerService.php` — heartbeat system
- `main/app/Jobs/ExecutionJob.php` — idempotency
- `main/app/Workers/ProcessSignalBasedBotWorker.php` — position sizing
- `main/app/Services/AutoSignalService.php` — multi-source price fetcher
- 6 files with empty catch blocks — add logging
