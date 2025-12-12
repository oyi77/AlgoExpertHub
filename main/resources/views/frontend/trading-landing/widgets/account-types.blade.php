@php
    $plans = \App\Models\Plan::whereStatus(true)->take(3)->get();
@endphp

<section class="account-types-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Compare Our Account Types</h2>
            <p class="section-description">Choose the Right Trading Account for Your Strategy and Risk Appetite</p>
        </div>

        <div class="pricing-cards">
            @foreach($plans as $index => $plan)
                <div class="pricing-card {{ $index === 1 ? 'featured' : '' }}">
                    @if($index === 1)
                        <div class="popular-badge">
                            <i class="las la-fire"></i>
                            <span>Popular</span>
                        </div>
                    @endif
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="las la-{{ $index === 0 ? 'user' : ($index === 1 ? 'user-star' : 'user-crown') }}"></i>
                        </div>
                        <h3 class="card-title">{{ $plan->name }}</h3>
                        <div class="card-price">
                            @if($plan->price > 0)
                                <span class="price">{{ Config::formatter($plan->price) }}</span>
                                @if($plan->plan_type === 'limited')
                                    <span class="period">/ {{ $plan->duration }} days</span>
                                @else
                                    <span class="period">/ lifetime</span>
                                @endif
                            @else
                                <span class="price">Free</span>
                            @endif
                        </div>
                        <p class="card-subtitle">{{ $plan->description ?? 'Professional trading account' }}</p>
                    </div>
                    <div class="card-features">
                        <div class="feature-item">
                            <span class="feature-label">Platform</span>
                            <span class="feature-value">MetaTrader 5</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Signals</span>
                            <span class="feature-value">{{ $plan->signals_count ?? 'Unlimited' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Max. Leverage</span>
                            <span class="feature-value">1:{{ $plan->leverage ?? '500' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Commission</span>
                            <span class="feature-value">{{ $plan->commission ?? 'None' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Support</span>
                            <span class="feature-value">{{ $plan->support_level ?? 'Email' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Min. Deposit</span>
                            <span class="feature-value">{{ Config::formatter($plan->min_deposit ?? 50) }}</span>
                        </div>
                    </div>
                    <a href="{{ route('user.register') }}?plan={{ $plan->id }}" class="btn btn-card-cta">
                        {{ $plan->price > 0 ? 'Subscribe Now' : 'Get Started' }}
                    </a>
                </div>
            @endforeach
            
            <div class="pricing-card featured">
                <div class="popular-badge">
                    <i class="las la-fire"></i>
                    <span>Popular</span>
                </div>
                <div class="card-header">
                    <div class="card-icon">
                        <i class="las la-user-star"></i>
                    </div>
                    <h3 class="card-title">ECN Pro Account</h3>
                    <p class="card-subtitle">Optimal for advanced traders needing raw pricing and low latency execution</p>
                </div>
                <div class="card-features">
                    <div class="feature-item">
                        <span class="feature-label">Platform</span>
                        <span class="feature-value">MetaTrader 5</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Spread</span>
                        <span class="feature-value">From 0.1 pips</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Commission</span>
                        <span class="feature-value">$3.00 per lot (round turn)</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Max. Leverage</span>
                        <span class="feature-value">1:300</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">SWAP Free</span>
                        <span class="feature-value">Available on request</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Min. Deposit</span>
                        <span class="feature-value">$200</span>
                    </div>
                </div>
                <a href="{{ route('user.register') }}" class="btn btn-card-cta">Open Account</a>
            </div>
            
            <div class="pricing-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="las la-user-heart"></i>
                    </div>
                    <h3 class="card-title">Premium Account</h3>
                    <p class="card-subtitle">For experienced traders managing larger portfolios</p>
                </div>
                <div class="card-features">
                    <div class="feature-item">
                        <span class="feature-label">Platform</span>
                        <span class="feature-value">MetaTrader 5</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Spread</span>
                        <span class="feature-value">Starting from 0.3 pips</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Commission</span>
                        <span class="feature-value">None</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Max. Leverage</span>
                        <span class="feature-value">1:500</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">SWAP Free</span>
                        <span class="feature-value">Available on request</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-label">Min. Deposit</span>
                        <span class="feature-value">$1000</span>
                    </div>
                </div>
                <a href="{{ route('user.register') }}" class="btn btn-card-cta">Open Account</a>
            </div>
        </div>
    </div>
</section>

