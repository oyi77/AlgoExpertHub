@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@push('style')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush

@section('element')
    <div class="container-fluid">
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        <!-- Execution Chart -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Execution Trends (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="executionChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Traders -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Traders by Followers</h6>
                    </div>
                    <div class="card-body">
                        @if(!empty($topTraders) && count($topTraders) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Trader</th>
                                        <th>Type</th>
                                        <th>Followers</th>
                                        <th>Win Rate</th>
                                        <th>Total P&L</th>
                                        <th>Total Trades</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topTraders as $index => $trader)
                                    <tr>
                                        <td>
                                            @if($index === 0)
                                                <i class="fas fa-trophy text-warning"></i> {{ $index + 1 }}
                                            @elseif($index === 1)
                                                <i class="fas fa-medal text-secondary"></i> {{ $index + 1 }}
                                            @elseif($index === 2)
                                                <i class="fas fa-medal text-danger"></i> {{ $index + 1 }}
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ $trader->trader_name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $trader->trader_type === 'admin' ? 'danger' : 'primary' }}">
                                                {{ ucfirst($trader->trader_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $trader->follower_count }}</td>
                                        <td>
                                            <span class="badge badge-{{ $trader->win_rate >= 50 ? 'success' : 'warning' }}">
                                                {{ number_format($trader->win_rate, 2) }}%
                                            </span>
                                        </td>
                                        <td class="{{ $trader->total_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $trader->total_pnl >= 0 ? '+' : '' }}{{ number_format($trader->total_pnl, 2) }}
                                        </td>
                                        <td>{{ $trader->total_trades }}</td>
                                        <td>
                                            <a href="{{ route('admin.copy-trading.traders.show', $trader->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No traders found yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($chartData) && !empty($chartData['labels']))
    const ctx = document.getElementById('executionChart').getContext('2d');
    const chartData = @json($chartData);
    
    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
    @else
    document.getElementById('executionChart').insertAdjacentHTML('beforebegin', 
        '<div class="alert alert-info">No execution data available yet.</div>');
    document.getElementById('executionChart').remove();
    @endif
});
</script>
@endpush

