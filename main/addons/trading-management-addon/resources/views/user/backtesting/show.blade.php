@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <!-- Header -->
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <h4>{{ $backtest->name }}</h4>
                            <p class="text-muted mb-0">
                                {{ $backtest->symbol }} • {{ strtoupper($backtest->timeframe) }} • 
                                {{ $backtest->start_date->format('Y-m-d') }} to {{ $backtest->end_date->format('Y-m-d') }}
                            </p>
                        </div>
                        <div>
                            @if($backtest->status === 'pending')
                                <button class="btn btn-sm btn-success" id="runBacktestBtn">
                                    <i class="las la-play"></i> {{ __('Run Backtest') }}
                                </button>
                            @endif
                            @if($backtest->status === 'completed')
                                <button class="btn btn-sm btn-info" onclick="window.print()">
                                    <i class="las la-print"></i> {{ __('Export PDF') }}
                                </button>
                            @endif
                            <button class="btn btn-sm btn-danger" id="deleteBacktestBtn">
                                <i class="las la-trash"></i> {{ __('Delete') }}
                            </button>
                            <a href="{{ route('user.backtesting.index') }}" class="btn btn-sm btn-secondary">
                                <i class="las la-arrow-left"></i> {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Progress -->
        @if($backtest->status === 'running')
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-body">
                        <h5><i class="las la-spinner la-spin"></i> {{ __('Backtest Running') }}</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 id="progressBar"
                                 style="width: {{ $backtest->progress_percent }}%">
                                {{ $backtest->progress_percent }}%
                            </div>
                        </div>
                        <p class="text-muted mt-2">{{ __('This may take a few minutes. The page will auto-refresh when complete.') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($backtest->status === 'failed')
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="las la-exclamation-triangle"></i> 
                    <strong>{{ __('Backtest Failed') }}</strong><br>
                    {{ $backtest->error_message }}
                </div>
            </div>
        @endif

        @if($backtest->status === 'completed' && $backtest->result)
            <!-- Summary Cards -->
            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">{{ __('Net Profit') }}</h6>
                        <h3 class="{{ $backtest->result->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($backtest->result->net_profit, 2) }}
                        </h3>
                        <p class="mb-0">
                            <span class="badge {{ $backtest->result->return_percent >= 0 ? 'bg-success' : 'bg-danger' }}">
                                {{ $backtest->result->return_percent > 0 ? '+' : '' }}{{ number_format($backtest->result->return_percent, 2) }}%
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">{{ __('Win Rate') }}</h6>
                        <h3 class="{{ $backtest->result->win_rate >= 50 ? 'text-success' : 'text-warning' }}">
                            {{ number_format($backtest->result->win_rate, 2) }}%
                        </h3>
                        <p class="mb-0 text-muted">
                            {{ $backtest->result->winning_trades }} / {{ $backtest->result->total_trades }} {{ __('trades') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">{{ __('Profit Factor') }}</h6>
                        <h3 class="{{ $backtest->result->profit_factor >= 1.5 ? 'text-success' : 'text-warning' }}">
                            {{ number_format($backtest->result->profit_factor, 2) }}
                        </h3>
                        <p class="mb-0 text-muted">{{ __('Risk/Reward Ratio') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">{{ __('Max Drawdown') }}</h6>
                        <h3 class="text-danger">
                            {{ number_format($backtest->result->max_drawdown_percent, 2) }}%
                        </h3>
                        <p class="mb-0 text-muted">${{ number_format($backtest->result->max_drawdown, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Performance Grade -->
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h5>{{ __('Performance Grade') }}</h5>
                        <h2 class="mb-0">
                            @php
                                $grade = substr($backtest->result->grade, 0, 1);
                                $gradeClass = match($grade) {
                                    'A' => 'text-success',
                                    'B' => 'text-info',
                                    'C' => 'text-warning',
                                    'D', 'F' => 'text-danger',
                                    default => 'text-muted'
                                };
                            @endphp
                            <span class="{{ $gradeClass }}">{{ $backtest->result->grade }}</span>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Equity Curve Chart -->
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-header">
                        <h5>{{ __('Equity Curve') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="equityChart" style="height: 400px;"></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Metrics -->
            <div class="col-md-6">
                <div class="sp_site_card">
                    <div class="card-header">
                        <h5>{{ __('Trade Statistics') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>{{ __('Total Trades') }}</td>
                                <td class="text-end"><strong>{{ $backtest->result->total_trades }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('Winning Trades') }}</td>
                                <td class="text-end text-success"><strong>{{ $backtest->result->winning_trades }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('Losing Trades') }}</td>
                                <td class="text-end text-danger"><strong>{{ $backtest->result->losing_trades }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('Average Win') }}</td>
                                <td class="text-end text-success">${{ number_format($backtest->result->avg_win, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Average Loss') }}</td>
                                <td class="text-end text-danger">${{ number_format($backtest->result->avg_loss, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Largest Win') }}</td>
                                <td class="text-end text-success">${{ number_format($backtest->result->largest_win, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Largest Loss') }}</td>
                                <td class="text-end text-danger">${{ number_format($backtest->result->largest_loss, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Consecutive Wins') }}</td>
                                <td class="text-end">{{ $backtest->result->consecutive_wins }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Consecutive Losses') }}</td>
                                <td class="text-end">{{ $backtest->result->consecutive_losses }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="sp_site_card">
                    <div class="card-header">
                        <h5>{{ __('Performance Metrics') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>{{ __('Initial Balance') }}</td>
                                <td class="text-end">${{ number_format($backtest->initial_balance, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Final Balance') }}</td>
                                <td class="text-end"><strong>${{ number_format($backtest->result->final_balance, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('Total Profit') }}</td>
                                <td class="text-end text-success">${{ number_format($backtest->result->total_profit, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Total Loss') }}</td>
                                <td class="text-end text-danger">${{ number_format($backtest->result->total_loss, 2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Net Profit') }}</td>
                                <td class="text-end {{ $backtest->result->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($backtest->result->net_profit, 2) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>{{ __('Return %') }}</td>
                                <td class="text-end {{ $backtest->result->return_percent >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $backtest->result->return_percent > 0 ? '+' : '' }}{{ number_format($backtest->result->return_percent, 2) }}%
                                </td>
                            </tr>
                            <tr>
                                <td>{{ __('Profit Factor') }}</td>
                                <td class="text-end">{{ number_format($backtest->result->profit_factor, 2) }}</td>
                            </tr>
                            @if($backtest->result->sharpe_ratio)
                            <tr>
                                <td>{{ __('Sharpe Ratio') }}</td>
                                <td class="text-end">{{ number_format($backtest->result->sharpe_ratio, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td>{{ __('Max Drawdown') }}</td>
                                <td class="text-end text-danger">{{ number_format($backtest->result->max_drawdown_percent, 2) }}%</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Trade List -->
            @if($backtest->result->trade_details && count($backtest->result->trade_details) > 0)
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-header">
                        <h5>{{ __('Trade History') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Entry Time') }}</th>
                                        <th>{{ __('Exit Time') }}</th>
                                        <th>{{ __('Direction') }}</th>
                                        <th>{{ __('Entry Price') }}</th>
                                        <th>{{ __('Exit Price') }}</th>
                                        <th>{{ __('P&L') }}</th>
                                        <th>{{ __('Duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($backtest->result->trade_details, 0, 50) as $index => $trade)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($trade['entry_time'])->format('Y-m-d H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($trade['exit_time'])->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $trade['direction'] === 'BUY' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $trade['direction'] }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($trade['entry_price'], 4) }}</td>
                                        <td>{{ number_format($trade['exit_price'], 4) }}</td>
                                        <td class="{{ $trade['pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>${{ number_format($trade['pnl'], 2) }}</strong>
                                        </td>
                                        <td>{{ $trade['duration'] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($backtest->result->trade_details) > 50)
                            <p class="text-muted text-center mt-2">
                                {{ __('Showing first 50 of') }} {{ count($backtest->result->trade_details) }} {{ __('trades') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        @endif

        @if($backtest->status === 'pending')
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-body text-center py-5">
                        <i class="las la-clock" style="font-size: 4rem; color: var(--tv-primary);"></i>
                        <h5 class="mt-3">{{ __('Backtest Ready to Run') }}</h5>
                        <p class="text-muted">{{ __('Click "Run Backtest" to start testing your strategy') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
$(document).ready(function() {
    // Run backtest
    $('#runBacktestBtn').on('click', function() {
        if (!confirm('{{ __("Start running this backtest?") }}')) return;
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> {{ __("Starting...") }}');
        
        $.ajax({
            url: '{{ route("user.backtesting.run", $backtest->id) }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __("Failed to start") }}');
                btn.prop('disabled', false).html('<i class="las la-play"></i> {{ __("Run Backtest") }}');
            }
        });
    });
    
    // Delete backtest
    $('#deleteBacktestBtn').on('click', function() {
        if (!confirm('{{ __("Delete this backtest? This cannot be undone.") }}')) return;
        
        $.ajax({
            url: '{{ route("user.backtesting.destroy", $backtest->id) }}',
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => window.location.href = response.redirect, 1000);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __("Failed to delete") }}');
            }
        });
    });
    
    @if($backtest->status === 'running')
    // Poll for progress
    setInterval(function() {
        $.get('{{ route("user.backtesting.status", $backtest->id) }}', function(response) {
            if (response.is_completed) {
                location.reload();
            } else if (response.is_running) {
                $('#progressBar').css('width', response.progress + '%').text(response.progress + '%');
            }
        });
    }, 2000);
    @endif
    
    @if($backtest->status === 'completed' && $backtest->result && $backtest->result->equity_curve)
    // Render equity curve chart
    const equityData = @json($backtest->result->equity_curve);
    const chart = echarts.init(document.getElementById('equityChart'));
    
    chart.setOption({
        tooltip: {
            trigger: 'axis',
            formatter: function(params) {
                return 'Balance: $' + params[0].value.toFixed(2);
            }
        },
        xAxis: {
            type: 'category',
            data: equityData.map((_, i) => i),
            name: 'Trade #'
        },
        yAxis: {
            type: 'value',
            name: 'Balance ($)',
            axisLabel: {
                formatter: '${value}'
            }
        },
        series: [{
            data: equityData,
            type: 'line',
            smooth: true,
            itemStyle: {
                color: '#1AFFD5'
            },
            areaStyle: {
                color: {
                    type: 'linear',
                    x: 0, y: 0, x2: 0, y2: 1,
                    colorStops: [
                        { offset: 0, color: 'rgba(26, 255, 213, 0.3)' },
                        { offset: 1, color: 'rgba(26, 255, 213, 0.05)' }
                    ]
                }
            }
        }]
    });
    
    // Responsive
    window.addEventListener('resize', () => chart.resize());
    @endif
});
</script>
@endpush
@endsection
