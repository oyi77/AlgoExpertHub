@php
    $hero = Config::builder('hero');
@endphp

<section class="tv-hero" id="hero">
    <div class="tv-container">
        <div class="tv-hero-content">
            <!-- Badge -->
            <div class="tv-hero-badge">
                <div class="tv-hero-avatars">
                    <div class="tv-hero-avatar" style="background: linear-gradient(135deg, #1AFFD5, #0D9488);"></div>
                    <div class="tv-hero-avatar" style="background: linear-gradient(135deg, #10B981, #059669);"></div>
                    <div class="tv-hero-avatar" style="background: linear-gradient(135deg, #3B82F6, #2563EB);"></div>
                    <div class="tv-hero-avatar" style="background: linear-gradient(135deg, #F59E0B, #D97706);"></div>
                </div>
                <span class="tv-hero-badge-text">{{ $hero->content->badge_text ?? '🚀 1M+ Active Traders' }}</span>
            </div>
            
            <!-- Title -->
            <h1 class="tv-hero-title tv-gradient-text">
                {{ Config::trans($hero->content->title ?? 'Master the Markets, Maximize Your Profits') }}
            </h1>
            
            <!-- Subtitle -->
            <p class="tv-hero-subtitle">
                {{ Config::trans($hero->content->description ?? 'Trade smarter with real-time insights, powerful tools, and expert strategies at your fingertips.') }}
            </p>
            
            <!-- CTA -->
            <div class="tv-hero-cta">
                <a href="{{ $hero->content->button_text_link ?? route('user.register') }}" class="tv-btn tv-btn-primary tv-btn-lg">
                    {{ Config::trans($hero->content->button_text ?? 'Get Started') }}
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Partners -->
        <div class="tv-partners">
            <p class="tv-partners-label">Trusted by</p>
            <div class="tv-partners-logos">
                <span style="font-size: 24px; opacity: 0.5; font-weight: 700;">MetaTrader</span>
                <span style="font-size: 24px; opacity: 0.5; font-weight: 700;">TradingView</span>
                <span style="font-size: 24px; opacity: 0.5; font-weight: 700;">Bloomberg</span>
                <span style="font-size: 24px; opacity: 0.5; font-weight: 700;">Reuters</span>
            </div>
        </div>
    </div>
</section>

