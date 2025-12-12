<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-credit-card" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Make Your First Deposit') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Fund your wallet to start trading. You can deposit using various payment methods.') }}
    </p>
    
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-university text-primary mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Bank Transfer') }}</h6>
                <p class="small text-muted mb-0">{{ __('Direct bank transfer') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-credit-card text-success mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Credit/Debit Card') }}</h6>
                <p class="small text-muted mb-0">{{ __('PayPal, Stripe, and more') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <i class="las la-coins text-warning mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Cryptocurrency') }}</h6>
                <p class="small text-muted mb-0">{{ __('BTC, ETH, USDT, and more') }}</p>
            </div>
        </div>
    </div>
    
    <div class="alert alert-success mb-4">
        <i class="las la-check-circle me-2"></i>
        <strong>{{ __('Secure & Fast:') }}</strong>
        {{ __('All transactions are encrypted and processed securely.') }}
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.deposit') }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-credit-card me-2"></i> {{ __('Make Deposit') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('This step is optional. You can skip it and deposit later.') }}
        </p>
    </div>
</div>

