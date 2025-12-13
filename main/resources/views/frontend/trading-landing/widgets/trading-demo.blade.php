@php
    $marketDataService = app(\App\Services\Trading\MarketDataService::class);
    $cryptoData = $marketDataService->getCryptoData(5);
    $selectedCrypto = $cryptoData[0] ?? null;
@endphp

<section class="trading-demo-section">
    <div class="trading-demo-background">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">{{ Config::trans(isset($content) ? ($content->title ?? 'Try Demo Trading') : 'Try Demo Trading') }}</h2>
                <p class="section-description">{{ Config::trans(isset($content) ? ($content->description ?? 'Experience our trading platform with virtual money. No risk, no commitment.') : 'Experience our trading platform with virtual money. No risk, no commitment.') }}</p>
            </div>

            <div class="trading-demo-container">
                <div class="demo-trading-card">
                    <div class="demo-header">
                        <div class="asset-selector">
                            <label for="demo-asset">Select Asset:</label>
                            <select id="demo-asset" class="form-select">
                                @foreach($cryptoData as $crypto)
                                    <option value="{{ $crypto['symbol'] }}" {{ $loop->first ? 'selected' : '' }}>
                                        {{ $crypto['symbol'] }} - ${{ number_format($crypto['price'], 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="demo-balance">
                            <span class="label">Demo Balance:</span>
                            <span class="value" id="demo-balance">$10,000.00</span>
                        </div>
                    </div>

                    <div class="price-display">
                        <div class="current-price">
                            <span class="label">Current Price</span>
                            <span class="value" id="current-price">
                                @if($selectedCrypto)
                                    ${{ number_format($selectedCrypto['price'], 2) }}
                                @else
                                    $0.00
                                @endif
                            </span>
                            <span class="change" id="price-change">
                                @if($selectedCrypto)
                                    <span class="{{ $selectedCrypto['change_24h'] >= 0 ? 'positive' : 'negative' }}">
                                        {{ $selectedCrypto['change_24h'] >= 0 ? '+' : '' }}{{ number_format($selectedCrypto['change_24h'], 2) }}%
                                    </span>
                                @endif
                            </span>
                        </div>
                        <div class="mini-chart">
                            <canvas id="price-chart" width="200" height="60" data-market-data="{{ json_encode($cryptoData ?? []) }}"></canvas>
                        </div>
                    </div>

                    <div class="trading-controls">
                        <div class="order-type">
                            <button class="btn btn-buy active" id="buy-btn">
                                <i class="fas fa-arrow-up"></i> Buy
                            </button>
                            <button class="btn btn-sell" id="sell-btn">
                                <i class="fas fa-arrow-down"></i> Sell
                            </button>
                        </div>

                        <div class="order-amount">
                            <label for="order-amount">Amount ($):</label>
                            <input type="number" id="order-amount" class="form-control" value="100" min="10" max="1000" step="10">
                        </div>

                        <div class="leverage-selector">
                            <label>Leverage:</label>
                            <div class="leverage-buttons">
                                <button class="leverage-btn active" data-leverage="1">1x</button>
                                <button class="leverage-btn" data-leverage="5">5x</button>
                                <button class="leverage-btn" data-leverage="10">10x</button>
                                <button class="leverage-btn" data-leverage="25">25x</button>
                            </div>
                        </div>

                        <button class="btn btn-place-order" id="place-order-btn">
                            <i class="fas fa-play"></i> Place Demo Order
                        </button>
                    </div>

                    <div class="demo-results" id="demo-results" style="display: none;">
                        <div class="result-header">
                            <h4>Trade Result</h4>
                            <button class="btn-close" id="close-results">&times;</button>
                        </div>
                        <div class="result-content" id="result-content">
                            <!-- Results will be populated by JavaScript -->
                        </div>
                    </div>

                    <div class="open-positions" id="open-positions">
                        <h4>Open Positions</h4>
                        <div class="positions-list" id="positions-list">
                            <p class="no-positions">No open positions</p>
                        </div>
                    </div>
                </div>

                <div class="demo-info-panel">
                    <div class="info-card">
                        <h4><i class="fas fa-info-circle"></i> How Demo Trading Works</h4>
                        <ul>
                            <li>Select a cryptocurrency to trade</li>
                            <li>Choose buy or sell and set your amount</li>
                            <li>Use leverage to amplify your position</li>
                            <li>Monitor your virtual P&L in real-time</li>
                            <li>No real money at risk</li>
                        </ul>
                    </div>

                    <div class="cta-card">
                        <h4>Ready for Real Trading?</h4>
                        <p>Join thousands of traders using our platform</p>
                        <a href="{{ route('user.register') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Free Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize with sample data - in production this would come from API
    const marketDataService = [
        {"symbol":"BTC","name":"Bitcoin","price":45000,"change_24h":2.5},
        {"symbol":"ETH","name":"Ethereum","price":2800,"change_24h":-1.2},
        {"symbol":"USDT","name":"Tether","price":1.00,"change_24h":0.1},
        {"symbol":"BNB","name":"Binance Coin","price":320,"change_24h":1.8},
        {"symbol":"DOGE","name":"Dogecoin","price":0.085,"change_24h":3.2}
    ];
    let currentAsset = 'BTC';
    let demoBalance = 10000;
    let currentPrice = 0;
    let positions = [];
    let selectedLeverage = 1;
    let priceHistory = [];
    let chart = null;

    // Initialize
    initializeDemo();

    function initializeDemo() {
        updateCurrentAsset();
        setupEventListeners();
        initializeChart();
        updatePriceDisplay();
    }

    function setupEventListeners() {
        // Asset selector
        document.getElementById('demo-asset').addEventListener('change', function(e) {
            currentAsset = e.target.value;
            updateCurrentAsset();
            updatePriceDisplay();
            updateChart();
        });

        // Buy/Sell buttons
        document.getElementById('buy-btn').addEventListener('click', function() {
            setOrderType('buy');
        });

        document.getElementById('sell-btn').addEventListener('click', function() {
            setOrderType('sell');
        });

        // Leverage buttons
        document.querySelectorAll('.leverage-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                selectedLeverage = parseInt(this.dataset.leverage);
                document.querySelectorAll('.leverage-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Place order button
        document.getElementById('place-order-btn').addEventListener('click', placeDemoOrder);

        // Close results
        document.getElementById('close-results').addEventListener('click', function() {
            document.getElementById('demo-results').style.display = 'none';
        });
    }

    function updateCurrentAsset() {
        const asset = marketDataService.find(crypto => crypto.symbol === currentAsset);
        if (asset) {
            currentPrice = asset.price;
        }
    }

    function setOrderType(type) {
        document.getElementById('buy-btn').classList.toggle('active', type === 'buy');
        document.getElementById('sell-btn').classList.toggle('active', type === 'sell');
    }

    function updatePriceDisplay() {
        const asset = marketDataService.find(crypto => crypto.symbol === currentAsset);
        if (asset) {
            document.getElementById('current-price').textContent = '$' + asset.price.toFixed(2);
            const changeElement = document.getElementById('price-change');
            changeElement.innerHTML = `<span class="${asset.change_24h >= 0 ? 'positive' : 'negative'}">${asset.change_24h >= 0 ? '+' : ''}${asset.change_24h.toFixed(2)}%</span>`;
        }
    }

    function initializeChart() {
        const ctx = document.getElementById('price-chart').getContext('2d');

        // Generate sample price history
        priceHistory = generatePriceHistory(currentPrice, 20);

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: 20}, (_, i) => i + 1),
                datasets: [{
                    data: priceHistory,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 0,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false
                    }
                },
                elements: {
                    point: {
                        radius: 0
                    }
                }
            }
        });
    }

    function updateChart() {
        if (chart) {
            priceHistory = generatePriceHistory(currentPrice, 20);
            chart.data.datasets[0].data = priceHistory;
            chart.update();
        }
    }

    function generatePriceHistory(basePrice, points) {
        const history = [basePrice];
        for (let i = 1; i < points; i++) {
            const change = (Math.random() - 0.5) * 0.02; // ±1% change
            const newPrice = history[i-1] * (1 + change);
            history.push(newPrice);
        }
        return history;
    }

    function placeDemoOrder() {
        const amount = parseFloat(document.getElementById('order-amount').value);
        const orderType = document.getElementById('buy-btn').classList.contains('active') ? 'buy' : 'sell';

        if (amount < 10 || amount > 1000) {
            showResult('Invalid amount. Must be between $10 and $1000.', 'error');
            return;
        }

        // Calculate position size with leverage
        const positionSize = amount * selectedLeverage;

        // Simulate price movement
        const entryPrice = currentPrice;
        const exitPrice = entryPrice * (1 + (Math.random() - 0.5) * 0.04); // ±2% movement
        const pnl = orderType === 'buy' ?
            (exitPrice - entryPrice) * (positionSize / entryPrice) :
            (entryPrice - exitPrice) * (positionSize / entryPrice);

        // Update demo balance
        demoBalance += pnl;
        updateBalanceDisplay();

        // Add to positions
        const position = {
            id: Date.now(),
            asset: currentAsset,
            type: orderType,
            amount: amount,
            leverage: selectedLeverage,
            entryPrice: entryPrice,
            exitPrice: exitPrice,
            pnl: pnl,
            timestamp: new Date()
        };

        positions.push(position);
        updatePositionsDisplay();

        // Show result
        showResult(generateResultMessage(position), pnl >= 0 ? 'success' : 'error');
    }

    function generateResultMessage(position) {
        return `
            <div class="trade-summary">
                <p><strong>${position.type.toUpperCase()} ${position.asset}</strong></p>
                <p>Amount: $${position.amount.toFixed(2)} (Leverage: ${position.leverage}x)</p>
                <p>Entry: $${position.entryPrice.toFixed(2)} | Exit: $${position.exitPrice.toFixed(2)}</p>
                <p class="${position.pnl >= 0 ? 'profit' : 'loss'}">
                    P&L: ${position.pnl >= 0 ? '+' : ''}$${position.pnl.toFixed(2)}
                </p>
            </div>
        `;
    }

    function showResult(message, type) {
        const resultsDiv = document.getElementById('demo-results');
        const contentDiv = document.getElementById('result-content');

        contentDiv.innerHTML = message;
        resultsDiv.className = `demo-results ${type}`;
        resultsDiv.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            resultsDiv.style.display = 'none';
        }, 5000);
    }

    function updateBalanceDisplay() {
        document.getElementById('demo-balance').textContent = '$' + demoBalance.toFixed(2);
    }

    function updatePositionsDisplay() {
        const positionsList = document.getElementById('positions-list');

        if (positions.length === 0) {
            positionsList.innerHTML = '<p class="no-positions">No open positions</p>';
            return;
        }

        const positionsHtml = positions.slice(-3).map(pos => `
            <div class="position-item ${pos.pnl >= 0 ? 'profit' : 'loss'}">
                <div class="position-info">
                    <span class="asset">${pos.asset}</span>
                    <span class="type">${pos.type.toUpperCase()}</span>
                </div>
                <div class="position-pnl">
                    ${pos.pnl >= 0 ? '+' : ''}$${pos.pnl.toFixed(2)}
                </div>
            </div>
        `).join('');

        positionsList.innerHTML = positionsHtml;
    }

    // Simulate price updates every 30 seconds
    setInterval(() => {
        // Small random price movements
        const change = (Math.random() - 0.5) * 0.005; // ±0.5%
        currentPrice *= (1 + change);

        // Update asset data
        const assetIndex = marketDataService.findIndex(crypto => crypto.symbol === currentAsset);
        if (assetIndex !== -1) {
            marketDataService[assetIndex].price = currentPrice;
        }

        updatePriceDisplay();
        updateChart();
    }, 30000);

    // Handle interactive CTA buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn-interactive') || e.target.closest('.btn-interactive')) {
            const btn = e.target.closest('.btn-interactive');
            const action = btn.dataset.action;

            if (action === 'register') {
                // Track registration click
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'engagement',
                        event_label: 'register_cta'
                    });
                }

                // Smooth scroll to top for registration
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } else if (action === 'demo') {
                // Track demo click
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'engagement',
                        event_label: 'demo_cta'
                    });
                }

                // Smooth scroll to trading demo section
                const demoSection = document.querySelector('.trading-demo-section');
                if (demoSection) {
                    demoSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }
    });

    // Add hover effects for interactive elements
    document.addEventListener('mouseover', function(e) {
        if (e.target.matches('.btn-interactive') || e.target.closest('.btn-interactive')) {
            const btn = e.target.closest('.btn-interactive');
            btn.style.transform = 'translateY(-2px)';
            btn.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        }
    });

    document.addEventListener('mouseout', function(e) {
        if (e.target.matches('.btn-interactive') || e.target.closest('.btn-interactive')) {
            const btn = e.target.closest('.btn-interactive');
            btn.style.transform = '';
            btn.style.boxShadow = '';
        }
    });
});
</script>