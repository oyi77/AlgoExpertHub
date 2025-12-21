/**
 * Trading Terminal JavaScript
 * Handles chart rendering, orderbook updates, order placement, and position management
 */

(function () {
    'use strict';

    // State
    let currentMode = (() => {
        // Check which mode button is active on page load
        const activeModeBtn = document.querySelector('.tv-mode-btn.active');
        return activeModeBtn ? activeModeBtn.dataset.mode : 'real';
    })();
    let currentSymbol = (() => {
        // First try localStorage (saved preference)
        const savedSymbol = localStorage.getItem('tradingTerminalSymbol');
        if (savedSymbol) {
            return savedSymbol;
        }
        // Then try DOM element
        const el = document.getElementById('selectedSymbol');
        if (el) {
            const symbol = el.textContent.replace('/', '').replace('USDT', 'USDT');
            if (symbol) {
                return symbol;
            }
        }
        // Default fallback
        return 'BTCUSDT';
    })();
    let currentInterval = '5m';
    let chart = null;
    let candlestickSeries = null;
    let volumeSeries = null;
    let updateInterval = null;
    let ws = null; // Binance WebSocket connection for orderbook
    let wsReconnectAttempts = 0;
    let wsMaxReconnectAttempts = 5;
    let depthChart = null;
    let useWebSocketForPositions = false;
    let useWebSocketForMarketData = false;
    let positionsChannel = null;
    let marketDataChannel = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        initializeSidebarToggle();
        initChart();
        initializeOrderForm();
        initDepthChart();
        initializeTabSwitching();
        initializeTimeframeSelector();
        initializeSymbolSelector();
        initializeModeToggle();

        // Initialize balance display
        updateBalanceDisplay(currentMode);

        // Initialize order form symbol suffix
        const orderAmountSymbol = document.getElementById('orderAmountSymbol');
        if (orderAmountSymbol && currentSymbol) {
            const baseSymbol = currentSymbol.replace('USDT', '').replace('USD', '').replace('EUR', '').replace('GBP', '');
            orderAmountSymbol.textContent = baseSymbol || 'BTC';
        }

        // Sync saved symbol to URL if different
        const url = new URL(window.location);
        const urlSymbol = url.searchParams.get('symbol');
        if (!urlSymbol || urlSymbol !== currentSymbol) {
            url.searchParams.set('symbol', currentSymbol);
            window.history.replaceState({}, '', url);
        }

        // Load orderbook immediately via REST (instant load)
        loadOrderbookREST();

        // Connect to WebSocket for real-time updates
        // Small delay to ensure DOM is ready
        setTimeout(() => {
            connectWebSocket();
        }, 500);

        // Initialize WebSocket listeners for positions and market data
        initLaravelEchoListeners();

        // Show/hide connection selector based on initial mode
        const connectionSelector = document.getElementById('connectionSelector');
        if (connectionSelector) {
            connectionSelector.style.display = currentMode === 'real' ? 'block' : 'none';
        }

        // Auto-start real trading mode
        if (currentMode === 'real') {
            initializeRealTrading();
        }
    });

    // Expose functions globally for Golden Layout re-initialization
    window.initChart = initChart;
    window.connectWebSocket = connectWebSocket;
    // Expose functions globally for onclick handlers and external access
    window.loadOrderbookREST = loadOrderbookREST;
    window.selectSymbol = selectSymbol;
    window.loadOrderbook = loadOrderbook;
    window.updateOrderbook = updateOrderbook;
    window.updateDepthChart = updateDepthChart;
    window.initDepthChart = initDepthChart;
    window.initializeOrderForm = initializeOrderForm;

    // Expose currentSymbol as getter
    Object.defineProperty(window, 'currentSymbol', {
        get: function () { return currentSymbol; },
        configurable: true
    });

    /**
     * Sidebar Toggle
     */
    function initializeSidebarToggle() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const panel = document.querySelector('.tv-panel');

        if (!sidebar) return;

        // Auto-hide sidebar on trading terminal page
        const isTradingTerminal = window.location.pathname.includes('/terminal');
        if (isTradingTerminal) {
            sidebar.classList.add('collapsed');
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            // Restore saved state for other pages
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }

        // Handle existing mobile toggle button
        // Mobile sidebar toggle - use 'show' class to match CSS and main.js
        const mobileToggle = document.getElementById('sidebarToggle');
        if (mobileToggle) {
            // Remove any existing listeners and use show class
            mobileToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            });
        }

        // Handle sidebar close button - use 'show' class
        const sidebarClose = document.getElementById('sidebarClose');
        if (sidebarClose) {
            sidebarClose.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            });
        }

        // Handle overlay click - use 'show' class
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    }

    /**
     * Symbol Selector - Dynamic loading with categories
     */
    let allTradingPairs = [];
    let currentCategory = 'all';
    let favorites = JSON.parse(localStorage.getItem('tradingFavorites') || '[]');

    function initializeSymbolSelector() {
        const selectorBtn = document.getElementById('symbolSelectorBtn');
        const dropdown = document.getElementById('symbolDropdown');
        const searchInput = document.getElementById('symbolSearch');
        const symbolList = document.getElementById('symbolList');
        const categoryBtns = document.querySelectorAll('.tv-category-btn');

        if (!selectorBtn || !dropdown || !symbolList) return;

        // Load trading pairs on first open
        let pairsLoaded = false;
        selectorBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
            if (dropdown.classList.contains('active') && !pairsLoaded) {
                loadTradingPairs();
                pairsLoaded = true;
            }
            if (dropdown.classList.contains('active')) {
                searchInput?.focus();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && !selectorBtn.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Category switching
        categoryBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                categoryBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentCategory = this.dataset.category;
                renderSymbolList();
                if (searchInput) searchInput.value = '';
            });
        });

        // Search functionality
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                renderSymbolList();
            });
        }

        // Load pairs on page load
        loadTradingPairs();
    }

    function loadTradingPairs() {
        const symbolList = document.getElementById('symbolList');
        if (!symbolList) return;

        symbolList.innerHTML = '<div class="tv-symbol-loading"><i class="las la-spinner la-spin"></i> Loading pairs...</div>';

        fetch('/api/trading-terminal/trading-pairs?category=' + currentCategory)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    allTradingPairs = data.data;
                    renderSymbolList();
                } else {
                    symbolList.innerHTML = '<div class="tv-symbol-error">Failed to load trading pairs</div>';
                }
            })
            .catch(error => {
                console.error('Error loading trading pairs:', error);
                symbolList.innerHTML = '<div class="tv-symbol-error">Error loading trading pairs</div>';
            });
    }

    function renderSymbolList() {
        const symbolList = document.getElementById('symbolList');
        const searchInput = document.getElementById('symbolSearch');
        if (!symbolList) return;

        let filteredPairs = allTradingPairs;

        // Filter by category
        if (currentCategory === 'favourites') {
            filteredPairs = filteredPairs.filter(pair => favorites.includes(pair.symbol));
        } else if (currentCategory !== 'all') {
            filteredPairs = filteredPairs.filter(pair => pair.category === currentCategory);
        }

        // Filter by search query
        const searchQuery = searchInput?.value.toLowerCase() || '';
        if (searchQuery) {
            filteredPairs = filteredPairs.filter(pair =>
                pair.displaySymbol.toLowerCase().includes(searchQuery) ||
                pair.name.toLowerCase().includes(searchQuery) ||
                pair.symbol.toLowerCase().includes(searchQuery)
            );
        }

        if (filteredPairs.length === 0) {
            symbolList.innerHTML = '<div class="tv-symbol-empty">No pairs found</div>';
            return;
        }

        symbolList.innerHTML = filteredPairs.map(pair => {
            const isActive = pair.symbol === currentSymbol;
            const isFavorite = favorites.includes(pair.symbol);
            const changeClass = pair.change24h >= 0 ? 'positive' : 'negative';
            const changeSign = pair.change24h >= 0 ? '+' : '';

            return `
                <div class="tv-symbol-item ${isActive ? 'active' : ''}" data-symbol="${pair.symbol}">
                    <div class="tv-symbol-left">
                        ${pair.icon ? `<img src="${pair.icon}" class="tv-symbol-icon" alt="${pair.name}">` : ''}
                        <div class="tv-symbol-info">
                            <div class="tv-symbol-name-row">
                                <span class="tv-symbol-name">${pair.displaySymbol}</span>
                                <span class="tv-symbol-leverage">${pair.leverage}</span>
                            </div>
                            <span class="tv-symbol-desc">${pair.name}</span>
                        </div>
                    </div>
                    <div class="tv-symbol-right">
                        <div class="tv-symbol-price">${formatPrice(pair.price)}</div>
                        <div class="tv-symbol-change ${changeClass}">${changeSign}${pair.change24h.toFixed(2)}%</div>
                        <div class="tv-symbol-volume">${pair.volumeDisplay || formatVolume(pair.volume)}</div>
                        <button class="tv-symbol-favorite ${isFavorite ? 'active' : ''}" 
                                data-symbol="${pair.symbol}" 
                                onclick="toggleFavorite(event, '${pair.symbol}')">
                            <i class="las la-star"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        // Attach click handlers
        symbolList.querySelectorAll('.tv-symbol-item').forEach(item => {
            item.addEventListener('click', function (e) {
                // Don't trigger if clicking the favorite button
                if (e.target.closest('.tv-symbol-favorite')) {
                    return;
                }

                const symbol = this.dataset.symbol;
                if (symbol) {
                    selectSymbol(symbol);
                }
            });
        });
    }

    function selectSymbol(symbol) {
        const symbolList = document.getElementById('symbolList');
        if (!symbolList) return;

        // Don't do anything if same symbol
        if (symbol === currentSymbol) {
            document.getElementById('symbolDropdown')?.classList.remove('active');
            return;
        }

        // Update active state
        symbolList.querySelectorAll('.tv-symbol-item').forEach(i => i.classList.remove('active'));
        const activeItem = symbolList.querySelector(`[data-symbol="${symbol}"]`);
        if (activeItem) activeItem.classList.add('active');

        // Update current symbol
        const oldSymbol = currentSymbol;
        currentSymbol = symbol;

        // Save to localStorage for persistence across reloads
        localStorage.setItem('tradingTerminalSymbol', symbol);

        console.log(`🔄 Symbol switching from ${oldSymbol} to ${symbol} - reloading page...`);

        // Update URL parameter and reload page to ensure all components update correctly
        const url = new URL(window.location);
        url.searchParams.set('symbol', symbol);
        window.location.href = url.toString();

        // Log symbol change for debugging
        console.log(`✅ Symbol changed from ${oldSymbol} to ${symbol}`);
    }

    /**
     * Update orderbook header labels to show correct currency
     */
    function updateOrderbookHeaders(symbol) {
        // Extract base and quote currencies
        const baseSymbol = symbol.replace('USDT', '').replace('USD', '').replace('EUR', '').replace('GBP', '');
        const quoteSymbol = symbol.includes('USDT') ? 'USDT' :
            symbol.includes('USD') ? 'USD' :
                symbol.includes('EUR') ? 'EUR' :
                    symbol.includes('GBP') ? 'GBP' : 'USDT';

        // Update orderbook header
        const orderbookHeader = document.querySelector('.tv-orderbook-header');
        if (orderbookHeader) {
            orderbookHeader.innerHTML = `
                <span>Price(${quoteSymbol})</span>
                <span>Amount(${baseSymbol})</span>
            `;
        }
    }



    function toggleFavorite(event, symbol) {
        event.stopPropagation();
        if (index > -1) {
            favorites.splice(index, 1);
        } else {
            favorites.push(symbol);
        }
        localStorage.setItem('tradingFavorites', JSON.stringify(favorites));
        renderSymbolList();
    }

    function formatPrice(price) {
        if (price >= 1000) {
            return price.toLocaleString('en-US', { maximumFractionDigits: 2 });
        } else if (price >= 1) {
            return price.toFixed(2);
        } else {
            return price.toFixed(4);
        }
    }

    function formatVolume(volume) {
        if (volume >= 1000000000) {
            return (volume / 1000000000).toFixed(2) + 'B';
        } else if (volume >= 1000000) {
            return (volume / 1000000).toFixed(2) + 'M';
        } else if (volume >= 1000) {
            return (volume / 1000).toFixed(2) + 'K';
        }
        return volume.toFixed(0);
    }

    // Expose toggleFavorite globally
    window.toggleFavorite = toggleFavorite;

    /**
     * Mode Toggle (Demo vs Real)
     */
    function initializeModeToggle() {
        document.querySelectorAll('.tv-mode-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const mode = this.dataset.mode;
                switchMode(mode);
            });
        });
    }

    function switchMode(mode) {
        currentMode = mode;

        // Update buttons
        document.querySelectorAll('.tv-mode-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.mode === mode);
        });

        // Update content
        document.querySelectorAll('.tv-mode-content').forEach(content => {
            content.classList.toggle('active', content.id === mode + 'Mode');
        });

        // Show/hide connection selector for real mode
        const connectionSelector = document.getElementById('connectionSelector');
        if (connectionSelector) {
            connectionSelector.style.display = mode === 'real' ? 'block' : 'none';
        }

        // Update balance display based on mode
        updateBalanceDisplay(mode);

        if (mode === 'real') {
            initializeRealTrading();
        } else {
            stopRealTrading();
        }
    }

    /**
     * Update balance display based on trading mode
     */
    function updateBalanceDisplay(mode) {
        const balanceEl = document.getElementById('availableBalance');
        if (!balanceEl) return;

        const realBalance = parseFloat(balanceEl.dataset.realBalance || 0);
        const demoBalance = parseFloat(balanceEl.dataset.demoBalance || 10000);

        const balance = mode === 'demo' ? demoBalance : realBalance;
        balanceEl.textContent = balance.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' USDT';
    }

    /**
     * Real Trading Initialization
     */
    function initializeRealTrading() {
        initChart();
        // Orderbook already loaded via REST, WebSocket will update it
        // Positions will load via REST first, then WebSocket will update
        loadPositions();
        updatePrice();
        // Only start polling as fallback if WebSocket not available
        if (!useWebSocketForPositions || !useWebSocketForMarketData) {
            startDataUpdates();
        }
    }

    function stopRealTrading() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    /**
     * TradingView Chart Widget
     */
    function initChart() {
        console.log('📺 initChart: Called for symbol:', currentSymbol);
        if (typeof TradingView === 'undefined') {
            console.log('TradingView not loaded yet, retrying...');
            setTimeout(initChart, 100);
            return;
        }

        // Check if container exists
        const container = document.getElementById('tradingview_chart');
        if (!container) {
            console.warn('Chart container not found');
            return;
        }

        // Completely clear the container and create a new unique container for the widget
        container.innerHTML = '';
        const widgetContainer = document.createElement('div');
        widgetContainer.id = 'tradingview_widget_' + Date.now(); // Unique ID to force fresh widget
        widgetContainer.style.height = '600px';
        container.appendChild(widgetContainer);

        // Determine exchange prefix based on symbol
        let exchangePrefix = 'BINANCE';
        if (currentSymbol.includes('USDT') || currentSymbol.includes('BTC') || currentSymbol.includes('ETH')) {
            exchangePrefix = 'BINANCE';
        } else if (currentSymbol.includes('EUR') || currentSymbol.includes('GBP') || currentSymbol.includes('USD')) {
            exchangePrefix = 'FX_IDC';
        }

        const symbolToLoad = exchangePrefix + ":" + currentSymbol;
        console.log('Initializing TradingView widget with symbol:', symbolToLoad, 'in container:', widgetContainer.id);

        // Initialize TradingView widget with current symbol
        try {
            chart = new TradingView.widget({
                "width": "100%",
                "height": 600,
                "symbol": symbolToLoad,
                "interval": "5",
                "timezone": "Etc/UTC",
                "theme": "dark",
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#f1f3f6",
                "enable_publishing": false,
                "hide_side_toolbar": false,
                "allow_symbol_change": false, // We control symbol via our dropdown
                "container_id": widgetContainer.id, // Use unique container ID
                "disabled_features": [
                    "use_localstorage_for_settings", // Prevent caching old studies
                    "volume_force_overlay"
                ],
                "overrides": {
                    "paneProperties.background": "#0a0e1a",
                    "paneProperties.vertGridProperties.color": "rgba(255, 255, 255, 0.05)",
                    "paneProperties.horzGridProperties.color": "rgba(255, 255, 255, 0.05)",
                    "scalesProperties.textColor": "#d1d4dc"
                }
            });

            console.log('✅ TradingView widget created successfully for', symbolToLoad);
        } catch (error) {
            console.error('Error creating TradingView widget:', error);
            container.innerHTML = '<div class="tv-chart-loading"><i class="las la-exclamation-triangle" style="color: #ef4444;"></i><p>Error loading chart</p></div>';
        }
    }

    // No need for loadCandlestickData or updateLegend as Widget handles it
    function loadCandlestickData() {
        // Placeholder to prevent errors if called
    }

    function updateLegend(param) {
        // Placeholder
    }

    function showChartError(message) {
        const chartContainer = document.getElementById('tradingviewChart');
        if (chartContainer) {
            chartContainer.innerHTML = `
                <div class="tv-chart-loading">
                    <i class="las la-exclamation-triangle" style="color: #ef4444;"></i>
                    <p>Error: ${message}</p>
                    <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #1AFFD5; border: none; border-radius: 4px; cursor: pointer;">
                        Reload Page
                    </button>
                </div>
            `;
        }
    }

    /**
     * Initialize Laravel Echo WebSocket listeners for positions and market data
     */
    function initLaravelEchoListeners() {
        if (typeof window.Echo === 'undefined') {
            console.log('Laravel Echo not available. Using polling fallback.');
            return;
        }

        try {
            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!userId) {
                console.warn('User ID not found. WebSocket listeners disabled.');
                return;
            }

            // Listen for position updates
            positionsChannel = window.Echo.private(`user.${userId}.positions`);

            positionsChannel.listen('.position.updated', (data) => {
                console.log('WebSocket: Position update received', data);
                useWebSocketForPositions = true;

                if (data.position) {
                    // Update single position or reload all
                    if (data.position.status === 'closed') {
                        // Position closed, reload all positions
                        loadPositions();
                    } else {
                        // Position updated, reload all positions to get fresh data
                        loadPositions();
                    }
                }
            });

            console.log('WebSocket: Subscribed to positions channel');
            useWebSocketForPositions = true;

            // Listen for market data updates
            const symbol = currentSymbol.toLowerCase();
            marketDataChannel = window.Echo.channel(`market.${symbol}`);

            marketDataChannel.listen('.price.updated', (data) => {
                console.log('WebSocket: Market price update received', data);
                useWebSocketForMarketData = true;

                if (data.price) {
                    updatePriceFromWebSocket(data);
                }
            });

            console.log(`WebSocket: Subscribed to market data channel: market.${symbol}`);
            useWebSocketForMarketData = true;

        } catch (error) {
            console.warn('WebSocket listener initialization failed:', error);
        }
    }

    /**
     * Update price from WebSocket data
     */
    function updatePriceFromWebSocket(data) {
        const price = parseFloat(data.price);
        if (!price || isNaN(price)) return;

        // Update Main Header Price
        const priceEl = document.getElementById('currentPrice');
        if (priceEl) {
            priceEl.textContent = price.toFixed(2);
        }

        // Update Quick Trade Buttons
        const quickBuy = document.getElementById('quickBuyPrice');
        const quickSell = document.getElementById('quickSellPrice');
        if (quickBuy) quickBuy.textContent = price.toFixed(2);
        if (quickSell) quickSell.textContent = price.toFixed(2);

        // Update price change if available
        if (data.stats && data.stats.priceChangePercent !== undefined) {
            const change = parseFloat(data.stats.priceChangePercent);
            const changeEl = document.getElementById('priceChange');
            if (changeEl) {
                changeEl.textContent = (change >= 0 ? '+' : '') + change.toFixed(2) + '%';
                changeEl.className = 'tv-change ' + (change >= 0 ? 'positive' : 'negative');
            }
        }
    }

    /**
     * WebSocket for Real-time Orderbook (Binance direct)
     * Falls back to REST polling if WebSocket connection fails
     */
    let wsConnectionTimeout = null;
    let wsUsePolling = false; // Flag to track if we should use polling instead

    function connectWebSocket() {
        // If we've determined WebSocket doesn't work, skip and use polling
        if (wsUsePolling) {
            console.log('WebSocket disabled, using REST polling for orderbook');
            startOrderbookPolling();
            return;
        }

        // Close existing connection if it exists and is not already closed
        if (ws) {
            if (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING) {
                ws.close();
            }
            ws = null;
        }

        // Clear any existing timeout
        if (wsConnectionTimeout) {
            clearTimeout(wsConnectionTimeout);
            wsConnectionTimeout = null;
        }

        try {
            // Use Binance WebSocket for orderbook depth
            // Format: symbol must be lowercase, e.g., btcusdt
            const symbol = currentSymbol.toLowerCase();
            // Binance depth stream format (using standard port 443, no explicit port needed)
            // Format: wss://stream.binance.com/ws/{symbol}@depth20@100ms
            const wsUrl = `wss://stream.binance.com/ws/${symbol}@depth20@100ms`;

            // Only log connection attempts in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log('Connecting to Binance WebSocket:', wsUrl);
            }
            ws = new WebSocket(wsUrl);

            // Set connection timeout (10 seconds)
            wsConnectionTimeout = setTimeout(() => {
                if (ws && ws.readyState !== WebSocket.OPEN) {
                    console.warn('WebSocket connection timeout, falling back to REST polling');
                    ws.close();
                    wsUsePolling = true;
                    startOrderbookPolling();
                }
            }, 10000);

            ws.onopen = () => {
                console.log('✅ Binance WebSocket connected for orderbook:', symbol);
                wsReconnectAttempts = 0;
                wsUsePolling = false; // Reset flag on successful connection

                // Clear timeout
                if (wsConnectionTimeout) {
                    clearTimeout(wsConnectionTimeout);
                    wsConnectionTimeout = null;
                }

                // Stop polling if it was running
                stopOrderbookPolling();

                // Show connected indicator for both layouts
                const spreadEl = document.getElementById('orderbookSpread');
                const spreadElInteract = document.getElementById('orderbookSpread_interact');
                if (spreadEl) {
                    spreadEl.style.color = '#22c55e';
                    spreadEl.title = 'WebSocket Connected';
                }
                if (spreadElInteract) {
                    spreadElInteract.style.color = '#22c55e';
                    spreadElInteract.title = 'WebSocket Connected';
                }
            };

            ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    if (data.bids && data.asks) {
                        // Check if we're using interact.js layout
                        const isInteractLayout = document.getElementById('interactLayoutContainer')?.style.display !== 'none';

                        if (isInteractLayout) {
                            // Update interact.js layout orderbook
                            updateInteractOrderbook({
                                bids: data.bids,
                                asks: data.asks
                            });
                        } else {
                            // Update Golden Layout orderbook
                            updateOrderbook({
                                bids: data.bids,
                                asks: data.asks
                            });
                        }

                        // Also update depth chart if it exists
                        if (window.updateDepthChart) {
                            window.updateDepthChart(data.asks, data.bids);
                        }
                    } else {
                        console.warn('WebSocket message missing bids/asks:', data);
                    }
                } catch (error) {
                    console.error('WebSocket message parse error:', error, event.data);
                }
            };

            ws.onerror = (error) => {
                // Only log error details in development, not in production
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.error('❌ WebSocket error:', error);
                } else {
                    console.log('WebSocket connection unavailable, using REST polling');
                }

                // Clear timeout
                if (wsConnectionTimeout) {
                    clearTimeout(wsConnectionTimeout);
                    wsConnectionTimeout = null;
                }

                // Show error indicator
                const spreadEl = document.getElementById('orderbookSpread');
                const spreadElInteract = document.getElementById('orderbookSpread_interact');
                if (spreadEl) {
                    spreadEl.style.color = '#ef4444';
                    spreadEl.title = 'Using REST polling (WebSocket unavailable)';
                }
                if (spreadElInteract) {
                    spreadElInteract.style.color = '#ef4444';
                    spreadElInteract.title = 'Using REST polling (WebSocket unavailable)';
                }

                // If error occurs before connection, fallback immediately
                if (ws && ws.readyState === WebSocket.CONNECTING) {
                    wsUsePolling = true;
                    setTimeout(() => {
                        if (ws) ws.close();
                        startOrderbookPolling();
                    }, 100);
                }
            };

            ws.onclose = (event) => {
                // Only log details in development
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.log('WebSocket closed:', event.code, event.reason || 'No reason provided');
                }

                // Clear timeout
                if (wsConnectionTimeout) {
                    clearTimeout(wsConnectionTimeout);
                    wsConnectionTimeout = null;
                }

                ws = null;

                // Show disconnected indicator for both layouts
                const spreadEl = document.getElementById('orderbookSpread');
                const spreadElInteract = document.getElementById('orderbookSpread_interact');
                if (spreadEl) {
                    spreadEl.style.color = '#ef4444';
                    spreadEl.title = 'Using REST polling';
                }
                if (spreadElInteract) {
                    spreadElInteract.style.color = '#ef4444';
                    spreadElInteract.title = 'Using REST polling';
                }

                // Error code 1006 = abnormal closure (network/firewall issue)
                // Error code 1000 = normal closure
                if (event.code === 1006) {
                    // Don't spam console in production
                    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                        console.log('WebSocket unavailable, using REST polling');
                    }
                    wsUsePolling = true;
                    startOrderbookPolling();
                } else if (event.code !== 1000 && wsReconnectAttempts < wsMaxReconnectAttempts) {
                    // Attempt reconnection for other error codes
                    wsReconnectAttempts++;
                    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                        console.log(`Reconnecting WebSocket (attempt ${wsReconnectAttempts}/${wsMaxReconnectAttempts})...`);
                    }
                    setTimeout(connectWebSocket, 2000 * wsReconnectAttempts);
                } else {
                    // Max attempts reached or normal closure
                    if (wsReconnectAttempts >= wsMaxReconnectAttempts) {
                        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                            console.log('Using REST polling for orderbook updates');
                        }
                        wsUsePolling = true;
                    }
                    startOrderbookPolling();
                }
            };
        } catch (error) {
            console.error('WebSocket connection error:', error);
            wsUsePolling = true;
            // Fallback to REST polling
            startOrderbookPolling();
        }
    }

    /**
     * Fallback: Poll orderbook via REST API
     */
    let orderbookPollInterval = null;
    function startOrderbookPolling() {
        // Clear any existing polling
        if (orderbookPollInterval) {
            clearInterval(orderbookPollInterval);
        }

        // Don't start polling if WebSocket is connected
        if (ws && ws.readyState === WebSocket.OPEN) {
            return;
        }

        console.log('Starting orderbook REST polling (fallback mode)');

        // Load immediately
        loadOrderbookREST();

        // Poll every 2 seconds
        orderbookPollInterval = setInterval(() => {
            // Only poll if WebSocket is not connected
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                loadOrderbookREST();
            } else {
                // WebSocket reconnected, stop polling
                stopOrderbookPolling();
            }
        }, 2000);
    }

    function stopOrderbookPolling() {
        if (orderbookPollInterval) {
            clearInterval(orderbookPollInterval);
            orderbookPollInterval = null;
            console.log('Stopped orderbook REST polling');
        }
    }

    function disconnectWebSocket() {
        if (ws) {
            ws.close();
            ws = null;
        }
        stopOrderbookPolling();
    }

    /**
     * Orderbook (REST Fallback)
     */
    function loadOrderbookREST() {
        console.log('📊 Loading orderbook for symbol:', currentSymbol);
        const asksContainer = document.getElementById('orderbookAsks');
        const bidsContainer = document.getElementById('orderbookBids');

        if (asksContainer && bidsContainer) {
            asksContainer.innerHTML = '<div style="padding: 1rem; text-align: center; color: #848e9c;"><i class="las la-spinner la-spin"></i> Loading...</div>';
            bidsContainer.innerHTML = '';
        }

        fetch(`/terminal/market-data?symbol=${currentSymbol}&type=orderbook`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Orderbook data loaded for', currentSymbol, ':', data);
                if (data.success && data.data) {
                    renderOrderbook(data.data);
                } else {
                    console.error('Orderbook data invalid:', data);
                    showOrderbookError('No data available');
                }
            })
            .catch(error => {
                console.error('Error loading orderbook:', error);
                showOrderbookError('Failed to load');
            });
    }

    // Alias for backward compatibility
    function loadOrderbook() {
        // Load REST first for instant display, then connect WebSocket for updates
        loadOrderbookREST();
        connectWebSocket();
    }

    function showOrderbookError(message) {
        const asksContainer = document.getElementById('orderbookAsks');
        const bidsContainer = document.getElementById('orderbookBids');

        if (asksContainer) {
            asksContainer.innerHTML = `<div style="padding: 1rem; text-align: center; color: #ef4444;">${message}</div>`;
        }
        if (bidsContainer) {
            bidsContainer.innerHTML = '';
        }
    }

    function updateOrderbook(data) {
        if (!data) {
            console.error('No orderbook data');
            return;
        }

        console.log('Raw orderbook data:', data);

        // Normalize data format - handle both array and object formats
        const normalizeOrderbookEntry = (entry) => {
            if (Array.isArray(entry)) {
                // Format: [price, amount]
                return {
                    price: parseFloat(entry[0]),
                    amount: parseFloat(entry[1])
                };
            } else if (typeof entry === 'object') {
                // Format: {price: x, amount: y} or {price: x, quantity: y}
                return {
                    price: parseFloat(entry.price || entry[0]),
                    amount: parseFloat(entry.amount || entry.quantity || entry[1])
                };
            }
            return { price: 0, amount: 0 };
        };

        // Validate and normalize asks/bids
        let asks = (data.asks || []).map(normalizeOrderbookEntry).filter(e => e.price > 0 && e.amount > 0);
        let bids = (data.bids || []).map(normalizeOrderbookEntry).filter(e => e.price > 0 && e.amount > 0);

        if (asks.length === 0 && bids.length === 0) {
            console.error('No valid orderbook entries');
            showOrderbookError('No orderbook data');
            return;
        }

        // Sort asks ascending (lowest price first = best ask)
        asks.sort((a, b) => a.price - b.price);
        // Sort bids descending (highest price first = best bid)
        bids.sort((a, b) => b.price - a.price);

        // Calculate cumulative depth for orderbook bars
        let asksCumulative = 0;
        let bidsCumulative = 0;
        const displayAsksCount = 20;
        const displayBidsCount = 20;

        const asksWithCumulative = asks.slice(0, displayAsksCount).map(ask => {
            asksCumulative += ask.amount;
            return { ...ask, cumulative: asksCumulative };
        });

        const bidsWithCumulative = bids.slice(0, displayBidsCount).map(bid => {
            bidsCumulative += bid.amount;
            return { ...bid, cumulative: bidsCumulative };
        });

        const maxCumulative = Math.max(asksCumulative, bidsCumulative);

        // Render Asks (Red) - Highest price at top
        const asksContainer = document.getElementById('orderbookAsks');
        if (asksContainer && asksWithCumulative.length > 0) {
            asksContainer.innerHTML = asksWithCumulative.map(ask => {
                const price = ask.price.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const amount = ask.amount.toFixed(4);
                const depthPct = Math.min((ask.cumulative / maxCumulative) * 100, 100);

                return `
                    <div class="tv-row ask" onclick="setPrice('${price}')" style="background: linear-gradient(to left, rgba(246, 70, 93, 0.15) ${depthPct}%, transparent ${depthPct}%)">
                        <span class="tv-ask-price">${price}</span>
                        <span class="tv-amount">${amount}</span>
                    </div>
                `;
            }).join(''); // Removed .reverse() because CSS uses column-reverse
        }

        // Render Bids (Green) - Highest price at top
        const bidsContainer = document.getElementById('orderbookBids');
        if (bidsContainer && bidsWithCumulative.length > 0) {
            bidsContainer.innerHTML = bidsWithCumulative.map(bid => {
                const price = bid.price.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const amount = bid.amount.toFixed(4);
                const depthPct = Math.min((bid.cumulative / maxCumulative) * 100, 100);

                return `
                    <div class="tv-row bid" onclick="setPrice('${price}')" style="background: linear-gradient(to left, rgba(34, 197, 94, 0.15) ${depthPct}%, transparent ${depthPct}%)">
                        <span class="tv-bid-price">${price}</span>
                        <span class="tv-amount">${amount}</span>
                    </div>
                `;
            }).join('');
        }

        // Update mid price and spread
        const midPriceEl = document.getElementById('midPrice');
        const spreadEl = document.getElementById('orderbookSpread');

        if (asks.length > 0 && bids.length > 0) {
            // Best ask = lowest ask price (first after sorting ascending)
            const bestAsk = asks[0].price;
            // Best bid = highest bid price (first after sorting descending)
            const bestBid = bids[0].price;

            // Calculate mid price
            const midPrice = ((bestAsk + bestBid) / 2).toFixed(currentSymbol.includes('USDT') ? 2 : 6);

            if (midPriceEl) {
                midPriceEl.textContent = midPrice;
                midPriceEl.className = 'tv-current-price';
            }

            // Calculate spread
            if (spreadEl && bestBid > 0) {
                const spread = bestAsk - bestBid;
                const spreadPips = spread.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const spreadPct = ((spread / bestBid) * 100).toFixed(4);

                // User specifically requested pip and % 
                // Using a slightly more robust format to ensure it's distinct from old logic
                spreadEl.textContent = `Spread: ${spreadPips} (${spreadPct}%)`;

                // Update spread color based on value (tight spread = green, wide = red)
                const pctVal = parseFloat(spreadPct);
                if (pctVal < 0.01) {
                    spreadEl.style.color = '#1AFFD5'; // Very tight
                } else if (pctVal < 0.05) {
                    spreadEl.style.color = '#22c55e'; // Tight
                } else if (pctVal < 0.2) {
                    spreadEl.style.color = '#fbbf24'; // Warning
                } else {
                    spreadEl.style.color = '#ef4444'; // Wide
                }
            }
        } else {
            // No data available
            if (spreadEl) {
                spreadEl.textContent = 'Spread: --';
                spreadEl.style.color = '#848e9c';
            }
            if (midPriceEl) {
                midPriceEl.textContent = '--';
            }
        }

        // Update Depth Chart
        if (typeof updateDepthChart === 'function') {
            updateDepthChart(asks, bids);
        }
    }

    // Alias for compatibility
    function renderOrderbook(data) {
        updateOrderbook(data);
    }

    /**
     * Update orderbook for interact.js layout
     */
    function updateInteractOrderbook(data) {
        const asksContainer = document.getElementById('orderbookAsks_interact');
        const bidsContainer = document.getElementById('orderbookBids_interact');
        const midPriceEl = document.getElementById('midPrice_interact');
        const spreadEl = document.getElementById('orderbookSpread_interact');
        const depthChartEl = document.getElementById('depthChart_interact');

        if (!asksContainer || !bidsContainer) {
            // Fallback to regular updateOrderbook
            updateOrderbook(data);
            return;
        }

        // Normalize data format
        const normalizeOrderbookEntry = (entry) => {
            if (Array.isArray(entry)) {
                return {
                    price: parseFloat(entry[0]),
                    amount: parseFloat(entry[1])
                };
            } else if (typeof entry === 'object') {
                return {
                    price: parseFloat(entry.price || entry[0]),
                    amount: parseFloat(entry.amount || entry.quantity || entry[1])
                };
            }
            return { price: 0, amount: 0 };
        };

        let asks = (data.asks || []).map(normalizeOrderbookEntry).filter(e => e.price > 0 && e.amount > 0);
        let bids = (data.bids || []).map(normalizeOrderbookEntry).filter(e => e.price > 0 && e.amount > 0);

        if (asks.length === 0 && bids.length === 0) {
            return;
        }

        asks.sort((a, b) => a.price - b.price);
        bids.sort((a, b) => b.price - a.price);

        // Calculate cumulative depth for background bars
        let asksCumulative = 0;
        let bidsCumulative = 0;
        const displayAsksCount = 20;
        const displayBidsCount = 20;

        const asksWithCumulative = asks.slice(0, displayAsksCount).map(ask => {
            asksCumulative += ask.amount;
            return { ...ask, cumulative: asksCumulative };
        });

        const bidsWithCumulative = bids.slice(0, displayBidsCount).map(bid => {
            bidsCumulative += bid.amount;
            return { ...bid, cumulative: bidsCumulative };
        });

        const maxCumulative = Math.max(asksCumulative, bidsCumulative);

        // Render Asks
        if (asksContainer && asksWithCumulative.length > 0) {
            asksContainer.innerHTML = asksWithCumulative.map(ask => {
                const price = ask.price.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const amount = ask.amount.toFixed(4);
                const depthPct = Math.min((ask.cumulative / maxCumulative) * 100, 100);
                return `
                    <div class="tv-row ask" onclick="setPrice('${price}')" style="background: linear-gradient(to left, rgba(246, 70, 93, 0.15) ${depthPct}%, transparent ${depthPct}%)">
                        <span class="tv-ask-price">${price}</span>
                        <span class="tv-amount">${amount}</span>
                    </div>
                `;
            }).join(''); // Removed .reverse() because CSS uses column-reverse
        }

        // Render Bids
        if (bidsContainer && bidsWithCumulative.length > 0) {
            bidsContainer.innerHTML = bidsWithCumulative.map(bid => {
                const price = bid.price.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const amount = bid.amount.toFixed(4);
                const depthPct = Math.min((bid.cumulative / maxCumulative) * 100, 100);
                return `
                    <div class="tv-row bid" onclick="setPrice('${price}')" style="background: linear-gradient(to left, rgba(34, 197, 94, 0.15) ${depthPct}%, transparent ${depthPct}%)">
                        <span class="tv-bid-price">${price}</span>
                        <span class="tv-amount">${amount}</span>
                    </div>
                `;
            }).join('');
        }

        // Update mid price and spread
        if (asks.length > 0 && bids.length > 0) {
            const bestAsk = asks[0].price;
            const bestBid = bids[0].price;
            const midPrice = ((bestAsk + bestBid) / 2).toFixed(currentSymbol.includes('USDT') ? 2 : 6);

            if (midPriceEl) {
                midPriceEl.textContent = midPrice;
                midPriceEl.className = 'tv-current-price';
            }

            if (spreadEl && bestBid > 0) {
                const spread = bestAsk - bestBid;
                const spreadPips = spread.toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const spreadPct = ((spread / bestBid) * 100).toFixed(4);
                spreadEl.textContent = `Spread: ${spreadPips} (${spreadPct}%)`;

                const pctVal = parseFloat(spreadPct);
                if (pctVal < 0.05) {
                    spreadEl.style.color = '#22c55e';
                } else if (pctVal < 0.2) {
                    spreadEl.style.color = '#fbbf24';
                } else {
                    spreadEl.style.color = '#ef4444';
                }
            }

            // Update depth chart for interact layout
            const depthChartEl = document.getElementById('depthChart_interact');

            if (window.updateDepthChart && depthChartEl && depthChartEl._interactChart) {
                window.updateDepthChart(asks, bids, depthChartEl._interactChart);
            }
        }
    }

    // Expose for WebSocket updates
    window.updateInteractOrderbook = updateInteractOrderbook;

    // Helper function to set price in limit order
    window.setPrice = function (price) {
        const limitPriceInput = document.getElementById('limitPrice');
        if (limitPriceInput) {
            limitPriceInput.value = price;
            // Show limit price group if hidden
            const limitPriceGroup = document.getElementById('limitPriceGroup');
            if (limitPriceGroup && limitPriceGroup.classList.contains('d-none')) {
                // Switch to limit tab
                document.querySelector('.tv-order-tab[data-type="limit"]')?.click();
            }
        }
    };

    // Click to fill price (legacy code, now using onclick in HTML)
    document.querySelectorAll('.tv-orderbook-row').forEach(row => {
        row.addEventListener('click', function () {
            const price = this.dataset.price;
            const limitPriceInput = document.getElementById('limitPrice');
            if (limitPriceInput) {
                limitPriceInput.value = price;
            }
        });
    });

    /**
     * Positions
     */
    function loadPositions() {
        fetch('/terminal/positions', {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderPositions(data.data);
                }
            })
            .catch(error => console.error('Error loading positions:', error));
    }

    function renderPositions(positions) {
        const tbody = document.getElementById('positionsTableBody');
        if (!tbody) return;

        if (positions.length === 0) {
            tbody.innerHTML = '<tr class="tv-empty-state"><td colspan="7">No open positions</td></tr>';
            return;
        }

        tbody.innerHTML = positions.map(position => `
            <tr>
                <td>${position.symbol}</td>
                <td><span class="tv-badge ${position.direction === 'buy' ? 'tv-badge-success' : 'tv-badge-danger'}">${position.direction.toUpperCase()}</span></td>
                <td>${position.quantity}</td>
                <td>$${parseFloat(position.entry_price).toFixed(2)}</td>
                <td>$${parseFloat(position.current_price).toFixed(2)}</td>
                <td class="${position.pnl >= 0 ? 'tv-text-success' : 'tv-text-danger'}">$${parseFloat(position.pnl).toFixed(2)}</td>
                <td>
                    <button class="tv-btn-close-position" data-id="${position.id}">
                        <i class="las la-times"></i> Close
                    </button>
                </td>
            </tr>
        `).join('');

        // Add close position handlers
        document.querySelectorAll('.tv-btn-close-position').forEach(btn => {
            btn.addEventListener('click', function () {
                closePosition(this.dataset.id);
            });
        });
    }

    /**
     * Order Form
     */
    function initializeOrderForm() {
        const orderForm = document.getElementById('orderForm');
        if (!orderForm) return;

        orderForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const direction = e.submitter.dataset.direction;
            placeOrder(direction);
        });

        // Order Type Toggle
        document.querySelectorAll('.tv-order-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const type = this.dataset.type;
                document.querySelectorAll('.tv-order-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const limitPriceGroup = document.getElementById('limitPriceGroup');
                if (limitPriceGroup) {
                    if (type === 'limit') {
                        limitPriceGroup.classList.remove('d-none');
                        limitPriceGroup.style.display = 'block';
                    } else {
                        limitPriceGroup.classList.add('d-none');
                        limitPriceGroup.style.display = 'none';
                    }
                }
            });
        });

        // BBO Buttons (Best Bid/Offer)
        document.querySelectorAll('.tv-bbo-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const side = this.dataset.side;
                const limitPriceInput = document.getElementById('limitPrice');

                if (!limitPriceInput) return;

                // Get best bid/ask from orderbook
                if (side === 'bid') {
                    const firstBid = document.querySelector('#orderbookBids .tv-row .tv-bid-price');
                    if (firstBid) {
                        limitPriceInput.value = firstBid.textContent.trim();
                        calculateOrderTotal();
                    }
                } else if (side === 'ask') {
                    const firstAsk = document.querySelector('#orderbookAsks .tv-row .tv-ask-price');
                    if (firstAsk) {
                        limitPriceInput.value = firstAsk.textContent.trim();
                        calculateOrderTotal();
                    }
                }
            });
        });

        // Quick Amount Buttons (25%, 50%, 75%, 100%)
        document.querySelectorAll('.tv-amount-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const pct = parseFloat(this.dataset.pct);
                const availBalance = parseFloat(document.querySelector('.tv-avail-balance')?.textContent.replace(/[^0-9.]/g, '') || 0);
                const orderAmountInput = document.getElementById('orderAmount');
                const limitPriceInput = document.getElementById('limitPrice');

                if (!orderAmountInput) return;

                // Get current price (from limit input or mid price)
                let currentPrice = parseFloat(limitPriceInput?.value || 0);
                if (!currentPrice) {
                    const midPrice = document.getElementById('midPrice')?.textContent;
                    currentPrice = parseFloat(midPrice) || 0;
                }

                if (currentPrice > 0 && availBalance > 0) {
                    // Calculate amount based on percentage of available balance
                    const usdtAmount = (availBalance * pct) / 100;
                    const btcAmount = usdtAmount / currentPrice;
                    orderAmountInput.value = btcAmount.toFixed(6);

                    // Update active state
                    document.querySelectorAll('.tv-amount-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    calculateOrderTotal();
                }
            });
        });

        // Order Total Calculation
        const orderAmountInput = document.getElementById('orderAmount');
        const limitPriceInput = document.getElementById('limitPrice');

        if (orderAmountInput) {
            orderAmountInput.addEventListener('input', calculateOrderTotal);
        }
        if (limitPriceInput) {
            limitPriceInput.addEventListener('input', calculateOrderTotal);
        }
    }

    function calculateOrderTotal() {
        const orderAmountInput = document.getElementById('orderAmount');
        const limitPriceInput = document.getElementById('limitPrice');
        const orderTotalInput = document.getElementById('orderTotal');

        if (!orderTotalInput) return;

        const amount = parseFloat(orderAmountInput?.value || 0);
        let price = parseFloat(limitPriceInput?.value || 0);

        // If no limit price, use mid price
        if (!price) {
            const midPrice = document.getElementById('midPrice')?.textContent;
            price = parseFloat(midPrice) || 0;
        }

        if (amount > 0 && price > 0) {
            const total = amount * price;
            orderTotalInput.value = total.toFixed(2);
        } else {
            orderTotalInput.value = '';
        }
    }

    function placeOrder(direction) {
        const amountInput = document.getElementById('orderAmount');
        const amount = amountInput?.value;
        const slPrice = document.getElementById('stopLoss')?.value;
        const tpPrice = document.getElementById('takeProfit')?.value;
        const errorDiv = document.getElementById('orderAmount-error');

        // Clear previous errors
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
        if (amountInput) {
            amountInput.classList.remove('is-invalid');
        }

        // Validate amount
        if (!amount || parseFloat(amount) <= 0) {
            const errorMsg = 'Amount is required and must be greater than 0. Please enter the quantity you want to trade.';
            if (errorDiv) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            }
            if (amountInput) {
                amountInput.classList.add('is-invalid');
                amountInput.focus();
            }
            if (typeof toastr !== 'undefined') {
                toastr.error('Please enter a valid amount');
            } else {
                alert(errorMsg);
            }
            return;
        }

        // Validate amount is not too large (check against available balance)
        const availableBalance = parseFloat(document.getElementById('availableBalance')?.textContent?.replace(/[^\d.]/g, '') || '0');
        const orderTotal = parseFloat(document.getElementById('orderTotal')?.value || '0');
        if (orderTotal > availableBalance && currentMode === 'real') {
            const errorMsg = `Insufficient balance. Available: ${availableBalance.toFixed(2)} USDT, Required: ${orderTotal.toFixed(2)} USDT. Please reduce the amount or deposit more funds.`;
            if (errorDiv) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            }
            if (amountInput) {
                amountInput.classList.add('is-invalid');
            }
            if (typeof toastr !== 'undefined') {
                toastr.error('Insufficient balance');
            } else {
                alert(errorMsg);
            }
            return;
        }

        // Get selected connection for real trading mode
        let connectionId = null;
        if (currentMode === 'real') {
            const connectionSelect = document.getElementById('exchangeConnectionSelect');
            if (connectionSelect) {
                connectionId = connectionSelect.value;
                if (!connectionId) {
                    // Show modal popup to add connection
                    showNoConnectionModal();
                    return;
                }
            } else {
                // No connection selector means no connections available
                showNoConnectionModal();
                return;
            }
        }

        const orderData = {
            symbol: currentSymbol,
            direction: direction,
            quantity: parseFloat(amount),
            sl_price: slPrice ? parseFloat(slPrice) : null,
            tp_price: tpPrice ? parseFloat(tpPrice) : null,
            mode: currentMode,
            connection_id: connectionId ? parseInt(connectionId) : null,
        };

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/user/terminal/order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Failed to place order');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Clear any errors
                    const errorDiv = document.getElementById('orderAmount-error');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                        errorDiv.textContent = '';
                    }
                    if (amountInput) {
                        amountInput.classList.remove('is-invalid');
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Order placed successfully');
                    } else {
                        alert(data.message || 'Order placed successfully');
                    }
                    document.getElementById('orderForm')?.reset();
                    loadPositions();
                } else {
                    // Display error message
                    const errorMsg = data.message || 'Failed to place order. Please check your inputs and try again.';
                    const errorDiv = document.getElementById('orderAmount-error');
                    if (errorDiv) {
                        errorDiv.textContent = errorMsg;
                        errorDiv.style.display = 'block';
                    }
                    if (amountInput) {
                        amountInput.classList.add('is-invalid');
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error placing order:', error);
                const errorMsg = error.message || 'Failed to place order. Please check your connection and try again.';
                const errorDiv = document.getElementById('orderAmount-error');
                if (errorDiv) {
                    errorDiv.textContent = errorMsg;
                    errorDiv.style.display = 'block';
                }
                if (amountInput) {
                    amountInput.classList.add('is-invalid');
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
            });
    }

    /**
     * Show modal popup when user tries to trade without connection
     */
    function showNoConnectionModal() {
        const modal = document.getElementById('noConnectionModal');
        if (modal) {
            // Try Bootstrap 5 first (if available)
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
            // Fallback to Bootstrap 4 / jQuery (most common)
            else if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                jQuery(modal).modal('show');
            }
            // Fallback: show alert with redirect option
            else {
                const createUrl = document.querySelector('#addConnectionBtn')?.href ||
                    document.querySelector('#noConnectionModal a.btn-primary')?.href;

                if (createUrl && confirm('No exchange connection available. Would you like to add an exchange connection now?')) {
                    window.location.href = createUrl;
                } else {
                    alert('No exchange connection available. Please add an exchange connection to trade with real funds.');
                }
            }
        } else {
            // Modal not found, show alert as fallback
            const createUrl = document.querySelector('#addConnectionBtn')?.href ||
                document.querySelector('a[href*="exchange-connections"]')?.href ||
                '/user/trading/operations?tab=connections';
            if (confirm('No exchange connection available. Would you like to add an exchange connection now?')) {
                window.location.href = createUrl;
            }
        }
    }

    function closePosition(positionId) {
        if (!confirm('Are you sure you want to close this position?')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch(`/terminal/position/${positionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }
                    loadPositions();
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message);
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error closing position:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to close position');
                } else {
                    alert('Failed to close position');
                }
            });
    }

    /**
     * Timeframe Selector
     */
    function initializeTimeframeSelector() {
        document.querySelectorAll('.tv-tf-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                currentInterval = this.dataset.interval;
                document.querySelectorAll('.tv-tf-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                loadCandlestickData();
            });
        });
    }

    /**
     * Tab Switching
     */
    function initializeTabSwitching() {
        document.querySelectorAll('.tv-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const tabName = this.dataset.tab;
                document.querySelectorAll('.tv-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                document.querySelectorAll('.tv-tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                const targetTab = document.getElementById(tabName + 'Tab');
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            });
        });
    }

    /**
     * Data Updates (Polling fallback - only used if WebSocket not available)
     */
    function startDataUpdates() {
        // Only poll if WebSocket is not available
        if (useWebSocketForPositions && useWebSocketForMarketData) {
            console.log('WebSocket active, skipping polling');
            return;
        }

        // Poll less frequently since WebSocket handles most updates
        updateInterval = setInterval(() => {
            if (currentMode === 'real') {
                // Only poll what's not available via WebSocket
                if (!useWebSocketForPositions) {
                    loadPositions();
                }
                if (!useWebSocketForMarketData) {
                    updatePrice();
                }
                // Orderbook is handled by Binance WebSocket, no need to poll
            }
        }, 10000); // Poll every 10 seconds as fallback (much less frequent)
    }

    /**
     * Quick Trade Overlay
     */
    window.fillOrder = function (direction) {
        // Pre-fill order form and submit
        const amount = document.getElementById('orderAmount');
        if (!amount || !amount.value) {
            // Default to min amount if empty
            if (amount) amount.value = 0.001;
            toastr.info('Default quantity set. Confirm order in panel.');
        }

        // Highlight order panel
        const orderPanel = document.querySelector('.tv-order-panel');
        orderPanel.style.borderColor = direction === 'buy' ? '#22c55e' : '#ef4444';
        setTimeout(() => orderPanel.style.borderColor = '', 1000);

        // Click the actual submit button to trigger existing logic
        const btn = document.querySelector(`button[data-direction="${direction}"]`);
        if (btn) btn.click();
    };

    /**
     * Mobile Trade Modal Logic
     */
    window.openMobileTrade = function (direction) {
        const modal = document.getElementById('mobileTradeModal');
        const title = document.getElementById('mobileModalTitle');
        const container = document.getElementById('mobileOrderFormContainer');
        const form = document.getElementById('orderForm');

        if (modal && form && container) {
            title.textContent = direction === 'buy' ? 'Buy ' + currentSymbol : 'Sell ' + currentSymbol;

            // Move form to modal
            container.appendChild(form);

            // Set direction style
            const btnBox = form.querySelector('.tv-order-actions');
            // Show only relevant button or style it? 
            // For simplicity, we keep both buttons but maybe highlight one?
            // Or better, just open the modal.

            modal.classList.add('active');
        }
    };

    window.closeMobileTrade = function () {
        const modal = document.getElementById('mobileTradeModal');
        const desktopContainer = document.querySelector('.tv-order-content'); // Original specific container
        const form = document.getElementById('orderForm');
        const indicator = document.getElementById('orderPanelMode'); // Insert after indicator

        if (modal && form && desktopContainer) {
            modal.classList.remove('active');

            // Move form back to desktop panel (after the mode indicator)
            if (indicator && indicator.nextSibling) {
                desktopContainer.insertBefore(form, indicator.nextSibling);
            } else {
                desktopContainer.appendChild(form);
            }
        }
    };
    function updatePrice() {
        if (!currentSymbol) return;

        // Fetch ticker manually if not socket logic
        fetch(`/terminal/market-data?symbol=${currentSymbol}&type=price`, {
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.price) {
                    const price = parseFloat(data.data.price);

                    // Update Main Header Price
                    const priceEl = document.getElementById('currentPrice');
                    if (priceEl) {
                        priceEl.textContent = price.toFixed(2);
                        // Add color class based on movement if we track it, for now just white/default
                    }

                    // Update Quick Trade Buttons
                    const quickBuy = document.getElementById('quickBuyPrice');
                    const quickSell = document.getElementById('quickSellPrice');
                    if (quickBuy) quickBuy.textContent = price.toFixed(2);
                    if (quickSell) quickSell.textContent = price.toFixed(2);

                    if (data.data.stats) {
                        const change = data.data.stats.priceChangePercent;
                        const changeEl = document.getElementById('priceChange');
                        if (changeEl) {
                            changeEl.textContent = (change >= 0 ? '+' : '') + parseFloat(change).toFixed(2) + '%';
                            changeEl.className = 'tv-change ' + (change >= 0 ? 'positive' : 'negative');
                        }
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // End of main logic, but continuing inside closure for depth chart


    // Depth Chart Implementation
    function initDepthChart() {
        const chartDom = document.getElementById('depthChart');
        if (!chartDom) {
            console.warn('Depth chart element not found');
            return;
        }

        // If already initialized, just resize and return
        if (depthChart) {
            console.log('Depth chart already initialized, resizing...');
            depthChart.resize();
            return;
        }

        console.log('Initializing depth chart for the first time...');
        // Initialize ECharts with renderer option to reduce warnings
        depthChart = echarts.init(chartDom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });
        const option = {
            animation: false,
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    label: {
                        backgroundColor: '#6a7985'
                    }
                },
                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                borderColor: '#374151',
                borderWidth: 1,
                textStyle: {
                    color: '#e5e7eb',
                    fontSize: 12
                },
                formatter: function (params) {
                    if (!params || params.length === 0) return '';
                    const price = params[0].name;
                    let result = `<div style="padding: 4px;">`;
                    result += `<div style="font-weight: bold; margin-bottom: 4px;">Price: ${parseFloat(price).toFixed(2)}</div>`;

                    params.forEach(param => {
                        if (param.value !== null) {
                            const color = param.seriesName === 'Bids' ? '#22c55e' : '#ef4444';
                            result += `<div style="color: ${color};">`;
                            result += `${param.seriesName}: ${parseFloat(param.value).toFixed(4)}`;
                            result += `</div>`;
                        }
                    });
                    result += `</div>`;
                    return result;
                }
            },
            grid: {
                left: 50,
                right: 20,
                top: 20,
                bottom: 40,
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: [],
                axisLabel: {
                    show: true,
                    color: '#9ca3af',
                    fontSize: 11,
                    formatter: function (value) {
                        return parseFloat(value).toFixed(2);
                    }
                },
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#374151'
                    }
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#1f2937',
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    show: true,
                    color: '#9ca3af',
                    fontSize: 11,
                    formatter: function (value) {
                        if (value >= 1000) {
                            return (value / 1000).toFixed(1) + 'K';
                        }
                        return value.toFixed(2);
                    }
                },
                axisLine: {
                    show: false
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#1f2937',
                        type: 'dashed'
                    }
                }
            },
            series: [
                {
                    name: 'Bids',
                    type: 'line',
                    symbol: 'none',
                    sampling: 'lttb',
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(34, 197, 94, 0.4)' },
                            { offset: 1, color: 'rgba(34, 197, 94, 0.05)' }
                        ])
                    },
                    lineStyle: {
                        width: 2,
                        color: '#22c55e'
                    },
                    data: []
                },
                {
                    name: 'Asks',
                    type: 'line',
                    symbol: 'none',
                    sampling: 'lttb',
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(239, 68, 68, 0.4)' },
                            { offset: 1, color: 'rgba(239, 68, 68, 0.05)' }
                        ])
                    },
                    lineStyle: {
                        width: 2,
                        color: '#ef4444'
                    },
                    data: []
                }
            ]
        };
        depthChart.setOption(option);
        console.log('Depth chart initialized successfully');

        // Handle window resize
        window.addEventListener('resize', () => {
            depthChart && depthChart.resize();
        });

        // Handle container resize with ResizeObserver for better responsiveness
        if (typeof ResizeObserver !== 'undefined') {
            const resizeObserver = new ResizeObserver(() => {
                if (depthChart) {
                    // Small delay to ensure container has settled
                    setTimeout(() => {
                        depthChart.resize();
                    }, 10);
                }
            });
            // Observe both the chart element and its parent containers
            resizeObserver.observe(chartDom);
            const parentContainer = chartDom.closest('.tv-tab-content');
            if (parentContainer) {
                resizeObserver.observe(parentContainer);
            }
            const panelContainer = chartDom.closest('.tv-depth-chart-panel, .tv-orderbook-depth-panel');
            if (panelContainer) {
                resizeObserver.observe(panelContainer);
            }
        }
    }

    function updateDepthChart(asks, bids, chartInstance = null) {
        // Target chart to update
        let targetChart = chartInstance || depthChart;

        // Check if depth chart is initialized
        if (!targetChart) {
            // Determine which element to look for
            // If we are in interact mode, we look for depthChart_interact
            const isInteract = (document.getElementById('interactLayoutContainer')?.style.display !== 'none');
            const elementId = isInteract ? 'depthChart_interact' : 'depthChart';
            const chartElement = document.getElementById(elementId);

            if (chartElement && typeof echarts !== 'undefined') {
                if (isInteract) {
                    // Initialize ECharts with renderer option to reduce warnings
                    targetChart = echarts.init(chartElement, null, {
                        renderer: 'canvas',
                        useDirtyRect: false
                    });
                    chartElement._interactChart = targetChart;
                } else {
                    initDepthChart();
                    targetChart = depthChart;
                }
            } else {
                return;
            }
        }

        if (!targetChart) return; // Still not initialized, skip

        // Normalize data format - handle array format [price, quantity] where both are strings
        const normalizeEntry = (entry) => {
            if (Array.isArray(entry)) {
                // Format: [price, quantity] as strings or numbers
                return {
                    price: parseFloat(entry[0]),
                    amount: parseFloat(entry[1])
                };
            } else if (typeof entry === 'object' && entry !== null) {
                // Format: {price: x, amount: y} or {price: x, quantity: y}
                return {
                    price: parseFloat(entry.price || entry[0] || 0),
                    amount: parseFloat(entry.amount || entry.quantity || entry[1] || 0)
                };
            }
            return { price: 0, amount: 0 };
        };

        // Normalize and filter valid entries
        let normalizedBids = (bids || []).map(normalizeEntry).filter(e => e.price > 0 && e.amount > 0);
        let normalizedAsks = (asks || []).map(normalizeEntry).filter(e => e.price > 0 && e.amount > 0);

        if (normalizedBids.length === 0 && normalizedAsks.length === 0) {
            console.warn('No valid depth chart data');
            return;
        }

        // Prepare data: Cumulative sum with bids and asks meeting in the middle
        // Bids: sorted high to low (best bid first), cumulative from best to worst
        // Asks: sorted low to high (best ask first), cumulative from best to worst
        const sortedBids = [...normalizedBids].sort((a, b) => b.price - a.price); // High to Low (best first)
        const sortedAsks = [...normalizedAsks].sort((a, b) => a.price - b.price); // Low to High (best first)

        // Calculate cumulative for bids (from highest price down)
        let bidCumulative = 0;
        const bidPoints = sortedBids.map(item => {
            bidCumulative += item.amount;
            return [item.price, bidCumulative];
        });

        // Calculate cumulative for asks (from lowest price up)
        let askCumulative = 0;
        const askPoints = sortedAsks.map(item => {
            askCumulative += item.amount;
            return [item.price, askCumulative];
        });

        // Reverse bids so they go from low to high for chart display
        // This makes the highest bid (best bid) appear at the right edge of the bid area
        bidPoints.reverse();

        // Combine: bids (low to high) + asks (low to high)
        // This creates a continuous price axis with bids on left, asks on right, meeting in middle
        const allPrices = [...bidPoints.map(p => p[0]), ...askPoints.map(p => p[0])];

        // Resize chart to ensure it fits container
        setTimeout(() => {
            if (targetChart) {
                targetChart.resize();
            }
        }, 100);

        // Update chart with proper configuration
        targetChart.setOption({
            grid: {
                left: 50,
                right: 20,
                top: 20,
                bottom: 40,
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: allPrices,
                axisLabel: {
                    show: true,
                    color: '#9ca3af',
                    fontSize: 11,
                    formatter: function (value) {
                        return parseFloat(value).toFixed(2);
                    }
                },
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#374151'
                    }
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#1f2937',
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    show: true,
                    color: '#9ca3af',
                    fontSize: 11,
                    formatter: function (value) {
                        if (value >= 1000) {
                            return (value / 1000).toFixed(1) + 'K';
                        }
                        return value.toFixed(2);
                    }
                },
                axisLine: {
                    show: false
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#1f2937',
                        type: 'dashed'
                    }
                }
            },
            series: [
                {
                    name: 'Bids',
                    type: 'line',
                    symbol: 'none',
                    sampling: 'lttb',
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(34, 197, 94, 0.4)' },
                            { offset: 1, color: 'rgba(34, 197, 94, 0.05)' }
                        ])
                    },
                    lineStyle: {
                        width: 2,
                        color: '#22c55e'
                    },
                    data: [...bidPoints.map(p => p[1]), ...Array(askPoints.length).fill(null)]
                },
                {
                    name: 'Asks',
                    type: 'line',
                    symbol: 'none',
                    sampling: 'lttb',
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(239, 68, 68, 0.4)' },
                            { offset: 1, color: 'rgba(239, 68, 68, 0.05)' }
                        ])
                    },
                    lineStyle: {
                        width: 2,
                        color: '#ef4444'
                    },
                    data: [...Array(bidPoints.length).fill(null), ...askPoints.map(p => p[1])]
                }
            ]
        }, { notMerge: false });
    }
})();
