{{-- Reusable form partial for create/edit --}}

{{-- Step 1: Basic Information --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-info-circle"></i> Step 1: Basic Information
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="name">{{ __('Bot Name') }} <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control @error('name') is-invalid @enderror" 
                   id="name" 
                   name="name" 
                   value="{{ old('name', isset($bot) && $bot ? $bot->name : '') }}" 
                   required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">{{ __('Description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      id="description" 
                      name="description" 
                      rows="3">{{ old('description', $bot->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Step 2: Exchange Connection --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-exchange-alt"></i> Step 2: Exchange Connection
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="exchange_connection_id">{{ __('Select Exchange/Broker') }} <span class="text-danger">*</span></label>
            @if($connections->isEmpty())
                <div class="alert alert-info">
                    <p class="mb-2"><i class="fa fa-info-circle"></i> No exchange connections available.</p>
                    @if(Route::has('user.execution-connections.create'))
                        <a href="{{ route('user.execution-connections.create') }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fa fa-plus"></i> Create New Exchange Connection
                        </a>
                    @endif
                </div>
                <input type="hidden" name="exchange_connection_id" value="">
            @else
                <select class="form-control @error('exchange_connection_id') is-invalid @enderror" 
                        id="exchange_connection_id" 
                        name="exchange_connection_id" 
                        required>
                    <option value="">-- Select Exchange --</option>
                    @foreach($connections as $connection)
                        @php
                            // Get selected value: old input first, then bot's exchange_connection_id
                            $selectedValue = old('exchange_connection_id');
                            if (empty($selectedValue) && isset($bot) && $bot && !empty($bot->exchange_connection_id)) {
                                $selectedValue = $bot->exchange_connection_id;
                            }
                            // Compare as strings to avoid type issues
                            $isSelected = !empty($selectedValue) && (string)$selectedValue === (string)$connection->id;
                        @endphp
                        <option value="{{ $connection->id }}" 
                                {{ $isSelected ? 'selected' : '' }}>
                            {{ $connection->name }} ({{ $connection->exchange_name }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mt-1">
                    @if(Route::has('user.execution-connections.create'))
                        <a href="{{ route('user.execution-connections.create') }}" target="_blank" class="text-primary">
                            <i class="fa fa-plus"></i> Add New Connection
                        </a>
                    @endif
                </small>
            @endif
            @error('exchange_connection_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Step 3: Risk Management Preset --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-shield-alt"></i> Step 3: Risk Management Preset
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="trading_preset_id">{{ __('Select Trading Preset') }} <span class="text-danger">*</span></label>
            <select class="form-control @error('trading_preset_id') is-invalid @enderror" 
                    id="trading_preset_id" 
                    name="trading_preset_id" 
                    required>
                <option value="">-- Select Preset --</option>
                @foreach($presets as $preset)
                    <option value="{{ $preset->id }}" 
                            {{ old('trading_preset_id', $bot->trading_preset_id ?? '') == $preset->id ? 'selected' : '' }}>
                        {{ $preset->name }}
                    </option>
                @endforeach
            </select>
            @error('trading_preset_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Step 4: Technical Indicator Filter (Optional) --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-chart-line"></i> Step 4: Technical Indicator Filter (Optional)
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="filter_strategy_id">{{ __('Select Filter Strategy') }}</label>
            <select class="form-control @error('filter_strategy_id') is-invalid @enderror" 
                    id="filter_strategy_id" 
                    name="filter_strategy_id">
                <option value="">-- No Filter --</option>
                @foreach($filterStrategies as $strategy)
                    <option value="{{ $strategy->id }}" 
                            {{ old('filter_strategy_id', isset($bot) && $bot ? $bot->filter_strategy_id : '') == $strategy->id ? 'selected' : '' }}>
                        {{ $strategy->name }}
                    </option>
                @endforeach
            </select>
            @error('filter_strategy_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Step 5: AI Confirmation (Optional) --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-brain"></i> Step 5: AI Market Confirmation (Optional)
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="ai_model_profile_id">{{ __('Select AI Model Profile') }}</label>
            @if($aiProfiles->isEmpty())
                <div class="alert alert-info">
                    <p class="mb-2"><i class="fa fa-info-circle"></i> No AI model profiles available.</p>
                    @if(Route::has('user.ai-model-profiles.create'))
                        <a href="{{ route('user.ai-model-profiles.create') }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fa fa-plus"></i> Create New AI Model Profile
                        </a>
                    @endif
                </div>
                <input type="hidden" name="ai_model_profile_id" value="">
            @else
                <select class="form-control @error('ai_model_profile_id') is-invalid @enderror" 
                        id="ai_model_profile_id" 
                        name="ai_model_profile_id">
                    <option value="">-- No AI Confirmation --</option>
                    @foreach($aiProfiles as $profile)
                        <option value="{{ $profile->id }}" 
                                {{ old('ai_model_profile_id', $bot->ai_model_profile_id ?? '') == $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mt-1">
                    @if(Route::has('user.ai-model-profiles.create'))
                        <a href="{{ route('user.ai-model-profiles.create') }}" target="_blank" class="text-primary">
                            <i class="fa fa-plus"></i> Add New AI Model Profile
                        </a>
                    @endif
                </small>
            @endif
            @error('ai_model_profile_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Step 6: Trading Mode --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-cogs"></i> Step 6: Trading Mode
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="trading_mode">{{ __('Trading Mode') }} <span class="text-danger">*</span></label>
            <select class="form-control @error('trading_mode') is-invalid @enderror" 
                    id="trading_mode" 
                    name="trading_mode" 
                    required>
                <option value="SIGNAL_BASED" {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : 'SIGNAL_BASED') == 'SIGNAL_BASED' ? 'selected' : '' }}>
                    Signal-Based (Execute only on published signals)
                </option>
                <option value="MARKET_STREAM_BASED" {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : '') == 'MARKET_STREAM_BASED' ? 'selected' : '' }}>
                    Market Stream-Based (Stream OHLCV data and apply technical indicators)
                </option>
            </select>
            <small class="form-text text-muted">
                <strong>Signal-Based:</strong> Bot executes trades only when signals are published.<br>
                <strong>Market Stream-Based:</strong> Bot continuously streams OHLCV data, applies technical indicators, and makes trading decisions based on market conditions.
            </small>
            @error('trading_mode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Data Connection (for MARKET_STREAM_BASED) - Auto-filled from exchange connection --}}
        <div class="form-group" id="data_connection_group" style="display: {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : '') == 'MARKET_STREAM_BASED' ? 'block' : 'none' }};">
            <label for="data_connection_id">{{ __('Data Connection') }} <span class="text-danger">*</span></label>
            <select class="form-control @error('data_connection_id') is-invalid @enderror" 
                    id="data_connection_id" 
                    name="data_connection_id"
                    required>
                <option value="">-- Auto-filled from Exchange Connection --</option>
                @if(isset($connections) && $connections->count() > 0)
                    @foreach($connections as $conn)
                        <option value="{{ $conn->id }}" 
                                {{ old('data_connection_id', isset($bot) && $bot ? $bot->data_connection_id : '') == $conn->id ? 'selected' : '' }}>
                            {{ $conn->name }} ({{ $conn->provider ?? $conn->exchange_name }})
                        </option>
                    @endforeach
                @endif
            </select>
            <small class="form-text text-muted">
                Connection used for streaming OHLCV market data. Will be auto-filled from the selected exchange connection above.
            </small>
            @error('data_connection_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Streaming Configuration (for MARKET_STREAM_BASED) --}}
        <div id="streaming_config_group" style="display: {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : '') == 'MARKET_STREAM_BASED' ? 'block' : 'none' }};">
            <div class="form-group">
                <label for="streaming_symbols">{{ __('Trading Symbols') }} <span class="text-danger">*</span></label>
                
                {{-- Multi-select dropdown (shown when symbols are available) --}}
                <select class="form-control @error('streaming_symbols') is-invalid @enderror" 
                        id="streaming_symbols" 
                        name="streaming_symbols[]" 
                        multiple
                        size="8"
                        style="min-height: 200px;">
                    @php
                        $selectedSymbols = old('streaming_symbols', isset($bot) && $bot && $bot->streaming_symbols ? $bot->streaming_symbols : []);
                    @endphp
                    @if(isset($bot) && $bot && $bot->exchange_connection_id)
                        {{-- Symbols will be loaded via AJAX when exchange connection is selected --}}
                        @foreach($selectedSymbols as $symbol)
                            <option value="{{ $symbol }}" selected>{{ $symbol }}</option>
                        @endforeach
                    @endif
                </select>
                
                {{-- Manual entry textarea (shown when no symbols are available or user wants to customize) --}}
                <textarea class="form-control @error('streaming_symbols_manual') is-invalid @enderror" 
                          id="streaming_symbols_manual" 
                          name="streaming_symbols_manual" 
                          rows="4"
                          placeholder="Enter symbols manually (one per line or comma-separated):&#10;EURUSD&#10;GBPUSD&#10;XAUUSD&#10;BTCUSDT"
                          style="display: none; margin-top: 10px;">{{ old('streaming_symbols_manual', isset($bot) && $bot && $bot->streaming_symbols ? implode("\n", $bot->streaming_symbols) : '') }}</textarea>
                
                <small class="form-text text-muted">
                    <span id="symbols-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> Loading symbols...</span>
                    <span id="symbols-count" style="display: none;"></span>
                    <span id="symbols-manual-hint" style="display: none;" class="text-warning">
                        <i class="fa fa-exclamation-triangle"></i> No symbols loaded. Please enter symbols manually above (e.g., XAUUSD, EURUSD, BTCUSDT).
                    </span>
                    <span id="symbols-auto-hint">Select trading pairs to monitor. Symbols are loaded from the selected exchange connection. You can also enter symbols manually.</span>
                </small>
                @error('streaming_symbols')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('streaming_symbols_manual')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="streaming_timeframes">{{ __('Timeframes') }} <span class="text-danger">*</span></label>
                <select class="form-control @error('streaming_timeframes') is-invalid @enderror" 
                        id="streaming_timeframes" 
                        name="streaming_timeframes[]" 
                        multiple
                        size="5">
                    @php
                        $timeframes = ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w'];
                        $selectedTimeframes = old('streaming_timeframes', isset($bot) && $bot && $bot->streaming_timeframes ? $bot->streaming_timeframes : []);
                    @endphp
                    @foreach($timeframes as $tf)
                        <option value="{{ $tf }}" {{ in_array($tf, $selectedTimeframes) ? 'selected' : '' }}>{{ $tf }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Hold Ctrl/Cmd to select multiple timeframes. Bot will analyze all selected timeframes.
                </small>
                @error('streaming_timeframes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="market_analysis_interval">{{ __('Market Analysis Interval (seconds)') }}</label>
                        <input type="number" 
                               class="form-control @error('market_analysis_interval') is-invalid @enderror" 
                               id="market_analysis_interval" 
                               name="market_analysis_interval" 
                               value="{{ old('market_analysis_interval', isset($bot) && $bot ? $bot->market_analysis_interval : 60) }}"
                               min="10"
                               step="1">
                        <small class="form-text text-muted">
                            How often to analyze market and make trading decisions (default: 60 seconds).
                        </small>
                        @error('market_analysis_interval')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="position_monitoring_interval">{{ __('Position Monitoring Interval (seconds)') }}</label>
                        <input type="number" 
                               class="form-control @error('position_monitoring_interval') is-invalid @enderror" 
                               id="position_monitoring_interval" 
                               name="position_monitoring_interval" 
                               value="{{ old('position_monitoring_interval', isset($bot) && $bot ? $bot->position_monitoring_interval : 5) }}"
                               min="1"
                               step="1">
                        <small class="form-text text-muted">
                            How often to check stop loss and take profit (default: 5 seconds).
                        </small>
                        @error('position_monitoring_interval')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const exchangeConnectionSelect = document.getElementById('exchange_connection_id');
    const dataConnectionSelect = document.getElementById('data_connection_id');
    const streamingSymbolsSelect = document.getElementById('streaming_symbols');
    const streamingSymbolsManual = document.getElementById('streaming_symbols_manual');
    const symbolsLoading = document.getElementById('symbols-loading');
    const symbolsCount = document.getElementById('symbols-count');
    const symbolsManualHint = document.getElementById('symbols-manual-hint');
    const symbolsAutoHint = document.getElementById('symbols-auto-hint');
    const tradingModeSelect = document.getElementById('trading_mode');
    const dataConnectionGroup = document.getElementById('data_connection_group');
    const streamingConfigGroup = document.getElementById('streaming_config_group');
    
    if (!exchangeConnectionSelect || !streamingSymbolsSelect) {
        return; // Elements not found, skip initialization
    }

    // Store initially selected symbols (for edit mode)
    const initiallySelectedSymbols = Array.from(streamingSymbolsSelect.selectedOptions).map(opt => opt.value);
    
    // If edit mode and symbols exist, populate manual field as fallback
    if (initiallySelectedSymbols.length > 0 && streamingSymbolsManual) {
        streamingSymbolsManual.value = initiallySelectedSymbols.join('\n');
    }

    /**
     * Show/hide manual entry based on symbols availability
     */
    function toggleManualEntry(showManual) {
        if (!streamingSymbolsManual || !symbolsManualHint || !symbolsAutoHint) return;
        
        if (showManual) {
            // Show manual entry, hide select
            streamingSymbolsSelect.style.display = 'none';
            streamingSymbolsManual.style.display = 'block';
            symbolsManualHint.style.display = 'inline';
            symbolsAutoHint.style.display = 'none';
            streamingSymbolsManual.required = true;
            streamingSymbolsSelect.required = false;
        } else {
            // Show select, hide manual entry
            streamingSymbolsSelect.style.display = 'block';
            streamingSymbolsManual.style.display = 'none';
            symbolsManualHint.style.display = 'none';
            symbolsAutoHint.style.display = 'inline';
            streamingSymbolsManual.required = false;
            streamingSymbolsSelect.required = true;
        }
    }

    /**
     * Parse manual entry and populate select (for form submission)
     */
    function parseManualEntry() {
        if (!streamingSymbolsManual) return;
        
        const manualText = streamingSymbolsManual.value.trim();
        if (!manualText) return;
        
        // Parse: split by newline or comma, trim each
        const symbols = manualText
            .split(/[\n,]+/)
            .map(s => s.trim().toUpperCase())
            .filter(s => s.length > 0);
        
        // Clear select and add parsed symbols
        streamingSymbolsSelect.innerHTML = '';
        symbols.forEach(symbol => {
            const option = document.createElement('option');
            option.value = symbol;
            option.textContent = symbol;
            option.selected = true;
            streamingSymbolsSelect.appendChild(option);
        });
    }

    /**
     * Auto-fill data connection from exchange connection
     */
    function autoFillDataConnection(exchangeConnectionId) {
        if (!dataConnectionSelect || !exchangeConnectionId) return;
        
        // Check if the exchange connection ID exists in data connection options
        const optionExists = Array.from(dataConnectionSelect.options).some(opt => opt.value === exchangeConnectionId);
        
        if (optionExists) {
            // Auto-fill data connection with the same exchange connection
            dataConnectionSelect.value = exchangeConnectionId;
            
            // Trigger change event to ensure validation recognizes the value
            const changeEvent = new Event('change', { bubbles: true });
            dataConnectionSelect.dispatchEvent(changeEvent);
        } else {
            console.warn('Exchange connection ID not found in data connection options:', exchangeConnectionId);
        }
    }

    /**
     * Load symbols from exchange connection
     */
    function loadSymbols(connectionId) {
        if (!connectionId) {
            streamingSymbolsSelect.innerHTML = '';
            if (symbolsCount) symbolsCount.style.display = 'none';
            toggleManualEntry(true);
            return;
        }

        // Show loading
        if (symbolsLoading) symbolsLoading.style.display = 'inline';
        if (symbolsCount) symbolsCount.style.display = 'none';
        streamingSymbolsSelect.disabled = true;
        streamingSymbolsSelect.innerHTML = '<option>Loading symbols...</option>';
        toggleManualEntry(false);

        // Fetch symbols via AJAX
        fetch(`{{ route('user.trading-management.trading-bots.exchange-symbols') }}?connection_id=${connectionId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (symbolsLoading) symbolsLoading.style.display = 'none';
            streamingSymbolsSelect.disabled = false;
            streamingSymbolsSelect.innerHTML = '';

            if (data.success && data.symbols && data.symbols.length > 0) {
                // Populate select with symbols
                data.symbols.forEach(symbol => {
                    const option = document.createElement('option');
                    option.value = symbol;
                    option.textContent = symbol;
                    
                    // Preserve initially selected symbols (for edit mode)
                    if (initiallySelectedSymbols.includes(symbol)) {
                        option.selected = true;
                    }
                    
                    streamingSymbolsSelect.appendChild(option);
                });

                // Show count
                if (symbolsCount) {
                    symbolsCount.textContent = `(${data.count} symbols available)`;
                    symbolsCount.style.display = 'inline';
                }
                toggleManualEntry(false);
            } else {
                // No symbols available - show manual entry
                streamingSymbolsSelect.innerHTML = '<option value="">No symbols available</option>';
                if (symbolsCount) symbolsCount.style.display = 'none';
                toggleManualEntry(true);
                
                if (data.message) {
                    console.warn('Failed to load symbols:', data.message);
                }
            }
        })
        .catch(error => {
            if (symbolsLoading) symbolsLoading.style.display = 'none';
            streamingSymbolsSelect.disabled = false;
            streamingSymbolsSelect.innerHTML = '<option value="">Error loading symbols</option>';
            if (symbolsCount) symbolsCount.style.display = 'none';
            toggleManualEntry(true);
            console.error('Error loading symbols:', error);
        });
    }

    /**
     * Toggle market stream fields visibility
     */
    function toggleMarketStreamFields() {
        const tradingMode = tradingModeSelect ? tradingModeSelect.value : '';
        
        if (tradingMode === 'MARKET_STREAM_BASED') {
            if (dataConnectionGroup) dataConnectionGroup.style.display = 'block';
            if (streamingConfigGroup) streamingConfigGroup.style.display = 'block';
            if (dataConnectionSelect) dataConnectionSelect.required = true;
        } else {
            if (dataConnectionGroup) dataConnectionGroup.style.display = 'none';
            if (streamingConfigGroup) streamingConfigGroup.style.display = 'none';
            if (dataConnectionSelect) dataConnectionSelect.required = false;
        }
    }
    
    // Parse manual entry and ensure data connection is filled before form submission
    const form = streamingSymbolsSelect.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Parse manual symbols if needed
            if (streamingSymbolsManual && streamingSymbolsManual.style.display !== 'none' && streamingSymbolsManual.value.trim()) {
                parseManualEntry();
            }
            
            // Auto-fill data connection if not set and MARKET_STREAM_BASED mode
            const tradingMode = tradingModeSelect ? tradingModeSelect.value : '';
            if (tradingMode === 'MARKET_STREAM_BASED' && dataConnectionSelect) {
                const exchangeConnectionId = exchangeConnectionSelect.value;
                if (!dataConnectionSelect.value && exchangeConnectionId) {
                    // Check if the exchange connection ID exists in data connection options
                    const optionExists = Array.from(dataConnectionSelect.options).some(opt => opt.value === exchangeConnectionId);
                    if (optionExists) {
                        dataConnectionSelect.value = exchangeConnectionId;
                    } else {
                        // If option doesn't exist, prevent submission and show error
                        e.preventDefault();
                        alert('Please select a valid data connection. The exchange connection you selected is not available as a data connection.');
                        return false;
                    }
                }
            }
        });
    }

    // Auto-fill data connection and load symbols when exchange connection changes
    exchangeConnectionSelect.addEventListener('change', function() {
        const connectionId = this.value;
        
        // Auto-fill data connection
        autoFillDataConnection(connectionId);
        
        // Only load symbols if MARKET_STREAM_BASED mode is selected
        const tradingMode = tradingModeSelect ? tradingModeSelect.value : '';
        if (tradingMode === 'MARKET_STREAM_BASED' && connectionId) {
            loadSymbols(connectionId);
        }
    });

    // Load symbols on page load if exchange connection is already selected (edit mode)
    if (exchangeConnectionSelect.value) {
        const tradingMode = tradingModeSelect ? tradingModeSelect.value : '';
        if (tradingMode === 'MARKET_STREAM_BASED') {
            // Auto-fill data connection
            autoFillDataConnection(exchangeConnectionSelect.value);
            
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                loadSymbols(exchangeConnectionSelect.value);
            }, 100);
        }
    }

    // Also reload symbols when trading mode changes to MARKET_STREAM_BASED
    if (tradingModeSelect) {
        tradingModeSelect.addEventListener('change', function() {
            toggleMarketStreamFields();
            
            if (this.value === 'MARKET_STREAM_BASED' && exchangeConnectionSelect.value) {
                // Auto-fill data connection
                autoFillDataConnection(exchangeConnectionSelect.value);
                loadSymbols(exchangeConnectionSelect.value);
            }
        });
    }

    // Initialize on page load
    toggleMarketStreamFields();
})();
</script>

{{-- Step 7: Paper Trading Mode --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-flask"></i> Step 7: Trading Settings
        </h5>
    </div>
    <div class="card-body">
        <div class="form-check">
            <input class="form-check-input" 
                   type="checkbox" 
                   id="is_paper_trading" 
                   name="is_paper_trading" 
                   value="1" 
                   {{ old('is_paper_trading', isset($bot) && $bot ? $bot->is_paper_trading : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_paper_trading">
                <strong>Paper Trading Mode (Demo)</strong>
            </label>
        </div>
    </div>
</div>

{{-- Submit Buttons --}}
<div class="d-flex justify-content-between">
    <a href="{{ isset($bot) ? route('user.trading-management.trading-bots.show', $bot->id) : route('user.trading-management.trading-bots.index') }}" class="btn btn-secondary">
        <i class="fa fa-times"></i> Cancel
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> {{ isset($bot) ? 'Update' : 'Create' }} Trading Bot
    </button>
</div>
