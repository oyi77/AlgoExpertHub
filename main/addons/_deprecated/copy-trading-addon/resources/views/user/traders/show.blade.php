@extends(Config::theme() . 'layout.auth')

@push('style')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush

@section('content')
    <div class="container">
        <div class="row mb-3">
            <div class="col-12">
                <a href="{{ route('user.copy-trading.traders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Traders
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Trader Profile Card -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h4>{{ $trader->username ?? $trader->email ?? 'Trader #' . $trader->id }}</h4>
                        <p class="text-muted">Member since {{ $trader->created_at->format('M Y') }}</p>

                        @if(!$is_following)
                        <a href="{{ route('user.copy-trading.subscriptions.create', $trader->id) }}" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i> Follow Trader
                        </a>
                        @else
                        <button class="btn btn-secondary btn-block" disabled>
                            <i class="fas fa-check"></i> Already Following
                        </button>
                        @endif

                        <hr>

                        <div class="text-left">
                            <h6 class="font-weight-bold mb-3">Trading Settings</h6>
                            <div class="mb-2">
                                <small><strong>Min Balance:</strong> {{ $setting->min_followers_balance ?? 'None' }}</small>
                            </div>
                            <div class="mb-2">
                                <small><strong>Manual Trades:</strong> {{ $setting->allow_manual_trades ? 'Yes' : 'No' }}</small>
                            </div>
                            <div class="mb-2">
                                <small><strong>Auto Trades:</strong> {{ $setting->allow_auto_trades ? 'Yes' : 'No' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics & Performance -->
            <div class="col-md-8">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-primary shadow h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Followers</div>
                                <div class="h5 mb-0 font-weight-bold">{{ $stats['follower_count'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-left-success shadow h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Win Rate</div>
                                <div class="h5 mb-0 font-weight-bold">{{ number_format($stats['win_rate'] ?? 0, 2) }}%</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-left-info shadow h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total P&L</div>
                                <div class="h5 mb-0 font-weight-bold {{ ($stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ ($stats['total_pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($stats['total_pnl'] ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-left-warning shadow h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Trades</div>
                                <div class="h5 mb-0 font-weight-bold">{{ $stats['total_trades'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Chart Placeholder -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Placeholder chart - in production, this would use real data
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Cumulative P&L',
                data: [10, 25, 15, {{ $stats['total_pnl'] ?? 0 }}],
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
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
