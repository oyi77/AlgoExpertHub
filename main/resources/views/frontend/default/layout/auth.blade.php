<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="description" content="{{ $page->seo_description ?? optional(Config::config())->seo_description ?? '' }}" />
    <meta name="keywords" content="{{ implode(",", is_array($page->seo_keywords ?? optional(Config::config())->seo_tags ?? []) ? ($page->seo_keywords ?? optional(Config::config())->seo_tags ?? []) : []) }} ">

    <title>{{ optional(Config::config())->appname ?? 'AlgoExpert Hub' }}</title>

    <link rel="shortcut icon" type="image/png" href="{{ Config::getFile('icon', optional(Config::config())->favicon ?? '', true) }}">

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'all.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'line-awesome.min.css') }}">

    @php
        $alertType = optional(Config::config())->alert ?? 'sweetalert';
    @endphp
    {{-- Laravel Notify CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/notify/notify.css') }}">

    @stack('external-css')

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'main.css') }}?v=20251202">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'helper.css') }}?v=20251202">
    @php
        $theme = \App\Models\Configuration::first()->theme ?? 'default';
        $menuGroupsPath = public_path('asset/frontend/' . $theme . '/css/menu-groups.css');
        $userPanelThemePath = public_path('asset/frontend/' . $theme . '/css/user-panel-admin-theme.css');
    @endphp
    @if(file_exists($menuGroupsPath))
        <style>
            {!! file_get_contents($menuGroupsPath) !!}
        </style>
    @else
        <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'menu-groups.css') }}?v=20251202">
    @endif
    <!-- User Panel Admin Theme Override - Light background like admin (MUST be loaded LAST to override dark theme) -->
    @if(file_exists($userPanelThemePath))
        <link rel="stylesheet" href="{{ asset('asset/frontend/' . $theme . '/css/user-panel-admin-theme.css') }}?v=20251208_2" media="all">
    @else
        <link rel="stylesheet" href="{{ asset('asset/frontend/default/css/user-panel-admin-theme.css') }}?v=20251208_2" media="all">
    @endif
    
    <!-- Inline style as final override to ensure light background -->
    <style>
        /* Final override - ensure light background on all user pages */
        body.user-pages-body,
        .user-pages-body {
            background-color: #F1F5F9 !important;
            background: #F1F5F9 !important;
        }
        body.user-pages-body .dashboard-main,
        .user-pages-body .dashboard-main,
        body.user-pages-body main.dashboard-main,
        .user-pages-body main.dashboard-main {
            background-color: #F1F5F9 !important;
            background: #F1F5F9 !important;
        }
    </style>

    @stack('style')


    @php
        $config = Config::config();
        $heading = optional($config)->fonts ? optional($config->fonts)->heading_font_family : 'DM Sans';
        $paragraph = optional($config)->fonts ? optional($config->fonts)->paragraph_font_family : 'Poppins';
    @endphp
    <style>
        :root {
            --h-font: <?=$heading ?>;
            --p-font: <?=$paragraph ?>;
        }
    </style>

</head>

<body class="user-pages-body">

    @include(Config::themeView('layout.user_sidebar_new'))

    <header class="user-header">
        <a href="{{ route('user.dashboard') }}" class="site-logo">
            <img src="{{ Config::getFile('logo', optional(Config::config())->logo ?? '', true) }}" alt="image">
        </a>

        <button type="button" class="sidebar-toggeler"><i class="las la-bars"></i></button>



        <div class="dropdown user-dropdown">
            <a type="button" target="_blank" href="{{ route('home') }}"
                class="btn sp_theme_btn btn-sm">{{ __('Visit Home') }}</a>
            <button class="user-btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                aria-expanded="false">
                <img src="{{ Config::getFile('user', auth()->user()->image, true) }}" alt="image">
                <span>{{ auth()->user()->username }}</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                <li><a class="dropdown-item" href="{{ route('user.profile') }}"><i class="far fa-user-circle me-2"></i>
                        {{ __('Profile') }}</a></li>
                <li><a class="dropdown-item" href="{{ route('user.2fa') }}"><i class="fas fa-cog me-2"></i>
                        {{ __('2FA') }}</a></li>
                <li><a class="dropdown-item" href="{{ route('user.logout') }}"><i class="fas fa-sign-out-alt me-2"></i>
                        {{ __('Logout') }}</a></li>
            </ul>
        </div>
    </header>

    <!-- TEST BANNER - SHOULD BE ALWAYS VISIBLE -->
    <div style="background: red; color: white; padding: 20px; text-align: center; font-size: 24px; font-weight: bold;">
        TEST BANNER - If you see this, the layout file is working!
    </div>

    <!-- Beta UI Banner (Persistent, Dismissible) -->
    <div id="beta-ui-banner" class="beta-banner">
        <div class="container-fluid">
            <div class="beta-banner-content">
                <div class="beta-banner-message">
                    <i class="fas fa-flask me-2"></i>
                    <span>{{ __('We have new UI if you might want to try it out.') }}</span>
                </div>
                <div class="beta-banner-actions">
                    <a href="{{ route('user.beta.dashboard') }}" class="btn btn-sm btn-primary me-2">
                        {{ __('Try New UI') }}
                    </a>
                    <button type="button" class="btn btn-sm btn-link text-white" onclick="dismissBetaBanner()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .beta-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .beta-banner-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .beta-banner-message {
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .beta-banner-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .beta-banner .btn-primary {
            background-color: white;
            color: #667eea;
            border: none;
            font-weight: 600;
        }
        .beta-banner .btn-primary:hover {
            background-color: #f8f9fa;
            color: #5568d3;
        }
        .beta-banner .btn-link {
            padding: 0.25rem 0.5rem;
            text-decoration: none;
        }
        .beta-banner.dismissed {
            display: none;
        }
        @media (max-width: 768px) {
            .beta-banner-content {
                flex-direction: column;
                align-items: stretch;
            }
            .beta-banner-actions {
                justify-content: space-between;
            }
        }
    </style>

    <script>
        // Hide banner immediately if previously dismissed
        (function() {
            const dismissed = localStorage.getItem('beta_banner_dismissed');
            if (dismissed) {
                const banner = document.getElementById('beta-ui-banner');
                if (banner) {
                    banner.classList.add('dismissed');
                }
            }
        })();

        // Dismiss the beta banner
        function dismissBetaBanner() {
            localStorage.setItem('beta_banner_dismissed', 'true');
            const banner = document.getElementById('beta-ui-banner');
            if (banner) {
                banner.classList.add('dismissed');
            }
        }
    </script>

    <main class="dashboard-main">
        @yield('content')
    </main>

    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/wow.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.paroller.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/slick.min.js') }}"></script>

    @stack('external-script')


    {{-- Laravel Notify JavaScript --}}
    <script defer src="{{ asset('vendor/notify/notify.js') }}"></script>

    <script src="{{ Config::jsLib('frontend', 'main.js') }}"></script>

    @include('alert')

    @stack('scripts')

    <script>
        'use strict'


        $(".sidebar-menu>li>a").each(function() {
            let submenuParent = $(this).parent('li');

            $(this).on('click', function() {
                submenuParent.toggleClass('open')
            })
        });

        $('.sidebar-open-btn').on('click', function() {
            $(this).toggleClass('active');
            $('.user-sidebar').toggleClass('active');
            $('.dashboard-main').toggleClass('active');
        });
    </script>

</body>

</html>
