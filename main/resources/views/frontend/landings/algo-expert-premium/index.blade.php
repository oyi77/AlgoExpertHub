@extends('frontend.landings.algo-expert-premium.layout.master')

@section('content')
<!-- Hero Section -->
<section class="hero-section" x-data="{ 
    activeStep: 0,
    init() {
        // Auto-advance strategy builder steps
        setInterval(() => {
            this.activeStep = (this.activeStep + 1) % 2;
        }, 3000);
    }
}">
    <div class="container">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6" x-intersect:enter="$el.classList.add('revealed')">
                <div class="badge-glass mb-4 reveal">
                    <span class="badge-icon">🚀</span>
                    <span>Professional-grade algorithmic trading for everyone</span>
                </div>
                
                <h1 class="hero-headline reveal">
                    Automate Your Edge.<br>
                    <span class="text-gradient">Master the Markets.</span>
                </h1>
                
                <p class="hero-subheadline reveal">
                    Professional-grade algorithmic trading bots for everyone. Connect, Configure, and Compound.
                </p>
                
                <div class="hero-cta reveal">
                    <a href="{{ route('user.register') }}" class="btn-shimmer">
                        <span class="btn-shimmer-text">Start Trading Free</span>
                    </a>
                    <a href="#strategy-canvas" class="btn-outline">
                        <i data-lucide="play-circle" class="icon"></i>
                        Watch Demo
                    </a>
                </div>
                
                <div class="hero-stats reveal">
                    <div class="stat-item">
                        <div class="stat-value" data-count="2.4">$0</div>
                        <div class="stat-label">B+ Volume</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value" data-count="150">0</div>
                        <div class="stat-label">k+ Active Bots</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 reveal" x-intersect:enter="$el.classList.add('revealed')">
                <!-- Visual Strategy Builder Mockup -->
                <div class="strategy-builder-mockup">
                    <div class="strategy-card" :class="{ 'active': activeStep === 0 }">
                        <div class="strategy-card-header">
                            <div class="strategy-card-icon">
                                <i data-lucide="cpu"></i>
                            </div>
                            <span class="strategy-card-label">Condition</span>
                        </div>
                        <div class="strategy-card-content">
                            <div class="strategy-rule">
                                <span class="rule-keyword">IF</span>
                                <select class="rule-select">
                                    <option>RSI (14)</option>
                                    <option>MACD</option>
                                    <option>Bollinger Bands</option>
                                </select>
                                <select class="rule-select">
                                    <option>&lt; 30</option>
                                    <option>&gt; 70</option>
                                    <option>Crosses Above</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="strategy-connector">
                        <i data-lucide="arrow-down"></i>
                    </div>
                    
                    <div class="strategy-card" :class="{ 'active': activeStep === 1 }">
                        <div class="strategy-card-header">
                            <div class="strategy-card-icon success">
                                <i data-lucide="zap"></i>
                            </div>
                            <span class="strategy-card-label">Action</span>
                        </div>
                        <div class="strategy-card-content">
                            <div class="strategy-rule">
                                <span class="rule-keyword">THEN</span>
                                <select class="rule-select">
                                    <option>Buy BTC</option>
                                    <option>Sell BTC</option>
                                    <option>DCA Buy</option>
                                </select>
                                <select class="rule-select">
                                    <option>$500</option>
                                    <option>2% Balance</option>
                                    <option>5% Balance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="strategy-status">
                        <div class="status-indicator">
                            <div class="status-dot"></div>
                            <span>Bot Active</span>
                        </div>
                        <div class="status-profit">
                            <i data-lucide="trending-up"></i>
                            <span>+12.4% Profit</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature Bento Grid -->
<section class="features-section py-5" id="features">
    <div class="container">
        <div class="section-header text-center mb-5 reveal">
            <h2 class="section-title">
                Everything You Need to <span class="text-gradient">Automate Trading</span>
            </h2>
            <p class="section-subtitle">Inspired by 3Commas and Coinrule, built for professionals</p>
        </div>
        
        <div class="bento-grid reveal">
            <!-- DCA Bots -->
            <div class="bento-card bento-large" x-intersect:enter="$el.classList.add('revealed')">
                <div class="bento-card-header">
                    <div class="bento-icon bg-primary">
                        <i data-lucide="trending-down"></i>
                    </div>
                    <div>
                        <h3 class="bento-title">DCA Bots</h3>
                        <p class="bento-subtitle">Smart Accumulation</p>
                    </div>
                </div>
                <div class="bento-content">
                    <p class="bento-description">
                        Automatically buy the dip with intelligent dollar-cost averaging. Set your intervals and watch your portfolio grow.
                    </p>
                    <div class="sparkline-chart">
                        <svg viewBox="0 0 200 60" class="sparkline">
                            <polyline
                                fill="none"
                                stroke="var(--color-primary-500)"
                                stroke-width="2"
                                points="0,50 20,45 40,35 60,30 80,25 100,20 120,15 140,20 160,25 180,30 200,25"
                            />
                            <circle cx="200" cy="25" r="3" fill="var(--color-primary-500)"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Grid Trading -->
            <div class="bento-card" x-intersect:enter="$el.classList.add('revealed')">
                <div class="bento-icon bg-success">
                    <i data-lucide="grid"></i>
                </div>
                <h3 class="bento-title">Grid Trading</h3>
                <div class="grid-visual">
                    <div class="price-channel">
                        <div class="channel-line buy-line">Buy Zone</div>
                        <div class="channel-line sell-line">Sell Zone</div>
                        <div class="price-indicator"></div>
                    </div>
                </div>
                <p class="bento-description">Profit from volatility with automated grid orders</p>
            </div>
            
            <!-- Signal Integration -->
            <div class="bento-card" x-intersect:enter="$el.classList.add('revealed')">
                <div class="bento-icon bg-warning">
                    <i data-lucide="send"></i>
                </div>
                <h3 class="bento-title">Signal Integration</h3>
                <div class="signal-badges">
                    <div class="signal-badge">
                        <i data-lucide="message-square"></i>
                        <span>Telegram</span>
                    </div>
                    <div class="signal-badge">
                        <i data-lucide="bar-chart-3"></i>
                        <span>TradingView</span>
                    </div>
                </div>
                <p class="bento-description">
                    Webhook execution in <strong>&lt;50ms</strong>. Connect your favorite signal providers instantly.
                </p>
            </div>
            
            <!-- Paper Trading -->
            <div class="bento-card" x-intersect:enter="$el.classList.add('revealed')" x-data="{ paperTrading: false }">
                <div class="bento-icon" :class="paperTrading ? 'bg-info' : 'bg-gray'">
                    <i data-lucide="file-text"></i>
                </div>
                <h3 class="bento-title">Paper Trading</h3>
                <div class="paper-trading-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" x-model="paperTrading">
                        <span class="toggle-slider" :class="{ 'active': paperTrading }"></span>
                    </label>
                    <span class="toggle-label" :class="{ 'active': paperTrading }">
                        <span x-show="!paperTrading">Live Trading</span>
                        <span x-show="paperTrading">Demo Mode</span>
                    </span>
                </div>
                <p class="bento-description" :class="{ 'demo-mode': paperTrading }">
                    Test strategies risk-free with paper trading mode
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Strategy Canvas (Interactive Mockup) -->
<section class="strategy-canvas-section py-5" id="strategy-canvas">
    <div class="container">
        <div class="section-header text-center mb-5 reveal">
            <h2 class="section-title">
                Build Strategies with <span class="text-gradient">Visual Logic</span>
            </h2>
            <p class="section-subtitle">Drag, drop, and configure. No code required.</p>
        </div>
        
        <div class="canvas-container reveal" x-data="strategyCanvas()">
            <div class="canvas-header">
                <div class="canvas-tabs">
                    <button class="canvas-tab active" @click="activeTab = 'rules'">Rule Builder</button>
                    <button class="canvas-tab" @click="activeTab = 'advanced'">Advanced Logic</button>
                </div>
            </div>
            
            <div class="canvas-body">
                <div class="strategy-sentence">
                    <span class="sentence-keyword">IF</span>
                    <div class="sentence-dropdown" @click="showDropdown = !showDropdown">
                        <span x-text="selectedCondition || 'Price Drops 5%'"></span>
                        <i data-lucide="chevron-down"></i>
                        <div class="dropdown-menu" x-show="showDropdown" @click.away="showDropdown = false">
                            <div class="dropdown-item" @click="selectedCondition = 'Price Drops 5%'; showDropdown = false">Price Drops 5%</div>
                            <div class="dropdown-item" @click="selectedCondition = 'RSI < 30'; showDropdown = false">RSI < 30</div>
                            <div class="dropdown-item" @click="selectedCondition = 'Volume is High'; showDropdown = false">Volume is High</div>
                        </div>
                    </div>
                    
                    <span class="sentence-keyword">AND</span>
                    
                    <div class="sentence-dropdown" @click="showDropdown2 = !showDropdown2">
                        <span x-text="selectedCondition2 || 'Volume is High'"></span>
                        <i data-lucide="chevron-down"></i>
                        <div class="dropdown-menu" x-show="showDropdown2" @click.away="showDropdown2 = false">
                            <div class="dropdown-item" @click="selectedCondition2 = 'Volume is High'; showDropdown2 = false">Volume is High</div>
                            <div class="dropdown-item" @click="selectedCondition2 = 'MACD Crossover'; showDropdown2 = false">MACD Crossover</div>
                            <div class="dropdown-item" @click="selectedCondition2 = 'Support Level Hit'; showDropdown2 = false">Support Level Hit</div>
                        </div>
                    </div>
                    
                    <span class="sentence-keyword">THEN</span>
                    
                    <div class="sentence-dropdown" @click="showDropdown3 = !showDropdown3">
                        <span x-text="selectedAction || 'Buy $500 BTC'"></span>
                        <i data-lucide="chevron-down"></i>
                        <div class="dropdown-menu" x-show="showDropdown3" @click.away="showDropdown3 = false">
                            <div class="dropdown-item" @click="selectedAction = 'Buy $500 BTC'; showDropdown3 = false">Buy $500 BTC</div>
                            <div class="dropdown-item" @click="selectedAction = 'Sell 10% Position'; showDropdown3 = false">Sell 10% Position</div>
                            <div class="dropdown-item" @click="selectedAction = 'Start DCA Bot'; showDropdown3 = false">Start DCA Bot</div>
                        </div>
                    </div>
                </div>
                
                <div class="canvas-preview">
                    <div class="preview-card">
                        <div class="preview-header">
                            <i data-lucide="check-circle" class="text-success"></i>
                            <span>Strategy Valid</span>
                        </div>
                        <div class="preview-content">
                            <p>Your strategy will execute automatically when conditions are met.</p>
                            <button class="btn-preview">Deploy Strategy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Performance & Trust -->
<section class="trust-section py-5">
    <div class="container">
        <div class="trust-grid reveal">
            <div class="trust-card stats-card">
                <div class="trust-icon">
                    <i data-lucide="dollar-sign"></i>
                </div>
                <div class="trust-value" data-count="2.4">$0</div>
                <div class="trust-label">B+ Volume Traded</div>
            </div>
            
            <div class="trust-card stats-card">
                <div class="trust-icon">
                    <i data-lucide="bot"></i>
                </div>
                <div class="trust-value" data-count="150">0</div>
                <div class="trust-label">k+ Active Bots</div>
            </div>
            
            <div class="trust-card stats-card">
                <div class="trust-icon">
                    <i data-lucide="activity"></i>
                </div>
                <div class="trust-value">99.9%</div>
                <div class="trust-label">Uptime</div>
            </div>
            
            <div class="trust-card security-card">
                <div class="security-header">
                    <div class="security-icon">
                        <i data-lucide="lock"></i>
                    </div>
                    <h3 class="security-title">API Security</h3>
                </div>
                <p class="security-description">
                    Funds never leave your exchange. We use <strong>AES-256 encrypted API keys</strong> with no withdrawal permissions.
                </p>
                <div class="security-features">
                    <div class="security-feature">
                        <i data-lucide="check" class="text-success"></i>
                        <span>Read-only API access</span>
                    </div>
                    <div class="security-feature">
                        <i data-lucide="check" class="text-success"></i>
                        <span>IP whitelisting</span>
                    </div>
                    <div class="security-feature">
                        <i data-lucide="check" class="text-success"></i>
                        <span>2FA protection</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Tiers -->
<section class="pricing-section py-5" id="pricing">
    <div class="container">
        <div class="section-header text-center mb-5 reveal">
            <h2 class="section-title">
                Simple <span class="text-gradient">Pricing</span>
            </h2>
            <p class="section-subtitle">Choose the plan that fits your trading needs</p>
        </div>
        
        <div class="pricing-grid reveal">
            <!-- Free Explorer -->
            <div class="pricing-card" x-intersect:enter="$el.classList.add('revealed')">
                <div class="pricing-header">
                    <h3 class="pricing-name">Free Explorer</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$0</span>
                        <span class="price-period">/mo</span>
                    </div>
                </div>
                <ul class="pricing-features">
                    <li><i data-lucide="check" class="text-success"></i> 1 Active Bot</li>
                    <li><i data-lucide="check" class="text-success"></i> 1 Exchange Connection</li>
                    <li><i data-lucide="check" class="text-success"></i> Basic Backtesting</li>
                    <li><i data-lucide="x" class="text-gray-400"></i> No Webhook Support</li>
                </ul>
                <a href="{{ route('user.register') }}" class="pricing-cta btn-outline">Start Free</a>
            </div>
            
            <!-- Pro Trader (Featured) -->
            <div class="pricing-card pricing-featured" x-intersect:enter="$el.classList.add('revealed')">
                <div class="pricing-badge">Most Popular</div>
                <div class="pricing-header">
                    <h3 class="pricing-name">Pro Trader</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$49</span>
                        <span class="price-period">/mo</span>
                    </div>
                </div>
                <ul class="pricing-features">
                    <li><i data-lucide="check" class="text-success"></i> 10 Active Bots</li>
                    <li><i data-lucide="check" class="text-success"></i> Unlimited Exchanges</li>
                    <li><i data-lucide="check" class="text-success"></i> Advanced Backtesting</li>
                    <li><i data-lucide="check" class="text-success"></i> TradingView Webhooks</li>
                    <li><i data-lucide="check" class="text-success"></i> Priority Support</li>
                </ul>
                <a href="{{ route('user.register') }}" class="pricing-cta btn-shimmer">
                    <span class="btn-shimmer-text">Get Pro Now</span>
                </a>
            </div>
            
            <!-- Market Master -->
            <div class="pricing-card" x-intersect:enter="$el.classList.add('revealed')">
                <div class="pricing-header">
                    <h3 class="pricing-name">Market Master</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$149</span>
                        <span class="price-period">/mo</span>
                    </div>
                </div>
                <ul class="pricing-features">
                    <li><i data-lucide="check" class="text-success"></i> Unlimited Bots</li>
                    <li><i data-lucide="check" class="text-success"></i> Dedicated Server</li>
                    <li><i data-lucide="check" class="text-success"></i> Custom API Integration</li>
                    <li><i data-lucide="check" class="text-success"></i> 24/7 VIP Support</li>
                    <li><i data-lucide="check" class="text-success"></i> White-label Options</li>
                </ul>
                <a href="{{ route('user.register') }}" class="pricing-cta btn-outline">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    // Counter animation
    function animateCounter(element) {
        const target = parseFloat(element.getAttribute('data-count'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target.toFixed(1) + (target >= 1 ? 'B' : 'k');
                clearInterval(timer);
            } else {
                element.textContent = current.toFixed(1) + (target >= 1 ? 'B' : 'k');
            }
        }, 16);
    }
    
    // Intersection Observer for counters
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target.hasAttribute('data-count')) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));
    
    // Strategy canvas Alpine.js data
    function strategyCanvas() {
        return {
            activeTab: 'rules',
            showDropdown: false,
            showDropdown2: false,
            showDropdown3: false,
            selectedCondition: 'Price Drops 5%',
            selectedCondition2: 'Volume is High',
            selectedAction: 'Buy $500 BTC'
        }
    }
    
    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        const offsetTop = target.offsetTop - 100; // Account for sticky navbar
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
    
    // Re-initialize Lucide icons after Alpine.js updates
    document.addEventListener('alpine:init', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endpush
@endsection

