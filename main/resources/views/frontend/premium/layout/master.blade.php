<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="description" content="{{ optional($page)->seo_description ?? optional(Config::config())->seo_description }}" />
    @php
        $keywords = optional($page)->seo_keywords ?? optional(Config::config())->seo_tags ?? [];
        $keywordsString = is_array($keywords) ? implode(',', $keywords) : $keywords;
    @endphp
    <meta name="keywords" content="{{ $keywordsString }}" />
    <title>{{ optional(Config::config())->appname ?? 'AlgoExpert Hub' }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Poppins:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ optional(Config::config()->fonts)->heading_font_url }}">
    <link rel="stylesheet" href="{{ optional(Config::config()->fonts)->paragraph_font_url }}">

    <link rel="shortcut icon" type="image/png" href="{{ Config::getFile('icon', optional(Config::config())->favicon, true) }}">

    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'all.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/slick.css') }}">
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/odometer.css') }}">

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

    <link href="{{ asset('asset/css/tokens.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('asset/css/utilities.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ Config::cssLib('frontend', 'components.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ Config::cssLib('frontend', 'main.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ Config::cssLib('frontend', 'helper.css') }}?v={{ time() }}" rel="stylesheet">

    @php
        $heading = optional(Config::config()->fonts)->heading_font_family ?? 'DM Sans';
        $paragraph = optional(Config::config()->fonts)->paragraph_font_family ?? 'Poppins';
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

    @stack('style')


</head>

<body>


    @if (optional(Config::config())->preloader_status)
        <div class="preloader-holder">
            <div class="preloader">
                <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
        </div>
    @endif


    @if (optional(Config::config())->analytics_status)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ optional(Config::config())->analytics_key }}"></script>
        <script>
            'use strict'
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag("js", new Date());
            gtag("config", "{{ optional(Config::config())->analytics_key }}");
        </script>
    @endif

    @if (optional(Config::config())->allow_modal)
        @include('cookie-consent::index')
    @endif

    <div class="body-content-area">
        @if (request()->routeIs('home'))
            @php
                $hasBannerInWidgets = false;
                if (isset($page) && $page && $page->widgets) {
                    $hasBannerInWidgets = $page->widgets->contains(function($widget) {
                        return $widget->sections === 'banner';
                    });
                }
            @endphp
            @if (!$hasBannerInWidgets)
                @include(Config::themeView('widgets.banner'))
            @endif
        @endif

        @include(Config::themeView('layout.header'))

        @if (!request()->routeIs('home'))
            @include(Config::themeView('widgets.breadcrumb'))
        @endif

        @yield('content')
    </div>

    @include(Config::themeView('widgets.footer'))

    <script src="{{ Config::jsLib('frontend', 'lib/jquery.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/slick.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/wow.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/jquery.paroller.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/TweenMax.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/odometer.min.js') }}"></script>
    <script src="{{ Config::jsLib('frontend', 'lib/viewport.jquery.js') }}"></script>



    @if (optional(Config::config())->alert ?? 'sweetalert' === 'izi')
        <script src="{{ Config::jsLib('frontend', 'izitoast.min.js') }}"></script>
    @elseif(optional(Config::config())->alert ?? 'sweetalert' === 'toast')
        <script src="{{ Config::jsLib('frontend', 'toastr.min.js') }}"></script>
    @else
        <script src="{{ Config::jsLib('frontend', 'sweetalert.min.js') }}"></script>
    @endif

    <script src="{{ Config::jsLib('frontend', 'main-optimized.js') }}" defer></script>

    @stack('script')


    @if (optional(Config::config())->twak_allow)
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = "{{ optional(Config::config())->twak_key }}";
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
    @endif

    <script>
        $(function() {
            'use strict'

            $(document).on('submit', '#subscribe', function(e) {
                e.preventDefault();
                const email = $('.subscribe-email').val();
                var url = "{{ route('subscribe') }}";
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        email: email,
                        _token: "{{ csrf_token() }}"
                    },
                    success: (response) => {

                        $('.subscribe-email').val('');

                        @include(Config::themeView('layout.ajax_alert')), [
                            'message' => 'Successfully Subscribe',
                            'message_error' => '',
                        ])

                    },
                    error: () => {

                        @if (optional(Config::config())->alert ?? 'sweetalert' === 'izi')
                            iziToast.error({
                                position: 'topRight',
                                message: "Email is Required",
                            });
                        @elseif (optional(Config::config())->alert ?? 'sweetalert' === 'toast')
                            toastr.error("Email is Required", {
                                positionClass: "toast-top-right"

                            })
                        @else
                            Swal.fire({
                                icon: 'error',
                                title: "Email is Required"
                            })
                        @endif
                    }
                })

            });

            var url = "{{ route('change-language') }}";

            $(".changeLang").on('change', function() {
               
                if ($(this).val() == '') {
                    return false;
                }
                window.location.href = url + "?lang=" + $(this).val();
            });

        })
    </script>


    @include('alert')


</body>

</html>
