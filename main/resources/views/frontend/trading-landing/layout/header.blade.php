<header class="trading-landing-header" role="banner">
    <nav class="trading-navbar">
        <div class="container">
            <div class="navbar-content">
                <a class="navbar-logo" href="{{ route('home') }}" aria-label="{{ __('Home') }}">
                    <img src="{{ Config::getFile('logo', Config::config()->logo) }}" alt="{{ Config::config()->appname ?? 'Logo' }}" loading="eager">
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="{{ __('Toggle navigation menu') }}">
                    <span class="menu-toggle"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-menu">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li class="has-dropdown">
                            <a href="#">Markets <i class="las la-angle-down"></i></a>
                        </li>
                        <li class="has-dropdown">
                            <a href="#">Trading Tools <i class="las la-angle-down"></i></a>
                        </li>
                        <li><a href="#">Account</a></li>
                        <li><a href="#">Education</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                    
                    <div class="navbar-actions">
                        <a href="{{ route('user.register') }}" class="btn btn-open-account">Open Account</a>
                        <a href="{{ route('user.login') }}" class="btn btn-login">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

