@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">{{ $title }}</h4>
                            @if(isset($connection))
                            <div>
                                <a href="{{ route('admin.execution-analytics.export.csv', ['connection_id' => $connection->id, 'days' => request('days', 30)]) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a href="{{ route('admin.execution-analytics.export.json', ['connection_id' => $connection->id, 'days' => request('days', 30)]) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-file-code"></i> Export JSON
                                </a>
                                <a href="{{ route('admin.execution-analytics.compare', ['connection_ids' => [$connection->id], 'days' => request('days', 30)]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-chart-bar"></i> Compare Channels
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.execution-analytics.index') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Select Connection <span class="text-danger">*</span></label>
                                        <select name="connection_id" id="connectionSelect" class="form-control" required onchange="this.form.submit()">
                                            <option value="">-- Select Connection --</option>
                                            @foreach($connections as $conn)
                                                <option value="{{ $conn->id }}" {{ request('connection_id') == $conn->id ? 'selected' : '' }}>
                                                    {{ $conn->name }} ({{ strtoupper($conn->type) }} - {{ $conn->exchange_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @if(request('connection_id'))
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Time Period</label>
                                        <select name="days" class="form-control" onchange="this.form.submit()">
                                            <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>Last 7 Days</option>
                                            <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 Days</option>
                                            <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>Last 90 Days</option>
                                            <option value="365" {{ request('days', 30) == 365 ? 'selected' : '' }}>Last Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <a href="{{ route('admin.execution-analytics.compare') }}" class="btn btn-primary form-control">
                                            <i class="fas fa-chart-bar"></i> Compare
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </form>

                        @if(isset($connection) && isset($summary))
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5>Total Trades</h5>
                                            <h3>{{ $summary['total_trades'] ?? 0 }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5>Win Rate</h5>
                                            <h3>{{ number_format($summary['win_rate'] ?? 0, 2) }}%</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5>Total PnL</h5>
                                            <h3>{{ number_format($summary['total_pnl'] ?? 0, 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h5>Profit Factor</h5>
                                            <h3>{{ number_format($summary['profit_factor'] ?? 0, 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-secondary text-white">
                                        <div class="card-body">
                                            <h5>Max Drawdown</h5>
                                            <h3>{{ number_format($summary['max_drawdown'] ?? 0, 2) }}%</h3>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $sharpeRatio = app(\Addons\TradingExecutionEngine\App\Services\AnalyticsService::class)
                                        ->calculateSharpeRatio($connection, \Carbon\Carbon::today());
                                @endphp
                                <div class="col-md-3">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body">
                                            <h5>Sharpe Ratio</h5>
                                            <h3>{{ number_format($sharpeRatio, 4) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5>Balance</h5>
                                            <h3>{{ number_format($summary['balance'] ?? 0, 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5>Equity</h5>
                                            <h3>{{ number_format($summary['equity'] ?? 0, 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(isset($recent_positions) && $recent_positions->count() > 0)
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5>Recent Closed Positions</h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Symbol</th>
                                                        <th>Direction</th>
                                                        <th>Entry Price</th>
                                                        <th>Close Price</th>
                                                        <th>PnL</th>
                                                        <th>Closed At</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recent_positions as $position)
                                                        <tr>
                                                            <td>{{ $position->symbol }}</td>
                                                            <td>{{ strtoupper($position->direction) }}</td>
                                                            <td>{{ number_format($position->entry_price, 4) }}</td>
                                                            <td>{{ $position->current_price ? number_format($position->current_price, 4) : 'N/A' }}</td>
                                                            <td class="{{ $position->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                                {{ number_format($position->pnl, 2) }} ({{ number_format($position->pnl_percentage, 2) }}%)
                                                            </td>
                                                            <td>{{ $position->closed_at ? $position->closed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @elseif($connections->count() > 0)
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Please select a connection from the dropdown above to view analytics.
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No connections found. <a href="{{ route('admin.execution-connections.create') }}">Create a connection</a> to view analytics.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

