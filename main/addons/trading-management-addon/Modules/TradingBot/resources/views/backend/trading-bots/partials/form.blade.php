{{-- Reusable form partial for create/edit (Admin) --}}

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
                    @if(Route::has('admin.trading-management.config.exchange-connections.create'))
                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fa fa-plus"></i> Create New Exchange Connection
                        </a>
                    @elseif(Route::has('admin.trading-management.exchange-connections.create'))
                        <a href="{{ route('admin.trading-management.exchange-connections.create') }}" class="btn btn-primary btn-sm" target="_blank">
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
                        <option value="{{ $connection->id }}" 
                                {{ old('exchange_connection_id', isset($bot) && $bot ? $bot->exchange_connection_id : '') == $connection->id ? 'selected' : '' }}>
                            {{ $connection->name }} ({{ $connection->exchange_name }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mt-1">
                    @if(Route::has('admin.trading-management.config.exchange-connections.create'))
                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" target="_blank" class="text-primary">
                            <i class="fa fa-plus"></i> Add New Connection
                        </a>
                    @elseif(Route::has('admin.trading-management.exchange-connections.create'))
                        <a href="{{ route('admin.trading-management.exchange-connections.create') }}" target="_blank" class="text-primary">
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
                            {{ old('filter_strategy_id', $bot->filter_strategy_id ?? '') == $strategy->id ? 'selected' : '' }}>
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
                    @if(Route::has('admin.ai-model-profiles.create'))
                        <a href="{{ route('admin.ai-model-profiles.create') }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fa fa-plus"></i> Create New AI Model Profile
                        </a>
                    @elseif(Route::has('admin.trading-management.strategy.ai-models.create'))
                        <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" class="btn btn-primary btn-sm" target="_blank">
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
                                {{ old('ai_model_profile_id', isset($bot) && $bot ? $bot->ai_model_profile_id : '') == $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mt-1">
                    @if(Route::has('admin.ai-model-profiles.create'))
                        <a href="{{ route('admin.ai-model-profiles.create') }}" target="_blank" class="text-primary">
                            <i class="fa fa-plus"></i> Add New AI Model Profile
                        </a>
                    @elseif(Route::has('admin.trading-management.strategy.ai-models.create'))
                        <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" target="_blank" class="text-primary">
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
                    required
                    onchange="toggleMarketStreamFields()">
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

        {{-- Data Connection (only for MARKET_STREAM_BASED) --}}
        <div class="form-group" id="data_connection_group" style="display: {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : 'SIGNAL_BASED') == 'MARKET_STREAM_BASED' ? 'block' : 'none' }};">
            <label for="data_connection_id">{{ __('Data Connection') }} <span class="text-danger">*</span></label>
            @php
                $dataConnections = isset($dataConnections) ? $dataConnections : collect();
            @endphp
            @if($dataConnections->isEmpty())
                <div class="alert alert-warning">
                    <p class="mb-2"><i class="fa fa-exclamation-triangle"></i> No data connections available for this exchange type.</p>
                    <p class="mb-0">Please create a data connection first.</p>
                </div>
                <input type="hidden" name="data_connection_id" value="">
            @else
                <select class="form-control @error('data_connection_id') is-invalid @enderror" 
                        id="data_connection_id" 
                        name="data_connection_id">
                    <option value="">-- Select Data Connection --</option>
                    @foreach($dataConnections as $connection)
                        <option value="{{ $connection->id }}" 
                                {{ old('data_connection_id', isset($bot) && $bot ? $bot->data_connection_id : '') == $connection->id ? 'selected' : '' }}>
                            {{ $connection->name }} ({{ $connection->exchange_name }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Connection used for streaming OHLCV market data. Must match exchange connection type (crypto/fx).
                </small>
            @endif
            @error('data_connection_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Streaming Configuration (only for MARKET_STREAM_BASED) --}}
        <div id="streaming_config_group" style="display: {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : 'SIGNAL_BASED') == 'MARKET_STREAM_BASED' ? 'block' : 'none' }};">
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
                
                {{-- Manual entry textarea (shown when no symbols are available) --}}
                <textarea class="form-control @error('streaming_symbols_manual') is-invalid @enderror" 
                          id="streaming_symbols_manual" 
                          name="streaming_symbols_manual" 
                          rows="4"
                          placeholder="Enter symbols manually (one per line or comma-separated):&#10;EURUSD&#10;GBPUSD&#10;XAUUSD"
                          style="display: none; margin-top: 10px;"></textarea>
                
                <small class="form-text text-muted">
                    <span id="symbols-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> Loading symbols...</span>
                    <span id="symbols-count" style="display: none;"></span>
                    <span id="symbols-manual-hint" style="display: none;" class="text-warning">
                        <i class="fa fa-exclamation-triangle"></i> No symbols loaded. Please enter symbols manually above.
                    </span>
                    <span id="symbols-auto-hint">Select trading pairs to monitor. Symbols are loaded from the selected exchange connection.</span>
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
function toggleMarketStreamFields() {
    const tradingMode = document.getElementById('trading_mode').value;
    const dataConnectionGroup = document.getElementById('data_connection_group');
    const streamingConfigGroup = document.getElementById('streaming_config_group');
    
    if (tradingMode === 'MARKET_STREAM_BASED') {
        dataConnectionGroup.style.display = 'block';
        streamingConfigGroup.style.display = 'block';
        document.getElementById('data_connection_id').required = true;
    } else {
        dataConnectionGroup.style.display = 'none';
        streamingConfigGroup.style.display = 'none';
        document.getElementById('data_connection_id').required = false;
    }
}
</script>

{{-- Step 7: Settings --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-cog"></i> Step 7: Bot Settings
        </h5>
    </div>
    <div class="card-body">
        <div class="form-check mb-3">
            <input class="form-check-input" 
                   type="checkbox" 
                   id="is_active" 
                   name="is_active" 
                   value="1" 
                   {{ old('is_active', isset($bot) && $bot ? $bot->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                <strong>Active</strong> (Bot will execute trades when signals are published or market conditions are met)
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" 
                   type="checkbox" 
                   id="is_paper_trading" 
                   name="is_paper_trading" 
                   value="1" 
                   {{ old('is_paper_trading', isset($bot) && $bot ? $bot->is_paper_trading : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_paper_trading">
                <strong>Paper Trading Mode (Demo)</strong> (Simulate trades without real money)
            </label>
        </div>
    </div>
</div>

{{-- Submit Buttons --}}
<div class="d-flex justify-content-between">
    <a href="{{ isset($bot) ? route('admin.trading-management.trading-bots.show', $bot->id) : route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">
        <i class="fa fa-times"></i> Cancel
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> {{ isset($bot) ? 'Update' : 'Create' }} Trading Bot
    </button>
</div>

<script>
(function() {
    const exchangeConnectionSelect = document.getElementById('exchange_connection_id');
    const streamingSymbolsSelect = document.getElementById('streaming_symbols');
    const streamingSymbolsManual = document.getElementById('streaming_symbols_manual');
    const symbolsLoading = document.getElementById('symbols-loading');
    const symbolsCount = document.getElementById('symbols-count');
    const symbolsManualHint = document.getElementById('symbols-manual-hint');
    const symbolsAutoHint = document.getElementById('symbols-auto-hint');
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
            .map(s => s.trim())
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
     * Load symbols from exchange connection
     */
    function loadSymbols(connectionId) {
        if (!connectionId) {
            streamingSymbolsSelect.innerHTML = '';
            symbolsCount.style.display = 'none';
            toggleManualEntry(true);
            return;
        }

        // Show loading
        symbolsLoading.style.display = 'inline';
        symbolsCount.style.display = 'none';
        streamingSymbolsSelect.disabled = true;
        streamingSymbolsSelect.innerHTML = '<option>Loading symbols...</option>';
        toggleManualEntry(false);

        // Fetch symbols via AJAX
        fetch(`{{ route('admin.trading-management.trading-bots.exchange-symbols') }}?connection_id=${connectionId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            symbolsLoading.style.display = 'none';
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
                symbolsCount.textContent = `(${data.count} symbols available)`;
                symbolsCount.style.display = 'inline';
                toggleManualEntry(false);
            } else {
                // No symbols available - show manual entry
                streamingSymbolsSelect.innerHTML = '<option value="">No symbols available</option>';
                symbolsCount.style.display = 'none';
                toggleManualEntry(true);
                
                if (data.message) {
                    console.warn('Failed to load symbols:', data.message);
                }
            }
        })
        .catch(error => {
            symbolsLoading.style.display = 'none';
            streamingSymbolsSelect.disabled = false;
            streamingSymbolsSelect.innerHTML = '<option value="">Error loading symbols</option>';
            symbolsCount.style.display = 'none';
            toggleManualEntry(true);
            console.error('Error loading symbols:', error);
        });
    }
    
    // Parse manual entry before form submission
    const form = streamingSymbolsSelect.closest('form');
    if (form && streamingSymbolsManual) {
        form.addEventListener('submit', function(e) {
            if (streamingSymbolsManual.style.display !== 'none' && streamingSymbolsManual.value.trim()) {
                parseManualEntry();
            }
        });
    }

    // Load symbols when exchange connection changes
    exchangeConnectionSelect.addEventListener('change', function() {
        const connectionId = this.value;
        
        // Only load symbols if MARKET_STREAM_BASED mode is selected
        const tradingMode = document.getElementById('trading_mode')?.value;
        if (tradingMode === 'MARKET_STREAM_BASED' && connectionId) {
            loadSymbols(connectionId);
        }
    });

    // Load symbols on page load if exchange connection is already selected (edit mode)
    if (exchangeConnectionSelect.value) {
        const tradingMode = document.getElementById('trading_mode')?.value;
        if (tradingMode === 'MARKET_STREAM_BASED') {
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                loadSymbols(exchangeConnectionSelect.value);
            }, 100);
        }
    }

    // Also reload symbols when trading mode changes to MARKET_STREAM_BASED
    const tradingModeSelect = document.getElementById('trading_mode');
    if (tradingModeSelect) {
        tradingModeSelect.addEventListener('change', function() {
            if (this.value === 'MARKET_STREAM_BASED' && exchangeConnectionSelect.value) {
                loadSymbols(exchangeConnectionSelect.value);
            }
        });
    }
})();
</script>
