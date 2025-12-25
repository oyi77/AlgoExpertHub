@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4><i class="fas fa-chart-line"></i> Bot Analysis - {{ $bot->name }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Bot
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Date Range Filter --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <form method="GET" action="{{ route('user.trading-management.trading-bots.analysis', $bot->id) }}" class="d-flex flex-wrap align-items-end" style="gap: 1rem;">
                    <div class="form-group mb-0">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Period</label>
                        <select name="period" class="form-control">
                            <option value="daily" {{ request('period') == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Metrics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Profit</h6>
                        <h3 class="mb-0">{{ number_format($metrics['total_profit'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Win Rate</h6>
                        <h3 class="mb-0">{{ number_format($metrics['win_rate'] ?? 0, 2) }}%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Trades</h6>
                        <h3 class="mb-0">{{ $metrics['total_trades'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Profit Factor</h6>
                        <h3 class="mb-0">{{ number_format($metrics['profit_factor'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Chart --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Performance Chart</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Metrics Table --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detailed Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Profit</td>
                                        <td>{{ number_format($metrics['total_profit'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Loss</td>
                                        <td>{{ number_format($metrics['total_loss'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Net Profit</td>
                                        <td>{{ number_format(($metrics['total_profit'] ?? 0) - ($metrics['total_loss'] ?? 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Win Rate</td>
                                        <td>{{ number_format($metrics['win_rate'] ?? 0, 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Profit Factor</td>
                                        <td>{{ number_format($metrics['profit_factor'] ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Sharpe Ratio</td>
                                        <td>{{ number_format($metrics['sharpe_ratio'] ?? 0, 4) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Max Drawdown</td>
                                        <td>{{ number_format($metrics['max_drawdown'] ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = @json($chart_data ?? []);
    
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'Cumulative Profit',
                data: chartData.data || [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
</script>
@endpush
@endsection

