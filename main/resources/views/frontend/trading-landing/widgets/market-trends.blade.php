@php
    $marketDataService = app(\App\Services\Trading\MarketDataService::class);
    $landingData = $marketDataService->getLandingPageData();
    $cryptoData = $landingData['cryptocurrencies'] ?? [];
    $forexData = $landingData['forex_pairs'] ?? [];
    $allData = array_merge($cryptoData, $forexData);
@endphp

<section class="market-trends-section">
    <div class="market-trends-background">
        <div class="pattern-overlay"></div>
    </div>

    <div class="container">
        <div class="section-header">
            <h2 class="section-title">{{ Config::trans(isset($content) ? ($content->title ?? 'Real-Time Market Trends') : 'Real-Time Market Trends') }}</h2>
            <p class="section-description">{{ Config::trans(isset($content) ? ($content->description ?? 'Stay Ahead with Up-to-the-Second Market Data on Major Currency Pairs and Cryptocurrencies') : 'Stay Ahead with Up-to-the-Second Market Data on Major Currency Pairs and Cryptocurrencies') }}</p>
        </div>
        
        <div class="market-cards-grid">
            @foreach(array_slice($allData, 0, 10) as $market)
                <div class="market-card {{ $market['change_24h'] >= 0 ? 'positive' : 'negative' }}">
                    <div class="card-header">
                        <div class="currency-flags">
                            @if(isset($market['symbol']))
                                @php
                                    $flagClass = 'flag-' . strtolower(substr($market['symbol'], 0, 2));
                                    if (strpos($market['symbol'], '/') !== false) {
                                        // Forex pair
                                        $parts = explode('/', $market['symbol']);
                                        $flagClass = 'flag-' . strtolower($parts[0]) . ' flag-' . strtolower($parts[1]);
                                    } elseif (strlen($market['symbol']) > 3) {
                                        // Crypto
                                        $flagClass = 'flag-crypto';
                                    }
                                @endphp
                                <span class="flag {{ $flagClass }}"></span>
                                @if(strpos($market['symbol'], '/') !== false)
                                    @php $parts = explode('/', $market['symbol']); @endphp
                                    <span class="flag flag-{{ strtolower($parts[1]) }}"></span>
                                @endif
                            @endif
                        </div>
                        <div class="currency-info">
                            <h3 class="currency-pair">{{ $market['symbol'] }}</h3>
                            <p class="currency-name">{{ $market['name'] }}</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="price-info">
                            <span class="label">Price</span>
                            <span class="value">
                                @if(isset($market['symbol']) && strpos($market['symbol'], '/') !== false)
                                    {{ number_format($market['price'], 4) }}
                                @else
                                    ${{ number_format($market['price'], 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="change-info {{ $market['change_24h'] >= 0 ? 'positive' : 'negative' }}">
                            <span class="label">Change</span>
                            <span class="value">{{ $market['change_24h'] >= 0 ? '+' : '' }}{{ number_format($market['change_24h'], 3) }}%</span>
                        </div>
                        <div class="chart-icon">
                            <i class="las la-chart-line"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="market-update-info">
            <small class="text-muted">
                Last updated: {{ isset($landingData['last_updated']) ? \Carbon\Carbon::parse($landingData['last_updated'])->format('M j, H:i:s T') : 'N/A' }}
                @if(isset($landingData['source']))
                    | Data source: {{ $landingData['source'] === 'api' ? 'Live API' : 'Simulated' }}
                @endif
            </small>
        </div>
    </div>
</section>

