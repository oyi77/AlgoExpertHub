<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="{{ optional($page)->seo_description ?? 'AlgoExpertHub - Professional algorithmic trading bots for everyone. Automate your trading strategy with lightning-fast execution.' }}" />
    <meta name="keywords" content="trading bots, algorithmic trading, crypto trading, automated trading, DCA bots, grid trading" />
    <title>@yield('title', 'AlgoExpertHub - Automate Your Trading')</title>

    <link rel="shortcut icon" type="image/png" href="{{ Config::getFile('icon', optional(Config::config())->favicon, true) }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/bootstrap.min.css') }}">
    <link href="{{ asset('asset/css/tokens.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('asset/css/utilities.css') }}?v={{ time() }}" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-glow: rgba(59, 130, 246, 0.3);
            --success: #10b981;
            --success-500: #10b981;
            --success-glow: rgba(16, 185, 129, 0.3);
            --warning: #f59e0b;
            --info: #06b6d4;
            --danger: #ef4444;
            --dark: #030712;
            --darker: #020617;
            --surface: #111827;
            --surface-light: #1f2937;
            --text-main: #f9fafb;
            --text-dim: #9ca3af;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-gradient: linear-gradient(135deg, var(--primary), #8b5cf6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--dark);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            overflow-x: hidden;
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }

        .text-gradient {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Global Ticker */
        .global-ticker {
            background: rgba(3, 7, 18, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            height: 40px;
            display: flex;
            align-items: center;
            overflow: hidden;
            font-size: 0.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1000;
        }

        .ticker-content {
            display: flex;
            animation: ticker-scroll 30s linear infinite;
            white-space: nowrap;
        }

        .ticker-item {
            display: flex;
            align-items: center;
            padding: 0 40px;
            gap: 8px;
        }

        .ticker-symbol {
            font-weight: 700;
            color: var(--text-main);
        }

        .ticker-price {
            color: var(--text-dim);
        }

        .ticker-change {
            font-weight: 600;
        }

        .ticker-change.positive {
            color: var(--success);
        }

        .ticker-change.negative {
            color: var(--danger);
        }

        @keyframes ticker-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Navbar */
        .navbar {
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            position: sticky;
            top: 40px;
            z-index: 999;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(3, 7, 18, 0.95);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-link {
            color: var(--text-dim) !important;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem !important;
        }

        .nav-link:hover {
            color: var(--text-main) !important;
        }

        /* Hero Section */
        .hero-section {
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: var(--primary-glow);
            filter: blur(100px);
            border-radius: 50%;
            z-index: -1;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: var(--success-glow);
            filter: blur(100px);
            border-radius: 50%;
            z-index: -1;
        }

        .badge-glass {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-dim);
        }

        .badge-icon {
            font-size: 1.2rem;
        }

        .hero-headline {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-subheadline {
            font-size: clamp(1.125rem, 2vw, 1.5rem);
            color: var(--text-dim);
            margin-bottom: 2.5rem;
            max-width: 600px;
            line-height: 1.6;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        /* Shimmer Button */
        .btn-shimmer {
            position: relative;
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px var(--primary-glow);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
                rgba(255, 255, 255, 0.4) 50%,
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

        .btn-outline {
            padding: 16px 32px;
            border: 2px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-outline .icon {
            width: 20px;
            height: 20px;
        }

        /* Hero Stats */
        .hero-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            font-family: 'Space Grotesk', sans-serif;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-dim);
            margin-top: 4px;
        }

        .stat-divider {
            width: 1px;
            height: 40px;
            background: var(--glass-border);
        }

        /* Strategy Builder Mockup */
        .strategy-builder-mockup {
            position: relative;
            padding: 2rem;
        }

        .strategy-card {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.6;
            transform: scale(0.98);
        }

        .strategy-card.active {
            opacity: 1;
            border-color: var(--primary);
            box-shadow: 0 0 30px var(--primary-glow);
            transform: scale(1);
        }

        .strategy-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .strategy-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(59, 130, 246, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .strategy-card-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .strategy-card-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-dim);
        }

        .strategy-rule {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .rule-keyword {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.125rem;
        }

        .rule-select {
            padding: 8px 12px;
            background: var(--darker);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .strategy-connector {
            text-align: center;
            color: var(--primary);
            margin: 0.5rem 0;
            transition: transform 0.3s ease;
        }

        .strategy-card.active ~ .strategy-connector {
            transform: scale(1.1);
        }

        .strategy-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--success);
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-profit {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--success);
            font-weight: 700;
        }

        /* Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 3rem;
        }

        .bento-card {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .bento-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent-gradient);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .bento-card:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .bento-card:hover::before {
            transform: scaleX(1);
        }

        .bento-large {
            grid-column: span 2;
        }

        .bento-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .bento-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bento-icon.bg-primary {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }

        .bento-icon.bg-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .bento-icon.bg-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .bento-icon.bg-info {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info);
        }

        .bento-icon.bg-gray {
            background: rgba(156, 163, 175, 0.1);
            color: var(--text-dim);
        }

        .bento-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .bento-subtitle {
            font-size: 0.875rem;
            color: var(--text-dim);
        }

        .bento-description {
            color: var(--text-dim);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        /* Sparkline Chart */
        .sparkline-chart {
            margin-top: 1rem;
        }

        .sparkline {
            width: 100%;
            height: 60px;
        }

        /* Grid Visual */
        .grid-visual {
            margin: 1rem 0;
        }

        .price-channel {
            position: relative;
            height: 100px;
            background: var(--darker);
            border-radius: 8px;
            padding: 1rem;
        }

        .channel-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            font-size: 0.75rem;
            font-weight: 600;
            padding-left: 8px;
        }

        .buy-line {
            top: 20%;
            background: var(--success);
            color: var(--success);
        }

        .sell-line {
            bottom: 20%;
            background: var(--danger);
            color: var(--danger);
        }

        .price-indicator {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        /* Signal Badges */
        .signal-badges {
            display: flex;
            gap: 12px;
            margin: 1rem 0;
        }

        .signal-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--darker);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Paper Trading Toggle */
        .paper-trading-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1rem 0;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--surface-light);
            transition: 0.3s;
            border-radius: 26px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        .toggle-slider.active {
            background-color: var(--info);
        }

        .toggle-slider.active:before {
            transform: translateX(24px);
        }

        .toggle-label {
            font-weight: 600;
            color: var(--text-dim);
            transition: color 0.3s;
        }

        .toggle-label.active {
            color: var(--info);
        }

        .demo-mode {
            color: var(--info) !important;
        }

        /* Strategy Canvas */
        .strategy-canvas-section {
            background: var(--darker);
        }

        .canvas-container {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .canvas-header {
            margin-bottom: 2rem;
        }

        .canvas-tabs {
            display: flex;
            gap: 1rem;
        }

        .canvas-tab {
            padding: 12px 24px;
            background: transparent;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--text-dim);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .canvas-tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .strategy-sentence {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--darker);
            border-radius: 16px;
        }

        .sentence-keyword {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.125rem;
        }

        .sentence-dropdown {
            position: relative;
            padding: 12px 20px;
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            min-width: 150px;
        }

        .sentence-dropdown:hover {
            border-color: var(--primary);
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            overflow: hidden;
            z-index: 10;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .dropdown-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background: var(--darker);
        }

        .canvas-preview {
            margin-top: 2rem;
        }

        .preview-card {
            background: var(--darker);
            border: 1px solid var(--success);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .preview-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .preview-content p {
            color: var(--text-dim);
            margin-bottom: 1rem;
        }

        .btn-preview {
            padding: 10px 20px;
            background: var(--success);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-preview:hover {
            background: var(--success-500);
            transform: translateY(-2px);
        }

        /* Trust Section */
        .trust-section {
            background: var(--darker);
        }

        .trust-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .trust-card {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
        }

        .trust-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .trust-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            border-radius: 16px;
            background: rgba(59, 130, 246, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .trust-value {
            font-size: 2.5rem;
            font-weight: 800;
            font-family: 'Space Grotesk', sans-serif;
            margin-bottom: 0.5rem;
        }

        .trust-label {
            color: var(--text-dim);
            font-size: 0.875rem;
        }

        .security-card {
            grid-column: span 1;
            text-align: left;
        }

        .security-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .security-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
        }

        .security-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .security-description {
            color: var(--text-dim);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .security-features {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .security-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }

        /* Pricing Section */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--surface);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            transition: all 0.4s;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .pricing-featured {
            border-color: var(--primary);
            box-shadow: 0 0 30px var(--primary-glow);
            transform: scale(1.05);
        }

        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-gradient);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pricing-header {
            margin-bottom: 2rem;
        }

        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .pricing-price {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 800;
            font-family: 'Space Grotesk', sans-serif;
        }

        .price-period {
            color: var(--text-dim);
            font-size: 1rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0;
            color: var(--text-dim);
        }

        .pricing-cta {
            width: 100%;
            text-align: center;
            padding: 16px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: block;
        }

        /* Section Styles */
        .section-header {
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--text-dim);
        }

        /* Reveal Animation */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Footer */
        footer {
            background: var(--darker);
            border-top: 1px solid var(--glass-border);
            padding: 4rem 0 2rem;
            margin-top: 6rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand p {
            color: var(--text-dim);
            line-height: 1.8;
            margin-top: 1rem;
        }

        .footer-section h6 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--text-dim);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--text-main);
        }

        .footer-social {
            display: flex;
            gap: 1rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dim);
            transition: all 0.3s;
        }

        .footer-social a:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-bottom p {
            color: var(--text-dim);
            font-size: 0.875rem;
            margin: 0;
        }

        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }

        .footer-bottom-links a {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s;
        }

        .footer-bottom-links a:hover {
            color: var(--text-main);
        }

        .risk-warning {
            font-size: 0.75rem;
            color: var(--text-dim);
            opacity: 0.7;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
            line-height: 1.6;
        }

        /* Loading states */
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Improved focus states for accessibility */
        a:focus-visible,
        button:focus-visible,
        select:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Better mobile touch targets */
        @media (max-width: 768px) {
            .btn-shimmer,
            .btn-outline,
            .pricing-cta {
                min-height: 48px;
            }
        }

        /* Prevent layout shift during icon loading */
        [data-lucide] {
            display: inline-block;
            width: 1em;
            height: 1em;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }

            .bento-large {
                grid-column: span 1;
            }

            .trust-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-featured {
                transform: scale(1);
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero-cta {
                flex-direction: column;
            }

            .btn-shimmer,
            .btn-outline {
                width: 100%;
            }

            .trust-grid {
                grid-template-columns: 1fr;
            }

            .strategy-sentence {
                flex-direction: column;
                align-items: stretch;
            }

            .sentence-dropdown {
                width: 100%;
            }
        }
    </style>
    @stack('style')
</head>
<body>
    <!-- Global Ticker -->
    <div class="global-ticker" id="globalTicker">
        <div class="ticker-content">
            <div class="ticker-item">
                <span class="ticker-symbol">BTC/USDT</span>
                <span class="ticker-price">$96,432.50</span>
                <span class="ticker-change positive">+1.2%</span>
            </div>
            <div class="ticker-item">
                <span class="ticker-symbol">ETH/USDT</span>
                <span class="ticker-price">$3,421.20</span>
                <span class="ticker-change negative">-0.8%</span>
            </div>
            <div class="ticker-item">
                <span class="ticker-symbol">SOL/USDT</span>
                <span class="ticker-price">$192.45</span>
                <span class="ticker-change positive">+4.5%</span>
            </div>
            <!-- Duplicate for seamless loop -->
            <div class="ticker-item">
                <span class="ticker-symbol">BTC/USDT</span>
                <span class="ticker-price">$96,432.50</span>
                <span class="ticker-change positive">+1.2%</span>
            </div>
            <div class="ticker-item">
                <span class="ticker-symbol">ETH/USDT</span>
                <span class="ticker-price">$3,421.20</span>
                <span class="ticker-change negative">-0.8%</span>
            </div>
            <div class="ticker-item">
                <span class="ticker-symbol">SOL/USDT</span>
                <span class="ticker-price">$192.45</span>
                <span class="ticker-change positive">+4.5%</span>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                    <img src="{{ Config::getFile('logo', optional(Config::config())->logo, true) }}" alt="AlgoExpertHub" height="38">
                </a>
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#strategy-canvas">Strategies</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pricing">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Documentation</a>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <a href="{{ route('user.login') }}" class="btn-outline" style="padding: 10px 20px; font-size: 0.875rem;">Login</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a href="{{ route('user.register') }}" class="btn-shimmer" style="padding: 10px 24px; font-size: 0.875rem;">Sign Up</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="{{ Config::getFile('logo', optional(Config::config())->logo, true) }}" alt="AlgoExpertHub" height="30" class="mb-3">
                    <p>
                        Revolutionizing retail trading through institutional-grade automation and logic-based strategy builders. 
                        Funds never leave your exchange.
                    </p>
                </div>
                <div class="footer-section">
                    <h6>Product</h6>
                    <ul class="footer-links">
                        <li><a href="#features">Trading Bots</a></li>
                        <li><a href="#strategy-canvas">Strategy Builder</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">API</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h6>Resources</h6>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Tutorials</a></li>
                        <li><a href="#">Security</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h6>Company</h6>
                    <ul class="footer-links">
                        <li><a href="#">About</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ Config::config()->appname ?? 'AlgoExpertHub' }}. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
            
            <div class="risk-warning">
                <strong>Risk Warning:</strong> Trading cryptocurrencies involves substantial risk of loss. Past performance is not indicative of future results. 
                Only trade with funds you can afford to lose. Please read our Terms of Service and Risk Disclosure before using our platform.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        // Initialize Lucide icons
        function initIcons() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initIcons);
        } else {
            initIcons();
        }

        // Navbar scroll effect with throttling
        let ticking = false;
        function updateNavbar() {
            const navbar = document.getElementById('navbar');
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
            ticking = false;
        }
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(updateNavbar);
                ticking = true;
            }
        });

        // Reveal animations
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

        // Live price ticker (simulated - replace with real API)
        function updateTickerPrices() {
            // Simulate price updates
            const tickerItems = document.querySelectorAll('.ticker-item');
            tickerItems.forEach(item => {
                const changeEl = item.querySelector('.ticker-change');
                if (changeEl && Math.random() > 0.7) {
                    const isPositive = Math.random() > 0.5;
                    const change = (Math.random() * 5).toFixed(2);
                    changeEl.textContent = (isPositive ? '+' : '-') + change + '%';
                    changeEl.className = 'ticker-change ' + (isPositive ? 'positive' : 'negative');
                }
            });
        }

        // Update prices every 5 seconds
        setInterval(updateTickerPrices, 5000);
        
        // Smooth scroll polyfill for older browsers
        if (!('scrollBehavior' in document.documentElement.style)) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/gh/cferdinandi/smooth-scroll@15/dist/smooth-scroll.polyfills.min.js';
            document.head.appendChild(script);
        }
        
        // Re-initialize Lucide icons after dynamic content updates
        const lucideObserver = new MutationObserver(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
        
        lucideObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    </script>
    
    @stack('scripts')
</body>
</html>

