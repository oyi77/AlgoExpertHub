@php
    $hero = Config::builder('hero');
@endphp

<section class="trading-hero-section">
    <div class="hero-background">
        <div class="gradient-circles"></div>
        <div class="light-effects"></div>
    </div>

    <div class="container">
        <div class="hero-content">
            @if($hero && $hero->content)
                <div class="hero-badge">
                    <div class="user-avatars">
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                    </div>
                    <span class="badge-text">{{ $hero->content->badge_text ?? '1M+ Users Active' }}</span>
                </div>

                <div class="hero-text">
                    <h1 class="hero-heading">{{ Config::trans($hero->content->title ?? 'Master the Markets, Maximize Your Profits') }}</h1>
                    <p class="hero-subheading">{{ Config::trans($hero->content->description ?? 'Trade smarter with real-time insights, powerful tools, and expert strategies at your fingertips.') }}</p>
                </div>

                <a href="{{ $hero->content->button_text_link ?? route('user.register') }}" class="btn btn-hero-cta">{{ Config::trans($hero->content->button_text ?? 'Explore Now') }}</a>
            @else
                <!-- Fallback content when no hero configuration exists -->
                <div class="hero-badge">
                    <div class="user-avatars">
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                        <div class="avatar"></div>
                    </div>
                    <span class="badge-text">1M+ Users Active</span>
                </div>

                <div class="hero-text">
                    <h1 class="hero-heading">Master the Markets, Maximize Your Profits</h1>
                    <p class="hero-subheading">Trade smarter with real-time insights, powerful tools, and expert strategies at your fingertips.</p>
                </div>

                <a href="{{ route('user.register') }}" class="btn btn-hero-cta">Explore Now</a>
            @endif
        </div>
    </div>
    
    <div class="trusted-by-section">
        <div class="container">
            <p class="trusted-by-label">Trusted by</p>
            <div class="partner-logos">
                <div class="partner-logo">
                    <img src="{{ asset('asset/frontend/trading-landing/images/landing/logos/partner-1.png') }}" alt="Partner 1" loading="lazy" onerror="this.style.display='none'">
                </div>
                <div class="partner-logo">
                    <img src="{{ asset('asset/frontend/trading-landing/images/landing/logos/partner-2.png') }}" alt="Partner 2" loading="lazy" onerror="this.style.display='none'">
                </div>
                <div class="partner-logo">
                    <img src="{{ asset('asset/frontend/trading-landing/images/landing/logos/partner-3.png') }}" alt="Partner 3" loading="lazy" onerror="this.style.display='none'">
                </div>
                <div class="partner-logo">
                    <img src="{{ asset('asset/frontend/trading-landing/images/landing/logos/partner-4.png') }}" alt="Partner 4" loading="lazy" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </div>
</section>
