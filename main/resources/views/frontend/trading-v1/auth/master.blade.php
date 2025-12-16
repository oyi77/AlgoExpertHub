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
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/main.css') }}">
    
    @stack('styles')
</head>
<body>
    <div class="tv-auth">
        <div class="tv-auth-card">
            <!-- Logo -->
            <div class="tv-auth-logo">
                <a href="{{ route('home') }}">
                    <img src="{{ Config::getFile('logo', Config::config()->logo ?? '') }}" alt="{{ Config::config()->appname ?? 'Logo' }}">
                </a>
            </div>
            
            <!-- Content -->
            @yield('content')
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
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

