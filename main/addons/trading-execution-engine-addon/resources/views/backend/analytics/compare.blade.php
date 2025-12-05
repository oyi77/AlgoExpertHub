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
                            <a href="{{ route('admin.execution-analytics.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Analytics
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.execution-analytics.compare') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Select Connections to Compare <span class="text-danger">*</span></label>
                                        <select name="connection_ids[]" id="connectionSelect" class="form-control" multiple required>
                                            @foreach($connections as $conn)
                                                <option value="{{ $conn->id }}" {{ in_array($conn->id, request('connection_ids', [])) ? 'selected' : '' }}>
                                                    {{ $conn->name }} ({{ strtoupper($conn->type) }} - {{ $conn->exchange_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Hold Ctrl/Cmd to select multiple connections</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Time Period</label>
                                        <select name="days" class="form-control">
                                            <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>7 Days</option>
                                            <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>30 Days</option>
                                            <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>90 Days</option>
                                            <option value="365" {{ request('days', 30) == 365 ? 'selected' : '' }}>1 Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary form-control">
                                            <i class="fas fa-chart-bar"></i> Compare
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        @if(isset($comparison) && !empty($comparison['channels']))
                            <div class="alert alert-info">
                                <strong>Period:</strong> {{ $comparison['start_date'] }} to {{ $comparison['end_date'] }} ({{ $comparison['period_days'] }} days)
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Connection</th>
                                            <th>Exchange</th>
                                            <th>Total Trades</th>
                                            <th>Win Rate</th>
                                            <th>Profit Factor</th>
                                            <th>Total P&L</th>
                                            <th>Sharpe Ratio</th>
                                            <th>Max Drawdown</th>
                                            <th>Avg Duration (Hrs)</th>
                                            <th>Balance</th>
                                            <th>Equity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($comparison['channels'] as $index => $channel)
                                            <tr class="{{ $index === 0 ? 'table-success' : '' }}">
                                                <td>
                                                    @if($index === 0)
                                                        <span class="badge badge-success">#1</span>
                                                    @else
                                                        #{{ $index + 1 }}
                                                    @endif
                                                </td>
                                                <td><strong>{{ $channel['connection_name'] }}</strong></td>
                                                <td>{{ $channel['exchange_name'] }}</td>
                                                <td>{{ $channel['total_trades'] }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $channel['win_rate'] >= 50 ? 'success' : 'warning' }}">
                                                        {{ number_format($channel['win_rate'], 2) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $channel['profit_factor'] >= 1 ? 'success' : 'danger' }}">
                                                        {{ number_format($channel['profit_factor'], 2) }}
                                                    </span>
                                                </td>
                                                <td class="{{ $channel['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    <strong>{{ number_format($channel['total_pnl'], 2) }}</strong>
                                                </td>
                                                <td>{{ number_format($channel['sharpe_ratio'], 4) }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $channel['max_drawdown'] < 20 ? 'success' : ($channel['max_drawdown'] < 50 ? 'warning' : 'danger') }}">
                                                        {{ number_format($channel['max_drawdown'], 2) }}%
                                                    </span>
                                                </td>
                                                <td>{{ number_format($channel['average_trade_duration_hours'], 1) }}</td>
                                                <td>{{ number_format($channel['balance'], 2) }}</td>
                                                <td>{{ number_format($channel['equity'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if(isset($comparison['best_performer']))
                            <div class="alert alert-success mt-3">
                                <h5><i class="fas fa-trophy"></i> Best Performer</h5>
                                <p class="mb-0">
                                    <strong>{{ $comparison['best_performer']['connection_name'] }}</strong> 
                                    with total P&L of <strong>{{ number_format($comparison['best_performer']['total_pnl'], 2) }}</strong>
                                    and win rate of <strong>{{ number_format($comparison['best_performer']['win_rate'], 2) }}%</strong>
                                </p>
                            </div>
                            @endif
                        @elseif(request('connection_ids'))
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No data available for the selected connections and period.
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Select one or more connections and click "Compare" to view comparison.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
