@extends(Config::themeView('layout.auth'))

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="row gy-4">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-2"><i class="las la-cog"></i> {{ __('Trading Configurations') }}</h4>
                <p class="text-muted mb-0">{{ __('Configure risk presets, filter strategies, and AI model profiles') }}</p>
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
                    <ul class="nav nav-pills" id="configurationsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'risk-presets' ? 'active' : '' }}" 
                               id="risk-presets-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('risk-presets')"
                               href="#risk-presets" 
                               role="tab">
                                <i class="las la-shield-alt me-1"></i> {{ __('Risk Presets') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'smart-risk' ? 'active' : '' }}" 
                               id="smart-risk-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('smart-risk')"
                               href="#smart-risk" 
                               role="tab">
                                <i class="las la-brain me-1"></i> {{ __('Smart Risk Management') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'filter-strategies' ? 'active' : '' }}" 
                               id="filter-strategies-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('filter-strategies')"
                               href="#filter-strategies" 
                               role="tab">
                                <i class="las la-filter me-1"></i> {{ __('Filter Strategies') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'ai-profiles' ? 'active' : '' }}" 
                               id="ai-profiles-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('ai-profiles')"
                               href="#ai-profiles" 
                               role="tab">
                                <i class="las la-robot me-1"></i> {{ __('AI Model Profiles') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="configurationsTabContent">
                        <!-- Risk Presets Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'risk-presets' ? 'show active' : '' }}" 
                             id="risk-presets" 
                             role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ __('My Risk Presets') }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('user.trading.marketplaces.index', ['category' => 'trading-presets']) }}" class="btn btn-outline-primary">
                                        <i class="las la-store me-1"></i> {{ __('Browse Marketplace') }}
                                    </a>
                                    @if(Route::has('user.trading-presets.create'))
                                        <a href="{{ route('user.trading-presets.create') }}" class="btn sp_theme_btn">
                                            <i class="las la-plus"></i> {{ __('Create Preset') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if(isset($presets) && $presets->count() > 0)
                                <div class="row gy-3">
                                    @foreach($presets as $preset)
                                    <div class="col-md-6">
                                        <div class="sp_site_card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="mb-1">{{ $preset->name }}</h5>
                                                    <p class="text-muted small mb-0">{{ $preset->description ?? 'No description' }}</p>
                                                </div>
                                                @if(is_null($preset->created_by_user_id))
                                                    <span class="badge bg-info">{{ __('System') }}</span>
                                                @endif
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Position Size') }}:</small>
                                                    <div>{{ $preset->position_sizing_strategy ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Risk Per Trade') }}:</small>
                                                    <div>{{ $preset->risk_per_trade ?? 'N/A' }}%</div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                @php
                                                    $editRoute = null;
                                                    $cloneRoute = null;
                                                    if (Route::has('user.trading-presets.edit')) {
                                                        $editRoute = route('user.trading-presets.edit', $preset->id);
                                                    }
                                                    if (Route::has('user.trading-presets.clone')) {
                                                        $cloneRoute = route('user.trading-presets.clone', $preset->id);
                                                    }
                                                @endphp
                                                @if($editRoute && ($preset->created_by_user_id === auth()->id() || is_null($preset->created_by_user_id)))
                                                    <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="las la-edit"></i> {{ __('Edit') }}
                                                    </a>
                                                @endif
                                                @if($cloneRoute && ($preset->isPublic() && $preset->isClonable()))
                                                    <form action="{{ $cloneRoute }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to clone this preset?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                                            <i class="las la-copy"></i> {{ __('Clone') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($presets->hasPages())
                                    <div class="mt-3">
                                        {{ $presets->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-shield-alt la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No risk presets found.') }}</p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('user.trading.marketplaces.index', ['category' => 'trading-presets']) }}" class="btn btn-outline-primary mt-2">
                                            <i class="las la-store"></i> {{ __('Browse Marketplace') }}
                                        </a>
                                        @if(Route::has('user.trading-presets.create'))
                                            <a href="{{ route('user.trading-presets.create') }}" class="btn sp_theme_btn mt-2">
                                                <i class="las la-plus"></i> {{ __('Create Preset') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Smart Risk Management Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'smart-risk' ? 'show active' : '' }}" 
                             id="smart-risk" 
                             role="tabpanel">
                            @if(isset($smartRiskSettings))
                                <div class="alert alert-info">
                                    <i class="las la-info-circle"></i>
                                    {{ __('Smart Risk Management uses AI to dynamically adjust position sizes based on market conditions and signal provider performance.') }}
                                </div>
                                <div class="sp_site_card">
                                    <h5 class="mb-3">{{ __('Smart Risk Settings') }}</h5>
                                    <form method="POST" action="{{ route('user.srm.settings.update') ?? '#' }}">
                                        @csrf
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enabled" id="srmEnabled" 
                                                       {{ ($smartRiskSettings['enabled'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="srmEnabled">
                                                    {{ __('Enable Smart Risk Management') }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Min Provider Score') }}</label>
                                                <input type="number" name="min_provider_score" class="form-control" 
                                                       value="{{ $smartRiskSettings['min_provider_score'] ?? 70 }}" min="0" max="100">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mt-4">
                                                    <input class="form-check-input" type="checkbox" name="slippage_buffer_enabled" id="slippageBuffer" 
                                                           {{ ($smartRiskSettings['slippage_buffer_enabled'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="slippageBuffer">
                                                        {{ __('Enable Slippage Buffer') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button type="submit" class="btn sp_theme_btn">
                                                <i class="las la-save"></i> {{ __('Save Settings') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-brain la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('Smart Risk Management settings not available.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Filter Strategies Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'filter-strategies' ? 'show active' : '' }}" 
                             id="filter-strategies" 
                             role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ __('My Filter Strategies') }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('user.trading.marketplaces.index', ['category' => 'filter-strategies']) }}" class="btn btn-outline-primary">
                                        <i class="las la-store me-1"></i> {{ __('Browse Marketplace') }}
                                    </a>
                                    @if(Route::has('user.filter-strategies.create'))
                                        <a href="{{ route('user.filter-strategies.create') }}" class="btn sp_theme_btn">
                                            <i class="las la-plus"></i> {{ __('Create Strategy') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if(isset($filterStrategies) && $filterStrategies->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Created') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($filterStrategies as $strategy)
                                            <tr>
                                                <td><strong>{{ $strategy->name }}</strong></td>
                                                <td><span class="badge bg-info">{{ ucfirst($strategy->type ?? 'N/A') }}</span></td>
                                                <td>
                                                    @if($strategy->is_active ?? true)
                                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $strategy->created_at ? $strategy->created_at->diffForHumans() : 'N/A' }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('user.filter-strategies.edit', $strategy->id) ?? '#' }}" 
                                                       class="btn btn-xs btn-outline-primary">
                                                        <i class="las la-edit"></i> {{ __('Edit') }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($filterStrategies->hasPages())
                                    <div class="mt-3">
                                        {{ $filterStrategies->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-filter la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No filter strategies found.') }}</p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('user.trading.marketplaces.index', ['category' => 'filter-strategies']) }}" class="btn btn-outline-primary mt-2">
                                            <i class="las la-store"></i> {{ __('Browse Marketplace') }}
                                        </a>
                                        @if(Route::has('user.filter-strategies.create'))
                                            <a href="{{ route('user.filter-strategies.create') }}" class="btn sp_theme_btn mt-2">
                                                <i class="las la-plus"></i> {{ __('Create Strategy') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- AI Model Profiles Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'ai-profiles' ? 'show active' : '' }}" 
                             id="ai-profiles" 
                             role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ __('My AI Model Profiles') }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('user.trading.marketplaces.index', ['category' => 'ai-profiles']) }}" class="btn btn-outline-primary">
                                        <i class="las la-store me-1"></i> {{ __('Browse Marketplace') }}
                                    </a>
                                    @if(Route::has('user.ai-model-profiles.create'))
                                        <a href="{{ route('user.ai-model-profiles.create') }}" class="btn sp_theme_btn">
                                            <i class="las la-plus"></i> {{ __('Create Profile') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if(isset($aiProfiles) && $aiProfiles->count() > 0)
                                <div class="row gy-3">
                                    @foreach($aiProfiles as $profile)
                                    <div class="col-md-6">
                                        <div class="sp_site_card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="mb-1">{{ $profile->name }}</h5>
                                                    <p class="text-muted small mb-0">{{ $profile->description ?? 'No description' }}</p>
                                                </div>
                                                <span class="badge bg-info">{{ $profile->model_provider ?? $profile->provider ?? 'N/A' }}</span>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Model') }}:</small>
                                                    <div>{{ $profile->model_name ?? 'N/A' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Temperature') }}:</small>
                                                    <div>{{ $profile->temperature ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                @if(Route::has('user.ai-model-profiles.edit'))
                                                    <a href="{{ route('user.ai-model-profiles.edit', $profile->id) ?? '#' }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="las la-edit"></i> {{ __('Edit') }}
                                                    </a>
                                                @endif
                                                @if(Route::has('user.trading-management.ai-profiles.show'))
                                                    <a href="{{ route('user.trading-management.ai-profiles.show', $profile->id) ?? '#' }}" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="las la-eye"></i> {{ __('View') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($aiProfiles->hasPages())
                                    <div class="mt-3">
                                        {{ $aiProfiles->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-robot la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No AI model profiles found.') }}</p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('user.trading.marketplaces.index', ['category' => 'ai-profiles']) }}" class="btn btn-outline-primary mt-2">
                                            <i class="las la-store"></i> {{ __('Browse Marketplace') }}
                                        </a>
                                        @if(Route::has('user.ai-model-profiles.create'))
                                            <a href="{{ route('user.ai-model-profiles.create') }}" class="btn sp_theme_btn mt-2">
                                                <i class="las la-plus"></i> {{ __('Create Profile') }}
                                            </a>
                                        @endif
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

@push('script')
<script>
    $(function() {
        'use strict'
        
        // Function to switch tabs and update URL
        function switchTab(tabName) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.location.href = url.toString();
        }
        
        // Make switchTab available globally
        window.switchTab = switchTab;
        
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            const tabLink = $('#configurationsTabs a[href="#' + tabParam + '"]');
            if (tabLink.length) {
                const tab = new bootstrap.Tab(tabLink[0]);
                tab.show();
            }
        }
        
        // Event handler for tab switching
        $('#configurationsTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const targetId = $(e.target).attr('href').replace('#', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId);
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endpush
@endsection

