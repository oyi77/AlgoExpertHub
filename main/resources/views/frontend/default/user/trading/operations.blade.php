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
                        
                        <!-- Trading Bots Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'trading-bots' ? 'show active' : '' }}" 
                             id="trading-bots" 
                             role="tabpanel">
                            @if(isset($bots) && $bots->count() > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">{{ __('My Trading Bots') }}</h5>
                                    @if(Route::has('user.trading-management.trading-bots.create'))
                                        <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn sp_theme_btn">
                                            <i class="las la-plus"></i> {{ __('Create Bot') }}
                                        </a>
                                    @endif
                                </div>
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
                                    <div class="mb-4">
                                        <i class="las la-robot la-4x text-primary mb-3"></i>
                                        <h4 class="mb-2">{{ __('Get Started with Automated Trading') }}</h4>
                                        <p class="text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
                                            {{ __('Trading bots automate your strategy 24/7. Execute signals automatically with custom rules, monitor performance, and manage risk in real-time.') }}
                                        </p>
                                    </div>
                                    
                                    <!-- Quick Start Guide -->
                                    <div class="row gy-3 mb-4" style="max-width: 800px; margin: 0 auto;">
                                        <div class="col-md-4">
                                            <div class="card border h-100">
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <i class="las la-exchange-alt la-2x text-primary"></i>
                                                    </div>
                                                    <h6 class="mb-2">Step 1: Connect Exchange</h6>
                                                    <p class="text-muted small mb-0">{{ __('Link your trading account') }}</p>
                                                    @if(Route::has('user.exchange-connections.create'))
                                                        <a href="{{ route('user.exchange-connections.create') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                            {{ __('Connect Now') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border h-100">
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <i class="las la-bullhorn la-2x text-info"></i>
                                                    </div>
                                                    <h6 class="mb-2">Step 2: Add Signal Source</h6>
                                                    <p class="text-muted small mb-0">{{ __('Choose where signals come from') }}</p>
                                                    @if(Route::has('user.trading.multi-channel-signal.index'))
                                                        <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="btn btn-sm btn-outline-info mt-2">
                                                            {{ __('Add Source') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border h-100">
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <i class="las la-cog la-2x text-success"></i>
                                                    </div>
                                                    <h6 class="mb-2">Step 3: Configure Bot</h6>
                                                    <p class="text-muted small mb-0">{{ __('Set risk management & filters') }}</p>
                                                    @if(Route::has('user.trading-management.trading-bots.create'))
                                                        <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn btn-sm btn-outline-success mt-2">
                                                            {{ __('Create Bot') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Primary CTA -->
                                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                                        @if(Route::has('user.trading-management.trading-bots.create'))
                                            <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn btn-primary btn-lg">
                                                <i class="las la-plus-circle"></i> {{ __('Create First Bot') }}
                                            </a>
                                        @endif
                                        @if(Route::has('user.trading-management.trading-bots.marketplace'))
                                            <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-outline-primary btn-lg">
                                                <i class="las la-store"></i> {{ __('Browse Templates') }}
                                            </a>
                                        @endif
                                    </div>
                                    
                                    <!-- Help Links -->
                                    <div class="mt-4">
                                        <small class="text-muted">
                                            <a href="#" class="text-decoration-none"><i class="las la-book"></i> {{ __('Learn How Bots Work') }}</a> |
                                            <a href="#" class="text-decoration-none"><i class="las la-video"></i> {{ __('Watch Demo') }}</a> |
                                            <a href="#" class="text-decoration-none"><i class="las la-question-circle"></i> {{ __('Get Help') }}</a>
                                        </small>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
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

