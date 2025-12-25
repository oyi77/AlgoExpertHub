@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4><i class="fas fa-tachometer-alt"></i> Real-Time Monitor - {{ $bot->name }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Bot
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Status Overview --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card {{ $data['status'] == 'running' ? 'bg-success' : ($data['status'] == 'paused' ? 'bg-warning' : 'bg-secondary') }} text-white">
                    <div class="card-body">
                        <h6 class="card-title">Bot Status</h6>
                        <h3 class="mb-0">{{ ucfirst($data['status'] ?? 'stopped') }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Open Positions</h6>
                        <h3 class="mb-0">{{ $data['open_positions'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card {{ ($data['current_pnl'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                    <div class="card-body">
                        <h6 class="card-title">Current P&L</h6>
                        <h3 class="mb-0">{{ number_format($data['current_pnl'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Last Activity</h6>
                        <h6 class="mb-0">{{ $data['last_activity'] ? \Carbon\Carbon::parse($data['last_activity'])->diffForHumans() : 'Never' }}</h6>
                    </div>
                </div>
            </div>
        </div>

        {{-- Health Status --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa fa-heartbeat"></i> Bot Health
                            <span class="badge 
                                @if(($data['health']['status'] ?? 'healthy') == 'healthy') bg-success
                                @elseif(($data['health']['status'] ?? 'healthy') == 'warning') bg-warning
                                @else bg-danger
                                @endif float-end">
                                {{ ucfirst($data['health']['status'] ?? 'healthy') }}
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!empty($data['health']['issues'] ?? []))
                            <div class="alert alert-warning">
                                <strong>Issues Detected:</strong>
                                <ul class="mb-0">
                                    @foreach($data['health']['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="alert alert-success mb-0">
                                <i class="fa fa-check-circle"></i> All systems operational
                            </div>
                        @endif
                        <small class="text-muted">Last checked: {{ $data['health']['last_check'] ?? 'Never' }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Worker Status --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Worker Status</h5>
                    </div>
                    <div class="card-body">
                        <div id="workerStatus">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Executions</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentExecutions">
                            @if(!empty($data['recent_executions'] ?? []))
                                <div class="list-group">
                                    @foreach($data['recent_executions'] as $execution)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>{{ $execution->symbol }}</strong>
                                                <span class="badge {{ $execution->direction == 'buy' || $execution->direction == 'long' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ strtoupper($execution->direction) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="badge 
                                                    @if($execution->status == 'executed') bg-success
                                                    @elseif($execution->status == 'failed') bg-danger
                                                    @else bg-warning
                                                    @endif">
                                                    {{ ucfirst($execution->status) }}
                                                </span>
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $execution->executed_at?->diffForHumans() ?? 'N/A' }}</small>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center mb-0">No recent executions</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto-refresh indicator --}}
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info mb-0">
                    <i class="fa fa-sync-alt fa-spin"></i> Auto-refreshing every 5 seconds
                    <button class="btn btn-sm btn-secondary float-end" onclick="location.reload()">
                        <i class="fa fa-refresh"></i> Refresh Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh monitoring data every 5 seconds
    setInterval(function() {
        fetch('{{ route("user.trading-management.trading-bots.monitor", $bot->id) }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update worker status
                fetch('{{ route("user.trading-management.trading-bots.worker-status", $bot->id) }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(workerData => {
                    if (workerData.success) {
                        const worker = workerData.worker_status;
                        let html = `
                            <p><strong>Status:</strong> <span class="badge ${worker.is_running ? 'bg-success' : 'bg-danger'}">${worker.is_running ? 'Running' : 'Stopped'}</span></p>
                            ${worker.pid ? `<p><strong>PID:</strong> ${worker.pid}</p>` : ''}
                            ${worker.uptime ? `<p><strong>Uptime:</strong> ${worker.uptime}</p>` : ''}
                            ${worker.last_heartbeat ? `<p><strong>Last Heartbeat:</strong> ${worker.last_heartbeat}</p>` : ''}
                        `;
                        document.getElementById('workerStatus').innerHTML = html;
                    }
                });
            }
        });
    }, 5000);

    // Initial load
    fetch('{{ route("user.trading-management.trading-bots.worker-status", $bot->id) }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const worker = data.worker_status;
            let html = `
                <p><strong>Status:</strong> <span class="badge ${worker.is_running ? 'bg-success' : 'bg-danger'}">${worker.is_running ? 'Running' : 'Stopped'}</span></p>
                ${worker.pid ? `<p><strong>PID:</strong> ${worker.pid}</p>` : ''}
                ${worker.uptime ? `<p><strong>Uptime:</strong> ${worker.uptime}</p>` : ''}
                ${worker.last_heartbeat ? `<p><strong>Last Heartbeat:</strong> ${worker.last_heartbeat}</p>` : ''}
            `;
            document.getElementById('workerStatus').innerHTML = html;
        }
    });
</script>
@endpush
@endsection

