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
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Subscriptions</h6>
                        <h3>{{ $stats['total_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active Subscriptions</h6>
                        <h3 class="text-success">{{ $stats['active_subscriptions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Traders</h6>
                        <h3>{{ $stats['total_traders'] }}</h3>
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
                        <h5 class="mb-3"><i class="fas fa-user-tie"></i> Traders (Ranked by Followers)</h5>

                        @if($traders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
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
                                        <td><span class="badge badge-primary badge-lg">{{ $item->follower_count }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.copy-trading.traders') }}?trader_id={{ $item->trader_id }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $traders->links() }}
                        @else
                        <div class="alert alert-info">No traders found.</div>
                        @endif
                    </div>

                    <!-- Subscriptions Tab -->
                    <div class="tab-pane fade" id="tab-subscriptions">
                        <h5 class="mb-3"><i class="fas fa-link"></i> Copy Trading Subscriptions</h5>

                        @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
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
                                        <td><span class="badge badge-info">{{ strtoupper($sub->copy_mode) }}</span></td>
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
                        {{ $subscriptions->links() }}
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.copy-trading.subscriptions') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Subscriptions
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No subscriptions found.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Copy Trading Analytics</h5>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Full analytics dashboard</p>
                            <a href="{{ route('admin.trading-management.copy-trading.analytics') }}" class="btn btn-primary">
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
