@php
    $plan_expired_at = now();
@endphp

@if (auth()->user()->currentplan)
    @php
        $is_subscribe = auth()
            ->user()
            ->currentplan()
            ->where('is_current', 1)
            ->first();
        
        if ($is_subscribe) {
            $plan_expired_at = $is_subscribe->plan_expired_at;
        }
    @endphp
@endif



@php
    $multiChannelUserModuleEnabled = \App\Support\AddonRegistry::active('multi-channel-signal-addon') && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'user_ui');
    // Trading Management Addon modules
    $tradingManagementEnabled = \App\Support\AddonRegistry::active('trading-management-addon');
    $executionEngineUserModuleEnabled = $tradingManagementEnabled && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution');
    $copyTradingUserModuleEnabled = $tradingManagementEnabled 
        && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'copy_trading')
        && $executionEngineUserModuleEnabled;
    $tradingPresetUserModuleEnabled = $tradingManagementEnabled && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management');
    $filterStrategyUserModuleEnabled = $tradingManagementEnabled && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'filter_strategy');
    $aiTradingUserModuleEnabled = $tradingManagementEnabled && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'ai_analysis');
    $srmUserModuleEnabled = $tradingManagementEnabled && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management');
@endphp

<aside class="user-sidebar">
    <a href="{{ route('user.dashboard') }}" class="site-logo">
        <img src="{{ Config::getFile('dark_logo', Config::config()->dark_logo, true) }}" alt="image">
    </a>

    <div class="user-sidebar-bottom">
        <div id="countdown"></div>
    </div>

    <ul class="sidebar-menu">
        <li class="{{ Config::singleMenu('user.dashboard') }}"><a href="{{ route('user.dashboard') }}" class="active"><i
                    class="fas fa-home"></i>
                {{ __('Dashboard') }}</a></li>
        <li class="{{ Config::singleMenu('user.signal.all') }}"><a href="{{ route('user.signal.all') }}"><i
                    class="fas fa-chart-bar"></i> {{ __('All Signal') }}</a></li>

        @if ($multiChannelUserModuleEnabled)
            <li class="{{ Config::singleMenu('user.signal-sources.index') }}"><a href="{{ route('user.signal-sources.index') }}"><i
                        class="fas fa-plug"></i> {{ __('Signal Sources') }}</a></li>
            <li class="{{ Config::singleMenu('user.channel-forwarding.index') }}"><a href="{{ route('user.channel-forwarding.index') }}"><i
                        class="fas fa-share-alt"></i> {{ __('Channel Forwarding') }}</a></li>
        @endif

        @if ($executionEngineUserModuleEnabled)
            @if (Route::has('user.execution-connections.index'))
            <li class="{{ Config::singleMenu('user.execution-connections.index') }}"><a href="{{ route('user.execution-connections.index') }}"><i
                        class="fas fa-exchange-alt"></i> {{ __('Auto Trading') }}</a></li>
            @endif
            @if (Route::has('user.execution-analytics.index'))
            <li class="{{ Config::singleMenu('user.execution-analytics.index') }}"><a href="{{ route('user.execution-analytics.index') }}"><i
                        class="fas fa-chart-bar"></i> {{ __('Trading Analytics') }}</a></li>
            @endif
        @endif

        @if ($tradingPresetUserModuleEnabled)
            @php
                $presetRoutes = [];
                if (Route::has('user.trading-presets.index')) $presetRoutes[] = route('user.trading-presets.index');
                if (Route::has('user.trading-presets.marketplace')) $presetRoutes[] = route('user.trading-presets.marketplace');
                if (Route::has('user.trading-presets.create')) $presetRoutes[] = route('user.trading-presets.create');
            @endphp
            <li class="has_submenu {{ in_array(url()->current(), $presetRoutes) ? 'open' : '' }}">
                <a href="#0"><i class="fas fa-cog"></i> {{ __('Trading Presets') }}</a>
                <ul class="submenu">
                    @if (Route::has('user.trading-presets.index'))
                    <li class="{{ Config::singleMenu('user.trading-presets.index') }}">
                        <a href="{{ route('user.trading-presets.index') }}">{{ __('My Presets') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.trading-presets.marketplace'))
                    <li class="{{ Config::singleMenu('user.trading-presets.marketplace') }}">
                        <a href="{{ route('user.trading-presets.marketplace') }}">{{ __('Marketplace') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($filterStrategyUserModuleEnabled)
            @php
                $filterRoutes = [];
                if (Route::has('user.filter-strategies.index')) $filterRoutes[] = route('user.filter-strategies.index');
                if (Route::has('user.filter-strategies.marketplace')) $filterRoutes[] = route('user.filter-strategies.marketplace');
                if (Route::has('user.filter-strategies.create')) $filterRoutes[] = route('user.filter-strategies.create');
            @endphp
            <li class="has_submenu {{ in_array(url()->current(), $filterRoutes) ? 'open' : '' }}">
                <a href="#0"><i class="fas fa-filter"></i> {{ __('Filter Strategies') }}</a>
                <ul class="submenu">
                    @if (Route::has('user.filter-strategies.index'))
                    <li class="{{ Config::singleMenu('user.filter-strategies.index') }}">
                        <a href="{{ route('user.filter-strategies.index') }}">{{ __('My Strategies') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.filter-strategies.marketplace'))
                    <li class="{{ Config::singleMenu('user.filter-strategies.marketplace') }}">
                        <a href="{{ route('user.filter-strategies.marketplace') }}">{{ __('Marketplace') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($aiTradingUserModuleEnabled)
            @php
                $aiRoutes = [];
                if (Route::has('user.ai-model-profiles.index')) $aiRoutes[] = route('user.ai-model-profiles.index');
                if (Route::has('user.ai-model-profiles.marketplace')) $aiRoutes[] = route('user.ai-model-profiles.marketplace');
                if (Route::has('user.ai-model-profiles.create')) $aiRoutes[] = route('user.ai-model-profiles.create');
            @endphp
            <li class="has_submenu {{ in_array(url()->current(), $aiRoutes) ? 'open' : '' }}">
                <a href="#0"><i class="fas fa-robot"></i> {{ __('AI Model Profiles') }}</a>
                <ul class="submenu">
                    @if (Route::has('user.ai-model-profiles.index'))
                    <li class="{{ Config::singleMenu('user.ai-model-profiles.index') }}">
                        <a href="{{ route('user.ai-model-profiles.index') }}">{{ __('My Profiles') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.ai-model-profiles.marketplace'))
                    <li class="{{ Config::singleMenu('user.ai-model-profiles.marketplace') }}">
                        <a href="{{ route('user.ai-model-profiles.marketplace') }}">{{ __('Marketplace') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($copyTradingUserModuleEnabled)
            @php
                $copyTradingRoutes = [];
                if (Route::has('user.copy-trading.settings')) $copyTradingRoutes[] = route('user.copy-trading.settings');
                if (Route::has('user.copy-trading.traders.index')) $copyTradingRoutes[] = route('user.copy-trading.traders.index');
                if (Route::has('user.copy-trading.subscriptions.index')) $copyTradingRoutes[] = route('user.copy-trading.subscriptions.index');
                if (Route::has('user.copy-trading.history.index')) $copyTradingRoutes[] = route('user.copy-trading.history.index');
            @endphp
            <li class="has_submenu {{ in_array(url()->current(), $copyTradingRoutes) ? 'open' : '' }}">
                <a href="#0"><i class="fas fa-copy"></i> {{ __('Copy Trading') }}</a>
                <ul class="submenu">
                    @if (Route::has('user.copy-trading.settings'))
                    <li class="{{ Config::singleMenu('user.copy-trading.settings') }}">
                        <a href="{{ route('user.copy-trading.settings') }}">{{ __('Settings') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.copy-trading.traders.index'))
                    <li class="{{ Config::singleMenu('user.copy-trading.traders.index') }}">
                        <a href="{{ route('user.copy-trading.traders.index') }}">{{ __('Browse Traders') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.copy-trading.subscriptions.index'))
                    <li class="{{ Config::singleMenu('user.copy-trading.subscriptions.index') }}">
                        <a href="{{ route('user.copy-trading.subscriptions.index') }}">{{ __('My Subscriptions') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.copy-trading.history.index'))
                    <li class="{{ Config::singleMenu('user.copy-trading.history.index') }}">
                        <a href="{{ route('user.copy-trading.history.index') }}">{{ __('History') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($srmUserModuleEnabled)
            @php
                $srmRoutes = [];
                if (Route::has('user.srm.dashboard')) $srmRoutes[] = route('user.srm.dashboard');
                if (Route::has('user.srm.adjustments.index')) $srmRoutes[] = route('user.srm.adjustments.index');
                if (Route::has('user.srm.insights.index')) $srmRoutes[] = route('user.srm.insights.index');
            @endphp
            <li class="has_submenu {{ in_array(url()->current(), $srmRoutes) ? 'open' : '' }}">
                <a href="#0"><i class="fas fa-shield-alt"></i> {{ __('Smart Risk Management') }}</a>
                <ul class="submenu">
                    @if (Route::has('user.srm.dashboard'))
                    <li class="{{ Config::singleMenu('user.srm.dashboard') }}">
                        <a href="{{ route('user.srm.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.srm.adjustments.index'))
                    <li class="{{ Config::singleMenu('user.srm.adjustments.index') }}">
                        <a href="{{ route('user.srm.adjustments.index') }}">{{ __('Adjustments') }}</a>
                    </li>
                    @endif
                    @if (Route::has('user.srm.insights.index'))
                    <li class="{{ Config::singleMenu('user.srm.insights.index') }}">
                        <a href="{{ route('user.srm.insights.index') }}">{{ __('Insights') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
        @endif

        <li><a href="{{ route('user.trade') }}" class="{{ Config::singleMenu('user.trade') }}"><i
                    class="fas fa-chart-line"></i></i>
                {{ __('Trade') }}</a></li>

        <li class="{{ Config::singleMenu('user.plans') }}"><a href="{{ route('user.plans') }}"><i
                    class="fas fa-clipboard-list"></i>{{ __('Plans') }}</a></li>

        <li class="{{ Config::singleMenu('user.deposit') }}"><a href="{{ route('user.deposit') }}"><i
                    class="fas fa-credit-card"></i>{{ __('Deposit Now') }}</a></li>

        <li class="{{ Config::singleMenu('user.withdraw') }}"><a href="{{ route('user.withdraw') }}"><i
                    class="fas fa-hand-holding-usd"></i> {{ __('Withdraw') }}</a></li>

        <li class="{{ Config::singleMenu('user.transfer_money') }}"><a href="{{ route('user.transfer_money') }}"><i
                    class="fas fa-exchange-alt"></i> {{ __('Transfer Money') }}</a></li>


        <li
            class="has_submenu {{ in_array(url()->current(), [route('user.deposit.log'), route('user.withdraw.all'), route('user.invest.log'), route('user.transaction.log'), route('user.transfer_money.log'), route('user.receive_money.log'), route('user.commision'), route('user.subscription')]) ? 'open' : '' }}">
            <a href="#0"><i class="fas fa-chart-bar"></i> {{ __('Report') }}</a>
            <ul class="submenu">
                <li class="{{ Config::singleMenu('user.deposit.log') }}">
                    <a href="{{ route('user.deposit.log') }}">{{ __('Deposit Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.withdraw.all') }}">
                    <a href="{{ route('user.withdraw.all') }}">{{ __('Withdraw Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.invest.log') }}">
                    <a href="{{ route('user.invest.log') }}">{{ __('Investment Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.transaction.log') }}">
                    <a href="{{ route('user.transaction.log') }}">{{ __('Transaction Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.transfer_money.log') }}">
                    <a href="{{ route('user.transfer_money.log') }}">{{ __('Transfer Money Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.receive_money.log') }}">
                    <a href="{{ route('user.receive_money.log') }}">{{ __('Receive Money Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.commision') }}">
                    <a href="{{ route('user.commision') }}">{{ __('commision Log') }}</a>
                </li>

                <li class="{{ Config::singleMenu('user.subscription') }}">
                    <a href="{{ route('user.subscription') }}">{{ __('Subscription Log') }}</a>
                </li>
            </ul>
        </li>

        <li class="{{ Config::singleMenu('user.refferalLog') }}"><a href="{{ route('user.refferalLog') }}"><i
                    class="fas fa-user-cog"></i> {{ __('Refferal Log') }}</a></li>

        <li class="{{ Config::singleMenu('user.profile') }}"><a href="{{ route('user.profile') }}"><i
                    class="fas fa-user-cog"></i> {{ __('Profile Settings') }}</a></li>
        <li class="{{ Config::singleMenu('user.ticket.index') }}"><a href="{{ route('user.ticket.index') }}"><i
                    class="fas fa-ticket-alt"></i> {{ __('Support Ticket') }}</a></li>
        <li class="{{ Config::singleMenu('user.logout') }}"><a href="{{ route('user.logout') }}"><i
                    class="fas fa-sign-out-alt"></i> {{ __('Logout') }}</a></li>
    </ul>
</aside>

<!-- mobile bottom menu start -->
<div class="mobile-bottom-menu-wrapper">
    <ul class="mobile-bottom-menu">

        <li>
            <a href="{{ route('user.deposit') }}" class="{{ Config::activeMenu(route('user.deposit')) }}">
                <i class="fas fa-wallet"></i>
                <span>{{ __('Deposit') }}</span>
            </a>
        </li>

        <li>
            <a href="{{ route('user.transfer_money') }}"
                class="{{ Config::activeMenu(route('user.transfer_money')) }}">
                <i class="fas fa-exchange-alt"></i>
                <span>{{ __('Send Money') }}</span>
            </a>
        </li>
        
        <li>
            <a href="{{ route('user.dashboard') }}" class="{{ Config::activeMenu(route('user.dashboard')) }}">
                <i class="fas fa-home"></i>
                <span>{{ __('Home') }}</span>
            </a>
        </li>

        <li>
            <a href="{{ route('user.withdraw') }}" class="{{ Config::activeMenu(route('user.withdraw')) }}">
                <i class="fas fa-hand-holding-usd"></i>
                <span>{{ __('Withdraw') }}</span>
            </a>
        </li>

        <li class="sidebar-open-btn">
            <a href="#0" class="">
                <i class="fas fa-bars"></i>
                <span>{{ __('Menu') }}</span>
            </a>
        </li>
    </ul>
</div>
<!-- mobile bottom menu end -->



@push('script')
    <script>
        $(function() {
            'use strict'

            var expirationDate = new Date('{{ $plan_expired_at }}');

            function updateCountdown() {
                var now = new Date();
                var timeLeft = expirationDate - now;

                if (timeLeft < 0) {
                    // The plan has expired
                    $('#countdown').html(`
                      <p class="upgrade-text"><i class="fas fa-rocket"></i> Please Upgrade Your Plan</p>
                    `);
                } else {
                    // The plan is still active
                    var daysLeft = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    var hoursLeft = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutesLeft = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    var secondsLeft = Math.floor((timeLeft % (1000 * 60)) / 1000);

                    $('#countdown').html(`
                      <h5 class="user-sidebar-bottom-title">{{ __('plan expired at :') }}</h5>
                      <div class="countdown-wrapper">
                        <p class="countdown-single">
                          ${daysLeft}
                          <span>D</span>
                        </p>
                        <p class="countdown-single">
                          ${hoursLeft}
                          <span>H</span>
                        </p>
                        <p class="countdown-single">
                          ${minutesLeft}
                          <span>M</span>
                        </p>
                        <p class="countdown-single">
                          ${secondsLeft}
                          <span>S</span>
                        </p>
                      </div>
                    `);
                }
            }
            // Call updateCountdown every second
            setInterval(updateCountdown, 1000);
        })
    </script>
@endpush
