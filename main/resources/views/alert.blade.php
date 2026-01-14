@php
    $alerts = [];

    // Laravel Notify (Primary)
    if (session()->has('notify')) {
        $notify = session('notify');
        if (is_array($notify)) {
            $alerts[] = [
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
            $alerts[] = [
                'type' => $type,
                'title' => ucfirst($type),
                'message' => session($type),
            ];
        }
    }

    // Validation errors
    if ($errors->any()) {
        foreach ($errors->all() as $error) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Validation Error',
                'message' => $error,
            ];
        }
    }
@endphp

<script>
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Pass PHP alerts to JS
        var alerts = <?php echo json_encode($alerts); ?>;

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

        // Try to show notification immediately if notify is ready, otherwise wait slightly
        if (typeof notify !== 'undefined') {
            showNotify();
        } else {
            // Wait for notify.js to load
            var attempts = 0;
            var interval = setInterval(function() {
                if (typeof notify !== 'undefined') {
                    clearInterval(interval);
                    showNotify();
                } else {
                    attempts++;
                    if (attempts >= 40) { // 2 seconds timeout
                        clearInterval(interval);
                        console.error('Notify.js failed to load within timeout');
                    }
                }
            }, 50);
        }
    });
</script>