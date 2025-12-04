@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>🧪 Trading Test (Backtesting)</h4>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('tab') || request()->get('tab') === 'create' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.test.index') }}?tab=create">
                    <i class="fas fa-play"></i> Create Backtest
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'results' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.test.index') }}?tab=results">
                    <i class="fas fa-list"></i> Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'reports' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.test.index') }}?tab=reports">
                    <i class="fas fa-file-alt"></i> Performance Reports
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        @php $currentTab = request()->get('tab', 'create'); @endphp

        @if($currentTab === 'create')
            <div class="alert alert-warning">
                <h5><i class="fas fa-play"></i> Create Backtest</h5>
                <p>Test your trading strategies on historical data.</p>
                <p class="mb-0"><strong>Phase 8</strong>: Backtesting module (deferred, optional feature).</p>
            </div>
        @elseif($currentTab === 'results')
            <div class="alert alert-info">
                <h5><i class="fas fa-list"></i> Backtest Results</h5>
                <p>View results from completed backtests.</p>
                <p class="mb-0"><strong>Phase 8</strong>: To be implemented.</p>
            </div>
        @elseif($currentTab === 'reports')
            <div class="alert alert-primary">
                <h5><i class="fas fa-file-alt"></i> Performance Reports</h5>
                <p>Detailed performance analysis with metrics.</p>
                <p class="mb-0"><strong>Phase 8</strong>: To be implemented.</p>
            </div>
        @endif
    </div>
</div>
@endsection

