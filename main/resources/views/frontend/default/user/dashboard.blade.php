@extends(Config::themeView('layout.auth'))

@section('content')

@php
    $plan_expired_at = now();
@endphp

@if (auth()->user()->currentplan)
    @php
        $is_subscribe = auth()->user()->currentplan()->where('is_current', 1)->first();

        if($is_subscribe){
            $plan_expired_at =  $is_subscribe->plan_expired_at;
        }
    @endphp
@endif

    <div class="row g-sm-4 g-3">
        <div class="col-xxl-9 col-xl-8 d-custom-left">
            <div class="d-left-wrapper">
                <!-- Onboarding Checklist Widget -->
                @if(isset($onboardingChecklist) && !empty($onboardingChecklist) && $onboardingProgress < 100)
                    @include(Config::themeView('user.onboarding._checklist_widget', [
                        'checklist' => $onboardingChecklist,
                        'progress' => $onboardingProgress
                    ])
                @endif
                
                <!-- Quick Action Banner Section -->
                @include(Config::themeView('user.dashboard._quick_actions_banner')
                
                <div class="d-left-countdown">
                    <div id="countdownTwo"></div>
                </div>
                <div class="row g-sm-4 g-3">
                    <div class="custom-xxl-6 col-xxl-3 col-xl-6 col-lg-3 col-6">
                        <x-ui.stats-card 
                            title="{{ __('Total Deposit') }}"
                            :value="Config::formatter($totalDeposit)"
                            icon='<i class="las la-credit-card"></i>'
                        />
                    </div>
                    <div class="custom-xxl-6 col-xxl-3 col-xl-6 col-lg-3 col-6">
                        <x-ui.stats-card 
                            title="{{ __('Total Withdraw') }}"
                            :value="Config::formatter($totalWithdraw)"
                            icon='<i class="las la-hand-holding-usd"></i>'
                        />
                    </div>
                    <div class="custom-xxl-6 col-xxl-3 col-xl-6 col-lg-3 col-6">
                        <x-ui.stats-card 
                            title="{{ __('Total Payment') }}"
                            :value="Config::formatter($totalPayments)"
                            icon='<i class="las la-chart-bar"></i>'
                        />
                    </div>
                    <div class="custom-xxl-6 col-xxl-3 col-xl-6 col-lg-3 col-6">
                        <x-ui.stats-card 
                            title="{{ __('Support Tickets') }}"
                            :value="$totalSupportTickets"
                            icon='<i class="las la-ticket-alt"></i>'
                        />
                    </div>
                </div>

                <div class="d-xl-none d-block mt-4">
                    <div class="row g-sm-4 g-3">
                        <div class="col-xl-12 col-lg-6">
                            <div class="d-card user-card not-hover"> 
                                <div class="text-center">
                                    <h5 class="user-card-title">{{ __('Total Balance') }}</h5>
                                    <h4 class="d-card-balance mt-xxl-3 mt-2">{{ Config::formatter($totalbalance) }}</h4>
                                    <div class="mt-4">
                                        <a href="{{ route('user.withdraw') }}" class="btn btn-md sp_btn_danger me-xxl-3 me-2"><i class="las la-minus-circle fs-lg"></i> {{ __('Withdraw') }}</a>
                                        <a href="{{ route('user.deposit') }}" class="btn btn-md sp_btn_success ms-xxl-3 ms-2"><i class="las la-plus-circle fs-lg"></i> {{ __('Deposit') }}</a>
                                    </div>
                                </div>

                                <hr class="my-4">



                                <ul class="recent-transaction-list mt-4">
                                    @foreach ($transactions as $trans)

                
                                        
                                    <li class="single-recent-transaction">
                                       
                                        <div class="content">
                                            <h6 class="title">{{$trans->details}}</h6>
                                            <span>{{$trans->created_at->format('d F, Y')}}</span>
                                        </div>
                                        <p class="recent-transaction-amount {{$trans->type == '+' ?  "sp_text_success" : 'sp_text_danger' }}">{{Config::formatter($trans->amount)}}</p>
                                    </li>
                                    @endforeach
                                    
                                </ul>
                                <a href="{{ route('user.transaction.log') }}" class="btn btn-primary mt-4 w-100 focus-ring"><i class="fas fa-rocket me-2"></i> {{ __('View All Transactions') }}</a>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="d-card not-hover">
                                <div id="chart3" class="d-flex justify-content-center"></div>
                            </div>

                            <h6 class="mb-2 mt-4">{{ __('Your Referral Link') }}</h6>
                            <form>
                                <div class="input-group">
                                    <input type="text" id="referral-link-mobile" class="form-control form-control-modern copy-text" placeholder="{{ __('Referral link') }}"
                                        value="{{ route('user.register', $user->username) }}" readonly aria-label="{{ __('Referral link') }}">
                                    <button type="button" class="btn btn-primary copy focus-ring" aria-label="{{ __('Copy referral link') }}">{{ __('Copy') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="d-card card-modern mt-4">
                    <h5 class="mb-4">{{ __('All Signals') }}</h5>
                    <div id="chart"></div>
                </div>

                <div class="sp_site_card card-modern mt-4">
                    <div class="card-header border-bottom pb-3 mb-3">
                        <h5 class="mb-0">{{ __('Latest Signals') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table sp_site_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Signal Date') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @forelse ($signals as $signal)
                                        <tr>
                                            <td data-caption="{{ __('Signal Date') }}">
                                                {{ $signal->created_at->format('dS M, Y -') }}

                                                <span class="table-date">{{ $signal->created_at->format('h:i:s A') }}</span>
                                            </td>
                                            <td data-caption="{{ __('Title') }}">{{ $signal->signal->title }}</td>
                                            <td data-caption="{{ __('Action') }}">
                                                <a href="{{ route('user.signal.details', ['id' => $signal->signal->id, 'slug' => Str::slug($signal->signal->title)]) }}"
                                                    class="btn btn-sm btn-outline-primary focus-ring" aria-label="{{ __('View signal') }} {{ $signal->signal->title }}">
                                                    <i class="far fa-eye me-1"></i> {{ __('View') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center" colspan="100%">{{ __('No Signals Found') }}</td>
                                        </tr>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($signals->hasPages())
                        <div class="card-footer">
                            {{ $signals->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 d-custom-right">
            <div class="d-right-wrapper">
                <div class="d-xl-block d-none">
                    <div class="row g-sm-4 g-3">
                        <div class="col-xl-12 col-lg-6">
                            <div class="d-card user-card card-modern"> 
                                <div class="text-center">
                                    <h5 class="user-card-title mb-3">{{ __('Total Balance') }}</h5>
                                    <h4 class="d-card-balance mt-xxl-3 mt-2 mb-4">{{ Config::formatter($totalbalance) }}</h4>
                                    <div class="mt-4 d-flex gap-2 justify-content-center">
                                        <a href="{{ route('user.withdraw') }}" class="btn btn-danger btn-md focus-ring"><i class="las la-minus-circle me-1"></i> {{ __('Withdraw') }}</a>
                                        <a href="{{ route('user.deposit') }}" class="btn btn-success btn-md focus-ring"><i class="las la-plus-circle me-1"></i> {{ __('Deposit') }}</a>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <ul class="recent-transaction-list mt-4">
                                    @foreach ($transactions as $trans)
                                    <li class="single-recent-transaction">
                                        <div class="content">
                                            <h6 class="title">{{$trans->details}}</h6>
                                            <span>{{$trans->created_at->format('d F, Y')}}</span>
                                        </div>
                                        <p class="recent-transaction-amount {{$trans->type == '+' ?  "sp_text_success" : 'sp_text_danger' }}">{{number_format($trans->amount)}}</p>
                                    </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('user.transaction.log') }}" class="btn btn-primary mt-4 w-100 focus-ring"><i class="fas fa-rocket me-2"></i> {{ __('View All Transactions') }}</a>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="d-card card-modern">
                                <div id="chart2" class="d-flex justify-content-center"></div>
                            </div>

                            <h6 class="mb-2 mt-4">{{ __('Your Referral Link') }}</h6>
                            <form>
                                <div class="input-group">
                                    <input type="text" id="referral-link-desktop" class="form-control form-control-modern copy-text2" placeholder="{{ __('Referral link') }}"
                                        value="{{ route('user.register', $user->username) }}" readonly aria-label="{{ __('Referral link') }}">
                                    <button type="button" class="btn btn-primary copy2 focus-ring" aria-label="{{ __('Copy referral link') }}">{{ __('Copy') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('external-css')
    <link rel="stylesheet" href="{{ Config::cssLib('frontend', 'lib/apex.min.css') }}">
@endpush


@push('external-script')
    <!-- ApexCharts Library - Direct blocking load -->
    <script src="{{ Config::jsLib('frontend', 'lib/apex.min.js') }}" async="false" defer="false" onerror="window.__apexchartsLoadFailed = true;"></script>
    <script>
        // Check if blocking load succeeded
        (function() {
            console.log('[ApexCharts] Checking after blocking load, defined:', typeof ApexCharts !== 'undefined', 'failed:', window.__apexchartsLoadFailed);
            
            // Small delay to allow script to initialize
            setTimeout(function() {
                if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                    console.log('[ApexCharts] Blocking load succeeded');
                    window.__apexchartsLoaded = true;
                    window.dispatchEvent(new Event('apexcharts-loaded'));
                } else if (window.__apexchartsLoadFailed || typeof ApexCharts === 'undefined') {
                    console.log('[ApexCharts] Blocking load failed or not available, using fallback');
                    loadFallback();
                } else {
                    // Script loaded but ApexCharts not ready yet - wait a bit more
                    var attempts = 0;
                    var checkInterval = setInterval(function() {
                        attempts++;
                        if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                            console.log('[ApexCharts] Available after', attempts, 'checks');
                            clearInterval(checkInterval);
                            window.__apexchartsLoaded = true;
                            window.dispatchEvent(new Event('apexcharts-loaded'));
                        } else if (attempts >= 20) {
                            console.warn('[ApexCharts] Timeout waiting, using fallback');
                            clearInterval(checkInterval);
                            loadFallback();
                        }
                    }, 50);
                }
            }, 50);
            
            function loadFallback() {
                if (window.__apexchartsLoading) return;
                window.__apexchartsLoading = true;
                
                var scripts = [
                    'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.44.0/apexcharts.min.js',
                    'https://unpkg.com/apexcharts@3.44.0/dist/apexcharts.min.js'
                ];
                
                function loadScript(index) {
                    if (index >= scripts.length) {
                        console.error('[ApexCharts] All fallback sources failed');
                        window.__apexchartsLoading = false;
                        return;
                    }
                    
                    if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                        console.log('[ApexCharts] Available from fallback');
                        window.__apexchartsLoaded = true;
                        window.__apexchartsLoading = false;
                        window.dispatchEvent(new Event('apexcharts-loaded'));
                        return;
                    }
                    
                    console.log('[ApexCharts] Loading fallback', index + 1, ':', scripts[index]);
                    var script = document.createElement('script');
                    script.src = scripts[index];
                    script.async = false;
                    script.defer = false;
                    
                    script.onload = function() {
                        var attempts = 0;
                        var checkInterval = setInterval(function() {
                            attempts++;
                            if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                                console.log('[ApexCharts] Fallback confirmed available');
                                clearInterval(checkInterval);
                                window.__apexchartsLoaded = true;
                                window.__apexchartsLoading = false;
                                window.dispatchEvent(new Event('apexcharts-loaded'));
                            } else if (attempts >= 100) {
                                clearInterval(checkInterval);
                                loadScript(index + 1);
                            }
                        }, 50);
                    };
                    
                    script.onerror = function() {
                        loadScript(index + 1);
                    };
                    
                    document.head.appendChild(script);
                }
                
                loadScript(0);
            }
        })();
    </script>
@endpush

@push('script')
    <script>
    // Initialize dashboard - wait for both DOM and ApexCharts
    (function() {
        'use strict'
        
        console.log('[Dashboard] Init script started, DOM:', document.readyState, 'ApexCharts:', typeof ApexCharts !== 'undefined');
        
        function initDashboard() {
            console.log('[Dashboard] initDashboard called, ApexCharts:', typeof ApexCharts !== 'undefined');

        var copyButton = document.querySelector('.copy');
        var copyInput = document.querySelector('.copy-text');
        if (copyButton && copyInput) {
            copyButton.addEventListener('click', function(e) {
                e.preventDefault();
                var text = copyInput.select();
                document.execCommand('copy');
            });
            copyInput.addEventListener('click', function() {
                this.select();
            });
        }

        var copyButton2 = document.querySelector('.copy2');
        var copyInput2 = document.querySelector('.copy-text2');
        if (copyButton2 && copyInput2) {
            copyButton2.addEventListener('click', function(e) {
                e.preventDefault();
                var text = copyInput2.select();
                document.execCommand('copy');
            });
            copyInput2.addEventListener('click', function() {
                this.select();
            });
        }

        var expirationDate = new Date('{{ $plan_expired_at }}');

        function updateCountdown() {
            var now = new Date();
            var timeLeft = expirationDate - now;

            if (timeLeft < 0) {
                // The plan has expired
                $('#countdownTwo').html(`
                    <p class="upgrade-text"><i class="fas fa-rocket"></i> Please Upgrade Your Plan To Get Signals</p>
                `);
            } else {
                // The plan is still active
                var daysLeft = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hoursLeft = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutesLeft = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var secondsLeft = Math.floor((timeLeft % (1000 * 60)) / 1000);

                $('#countdownTwo').html(`
                    <h5 class="d-left-countdown-title">{{ __('plan expired at :') }}</h5>
                    <div class="countdown-wrapper">
                    <p class="countdown-single">
                        ${daysLeft}
                        <span>D</span>
                    </p>
                    <p class="countdown-single">
                        ${hoursLeft}
                        <span>H</span>
                    </p>
                    <p class="countdown-single">
                        ${minutesLeft}
                        <span>M</span>
                    </p>
                    <p class="countdown-single">
                        ${secondsLeft}
                        <span>S</span>
                    </p>
                    </div>
                `);
            }
        }
        // Call updateCountdown every second
        setInterval(updateCountdown, 1000);

        // Wait for ApexCharts to be available - with strict verification
        function waitForApexCharts(callback) {
            console.log('[Dashboard] waitForApexCharts called, ApexCharts:', typeof ApexCharts !== 'undefined');
            
            // Check immediately with strict verification
            if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                console.log('[Dashboard] ApexCharts available immediately');
                // Double-check it's actually callable
                try {
                    if (ApexCharts.prototype && ApexCharts.prototype.constructor) {
                        callback();
                        return;
                    }
                } catch(e) {
                    console.warn('[Dashboard] ApexCharts exists but not callable:', e);
                }
            }
            
            // Check if already loaded flag is set
            if (window.__apexchartsLoaded) {
                setTimeout(function() {
                    if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                        console.log('[Dashboard] ApexCharts confirmed via loaded flag');
                        callback();
                        return;
                    }
                }, 10);
            }
            
            // Listen for loaded event (only once)
            var eventHandler = function() {
                console.log('[Dashboard] apexcharts-loaded event fired');
                // Wait a tiny bit to ensure ApexCharts is fully initialized
                setTimeout(function() {
                    if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                        console.log('[Dashboard] ApexCharts confirmed in event handler');
                        callback();
                    } else {
                        console.error('[Dashboard] Event fired but ApexCharts still not valid!');
                    }
                }, 10);
            };
            window.addEventListener('apexcharts-loaded', eventHandler, { once: true });
            
            // Fallback polling (max 10 seconds = 200 attempts * 50ms)
            var attempts = 0;
            var maxAttempts = 200;
            var pollInterval = setInterval(function() {
                attempts++;
                if (typeof ApexCharts !== 'undefined' && typeof ApexCharts === 'function') {
                    console.log('[Dashboard] ApexCharts detected via polling after', attempts, 'attempts');
                    clearInterval(pollInterval);
                    window.removeEventListener('apexcharts-loaded', eventHandler);
                    callback();
                } else if (attempts >= maxAttempts) {
                    console.error('[Dashboard] Polling timeout - ApexCharts failed to load');
                    clearInterval(pollInterval);
                    window.removeEventListener('apexcharts-loaded', eventHandler);
                }
            }, 50);
        }

        // Initialize charts when ApexCharts is ready
        waitForApexCharts(function() {
            console.log('[Dashboard] Chart init callback executing');
            
            // Strict verification - ApexCharts must be a function
            if (typeof ApexCharts === 'undefined' || typeof ApexCharts !== 'function') {
                console.error('[Dashboard] CRITICAL: ApexCharts not available or not a function. Type:', typeof ApexCharts);
                return;
            }
            
            // Additional verification - try to access ApexCharts constructor
            try {
                if (!ApexCharts.prototype || !ApexCharts.prototype.constructor) {
                    console.error('[Dashboard] CRITICAL: ApexCharts exists but prototype invalid');
                    return;
                }
            } catch(e) {
                console.error('[Dashboard] CRITICAL: Error accessing ApexCharts:', e);
                return;
            }
            
            console.log('[Dashboard] ApexCharts verified, proceeding with chart creation');

            var colors = ['#9C0AC1'];

            var options = {
                series: [{
                    name: 'Signal',
                    data: @json($signalGrapTotal)
                }],
                legend: {
                    labels: {
                        colors: '#ffffff'
                    }
                },
                colors: colors,
                chart: {
                    height: 280,
                    type: 'bar',
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '40%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent'],
                    curve: 'smooth'
                },
                xaxis: {
                    categories: @json($months),
                    labels: {
                        style: {
                            colors: '#bebebe',
                            fontSize: '12px',
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#bebebe'
                        }
                    }
                },
                grid: {
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },   
                    yaxis: {
                        lines: {
                            show: false
                        }
                    }, 
                },
                fill: {
                    opacity: 1,
                    colors: colors
                },
                tooltip: {
                    x: {
                        format: 'dd/MM/yy HH:mm'
                    },
                },
            };

            // Final safety check - ensure ApexCharts is actually available
            if (typeof ApexCharts === 'undefined') {
                console.error('[Dashboard] CRITICAL: ApexCharts undefined before chart creation - aborting');
                return;
            }
            
            console.log('[Dashboard] Creating chart 1, ApexCharts type:', typeof ApexCharts);
            var chartElement = document.querySelector("#chart");
            if (chartElement) {
                try {
                    var chart = new ApexCharts(chartElement, options);
                    chart.render();
                    console.log('[Dashboard] Chart 1 created successfully');
                } catch(e) {
                    console.error('[Dashboard] Chart 1 creation failed:', e.message, e.stack);
                }
            } else {
                console.warn('[Dashboard] Chart element #chart not found');
            }

            var options2 = {
                series: [{{$totalAmount->sum()}}, {{$withdrawTotalAmount->sum()}}, {{$depositTotalAmount->sum()}}],
                labels: ['Payment', 'Withdraw', 'Deposit'],
                chart: {
                    type: 'donut',
                    width: 370,
                    height: 430
                },
                colors: ['#622bd7', '#e7515a', '#10a373', '#10a373'],
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '14px',
                    labels: {
                        colors: '#ffffff'
                    },
                    markers: {
                        width: 10,
                        height: 10,
                        offsetX: -5,
                        offsetY: 0
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 30
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                        size: '75%',
                        background: 'transparent',
                        labels: {
                            show: true,
                            name: {
                            show: true,
                            fontSize: '29px',
                            fontFamily: 'Nunito, sans-serif',
                            color: '#ffffff',
                            offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '26px',
                                fontFamily: 'Nunito, sans-serif',
                                color: '#bfc9d4',
                                offsetY: 16,
                                number_format: function (val) {
                                    return val
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total',
                                color: '#ffffff',
                                fontSize: '30px',
                                number_format: function (w) {
                                    return w.globals.seriesTotals.reduce( function(a, b) {
                                    return a + b
                                    }, 0)
                                }
                            }
                        }
                        }
                    }
                },
                stroke: {
                    show: true,
                    width: 15,
                    colors: '#1E1F25'
                  },
                  responsive: [
                    { 
                      breakpoint: 1440, options: {
                        chart: {
                          width: 325
                        },
                      }
                    },
                    { 
                      breakpoint: 1199, options: {
                        chart: {
                          width: 380
                        },
                      }
                    },
                    { 
                      breakpoint: 575, options: {
                        chart: {
                          width: 320
                        },
                      }
                    },
                  ],
            };

            // Double-check ApexCharts before each chart creation
            if (typeof ApexCharts === 'undefined') {
                console.error('[Dashboard] ApexCharts undefined before chart 2 - stopping');
                return;
            }
            
            console.log('[Dashboard] Creating chart 2');
            var chart2Element = document.querySelector("#chart2");
            if (chart2Element) {
                try {
                    var chart2 = new ApexCharts(chart2Element, options2);
                    chart2.render();
                    console.log('[Dashboard] Chart 2 created successfully');
                } catch(e) {
                    console.error('[Dashboard] Chart 2 creation failed:', e.message, e.stack);
                }
            } else {
                console.warn('[Dashboard] Chart element #chart2 not found');
            }

            if (typeof ApexCharts === 'undefined') {
                console.error('[Dashboard] ApexCharts undefined before chart 3 - stopping');
                return;
            }
            
            console.log('[Dashboard] Creating chart 3');
            var chart3Element = document.querySelector("#chart3");
            if (chart3Element) {
                try {
                    var chart3 = new ApexCharts(chart3Element, options2);
                    chart3.render();
                    console.log('[Dashboard] Chart 3 created successfully');
                } catch(e) {
                    console.error('[Dashboard] Chart 3 creation failed:', e.message, e.stack);
                }
            } else {
                console.warn('[Dashboard] Chart element #chart3 not found');
            }
        });
        }
        
        // Wait for both DOM and ApexCharts before initializing
        function waitForBoth() {
            var domReady = document.readyState === 'complete' || document.readyState === 'interactive';
            
            console.log('[Dashboard] waitForBoth check - DOM:', domReady, 'readyState:', document.readyState);
            
            if (domReady) {
                // DOM is ready, now wait for ApexCharts
                waitForApexCharts(function() {
                    console.log('[Dashboard] Both DOM and ApexCharts ready, initializing dashboard');
                    initDashboard();
                });
            } else {
                // Wait for DOM first
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', waitForBoth, { once: true });
                } else if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    $(document).ready(waitForBoth);
                } else {
                    // Fallback: check again after a short delay
                    setTimeout(waitForBoth, 100);
                }
            }
        }
        
        // Start waiting - use jQuery ready if available, otherwise DOMContentLoaded
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            $(document).ready(waitForBoth);
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForBoth, { once: true });
        } else {
            waitForBoth();
        }
    })();
    </script>
@endpush
