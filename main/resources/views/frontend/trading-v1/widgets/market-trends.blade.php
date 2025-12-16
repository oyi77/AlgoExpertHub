@php
    $marketData = \App\Models\CurrencyPair::where('status', 1)
        ->take(8)
        ->get();
@endphp

<section class="tv-section" id="market-trends">
    <div class="tv-container">
        <!-- Section Header -->
        <div class="tv-section-header scroll-reveal">
            <h2 class="tv-section-title tv-gradient-text-alt">Live Market Trends</h2>
            <p class="tv-section-desc">
                Real-time market data from major currency pairs and assets
            </p>
        </div>
        
        <!-- Market Cards Grid -->
        <div class="tv-market-grid" id="marketTrendsGrid">
            @forelse($marketData as $index => $pair)
                @php
                    $isPositive = ($index % 2 == 0);
                    $price = number_format(rand(10000, 99999) / 100, 2);
                    $change = number_format(rand(10, 500) / 100, 2);
                    $changePercent = number_format(rand(10, 300) / 100, 2);
                @endphp
                
                <div class="tv-market-card {{ $isPositive ? 'positive' : 'negative' }} scroll-reveal scroll-reveal-scale scroll-reveal-delay-{{ ($index % 3) + 1 }}" data-pair="{{ $pair->name }}">
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
                            <div class="tv-market-price" data-price>{{ $price }}</div>
                        </div>
                        <div class="tv-market-data-block">
                            <div class="tv-market-data-label">24h Change</div>
                            <div class="tv-market-change {{ $isPositive ? 'positive' : 'negative' }}" data-change>
                                {{ $isPositive ? '+' : '-' }}{{ $changePercent }}%
                            </div>
                        </div>
                        <div class="tv-market-data-block">
                            <div class="tv-market-data-label">Volume</div>
                            <div class="tv-market-data-value" data-volume>{{ number_format(rand(100, 999), 0) }}M</div>
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
            <i class="fas fa-sync-alt fa-spin" id="marketUpdateIcon" style="display: none;"></i>
            <span id="marketUpdateTime">
            <i class="fas fa-clock"></i> Updated: {{ now()->format('M d, Y H:i') }} UTC
            </span>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function() {
    'use strict';
    
    const marketGrid = document.getElementById('marketTrendsGrid');
    if (!marketGrid) return;
    
    let updateInterval = null;
    let isUpdating = false;
    
    function updateMarketData() {
        if (isUpdating) return;
        isUpdating = true;
        
        const updateIcon = document.getElementById('marketUpdateIcon');
        const updateTime = document.getElementById('marketUpdateTime');
        
        if (updateIcon) updateIcon.style.display = 'inline-block';
        
        fetch('/api/market-data/realtime')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const pairs = data.data.forex_pairs || [];
                    const cards = marketGrid.querySelectorAll('.tv-market-card');
                    
                    cards.forEach((card, index) => {
                        const pairName = card.getAttribute('data-pair');
                        const pairData = pairs.find(p => {
                            const symbol = p.symbol || '';
                            return symbol.includes(pairName.replace('/', '')) || 
                                   symbol === pairName.replace('/', '');
                        });
                        
                        if (pairData) {
                            const priceEl = card.querySelector('[data-price]');
                            const changeEl = card.querySelector('[data-change]');
                            const volumeEl = card.querySelector('[data-volume]');
                            
                            if (priceEl) {
                                const oldPrice = parseFloat(priceEl.textContent.replace(/,/g, ''));
                                const newPrice = parseFloat(pairData.price || 0);
                                
                                priceEl.textContent = newPrice.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 4
                                });
                                
                                // Flash animation on price change
                                if (oldPrice !== newPrice) {
                                    priceEl.classList.add('price-updated');
                                    setTimeout(() => priceEl.classList.remove('price-updated'), 1000);
                                }
                            }
                            
                            if (changeEl) {
                                const change = parseFloat(pairData.change_24h || 0);
                                const isPositive = change >= 0;
                                
                                changeEl.textContent = (isPositive ? '+' : '') + change.toFixed(2) + '%';
                                changeEl.className = 'tv-market-change ' + (isPositive ? 'positive' : 'negative');
                                card.className = 'tv-market-card ' + (isPositive ? 'positive' : 'negative');
                            }
                            
                            if (volumeEl && pairData.volume) {
                                const volume = parseFloat(pairData.volume || 0);
                                const volumeM = (volume / 1000000).toFixed(0);
                                volumeEl.textContent = volumeM + 'M';
                            }
                        }
                    });
                    
                    if (updateTime) {
                        const now = new Date();
                        updateTime.innerHTML = '<i class="fas fa-clock"></i> Updated: ' + 
                            now.toLocaleString('en-US', { 
                                month: 'short', 
                                day: 'numeric', 
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) + ' UTC';
                    }
                }
            })
            .catch(error => {
                console.error('Error updating market data:', error);
            })
            .finally(() => {
                isUpdating = false;
                if (updateIcon) updateIcon.style.display = 'none';
            });
    }
    
    // Initial update after 2 seconds
    setTimeout(updateMarketData, 2000);
    
    // Update every 5 seconds
    updateInterval = setInterval(updateMarketData, 5000);
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (updateInterval) clearInterval(updateInterval);
    });
})();
</script>
<style>
.price-updated {
    animation: priceFlash 0.5s ease-in-out;
}

@keyframes priceFlash {
    0%, 100% { background-color: transparent; }
    50% { background-color: rgba(26, 255, 213, 0.2); }
}
</style>
@endpush

