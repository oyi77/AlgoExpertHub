<section class="cta-education-section">
    <div class="container">
        <div class="cta-content">
            <div class="cta-text">
                <h2 class="cta-title">{{ Config::trans(isset($content) ? ($content->title ?? 'Sharpen Your Trading Edge with Our Education Content') : 'Sharpen Your Trading Edge with Our Education Content') }}</h2>
                <p class="cta-description">{{ Config::trans(isset($content) ? ($content->description ?? 'Master the markets with our comprehensive trading education, from beginner basics to advanced strategies.') : 'Master the markets with our comprehensive trading education, from beginner basics to advanced strategies.') }}</p>
                <div class="cta-buttons">
                    <a href="{{ isset($content) ? ($content->button_text_link ?? route('user.register')) : route('user.register') }}" class="btn btn-cta-primary btn-interactive" data-action="register">
                        <i class="fas fa-graduation-cap"></i> {{ Config::trans(isset($content) ? ($content->button_text ?? 'Start Learning') : 'Start Learning') }}
                    </a>
                    <a href="{{ isset($content) ? ($content->button_two_text_link ?? '#trading-demo') : '#trading-demo' }}" class="btn btn-outline btn-interactive" data-action="demo">
                        <i class="fas fa-play-circle"></i> {{ Config::trans(isset($content) ? ($content->button_two_text ?? 'Try Free Demo') : 'Try Free Demo') }}
                    </a>
                </div>
                <div class="education-features">
                    <div class="feature">
                        <i class="fas fa-book"></i>
                        <span>Trading Guides</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-video"></i>
                        <span>Video Tutorials</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <span>Community Support</span>
                    </div>
                </div>
            </div>
            <div class="cta-visual">
                <div class="chart-illustration">
                    <div class="chart-bars">
                        <div class="bar" style="height: 60%"></div>
                        <div class="bar" style="height: 80%"></div>
                        <div class="bar" style="height: 45%"></div>
                        <div class="bar" style="height: 90%"></div>
                        <div class="bar" style="height: 70%"></div>
                        <div class="bar" style="height: 85%"></div>
                    </div>
                    <div class="chart-icon-overlay">
                        <i class="las la-chart-bar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

