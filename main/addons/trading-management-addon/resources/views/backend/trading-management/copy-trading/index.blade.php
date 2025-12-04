@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>👥 Copy Trading</h4>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('tab') || request()->get('tab') === 'traders' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.copy-trading.index') }}?tab=traders">
                    <i class="fas fa-users"></i> Traders List
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'subscriptions' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.copy-trading.index') }}?tab=subscriptions">
                    <i class="fas fa-user-check"></i> Subscriptions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'analytics' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.copy-trading.index') }}?tab=analytics">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        @php $currentTab = request()->get('tab', 'traders'); @endphp

        @if($currentTab === 'traders')
            <div class="alert alert-info">
                <h5><i class="fas fa-users"></i> Traders List</h5>
                <p>Browse traders available for copying.</p>
                <p class="mb-0"><strong>Phase 6 Complete</strong>: CopyTradingSubscription model operational.</p>
            </div>
        @elseif($currentTab === 'subscriptions')
            <div class="alert alert-success">
                <h5><i class="fas fa-user-check"></i> Active Subscriptions</h5>
                <p>Manage active copy trading subscriptions.</p>
                <p class="mb-0"><strong>Phase 6 Complete</strong>: Models ready, controllers in Phase 7+.</p>
            </div>
        @elseif($currentTab === 'analytics')
            <div class="alert alert-primary">
                <h5><i class="fas fa-chart-bar"></i> Copy Trading Analytics</h5>
                <p>Performance metrics for copy trading activities.</p>
                <p class="mb-0"><strong>Phase 7+</strong>: Analytics service to be implemented.</p>
            </div>
        @endif
    </div>
</div>
@endsection

