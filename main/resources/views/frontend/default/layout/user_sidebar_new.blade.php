@php
    $plan_expired_at = now();
    $currentPlanSubscription = auth()->user()->currentplan()->where('is_current', 1)->first();
    if ($currentPlanSubscription) {
        $plan_expired_at = $currentPlanSubscription->plan_expired_at;
    }

    // Get menu structure from MenuConfigService (with caching)
    $menuConfig = app(\App\Services\MenuConfigService::class);
    $user = auth()->user();
    
    // Use cached menu (getMenuForUser includes caching)
    // Force refresh to ensure Trading Configuration is removed
    $menuStructure = $menuConfig->getMenuForUser($user, true);
    
    // Final safety check: Ensure trading_console menu exists if user has active plan
    $onboardingService = app(\App\Services\UserOnboardingService::class);
    if ($onboardingService->hasActivePlan($user)) {
        // Support both new 'trading_console' and legacy 'trading' keys
        if ((!isset($menuStructure['trading_console']) || empty($menuStructure['trading_console']['items'])) 
            && (!isset($menuStructure['trading']) || empty($menuStructure['trading']['items']))) {
            // Re-add trading menu if it was removed or empty
            $menuStructure['trading_console'] = [
                'label' => __('TRADING CONSOLE'),
                'icon' => 'fas fa-chart-line',
                'items' => $menuConfig->getTradingConsoleMenuItems(),
            ];
        }
    }
    
    // Debug: Log menu structure (temporary - remove in production)
    // \Log::info('Menu Structure Final', [
    //     'keys' => array_keys($menuStructure),
    //     'trading_exists' => isset($menuStructure['trading']),
    //     'trading_items_count' => isset($menuStructure['trading']) ? count($menuStructure['trading']['items']) : 0
    // ]);
@endphp

<aside class="user-sidebar">
    <a href="{{ route('user.dashboard') }}" class="site-logo">
        <img src="{{ Config::getFile('logo', optional(Config::config())->logo ?? '', true) }}" alt="image">
    </a>

    <div class="user-sidebar-bottom">
        <div id="countdown"></div>
    </div>

    <ul class="sidebar-menu">
        @php
            // Final safety check: Ensure trading_console menu exists before rendering
            $onboardingService = app(\App\Services\UserOnboardingService::class);
            if ($onboardingService->hasActivePlan(auth()->user())) {
                if ((!isset($menuStructure['trading_console']) || empty($menuStructure['trading_console']['items']))
                    && (!isset($menuStructure['trading']) || empty($menuStructure['trading']['items']))) {
                    $menuStructure['trading_console'] = [
                        'label' => __('TRADING CONSOLE'),
                        'icon' => 'fas fa-chart-line',
                        'items' => app(\App\Services\MenuConfigService::class)->getTradingConsoleMenuItems(),
                    ];
                }
            }
        @endphp
        {{-- Debug output (temporary) --}}
        <!-- DEBUG START: Menu Structure Analysis -->
        <!-- DEBUG: Menu keys: {{ implode(', ', array_keys($menuStructure ?? [])) }} -->
        <!-- DEBUG: Trading exists: {{ isset($menuStructure['trading']) ? 'YES' : 'NO' }} -->
        @if(isset($menuStructure['trading']))
            <!-- DEBUG: Trading items count: {{ count($menuStructure['trading']['items'] ?? []) }} -->
            <!-- DEBUG: Trading items empty: {{ empty($menuStructure['trading']['items']) ? 'YES' : 'NO' }} -->
            <!-- DEBUG: Trading items is_array: {{ is_array($menuStructure['trading']['items'] ?? null) ? 'YES' : 'NO' }} -->
        @endif
        <!-- DEBUG END -->
        
        @foreach($menuStructure as $groupKey => $group)
            <!-- DEBUG: Loop iteration - groupKey: {{ $groupKey }} -->
            {{-- Skip if group is empty --}}
            @if(empty($group['items']) || !is_array($group['items']) || count($group['items']) === 0)
                <!-- DEBUG: Skipping {{ $groupKey }} - empty or not array -->
                @continue
            @endif
            <!-- DEBUG: Rendering {{ $groupKey }} group header -->
            
            {{-- Group Header --}}
            <li class="nav-label">
                <i class="{{ $group['icon'] ?? 'fas fa-circle' }} me-2"></i>
                <span>{{ $group['label'] ?? strtoupper($groupKey) }}</span>
            </li>
            
            @if($groupKey === 'home')
                {{-- HOME Group --}}
                @foreach($group['items'] as $item)
                    <li class="{{ Config::singleMenu($item['route']) }}">
                        <a href="{{ route($item['route']) }}">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            @elseif(in_array($groupKey, ['trading', 'trading_console', 'market_analysis', 'marketplace', 'support']))
                {{-- TRADING, MARKET ANALYSIS, MARKETPLACE, SUPPORT Groups (use same rendering logic) --}}
                @if(isset($group['items']) && is_array($group['items']) && count($group['items']) > 0)
                    @foreach($group['items'] as $item)
                        @php
                            $itemRoute = $item['route'] ?? null;
                            
                            // Skip Trading Configuration menu item
                            if ($itemRoute === 'user.trading.configuration.index') {
                                continue;
                            }
                            
                            $routeUrl = '#';
                            if ($itemRoute) {
                                try {
                                    $routeUrl = route($itemRoute);
                                } catch (\Exception $e) {
                                    $routeUrl = '#';
                                }
                            }
                        @endphp
                        @if(isset($item['type']) && $item['type'] === 'unified_page')
                            {{-- Unified Page with Tabs (with optional submenu) --}}
                            @if(isset($item['children']) && is_array($item['children']) && count($item['children']) > 0)
                                {{-- Has submenu (children) --}}
                                @php
                                    $submenuRoutes = collect($item['children'])->pluck('route')->toArray();
                                    $isSubmenuActive = in_array(request()->route()->getName() ?? '', $submenuRoutes) || Config::singleMenu($itemRoute ?? '');
                                @endphp
                                <li class="has_submenu {{ $isSubmenuActive ? 'open' : '' }}">
                                    <a href="{{ $routeUrl }}" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="right" 
                                       title="{{ $item['tooltip'] ?? $item['label'] ?? 'N/A' }}">
                                        <i class="{{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                        <span>{{ $item['label'] ?? 'N/A' }}</span>
                                    </a>
                                    <ul class="submenu">
                                        @foreach($item['children'] as $child)
                                            @php
                                                $childRoute = $child['route'] ?? null;
                                                $childUrl = '#';
                                                if ($childRoute) {
                                                    try {
                                                        $childUrl = route($childRoute);
                                                    } catch (\Exception $e) {
                                                        $childUrl = '#';
                                                    }
                                                }
                                            @endphp
                                            <li class="{{ Config::singleMenu($childRoute ?? '') }}">
                                                <a href="{{ $childUrl }}" 
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="right" 
                                                   title="{{ $child['tooltip'] ?? $child['label'] ?? 'N/A' }}">
                                                    <i class="{{ $child['icon'] ?? 'fas fa-circle' }}"></i>
                                                    <span>{{ $child['label'] ?? 'N/A' }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                {{-- No submenu, direct link --}}
                                <li class="{{ Config::singleMenu($itemRoute ?? '') }}">
                                    <a href="{{ $routeUrl }}" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="right" 
                                       title="{{ $item['tooltip'] ?? $item['label'] ?? 'N/A' }}">
                                        <i class="{{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                        <span>{{ $item['label'] ?? 'N/A' }}</span>
                                    </a>
                                </li>
                            @endif
                        @elseif(isset($item['type']) && $item['type'] === 'marketplace')
                            {{-- Marketplace - Show as accordion menu --}}
                            <li class="has_submenu {{ Config::singleMenu($itemRoute ?? '') ? 'open' : '' }}">
                                <a href="javascript:void(0)" class="menu-toggle">
                                    <i class="{{ $item['icon'] ?? 'fas fa-store' }}"></i>
                                    <span>{{ $item['label'] ?? 'N/A' }}</span>
                                </a>
                                @if(isset($item['categories']) && is_array($item['categories']) && count($item['categories']) > 0)
                                    <ul class="submenu">
                                        @foreach($item['categories'] as $catKey => $catLabel)
                                            @php
                                                $catUrl = '#';
                                                if ($itemRoute) {
                                                    try {
                                                        $catUrl = route($itemRoute, ['category' => $catKey]);
                                                    } catch (\Exception $e) {
                                                        $catUrl = '#';
                                                    }
                                                }
                                            @endphp
                                            <li class="{{ request()->get('category') === $catKey && Config::singleMenu($itemRoute ?? '') ? 'active' : '' }}">
                                                <a href="{{ $catUrl }}">
                                                    {{ $catLabel }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @else
                            {{-- Regular Menu Item --}}
                            <li class="{{ Config::singleMenu($itemRoute ?? '') }}">
                                <a href="{{ $routeUrl }}" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="right" 
                                   title="{{ $item['tooltip'] ?? $item['label'] ?? 'N/A' }}">
                                    <i class="{{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                    <span>{{ $item['label'] ?? 'N/A' }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @elseif($groupKey === 'account')
                {{-- ACCOUNT Group --}}
                @foreach($group['items'] as $item)
                    @if(isset($item['type']) && $item['type'] === 'submenu' && isset($item['children']))
                        {{-- Submenu (e.g., Wallet) --}}
                        @php
                            $submenuRoutes = collect($item['children'])->pluck('route')->toArray();
                            $isSubmenuActive = in_array(request()->route()->getName() ?? '', $submenuRoutes);
                        @endphp
                        <li class="has_submenu {{ $isSubmenuActive ? 'open' : '' }}">
                            <a href="#0">
                                <i class="{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                            <ul class="submenu">
                                @foreach($item['children'] as $child)
                                    <li class="{{ Config::singleMenu($child['route']) }}">
                                        <a href="{{ route($child['route']) }}">
                                            <i class="{{ $child['icon'] ?? '' }}"></i>
                                            <span>{{ $child['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Regular Menu Item --}}
                        <li class="{{ Config::singleMenu($item['route']) }}">
                            <a href="{{ route($item['route']) }}">
                                <i class="{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Logout (always visible) --}}
        <li class="nav-label mt-3">{{ __('Others') }}</li>
        <li class="{{ Config::singleMenu('user.logout') }}">
            <a href="{{ route('user.logout') }}">
                <i class="fas fa-sign-out-alt"></i>
                <span>{{ __('Logout') }}</span>
            </a>
        </li>
    </ul>
</aside>

<!-- mobile bottom menu start -->
<div class="mobile-bottom-menu-wrapper">
    <ul class="mobile-bottom-menu">
        <li>
            <a href="{{ route('user.dashboard') }}" class="{{ Config::activeMenu(route('user.dashboard')) }}">
                <i class="fas fa-home"></i>
                <span>{{ __('Overview') }}</span>
            </a>
        </li>

        <li>
            <a href="{{ route('user.trading.operations.index') }}" class="{{ Config::activeMenu(route('user.trading.operations.index')) }}">
                <i class="fas fa-robot"></i>
                <span>{{ __('Bots') }}</span>
            </a>
        </li>

        <li>
            @if(Route::has('user.manual-trading.index'))
                <a href="{{ route('user.manual-trading.index') }}" class="{{ Config::activeMenu(route('user.manual-trading.index')) }}">
                    <i class="fas fa-hand-pointer"></i>
                    <span>{{ __('Trade') }}</span>
                </a>
            @else
                <a href="{{ route('user.dashboard') }}" class="{{ Config::activeMenu(route('user.dashboard')) }}">
                    <i class="fas fa-hand-pointer"></i>
                    <span>{{ __('Trade') }}</span>
                </a>
            @endif
        </li>

        <li>
            <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="{{ Config::activeMenu(route('user.trading.multi-channel-signal.index')) }}">
                <i class="fas fa-signal"></i>
                <span>{{ __('Signals') }}</span>
            </a>
        </li>

        <li class="sidebar-open-btn">
            <a href="#0">
                <i class="fas fa-bars"></i>
                <span>{{ __('More') }}</span>
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
                    $('#countdown').html(`
                      <p class="upgrade-text"><i class="fas fa-rocket"></i> Please Upgrade Your Plan</p>
                    `);
                } else {
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
            setInterval(updateCountdown, 1000);
        })
        
        // Initialize Bootstrap tooltips
        $(function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Accordion menu toggle (harmonika like admin)
            $('.sidebar-menu .has_submenu > a.menu-toggle').on('click', function(e) {
                e.preventDefault();
                var $parent = $(this).parent('li');
                var $submenu = $parent.find('> .submenu');
                
                // Close other submenus in same group
                $parent.siblings('.has_submenu').removeClass('open').find('.submenu').slideUp(300);
                
                // Toggle current submenu
                $parent.toggleClass('open');
                $submenu.slideToggle(300);
            });
            
            // Auto-open submenu if current page is in it
            $('.sidebar-menu .has_submenu').each(function() {
                var $submenu = $(this).find('> .submenu');
                var hasActive = $submenu.find('li.active').length > 0;
                if (hasActive) {
                    $(this).addClass('open');
                    $submenu.show();
                }
            });
        });
    </script>
@endpush

