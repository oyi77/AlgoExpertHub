<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ optional(Config::config())->appname ?? 'Admin Panel' }}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ Config::fetchImage('icon', optional(Config::config())->favicon ?? '') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap">

    <link href="{{ Config::cssLib('backend', 'all.min.css') }}" rel="stylesheet">


    <link href="{{ Config::cssLib('backend', 'line-awesome.min.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'perfect-scrollbar.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'metisMenu.min.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'uploader.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'iconpicker.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'jquery.dataTables.min.css') }}" rel="stylesheet">

    <link href="{{ Config::cssLib('backend', 'summernote-bs4.min.css') }}" rel="stylesheet">

    @php
        $alertType = optional(Config::config())->alert ?? 'notify';
    @endphp
    <link href="{{ Config::cssLib('backend', 'ui.css') }}" rel="stylesheet">

    {{-- Laravel Notify CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/notify/notify.css') }}">

    @stack('external-style')

    <link href="{{ Config::cssLib('backend', 'style.css') }}" rel="preload" as="style" onload="this.rel='stylesheet'">
    <noscript>
        <link href="{{ Config::cssLib('backend', 'style.css') }}" rel="stylesheet">
    </noscript>

    <link href="{{ Config::cssLib('backend', 'main.css') }}" rel="stylesheet">

    @if(optional(Config::config())->enable_new_styles)
        <link href="{{ Config::cssLib('backend', 'new-styles.css') }}" rel="stylesheet">
    @endif

    @stack('style')

    {{-- Admin Core JS (Theme, Loader, Interceptor) --}}
    <script src="{{ asset('asset/backend/js/admin-core.js') }}"></script>
    
    {{-- Seamless Navigation (Optional SPA-like feel) --}}
    {{-- <script src="{{ asset('asset/backend/js/seamless-nav.js') }}"></script> --}}

    {{-- WebSocket Support (Pusher + Laravel Echo) --}}
    @if(config('broadcasting.default') !== 'null' && config('broadcasting.connections.pusher.key'))
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        // Initialize Laravel Echo with Pusher (Soketi)
        try {
            const pusherConfig = {
                broadcaster: 'pusher',
                key: '{{ config("broadcasting.connections.pusher.key") }}',
                forceTLS: {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 'true' : 'false' }},
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
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

    @livewireStyles
    <style>
        /* Theme Toggle Styles */
        .theme-toggle {
            padding: 0.5rem !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-toggle:hover {
            background-color: rgba(0,0,0,0.05);
            border-radius: 50%;
        }
        [data-theme="dark"] .theme-toggle:hover {
            background-color: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

    <div id="main-wrapper">

        @include('backend.layout.header')

        @include('backend.layout.sidebar')

        <div class="content-body">
            <div id="overlay">
                <div class="cv-spinner">
                    <span class="spinner"></span>
                </div>
            </div>
            <div class="container-fluid">
                @include('backend.layout.breadcrumb')

                @yield('element')

            </div>
        </div>

        @include('backend.layout.footer')

    </div>

    {{-- Load jQuery with blocking script tag first, fallback to CDN --}}
    <script src="{{ Config::jsLib('backend', 'global.min.js') }}"></script>
    <script>
        // Use AdminCore to manage jQuery fallback
        AdminCore.loader.initJQueryFallback(
            null, // Local already tried above
            'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
            'https://code.jquery.com/jquery-3.7.1.min.js'
        );
    </script>

    {{-- Non-jQuery scripts can load immediately --}}
    <script defer src="{{ Config::jsLib('backend', 'feather.min.js') }}"></script>

    {{-- jQuery-dependent scripts - load after jQuery is ready --}}
    <script>
        (function() {
            // Load jQuery-dependent scripts using AdminCore loader
            const loader = AdminCore.loader;
            
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'quixnav-init.js') }}');
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'metismenu.min.js') }}');
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'perfectscroll.min.js') }}');
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'ui.js') }}');
            
            @hasSection('uses_datatable')
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'jquery.dataTables.min.js') }}');
            @endif

            @hasSection('uses_uploadpreview')
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'jquery.uploadPreview.min.js') }}');
            @endif

            @hasSection('uses_summernote')
            loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'summernote-bs4.min.js') }}');
            @endif
        })();
    </script>

    @hasSection('uses_apexchart')
        <script defer src="{{ Config::jsLib('backend', 'apex-chart.min.js') }}"></script>
    @endif

    @hasSection('uses_iconpicker')
        <script defer src="{{ Config::jsLib('backend', 'iconpicker.js') }}"></script>
    @endif

    {{-- Laravel Notify JavaScript - load after jQuery is ready --}}
    <script>
        (function() {
            const loader = AdminCore.loader;
            
            // Helper to load notify.js
            const loadNotify = function() {
                var script = document.createElement('script');
                script.src = '{{ asset('vendor/notify/notify.js') }}';
                script.async = false;
                script.defer = false;
                script.onload = function() { window.dispatchEvent(new Event('notify-loaded')); };
                script.onerror = function() { window.dispatchEvent(new Event('notify-loaded')); };
                document.head.appendChild(script);
            };

            // Use the core loader to wait for jQuery
            loader.waitForJQuery(loadNotify);
        })();
    </script>
    
    {{-- Alert notifications --}}
    @include('backend.layout.alert')

    {{-- External scripts interceptor is now in admin-core.js --}}
    
    @stack('external-script')

    <!-- Dialog Wrapper - Replaces native alert/confirm/prompt with custom modals -->
    <script defer src="{{ asset('asset/backend/js/dialog-wrapper.js') }}"></script>
    <script defer src="{{ asset('asset/js/fix-onsubmit-confirm.js') }}"></script>

    {{-- Custom.js - load after jQuery is ready --}}
    <script>
        AdminCore.loader.loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'custom.js') }}');
    </script>

    @livewireScripts
    <script>
        // Ensure jQuery is available before executing any scripts
        (function() {
            // Wait for jQuery before running Summernote and other init logic
            AdminCore.loader.waitForJQuery(function() {
                var $ = window.jQuery || window.$;
                
                $(function() {
                    'use strict'
                    
                    // Only initialize Summernote if elements exist AND Summernote is loaded
                    var summernoteElements = $('.summernote');
                    if (summernoteElements.length > 0) {
                        // Simple polling for plugin since AdminCore doesn't have a specific waitForPlugin method yet
                        var attempts = 0;
                        var check = function() {
                            if (typeof $.fn.summernote !== 'undefined') {
                                try {
                                    summernoteElements.summernote({ height: 250 });
                                } catch (e) {
                                    console.warn('Failed to initialize Summernote:', e);
                                }
                            } else if (attempts < 100) {
                                attempts++;
                                setTimeout(check, 50);
                            }
                        };
                        check();
                    }

                    // Handled by AdminCore delegation now
                    // var url = "{{ route('admin.changeLang') }}";
                    // $(".changeLang").change(function() { ... });
                });
            });
        })();
    </script>
    
    {{-- Stack scripts - should wrap jQuery-dependent code in waitForJQuery if needed --}}
    @stack('scripts')

</body>

</html>