<section class="why-choose-us-section">
    <div class="container">
        <div class="section-header">
            @if(isset($content) && isset($content->section_header))
                <span class="section-badge">{{ Config::trans($content->section_header) }}</span>
            @endif
            <h2 class="section-title">{{ Config::trans(isset($content) ? ($content->title ?? 'Built for Traders, Backed by Experts') : 'Built for Traders, Backed by Experts') }}</h2>
            <p class="section-description">{{ Config::trans(isset($content) ? ($content->color_text_for_title ?? 'Discover the tools, insights, and support that set us apart in global markets') : 'Discover the tools, insights, and support that set us apart in global markets') }}</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card portfolio-insights">
                <div class="card-content">
                    <h3 class="card-title">Portfolio Insights</h3>
                    <p class="card-description">Track your assets, monitor portfolio performance, and analyze past results with clear visual charts.</p>
                    <a href="#" class="card-link">Learn More <i class="las la-arrow-right"></i></a>
                </div>
                <div class="card-visual">
                    <div class="dashboard-mockup">
                        <div class="mockup-tabs">
                            <span class="tab active">Portfolio</span>
                            <span class="tab">Investment</span>
                            <span class="tab">Withdraw</span>
                            <span class="tab">Transfer</span>
                        </div>
                        <div class="mockup-content">
                            <div class="chart-placeholder">
                                <i class="las la-chart-bar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="feature-card event-forecast">
                <div class="card-content">
                    <h3 class="card-title">Event Forecast</h3>
                    <p class="card-description">Access real-time economic events with potential market impact — plan trades based on data.</p>
                    <a href="#" class="card-link">View Calendar <i class="las la-arrow-right"></i></a>
                </div>
                <div class="card-visual">
                    <div class="calendar-widget">
                        <div class="calendar-header">
                            <button class="calendar-nav"><i class="las la-chevron-left"></i></button>
                            <span class="calendar-month">4 December, Monday</span>
                            <button class="calendar-nav"><i class="las la-chevron-right"></i></button>
                        </div>
                        <div class="calendar-events">
                            <div class="event-item">
                                <span class="event-time">05:45</span>
                                <span class="event-flag flag-jp"></span>
                                <span class="event-currency">JPY</span>
                                <span class="event-name">BoJ Bank Lending y/y</span>
                                <span class="event-forecast">3.0%</span>
                            </div>
                            <div class="event-item">
                                <span class="event-time">09:45</span>
                                <span class="event-flag flag-no"></span>
                                <span class="event-currency">NOK</span>
                                <span class="event-name">CPI m/m</span>
                                <span class="event-forecast">3.0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="feature-card market-headlines">
                <div class="card-content">
                    <h3 class="card-title">Market Headlines</h3>
                    <p class="card-description">Stay informed with daily financial news, global updates, and trending insights in forex and commodities.</p>
                    <a href="#" class="card-link">Read News <i class="las la-arrow-right"></i></a>
                </div>
                <div class="card-visual">
                    <div class="news-feed">
                        <div class="news-item">
                            <div class="news-content">
                                <h4 class="news-title">Market Analysis Update</h4>
                                <p class="news-excerpt">Latest trends in global markets...</p>
                            </div>
                        </div>
                        <div class="news-item">
                            <div class="news-content">
                                <h4 class="news-title">Economic Indicators</h4>
                                <p class="news-excerpt">Key metrics to watch this week...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="feature-card trend-analysis">
                <div class="card-content">
                    <h3 class="card-title">Trend Analysis Tools</h3>
                    <p class="card-description">Deep-dive into market movements using analytical charts, indicators, and predictive insights.</p>
                    <a href="#" class="card-link">Explore Tools <i class="las la-arrow-right"></i></a>
                </div>
                <div class="card-visual">
                    <div class="chart-icons">
                        <div class="chart-icon-item">
                            <i class="las la-chart-bar"></i>
                        </div>
                        <div class="chart-icon-item">
                            <i class="las la-chart-area"></i>
                        </div>
                        <div class="chart-icon-item large">
                            <i class="las la-chart-line"></i>
                        </div>
                        <div class="chart-icon-item">
                            <i class="las la-chart-pie"></i>
                        </div>
                        <div class="chart-icon-item">
                            <i class="las la-bolt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

