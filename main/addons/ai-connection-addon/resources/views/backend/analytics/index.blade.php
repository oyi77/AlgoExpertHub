@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('AI Usage Analytics') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <form action="" method="get" class="form-inline">
                            <select name="days" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 Days') }}</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 Days') }}</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>{{ __('Last 90 Days') }}</option>
                            </select>
                        </form>
                        <a href="{{ route('admin.ai-connections.usage-analytics.export', ['days' => $days]) }}" class="btn btn-sm btn-primary ml-2">
                            <i class="fa fa-download"></i> {{ __('Export CSV') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total Cost') }}</h5>
                                    <h3>${{ number_format($totalCost, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total Tokens') }}</h5>
                                    <h3>{{ number_format($totalTokens) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>{{ __('Avg Response') }}</h5>
                                    <h3>{{ number_format($avgResponseTime) }}ms</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>{{ __('By Feature') }}</h5>
                                    <h3>{{ count($usageByFeature) }} {{ __('features') }}</h3>
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

                    <!-- Top Connections -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>{{ __('Top Connections by Usage') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Connection') }}</th>
                                            <th>{{ __('Provider') }}</th>
                                            <th>{{ __('Usage Count') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topConnections as $connection)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.ai-connections.usage-analytics.connection', ['connection' => $connection->id, 'days' => $days]) }}">
                                                        {{ $connection->name }}
                                                    </a>
                                                </td>
                                                <td>{{ $connection->provider->name }}</td>
                                                <td><span class="badge badge-info">{{ $connection->usage_logs_count }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">{{ __('No usage data') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Usage by Feature -->
                        <div class="col-md-6">
                            <h5>{{ __('Usage by Feature') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Feature') }}</th>
                                            <th>{{ __('Count') }}</th>
                                            <th>{{ __('Cost') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($usageByFeature as $feature => $data)
                                            <tr>
                                                <td>{{ $feature }}</td>
                                                <td><span class="badge badge-info">{{ $data['count'] }}</span></td>
                                                <td>${{ number_format($data['cost'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">{{ __('No usage data') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Errors -->
                    <div class="row">
                        <div class="col-12">
                            <h5>{{ __('Recent Errors') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Time') }}</th>
                                            <th>{{ __('Connection') }}</th>
                                            <th>{{ __('Feature') }}</th>
                                            <th>{{ __('Error') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentErrors as $error)
                                            <tr>
                                                <td>{{ $error->created_at->diffForHumans() }}</td>
                                                <td>{{ $error->connection->name ?? 'N/A' }}</td>
                                                <td>{{ $error->feature }}</td>
                                                <td><small class="text-danger">{{ Str::limit($error->error_message, 100) }}</small></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">{{ __('No errors') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


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

        var options = {
            series: [{
                name: '{{ __('Requests') }}',
                data: counts
            }, {
                name: '{{ __('Cost ($)') }}',
                data: costs
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
                    text: '{{ __('Cost ($)') }}'
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#dailyUsageChart"), options);
        chart.render();
    });
</script>
@endpush

