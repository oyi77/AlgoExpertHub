@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-heartbeat"></i> {{ $title }}</h4>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                {{-- Worker Status Summary --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-robot"></i> Trading Bot Workers</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Total Bots</h6>
                                        <h3>{{ $workerStats['total'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6>Running</h6>
                                        <h3>{{ $workerStats['running'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h6>Dead</h6>
                                        <h3>{{ $workerStats['dead'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body text-center">
                                        <h6>Stopped/Paused</h6>
                                        <h3>{{ $workerStats['stopped'] + $workerStats['paused'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Queue Workers Status --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-tasks"></i> Queue Workers</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Laravel Queue Workers</h6>
                                        <p>
                                            Status: 
                                            <span class="badge bg-{{ $queueWorkersRunning ? 'success' : 'danger' }}">
                                                {{ $queueWorkersRunning ? 'Running' : 'Stopped' }}
                                            </span>
                                        </p>
                                        <p>Process Count: <strong>{{ $queueWorkersCount }}</strong></p>
                                        <small class="text-muted">Check: <code>ps aux | grep queue:work</code></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>MetaAPI Stream Workers</h6>
                                        <p>Process Count: <strong>{{ $metaapiWorkersCount }}</strong></p>
                                        <small class="text-muted">Check: <code>ps aux | grep metaapi:stream-worker</code></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Connection Health --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-plug"></i> Exchange Connections</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Total</h6>
                                        <h3>{{ $connectionStats['total'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6>Active</h6>
                                        <h3>{{ $connectionStats['active'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h6>Error</h6>
                                        <h3>{{ $connectionStats['error'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h6>Testing/Inactive</h6>
                                        <h3>{{ $connectionStats['testing'] + $connectionStats['inactive'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- System Metrics --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-chart-bar"></i> System Metrics</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Pending Jobs</h6>
                                        <h3 id="system-pending-jobs">{{ $systemMetrics['queue_size'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h6>Processing Jobs</h6>
                                        <h3 id="system-processing-jobs">{{ $systemMetrics['processing_jobs'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h6>Failed Jobs</h6>
                                        <h3 id="system-failed-jobs">{{ $systemMetrics['failed_jobs'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scheduled Jobs Status --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-clock"></i> Scheduled Jobs</h5>
                        <div class="card">
                            <div class="card-body">
                                <p>
                                    Scheduler Status: 
                                    <span class="badge bg-{{ $schedulerRunning ? 'success' : 'danger' }}">
                                        {{ $schedulerRunning ? 'Running' : 'Not Running' }}
                                    </span>
                                </p>
                                <small class="text-muted">
                                    Verify: <code>php artisan schedule:list</code> or check cron: <code>crontab -l</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Bot Activity --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5><i class="fa fa-history"></i> Recent Bot Activity</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
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
                                            <td>{{ $bot->name }}</td>
                                            <td>
                                                @if($bot->admin)
                                                    <span class="badge bg-info">Admin: {{ $bot->admin->username }}</span>
                                                @elseif($bot->user)
                                                    <span class="badge bg-primary">User: {{ $bot->user->username }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $bot->status === 'running' ? 'success' : ($bot->status === 'paused' ? 'warning' : 'secondary') }}">
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

                {{-- All Bots Status Table --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5><i class="fa fa-list"></i> All Trading Bots Status</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
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
                                            <td>{{ $bot->name }}</td>
                                            <td>
                                                <span class="badge bg-{{ $bot->status === 'running' ? 'success' : ($bot->status === 'paused' ? 'warning' : 'secondary') }}">
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
                                                <span class="badge bg-{{ $workerStatus === 'running' ? 'success' : ($workerStatus === 'dead' ? 'danger' : 'secondary') }}">
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
    </div>
</div>

<script>
// Auto-refresh system metrics
setInterval(function() {
    // Refresh queue stats (would need an endpoint for this)
    // For now, just reload the page every 30 seconds
}, 30000);
</script>
@endsection
