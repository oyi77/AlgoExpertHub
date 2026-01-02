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
    
    {{-- Legacy notification libraries (will be removed after migration) --}}
    @if ($alertType === 'izi')
        <link href="{{ Config::cssLib('backend', 'izitoast.min.css') }}" rel="stylesheet">
    @elseif($alertType === 'toast')
        <link href="{{ Config::cssLib('backend', 'toastr.min.css') }}" rel="stylesheet">
    @elseif($alertType === 'sweetalert')
        <link href="{{ Config::cssLib('backend', 'sweetalert.min.css') }}" rel="stylesheet">
    @endif

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
                    // Don't show error to user if WebSocket server is not available
                    // This is expected if BROADCAST_DRIVER=null or server is not running
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

    @livewireStyles
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

    {{-- Load jQuery synchronously first to ensure it's available for inline scripts and repeater.js --}}
    <script src="{{ Config::jsLib('backend', 'global.min.js') }}"></script>

    <script defer src="{{ Config::jsLib('backend', 'feather.min.js') }}"></script>

    <script defer src="{{ Config::jsLib('backend', 'quixnav-init.js') }}"></script>

    <script defer src="{{ Config::jsLib('backend', 'metismenu.min.js') }}"></script>

    <script defer src="{{ Config::jsLib('backend', 'perfectscroll.min.js') }}"></script>

    @hasSection('uses_datatable')
        <script defer src="{{ Config::jsLib('backend', 'jquery.dataTables.min.js') }}"></script>
    @endif

    @hasSection('uses_uploadpreview')
        <script defer src="{{ Config::jsLib('backend', 'jquery.uploadPreview.min.js') }}"></script>
    @endif

    @hasSection('uses_summernote')
        <script defer src="{{ Config::jsLib('backend', 'summernote-bs4.min.js') }}"></script>
    @endif

    <script defer src="{{ Config::jsLib('backend', 'ui.js') }}"></script>

    @hasSection('uses_apexchart')
        <script defer src="{{ Config::jsLib('backend', 'apex-chart.min.js') }}"></script>
    @endif

    @hasSection('uses_iconpicker')
        <script defer src="{{ Config::jsLib('backend', 'iconpicker.js') }}"></script>
    @endif

    {{-- Laravel Notify JavaScript --}}
    <script defer src="{{ asset('vendor/notify/notify.js') }}"></script>
    
    {{-- Legacy notification libraries (will be removed after migration) --}}
    @if ($alertType === 'izi')
        <script defer src="{{ Config::jsLib('backend', 'izitoast.min.js') }}"></script>
    @elseif($alertType === 'toast')
        <script defer src="{{ Config::jsLib('backend', 'toastr.min.js') }}"></script>
    @elseif($alertType === 'sweetalert')
        <script defer src="{{ Config::jsLib('backend', 'sweetalert.min.js') }}"></script>
    @endif

    @stack('external-script')

    <!-- Dialog Wrapper - Replaces native alert/confirm/prompt with custom modals -->
    <script defer src="{{ asset('asset/backend/js/dialog-wrapper.js') }}"></script>
    <script defer src="{{ asset('asset/js/fix-onsubmit-confirm.js') }}"></script>

    <script defer src="{{ Config::jsLib('backend', 'custom.js') }}"></script>

    @stack('script')
    @include('backend.layout.alert')

    @livewireScripts
    <script>
        // Ensure jQuery is available before executing any scripts
        (function() {
            function waitForJQuery(callback) {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    callback();
                } else {
                    setTimeout(function() { waitForJQuery(callback); }, 50);
                }
            }
            
            waitForJQuery(function() {
                $(function() {
                    'use strict'
                    
                    // Only initialize Summernote if it's loaded and elements exist
                    if (typeof $.fn.summernote !== 'undefined' && $('.summernote').length > 0) {
                        $('.summernote').summernote({
                            height: 250,
                        });
                    }

                    var url = "{{ route('admin.changeLang') }}";

                    $(".changeLang").change(function() {
                        if ($(this).val() == '') {
                            return false;
                        }
                        window.location.href = url + "?lang=" + $(this).val();
                    });
                });
            });
        })();
    </script>

</body>

</html>
