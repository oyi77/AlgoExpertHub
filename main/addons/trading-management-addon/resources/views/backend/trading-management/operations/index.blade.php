@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>⚡ Trading Operations</h4>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('tab') || request()->get('tab') === 'connections' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.operations.index') }}?tab=connections">
                    <i class="fas fa-plug"></i> Connections
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'executions' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.operations.index') }}?tab=executions">
                    <i class="fas fa-history"></i> Executions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'open' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.operations.index') }}?tab=open">
                    <i class="fas fa-chart-line"></i> Open Positions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'closed' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.operations.index') }}?tab=closed">
                    <i class="fas fa-check-circle"></i> Closed Positions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'analytics' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.operations.index') }}?tab=analytics">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            @php $currentTab = request()->get('tab', 'connections'); @endphp

            @if($currentTab === 'connections')
                <div class="alert alert-info">
                    <h5><i class="fas fa-plug"></i> Execution Connections</h5>
                    <p>Manage your exchange/broker connections for trade execution.</p>
                    <p class="mb-0"><strong>Phase 5 Complete</strong>: Models ready. Controllers/views in Phase 7+.</p>
                </div>
            @elseif($currentTab === 'executions')
                <div class="alert alert-info">
                    <h5><i class="fas fa-history"></i> Execution History</h5>
                    <p>View all executed trades.</p>
                    <p class="mb-0"><strong>Phase 5 Complete</strong>: ExecutionLog model ready.</p>
                </div>
            @elseif($currentTab === 'open')
                <div class="alert alert-success">
                    <h5><i class="fas fa-chart-line"></i> Open Positions</h5>
                    <p>Monitor active trades with SL/TP tracking.</p>
                    <p class="mb-0"><strong>Phase 5 Complete</strong>: ExecutionPosition model ready.</p>
                </div>
            @elseif($currentTab === 'closed')
                <div class="alert alert-secondary">
                    <h5><i class="fas fa-check-circle"></i> Closed Positions</h5>
                    <p>View historical positions and performance.</p>
                    <p class="mb-0"><strong>Phase 5 Complete</strong>: Data structure ready.</p>
                </div>
            @elseif($currentTab === 'analytics')
                <div class="alert alert-primary">
                    <h5><i class="fas fa-chart-bar"></i> Performance Analytics</h5>
                    <p>Win rate, profit factor, drawdown metrics.</p>
                    <p class="mb-0"><strong>Phase 5 Complete</strong>: ExecutionAnalytics model ready.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

