@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-users"></i> Copy Trading</h3>
                <p class="text-muted mb-0">Social trading - manage traders, followers, and copy trading subscriptions</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Subscriptions</h6>
                        <h3>{{ $stats['total_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active Subscriptions</h6>
                        <h3 class="text-success">{{ $stats['active_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Traders</h6>
                        <h3>{{ $stats['total_traders'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Followers</h6>
                        <h3>{{ $stats['total_followers'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Executions</h6>
                        <h3>{{ $stats['total_executions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Executions Today</h6>
                        <h3>{{ $stats['executions_today'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-traders" data-toggle="tab">
                            <i class="fas fa-user-tie"></i> Traders List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-subscriptions" data-toggle="tab">
                            <i class="fas fa-link"></i> Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-analytics" data-toggle="tab">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Traders Tab -->
                    <div class="tab-pane fade show active" id="tab-traders">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Traders List</h5>
                            <a href="{{ route('admin.trading-management.copy-trading.traders') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Traders
                            </a>
                        </div>
                        <p class="text-muted">View and manage traders ranked by follower count and performance.</p>
                    </div>

                    <!-- Subscriptions Tab -->
                    <div class="tab-pane fade" id="tab-subscriptions">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Subscriptions</h5>
                            <a href="{{ route('admin.trading-management.copy-trading.subscriptions') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage Subscriptions
                            </a>
                        </div>
                        <p class="text-muted">Manage copy trading subscriptions between traders and followers.</p>
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Analytics</h5>
                            <a href="{{ route('admin.trading-management.copy-trading.analytics') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Analytics
                            </a>
                        </div>
                        <p class="text-muted">Performance metrics, charts, and top traders analysis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
