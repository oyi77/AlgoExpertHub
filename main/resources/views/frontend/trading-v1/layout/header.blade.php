<header class="tv-header" id="header">
    <div class="tv-container">
        <nav class="tv-navbar">
            <!-- Mobile Toggle -->
            <button class="tv-navbar-toggle" id="navToggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Logo -->
            <a href="{{ route('home') }}" class="tv-navbar-logo">
                <img src="{{ Config::getFile('logo', Config::config()->logo ?? '') }}" alt="{{ Config::config()->appname ?? 'Logo' }}">
            </a>
            
            <!-- Menu -->
            <div class="tv-navbar-menu" id="navMenu">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                @auth
                    @if(Route::has('user.signal.all'))
                        <a href="{{ route('user.signal.all') }}">Signals</a>
                    @endif
                    @if(Route::has('user.plans'))
                        <a href="{{ route('user.plans') }}">Plans</a>
                    @endif
                @else
                    <a href="{{ route('home') }}#market-trends">Markets</a>
                    <a href="{{ route('home') }}#why-choose-us">Features</a>
                    <a href="{{ route('home') }}#account-types">Pricing</a>
                @endauth
                <a href="{{ route('home') }}#footer-cta">Contact</a>
            </div>
            
            <!-- Actions -->
            <div class="tv-navbar-actions">
                @auth
                    <a href="{{ route('user.dashboard') }}" class="tv-btn tv-btn-primary">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('user.register') }}" class="tv-btn tv-btn-outline">Open Account</a>
                    <a href="{{ route('user.login') }}" class="tv-btn tv-btn-primary">Login</a>
                @endauth
            </div>
        </nav>
    </div>
</header>

