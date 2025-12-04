@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Date Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4">
                        <label>From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-4">
                        <label>To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('admin.trading-management.operations.analytics') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Trades</h6>
                        <h3>{{ $metrics['total_trades'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Win Rate</h6>
                        <h3 class="text-success">{{ number_format($metrics['win_rate'], 2) }}%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total P&L</h6>
                        <h3 class="{{ $metrics['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($metrics['total_pnl'], 2) }}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Profit Factor</h6>
                        <h3>{{ number_format($metrics['profit_factor'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Winning Trades</h6>
                        <h3 class="text-success">{{ $metrics['winning_trades'] }}</h3>
                        <small>Avg: ${{ number_format($metrics['avg_win'], 2) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Losing Trades</h6>
                        <h3 class="text-danger">{{ $metrics['losing_trades'] }}</h3>
                        <small>Avg: ${{ number_format($metrics['avg_loss'], 2) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Risk/Reward Ratio</h6>
                        <h3>{{ abs($metrics['avg_loss']) > 0 ? number_format(abs($metrics['avg_win'] / $metrics['avg_loss']), 2) : 'N/A' }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily P&L Chart -->
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Daily P&L</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyPnlChart" height="80"></canvas>
            </div>
        </div>

        <!-- Top Performing Connections -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-trophy"></i> Top Performing Connections</h5>
            </div>
            <div class="card-body">
                @if($topConnections->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Connection</th>
                                <th>Total Trades</th>
                                <th>Total P&L</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topConnections as $index => $conn)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $conn->connection->name ?? 'N/A' }}</td>
                                <td>{{ $conn->total_trades }}</td>
                                <td class="{{ $conn->total_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($conn->total_pnl, 2) }}</strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">No data available for this period.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    var ctx = document.getElementById('dailyPnlChart').getContext('2d');
    var dailyData = @json($dailyPnl);
    
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Daily P&L',
                data: dailyData.map(d => d.pnl),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
@endsection

