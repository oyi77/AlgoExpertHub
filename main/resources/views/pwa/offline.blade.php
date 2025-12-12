<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Offline - {{ config('app.name') }}</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .offline-container {
            max-width: 400px;
            width: 100%;
        }
        
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .offline-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .offline-message {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .retry-button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-height: 44px; /* Touch-friendly */
            min-width: 44px;
        }
        
        .retry-button:hover,
        .retry-button:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        .retry-button:active {
            transform: translateY(0);
        }
        
        .connection-status {
            margin-top: 2rem;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .connection-status.online {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .connection-status.offline {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        /* Mobile optimizations */
        @media (max-width: 480px) {
            .offline-title {
                font-size: 1.5rem;
            }
            
            .offline-message {
                font-size: 1rem;
            }
            
            .retry-button {
                width: 100%;
                padding: 16px 24px;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .retry-button {
                padding: 16px 24px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body class="{{ implode(' ', $deviceClasses ?? []) }}">
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1 class="offline-title">You're Offline</h1>
        <p class="offline-message">
            It looks like you've lost your internet connection. Don't worry, you can still browse some content that was previously loaded.
        </p>
        
        <button class="retry-button" onclick="window.location.reload()">
            Try Again
        </button>
        
        <div id="connection-status" class="connection-status offline">
            Connection Status: Offline
        </div>
    </div>

    <script>
        // Monitor connection status
        function updateConnectionStatus() {
            const statusElement = document.getElementById('connection-status');
            if (navigator.onLine) {
                statusElement.textContent = 'Connection Status: Online';
                statusElement.className = 'connection-status online';
                // Auto-reload when back online
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                statusElement.textContent = 'Connection Status: Offline';
                statusElement.className = 'connection-status offline';
            }
        }

        // Listen for connection changes
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);

        // Initial status check
        updateConnectionStatus();

        // Retry button functionality
        document.querySelector('.retry-button').addEventListener('click', function() {
            this.textContent = 'Retrying...';
            this.disabled = true;
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });

        // Touch feedback for mobile devices
        if ('ontouchstart' in window) {
            document.querySelector('.retry-button').addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            document.querySelector('.retry-button').addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        }
    </script>
</body>
</html>