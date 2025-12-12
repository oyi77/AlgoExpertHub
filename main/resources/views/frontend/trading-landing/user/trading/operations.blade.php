@extends(Config::themeView('layout.auth'))

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
                                    @php
                                        $createRoute = null;
                                        if (Route::has('user.exchange-connections.create')) {
                                            $createRoute = route('user.exchange-connections.create');
                                        } elseif (Route::has('user.execution-connections.create')) {
                                            $createRoute = route('user.execution-connections.create');
                                        }
                                    @endphp
                                    @if($createRoute)
                                        <a href="{{ $createRoute }}" class="btn sp_theme_btn">
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
                                                    @php
                                                        $showRoute = null;
                                                        if (Route::has('user.exchange-connections.show')) {
                                                            $showRoute = route('user.exchange-connections.show', $conn->id);
                                                        } elseif (Route::has('user.execution-connections.show')) {
                                                            $showRoute = route('user.execution-connections.show', $conn->id);
                                                        } elseif (Route::has('admin.exchange-connections.show')) {
                                                            // Fallback to admin route if user route doesn't exist
                                                            $showRoute = route('admin.exchange-connections.show', $conn->id);
                                                        }
                                                    @endphp
                                                    @if($showRoute)
                                                        <a href="{{ $showRoute }}" class="btn btn-xs btn-outline-info">
                                                            <i class="las la-eye"></i> {{ __('View') }}
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">{{ __('View not available') }}</span>
                                                    @endif
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
                                    @php
                                        $createRoute = null;
                                        if (Route::has('user.exchange-connections.create')) {
                                            $createRoute = route('user.exchange-connections.create');
                                        } elseif (Route::has('user.execution-connections.create')) {
                                            $createRoute = route('user.execution-connections.create');
                                        }
                                    @endphp
                                    @if($createRoute)
                                        <a href="{{ $createRoute }}" class="btn sp_theme_btn mt-2">
                                            <i class="las la-plus"></i> {{ __('Create First Connection') }}
                                        </a>
                                    @endif
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

