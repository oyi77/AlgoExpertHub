@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-flask"></i> Trading Test & Backtesting</h3>
                <p class="text-muted mb-0">Test strategies on historical data and analyze performance</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Backtests</h6>
                        <h3>{{ $stats['total_backtests'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Completed</h6>
                        <h3 class="text-success">{{ $stats['completed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">Running</h6>
                        <h3 class="text-info">{{ $stats['running'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-create" data-toggle="tab">
                            <i class="fas fa-plus"></i> Create Backtest
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-reports" data-toggle="tab">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-results" data-toggle="tab">
                            <i class="fas fa-chart-bar"></i> Results
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Create Backtest Tab -->
                    <div class="tab-pane fade show active" id="tab-create">
                        <h5 class="mb-3"><i class="fas fa-plus"></i> Create New Backtest</h5>
                        <div class="text-center py-4">
                            <i class="fas fa-flask fa-3x text-primary mb-3"></i>
                            <p class="text-muted">Start a new backtest to test your strategies on historical data</p>
                            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i> Create New Backtest
                            </a>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div class="tab-pane fade" id="tab-reports">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Backtest Reports</h5>
                            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Backtest
                            </a>
                        </div>

                        @if($backtests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Symbol</th>
                                        <th>Timeframe</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backtests as $backtest)
                                    <tr>
                                        <td><strong>{{ $backtest->name }}</strong></td>
                                        <td>{{ $backtest->symbol }}</td>
                                        <td>{{ $backtest->timeframe }}</td>
                                        <td>{{ $backtest->start_date->format('Y-m-d') }} - {{ $backtest->end_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($backtest->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                            @elseif($backtest->status === 'running')
                                            <span class="badge badge-info">Running</span>
                                            @elseif($backtest->status === 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                            @else
                                            <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px; min-width: 80px;">
                                                <div class="progress-bar" style="width: {{ $backtest->progress_percent }}%">
                                                    {{ $backtest->progress_percent }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.test.backtests.show', $backtest) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $backtests->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No backtests found. <a href="{{ route('admin.trading-management.test.backtests.create') }}">Create your first backtest</a>.
                        </div>
                        @endif
                    </div>

                    <!-- Results Tab -->
                    <div class="tab-pane fade" id="tab-results">
                        <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Backtest Results</h5>

                        @if($results->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Backtest</th>
                                        <th>Entry Time</th>
                                        <th>Direction</th>
                                        <th>Entry</th>
                                        <th>Exit</th>
                                        <th>P&L</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $result)
                                    <tr>
                                        <td>{{ $result->backtest->name ?? 'N/A' }}</td>
                                        <td>{{ $result->entry_time->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $result->direction === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($result->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $result->entry_price }}</td>
                                        <td>{{ $result->exit_price }}</td>
                                        <td class="{{ $result->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($result->pnl, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $results->links() }}
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.test.results.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Results
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No results yet.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab (iframe or redirect) -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Performance Analytics</h5>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="text-muted">View detailed copy trading analytics and performance charts</p>
                            <a href="{{ route('admin.trading-management.copy-trading.analytics') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-external-link-alt"></i> Open Analytics Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
