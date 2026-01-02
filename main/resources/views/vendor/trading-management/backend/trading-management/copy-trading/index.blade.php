@extends('backend.layout.master')

@section('element')
<div class="row">
    <!-- Statistics Overview -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Subscriptions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_subscriptions'] }}</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-link fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Subscriptions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_subscriptions'] }}</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Traders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_traders'] }}</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-user-tie fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 font-weight-bold">
                            <i class="fas fa-users text-primary"></i> Copy Trading
                        </h5>
                        <small class="text-muted">Social trading - manage traders, followers, and copy trading subscriptions</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs border-bottom" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-traders" data-toggle="tab">
                            <i class="fas fa-user-tie"></i> Traders List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.trading-management.marketplace.traders.index') }}">
                            <i class="fas fa-store"></i> Trader Marketplace
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

                <div class="tab-content p-4">
                    <!-- Traders Tab -->
                    <div class="tab-pane fade show active" id="tab-traders">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-user-tie text-primary"></i> Traders (Ranked by Followers)</h5>

                        @if($traders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Trader</th>
                                        <th>Email</th>
                                        <th>Followers</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($traders as $index => $item)
                                    <tr>
                                        <td>{{ $traders->firstItem() + $index }}</td>
                                        <td><strong>{{ $item->trader->username ?? 'N/A' }}</strong></td>
                                        <td>{{ $item->trader->email ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-primary badge-lg">{{ $item->follower_count }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.copy-trading.traders') }}?trader_id={{ $item->trader_id }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $traders->links() }}
                        </div>
                        @else
                        <div class="alert alert-info border">No traders found.</div>
                        @endif
                    </div>

                    <!-- Subscriptions Tab -->
                    <div class="tab-pane fade" id="tab-subscriptions">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-link text-primary"></i> Copy Trading Subscriptions</h5>

                        @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Trader</th>
                                        <th>Follower</th>
                                        <th>Copy Mode</th>
                                        <th>Risk Multiplier</th>
                                        <th>Status</th>
                                        <th>Subscribed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $sub)
                                    <tr>
                                        <td><strong>{{ $sub->trader->username ?? 'N/A' }}</strong></td>
                                        <td>{{ $sub->follower->username ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ strtoupper($sub->copy_mode) }}</span>
                                        </td>
                                        <td>{{ $sub->risk_multiplier }}x</td>
                                        <td>
                                            @if($sub->is_active)
                                            <span class="badge badge-success">Active</span>
                                            @else
                                            <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $sub->subscribed_at->format('Y-m-d') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $subscriptions->links() }}
                            <a href="{{ route('admin.trading-management.copy-trading.subscriptions') }}" class="btn btn-primary ml-2">
                                <i class="fas fa-external-link-alt"></i> View All Subscriptions
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info border">No subscriptions found.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-chart-line text-primary"></i> Copy Trading Analytics</h5>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                            <p class="text-muted mb-4">Full analytics dashboard with detailed metrics and charts</p>
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

@push('style')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
</style>
@endpush
@endsection
