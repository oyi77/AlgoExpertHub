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
    @if ($alertType === 'izi')
        <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'izitoast.min.css') }}">
    @elseif($alertType === 'toast')
        <link href="{{ Config::cssLib('frontend', 'toastr.min.css') }}" rel="stylesheet">
    @else
        <link href="{{ Config::cssLib('frontend', 'sweetalert.min.css') }}" rel="stylesheet">
    @endif

    @stack('external-css')

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'main.css') }}?v=20251202">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'helper.css') }}?v=20251202">
    {{-- Trading Landing Theme CSS - Provides CSS variables for inheritance --}}
    <link href="{{ asset('asset/css/trading-landing.css') }}?v={{ time() }}" rel="stylesheet">
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
    
    <!-- Trading Landing Dark Theme for User Panel -->
    <style>
        /* Trading Landing Dark Theme - Uses CSS variables from trading-landing.css */
        body.user-pages-body,
        .user-pages-body {
            background-color: var(--trading-neutral-060f11) !important;
            color: var(--trading-neutral-fdfd) !important;
        }
        body.user-pages-body .dashboard-main,
        .user-pages-body .dashboard-main,
        body.user-pages-body main.dashboard-main,
        .user-pages-body main.dashboard-main {
            background-color: var(--trading-neutral-060f11) !important;
            color: var(--trading-neutral-fdfd) !important;
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
        <button type="button" class="sidebar-toggeler"><i class="las la-bars"></i></button>

        <a href="{{ route('user.dashboard') }}" class="site-logo">
            <img src="{{ Config::getFile('logo', optional(Config::config())->logo ?? '', true) }}" alt="image">
        </a>



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

    <main class="dashboard-main">
        <div class="dashboard-content-wrapper">
            @yield('content')
        </div>
    </main>

    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/wow.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.paroller.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/slick.min.js') }}"></script>

    @stack('external-script')


    @if ($alertType === 'izi')
        <script src="{{ Config::jsLib('frontend', 'izitoast.min.js') }}"></script>
    @elseif($alertType === 'toast')
        <script src="{{ Config::jsLib('frontend', 'toastr.min.js') }}"></script>
    @else
        <script src="{{ Config::jsLib('frontend', 'sweetalert.min.js') }}"></script>
    @endif

    <script src="{{ Config::jsLib('frontend', 'main.js') }}"></script>

    @include('alert')

    @stack('script')

    <script>
        'use strict'
        
        // Wait for jQuery and DOM to be ready
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function($) {
                // Initialize sidebar state based on screen size
                function initSidebarState() {
                    var windowWidth = $(window).width();
                    if (windowWidth <= 1199) {
                        // Mobile/Tablet: Start hidden (no active class)
                        $('.user-sidebar').removeClass('active');
                        $('.dashboard-main').removeClass('active');
                    } else {
                        // Desktop: Always visible (add active class for consistency)
                        $('.user-sidebar').addClass('active');
                    }
                }
                
                // Initialize on page load
                initSidebarState();
                
                // Re-initialize on window resize
                $(window).on('resize', function() {
                    initSidebarState();
                });
                
                // Sidebar menu submenu toggle
                $(".sidebar-menu>li>a").each(function() {
                    let submenuParent = $(this).parent('li');
                    $(this).on('click', function() {
                        submenuParent.toggleClass('open')
                    })
                });

                // Sidebar toggle from header button
                $('.sidebar-toggeler').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var windowWidth = $(window).width();
                    
                    // Only toggle on mobile/tablet
                    if (windowWidth <= 1199) {
                        var currentState = $('.user-sidebar').hasClass('active');
                        console.log('Sidebar toggle clicked (mobile)');
                        console.log('Current state:', currentState);
                        console.log('Window width:', windowWidth);
                        
                        $('.user-sidebar').toggleClass('active');
                        $('.dashboard-main').toggleClass('active');
                        
                        var newState = $('.user-sidebar').hasClass('active');
                        console.log('New state:', newState);
                        console.log('Sidebar computed left:', $('.user-sidebar').css('left'));
                        console.log('Sidebar computed visibility:', $('.user-sidebar').css('visibility'));
                        console.log('Sidebar computed opacity:', $('.user-sidebar').css('opacity'));
                    } else {
                        console.log('Sidebar toggle clicked (desktop - no action needed)');
                    }
                    
                    return false;
                });

                // Sidebar close button (inside sidebar)
                $('.sidebar-close-btn').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Sidebar close clicked');
                    $('.user-sidebar').removeClass('active');
                    $('.dashboard-main').removeClass('active');
                    return false;
                });

                // Mobile bottom menu sidebar toggle
                $('.sidebar-open-btn').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Mobile menu toggle clicked');
                    $(this).toggleClass('active');
                    $('.user-sidebar').toggleClass('active');
                    $('.dashboard-main').toggleClass('active');
                    return false;
                });

                // Close sidebar when clicking overlay or outside (on mobile only)
                $(document).on('click', function(e) {
                    // Only handle on mobile/tablet
                    if ($(window).width() <= 1199 && $('.user-sidebar').hasClass('active')) {
                        // Check if click is outside sidebar and not on any toggle buttons
                        var target = $(e.target);
                        var isSidebar = target.closest('.user-sidebar').length > 0;
                        var isToggle = target.closest('.sidebar-toggeler').length > 0;
                        var isCloseBtn = target.closest('.sidebar-close-btn').length > 0;
                        var isOpenBtn = target.closest('.sidebar-open-btn').length > 0;
                        
                        if (!isSidebar && !isToggle && !isCloseBtn && !isOpenBtn) {
                            console.log('Closing sidebar - clicked outside');
                            $('.user-sidebar').removeClass('active');
                            $('.dashboard-main').removeClass('active');
                        }
                    }
                });
            });
        } else {
            console.error('jQuery is not loaded!');
        }
    </script>

</body>

</html>
