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
                        <a href="{{ route('admin.trading-management.copy-trading.analytics') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metrics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Subscriptions</h6>
                        <h3>{{ $metrics['total_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active Subscriptions</h6>
                        <h3 class="text-success">{{ $metrics['active_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Executions</h6>
                        <h3>{{ $metrics['total_executions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Success Rate</h6>
                        <h3>{{ number_format($metrics['success_rate'], 2) }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Executions Chart -->
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Daily Execution Volume</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyExecutionsChart" height="80"></canvas>
            </div>
        </div>

        <!-- Top Traders -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-trophy"></i> Top Traders by Followers</h5>
            </div>
            <div class="card-body">
                @if($topTraders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Trader</th>
                                <th>Followers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topTraders as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->trader->username ?? 'N/A' }}</td>
                                <td>
                                    <strong>{{ $item->follower_count }}</strong>
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
    var ctx = document.getElementById('dailyExecutionsChart').getContext('2d');
    var dailyData = @json($dailyExecutions);
    
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Executions',
                data: dailyData.map(d => d.count),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
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

