@extends('backend.layout.master')

@section('title', 'System Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">System Monitoring Dashboard</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshMetrics(true)">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="clearCache()">
                            <i class="fas fa-broom"></i> Clear Cache
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Alerts Panel -->
                    <div id="alerts-container" class="mb-4">
                        @include('backend.monitoring.partials.alerts')
                    </div>

                    <!-- System Health Metrics -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">CPU Load (1m)</h6>
                                            <h3 id="cpu-load-1m">-</h3>
                                            <small>5m: <span id="cpu-load-5m">-</span> | 15m: <span id="cpu-load-15m">-</span></small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-microchip fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Memory Usage</h6>
                                            <h3 id="memory-usage">-</h3>
                                            <small><span id="memory-percent">-</span>% used</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-memory fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Disk Usage</h6>
                                            <h3 id="disk-usage">-</h3>
                                            <small><span id="disk-percent">-</span>% used</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-hdd fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Database Connections</h6>
                                            <h3 id="db-connections">-</h3>
                                            <small>Slow queries: <span id="slow-queries">-</span></small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-database fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Cache Hit Rate</h6>
                                    <h3 id="cache-hit-rate">-</h3>
                                    <small>Hits: <span id="cache-hits">-</span> | Misses: <span id="cache-misses">-</span></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Cache Memory</h6>
                                    <h3 id="cache-memory">-</h3>
                                    <small>MB</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workers Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Worker Status</h5>
                            <div id="workers-container">
                                @include('backend.monitoring.partials.workers')
                            </div>
                        </div>
                    </div>

                    <!-- Performance Charts -->
                    <div class="row">
                        <div class="col-12">
                            <h5>Performance Trends (24 Hours)</h5>
                            <div id="charts-container">
                                @include('backend.monitoring.partials.charts')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restart Worker Confirmation Modal -->
<div class="modal fade" id="restartWorkerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Worker Restart</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restart <strong id="restart-worker-type"></strong> workers?</p>
                <p class="text-muted"><small>This action will interrupt current operations.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-restart">Restart</button>
            </div>
        </div>
    </div>
</div>

<!-- Clear Cache Confirmation Modal -->
<div class="modal fade" id="clearCacheModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Cache Clear</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear all cache?</p>
                <p class="text-muted"><small>This action will clear all cached data and may temporarily slow down the application.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-clear-cache">Clear Cache</button>
            </div>
        </div>
    </div>
</div>

@push('style')
<link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
<style>
    .alert-banner {
        border-left: 4px solid;
        padding: 12px;
        margin-bottom: 10px;
    }
    .alert-critical {
        background-color: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }
    .alert-info {
        background-color: #d1ecf1;
        border-color: #17a2b8;
        color: #0c5460;
    }
    .worker-status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .status-active { background-color: #28a745; color: white; }
    .status-inactive { background-color: #6c757d; color: white; }
    .status-error { background-color: #dc3545; color: white; }
    .status-not-installed { background-color: #ffc107; color: #000; }
    .status-not-running { background-color: #ffc107; color: #000; }
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let refreshInterval;
let charts = {};
let lastHistoryFetch = null;

$(document).ready(function() {
    // Initial load
    refreshMetrics(true);
    
    // Auto-refresh every 30 seconds
    const refreshIntervalMs = {{ config('monitoring.refresh_interval', 30000) }};
    refreshInterval = setInterval(function () {
        refreshMetrics(false);
    }, refreshIntervalMs);
    
    // Setup modals
    setupModals();

    // Load history initially
    loadHistory();
});

function refreshMetrics(forceHistory = false) {
    $.ajax({
        url: '{{ route("admin.monitoring.health") }}',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            updateDashboard(data);
            if (forceHistory || shouldFetchHistory()) {
                loadHistory();
            }
        },
        error: function(xhr) {
            console.error('Failed to refresh metrics:', xhr);
            showNotification('error', 'Failed to refresh metrics');
        }
    });
}

function shouldFetchHistory() {
    if (!lastHistoryFetch) {
        return true;
    }
    const diff = Date.now() - lastHistoryFetch;
    return diff > 60000; // once per minute
}

function loadHistory() {
    $.ajax({
        url: '{{ route("admin.monitoring.history") }}',
        method: 'GET',
        success: function(history) {
            lastHistoryFetch = Date.now();
            renderCharts(history);
        },
        error: function(xhr) {
            console.error('Failed to load history', xhr);
        }
    });
}

function updateDashboard(data) {
    // Update system metrics
    if (data.system) {
        $('#cpu-load-1m').text(data.system.cpu_load_1m?.toFixed(2) || '-');
        $('#cpu-load-5m').text(data.system.cpu_load_5m?.toFixed(2) || '-');
        $('#cpu-load-15m').text(data.system.cpu_load_15m?.toFixed(2) || '-');
        $('#memory-usage').text(data.system.memory_usage_mb?.toFixed(0) + ' MB' || '-');
        $('#memory-percent').text(data.system.memory_usage_percent?.toFixed(1) || '-');
        $('#disk-usage').text(data.system.disk_usage_percent?.toFixed(1) + '%' || '-');
        $('#disk-percent').text(data.system.disk_usage_percent?.toFixed(1) || '-');
    }
    
    // Update database metrics
    if (data.system?.database) {
        $('#db-connections').text(data.system.database.active_connections || '-');
        $('#slow-queries').text(data.system.database.slow_queries || '-');
    }
    
    // Update cache metrics
    if (data.system?.cache) {
        $('#cache-hit-rate').text(data.system.cache.hit_rate?.toFixed(1) + '%' || '-');
        $('#cache-hits').text(data.system.cache.hits || '-');
        $('#cache-misses').text(data.system.cache.misses || '-');
        $('#cache-memory').text(data.system.cache.memory_mb?.toFixed(0) || '-');
    }
    
    // Update alerts
    if (data.alerts) {
        updateAlerts(data.alerts);
    }
    
    // Update workers
    if (data.workers) {
        updateWorkers(data.workers);
    }
}

function updateAlerts(alerts) {
    const container = $('#alerts-container');
    
    if (alerts.length === 0) {
        container.html('<div class="alert alert-info">No active alerts</div>');
        return;
    }
    
    let html = '';
    alerts.forEach(function(alert) {
        const severityClass = 'alert-' + alert.severity;
        html += `
            <div class="alert-banner ${severityClass}">
                <strong>${alert.severity.toUpperCase()}:</strong> ${alert.message}
                <br><small>Current: ${alert.value} | Threshold: ${alert.threshold}</small>
            </div>
        `;
    });
    
    container.html(html);
}

function updateWorkers(workers) {
    const container = $('#workers-container');
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Worker Type</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Queue workers
    if (workers.queue) {
        const q = workers.queue;
        html += `
            <tr>
                <td><strong>Queue Workers</strong></td>
                <td><span class="worker-status-badge status-${q.status}">${q.status}</span></td>
                <td>${q.active || 0}</td>
                <td>Jobs: ${q.total_jobs || 0} | Failed: ${q.failed_jobs || 0}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="restartWorker('queue')">
                        <i class="fas fa-redo"></i> Restart
                    </button>
                </td>
            </tr>
        `;
    }
    
    // Bot workers
    if (workers.bots) {
        const b = workers.bots;
        const statusClass = b.status === 'active' ? 'status-active' : 'status-inactive';
        html += `
            <tr>
                <td><strong>Bot Workers</strong></td>
                <td><span class="worker-status-badge ${statusClass}">${b.status}</span></td>
                <td>${b.active || 0}</td>
                <td>Total: ${b.total || 0}</td>
                <td>
                    ${b.status === 'active' ? `
                        <button class="btn btn-sm btn-warning" onclick="restartWorker('bots')">
                            <i class="fas fa-redo"></i> Restart
                        </button>
                    ` : '<span class="text-muted">N/A</span>'}
                </td>
            </tr>
        `;
    }
    
    // Octane workers
    if (workers.octane) {
        const o = workers.octane;
        let statusClass = 'status-inactive';
        if (o.status === 'running') statusClass = 'status-active';
        else if (o.status === 'not_installed' || o.status === 'not_running') statusClass = 'status-not-running';
        
        html += `
            <tr>
                <td><strong>Octane Workers</strong></td>
                <td><span class="worker-status-badge ${statusClass}">${o.status}</span></td>
                <td>${o.workers || 0}</td>
                <td>${o.memory_mb ? o.memory_mb + ' MB' : o.message || '-'}</td>
                <td>
                    ${o.status === 'running' ? `
                        <button class="btn btn-sm btn-warning" onclick="restartWorker('octane')">
                            <i class="fas fa-redo"></i> Restart
                        </button>
                    ` : '<span class="text-muted">N/A</span>'}
                </td>
            </tr>
        `;
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.html(html);
}

function restartWorker(type) {
    $('#restart-worker-type').text(type);
    $('#restartWorkerModal').modal('show');
    
    $('#confirm-restart').off('click').on('click', function() {
        $.ajax({
            url: `/admin/monitoring/workers/${type}/restart`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#restartWorkerModal').modal('hide');
                showNotification(response.type, response.message);
                setTimeout(refreshMetrics, 2000);
            },
            error: function(xhr) {
                $('#restartWorkerModal').modal('hide');
                const error = xhr.responseJSON || { message: 'Failed to restart workers' };
                showNotification('error', error.message);
            }
        });
    });
}

function clearCache() {
    $('#clearCacheModal').modal('show');
    
    $('#confirm-clear-cache').off('click').on('click', function() {
        $.ajax({
            url: '{{ route("admin.monitoring.cache.clear") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#clearCacheModal').modal('hide');
                showNotification(response.type, response.message);
                setTimeout(function () {
                    refreshMetrics(true);
                }, 2000);
            },
            error: function(xhr) {
                $('#clearCacheModal').modal('hide');
                const error = xhr.responseJSON || { message: 'Failed to clear cache' };
                showNotification('error', error.message);
            }
        });
    });
}

function setupModals() {
    // Reset modals on close
    $('#restartWorkerModal').on('hidden.bs.modal', function() {
        $('#confirm-restart').off('click');
    });
    
    $('#clearCacheModal').on('hidden.bs.modal', function() {
        $('#confirm-clear-cache').off('click');
    });
}

function renderCharts(history) {
    if (!history || !history.labels) {
        return;
    }

    renderChart('systemHealthChart', history.labels, [
        {
            label: 'CPU Load',
            borderColor: '#4e73df',
            fill: false,
            data: history.system_health.cpu || []
        },
        {
            label: 'Memory %',
            borderColor: '#1cc88a',
            fill: false,
            data: history.system_health.memory || []
        },
        {
            label: 'Disk Usage %',
            borderColor: '#f6c23e',
            fill: false,
            data: history.system_health.disk || []
        }
    ]);

    renderChart('workerActivityChart', history.labels, [
        {
            label: 'Queue Workers',
            borderColor: '#36b9cc',
            fill: false,
            data: history.worker_activity.queue_active || []
        },
        {
            label: 'Pending Jobs',
            borderColor: '#858796',
            fill: false,
            data: history.worker_activity.queue_jobs || []
        },
        {
            label: 'Bot Workers',
            borderColor: '#e74a3b',
            fill: false,
            data: history.worker_activity.bot_active || []
        },
        {
            label: 'Octane Workers',
            borderColor: '#20c997',
            fill: false,
            data: history.worker_activity.octane_workers || []
        }
    ]);

    renderChart('databaseChart', history.labels, [
        {
            label: 'Connections',
            borderColor: '#6f42c1',
            fill: false,
            data: history.database.connections || []
        },
        {
            label: 'Slow Queries',
            borderColor: '#fd7e14',
            fill: false,
            data: history.database.slow_queries || []
        }
    ]);
}

function renderChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId).getContext('2d');

    if (charts[canvasId]) {
        charts[canvasId].data.labels = labels;
        charts[canvasId].data.datasets.forEach((dataset, index) => {
            dataset.data = datasets[index].data;
        });
        charts[canvasId].update();
        return;
    }

    charts[canvasId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                }
            }
        }
    });
}

function showNotification(type, message) {
    // Use Laravel Notify if available, otherwise alert
    if (typeof notify !== 'undefined') {
        notify(type, message);
    } else {
        alert(message);
    }
}
</script>
@endpush
@endsection

