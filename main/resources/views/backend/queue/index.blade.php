@extends('backend.layout.master')

@section('title', 'Queue Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Queue Management Dashboard</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshMetrics()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="restartWorkers()">
                            <i class="fas fa-redo"></i> Restart Workers
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="clearMetrics()">
                            <i class="fas fa-trash"></i> Clear Metrics
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Queue Health Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Jobs</h6>
                                            <h3 id="total-jobs">{{ $metrics['overall']['total_jobs'] ?? 0 }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-tasks fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Active Workers</h6>
                                            <h3 id="active-workers">{{ $metrics['overall']['active_workers'] ?? 0 }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-cogs fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Average Health</h6>
                                            <h3 id="average-health">{{ round($metrics['overall']['average_health'] ?? 0, 1) }}%</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-heartbeat fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Recommended Workers</h6>
                                            <h3 id="recommended-workers">{{ $metrics['overall']['recommended_workers'] ?? 0 }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Details Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Queue Status</h5>
                            <div class="table-responsive">
                                <table class="table table-striped" id="queue-status-table">
                                    <thead>
                                        <tr>
                                            <th>Queue</th>
                                            <th>Size</th>
                                            <th>Processing Rate (jobs/min)</th>
                                            <th>Failure Rate (%)</th>
                                            <th>Health Score (%)</th>
                                            <th>ETA (min)</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="queue-status-tbody">
                                        @foreach($metrics as $queue => $data)
                                            @if($queue !== 'overall' && $queue !== 'historical' && $queue !== 'performance')
                                                <tr>
                                                    <td><span class="badge badge-secondary">{{ $queue }}</span></td>
                                                    <td>{{ $data['size'] ?? 0 }}</td>
                                                    <td>{{ round($data['processing_rate'] ?? 0, 1) }}</td>
                                                    <td>{{ round($data['failure_rate'] ?? 0, 1) }}</td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            @php
                                                                $health = $data['health_score'] ?? 0;
                                                                $color = $health >= 80 ? 'success' : ($health >= 60 ? 'warning' : 'danger');
                                                            @endphp
                                                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $health }}%">
                                                                {{ round($health, 1) }}%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $data['estimated_completion'] ? round($data['estimated_completion'], 1) : 'N/A' }}</td>
                                                    <td>
                                                        @php
                                                            $health = $data['health_score'] ?? 0;
                                                            $status = $health >= 80 ? 'Healthy' : ($health >= 60 ? 'Warning' : 'Critical');
                                                            $statusColor = $health >= 80 ? 'success' : ($health >= 60 ? 'warning' : 'danger');
                                                        @endphp
                                                        <span class="badge badge-{{ $statusColor }}">{{ $status }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Worker Scaling -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Worker Scaling</h6>
                                </div>
                                <div class="card-body">
                                    <form id="scale-workers-form">
                                        <div class="form-group">
                                            <label for="worker-count">Number of Workers</label>
                                            <input type="number" class="form-control" id="worker-count" min="1" max="20" value="{{ $metrics['overall']['active_workers'] ?? 2 }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Scale Workers</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Performance Metrics</h6>
                                </div>
                                <div class="card-body">
                                    @if(isset($metrics['performance']))
                                        <p><strong>Average Job Duration:</strong> {{ round($metrics['performance']['average_job_duration'] ?? 0, 2) }}ms</p>
                                        <p><strong>Memory Usage:</strong> {{ formatBytes($metrics['performance']['memory_usage']['current'] ?? 0) }}</p>
                                        <p><strong>Peak Memory:</strong> {{ formatBytes($metrics['performance']['memory_usage']['peak'] ?? 0) }}</p>
                                        <p><strong>CPU Usage:</strong> {{ round($metrics['performance']['cpu_usage'] ?? 0, 1) }}%</p>
                                    @else
                                        <p class="text-muted">Performance metrics not available</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Job Throughput (24h)</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="throughput-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Response Times (24h)</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="response-time-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let throughputChart, responseTimeChart;

$(document).ready(function() {
    initializeCharts();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshMetrics, 30000);
    
    // Handle worker scaling form
    $('#scale-workers-form').on('submit', function(e) {
        e.preventDefault();
        scaleWorkers();
    });
});

function initializeCharts() {
    // Initialize throughput chart
    const throughputCtx = document.getElementById('throughput-chart').getContext('2d');
    throughputChart = new Chart(throughputCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Jobs Processed',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Jobs Failed',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Initialize response time chart
    const responseTimeCtx = document.getElementById('response-time-chart').getContext('2d');
    responseTimeChart = new Chart(responseTimeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Average Response Time (ms)',
                data: [],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }, {
                label: '95th Percentile (ms)',
                data: [],
                borderColor: 'rgb(255, 206, 86)',
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Load initial chart data
    loadChartData();
}

function refreshMetrics() {
    $.get('{{ route("admin.queue.health") }}')
        .done(function(data) {
            updateOverviewCards(data);
            updateQueueTable(data);
        })
        .fail(function() {
            showNotification('error', 'Failed to refresh metrics');
        });
}

function updateOverviewCards(data) {
    $('#total-jobs').text(data.overall.total_jobs || 0);
    $('#active-workers').text(data.overall.active_workers || 0);
    $('#average-health').text(Math.round(data.overall.average_health || 0) + '%');
    $('#recommended-workers').text(data.overall.recommended_workers || 0);
}

function updateQueueTable(data) {
    const tbody = $('#queue-status-tbody');
    tbody.empty();
    
    Object.keys(data).forEach(function(queue) {
        if (queue === 'overall' || queue === 'historical' || queue === 'performance') {
            return;
        }
        
        const queueData = data[queue];
        const health = queueData.health_score || 0;
        const healthColor = health >= 80 ? 'success' : (health >= 60 ? 'warning' : 'danger');
        const status = health >= 80 ? 'Healthy' : (health >= 60 ? 'Warning' : 'Critical');
        const eta = queueData.estimated_completion ? Math.round(queueData.estimated_completion * 10) / 10 : 'N/A';
        
        const row = `
            <tr>
                <td><span class="badge badge-secondary">${queue}</span></td>
                <td>${queueData.size || 0}</td>
                <td>${Math.round((queueData.processing_rate || 0) * 10) / 10}</td>
                <td>${Math.round((queueData.failure_rate || 0) * 10) / 10}</td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-${healthColor}" style="width: ${health}%">
                            ${Math.round(health * 10) / 10}%
                        </div>
                    </div>
                </td>
                <td>${eta}</td>
                <td><span class="badge badge-${healthColor}">${status}</span></td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

function scaleWorkers() {
    const workerCount = $('#worker-count').val();
    
    $.post('{{ route("admin.queue.scale") }}', {
        workers: workerCount,
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        showNotification(response.type, response.message);
        if (response.type === 'success') {
            refreshMetrics();
        }
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        showNotification('error', response.message || 'Failed to scale workers');
    });
}

function restartWorkers() {
    if (!confirm('Are you sure you want to restart all queue workers?')) {
        return;
    }
    
    $.post('{{ route("admin.queue.restart") }}', {
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        showNotification(response.type, response.message);
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        showNotification('error', response.message || 'Failed to restart workers');
    });
}

function clearMetrics() {
    if (!confirm('Are you sure you want to clear all queue metrics?')) {
        return;
    }
    
    $.post('{{ route("admin.queue.clear-metrics") }}', {
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        showNotification(response.type, response.message);
        if (response.type === 'success') {
            refreshMetrics();
            loadChartData();
        }
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        showNotification('error', response.message || 'Failed to clear metrics');
    });
}

function loadChartData() {
    $.get('{{ route("admin.queue.statistics") }}')
        .done(function(data) {
            updateThroughputChart(data.throughput);
            updateResponseTimeChart(data.response_times);
        })
        .fail(function() {
            console.error('Failed to load chart data');
        });
}

function updateThroughputChart(data) {
    const labels = data.map(item => new Date(item.timestamp).toLocaleTimeString());
    const processed = data.map(item => item.jobs_processed);
    const failed = data.map(item => item.jobs_failed);
    
    throughputChart.data.labels = labels;
    throughputChart.data.datasets[0].data = processed;
    throughputChart.data.datasets[1].data = failed;
    throughputChart.update();
}

function updateResponseTimeChart(data) {
    const labels = data.map(item => new Date(item.timestamp).toLocaleTimeString());
    const avgTimes = data.map(item => item.avg_response_time);
    const p95Times = data.map(item => item.p95_response_time);
    
    responseTimeChart.data.labels = labels;
    responseTimeChart.data.datasets[0].data = avgTimes;
    responseTimeChart.data.datasets[1].data = p95Times;
    responseTimeChart.update();
}

function showNotification(type, message) {
    // Assuming you have a notification system in place
    // This is a placeholder implementation
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>`;
    
    $('.container-fluid').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush

@php
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
@endphp