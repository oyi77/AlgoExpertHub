@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Signal Provider Details' }}
@endsection

@section('content')
    <div class="container-fluid">
        @include('backend.layout.breadcrumb', ['title' => $title ?? 'Signal Provider Details'])

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Provider: {{ $metric->signal_provider_id }}</h4>
                    </div>
                    <div class="card-body">
                        <!-- Metrics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6>Performance Score</h6>
                                        <h3>{{ number_format($metric->performance_score, 2) }}</h3>
                                        <small>Trend: 
                                            @if($metric->score_trend == 'up')
                                                <i class="fas fa-arrow-up"></i> Up
                                            @elseif($metric->score_trend == 'down')
                                                <i class="fas fa-arrow-down"></i> Down
                                            @else
                                                <i class="fas fa-minus"></i> Stable
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6>Win Rate</h6>
                                        <h3>{{ number_format($metric->win_rate, 2) }}%</h3>
                                        <small>{{ $metric->winning_signals }} / {{ $metric->total_signals }} signals</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6>Avg Slippage</h6>
                                        <h3>{{ number_format($metric->avg_slippage, 4) }} pips</h3>
                                        <small>Max: {{ number_format($metric->max_slippage, 4) }} pips</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6>Max Drawdown</h6>
                                        <h3>{{ number_format($metric->max_drawdown, 2) }}%</h3>
                                        <small>Reward:Risk = {{ number_format($metric->reward_to_risk_ratio, 2) }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Score History Chart -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Performance Score History</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="performanceChart" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Details Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Provider Type</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $metric->signal_provider_type)) }}</td>
                                </tr>
                                <tr>
                                    <th>Period</th>
                                    <td>{{ $metric->period_start->format('Y-m-d') }} to {{ $metric->period_end->format('Y-m-d') }}</td>
                                </tr>
                                <tr>
                                    <th>Total Signals</th>
                                    <td>{{ $metric->total_signals }}</td>
                                </tr>
                                <tr>
                                    <th>Winning Signals</th>
                                    <td>{{ $metric->winning_signals }}</td>
                                </tr>
                                <tr>
                                    <th>Losing Signals</th>
                                    <td>{{ $metric->losing_signals }}</td>
                                </tr>
                                <tr>
                                    <th>SL Compliance Rate</th>
                                    <td>{{ number_format($metric->sl_compliance_rate, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Avg Latency</th>
                                    <td>{{ $metric->avg_latency_ms }} ms</td>
                                </tr>
                                <tr>
                                    <th>Calculated At</th>
                                    <td>{{ $metric->calculated_at ? $metric->calculated_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('performanceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($history->pluck('period_start')->map(fn($d) => $d->format('M d'))->toArray()) !!},
                    datasets: [{
                        label: 'Performance Score',
                        data: {!! json_encode($history->pluck('performance_score')->toArray()) !!},
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    </script>
@endsection

