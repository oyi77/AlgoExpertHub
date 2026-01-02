// Golden Layout Integration for Trading Terminal
(function () {
    'use strict';

    let layoutInstance = null;
    const LAYOUT_STORAGE_KEY = 'tradingTerminalLayout';
    const LAYOUT_VERSION_KEY = 'tradingTerminalLayoutVersion';
    const CURRENT_LAYOUT_VERSION = '2.0.0'; // Major increment to force reset for everyone

    /**
     * Initialize Golden Layout
     */
    function initGoldenLayout() {
        const container = document.getElementById('layoutContainer');
        if (!container) {
            console.warn('Layout container not found, using static layout');
            return null;
        }

        // Prevent duplicate initialization
        if (layoutInstance) {
            console.warn('Golden Layout already initialized, skipping...');
            return layoutInstance;
        }

        // Clear container to prevent duplicates
        container.innerHTML = '';

        // Check for version mismatch to force reset if needed
        const savedVersion = localStorage.getItem(LAYOUT_VERSION_KEY);
        if (savedVersion !== CURRENT_LAYOUT_VERSION) {
            console.warn('Layout version mismatch. Forcing reset to default...');
            localStorage.removeItem(LAYOUT_STORAGE_KEY);
            localStorage.setItem(LAYOUT_VERSION_KEY, CURRENT_LAYOUT_VERSION);
        }

        // Try to restore saved layout
        let savedConfig = getSavedLayout();

        // Validate and clean saved config
        if (savedConfig) {
            savedConfig = validateAndCleanConfig(savedConfig);
            if (!savedConfig) {
                // If validation failed, clear invalid saved layout
                localStorage.removeItem(LAYOUT_STORAGE_KEY);
                savedConfig = null;
            }
        }

        const config = savedConfig || getDefaultLayoutConfig();

        try {
            const layout = new GoldenLayout.GoldenLayout(config, container);

            // Register all components
            registerComponents(layout);

            // Initialize layout
            layout.init();

            // Save layout on state change
            layout.on('stateChanged', function () {
                saveLayout(layout);
            });

            return layout;
        } catch (error) {
            console.error('Failed to initialize Golden Layout:', error);

            // If error is related to configuration and we used saved config, try with default
            if (savedConfig && (error.message && (error.message.includes('size string') || error.message.includes('ConfigurationError')))) {
                console.warn('Saved layout appears corrupted, clearing and retrying with default...');
                localStorage.removeItem(LAYOUT_STORAGE_KEY);

                // Retry with default config
                try {
                    const defaultConfig = getDefaultLayoutConfig();
                    const layout = new GoldenLayout.GoldenLayout(defaultConfig, container);
                    registerComponents(layout);
                    layout.init();
                    layout.on('stateChanged', function () {
                        saveLayout(layout);
                    });
                    return layout;
                } catch (retryError) {
                    console.error('Failed to initialize with default config:', retryError);
                    return null;
                }
            }

            // Clear potentially corrupted saved layout
            localStorage.removeItem(LAYOUT_STORAGE_KEY);
            return null;
        }
    }

    /**
     * Validate and clean layout configuration
     */
    function validateAndCleanConfig(config) {
        if (!config || typeof config !== 'object') {
            return null;
        }

        try {
            // Recursively clean content items
            if (config.content && Array.isArray(config.content)) {
                config.content = config.content.map(item => cleanItemConfig(item)).filter(item => item !== null);
            }

            return config;
        } catch (error) {
            console.warn('Error validating config:', error);
            return null;
        }
    }

    /**
     * Clean individual item configuration
     */
    function cleanItemConfig(item) {
        if (!item || typeof item !== 'object') {
            return null;
        }

        // Remove invalid size values (must be string with unit or number for width/height)
        if (item.size !== undefined) {
            if (typeof item.size !== 'string' || !/^\d+(%|fr|px|em)$/.test(item.size)) {
                delete item.size;
            }
        }

        // Ensure width/height are numbers if present
        if (item.width !== undefined && (typeof item.width !== 'number' || isNaN(item.width))) {
            delete item.width;
        }
        if (item.height !== undefined && (typeof item.height !== 'number' || isNaN(item.height))) {
            delete item.height;
        }

        // Recursively clean content
        if (item.content && Array.isArray(item.content)) {
            item.content = item.content.map(child => cleanItemConfig(child)).filter(child => child !== null);
        }

        return item;
    }

    /**
     * Default layout configuration
     */
    function getDefaultLayoutConfig() {
        return {
            settings: {
                showPopoutIcon: true,
                showMaximiseIcon: true,
                showCloseIcon: false,
                reorderEnabled: true
            },
            dimensions: {
                borderWidth: 3,
                headerHeight: 32
            },
            content: [{
                type: 'row',
                content: [
                    {
                        type: 'component',
                        componentName: 'chartComponent',
                        title: 'Chart',
                        width: 60,
                        isClosable: false
                    },
                    {
                        type: 'component',
                        componentName: 'orderbookDepthComponent',
                        title: 'Order Book / Depth',
                        width: 20,
                        isClosable: false
                    },
                    {
                        type: 'component',
                        componentName: 'orderPanelComponent',
                        title: 'Trade',
                        width: 20,
                        isClosable: false
                    }
                ]
            }]
        };
    }

    /**
     * Register all components with Golden Layout
     */
    function registerComponents(layout) {
        // Chart Component
        layout.registerComponent('chartComponent', function (container, state) {
            const chartTemplate = document.getElementById('chartComponentTemplate');
            if (chartTemplate) {
                const content = chartTemplate.content.cloneNode(true);
                const element = container.getElement();
                element.appendChild(content);

                // Re-initialize TradingView chart - wait for container to be fully rendered
                // Use multiple retries to ensure TradingView is loaded and container exists
                let retryCount = 0;
                const maxRetries = 20;
                const initChartWithRetry = function() {
                    const chartContainer = document.getElementById('tradingview_chart');
                    if (chartContainer && typeof TradingView !== 'undefined' && window.initChart) {
                        try {
                            window.initChart();
                        } catch (e) {
                            console.error('Error initializing chart:', e);
                        }
                    } else if (retryCount < maxRetries) {
                        retryCount++;
                        setTimeout(initChartWithRetry, 200);
                    } else {
                        console.warn('Chart initialization failed after multiple retries');
                    }
                };
                setTimeout(initChartWithRetry, 300);
            }
        });

        // Orderbook & Depth Combined Component
        layout.registerComponent('orderbookDepthComponent', function (container, state) {
            const template = document.getElementById('orderbookDepthComponentTemplate');
            if (template) {
                const content = template.content.cloneNode(true);
                const element = container.getElement();
                element.appendChild(content);

                // Initialize tab switching
                const tabButtons = element.querySelectorAll('.tv-panel-tab');
                const tabContents = element.querySelectorAll('.tv-tab-content');

                tabButtons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const targetTab = this.dataset.tab;

                        // Update active states
                        tabButtons.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');

                        tabContents.forEach(content => {
                            if (content.id === targetTab + 'Tab') {
                                content.style.display = 'flex';
                                content.classList.add('active');
                            } else {
                                content.style.display = 'none';
                                content.classList.remove('active');
                            }
                        });

                        // Initialize components when tab is shown
                        if (targetTab === 'orderbook') {
                            setTimeout(() => {
                                if (window.connectWebSocket) {
                                    window.connectWebSocket();
                                }
                            }, 100);
                        } else if (targetTab === 'depth') {
                            setTimeout(() => {
                                if (window.initDepthChart) {
                                    window.initDepthChart();
                                }
                            }, 100);
                        }
                    });
                });

                // Initialize orderbook by default - load REST first for instant display
                setTimeout(() => {
                    // Load orderbook data immediately via REST
                    if (window.loadOrderbookREST) {
                        window.loadOrderbookREST();
                    } else if (window.loadOrderbook) {
                        window.loadOrderbook();
                    }
                    // Then connect WebSocket for real-time updates
                    if (window.connectWebSocket) {
                        window.connectWebSocket();
                    }
                }, 100);

                // Also initialize depth chart proactively (even if tab is not active)
                // This ensures it's ready when user switches to depth tab
                // Wait for depth chart container to exist and ECharts to be loaded
                let depthRetryCount = 0;
                const maxDepthRetries = 15;
                const initDepthChartWithRetry = function() {
                    const depthContainer = document.getElementById('depthChart');
                    if (depthContainer && typeof echarts !== 'undefined' && window.initDepthChart) {
                        try {
                            window.initDepthChart();
                            console.log('Depth chart proactively initialized');
                        } catch (e) {
                            console.error('Error initializing depth chart:', e);
                        }
                    } else if (depthRetryCount < maxDepthRetries) {
                        depthRetryCount++;
                        setTimeout(initDepthChartWithRetry, 200);
                    }
                };
                setTimeout(initDepthChartWithRetry, 300);
            }
        });

        // Order Panel Component
        layout.registerComponent('orderPanelComponent', function (container, state) {
            const template = document.getElementById('orderPanelComponentTemplate');
            if (template) {
                const content = template.content.cloneNode(true);
                const element = container.getElement();
                element.appendChild(content);

                // Re-initialize order form
                setTimeout(() => {
                    if (window.initializeOrderForm) {
                        window.initializeOrderForm();
                    }

                    // Update mode indicator to reflect current mode
                    // This is needed because the template has server-rendered mode text
                    const orderPanelMode = element.querySelector('#orderPanelMode');
                    const orderPanelModeText = element.querySelector('#orderPanelModeText');
                    if (orderPanelMode && orderPanelModeText && window.currentMode) {
                        const mode = window.currentMode;
                        orderPanelMode.className = 'tv-order-mode-indicator ' + (mode === 'demo' ? 'demo' : 'real');
                        const demoText = orderPanelMode.dataset.demoText || 'Demo Mode';
                        const realText = orderPanelMode.dataset.realText || 'Real Trading';
                        orderPanelModeText.textContent = mode === 'demo' ? demoText : realText;
                    }
                }, 100);
            }
        });
    }

    /**
     * Save layout configuration to localStorage
     */
    function saveLayout(layout) {
        try {
            const config = layout.toConfig();
            localStorage.setItem(LAYOUT_STORAGE_KEY, JSON.stringify(config));
        } catch (error) {
            console.warn('Failed to save layout:', error);
        }
    }

    /**
     * Get saved layout from localStorage
     */
    function getSavedLayout() {
        try {
            const saved = localStorage.getItem(LAYOUT_STORAGE_KEY);
            if (!saved) {
                return null;
            }
            const parsed = JSON.parse(saved);
            // Additional validation
            if (!parsed || typeof parsed !== 'object') {
                return null;
            }
            return parsed;
        } catch (error) {
            console.warn('Failed to restore layout:', error);
            // Clear corrupted data
            localStorage.removeItem(LAYOUT_STORAGE_KEY);
            return null;
        }
    }

    /**
     * Reset to default layout
     */
    window.resetTradingLayout = function () {
        if (confirm('Reset layout to default? This will reload the page.')) {
            localStorage.removeItem(LAYOUT_STORAGE_KEY);
            location.reload();
        }
    };

    // Expose initGoldenLayout globally for layout manager
    window.initGoldenLayout = initGoldenLayout;

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function () {
        // Retry logic for Golden Layout loading
        let attempts = 0;
        const maxAttempts = 20; // 2 seconds total

        function checkAndInit() {
            if (typeof GoldenLayout !== 'undefined') {
                layoutInstance = initGoldenLayout();
                window.tradingLayout = layoutInstance;
                return;
            }

            attempts++;
            if (attempts < maxAttempts) {
                setTimeout(checkAndInit, 100);
            } else {
                console.warn('Golden Layout not loaded after timeout, using static layout');
            }
        }

        checkAndInit();
    });

    // Handle window resize
    window.addEventListener('resize', function () {
        if (layoutInstance) {
            layoutInstance.updateSize();
        }
    });

})();
