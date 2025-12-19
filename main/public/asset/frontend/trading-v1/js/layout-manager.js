/**
 * Layout Manager - Switch between Golden Layout and Interact.js
 */
(function () {
    'use strict';

    let currentLayout = localStorage.getItem('tradingTerminalLayoutType') || 'interactjs';
    let goldenLayoutInstance = null;
    let interactLayoutInitialized = false;

    /**
     * Initialize layout system
     */
    function init() {
        // Setup settings popup
        setupSettingsPopup();

        // Setup layout toggle buttons
        setupLayoutToggle();

        // Initialize the selected layout
        switchLayout(currentLayout, false);
    }

    /**
     * Setup settings popup
     */
    function setupSettingsPopup() {
        const settingsBtn = document.getElementById('tvSettingsBtn');
        const settingsPopup = document.getElementById('tvSettingsPopup');
        const settingsClose = document.getElementById('tvSettingsClose');

        if (settingsBtn && settingsPopup) {
            settingsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                settingsPopup.classList.toggle('active');
            });

            if (settingsClose) {
                settingsClose.addEventListener('click', function () {
                    settingsPopup.classList.remove('active');
                });
            }

            // Close popup when clicking outside
            document.addEventListener('click', function (e) {
                if (settingsPopup && !settingsPopup.contains(e.target) && !settingsBtn.contains(e.target)) {
                    settingsPopup.classList.remove('active');
                }
            });
        }
    }

    /**
     * Setup layout toggle buttons
     */
    function setupLayoutToggle() {
        const toggleButtons = document.querySelectorAll('.tv-layout-btn');
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const layoutType = this.dataset.layout;
                switchLayout(layoutType, true);
                // Close popup after selection
                const popup = document.getElementById('tvSettingsPopup');
                if (popup) {
                    popup.classList.remove('active');
                }
            });
        });
    }

    /**
     * Switch between layout systems
     */
    function switchLayout(layoutType, savePreference = true) {
        const goldenContainer = document.getElementById('layoutContainer');
        const interactContainer = document.getElementById('interactLayoutContainer');
        const toggleButtons = document.querySelectorAll('.tv-layout-btn');

        // Update button states
        toggleButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.layout === layoutType);
        });

        if (layoutType === 'goldenlayout') {
            // Show Golden Layout
            if (goldenContainer) {
                goldenContainer.style.display = 'block';
                goldenContainer.classList.add('tv-layout-goldenlayout');
            }
            if (interactContainer) {
                interactContainer.style.display = 'none';
            }

            // Initialize Golden Layout if not already done
            if (!goldenLayoutInstance && typeof window.initGoldenLayout === 'function') {
                goldenLayoutInstance = window.initGoldenLayout();
            }

            // Update size to ensure proper rendering after switching
            if (goldenLayoutInstance && typeof goldenLayoutInstance.updateSize === 'function') {
                setTimeout(() => {
                    goldenLayoutInstance.updateSize();
                    console.log('Golden Layout size updated after switch');
                }, 100);
            }

            // Reconnect WebSocket for orderbook updates
            if (window.connectWebSocket) {
                setTimeout(() => {
                    window.connectWebSocket();
                }, 200);
            }
        } else if (layoutType === 'interactjs') {
            // Show Interact.js Layout
            if (goldenContainer) {
                goldenContainer.style.display = 'none';
            }
            if (interactContainer) {
                interactContainer.style.display = 'block';
                interactContainer.classList.add('tv-layout-interactjs');
            }

            // Initialize Interact.js layout if not already done
            if (!interactLayoutInitialized && typeof interact !== 'undefined') {
                initInteractLayout();
                interactLayoutInitialized = true;
            }

            // Reconnect WebSocket for orderbook updates
            if (window.connectWebSocket) {
                setTimeout(() => {
                    window.connectWebSocket();
                }, 200);
            }
        }

        // Save preference
        if (savePreference) {
            localStorage.setItem('tradingTerminalLayoutType', layoutType);
            currentLayout = layoutType;
        }
    }

    /**
     * Initialize Interact.js layout
     */
    function initInteractLayout() {
        const container = document.getElementById('interactLayoutContainer');
        if (!container || typeof interact === 'undefined') {
            console.error('Interact.js not loaded or container not found');
            return;
        }

        // Clear container first
        container.innerHTML = '';

        // Get templates
        const chartTemplate = document.getElementById('chartComponentTemplate');
        const orderbookTemplate = document.getElementById('orderbookDepthComponentTemplate');
        const orderTemplate = document.getElementById('orderPanelComponentTemplate');

        // Create layout structure
        const layoutDiv = document.createElement('div');
        layoutDiv.className = 'tv-interact-layout';

        // Chart Panel
        if (chartTemplate) {
            const chartPanel = document.createElement('div');
            chartPanel.className = 'tv-interact-panel tv-interact-chart';
            chartPanel.id = 'interactChartPanel';
            chartPanel.innerHTML = `
                <div class="tv-interact-panel-header">
                    <span>Chart</span>
                    <button class="tv-interact-close" onclick="removePanel('interactChartPanel')">×</button>
                </div>
                <div class="tv-interact-panel-content">
                    <div id="tradingview_chart_interact" class="tv-chart-container" style="height: 100%; width: 100%;"></div>
                </div>
            `;
            layoutDiv.appendChild(chartPanel);
        }

        // Orderbook Panel
        if (orderbookTemplate) {
            const orderbookPanel = document.createElement('div');
            orderbookPanel.className = 'tv-interact-panel tv-interact-orderbook';
            orderbookPanel.id = 'interactOrderbookPanel';
            orderbookPanel.innerHTML = `
                <div class="tv-interact-panel-header">
                    <span>Order Book / Depth</span>
                    <button class="tv-interact-close" onclick="removePanel('interactOrderbookPanel')">×</button>
                </div>
                <div class="tv-interact-panel-content">
                    <div id="orderbookDepthComponent_interact"></div>
                </div>
            `;
            layoutDiv.appendChild(orderbookPanel);
        }

        // Order Panel
        if (orderTemplate) {
            const orderPanel = document.createElement('div');
            orderPanel.className = 'tv-interact-panel tv-interact-order';
            orderPanel.id = 'interactOrderPanel';
            orderPanel.innerHTML = `
                <div class="tv-interact-panel-header">
                    <span>Trade</span>
                    <button class="tv-interact-close" onclick="removePanel('interactOrderPanel')">×</button>
                </div>
                <div class="tv-interact-panel-content">
                    <div id="orderPanelComponent_interact"></div>
                </div>
            `;
            layoutDiv.appendChild(orderPanel);
        }

        container.appendChild(layoutDiv);

        // Clone templates into panels
        if (orderbookTemplate) {
            const target = document.getElementById('orderbookDepthComponent_interact');
            if (target) {
                const content = orderbookTemplate.content.cloneNode(true);

                // Make IDs unique for interact layout
                const orderbookPanel = content.querySelector('.tv-orderbook-depth-panel');
                if (orderbookPanel) {
                    // Update IDs to be unique
                    const asksEl = orderbookPanel.querySelector('#orderbookAsks');
                    const bidsEl = orderbookPanel.querySelector('#orderbookBids');
                    const midPriceEl = orderbookPanel.querySelector('#midPrice');
                    const spreadEl = orderbookPanel.querySelector('#orderbookSpread');
                    const orderbookTab = orderbookPanel.querySelector('#orderbookTab');
                    const depthTab = orderbookPanel.querySelector('#depthTab');

                    if (asksEl) asksEl.id = 'orderbookAsks_interact';
                    if (bidsEl) bidsEl.id = 'orderbookBids_interact';
                    if (midPriceEl) midPriceEl.id = 'midPrice_interact';
                    if (spreadEl) spreadEl.id = 'orderbookSpread_interact';
                    if (orderbookTab) orderbookTab.id = 'orderbookTab_interact';
                    if (depthTab) depthTab.id = 'depthTab_interact';

                    // Also update depth chart ID
                    const depthChartEl = orderbookPanel.querySelector('#depthChart');
                    if (depthChartEl) depthChartEl.id = 'depthChart_interact';
                }

                target.appendChild(content);

                // Initialize tab switching for orderbook
                setTimeout(() => {
                    const element = target.querySelector('.tv-orderbook-depth-panel');
                    if (element) {
                        const tabButtons = element.querySelectorAll('.tv-panel-tab');
                        const tabContents = element.querySelectorAll('.tv-tab-content');

                        tabButtons.forEach(btn => {
                            btn.addEventListener('click', function () {
                                const targetTab = this.dataset.tab;
                                tabButtons.forEach(b => b.classList.remove('active'));
                                this.classList.add('active');
                                tabContents.forEach(content => {
                                    if (content.id === targetTab + 'Tab_interact') {
                                        content.style.display = 'flex';
                                        content.classList.add('active');

                                        // If switching to depth tab, resize the chart
                                        if (targetTab === 'depth') {
                                            setTimeout(() => {
                                                const depthChartEl = document.getElementById('depthChart_interact');
                                                if (depthChartEl && depthChartEl._interactChart) {
                                                    // Force resize multiple times to ensure it works
                                                    depthChartEl._interactChart.resize();
                                                    setTimeout(() => {
                                                        depthChartEl._interactChart.resize();
                                                    }, 50);
                                                    setTimeout(() => {
                                                        depthChartEl._interactChart.resize();
                                                    }, 200);
                                                }
                                            }, 50);
                                        }
                                    } else {
                                        content.style.display = 'none';
                                        content.classList.remove('active');
                                    }
                                });
                            });
                        });
                    }
                }, 50);
            }
        }

        if (orderTemplate) {
            const target = document.getElementById('orderPanelComponent_interact');
            if (target) {
                const content = orderTemplate.content.cloneNode(true);
                target.appendChild(content);
            }
        }

        // Initialize chart for interact layout
        setTimeout(() => {
            const chartContainer = document.getElementById('tradingview_chart_interact');
            if (chartContainer && typeof TradingView !== 'undefined') {
                // Initialize TradingView widget directly for interact layout
                try {
                    const symbol = (typeof window.currentSymbol === 'string' ? window.currentSymbol : (window.currentSymbol || 'BTCUSDT'));
                    new TradingView.widget({
                        "width": "100%",
                        "height": "100%",
                        "symbol": "BINANCE:" + symbol,
                        "interval": "5",
                        "timezone": "Etc/UTC",
                        "theme": "dark",
                        "style": "1",
                        "locale": "en",
                        "toolbar_bg": "#f1f3f6",
                        "enable_publishing": false,
                        "hide_side_toolbar": false,
                        "allow_symbol_change": false,
                        "container_id": "tradingview_chart_interact",
                        "disabled_features": [
                            "use_localstorage_for_settings",
                            "volume_force_overlay"
                        ],
                        "overrides": {
                            "paneProperties.background": "#0a0e1a",
                            "paneProperties.vertGridProperties.color": "rgba(255, 255, 255, 0.05)",
                            "paneProperties.horzGridProperties.color": "rgba(255, 255, 255, 0.05)",
                            "scalesProperties.textColor": "#d1d4dc"
                        }
                    });
                    console.log('Chart initialized for interact layout');
                } catch (error) {
                    console.error('Failed to initialize chart for interact layout:', error);
                }
            } else if (chartContainer && window.initChart) {
                // Fallback to window.initChart if TradingView not available
                const originalContainer = document.getElementById('tradingview_chart');
                if (originalContainer) {
                    const tempId = originalContainer.id;
                    originalContainer.id = 'tradingview_chart_backup';
                    chartContainer.id = 'tradingview_chart';
                    window.initChart();
                    chartContainer.id = 'tradingview_chart_interact';
                    originalContainer.id = tempId;
                }
            }
        }, 300);

        // Initialize orderbook for interact layout
        setTimeout(() => {
            // Create a custom orderbook loader for interact layout
            const asksContainer = document.getElementById('orderbookAsks_interact');
            const bidsContainer = document.getElementById('orderbookBids_interact');
            const midPriceEl = document.getElementById('midPrice_interact');
            const spreadEl = document.getElementById('orderbookSpread_interact');
            const depthChartEl = document.getElementById('depthChart_interact');

            if (asksContainer && bidsContainer && window.currentSymbol) {
                // Show loading state
                asksContainer.innerHTML = '<div style="padding: 1rem; text-align: center; color: #848e9c;">Loading...</div>';
                bidsContainer.innerHTML = '';

                // Load orderbook data
                const symbol = (typeof window.currentSymbol === 'string' ? window.currentSymbol : (window.currentSymbol || 'BTCUSDT'));
                fetch(`/terminal/market-data?symbol=${symbol}&type=orderbook`, {
                    credentials: 'same-origin'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            // Use the updateOrderbook function but with interact IDs
                            if (window.updateOrderbook) {
                                // Temporarily swap IDs for all orderbook elements
                                const originalAsks = document.getElementById('orderbookAsks');
                                const originalBids = document.getElementById('orderbookBids');
                                const originalMidPrice = document.getElementById('midPrice');
                                const originalSpread = document.getElementById('orderbookSpread');
                                const originalDepthChart = document.getElementById('depthChart');

                                // Backup original IDs
                                if (originalAsks) originalAsks.id = 'orderbookAsks_backup';
                                if (originalBids) originalBids.id = 'orderbookBids_backup';
                                if (originalMidPrice) originalMidPrice.id = 'midPrice_backup';
                                if (originalSpread) originalSpread.id = 'orderbookSpread_backup';
                                if (originalDepthChart) originalDepthChart.id = 'depthChart_backup';

                                // Swap to interact IDs
                                asksContainer.id = 'orderbookAsks';
                                bidsContainer.id = 'orderbookBids';
                                if (midPriceEl) midPriceEl.id = 'midPrice';
                                if (spreadEl) spreadEl.id = 'orderbookSpread';
                                if (depthChartEl) depthChartEl.id = 'depthChart';

                                // Update orderbook (this will update mid price, spread)
                                window.updateOrderbook(data.data);

                                // Update depth chart for interact layout separately using main logic
                                if (depthChartEl && depthChartEl._interactChart && data.data.asks && data.data.bids && window.updateDepthChart) {
                                    const asks = data.data.asks.map(a => Array.isArray(a) ? { price: parseFloat(a[0]), amount: parseFloat(a[1]) } : a);
                                    const bids = data.data.bids.map(b => Array.isArray(b) ? { price: parseFloat(b[0]), amount: parseFloat(b[1]) } : b);
                                    window.updateDepthChart(asks, bids, depthChartEl._interactChart);
                                }

                                // Restore IDs
                                asksContainer.id = 'orderbookAsks_interact';
                                bidsContainer.id = 'orderbookBids_interact';
                                if (midPriceEl) midPriceEl.id = 'midPrice_interact';
                                if (spreadEl) spreadEl.id = 'orderbookSpread_interact';
                                if (depthChartEl) depthChartEl.id = 'depthChart_interact';

                                // Restore original IDs
                                if (originalAsks) originalAsks.id = 'orderbookAsks';
                                if (originalBids) originalBids.id = 'orderbookBids';
                                if (originalMidPrice) originalMidPrice.id = 'midPrice';
                                if (originalSpread) originalSpread.id = 'orderbookSpread';
                                if (originalDepthChart) originalDepthChart.id = 'depthChart';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading orderbook for interact layout:', error);
                    });
            }

            // Initialize depth chart for interact layout
            if (depthChartEl && typeof echarts !== 'undefined') {
                setTimeout(() => {
                    // Initialize depth chart directly for interact layout
                    try {
                        if (echarts.getInstanceByDom(depthChartEl)) {
                            echarts.dispose(depthChartEl);
                        }
                        // Initialize ECharts with renderer option to reduce warnings
                        const interactDepthChart = echarts.init(depthChartEl, null, {
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
                        interactDepthChart.setOption(option);

                        // Store reference for updates
                        depthChartEl._interactChart = interactDepthChart;

                        // Force initial resize after a short delay to ensure container is sized
                        // Multiple resize attempts to handle container sizing
                        setTimeout(() => {
                            if (interactDepthChart) {
                                interactDepthChart.resize();
                            }
                        }, 100);
                        setTimeout(() => {
                            if (interactDepthChart) {
                                interactDepthChart.resize();
                            }
                        }, 300);
                        setTimeout(() => {
                            if (interactDepthChart) {
                                interactDepthChart.resize();
                            }
                        }, 500);

                        // Handle window resize
                        window.addEventListener('resize', () => {
                            if (interactDepthChart) {
                                interactDepthChart.resize();
                            }
                        });

                        // Handle container resize with ResizeObserver for better responsiveness
                        if (typeof ResizeObserver !== 'undefined') {
                            const resizeObserver = new ResizeObserver((entries) => {
                                if (interactDepthChart) {
                                    // Check if the element actually has dimensions
                                    for (const entry of entries) {
                                        if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
                                            // Small delay to ensure container has settled
                                            setTimeout(() => {
                                                interactDepthChart.resize();
                                            }, 10);
                                            break;
                                        }
                                    }
                                }
                            });
                            // Observe both the chart element and its parent containers
                            resizeObserver.observe(depthChartEl);
                            const parentContainer = depthChartEl.closest('.tv-tab-content');
                            if (parentContainer) {
                                resizeObserver.observe(parentContainer);
                            }
                            const panelContainer = depthChartEl.closest('.tv-interact-panel');
                            if (panelContainer) {
                                resizeObserver.observe(panelContainer);
                            }
                            const panelContent = depthChartEl.closest('.tv-interact-panel-content');
                            if (panelContent) {
                                resizeObserver.observe(panelContent);
                            }
                        }
                        
                        // Also watch for visibility changes using MutationObserver
                        if (typeof MutationObserver !== 'undefined') {
                            const mutationObserver = new MutationObserver((mutations) => {
                                mutations.forEach((mutation) => {
                                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                                        const target = mutation.target;
                                        if (target === depthChartEl || target.contains(depthChartEl) || depthChartEl.closest('.tv-tab-content') === target) {
                                            const computedStyle = window.getComputedStyle(depthChartEl);
                                            if (computedStyle.display !== 'none' && computedStyle.visibility !== 'hidden') {
                                                setTimeout(() => {
                                                    if (interactDepthChart) {
                                                        interactDepthChart.resize();
                                                    }
                                                }, 50);
                                            }
                                        }
                                    }
                                });
                            });
                            const depthTab = depthChartEl.closest('.tv-tab-content');
                            if (depthTab) {
                                mutationObserver.observe(depthTab, {
                                    attributes: true,
                                    attributeFilter: ['style', 'class']
                                });
                            }
                        }

                        console.log('Depth chart initialized for interact layout');
                    } catch (error) {
                        console.error('Failed to initialize depth chart for interact layout:', error);
                    }
                }, 500);
            }
        }, 400);

        // Initialize order form
        setTimeout(() => {
            if (window.initializeOrderForm) {
                window.initializeOrderForm();
            }
        }, 300);

        // Make panels draggable and resizable
        setTimeout(() => {
            const panels = container.querySelectorAll('.tv-interact-panel');
            panels.forEach(panel => {
                makePanelDraggable(panel);
                makePanelResizable(panel);
            });
        }, 400);
    }

    /**
     * Clone template content
     */
    function cloneTemplate(templateId, targetId) {
        const template = document.getElementById(templateId);
        const target = document.getElementById(targetId);
        if (template && target) {
            const content = template.content.cloneNode(true);
            target.appendChild(content);
        }
    }

    /**
     * Make panel draggable
     */
    function makePanelDraggable(panel) {
        if (typeof interact === 'undefined') return;

        const header = panel.querySelector('.tv-interact-panel-header');
        if (!header) return;

        interact(panel)
            .draggable({
                allowFrom: header,
                listeners: {
                    move: dragMoveListener
                },
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: 'parent',
                        endOnly: true
                    })
                ],
                inertia: true
            });
    }

    /**
     * Make panel resizable
     */
    function makePanelResizable(panel) {
        if (typeof interact === 'undefined') return;

        interact(panel)
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move: resizeMoveListener
                },
                modifiers: [
                    interact.modifiers.restrictEdges({
                        outer: 'parent'
                    }),
                    interact.modifiers.restrictSize({
                        min: { width: 200, height: 150 }
                    })
                ],
                inertia: true
            });
    }

    /**
     * Drag move listener
     */
    function dragMoveListener(event) {
        const target = event.target;
        const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
        const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

        target.style.transform = `translate(${x}px, ${y}px)`;
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    }

    /**
     * Resize move listener
     */
    function resizeMoveListener(event) {
        const target = event.target;
        let x = (parseFloat(target.getAttribute('data-x')) || 0);
        let y = (parseFloat(target.getAttribute('data-y')) || 0);

        target.style.width = event.rect.width + 'px';
        target.style.height = event.rect.height + 'px';

        x += event.deltaRect.left;
        y += event.deltaRect.top;

        target.style.transform = `translate(${x}px, ${y}px)`;
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    }

    /**
     * Remove panel
     */
    window.removePanel = function (panelId) {
        const panel = document.getElementById(panelId);
        if (panel) {
            panel.remove();
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose switchLayout globally
    window.switchLayout = switchLayout;
    window.currentLayoutType = () => currentLayout;

})();

