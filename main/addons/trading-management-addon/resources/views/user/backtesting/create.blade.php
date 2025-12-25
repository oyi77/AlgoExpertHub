@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <h4>{{ __('Create Backtest') }}</h4>
                            <p class="text-muted mb-0">{{ __('Test your trading strategy on historical data') }}</p>
                        </div>
                        <a href="{{ route('user.backtesting.index') }}" class="btn btn-sm btn-secondary">
                            <i class="las la-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('user.backtesting.store') }}" method="POST" id="backtestForm">
                        @csrf

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('Backtest Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" required placeholder="{{ __('e.g., BTC Trend Following Strategy') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">{{ __('A descriptive name for this backtest') }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">{{ __('Description') }}</label>
                                    <input type="text" name="description" id="description" class="form-control" 
                                           value="{{ old('description') }}" placeholder="{{ __('Optional description') }}">
                                    <small class="text-muted">{{ __('Brief description of the strategy being tested') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Market Selection -->
                        <h5 class="mb-3">{{ __('Market Selection') }}</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="symbol">{{ __('Symbol') }} <span class="text-danger">*</span></label>
                                    <select name="symbol" id="symbol" class="form-control @error('symbol') is-invalid @enderror" required>
                                        <option value="">{{ __('Select Symbol') }}</option>
                                        @foreach($symbols as $symbol)
                                            <option value="{{ $symbol }}" {{ old('symbol') === $symbol ? 'selected' : '' }}>
                                                {{ $symbol }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="timeframe">{{ __('Timeframe') }} <span class="text-danger">*</span></label>
                                    <select name="timeframe" id="timeframe" class="form-control @error('timeframe') is-invalid @enderror" required>
                                        <option value="">{{ __('Select Timeframe') }}</option>
                                        @foreach($timeframes as $value => $label)
                                            <option value="{{ $value }}" {{ old('timeframe') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('timeframe')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <h5 class="mb-3">{{ __('Date Range') }}</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" 
                                           class="form-control @error('start_date') is-invalid @enderror" 
                                           value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date" 
                                           class="form-control @error('end_date') is-invalid @enderror" 
                                           value="{{ old('end_date') }}" required max="{{ date('Y-m-d') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Quick Presets -->
                        <div class="mb-4">
                            <label class="form-label">{{ __('Quick Date Presets') }}</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary date-preset" data-months="1">{{ __('Last Month') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary date-preset" data-months="3">{{ __('Last 3 Months') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary date-preset" data-months="6">{{ __('Last 6 Months') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary date-preset" data-months="12">{{ __('Last Year') }}</button>
                            </div>
                        </div>

                        <!-- Strategy Configuration -->
                        <h5 class="mb-3">{{ __('Strategy Configuration') }}</h5>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="preset_id">{{ __('Trading Preset') }} <span class="text-danger">*</span></label>
                                    <select name="preset_id" id="preset_id" class="form-control @error('preset_id') is-invalid @enderror" required>
                                        <option value="">{{ __('Select Preset') }}</option>
                                        @foreach($presets as $preset)
                                            <option value="{{ $preset->id }}" {{ old('preset_id') == $preset->id ? 'selected' : '' }}>
                                                {{ $preset->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('preset_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">{{ __('Risk management and position sizing') }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filter_strategy_id">{{ __('Filter Strategy') }}</label>
                                    <select name="filter_strategy_id" id="filter_strategy_id" class="form-control">
                                        <option value="">{{ __('None (Optional)') }}</option>
                                        @foreach($filterStrategies as $strategy)
                                            <option value="{{ $strategy->id }}" {{ old('filter_strategy_id') == $strategy->id ? 'selected' : '' }}>
                                                {{ $strategy->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('Technical indicator filters') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ai_model_profile_id">{{ __('AI Model Profile') }}</label>
                                    <select name="ai_model_profile_id" id="ai_model_profile_id" class="form-control">
                                        <option value="">{{ __('None (Optional)') }}</option>
                                        @foreach($aiProfiles as $profile)
                                            <option value="{{ $profile->id }}" {{ old('ai_model_profile_id') == $profile->id ? 'selected' : '' }}>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('AI-based signal confirmation') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Initial Balance -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="initial_balance">{{ __('Initial Balance') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="initial_balance" id="initial_balance" 
                                           class="form-control @error('initial_balance') is-invalid @enderror" 
                                           value="{{ old('initial_balance', 10000) }}" required min="100" max="1000000" step="100">
                                    @error('initial_balance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <small class="text-muted">{{ __('Starting capital for the backtest (min: $100, max: $1,000,000)') }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn sp_theme_btn" id="submitBtn">
                                <i class="las la-save"></i> {{ __('Create Backtest') }}
                            </button>
                            <a href="{{ route('user.backtesting.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Date preset buttons
    $('.date-preset').on('click', function() {
        const months = $(this).data('months');
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - months);
        
        $('#end_date').val(endDate.toISOString().split('T')[0]);
        $('#start_date').val(startDate.toISOString().split('T')[0]);
    });
    
    // Form submission
    $('#backtestForm').on('submit', function(e) {
        const btn = $('#submitBtn');
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> {{ __("Creating...") }}');
    });
});
</script>
@endpush
@endsection
