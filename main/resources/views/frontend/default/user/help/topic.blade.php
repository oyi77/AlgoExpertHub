@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-0">{{ $title }}</h4>
                <p class="text-muted mb-0">{{ __('Find answers to your questions') }}</p>
            </div>
            <a href="{{ route('user.help.index') }}" class="btn btn-outline-secondary">
                <i class="las la-arrow-left me-2"></i> {{ __('Back to Help Center') }}
            </a>
        </div>
    </div>
    
    <div class="col-12">
        <div class="sp_site_card">
            <div class="card-body">
                @if($topic === 'general')
                    <h5 class="mb-3">{{ __('General Help') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('Getting Started') }}</h6>
                        <p>{{ __('Welcome to AlgoExpert Hub! This platform helps you receive and execute trading signals automatically.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Key Features') }}</h6>
                        <ul>
                            <li>{{ __('Multi-Channel Signal: Receive signals from Telegram, API, RSS, and web scraping') }}</li>
                            <li>{{ __('Auto Trading: Automatically execute signals on your connected exchange or broker') }}</li>
                            <li>{{ __('Trading Presets: Manage risk and position sizing with customizable presets') }}</li>
                            <li>{{ __('Marketplaces: Browse and clone trading strategies, presets, and bots') }}</li>
                        </ul>
                        
                        <h6 class="mt-4">{{ __('Need More Help?') }}</h6>
                        <p>{{ __('If you can\'t find what you\'re looking for, please contact our support team.') }}</p>
                        <a href="{{ route('user.ticket.create') ?? '#' }}" class="btn sp_theme_btn">
                            <i class="las la-headset me-2"></i> {{ __('Contact Support') }}
                        </a>
                    </div>
                @elseif($topic === 'signals')
                    <h5 class="mb-3">{{ __('Trading Signals') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('What are Trading Signals?') }}</h6>
                        <p>{{ __('Trading signals are buy/sell recommendations for specific currency pairs or assets. They include entry price, stop loss, and take profit levels.') }}</p>
                        
                        <h6 class="mt-4">{{ __('How to Receive Signals') }}</h6>
                        <ol>
                            <li>{{ __('Subscribe to a plan that includes signals') }}</li>
                            <li>{{ __('Connect a signal source (Telegram, API, RSS, or web scraping') }}</li>
                            <li>{{ __('Signals will appear in your dashboard and can be executed automatically') }}</li>
                        </ol>
                        
                        <h6 class="mt-4">{{ __('Signal Sources') }}</h6>
                        <p>{{ __('You can connect multiple signal sources:') }}</p>
                        <ul>
                            <li><strong>{{ __('Telegram Bot') }}</strong>: {{ __('Connect Telegram channels for automatic forwarding') }}</li>
                            <li><strong>{{ __('API Integration') }}</strong>: {{ __('Connect external APIs for signal ingestion') }}</li>
                            <li><strong>{{ __('RSS Feed') }}</strong>: {{ __('Subscribe to RSS feeds for signals') }}</li>
                            <li><strong>{{ __('Web Scraping') }}</strong>: {{ __('Scrape websites for trading signals') }}</li>
                        </ul>
                    </div>
                @elseif($topic === 'auto-trading')
                    <h5 class="mb-3">{{ __('Auto Trading') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('What is Auto Trading?') }}</h6>
                        <p>{{ __('Auto trading automatically executes trading signals on your connected exchange or broker without manual intervention.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Setting Up Auto Trading') }}</h6>
                        <ol>
                            <li>{{ __('Go to Trading Operations > Connections') }}</li>
                            <li>{{ __('Create a new connection to your exchange or broker') }}</li>
                            <li>{{ __('Configure position sizing and risk management') }}</li>
                            <li>{{ __('Enable auto trading for the connection') }}</li>
                        </ol>
                        
                        <h6 class="mt-4">{{ __('Supported Exchanges') }}</h6>
                        <p>{{ __('We support 100+ crypto exchanges via CCXT and Forex brokers via MT4/MT5 integration.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Risk Warning') }}</h6>
                        <div class="alert alert-warning">
                            <i class="las la-exclamation-triangle me-2"></i>
                            <strong>{{ __('Important:') }}</strong>
                            {{ __('Auto trading involves risk. Always start with paper trading mode to test your setup before using real funds.') }}
                        </div>
                    </div>
                @elseif($topic === 'presets')
                    <h5 class="mb-3">{{ __('Trading Presets') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('What are Trading Presets?') }}</h6>
                        <p>{{ __('Trading presets are pre-configured settings for risk management and position sizing. They help you maintain consistent trading rules.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Creating a Preset') }}</h6>
                        <ol>
                            <li>{{ __('Go to Trading Configuration > Risk Presets') }}</li>
                            <li>{{ __('Click "Create Preset"') }}</li>
                            <li>{{ __('Configure position sizing (fixed, percentage, or fixed amount') }}</li>
                            <li>{{ __('Set stop loss and take profit levels') }}</li>
                            <li>{{ __('Enable advanced features (multi-TP, break-even, trailing stop) if needed') }}</li>
                        </ol>
                        
                        <h6 class="mt-4">{{ __('Marketplace Presets') }}</h6>
                        <p>{{ __('You can browse and clone presets from the marketplace, or create your own custom preset.') }}</p>
                    </div>
                @elseif($topic === 'marketplaces')
                    <h5 class="mb-3">{{ __('Marketplaces') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('What is the Marketplace?') }}</h6>
                        <p>{{ __('The marketplace is where you can browse, clone, and subscribe to trading presets, strategies, AI profiles, copy trading traders, and bots created by other users.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Marketplace Categories') }}</h6>
                        <ul>
                            <li><strong>{{ __('Trading Presets') }}</strong>: {{ __('Risk management and position sizing configurations') }}</li>
                            <li><strong>{{ __('Filter Strategies') }}</strong>: {{ __('Signal filtering and selection strategies') }}</li>
                            <li><strong>{{ __('AI Model Profiles') }}</strong>: {{ __('AI-powered trading configurations') }}</li>
                            <li><strong>{{ __('Copy Trading') }}</strong>: {{ __('Follow successful traders') }}</li>
                            <li><strong>{{ __('Bot Marketplace') }}</strong>: {{ __('Trading bots and templates') }}</li>
                        </ul>
                        
                        <h6 class="mt-4">{{ __('How to Use') }}</h6>
                        <p>{{ __('Browse items by category, read reviews and ratings, then clone or subscribe to items you like.') }}</p>
                    </div>
                @elseif($topic === 'wallet')
                    <h5 class="mb-3">{{ __('Wallet & Payments') }}</h5>
                    <div class="help-content">
                        <h6>{{ __('Wallet System') }}</h6>
                        <p>{{ __('Your wallet is your account balance. You can deposit funds, withdraw earnings, and transfer money between accounts.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Deposits') }}</h6>
                        <p>{{ __('You can deposit using:') }}</p>
                        <ul>
                            <li>{{ __('Bank transfer') }}</li>
                            <li>{{ __('Credit/Debit card (PayPal, Stripe, etc.)') }}</li>
                            <li>{{ __('Cryptocurrency (BTC, ETH, USDT, etc.)') }}</li>
                        </ul>
                        
                        <h6 class="mt-4">{{ __('Withdrawals') }}</h6>
                        <p>{{ __('Withdrawal requests are reviewed by administrators. Processing time varies by payment method.') }}</p>
                        
                        <h6 class="mt-4">{{ __('Transaction History') }}</h6>
                        <p>{{ __('View all your deposits, withdrawals, transfers, and other transactions in one place.') }}</p>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="las la-info-circle me-2"></i>
                        {{ __('Help content for this topic is coming soon.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

