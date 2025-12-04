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
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Backtests</h6>
                        <h3>{{ $stats['total_backtests'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Completed</h6>
                        <h3 class="text-success">{{ $stats['completed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">Running</h6>
                        <h3 class="text-info">{{ $stats['running'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Failed</h6>
                        <h3 class="text-danger">{{ $stats['failed'] }}</h3>
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
                        <a class="nav-link" href="#tab-results" data-toggle="tab">
                            <i class="fas fa-chart-bar"></i> Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-reports" data-toggle="tab">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Create Backtest Tab -->
                    <div class="tab-pane fade show active" id="tab-create">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Create Backtest</h5>
                            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> New Backtest
                            </a>
                        </div>
                        <p class="text-muted">Run strategy backtests on historical market data to validate performance.</p>
                    </div>

                    <!-- Results Tab -->
                    <div class="tab-pane fade" id="tab-results">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Results</h5>
                            <a href="{{ route('admin.trading-management.test.results.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Results
                            </a>
                        </div>
                        <p class="text-muted">Detailed trade-by-trade results from completed backtests.</p>
                    </div>

                    <!-- Reports Tab -->
                    <div class="tab-pane fade" id="tab-reports">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Reports</h5>
                            <a href="{{ route('admin.trading-management.test.backtests.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Reports
                            </a>
                        </div>
                        <p class="text-muted">Summary reports of all backtests with performance metrics and comparisons.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
