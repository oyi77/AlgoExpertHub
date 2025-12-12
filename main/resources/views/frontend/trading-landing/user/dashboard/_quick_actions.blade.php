@php
    $onboardingService = app(\App\Services\UserOnboardingService::class);
    $user = auth()->user();
    $hasActivePlan = $onboardingService->hasActivePlan($user);
    $hasSignalSource = $onboardingService->hasSignalSource($user);
    $hasTradingConnection = $onboardingService->hasTradingConnection($user);
    $hasTradingPreset = $onboardingService->hasTradingPreset($user);
@endphp

@if(!$hasActivePlan || !$hasSignalSource || !$hasTradingConnection || !$hasTradingPreset)
<div class="row g-3 mb-4">
    <div class="col-12">
        <h5 class="mb-3">
            <i class="las la-bolt me-2"></i> {{ __('Quick Actions') }}
        </h5>
    </div>
    
    @if(!$hasActivePlan)
    <div class="col-md-6 col-lg-3">
        <div class="sp_site_card quick-action-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="las la-clipboard-list" style="font-size: 48px; color: var(--base-color);"></i>
                </div>
                <h6 class="mb-2">{{ __('Subscribe to Plan') }}</h6>
                <p class="text-muted small mb-3">{{ __('Get access to trading signals') }}</p>
                <a href="{{ route('user.plans') }}" class="btn sp_theme_btn btn-sm w-100">
                    {{ __('Subscribe Now') }}
                </a>
            </div>
        </div>
    </div>
    @endif
    
    @if($hasActivePlan && !$hasSignalSource && \App\Support\AddonRegistry::active('multi-channel-signal-addon'))
    <div class="col-md-6 col-lg-3">
        <div class="sp_site_card quick-action-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="las la-signal" style="font-size: 48px; color: var(--base-color);"></i>
                </div>
                <h6 class="mb-2">{{ __('Connect Signal Source') }}</h6>
                <p class="text-muted small mb-3">{{ __('Receive signals automatically') }}</p>
                <a href="{{ route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']) }}" class="btn sp_theme_btn btn-sm w-100">
                    {{ __('Add Source') }}
                </a>
            </div>
        </div>
    </div>
    @endif
    
    @if($hasActivePlan && !$hasTradingConnection && \App\Support\AddonRegistry::active('trading-management-addon'))
    <div class="col-md-6 col-lg-3">
        <div class="sp_site_card quick-action-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="las la-bolt" style="font-size: 48px; color: var(--base-color);"></i>
                </div>
                <h6 class="mb-2">{{ __('Setup Auto Trading') }}</h6>
                <p class="text-muted small mb-3">{{ __('Automate your trading') }}</p>
                <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}" class="btn sp_theme_btn btn-sm w-100">
                    {{ __('Setup Now') }}
                </a>
            </div>
        </div>
    </div>
    @endif
    
    @if($hasActivePlan && !$hasTradingPreset && \App\Support\AddonRegistry::active('trading-management-addon'))
    <div class="col-md-6 col-lg-3">
        <div class="sp_site_card quick-action-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="las la-cog" style="font-size: 48px; color: var(--base-color);"></i>
                </div>
                <h6 class="mb-2">{{ __('Create Trading Preset') }}</h6>
                <p class="text-muted small mb-3">{{ __('Manage risk & position sizing') }}</p>
                <a href="{{ route('user.trading.configuration.index', ['tab' => 'risk-presets']) }}" class="btn sp_theme_btn btn-sm w-100">
                    {{ __('Create Preset') }}
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@push('style')
<style>
    .quick-action-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(0,0,0,0.1);
    }
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>
@endpush
@endif

