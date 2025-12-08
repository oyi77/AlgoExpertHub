@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row gy-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-0">{{ __('Backtesting') }}</h4>
                <p class="text-muted mb-0">{{ __('Test your trading strategies on historical data') }}</p>
            </div>
        </div>
    </div>

    @if(!$tradingManagementEnabled)
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="las la-exclamation-triangle"></i> 
                {{ __('Backtesting module is not enabled. Please contact administrator.') }}
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header p-3 border-bottom">
                    <ul class="nav nav-pills" id="backtestingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'create' ? 'active' : '' }}" 
                               id="create-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('create')"
                               href="#create" 
                               role="tab">
                                <i class="las la-plus-circle me-1"></i> {{ __('Create Backtest') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'results' ? 'active' : '' }}" 
                               id="results-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('results')"
                               href="#results" 
                               role="tab">
                                <i class="las la-list me-1"></i> {{ __('Results') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'reports' ? 'active' : '' }}" 
                               id="reports-tab" 
                               data-bs-toggle="tab" 
                               onclick="switchTab('reports')"
                               href="#reports" 
                               role="tab">
                                <i class="las la-file-alt me-1"></i> {{ __('Performance Reports') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="backtestingTabContent">
                        <!-- Create Backtest Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'create' ? 'show active' : '' }}" 
                             id="create" 
                             role="tabpanel">
                            <div class="alert alert-info">
                                <i class="las la-info-circle"></i>
                                {{ __('Backtesting feature is coming soon. You will be able to test your trading strategies on historical market data.') }}
                            </div>
                            @if(isset($presets) && $presets->count() > 0)
                                <div class="sp_site_card">
                                    <h5 class="mb-3">{{ __('Available Presets for Testing') }}</h5>
                                    <div class="row gy-3">
                                        @foreach($presets as $preset)
                                        <div class="col-md-4">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6>{{ $preset->name }}</h6>
                                                    <p class="text-muted small mb-2">{{ Str::limit($preset->description ?? 'No description', 100) }}</p>
                                                    <button class="btn btn-sm btn-outline-primary w-100" disabled>
                                                        <i class="las la-flask"></i> {{ __('Test Strategy') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Results Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'results' ? 'show active' : '' }}" 
                             id="results" 
                             role="tabpanel">
                            @if(isset($results) && $results->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Test Name') }}</th>
                                                <th>{{ __('Strategy') }}</th>
                                                <th>{{ __('Period') }}</th>
                                                <th>{{ __('Win Rate') }}</th>
                                                <th>{{ __('Total P&L') }}</th>
                                                <th>{{ __('Created') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($results as $result)
                                            <tr>
                                                <td><strong>{{ $result->name ?? 'N/A' }}</strong></td>
                                                <td>{{ $result->strategy ?? 'N/A' }}</td>
                                                <td>{{ $result->period ?? 'N/A' }}</td>
                                                <td>{{ number_format($result->win_rate ?? 0, 2) }}%</td>
                                                <td class="{{ ($result->total_pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($result->total_pnl ?? 0, 2) }}
                                                </td>
                                                <td>{{ $result->created_at ? $result->created_at->diffForHumans() : 'N/A' }}</td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-xs btn-outline-info">
                                                        <i class="las la-eye"></i> {{ __('View') }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-list la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No backtest results found.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Performance Reports Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'reports' ? 'show active' : '' }}" 
                             id="reports" 
                             role="tabpanel">
                            @if(isset($reports) && $reports->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Report Name') }}</th>
                                                <th>{{ __('Period') }}</th>
                                                <th>{{ __('Total Tests') }}</th>
                                                <th>{{ __('Avg Win Rate') }}</th>
                                                <th>{{ __('Generated') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($reports as $report)
                                            <tr>
                                                <td><strong>{{ $report->name ?? 'N/A' }}</strong></td>
                                                <td>{{ $report->period ?? 'N/A' }}</td>
                                                <td>{{ $report->total_tests ?? 0 }}</td>
                                                <td>{{ number_format($report->avg_win_rate ?? 0, 2) }}%</td>
                                                <td>{{ $report->created_at ? $report->created_at->diffForHumans() : 'N/A' }}</td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-xs btn-outline-primary">
                                                        <i class="las la-download"></i> {{ __('Download') }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-file-alt la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No performance reports found.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('script')
<script>
    $(function() {
        'use strict'
        
        // Function to switch tabs and update URL
        function switchTab(tabName) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.location.href = url.toString();
        }
        
        // Make switchTab available globally
        window.switchTab = switchTab;
        
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            const tabLink = $('#backtestingTabs a[href="#' + tabParam + '"]');
            if (tabLink.length) {
                const tab = new bootstrap.Tab(tabLink[0]);
                tab.show();
            }
        }
        
        // Old event handler - keep for compatibility
        $('#backtestingTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const targetId = $(e.target).attr('href').replace('#', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId);
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endpush
@endsection

