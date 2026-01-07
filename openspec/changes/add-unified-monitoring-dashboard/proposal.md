# Proposal: Add Unified Monitoring Dashboard

## Why

Currently, the admin panel has **fragmented monitoring capabilities** spread across multiple pages:
- Queue management at `/admin/queue` (workers, jobs, throughput)
- Cache management at `/admin/cache` (hit rates, memory)
- System performance at `/admin/general/index` (optimization controls)
- Trading bot monitoring via **API only** (no admin UI)
- Laravel Octane status **not monitored at all**

Administrators need to navigate multiple pages to get a complete picture of system health. There's no unified view showing:
- All worker processes (queue workers, bot workers, Octane workers)
- Real-time system health (CPU, memory, disk, database)
- Active alerts and threshold violations
- Consolidated performance metrics

This creates operational blind spots and makes it difficult to quickly diagnose issues or assess overall platform health.

## What Changes

Create a **unified monitoring dashboard** at `/admin/monitoring` that consolidates all system monitoring capabilities into a single, comprehensive interface:

### New Features
1. **Unified Dashboard Page** - Single-page overview with real-time metrics
2. **Multi-Worker Monitoring** - Track queue workers, bot workers, and Octane processes
3. **System Health Panel** - CPU, memory, disk usage, database connections
4. **Alert System** - Visual alerts for threshold violations with severity levels
5. **Real-time Updates** - Auto-refresh every 30 seconds with manual refresh option
6. **Performance Charts** - Historical trends for key metrics (24-hour view)
7. **Quick Actions** - Restart workers, clear cache, scale resources from dashboard

### Components
- **Backend**:
  - New `SystemMonitoringController` in `app/Http/Controllers/Backend/`
  - Enhanced `SystemMonitor` service to include Octane and bot worker metrics
  - New `AlertManager` service for threshold monitoring
  - API endpoints for real-time data fetching

- **Frontend**:
  - New Blade view `resources/views/backend/monitoring/index.blade.php`
  - JavaScript for real-time updates and chart rendering (Chart.js)
  - Responsive grid layout with metric cards and charts

- **Routes**:
  - `GET /admin/monitoring` - Main dashboard
  - `GET /admin/monitoring/health` - Real-time health data (AJAX)
  - `GET /admin/monitoring/workers` - All worker statuses (AJAX)
  - `GET /admin/monitoring/alerts` - Active alerts (AJAX)
  - `POST /admin/monitoring/workers/{type}/restart` - Restart workers

### Integration Points
- Reuse existing `QueueManagementController` logic for queue metrics
- Integrate with `TradingBotMonitoringService` for bot worker data
- Extend `SystemMonitor` service for Octane process monitoring
- Leverage existing `CacheManager` for cache statistics

## Impact

### Affected Specs
- **NEW**: `system-monitoring` capability (comprehensive monitoring dashboard)
- **MODIFIED**: `performance` (add Octane monitoring)
- **MODIFIED**: `trading-bot` (expose bot worker metrics to admin)

### Affected Code
- **New Files**:
  - `app/Http/Controllers/Backend/SystemMonitoringController.php`
  - `app/Services/Monitoring/AlertManager.php`
  - `resources/views/backend/monitoring/index.blade.php`
  - `resources/views/backend/monitoring/partials/*.blade.php`
  
- **Modified Files**:
  - `app/Services/Monitoring/SystemMonitor.php` (add Octane, bot worker tracking)
  - `routes/admin.php` (add monitoring routes)
  - `resources/views/backend/layout/sidebar.blade.php` (add menu item)

### User Experience Impact
- **Positive**: Admins get single-pane-of-glass visibility into entire system
- **Positive**: Faster incident detection and response
- **Positive**: Reduced context switching between monitoring pages
- **Neutral**: Existing monitoring pages remain functional (no breaking changes)

### Performance Impact
- **Minimal**: Dashboard uses AJAX for updates, no full page reloads
- **Consideration**: Real-time metrics collection adds ~5-10ms overhead per request
- **Mitigation**: Cache metrics for 5 seconds to reduce database queries

## Success Criteria

1. **Dashboard Loads** - `/admin/monitoring` displays all metrics within 2 seconds
2. **Real-time Updates** - Metrics refresh automatically every 30 seconds
3. **Worker Visibility** - Shows status of queue workers, bot workers, and Octane
4. **Alert System** - Displays active alerts with severity (critical/warning/info)
5. **Quick Actions** - Can restart workers and clear cache from dashboard
6. **Mobile Responsive** - Dashboard usable on tablet devices (768px+)
7. **No Regressions** - Existing monitoring pages continue to work

## Timeline Estimate

- **Phase 1 (Backend)**: 2-3 days
  - Controller, services, routes, API endpoints
- **Phase 2 (Frontend)**: 2-3 days
  - Blade views, JavaScript, charts, styling
- **Phase 3 (Testing & Polish)**: 1-2 days
  - Integration testing, UI refinement, documentation
- **Total**: 5-8 days (1-1.5 sprints)

## Risk Assessment

- **Low Risk**: Dashboard is additive, doesn't modify existing functionality
- **Medium Risk**: Real-time metrics collection could impact performance
  - **Mitigation**: Implement caching, rate limiting, and efficient queries
- **Low Risk**: UI complexity manageable with existing Bootstrap/jQuery stack
- **Consideration**: Octane monitoring requires Octane to be running
  - **Mitigation**: Gracefully handle when Octane is not available

## Dependencies

- Existing monitoring services (`SystemMonitor`, `QueueOptimizer`, `CacheManager`)
- Chart.js library (already used in queue management page)
- Laravel Sanctum for API authentication (already configured)
- Trading Bot addon (for bot worker metrics)

## Future Enhancements (Out of Scope)

- Historical data retention beyond 24 hours
- Custom alert threshold configuration UI
- Email/Slack notifications for critical alerts
- Exportable reports and metrics
- Integration with external monitoring tools (Datadog, New Relic)
