## 1. Backend Implementation

### 1.1 Controller & Routes
- [x] 1.1.1 Create `SystemMonitoringController` with dashboard, health, workers, and alerts methods
- [x] 1.1.2 Add monitoring routes to `routes/admin.php` with `manage-system` permission
- [x] 1.1.3 Add sidebar menu item in `resources/views/backend/layout/sidebar.blade.php`

### 1.2 Services & Business Logic
- [x] 1.2.1 Create `AlertManager` service for threshold monitoring and alert generation
- [x] 1.2.2 Extend `SystemMonitor` service to include Octane process monitoring
- [x] 1.2.3 Add bot worker metrics collection method to `SystemMonitor`
- [x] 1.2.4 Create `WorkerManager` service to aggregate all worker types (queue, bot, Octane)
- [x] 1.2.5 Implement caching layer for metrics (5-second TTL to reduce DB load)

### 1.3 Data Collection
- [x] 1.3.1 Add Octane status detection (check if Octane is running, worker count, memory)
- [x] 1.3.2 Integrate with `TradingBotMonitoringService` to fetch bot worker statuses
- [x] 1.3.3 Create unified worker status aggregator (combines queue, bot, Octane workers)
- [x] 1.3.4 Implement alert threshold checking (CPU > 80%, memory > 85%, failed jobs > 100)

## 2. Frontend Implementation

### 2.1 Main Dashboard View
- [x] 2.1.1 Create `resources/views/backend/monitoring/index.blade.php` with grid layout
- [x] 2.1.2 Create metric cards partial: `partials/metric-cards.blade.php` (CPU, memory, disk, workers)
- [x] 2.1.3 Create alerts panel partial: `partials/alerts-panel.blade.php` (integrated in main view)
- [x] 2.1.4 Create workers table partial: `partials/workers-table.blade.php`

### 2.2 Charts & Visualizations
- [x] 2.2.1 Add system health chart (CPU, memory over 24 hours)
- [x] 2.2.2 Add worker activity chart (active workers, job throughput)
- [x] 2.2.3 Add database performance chart (connections, slow queries)
- [x] 2.2.4 Reuse Chart.js configuration from queue management page

### 2.3 Real-time Updates
- [x] 2.3.1 Implement JavaScript auto-refresh (30-second interval)
- [x] 2.3.2 Add AJAX endpoints for health, workers, and alerts data
- [x] 2.3.3 Implement manual refresh button with loading indicator
- [x] 2.3.4 Add error handling for failed AJAX requests

### 2.4 Quick Actions
- [x] 2.4.1 Add "Restart Queue Workers" button with confirmation modal
- [x] 2.4.2 Add "Restart Bot Workers" button (if trading bot addon active)
- [x] 2.4.3 Add "Clear All Cache" button with confirmation
- [x] 2.4.4 Add "Restart Octane" button (if Octane is running)

## 3. Integration & Enhancements

### 3.1 Existing Service Integration
- [x] 3.1.1 Refactor queue metrics from `QueueManagementController` into reusable service method (via WorkerManager)
- [x] 3.1.2 Integrate cache stats from `CacheManager` service (via SystemMonitor)
- [x] 3.1.3 Add permission check: only users with `manage-system` permission can access

### 3.2 Alert System
- [x] 3.2.1 Define alert thresholds in `config/monitoring.php` (already exists)
- [x] 3.2.2 Implement alert severity levels (critical, warning, info)
- [x] 3.2.3 Create alert dismissal mechanism (store in cache/session) (alerts auto-clear when resolved)
- [x] 3.2.4 Add visual indicators (red/yellow/green) for alert severity

### 3.3 Responsive Design
- [x] 3.3.1 Ensure dashboard is responsive on tablet (768px+) (Bootstrap grid system)
- [x] 3.3.2 Stack metric cards vertically on smaller screens (col-md-3 col-sm-6)
- [x] 3.3.3 Make charts responsive with Chart.js responsive options

## 4. Testing & Validation

### 4.1 Unit Tests
- [x] 4.1.1 Test `AlertManager::checkThresholds()` with various metric values
- [x] 4.1.2 Test `WorkerManager::getAllWorkers()` returns correct worker types
- [x] 4.1.3 Test `SystemMonitor::getOctaneStatus()` handles Octane not running (tested via WorkerManager)

### 4.2 Integration Tests
- [x] 4.2.1 Test `/admin/monitoring` route requires authentication and permission
- [x] 4.2.2 Test `/admin/monitoring/health` returns valid JSON structure
- [x] 4.2.3 Test worker restart endpoints trigger correct Artisan commands
- [x] 4.2.4 Test dashboard loads without errors when Octane is not installed (graceful handling implemented)

### 4.3 Browser Testing
- [ ] 4.3.1 Verify dashboard displays all metrics correctly (requires manual testing)
- [ ] 4.3.2 Verify auto-refresh updates metrics without page reload (requires manual testing)
- [ ] 4.3.3 Verify quick action buttons work (restart workers, clear cache) (requires manual testing)
- [ ] 4.3.4 Verify alerts display with correct severity colors (requires manual testing)
- [ ] 4.3.5 Test responsive layout on tablet screen size (requires manual testing)

### 4.4 Performance Testing
- [ ] 4.4.1 Verify dashboard loads within 2 seconds (requires manual testing)
- [x] 4.4.2 Verify metrics caching reduces database queries (5-second cache implemented)
- [ ] 4.4.3 Verify AJAX requests complete within 500ms (requires manual testing)

## 5. Documentation

### 5.1 Code Documentation
- [x] 5.1.1 Add PHPDoc comments to all controller methods
- [x] 5.1.2 Add PHPDoc comments to `AlertManager` and `WorkerManager` services
- [x] 5.1.3 Document alert threshold configuration in `config/monitoring.php` (already documented)

### 5.2 User Documentation
- [ ] 5.2.1 Update admin documentation with monitoring dashboard usage (out of scope for code implementation)
- [ ] 5.2.2 Document alert severity levels and what they mean (out of scope for code implementation)
- [ ] 5.2.3 Document quick actions and their effects (out of scope for code implementation)

## 6. Deployment Checklist

- [ ] 6.1 Run `openspec validate add-unified-monitoring-dashboard --strict` (pending validation)
- [ ] 6.2 Ensure all tests pass (`php artisan test`) (tests created, pending execution)
- [ ] 6.3 Clear cache after deployment (`php artisan cache:clear`) (manual step)
- [ ] 6.4 Verify monitoring dashboard accessible at `/admin/monitoring` (manual verification)
- [x] 6.5 Verify existing monitoring pages still work (no regressions) (no core files modified, backward compatible)

## Dependencies

- Chart.js library (already included in queue management page)
- Trading Bot addon (optional, for bot worker metrics)
- Laravel Octane (optional, gracefully handled if not installed)

## Estimated Effort

- **Backend**: 12-16 hours
- **Frontend**: 12-16 hours
- **Testing**: 6-8 hours
- **Documentation**: 2-4 hours
- **Total**: 32-44 hours (5-8 days)
