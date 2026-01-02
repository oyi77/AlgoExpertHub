<script>
    'use strict';
    
    // Wait for jQuery and notify to be available before showing notifications
    (function() {
        function showNotifications() {
            if (typeof notify === 'undefined') {
                return;
            }

            @php
                $alertType = optional(Config::config())->alert ?? 'notify';
            @endphp

            {{-- Laravel Notify (Primary - Always enabled after migration) --}}
            @if (session()->has('notify'))
                @php
                    $notify = session('notify');
                @endphp
                @if (is_array($notify))
                    try {
                        @php
                            $notifyType = isset($notify['type']) && in_array(strtolower($notify['type']), ['success', 'error', 'warning', 'info']) 
                                ? strtolower($notify['type']) 
                                : 'success';
                            $notifyTitle = isset($notify['title']) ? addslashes($notify['title']) : '';
                            $notifyMessage = isset($notify['message']) ? addslashes($notify['message']) : '';
                            $notifyDuration = isset($notify['duration']) ? (int)$notify['duration'] : null;
                        @endphp
                        
                        // Build notification chain using the same pattern as other notifications
                        var notifyChain = notify().{{ $notifyType }}();
                        
                        @if(!empty($notifyTitle))
                            if (notifyChain && typeof notifyChain.title === 'function') {
                                notifyChain = notifyChain.title('{{ $notifyTitle }}');
                            }
                        @endif
                        
                        @if(!empty($notifyMessage))
                            if (notifyChain && typeof notifyChain.message === 'function') {
                                notifyChain = notifyChain.message('{{ $notifyMessage }}');
                            }
                        @endif
                        
                        @if($notifyDuration !== null)
                            if (notifyChain && typeof notifyChain.duration === 'function') {
                                notifyChain = notifyChain.duration({{ $notifyDuration }});
                            }
                        @endif
                        
                        if (notifyChain && typeof notifyChain.send === 'function') {
                            notifyChain.send();
                        }
                    } catch(e) {
                        console.error('Error showing notification:', e);
                    }
                @endif
            @endif

            {{-- Legacy session flash messages (backward compatibility) --}}
            @if (session()->has('error'))
                try {
                    notify()
                        .error()
                        .title('Error')
                        .message("{{ addslashes(session('error')) }}")
                        .send();
                } catch(e) {
                    console.error('Error showing error notification:', e);
                }
            @endif

            @if (session()->has('success'))
                try {
                    notify()
                        .success()
                        .title('Success')
                        .message("{{ addslashes(session('success')) }}")
                        .send();
                } catch(e) {
                    console.error('Error showing success notification:', e);
                }
            @endif

            @if (session()->has('warning'))
                try {
                    notify()
                        .warning()
                        .title('Warning')
                        .message("{{ addslashes(session('warning')) }}")
                        .send();
                } catch(e) {
                    console.error('Error showing warning notification:', e);
                }
            @endif

            @if (session()->has('info'))
                try {
                    notify()
                        .info()
                        .title('Info')
                        .message("{{ addslashes(session('info')) }}")
                        .send();
                } catch(e) {
                    console.error('Error showing info notification:', e);
                }
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    try {
                        notify()
                            .error()
                            .title('Validation Error')
                            .message("{{ addslashes($error) }}")
                            .send();
                    } catch(e) {
                        console.error('Error showing validation error:', e);
                    }
                @endforeach
            @endif
        }

        // Wait for both jQuery and notify to be available
        function waitForDependencies() {
            if (typeof window.jQuery !== 'undefined' && typeof notify !== 'undefined') {
                showNotifications();
                return;
            }
            
            // Wait for notify-loaded event
            var notifyHandler = function() {
                window.removeEventListener('notify-loaded', notifyHandler);
                // Give it a moment to initialize
                setTimeout(function() {
                    if (typeof notify !== 'undefined') {
                        showNotifications();
                    } else {
                        // Try polling as fallback
                        var attempts = 0;
                        var maxAttempts = 20;
                        var checkInterval = setInterval(function() {
                            attempts++;
                            if (typeof notify !== 'undefined') {
                                clearInterval(checkInterval);
                                showNotifications();
                            } else if (attempts >= maxAttempts) {
                                clearInterval(checkInterval);
                                console.warn('Notify library not available after event');
                            }
                        }, 100);
                    }
                }, 100);
            };
            window.addEventListener('notify-loaded', notifyHandler, { once: true });
            
            // Also poll as fallback (max 5 seconds)
            var attempts = 0;
            var maxAttempts = 50;
            var checkInterval = setInterval(function() {
                attempts++;
                if (typeof window.jQuery !== 'undefined' && typeof notify !== 'undefined') {
                    clearInterval(checkInterval);
                    window.removeEventListener('notify-loaded', notifyHandler);
                    showNotifications();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    window.removeEventListener('notify-loaded', notifyHandler);
                    console.warn('Dependencies not loaded after 5 seconds');
                }
            }, 100);
        }
        
        waitForDependencies();
    })();
</script>
