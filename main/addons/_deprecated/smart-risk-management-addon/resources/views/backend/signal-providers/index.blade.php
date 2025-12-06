@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Signal Provider Metrics' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Signal Provider Metrics</h4>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-primary" onclick="exportToCSV()">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" action="{{ route('admin.srm.signal-providers.index') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Type</label>
                                    <select name="type" class="form-control">
                                        <option value="">All Types</option>
                                        <option value="channel_source" {{ request('type') == 'channel_source' ? 'selected' : '' }}>Channel Source</option>
                                        <option value="user" {{ request('type') == 'user' ? 'selected' : '' }}>User</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-3">
                                    <label>Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-3">
                                    <label>Performance Score</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" name="score_min" class="form-control" placeholder="Min" value="{{ request('score_min') }}">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="score_max" class="form-control" placeholder="Max" value="{{ request('score_max') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <input type="text" name="search" class="form-control" placeholder="Search by Provider ID..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('admin.srm.signal-providers.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5>Total Providers</h5>
                                        <h3>{{ $stats['total_providers'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5>Avg Performance Score</h5>
                                        <h3>{{ number_format($stats['avg_performance_score'] ?? 0, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5>Total Signals</h5>
                                        <h3>{{ number_format($stats['total_signals'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Provider ID</th>
                                        <th>Type</th>
                                        <th>Total Signals</th>
                                        <th>Win Rate</th>
                                        <th>Avg Slippage</th>
                                        <th>Performance Score</th>
                                        <th>Trend</th>
                                        <th>Period</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($metrics as $metric)
                                        <tr>
                                            <td>{{ $metric->signal_provider_id }}</td>
                                            <td>
                                                <span class="badge badge-{{ $metric->signal_provider_type == 'channel_source' ? 'info' : 'primary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $metric->signal_provider_type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $metric->total_signals }}</td>
                                            <td>{{ number_format($metric->win_rate, 2) }}%</td>
                                            <td>{{ number_format($metric->avg_slippage, 4) }} pips</td>
                                            <td>
                                                <span class="badge badge-{{ $metric->performance_score >= 70 ? 'success' : ($metric->performance_score >= 50 ? 'warning' : 'danger') }}">
                                                    {{ number_format($metric->performance_score, 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($metric->score_trend == 'up')
                                                    <i class="fas fa-arrow-up text-success"></i>
                                                @elseif($metric->score_trend == 'down')
                                                    <i class="fas fa-arrow-down text-danger"></i>
                                                @else
                                                    <i class="fas fa-minus text-secondary"></i>
                                                @endif
                                            </td>
                                            <td>{{ $metric->period_start->format('M d') }} - {{ $metric->period_end->format('M d') }}</td>
                                            <td>
                                                <a href="{{ route('admin.srm.signal-providers.show', $metric->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No metrics found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $metrics->links() }}
                        </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportToCSV() {
            // TODO: Implement CSV export
            alert('CSV export feature coming soon');
        }
    </script>
@endsection

