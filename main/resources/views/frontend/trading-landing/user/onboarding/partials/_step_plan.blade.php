<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-clipboard-list" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Subscribe to a Plan') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Choose a subscription plan to unlock trading signals and advanced features.') }}
    </p>
    
    <div class="alert alert-info mb-4">
        <i class="las la-info-circle me-2"></i>
        <strong>{{ __('Why subscribe?') }}</strong>
        <ul class="mb-0 mt-2">
            <li>{{ __('Access to premium trading signals') }}</li>
            <li>{{ __('Real-time market analysis') }}</li>
            <li>{{ __('Advanced trading tools') }}</li>
            <li>{{ __('Priority support') }}</li>
        </ul>
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.plans') }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-clipboard-list me-2"></i> {{ __('Browse Plans') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('After subscribing, return here to continue.') }}
        </p>
    </div>
</div>

