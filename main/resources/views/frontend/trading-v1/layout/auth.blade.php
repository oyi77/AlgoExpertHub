@php
    use App\Helpers\Helper\Helper;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', Config::config()->appname ?? 'AlgoExpertHub')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ Config::getFile('icon', Config::config()->icon ?? '') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Line Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/css/line-awesome.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/main.css') }}">
    
    @stack('styles')
</head>
<body>
    <!-- Mobile Top Bar -->
    <div class="tv-mobile-topbar">
        <button class="tv-mobile-toggle" id="sidebarToggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h2 class="tv-mobile-title">@yield('page_title', 'Dashboard')</h2>
        <div class="tv-mobile-profile">
            @php
                $userImage = auth()->user()->image ? file_exists(storage_path('app/public/' . auth()->user()->image)) ? asset('storage/'.auth()->user()->image) : asset('asset/images/avatar.png') : asset('asset/images/avatar.png');
            @endphp
            <button class="tv-mobile-profile-btn" id="mobileProfileToggle" aria-label="Profile Menu">
                <img src="{{ $userImage }}" alt="Profile" class="tv-mobile-profile-img">
                <i class="las la-angle-down"></i>
            </button>
            <div class="tv-mobile-profile-dropdown" id="mobileProfileDropdown">
                <a href="{{ route('user.profile') }}" class="tv-mobile-dropdown-item">
                    <i class="las la-user"></i>
                    <span>Profile</span>
                </a>
                <a href="{{ route('user.logout') }}" class="tv-mobile-dropdown-item tv-mobile-dropdown-item-danger">
                    <i class="las la-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="tv-mobile-overlay" id="sidebarOverlay"></div>

    <div class="tv-panel">
        <!-- Sidebar -->
        @include(\App\Helpers\Helper\Helper::theme() . 'layout.user_sidebar')
        
        <!-- Main Content -->
        <div class="tv-main">
            <!-- Page Header -->
            <div class="tv-page-header">
                <h1 class="tv-page-title">@yield('page_title', 'Dashboard')</h1>
                
                <!-- Profile Dropdown -->
                <div class="tv-header-profile">
                    <div class="tv-profile-toggle" id="profileToggle">
                        @php
                            $userImage = auth()->user()->image ? file_exists(storage_path('app/public/' . auth()->user()->image)) ? asset('storage/'.auth()->user()->image) : asset('asset/images/avatar.png') : asset('asset/images/avatar.png');
                        @endphp
                        <img src="{{ $userImage }}" alt="Profile" class="tv-profile-img">
                        <span class="tv-profile-name d-none d-md-inline">{{ auth()->user()->username }}</span>
                        <i class="las la-angle-down"></i>
                    </div>
                    <div class="tv-profile-dropdown" id="profileDropdown">
                        <a href="{{ route('user.profile') }}" class="tv-dropdown-item">
                            <i class="las la-user"></i> Profile
                        </a>
                        <a href="{{ route('user.logout') }}" class="tv-dropdown-item text-danger">
                            <i class="las la-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            @yield('content')
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- Main JS -->
    <script src="{{ asset('asset/frontend/trading-v1/js/main.js') }}"></script>
    
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Flash Messages -->
    @if(session('success'))
        <script>toastr.success("{{ session('success') }}");</script>
    @endif
    @if(session('error'))
        <script>toastr.error("{{ session('error') }}");</script>
    @endif
    @if(session('warning'))
        <script>toastr.warning("{{ session('warning') }}");</script>
    @endif
    @if(session('info'))
        <script>toastr.info("{{ session('info') }}");</script>
    @endif
    
    @stack('scripts')
</body>
</html>

