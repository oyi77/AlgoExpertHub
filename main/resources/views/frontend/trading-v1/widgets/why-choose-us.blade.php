@php
    $whyChooseUs = Config::builder('why_choose_us');
@endphp

<section class="tv-section" id="why-choose-us">
    <div class="tv-container">
        <!-- Section Header -->
        <div class="tv-section-header scroll-reveal">
            <h2 class="tv-section-title tv-gradient-text">
                {{ Config::trans($whyChooseUs->content->title ?? 'Why Choose Us') }}
            </h2>
            <p class="tv-section-desc">
                {{ Config::trans($whyChooseUs->content->description ?? 'Everything you need to succeed in trading') }}
            </p>
        </div>
        
        <!-- Features Grid -->
        <div class="tv-features-grid">
            <!-- Feature 1 -->
            <div class="tv-feature-card scroll-reveal scroll-reveal-left scroll-reveal-delay-1">
                <div class="tv-feature-header">
                    <div class="tv-feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="tv-feature-title tv-gradient-text">Real-Time Market Data</h3>
                </div>
                <p class="tv-feature-desc">
                    Access live market data, advanced charts, and technical indicators to make informed trading decisions with confidence.
                </p>
                <a href="#market-trends" class="tv-feature-link">
                    Explore Markets <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Feature 2 -->
            <div class="tv-feature-card scroll-reveal scroll-reveal-right scroll-reveal-delay-2">
                <div class="tv-feature-header">
                    <div class="tv-feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="tv-feature-title tv-gradient-text">Secure & Reliable</h3>
                </div>
                <p class="tv-feature-desc">
                    Bank-level security with encrypted transactions and secure data storage to protect your investments and personal information.
                </p>
                <a href="#" class="tv-feature-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Feature 3 -->
            <div class="tv-feature-card scroll-reveal scroll-reveal-left scroll-reveal-delay-3">
                <div class="tv-feature-header">
                    <div class="tv-feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="tv-feature-title tv-gradient-text">AI-Powered Insights</h3>
                </div>
                <p class="tv-feature-desc">
                    Leverage artificial intelligence to analyze market trends and get personalized trading recommendations tailored to your strategy.
                </p>
                <a href="#" class="tv-feature-link">
                    Try AI Tools <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Feature 4 -->
            <div class="tv-feature-card scroll-reveal scroll-reveal-right scroll-reveal-delay-4">
                <div class="tv-feature-header">
                    <div class="tv-feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="tv-feature-title tv-gradient-text">Expert Community</h3>
                </div>
                <p class="tv-feature-desc">
                    Join a community of professional traders and learn from experts with years of market experience and proven track records.
                </p>
                <a href="{{ route('user.register') }}" class="tv-feature-link">
                    Join Community <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

