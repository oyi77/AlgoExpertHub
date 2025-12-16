@php
    $plans = \App\Models\Plan::where('status', 1)->get();
@endphp

<section class="tv-section" id="account-types">
    <div class="tv-container">
        <!-- Section Header -->
        <div class="tv-section-header">
            <h2 class="tv-section-title tv-gradient-text">Choose Your Plan</h2>
            <p class="tv-section-desc">
                Select the perfect plan for your trading journey
            </p>
        </div>
        
        <!-- Pricing Grid -->
        <div class="tv-pricing-grid">
            @forelse($plans as $index => $plan)
                <div class="tv-pricing-card {{ $index == 1 ? 'featured' : '' }}">
                    @if($index == 1)
                        <div class="tv-pricing-badge">
                            <i class="fas fa-fire"></i> Popular
                        </div>
                    @endif
                    
                    <!-- Header -->
                    <div class="tv-pricing-header">
                        <div class="tv-pricing-icon">
                            <i class="fas fa-{{ $index == 0 ? 'star' : ($index == 1 ? 'crown' : 'rocket') }}"></i>
                        </div>
                        <h3 class="tv-pricing-name tv-gradient-text">{{ $plan->name }}</h3>
                        <p class="tv-pricing-desc">{{ $plan->description ?? 'Perfect for ' . strtolower($plan->name) . ' traders' }}</p>
                    </div>
                    
                    <!-- Features -->
                    <div class="tv-pricing-features">
                        <div class="tv-pricing-feature">
                            <span class="tv-pricing-feature-label">Signals</span>
                            <span class="tv-pricing-feature-value">{{ $plan->signals()->count() }} Pairs</span>
                        </div>
                        <div class="tv-pricing-feature">
                            <span class="tv-pricing-feature-label">Duration</span>
                            <span class="tv-pricing-feature-value">{{ $plan->plan_type == 'lifetime' ? 'Lifetime' : $plan->duration . ' Days' }}</span>
                        </div>
                        <div class="tv-pricing-feature">
                            <span class="tv-pricing-feature-label">Support</span>
                            <span class="tv-pricing-feature-value">24/7</span>
                        </div>
                        <div class="tv-pricing-feature">
                            <span class="tv-pricing-feature-label">Updates</span>
                            <span class="tv-pricing-feature-value">Real-time</span>
                        </div>
                        <div class="tv-pricing-feature">
                            <span class="tv-pricing-feature-label">Price</span>
                            <span class="tv-pricing-feature-value">${{ number_format($plan->price, 2) }}</span>
                        </div>
                    </div>
                    
                    <!-- CTA -->
                    <div class="tv-pricing-cta">
                        @auth
                            <a href="{{ route('user.plans') }}" class="tv-btn">
                                Subscribe Now
                            </a>
                        @else
                            <a href="{{ route('user.register') }}" class="tv-btn">
                                Get Started
                            </a>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="tv-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <p class="tv-text-secondary">No plans available at the moment</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

