<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    @php
        $config = Config::config();
    @endphp
    <title>{{ optional($config)->appname ?? config('app.name', 'AlgoExpertHub') }}</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ Config::fetchImage('icon', optional($config)->favicon ?? '', true) }}">

    
    {{-- Laravel Notify CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/notify/notify.css') }}">

    <link href="{{ Config::cssLib('backend', 'main.css') }}" rel="stylesheet">

</head>

<body class="h-100">

    <div class="authincation">
        <div class="authincation-content">
            <div class="auth-form">
                @yield('element')
            </div>
        </div>
    </div>

    <script src="{{ Config::jsLib('backend', 'global.min.js') }}"></script>

    {{-- Laravel Notify JavaScript --}}
    <script defer src="{{ asset('vendor/notify/notify.js') }}"></script>

    @include('alert')


    @stack('scripts')

</body>

</html>
