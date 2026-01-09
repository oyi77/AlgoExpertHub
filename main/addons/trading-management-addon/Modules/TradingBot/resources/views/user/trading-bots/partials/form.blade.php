{{-- Reusable form partial for create/edit --}}

{{-- Progress Stepper --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="progress-stepper" id="bot-creation-progress">
            <div class="stepper-item active" data-step="1">
                <div class="stepper-icon"><i class="fa fa-info-circle"></i></div>
                <div class="stepper-label">Basic Info</div>
            </div>
            <div class="stepper-item" data-step="2">
                <div class="stepper-icon"><i class="fa fa-exchange-alt"></i></div>
                <div class="stepper-label">Connection</div>
            </div>
            <div class="stepper-item" data-step="3">
                <div class="stepper-icon"><i class="fa fa-shield-alt"></i></div>
                <div class="stepper-label">Preset</div>
            </div>
            <div class="stepper-item" data-step="4">
                <div class="stepper-icon"><i class="fa fa-chart-line"></i></div>
                <div class="stepper-label">Filter</div>
            </div>
            <div class="stepper-item" data-step="5">
                <div class="stepper-icon"><i class="fa fa-brain"></i></div>
                <div class="stepper-label">AI</div>
            </div>
            <div class="stepper-item" data-step="6">
                <div class="stepper-icon"><i class="fa fa-cogs"></i></div>
                <div class="stepper-label">Mode</div>
            </div>
            <div class="stepper-item" data-step="7">
                <div class="stepper-icon"><i class="fa fa-flask"></i></div>
                <div class="stepper-label">Settings</div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-stepper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 20px 0;
}

.progress-stepper::before {
    content: '';
    position: absolute;
    top: 30px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0e0e0;
    z-index: 0;
}

.stepper-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.stepper-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.stepper-item.active .stepper-icon {
    background: #007bff;
    color: white;
}

.stepper-item.completed .stepper-icon {
    background: #28a745;
    color: white;
}

.stepper-label {
    font-size: 12px;
    color: #666;
    text-align: center;
}

.stepper-item.active .stepper-label {
    color: #007bff;
    font-weight: 600;
}

.stepper-item.completed .stepper-label {
    color: #28a745;
}
</style>

<script>
(function() {
    // Update progress stepper based on form completion
    function updateProgressStepper() {
        const name = document.getElementById('name')?.value;
        const connectionId = document.getElementById('exchange_connection_id')?.value;
        const presetId = document.getElementById('trading_preset_id')?.value;
        const filterId = document.getElementById('filter_strategy_id')?.value;
        const aiId = document.getElementById('ai_model_profile_id')?.value;
        const tradingMode = document.getElementById('trading_mode')?.value;
        const paperTrading = document.getElementById('is_paper_trading')?.checked;
        
        // Update step 1 (Basic Info)
        if (name) {
            markStepComplete(1);
        } else {
            markStepActive(1);
        }
        
        // Update step 2 (Connection)
        if (connectionId) {
            markStepComplete(2);
        } else {
            markStepInactive(2);
        }
        
        // Update step 3 (Preset)
        if (presetId) {
            markStepComplete(3);
        } else {
            markStepInactive(3);
        }
        
        // Update step 4 (Filter) - optional
        if (filterId) {
            markStepComplete(4);
        } else {
            markStepInactive(4);
        }
        
        // Update step 5 (AI) - optional
        if (aiId) {
            markStepComplete(5);
        } else {
            markStepInactive(5);
        }
        
        // Update step 6 (Mode)
        if (tradingMode) {
            markStepComplete(6);
        } else {
            markStepInactive(6);
        }
        
        // Update step 7 (Settings)
        if (paperTrading !== null) {
            markStepComplete(7);
        } else {
            markStepInactive(7);
        }
    }
    
    function markStepActive(step) {
        const item = document.querySelector(`.stepper-item[data-step="${step}"]`);
        if (item) {
            item.classList.remove('completed');
            item.classList.add('active');
        }
    }
    
    function markStepComplete(step) {
        const item = document.querySelector(`.stepper-item[data-step="${step}"]`);
        if (item) {
            item.classList.remove('active');
            item.classList.add('completed');
        }
    }
    
    function markStepInactive(step) {
        const item = document.querySelector(`.stepper-item[data-step="${step}"]`);
        if (item) {
            item.classList.remove('active', 'completed');
        }
    }
    
    // Listen for form changes
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('bot-form') || document.querySelector('form');
        if (form) {
            form.addEventListener('input', updateProgressStepper);
            form.addEventListener('change', updateProgressStepper);
            updateProgressStepper(); // Initial update
        }
    });
})();
</script>

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
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createConnectionModal">
                        <i class="fa fa-plus"></i> Create New Exchange Connection
                    </button>
                </div>
                <input type="hidden" name="exchange_connection_id" value="">
            @else
                <div class="input-group">
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
                                
                                // Determine health status
                                $healthStatus = 'unknown';
                                $healthBadge = '';
                                if ($connection->status === 'active' && $connection->is_active) {
                                    $healthStatus = 'healthy';
                                    $healthBadge = '<span class="badge bg-success ms-2"><i class="fa fa-check-circle"></i> Active</span>';
                                } elseif ($connection->status === 'error' || !$connection->is_active) {
                                    $healthStatus = 'error';
                                    $healthBadge = '<span class="badge bg-danger ms-2"><i class="fa fa-exclamation-circle"></i> Error</span>';
                                } elseif ($connection->status === 'testing') {
                                    $healthStatus = 'testing';
                                    $healthBadge = '<span class="badge bg-warning ms-2"><i class="fa fa-spinner fa-spin"></i> Testing</span>';
                                } else {
                                    $healthStatus = 'inactive';
                                    $healthBadge = '<span class="badge bg-secondary ms-2"><i class="fa fa-pause-circle"></i> Inactive</span>';
                                }
                            @endphp
                            <option value="{{ $connection->id }}" 
                                    data-health="{{ $healthStatus }}"
                                    data-exchange-name="{{ strtolower($connection->exchange_name ?? $connection->provider ?? '') }}"
                                    data-connection-type="{{ $connection->connection_type }}"
                                    {{ $isSelected ? 'selected' : '' }}>
                                {{ $connection->name }} ({{ $connection->exchange_name }})
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createConnectionModal" title="Create New Connection">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                
                {{-- Connection Health Info --}}
                <div id="connection-health-info" class="mt-2" style="display: none;">
                    <div id="connection-health-badge" class="mb-2"></div>
                    <div id="connection-requirements" class="alert alert-info" style="display: none;">
                        <small><strong>Requirements:</strong> <span id="connection-requirements-text"></span></small>
                    </div>
                </div>
                
                <small class="form-text text-muted mt-1">
                    <i class="fa fa-info-circle"></i> Select an active connection. Connections with errors need to be fixed before use.
                </small>
            @endif
            @error('exchange_connection_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Inline Connection Creation Modal --}}
<div class="modal fade" id="createConnectionModal" tabindex="-1" aria-labelledby="createConnectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createConnectionModalLabel">
                    <i class="fa fa-plus"></i> Create New Exchange Connection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="inline-connection-form">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="inline_connection_name">{{ __('Connection Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="inline_connection_name" name="name" required placeholder="e.g., My Binance Account">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="inline_exchange_type">{{ __('Exchange Type') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="inline_exchange_type" name="exchange_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="CRYPTO_EXCHANGE">Cryptocurrency Exchange</option>
                            <option value="FX_BROKER">Forex Broker (MT4/MT5)</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3" id="inline_exchange_name_group" style="display: none;">
                        <label for="inline_exchange_name">{{ __('Exchange/Provider') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="inline_exchange_name" name="exchange_name" required>
                            <option value="">-- Select Exchange --</option>
                        </select>
                        <small class="form-text text-muted" id="inline_exchange_hint"></small>
                    </div>
                    
                    <div class="form-group mb-3" id="inline_connection_type_group" style="display: none;">
                        <label for="inline_connection_type">{{ __('Connection Purpose') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="inline_connection_type" name="connection_type" required>
                            <option value="BOTH">Both (Data + Execution)</option>
                            <option value="EXECUTION_ONLY">Execution Only</option>
                            <option value="DATA_ONLY">Data Only</option>
                        </select>
                    </div>
                    
                    {{-- Dynamic Credential Fields --}}
                    <div id="inline_credentials_group" style="display: none;">
                        <h6 class="mt-3 mb-2">{{ __('API Credentials') }}</h6>
                        
                        <div class="form-group mb-3" id="inline_api_key_field">
                            <label for="inline_api_key">{{ __('API Key') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inline_api_key" name="credentials[api_key]" placeholder="Enter your API key">
                            <small class="form-text text-muted">Found in your exchange account settings under API Management</small>
                        </div>
                        
                        <div class="form-group mb-3" id="inline_api_secret_field">
                            <label for="inline_api_secret">{{ __('API Secret') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="inline_api_secret" name="credentials[api_secret]" placeholder="Enter your API secret">
                            <small class="form-text text-muted">Shown only once when you create the API key</small>
                        </div>
                        
                        <div class="form-group mb-3" id="inline_api_passphrase_field" style="display: none;">
                            <label for="inline_api_passphrase">{{ __('API Passphrase') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="inline_api_passphrase" name="credentials[api_passphrase]" placeholder="Enter your API passphrase">
                            <small class="form-text text-muted">Required for OKX, Kucoin, and Coinbase Pro</small>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3" id="inline_preset_group" style="display: none;">
                        <label for="inline_preset_id">{{ __('Trading Preset (Optional)') }}</label>
                        <select class="form-control" id="inline_preset_id" name="preset_id">
                            <option value="">-- No Preset --</option>
                            @foreach($presets ?? [] as $preset)
                                <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div id="inline_connection_errors" class="alert alert-danger" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-inline-connection">
                    <i class="fa fa-save"></i> Create & Test Connection
                </button>
            </div>
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
            <small class="form-text text-muted">{{ __('Select a risk management preset that defines position sizing, stop loss, take profit, and other risk parameters for your bot.') }}</small>
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
            <small class="form-text text-muted">{{ __('Optional: Apply technical indicator filters (e.g., MA100, MA10, Parabolic SAR) to filter trading signals before execution.') }}</small>
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
                <small class="form-text text-muted">{{ __('Optional: Use AI market confirmation to validate signals before execution. AI analyzes market conditions and provides a safety score.') }}</small>
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
            {{-- Hidden input to ensure value is sent when unchecked --}}
            <input type="hidden" name="is_paper_trading" value="0">
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

{{-- Step 8: Advanced Configuration (Optional) --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-cog"></i> Step 8: Advanced Configuration (Optional)
        </h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="data_fetch_interval">{{ __('Data Fetch Interval (seconds)') }}</label>
            <input type="number" 
                   class="form-control @error('data_fetch_interval') is-invalid @enderror" 
                   id="data_fetch_interval" 
                   name="data_fetch_interval" 
                   value="{{ old('data_fetch_interval', isset($bot) && $bot ? ($bot->data_fetch_interval ?? 60) : 60) }}" 
                   min="10" 
                   max="3600">
            <small class="form-text text-muted">{{ __('Interval in seconds for fetching market data. Default: 60 seconds.') }}</small>
            @error('data_fetch_interval')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>{{ __('Filter Priority Configuration') }}</label>
            <small class="form-text text-muted d-block mb-2">{{ __('Configure multiple filters with priority order. Leave empty to use single filter strategy above.') }}</small>
            <div id="filter-priority-container">
                @php
                    $filterPriority = old('filter_priority', isset($bot) && $bot && $bot->filter_priority ? $bot->filter_priority : []);
                @endphp
                @if(!empty($filterPriority) && is_array($filterPriority))
                    @foreach($filterPriority as $index => $filterConfig)
                        <div class="filter-priority-item mb-3 p-3 border rounded">
                            <div class="row">
                                <div class="col-md-5">
                                    <label>Filter Strategy</label>
                                    <select name="filter_priority[{{ $index }}][filter_strategy_id]" class="form-control">
                                        <option value="">-- Select Filter --</option>
                                        @foreach($filterStrategies as $strategy)
                                            <option value="{{ $strategy->id }}" 
                                                    {{ ($filterConfig['filter_strategy_id'] ?? '') == $strategy->id ? 'selected' : '' }}>
                                                {{ $strategy->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Priority</label>
                                    <input type="number" name="filter_priority[{{ $index }}][priority]" 
                                           class="form-control" 
                                           value="{{ $filterConfig['priority'] ?? ($index + 1) }}" 
                                           min="1">
                                </div>
                                <div class="col-md-2">
                                    <label>Logic</label>
                                    <select name="filter_priority[{{ $index }}][logic]" class="form-control">
                                        <option value="AND" {{ ($filterConfig['logic'] ?? 'AND') == 'AND' ? 'selected' : '' }}>AND</option>
                                        <option value="OR" {{ ($filterConfig['logic'] ?? '') == 'OR' ? 'selected' : '' }}>OR</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-block remove-filter-priority">
                                        <i class="fa fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-filter-priority">
                <i class="fa fa-plus"></i> Add Filter
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Filter Priority Management
    document.addEventListener('DOMContentLoaded', function() {
        let filterPriorityIndex = {{ !empty($filterPriority) && is_array($filterPriority) ? count($filterPriority) : 0 }};
        
        document.getElementById('add-filter-priority')?.addEventListener('click', function() {
            const container = document.getElementById('filter-priority-container');
            const filterStrategies = @json($filterStrategies ?? []);
            
            const html = `
                <div class="filter-priority-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-5">
                            <label>Filter Strategy</label>
                            <select name="filter_priority[${filterPriorityIndex}][filter_strategy_id]" class="form-control">
                                <option value="">-- Select Filter --</option>
                                ${filterStrategies.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Priority</label>
                            <input type="number" name="filter_priority[${filterPriorityIndex}][priority]" 
                                   class="form-control" 
                                   value="${filterPriorityIndex + 1}" 
                                   min="1">
                        </div>
                        <div class="col-md-2">
                            <label>Logic</label>
                            <select name="filter_priority[${filterPriorityIndex}][logic]" class="form-control">
                                <option value="AND">AND</option>
                                <option value="OR">OR</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-block remove-filter-priority">
                                <i class="fa fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
            filterPriorityIndex++;
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-filter-priority')) {
                e.target.closest('.filter-priority-item').remove();
            }
        });
    });
</script>
@endpush

{{-- Submit Buttons --}}
<div class="d-flex justify-content-between">
    <a href="{{ isset($bot) ? route('user.trading-management.trading-bots.show', $bot->id) : route('user.trading-management.trading-bots.index') }}" class="btn btn-secondary">
        <i class="fa fa-times"></i> Cancel
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> {{ isset($bot) ? 'Update' : 'Create' }} Trading Bot
    </button>
</div>
