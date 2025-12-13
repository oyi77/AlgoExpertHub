<header class="trading-landing-header" role="banner">
    <nav class="trading-navbar">
        <div class="container">
            <div class="navbar-content">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="{{ __('Toggle navigation menu') }}">
                    <span class="menu-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                
                <a class="navbar-logo" href="{{ route('home') }}" aria-label="{{ __('Home') }}">
                    <img src="{{ Config::getFile('logo', Config::config()->logo) }}" alt="{{ Config::config()->appname ?? 'Logo' }}" loading="eager">
                </a>
                
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-menu">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        @auth
                            @if(Route::has('user.signal.all'))
                                <li><a href="{{ route('user.signal.all') }}">Signals</a></li>
                            @endif
                            @if(Route::has('user.plans'))
                                <li><a href="{{ route('user.plans') }}">Plans</a></li>
                            @endif
                            @if(Route::has('user.trading.operations.index'))
                                <li><a href="{{ route('user.trading.operations.index') }}">Trading</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('home') }}#trading-instruments">Markets</a></li>
                            <li><a href="{{ route('home') }}#why-choose-us">Features</a></li>
                            <li><a href="{{ route('home') }}#account-types">Pricing</a></li>
                        @endauth
                        @if(Route::has('user.ticket.index'))
                            <li><a href="{{ route('user.ticket.index') }}">Support</a></li>
                        @endif
                    </ul>
                </div>
                
                <div class="navbar-actions">
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="btn btn-dashboard-icon" aria-label="{{ __('Dashboard') }}" title="{{ __('Dashboard') }}">
                            <i class="fas fa-home"></i>
                        </a>
                    @else
                        <a href="{{ route('user.login') }}" class="btn btn-login-icon" aria-label="{{ __('Login') }}" title="{{ __('Login') }}">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>

