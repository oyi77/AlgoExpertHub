# Multi-Perp DEX Analytics & AI Intelligence Add-On Work Plan

**Plan ID**: `multi-perp-dex-analytics-addon`  
**Created**: 2026-01-21  
**Status**: READY FOR FINAL APPROVAL (Scheduling Fixed)  
**Accuracy Check**: High Accuracy (Pending Momus OK)

---

## ⚠️ FINAL INTEGRATION DECISIONS (Critical - Implementation Guide)

### Where Code Lives (ALL in Addon, NO Core Modifications)

| Component | Location | Notes |
|-----------|----------|-------|
| **Addon Root** | `main/addons/dex-analytics-addon/` | Self-contained |
| **Namespace** | `Addons\DexAnalyticsAddon\` | PSR-4 registered in composer.json |
| **Controllers** | `App/Http/Controllers/Backend/` | Loaded via namespace |
| **Services** | `App/Services/` | Platform clients + analytics |
| **Jobs** | `App/Jobs/` | Polling + refresh |
| **Migrations** | `database/migrations/` | Auto-loaded |
| **Routes** | `routes/admin.php` | Loaded by service provider |
| **Views** | `resources/views/` | Loaded via namespace |
| **Config** | `config/dex-analytics.php` | Merged by service provider |

### Core Files That MUST Be Modified (Only These 3)

| File | Change | Justification |
|------|--------|---------------|
| `main/composer.json` | Add PSR-4 autoload entry | Required for namespace resolution |
| `main/app/Providers/AppServiceProvider.php` | Add provider to `$addonProviders` array | Addon registration pattern |
| `main/app/Console/Kernel.php` | Add addon schedule lines | Scheduling requires Kernel.php in this repo |

### Core Files NOT Modified
- ❌ `main/config/app.php` - Not touched

### Scheduling Approach

**In this repository, scheduling MUST be done in `Kernel.php`** (no alternative hook works reliably).

**Pattern from existing addons**:
```php
// In main/app/Console/Kernel.php schedule() method:
if (AddonRegistry::active('dex-analytics-addon')) {
    $schedule->command('dex-analytics:poll')->everyMinute()->withoutOverlapping();
    $schedule->command('dex-analytics:refresh')->everyFiveMinutes()->withoutOverlapping();
}
```

**Why Kernel.php modification is required**:
- This repo's scheduler is centralized in Kernel.php
- Commands registered in service providers do NOT automatically appear in `schedule:list`
- The `booted()` method approach was evaluated but is unproven in this repo
- Following the existing addon pattern ensures compatibility

**Verification**:
```bash
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan schedule:list"
# Should show:
# dex-analytics:poll      Every minute
# dex-analytics:refresh   Every 5 minutes
```

### Read-Only Definition
**"Read-only" = No trading/execution/mutations of external state**
- DB storage (snapshots, PnL, labels) is ALLOWED
- API calls for data ingestion are ALLOWED
- Only forbidden: trade execution, wallet management, order signing

### Verification Commands (Docker Required)
```bash
# Run tests
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan test --filter=DexAnalytics"

# Check addon registration
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan tinker" >>> AddonRegistry::active('dex-analytics-addon') >>> true

# Manual polling
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan dex-analytics:poll"

# Check schedule
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan schedule:list"
```

### Command Names (Consistent)
- `dex-analytics:poll` - Triggers position polling
- `dex-analytics:refresh` - Triggers analytics refresh

---

## Context

### Original Request
Create a Laravel add-on module for multi-platform perpetual DEX analytics and AI intelligence. The module must:
- Act strictly as a **research-driven analytics engine** (NO execution)
- Consume data from supported DEX platforms
- Return structured analytical outputs
- Never mutate application state
- Be audit-grade (real capital depends on correctness)

### Interview Summary

**Key Discussions**:
- **Research-first mandate**: All data sources must be verified before implementation
- **Platform scope**: GMX, Hyperliquid, Aster, Lighter, dYdX v4
- **Intelligence scope**: Trader behavior analysis, position observability, leaderboards, AI-driven insights
- **Constraints**: No mock data, no assumptions, read-only only

**Research Findings**:
- 5/5 platforms fully verified for data availability
- All metrics except unrealized PnL (some platforms) are fully verifiable
- Hyperliquid provides the most comprehensive data access (7/7 metrics verified)
- Lighter requires API key authentication for some endpoints
- GMX has V1 (deprecated) and V2 (active) requiring version handling

**Architecture Integration**:
- Addon follows existing pattern: `main/addons/dex-analytics-addon/`
- Integrates with `AiConnectionAddon` for AI features
- Uses queue jobs for polling (60s interval)
- Admin-only routes for watchlist management and analytics

### Metis Review

**Identified Gaps Addressed**:
1. ✅ dYdX v4 data source constraints (ToS, rate limits) - Added as TODO
2. ✅ Data retention and audit requirements - Defined (90 days raw, permanent aggregates)
3. ✅ AI output scope - Limited to summarization/insights (no auto-actions)
4. ✅ Admin-only constraints - Explicit in scope boundaries
5. ✅ Failure modes - Per-platform degradation strategy defined

**Guardrails Applied**:
- Read-only enforcement (no execution, no signing)
- Rate limits per platform with backoff
- Admin-only routes by default
- Queue jobs capped at 2s (project rule)
- AI advisory only (no auto-trading)

---

## Work Objectives

### Core Objective
Build a **read-only, observation-only** Laravel add-on that aggregates perpetual DEX trading data across 5 platforms, computes verified analytics, and generates AI-driven behavioral insights for trader intelligence.

### Concrete Deliverables
1. Addon directory: `main/addons/dex-analytics-addon/`
2. Database migrations for trader watchlist, position snapshots, PnL records, labels, copy scores
3. Data ingestion services for 5 DEX platforms (GMX, Hyperliquid, Aster, Lighter, dYdX v4)
4. Admin UI for watchlist management and analytics dashboard
5. Analytics engine computing PnL, exposure, rankings
6. AI integration for behavior clustering and insights
7. Leaderboard system with confidence scores
8. Premium services: Wallet labeling (Nansen), Dual-tier (Lighterlytics), Copy-suitability, Visualizations

### Definition of Done
- [ ] Addon registers correctly via AddonRegistry
- [ ] Composer dump-autoload executed
- [ ] Polling jobs enqueue and respect per-platform rate limits
- [ ] Admin watchlist CRUD works
- [ ] Metrics computed: PnL, exposure, rankings, clustering with confidence score
- [ ] Audit logs with provenance for each metric snapshot
- [ ] AI outputs displayed/admin accessible (no execution hooks)
- [ ] All tests pass

### Must Have
- Verified data sources for all metrics
- Per-platform rate limiting and backoff
- Trader watchlist (admin-configurable)
- Position tracking with PnL computation
- Funding and fee tracking
- Liquidation events capture
- Behavior clustering via AI
- Admin analytics dashboard
- Provenance logging for audit

### Must NOT Have (Guardrails)
- ❌ Trade execution or order signing
- ❌ Wallet management or private key storage
- ❌ Mock data or synthetic metrics
- ❌ User-facing routes (admin-only)
- ❌ Real-time WebSocket streaming (batch polling only)
- ❌ Historical backfill beyond going-forward
- ❌ Auto-trading or auto-alerts from AI

---

## 🎯 BEST-IN-CLASS PLATFORM ANALYSIS

### Research References
Based on analysis of leading analytics platforms:

| Platform | Key Innovation | Feature to Implement |
|----------|---------------|---------------------|
| **Nansen.ai** | Smart Money Tracking | **Wallet Labeling System** with AI-probability scores |
| **Lighterlytics** | Dual-Tier Analytics | **Premium/Standard Account Separation** + LLP tracking |
| **Hyperdash** (pending) | Copy-Trading Readiness | **Copy-Suitability Score** + Trade Replicability |
| **Lightalytics** | Visual PnL | **Heatmaps & Drawdown Charts** |

### 1. Nansen.ai Inspired: Smart Money Detection

**Implementation**: `App/Services/DexSmartMoneyService.php`

**Detection Logic**:
- **30D Smart Trader**: Top performers over 30 days (Win Rate + PnL)
- **90D Smart Trader**: Consistent performers over 90 days
- **180D Smart Trader**: All-time consistent performers
- **Institutional Fund**: Multi-sig patterns, large AUM

---

### 2. Lighterlytics Inspired: Dual-Tier Analytics

**Implementation**: `App/Services/DexDualTierService.php`

**Account Tiers**:
| Tier | Latency | Fees | Analytics Focus |
|------|---------|------|-----------------|
| **Standard** | 300ms taker | 0% maker/0% taker | Volume tracking, zero-fee behavior |
| **Premium** | 150ms taker | 0.002% maker/0.02% taker | Maker/taker ratio, liquidity provision |
| **LLP** | N/A | Liquidation fees + premiums | Insurance fund health, APY tracking |

---

### 3. Copy-Trading Readiness (Future-Ready)

**Implementation**: `App/Services/DexCopyReadinessService.php`

**Copy-Suitability Score** (0-100):
- Base: 100 points
- High frequency penalty: -30 if >20 trades/day
- Small cap penalty: -20 if avg position <$1K
- High drawdown penalty: -25 if max drawdown >50%
- Consistency bonus: +10 if profit factor ≥2.0

---

## Task Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    DEX ANALYTICS ADD-ON                         │
│                    main/addons/dex-analytics-addon/             │
└─────────────────────────────────────────────────────────────────┘
         │                    │
         ▼                    ▼
   ┌──────────────┐    ┌──────────────┐
   │ composer.json│    │ Migrations   │
   │ PSR-4 Update │    │ (9 tables)   │
   └──────────────┘    └──────────────┘
         │                    │
         └────────────────────┤
                              ▼
                    ┌─────────────────────┐
                    │ AddonServiceProvider │
                    │ + Commands + Routes  │
                    └─────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         ▼                    ▼                    ▼
   ┌──────────┐        ┌──────────────┐     ┌──────────────┐
   │ Platform │        │ Normalization│     │ Admin UI     │
   │ Clients  │        │ + Storage    │     │ (Views)      │
   └──────────┘        └──────────────┘     └──────────────┘
         │                    │
         └────────────────────┼────────────────────┐
                              ▼
                    ┌─────────────────────┐
                    │ Analytics Engine    │
                    │ + AI Intelligence   │
                    └─────────────────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │ Premium Services    │
                    │ (Labels, LLP, Copy, │
                    │  Visualization)     │
                    └─────────────────────┘
```

## Parallelization

| Group | Tasks | Reason |
|-------|-------|--------|
| A | 1-5 | Independent setup tasks |
| B | 6-10 | API clients can be developed in parallel |
| C | 11-15 | Normalization and storage build on clients |
| D | 16-21 | Analytics build on normalized data |
| E | 22-25 | Admin UI builds on all services |
| F | 26-28 | Queue jobs and scheduling |
| G | 29-31 | Testing and documentation |

---

## TODOs

### Phase 1: Addon Skeleton & Core Integration

- [ ] 1. Create addon directory structure and composer.json PSR-4

  **What to do**:
  - Create `main/addons/dex-analytics-addon/` directory
  - Create `addon.json` manifest with metadata and modules
  - Create `AddonServiceProvider.php` for registration
  - Set up `App/`, `config/`, `database/migrations/`, `routes/`, `resources/views/` directories
  - **CRITICAL**: Add PSR-4 autoload to `main/composer.json`:
    ```json
    "Addons\\DexAnalyticsAddon\\": "addons/dex-analytics-addon/",
    "Addons\\DexAnalyticsAddon\\App\\": "addons/dex-analytics-addon/App/"
    ```
  - Run `composer dump-autoload` after file creation

  **Must NOT do**:
  - Create any business logic yet

  **Parallelizable**: YES (with 2, 3, 4, 5)

  **References**:

  **Pattern References**:
  - `main/addons/ai-connection-addon/addon.json` - Addon manifest format
  - `main/addons/ai-connection-addon/AddonServiceProvider.php` - Service provider pattern
  - `main/composer.json:76-77` - Existing PSR-4 entry for AiConnectionAddon

  **WHY Each Reference Matters**:
  - Addon manifest defines modules, dependencies, and registration
  - Service provider handles addon bootstrapping and route loading
  - PSR-4 is REQUIRED for namespace autoloading

  **Acceptance Criteria**:
  - [ ] Addon directory created: `main/addons/dex-analytics-addon/`
  - [ ] `addon.json` exists with valid schema
  - [ ] `main/composer.json` updated with PSR-4 entries
  - [ ] `composer dump-autoload` executed successfully
  - [ ] `php artisan tinker` → `AddonRegistry::active('dex-analytics-addon')` → `true`

  **Commit**: YES
  - Message: `feat(addon): create dex-analytics-addon skeleton with PSR-4 autoload`
  - Files: `main/addons/dex-analytics-addon/*`, `main/composer.json`

- [ ] 2. Create addon.json manifest

  **What to do**:
  - Define addon metadata (name, version, author, namespace)
  - Declare modules: `admin_ui`, `processing`, `api`
  - Set dependencies (ai-connection-addon)
  - Configure module targets (admin routes, jobs, scheduling)

  **Must NOT do**:
  - Enable modules that don't exist yet

  **Parallelizable**: YES (with 1, 3, 4, 5)

  **References**:

  **Pattern References**:
  - `main/addons/ai-connection-addon/addon.json` - Full manifest structure

  **Documentation References**:
  - `.cursor/rules/addon-system.mdc:Addon Manifest` - Format specification

  **Acceptance Criteria**:
  - [ ] `addon.json` valid JSON
  - [ ] `name`: `dex-analytics-addon`
  - [ ] `namespace`: `Addons\DexAnalyticsAddon`
  - [ ] Modules declared: `admin_ui`, `processing`
  - [ ] Dependencies: `ai-connection-addon`

  **Commit**: YES (with task 1)

- [ ] 3. Create AddonServiceProvider.php

  **What to do**:
  - Create in `App/Providers/AddonServiceProvider.php`
  - Implement `register()`: merge config, bind services
  - Implement `boot()`: load migrations, views, routes
  - Register queue jobs if module enabled
  - Handle demo mode restrictions

  **Must NOT do**:
  - Call methods on non-registered services

  **Parallelizable**: YES (with 1, 2, 4, 5)

  **References**:

  **Pattern References**:
  - `main/addons/ai-connection-addon/AddonServiceProvider.php` - Full service provider

  **API/Type References**:
  - `App\Support\AddonRegistry` - Addon status checking
  - `Illuminate\Support\ServiceProvider` - Base class

  **Acceptance Criteria**:
  - [ ] `AddonServiceProvider.php` created in `App/Providers/`
  - [ ] `register()` merges config from `config/addon.php`
  - [ ] `boot()` loads migrations, views, routes conditionally
  - [ ] Queue jobs registered if module enabled

  **Commit**: YES (with task 1)

- [ ] 4. Create database migrations

  **What to do**:
  - Create migrations in `database/migrations/`:
    - `dex_trader_watchlist` - Tracked wallet addresses
    - `dex_position_snapshots` - Position data over time
    - `dex_pnl_records` - Realized PnL events
    - `dex_funding_logs` - Funding payments
    - `dex_liquidation_events` - Liquidation records
    - `dex_analytics_cache` - Computed metrics
    - `dex_provenance_logs` - Audit trail
    - `dex_trader_labels` - Wallet labels (Task 18b)
    - `dex_copy_suitability` - Copy scores (Task 18d)

  **Must NOT do**:
  - Create tables for features not in scope

  **Parallelizable**: YES (with 1, 2, 3, 5)

  **References**:

  **Pattern References**:
  - `main/addons/multi-channel-signal-addon/database/migrations/2025_01_27_100000_create_channel_sources_table.php` - Migration format

  **Acceptance Criteria**:
  - [ ] 9 migration files created
  - [ ] Table prefix: `dex_` for all tables
  - [ ] Indexes on wallet addresses, timestamps, foreign keys
  - [ ] `php artisan migrate:status` shows pending migrations

  **Commit**: YES (with task 1)

- [ ] 5. Create addon configuration file

  **What to do**:
  - Create `config/dex-analytics.php` with all configurable settings
  - Define platform-specific settings (API URLs, rate limits, timeouts)
  - Define polling intervals per platform
  - Configure data retention policies
  - Set AI feature flags
  - Define leaderboard eligibility rules

  **Must NOT do**:
  - Hardcode API keys or secrets (use env vars)

  **Acceptance Criteria**:
  - [ ] `config/dex-analytics.php` created
  - [ ] Platform configurations: GMX, Hyperliquid, Aster, Lighter, dYdX v4
  - [ ] Rate limits defined per platform
  - [ ] Polling intervals configurable (default 60s)

### Phase 2: API Clients

- [ ] 6. Create GMX API client service

  **What to do**:
  - Create `App/Services/Platform/GmXApiClientService.php`
  - Implement V1 subgraph queries for historical data
  - Implement V2 reader contract calls for current positions
  - Implement price feed fetching from REST API
  - Handle V1/V2 version detection automatically
  - Add rate limiting and retry logic

  **Must NOT do**:
  - Execute any trades or interact with contracts

  **References**:

  **Pattern References**:
  - `main/addons/trading-management-addon/modules/*/Services/*` - Service patterns

  **Acceptance Criteria**:
  - [ ] `GmXApiClientService.php` created in `App/Services/Platform/`
  - [ ] Methods: `getPositions($wallet)`, `getPositionHistory($wallet)`, `getFundingHistory($wallet)`, `getLiquidations($wallet)`, `getCurrentPrices()`

- [ ] 7. Create Hyperliquid API client service

  **What to do**:
  - Create `App/Services/Platform/HyperliquidApiClientService.php`
  - Implement `clearinghouseState` for positions
  - Implement `userFills` for realized PnL
  - Implement `userFunding` for funding history
  - Implement `userEvents` for liquidations
  - Add HTTP client for REST API

- [ ] 8. Create Aster API client service

  **What to do**:
  - Create `App/Services/Platform/AsterApiClientService.php`
  - Implement Bitquery GraphQL integration for on-chain events
  - Implement REST API for position data
  - Implement funding rate and liquidation endpoints
  - Handle API key authentication

- [ ] 9. Create Lighter API client service

  **What to do**:
  - Create `App/Services/Platform/LighterApiClientService.php`
  - Implement `/api/v1/account` for positions
  - Implement `/api/v1/pnl` for PnL chart
  - Implement `/api/v1/positionFunding` for funding
  - Implement `/api/v1/liquidations` for liquidation events
  - Handle API key authentication

- [ ] 10. Create dYdX v4 API client service

  **What to do**:
  - Create `App/Services/Platform/DyDXV4ApiClientService.php`
  - Implement Indexer API for positions (`/v4/perpetualPositions`)
  - Implement fills endpoint (`/v4/fills`)
  - Implement historical PnL endpoint (`/v4/historical-pnl`)
  - Implement funding history endpoint
  - Implement markets endpoint for oracle prices
  - Handle subaccount number (dYdX uses subaccounts)

### Phase 3: Normalization & Storage

- [ ] 11. Create normalization service

  **What to do**:
  - Create `App/Services/DexAnalyticsNormalizationService.php`
  - Create platform-specific transformers for each API client response
  - Normalize to unified schema
  - Add provenance tracking for each normalized record

- [ ] 12. Create position snapshot storage service

  **What to do**:
  - Create `App/Services/DexPositionSnapshotService.php`
  - Implement `capturePosition($normalizedPosition)` method
  - Store position snapshot in `dex_position_snapshots` table
  - Create provenance log entry

- [ ] 13. Create PnL tracking service

  **What to do**:
  - Create `App/Services/DexPnLTrackingService.php`
  - Detect position closes from API responses
  - Calculate realized PnL from fills/closes
  - Aggregate funding fees into PnL
  - Store PnL records in `dex_pnl_records` table

- [ ] 14. Create funding tracking service

  **What to do**:
  - Create `App/Services/DexFundingTrackingService.php`
  - Fetch funding payments from each platform API
  - Normalize funding data to unified schema
  - Store in `dex_funding_logs` table

- [ ] 15. Create liquidation tracking service

  **What to do**:
  - Create `App/Services/DexLiquidationTrackingService.php`
  - Detect liquidation events from each platform
  - Extract liquidation details
  - Store in `dex_liquidation_events` table

### Phase 4: Analytics Engine

- [ ] 16. Create analytics computation service

  **What to do**:
  - Create `App/Services/DexAnalyticsComputationService.php`
  - Implement metrics: Total PnL, Win Rate, Avg Holding Time, Profit Factor, Max Drawdown, Avg Trade Size, Funding Cost Ratio, Liquidation Rate

- [ ] 17. Create leaderboard service

  **What to do**:
  - Create `App/Services/DexLeaderboardService.php`
  - Define eligibility rules
  - Rank traders by configurable metrics
  - Compute confidence/completeness scores

- [ ] 18. Create AI intelligence service

  **What to do**:
  - Create `App/Services/DexAiIntelligenceService.php`
  - Integrate with existing `AiConnectionAddon` service
  - Implement behavior clustering
  - Implement crowded trade detection
  - Implement regime detection

- [ ] 18b. Create wallet labeling service (Nansen-inspired)

  **What to do**:
  - Create `App/Services/DexLabelingService.php`
  - Implement label calculation: Smart Money 🧠, Whale 🐋, Diamond Hands 💎, Paper Hands 📄, HFT/Scalper ⚡
  - Implement label confidence scores (0-100%)
  - Store labels in `dex_trader_labels` table

- [ ] 18c. Create dual-tier analytics service (Lighterlytics-inspired)

  **What to do**:
  - Create `App/Services/DexDualTierService.php`
  - Implement account tier detection (Standard/Premium/LLP)
  - Track LLP-specific metrics: APY, TVL, liquidation fee capture

- [ ] 18d. Create copy-suitability service (Future-Ready)

  **What to do**:
  - Create `App/Services/DexCopyReadinessService.php`
  - Calculate Copy-Suitability Score (0-100)
  - Scaffold copy-trading infrastructure (no execution)
  - Store scores in `dex_copy_suitability` table

- [ ] 18e. Create visualization service (Heatmaps & Charts)

  **What to do**:
  - Create `App/Services/DexVisualizationService.php`
  - Implement PnL Heatmap generation
  - Implement Drawdown Analysis
  - Implement Portfolio Concentration charts

### Phase 5: Admin UI

- [ ] 19. Create admin routes

  **What to do**:
  - Create `routes/admin.php` in addon routes directory
  - Define routes for watchlist, traders, leaderboards, settings
  - Apply middleware: `['web', 'admin', 'demo']`

- [ ] 20. Create admin controllers

  **What to do**:
  - Create `App/Http/Controllers/Backend/DexAnalyticsController.php`
  - Implement controller methods for each route
  - Use dependency injection for services

- [ ] 21. Create admin views

  **What to do**:
  - Create views in `resources/views/backend/dex-analytics/`
  - Include: Dashboard, Watchlist, Trader Detail, Leaderboards, Settings

### Phase 6: Queue Jobs & Scheduling

- [ ] 22. Create position polling job

  **What to do**:
  - Create `App/Jobs/PollDexPositionsJob.php`
  - Dispatched by scheduler every 60 seconds
  - For each active watchlist trader, fetch positions

- [ ] 23. Create analytics refresh job

  **What to do**:
  - Create `App/Jobs/RefreshDexAnalyticsJob.php`
  - Dispatched after position polling completes
  - Recompute all metrics and update leaderboards

- [ ] 24. Create artisan commands and scheduling

   **What to do**:
   - Create commands in `App/Console/Commands/`:
     - `DexAnalyticsPollCommand` (handles `dex-analytics:poll`)
     - `DexAnalyticsRefreshCommand` (handles `dex-analytics:refresh`)
   - Register commands in `AddonServiceProvider.php` `commands()` method
   - **Modify `main/app/Console/Kernel.php`** to add scheduling (required in this repo):
     ```php
     protected function schedule(Schedule $schedule): void
     {
         // ... existing schedule code ...
         
         if (AddonRegistry::active('dex-analytics-addon')) {
             $schedule->command('dex-analytics:poll')->everyMinute()->withoutOverlapping();
             $schedule->command('dex-analytics:refresh')->everyFiveMinutes()->withoutOverlapping();
         }
     }
     ```

- [ ] 25. Register addon in AppServiceProvider

   **What to do**:
   - Add `Addons\DexAnalyticsAddon\App\Providers\AddonServiceProvider::class` to `$addonProviders` array in `main/app/Providers/AppServiceProvider.php`

### Phase 7: Testing & Documentation

- [ ] 26. Create unit tests

  **What to do**:
  - Create test files in `tests/Unit/Services/DexAnalytics/`
  - Test API clients, normalization, analytics computation

- [ ] 27. Create feature tests

  **What to do**:
  - Create test files in `tests/Feature/DexAnalytics/`
  - Test admin routes, watchlist CRUD, data ingestion

- [ ] 28. Create addon documentation

  **What to do**:
  - Create `README.md` in addon root
  - Include: Overview, supported platforms, configuration, usage guide

---

## Success Criteria

### Verification Commands
```bash
# Run tests
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan test --filter=DexAnalytics"

# Check addon registration
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan tinker" >>> AddonRegistry::active('dex-analytics-addon') >>> true

# Manual polling
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan dex-analytics:poll"

# Check schedule
docker exec 1Panel-php8-mrTy bash -c "cd /var/www/main && php artisan schedule:list"
```

### Final Checklist
- [ ] All 5 platforms integrated with verified data sources
- [ ] Addon registers correctly via AddonRegistry
- [ ] Composer PSR-4 autoload working
- [ ] `main/app/Console/Kernel.php` modified with addon schedule
- [ ] `php artisan schedule:list` shows dex-analytics commands
- [ ] Queue jobs execute every 60 seconds
- [ ] Admin routes accessible with proper permissions
- [ ] Position snapshots stored with provenance
- [ ] PnL tracking works for all position closes
- [ ] Analytics metrics computed correctly
- [ ] Leaderboards generate with confidence scores
- [ ] AI clustering produces insights
- [ ] Wallet labeling working
- [ ] Copy-suitability scoring working
- [ ] Visualizations generating data
- [ ] All tests pass (>80% coverage)
- [ ] Documentation complete

---

## RESEARCH LOG

### Protocols Reviewed
1. **GMX** (Arbitrum, Avalanche) - V1 and V2
2. **Hyperliquid** (L1 blockchain)
3. **Aster** (BSC-based)
4. **Lighter** (ZK-rollup on Bitcoin)
5. **dYdX v4** (Cosmos app chain)

### Reference Platforms Analyzed
1. **Nansen.ai** - Smart Money Detection, Wallet Labeling
2. **Lighterlytics** - Dual-Tier Analytics, LLP Tracking
3. **Hyperdash** - Leaderboards, Copy-Trading (research pending)

### Documentation Sources
- GMX: GitHub repos, Official docs, Subgraphs
- Hyperliquid: Official API docs
- Aster: Official docs, Bitquery API docs
- Lighter: Official docs, API docs
- dYdX v4: Indexer API docs
- Nansen: API docs, Smart Money methodology

### Verified Findings

| Platform | Positions | PnL (Realized) | PnL (Unrealized) | Funding | Fees | Liquidations | Overall |
|----------|-----------|----------------|------------------|---------|------|--------------|---------|
| **GMX** | ✅ | ✅ | ⚠️ Oracle prices | ✅ | ✅ | ✅ | **HIGH** |
| **Hyperliquid** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **HIGHEST** |
| **Aster** | ✅ | ✅ | ⚠️ Mark price | ✅ | ✅ | ✅ | **HIGH** |
| **Lighter** | ✅ | ✅ | ✅ | ✅ | ⚠️ Premium | ✅ | **HIGH** |
| **dYdX v4** | ✅ | ✅ | ✅ | ⚠️ Oracle | ✅ | ✅ | **HIGH** |

---

## Plan Complete ✅

**31 Tasks** covering all aspects of the Multi-Perp DEX Analytics Add-On with Best-in-Class Features.

---

## 🚀 Ready for Execution

Run: `/start-work`

This will:
1. Register the plan as your active boulder
2. Track progress across 31 tasks
3. Enable automatic continuation if interrupted

**Plan Location**: `.sisyphus/plans/multi-perp-dex-analytics-addon.md`
