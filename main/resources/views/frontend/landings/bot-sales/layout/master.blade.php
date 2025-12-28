<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name')) - {{ __('Automated Trading Bots') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    },
                    animation: {
                        'shimmer': 'shimmer 2s linear infinite',
                        'spin-slow': 'spin 3s linear infinite',
                    },
                    keyframes: {
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        }
                    }
                }
            }
        }
    </script>

    <style type="text/tailwindcss">
        @layer components {
            .glass {
                @apply bg-white/10 backdrop-blur-lg border border-white/20 shadow-xl;
            }
            .glass-dark {
                @apply bg-dark-900/40 backdrop-blur-xl border border-white/10 shadow-2xl;
            }
            .btn-shimmer {
                @apply relative overflow-hidden bg-primary-600 text-white transition-all duration-300;
                background-image: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                background-size: 200% 100%;
            }
            .btn-shimmer:hover {
                @apply bg-primary-700 shadow-lg shadow-primary-500/30 scale-105;
                animation: shimmer 1.5s infinite;
            }
        }
        
        body {
            @apply bg-dark-950 text-dark-100 overflow-x-hidden;
        }

        .gradient-bg {
            background: radial-gradient(circle at 10% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 40%);
        }
    </style>

    @stack('styles')
</head>
<body class="gradient-bg">
    @include('frontend.landings.bot-sales.layout.header')

    <main>
        @yield('content')
    </main>

    @include('frontend.landings.bot-sales.layout.footer')

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
    @stack('scripts')
</body>
</html>
