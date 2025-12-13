@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4><i class="fas fa-robot"></i> {{ $bot->name }}</h4>
            <div>
                @if(Route::has('user.trading-management.trading-bots.edit'))
                <a href="{{ route('user.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-edit"></i> Edit
                </a>
                @endif
                @if(Route::has('user.trading-management.trading-bots.index'))
                <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Bot Control Buttons --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa fa-play-circle text-primary"></i> Bot Controls
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center">
                            @php
                                $isRunning = $bot->isRunning();
                                $isPaused = $bot->isPaused();
                                $isStopped = $bot->isStopped();
                            @endphp
                            
                            {{-- Status Badge --}}
                            <div class="mr-auto mb-2">
                                <span class="badge 
                                    @if($isRunning) bg-success
                                    @elseif($isPaused) bg-warning
                                    @else bg-secondary
                                    @endif" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                    @if($isRunning)
                                        <i class="fa fa-play-circle"></i> Running
                                    @elseif($isPaused)
                                        <i class="fa fa-pause-circle"></i> Paused
                                    @else
                                        <i class="fa fa-stop-circle"></i> Stopped
                                    @endif
                                </span>
                                @if($bot->is_active)
                                    <span class="badge bg-info">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                                @if($bot->is_paper_trading)
                                    <span class="badge bg-warning">Paper Trading</span>
                                @endif
                            </div>

                            <div class="ml-auto d-flex flex-wrap" style="gap: 0.5rem;">
                                {{-- Control Buttons --}}
                                @if($isStopped)
                                    <form action="{{ route('user.trading-management.trading-bots.start', $bot->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fa fa-play"></i> Start Bot
                                        </button>
                                    </form>
                                @elseif($isRunning)
                                    <form action="{{ route('user.trading-management.trading-bots.restart', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to restart this bot?">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-lg">
                                            <i class="fa fa-redo"></i> Restart Bot
                                        </button>
                                    </form>
                                    <form action="{{ route('user.trading-management.trading-bots.pause', $bot->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-lg">
                                            <i class="fa fa-pause"></i> Pause Bot
                                        </button>
                                    </form>
                                    <form action="{{ route('user.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to stop this bot?">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-lg">
                                            <i class="fa fa-stop"></i> Stop Bot
                                        </button>
                                    </form>
                                @elseif($isPaused)
                                    <form action="{{ route('user.trading-management.trading-bots.restart', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to restart this bot?">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-lg">
                                            <i class="fa fa-redo"></i> Restart Bot
                                        </button>
                                    </form>
                                    <form action="{{ route('user.trading-management.trading-bots.resume', $bot->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fa fa-play"></i> Resume Bot
                                        </button>
                                    </form>
                                    <form action="{{ route('user.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to stop this bot?">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-lg">
                                            <i class="fa fa-stop"></i> Stop Bot
                                        </button>
                                    </form>
                                @endif

                                {{-- Toggle Active Status --}}
                                <form action="{{ route('user.trading-management.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-{{ $bot->is_active ? 'warning' : 'success' }}">
                                        <i class="fa fa-{{ $bot->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                        {{ $bot->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        {{-- Status Info --}}
                        <div class="mt-3">
                            @if($isRunning && $bot->last_started_at)
                                <small class="text-muted">
                                    <i class="fa fa-clock"></i> Started: {{ $bot->last_started_at->format('Y-m-d H:i:s') }}
                                </small>
                            @elseif($isPaused && $bot->last_paused_at)
                                <small class="text-muted">
                                    <i class="fa fa-clock"></i> Paused: {{ $bot->last_paused_at->format('Y-m-d H:i:s') }}
                                </small>
                            @elseif($isStopped && $bot->last_stopped_at)
                                <small class="text-muted">
                                    <i class="fa fa-clock"></i> Stopped: {{ $bot->last_stopped_at->format('Y-m-d H:i:s') }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5>Bot Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Name</th>
                        <td>{{ $bot->name }}</td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $bot->description ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @php
                                $status = $bot->status ?? 'stopped';
                            @endphp
                            @if($status === 'running')
                                <span class="badge bg-success"><i class="fa fa-play-circle"></i> Running</span>
                            @elseif($status === 'paused')
                                <span class="badge bg-warning"><i class="fa fa-pause-circle"></i> Paused</span>
                            @else
                                <span class="badge bg-secondary"><i class="fa fa-stop-circle"></i> Stopped</span>
                            @endif
                            @if($bot->is_active)
                                <span class="badge bg-info">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                            @if($bot->is_paper_trading)
                                <span class="badge bg-warning">Paper Trading</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Configuration</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Exchange Connection</th>
                        <td>{{ $bot->exchangeConnection->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Trading Preset</th>
                        <td>{{ $bot->tradingPreset->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Filter Strategy</th>
                        <td>{{ $bot->filterStrategy->name ?? 'None' }}</td>
                    </tr>
                    <tr>
                        <th>AI Model Profile</th>
                        <td>{{ $bot->aiModelProfile->name ?? 'None' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h5>Statistics</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Total Executions</h6>
                                <h3>{{ $bot->total_executions }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Successful</h6>
                                <h3 class="text-success">{{ $bot->successful_executions }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Win Rate</h6>
                                <h3>{{ number_format($bot->win_rate, 1) }}%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Total Profit</h6>
                                <h3 class="{{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($bot->total_profit, 2) }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($executions) && $executions->count() > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <h5>Recent Executions</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Signal</th>
                                <th>Symbol</th>
                                <th>Side</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executions as $execution)
                                <tr>
                                    <td>{{ $execution->signal?->title ?? 'N/A' }}</td>
                                    <td>{{ $execution->symbol ?: 'N/A' }}</td>
                                    <td>{{ $execution->side ? strtoupper($execution->side) : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ in_array($execution->status, ['SUCCESS', 'filled']) ? 'success' : 'warning' }}">
                                            {{ ucfirst($execution->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $execution->created_at ? $execution->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $executions->links() }}
            </div>
        </div>
        @endif

        {{-- Worker Status & Monitoring Panel --}}
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-info">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa fa-cog text-info"></i> Worker Status & Monitoring
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="worker-status-panel">
                            <div class="col-md-6">
                                <h6>Worker Process Status</h6>
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-{{ ($workerStatus['status'] ?? 'stopped') === 'running' ? 'success' : (($workerStatus['status'] ?? 'stopped') === 'dead' ? 'danger' : 'secondary') }}" id="worker-status-badge">
                                                {{ ucfirst($workerStatus['status'] ?? 'stopped') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Worker PID</th>
                                        <td id="worker-pid">{{ $workerStatus['worker_pid'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Uptime</th>
                                        <td id="worker-uptime">{{ $workerStatus['uptime'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Started At</th>
                                        <td id="worker-started-at">
                                            @if(isset($workerStatus['worker_started_at']) && $workerStatus['worker_started_at'])
                                                {{ \Carbon\Carbon::parse($workerStatus['worker_started_at'])->format('Y-m-d H:i:s') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Health Metrics</h6>
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Last Signal Processed</th>
                                        <td id="last-signal-processed">
                                            @if(isset($botMetrics['last_signal_processed_at']) && $botMetrics['last_signal_processed_at'])
                                                {{ \Carbon\Carbon::parse($botMetrics['last_signal_processed_at'])->diffForHumans() }}
                                            @else
                                                Never
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Last Market Analysis</th>
                                        <td id="last-market-analysis">
                                            @if(isset($botMetrics['last_market_analysis_at']) && $botMetrics['last_market_analysis_at'])
                                                {{ \Carbon\Carbon::parse($botMetrics['last_market_analysis_at'])->diffForHumans() }}
                                            @else
                                                Never
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Signals Processed</th>
                                        <td id="signals-processed">{{ $botMetrics['signals_processed'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Errors (24h)</th>
                                        <td>
                                            <span class="badge bg-{{ ($botMetrics['error_count_24h'] ?? 0) > 0 ? 'danger' : 'success' }}" id="error-count">
                                                {{ $botMetrics['error_count_24h'] ?? 0 }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Worker Restarts</th>
                                        <td id="restart-count">{{ $botMetrics['worker_restart_count'] ?? 0 }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        {{-- Real-time Logs --}}
                        <div class="mt-3">
                            <h6>Recent Logs</h6>
                            <div class="form-group mb-2">
                                <select class="form-control form-control-sm" id="log-level-filter" style="width: 150px; display: inline-block;">
                                    <option value="">All Levels</option>
                                    <option value="error">Error</option>
                                    <option value="warning">Warning</option>
                                    <option value="info">Info</option>
                                    <option value="debug">Debug</option>
                                </select>
                            </div>
                            <div class="border rounded p-2" style="background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;" id="log-container">
                                <div class="text-muted">Loading logs...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Position Monitoring Dashboard --}}
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-success">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa fa-chart-line text-success"></i> Position Monitoring
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Position Statistics --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Open Positions</h6>
                                        <h3 id="total-open-positions">{{ $positionStats['total_open'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Unrealized P/L</h6>
                                        <h3 class="{{ ($positionStats['total_unrealized_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="total-unrealized-pnl">
                                            ${{ number_format($positionStats['total_unrealized_pnl'] ?? 0, 2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>At Risk</h6>
                                        <h3 class="text-warning" id="positions-at-risk">{{ $positionStats['positions_at_risk'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Near TP</h6>
                                        <h3 class="text-info" id="positions-near-tp">{{ $positionStats['positions_near_tp'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Open Positions Table --}}
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="positions-table">
                                <thead>
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Entry Price</th>
                                        <th>Current Price</th>
                                        <th>Stop Loss</th>
                                        <th>Take Profit</th>
                                        <th>Quantity</th>
                                        <th>P/L</th>
                                        <th>P/L %</th>
                                        <th>Status</th>
                                        <th>Opened</th>
                                    </tr>
                                </thead>
                                <tbody id="positions-tbody">
                                    @if(isset($openPositions) && count($openPositions) > 0)
                                        @foreach($openPositions as $position)
                                            <tr>
                                                <td>{{ $position['symbol'] ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ ($position['direction'] ?? 'buy') === 'buy' ? 'success' : 'danger' }}">
                                                        {{ strtoupper($position['direction'] ?? 'N/A') }}
                                                    </span>
                                                </td>
                                                <td>{{ isset($position['entry_price']) ? number_format($position['entry_price'], 8) : 'N/A' }}</td>
                                                <td id="price-{{ $position['id'] ?? '' }}">{{ isset($position['current_price']) ? number_format($position['current_price'], 8) : (isset($position['entry_price']) ? number_format($position['entry_price'], 8) : 'N/A') }}</td>
                                                <td>{{ isset($position['stop_loss']) && $position['stop_loss'] ? number_format($position['stop_loss'], 8) : 'N/A' }}</td>
                                                <td>{{ isset($position['take_profit']) && $position['take_profit'] ? number_format($position['take_profit'], 8) : 'N/A' }}</td>
                                                <td>{{ isset($position['quantity']) ? number_format($position['quantity'], 8) : 'N/A' }}</td>
                                                <td class="{{ (($position['profit_loss'] ?? 0) >= 0 ? 'text-success' : 'text-danger') }}" id="pnl-{{ $position['id'] ?? '' }}">
                                                    ${{ number_format($position['profit_loss'] ?? 0, 2) }}
                                                </td>
                                                <td class="{{ (($position['profit_loss_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger') }}" id="pnl-pct-{{ $position['id'] ?? '' }}">
                                                    {{ isset($position['profit_loss_percent']) ? number_format($position['profit_loss_percent'], 2) . '%' : '0%' }}
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">{{ ucfirst($position['status'] ?? 'open') }}</span>
                                                </td>
                                                <td>
                                                    @if(isset($position['opened_at']) && $position['opened_at'])
                                                        {{ \Carbon\Carbon::parse($position['opened_at'])->format('Y-m-d H:i') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">No open positions</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Queue Jobs Monitoring --}}
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-warning">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa fa-tasks text-warning"></i> Queue Jobs Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Pending Jobs</h6>
                                        <h3 id="pending-jobs">{{ $queueStats['pending'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Processing</h6>
                                        <h3 id="processing-jobs">{{ $queueStats['processing'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Failed Jobs</h6>
                                        <h3 class="text-danger" id="failed-jobs">{{ $queueStats['failed'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Executions (24h)</h6>
                                        <h3 id="executions-24h">{{ $queueStats['executions_24h'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
// Auto-refresh monitoring data
(function() {
    const botId = {{ $bot->id }};
    let logLevelFilter = '';
    
    // Worker status refresh (every 10 seconds)
    setInterval(function() {
        fetch(`{{ route('user.trading-management.trading-bots.worker-status', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ws = data.worker_status;
                    const metrics = data.metrics;
                    
                    // Update worker status badge
                    const badge = document.getElementById('worker-status-badge');
                    if (badge) {
                        badge.textContent = (ws.status || 'stopped').charAt(0).toUpperCase() + (ws.status || 'stopped').slice(1);
                        badge.className = 'badge bg-' + ((ws.status || 'stopped') === 'running' ? 'success' : ((ws.status || 'stopped') === 'dead' ? 'danger' : 'secondary'));
                    }
                    
                    // Update other fields
                    if (document.getElementById('worker-pid')) document.getElementById('worker-pid').textContent = ws.worker_pid || 'N/A';
                    if (document.getElementById('worker-uptime')) document.getElementById('worker-uptime').textContent = ws.uptime || 'N/A';
                    if (document.getElementById('last-signal-processed')) {
                        document.getElementById('last-signal-processed').textContent = 
                            metrics.last_signal_processed_at ? 
                            new Date(metrics.last_signal_processed_at).toLocaleString() : 'Never';
                    }
                    if (document.getElementById('last-market-analysis')) {
                        document.getElementById('last-market-analysis').textContent = 
                            metrics.last_market_analysis_at ? 
                            new Date(metrics.last_market_analysis_at).toLocaleString() : 'Never';
                    }
                    if (document.getElementById('signals-processed')) document.getElementById('signals-processed').textContent = metrics.signals_processed || 0;
                    if (document.getElementById('error-count')) {
                        const ec = document.getElementById('error-count');
                        ec.textContent = metrics.error_count_24h || 0;
                        ec.className = 'badge bg-' + ((metrics.error_count_24h || 0) > 0 ? 'danger' : 'success');
                    }
                    if (document.getElementById('restart-count')) document.getElementById('restart-count').textContent = metrics.worker_restart_count || 0;
                }
            })
            .catch(error => console.error('Error fetching worker status:', error));
    }, 10000);
    
    // Positions refresh (every 5 seconds)
    setInterval(function() {
        fetch(`{{ route('user.trading-management.trading-bots.positions', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    const positions = data.positions;
                    
                    // Update stats
                    if (document.getElementById('total-open-positions')) document.getElementById('total-open-positions').textContent = stats.total_open || 0;
                    if (document.getElementById('total-unrealized-pnl')) {
                        const pnlEl = document.getElementById('total-unrealized-pnl');
                        pnlEl.textContent = '$' + parseFloat(stats.total_unrealized_pnl || 0).toFixed(2);
                        pnlEl.className = (stats.total_unrealized_pnl || 0) >= 0 ? 'text-success' : 'text-danger';
                    }
                    if (document.getElementById('positions-at-risk')) document.getElementById('positions-at-risk').textContent = stats.positions_at_risk || 0;
                    if (document.getElementById('positions-near-tp')) document.getElementById('positions-near-tp').textContent = stats.positions_near_tp || 0;
                    
                    // Update positions table
                    const tbody = document.getElementById('positions-tbody');
                    if (tbody) {
                        if (positions.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">No open positions</td></tr>';
                        } else {
                            tbody.innerHTML = positions.map(p => `
                                <tr>
                                    <td>${p.symbol || 'N/A'}</td>
                                    <td><span class="badge bg-${(p.direction || 'buy') === 'buy' ? 'success' : 'danger'}">${(p.direction || 'N/A').toUpperCase()}</span></td>
                                    <td>${p.entry_price ? parseFloat(p.entry_price).toFixed(8) : 'N/A'}</td>
                                    <td id="price-${p.id || ''}">${p.current_price ? parseFloat(p.current_price).toFixed(8) : (p.entry_price ? parseFloat(p.entry_price).toFixed(8) : 'N/A')}</td>
                                    <td>${p.stop_loss ? parseFloat(p.stop_loss).toFixed(8) : 'N/A'}</td>
                                    <td>${p.take_profit ? parseFloat(p.take_profit).toFixed(8) : 'N/A'}</td>
                                    <td>${p.quantity ? parseFloat(p.quantity).toFixed(8) : 'N/A'}</td>
                                    <td class="${(p.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-${p.id || ''}">$${parseFloat(p.profit_loss || 0).toFixed(2)}</td>
                                    <td class="${(p.profit_loss_percent || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-pct-${p.id || ''}">${p.profit_loss_percent ? parseFloat(p.profit_loss_percent).toFixed(2) + '%' : '0%'}</td>
                                    <td><span class="badge bg-success">${(p.status || 'open').charAt(0).toUpperCase() + (p.status || 'open').slice(1)}</span></td>
                                    <td>${p.opened_at ? new Date(p.opened_at).toLocaleString() : 'N/A'}</td>
                                </tr>
                            `).join('');
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching positions:', error));
    }, 5000);
    
    // Logs refresh (every 10 seconds)
    function refreshLogs() {
        const level = document.getElementById('log-level-filter')?.value || '';
        fetch(`{{ route('user.trading-management.trading-bots.logs', $bot->id) }}?limit=50&level=${level}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.logs) {
                    const container = document.getElementById('log-container');
                    if (container) {
                        if (data.logs.length === 0) {
                            container.innerHTML = '<div class="text-muted">No logs found</div>';
                        } else {
                            container.innerHTML = data.logs.map(log => {
                                // Skip logs with invalid format
                                if (log.level === 'unknown' && !log.timestamp) {
                                    return '';
                                }
                                const levelClass = log.level === 'error' ? 'text-danger' : (log.level === 'warning' ? 'text-warning' : (log.level === 'info' ? 'text-info' : 'text-muted'));
                                const level = (log.level || 'info').toUpperCase();
                                const message = log.message || log.raw || '';
                                // Only show if we have a valid message
                                if (!message || message.trim() === '') {
                                    return '';
                                }
                                return `<div class="${levelClass}">[${log.timestamp || 'N/A'}] [${level}] ${message}</div>`;
                            }).filter(html => html !== '').join('');
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching logs:', error));
    }
    
    setInterval(refreshLogs, 10000);
    refreshLogs(); // Initial load
    
    // Log level filter change
    const logFilter = document.getElementById('log-level-filter');
    if (logFilter) {
        logFilter.addEventListener('change', refreshLogs);
    }
    
    // Queue stats refresh (every 15 seconds)
    setInterval(function() {
        fetch(`{{ route('user.trading-management.trading-bots.metrics', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.queue_stats) {
                    const qs = data.queue_stats;
                    if (document.getElementById('pending-jobs')) document.getElementById('pending-jobs').textContent = qs.pending || 0;
                    if (document.getElementById('processing-jobs')) document.getElementById('processing-jobs').textContent = qs.processing || 0;
                    if (document.getElementById('failed-jobs')) document.getElementById('failed-jobs').textContent = qs.failed || 0;
                    if (document.getElementById('executions-24h')) document.getElementById('executions-24h').textContent = qs.executions_24h || 0;
                }
            })
            .catch(error => console.error('Error fetching queue stats:', error));
    }, 15000);
})();

// Handle bot action forms with confirmation
$(document).ready(function() {
    $('.bot-action-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        const form = $(this);
        const message = form.data('confirm-message') || 'Are you sure?';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '{{ __("Confirmation") }}',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __("Confirm") }}',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.off('submit').submit();
                }
            });
        } else {
            if (confirm(message)) {
                form.off('submit').submit();
            }
        }
        
        return false;
    });
});
</script>
@endpush
@endsection
