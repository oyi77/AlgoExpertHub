@extends('frontend.landings.bot-sales.layout.master')

@section('content')
<!-- Hero Section -->
<section class="relative pt-32 pb-20 md:pt-48 md:pb-32 overflow-hidden">
    <div class="container mx-auto px-6 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <div data-aos="fade-up" class="inline-flex items-center space-x-2 px-4 py-2 rounded-full glass mb-8 text-sm font-medium text-primary-400">
                <span class="flex h-2 w-2 rounded-full bg-primary-400 animate-pulse"></span>
                <span>{{ __('New: v5.0 institutional algorithms now live') }}</span>
            </div>
            
            <h1 data-aos="fade-up" data-aos-delay="100" class="text-5xl md:text-7xl font-extrabold text-white tracking-tight mb-8 leading-[1.1]">
                {{ __('Automate Your Trading with') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-indigo-400">{{ __('Institutional Precision') }}</span>
            </h1>
            
            <p data-aos="fade-up" data-aos-delay="200" class="text-xl text-dark-300 mb-10 leading-relaxed max-w-2xl mx-auto">
                {{ __('Deploy sophisticated AI-driven bots across Crypto, Forex, and Indices. Zero coding required. 24/7 execution with millisecond latency.') }}
            </p>
            
            <div data-aos="fade-up" data-aos-delay="300" class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="{{ route('user.register') }}" class="btn-shimmer px-10 py-4 rounded-2xl text-lg font-bold shadow-2xl shadow-primary-500/40 w-full sm:w-auto">
                    {{ __('Start Free Trial') }}
                </a>
                <a href="#bots" class="px-10 py-4 rounded-2xl border border-white/10 hover:bg-white/5 transition-all text-lg font-bold w-full sm:w-auto">
                    {{ __('Explore Bots') }}
                </a>
            </div>

            <div data-aos="fade-up" data-aos-delay="400" class="mt-16 flex items-center justify-center space-x-8 grayscale opacity-50">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/46/Bitcoin.svg" class="h-8" alt="Bitcoin">
                <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Ethereum_logo_2014.svg" class="h-8" alt="Ethereum">
                <img src="https://upload.wikimedia.org/wikipedia/commons/8/8b/Binance_Logo.svg" class="h-8" alt="Binance">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a4/Coinbase_Logo.svg" class="h-8" alt="Coinbase">
            </div>
        </div>
    </div>

    <!-- Background Elements -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-primary-600/10 blur-[120px] rounded-full -z-10 mt-[-200px]"></div>
</section>

<!-- Live Ticker -->
<div class="border-y border-white/5 bg-dark-900/50 backdrop-blur-md py-4 overflow-hidden relative">
    <div class="flex whitespace-nowrap animate-[marquee_30s_linear_infinite]">
        @foreach(['BTC/USDT' => '+2.45%', 'ETH/USDT' => '-1.12%', 'BNB/USDT' => '+0.89%', 'XRP/USDT' => '+5.67%', 'SOL/USDT' => '+3.21%', 'ADA/USDT' => '-0.45%', 'DOT/USDT' => '+1.23%'] as $pair => $change)
            <div class="flex items-center space-x-4 mx-12">
                <span class="font-bold text-white">{{ $pair }}</span>
                <span class="{{ str_contains($change, '+') ? 'text-green-500' : 'text-red-500' }} font-mono">{{ $change }}</span>
            </div>
        @endforeach
        <!-- Repeat for continuous loop -->
        @foreach(['BTC/USDT' => '+2.45%', 'ETH/USDT' => '-1.12%', 'BNB/USDT' => '+0.89%', 'XRP/USDT' => '+5.67%', 'SOL/USDT' => '+3.21%', 'ADA/USDT' => '-0.45%', 'DOT/USDT' => '+1.23%'] as $pair => $change)
            <div class="flex items-center space-x-4 mx-12">
                <span class="font-bold text-white">{{ $pair }}</span>
                <span class="{{ str_contains($change, '+') ? 'text-green-500' : 'text-red-500' }} font-mono">{{ $change }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Features Bento Grid -->
<section id="features" class="py-32 bg-dark-950">
    <div class="container mx-auto px-6">
        <div class="text-center mb-20">
            <h2 data-aos="fade-up" class="text-4xl md:text-5xl font-bold text-white mb-6">{{ __('Built for Speed, Engineered for Profit') }}</h2>
            <p data-aos="fade-up" data-aos-delay="100" class="text-dark-400 max-w-2xl mx-auto">{{ __('Institutional grade infrastructure available to every trader. Scale your portfolio with intelligent automation.') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Large Feature -->
            <div data-aos="fade-up" class="md:col-span-2 glass-dark p-10 rounded-3xl group overflow-hidden relative">
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-primary-600/20 rounded-2xl flex items-center justify-center text-primary-400 mb-8">
                        <i data-feather="cpu" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ __('Advanced Neural Algorithms') }}</h3>
                    <p class="text-dark-400 leading-relaxed max-w-md">
                        {{ __('Our bots utilize multi-layer neural networks to identify patterns in volatile markets, executing trades with 99.9% uptime.') }}
                    </p>
                </div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-primary-600/10 blur-3xl rounded-full group-hover:bg-primary-600/20 transition-all duration-700"></div>
            </div>

            <!-- Small Feature -->
            <div data-aos="fade-up" data-aos-delay="100" class="glass-dark p-10 rounded-3xl border-primary-500/20">
                <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-400 mb-8">
                    <i data-feather="shield" class="w-8 h-8"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4">{{ __('Risk First') }}</h3>
                <p class="text-dark-400 leading-relaxed">
                    {{ __('Dynamic stop-loss, trailing take-profit, and asset-diversification built into every core.') }}
                </p>
            </div>

            <!-- Small Feature -->
            <div data-aos="fade-up" data-aos-delay="200" class="glass-dark p-10 rounded-3xl">
                <div class="w-14 h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-400 mb-8">
                    <i data-feather="zap" class="w-8 h-8"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4">{{ __('Instant Sync') }}</h3>
                <p class="text-dark-400 leading-relaxed">
                    {{ __('Connect your favorite exchange via highly secure API in under 60 seconds.') }}
                </p>
            </div>

            <!-- Large Feature -->
            <div data-aos="fade-up" data-aos-delay="300" class="md:col-span-2 glass-dark p-10 rounded-3xl overflow-hidden relative">
                <div class="flex flex-col md:flex-row items-center gap-10">
                    <div class="flex-1">
                        <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-400 mb-8">
                            <i data-feather="bar-chart-2" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">{{ __('Analytics Dashboard') }}</h3>
                        <p class="text-dark-400 leading-relaxed">
                            {{ __('Track every execution, analyze performance metrics, and optimize your strategies with our comprehensive data center.') }}
                        </p>
                    </div>
                    <div class="hidden md:block w-full max-w-xs glass p-4 rounded-2xl rotate-2">
                        <div class="space-y-3">
                            <div class="h-2 w-full bg-white/10 rounded"></div>
                            <div class="h-2 w-2/3 bg-white/10 rounded"></div>
                            <div class="h-16 w-full bg-primary-600/30 rounded-xl"></div>
                            <div class="flex justify-between">
                                <div class="h-2 w-1/4 bg-white/10 rounded"></div>
                                <div class="h-2 w-1/4 bg-green-500/30 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bot Showcase Section -->
<section id="bots" class="py-32 relative">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row items-end justify-between mb-16 gap-6">
            <div class="max-w-2xl">
                <h2 data-aos="fade-right" class="text-4xl font-bold text-white mb-6">{{ __('The Elite Bot Fleet') }}</h2>
                <p data-aos="fade-right" data-aos-delay="100" class="text-dark-400">{{ __('Choose from our pre-configured battle-tested strategies or build your own.') }}</p>
            </div>
            <a data-aos="fade-left" href="#" class="text-primary-400 font-bold flex items-center group">
                {{ __('View Marketplace') }}
                <i data-feather="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['name' => 'Neural Sniper', 'type' => 'Crypto Scalp', 'win' => '84.2%', 'roi' => '+228%', 'color' => 'primary', 'desc' => 'High-frequency execution on major crypto pairs.'],
                ['name' => 'FX Guardian', 'type' => 'Forex Trend', 'win' => '76.8%', 'roi' => '+145%', 'color' => 'purple', 'desc' => 'Long-term trend following for major currency markets.'],
                ['name' => 'Index Master', 'type' => 'Indices Grid', 'win' => '81.4%', 'roi' => '+189%', 'color' => 'orange', 'desc' => 'Grid trading optimized for S&P 500 and NASDAQ volatility.']
            ] as $bot)
            <div data-aos="zoom-in" class="glass-dark p-8 rounded-3xl border-white/5 hover:border-{{ $bot['color'] }}-500/30 transition-all group">
                <div class="flex justify-between items-start mb-8">
                    <div class="w-16 h-16 bg-{{ $bot['color'] }}-500/10 rounded-2xl flex items-center justify-center text-{{ $bot['color'] }}-400">
                        <i data-feather="terminal" class="w-10 h-10"></i>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-white/5 text-xs font-bold text-dark-400 uppercase tracking-widest">{{ $bot['type'] }}</span>
                </div>
                <h4 class="text-2xl font-bold text-white mb-2 group-hover:text-{{ $bot['color'] }}-400 transition-colors">{{ $bot['name'] }}</h4>
                <p class="text-dark-400 text-sm mb-8 leading-relaxed">{{ $bot['desc'] }}</p>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-4 rounded-2xl bg-white/5">
                        <span class="block text-xs text-dark-500 mb-1 font-bold">{{ __('Win Rate') }}</span>
                        <span class="text-lg font-bold text-white">{{ $bot['win'] }}</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/5">
                        <span class="block text-xs text-dark-500 mb-1 font-bold">{{ __('Est. ROI') }}</span>
                        <span class="text-lg font-bold text-green-400">{{ $bot['roi'] }}</span>
                    </div>
                </div>

                <a href="#" class="block text-center py-4 rounded-2xl bg-white/10 hover:bg-white/20 transition-all font-bold text-sm">
                    {{ __('Details') }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-32 bg-dark-900/30">
    <div class="container mx-auto px-6">
        <div class="text-center mb-20">
            <h2 data-aos="fade-up" class="text-4xl font-bold text-white mb-6">{{ __('Flexible Plans for Every Trader') }}</h2>
            <p data-aos="fade-up" data-aos-delay="100" class="text-dark-400">{{ __('No hidden fees. Scale as you grow.') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <!-- Basic -->
            <div data-aos="fade-up" class="glass-dark p-10 rounded-3xl border-white/5 flex flex-col">
                <h4 class="text-xl font-bold text-white mb-2">{{ __('Starter') }}</h4>
                <div class="flex items-baseline mb-8">
                    <span class="text-4xl font-black text-white">$49</span>
                    <span class="text-dark-500 ml-2">/mo</span>
                </div>
                <ul class="space-y-4 mb-10 flex-1">
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> 2 Active Bots
                    </li>
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> 1 Exchange Sync
                    </li>
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> Core Algorithms
                    </li>
                </ul>
                <a href="#" class="block text-center py-4 rounded-2xl border border-white/10 hover:bg-white/5 transition-all font-bold">
                    {{ __('Select Plan') }}
                </a>
            </div>

            <!-- Pro -->
            <div data-aos="fade-up" data-aos-delay="100" class="glass-dark p-10 rounded-3xl border-primary-500/50 relative transform md:scale-110 shadow-2xl shadow-primary-500/10 flex flex-col">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-primary-600 rounded-full text-[10px] font-black uppercase tracking-tighter">Most Popular</div>
                <h4 class="text-xl font-bold text-white mb-2">{{ __('Pro Trader') }}</h4>
                <div class="flex items-baseline mb-8">
                    <span class="text-4xl font-black text-white">$99</span>
                    <span class="text-dark-500 ml-2">/mo</span>
                </div>
                <ul class="space-y-4 mb-10 flex-1">
                    <li class="flex items-center text-sm text-white">
                        <i data-feather="check" class="w-4 h-4 text-primary-400 mr-3"></i> 10 Active Bots
                    </li>
                    <li class="flex items-center text-sm text-white">
                        <i data-feather="check" class="w-4 h-4 text-primary-400 mr-3"></i> Unlimited Exchanges
                    </li>
                    <li class="flex items-center text-sm text-white">
                        <i data-feather="check" class="w-4 h-4 text-primary-400 mr-3"></i> Neural Scalp Add-on
                    </li>
                    <li class="flex items-center text-sm text-white">
                        <i data-feather="check" class="w-4 h-4 text-primary-400 mr-3"></i> Advanced Analytics
                    </li>
                </ul>
                <a href="#" class="btn-shimmer block text-center py-4 rounded-2xl font-bold shadow-xl shadow-primary-500/20">
                    {{ __('Start Pro Free') }}
                </a>
            </div>

            <!-- Elite -->
            <div data-aos="fade-up" data-aos-delay="200" class="glass-dark p-10 rounded-3xl border-white/5 flex flex-col">
                <h4 class="text-xl font-bold text-white mb-2">{{ __('Institutional') }}</h4>
                <div class="flex items-baseline mb-8">
                    <span class="text-4xl font-black text-white">$249</span>
                    <span class="text-dark-500 ml-2">/mo</span>
                </div>
                <ul class="space-y-4 mb-10 flex-1">
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> Unlimited Everything
                    </li>
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> Dedicated Account Manager
                    </li>
                    <li class="flex items-center text-sm text-dark-300">
                        <i data-feather="check" class="w-4 h-4 text-green-400 mr-3"></i> API Direct Access
                    </li>
                </ul>
                <a href="#" class="block text-center py-4 rounded-2xl border border-white/10 hover:bg-white/5 transition-all font-bold">
                    {{ __('Select Plan') }}
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-32 overflow-hidden relative">
    <div class="container mx-auto px-6">
        <div class="glass-dark p-16 rounded-[3rem] text-center relative overflow-hidden">
            <div class="relative z-10">
                <h2 data-aos="fade-up" class="text-4xl md:text-6xl font-black text-white mb-8">{{ __('Ready to Outperform?') }}</h2>
                <p data-aos="fade-up" data-aos-delay="100" class="text-xl text-dark-300 mb-12 max-w-xl mx-auto">{{ __('Join 50,000+ traders already automating their wealth. Start your 14-day free trial today.') }}</p>
                <div data-aos="fade-up" data-aos-delay="200" class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <a href="{{ route('user.register') }}" class="btn-shimmer px-12 py-5 rounded-2xl text-xl font-bold shadow-2xl shadow-primary-500/40 w-full sm:w-auto">
                        {{ __('Get Started Now') }}
                    </a>
                    <a href="#" class="px-12 py-5 rounded-2xl border border-white/10 hover:bg-white/5 transition-all text-xl font-bold w-full sm:w-auto">
                        {{ __('Watch Demo') }}
                    </a>
                </div>
            </div>
            
            <!-- Shimmer backgrounds for CTA -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-600/10 blur-[100px] rounded-full"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-600/10 blur-[100px] rounded-full"></div>
        </div>
    </div>
</section>

<style>
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
</style>
@endsection
