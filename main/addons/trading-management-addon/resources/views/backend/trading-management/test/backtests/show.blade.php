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
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Final Balance</h6>
                        <h3 class="{{ $summary['final_balance'] >= $backtest->initial_balance ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($summary['final_balance'], 2) }}
                        </h3>
                        <small>Return: {{ number_format($summary['return_percent'], 2) }}%</small>
                    </div>
                </div>
            </div>
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
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Sharpe Ratio</h6>
                        <h3>{{ number_format($summary['sharpe_ratio'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Max Drawdown</h6>
                        <h3 class="text-danger">{{ number_format($summary['max_drawdown_percent'] ?? 0, 2) }}%</h3>
                        <small>${{ number_format($summary['max_drawdown'] ?? 0, 2) }}</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Grade</h6>
                        <h3>{{ $summary['grade'] ?? 'N/A' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Largest Win</h6>
                        <h3 class="text-success">${{ number_format($summary['largest_win'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Largest Loss</h6>
                        <h3 class="text-danger">${{ number_format($summary['largest_loss'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Consecutive</h6>
                        <h3>
                            <span class="text-success">W: {{ $summary['consecutive_wins'] ?? 0 }}</span> / 
                            <span class="text-danger">L: {{ $summary['consecutive_losses'] ?? 0 }}</span>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equity Curve Chart -->
        @if(!empty($equityCurve))
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Equity Curve</h5>
            </div>
            <div class="card-body">
                <canvas id="equityCurveChart" height="100"></canvas>
            </div>
        </div>
        @endif

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
                <h5><i class="fas fa-list"></i> Trade Results ({{ $summary['total_trades'] ?? 0 }} trades)</h5>
            </div>
            <div class="card-body">
                @if(!empty($tradeDetails) && count($tradeDetails) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Direction</th>
                                <th>Entry Price</th>
                                <th>Exit Price</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>Lot Size</th>
                                <th>P&L</th>
                                <th>Exit Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($tradeDetails, 0, 100) as $trade)
                            <tr>
                                <td>{{ isset($trade['entry_time']) ? date('Y-m-d H:i', $trade['entry_time']) : 'N/A' }}</td>
                                <td>{{ isset($trade['exit_time']) ? date('Y-m-d H:i', $trade['exit_time']) : 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ ($trade['direction'] ?? 'buy') === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                        {{ strtoupper($trade['direction'] ?? 'BUY') }}
                                    </span>
                                </td>
                                <td>{{ number_format($trade['entry_price'] ?? 0, 5) }}</td>
                                <td>{{ number_format($trade['exit_price'] ?? 0, 5) }}</td>
                                <td>{{ number_format($trade['sl'] ?? 0, 5) }}</td>
                                <td>{{ number_format($trade['tp'] ?? 0, 5) }}</td>
                                <td>{{ number_format($trade['lot_size'] ?? 0, 2) }}</td>
                                <td class="{{ ($trade['pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($trade['pnl'] ?? 0, 2) }}</strong>
                                </td>
                                <td>
                                    <span class="badge {{ ($trade['exit_reason'] ?? '') === 'TP' ? 'badge-success' : 'badge-danger' }}">
                                        {{ $trade['exit_reason'] ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($tradeDetails) > 100)
                <p class="text-muted mt-2">Showing first 100 trades of {{ count($tradeDetails) }} total.</p>
                @endif
                @else
                <div class="alert alert-info">No trade details available. Backtest is {{ $backtest->status }}.</div>
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

@if(!empty($equityCurve))
@push('script')
<script src="{{ Config::jsLib('backend', 'chartjs.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('equityCurveChart');
        if (ctx) {
            const equityData = @json($equityCurve);
            const labels = equityData.map(item => new Date(item.timestamp * 1000).toLocaleDateString());
            const equityValues = equityData.map(item => parseFloat(item.equity));
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Equity',
                        data: equityValues,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Equity ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endif
@endsection

