# Design: Unified Monitoring Dashboard

## Context

The platform currently has **fragmented monitoring** across multiple admin pages and services:
- Queue management (`QueueManagementController`) tracks Laravel queue workers
- Cache management (`CacheManagementController`) monitors cache performance
- System monitoring service (`SystemMonitor`) collects metrics but has no dedicated UI
- Trading bot workers are monitored via API only (no admin interface)
- Laravel Octane (if running) has no monitoring integration

**Stakeholders:**
- **Platform Administrators**: Need single-pane-of-glass visibility for operations
- **DevOps Engineers**: Require quick incident detection and response
- **System Architects**: Want extensible monitoring framework for future enhancements

**Constraints:**
- Must not break existing monitoring pages (backward compatibility)
- Should reuse existing services and avoid duplication
- Performance overhead must be minimal (< 10ms per request)
- Must work whether Octane or Trading Bot addon is installed or not

## Goals / Non-Goals

### Goals
1. **Unified Visibility**: Single dashboard showing all system components
2. **Real-time Monitoring**: Auto-refreshing metrics without page reload
3. **Actionable Alerts**: Visual indicators for threshold violations
4. **Quick Actions**: Restart workers, clear cache from dashboard
5. **Extensibility**: Easy to add new metrics and worker types
6. **Performance**: Minimal overhead through caching and efficient queries

### Non-Goals
1. **Historical Data Retention**: Beyond 24 hours (future enhancement)
2. **Custom Alert Configuration**: UI for threshold customization (use config file)
3. **External Integrations**: Datadog, New Relic, Prometheus exporters
4. **Email/Slack Notifications**: Alert notifications (future enhancement)
5. **Mobile App**: Dashboard is web-only (responsive for tablets)

## Decisions

### Decision 1: Architecture Pattern - Service Aggregation

**Choice**: Create dedicated aggregation services (`WorkerManager`, `AlertManager`) that compose existing services rather than modifying them.

**Rationale**:
- **Separation of Concerns**: Existing services (`SystemMonitor`, `QueueOptimizer`) remain focused on their specific domains
- **Testability**: Aggregation logic can be tested independently
- **Flexibility**: Easy to add/remove data sources without touching core services
- **Backward Compatibility**: No changes to existing monitoring pages

**Alternatives Considered**:
1. **Modify Existing Services**: Add dashboard-specific methods to `SystemMonitor`
   - ❌ Violates single responsibility principle
   - ❌ Couples dashboard to core monitoring logic
   
2. **Controller-Level Aggregation**: Put all logic in `SystemMonitoringController`
   - ❌ Fat controller anti-pattern
   - ❌ Difficult to test and reuse

**Implementation**:
```php
// WorkerManager aggregates all worker types
class WorkerManager {
    public function getAllWorkers(): array {
        return [
            'queue' => $this->queueOptimizer->getMetrics(),
            'bots' => $this->getBotWorkers(),
            'octane' => $this->getOctaneWorkers(),
        ];
    }
}

// AlertManager checks thresholds across all metrics
class AlertManager {
    public function getActiveAlerts(): array {
        $alerts = [];
        $metrics = $this->systemMonitor->collectMetrics();
        
        if ($metrics['cpu'] > config('monitoring.cpu_threshold')) {
            $alerts[] = ['type' => 'cpu', 'severity' => 'critical'];
        }
        
        return $alerts;
    }
}
```

### Decision 2: Data Refresh Strategy - Hybrid (Cached + Real-time)

**Choice**: Cache metrics for 5 seconds, refresh via AJAX every 30 seconds on frontend.

**Rationale**:
- **Performance**: Reduces database queries from N requests/sec to 1 query/5sec
- **Freshness**: 30-second refresh is acceptable for monitoring (not mission-critical)
- **User Control**: Manual refresh button for immediate updates
- **Scalability**: Handles multiple admins viewing dashboard simultaneously

**Alternatives Considered**:
1. **WebSocket/Server-Sent Events**: Real-time push updates
   - ❌ Adds complexity (requires Redis, Pusher, or custom WebSocket server)
   - ❌ Overkill for monitoring dashboard (30-second refresh is sufficient)
   
2. **No Caching**: Fetch fresh data on every request
   - ❌ Performance impact: 10-20 database queries per dashboard load
   - ❌ Doesn't scale with multiple concurrent admins

**Implementation**:
```php
public function getHealthData(): array {
    return Cache::remember('monitoring:health', 5, function () {
        return [
            'system' => $this->systemMonitor->collectMetrics(),
            'workers' => $this->workerManager->getAllWorkers(),
            'alerts' => $this->alertManager->getActiveAlerts(),
        ];
    });
}
```

**Frontend**:
```javascript
// Auto-refresh every 30 seconds
setInterval(refreshMetrics, 30000);

function refreshMetrics() {
    $.get('/admin/monitoring/health', function(data) {
        updateDashboard(data);
    });
}
```

### Decision 3: Octane Detection - Graceful Degradation

**Choice**: Detect Octane at runtime, show "Not Running" status if unavailable.

**Rationale**:
- **Flexibility**: Dashboard works whether Octane is installed or not
- **User Experience**: Clear messaging when Octane is not available
- **No Hard Dependency**: Octane is optional for the platform

**Implementation**:
```php
public function getOctaneStatus(): array {
    if (!class_exists(\Laravel\Octane\Octane::class)) {
        return ['status' => 'not_installed'];
    }
    
    // Check if Octane server is running
    $process = shell_exec('ps aux | grep "octane:start" | grep -v grep');
    
    if (empty($process)) {
        return ['status' => 'not_running'];
    }
    
    return [
        'status' => 'running',
        'workers' => $this->parseOctaneWorkerCount($process),
        'memory' => $this->parseOctaneMemory($process),
    ];
}
```

### Decision 4: UI Framework - Bootstrap 4 + Chart.js (Existing Stack)

**Choice**: Use existing Bootstrap 4 and Chart.js (already used in queue management page).

**Rationale**:
- **Consistency**: Matches existing admin panel design
- **No New Dependencies**: Reuse libraries already in project
- **Developer Familiarity**: Team already knows Bootstrap and Chart.js
- **Proven**: Queue management page demonstrates this stack works well

**Alternatives Considered**:
1. **Vue.js/React**: Modern reactive framework
   - ❌ Adds build complexity (Webpack, npm dependencies)
   - ❌ Inconsistent with rest of admin panel (Blade + jQuery)
   
2. **Livewire**: Laravel's reactive components
   - ❌ Requires Livewire installation and learning curve
   - ❌ Existing monitoring pages don't use Livewire

**Implementation**: Reuse chart configuration from `resources/views/backend/queue/index.blade.php`

### Decision 5: Alert Storage - In-Memory (Cache) vs Database

**Choice**: Store alerts in cache (Redis/File) with 1-hour TTL, no database persistence.

**Rationale**:
- **Performance**: No database writes for transient alerts
- **Simplicity**: Alerts are ephemeral (resolved when metrics normalize)
- **Scalability**: Cache is faster than database for high-frequency reads

**Alternatives Considered**:
1. **Database Table**: `system_alerts` table with timestamps
   - ❌ Unnecessary persistence for transient alerts
   - ❌ Requires migration and cleanup job
   
2. **Session Storage**: Store alerts in admin session
   - ❌ Alerts lost on logout
   - ❌ Not shared across admin users

**Implementation**:
```php
public function checkThresholds(): void {
    $alerts = $this->generateAlerts();
    Cache::put('monitoring:alerts', $alerts, 3600); // 1 hour
}
```

## Risks / Trade-offs

### Risk 1: Performance Impact of Metrics Collection
- **Risk**: Collecting metrics from multiple sources (queue, bots, Octane, system) could slow down dashboard
- **Mitigation**: 
  - Implement 5-second caching layer
  - Use efficient queries (avoid N+1 problems)
  - Lazy-load charts (only fetch chart data when tab is visible)
- **Monitoring**: Track dashboard load time, alert if > 2 seconds

### Risk 2: Octane Process Detection Reliability
- **Risk**: `ps aux` command may not work in all environments (Docker, restricted shells)
- **Mitigation**:
  - Fallback to checking Octane PID file if exists
  - Gracefully handle command failures (show "Unknown" status)
  - Document environment requirements
- **Monitoring**: Log failures to detect environment issues

### Risk 3: Alert Fatigue
- **Risk**: Too many alerts could overwhelm admins
- **Mitigation**:
  - Set reasonable thresholds (CPU > 80%, not > 50%)
  - Group similar alerts (e.g., "3 bot workers down" not 3 separate alerts)
  - Allow alert dismissal (store in session)
- **Future**: Add alert configuration UI

### Trade-off 1: Real-time vs Performance
- **Trade-off**: More frequent updates = fresher data but higher server load
- **Decision**: 30-second refresh with 5-second cache is sweet spot
- **Rationale**: Monitoring doesn't require sub-second precision

### Trade-off 2: Feature Completeness vs Simplicity
- **Trade-off**: Could add custom dashboards, alert rules, historical graphs
- **Decision**: Start with MVP (single dashboard, fixed thresholds, 24-hour history)
- **Rationale**: Deliver value quickly, iterate based on feedback

## Data Model

### Metrics Structure (Cached)
```php
[
    'timestamp' => '2026-01-05T16:30:00Z',
    'system' => [
        'cpu_load_1m' => 2.5,
        'memory_usage_mb' => 512,
        'disk_usage_percent' => 45,
    ],
    'workers' => [
        'queue' => [
            'active' => 4,
            'total_jobs' => 1234,
            'failed_jobs' => 5,
        ],
        'bots' => [
            'active' => 2,
            'total_bots' => 3,
        ],
        'octane' => [
            'status' => 'running',
            'workers' => 8,
            'memory_mb' => 256,
        ],
    ],
    'database' => [
        'active_connections' => 12,
        'slow_queries' => 3,
    ],
    'cache' => [
        'hit_rate' => 85.5,
        'memory_mb' => 100,
    ],
]
```

### Alert Structure
```php
[
    [
        'type' => 'cpu',
        'severity' => 'critical', // critical|warning|info
        'message' => 'CPU load exceeds 80%',
        'value' => 85.2,
        'threshold' => 80,
        'timestamp' => '2026-01-05T16:30:00Z',
    ],
    [
        'type' => 'failed_jobs',
        'severity' => 'warning',
        'message' => 'High number of failed jobs',
        'value' => 120,
        'threshold' => 100,
        'timestamp' => '2026-01-05T16:29:00Z',
    ],
]
```

## Migration Plan

### Phase 1: Backend Foundation (Days 1-2)
1. Create `SystemMonitoringController` with basic routes
2. Implement `WorkerManager` and `AlertManager` services
3. Add Octane detection logic to `SystemMonitor`
4. Set up caching layer for metrics

### Phase 2: Frontend Implementation (Days 3-4)
1. Create main dashboard Blade view with metric cards
2. Add charts using Chart.js (reuse queue management config)
3. Implement AJAX auto-refresh
4. Add quick action buttons

### Phase 3: Integration & Testing (Days 5-6)
1. Integrate with existing services (queue, cache, bot monitoring)
2. Write unit and integration tests
3. Browser testing on different screen sizes
4. Performance testing and optimization

### Phase 4: Polish & Documentation (Day 7)
1. UI refinement and responsive design fixes
2. Add PHPDoc comments
3. Update admin documentation
4. Final validation with `openspec validate --strict`

### Rollback Plan
- Dashboard is additive (no modifications to existing code)
- If issues arise, simply remove routes from `routes/admin.php`
- No database migrations, no data loss risk

## Open Questions

1. **Alert Notification Preferences**: Should admins be able to configure which alerts they see?
   - **Decision**: Not in MVP, use fixed thresholds from config file
   
2. **Historical Data Retention**: How long should we keep metrics history?
   - **Decision**: 24 hours in-memory (cache), no database persistence in MVP
   
3. **Multi-Server Support**: Should dashboard aggregate metrics from multiple servers?
   - **Decision**: Out of scope, single-server monitoring only
   
4. **Custom Metrics**: Should users be able to add custom metrics?
   - **Decision**: Not in MVP, future enhancement

## Success Metrics

- **Performance**: Dashboard loads in < 2 seconds
- **Adoption**: 80%+ of admins use dashboard within first month
- **Reliability**: 99.9% uptime for monitoring endpoints
- **User Satisfaction**: Positive feedback from admin users
- **Operational Impact**: Reduced mean time to detection (MTTD) for incidents
