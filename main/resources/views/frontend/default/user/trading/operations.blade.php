@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row gy-4">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-2">{{ __('Trading Operations') }}</h4>
                <p class="text-muted mb-0">{{ __('Manage connections, monitor positions, and view trading analytics') }}</p>
            </div>
        </div>
    </div>

    @if(!$tradingManagementEnabled)
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="las la-exclamation-triangle"></i> 
                {{ __('Trading Management Addon is not enabled. Please contact administrator.') }}
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header p-3 border-bottom">
                    <ul class="nav nav-pills" id="operationsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'connections' ? 'active' : '' }}" 
                               id="connections-tab" 
                               data-bs-toggle="tab" 
                               href="#connections" 
                               role="tab"
                               onclick="switchTab('connections')">
                                <i class="las la-exchange-alt me-1"></i> {{ __('Connections') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'executions' ? 'active' : '' }}" 
                               id="executions-tab" 
                               data-bs-toggle="tab"
                               onclick="switchTab('executions')" 
                               href="#executions" 
                               role="tab">
                                <i class="las la-list me-1"></i> {{ __('Executions') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'open-positions' ? 'active' : '' }}" 
                               id="open-positions-tab" 
                               data-bs-toggle="tab"
                               onclick="switchTab('open-positions')" 
                               href="#open-positions" 
                               role="tab">
                                <i class="las la-chart-area me-1"></i> {{ __('Open Positions') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'closed-positions' ? 'active' : '' }}" 
                               id="closed-positions-tab" 
                               data-bs-toggle="tab"
                               onclick="switchTab('closed-positions')" 
                               href="#closed-positions" 
                               role="tab">
                                <i class="las la-history me-1"></i> {{ __('Closed Positions') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'analytics' ? 'active' : '' }}" 
                               id="analytics-tab" 
                               data-bs-toggle="tab"
                               onclick="switchTab('analytics')" 
                               href="#analytics" 
                               role="tab">
                                <i class="las la-chart-pie me-1"></i> {{ __('Analytics') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'trading-bots' ? 'active' : '' }}" 
                               id="trading-bots-tab" 
                               data-bs-toggle="tab"
                               onclick="switchTab('trading-bots')" 
                               href="#trading-bots" 
                               role="tab">
                                <i class="las la-robot me-1"></i> {{ __('Trading Bots') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="operationsTabContent">
                        <!-- Connections Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'connections' ? 'show active' : '' }}" 
                             id="connections" 
                             role="tabpanel">
                            @if(isset($connections) && $connections->count() > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">{{ __('My Execution Connections') }}</h5>
                                    @if(Route::has('user.execution-connections.create'))
                                        <a href="{{ route('user.execution-connections.create') }}" class="btn sp_theme_btn">
                                            <i class="las la-plus"></i> {{ __('Create Connection') }}
                                        </a>
                                    @endif
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Exchange') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($connections as $conn)
                                            <tr>
                                                <td><strong>{{ $conn->name }}</strong></td>
                                                <td>{{ $conn->exchange_name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-info">{{ ucfirst($conn->exchange_type ?? 'N/A') }}</span></td>
                                                <td>
                                                    @if($conn->is_active)
                                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('user.execution-connections.show', $conn->id) ?? '#' }}" 
                                                       class="btn btn-xs btn-outline-info">
                                                        <i class="las la-eye"></i> {{ __('View') }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($connections->hasPages())
                                    <div class="mt-3">
                                        {{ $connections->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-exchange-alt la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No execution connections found.') }}</p>
                                    @if(Route::has('user.execution-connections.create'))
                                        <a href="{{ route('user.execution-connections.create') }}" class="btn sp_theme_btn mt-2">
                                            <i class="las la-plus"></i> {{ __('Create First Connection') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Executions Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'executions' ? 'show active' : '' }}" 
                             id="executions" 
                             role="tabpanel">
                            @if(isset($executions) && $executions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Time') }}</th>
                                                <th>{{ __('Connection') }}</th>
                                                <th>{{ __('Symbol') }}</th>
                                                <th>{{ __('Direction') }}</th>
                                                <th>{{ __('Lot Size') }}</th>
                                                <th>{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($executions as $exec)
                                            <tr>
                                                <td>{{ $exec->created_at->format('Y-m-d H:i') }}</td>
                                                <td>{{ $exec->connection->name ?? 'N/A' }}</td>
                                                <td>{{ $exec->symbol ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge {{ in_array($exec->direction ?? '', ['BUY', 'LONG']) ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $exec->direction ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ $exec->lot_size ?? 'N/A' }}</td>
                                                <td>
                                                    @if(($exec->status ?? '') === 'SUCCESS')
                                                        <span class="badge bg-success">{{ __('Success') }}</span>
                                                    @elseif(($exec->status ?? '') === 'FAILED')
                                                        <span class="badge bg-danger">{{ __('Failed') }}</span>
                                                    @else
                                                        <span class="badge bg-warning">{{ __('Pending') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($executions->hasPages())
                                    <div class="mt-3">
                                        {{ $executions->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-list la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No execution logs found.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Open Positions Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'open-positions' ? 'show active' : '' }}" 
                             id="open-positions" 
                             role="tabpanel">
                            @if(isset($openPositions) && $openPositions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Symbol') }}</th>
                                                <th>{{ __('Direction') }}</th>
                                                <th>{{ __('Entry Price') }}</th>
                                                <th>{{ __('Current Price') }}</th>
                                                <th>{{ __('P&L') }}</th>
                                                <th>{{ __('SL/TP') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($openPositions as $position)
                                            <tr>
                                                <td><strong>{{ $position->symbol ?? 'N/A' }}</strong></td>
                                                <td>
                                                    <span class="badge {{ in_array($position->direction ?? '', ['BUY', 'LONG']) ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $position->direction ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ $position->entry_price ?? 'N/A' }}</td>
                                                <td>{{ $position->current_price ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="{{ ($position->unrealized_pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($position->unrealized_pnl ?? 0, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    SL: {{ $position->stop_loss ?? 'N/A' }}<br>
                                                    TP: {{ $position->take_profit ?? 'N/A' }}
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-xs btn-danger" onclick="closePosition({{ $position->id }})">
                                                        <i class="las la-times"></i> {{ __('Close') }}
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($openPositions->hasPages())
                                    <div class="mt-3">
                                        {{ $openPositions->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-chart-area la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No open positions.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Closed Positions Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'closed-positions' ? 'show active' : '' }}" 
                             id="closed-positions" 
                             role="tabpanel">
                            @if(isset($closedPositions) && $closedPositions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Symbol') }}</th>
                                                <th>{{ __('Direction') }}</th>
                                                <th>{{ __('Entry') }}</th>
                                                <th>{{ __('Exit') }}</th>
                                                <th>{{ __('P&L') }}</th>
                                                <th>{{ __('Closed At') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($closedPositions as $position)
                                            <tr>
                                                <td><strong>{{ $position->symbol ?? 'N/A' }}</strong></td>
                                                <td>
                                                    <span class="badge {{ in_array($position->direction ?? '', ['BUY', 'LONG']) ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $position->direction ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ $position->entry_price ?? 'N/A' }}</td>
                                                <td>{{ $position->exit_price ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="{{ ($position->realized_pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($position->realized_pnl ?? 0, 2) }}
                                                    </span>
                                                </td>
                                                <td>{{ $position->closed_at ? $position->closed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($closedPositions->hasPages())
                                    <div class="mt-3">
                                        {{ $closedPositions->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-history la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No closed positions.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Analytics Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'analytics' ? 'show active' : '' }}" 
                             id="analytics" 
                             role="tabpanel">
                            @if(isset($analytics) && $analytics->count() > 0)
                                <div class="row g-3 mb-4">
                                    @foreach($analytics as $analytic)
                                    <div class="col-md-4">
                                        <div class="sp_site_card">
                                            <h6 class="mb-1">{{ __('Win Rate') }}</h6>
                                            <h3 class="mb-0">{{ number_format($analytic->win_rate ?? 0, 2) }}%</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sp_site_card">
                                            <h6 class="mb-1">{{ __('Profit Factor') }}</h6>
                                            <h3 class="mb-0">{{ number_format($analytic->profit_factor ?? 0, 2) }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sp_site_card">
                                            <h6 class="mb-1">{{ __('Total P&L') }}</h6>
                                            <h3 class="mb-0 {{ ($analytic->total_pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($analytic->total_pnl ?? 0, 2) }}
                                            </h3>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Connection') }}</th>
                                                <th>{{ __('Win Rate') }}</th>
                                                <th>{{ __('Profit Factor') }}</th>
                                                <th>{{ __('Total P&L') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($analytics as $analytic)
                                            <tr>
                                                <td>{{ $analytic->date ?? 'N/A' }}</td>
                                                <td>{{ $analytic->connection->name ?? 'N/A' }}</td>
                                                <td>{{ number_format($analytic->win_rate ?? 0, 2) }}%</td>
                                                <td>{{ number_format($analytic->profit_factor ?? 0, 2) }}</td>
                                                <td class="{{ ($analytic->total_pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($analytic->total_pnl ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($analytics->hasPages())
                                    <div class="mt-3">
                                        {{ $analytics->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-chart-pie la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No analytics data available.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Trading Bots Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'trading-bots' ? 'show active' : '' }}" 
                             id="trading-bots" 
                             role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ __('My Trading Bots') }}</h5>
                                @if(Route::has('user.trading-management.trading-bots.create'))
                                    <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn sp_theme_btn">
                                        <i class="las la-plus"></i> {{ __('Create Bot') }}
                                    </a>
                                @endif
                            </div>
                            @if(isset($bots) && $bots->count() > 0)
                                <div class="row gy-3">
                                    @foreach($bots as $bot)
                                    <div class="col-md-6">
                                        <div class="sp_site_card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="mb-1">{{ $bot->name }}</h5>
                                                    <p class="text-muted small mb-0">{{ $bot->description ?? 'No description' }}</p>
                                                </div>
                                                <span class="badge {{ $bot->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $bot->is_active ? __('Active') : __('Inactive') }}
                                                </span>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Exchange') }}:</small>
                                                    <div>{{ $bot->exchangeConnection->name ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Preset') }}:</small>
                                                    <div>{{ $bot->tradingPreset->name ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('user.trading-management.trading-bots.show', $bot->id) ?? '#' }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="las la-eye"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('user.trading-management.trading-bots.edit', $bot->id) ?? '#' }}" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="las la-edit"></i> {{ __('Edit') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($bots->hasPages())
                                    <div class="mt-3">
                                        {{ $bots->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-robot la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No trading bots found.') }}</p>
                                    @if(Route::has('user.trading-management.trading-bots.create'))
                                        <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn sp_theme_btn mt-2">
                                            <i class="las la-plus"></i> {{ __('Create First Bot') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('script')
<script>
    $(function() {
        'use strict'
        
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            const tabLink = $('#operationsTabs a[href="#' + tabParam + '"]');
            if (tabLink.length) {
                const tab = new bootstrap.Tab(tabLink[0]);
                tab.show();
            }
        }
        // Function to switch tabs and update URL
        function switchTab(tabName) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.location.href = url.toString();
        }
        
        // Make switchTab available globally
        window.switchTab = switchTab;
    });
</script>
@endpush
@endsection

