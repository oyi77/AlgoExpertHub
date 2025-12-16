/**
 * Trading Terminal JavaScript
 * Handles chart rendering, orderbook updates, order placement, and position management
 */

(function () {
    'use strict';

    // State
    let currentMode = 'real'; // Start in real mode
    let currentSymbol = document.getElementById('selectedSymbol')?.textContent || 'BTCUSDT';
    let currentInterval = '5m';
    let chart = null;
    let candlestickSeries = null;
    let volumeSeries = null;
    let updateInterval = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        initializeSymbolSelector();
        initializeModeToggle();
        initializeOrderForm();
        initializeTabSwitching();
        initializeTimeframeSelector();

        // Auto-start real trading mode
        if (currentMode === 'real') {
            initializeRealTrading();
        }
    });

    /**
     * Symbol Selector
     */
    function initializeSymbolSelector() {
        const selectorBtn = document.getElementById('symbolSelectorBtn');
        const dropdown = document.getElementById('symbolDropdown');
        const searchInput = document.getElementById('symbolSearch');
        const symbolItems = document.querySelectorAll('.tv-symbol-item');

        if (!selectorBtn || !dropdown) return;

        // Toggle dropdown
        selectorBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
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

        // Search functionality
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase();
                symbolItems.forEach(item => {
                    const name = item.querySelector('.tv-symbol-name').textContent.toLowerCase();
                    const desc = item.querySelector('.tv-symbol-desc').textContent.toLowerCase();
                    const matches = name.includes(query) || desc.includes(query);
                    item.style.display = matches ? 'flex' : 'none';
                });
            });
        }

        // Symbol selection
        symbolItems.forEach(item => {
            item.addEventListener('click', function () {
                const newSymbol = this.dataset.symbol;
                if (newSymbol === currentSymbol) {
                    dropdown.classList.remove('active');
                    return;
                }

                // Update active state
                symbolItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // Update current symbol
                currentSymbol = newSymbol;
                document.getElementById('selectedSymbol').textContent = newSymbol;

                // Close dropdown
                dropdown.classList.remove('active');

                // Reload data if in real mode
                if (currentMode === 'real') {
                    // Re-init chart with new symbol
                    const container = document.getElementById('tradingview_chart');
                    if (container) container.innerHTML = ''; // Clear existing
                    chart = null; // Reset chart instance
                    initChart(); // Re-initialize

                    loadOrderbook();
                    loadPositions();
                    updatePrice();
                }
            });
        });
    }

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

        if (mode === 'real') {
            initializeRealTrading();
        } else {
            stopRealTrading();
        }
    }

    /**
     * Real Trading Initialization
     */
    function initializeRealTrading() {
        initChart();
        loadOrderbook();
        loadPositions();
        startDataUpdates();
    }

    function stopRealTrading() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    /**
     * TradingView Chart
     */
    /**
     * TradingView Chart Widget
     */
    function initChart() {
        if (chart) return; // Already initialized instance check, but we set chart=null before calling if reloading

        if (typeof TradingView === 'undefined') {
            setTimeout(initChart, 100);
            return;
        }

        // Clear container first just in case
        const container = document.getElementById('tradingview_chart');
        if (container) container.innerHTML = '';

        chart = new TradingView.widget({
            "width": "100%",
            "height": 600,
            "symbol": "BINANCE:" + currentSymbol,
            "interval": "5",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": "1",
            "locale": "en",
            "toolbar_bg": "#f1f3f6",
            "enable_publishing": false,
            "hide_side_toolbar": false,
            "allow_symbol_change": false, // We control symbol via our dropdown
            "container_id": "tradingview_chart",
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
     * Orderbook
     */
    function loadOrderbook() {
        fetch(`/terminal/market-data?symbol=${currentSymbol}&type=orderbook`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    renderOrderbook(data.data);
                }
            })
            .catch(error => console.error('Error loading orderbook:', error));
    }

    function updateOrderbook(data) {
        if (!data) return;

        // Helper to format rows with depth bars
        // Find max amount to calculate depth %
        let maxAmt = 0;
        if (data.asks) data.asks.forEach(a => maxAmt = Math.max(maxAmt, parseFloat(a[1])));
        if (data.bids) data.bids.forEach(b => maxAmt = Math.max(maxAmt, parseFloat(b[1])));

        // Render Asks (Red)
        const asksContainer = document.getElementById('orderbookAsks');
        if (asksContainer && data.asks) {
            // Take only last 15 asks (closest to price) for vertical spacing
            // Asks come as [Price, Qty]. Lowest price first.
            let asks = data.asks.slice(0, 15);

            asksContainer.innerHTML = asks.map(ask => {
                const price = typeof ask[0] === 'number' ? ask[0].toFixed(currentSymbol.includes('USDT') ? 2 : 6) : parseFloat(ask[0]).toFixed(currentSymbol.includes('USDT') ? 2 : 6);
                const amount = parseFloat(ask[1]).toFixed(4);
                const depthPct = Math.min((parseFloat(ask[1]) / maxAmt) * 100, 100);

                return `
                    <div class="tv-row" onclick="setPrice('${price}')" style="background: linear-gradient(to left, rgba(246, 70, 93, 0.15) ${depthPct}%, transparent ${depthPct}%)">
                        <span class="tv-ask-price">${price}</span>
                        <span class="tv-amount">${amount}</span>
                    </div>
                `;
            }).reverse().join(''); // Reverse so highest price is at top

            // Check spacing - Asks should be Lowest at Bottom (closest to spread)
            // If we reverse, Highest is Top. Correct.
        }

        // Render Bids (Green)
        const bidsContainer = document.getElementById('orderbookBids');
    }

    // Click to fill price
    document.querySelectorAll('.tv-orderbook-row').forEach(row => {
        row.addEventListener('click', function () {
            const price = this.dataset.price;
            const limitPriceInput = document.getElementById('limitPrice');
            if (limitPriceInput) {
                limitPriceInput.value = price;
            }
        });
    });
}

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
                limitPriceGroup.style.display = type === 'limit' ? 'block' : 'none';
            }
        });
    });
}

function placeOrder(direction) {
    const amount = document.getElementById('orderAmount')?.value;
    const slPrice = document.getElementById('stopLoss')?.value;
    const tpPrice = document.getElementById('takeProfit')?.value;

    if (!amount) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please enter amount');
        } else {
            alert('Please enter amount');
        }
        return;
    }

    const orderData = {
        symbol: currentSymbol,
        direction: direction,
        quantity: parseFloat(amount),
        sl_price: slPrice ? parseFloat(slPrice) : null,
        tp_price: tpPrice ? parseFloat(tpPrice) : null,
    };

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/terminal/order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message);
                } else {
                    alert(data.message);
                }
                document.getElementById('orderForm')?.reset();
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
            console.error('Error placing order:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to place order');
            } else {
                alert('Failed to place order');
            }
        });
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
 * Data Updates
 */
function startDataUpdates() {
    updateInterval = setInterval(() => {
        if (currentMode === 'real') {
            loadOrderbook();
            loadPositions();
            updatePrice();
        }
    }, 3000);
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
                 if(quickBuy) quickBuy.textContent = price.toFixed(2);
                 if(quickSell) quickSell.textContent = price.toFixed(2);

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

}) ();
