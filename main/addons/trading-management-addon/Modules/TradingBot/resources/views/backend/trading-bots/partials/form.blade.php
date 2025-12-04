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
    </div>
</div>

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
