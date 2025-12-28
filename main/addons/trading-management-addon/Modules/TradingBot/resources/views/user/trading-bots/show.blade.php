@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')

<div class="row mb-4" style="position: relative; z-index: 10;">
    <div class="col-12">

        @if(Route::has('user.trading-management.trading-bots.index'))
        <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-outline-info mb-3">
            <i class="fa fa-arrow-left me-1"></i> Back to Trading Bots
        </a>
        @endif
        <div class="card shadow-sm border-0" style="overflow: visible;">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                            <i class="fas fa-robot fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold">{{ $bot->name }}</h4>
                            <div class="d-flex flex-wrap gap-2 text-muted small">
                                <span><i class="fas fa-hashtag me-1"></i>ID: {{ $bot->id }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $bot->exchangeConnection->name ?? 'No Connection' }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $bot->tradingPreset->name ?? 'No Preset' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @php
                            $isRunning = $bot->isRunning();
                            $isPaused = $bot->isPaused();
                            $isStopped = $bot->isStopped();
                        @endphp

                        {{-- Status Badges --}}
                        <span class="badge {{ $isRunning ? 'bg-success' : ($isPaused ? 'bg-warning' : 'bg-secondary') }} px-3 py-2 rounded-pill">
                            <i class="fas {{ $isRunning ? 'fa-sync fa-spin' : ($isPaused ? 'fa-pause' : 'fa-stop') }} me-1"></i>
                            {{ $isRunning ? 'Running' : ($isPaused ? 'Paused' : 'Stopped') }}
                        </span>

                        @if($bot->is_paper_trading)
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                <i class="fas fa-flask me-1"></i> Paper Trading
                            </span>
                        @endif

                        {{-- Control Buttons --}}
                        <div class="d-flex align-items-center gap-3 ms-lg-3">
                            @if($isStopped)
                                <form action="{{ route('user.trading-management.trading-bots.start', $bot->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-4 fw-bold shadow-sm transition-all hover-lift">
                                        <i class="fa fa-play me-2"></i> Start Bot
                                    </button>
                                </form>
                            @elseif($isRunning)
                                <form action="{{ route('user.trading-management.trading-bots.pause', $bot->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm transition-all hover-lift d-flex align-items-center">
                                        <i class="fa fa-pause me-2"></i> Pause
                                    </button>
                                </form>
                                
                                <form action="{{ route('user.trading-management.trading-bots.restart', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Restart bot?">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-muted hover-text-info transition-all p-2" title="Restart Strategy">
                                        <i class="fa fa-redo fa-lg"></i>
                                    </button>
                                </form>
                                
                                <form action="{{ route('user.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Stop bot?">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-muted hover-text-danger text-decoration-none fw-bold d-flex align-items-center transition-all" title="Stop Bot">
                                        <i class="fa fa-stop me-2"></i> Stop
                                    </button>
                                </form>
                            @elseif($isPaused)
                                <form action="{{ route('user.trading-management.trading-bots.resume', $bot->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-4 fw-bold shadow-sm transition-all hover-lift">
                                        <i class="fa fa-play me-2"></i> Resume
                                    </button>
                                </form>
                                <form action="{{ route('user.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Stop bot?">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-muted hover-text-danger text-decoration-none fw-bold d-flex align-items-center transition-all">
                                        <i class="fa fa-stop me-2"></i> Stop
                                    </button>
                                </form>
                            @endif
                        </div>
                        
                        {{-- Actions Dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-primary border shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                @if(Route::has('user.trading-management.trading-bots.edit'))
                                <li>
                                    <a href="{{ route('user.trading-management.trading-bots.edit', $bot->id) }}" class="dropdown-item">
                                        <i class="fa fa-edit me-2 text-primary"></i> Edit Configuration
                                    </a>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('user.trading-management.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-block w-100">
                                        @csrf
                                        <button type="submit" class="dropdown-item {{ $bot->is_active ? 'text-warning' : 'text-success' }}">
                                            <i class="fa fa-{{ $bot->is_active ? 'toggle-on' : 'toggle-off' }} me-2"></i>
                                            {{ $bot->is_active ? 'Deactivate Bot' : 'Activate Bot' }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted text-uppercase small mb-0 spacing-1">Total P/L</h6>
                    <span class="badge bg-light text-dark rounded-circle p-2"><i class="fas fa-dollar-sign"></i></span>
                </div>
                <h3 class="mb-0 {{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $bot->total_profit >= 0 ? '+' : '' }}{{ number_format($bot->total_profit, 2) }}
                </h3>
                <small class="text-muted">Total Profit/Loss</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted text-uppercase small mb-0 spacing-1">Win Rate</h6>
                    <span class="badge bg-light text-dark rounded-circle p-2"><i class="fas fa-percentage"></i></span>
                </div>
                <h3 class="mb-0 text-primary">{{ number_format($bot->win_rate, 1) }}%</h3>
                <small class="text-muted">{{ $bot->successful_executions }} / {{ $bot->total_executions }} Trades</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted text-uppercase small mb-0 spacing-1">Open Positions</h6>
                    <span class="badge bg-light text-dark rounded-circle p-2"><i class="fas fa-layer-group"></i></span>
                </div>
                <h3 class="mb-0" id="total-open-positions">{{ $positionStats['total_open'] ?? 0 }}</h3>
                <small class="text-muted">Active Trades</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted text-uppercase small mb-0 spacing-1">Unrealized P/L</h6>
                    <span class="badge bg-light text-dark rounded-circle p-2"><i class="fas fa-chart-line"></i></span>
                </div>
                <h3 class="mb-0 {{ ($positionStats['total_unrealized_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="total-unrealized-pnl">
                    {{ ($positionStats['total_unrealized_pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($positionStats['total_unrealized_pnl'] ?? 0, 2) }}
                </h3>
                <small class="text-muted">Floating P/L</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Main Content Column --}}
    <div class="col-12 col-lg-8">
        {{-- Live Monitor Panel --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-desktop text-primary me-2"></i>Live Monitor</h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-circle text-{{ ($workerStatus['status'] ?? 'stopped') === 'running' ? 'success' : 'danger' }} me-1 small"></i>
                        Worker: {{ ucfirst($workerStatus['status'] ?? 'stopped') }}
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" id="positions-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Symbol</th>
                                <th>Side</th>
                                <th>Entry</th>
                                <th>Current</th>
                                <th>P/L</th>
                                <th class="pe-4 text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody id="positions-tbody">
                            @if(isset($openPositions) && count($openPositions) > 0)
                                @foreach($openPositions as $position)
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $position['symbol'] ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ ($position['direction'] ?? 'buy') === 'buy' ? 'success' : 'danger' }} bg-opacity-10 text-{{ ($position['direction'] ?? 'buy') === 'buy' ? 'success' : 'danger' }} border border-{{ ($position['direction'] ?? 'buy') === 'buy' ? 'success' : 'danger' }}">
                                                {{ strtoupper($position['direction'] ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td>{{ isset($position['entry_price']) ? number_format($position['entry_price'], 8) : '-' }}</td>
                                        <td id="price-{{ $position['id'] ?? '' }}">{{ isset($position['current_price']) ? number_format($position['current_price'], 8) : '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="{{ (($position['profit_loss'] ?? 0) >= 0 ? 'text-success' : 'text-danger') }} fw-bold" id="pnl-{{ $position['id'] ?? '' }}">
                                                    ${{ number_format($position['profit_loss'] ?? 0, 2) }}
                                                </span>
                                                <span class="small {{ (($position['profit_loss_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger') }}" id="pnl-pct-{{ $position['id'] ?? '' }}">
                                                    {{ isset($position['profit_loss_percent']) ? number_format($position['profit_loss_percent'], 2) . '%' : '0%' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end text-muted small">
                                            @if(isset($position['opened_at']) && $position['opened_at'])
                                                {{ \Carbon\Carbon::parse($position['opened_at'])->diffForHumans(null, true, true) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                            <span>No active positions</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Live Logs --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-terminal text-dark me-2"></i>Live Logs</h6>
                <select class="form-select form-select-sm w-auto" id="log-level-filter">
                    <option value="">All Levels</option>
                    <option value="error">Errors</option>
                    <option value="warning">Warnings</option>
                    <option value="info">Info</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div class="bg-dark text-light p-3 font-monospace small" style="height: 300px; overflow-y: auto; border-radius: 0 0 calc(0.375rem - 1px) calc(0.375rem - 1px);" id="log-container">
                    <div class="text-muted">Waiting for logs...</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar Column --}}
    <div class="col-12 col-lg-4">
        {{-- Health Status --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-heartbeat text-danger me-2"></i>Health Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                        <span class="text-muted">Last Signal</span>
                        <span class="fw-bold" id="last-signal-processed">
                            {{ isset($botMetrics['last_signal_processed_at']) ? \Carbon\Carbon::parse($botMetrics['last_signal_processed_at'])->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                        <span class="text-muted">Last Analysis</span>
                        <span class="fw-bold" id="last-market-analysis">
                            {{ isset($botMetrics['last_market_analysis_at']) ? \Carbon\Carbon::parse($botMetrics['last_market_analysis_at'])->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                        <span class="text-muted">Signals Processed</span>
                        <span class="fw-bold" id="signals-processed">{{ $botMetrics['signals_processed'] ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Errors (24h)</span>
                        <span class="badge bg-{{ ($botMetrics['error_count_24h'] ?? 0) > 0 ? 'danger' : 'success' }} rounded-pill" id="error-count">{{ $botMetrics['error_count_24h'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-sliders-h text-secondary me-2"></i>Configuration</h6>
                @if(Route::has('user.trading-management.trading-bots.edit'))
                    <a href="{{ route('user.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-sm btn-link text-decoration-none">Edit</a>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small text-uppercase">Exchange</label>
                    <div class="fw-bold">{{ $bot->exchangeConnection->name ?? 'N/A' }}</div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small text-uppercase">Strategy</label>
                    <div class="fw-bold">{{ $bot->tradingPreset->name ?? 'N/A' }}</div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small text-uppercase">Filter</label>
                    <div class="fw-bold text-truncate">{{ $bot->filterStrategy->name ?? 'None' }}</div>
                </div>
                 <div class="mb-0">
                    <label class="text-muted small text-uppercase">AI Profile</label>
                    <div class="fw-bold text-truncate">{{ $bot->aiModelProfile->name ?? 'None' }}</div>
                </div>
            </div>
        </div>

        {{-- Queue Status --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tasks text-info me-2"></i>Queue Status</h6>
            </div>
            <div class="card-body">
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="small text-muted mb-1">Pending</div>
                            <div class="fw-bold" id="pending-jobs">{{ $queueStats['pending'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="small text-muted mb-1">Active</div>
                            <div class="fw-bold text-primary" id="processing-jobs">{{ $queueStats['processing'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <div class="small text-muted mb-1">Failed</div>
                            <div class="fw-bold text-danger" id="failed-jobs">{{ $queueStats['failed'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh monitoring data
(function() {
    // Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded! Monitoring features will not work.');
        return;
    }
    
    const botId = {{ $bot->id }};
    
    // Worker status refresh (every 10 seconds)
    setInterval(function() {
        fetch(`{{ route('user.trading-management.trading-bots.worker-status', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ws = data.worker_status;
                    const metrics = data.metrics;
                    
                    // Update worker status badge (if exists in DOM)
                    const badge = document.getElementById('worker-status-badge');
                    if (badge) {
                        badge.textContent = (ws.status || 'stopped').charAt(0).toUpperCase() + (ws.status || 'stopped').slice(1);
                        badge.className = 'badge bg-' + ((ws.status || 'stopped') === 'running' ? 'success' : ((ws.status || 'stopped') === 'dead' ? 'danger' : 'secondary'));
                    }
                    
                    // Update metrics
                    if (document.getElementById('last-signal-processed')) {
                        document.getElementById('last-signal-processed').textContent = 
                            metrics.last_signal_processed_at ? new Date(metrics.last_signal_processed_at).toLocaleString() : 'Never';
                    }
                    if (document.getElementById('last-market-analysis')) {
                        document.getElementById('last-market-analysis').textContent = 
                            metrics.last_market_analysis_at ? new Date(metrics.last_market_analysis_at).toLocaleString() : 'Never';
                    }
                    if (document.getElementById('signals-processed')) document.getElementById('signals-processed').textContent = metrics.signals_processed || 0;
                    if (document.getElementById('error-count')) {
                        const ec = document.getElementById('error-count');
                        ec.textContent = metrics.error_count_24h || 0;
                        ec.className = 'badge rounded-pill bg-' + ((metrics.error_count_24h || 0) > 0 ? 'danger' : 'success');
                    }
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
                    
                    // Update stats headings
                    if (document.getElementById('total-open-positions')) document.getElementById('total-open-positions').textContent = stats.total_open || 0;
                    if (document.getElementById('total-unrealized-pnl')) {
                        const pnlEl = document.getElementById('total-unrealized-pnl');
                        const pnlVal = parseFloat(stats.total_unrealized_pnl || 0);
                        pnlEl.textContent = (pnlVal >= 0 ? '+' : '') + pnlVal.toFixed(2);
                        pnlEl.className = 'mb-0 ' + (pnlVal >= 0 ? 'text-success' : 'text-danger');
                    }
                    
                    // Update positions table
                    const tbody = document.getElementById('positions-tbody');
                    if (tbody) {
                        if (positions.length === 0) {
                            tbody.innerHTML = `<tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                            <span>No active positions</span>
                                        </div>
                                    </td>
                                </tr>`;
                        } else {
                            tbody.innerHTML = positions.map(p => `
                                <tr>
                                    <td class="ps-4 fw-bold">${p.symbol || 'N/A'}</td>
                                    <td>
                                        <span class="badge bg-${(p.direction || 'buy') === 'buy' ? 'success' : 'danger'} bg-opacity-10 text-${(p.direction || 'buy') === 'buy' ? 'success' : 'danger'} border border-${(p.direction || 'buy') === 'buy' ? 'success' : 'danger'}">
                                            ${(p.direction || 'N/A').toUpperCase()}
                                        </span>
                                    </td>
                                    <td>${p.entry_price ? parseFloat(p.entry_price).toFixed(8) : '-'}</td>
                                    <td id="price-${p.id || ''}">${p.current_price ? parseFloat(p.current_price).toFixed(8) : (p.entry_price ? parseFloat(p.entry_price).toFixed(8) : '-')}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="${(p.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'} fw-bold" id="pnl-${p.id || ''}">$${parseFloat(p.profit_loss || 0).toFixed(2)}</span>
                                            <span class="small ${(p.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-pct-${p.id || ''}">${p.profit_loss_percent ? parseFloat(p.profit_loss_percent).toFixed(2) + '%' : '0%'}</span>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-end text-muted small">${p.opened_at ? new Date(p.opened_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-'}</td>
                                </tr>
                            `).join('');
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching positions:', error));
    }, 5000);
    
    // Logs refresh (every 5 seconds)
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
                                if (log.level === 'unknown' && !log.timestamp) return '';
                                const levelColor = log.level === 'error' ? 'text-danger' : (log.level === 'warning' ? 'text-warning' : (log.level === 'info' ? 'text-info' : 'text-muted'));
                                const message = log.message || log.raw || '';
                                if (!message || message.trim() === '') return '';
                                // Format: [10:00:00] [INFO] Message
                                const time = log.timestamp ? new Date(log.timestamp).toLocaleTimeString() : 'N/A';
                                return `<div class="mb-1"><span class="text-muted">[${time}]</span> <span class="${levelColor} fw-bold">[${(log.level || 'INFO').toUpperCase()}]</span> <span class="text-light">${message}</span></div>`;
                            }).filter(html => html !== '').join('');
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching logs:', error));
    }
    
    setInterval(refreshLogs, 5000);
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
                }
            })
            .catch(error => console.error('Error fetching queue stats:', error));
    }, 15000);
})();

// SweetAlert2 Confirmation
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        $('.bot-action-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const message = form.data('confirm-message') || 'Are you sure?';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Action',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit();
                    }
                });
            } else {
                if (confirm(message)) form.off('submit').submit();
            }
        });
    });
}
</script>
@endpush
@endsection
