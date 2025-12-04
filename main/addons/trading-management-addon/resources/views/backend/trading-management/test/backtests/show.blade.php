@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Backtest Info -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-flask"></i> {{ $backtest->name }}</h4>
                    <a href="{{ route('admin.trading-management.test.backtests.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Symbol:</strong> {{ $backtest->symbol }}</p>
                        <p><strong>Timeframe:</strong> {{ $backtest->timeframe }}</p>
                        <p><strong>Period:</strong> {{ $backtest->start_date->format('Y-m-d') }} to {{ $backtest->end_date->format('Y-m-d') }}</p>
                        <p><strong>Initial Balance:</strong> ${{ number_format($backtest->initial_balance, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Filter Strategy:</strong> {{ $backtest->filterStrategy->name ?? 'None' }}</p>
                        <p><strong>AI Model:</strong> {{ $backtest->aiModelProfile->name ?? 'None' }}</p>
                        <p><strong>Preset:</strong> {{ $backtest->preset->name ?? 'None' }}</p>
                        <p><strong>Status:</strong> 
                            @if($backtest->status === 'completed')
                            <span class="badge badge-success">Completed</span>
                            @elseif($backtest->status === 'running')
                            <span class="badge badge-info">Running ({{ $backtest->progress_percent }}%)</span>
                            @elseif($backtest->status === 'failed')
                            <span class="badge badge-danger">Failed</span>
                            @else
                            <span class="badge badge-warning">Pending</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($backtest->description)
                <hr>
                <p><strong>Description:</strong></p>
                <p>{{ $backtest->description }}</p>
                @endif

                @if($backtest->error_message)
                <div class="alert alert-danger mt-3">
                    <strong>Error:</strong> {{ $backtest->error_message }}
                </div>
                @endif
            </div>
        </div>

        @if($summary)
        <!-- Performance Summary -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Trades</h6>
                        <h3>{{ $summary['total_trades'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Win Rate</h6>
                        <h3 class="text-success">{{ number_format($summary['win_rate'], 2) }}%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total P&L</h6>
                        <h3 class="{{ $summary['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($summary['total_pnl'], 2) }}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Profit Factor</h6>
                        <h3>{{ number_format($summary['profit_factor'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Winning Trades</h6>
                        <h3 class="text-success">{{ $summary['winning_trades'] }}</h3>
                        <small>Avg: ${{ number_format($summary['avg_win'], 2) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Losing Trades</h6>
                        <h3 class="text-danger">{{ $summary['losing_trades'] }}</h3>
                        <small>Avg: ${{ number_format($summary['avg_loss'], 2) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">R/R Ratio</h6>
                        <h3>{{ abs($summary['avg_loss']) > 0 ? number_format(abs($summary['avg_win'] / $summary['avg_loss']), 2) : 'N/A' }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Results Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Trade Results ({{ $backtest->results->count() }} trades)</h5>
            </div>
            <div class="card-body">
                @if($backtest->results->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Direction</th>
                                <th>Entry</th>
                                <th>Exit</th>
                                <th>Lot Size</th>
                                <th>P&L</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backtest->results->take(100) as $result)
                            <tr>
                                <td>{{ $result->entry_time->format('Y-m-d H:i') }}</td>
                                <td>{{ $result->exit_time ? $result->exit_time->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $result->direction === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                        {{ strtoupper($result->direction) }}
                                    </span>
                                </td>
                                <td>{{ $result->entry_price }}</td>
                                <td>{{ $result->exit_price }}</td>
                                <td>{{ $result->lot_size }}</td>
                                <td class="{{ $result->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($result->pnl, 2) }}</strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($backtest->results->count() > 100)
                <p class="text-muted mt-2">Showing first 100 trades. View all in <a href="{{ route('admin.trading-management.test.results.index', ['backtest_id' => $backtest->id]) }}">Results</a>.</p>
                @endif
                @else
                <div class="alert alert-info">No results yet. Backtest is {{ $backtest->status }}.</div>
                @endif
            </div>
        </div>
        @else
        <div class="alert alert-info">
            Backtest results will appear here once the backtest is completed.
        </div>
        @endif
    </div>
</div>
@endsection

