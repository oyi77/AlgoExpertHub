@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('Signal Analytics & Reporting') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.signal-analytics.report') }}" class="btn btn-sm btn-info">
                            <i class="fa fa-file-alt"></i> {{ __('View Reports') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.signal-analytics.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label>{{ __('Channel') }}</label>
                                <select name="channel_source_id" class="form-control">
                                    <option value="">{{ __('All Channels') }}</option>
                                    @foreach ($channels as $channel)
                                        <option value="{{ $channel->id }}" {{ $channelSourceId == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>{{ __('Plan') }}</label>
                                <select name="plan_id" class="form-control">
                                    <option value="">{{ __('All Plans') }}</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ $planId == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>{{ __('Start Date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label>{{ __('End Date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-filter"></i> {{ __('Filter') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @if ($analytics)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5>{{ __('Total Signals') }}</h5>
                                        <h2>{{ number_format($analytics['total_signals']) }}</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5>{{ __('Published') }}</h5>
                                        <h2>{{ number_format($analytics['published_signals']) }}</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5>{{ __('Win Rate') }}</h5>
                                        <h2>{{ number_format($analytics['win_rate'], 2) }}%</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-{{ $analytics['total_profit_loss'] >= 0 ? 'success' : 'danger' }} text-white">
                                    <div class="card-body">
                                        <h5>{{ __('Total P/L') }}</h5>
                                        <h2>{{ number_format($analytics['total_profit_loss'], 2) }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>{{ __('Trade Statistics') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table">
                                            <tr>
                                                <td><strong>{{ __('Closed Trades') }}</strong></td>
                                                <td>{{ number_format($analytics['closed_trades']) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Profitable Trades') }}</strong></td>
                                                <td class="text-success">{{ number_format($analytics['profitable_trades']) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Loss Trades') }}</strong></td>
                                                <td class="text-danger">{{ number_format($analytics['loss_trades']) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Average P/L') }}</strong></td>
                                                <td>{{ number_format($analytics['avg_profit_loss'], 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{ __('Average Pips') }}</strong></td>
                                                <td>{{ number_format($analytics['avg_pips'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>{{ __('Daily Statistics') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="dailyChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            {{ __('Select a channel or plan to view analytics.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($analytics && !empty($dailyStats))
        @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('dailyChart').getContext('2d');
            const dailyData = @json($dailyStats);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date),
                    datasets: [{
                        label: '{{ __('Total Signals') }}',
                        data: dailyData.map(d => d.total_signals),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }, {
                        label: '{{ __('Published Signals') }}',
                        data: dailyData.map(d => d.published_signals),
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        </script>
        @endpush
    @endif
@endsection

