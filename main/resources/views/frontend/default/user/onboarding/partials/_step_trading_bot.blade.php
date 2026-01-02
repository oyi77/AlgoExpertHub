<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-robot" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Create Your First Trading Bot') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Create an automated trading bot that executes trades based on your strategy and risk management settings.') }}
    </p>
    
    <div class="alert alert-info mb-4">
        <i class="las la-info-circle me-2"></i>
        <strong>{{ __('What is a Trading Bot?') }}</strong>
        <p class="mb-0 mt-2">
            {{ __('A trading bot automatically executes trades when signals match your technical indicator filters and risk management rules. You can create multiple bots with different strategies.') }}
        </p>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-exchange-alt text-primary mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Step 1') }}</h6>
                <p class="small text-muted mb-0">{{ __('Select Exchange Connection') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-shield-alt text-success mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Step 2') }}</h6>
                <p class="small text-muted mb-0">{{ __('Choose Risk Preset') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-filter text-warning mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Step 3') }}</h6>
                <p class="small text-muted mb-0">{{ __('Add Filter Strategy') }}</p>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.trading-management.trading-bots.wizard.index') }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-robot me-2"></i> {{ __('Create Trading Bot') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('After creating your bot, return here to continue.') }}
        </p>
    </div>
</div>
