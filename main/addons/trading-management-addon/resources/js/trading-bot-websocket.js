/**
 * Trading Bot WebSocket Integration
 * 
 * Prerequisites:
 * 1. Include Laravel Echo and Pusher JS:
 *    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
 *    <script src="/vendor/laravel-echo/echo.iife.js"></script>
 * 
 * 2. Initialize Echo in your layout:
 *    window.Echo = new Echo({
 *        broadcaster: 'pusher',
 *        key: '{{ config("broadcasting.connections.pusher.key") }}',
 *        cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
 *        encrypted: true,
 *        authEndpoint: '/broadcasting/auth'
 *    });
 * 
 * 3. Use the TradingBotWebSocket class:
 *    const ws = new TradingBotWebSocket(botId, {
 *        onPositionUpdate: (data) => updatePositionsTable(data),
 *        onOrderExecuted: (data) => showOrderNotification(data),
 *        onStatusChange: (data) => updateBotStatus(data)
 *    });
 *    ws.connect();
 */

class TradingBotWebSocket {
    constructor(botId, callbacks = {}) {
        this.botId = botId;
        this.callbacks = {
            onPositionUpdate: callbacks.onPositionUpdate || (() => {}),
            onOrderExecuted: callbacks.onOrderExecuted || (() => {}),
            onStatusChange: callbacks.onStatusChange || (() => {}),
            onMarketData: callbacks.onMarketData || (() => {}),
            onError: callbacks.onError || console.error,
            onConnected: callbacks.onConnected || (() => {}),
        };
        this.channel = null;
        this.isAdmin = document.body.dataset.userType === 'admin';
        this.userId = document.body.dataset.userId;
    }

    /**
     * Connect to WebSocket channel
     */
    connect() {
        if (typeof window.Echo === 'undefined') {
            console.warn('Laravel Echo not initialized. WebSocket updates disabled.');
            console.warn('To enable: Set BROADCAST_DRIVER=pusher in .env and configure Pusher credentials.');
            return false;
        }

        try {
            // Choose channel based on user type
            const channelName = this.isAdmin 
                ? `admin.trading-bot.${this.botId}`
                : `user.${this.userId}.trading-bot.${this.botId}`;

            this.channel = window.Echo.private(channelName);

            // Listen for position updates
            this.channel.listen('.position.updated', (data) => {
                console.log('WebSocket: Position update received', data);
                this.callbacks.onPositionUpdate(data);
            });

            // Listen for order executions
            this.channel.listen('.order.executed', (data) => {
                console.log('WebSocket: Order executed', data);
                this.callbacks.onOrderExecuted(data);
            });

            // Listen for status changes
            this.channel.listen('.bot.status', (data) => {
                console.log('WebSocket: Status change', data);
                this.callbacks.onStatusChange(data);
            });

            this.callbacks.onConnected();
            console.log(`WebSocket: Connected to ${channelName}`);
            return true;

        } catch (error) {
            this.callbacks.onError(error);
            return false;
        }
    }

    /**
     * Subscribe to market data for a symbol
     */
    subscribeMarket(symbol) {
        if (typeof window.Echo === 'undefined') {
            return false;
        }

        try {
            // Market data is on a public channel
            const channel = window.Echo.channel(`market.${symbol}`);
            
            channel.listen('.market.updated', (data) => {
                this.callbacks.onMarketData(data);
            });

            console.log(`WebSocket: Subscribed to market.${symbol}`);
            return true;

        } catch (error) {
            this.callbacks.onError(error);
            return false;
        }
    }

    /**
     * Disconnect from WebSocket
     */
    disconnect() {
        if (this.channel && typeof window.Echo !== 'undefined') {
            const channelName = this.isAdmin 
                ? `admin.trading-bot.${this.botId}`
                : `user.${this.userId}.trading-bot.${this.botId}`;
            
            window.Echo.leave(channelName);
            this.channel = null;
            console.log('WebSocket: Disconnected');
        }
    }
}

/**
 * Helper function to update positions table from WebSocket data
 */
function updatePositionsFromWebSocket(data) {
    const { positions, stats } = data;

    // Update stats
    if (stats) {
        const statElements = {
            'total-open-positions': stats.total_open || 0,
            'overview-open-positions': stats.total_open || 0,
            'positions-at-risk': stats.positions_at_risk || 0,
            'positions-near-tp': stats.positions_near_tp || 0,
        };

        Object.entries(statElements).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });

        // Update PnL with color
        const pnlEl = document.getElementById('total-unrealized-pnl');
        if (pnlEl) {
            const pnl = stats.total_unrealized_pnl || 0;
            pnlEl.textContent = '$' + parseFloat(pnl).toFixed(2);
            pnlEl.className = pnl >= 0 ? 'text-success' : 'text-danger';
        }
    }

    // Update positions table
    const tbody = document.getElementById('positions-tbody');
    if (tbody && positions) {
        if (positions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">No open positions</td></tr>';
        } else {
            tbody.innerHTML = positions.map(p => `
                <tr>
                    <td>${p.symbol}</td>
                    <td><span class="badge bg-${p.direction === 'buy' ? 'success' : 'danger'}">${p.direction.toUpperCase()}</span></td>
                    <td>${parseFloat(p.entry_price).toFixed(8)}</td>
                    <td id="price-${p.id}">${parseFloat(p.current_price || p.entry_price).toFixed(8)}</td>
                    <td>${p.stop_loss ? parseFloat(p.stop_loss).toFixed(8) : 'N/A'}</td>
                    <td>${p.take_profit ? parseFloat(p.take_profit).toFixed(8) : 'N/A'}</td>
                    <td>${parseFloat(p.quantity).toFixed(8)}</td>
                    <td class="${(p.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-${p.id}">$${parseFloat(p.profit_loss || 0).toFixed(2)}</td>
                    <td class="${(p.profit_loss_percent || 0) >= 0 ? 'text-success' : 'text-danger'}" id="pnl-pct-${p.id}">${p.profit_loss_percent ? parseFloat(p.profit_loss_percent).toFixed(2) + '%' : '0%'}</td>
                    <td><span class="badge bg-success">${p.status.charAt(0).toUpperCase() + p.status.slice(1)}</span></td>
                    <td>${p.opened_at ? new Date(p.opened_at).toLocaleString() : 'N/A'}</td>
                </tr>
            `).join('');
        }
    }
}

/**
 * Initialize WebSocket with fallback to AJAX polling
 */
function initTradingBotUpdates(botId, options = {}) {
    const {
        pollInterval = 5000,
        positionsEndpoint = null,
        metricsEndpoint = null,
    } = options;

    let useWebSocket = false;
    let pollTimerId = null;

    // Try WebSocket first
    const ws = new TradingBotWebSocket(botId, {
        onPositionUpdate: (data) => {
            updatePositionsFromWebSocket(data);
        },
        onOrderExecuted: (data) => {
            // Show notification
            if (typeof toastr !== 'undefined') {
                toastr.info(`Order ${data.action}: ${data.order.symbol} ${data.order.direction}`);
            }
        },
        onStatusChange: (data) => {
            // Update status indicator
            const statusEl = document.getElementById('bot-status');
            if (statusEl) {
                statusEl.textContent = data.status;
                statusEl.className = `badge bg-${data.status === 'running' ? 'success' : 'secondary'}`;
            }
        },
        onConnected: () => {
            useWebSocket = true;
            console.log('Using WebSocket for real-time updates');
            
            // Stop AJAX polling if WebSocket connected
            if (pollTimerId) {
                clearInterval(pollTimerId);
                pollTimerId = null;
            }
        },
        onError: (error) => {
            console.warn('WebSocket error, falling back to AJAX polling:', error);
        }
    });

    const wsConnected = ws.connect();

    // Fallback to AJAX polling if WebSocket not available
    if (!wsConnected && positionsEndpoint) {
        console.log('WebSocket not available, using AJAX polling');
        
        pollTimerId = setInterval(() => {
            fetch(positionsEndpoint)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updatePositionsFromWebSocket({
                            positions: data.positions,
                            stats: data.stats
                        });
                    }
                })
                .catch(error => console.error('AJAX poll error:', error));
        }, pollInterval);
    }

    // Return cleanup function
    return () => {
        ws.disconnect();
        if (pollTimerId) {
            clearInterval(pollTimerId);
        }
    };
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TradingBotWebSocket, updatePositionsFromWebSocket, initTradingBotUpdates };
}

