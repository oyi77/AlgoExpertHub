@extends('backend.layout.master')

@section('element')
<div class="row">
    <!-- Page Header -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold">
                        <i class="fas fa-heartbeat text-primary"></i> {{ $title }}
                    </h5>
                    <button class="btn btn-sm btn-primary" onclick="location.reload()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Bot Workers Stats -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-robot text-primary"></i> Trading Bot Workers</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Bots</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $workerStats['total'] }}</div>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-robot fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-success shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Running</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $workerStats['running'] }}</div>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-play-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-danger shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Dead</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $workerStats['dead'] }}</div>
                                    </div>
                                    <div class="text-danger">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-left-secondary shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Stopped/Paused</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $workerStats['stopped'] + $workerStats['paused'] }}</div>
                                    </div>
                                    <div class="text-secondary">
                                        <i class="fas fa-pause-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Workers & Exchange Connections -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-tasks text-primary"></i> Queue Workers</h6>
            </div>
            <div class="card-body">
                <div class="card border mb-3">
                    <div class="card-body">
                        <h6 class="font-weight-bold">Laravel Queue Workers</h6>
                        <p class="mb-2">
                            Status: 
                            <span class="badge badge-{{ $queueWorkersRunning ? 'success' : 'danger' }}">
                                {{ $queueWorkersRunning ? 'Running' : 'Stopped' }}
                            </span>
                        </p>
                        <p class="mb-0">Process Count: <strong>{{ $queueWorkersCount }}</strong></p>
                        <small class="text-muted">Check: <code>ps aux | grep queue:work</code></small>
                    </div>
                </div>
                <div class="card border">
                    <div class="card-body">
                        <h6 class="font-weight-bold">MetaAPI Stream Workers</h6>
                        <p class="mb-0">Process Count: <strong>{{ $metaapiWorkersCount }}</strong></p>
                        <small class="text-muted">Check: <code>ps aux | grep metaapi:stream-worker</code></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-plug text-primary"></i> Exchange Connections</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="card border-left-primary shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total</h6>
                                <h4 class="font-weight-bold">{{ $connectionStats['total'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card border-left-success shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Active</h6>
                                <h4 class="font-weight-bold text-success">{{ $connectionStats['active'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card border-left-danger shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Error</h6>
                                <h4 class="font-weight-bold text-danger">{{ $connectionStats['error'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card border-left-warning shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Testing/Inactive</h6>
                                <h4 class="font-weight-bold text-warning">{{ $connectionStats['testing'] + $connectionStats['inactive'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Metrics -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-chart-bar text-primary"></i> System Metrics</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-primary shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Pending Jobs</h6>
                                <h3 id="system-pending-jobs" class="font-weight-bold">{{ $systemMetrics['queue_size'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-warning shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Processing Jobs</h6>
                                <h3 id="system-processing-jobs" class="font-weight-bold text-warning">{{ $systemMetrics['processing_jobs'] }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-danger shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Failed Jobs</h6>
                                <h3 id="system-failed-jobs" class="font-weight-bold text-danger">{{ $systemMetrics['failed_jobs'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Jobs -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-clock text-primary"></i> Scheduled Jobs</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Scheduler Status: 
                    <span class="badge badge-{{ $schedulerRunning ? 'success' : 'danger' }}">
                        {{ $schedulerRunning ? 'Running' : 'Not Running' }}
                    </span>
                </p>
                <small class="text-muted">
                    Verify: <code>php artisan schedule:list</code> or check cron: <code>crontab -l</code>
                </small>
            </div>
        </div>
    </div>

    <!-- Recent Bot Activity -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-history text-primary"></i> Recent Bot Activity</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Bot Name</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Worker PID</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $bot)
                                <tr>
                                    <td><strong>{{ $bot->name }}</strong></td>
                                    <td>
                                        @if($bot->admin)
                                            <span class="badge badge-info">Admin: {{ $bot->admin->username }}</span>
                                        @elseif($bot->user)
                                            <span class="badge badge-primary">User: {{ $bot->user->username }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $bot->status === 'running' ? 'success' : ($bot->status === 'paused' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($bot->status ?? 'stopped') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($bot->worker_pid)
                                            <code>{{ $bot->worker_pid }}</code>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $bot->updated_at->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('admin.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- All Bots Status Table -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 font-weight-bold"><i class="fa fa-list text-primary"></i> All Trading Bots Status</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Worker PID</th>
                                <th>Worker Status</th>
                                <th>Exchange Connection</th>
                                <th>Last Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allBots as $bot)
                                @php
                                    $workerStatus = $botWorkerStatuses[$bot->id] ?? 'stopped';
                                @endphp
                                <tr>
                                    <td>{{ $bot->id }}</td>
                                    <td><strong>{{ $bot->name }}</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $bot->status === 'running' ? 'success' : ($bot->status === 'paused' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($bot->status ?? 'stopped') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($bot->worker_pid)
                                            <code>{{ $bot->worker_pid }}</code>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $workerStatus === 'running' ? 'success' : ($workerStatus === 'dead' ? 'danger' : 'secondary') }}">
                                            {{ ucfirst($workerStatus) }}
                                        </span>
                                    </td>
                                    <td>{{ $bot->exchangeConnection->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($bot->worker_started_at)
                                            {{ $bot->worker_started_at->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-danger { border-left: 4px solid #e74a3b !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-secondary { border-left: 4px solid #858796 !important; }
</style>
@endpush

<script>
// Auto-refresh system metrics
setInterval(function() {
    // Refresh queue stats (would need an endpoint for this)
    // For now, just reload the page every 30 seconds
}, 30000);
</script>
@endsection
