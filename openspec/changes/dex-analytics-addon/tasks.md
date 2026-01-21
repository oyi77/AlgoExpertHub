# Tasks: Multi-Perp DEX Analytics Addon

## Phase 1: Addon Foundation

- [ ] **Task 1.1 — Add addon skeleton**
  - Create `main/addons/dex-analytics-addon/` structure
  - Add `addon.json` manifest
  - Add `AddonServiceProvider.php`
  - Add PSR-4 autoload entries in `main/composer.json`

- [ ] **Task 1.2 — Database migrations**
  - Create 9 tables for watchlist, snapshots, PnL, funding, liquidations, labels, provenance

- [ ] **Task 1.3 — Config + registration**
  - Add `config/dex-analytics.php`
  - Register provider in `AppServiceProvider`
  - Add schedule entries in `Kernel.php`

## Phase 2: Data Ingestion + Normalization

- [ ] **Task 2.1 — Platform API clients**
  - GMX, Hyperliquid, Aster, Lighter, dYdX v4 clients

- [ ] **Task 2.2 — Normalization services**
  - Normalize positions, PnL, funding, liquidations
  - Write provenance logs

## Phase 3: Analytics Engine

- [ ] **Task 3.1 — Analytics computation**
  - Metrics: PnL, win rate, drawdown, profit factor

- [ ] **Task 3.2 — Leaderboards + labeling**
  - Smart money labels, copy suitability, confidence scoring

- [ ] **Task 3.3 — AI insights integration**
  - Clustering, regime detection, crowded trade signals

## Phase 4: Admin UI

- [ ] **Task 4.1 — Admin routes + controllers**
  - Dashboard, watchlist, analytics, leaderboards, AI insights, settings

- [ ] **Task 4.2 — Admin views**
  - Tabbed analytics and leaderboards, settings, AI insights

- [ ] **Task 4.3 — Admin navigation + assets**
  - Sidebar menu and JS/CSS assets

## Phase 5: User UI

- [ ] **Task 5.1 — User routes + controllers**
  - View-only dashboard, analytics, leaderboards

- [ ] **Task 5.2 — User views + navigation**
  - Filtered data views with subscription checks

## Phase 6: Jobs + Scheduling

- [ ] **Task 6.1 — Polling job**
- [ ] **Task 6.2 — Refresh job**
- [ ] **Task 6.3 — Artisan commands**

## Phase 7: Testing + Documentation

- [ ] **Task 7.1 — Unit tests**
- [ ] **Task 7.2 — Feature tests**
- [ ] **Task 7.3 — Integration/performance tests**
- [ ] **Task 7.4 — Addon documentation**

## Reference Plan

- Detailed implementation plan: `.sisyphus/plans/multi-perp-dex-analytics-addon.md`
- Expanded UI breakdown: `.sisyphus/notepads/multi-perp-dex-analytics-addon/expanded-task-list.md`
