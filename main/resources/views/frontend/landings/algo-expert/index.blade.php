@extends('frontend.landings.algo-expert.layout.master')

@section('content')
<!-- Hero Section -->
<header class="py-5 position-relative overflow-hidden" style="min-height: 85vh; display: flex; align-items: center;">
    <div class="glow-box" style="top: 10%; right: 10%;"></div>
    <div class="glow-box" style="bottom: 10%; left: 5%; background: var(--success-glow);"></div>
    
    <div class="container h-full">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-4 reveal">
                    🚀 Institutional grade trading for everyone
                </div>
                <h1 class="display-3 mb-4 reveal" style="line-height: 1.1;">
                    Master the Markets with <span class="text-gradient">AlgoExpertHub.</span>
                </h1>
                <p class="lead text-dim mb-5 reveal" style="max-width: 600px;">
                    Automate your trading strategy with lightning-fast execution, 24/7. Connect your exchange via API and let our bots handle the rest. Non-custodial, secure, and powerful.
                </p>
                <div class="d-flex flex-wrap gap-4 reveal">
                    <a href="{{ route('user.register') }}" class="btn-shimmer">Get Started for Free</a>
                    <a href="#strategies" class="btn btn-link text-white text-decoration-none d-flex align-items-center gap-2 font-bold">
                        <i data-lucide="play-circle"></i> View Demo
                    </a>
                </div>
                
                <div class="mt-5 pt-4 d-flex align-items-center gap-5 reveal">
                    <div>
                        <h4 class="mb-1">$2.4B+</h4>
                        <p class="small text-dim mb-0">Volume Traded</p>
                    </div>
                    <div style="width: 1px; height: 40px; background: var(--glass-border);"></div>
                    <div>
                        <h4 class="mb-1">150k+</h4>
                        <p class="small text-dim mb-0">Active Traders</p>
                    </div>
                    <div style="width: 1px; height: 40px; background: var(--glass-border);"></div>
                    <div>
                        <h4 class="mb-1">99.9%</h4>
                        <p class="small text-dim mb-0">Uptime</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block reveal">
                <div class="position-relative">
                    <div class="glass-card p-4" style="transform: perspective(1000px) rotateY(-10deg) rotateX(5deg);">
                        <img src="{{ asset('asset/frontend/landings/bot-sales/hero.png') }}" alt="trading-bot" class="img-fluid rounded-xl shadow-2xl">
                    </div>
                    <!-- Small floating UI elements -->
                    <div class="glass-card p-3 absolute" style="top: -20px; right: 20px; animation: float 4s ease-in-out infinite;">
                        <span class="text-success d-flex align-items-center gap-2"><i data-lucide="trending-up"></i> +12.4% Profit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Bento Section: Strategy Builder -->
<section id="strategies" class="py-5">
    <div class="container">
        <div class="text-center mb-5 pb-4">
            <h2 class="display-5 mb-3">Logic-Based <span class="text-gradient">Trading.</span></h2>
            <p class="text-dim">Build complex strategies without writing a single line of code.</p>
        </div>

        <div class="bento-grid" style="height: 600px;">
            <!-- Main Bento Box -->
            <div class="bento-item col-span-2" style="grid-column: span 2; grid-row: span 2;">
                <div class="d-flex justify-content-between align-items-start mb-5">
                    <div>
                        <h3 class="h4 mb-2">The Engine</h3>
                        <p class="text-dim small">Dynamic Rule Execution</p>
                    </div>
                    <i data-lucide="cpu" class="text-primary" size="32"></i>
                </div>
                <div class="strategy-visual mt-4">
                    <div class="d-flex flex-column gap-3">
                        <div class="glass-card p-3 border-primary border-opacity-50">
                            <span class="text-xs text-primary font-bold uppercase mb-1 d-block">If Condition</span>
                            <p class="mb-0">RSI (14) crosses below 30 on 1h timeframe</p>
                        </div>
                        <div class="text-center py-2"><i data-lucide="arrow-down"></i></div>
                        <div class="glass-card p-3 border-success border-opacity-50">
                            <span class="text-xs text-success font-bold uppercase mb-1 d-block">Then Action</span>
                            <p class="mb-0">Execute Limit Buy order with 2% wallet balance</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Bento -->
            <div class="bento-item" style="grid-column: span 1;">
                <i data-lucide="zap" class="text-warning mb-3" size="24"></i>
                <h4 class="h5 mb-2">Instant Execution</h4>
                <p class="text-dim small mb-0">Sub-50ms latency between signal and exchange order.</p>
            </div>

            <div class="bento-item" style="grid-column: span 1;">
                <i data-lucide="shield-check" class="text-success mb-3" size="24"></i>
                <h4 class="h5 mb-2">API Security</h4>
                <p class="text-dim small mb-0">AES-256 encrypted keys with mandatory IP whitelisting.</p>
            </div>

            <div class="bento-item" style="grid-column: span 2;">
                <div class="row align-items-center">
                    <div class="col-7">
                        <h4 class="h5 mb-2">Backtest Suite</h4>
                        <p class="text-dim small mb-0">Run your logic against 5 years of historical tick-level data.</p>
                    </div>
                    <div class="col-5">
                        <div class="chart-mockup d-flex align-items-end gap-1 h-100">
                            <div class="bg-primary" style="height: 30%; width: 10px; border-radius: 2px;"></div>
                            <div class="bg-primary" style="height: 50%; width: 10px; border-radius: 2px;"></div>
                            <div class="bg-primary" style="height: 80%; width: 10px; border-radius: 2px;"></div>
                            <div class="bg-primary" style="height: 40%; width: 10px; border-radius: 2px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Features Section -->
<section id="features" class="py-5 bg-surface-light bg-opacity-25">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-md-4 reveal">
                <div class="glass-card p-5 h-100">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-4" style="width: 60px; height: 600px; max-height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 16px;">
                        <i data-lucide="grid"></i>
                    </div>
                    <h4>Grid & DCA Bots</h4>
                    <p class="text-dim">Accumulate assets during sideways markets or buy the dip automatically with our advanced Dollar Cost Averaging engine.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-card p-5 h-100">
                    <div class="icon-circle bg-success bg-opacity-10 text-success mb-4" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 16px;">
                        <i data-lucide="send"></i>
                    </div>
                    <h4>Signal Integration</h4>
                    <p class="text-dim">Follow your favorite Telegram or TradingView signal providers. Our system ingests webhooks and executes instantly.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-card p-5 h-100">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-4" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 16px;">
                        <i data-lucide="layout"></i>
                    </div>
                    <h4>Multi-Exchange</h4>
                    <p class="text-dim">Manage your portfolio across Binance, Bybit, OKX, and Coinbase from a single unified dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="pricing" class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5 pb-4">
            <h2 class="display-5 mb-3">Simple <span class="text-gradient">Pricing.</span></h2>
            <p class="text-dim">Scale your automation as your portfolio grows.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6 reveal">
                <div class="glass-card p-5 h-100">
                    <p class="text-primary font-bold mb-2">Starter</p>
                    <h3 class="display-6 mb-4">$0 <small class="text-dim" style="font-size: 1rem;">/mo</small></h3>
                    <ul class="list-unstyled text-dim mb-5">
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> 1 Active Bot</li>
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> 1 Exchange Connection</li>
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> Basic Backtesting</li>
                        <li class="text-muted"><i data-lucide="x-circle" size="18"></i> No Webhook Support</li>
                    </ul>
                    <a href="{{ route('user.register') }}" class="btn btn-outline-light w-full py-3" style="border-radius: 12px;">Start Free</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="glass-card p-5 h-100 border-primary" style="position: relative;">
                    <div class="badge bg-primary absolute" style="top: -12px; left: 50%; transform: translateX(-50%);">Most Popular</div>
                    <p class="text-primary font-bold mb-2">Pro</p>
                    <h3 class="display-6 mb-4">$49 <small class="text-dim" style="font-size: 1rem;">/mo</small></h3>
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3 d-flex gap-2 text-white"><i data-lucide="check-circle" class="text-success" size="18"></i> 10 Active Bots</li>
                        <li class="mb-3 d-flex gap-2 text-white"><i data-lucide="check-circle" class="text-success" size="18"></i> Unlimited Exchanges</li>
                        <li class="mb-3 d-flex gap-2 text-white"><i data-lucide="check-circle" class="text-success" size="18"></i> Advanced Backtesting</li>
                        <li class="mb-3 d-flex gap-2 text-white"><i data-lucide="check-circle" class="text-success" size="18"></i> TradingView Webhooks</li>
                    </ul>
                    <a href="{{ route('user.register') }}" class="btn-shimmer w-full py-3">Get Pro Now</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="glass-card p-5 h-100">
                    <p class="text-primary font-bold mb-2">Institutional</p>
                    <h3 class="display-6 mb-4">$199 <small class="text-dim" style="font-size: 1rem;">/mo</small></h3>
                    <ul class="list-unstyled text-dim mb-5">
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> Unlimited Bots</li>
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> Dedicated Server</li>
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> Custom API Integration</li>
                        <li class="mb-3 d-flex gap-2"><i data-lucide="check-circle" class="text-success" size="18"></i> 24/7 VIP Support</li>
                    </ul>
                    <a href="{{ route('user.register') }}" class="btn btn-outline-light w-full py-3" style="border-radius: 12px;">Contact Sales</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security Banner -->
<section class="py-5">
    <div class="container py-5">
        <div class="glass-card p-5 border-success border-opacity-25" style="background: linear-gradient(rgba(16, 185, 129, 0.05), transparent);">
            <div class="row align-items-center">
                <div class="col-md-1 text-center mb-4 mb-md-0">
                    <i data-lucide="shield-alert" class="text-success" size="48"></i>
                </div>
                <div class="col-md-8">
                    <h4 class="mb-2">Your Security is Our Priority</h4>
                    <p class="text-dim mb-0">Unlike other platforms, AlgoExpertHub is 100% non-custodial. We never ask for withdrawal permissions on your API keys. Your funds always stay in your exchange wallet.</p>
                </div>
                <div class="col-md-3 text-md-end mt-4 mt-md-0">
                    <a href="#" class="btn btn-outline-success font-bold" style="border-radius: 12px; padding: 12px 24px;">Security Audit Details</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .reveal {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s ease-out;
    }
    .reveal.revealed {
        opacity: 1;
        transform: translateY(0);
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
</style>
@endsection
