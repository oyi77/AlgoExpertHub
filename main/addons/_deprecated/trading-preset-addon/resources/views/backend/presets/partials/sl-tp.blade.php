{{-- Stop Loss & Take Profit Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-shield-alt mr-2"></i>
            {{ __('Stop Loss & Take Profit') }}
        </h5>
    </div>
    <div class="card-body">
        {{-- Stop Loss --}}
        <h6 class="mb-3">{{ __('Stop Loss Configuration') }}</h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sl_mode">{{ __('Stop Loss Mode') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('sl_mode') is-invalid @enderror" 
                            id="sl_mode" 
                            name="sl_mode" 
                            required>
                        <option value="PIPS" {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : 'PIPS')) == 'PIPS' ? 'selected' : '' }}>
                            {{ __('PIPS') }} (Fixed Distance in Pips)
                        </option>
                        <option value="R_MULTIPLE" {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : '')) == 'R_MULTIPLE' ? 'selected' : '' }}>
                            {{ __('R Multiple') }} (Risk-Reward Based)
                        </option>
                        <option value="STRUCTURE" {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : '')) == 'STRUCTURE' ? 'selected' : '' }}>
                            {{ __('Structure') }} (Structure-Based Price)
                        </option>
                    </select>
                    @error('sl_mode')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('How to calculate stop loss distance') }}</small>
                </div>
            </div>

            <div class="col-md-6" id="sl_pips_group" style="display: {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : 'PIPS')) == 'PIPS' ? 'block' : 'none' }};">
                <div class="form-group">
                    <label for="sl_pips">{{ __('Stop Loss (Pips)') }}</label>
                    <input type="number" 
                           class="form-control @error('sl_pips') is-invalid @enderror" 
                           id="sl_pips" 
                           name="sl_pips" 
                           value="{{ old('sl_pips', (isset($preset) ? $preset->sl_pips : '')) }}" 
                           min="1" 
                           max="10000"
                           placeholder="50">
                    @error('sl_pips')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Fixed distance in pips (e.g., 50 pips)') }}</small>
                </div>
            </div>

            <div class="col-md-6" id="sl_r_multiple_group" style="display: {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : 'PIPS')) == 'R_MULTIPLE' ? 'block' : 'none' }};">
                <div class="form-group">
                    <label for="sl_r_multiple">{{ __('SL R Multiple') }}</label>
                    <input type="number" 
                           class="form-control @error('sl_r_multiple') is-invalid @enderror" 
                           id="sl_r_multiple" 
                           name="sl_r_multiple" 
                           value="{{ old('sl_r_multiple', (isset($preset) ? $preset->sl_r_multiple : '')) }}" 
                           step="0.1" 
                           min="0.1" 
                           max="10"
                           placeholder="1.5">
                    @error('sl_r_multiple')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('R multiple for stop loss (e.g., 1.5R)') }}</small>
                </div>
            </div>
        </div>

        <div class="alert alert-info" id="sl_structure_info" style="display: {{ old('sl_mode', (isset($preset) ? $preset->sl_mode : 'PIPS')) == 'STRUCTURE' ? 'block' : 'none' }};">
            <i class="fa fa-info-circle"></i>
            {{ __('Structure mode uses structure-based price from signal. The structure price should be provided in the signal.') }}
        </div>

        <hr>

        {{-- Take Profit --}}
        <h6 class="mb-3">{{ __('Take Profit Configuration') }}</h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="tp_mode">{{ __('Take Profit Mode') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('tp_mode') is-invalid @enderror" 
                            id="tp_mode" 
                            name="tp_mode" 
                            required>
                        <option value="SINGLE" {{ old('tp_mode', (isset($preset) ? $preset->tp_mode : 'SINGLE')) == 'SINGLE' ? 'selected' : '' }}>
                            {{ __('Single TP') }}
                        </option>
                        <option value="MULTI" {{ old('tp_mode', (isset($preset) ? $preset->tp_mode : '')) == 'MULTI' ? 'selected' : '' }}>
                            {{ __('Multi-TP') }} (Up to 3 Levels)
                        </option>
                        <option value="DISABLED" {{ old('tp_mode', (isset($preset) ? $preset->tp_mode : '')) == 'DISABLED' ? 'selected' : '' }}>
                            {{ __('Disabled') }}
                        </option>
                    </select>
                    @error('tp_mode')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Single TP or multiple TP levels with partial closes') }}</small>
                </div>
            </div>
        </div>

        {{-- Single TP --}}
        <div id="single_tp_group" style="display: {{ old('tp_mode', (isset($preset) ? $preset->tp_mode : 'SINGLE')) == 'SINGLE' ? 'block' : 'none' }};">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-4">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="tp1_enabled" 
                                   name="tp1_enabled" 
                                   value="1"
                                   {{ old('tp1_enabled', (isset($preset) ? $preset->tp1_enabled : true)) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="tp1_enabled">
                                {{ __('TP1 Enabled') }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tp1_rr">{{ __('TP1 Risk:Reward') }}</label>
                        <input type="number" 
                               class="form-control @error('tp1_rr') is-invalid @enderror" 
                               id="tp1_rr" 
                               name="tp1_rr" 
                               value="{{ old('tp1_rr', (isset($preset) ? $preset->tp1_rr : '')) }}" 
                               step="0.1" 
                               min="0.1" 
                               max="100"
                               placeholder="2.0">
                        @error('tp1_rr')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Risk:Reward ratio (e.g., 2.0 = 2R)') }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tp1_close_pct">{{ __('TP1 Close %') }}</label>
                        <input type="number" 
                               class="form-control @error('tp1_close_pct') is-invalid @enderror" 
                               id="tp1_close_pct" 
                               name="tp1_close_pct" 
                               value="{{ old('tp1_close_pct', (isset($preset) ? $preset->tp1_close_pct : '100')) }}" 
                               step="0.1" 
                               min="0" 
                               max="100"
                               placeholder="100">
                        @error('tp1_close_pct')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Percentage to close at TP1 (100% for single TP)') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Multi-TP --}}
        <div id="multi_tp_group" style="display: {{ old('tp_mode', (isset($preset) ? $preset->tp_mode : 'SINGLE')) == 'MULTI' ? 'block' : 'none' }};">
            {{-- TP1 --}}
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <strong>{{ __('Take Profit 1') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="tp1_enabled_multi" 
                                           name="tp1_enabled" 
                                           value="1"
                                           {{ old('tp1_enabled', (isset($preset) ? $preset->tp1_enabled : true)) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tp1_enabled_multi">
                                        {{ __('Enabled') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp1_rr_multi">{{ __('Risk:Reward') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp1_rr') is-invalid @enderror" 
                                       id="tp1_rr_multi" 
                                       name="tp1_rr" 
                                       value="{{ old('tp1_rr', (isset($preset) ? $preset->tp1_rr : '')) }}" 
                                       step="0.1" 
                                       min="0.1" 
                                       max="100"
                                       placeholder="1.5">
                                @error('tp1_rr')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp1_close_pct_multi">{{ __('Close %') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp1_close_pct') is-invalid @enderror" 
                                       id="tp1_close_pct_multi" 
                                       name="tp1_close_pct" 
                                       value="{{ old('tp1_close_pct', (isset($preset) ? $preset->tp1_close_pct : '')) }}" 
                                       step="0.1" 
                                       min="0" 
                                       max="100"
                                       placeholder="50">
                                @error('tp1_close_pct')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TP2 --}}
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">
                    <strong>{{ __('Take Profit 2') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="tp2_enabled" 
                                           name="tp2_enabled" 
                                           value="1"
                                           {{ old('tp2_enabled', (isset($preset) ? $preset->tp2_enabled : false)) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tp2_enabled">
                                        {{ __('Enabled') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp2_rr">{{ __('Risk:Reward') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp2_rr') is-invalid @enderror" 
                                       id="tp2_rr" 
                                       name="tp2_rr" 
                                       value="{{ old('tp2_rr', (isset($preset) ? $preset->tp2_rr : '')) }}" 
                                       step="0.1" 
                                       min="0.1" 
                                       max="100"
                                       placeholder="2.5">
                                @error('tp2_rr')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp2_close_pct">{{ __('Close %') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp2_close_pct') is-invalid @enderror" 
                                       id="tp2_close_pct" 
                                       name="tp2_close_pct" 
                                       value="{{ old('tp2_close_pct', (isset($preset) ? $preset->tp2_close_pct : '')) }}" 
                                       step="0.1" 
                                       min="0" 
                                       max="100"
                                       placeholder="30">
                                @error('tp2_close_pct')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TP3 --}}
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning text-white">
                    <strong>{{ __('Take Profit 3') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="tp3_enabled" 
                                           name="tp3_enabled" 
                                           value="1"
                                           {{ old('tp3_enabled', (isset($preset) ? $preset->tp3_enabled : false)) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tp3_enabled">
                                        {{ __('Enabled') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp3_rr">{{ __('Risk:Reward') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp3_rr') is-invalid @enderror" 
                                       id="tp3_rr" 
                                       name="tp3_rr" 
                                       value="{{ old('tp3_rr', (isset($preset) ? $preset->tp3_rr : '')) }}" 
                                       step="0.1" 
                                       min="0.1" 
                                       max="100"
                                       placeholder="3.5">
                                @error('tp3_rr')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tp3_close_pct">{{ __('Close %') }}</label>
                                <input type="number" 
                                       class="form-control @error('tp3_close_pct') is-invalid @enderror" 
                                       id="tp3_close_pct" 
                                       name="tp3_close_pct" 
                                       value="{{ old('tp3_close_pct', (isset($preset) ? $preset->tp3_close_pct : '')) }}" 
                                       step="0.1" 
                                       min="0" 
                                       max="100"
                                       placeholder="20">
                                @error('tp3_close_pct')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Close Remaining at TP3 --}}
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="close_remaining_at_tp3" 
                           name="close_remaining_at_tp3" 
                           value="1"
                           {{ old('close_remaining_at_tp3', (isset($preset) ? $preset->close_remaining_at_tp3 : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="close_remaining_at_tp3">
                        <strong>{{ __('Close Remaining Position at TP3') }}</strong>
                    </label>
                </div>
                <small class="form-text text-muted">{{ __('If enabled, all remaining position will be closed when TP3 is hit') }}</small>
            </div>
        </div>
    </div>
</div>

