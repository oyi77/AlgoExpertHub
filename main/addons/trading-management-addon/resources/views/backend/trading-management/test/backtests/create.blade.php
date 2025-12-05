@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Backtest</h4>
                    <a href="{{ route('admin.trading-management.test.backtests.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Data Availability Alert -->
                <div id="dataAvailabilityAlert" class="alert" style="display:none;">
                    <i class="fas fa-info-circle"></i> <span id="dataAvailabilityMessage"></span>
                </div>

                <form action="{{ route('admin.trading-management.test.backtests.store') }}" method="POST" id="backtestForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Backtest Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Symbol *</label>
                                <input type="text" name="symbol" id="symbol" class="form-control" placeholder="e.g., EURUSD, BTCUSDT" required>
                                <small class="form-text text-muted">Enter the trading pair symbol</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Timeframe *</label>
                                <select name="timeframe" id="timeframe" class="form-control" required>
                                    <option value="">Select Timeframe</option>
                                    <option value="1m">1 Minute</option>
                                    <option value="5m">5 Minutes</option>
                                    <option value="15m">15 Minutes</option>
                                    <option value="30m">30 Minutes</option>
                                    <option value="1h">1 Hour</option>
                                    <option value="4h">4 Hours</option>
                                    <option value="1d">1 Day</option>
                                </select>
                                <small class="form-text text-muted">Select timeframe to check data availability</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Initial Balance *</label>
                                <input type="number" name="initial_balance" class="form-control" step="0.01" min="100" value="10000" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                                <small class="form-text text-muted" id="startDateHint">Select a date within available data range</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date *</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                                <small class="form-text text-muted" id="endDateHint">Select a date within available data range</small>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range Validation Alert -->
                    <div id="dateRangeAlert" class="alert" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="dateRangeMessage"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Filter Strategy</label>
                                <select name="filter_strategy_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($filters as $filter)
                                    <option value="{{ $filter->id }}">{{ $filter->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>AI Model Profile</label>
                                <select name="ai_model_profile_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($aiModels as $model)
                                    <option value="{{ $model->id }}">{{ $model->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Trading Preset</label>
                                <select name="preset_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($presets as $preset)
                                    <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-play"></i> Run Backtest
                        </button>
                        <a href="{{ route('admin.trading-management.test.backtests.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const symbolInput = document.getElementById('symbol');
    const timeframeSelect = document.getElementById('timeframe');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const dataAvailabilityAlert = document.getElementById('dataAvailabilityAlert');
    const dataAvailabilityMessage = document.getElementById('dataAvailabilityMessage');
    const dateRangeAlert = document.getElementById('dateRangeAlert');
    const dateRangeMessage = document.getElementById('dateRangeMessage');
    const startDateHint = document.getElementById('startDateHint');
    const endDateHint = document.getElementById('endDateHint');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('backtestForm');

    let availableDateRange = null;
    let availableDates = [];

    // Check data availability when symbol or timeframe changes
    function checkDataAvailability() {
        const symbol = symbolInput.value.trim();
        const timeframe = timeframeSelect.value;

        if (!symbol || !timeframe) {
            hideDataAvailability();
            resetDateInputs();
            return;
        }

        // Show loading
        dataAvailabilityAlert.className = 'alert alert-info';
        dataAvailabilityMessage.textContent = 'Checking data availability...';
        dataAvailabilityAlert.style.display = 'block';

        fetch('{{ route("admin.trading-management.test.backtests.check-data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ symbol, timeframe })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                availableDateRange = data.date_range;
                availableDates = data.available_dates;
                
                // Update date inputs min/max
                startDateInput.min = data.date_range.min_date;
                startDateInput.max = data.date_range.max_date;
                endDateInput.min = data.date_range.min_date;
                endDateInput.max = data.date_range.max_date;
                
                // Set default dates (last 30 days if available, or full range)
                const maxDate = new Date(data.date_range.max_date);
                const minDate = new Date(data.date_range.min_date);
                const thirtyDaysAgo = new Date(maxDate);
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                
                const defaultStart = thirtyDaysAgo > minDate ? thirtyDaysAgo.toISOString().split('T')[0] : data.date_range.min_date;
                const defaultEnd = data.date_range.max_date;
                
                if (!startDateInput.value) {
                    startDateInput.value = defaultStart;
                }
                if (!endDateInput.value) {
                    endDateInput.value = defaultEnd;
                }

                // Show success message
                dataAvailabilityAlert.className = 'alert alert-success';
                dataAvailabilityMessage.innerHTML = '<strong>Data Available:</strong> ' + data.message;
                startDateHint.textContent = 'Available: ' + data.date_range.min_date + ' to ' + data.date_range.max_date + ' (' + data.date_range.total_candles + ' candles)';
                endDateHint.textContent = 'Available: ' + data.date_range.min_date + ' to ' + data.date_range.max_date;
                
                // Validate current date selection
                validateDateRange();
            } else {
                availableDateRange = null;
                availableDates = [];
                resetDateInputs();
                
                dataAvailabilityAlert.className = 'alert alert-warning';
                dataAvailabilityMessage.innerHTML = '<strong>No Data Available:</strong> ' + data.message;
                startDateHint.textContent = 'No data available for this symbol/timeframe';
                endDateHint.textContent = 'No data available for this symbol/timeframe';
                
                submitBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error checking data availability:', error);
            dataAvailabilityAlert.className = 'alert alert-danger';
            dataAvailabilityMessage.textContent = 'Error checking data availability. Please try again.';
            resetDateInputs();
        });
    }

    // Validate date range when dates change
    function validateDateRange() {
        const symbol = symbolInput.value.trim();
        const timeframe = timeframeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!symbol || !timeframe || !startDate || !endDate || !availableDateRange) {
            hideDateRangeAlert();
            return;
        }

        // Basic validation
        if (new Date(startDate) > new Date(endDate)) {
            showDateRangeError('Start date must be before end date');
            submitBtn.disabled = true;
            return;
        }

        // Check if dates are within available range
        if (startDate < availableDateRange.min_date) {
            showDateRangeError('Start date is before available data. Earliest: ' + availableDateRange.min_date);
            submitBtn.disabled = true;
            return;
        }

        if (endDate > availableDateRange.max_date) {
            showDateRangeError('End date is after available data. Latest: ' + availableDateRange.max_date);
            submitBtn.disabled = true;
            return;
        }

        // Check coverage via API
        fetch('{{ route("admin.trading-management.test.backtests.validate-dates") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ symbol, timeframe, start_date: startDate, end_date: endDate })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                hideDateRangeAlert();
                submitBtn.disabled = false;
            } else {
                const missingCount = data.missing_dates ? data.missing_dates.length : 0;
                showDateRangeWarning('Data coverage: ' + data.coverage_percent + '% (' + missingCount + ' missing dates)');
                submitBtn.disabled = false; // Allow submission but warn user
            }
        })
        .catch(error => {
            console.error('Error validating date range:', error);
            hideDateRangeAlert();
            submitBtn.disabled = false;
        });
    }

    function showDateRangeError(message) {
        dateRangeAlert.className = 'alert alert-danger';
        dateRangeMessage.textContent = message;
        dateRangeAlert.style.display = 'block';
    }

    function showDateRangeWarning(message) {
        dateRangeAlert.className = 'alert alert-warning';
        dateRangeMessage.textContent = message;
        dateRangeAlert.style.display = 'block';
    }

    function hideDateRangeAlert() {
        dateRangeAlert.style.display = 'none';
    }

    function hideDataAvailability() {
        dataAvailabilityAlert.style.display = 'none';
    }

    function resetDateInputs() {
        startDateInput.min = '';
        startDateInput.max = '';
        endDateInput.min = '';
        endDateInput.max = '';
        startDateInput.value = '';
        endDateInput.value = '';
        submitBtn.disabled = false;
    }

    // Event listeners
    symbolInput.addEventListener('blur', checkDataAvailability);
    timeframeSelect.addEventListener('change', checkDataAvailability);
    startDateInput.addEventListener('change', validateDateRange);
    endDateInput.addEventListener('change', validateDateRange);

    // Prevent form submission if data not available
    form.addEventListener('submit', function(e) {
        if (!availableDateRange) {
            e.preventDefault();
            alert('Please check data availability first by entering symbol and timeframe.');
            return false;
        }
    });
});
</script>
@endpush
