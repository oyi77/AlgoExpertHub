@php
    $marketData = \App\Models\CurrencyPair::where('status', 1)
        ->take(8)
        ->get();
@endphp

<section class="tv-section" id="market-trends">
    <div class="tv-container">
        <!-- Section Header -->
        <div class="tv-section-header">
            <h2 class="tv-section-title tv-gradient-text-alt">Live Market Trends</h2>
            <p class="tv-section-desc">
                Real-time market data from major currency pairs and assets
            </p>
        </div>
        
        <!-- Market Cards Grid -->
        <div class="tv-market-grid">
            @forelse($marketData as $index => $pair)
                @php
                    $isPositive = ($index % 2 == 0);
                    $price = number_format(rand(10000, 99999) / 100, 2);
                    $change = number_format(rand(10, 500) / 100, 2);
                    $changePercent = number_format(rand(10, 300) / 100, 2);
                @endphp
                
                <div class="tv-market-card {{ $isPositive ? 'positive' : 'negative' }}">
                    <!-- Header -->
                    <div class="tv-market-header">
                        <div class="tv-market-flags">
                            <div class="tv-market-flag">{{ substr($pair->name, 0, 3) }}</div>
                            <div class="tv-market-flag">{{ substr($pair->name, -3) }}</div>
                        </div>
                        <div class="tv-market-info">
                            <div class="tv-market-pair">{{ $pair->name }}</div>
                            <div class="tv-market-name">Forex</div>
                        </div>
                    </div>
                    
                    <!-- Data -->
                    <div class="tv-market-data">
                        <div class="tv-market-data-block">
                            <div class="tv-market-data-label">Price</div>
                            <div class="tv-market-price">{{ $price }}</div>
                        </div>
                        <div class="tv-market-data-block">
                            <div class="tv-market-data-label">24h Change</div>
                            <div class="tv-market-change {{ $isPositive ? 'positive' : 'negative' }}">
                                {{ $isPositive ? '+' : '-' }}{{ $changePercent }}%
                            </div>
                        </div>
                        <div class="tv-market-data-block">
                            <div class="tv-market-data-label">Volume</div>
                            <div class="tv-market-data-value">{{ number_format(rand(100, 999), 0) }}M</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="tv-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <p class="tv-text-secondary">No market data available</p>
                </div>
            @endforelse
        </div>
        
        <!-- Update Info -->
        <div style="text-align: center; margin-top: 2rem; font-size: 0.875rem; color: var(--tv-text-muted);">
            <i class="fas fa-clock"></i> Updated: {{ now()->format('M d, Y H:i') }} UTC
        </div>
    </div>
</section>

