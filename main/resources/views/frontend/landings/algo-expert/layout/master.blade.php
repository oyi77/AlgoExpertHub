<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="{{ optional($page)->seo_description ?? Config::config()->seo_description }}" />
    <meta name="keywords" content="{{ is_array(optional($page)->seo_keywords ?? Config::config()->seo_tags) ? implode(',', optional($page)->seo_keywords ?? Config::config()->seo_tags) : (optional($page)->seo_keywords ?? Config::config()->seo_tags) }}" />
    <title>@yield('title', Config::config()->appname)</title>

    <link rel="shortcut icon" type="image/png" href="{{ Config::getFile('icon', optional(Config::config())->favicon, true) }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'all.min.css') }}">
    
    <style>
        :root {
            --primary: #3b82f6; /* Trust Blue from tokens */
            --primary-glow: rgba(59, 130, 246, 0.5);
            --success: #10b981; /* Performance Green from tokens */
            --success-glow: rgba(16, 185, 129, 0.5);
            --dark: #030712; /* Gray-950 from tokens */
            --darker: #020617;
            --surface: #111827; /* Gray-900 */
            --surface-light: #1f2937; /* Gray-800 */
            --text-main: #f9fafb;
            --text-dim: #9ca3af;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-gradient: linear-gradient(135deg, var(--primary), #8b5cf6);
        }

        body {
            background-color: var(--dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        .text-gradient {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Nav Marquee */
        .top-marquee {
            background: var(--darker);
            border-bottom: 1px solid var(--glass-border);
            height: 40px;
            display: flex;
            align-items: center;
            overflow: hidden;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .marquee-content {
            display: flex;
            animation: marquee 30s linear infinite;
            white-space: nowrap;
        }

        .marquee-item {
            display: flex;
            align-items: center;
            padding: 0 40px;
        }

        .price-up { color: var(--success); }
        .price-down { color: #ef4444; }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Buttons */
        .btn-shimmer {
            position: relative;
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 14px 34px;
            border-radius: 12px;
            font-weight: 700;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px var(--primary-glow);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-shimmer::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: skewX(-25deg);
            transition: 0.75s;
        }

        .btn-shimmer:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 35px var(--primary-glow);
            color: white;
        }

        .btn-shimmer:hover::before {
            left: 125%;
        }

        /* Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 24px;
        }

        .bento-item {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .bento-item:hover {
            border-color: var(--primary);
            transform: scale(1.02);
            background: rgba(17, 24, 39, 0.8);
        }

        /* Glow effects */
        .glow-box {
            position: absolute;
            width: 200px;
            height: 200px;
            background: var(--primary-glow);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
        }

        .navbar {
            background: rgba(3, 7, 18, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        footer {
            background: var(--darker);
            border-top: 1px solid var(--glass-border);
            padding: 100px 0 50px;
        }
    </style>
    @stack('style')
    @livewireStyles
</head>
<body>

    <div class="top-marquee">
        <div class="marquee-content">
            <div class="marquee-item">BTC/USDT <span class="price-up ml-2">$96,432.50 (+1.2%)</span></div>
            <div class="marquee-item">ETH/USDT <span class="price-down ml-2">$3,421.20 (-0.8%)</span></div>
            <div class="marquee-item">SOL/USDT <span class="price-up ml-2">$192.45 (+4.5%)</span></div>
            <div class="marquee-item">BNB/USDT <span class="price-up ml-2">$642.10 (+0.5%)</span></div>
            <!-- Duplicate for seamless loop -->
            <div class="marquee-item">BTC/USDT <span class="price-up ml-2">$96,432.50 (+1.2%)</span></div>
            <div class="marquee-item">ETH/USDT <span class="price-down ml-2">$3,421.20 (-0.8%)</span></div>
            <div class="marquee-item">SOL/USDT <span class="price-up ml-2">$192.45 (+4.5%)</span></div>
            <div class="marquee-item">BNB/USDT <span class="price-up ml-2">$642.10 (+0.5%)</span></div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ Config::getFile('logo', optional(Config::config())->logo, true) }}" alt="logo" height="38">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link font-semibold text-dim" href="#features">Solutions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-semibold text-dim" href="#strategies">Strategies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-semibold text-dim" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item ms-lg-4">
                        <a href="{{ route('user.login') }}" class="btn-shimmer py-2 px-4" style="font-size: 0.8rem;">Login</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a href="{{ route('user.register') }}" class="btn btn-outline-light font-bold" style="border-radius: 12px; padding: 10px 24px;">Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <img src="{{ Config::getFile('logo', optional(Config::config())->logo, true) }}" alt="logo" height="30" class="mb-4">
                    <p class="text-dim" style="line-height: 1.8;">Revolutionizing retail trading through institutional-grade automation and logic-based strategy builders. Funds never leave your exchange.</p>
                </div>
                <div class="col-6 col-lg-2 offset-lg-2">
                    <h6 class="mb-4">Platform</h6>
                    <ul class="list-unstyled text-dim">
                        <li class="mb-2"><a href="#" class="text-inherit">Trading Bots</a></li>
                        <li class="mb-2"><a href="#" class="text-inherit">Signals</a></li>
                        <li class="mb-2"><a href="#" class="text-inherit">Backtesting</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="mb-4">Resources</h6>
                    <ul class="list-unstyled text-dim">
                        <li class="mb-2"><a href="#" class="text-inherit">Docs</a></li>
                        <li class="mb-2"><a href="#" class="text-inherit">API</a></li>
                        <li class="mb-2"><a href="#" class="text-inherit">Security</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-4">Community</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-dim"><i data-lucide="twitter"></i></a>
                        <a href="#" class="text-dim"><i data-lucide="send"></i></a>
                        <a href="#" class="text-dim"><i data-lucide="message-square"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-5" style="border-color: var(--glass-border);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <p class="text-dim small mb-0">&copy; {{ date('Y') }} {{ Config::config()->appname }}. Non-custodial Crypto Trading.</p>
                <div class="d-flex gap-4 small text-dim">
                    <a href="#" class="text-inherit">Privacy Policy</a>
                    <a href="#" class="text-inherit">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Reveal animations
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    </script>
    @stack('script')
    @livewireScripts
</body>
</html>
