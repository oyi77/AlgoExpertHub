@php
    $singleElement = Config::builder('contact');
    $socials = Config::builder('socials', true);
@endphp


<header class="sp_header" role="banner">
    <div class="sp_header_info_bar">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 header-top-left">
                    <ul class="hc-list justify-content-lg-start justify-content-center">
                        @if($singleElement->content->email ?? null)
                            <li><a href="mailto:{{ $singleElement->content->email }}" aria-label="Email us"><i class="fas fa-envelope" aria-hidden="true"></i>
                                    <span>{{ $singleElement->content->email }}</span></a></li>
                        @endif
                        @if($singleElement->content->phone ?? null)
                            <li><a href="tel:{{ $singleElement->content->phone }}" aria-label="Call us"><i class="fas fa-phone-alt" aria-hidden="true"></i>
                                    <span>{{ $singleElement->content->phone }}</span></a></li>
                        @endif
                    </ul>
                </div>
                <div class="col-lg-6 header-top-right d-lg-block d-none">
                    <ul class="hc-list justify-content-lg-end justify-content-center">
                        @if($socials && count($socials) > 0)
                            <li>
                                <ul class="social-icons">
                                    @php
                                        $uniqueSocials = collect($socials)->unique(function ($social) {
                                            return $social->content->link ?? $social->content->icon ?? $social->id;
                                        });
                                    @endphp
                                    @foreach ($uniqueSocials as $social)
                                        @if(isset($social->content->link) && isset($social->content->icon))
                                            <li><a href="{{ $social->content->link }}" aria-label="Visit our {{ $social->content->icon }}" target="_blank" rel="noopener noreferrer"><i
                                                        class="{{ $social->content->icon }}" aria-hidden="true"></i></a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                        <li>
                            <label for="language-select-desktop" class="visually-hidden">{{ __('Select Language') }}</label>
                            <select id="language-select-desktop" class="custom-select-form selectric ms-3 rounded changeLang nav-link scrollto focus-ring"
                                aria-label="{{ __('Select Language') }}">
                                @foreach (Config::languages() as $language)
                                    <option value="{{ $language->code }}"
                                        {{ Config::languageSelection($language->code) }}>
                                        {{ __(ucwords($language->name)) }}
                                    </option>
                                @endforeach
                            </select>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="sp_header_main">
        <div class="container">
            <nav class="navbar navbar-expand-xl p-0 align-items-center" role="navigation" aria-label="{{ __('Main navigation') }}">
                <a class="site-logo site-title" href="{{ route('home') }}" aria-label="{{ __('Home') }}">
                    <img src="{{ Config::getFile('logo', Config::config()->logo) }}" alt="{{ Config::config()->appname ?? 'Logo' }}" loading="eager">
                </a>
                <button class="navbar-toggler ms-auto focus-ring" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation menu') }}">
                    <span class="menu-toggle" aria-hidden="true"></span>
                </button>
                <div class="collapse navbar-collapse mt-lg-0 mt-3" id="mainNavbar">
                    <ul class="nav navbar-nav sp_site_menu ms-auto">
                        <?= Config::navbarMenus() ?>
                    </ul>

                    <label for="language-select-mobile" class="visually-hidden">{{ __('Select Language') }}</label>
                    <select id="language-select-mobile" class="custom-select-form rounded changeLang nav-link mb-3 d-xl-none focus-ring"
                        aria-label="{{ __('Select Language') }}">
                        @foreach (Config::languages() as $language)
                            <option value="{{ $language->code }}" {{ Config::languageSelection($language->code) }}>
                                {{ __(ucwords($language->name)) }}
                            </option>
                        @endforeach
                    </select>

                    <div class="navbar-action">
                        @auth
                            <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-sm focus-ring">{{ __('Dashboard') }}
                                <i class="las la-long-arrow-alt-right ms-2" aria-hidden="true"></i></a>
                        @else
                            <a href="{{ route('user.login') }}" class="me-3 text-p focus-ring">{{ __('Sign In') }}</a>
                            <a href="{{ route('user.register') }}" class="btn btn-primary btn-sm focus-ring">{{ __('Sign up') }} <i
                                    class="las la-long-arrow-alt-right ms-2" aria-hidden="true"></i></a>
                        @endauth
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>
