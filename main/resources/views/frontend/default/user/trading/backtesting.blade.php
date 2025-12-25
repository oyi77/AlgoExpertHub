@extends(Config::themeView('layout.auth'))

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
                            <form id="createBacktestForm" action="{{ route('user.trading.backtesting.store') }}" method="POST">
                                @csrf
                                
                                <div class="row gy-4">
                                    <!-- Basic Information -->
                                    <div class="col-12">
                                        <h5 class="mb-3"><i class="las la-info-circle"></i> {{ __('Basic Information') }}</h5>
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">{{ __('Backtest Name') }} <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', '') }}" required placeholder="{{ __('My Strategy Test') }}">
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="symbol">{{ __('Trading Pair') }} <span class="text-danger">*</span></label>
                                                    <select name="symbol" id="symbol" class="form-control @error('symbol') is-invalid @enderror" required>
                                                        <option value="">{{ __('Select Pair') }}</option>
                                                        @if(isset($currencyPairs) && $currencyPairs->count() > 0)
                                                            @foreach($currencyPairs as $pair)
                                                                <option value="{{ $pair->name }}" {{ old('symbol') == $pair->name ? 'selected' : '' }}>
                                                                    {{ $pair->name }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    @error('symbol')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="text-muted">{{ __('Select the trading pair to backtest') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="description">{{ __('Description') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                                                    <textarea name="description" id="description" class="form-control" rows="2" placeholder="{{ __('Optional description for this backtest') }}">{{ old('description', '') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Market Data Configuration -->
                                    <div class="col-12">
                                        <h5 class="mb-3"><i class="las la-chart-line"></i> {{ __('Market Data') }}</h5>
                                        <div class="row gy-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="timeframe">{{ __('Timeframe') }} <span class="text-danger">*</span></label>
                                                    <select name="timeframe" id="timeframe" class="form-control @error('timeframe') is-invalid @enderror" required>
                                                        <option value="">{{ __('Select Timeframe') }}</option>
                                                        @if(isset($timeframes) && $timeframes->count() > 0)
                                                            @foreach($timeframes as $tf)
                                                                <option value="{{ $tf->name }}" {{ old('timeframe') == $tf->name ? 'selected' : '' }}>
                                                                    {{ $tf->name }}
                                                                </option>
                                                            @endforeach
                                                        @else
                                                            <option value="1h" {{ old('timeframe') == '1h' ? 'selected' : 'selected' }}>1 Hour</option>
                                                            <option value="4h" {{ old('timeframe') == '4h' ? 'selected' : '' }}>4 Hours</option>
                                                            <option value="1d" {{ old('timeframe') == '1d' ? 'selected' : '' }}>1 Day</option>
                                                        @endif
                                                    </select>
                                                    @error('timeframe')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="initial_balance">{{ __('Initial Balance') }} <span class="text-danger">*</span></label>
                                                    <input type="number" name="initial_balance" id="initial_balance" class="form-control @error('initial_balance') is-invalid @enderror" value="{{ old('initial_balance', 10000) }}" required min="100" step="0.01">
                                                    @error('initial_balance')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="text-muted">{{ __('Starting capital for backtest') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_date">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                                    <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d', strtotime('-30 days'))) }}" required max="{{ date('Y-m-d') }}">
                                                    @error('start_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_date">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                                    <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', date('Y-m-d')) }}" required max="{{ date('Y-m-d') }}">
                                                    @error('end_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Strategy Components (Optional) -->
                                    <div class="col-12">
                                        <h5 class="mb-3"><i class="las la-cog"></i> {{ __('Strategy Components') }} <span class="text-muted small">({{ __('Optional') }})</span></h5>
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="filter_strategy_id">{{ __('Filter Strategy') }}</label>
                                                    <select name="filter_strategy_id" id="filter_strategy_id" class="form-control">
                                                        <option value="">{{ __('None - Use all signals') }}</option>
                                                        @php
                                                            $filterStrategies = [];
                                                            if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                                                                try {
                                                                    $filterStrategies = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where(function($query) {
                                                                        $query->where('created_by_user_id', auth()->id())
                                                                              ->orWhereNull('created_by_user_id');
                                                                    })->get();
                                                                } catch (\Exception $e) {
                                                                    $filterStrategies = collect([]);
                                                                }
                                                            }
                                                        @endphp
                                                        @foreach($filterStrategies as $strategy)
                                                            <option value="{{ $strategy->id }}" {{ old('filter_strategy_id') == $strategy->id ? 'selected' : '' }}>
                                                                {{ $strategy->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">{{ __('Apply technical indicator filters to signals') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="ai_model_profile_id">{{ __('AI Model Profile') }}</label>
                                                    <select name="ai_model_profile_id" id="ai_model_profile_id" class="form-control">
                                                        <option value="">{{ __('None - No AI confirmation') }}</option>
                                                        @php
                                                            $aiProfiles = [];
                                                            if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                                                                try {
                                                                    $aiProfiles = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::where(function($query) {
                                                                        $query->where('created_by_user_id', auth()->id())
                                                                              ->orWhereNull('created_by_user_id');
                                                                    })->get();
                                                                } catch (\Exception $e) {
                                                                    $aiProfiles = collect([]);
                                                                }
                                                            }
                                                        @endphp
                                                        @foreach($aiProfiles as $profile)
                                                            <option value="{{ $profile->id }}" {{ old('ai_model_profile_id') == $profile->id ? 'selected' : '' }}>
                                                                {{ $profile->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">{{ __('Use AI to confirm signals before execution') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">
                                                    <i class="las la-info-circle"></i> 
                                                    {{ __('Backtest will run in the background. You can check results in the Results tab.') }}
                                                </small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="las la-play"></i> {{ __('Start Backtest') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Results Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'results' ? 'show active' : '' }}" 
                             id="results" 
                             role="tabpanel">
                            @if(isset($backtests) && $backtests->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Symbol') }}</th>
                                                <th>{{ __('Timeframe') }}</th>
                                                <th>{{ __('Period') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Win Rate') }}</th>
                                                <th>{{ __('Return') }}</th>
                                                <th>{{ __('Created') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($backtests as $backtest)
                                            <tr>
                                                <td><strong>{{ $backtest->name }}</strong></td>
                                                <td>{{ $backtest->symbol }}</td>
                                                <td>{{ strtoupper($backtest->timeframe) }}</td>
                                                <td>
                                                    {{ $backtest->start_date->format('M d, Y') }} - 
                                                    {{ $backtest->end_date->format('M d, Y') }}
                                                </td>
                                                <td>
                                                    @if($backtest->status === 'completed')
                                                        <span class="badge bg-success">{{ __('Completed') }}</span>
                                                    @elseif($backtest->status === 'running')
                                                        <span class="badge bg-info">{{ __('Running') }}</span>
                                                    @elseif($backtest->status === 'failed')
                                                        <span class="badge bg-danger">{{ __('Failed') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($backtest->status === 'completed')
                                                        {{ number_format($backtest->win_rate, 2) }}%
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($backtest->status === 'completed')
                                                        <span class="{{ $backtest->total_return >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ $backtest->total_return >= 0 ? '+' : '' }}{{ number_format($backtest->total_return, 2) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $backtest->created_at->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    @if($backtest->status === 'completed')
                                                        <a href="{{ route('user.trading.backtesting.show', $backtest->id) }}" class="btn btn-sm btn-outline-info">
                                                            <i class="las la-eye"></i> {{ __('View') }}
                                                        </a>
                                                    @elseif($backtest->status === 'failed')
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ $backtest->error_message ?? 'Unknown error' }}">
                                                            <i class="las la-exclamation-triangle"></i> {{ __('Error') }}
                                                        </button>
                                                    @else
                                                        <span class="text-muted">{{ __('In Progress') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    
                                    @if(method_exists($backtests, 'links'))
                                        <div class="mt-3">
                                            {{ $backtests->links() }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-list la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No backtest results found. Create your first backtest in the "Create Backtest" tab.') }}</p>
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

@push('scripts')
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

