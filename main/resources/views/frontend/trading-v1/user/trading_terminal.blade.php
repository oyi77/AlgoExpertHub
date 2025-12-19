@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Terminal')
@section('page_title', 'Trading Terminal')

@push('styles')
<meta name="user-id" content="{{ auth()->id() }}">
@endpush

@section('content')
<div class="tv-trading-terminal">
    <!-- Terminal Header with Mode Toggle and Symbol Selector -->
    <div class="tv-terminal-header">
        <div class="tv-left-controls">
            <!-- Symbol Selector -->
            <div class="tv-symbol-selector">
                <button class="tv-symbol-selector-btn" id="symbolSelectorBtn">
                    <span id="selectedSymbol">{{ str_replace('USDT', '/USDT', $symbol) }}</span>
                    <i class="las la-angle-down"></i>
                </button>
                <div class="tv-symbol-dropdown" id="symbolDropdown">
                    <input type="text" class="tv-symbol-search" id="symbolSearch" placeholder="Search pair...">
                    <div class="tv-symbol-categories" id="symbolCategories">
                        <button class="tv-category-btn active" data-category="all">All</button>
                        <button class="tv-category-btn" data-category="crypto">Crypto</button>
                        <button class="tv-category-btn" data-category="forex">Forex</button>
                        <button class="tv-category-btn" data-category="indices">Indices</button>
                        <button class="tv-category-btn" data-category="commodities">Commodities</button>
                        <button class="tv-category-btn" data-category="stocks">Stocks</button>
                        <button class="tv-category-btn" data-category="favourites" title="Favorites">
                            <i class="las la-star"></i>
                            <span class="tv-category-label">Favorites</span>
                        </button>
                    </div>
                    <div class="tv-symbol-list" id="symbolList">
                        <div class="tv-symbol-loading">
                            <i class="las la-spinner la-spin"></i> Loading pairs...
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
            <button class="tv-mode-btn {{ $isDemo ? 'active' : '' }}" data-mode="demo">
                <i class="las la-graduation-cap"></i>
                <span class="tv-mode-label">Demo Trading</span>
                <span class="tv-mode-badge">Paper Money</span>
            </button>
            <button class="tv-mode-btn {{ $isDemo ? '' : 'active' }}" data-mode="real">
                <i class="las la-chart-line"></i>
                <span class="tv-mode-label">Real Trading</span>
                <span class="tv-mode-badge">Live Funds</span>
            </button>
        </div>
    </div>

    <!-- Exchange Connection Selector (for Real mode) -->
    <div class="tv-connection-selector" id="connectionSelector" style="display: none;">
        @if($hasExchangeConnections && $exchangeConnections->count() > 0)
            <div class="tv-connection-selector-content">
                <label for="exchangeConnectionSelect">
                    <i class="las la-exchange-alt"></i> {{ __('Select Exchange Connection') }}:
                </label>
                <select id="exchangeConnectionSelect" class="tv-connection-select">
                    <option value="">{{ __('Select a connection...') }}</option>
                    @foreach($exchangeConnections as $conn)
                        <option value="{{ $conn->id }}" 
                                data-type="{{ $conn->connection_type ?? $conn->type ?? 'crypto' }}"
                                data-provider="{{ $conn->provider ?? $conn->exchange_name ?? 'N/A' }}">
                            {{ $conn->name }} ({{ strtoupper($conn->provider ?? $conn->exchange_name ?? 'N/A') }})
                        </option>
                    @endforeach
                </select>
                <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}" 
                   class="tv-connection-link" 
                   target="_blank"
                   title="{{ __('Manage Connections') }}">
                    <i class="las la-cog"></i>
                </a>
            </div>
        @else
            <div class="tv-notice tv-notice-warning">
                <i class="las la-exclamation-triangle"></i>
                <div class="tv-notice-content">
                    <strong>{{ __('No Exchange Connected') }}</strong>
                    <p>{{ __('You need to connect an exchange account to trade live.') }}</p>
                    <a href="{{ route('user.exchange-connections.create') ?? route('user.trading.operations.index', ['tab' => 'connections']) }}" 
                       class="btn btn-sm btn-primary mt-2">
                        <i class="las la-plus"></i> {{ __('Create Connection') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- No Connection Modal -->
    <div class="modal fade" id="noConnectionModal" tabindex="-1" role="dialog" aria-labelledby="noConnectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noConnectionModalLabel">
                        <i class="las la-exchange-alt"></i> {{ __('Exchange Connection Required') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="las la-exclamation-triangle la-3x text-warning mb-3"></i>
                        <h5>{{ __('No Exchange Connection Available') }}</h5>
                        <p class="text-muted">
                            {{ __('To trade with real funds, you need to connect an exchange account first.') }}
                        </p>
                    </div>
                    <div class="alert alert-info">
                        <i class="las la-info-circle"></i>
                        <strong>{{ __('What you can do:') }}</strong>
                        <ul class="mb-0 mt-2">
                            <li>{{ __('Connect your Binance, Coinbase, or other crypto exchange') }}</li>
                            <li>{{ __('Connect your MetaTrader 4/5 broker account') }}</li>
                            <li>{{ __('Use Demo Mode for practice trading') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <a href="{{ route('user.exchange-connections.create') ?? route('user.trading.operations.index', ['tab' => 'connections']) }}" 
                       class="btn btn-primary" id="addConnectionBtn">
                        <i class="las la-plus"></i> {{ __('Add Exchange Connection') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Terminal (Resizable Layout) -->
    <div class="tv-terminal-content">
        <!-- Floating Settings Button -->
        <button class="tv-settings-btn" id="tvSettingsBtn" title="Layout Settings" type="button">
            <i class="las la-cog" aria-hidden="true"></i>
        </button>
        
        <!-- Settings Popup -->
        <div class="tv-settings-popup" id="tvSettingsPopup">
            <div class="tv-settings-header">
                <h3>Layout Settings</h3>
                <button class="tv-settings-close" id="tvSettingsClose">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="tv-settings-body">
                <div class="tv-settings-section">
                    <label>Layout System</label>
                    <div class="tv-layout-toggle">
                        <button class="tv-layout-btn active" data-layout="goldenlayout" title="Golden Layout">
                            <i class="las la-th"></i> Golden Layout
                        </button>
                        <button class="tv-layout-btn" data-layout="interactjs" title="Interact.js">
                            <i class="las la-arrows-alt"></i> Interact.js
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Golden Layout Container -->
        <div id="layoutContainer" class="tv-layout-container tv-layout-goldenlayout" style="height: 100%; width: 100%;"></div>
        
        <!-- Interact.js Layout Container -->
        <div id="interactLayoutContainer" class="tv-layout-container tv-layout-interactjs" style="display: none; height: 100%; width: 100%;"></div>
    </div>

    <!-- Component Templates (Hidden, used by Golden Layout) -->
    
    <!-- Chart Component Template -->
    <template id="chartComponentTemplate">
        <div class="tv-chart-section">
            <div id="tradingview_chart" class="tv-chart-container" style="height: 100%; width: 100%; position: relative;">
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
    </template>

    <!-- Orderbook & Depth Combined Component Template -->
    <!-- Orderbook & Depth Combined Component Template -->
    <template id="orderbookDepthComponentTemplate">
        <div class="tv-orderbook-depth-panel">
            <div class="tv-panel-header">
                <div class="tv-panel-tabs">
                    <button class="tv-panel-tab active" data-tab="orderbook">
                        <span>Order Book</span>
                        <span class="tv-text-muted" id="orderbookSpread">Spread: 0.00%</span>
                    </button>
                    <button class="tv-panel-tab" data-tab="depth">
                        <span>Depth</span>
                    </button>
                </div>
            </div>
            <div class="tv-panel-content">
                <!-- Order Book Tab -->
                <div class="tv-tab-content active" id="orderbookTab">
                    <div class="tv-orderbook-content">
                        <div class="tv-orderbook-header">
                            <span>Price(USDT)</span>
                            <span>Amount(BTC)</span>
                        </div>
                        <div class="tv-orderbook-scroll">
                            <div class="tv-orderbook-asks" id="orderbookAsks">
                                <div style="padding: 1rem; text-align: center; color: #848e9c;">Loading orderbook...</div>
                            </div>
                            <div class="tv-current-price" id="midPrice">--</div>
                            <div class="tv-orderbook-bids" id="orderbookBids"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Depth Tab -->
                <div class="tv-tab-content" id="depthTab">
                    <div id="depthChart"></div>
                </div>
            </div>
        </div>
    </template>

    <!-- Order Panel Component Template -->
    <template id="orderPanelComponentTemplate">
        <div class="tv-order-panel" style="height: 100%;">
            <div class="tv-order-header">
                <button class="tv-order-tab active" data-type="market">Market</button>
                <button class="tv-order-tab" data-type="limit">Limit</button>
            </div>
            <div class="tv-order-content">
                <div class="tv-order-mode-indicator {{ $isDemo ? 'demo' : 'real' }}" id="orderPanelMode">
                    <span class="dot"></span>
                    <span class="text">{{ $isDemo ? 'Demo Mode' : 'Real Trading' }}</span>
                </div>

                <form id="orderForm" class="tv-order-form">
                    <div class="tv-form-group">
                        <label>Avail:</label>
                        <span class="tv-avail-balance" id="availableBalance" data-real-balance="{{ number_format($realBalance ?? 0, 2) }}" data-demo-balance="{{ number_format($demoBalance ?? 10000, 2) }}">
                            {{ number_format($isDemo ? ($demoBalance ?? 10000) : ($realBalance ?? 0), 2) }} USDT
                        </span>
                    </div>
                    
                    <div class="tv-form-group d-none" id="limitPriceGroup">
                        <label>Price (USDT)</label>
                        <div class="tv-input-group">
                            <input type="number" id="limitPrice" class="tv-input" step="0.01" placeholder="0.00">
                        </div>
                        <div class="tv-bbo-buttons">
                            <button type="button" class="tv-bbo-btn" data-side="bid" title="Best Bid">
                                <i class="las la-arrow-down"></i> Bid
                            </button>
                            <button type="button" class="tv-bbo-btn" data-side="ask" title="Best Ask">
                                <i class="las la-arrow-up"></i> Ask
                            </button>
                        </div>
                    </div>

                    <div class="tv-form-group">
                        <label>Amount</label>
                        <div class="tv-input-group">
                            <input type="number" id="orderAmount" class="tv-input" step="0.0001" placeholder="0.00">
                            <span class="tv-input-suffix">BTC</span>
                        </div>
                        <div class="tv-amount-buttons">
                            <button type="button" class="tv-amount-btn" data-pct="25">25%</button>
                            <button type="button" class="tv-amount-btn" data-pct="50">50%</button>
                            <button type="button" class="tv-amount-btn" data-pct="75">75%</button>
                            <button type="button" class="tv-amount-btn" data-pct="100">100%</button>
                        </div>
                    </div>

                    <div class="tv-form-group">
                        <label>Total</label>
                        <div class="tv-input-group">
                            <input type="number" id="orderTotal" class="tv-input" readonly placeholder="0.00">
                            <span class="tv-input-suffix">USDT</span>
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
    </template>

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
<!-- Golden Layout -->
<link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/js/golden-layout/dist/css/goldenlayout-base.css') }}">
<link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/js/golden-layout/dist/css/themes/goldenlayout-dark-theme.css') }}">
<link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/golden-layout-custom.css') }}">
<link rel="stylesheet" href="{{ asset('asset/frontend/trading-v1/css/trading-terminal.css') }}">
@endpush

@push('scripts')
<!-- Interact.js for alternative layout -->
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<!-- Layout Manager -->
<script src="{{ asset('asset/frontend/trading-v1/js/layout-manager.js') }}"></script>

<!-- String.trimStart polyfill for older browsers -->
<script>
    if (!String.prototype.trimStart) {
        String.prototype.trimStart = function() {
            return this.replace(/^\s+/, '');
        };
    }
    if (!String.prototype.trimLeft) {
        String.prototype.trimLeft = String.prototype.trimStart;
    }
</script>
<!-- Golden Layout -->
<script src="{{ asset('asset/frontend/trading-v1/js/goldenlayout.js') }}"></script>
<!-- ECharts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
<script src="https://s3.tradingview.com/tv.js"></script>
<script src="{{ asset('asset/frontend/trading-v1/js/trading-terminal.js') }}"></script>
<script src="{{ asset('asset/frontend/trading-v1/js/golden-layout-init.js') }}"></script>
@endpush
