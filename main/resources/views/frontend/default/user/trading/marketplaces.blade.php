@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row gy-4">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-2">{{ __('Marketplaces') }}</h4>
                <p class="text-muted mb-0">{{ __('Browse and subscribe to trading presets, strategies, AI models, and bots') }}</p>
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
                    <ul class="nav nav-pills" id="marketplaceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === 'trading-presets' ? 'active' : '' }}" 
                               id="trading-presets-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('trading-presets')"
                               href="#trading-presets" 
                               role="tab">
                                <i class="las la-shield-alt me-1"></i> {{ __('Trading Presets') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === 'filter-strategies' ? 'active' : '' }}" 
                               id="filter-strategies-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('filter-strategies')"
                               href="#filter-strategies" 
                               role="tab">
                                <i class="las la-filter me-1"></i> {{ __('Filter Strategies') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === 'ai-profiles' ? 'active' : '' }}" 
                               id="ai-profiles-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('ai-profiles')"
                               href="#ai-profiles" 
                               role="tab">
                                <i class="las la-robot me-1"></i> {{ __('AI Model Profiles') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === 'copy-trading' ? 'active' : '' }}" 
                               id="copy-trading-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('copy-trading')"
                               href="#copy-trading" 
                               role="tab">
                                <i class="las la-copy me-1"></i> {{ __('Copy Trading') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === 'bot-marketplace' ? 'active' : '' }}" 
                               id="bot-marketplace-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('bot-marketplace')"
                               href="#bot-marketplace" 
                               role="tab">
                                <i class="las la-store me-1"></i> {{ __('Bot Marketplace') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="marketplaceTabContent">
                        <!-- Trading Presets Category -->
                        <div class="tab-pane fade {{ $activeCategory === 'trading-presets' ? 'show active' : '' }}" 
                             id="trading-presets" 
                             role="tabpanel">
                            @if(isset($items) && $items->count() > 0)
                                <div class="row gy-3 marketplace-grid">
                                    @foreach($items as $item)
                                    <div class="col-md-4">
                                        <div class="marketplace-card">
                                            <div class="sp_site_card">
                                                <div class="card-header-section">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h5 class="mb-1">{{ $item->name }}</h5>
                                                            <p class="text-muted small mb-0">{{ Str::limit($item->description ?? 'No description', 100) }}</p>
                                                        </div>
                                                        <span class="badge bg-info ms-2 flex-shrink-0">{{ __('Public') }}</span>
                                                    </div>
                                                </div>
                                                <div class="card-body-section">
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Position Size') }}:</small>
                                                            <div>{{ $item->position_sizing_strategy ?? 'N/A' }}</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Risk') }}:</small>
                                                            <div>{{ $item->risk_per_trade ?? 'N/A' }}%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer-section">
                                                    <div class="d-flex gap-2">
                                                @php
                                                    $cloneRoute = null;
                                                    if (Route::has('user.trading-presets.clone')) {
                                                        $cloneRoute = route('user.trading-presets.clone', $item->id);
                                                    } elseif (Route::has('user.trading-management.trading-presets.clone')) {
                                                        $cloneRoute = route('user.trading-management.trading-presets.clone', $item->id);
                                                    }
                                                @endphp
                                                @if($cloneRoute)
                                                    <form action="{{ $cloneRoute }}" method="POST" class="w-100" onsubmit="return confirm('{{ __('Are you sure you want to clone this preset?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm sp_theme_btn w-100">
                                                            <i class="las la-copy"></i> {{ __('Clone') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="#" class="btn btn-sm btn-secondary w-100" onclick="alert('{{ __('Clone feature is not available. Please contact administrator.') }}'); return false;">
                                                        <i class="las la-copy"></i> {{ __('Clone') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($items->hasPages())
                                    <div class="mt-3">
                                        {{ $items->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center marketplace-empty-state">
                                    <i class="las la-shield-alt la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No trading presets available in marketplace.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Filter Strategies Category -->
                        <div class="tab-pane fade {{ $activeCategory === 'filter-strategies' ? 'show active' : '' }}" 
                             id="filter-strategies" 
                             role="tabpanel">
                            @if(isset($items) && $items->count() > 0)
                                <div class="row gy-3 marketplace-grid">
                                    @foreach($items as $item)
                                    <div class="col-md-4">
                                        <div class="marketplace-card">
                                            <div class="sp_site_card">
                                                <div class="card-header-section">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h5 class="mb-1">{{ $item->name }}</h5>
                                                            <p class="text-muted small mb-0">{{ Str::limit($item->description ?? 'No description', 100) }}</p>
                                                        </div>
                                                        <span class="badge bg-info ms-2 flex-shrink-0">{{ ucfirst($item->type ?? 'N/A') }}</span>
                                                    </div>
                                                </div>
                                                <div class="card-footer-section">
                                                    <div class="d-flex gap-2">
                                                @php
                                                    $cloneRoute = null;
                                                    if (Route::has('user.filter-strategies.clone')) {
                                                        $cloneRoute = route('user.filter-strategies.clone', $item->id);
                                                    } elseif (Route::has('user.trading-management.filter-strategies.clone')) {
                                                        $cloneRoute = route('user.trading-management.filter-strategies.clone', $item->id);
                                                    }
                                                @endphp
                                                @if($cloneRoute)
                                                    <form action="{{ $cloneRoute }}" method="POST" class="w-100" onsubmit="return confirm('{{ __('Are you sure you want to clone this strategy?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm sp_theme_btn w-100">
                                                            <i class="las la-copy"></i> {{ __('Clone') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="#" class="btn btn-sm btn-secondary w-100" onclick="alert('{{ __('Clone feature is not available. Please contact administrator.') }}'); return false;">
                                                        <i class="las la-copy"></i> {{ __('Clone') }}
                                                    </a>
                                                @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($items->hasPages())
                                    <div class="mt-3">
                                        {{ $items->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center marketplace-empty-state">
                                    <i class="las la-filter la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No filter strategies available in marketplace.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- AI Model Profiles Category -->
                        <div class="tab-pane fade {{ $activeCategory === 'ai-profiles' ? 'show active' : '' }}" 
                             id="ai-profiles" 
                             role="tabpanel">
                            @if(isset($items) && $items->count() > 0)
                                <div class="row gy-3 marketplace-grid">
                                    @foreach($items as $item)
                                    <div class="col-md-4">
                                        <div class="marketplace-card">
                                            <div class="sp_site_card">
                                                <div class="card-header-section">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h5 class="mb-1">{{ $item->name }}</h5>
                                                            <p class="text-muted small mb-0">{{ Str::limit($item->description ?? 'No description', 100) }}</p>
                                                        </div>
                                                        <span class="badge bg-info ms-2 flex-shrink-0">{{ $item->model_provider ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                <div class="card-body-section">
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Model') }}:</small>
                                                            <div>{{ $item->model_name ?? 'N/A' }}</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Temperature') }}:</small>
                                                            <div>{{ $item->temperature ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer-section">
                                                    <div class="d-flex gap-2">
                                                @php
                                                    $cloneRoute = null;
                                                    if (Route::has('user.ai-model-profiles.clone')) {
                                                        $cloneRoute = route('user.ai-model-profiles.clone', $item->id);
                                                    } elseif (Route::has('user.trading-management.ai-model-profiles.clone')) {
                                                        $cloneRoute = route('user.trading-management.ai-model-profiles.clone', $item->id);
                                                    }
                                                @endphp
                                                @if($cloneRoute)
                                                    <form action="{{ $cloneRoute }}" method="POST" class="w-100" onsubmit="return confirm('{{ __('Are you sure you want to clone this AI profile?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm sp_theme_btn w-100">
                                                            <i class="las la-copy"></i> {{ __('Clone') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="#" class="btn btn-sm btn-secondary w-100" onclick="alert('{{ __('Clone feature is not available. Please contact administrator.') }}'); return false;">
                                                        <i class="las la-copy"></i> {{ __('Clone') }}
                                                    </a>
                                                @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($items->hasPages())
                                    <div class="mt-3">
                                        {{ $items->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center marketplace-empty-state">
                                    <i class="las la-robot la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No AI model profiles available in marketplace.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Copy Trading Category -->
                        <div class="tab-pane fade {{ $activeCategory === 'copy-trading' ? 'show active' : '' }}" 
                             id="copy-trading" 
                             role="tabpanel">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#browse-traders">
                                        <i class="las la-users"></i> {{ __('Browse Traders') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#my-subscriptions">
                                        <i class="las la-list"></i> {{ __('My Subscriptions') }}
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="browse-traders">
                                    <div class="text-center py-5">
                                        <i class="las la-users la-3x text-muted mb-3"></i>
                                        <p class="text-muted">{{ __('Copy Trading feature coming soon.') }}</p>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="my-subscriptions">
                                    <div class="text-center py-5">
                                        <i class="las la-list la-3x text-muted mb-3"></i>
                                        <p class="text-muted">{{ __('No active subscriptions.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bot Marketplace Category -->
                        <div class="tab-pane fade {{ $activeCategory === 'bot-marketplace' ? 'show active' : '' }}" 
                             id="bot-marketplace" 
                             role="tabpanel">
                            @if(isset($items) && $items->count() > 0)
                                <div class="row gy-3 marketplace-grid">
                                    @foreach($items as $item)
                                    <div class="col-md-4">
                                        <div class="marketplace-card">
                                            <div class="sp_site_card">
                                                <div class="card-header-section">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <h5 class="mb-1">{{ $item->name }}</h5>
                                                            <p class="text-muted small mb-0">{{ Str::limit($item->description ?? 'No description', 100) }}</p>
                                                        </div>
                                                        <span class="badge bg-success ms-2 flex-shrink-0">{{ __('Public') }}</span>
                                                    </div>
                                                </div>
                                                <div class="card-body-section">
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Exchange') }}:</small>
                                                            <div>{{ $item->exchangeConnection->name ?? 'N/A' }}</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">{{ __('Preset') }}:</small>
                                                            <div>{{ $item->tradingPreset->name ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer-section">
                                                    <div class="d-flex gap-2">
                                                @php
                                                    $cloneRoute = null;
                                                    $subscribeRoute = null;
                                                    if (Route::has('user.trading-management.trading-bots.clone')) {
                                                        $cloneRoute = route('user.trading-management.trading-bots.clone', $item->id);
                                                    } elseif (Route::has('user.trading-bots.clone')) {
                                                        $cloneRoute = route('user.trading-bots.clone', $item->id);
                                                    }
                                                    if (Route::has('user.trading-management.trading-bots.subscribe')) {
                                                        $subscribeRoute = route('user.trading-management.trading-bots.subscribe', $item->id);
                                                    } elseif (Route::has('user.trading-bots.subscribe')) {
                                                        $subscribeRoute = route('user.trading-bots.subscribe', $item->id);
                                                    }
                                                @endphp
                                                @if($cloneRoute)
                                                    <a href="{{ $cloneRoute }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="las la-copy"></i> {{ __('Clone') }}
                                                    </a>
                                                @else
                                                    <a href="#" class="btn btn-sm btn-secondary" onclick="alert('{{ __('Clone feature is not available. Please contact administrator.') }}'); return false;">
                                                        <i class="las la-copy"></i> {{ __('Clone') }}
                                                    </a>
                                                @endif
                                                @if($subscribeRoute)
                                                    <a href="{{ $subscribeRoute }}" class="btn btn-sm sp_theme_btn">
                                                        <i class="las la-bell"></i> {{ __('Subscribe') }}
                                                    </a>
                                                @else
                                                    <a href="#" class="btn btn-sm btn-secondary" onclick="alert('{{ __('Subscribe feature is not available. Please contact administrator.') }}'); return false;">
                                                        <i class="las la-bell"></i> {{ __('Subscribe') }}
                                                    </a>
                                                @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($items->hasPages())
                                    <div class="mt-3">
                                        {{ $items->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center marketplace-empty-state">
                                    <i class="las la-store la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No bots available in marketplace.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('style')
<style>
    /* Marketplace Card Styling - Consistent with other trading pages */
    .marketplace-card {
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .marketplace-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .marketplace-card .sp_site_card {
        height: 100%;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
    }
    
    .marketplace-card h5 {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    
    .marketplace-card .text-muted.small {
        font-size: 0.875rem;
        line-height: 1.6;
        margin-bottom: 1rem;
        min-height: 3rem;
    }
    
    .marketplace-card .badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.7rem;
        font-weight: 500;
    }
    
    .marketplace-card .row.g-2 {
        margin: 1rem 0;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .marketplace-card .row.g-2 small {
        font-size: 0.75rem;
        display: block;
        margin-bottom: 0.35rem;
        opacity: 0.7;
        font-weight: 500;
    }
    
    .marketplace-card .row.g-2 > div {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .marketplace-card .card-footer-section {
        margin-top: auto;
        padding-top: 1rem;
    }
    
    .marketplace-card .btn {
        font-weight: 500;
        padding: 0.625rem 1.25rem;
    }
    
    /* Grid spacing */
    .marketplace-grid .col-md-4 {
        margin-bottom: 1.5rem;
    }
    
    /* Empty state */
    .marketplace-empty-state {
        padding: 3rem 1rem;
    }
    
    .marketplace-empty-state i {
        opacity: 0.5;
        margin-bottom: 1rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .marketplace-card .sp_site_card {
            padding: 1.25rem;
        }
        
        .marketplace-card h5 {
            font-size: 1rem;
        }
        
        .marketplace-card .text-muted.small {
            font-size: 0.8rem;
            min-height: auto;
        }
        
        .marketplace-grid .col-md-4 {
            margin-bottom: 1rem;
        }
    }
</style>
@endpush

@push('script')
<script>
    $(function() {
        'use strict'
        
        // Function to switch tabs and update URL
        function switchTab(categoryName) {
            const url = new URL(window.location);
            url.searchParams.set('category', categoryName);
            window.location.href = url.toString();
        }
        
        // Make switchTab available globally
        window.switchTab = switchTab;
        
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParam = urlParams.get('category');
        
        if (categoryParam) {
            const tabLink = $('#marketplaceTabs a[href="#' + categoryParam + '"]');
            if (tabLink.length) {
                const tab = new bootstrap.Tab(tabLink[0]);
                tab.show();
            }
        }
        
        // Old event handler - keep for compatibility
        $('#marketplaceTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const targetId = $(e.target).attr('href').replace('#', '');
            const url = new URL(window.location);
            url.searchParams.set('category', targetId);
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endpush
@endsection

