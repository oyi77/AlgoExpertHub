@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Terminal')
@section('page_title', 'Trading Terminal')

@section('content')
<div class="tv-trading-terminal">
    <!-- Terminal Header with Mode Toggle and Symbol Selector -->
    <div class="tv-terminal-header">
        <div class="tv-left-controls">
            <!-- Symbol Selector -->
            <div class="tv-symbol-selector">
                <button class="tv-symbol-selector-btn" id="symbolSelectorBtn">
                    <span id="selectedSymbol">{{ $symbol }}</span>
                    <i class="las la-angle-down"></i>
                </button>
                <div class="tv-symbol-dropdown" id="symbolDropdown">
                    <input type="text" class="tv-symbol-search" id="symbolSearch" placeholder="Search pair...">
                    <div class="tv-symbol-list" id="symbolList">
                        <div class="tv-symbol-item active" data-symbol="BTCUSDT">
                            <span class="tv-symbol-name">BTC/USDT</span>
                            <span class="tv-symbol-desc">Bitcoin</span>
                        </div>
                        <div class="tv-symbol-item" data-symbol="ETHUSDT">
                            <span class="tv-symbol-name">ETH/USDT</span>
                            <span class="tv-symbol-desc">Ethereum</span>
                        </div>
                        <div class="tv-symbol-item" data-symbol="BNBUSDT">
                            <span class="tv-symbol-name">BNB/USDT</span>
                            <span class="tv-symbol-desc">Binance Coin</span>
                        </div>
                        <div class="tv-symbol-item" data-symbol="SOLUSDT">
                            <span class="tv-symbol-name">SOL/USDT</span>
                            <span class="tv-symbol-desc">Solana</span>
                        </div>
                        <div class="tv-symbol-item" data-symbol="XRPUSDT">
                            <span class="tv-symbol-name">XRP/USDT</span>
                            <span class="tv-symbol-desc">Ripple</span>
                        </div>
                        <div class="tv-symbol-item" data-symbol="ADAUSDT">
                            <span class="tv-symbol-name">ADA/USDT</span>
                            <span class="tv-symbol-desc">Cardano</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Price Display -->
            <div class="tv-price-display">
                <span id="currentPrice" class="tv-price">Loading...</span>
                <span id="priceChange" class="tv-change">-</span>
            </div>
        </div>
        
        <!-- Mode Toggle -->
        <div class="tv-mode-toggle">
            <button class="tv-mode-btn" data-mode="demo">
                <i class="las la-graduation-cap"></i>
                <span class="tv-mode-label">Demo Trading</span>
                <span class="tv-mode-badge">Paper Money</span>
            </button>
            <button class="tv-mode-btn active" data-mode="real">
                <i class="las la-chart-line"></i>
                <span class="tv-mode-label">Real Trading</span>
                <span class="tv-mode-badge">Live Funds</span>
            </button>
        </div>
    </div>

    <!-- Exchange Connection Notice (for Real mode) -->
    @if(!$hasExchangeConnections)
    <div class="tv-notice tv-notice-warning" id="exchangeNotice" style="display: none;">
        <i class="las la-exclamation-triangle"></i>
        <div class="tv-notice-content">
            <strong>No Exchange Connected</strong>
            <p>You need to connect an exchange account to trade live. Currently using internal broker.</p>
        </div>
    </div>
    @endif

    <!-- Trading Terminal (Same UI for Both Modes) -->
    <div class="tv-terminal-content">
    <!-- Terminal Grid Layout -->
    <div class="tv-terminal-grid">
        
        <!-- Center Column: Chart -->
        <div class="tv-grid-center">
            <!-- Chart Section -->
            <div class="tv-chart-section">
                <!-- TradingView Widget Container -->
                <div id="tradingview_chart" class="tv-chart-container" style="height: 600px; width: 100%; position: relative;">
                    <!-- Quick Trade Overlay (Desktop Only) -->
                    <div class="tv-quick-trade-overlay d-none d-md-flex">
                        <button class="tv-quick-btn buy" onclick="fillOrder('buy')">
                            <span class="label">Buy Market</span>
                            <span class="price" id="quickBuyPrice">--</span>
                        </button>
                        <button class="tv-quick-btn sell" onclick="fillOrder('sell')">
                            <span class="label">Sell Market</span>
                            <span class="price" id="quickSellPrice">--</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Orderbook & Order Panel (Desktop) -->
        <div class="tv-grid-right d-none d-md-flex">
            <!-- Orderbook -->
            <div class="tv-orderbook-panel">
                <div class="tv-panel-header">
                    <span>Order Book</span>
                    <span class="tv-text-muted" id="orderbookSpread">--</span>
                </div>
                <div class="tv-orderbook-content">
                    <div class="tv-orderbook-header">
                        <span>Price(USDT)</span>
                        <span>Amount(BTC)</span>
                    </div>
                    <div class="tv-orderbook-scroll">
                        <div class="tv-orderbook-asks" id="orderbookAsks">
                            <!-- Populated by JS -->
                        </div>
                        <div class="tv-current-price" id="midPrice">--</div>
                        <div class="tv-orderbook-bids" id="orderbookBids">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Panel -->
            <div class="tv-order-panel">
                <div class="tv-order-header">
                    <button class="tv-order-tab active" data-type="market">Market</button>
                    <button class="tv-order-tab" data-type="limit">Limit</button>
                </div>
                <div class="tv-order-content">
                    <!-- Mode Indicator -->
                    <div class="tv-order-mode-indicator {{ $isDemo ? 'demo' : 'real' }}" id="orderPanelMode">
                        <span class="dot"></span>
                        <span class="text">{{ $isDemo ? 'Demo Mode' : 'Real Trading' }}</span>
                    </div>

                    <form id="orderForm" class="tv-order-form">
                        <div class="tv-form-group">
                            <label>Avail:</label>
                            <span class="tv-avail-balance">{{ number_format(Auth::user()->balance, 2) }} USDT</span>
                        </div>
                        
                        <div class="tv-form-group d-none" id="limitPriceGroup">
                            <label>Price (USDT)</label>
                            <div class="tv-input-group">
                                <input type="number" id="limitPrice" class="tv-input" step="0.01" placeholder="0.00">
                            </div>
                        </div>

                        <div class="tv-form-group">
                            <label>Amount</label>
                            <div class="tv-input-group">
                                <input type="number" id="orderAmount" class="tv-input" step="0.0001" placeholder="0.00">
                                <span class="tv-input-suffix">BTC</span>
                            </div>
                        </div>
                        
                        <div class="tv-form-row">
                            <div class="tv-form-group">
                                <label>TP</label>
                                <input type="number" id="takeProfit" class="tv-input" placeholder="Take Profit">
                            </div>
                            <div class="tv-form-group">
                                <label>SL</label>
                                <input type="number" id="stopLoss" class="tv-input" placeholder="Stop Loss">
                            </div>
                        </div>

                        <div class="tv-order-actions">
                            <button type="submit" class="tv-btn tv-btn-buy" data-direction="buy">Buy / Long</button>
                            <button type="submit" class="tv-btn tv-btn-sell" data-direction="sell">Sell / Short</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Positions & Tabs -->
    <div class="tv-bottom-section">
        <div class="tv-tabs-header">
            <button class="tv-tab active" data-tab="positions">
                <i class="las la-list"></i> Open Positions
                <span class="tv-tab-count" id="positionsCount">0</span>
            </button>
            <button class="tv-tab" data-tab="orders">
                <i class="las la-history"></i> Order History
            </button>
            <button class="tv-tab" data-tab="trades">
                <i class="las la-exchange-alt"></i> Trade History
            </button>
            <div style="flex: 1;"></div>
            @if($isDemo)
            <div class="tv-demo-badge">Demo Trading Active</div>
            @endif
        </div>
        
        <div class="tv-tab-content active" id="positionsTab">
            <div class="tv-table-responsive">
                <table class="tv-table">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Side</th>
                            <th>Size</th>
                            <th>Entry Price</th>
                            <th>Mark Price</th>
                            <th>PNL (USDT)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="positionsTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tv-tab-content" id="ordersTab">
            <div class="tv-empty-state">
                <i class="las la-clipboard-list"></i>
                <p>No active orders</p>
            </div>
        </div>

        <div class="tv-tab-content" id="tradesTab">
            <div class="tv-table-responsive">
                <table class="tv-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Symbol</th>
                            <th>Side</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Placeholder -->
                        <tr class="tv-empty-state"><td colspan="6">No trade history</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Bar -->
    <div class="tv-mobile-bottom-bar d-flex d-md-none">
        <button class="tv-mobile-btn buy" onclick="openMobileTrade('buy')">Buy / Long</button>
        <button class="tv-mobile-btn sell" onclick="openMobileTrade('sell')">Sell / Short</button>
    </div>

    <!-- Mobile Trade Modal -->
    <div class="tv-mobile-modal" id="mobileTradeModal">
        <div class="tv-mobile-modal-content">
            <div class="tv-modal-header">
                <span id="mobileModalTitle">Buy BTC</span>
                <button class="tv-close-btn" onclick="closeMobileTrade()"><i class="las la-times"></i></button>
            </div>
            <div class="tv-modal-body">
                <!-- Cloned Order Form will be injected here via JS or just reused form logic -->
                <div id="mobileOrderFormContainer"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/trading-terminal.css') }}">
@endpush

@push('scripts')
<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script src="{{ asset('asset/frontend/trading-v1/js/trading-terminal.js') }}?v={{ time() }}"></script>
@endpush
