{{-- Advanced Features Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-cogs mr-2"></i>
            {{ __('Advanced Features') }}
        </h5>
    </div>
    <div class="card-body">
        {{-- Break Even --}}
        <div class="card border-info mb-3">
            <div class="card-header bg-info text-white">
                <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="be_enabled" 
                           name="be_enabled" 
                           value="1"
                           {{ old('be_enabled', (isset($preset) ? $preset->be_enabled : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label text-white" for="be_enabled">
                        <strong>{{ __('Break-Even') }}</strong>
                    </label>
                </div>
            </div>
            <div class="card-body" id="be_fields" style="display: {{ old('be_enabled', (isset($preset) ? $preset->be_enabled : false)) ? 'block' : 'none' }};">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="be_trigger_rr">{{ __('Trigger R:R') }}</label>
                            <input type="number" 
                                   class="form-control @error('be_trigger_rr') is-invalid @enderror" 
                                   id="be_trigger_rr" 
                                   name="be_trigger_rr" 
                                   value="{{ old('be_trigger_rr', (isset($preset) ? $preset->be_trigger_rr : '')) }}" 
                                   step="0.1" 
                                   min="0.1" 
                                   max="10"
                                   placeholder="1.0">
                            @error('be_trigger_rr')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Move SL to break-even when this R:R is reached') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="be_offset_pips">{{ __('Offset (Pips)') }}</label>
                            <input type="number" 
                                   class="form-control @error('be_offset_pips') is-invalid @enderror" 
                                   id="be_offset_pips" 
                                   name="be_offset_pips" 
                                   value="{{ old('be_offset_pips', (isset($preset) ? $preset->be_offset_pips : '')) }}" 
                                   min="-1000" 
                                   max="1000"
                                   placeholder="5">
                            @error('be_offset_pips')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Offset from entry price (positive = above entry for buy)') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trailing Stop --}}
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning text-white">
                <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="ts_enabled" 
                           name="ts_enabled" 
                           value="1"
                           {{ old('ts_enabled', (isset($preset) ? $preset->ts_enabled : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label text-white" for="ts_enabled">
                        <strong>{{ __('Trailing Stop') }}</strong>
                    </label>
                </div>
            </div>
            <div class="card-body" id="ts_fields" style="display: {{ old('ts_enabled', (isset($preset) ? $preset->ts_enabled : false)) ? 'block' : 'none' }};">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_trigger_rr">{{ __('Trigger R:R') }}</label>
                            <input type="number" 
                                   class="form-control @error('ts_trigger_rr') is-invalid @enderror" 
                                   id="ts_trigger_rr" 
                                   name="ts_trigger_rr" 
                                   value="{{ old('ts_trigger_rr', (isset($preset) ? $preset->ts_trigger_rr : '')) }}" 
                                   step="0.1" 
                                   min="0.1" 
                                   max="10"
                                   placeholder="1.5">
                            @error('ts_trigger_rr')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Start trailing when this R:R is reached') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_mode">{{ __('Trailing Mode') }} <span class="text-danger">*</span></label>
                            <select class="form-control @error('ts_mode') is-invalid @enderror" 
                                    id="ts_mode" 
                                    name="ts_mode" 
                                    required>
                                <option value="STEP_PIPS" {{ old('ts_mode', (isset($preset) ? $preset->ts_mode : 'STEP_PIPS')) == 'STEP_PIPS' ? 'selected' : '' }}>
                                    {{ __('Step Pips') }} (Fixed Distance)
                                </option>
                                <option value="STEP_ATR" {{ old('ts_mode', (isset($preset) ? $preset->ts_mode : '')) == 'STEP_ATR' ? 'selected' : '' }}>
                                    {{ __('Step ATR') }} (ATR-Based)
                                </option>
                                <option value="CHANDELIER" {{ old('ts_mode', (isset($preset) ? $preset->ts_mode : '')) == 'CHANDELIER' ? 'selected' : '' }}>
                                    {{ __('Chandelier') }} (Volatility-Based)
                                </option>
                            </select>
                            @error('ts_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- STEP_PIPS fields --}}
                <div class="row" id="ts_step_pips_group" style="display: {{ old('ts_mode', (isset($preset) ? $preset->ts_mode : 'STEP_PIPS')) == 'STEP_PIPS' ? 'block' : 'none' }};">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_step_pips">{{ __('Step (Pips)') }}</label>
                            <input type="number" 
                                   class="form-control @error('ts_step_pips') is-invalid @enderror" 
                                   id="ts_step_pips" 
                                   name="ts_step_pips" 
                                   value="{{ old('ts_step_pips', (isset($preset) ? $preset->ts_step_pips : '')) }}" 
                                   min="1" 
                                   max="1000"
                                   placeholder="20">
                            @error('ts_step_pips')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Fixed pips distance for trailing') }}</small>
                        </div>
                    </div>
                </div>

                {{-- STEP_ATR and CHANDELIER fields --}}
                <div class="row" id="ts_atr_group" style="display: {{ in_array(old('ts_mode', (isset($preset) ? $preset->ts_mode : 'STEP_PIPS')), ['STEP_ATR', 'CHANDELIER']) ? 'block' : 'none' }};">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_atr_period">{{ __('ATR Period') }}</label>
                            <input type="number" 
                                   class="form-control @error('ts_atr_period') is-invalid @enderror" 
                                   id="ts_atr_period" 
                                   name="ts_atr_period" 
                                   value="{{ old('ts_atr_period', (isset($preset) ? $preset->ts_atr_period : '14')) }}" 
                                   min="1" 
                                   max="200"
                                   placeholder="14">
                            @error('ts_atr_period')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Period for ATR calculation (default: 14)') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_atr_multiplier">{{ __('ATR Multiplier') }}</label>
                            <input type="number" 
                                   class="form-control @error('ts_atr_multiplier') is-invalid @enderror" 
                                   id="ts_atr_multiplier" 
                                   name="ts_atr_multiplier" 
                                   value="{{ old('ts_atr_multiplier', (isset($preset) ? $preset->ts_atr_multiplier : '')) }}" 
                                   step="0.1" 
                                   min="0.1" 
                                   max="10"
                                   placeholder="2.0">
                            @error('ts_atr_multiplier')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Multiplier for ATR (e.g., 2.0 = 2x ATR)') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ts_update_interval_sec">{{ __('Update Interval (Seconds)') }}</label>
                            <input type="number" 
                                   class="form-control @error('ts_update_interval_sec') is-invalid @enderror" 
                                   id="ts_update_interval_sec" 
                                   name="ts_update_interval_sec" 
                                   value="{{ old('ts_update_interval_sec', (isset($preset) ? $preset->ts_update_interval_sec : '60')) }}" 
                                   min="1" 
                                   max="3600"
                                   placeholder="60">
                            @error('ts_update_interval_sec')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('How often to update trailing stop (default: 60 seconds)') }}</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>{{ __('ATR & Chandelier Modes:') }}</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>STEP_ATR:</strong> {{ __('Trails stop loss by ATR distance') }}</li>
                        <li><strong>CHANDELIER:</strong> {{ __('Uses highest high/lowest low + ATR for stop loss') }}</li>
                    </ul>
                    <small>{{ __('Note: These modes require price history data from your broker.') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

