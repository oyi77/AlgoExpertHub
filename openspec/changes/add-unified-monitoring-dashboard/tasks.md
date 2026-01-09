# Tasks: Unified Monitoring Dashboard

## Phase 1: Backend Foundation

- [x] **Task 1.1 — Create `SystemMonitoringController`**
  - Add controller at `app/Http/Controllers/Backend/SystemMonitoringController.php`
  - Implement `index`, `health`, `workers`, `alerts`, `restartWorkers`, `clearCache`
  - Enforce `permission:manage-system`

- [x] **Task 1.2 — Build `WorkerManager` service**
  - Create `app/Services/Monitoring/WorkerManager.php`
  - Aggregate queue, trading-bot, and Octane workers via `getAllWorkers()`
  - Handle missing subsystems gracefully

- [x] **Task 1.3 — Build `AlertManager` service**
  - Create `app/Services/Monitoring/AlertManager.php`
  - `getActiveAlerts()` evaluates CPU, memory, disk, failed jobs, cache hit rate thresholds
  - Persist alerts in cache for 1 hour

- [x] **Task 1.4 — Build `SystemMonitor` service**
  - Create `app/Services/Monitoring/SystemMonitor.php`
  - `collectMetrics()` gathers CPU, memory, disk, DB, cache stats
  - Detect Octane + trading bot workers when available

- [x] **Task 1.5 — Add `config/monitoring.php`**
  - Define alert thresholds + TTLs with `.env` overrides
  - Document defaults for CPU, memory, disk, failed jobs, cache

- [x] **Task 1.6 — Register monitoring routes**
  - Add GET/POST routes for dashboard, health, workers, alerts, restart, cache clear
  - Apply `admin` + `permission:manage-system` middleware stack

## Phase 2: Frontend Implementation

- [x] **Task 2.1 — Create dashboard view**
  - `resources/views/backend/monitoring/index.blade.php` with responsive metric cards
  - 4-column desktop / 2-column tablet grid

- [x] **Task 2.2 — Workers partial**
  - `partials/workers.blade.php` showing queue, bot, Octane workers with state badges
  - Include graceful fallback text

- [x] **Task 2.3 — Alerts partial**
  - `partials/alerts.blade.php` with severity colors + timestamp
  - Empty state for zero alerts

- [x] **Task 2.4 — Charts partial**
  - `partials/charts.blade.php` using Chart.js for 24h CPU/memory/worker/DB graphs
  - Ensure responsive resizing (basic implementation done)

- [x] **Task 2.5 — Auto-refresh JS**
  - Poll every 30s with manual refresh button + loading indicators
  - Update metrics, workers, alerts, charts

- [x] **Task 2.6 — Quick-action JS**
  - AJAX helpers for restart workers + clear cache w/ confirmation + toast feedback

- [x] **Task 2.7 — Sidebar navigation**
  - Add "System Monitoring" menu entry (icon + permission guard)

## Phase 3: Integration & Testing

- [x] **Task 3.1 — Wire up QueueOptimizer metrics**
  - ✅ Uses `QueueOptimizer::getMetrics()` + `monitorHealth()` inside WorkerManager
  - ✅ Shows queue worker health + failures on dashboard

- [x] **Task 3.2 — Integrate CacheManager**
  - ✅ Cache hit-rate + memory surfaced via SystemMonitor::getCacheMetrics()
  - ✅ Cache alerts displayed when thresholds exceeded via AlertManager

- [x] **Task 3.3 — Pull trading bot worker stats**
  - ✅ Trading Bot addon detected via SystemMonitor::getBotWorkers()
  - ✅ Bot worker counts displayed alongside queue workers in WorkerManager

- [x] **Task 3.4 — Validate Octane detection**
  - ✅ Octane detection implemented in SystemMonitor::getOctaneStatus()
  - ✅ Graceful handling for installed, not installed, and stopped states
  - ✅ UI messaging remains stable with fallback messages

- [~] **Task 3.5 — Exercise alert lifecycle**
  - ⚠️ **Requires manual testing** - Alert generation logic implemented
  - ⚠️ **Pending**: Manual verification of threshold-driven alert creation/clearing
  - ✅ Cache persistence + severity mapping implemented

- [~] **Task 3.6 — Responsive QA**
  - ⚠️ **Requires manual testing** - Responsive grid layout implemented
  - ⚠️ **Pending**: Browser testing at 1920px, 768px, mobile breakpoints
  - ✅ Bootstrap 4 responsive classes applied

- [~] **Task 3.7 — Permission tests**
  - ✅ Service-focused tests implemented (`SystemMonitoringTest.php` - 8 tests passing)
  - ⚠️ **Pending**: Full HTTP permission tests (requires database setup)
  - ✅ Routes protected with `permission:manage-system` middleware

- [~] **Task 3.8 — Performance benchmarking**
  - ✅ Metrics cached for 5 seconds (configurable)
  - ⚠️ **Requires manual testing** - Performance validation in staging/production
  - ✅ AJAX endpoints optimized with caching

## Phase 4: Documentation & Polish

- [x] **Task 4.1 — Add PHPDoc coverage**
  - ✅ 36 PHPDoc blocks covering all public/protected methods
  - ✅ All services (SystemMonitor, WorkerManager, AlertManager, MonitoringHistory) documented
  - ✅ Controller methods documented
  - ✅ Class-level documentation added

- [~] **Task 4.2 — Update admin docs**
  - ✅ Implementation documentation in `IMPLEMENTATION.md`
  - ✅ Test documentation in `tests/Feature/Backend/README.md`
  - ⚠️ **Optional**: Additional admin wiki/docs updates (out of scope for core implementation)
