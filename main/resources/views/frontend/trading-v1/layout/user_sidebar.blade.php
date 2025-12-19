<aside class="tv-sidebar" id="sidebar">
    <!-- Logo -->
    <div class="tv-sidebar-logo">
        <a href="{{ route('home') }}">
            <img src="{{ Config::getFile('logo', Config::config()->logo ?? '') }}" alt="{{ Config::config()->appname ?? 'Logo' }}">
        </a>
        <button class="tv-sidebar-close" id="sidebarClose" aria-label="Close Sidebar">
            <i class="las la-times"></i>
        </button>
    </div>
    
    <!-- Menu -->
    <nav class="tv-sidebar-menu">
        <!-- HOME -->
        <div class="tv-menu-label">
            <i class="las la-home"></i>
            <span>HOME</span>
        </div>
        
        <a href="{{ route('user.dashboard') }}" class="tv-sidebar-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            <i class="las la-th-large"></i>
            <span>Dashboard</span>
        </a>
        
        
        <!-- TRADING -->
        <div class="tv-menu-label">
            <i class="las la-chart-line"></i>
            <span>TRADING</span>
        </div>
        
        <a href="{{ route('user.terminal.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.terminal.*') ? 'active' : '' }}">
            <i class="las la-chart-area"></i>
            <span>Trading Terminal</span>
        </a>


        
        <!-- TRADING CONSOLE -->
        @php
            $hasTradingAddon = false;
            try {
                $hasTradingAddon = \App\Support\AddonRegistry::active('trading-management-addon');
            } catch (\Exception $e) {}
        @endphp
        
        @if($hasTradingAddon)
        <div class="tv-menu-label">
            <i class="las la-robot"></i>
            <span>TRADING BOTS</span>
        </div>
        
        @if(Route::has('user.trading.operations.index'))
            <a href="{{ route('user.trading.operations.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.operations.*') ? 'active' : '' }}">
                <i class="las la-robot"></i>
                <span>My Bots</span>
            </a>
        @endif
        
        @if(Route::has('user.trading.multi-channel-signal.index'))
            <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.multi-channel-signal.*') ? 'active' : '' }}">
                <i class="las la-broadcast-tower"></i>
                <span>Signal Center</span>
            </a>
        @endif
        
        @if(Route::has('user.trading.configuration.index'))
            <a href="{{ route('user.trading.configuration.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.configuration.*') ? 'active' : '' }}">
                <i class="las la-cog"></i>
                <span>Trading Configuration</span>
            </a>
        @endif
        
        <!-- MARKET & ANALYSIS -->
        <div class="tv-menu-label">
            <i class="las la-brain"></i>
            <span>MARKET & ANALYSIS</span>
        </div>
        
        @if(Route::has('user.trading.execution-log.index'))
            <a href="{{ route('user.trading.execution-log.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.execution-log.*') ? 'active' : '' }}">
                <i class="las la-chart-bar"></i>
                <span>Performance Analytics</span>
            </a>
        @endif
        
        @if(Route::has('user.trading.backtesting.index'))
            <a href="{{ route('user.trading.backtesting.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.backtesting.*') ? 'active' : '' }}">
                <i class="las la-history"></i>
                <span>Backtesting Center</span>
            </a>
        @endif
        
        <!-- MARKETPLACE -->
        <div class="tv-menu-label">
            <i class="las la-store"></i>
            <span>MARKETPLACE</span>
        </div>
        
        @if(Route::has('user.trading.marketplaces.index'))
            <a href="{{ route('user.trading.marketplaces.index') }}" class="tv-sidebar-link {{ request()->routeIs('user.trading.marketplaces.*') ? 'active' : '' }}">
                <i class="las la-store"></i>
                <span>Marketplace</span>
            </a>
        @endif
        @endif
        
        <!-- ACCOUNT -->
        <div class="tv-menu-label">
            <i class="las la-user-circle"></i>
            <span>ACCOUNT</span>
        </div>
        
        @if(Route::has('user.subscription.log'))
            <a href="{{ route('user.subscription.log') }}" class="tv-sidebar-link {{ request()->routeIs('user.subscription.log') ? 'active' : '' }}">
                <i class="las la-id-card"></i>
                <span>My Subscription</span>
            </a>
        @endif
        
        @if(Route::has('user.plans'))
            <a href="{{ route('user.plans') }}" class="tv-sidebar-link {{ request()->routeIs('user.plans') ? 'active' : '' }}">
                <i class="las la-box"></i>
                <span>Plans</span>
            </a>
        @endif
        
        <!-- Wallet Submenu -->
        @php
            $walletRoutes = ['user.deposit', 'user.withdraw', 'user.transfer_money', 'user.transaction'];
            $walletActive = collect($walletRoutes)->contains(fn($r) => request()->routeIs($r));
        @endphp
        <div class="tv-sidebar-submenu {{ $walletActive ? 'open' : '' }}">
            <a href="javascript:void(0)" class="tv-sidebar-link tv-has-submenu {{ $walletActive ? 'active' : '' }}">
                <i class="las la-wallet"></i>
                <span>Wallet</span>
                <i class="las la-angle-down tv-submenu-arrow"></i>
            </a>
            <div class="tv-submenu-items">
                @if(Route::has('user.deposit'))
                    <a href="{{ route('user.deposit') }}" class="tv-submenu-link {{ request()->routeIs('user.deposit') ? 'active' : '' }}">
                        <i class="las la-credit-card"></i>
                        <span>Deposit</span>
                    </a>
                @endif
                
                @if(Route::has('user.withdraw'))
                    <a href="{{ route('user.withdraw') }}" class="tv-submenu-link {{ request()->routeIs('user.withdraw') ? 'active' : '' }}">
                        <i class="las la-hand-holding-usd"></i>
                        <span>Withdraw</span>
                    </a>
                @endif
                
                @if(Route::has('user.transfer_money'))
                    <a href="{{ route('user.transfer_money') }}" class="tv-submenu-link {{ request()->routeIs('user.transfer_money') ? 'active' : '' }}">
                        <i class="las la-exchange-alt"></i>
                        <span>Transfer Money</span>
                    </a>
                @endif
                
                @if(Route::has('user.transaction'))
                    <a href="{{ route('user.transaction') }}" class="tv-submenu-link {{ request()->routeIs('user.transaction') ? 'active' : '' }}">
                        <i class="las la-history"></i>
                        <span>Transaction History</span>
                    </a>
                @endif
            </div>
        </div>
        
        @if(Route::has('user.deposit.log'))
            <a href="{{ route('user.deposit.log') }}" class="tv-sidebar-link {{ request()->routeIs('user.deposit.log') ? 'active' : '' }}">
                <i class="las la-wallet"></i>
                <span>Deposit Log</span>
            </a>
        @endif
        
        @if(Route::has('user.withdraw.log'))
            <a href="{{ route('user.withdraw.log') }}" class="tv-sidebar-link {{ request()->routeIs('user.withdraw.*') ? 'active' : '' }}">
                <i class="las la-money-bill-wave"></i>
                <span>Withdrawal Log</span>
            </a>
        @endif
        
        @if(Route::has('user.invest.log'))
            <a href="{{ route('user.invest.log') }}" class="tv-sidebar-link {{ request()->routeIs('user.invest.log') ? 'active' : '' }}">
                <i class="las la-chart-bar"></i>
                <span>Investment Log</span>
            </a>
        @endif
        
        <a href="{{ route('user.profile') }}" class="tv-sidebar-link {{ request()->routeIs('user.profile') ? 'active' : '' }}">
            <i class="las la-user-circle"></i>
            <span>Profile Settings</span>
        </a>
        
        @if(Route::has('user.changepassword'))
            <a href="{{ route('user.changepassword') }}" class="tv-sidebar-link {{ request()->routeIs('user.changepassword') ? 'active' : '' }}">
                <i class="las la-key"></i>
                <span>Change Password</span>
            </a>
        @endif
        
        @if(Route::has('user.2fa.settings'))
            <a href="{{ route('user.2fa.settings') }}" class="tv-sidebar-link {{ request()->routeIs('user.2fa.*') ? 'active' : '' }}">
                <i class="las la-shield-alt"></i>
                <span>2FA Security</span>
            </a>
        @endif
        
        @if(Route::has('user.refferal'))
            <a href="{{ route('user.refferal') }}" class="tv-sidebar-link {{ request()->routeIs('user.refferal') ? 'active' : '' }}">
                <i class="las la-users"></i>
                <span>Referral Log</span>
            </a>
        @endif
        
        <!-- SUPPORT -->
        <div class="tv-menu-label">
            <i class="las la-life-ring"></i>
            <span>SUPPORT</span>
        </div>
        
        @if(Route::has('user.ticket'))
            <a href="{{ route('user.ticket') }}" class="tv-sidebar-link {{ request()->routeIs('user.ticket*') ? 'active' : '' }}">
                <i class="las la-ticket-alt"></i>
                <span>Support Tickets</span>
            </a>
        @endif
        
        <!-- OTHERS -->
        
    </nav>
</aside>
