<section class="trade-section sp_pt_120 sp_pb_120">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 text-center">
        <div class="sp_theme_top  wow fadeInUp" data-wow-duration="0.3s" data-wow-delay="0.3s">
          <div class="sp_theme_top_caption"><i class="fas fa-bolt"></i> {{ Config::trans($content->section_header) }}</div>
          <h2 class="sp_theme_top_title"><?= Config::colorText(optional($content)->title, optional($content)->color_text_for_title) ?></h2>
        </div>
      </div>
    </div>

    <div>
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
              <a href="{{route('user.trade')}}" class="btn sp_theme_btn order">{{ Config::trans($content->button_text) }}</a>
          </div>
        </div>
        <div class="sp_card_body">
            <div id="linechart"></div>
        </div>
      </div>
    </div>
  </div>
</section>



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
        (function() {
            'use strict'

            // Wait for jQuery and ApexCharts
            function waitForjQuery(callback, maxAttempts = 20, attempt = 0) {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    callback();
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        waitForjQuery(callback, maxAttempts, attempt + 1);
                    }, 100);
                } else {
                    console.error('jQuery library failed to load');
                }
            }

            function waitForApexCharts(callback, maxAttempts = 20, attempt = 0) {
                if (typeof ApexCharts !== 'undefined') {
                    callback();
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        waitForApexCharts(callback, maxAttempts, attempt + 1);
                    }, 100);
                } else {
                    console.error('ApexCharts library failed to load');
                }
            }

            // Initialize chart
            function initializeChart() {
                if (typeof ApexCharts === 'undefined') {
                    return false;
                }

                var chartElement = document.querySelector("#linechart");
                if (!chartElement) {
                    return false;
                }

                if (chartElement.offsetWidth === 0 || chartElement.offsetHeight === 0) {
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

                    window.widgetChart = chart;
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
            let backoffDelay = 5000;
            const POLL_INTERVAL = 5000;
            const MIN_POLL_GAP = 3000;
            const MAX_BACKOFF = 60000;
            const RATE_LIMIT_BACKOFF = 30000;

            function startPolling(currency) {
                stopPolling();
                consecutiveErrors = 0;
                backoffDelay = POLL_INTERVAL;
                isRateLimited = false;

                function poll() {
                    const now = Date.now();
                    if (now - lastPollTime < MIN_POLL_GAP) {
                        return;
                    }
                    lastPollTime = now;

                    if (!window.widgetChart || typeof $ === 'undefined') {
                        return;
                    }

                    $.ajax({
                        url: "{{ route('ticker') }}",
                        method: "GET",
                        data: { currency: currency },
                        timeout: 8000
                    }).then(function(response) {
                        let chartData = null;
                        
                        if (Array.isArray(response)) {
                            chartData = response;
                        } else if (response && response.data && Array.isArray(response.data)) {
                            chartData = response.data;
                        } else if (response && response.error) {
                            if (response.rate_limited) {
                                handleRateLimit();
                                return;
                            }
                            handleError(response.error);
                            return;
                        }
                        
                        if (chartData && chartData.length > 0) {
                            window.widgetChart.updateSeries([{ data: chartData }]);
                            consecutiveErrors = 0;
                            backoffDelay = POLL_INTERVAL;
                            isRateLimited = false;
                        } else {
                            handleNoData();
                        }
                    }).catch(function(xhr) {
                        handleError(xhr.status === 429 ? 'Rate limit exceeded' : 'Network error');
                    });
                }
                
                function handleRateLimit() {
                    if (!isRateLimited) {
                        isRateLimited = true;
                        backoffDelay = RATE_LIMIT_BACKOFF;
                        const currency = $("input[name='currency']:checked").val() || 'BTC';
                        stopPolling();
                        setTimeout(function() {
                            startPolling(currency);
                        }, RATE_LIMIT_BACKOFF);
                    }
                }
                
                function handleError(message) {
                    consecutiveErrors++;
                    if (consecutiveErrors > 3) {
                        backoffDelay = Math.min(backoffDelay * 1.5, MAX_BACKOFF);
                        const currency = $("input[name='currency']:checked").val() || 'BTC';
                        stopPolling();
                        setTimeout(function() {
                            startPolling(currency);
                        }, backoffDelay);
                    }
                }
                
                function handleNoData() {
                    // No data is not necessarily an error
                }

                poll();
                pollingInterval = setInterval(poll, backoffDelay);
            }

            function stopPolling() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            // Main initialization
            function initWidget() {
                waitForjQuery(function() {
                    waitForApexCharts(function() {
                        setTimeout(function() {
                            if (initializeChart()) {
                                var currency = $("input[name='currency']:checked").val() || 'BTC';
                                startPolling(currency);

                                $('.currency').on('click', function() {
                                    currency = $(this).val();
                                    startPolling(currency);
                                });
                            }
                        }, 100);
                    });
                });
            }

            // Start when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWidget);
            } else {
                initWidget();
            }

            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                stopPolling();
            });
        })();
    </script>
@endpush
