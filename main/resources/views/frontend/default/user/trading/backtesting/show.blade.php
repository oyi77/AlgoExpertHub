@extends(Config::themeView('layout.auth'))

@section('content')
<div class="row gy-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-0">{{ __('Backtest Results') }} - {{ $backtest->name }}</h4>
                <p class="text-muted mb-0">{{ __('Detailed performance analysis') }}</p>
            </div>
            <a href="{{ route('user.trading.backtesting.index', ['tab' => 'results']) }}" class="btn btn-secondary">
                <i class="las la-arrow-left"></i> {{ __('Back to Results') }}
            </a>
        </div>
    </div>

    @if($backtest->status === 'completed')
        <!-- Performance Metrics -->
        <div class="col-12">
            <div class="row gy-3">
                <div class="col-md-3">
                    <div class="sp_site_card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">{{ __('Total Return') }}</h6>
                            <h3 class="mb-0 {{ $backtest->total_return >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $backtest->total_return >= 0 ? '+' : '' }}{{ number_format($backtest->total_return, 2) }}%
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sp_site_card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">{{ __('Win Rate') }}</h6>
                            <h3 class="mb-0">{{ number_format($backtest->win_rate, 2) }}%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sp_site_card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">{{ __('Max Drawdown') }}</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($backtest->max_drawdown, 2) }}%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="sp_site_card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">{{ __('Profit Factor') }}</h6>
                            <h3 class="mb-0">{{ number_format($backtest->profit_factor, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Performance Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>{{ __('Initial Balance') }}:</strong> ${{ number_format($backtest->initial_balance, 2) }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Final Balance') }}:</strong> ${{ number_format($backtest->final_balance, 2) }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Total Trades') }}:</strong> {{ $backtest->total_trades }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Winning Trades') }}:</strong> {{ $backtest->winning_trades }} / {{ $backtest->losing_trades }} {{ __('Losing') }}
                        </div>
                        <div class="col-md-3 mt-3">
                            <strong>{{ __('Average Win') }}:</strong> ${{ number_format($backtest->average_win, 2) }}
                        </div>
                        <div class="col-md-3 mt-3">
                            <strong>{{ __('Average Loss') }}:</strong> ${{ number_format($backtest->average_loss, 2) }}
                        </div>
                        <div class="col-md-3 mt-3">
                            <strong>{{ __('Symbol') }}:</strong> {{ $backtest->symbol }}
                        </div>
                        <div class="col-md-3 mt-3">
                            <strong>{{ __('Timeframe') }}:</strong> {{ $backtest->timeframe }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equity Curve Chart -->
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Equity Curve') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="equityCurveChart" height="80"></canvas>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            {{ __('Account balance over time') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Generate equity curve data from trades
                const trades = @json($trades->items() ?? []);
                const initialBalance = {{ $backtest->initial_balance }};
                const finalBalance = {{ $backtest->final_balance }};
                
                // Calculate equity curve points
                let balance = initialBalance;
                const equityData = [balance];
                const labels = ['Start'];
                
                // Sort trades by entry time
                const sortedTrades = trades.sort((a, b) => {
                    return new Date(a.entry_time) - new Date(b.entry_time);
                });
                
                sortedTrades.forEach((trade, index) => {
                    balance += parseFloat(trade.profit_loss || 0);
                    equityData.push(balance);
                    labels.push(new Date(trade.exit_time || trade.entry_time).toLocaleDateString());
                });
                
                // Add final balance point
                if (equityData.length === 1) {
                    equityData.push(finalBalance);
                    labels.push('End');
                }
                
                // Create chart
                const ctx = document.getElementById('equityCurveChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: '{{ __('Account Balance') }}',
                                data: equityData,
                                borderColor: balance >= initialBalance ? 'rgb(75, 192, 192)' : 'rgb(255, 99, 132)',
                                backgroundColor: balance >= initialBalance ? 'rgba(75, 192, 192, 0.2)' : 'rgba(255, 99, 132, 0.2)',
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + context.parsed.y.toFixed(2);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value.toFixed(2);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
        @endpush

        <!-- Trade List -->
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Trade History') }}</h5>
                    <a href="{{ route('user.trading.backtesting.export', $backtest->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="las la-download"></i> {{ __('Export CSV') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($trades->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Entry Time') }}</th>
                                        <th>{{ __('Exit Time') }}</th>
                                        <th>{{ __('Direction') }}</th>
                                        <th>{{ __('Entry Price') }}</th>
                                        <th>{{ __('Exit Price') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('P&L') }}</th>
                                        <th>{{ __('P&L %') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trades as $trade)
                                    <tr>
                                        <td>{{ $trade->entry_time->format('Y-m-d H:i') }}</td>
                                        <td>{{ $trade->exit_time ? $trade->exit_time->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <span class="badge {{ $trade->direction === 'buy' || $trade->direction === 'long' ? 'bg-success' : 'bg-danger' }}">
                                                {{ strtoupper($trade->direction) }}
                                            </span>
                                        </td>
                                        <td>${{ number_format($trade->entry_price, 2) }}</td>
                                        <td>${{ $trade->exit_price ? number_format($trade->exit_price, 2) : '-' }}</td>
                                        <td>{{ number_format($trade->quantity, 4) }}</td>
                                        <td class="{{ $trade->profit_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $trade->profit_loss >= 0 ? '+' : '' }}${{ number_format($trade->profit_loss, 2) }}
                                        </td>
                                        <td class="{{ $trade->profit_loss_percent >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $trade->profit_loss_percent >= 0 ? '+' : '' }}{{ number_format($trade->profit_loss_percent, 2) }}%
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $trades->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="las la-list la-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No trades found for this backtest.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($backtest->status === 'running')
        <div class="col-12">
            <div class="alert alert-info">
                <i class="las la-spinner la-spin"></i> 
                {{ __('Backtest is currently running. Please check back later.') }}
            </div>
        </div>
    @elseif($backtest->status === 'failed')
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="las la-exclamation-triangle"></i> 
                <strong>{{ __('Backtest Failed') }}</strong><br>
                {{ $backtest->error_message ?? __('Unknown error occurred') }}
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="las la-clock"></i> 
                {{ __('Backtest is pending execution.') }}
            </div>
        </div>
    @endif
</div>
@endsection

