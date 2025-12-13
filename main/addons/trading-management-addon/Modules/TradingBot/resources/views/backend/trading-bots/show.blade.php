@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-robot"></i> {{ $title }}</h4>
                    <div>
                        <a href="{{ route('admin.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-secondary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
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
                                @php
                                    $isRunning = method_exists($bot, 'isRunning') ? $bot->isRunning() : ($bot->status === 'running');
                                    $isPaused = method_exists($bot, 'isPaused') ? $bot->isPaused() : ($bot->status === 'paused');
                                    $isStopped = method_exists($bot, 'isStopped') ? $bot->isStopped() : ($bot->status === 'stopped' || !$bot->status);
                                @endphp
                                
                                {{-- 3 Column, 2 Row Grid Layout --}}
                                <div class="row">
                                    {{-- Column 1: Status Badge + Started Time (Started time in bottom left) --}}
                                    <div class="col-md-4 d-flex flex-column justify-content-between">
                                        <div class="mb-2">
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
                                        </div>
                                    </div>

                                    {{-- Column 3: Empty (Row 1) --}}
                                    <div class="col-md-8 d-flex flex-wrap align-items-center justify-content-end" style="gap: 0.5rem;">
                                        @if($isStopped)
                                            <form action="{{ route('admin.trading-management.trading-bots.start', $bot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fa fa-play"></i> Start Bot
                                                </button>
                                            </form>
                                        @elseif($isRunning)
                                            <form action="{{ route('admin.trading-management.trading-bots.restart', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to restart this bot?">
                                                @csrf
                                                <button type="submit" class="btn btn-info btn-lg">
                                                    <i class="fa fa-redo"></i> Restart Bot
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trading-management.trading-bots.pause', $bot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-lg">
                                                    <i class="fa fa-pause"></i> Pause Bot
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to stop this bot?">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-lg">
                                                    <i class="fa fa-stop"></i> Stop Bot
                                                </button>
                                            </form>
                                        @elseif($isPaused)
                                            <form action="{{ route('admin.trading-management.trading-bots.restart', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to restart this bot?">
                                                @csrf
                                                <button type="submit" class="btn btn-info btn-lg">
                                                    <i class="fa fa-redo"></i> Restart Bot
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trading-management.trading-bots.resume', $bot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fa fa-play"></i> Resume Bot
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.trading-management.trading-bots.stop', $bot->id) }}" method="POST" class="d-inline bot-action-form" data-confirm-message="Are you sure you want to stop this bot?">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-lg">
                                                    <i class="fa fa-stop"></i> Stop Bot
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                {{-- Row 2 --}}
                                <div class="row mt-2">
                                    {{-- Column 1: Empty (already occupied by rowspan) --}}
                                    <div class="col-md-4 d-flex flex-column justify-content-start">
                                        <div class="d-flex align-items-end" style="flex:1;">
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

                                    {{-- Column 2: Empty (Row 2) --}}
                                    <div class="col-md-4"></div>

                                    {{-- Column 3: Secondary Buttons (Row 2, Right Side) --}}
                                    <div class="col-md-4 d-flex flex-wrap justify-content-end" style="gap: 0.5rem;">
                                        @if(!$isStopped)
                                            {{-- Toggle Active Status --}}
                                            <form action="{{ route('admin.trading-management.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-{{ $bot->is_active ? 'warning' : 'success' }} btn-lg">
                                                    <i class="fa fa-{{ $bot->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                    {{ $bot->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            
                                            {{-- Test Execution Button --}}
                                            <button type="button" class="btn btn-info btn-lg" id="test-execution-btn" onclick="testExecution()">
                                                <i class="fa fa-flask"></i> Test Bot
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Test Execution Result Alert --}}
                                <div id="test-execution-alert" class="alert" style="display: none; margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabbed Interface --}}
                <ul class="nav nav-tabs page-link-list border-bottom-0" role="tablist" style="width: 100%; display: flex; justify-content: space-between; flex-wrap: nowrap;">
                    <li style="flex: 1; text-align: center;">
                        <a class="active" data-toggle="tab" href="#overview" style="display: block; text-align: center;">
                            <i class="fa fa-info-circle"></i> Overview
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#information" style="display: block; text-align: center;">
                            <i class="fa fa-cog"></i> Information
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#statistics" style="display: block; text-align: center;">
                            <i class="fa fa-chart-bar"></i> Statistics
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#executions" style="display: block; text-align: center;">
                            <i class="fa fa-list"></i> Executions
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#monitoring" style="display: block; text-align: center;">
                            <i class="fa fa-cog"></i> Monitoring
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#positions" style="display: block; text-align: center;">
                            <i class="fa fa-chart-line"></i> Positions
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#queue" style="display: block; text-align: center;">
                            <i class="fa fa-tasks"></i> Queue Jobs
                        </a>
                    </li>
                    <li style="flex: 1; text-align: center;">
                        <a data-toggle="tab" href="#logs" style="display: block; text-align: center;">
                            <i class="fa fa-file-alt"></i> Logs
                        </a>
                    </li>
                </ul>

                <div class="tab-content tabcontent-border">
                    {{-- Overview Tab --}}
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="card-body pt-4">
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
                                            <th>Owner</th>
                                            <td>
                                                @if($bot->admin)
                                                    <span class="badge bg-info">Admin: {{ $bot->admin->username }}</span>
                                                @elseif($bot->user)
                                                    <span class="badge bg-primary">User: {{ $bot->user->username }}</span>
                                                @endif
                                            </td>
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
                                    <h5>Quick Stats</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6>Total Executions</h6>
                                                    <h3>{{ $actualTotalExecutions ?? ($bot->total_executions ?? 0) }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6>Win Rate</h6>
                                                    <h3>{{ number_format($actualWinRate ?? ($bot->win_rate ?? 0), 1) }}%</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6>Open Positions</h6>
                                                    <h3 id="overview-open-positions">{{ $positionStats['total_open'] ?? 0 }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h6>Total Profit</h6>
                                                    <h3 class="{{ ($actualTotalProfit ?? $bot->total_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        ${{ number_format($actualTotalProfit ?? $bot->total_profit ?? 0, 2) }}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Information Tab --}}
                    <div class="tab-pane fade" id="information" role="tabpanel">
                        <div class="card-body pt-4">
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
                                            <th>Owner</th>
                                            <td>
                                                @if($bot->admin)
                                                    <span class="badge bg-info">Admin: {{ $bot->admin->username }}</span>
                                                @elseif($bot->user)
                                                    <span class="badge bg-primary">User: {{ $bot->user->username }}</span>
                                                @endif
                                            </td>
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
                                        @if($bot->status)
                                        <tr>
                                            <th>Bot Status</th>
                                            <td>
                                                <span class="badge bg-{{ $bot->status === 'running' ? 'success' : ($bot->status === 'paused' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($bot->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endif
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
                        </div>
                    </div>

                    {{-- Statistics Tab --}}
                    <div class="tab-pane fade" id="statistics" role="tabpanel">
                        <div class="card-body pt-4">
                            <h5>Statistics</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h6>Total Executions</h6>
                                            <h3>{{ $actualTotalExecutions ?? ($bot->total_executions ?? 0) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h6>Successful</h6>
                                            <h3 class="text-success">{{ $actualSuccessfulExecutions ?? ($bot->successful_executions ?? 0) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h6>Win Rate</h6>
                                            <h3>{{ number_format($actualWinRate ?? ($bot->win_rate ?? 0), 1) }}%</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h6>Total Profit</h6>
                                            <h3 class="{{ ($actualTotalProfit ?? $bot->total_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($actualTotalProfit ?? $bot->total_profit ?? 0, 2) }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Executions Tab --}}
                    <div class="tab-pane fade" id="executions" role="tabpanel">
                        <div class="card-body pt-4">
                            @if($executions->count() > 0)
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
                            @else
                                <div class="alert alert-info">No executions found.</div>
                            @endif
                        </div>
                    </div>

                    {{-- Monitoring Tab --}}
                    <div class="tab-pane fade" id="monitoring" role="tabpanel">
                        <div class="card-body pt-4">
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
                                                        <span class="badge bg-{{ $workerStatus['status'] === 'running' ? 'success' : ($workerStatus['status'] === 'dead' ? 'danger' : 'secondary') }}" id="worker-status-badge">
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
                                                        @if($workerStatus['worker_started_at'])
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
                                                        @if($botMetrics['last_signal_processed_at'])
                                                            {{ \Carbon\Carbon::parse($botMetrics['last_signal_processed_at'])->diffForHumans() }}
                                                        @else
                                                            Never
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Last Market Analysis</th>
                                                    <td id="last-market-analysis">
                                                        @if($botMetrics['last_market_analysis_at'])
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
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Positions Tab --}}
                    <div class="tab-pane fade" id="positions" role="tabpanel">
                        <div class="card-body pt-4">
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
                                                @if(count($openPositions) > 0)
                                                    @foreach($openPositions as $position)
                                                        <tr>
                                                            <td>{{ $position['symbol'] }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ $position['direction'] === 'buy' ? 'success' : 'danger' }}">
                                                                    {{ strtoupper($position['direction']) }}
                                                                </span>
                                                            </td>
                                                            <td>{{ number_format($position['entry_price'], 8) }}</td>
                                                            <td id="price-{{ $position['id'] }}">{{ number_format($position['current_price'] ?? $position['entry_price'], 8) }}</td>
                                                            <td>{{ $position['stop_loss'] ? number_format($position['stop_loss'], 8) : 'N/A' }}</td>
                                                            <td>{{ $position['take_profit'] ? number_format($position['take_profit'], 8) : 'N/A' }}</td>
                                                            <td>{{ number_format($position['quantity'], 8) }}</td>
                                                            <td class="{{ ($position['profit_loss'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="pnl-{{ $position['id'] }}">
                                                                ${{ number_format($position['profit_loss'] ?? 0, 2) }}
                                                            </td>
                                                            <td class="{{ ($position['profit_loss_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="pnl-pct-{{ $position['id'] }}">
                                                                {{ $position['profit_loss_percent'] ? number_format($position['profit_loss_percent'], 2) . '%' : '0%' }}
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success">{{ ucfirst($position['status']) }}</span>
                                                            </td>
                                                            <td>
                                                                @if($position['opened_at'])
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

                    {{-- Queue Jobs Tab --}}
                    <div class="tab-pane fade" id="queue" role="tabpanel">
                        <div class="card-body pt-4">
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
                                    
                                    @if(isset($queueStats['by_type']) && count($queueStats['by_type']) > 0)
                                    <div class="mt-3">
                                        <h6>Jobs by Type</h6>
                                        <div class="row">
                                            @foreach($queueStats['by_type'] as $type => $count)
                                                @if($count > 0)
                                                <div class="col-md-3 mb-2">
                                                    <div class="border rounded p-2">
                                                        <strong>{{ $type }}</strong>: <span class="badge bg-info">{{ $count }}</span>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Logs Tab --}}
                    <div class="tab-pane fade" id="logs" role="tabpanel">
                        <div class="card-body pt-4">
                            <div class="card border-info">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fa fa-file-alt text-info"></i> Real-time Logs
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-2">
                                        <select class="form-control form-control-sm" id="log-level-filter" style="width: 150px; display: inline-block;">
                                            <option value="">All Levels</option>
                                            <option value="error">Error</option>
                                            <option value="warning">Warning</option>
                                            <option value="info">Info</option>
                                            <option value="debug">Debug</option>
                                        </select>
                                    </div>
                                    <div class="border rounded p-2" style="background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 12px; max-height: 500px; overflow-y: auto;" id="log-container">
                                        <div class="text-muted">Loading logs...</div>
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

<script>
// Auto-refresh monitoring data
(function() {
    const botId = {{ $bot->id }};
    let logLevelFilter = '';
    
    // Worker status refresh (every 10 seconds)
    setInterval(function() {
        fetch(`{{ route('admin.trading-management.trading-bots.worker-status', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ws = data.worker_status;
                    const metrics = data.metrics;
                    
                    // Update worker status badge
                    const badge = document.getElementById('worker-status-badge');
                    if (badge) {
                        badge.textContent = ws.status.charAt(0).toUpperCase() + ws.status.slice(1);
                        badge.className = 'badge bg-' + (ws.status === 'running' ? 'success' : (ws.status === 'dead' ? 'danger' : 'secondary'));
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
        fetch(`{{ route('admin.trading-management.trading-bots.positions', $bot->id) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    const positions = data.positions;
                    
                    // Update stats
                    if (document.getElementById('total-open-positions')) document.getElementById('total-open-positions').textContent = stats.total_open || 0;
                    if (document.getElementById('overview-open-positions')) document.getElementById('overview-open-positions').textContent = stats.total_open || 0;
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
                                    <td>${p.symbol}</td>
                                    <td><span class="badge bg-${p.direction === 'buy' ? 'success' : 'danger'}">${p.direction.toUpperCase()}</span></td>
                                    <td>${parseFloat(p.entry_price).toFixed(8)}</td>
                                    <td id="price-${p.id}">${parseFloat(p.current_price || p.entry_price).toFixed(8)}</td>
                                    <td>${p.stop_loss ? parseFloat(p.stop_loss).toFixed(8) : 'N/A'}</td>
                                    <td>${p.take_profit ? parseFloat(p.take_profit).toFixed(8) : 'N/A'}</td>
                                    <td>${parseFloat(p.quantity).toFixed(8)}</td>
                                    <td class="${(p.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-${p.id}">$${parseFloat(p.profit_loss || 0).toFixed(2)}</td>
                                    <td class="${(p.profit_loss_percent || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-pct-${p.id}">${p.profit_loss_percent ? parseFloat(p.profit_loss_percent).toFixed(2) + '%' : '0%'}</td>
                                    <td><span class="badge bg-success">${p.status.charAt(0).toUpperCase() + p.status.slice(1)}</span></td>
                                    <td>${p.opened_at ? new Date(p.opened_at).toLocaleString() : 'N/A'}</td>
                                </tr>
                            `).join('');
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching positions:', error));
    }, 5000);
    
    // Logs refresh (every 5 seconds for real-time feel)
    function refreshLogs() {
        const level = document.getElementById('log-level-filter')?.value || '';
        fetch(`{{ route('admin.trading-management.trading-bots.logs', $bot->id) }}?limit=100&level=${level}`)
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
    
    setInterval(refreshLogs, 5000);
    refreshLogs(); // Initial load
    
    // Log level filter change
    const logFilter = document.getElementById('log-level-filter');
    if (logFilter) {
        logFilter.addEventListener('change', refreshLogs);
    }
    
    // Test Bot
    function testExecution() {
        const btn = document.getElementById('test-execution-btn');
        const alert = document.getElementById('test-execution-alert');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';
        alert.style.display = 'none';
        
        fetch(`{{ route('admin.trading-management.trading-bots.test-execution', $bot->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-flask"></i> Test Bot';
            
            if (data.success) {
                alert.className = 'alert alert-success';
                alert.innerHTML = '<i class="fa fa-check-circle"></i> <strong>Success!</strong> ' + (data.message || 'Test execution completed. Check logs and refresh page to see results.');
                alert.style.display = 'block';
                
                // Refresh page after 3 seconds to show updated stats
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> ' + (data.message || 'Test execution failed');
                alert.style.display = 'block';
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-flask"></i> Test Bot';
            alert.className = 'alert alert-danger';
            alert.innerHTML = '<i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> ' + error.message;
            alert.style.display = 'block';
        });
    }
    
    // Queue stats refresh (every 15 seconds)
    setInterval(function() {
        fetch(`{{ route('admin.trading-management.trading-bots.metrics', $bot->id) }}`)
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
@endsection
