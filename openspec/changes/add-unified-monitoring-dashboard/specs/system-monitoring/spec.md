## ADDED Requirements

### Requirement: Unified Monitoring Dashboard

The system SHALL provide a unified monitoring dashboard accessible at `/admin/monitoring` that displays real-time metrics for all system components including queue workers, trading bot workers, Laravel Octane processes, system resources, database performance, and cache statistics.

#### Scenario: Admin accesses monitoring dashboard
- **GIVEN** an authenticated admin user with `manage-system` permission
- **WHEN** the admin navigates to `/admin/monitoring`
- **THEN** the dashboard displays within 2 seconds
- **AND** shows current metrics for CPU, memory, disk usage, active workers, and database connections
- **AND** displays any active alerts with severity indicators

#### Scenario: Dashboard auto-refreshes metrics
- **GIVEN** the monitoring dashboard is open
- **WHEN** 30 seconds have elapsed since last refresh
- **THEN** the system fetches updated metrics via AJAX
- **AND** updates all metric cards and charts without page reload
- **AND** preserves user's scroll position

#### Scenario: Unauthorized user attempts access
- **GIVEN** a user without `manage-system` permission
- **WHEN** the user attempts to access `/admin/monitoring`
- **THEN** the system returns 403 Forbidden error
- **AND** redirects to admin dashboard with error message

### Requirement: Multi-Worker Status Monitoring

The system SHALL display the status of all worker types (queue workers, trading bot workers, and Laravel Octane workers) in a unified table showing worker type, count, status, CPU usage, memory usage, and uptime.

#### Scenario: Display queue worker status
- **GIVEN** Laravel queue workers are running
- **WHEN** the dashboard loads
- **THEN** the workers table shows queue worker count
- **AND** displays total jobs processed, pending jobs, and failed jobs
- **AND** shows worker health status (healthy/warning/critical)

#### Scenario: Display bot worker status
- **GIVEN** trading bot addon is active with running bots
- **WHEN** the dashboard loads
- **THEN** the workers table shows bot worker count
- **AND** displays number of active bots and total bots
- **AND** shows bot worker status for each running bot

#### Scenario: Display Octane worker status
- **GIVEN** Laravel Octane is installed and running
- **WHEN** the dashboard loads
- **THEN** the workers table shows Octane worker count
- **AND** displays Octane memory usage and uptime
- **AND** shows Octane status as "running"

#### Scenario: Graceful handling when Octane not installed
- **GIVEN** Laravel Octane is not installed
- **WHEN** the dashboard loads
- **THEN** the Octane row shows status as "Not Installed"
- **AND** displays informational message about Octane
- **AND** does not show error or break dashboard

#### Scenario: Graceful handling when Octane not running
- **GIVEN** Laravel Octane is installed but not running
- **WHEN** the dashboard loads
- **THEN** the Octane row shows status as "Not Running"
- **AND** displays "Start Octane" quick action button
- **AND** does not show error or break dashboard

### Requirement: System Health Metrics

The system SHALL display real-time system health metrics including CPU load (1m, 5m, 15m averages), memory usage (current and peak), disk usage percentage, active database connections, slow query count, cache hit rate, and cache memory usage.

#### Scenario: Display CPU metrics
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** system CPU load is retrieved
- **THEN** dashboard displays 1-minute, 5-minute, and 15-minute load averages
- **AND** shows visual indicator (green/yellow/red) based on threshold
- **AND** triggers critical alert if 1-minute load exceeds configured threshold

#### Scenario: Display memory metrics
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** system memory usage is retrieved
- **THEN** dashboard displays current memory usage in MB
- **AND** displays peak memory usage in MB
- **AND** shows memory usage percentage with progress bar
- **AND** triggers warning alert if memory exceeds 85% of available

#### Scenario: Display database metrics
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** database performance metrics are retrieved
- **THEN** dashboard displays active database connections count
- **AND** displays slow query count (queries > 100ms)
- **AND** triggers warning alert if slow queries exceed threshold

#### Scenario: Display cache metrics
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** cache performance metrics are retrieved
- **THEN** dashboard displays cache hit rate percentage
- **AND** displays cache memory usage in MB
- **AND** shows cache effectiveness indicator (good/poor)

### Requirement: Alert System

The system SHALL monitor metrics against configured thresholds and display active alerts with severity levels (critical, warning, info), alert message, current value, threshold value, and timestamp.

#### Scenario: Generate critical CPU alert
- **GIVEN** CPU 1-minute load average exceeds critical threshold (default 4.0)
- **WHEN** alert system checks thresholds
- **THEN** system generates critical alert for CPU
- **AND** displays red alert banner on dashboard
- **AND** includes current CPU load and threshold in alert message

#### Scenario: Generate warning for failed jobs
- **GIVEN** failed job count exceeds warning threshold (default 100)
- **WHEN** alert system checks thresholds
- **THEN** system generates warning alert for failed jobs
- **AND** displays yellow alert banner on dashboard
- **AND** includes failed job count and threshold in alert message

#### Scenario: Generate warning for low cache hit rate
- **GIVEN** cache hit rate falls below 60%
- **WHEN** alert system checks thresholds
- **THEN** system generates warning alert for cache performance
- **AND** displays yellow alert banner on dashboard
- **AND** suggests running cache warm command

#### Scenario: Clear resolved alerts
- **GIVEN** an active alert exists for high CPU
- **WHEN** CPU load drops below threshold
- **AND** alert system checks thresholds again
- **THEN** system removes the CPU alert from active alerts
- **AND** dashboard no longer displays the alert

#### Scenario: Alert threshold configuration
- **GIVEN** admin wants to customize alert thresholds
- **WHEN** admin updates `config/monitoring.php` thresholds
- **THEN** alert system uses new thresholds for future checks
- **AND** existing alerts are re-evaluated against new thresholds

### Requirement: Performance Charts

The system SHALL display historical performance charts for the last 24 hours showing system health trends (CPU, memory), worker activity (job throughput, active workers), and database performance (connections, slow queries).

#### Scenario: Display system health chart
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** chart data is fetched for last 24 hours
- **THEN** dashboard displays line chart with CPU and memory trends
- **AND** chart shows hourly data points
- **AND** chart is responsive and updates on window resize

#### Scenario: Display worker activity chart
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** worker activity data is fetched
- **THEN** dashboard displays chart showing jobs processed per hour
- **AND** chart shows active worker count over time
- **AND** chart distinguishes between queue and bot workers

#### Scenario: Display database performance chart
- **GIVEN** the monitoring dashboard is loaded
- **WHEN** database metrics are fetched
- **THEN** dashboard displays chart showing connection count over time
- **AND** chart shows slow query count trend
- **AND** chart highlights periods with high slow query counts

### Requirement: Quick Actions

The system SHALL provide quick action buttons to restart queue workers, restart bot workers, restart Octane, and clear all cache, with confirmation modals and success/error feedback.

#### Scenario: Restart queue workers
- **GIVEN** admin clicks "Restart Queue Workers" button
- **WHEN** admin confirms the action in modal
- **THEN** system executes `php artisan queue:restart` command
- **AND** displays success notification
- **AND** refreshes worker metrics to show updated status

#### Scenario: Restart bot workers
- **GIVEN** trading bot addon is active
- **AND** admin clicks "Restart Bot Workers" button
- **WHEN** admin confirms the action in modal
- **THEN** system restarts all active bot workers
- **AND** displays success notification with count of restarted bots
- **AND** refreshes bot worker metrics

#### Scenario: Clear all cache
- **GIVEN** admin clicks "Clear All Cache" button
- **WHEN** admin confirms the action in modal
- **THEN** system executes cache clear commands
- **AND** displays success notification
- **AND** refreshes cache metrics showing cleared state

#### Scenario: Restart Octane
- **GIVEN** Laravel Octane is running
- **AND** admin clicks "Restart Octane" button
- **WHEN** admin confirms the action in modal
- **THEN** system executes `php artisan octane:reload` command
- **AND** displays success notification
- **AND** refreshes Octane metrics

#### Scenario: Quick action requires confirmation
- **GIVEN** admin clicks any quick action button
- **WHEN** the button is clicked
- **THEN** system displays confirmation modal
- **AND** modal explains the action's impact
- **AND** provides "Confirm" and "Cancel" buttons
- **AND** action only executes after "Confirm" is clicked

#### Scenario: Quick action error handling
- **GIVEN** admin attempts to restart workers
- **WHEN** the command fails (e.g., insufficient permissions)
- **THEN** system displays error notification with failure reason
- **AND** does not refresh metrics
- **AND** logs error for admin review

### Requirement: Metrics Caching

The system SHALL cache collected metrics for 5 seconds to reduce database load and improve dashboard performance, while ensuring metrics remain reasonably fresh.

#### Scenario: Cache metrics on first request
- **GIVEN** no cached metrics exist
- **WHEN** dashboard requests health data
- **THEN** system collects metrics from all sources
- **AND** stores metrics in cache with 5-second TTL
- **AND** returns metrics to dashboard

#### Scenario: Serve cached metrics
- **GIVEN** cached metrics exist and are less than 5 seconds old
- **WHEN** dashboard requests health data
- **THEN** system returns cached metrics immediately
- **AND** does not query database or system resources
- **AND** response time is under 50ms

#### Scenario: Refresh expired cache
- **GIVEN** cached metrics are older than 5 seconds
- **WHEN** dashboard requests health data
- **THEN** system collects fresh metrics
- **AND** updates cache with new 5-second TTL
- **AND** returns fresh metrics to dashboard

#### Scenario: Manual refresh bypasses cache
- **GIVEN** admin clicks manual refresh button
- **WHEN** refresh request is sent
- **THEN** system clears cached metrics
- **AND** collects fresh metrics immediately
- **AND** updates cache with new data

### Requirement: Responsive Design

The system SHALL ensure the monitoring dashboard is responsive and usable on tablet devices (768px and above) with stacked metric cards, responsive charts, and touch-friendly controls.

#### Scenario: Dashboard on desktop (1920px)
- **GIVEN** dashboard is viewed on desktop screen
- **WHEN** page loads
- **THEN** metric cards display in 4-column grid
- **AND** charts display side-by-side
- **AND** workers table shows all columns

#### Scenario: Dashboard on tablet (768px)
- **GIVEN** dashboard is viewed on tablet screen
- **WHEN** page loads
- **THEN** metric cards display in 2-column grid
- **AND** charts stack vertically
- **AND** workers table remains scrollable horizontally

#### Scenario: Charts resize on window change
- **GIVEN** dashboard is open with charts displayed
- **WHEN** browser window is resized
- **THEN** charts automatically adjust to new width
- **AND** maintain aspect ratio
- **AND** remain readable at all sizes

### Requirement: Permission-Based Access

The system SHALL restrict access to the monitoring dashboard to users with the `manage-system` permission, enforced at both route and controller levels.

#### Scenario: Admin with permission accesses dashboard
- **GIVEN** authenticated admin has `manage-system` permission
- **WHEN** admin navigates to `/admin/monitoring`
- **THEN** dashboard loads successfully
- **AND** all metrics and actions are available

#### Scenario: Admin without permission denied access
- **GIVEN** authenticated admin lacks `manage-system` permission
- **WHEN** admin attempts to access `/admin/monitoring`
- **THEN** system returns 403 Forbidden response
- **AND** redirects to admin dashboard
- **AND** displays "Insufficient permissions" error message

#### Scenario: Unauthenticated user redirected
- **GIVEN** unauthenticated user
- **WHEN** user attempts to access `/admin/monitoring`
- **THEN** system redirects to admin login page
- **AND** preserves intended URL for post-login redirect

#### Scenario: API endpoints require authentication
- **GIVEN** unauthenticated request to `/admin/monitoring/health`
- **WHEN** request is made
- **THEN** system returns 401 Unauthorized
- **AND** does not expose any metrics data
