@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'A/B Test Results' }}
@endsection

@section('content')
    <div class="container-fluid">
        @include('backend.layout.breadcrumb', ['title' => $title ?? 'A/B Test Results'])

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $test->name }} - Results</h4>
                    </div>
                    <div class="card-body">
                        <!-- Comparison Cards -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5>Pilot Group</h5>
                                        <p>Avg P/L: ${{ number_format($results['pilot']['avg_pnl'] ?? 0, 2) }}</p>
                                        <p>Avg Drawdown: {{ number_format($results['pilot']['avg_drawdown'] ?? 0, 2) }}%</p>
                                        <p>Win Rate: {{ number_format($results['pilot']['win_rate'] ?? 0, 2) }}%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body">
                                        <h5>Control Group</h5>
                                        <p>Avg P/L: ${{ number_format($results['control']['avg_pnl'] ?? 0, 2) }}</p>
                                        <p>Avg Drawdown: {{ number_format($results['control']['avg_drawdown'] ?? 0, 2) }}%</p>
                                        <p>Win Rate: {{ number_format($results['control']['win_rate'] ?? 0, 2) }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistical Significance -->
                        <div class="alert alert-{{ $test->is_significant ? 'success' : 'warning' }}">
                            <h5>Statistical Significance</h5>
                            <p><strong>P-Value:</strong> {{ number_format($p_value, 6) }}</p>
                            <p><strong>Significant:</strong> 
                                <span class="badge badge-{{ $test->is_significant ? 'success' : 'warning' }}">
                                    {{ $test->is_significant ? 'Yes' : 'No' }}
                                </span>
                            </p>
                            <p><small>P-value < 0.05 indicates statistical significance</small></p>
                        </div>

                        <!-- Decision -->
                        @if($test->decision)
                            <div class="alert alert-info">
                                <h5>Decision</h5>
                                <p><strong>{{ ucfirst($test->decision) }}</strong></p>
                                @if($test->decision_notes)
                                    <p>{{ $test->decision_notes }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Comparison Chart -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Performance Comparison</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="comparisonChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('comparisonChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Avg P/L', 'Avg Drawdown', 'Win Rate'],
                    datasets: [{
                        label: 'Pilot Group',
                        data: [
                            {{ $results['pilot']['avg_pnl'] ?? 0 }},
                            {{ $results['pilot']['avg_drawdown'] ?? 0 }},
                            {{ $results['pilot']['win_rate'] ?? 0 }}
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    }, {
                        label: 'Control Group',
                        data: [
                            {{ $results['control']['avg_pnl'] ?? 0 }},
                            {{ $results['control']['avg_drawdown'] ?? 0 }},
                            {{ $results['control']['win_rate'] ?? 0 }}
                        ],
                        backgroundColor: 'rgba(201, 203, 207, 0.5)',
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
@endsection

