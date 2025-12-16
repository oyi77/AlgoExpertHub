<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Install App - {{ config('app.name') }}</title>
    
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
        
        .install-container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .app-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .install-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .install-subtitle {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 2rem;
        }
        
        .features-list {
            text-align: left;
            margin-bottom: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .feature-icon {
            width: 24px;
            height: 24px;
            background: rgba(76, 175, 80, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .install-buttons {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        .install-button {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-height: 48px; /* Touch-friendly */
        }
        
        .install-button:hover,
        .install-button:focus {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .install-button:active {
            transform: translateY(0);
        }
        
        .install-button.secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .install-button.secondary:hover,
        .install-button.secondary:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .install-instructions {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 0.9rem;
            line-height: 1.5;
            display: none;
        }
        
        .install-instructions.show {
            display: block;
        }
        
        /* Mobile optimizations */
        @media (max-width: 480px) {
            .install-container {
                padding: 1.5rem;
            }
            
            .install-title {
                font-size: 1.5rem;
            }
            
            .install-buttons {
                gap: 16px;
            }
            
            .install-button {
                padding: 16px 24px;
                font-size: 1.1rem;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .install-button {
                padding: 18px 24px;
                font-size: 1.1rem;
            }
        }
        
        @media (min-width: 481px) {
            .install-buttons {
                flex-direction: row;
            }
            
            .install-button {
                flex: 1;
            }
        }
    </style>
</head>
<body class="{{ implode(' ', $deviceClasses ?? []) }}">
    <div class="install-container">
        <div class="app-icon">📱</div>
        <h1 class="install-title">Install {{ config('app.name') }}</h1>
        <p class="install-subtitle">Get the full app experience with offline access and push notifications</p>
        
        <div class="features-list">
            <div class="feature-item">
                <div class="feature-icon">✓</div>
                <span>Works offline - access your signals anytime</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">✓</div>
                <span>Push notifications for new signals</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">✓</div>
                <span>Faster loading and better performance</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">✓</div>
                <span>Native app-like experience</span>
            </div>
        </div>
        
        <div class="install-buttons">
            <button id="install-button" class="install-button" style="display: none;">
                Install App
            </button>
            <button id="manual-install-button" class="install-button secondary">
                Manual Install
            </button>
            <a href="/" class="install-button secondary">
                Continue in Browser
            </a>
        </div>
        
        <div id="install-instructions" class="install-instructions">
            <strong>Manual Installation:</strong><br>
            <span id="ios-instructions" style="display: none;">
                1. Tap the Share button in Safari<br>
                2. Scroll down and tap "Add to Home Screen"<br>
                3. Tap "Add" to install the app
            </span>
            <span id="android-instructions" style="display: none;">
                1. Tap the menu button (⋮) in your browser<br>
                2. Tap "Add to Home screen" or "Install app"<br>
                3. Follow the prompts to install
            </span>
            <span id="desktop-instructions" style="display: none;">
                1. Look for the install icon in your browser's address bar<br>
                2. Click it and follow the prompts<br>
                3. Or use your browser's menu to "Install" or "Add to desktop"
            </span>
        </div>
    </div>

    <script>
        let deferredPrompt;
        const installButton = document.getElementById('install-button');
        const manualInstallButton = document.getElementById('manual-install-button');
        const installInstructions = document.getElementById('install-instructions');

        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            // Show the install button
            installButton.style.display = 'block';
        });

        // Handle install button click
        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                // Show the install prompt
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                // Clear the deferredPrompt variable
                deferredPrompt = null;
                // Hide the install button
                installButton.style.display = 'none';
            }
        });

        // Handle manual install button click
        manualInstallButton.addEventListener('click', () => {
            installInstructions.classList.toggle('show');
            
            // Show appropriate instructions based on device
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            
            document.getElementById('ios-instructions').style.display = isIOS ? 'block' : 'none';
            document.getElementById('android-instructions').style.display = isAndroid ? 'block' : 'none';
            document.getElementById('desktop-instructions').style.display = (!isIOS && !isAndroid) ? 'block' : 'none';
        });

        // Listen for the appinstalled event
        window.addEventListener('appinstalled', (evt) => {
            console.log('App was installed');
            // Redirect to the app or show success message
            setTimeout(() => {
                window.location.href = '/';
            }, 1000);
        });

        // Check if app is already installed
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            // App is already installed, redirect to main app
            window.location.href = '/';
        }

        // Touch feedback for mobile devices
        if ('ontouchstart' in window) {
            document.querySelectorAll('.install-button').forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }
    </script>
</body>
</html>