<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-user-circle" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Complete Your Profile') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Let\'s start by completing your profile information. This helps us personalize your experience.') }}
    </p>
    
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center p-3 border rounded">
                <i class="las la-check-circle text-success me-3" style="font-size: 24px;"></i>
                <div>
                    <strong>{{ __('Profile Information') }}</strong>
                    <p class="mb-0 small text-muted">{{ __('Name, email, and contact details') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center p-3 border rounded">
                <i class="las la-check-circle text-success me-3" style="font-size: 24px;"></i>
                <div>
                    <strong>{{ __('Profile Picture') }}</strong>
                    <p class="mb-0 small text-muted">{{ __('Optional: Upload your photo') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.profile') }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-user me-2"></i> {{ __('Go to Profile Settings') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('After completing your profile, return here to continue.') }}
        </p>
    </div>
</div>

