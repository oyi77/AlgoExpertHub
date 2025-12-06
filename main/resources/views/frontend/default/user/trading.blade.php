@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="sp_site_card">
                <div class="card-header d-flex flex-wrap justify-content-between">
                    <div class="radio_button_list">
                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-1" name="currency"
                                value="BTC" checked>
                            <label class="form-check-label" for="trad-1">
                                {{ __('BTC') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-2" name="currency"
                                value="ETH">
                            <label class="form-check-label" for="trad-2">
                                {{ __('ETH') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-3" name="currency"
                                value="USDT">
                            <label class="form-check-label" for="trad-3">
                                {{ __('USDT') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-4" name="currency"
                                value="BNB">
                            <label class="form-check-label" for="trad-4">
                                {{ __('BNB') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-5" name="currency"
                                value="DOGE">
                            <label class="form-check-label" for="trad-5">
                                {{ __('DOGE') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-6" name="currency"
                                value="LTC">
                            <label class="form-check-label" for="trad-6">
                                {{ __('LTC') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-7" name="currency"
                                value="DASH">
                            <label class="form-check-label" for="trad-7">
                                {{ __('DASH') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-8" name="currency"
                                value="ETC">
                            <label class="form-check-label" for="trad-8">
                                {{ __('ETC') }}
                            </label>
                        </div>

                        <div class="sp_site_radio">
                            <input type="radio" class="form-check-input currency" id="trad-9" name="currency"
                                value="BCH">
                            <label class="form-check-label" for="trad-9">
                                {{ __('BCH') }}
                            </label>
                        </div>
                    </div>

                    <div>
                        <button class="btn sp_theme_btn order">{{ __('Place Order') }}</button>
                    </div>
                </div>
                <div class="sp_card_body">
                    <div id="linechart"></div>
                </div>

            </div>
        </div>
    </div>


    <div class="row">

        <script>
            'use strict'


            function firePayment(elementId) {
                $.ajax({
                    url: "{{ route('user.tradeClose') }}",
                    method: "GET",
                    success: function(response) {
                        if (response) {
                            document.getElementById(elementId).innerHTML = "COMPLETE";
                            return
                        }

                        window.location.href = "{{ url()->current() }}"
                    }
                })
            }

            function getCountDown(elementId, seconds) {
                var times = seconds;

                var x = setInterval(function() {
                    var distance = times * 1000;

                    if (distance < 0) {
                        clearInterval(x);
                        firePayment(elementId);
                        return
                    }
                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    document.getElementById(elementId).innerHTML = days + "d " + hours + "h " + minutes + "m " +
                        seconds + "s ";
                    times--;
                }, 1000);
            }
        </script>
        <div class="col-md-12 mt-4">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="card-header-items">
                        <h5 class="card-header-item">{{ __('Current Balance') }} :
                            {{ Config::formatter(auth()->user()->balance) }}</h5>
                        <form action="" method="get" class="row justify-content-md-end g-3 card-header-item">
                            <div class="col-auto">
                                <input type="text" name="trx" class="form-control me-2"
                                    placeholder="transaction id">
                            </div>
                            <div class="col-auto">
                                <input type="date" class="form-control me-3" 
                                    name="date">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn sp_theme_btn">{{ __('Search') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table sp_site_table">
                            <thead>
                                <tr>
                                    <th>{{ __('Ref') }}</th>
                                    <th>{{ __('Currency Sym') }}</th>
                                    <th>{{ __('Trade Price At') }}</th>
                                    <th>{{ __('Trade Type') }}</th>
                                    <th>{{ __('Trade Close At') }}</th>
                                    <th>{{ __('Profit/Loss') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($trades as $key => $trade)
                                    <tr>
                                        <td>{{ strtoupper($trade->ref) }}</td>
                                        <td>{{ $trade->currency }}</td>
                                        <td>{{ Config::formatter($trade->current_price) }}</td>

                                        <td>
                                            @if ($trade->trade_type == 'buy')
                                                <i class="fas fa-arrow-alt-circle-up text-success"></i>
                                                {{ $trade->trade_type }}
                                            @else
                                                <i class="fas fa-arrow-alt-circle-down text-danger"></i>
                                                {{ $trade->trade_type }}
                                            @endif
                                        </td>

                                        <td>
                                            <p id="count_{{ $loop->iteration }}" class="mb-2">
                                                @if ($trade->profit_type != null)
                                                    <span class="sp_badge sp_badge_success">
                                                        {{ $trade->trade_stop_at }}
                                                    </span>
                                                @endif
                                            </p>
                                            <script>
                                                @if ($trade->profit_type == null)
                                                    getCountDown("count_{{ $loop->iteration }}",
                                                        "{{ now()->gt($trade->trade_stop_at) ? 0 : now()->diffInSeconds($trade->trade_stop_at) }}"
                                                    )
                                                @endif
                                            </script>
                                        </td>

                                        <td>
                                            @if ($trade->profit_type == '+')
                                                <span class="text-success">{{ __('+' . $trade->profit_amount) }}</span>
                                            @elseif($trade->profit_type == '-')
                                                <span class="text-danger">{{ __('-' . $trade->loss_amount) }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($trade->status)
                                                <span class="text-success"><i class="far fa-check-circle"></i></span>
                                            @else
                                                <span class="text-danger"><i class="fas fa-spinner fa-spin"></i></span>
                                            @endif
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Trades Found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
                @if ($trades->hasPages())
                    <div class="sp_card_footer">
                        {{ $trades->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="order" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form action="" method="post">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Place Order') }}</h5>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between">
                            <h5 id="currentPrice" class="mb-4">{{ __('Current Price') }} : </h5>
                        </div>
                        <input type="hidden" name="trade_cur">
                        <input type="hidden" name="trade_price">
                        <div class="form-group mb-3">
                            <label for="">{{ __('Trade Duration') }} <span class="sp_theme_color">(
                                    {{ __('in Minutes') }} )</span> </label>
                            <input type="text" name="duration" class="form-control" placeholder="ex. 1">
                        </div>

                        <div class="row">
                            <div class="col-auto">
                                <div class="sp_form_check">
                                    <input class="form-check-input" id="trading-buy" type="radio" name="type"
                                        value="buy" checked>
                                    <label class="form-check-label" for="trading-buy">
                                        {{ __('BUY') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="sp_form_check">
                                    <input class="form-check-input" id="trading-sell" type="radio" name="type"
                                        value="sell">
                                    <label class="form-check-label" for="trading-sell">
                                        {{ __('SELL') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex">
                        <button type="submit" class="btn sp_theme_btn">{{ __('Confirm') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="spinner"></div>
@endsection

@push('style')
    <style>
        #linechart {
            min-height: 400px;
            width: 100%;
        }
        
        #linechart .apexcharts-tooltip {
            background-color: #220700 !important;
            border: 1px solid rgba(255, 255, 255, 0.15)
        }

        .sp_trading_section {
            padding: 120px 0;
        }

        .radio_button_list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin: -3px -15px;
        }

        .radio_button_list .sp_site_radio {
            padding: 3px 15px;
        }
    </style>
@endpush


@push('external-script')
    <script src="{{ Config::jsLib('frontend', 'lib/apex.min.js') }}?v={{ time() }}" onerror="this.onerror=null; this.src='https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js';"></script>
@endpush

@push('script')
    <script>
        // Disable old polling code globally
        window.__TRADING_SSE_ENABLED = true;
        window.__TRADING_POLLING_DISABLED = true;
        
        // Clear any existing intervals to prevent old polling code from running
        var maxIntervalId = setTimeout(function(){}, 0);
        for (var i = 0; i < maxIntervalId; i++) {
            try {
                clearInterval(i);
            } catch(e) {}
        }
        
        // Override fetchCryptocurrencyPrices and currentPrice if they exist
        if (typeof window.fetchCryptocurrencyPrices === 'function') {
            window.fetchCryptocurrencyPrices = function() {
                console.warn('Polling disabled - using SSE instead');
                return;
            };
        }
        if (typeof window.currentPrice === 'function') {
            window.currentPrice = function() {
                console.warn('Polling disabled - using SSE instead');
                return;
            };
        }
        
        (function() {
            'use strict'

            // Wait for jQuery to be available
            function waitForjQuery(callback, maxAttempts = 20, attempt = 0) {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    callback();
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        waitForjQuery(callback, maxAttempts, attempt + 1);
                    }, 100);
                } else {
                    console.error('jQuery library failed to load after multiple attempts');
                }
            }

            // Wait for ApexCharts to be available
            function waitForApexCharts(callback, maxAttempts = 20, attempt = 0) {
                if (typeof ApexCharts !== 'undefined') {
                    callback();
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        waitForApexCharts(callback, maxAttempts, attempt + 1);
                    }, 100);
                } else {
                    console.error('ApexCharts library failed to load after multiple attempts');
                }
            }

            // Initialize chart
            function initializeChart() {
                if (typeof ApexCharts === 'undefined') {
                    console.error('ApexCharts library is not loaded');
                    return false;
                }

                var chartElement = document.querySelector("#linechart");
                if (!chartElement) {
                    console.error('Chart container #linechart not found');
                    return false;
                }

                // Ensure element is visible and has dimensions
                if (chartElement.offsetWidth === 0 || chartElement.offsetHeight === 0) {
                    console.warn('Chart container has no dimensions, waiting...', {
                        width: chartElement.offsetWidth,
                        height: chartElement.offsetHeight,
                        display: window.getComputedStyle(chartElement).display
                    });
                    setTimeout(function() {
                        initializeChart();
                    }, 300);
                    return false;
                }

                try {
                    var options = {
                        series: [{
                            data: []
                        }],
                        chart: {
                            type: 'candlestick',
                            height: 400,
                            width: '100%'
                        },
                        title: {
                            text: 'CandleStick Chart',
                            align: 'left',
                            style: {
                                color: '#ffffff'
                            }
                        },
                        xaxis: {
                            type: 'datetime',
                            labels: {
                                style: {
                                    colors: ['#ffffff', '#ffffff', '#ffffff', '#ffffff', '#ffffff']
                                }
                            }
                        },
                        yaxis: {
                            tooltip: {
                                enabled: true
                            },
                            labels: {
                                style: {
                                    colors: ['#ffffff', '#ffffff', '#ffffff', '#ffffff', '#ffffff']
                                }
                            }
                        },
                        grid: {
                            show: true,
                            borderColor: '#ffffff26',
                            strokeDashArray: 0,
                            yaxis: {
                                lines: {
                                    show: true
                                }
                            }
                        }
                    };

                    var chart = new ApexCharts(chartElement, options);
                    chart.render();

                    window.tradingChart = chart;
                    console.log('Chart initialized successfully');
                    return true;
                } catch (error) {
                    console.error('Error initializing chart:', error);
                    return false;
                }
            }

            // Optimized polling with rate limiting and error handling
            let pollingInterval = null;
            let lastPollTime = 0;
            let consecutiveErrors = 0;
            let isRateLimited = false;
            let backoffDelay = 5000; // Start with 5 seconds
            const POLL_INTERVAL = 5000; // 5 seconds
            const MIN_POLL_GAP = 3000; // Minimum 3 seconds between polls
            const MAX_BACKOFF = 60000; // Max 60 seconds delay
            const RATE_LIMIT_BACKOFF = 30000; // 30 seconds when rate limited
            
            function startPolling(currency) {
                // Stop existing polling
                stopPolling();
                consecutiveErrors = 0;
                backoffDelay = POLL_INTERVAL;
                isRateLimited = false;
                
                function poll() {
                    const now = Date.now();
                    // Rate limiting - don't poll too frequently
                    if (now - lastPollTime < MIN_POLL_GAP) {
                        return;
                    }
                    lastPollTime = now;
                    
                    if (!window.tradingChart || typeof $ === 'undefined') {
                        return;
                    }
                    
                    // Fetch chart data and price in parallel
                    const chartPromise = $.ajax({
                        url: "{{ route('ticker') }}",
                        method: "GET",
                        data: { currency: currency },
                        timeout: 8000
                    }).then(function(response) {
                        // Handle different response formats
                        let chartData = null;
                        
                        if (Array.isArray(response)) {
                            chartData = response;
                        } else if (response && response.data && Array.isArray(response.data)) {
                            chartData = response.data;
                        } else if (response && response.error) {
                            // API returned an error
                            if (response.rate_limited) {
                                handleRateLimit();
                                return;
                            }
                            handleError('chart', response.error);
                            return;
                        }
                        
                        if (chartData && chartData.length > 0) {
                            window.tradingChart.updateSeries([{ data: chartData }]);
                            consecutiveErrors = 0; // Reset on success
                            backoffDelay = POLL_INTERVAL; // Reset backoff
                            isRateLimited = false;
                        } else {
                            // No data received
                            handleNoData('chart');
                        }
                    }).catch(function(xhr) {
                        handleError('chart', xhr.status === 429 ? 'Rate limit exceeded' : 'Network error');
                    });
                    
                    const pricePromise = $.ajax({
                        url: "{{ route('user.current-price') }}",
                        method: "GET",
                        data: { currency: currency },
                        timeout: 8000
                    }).then(function(response) {
                        // Handle different response formats
                        let price = null;
                        
                        if (typeof response === 'number') {
                            price = response;
                        } else if (response && typeof response === 'object') {
                            if (response.error) {
                                if (response.rate_limited) {
                                    handleRateLimit();
                                    return;
                                }
                                handleError('price', response.error);
                                return;
                            }
                            price = response;
                        }
                        
                        if (price !== null && !isNaN(price) && price > 0) {
                            if (typeof $ !== 'undefined') {
                                $('#currentPrice').text('Current Price ' + price + '(' + currency + ')');
                                $('input[name=trade_cur]').val(currency);
                                $('input[name=trade_price]').val(price);
                            }
                            consecutiveErrors = 0; // Reset on success
                            backoffDelay = POLL_INTERVAL; // Reset backoff
                            isRateLimited = false;
                        } else {
                            handleNoData('price');
                        }
                    }).catch(function(xhr) {
                        handleError('price', xhr.status === 429 ? 'Rate limit exceeded' : 'Network error');
                    });
                    
                    // Wait for both to complete
                    Promise.all([chartPromise, pricePromise]).catch(function() {
                        // Errors already handled
                    });
                }
                
                // Initial poll immediately
                poll();
                // Then poll at intervals
                pollingInterval = setInterval(poll, backoffDelay);
            }
            
            function handleRateLimit() {
                if (!isRateLimited) {
                    console.warn('Rate limit detected, backing off...');
                    isRateLimited = true;
                    backoffDelay = RATE_LIMIT_BACKOFF;
                    
                    // Restart polling with longer delay
                    const currency = $("input[name='currency']:checked").val() || 'BTC';
                    stopPolling();
                    setTimeout(function() {
                        startPolling(currency);
                    }, RATE_LIMIT_BACKOFF);
                }
            }
            
            function handleError(type, message) {
                consecutiveErrors++;
                
                // Exponential backoff on consecutive errors
                if (consecutiveErrors > 3) {
                    backoffDelay = Math.min(backoffDelay * 1.5, MAX_BACKOFF);
                    console.warn('Multiple errors, increasing poll interval to', backoffDelay, 'ms');
                    
                    // Restart with new delay
                    const currency = $("input[name='currency']:checked").val() || 'BTC';
                    stopPolling();
                    setTimeout(function() {
                        startPolling(currency);
                    }, backoffDelay);
                }
            }
            
            function handleNoData(type) {
                // Don't treat no data as error, but log it
                if (consecutiveErrors === 0) {
                    console.info('No data received for', type, '- this may be normal');
                }
            }
            
            function stopPolling() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }
            
            // Legacy function names for compatibility
            function connectSSE(currency) {
                startPolling(currency);
            }
            
            function disconnectSSE() {
                stopPolling();
            }

            // Main initialization
            function initTradingPage() {
                console.log('Starting trading page initialization...');
                waitForjQuery(function() {
                    console.log('jQuery loaded');
                    waitForApexCharts(function() {
                        console.log('ApexCharts loaded');
                        // Small delay to ensure DOM is fully rendered
                        setTimeout(function() {
                            // Initialize chart
                            if (initializeChart()) {
                                // Get initial currency
                                var currency = $("input[name='currency']:checked").val() || 'BTC';
                                console.log('Initial currency:', currency);

                                // Start polling for updates
                                startPolling(currency);

                                // Set up currency change handlers
                                $('.currency').on('click', function() {
                                    currency = $(this).val();
                                    startPolling(currency);
                                });

                                // Order button handler
                                $('.order').on('click', function() {
                                    var modal = $('#order');
                                    modal.modal('show');
                                });
                                
                                // Cleanup on page unload
                                window.addEventListener('beforeunload', function() {
                                    stopPolling();
                                });
                            } else {
                                console.error('Chart initialization failed');
                            }
                        }, 100);
                    });
                });
            }

            // Start initialization when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTradingPage);
            } else {
                initTradingPage();
            }
        })();
    </script>
@endpush
