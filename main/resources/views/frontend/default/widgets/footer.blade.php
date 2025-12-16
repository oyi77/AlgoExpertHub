@php
    $content = Config::builder('footer');
    
    $links = Config::builder('links', true);

    $socials = Config::builder('socials', true);

    $element = Config::builder('brand', true);
@endphp

@if($element && is_array($element))
<div class="sp_brand_wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="sp_brand_slider">
                    @foreach ($element as $brand)
                        @if($brand && isset($brand->content))
                        <div class="sp_brand_slide">
                            <div class="sp_brand_item">
                                <img src="{{ Config::getFile('brand', $brand->content->image_one ?? '') }}" alt="Partner brand" loading="lazy">
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- footer section start -->
@if($content && $content->content)
<footer class="footer-section" role="contentinfo">
    <div class="sp_footer_menu_area">
        <a href="#top" class="back-to-top focus-ring" aria-label="{{ __('Back to top') }}">
            <div class="back-to-top-inner">
                <i class="las la-arrow-up" aria-hidden="true"></i>
            </div>
        </a>

        <div class="container">
            <div class="row gy-4 justify-content-between">
                <div class="col-lg-4 pe-xl-5">
                    <div class="sp_footer_item">
                        <a href="{{ route('home') }}" class="site-logo" aria-label="{{ __('Home') }}">
                            <img src="{{ Config::getFile('footer', $content->content->image_one ?? '') }}" alt="{{ Config::config()->appname ?? 'Logo' }}" loading="lazy">
                        </a>
                        <p class="mt-4">{{ Config::trans($content->content->footer_short_details ?? '') }}</p>
                        
                        {{-- Trust Indicators --}}
                        <div class="mt-4">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <span class="badge-modern badge-success" title="{{ __('SSL Secured') }}">
                                    <i class="fas fa-lock me-1" aria-hidden="true"></i> {{ __('SSL Secured') }}
                                </span>
                                @if(Config::config()->allow_2fa ?? false)
                                    <span class="badge-modern badge-info" title="{{ __('2FA Enabled') }}">
                                        <i class="fas fa-shield-alt me-1" aria-hidden="true"></i> {{ __('2FA') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="sp_footer_item">
                        <h5 class="sp_footer_item_title">{{ __('Company') }}</h5>
                        <ul class="sp_footer_menu">
                            @foreach (Config::pages() as $page)
                                <li><a href="{{ route('pages', $page->slug) }}" class="focus-ring">{{ __($page->name) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="sp_footer_item">
                        <h5 class="sp_footer_item_title">{{ __('Links') }}</h5>
                        <ul class="sp_footer_menu">
                            @if($links && is_array($links))
                                @foreach ($links as $item)
                                    @if($item && isset($item->content) && isset($item->content->page_title))
                                        <li><a href="{{ route('links', [$item->id, Str::slug($item->content->page_title)]) }}" class="focus-ring">{{ Config::trans($item->content->page_title) }}</a></li>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                        <ul class="sp_footer_menu mt-3">
                            <li><a href="{{ route('pages', 'privacy-policy') ?? '#' }}" class="focus-ring">{{ __('Privacy Policy') }}</a></li>
                            <li><a href="{{ route('pages', 'terms-conditions') ?? '#' }}" class="focus-ring">{{ __('Terms & Conditions') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4">
                    <div class="sp_footer_item">
                        <h5 class="sp_footer_item_title">{{ __('Newsletter') }}</h5>
                        <form class="sp_subscription_form" id="subscribe" method="POST" aria-label="{{ __('Newsletter subscription') }}">
                            @csrf
                            <label for="subscribe-email" class="visually-hidden">{{ __('Email Address') }}</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="subscribe-email"
                                class="form-control subscribe-email form-control-modern focus-ring"
                                placeholder="{{ __('Email Address') }}"
                                required
                                aria-label="{{ __('Email Address') }}"
                            >
                            <button type="submit" class="subs-btn focus-ring" aria-label="{{ __('Subscribe') }}">
                                <i class="far fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </form>
                        <h5 class="mt-4">{{ __('Follow Us') }}</h5>
                        <ul class="sp_social_links mt-2">
                            @if($socials && is_array($socials))
                                @foreach ($socials as $social)
                                    @if($social && isset($social->content))
                                        <li>
                                            <a href="{{ $social->content->link ?? '#' }}" 
                                               class="focus-ring"
                                               aria-label="{{ __('Visit us on') }} {{ $social->content->icon ?? 'social media' }}"
                                               target="_blank"
                                               rel="noopener noreferrer">
                                                <i class="{{ $social->content->icon ?? '' }}" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="sp_copy_right_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <p>{{ Config::config()->copyright ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
</footer>
@endif
<!-- footer section end -->
