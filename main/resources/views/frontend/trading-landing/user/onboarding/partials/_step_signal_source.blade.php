<div class="onboarding-step-content">
    <div class="text-center mb-4">
        <i class="las la-signal" style="font-size: 64px; color: var(--base-color);"></i>
    </div>
    <h4 class="text-center mb-3">{{ __('Connect Signal Source') }}</h4>
    <p class="text-muted text-center mb-4">
        {{ __('Connect a signal source to automatically receive trading signals from Telegram, API, RSS, or web scraping.') }}
    </p>
    
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="p-3 border rounded h-100">
                <i class="las la-paper-plane text-primary mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Telegram Bot') }}</h6>
                <p class="small text-muted mb-0">{{ __('Connect Telegram channels for automatic signal forwarding') }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 border rounded h-100">
                <i class="las la-plug text-success mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('API Integration') }}</h6>
                <p class="small text-muted mb-0">{{ __('Connect external APIs for signal ingestion') }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 border rounded h-100">
                <i class="las la-rss text-warning mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('RSS Feed') }}</h6>
                <p class="small text-muted mb-0">{{ __('Subscribe to RSS feeds for signals') }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 border rounded h-100">
                <i class="las la-globe text-info mb-2" style="font-size: 32px;"></i>
                <h6 class="mb-2">{{ __('Web Scraping') }}</h6>
                <p class="small text-muted mb-0">{{ __('Scrape websites for trading signals') }}</p>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="{{ route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']) }}" class="btn sp_theme_btn btn-lg" target="_blank">
            <i class="las la-signal me-2"></i> {{ __('Add Signal Source') }}
        </a>
        <p class="text-muted small mt-2 mb-0">
            {{ __('After adding a source, return here to continue.') }}
        </p>
    </div>
</div>

