@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('Connection Analytics') }}: {{ $connection->name }}</h4>
                    </div>
                    <div class="card-header-right">
                        <form action="" method="get" class="form-inline">
                            <select name="days" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 Days') }}</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 Days') }}</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>{{ __('Last 90 Days') }}</option>
                            </select>
                        </form>
                        <a href="{{ route('admin.ai-connections.usage-analytics.index') }}" class="btn btn-sm btn-primary ml-2">
                            <i class="fa fa-arrow-left"></i> {{ __('Back to Analytics') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Connection Info -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>{{ __('Provider:') }}</strong> {{ $connection->provider->name }}<br>
                                <strong>{{ __('Status:') }}</strong> 
                                <span class="badge badge-{{ $connection->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($connection->status) }}
                                </span><br>
                                <strong>{{ __('Priority:') }}</strong> {{ $connection->priority }}<br>
                                @if ($connection->rate_limit_per_minute)
                                    <strong>{{ __('Rate Limit:') }}</strong> {{ $connection->rate_limit_per_minute }}/min
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total Requests') }}</h5>
                                    <h3>{{ number_format($stats['total_requests'] ?? 0) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>{{ __('Success Rate') }}</h5>
                                    <h3>{{ number_format($stats['success_rate'] ?? 0, 1) }}%</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total Cost') }}</h5>
                                    <h3>${{ number_format($stats['total_cost'] ?? 0, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>{{ __('Avg Response') }}</h5>
                                    <h3>{{ number_format($stats['avg_response_time'] ?? 0) }}ms</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Usage Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>{{ __('Daily Usage') }}</h5>
                            <div id="dailyUsageChart"></div>
                        </div>
                    </div>

                    <!-- Recent Usage -->
                    <div class="row">
                        <div class="col-12">
                            <h5>{{ __('Recent Usage') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Time') }}</th>
                                            <th>{{ __('Feature') }}</th>
                                            <th>{{ __('Tokens') }}</th>
                                            <th>{{ __('Cost') }}</th>
                                            <th>{{ __('Response Time') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentUsage as $usage)
                                            <tr>
                                                <td>{{ $usage->created_at->format('Y-m-d H:i:s') }}</td>
                                                <td>{{ $usage->feature }}</td>
                                                <td>{{ number_format($usage->tokens_used ?? 0) }}</td>
                                                <td>${{ number_format($usage->cost ?? 0, 4) }}</td>
                                                <td>{{ $usage->response_time_ms ?? 'N/A' }}ms</td>
                                                <td>
                                                    @if ($usage->success)
                                                        <span class="badge badge-success">{{ __('Success') }}</span>
                                                    @else
                                                        <span class="badge badge-danger" title="{{ $usage->error_message }}">{{ __('Failed') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">{{ __('No usage data') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($recentUsage->hasPages())
                                <div class="mt-3">
                                    {{ $recentUsage->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('external-style')
<link href="{{ Config::cssLib('backend', 'apexcharts.css') }}" rel="stylesheet">
@endpush

@push('script')
<script src="{{ asset('asset/backend/js/apexcharts.min.js') }}"></script>
<script>
    $(function() {
        'use strict';

        // Daily usage chart
        var dailyData = @json($dailyUsage);
        var dates = dailyData.map(d => d.date);
        var counts = dailyData.map(d => d.count);
        var costs = dailyData.map(d => parseFloat(d.cost) || 0);
        var tokens = dailyData.map(d => parseInt(d.tokens) || 0);

        var options = {
            series: [{
                name: '{{ __('Requests') }}',
                data: counts
            }, {
                name: '{{ __('Cost ($)') }}',
                data: costs
            }, {
                name: '{{ __('Tokens (K)') }}',
                data: tokens.map(t => t / 1000)
            }],
            chart: {
                height: 350,
                type: 'line',
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            xaxis: {
                categories: dates
            },
            yaxis: [{
                title: {
                    text: '{{ __('Requests') }}'
                }
            }, {
                opposite: true,
                title: {
                    text: '{{ __('Cost / Tokens') }}'
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#dailyUsageChart"), options);
        chart.render();
    });
</script>
@endpush

