@php
    $js_alerts = [];

    // Laravel Notify (Primary)
    if (session()->has('notify')) {
        $notify = session('notify');
        if (is_array($notify)) {
            $js_alerts[] = [
                'type' => $notify['type'] ?? 'success',
                'title' => $notify['title'] ?? '',
                'message' => $notify['message'] ?? '',
                'duration' => $notify['duration'] ?? null,
            ];
        }
    }

    // Legacy session flash messages
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type)) {
            $js_alerts[] = [
                'type' => $type,
                'title' => ucfirst($type),
                'message' => session($type),
            ];
        }
    }

    // Validation errors
    if ($errors->any()) {
        foreach ($errors->all() as $error) {
            $js_alerts[] = [
                'type' => 'error',
                'title' => 'Validation Error',
                'message' => $error,
            ];
        }
    }
@endphp

<script>
    'use strict';
    
    // In backend, we use AdminCore loader if available, otherwise DOMContentLoaded
    (function() {
        // Safe injection of PHP data into JS
        var alerts = <?php echo json_encode($js_alerts); ?>;

        var showNotify = function() {
            if (typeof notify === 'undefined') {
                console.warn('Notify.js not loaded');
                return;
            }

            if (!Array.isArray(alerts) || alerts.length === 0) return;

            alerts.forEach(function(alert) {
                try {
                    var type = (alert.type || 'success').toLowerCase();
                    if (!['success', 'error', 'warning', 'info'].includes(type)) {
                        type = 'success';
                    }

                    var notifyChain = notify()[type]();

                    if (alert.title) {
                        notifyChain = notifyChain.title(alert.title);
                    }
                    
                    if (alert.message) {
                        notifyChain = notifyChain.message(alert.message);
                    }
                    
                    if (alert.duration) {
                        notifyChain = notifyChain.duration(parseInt(alert.duration));
                    }
                    
                    notifyChain.send();
                } catch(e) {
                    console.error('Error showing notification:', e);
                }
            });
            
            // Clear alerts to prevent duplicate showing
            alerts = [];
        };

        // If AdminCore is available (from our frontend optimization), use it
        if (typeof window.AdminCore !== 'undefined' && typeof window.AdminCore.loader !== 'undefined') {
            window.addEventListener('notify-loaded', showNotify);
            if (typeof notify !== 'undefined') showNotify();
        } else {
            // Fallback for standard DOM usage
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof notify !== 'undefined') {
                    showNotify();
                } else {
                    var attempts = 0;
                    var interval = setInterval(function() {
                        if (typeof notify !== 'undefined') {
                            clearInterval(interval);
                            showNotify();
                        } else {
                            attempts++;
                            if (attempts >= 40) clearInterval(interval);
                        }
                    }, 50);
                }
            });
        }
    })();
</script>