@php
    use App\Helpers\Helper\Helper;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', Config::config()->appname ?? 'AlgoExpertHub')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ Config::getFile('icon', Config::config()->icon ?? '') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Line Awesome -->
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/main.css') }}">
    
    {{-- WebSocket Support (Pusher + Laravel Echo) --}}
    @if(config('broadcasting.default') !== 'null' && config('broadcasting.connections.pusher.key'))
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        // Initialize Laravel Echo with Pusher (Soketi)
        try {
            // Get CSRF token from meta tag or cookie
            function getCsrfToken() {
                const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (metaToken) return metaToken;
                
                // Fallback to cookie
                const name = 'XSRF-TOKEN';
                const cookies = document.cookie.split(';');
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i].trim();
                    if (cookie.substring(0, name.length + 1) === (name + '=')) {
                        return decodeURIComponent(cookie.substring(name.length + 1));
                    }
                }
                return '{{ csrf_token() }}';
            }
            
            const pusherConfig = {
                broadcaster: 'pusher',
                key: '{{ config("broadcasting.connections.pusher.key") }}',
                forceTLS: {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 'true' : 'false' }},
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            };
            
            // Use configured WebSocket host (reverse proxy subdomain) or fallback to current hostname
            @if(config('broadcasting.connections.pusher.options.ws_host'))
                // When using reverse proxy subdomain, use standard ports (443 for WSS, 80 for WS)
                pusherConfig.wsHost = '{{ config("broadcasting.connections.pusher.options.ws_host") }}';
                pusherConfig.wsPort = {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 443 : 80 }};
                pusherConfig.wssPort = 443;
            @else
                // Direct connection to Soketi server (no reverse proxy)
                pusherConfig.wsHost = window.location.hostname;
                pusherConfig.wsPort = {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 443 : config('broadcasting.connections.pusher.options.port', 6001) }};
                pusherConfig.wssPort = {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 443 : config('broadcasting.connections.pusher.options.port', 6001) }};
            @endif

            // Add cluster if configured (required by Pusher.js even for custom hosts)
            @if(config('broadcasting.connections.pusher.options.cluster'))
                pusherConfig.cluster = '{{ config("broadcasting.connections.pusher.options.cluster") }}';
            @else
                pusherConfig.cluster = 'mt1'; // Default cluster for Pusher.js compatibility
            @endif

            window.Echo = new Echo(pusherConfig);

            // Log connection status
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind('connected', function() {
                    console.log('✅ WebSocket connected to Soketi');
                });

                window.Echo.connector.pusher.connection.bind('disconnected', function() {
                    console.warn('⚠️ WebSocket disconnected');
                });

                window.Echo.connector.pusher.connection.bind('error', function(err) {
                    console.error('❌ WebSocket error:', err);
                });

                // Handle connection state changes
                window.Echo.connector.pusher.connection.bind('state_change', function(states) {
                    if (states.current === 'failed' || states.current === 'unavailable') {
                        console.warn('⚠️ WebSocket server unavailable. Real-time features disabled.');
                    }
                });
            }
        } catch (e) {
            console.warn('⚠️ WebSocket initialization failed:', e);
        }
    </script>
    @endif
    
    @stack('styles')
    <style>
        #loading-overlay {
            backdrop-filter: blur(5px);
            transition: opacity 0.3s ease;
        }
        #loading-overlay .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(26, 255, 213, 0.1);
            border-top: 4px solid var(--tv-primary, #1AFFD5);
            border-radius: 50%;
            animation: tv-spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            box-shadow: 0 0 20px rgba(26, 255, 213, 0.2);
        }
        @keyframes tv-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .loading-text {
            color: var(--tv-primary, #1AFFD5);
            font-family: var(--tv-font-body, 'Inter', sans-serif);
            margin-top: 15px;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <!-- Mobile Top Bar -->
    <div class="tv-mobile-topbar">
        <button class="tv-mobile-toggle" id="sidebarToggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h2 class="tv-mobile-title">@yield('page_title', 'Dashboard')</h2>
        <div class="tv-mobile-profile">
            @php
                $userImage = auth()->user()->image ? file_exists(storage_path('app/public/' . auth()->user()->image)) ? asset('storage/'.auth()->user()->image) : asset('asset/images/avatar.png') : asset('asset/images/avatar.png');
            @endphp
            <button class="tv-mobile-profile-btn" id="mobileProfileToggle" aria-label="Profile Menu">
                <img src="{{ $userImage }}" alt="Profile" class="tv-mobile-profile-img">
                <i class="las la-angle-down"></i>
            </button>
            <div class="tv-mobile-profile-dropdown" id="mobileProfileDropdown">
                <a href="{{ route('user.profile') }}" class="tv-mobile-dropdown-item">
                    <i class="las la-user"></i>
                    <span>Profile</span>
                </a>
                <a href="{{ route('user.logout') }}" class="tv-mobile-dropdown-item tv-mobile-dropdown-item-danger">
                    <i class="las la-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="tv-mobile-overlay" id="sidebarOverlay"></div>

    <div class="tv-panel">
        <!-- Sidebar -->
        @include(\App\Helpers\Helper\Helper::theme() . 'layout.user_sidebar')
        
        <!-- Main Content -->
        <div class="tv-main">
            <!-- Page Header -->
            <div class="tv-page-header">
                <!-- Desktop Sidebar Toggle -->
                <button class="tv-desktop-sidebar-toggle mr-3" id="desktopSidebarToggle" title="Toggle Sidebar">
                    <i class="las la-bars"></i>
                </button>

                <h1 class="tv-page-title">@yield('page_title', 'Dashboard')</h1>
                
                <!-- Profile Dropdown -->
                <div class="tv-header-profile">
                    <div class="tv-profile-toggle" id="profileToggle">
                        @php
                            $userImage = auth()->user()->image ? file_exists(storage_path('app/public/' . auth()->user()->image)) ? asset('storage/'.auth()->user()->image) : asset('asset/images/avatar.png') : asset('asset/images/avatar.png');
                        @endphp
                        <img src="{{ $userImage }}" alt="Profile" class="tv-profile-img">
                        <span class="tv-profile-name d-none d-md-inline">{{ auth()->user()->username }}</span>
                        <i class="las la-angle-down"></i>
                    </div>
                    <div class="tv-profile-dropdown" id="profileDropdown">
                        <a href="{{ route('user.profile') }}" class="tv-dropdown-item">
                            <i class="las la-user"></i> Profile
                        </a>
                        <a href="{{ route('user.logout') }}" class="tv-dropdown-item text-danger">
                            <i class="las la-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            @yield('content')
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="{{ asset('asset/frontend/trading-v1/js/main.js') }}"></script>
    
    {{-- Laravel Notify CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/notify/notify.css') }}">
    
    {{-- Laravel Notify JavaScript --}}
    <script defer src="{{ asset('vendor/notify/notify.js') }}"></script>
    
    {{-- Flash Messages (Laravel Notify) --}}
    @include('alert')
    
    @stack('scripts')
    <script>
        // Wait for jQuery to be available before using it
        (function() {
            function initWhenReady() {
                if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                    setTimeout(initWhenReady, 50);
                    return;
                }
                
                $(document).ready(function() {
                    // Intercept internal link clicks to show loading
                    $('a').on('click', function(e) {
                        const href = $(this).attr('href');
                        const target = $(this).attr('target');
                        
                        // Only show for internal links, not # or javascript:, and not new tabs
                        if (href && 
                            href !== '#' && 
                            !href.startsWith('javascript:') && 
                            !href.startsWith('mailto:') && 
                            !href.startsWith('tel:') &&
                            (!target || target === '_self') &&
                            (href.startsWith('/') || href.includes(window.location.hostname))) {
                            
                            if (typeof window.showLoading === 'function') {
                                window.showLoading();
                                // Add a small timeout to allow the browser to render the loading overlay 
                                // before starting the heavy navigation process
                            }
                        }
                    });

                    // Re-define showLoading to be more beautiful and include text
                    window.showLoading = function() {
                        if (!$('#loading-overlay').length) {
                            $('body').append(`
                                <div id="loading-overlay" style="
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    bottom: 0;
                                    background: rgba(18, 18, 18, 0.85);
                                    display: flex;
                                    flex-direction: column;
                                    align-items: center;
                                    justify-content: center;
                                    z-index: 10000;
                                    opacity: 0;
                                ">
                                    <div class="spinner"></div>
                                    <div class="loading-text">{{ __('Loading...') }}</div>
                                </div>
                            `);
                            setTimeout(() => $('#loading-overlay').css('opacity', '1'), 10);
                        }
                    };
                });

                // Hide loading when page is fully loaded or from cache (back button)
                $(window).on('pageshow load', function() {
                    if (typeof window.hideLoading === 'function') {
                        window.hideLoading();
                    }
                });
            }
            
            // Start initialization
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWhenReady);
            } else {
                initWhenReady();
            }
        })();
    </script>
</body>
</html>

