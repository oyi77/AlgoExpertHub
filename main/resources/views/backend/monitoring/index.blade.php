@extends('backend.layout.master')

@section('title', 'System Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Unified System Monitoring Dashboard</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshMetrics()" id="refresh-btn">
                            <i class="fas fa-sync-alt"></i> <span id="refresh-text">Refresh</span>
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="showQuickActions()">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Alerts Panel -->
                    <div id="alerts-container" class="mb-4"></div>

                    <!-- System Health Metrics -->
                    @include('backend.monitoring.partials.metric-cards')

                    <!-- Workers Table -->
                    @include('backend.monitoring.partials.workers-table')

                    <!-- Performance Charts -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">System Health (24h)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="system-health-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Worker Activity (24h)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="worker-activity-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Database Performance (24h)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="database-chart" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Actions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action" onclick="restartQueueWorkers()">
                        <i class="fas fa-redo"></i> Restart Queue Workers
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" id="restart-bot-btn" onclick="restartBotWorkers()" style="display: none;">
                        <i class="fas fa-robot"></i> Restart Bot Workers
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" id="restart-octane-btn" onclick="restartOctane()" style="display: none;">
                        <i class="fas fa-server"></i> Restart Octane
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="clearAllCache()">
                        <i class="fas fa-broom"></i> Clear All Cache
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirm-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-action-btn">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let systemHealthChart, workerActivityChart, databaseChart;
let refreshInterval;

// Initialize dashboard
$(document).ready(function() {
    loadDashboard();
    startAutoRefresh();
    loadChartData();
});

// Load dashboard data
function loadDashboard() {
    $.ajax({
        url: '{{ route("admin.monitoring.health") }}',
        method: 'GET',
        success: function(data) {
            updateMetrics(data);
            updateAlerts(data.alerts || []);
            updateWorkers(data.workers || {});
            checkQuickActionButtons(data.workers || {});
        },
        error: function(xhr) {
            console.error('Failed to load dashboard:', xhr);
            showAlert('error', 'Failed to load monitoring data');
        }
    });
}

// Update metric cards
function updateMetrics(data) {
    const system = data.system || {};
    const database = data.database || {};
    const cache = data.cache || {};

    // CPU
    $('#cpu-load-1m-main').text(system.cpu_load_1m?.toFixed(2) || '0.00');
    $('#cpu-load-1m').text(system.cpu_load_1m?.toFixed(2) || '0.00');
    $('#cpu-load-5m').text(system.cpu_load_5m?.toFixed(2) || '0.00');
    $('#cpu-load-15m').text(system.cpu_load_15m?.toFixed(2) || '0.00');
    updateMetricStatus('cpu', system.cpu_load_1m, 4.0);

    // Memory
    $('#memory-usage').text(Math.round(system.memory_usage_mb || 0));
    $('#memory-peak').text(Math.round(system.memory_peak_mb || 0));
    const memoryPercent = ((system.memory_usage_mb || 0) / 512) * 100;
    $('#memory-progress').css('width', Math.min(memoryPercent, 100) + '%');
    updateMetricStatus('memory', memoryPercent, 85);

    // Disk
    $('#disk-usage').text(system.disk_usage_percent?.toFixed(1) || '0.0');
    updateMetricStatus('disk', system.disk_usage_percent, 80);

    // Database
    $('#db-connections').text(database.active_connections || 0);
    $('#db-slow-queries').text(database.slow_queries || 0);
    updateMetricStatus('database', database.slow_queries, 10);

    // Cache
    $('#cache-hit-rate').text(cache.hit_rate?.toFixed(1) || '0.0');
    updateMetricStatus('cache', cache.hit_rate, 60, true); // true = lower is worse
}

// Update metric status indicator
function updateMetricStatus(type, value, threshold, lowerIsWorse = false) {
    const card = $(`#${type}-metric-card`);
    card.removeClass('border-success border-warning border-danger');
    
    let status = 'success';
    if (lowerIsWorse) {
        if (value < threshold) status = value < (threshold * 0.67) ? 'danger' : 'warning';
    } else {
        if (value > threshold) status = value > (threshold * 1.5) ? 'danger' : 'warning';
    }
    
    card.addClass(`border-${status}`);
}

// Update alerts panel
function updateAlerts(alerts) {
    const container = $('#alerts-container');
    container.empty();

    if (alerts.length === 0) {
        container.html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> All systems operational</div>');
        return;
    }

    alerts.forEach(function(alert) {
        const severityClass = {
            'critical': 'danger',
            'warning': 'warning',
            'info': 'info'
        }[alert.severity] || 'info';

        const alertHtml = `
            <div class="alert alert-${severityClass} alert-dismissible fade show" role="alert">
                <strong>${alert.severity.toUpperCase()}:</strong> ${alert.message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        container.append(alertHtml);
    });
}

// Update workers table
function updateWorkers(workers) {
    const queue = workers.queue || {};
    const bots = workers.bots || {};
    const octane = workers.octane || {};

    // Queue workers
    $('#queue-workers-count').text(queue.active || 0);
    $('#queue-jobs-total').text(queue.total_jobs || 0);
    $('#queue-jobs-pending').text(queue.pending_jobs || 0);
    $('#queue-jobs-failed').text(queue.failed_jobs || 0);
    updateWorkerStatus('queue', queue.status);

    // Bot workers
    if (bots.status === 'not_installed') {
        $('#bot-workers-row').html('<td colspan="5" class="text-muted">Trading Bot addon not installed</td>');
    } else {
        $('#bot-workers-count').text(bots.active || 0);
        $('#bot-total').text(bots.total_bots || 0);
        updateWorkerStatus('bot', bots.status);
    }

    // Octane workers
    if (octane.status === 'not_installed') {
        $('#octane-workers-row').html('<td colspan="5" class="text-muted">Laravel Octane not installed</td>');
    } else if (octane.status === 'not_running') {
        $('#octane-workers-row').html('<td colspan="5" class="text-warning">Octane is not running</td>');
    } else {
        $('#octane-workers-count').text(octane.workers || 0);
        $('#octane-memory').text(Math.round(octane.memory_mb || 0));
        updateWorkerStatus('octane', octane.status);
    }
}

// Update worker status badge
function updateWorkerStatus(type, status) {
    const badge = $(`#${type}-status`);
    badge.removeClass('badge-success badge-warning badge-danger badge-secondary');
    
    const statusClass = {
        'healthy': 'success',
        'running': 'success',
        'warning': 'warning',
        'critical': 'danger',
        'stopped': 'secondary',
        'error': 'danger'
    }[status] || 'secondary';

    badge.addClass(`badge-${statusClass}`).text(status);
}

// Check and show/hide quick action buttons
function checkQuickActionButtons(workers) {
    if (workers.bots && workers.bots.status !== 'not_installed') {
        $('#restart-bot-btn').show();
    }
    if (workers.octane && workers.octane.status === 'running') {
        $('#restart-octane-btn').show();
    }
}

// Load chart data
function loadChartData() {
    ['system', 'workers', 'database'].forEach(function(type) {
        $.ajax({
            url: '{{ route("admin.monitoring.chart-data") }}',
            method: 'GET',
            data: { type: type },
            success: function(data) {
                updateChart(type, data);
            }
        });
    });
}

// Update charts
function updateChart(type, data) {
    const labels = data.map(d => new Date(d.timestamp).toLocaleTimeString());
    
    switch(type) {
        case 'system':
            if (!systemHealthChart) {
                const ctx = document.getElementById('system-health-chart').getContext('2d');
                systemHealthChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'CPU Load (1m)',
                            data: data.map(d => d.cpu_load_1m),
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1
                        }, {
                            label: 'Memory (MB)',
                            data: data.map(d => d.memory_usage_mb),
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } else {
                systemHealthChart.data.labels = labels;
                systemHealthChart.data.datasets[0].data = data.map(d => d.cpu_load_1m);
                systemHealthChart.data.datasets[1].data = data.map(d => d.memory_usage_mb);
                systemHealthChart.update();
            }
            break;

        case 'workers':
            if (!workerActivityChart) {
                const ctx = document.getElementById('worker-activity-chart').getContext('2d');
                workerActivityChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Queue Workers',
                            data: data.map(d => d.queue_workers),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }, {
                            label: 'Bot Workers',
                            data: data.map(d => d.bot_workers),
                            borderColor: 'rgb(255, 159, 64)',
                            tension: 0.1
                        }, {
                            label: 'Jobs Processed',
                            data: data.map(d => d.jobs_processed),
                            borderColor: 'rgb(153, 102, 255)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } else {
                workerActivityChart.data.labels = labels;
                workerActivityChart.data.datasets[0].data = data.map(d => d.queue_workers);
                workerActivityChart.data.datasets[1].data = data.map(d => d.bot_workers);
                workerActivityChart.data.datasets[2].data = data.map(d => d.jobs_processed);
                workerActivityChart.update();
            }
            break;

        case 'database':
            if (!databaseChart) {
                const ctx = document.getElementById('database-chart').getContext('2d');
                databaseChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Active Connections',
                            data: data.map(d => d.active_connections),
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1
                        }, {
                            label: 'Slow Queries',
                            data: data.map(d => d.slow_queries),
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } else {
                databaseChart.data.labels = labels;
                databaseChart.data.datasets[0].data = data.map(d => d.active_connections);
                databaseChart.data.datasets[1].data = data.map(d => d.slow_queries);
                databaseChart.update();
            }
            break;
    }
}

// Auto-refresh every 30 seconds
function startAutoRefresh() {
    refreshInterval = setInterval(function() {
        loadDashboard();
    }, 30000);
}

// Manual refresh
function refreshMetrics() {
    $('#refresh-btn').prop('disabled', true);
    $('#refresh-text').html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
    
    // Clear cache and reload
    $.ajax({
        url: '{{ route("admin.monitoring.health") }}',
        method: 'GET',
        headers: {
            'X-Cache-Bypass': 'true'
        },
        success: function(data) {
            updateMetrics(data);
            updateAlerts(data.alerts || []);
            updateWorkers(data.workers || {});
            $('#refresh-btn').prop('disabled', false);
            $('#refresh-text').text('Refresh');
        },
        error: function() {
            $('#refresh-btn').prop('disabled', false);
            $('#refresh-text').text('Refresh');
            showAlert('error', 'Failed to refresh metrics');
        }
    });
}

// Quick actions
function showQuickActions() {
    $('#quickActionsModal').modal('show');
}

function restartQueueWorkers() {
    showConfirm('Are you sure you want to restart all queue workers? This will interrupt currently processing jobs.', function() {
        performAction('{{ route("admin.monitoring.workers.queue.restart") }}', 'POST', 'Queue workers restarted successfully');
    });
}

function restartBotWorkers() {
    showConfirm('Are you sure you want to restart all bot workers?', function() {
        performAction('{{ route("admin.monitoring.workers.bot.restart") }}', 'POST', 'Bot workers restarted successfully');
    });
}

function restartOctane() {
    showConfirm('Are you sure you want to reload Octane? This will restart all Octane workers.', function() {
        performAction('{{ route("admin.monitoring.workers.octane.restart") }}', 'POST', 'Octane reloaded successfully');
    });
}

function clearAllCache() {
    showConfirm('Are you sure you want to clear all cache? This may temporarily slow down the application.', function() {
        performAction('{{ route("admin.monitoring.cache.clear") }}', 'POST', 'All cache cleared successfully');
    });
}

// Perform action with confirmation
function performAction(url, method, successMessage) {
    $.ajax({
        url: url,
        method: method,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showAlert('success', successMessage);
            $('#quickActionsModal').modal('hide');
            setTimeout(loadDashboard, 1000);
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Action failed';
            showAlert('error', message);
        }
    });
}

// Show confirmation modal
function showConfirm(message, callback) {
    $('#confirm-message').text(message);
    $('#confirm-action-btn').off('click').on('click', function() {
        $('#confirmModal').modal('hide');
        callback();
    });
    $('#confirmModal').modal('show');
}

// Show alert
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush

@endsection

