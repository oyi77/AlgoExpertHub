<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @php
        $config = Config::config();
        $fonts = optional($config)->fonts;
    @endphp
    <meta name="description" content="{{ $page->seo_description ?? optional($config)->seo_description ?? 'AlgoExpertHub Trading Signal Platform' }}" />
    <meta name="keywords" content="{{ implode(',', $page->seo_keywords ?? optional($config)->seo_tags ?? []) }} ">

    <title>{{ optional($config)->appname ?? config('app.name', 'AlgoExpertHub') }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Poppins:wght@300;400;500;600;700;800&display=swap">
    @if(optional($fonts)->heading_font_url)
        <link rel="stylesheet" href="{{ optional($fonts)->heading_font_url }}">
    @endif
    @if(optional($fonts)->paragraph_font_url)
        <link rel="stylesheet" href="{{ optional($fonts)->paragraph_font_url }}">
    @endif

    <link rel="shortcut icon" type="image/png" href="{{ Config::getFile('icon', optional(Config::config())->favicon, true) }}">

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'all.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/slick.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/odometer.css') }}">

    {{-- Laravel Notify CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/notify/notify.css') }}">

    <link href="{{ Config::cssLib('frontend', 'main.css') }}?v=20251202" rel="stylesheet">
    <link href="{{ Config::cssLib('frontend', 'helper.css') }}?v=20251202" rel="stylesheet">

    @php
        $heading = optional($fonts)->heading_font_family ?? 'DM Sans';
        $paragraph = optional($fonts)->paragraph_font_family ?? 'Poppins';
    @endphp

    <style>
        :root {
            --h-font: <?=$heading ?>;
            --p-font: <?=$paragraph ?>;
            --display-font: "DM Sans", sans-serif;
            --body-font: "Poppins", sans-serif;
        }
    </style>

    @stack('external-css')


</head>

<body>

    @if (optional($config)->preloader_status)
        <div class="preloader-holder">
            <div class="preloader">
                <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
        </div>
    @endif


    @if (optional($config)->analytics_status)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ optional($config)->analytics_key }}"></script>
        <script>
            'use strict'
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag("js", new Date());
            gtag("config", "{{ optional($config)->analytics_key }}");
        </script>
    @endif

    @if (optional($config)->allow_modal)
        @include('cookie-consent::index')
    @endif

    @php
        $content= App\Models\Content::where('name', 'auth')->where('theme', optional($config)->theme ?? 'default')->first();
    @endphp
    

    <div class="account-page">
        <div class="form-wrapper">
            <div class="logo text-center">
                <a href="{{ route('home') }}" class="site-logo"><img src="{{ Config::getFile('logo', optional($config)->logo ?? '') }}"
                        alt="image"></a>
            </div>
            <div class="inner-wrapper">
                <h3 class="title">{{ optional($content)->content->title ?? '' }}</h3>
                @yield('content')
            </div>
            <div class="copy-right-text">
                <p>{{__(optional($config)->copyright ?? '')}}</p>
            </div>
        </div>
        <div class="img-wrapper">
            <img src="{{ Config::getFile('auth', optional($content)->content->image_one ?? '') }}" class="account-line-bg" alt="image">
        </div>
    </div>

    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/slick.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/wow.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.paroller.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/TweenMax.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/odometer.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/viewport.jquery.js') }}"></script>

    {{-- Laravel Notify JavaScript --}}
    <script defer src="{{ asset('vendor/notify/notify.js') }}"></script>

    <script src="{{ Config::jsLib('frontend', 'main.js') }}"></script>
    @stack('scripts')


    @include('alert')
</body>

</html>
