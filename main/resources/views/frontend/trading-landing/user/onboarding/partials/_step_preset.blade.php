<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-cog" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Create Trading Preset') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Configure your risk management and position sizing preferences with a trading preset.') }}
    </p>
    
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-dollar-sign text-primary mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Position Sizing') }}</h6>
                <p class="small text-muted mb-0">{{ __('Fixed, percentage, or fixed amount') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-shield-alt text-success mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Stop Loss / Take Profit') }}</h6>
                <p class="small text-muted mb-0">{{ __('Multi-TP, break-even, trailing stop') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-layer-group text-warning mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Advanced Features') }}</h6>
                <p class="small text-muted mb-0">{{ __('Layering, hedging, and more') }}</p>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mb-4">
        <i class="las la-lightbulb me-2"></i>
        <strong>{{ __('Tip:') }}</strong>
        {{ __('You can browse marketplace presets or create your own custom preset.') }}
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.trading.configuration.index', ['tab' => 'risk-presets']) }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-cog me-2"></i> {{ __('Create Trading Preset') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('After creating a preset, return here to continue.') }}
        </p>
    </div>
</div>

