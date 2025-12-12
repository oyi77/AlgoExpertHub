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
        <div class="sp_site_card quick-actions-banner" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
            <div class="card-body text-white">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2 text-white">
                            <i class="las la-bolt me-2"></i> {{ __('Quick Actions') }}
                        </h4>
                        <p class="mb-0 text-white-50">{{ __('Complete your setup to unlock all trading features') }}</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        @if(!$hasActivePlan)
                            <a href="{{ route('user.plans') }}" class="btn btn-light btn-sm">
                                <i class="las la-clipboard-list me-1"></i> {{ __('Subscribe to Plan') }}
                            </a>
                        @elseif(!$hasSignalSource && \App\Support\AddonRegistry::active('multi-channel-signal-addon'))
                            <a href="{{ route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']) }}" class="btn btn-light btn-sm">
                                <i class="las la-signal me-1"></i> {{ __('Add Signal Source') }}
                            </a>
                        @elseif(!$hasTradingConnection && \App\Support\AddonRegistry::active('trading-management-addon'))
                            <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}" class="btn btn-light btn-sm">
                                <i class="las la-bolt me-1"></i> {{ __('Setup Auto Trading') }}
                            </a>
                        @elseif(!$hasTradingPreset && \App\Support\AddonRegistry::active('trading-management-addon'))
                            <a href="{{ route('user.trading.configuration.index', ['tab' => 'risk-presets']) }}" class="btn btn-light btn-sm">
                                <i class="las la-cog me-1"></i> {{ __('Create Trading Preset') }}
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="row g-3 mt-3">
                    @if(!$hasActivePlan)
                    <div class="col-md-6 col-lg-3">
                        <div class="quick-action-item bg-white bg-opacity-10 rounded p-3 text-center">
                            <i class="las la-clipboard-list mb-2" style="font-size: 32px;"></i>
                            <h6 class="mb-1 text-white">{{ __('Subscribe to Plan') }}</h6>
                            <p class="small mb-2 text-white-50">{{ __('Get access to trading signals') }}</p>
                            <a href="{{ route('user.plans') }}" class="btn btn-light btn-sm w-100">
                                {{ __('Subscribe Now') }}
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    @if($hasActivePlan && !$hasSignalSource && \App\Support\AddonRegistry::active('multi-channel-signal-addon'))
                    <div class="col-md-6 col-lg-3">
                        <div class="quick-action-item bg-white bg-opacity-10 rounded p-3 text-center">
                            <i class="las la-signal mb-2" style="font-size: 32px;"></i>
                            <h6 class="mb-1 text-white">{{ __('Connect Signal Source') }}</h6>
                            <p class="small mb-2 text-white-50">{{ __('Receive signals automatically') }}</p>
                            <a href="{{ route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']) }}" class="btn btn-light btn-sm w-100">
                                {{ __('Add Source') }}
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    @if($hasActivePlan && !$hasTradingConnection && \App\Support\AddonRegistry::active('trading-management-addon'))
                    <div class="col-md-6 col-lg-3">
                        <div class="quick-action-item bg-white bg-opacity-10 rounded p-3 text-center">
                            <i class="las la-bolt mb-2" style="font-size: 32px;"></i>
                            <h6 class="mb-1 text-white">{{ __('Setup Auto Trading') }}</h6>
                            <p class="small mb-2 text-white-50">{{ __('Automate your trading') }}</p>
                            <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}" class="btn btn-light btn-sm w-100">
                                {{ __('Setup Now') }}
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    @if($hasActivePlan && !$hasTradingPreset && \App\Support\AddonRegistry::active('trading-management-addon'))
                    <div class="col-md-6 col-lg-3">
                        <div class="quick-action-item bg-white bg-opacity-10 rounded p-3 text-center">
                            <i class="las la-cog mb-2" style="font-size: 32px;"></i>
                            <h6 class="mb-1 text-white">{{ __('Create Trading Preset') }}</h6>
                            <p class="small mb-2 text-white-50">{{ __('Manage risk & position sizing') }}</p>
                            <a href="{{ route('user.trading.configuration.index', ['tab' => 'risk-presets']) }}" class="btn btn-light btn-sm w-100">
                                {{ __('Create Preset') }}
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

