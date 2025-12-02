{{-- Basic Information Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-info-circle mr-2"></i>
            {{ __('Basic Information') }}
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">{{ __('Preset Name') }} <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', isset($preset) && $preset ? $preset->name : '') }}" 
                           placeholder="{{ __('Enter preset name') }}"
                           required>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('A descriptive name for this preset (e.g., EURUSD Scalper)') }}</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="symbol">{{ __('Symbol') }}</label>
                    <input type="text" 
                           class="form-control @error('symbol') is-invalid @enderror" 
                           id="symbol" 
                           name="symbol" 
                           value="{{ old('symbol', isset($preset) && $preset ? $preset->symbol : '') }}" 
                           placeholder="{{ __('e.g., EURUSD, XAUUSD') }}"
                           maxlength="50">
                    @error('symbol')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Leave empty for all symbols, or specify a symbol (e.g., EURUSD)') }}</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="3" 
                              placeholder="{{ __('Enter preset description') }}">{{ old('description', isset($preset) && $preset ? $preset->description : '') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Optional description of this preset') }}</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="timeframe">{{ __('Timeframe') }}</label>
                    <select class="form-control @error('timeframe') is-invalid @enderror" 
                            id="timeframe" 
                            name="timeframe">
                        <option value="">{{ __('All Timeframes') }}</option>
                        <option value="M1" {{ old('timeframe', $preset->timeframe ?? '') == 'M1' ? 'selected' : '' }}>M1 (1 Minute)</option>
                        <option value="M5" {{ old('timeframe', $preset->timeframe ?? '') == 'M5' ? 'selected' : '' }}>M5 (5 Minutes)</option>
                        <option value="M15" {{ old('timeframe', $preset->timeframe ?? '') == 'M15' ? 'selected' : '' }}>M15 (15 Minutes)</option>
                        <option value="M30" {{ old('timeframe', $preset->timeframe ?? '') == 'M30' ? 'selected' : '' }}>M30 (30 Minutes)</option>
                        <option value="H1" {{ old('timeframe', $preset->timeframe ?? '') == 'H1' ? 'selected' : '' }}>H1 (1 Hour)</option>
                        <option value="H4" {{ old('timeframe', $preset->timeframe ?? '') == 'H4' ? 'selected' : '' }}>H4 (4 Hours)</option>
                        <option value="D1" {{ old('timeframe', $preset->timeframe ?? '') == 'D1' ? 'selected' : '' }}>D1 (Daily)</option>
                        <option value="W1" {{ old('timeframe', $preset->timeframe ?? '') == 'W1' ? 'selected' : '' }}>W1 (Weekly)</option>
                    </select>
                    @error('timeframe')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Leave empty for all timeframes') }}</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="tags">{{ __('Tags') }}</label>
                    <input type="text" 
                           class="form-control @error('tags') is-invalid @enderror" 
                           id="tags" 
                           name="tags" 
                           value="{{ old('tags', $preset && $preset->tags ? implode(', ', $preset->tags) : '') }}" 
                           placeholder="{{ __('e.g., scalping, xau, layering') }}">
                    @error('tags')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Comma-separated tags for organization') }}</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ __('Status') }}</label>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="enabled" 
                               name="enabled" 
                               value="1"
                               {{ old('enabled', $preset->enabled ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="enabled">
                            {{ __('Enabled') }}
                        </label>
                    </div>
                    <small class="form-text text-muted">{{ __('Disabled presets will not be used') }}</small>
                </div>
            </div>
        </div>

        {{-- Filter Strategy & AI Configuration (Sprint 1 & 2) --}}
        <div class="row">
            {{-- Filter Strategy (Sprint 1) --}}
            @if(isset($filterStrategies) && $filterStrategies->isNotEmpty())
            <div class="col-md-6">
                <div class="form-group">
                    <label for="filter_strategy_id">
                        {{ __('Filter Strategy') }}
                        @if(isset($preset) && $preset && $preset->filterStrategy)
                            <i class="fa fa-check-circle text-success" title="Filter Strategy Active"></i>
                        @endif
                    </label>
                    <select class="form-control @error('filter_strategy_id') is-invalid @enderror" 
                            id="filter_strategy_id" 
                            name="filter_strategy_id">
                        <option value="">{{ __('No Filter Strategy (Bypass)') }}</option>
                        @foreach($filterStrategies as $strategy)
                            <option value="{{ $strategy->id }}" 
                                    {{ old('filter_strategy_id', isset($preset) && $preset ? $preset->filter_strategy_id : '') == $strategy->id ? 'selected' : '' }}>
                                {{ $strategy->name }}
                                @if($strategy->visibility === 'PUBLIC_MARKETPLACE')
                                    (Public)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('filter_strategy_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        {{ __('Optional: Select a filter strategy to validate signals before execution.') }}
                    </small>
                    @if(isset($preset) && $preset && $preset->filterStrategy)
                        <div class="mt-2">
                            <a href="{{ route('user.filter-strategies.edit', $preset->filterStrategy->id) }}" class="btn btn-sm btn-info" target="_blank">
                                <i class="fa fa-cog"></i> {{ __('Manage Filter Strategy') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- AI Model Profile (Sprint 2) --}}
            @if(isset($aiModelProfiles) && $aiModelProfiles->isNotEmpty())
            <div class="col-md-6">
                <div class="form-group">
                    <label for="ai_model_profile_id">
                        {{ __('AI Model Profile') }}
                        @if(isset($preset) && $preset && $preset->aiModelProfile)
                            <i class="fa fa-check-circle text-success" title="AI Profile Active"></i>
                        @endif
                    </label>
                    <select class="form-control @error('ai_model_profile_id') is-invalid @enderror" 
                            id="ai_model_profile_id" 
                            name="ai_model_profile_id">
                        <option value="">{{ __('No AI Profile (Bypass)') }}</option>
                        @foreach($aiModelProfiles as $profile)
                            <option value="{{ $profile->id }}" 
                                    {{ old('ai_model_profile_id', isset($preset) && $preset ? $preset->ai_model_profile_id : '') == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }} ({{ $profile->provider }}/{{ $profile->mode }})
                                @if($profile->visibility === 'PUBLIC_MARKETPLACE')
                                    - Public
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('ai_model_profile_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        {{ __('Optional: Select an AI model profile for signal confirmation.') }}
                    </small>
                    @if(isset($preset) && $preset && $preset->aiModelProfile)
                        <div class="mt-2">
                            <a href="{{ route('user.ai-model-profiles.edit', $preset->aiModelProfile->id) }}" class="btn btn-sm btn-info" target="_blank">
                                <i class="fa fa-cog"></i> {{ __('Manage AI Profile') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- AI Configuration Settings (Sprint 2) --}}
        @if(isset($preset) && $preset && $preset->ai_model_profile_id)
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('AI Configuration') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ai_confirmation_mode">{{ __('AI Confirmation Mode') }} *</label>
                                    <select class="form-control @error('ai_confirmation_mode') is-invalid @enderror" 
                                            id="ai_confirmation_mode" 
                                            name="ai_confirmation_mode">
                                        <option value="NONE" {{ old('ai_confirmation_mode', $preset->ai_confirmation_mode ?? 'NONE') === 'NONE' ? 'selected' : '' }}>
                                            {{ __('None (Bypass AI)') }}
                                        </option>
                                        <option value="REQUIRED" {{ old('ai_confirmation_mode', $preset->ai_confirmation_mode ?? 'NONE') === 'REQUIRED' ? 'selected' : '' }}>
                                            {{ __('Required (Must Accept)') }}
                                        </option>
                                        <option value="ADVISORY" {{ old('ai_confirmation_mode', $preset->ai_confirmation_mode ?? 'NONE') === 'ADVISORY' ? 'selected' : '' }}>
                                            {{ __('Advisory (Can Override)') }}
                                        </option>
                                    </select>
                                    @error('ai_confirmation_mode')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        {{ __('REQUIRED: Signal must be accepted by AI to execute. ADVISORY: AI recommendation only.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ai_min_safety_score">{{ __('Min Safety Score') }}</label>
                                    <input type="number" 
                                           class="form-control @error('ai_min_safety_score') is-invalid @enderror" 
                                           id="ai_min_safety_score" 
                                           name="ai_min_safety_score" 
                                           min="0" 
                                           max="100" 
                                           step="0.1"
                                           value="{{ old('ai_min_safety_score', $preset->ai_min_safety_score ?? '') }}">
                                    @error('ai_min_safety_score')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        {{ __('Minimum safety score (0-100) required for execution. Leave empty for no threshold.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" 
                                               id="ai_position_mgmt_enabled" 
                                               name="ai_position_mgmt_enabled" 
                                               value="1"
                                               {{ old('ai_position_mgmt_enabled', $preset->ai_position_mgmt_enabled ?? false) ? 'checked' : '' }}>
                                        {{ __('Enable AI Position Management') }}
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        {{ __('Allow AI to manage open positions (trailing stop, break-even, etc.)') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(isset($preset) && !empty($preset) && $preset->is_default_template)
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                {{ __('This is a default template preset. Some fields may be read-only.') }}
            </div>
        @endif
    </div>
</div>

