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

    {{-- Load jQuery with blocking script tag first, fallback to CDN --}}
    <script src="{{ Config::jsLib('backend', 'global.min.js') }}"></script>
    <script>
        // Ensure jQuery and $ are available, fallback to CDN if local failed
        // Check after a short delay to handle HTTP/2 protocol errors that return 200 but don't execute
        (function() {
            function checkAndLoadJQuery() {
                if (typeof window.jQuery === 'undefined') {
                    console.warn('jQuery: Local file failed or not loaded, loading from CDN');
                    var script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js';
                    script.async = false;
                    script.defer = false;
                    script.onload = function() {
                        // Double-check after load
                        setTimeout(function() {
                            if (typeof window.jQuery !== 'undefined') {
                                if (typeof window.$ === 'undefined') {
                                    window.$ = window.jQuery;
                                }
                                console.log('jQuery: Successfully loaded from CDN');
                                window.dispatchEvent(new Event('jquery-loaded'));
                            } else {
                                console.error('jQuery: CDN load failed, trying alternative');
                                // Try alternative CDN
                                var altScript = document.createElement('script');
                                altScript.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
                                altScript.async = false;
                                altScript.onload = function() {
                                    if (typeof window.jQuery !== 'undefined') {
                                        if (typeof window.$ === 'undefined') {
                                            window.$ = window.jQuery;
                                        }
                                        window.dispatchEvent(new Event('jquery-loaded'));
                                    }
                                };
                                document.head.appendChild(altScript);
                            }
                        }, 50);
                    };
                    script.onerror = function() {
                        console.error('jQuery: CDN load error, trying alternative');
                        var altScript = document.createElement('script');
                        altScript.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
                        altScript.async = false;
                        altScript.onload = function() {
                            if (typeof window.jQuery !== 'undefined') {
                                if (typeof window.$ === 'undefined') {
                                    window.$ = window.jQuery;
                                }
                                window.dispatchEvent(new Event('jquery-loaded'));
                            }
                        };
                        document.head.appendChild(altScript);
                    };
                    document.head.appendChild(script);
                } else {
                    // jQuery loaded successfully
                    if (typeof window.$ === 'undefined') {
                        window.$ = window.jQuery;
                    }
                    window.dispatchEvent(new Event('jquery-loaded'));
                }
            }
            
            // Check immediately, then again after a short delay to catch HTTP/2 errors
            checkAndLoadJQuery();
            setTimeout(checkAndLoadJQuery, 200);
        })();
    </script>

    {{-- Non-jQuery scripts can load immediately --}}
    <script defer src="{{ Config::jsLib('backend', 'feather.min.js') }}"></script>

    {{-- jQuery-dependent scripts - load after jQuery is ready --}}
    <script>
        (function() {
            function loadScriptWhenJQueryReady(src, callback) {
                function waitForJQuery() {
                    if (typeof window.jQuery !== 'undefined') {
                        var script = document.createElement('script');
                        script.src = src;
                        script.async = false;
                        script.defer = false;
                        if (callback) {
                            script.onload = callback;
                        }
                        document.head.appendChild(script);
                    } else {
                        // Listen for jquery-loaded event or poll
                        var handler = function() {
                            window.removeEventListener('jquery-loaded', handler);
                            var script = document.createElement('script');
                            script.src = src;
                            script.async = false;
                            script.defer = false;
                            if (callback) {
                                script.onload = callback;
                            }
                            document.head.appendChild(script);
                        };
                        window.addEventListener('jquery-loaded', handler, { once: true });
                        
                        // Poll as fallback
                        setTimeout(function() {
                            if (typeof window.jQuery !== 'undefined') {
                                window.removeEventListener('jquery-loaded', handler);
                                var script = document.createElement('script');
                                script.src = src;
                                script.async = false;
                                script.defer = false;
                                if (callback) {
                                    script.onload = callback;
                                }
                                document.head.appendChild(script);
                            }
                        }, 100);
                    }
                }
                waitForJQuery();
            }
            
            // Load jQuery-dependent scripts
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'quixnav-init.js') }}');
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'metismenu.min.js') }}');
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'perfectscroll.min.js') }}');
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'ui.js') }}');
            
            @hasSection('uses_datatable')
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'jquery.dataTables.min.js') }}');
            @endif

            @hasSection('uses_uploadpreview')
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'jquery.uploadPreview.min.js') }}');
            @endif

            @hasSection('uses_summernote')
            loadScriptWhenJQueryReady('{{ Config::jsLib('backend', 'summernote-bs4.min.js') }}');
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
            function loadNotify() {
                var script = document.createElement('script');
                script.src = '{{ asset('vendor/notify/notify.js') }}';
                script.async = false;
                script.defer = false;
                script.onload = function() {
                    // Dispatch event when notify is loaded
                    window.dispatchEvent(new Event('notify-loaded'));
                };
                script.onerror = function() {
                    console.error('Failed to load notify.js');
                    // Dispatch event anyway so alerts can try to show
                    window.dispatchEvent(new Event('notify-loaded'));
                };
                document.head.appendChild(script);
            }
            
            if (typeof window.jQuery !== 'undefined') {
                loadNotify();
            } else {
                // Wait for jQuery
                var handler = function() {
                    window.removeEventListener('jquery-loaded', handler);
                    loadNotify();
                };
                window.addEventListener('jquery-loaded', handler, { once: true });
                
                // Poll as fallback
                var attempts = 0;
                var checkInterval = setInterval(function() {
                    attempts++;
                    if (typeof window.jQuery !== 'undefined') {
                        clearInterval(checkInterval);
                        window.removeEventListener('jquery-loaded', handler);
                        loadNotify();
                    } else if (attempts >= 100) {
                        clearInterval(checkInterval);
                        window.removeEventListener('jquery-loaded', handler);
                        console.error('jQuery not loaded, loading notify.js anyway');
                        loadNotify();
                    }
                }, 50);
            }
        })();
    </script>
    
    {{-- Alert notifications (waits for notify.js via the alert template itself) --}}
    @include('backend.layout.alert')

    {{-- External scripts - intercept and wrap to wait for jQuery --}}
    <script>
        (function() {
            // Intercept script tags added to DOM and wrap jQuery-dependent ones
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.tagName === 'SCRIPT' && node.src) {
                            // Check if this is a jQuery-dependent script
                            var jqueryDependent = /(toogle|colorpicker|select2|jquery\.|\.min\.js)/i.test(node.src);
                            
                            if (jqueryDependent && typeof window.jQuery === 'undefined') {
                                // Remove the script and queue it
                                var src = node.src;
                                node.remove();
                                
                                // Wait for jQuery then load
                                function loadWhenReady() {
                                    if (typeof window.jQuery !== 'undefined') {
                                        var script = document.createElement('script');
                                        script.src = src;
                                        script.async = node.async;
                                        script.defer = node.defer;
                                        document.head.appendChild(script);
                                    } else {
                                        var handler = function() {
                                            window.removeEventListener('jquery-loaded', handler);
                                            var script = document.createElement('script');
                                            script.src = src;
                                            script.async = node.async;
                                            script.defer = node.defer;
                                            document.head.appendChild(script);
                                        };
                                        window.addEventListener('jquery-loaded', handler, { once: true });
                                        setTimeout(function() {
                                            if (typeof window.jQuery !== 'undefined') {
                                                window.removeEventListener('jquery-loaded', handler);
                                                var script = document.createElement('script');
                                                script.src = src;
                                                script.async = node.async;
                                                script.defer = node.defer;
                                                document.head.appendChild(script);
                                            }
                                        }, 100);
                                    }
                                }
                                loadWhenReady();
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.head, { childList: true, subtree: true });
        })();
    </script>
    @stack('external-script')

    <!-- Dialog Wrapper - Replaces native alert/confirm/prompt with custom modals -->
    <script defer src="{{ asset('asset/backend/js/dialog-wrapper.js') }}"></script>
    <script defer src="{{ asset('asset/js/fix-onsubmit-confirm.js') }}"></script>

    {{-- Custom.js - load after jQuery is ready --}}
    <script>
        (function() {
            function loadCustomJs() {
                if (typeof window.jQuery !== 'undefined') {
                    var script = document.createElement('script');
                    script.src = '{{ Config::jsLib('backend', 'custom.js') }}';
                    script.async = false;
                    script.defer = false;
                    document.head.appendChild(script);
                } else {
                    var handler = function() {
                        window.removeEventListener('jquery-loaded', handler);
                        loadCustomJs();
                    };
                    window.addEventListener('jquery-loaded', handler, { once: true });
                    
                    // Poll as fallback
                    var attempts = 0;
                    var checkInterval = setInterval(function() {
                        attempts++;
                        if (typeof window.jQuery !== 'undefined') {
                            clearInterval(checkInterval);
                            window.removeEventListener('jquery-loaded', handler);
                            loadCustomJs();
                        } else if (attempts >= 100) {
                            clearInterval(checkInterval);
                            window.removeEventListener('jquery-loaded', handler);
                        }
                    }, 50);
                }
            }
            loadCustomJs();
        })();
    </script>

    @livewireScripts
    <script>
        // Ensure jQuery is available before executing any scripts
        (function() {
            function waitForJQuery(callback, maxAttempts) {
                maxAttempts = maxAttempts || 200;
                var attempts = 0;
                var callbackExecuted = false;
                
                // Listen for jquery-loaded event (from fallback loader)
                var eventHandler = function() {
                    // jQuery loaded via event, verify and execute callback
                    if (typeof window.jQuery !== 'undefined' && !callbackExecuted) {
                        if (typeof window.$ === 'undefined') {
                            window.$ = window.jQuery;
                        }
                        callbackExecuted = true;
                        window.removeEventListener('jquery-loaded', eventHandler);
                        callback();
                    }
                };
                window.addEventListener('jquery-loaded', eventHandler, { once: true });
                
                function check() {
                    attempts++;
                    // Check for jQuery (required)
                    if (typeof window.jQuery !== 'undefined') {
                        // jQuery is available, ensure $ is also available
                        if (typeof window.$ === 'undefined') {
                            // jQuery is in no-conflict mode, assign $ to jQuery
                            window.$ = window.jQuery;
                        }
                        // Remove event listener if it was set
                        if (!callbackExecuted) {
                            callbackExecuted = true;
                            window.removeEventListener('jquery-loaded', eventHandler);
                            callback();
                        }
                    } else if (attempts < maxAttempts) {
                        setTimeout(check, 50);
                    } else {
                        console.error('jQuery failed to load after ' + maxAttempts + ' attempts');
                        // Remove event listener if still waiting
                        if (!callbackExecuted) {
                            window.removeEventListener('jquery-loaded', eventHandler);
                        }
                    }
                }
                
                check();
            }
            
            function waitForSummernote(callback, maxAttempts) {
                maxAttempts = maxAttempts || 100;
                var attempts = 0;
                
                function check() {
                    attempts++;
                    if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.summernote !== 'undefined') {
                        callback();
                    } else if (attempts < maxAttempts) {
                        setTimeout(check, 50);
                    } else {
                        // Summernote not loaded, but continue anyway (it might not be needed on this page)
                        callback();
                    }
                }
                check();
            }
            
            waitForJQuery(function() {
                // Use window.jQuery instead of $ to avoid conflicts
                var $ = window.jQuery || window.$;
                if (typeof $ === 'undefined') {
                    console.error('jQuery not available');
                    return;
                }
                
                $(function() {
                    'use strict'
                    
                    // Only initialize Summernote if elements exist AND Summernote is loaded
                    var summernoteElements = $('.summernote');
                    if (summernoteElements.length > 0) {
                        waitForSummernote(function() {
                            // Double-check that Summernote is available before using it
                            if (typeof jQuery !== 'undefined' && typeof jQuery.fn !== 'undefined' && typeof jQuery.fn.summernote !== 'undefined') {
                                try {
                                    summernoteElements.summernote({
                                        height: 250,
                                    });
                                } catch (e) {
                                    console.warn('Failed to initialize Summernote:', e);
                                }
                            } else {
                                console.warn('Summernote plugin not available, skipping initialization');
                            }
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
    
    {{-- Stack scripts - should wrap jQuery-dependent code in waitForJQuery if needed --}}
    @stack('scripts')

</body>

</html>
