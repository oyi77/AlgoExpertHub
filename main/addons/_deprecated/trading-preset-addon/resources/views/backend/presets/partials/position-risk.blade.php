{{-- Position & Risk Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-chart-line mr-2"></i>
            {{ __('Position & Risk Management') }}
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="position_size_mode">{{ __('Position Size Mode') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('position_size_mode') is-invalid @enderror" 
                            id="position_size_mode" 
                            name="position_size_mode" 
                            required>
                        <option value="RISK_PERCENT" {{ old('position_size_mode', (isset($preset) ? $preset->position_size_mode : 'RISK_PERCENT')) == 'RISK_PERCENT' ? 'selected' : '' }}>
                            {{ __('Risk Percentage') }} (Recommended)
                        </option>
                        <option value="FIXED" {{ old('position_size_mode', (isset($preset) ? $preset->position_size_mode : '')) == 'FIXED' ? 'selected' : '' }}>
                            {{ __('Fixed Lot Size') }}
                        </option>
                    </select>
                    @error('position_size_mode')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('How to calculate position size') }}</small>
                </div>
            </div>

            <div class="col-md-6" id="fixed_lot_group" style="display: {{ old('position_size_mode', (isset($preset) ? $preset->position_size_mode : 'RISK_PERCENT')) == 'FIXED' ? 'block' : 'none' }};">
                <div class="form-group">
                    <label for="fixed_lot">{{ __('Fixed Lot Size') }}</label>
                    <input type="number" 
                           class="form-control @error('fixed_lot') is-invalid @enderror" 
                           id="fixed_lot" 
                           name="fixed_lot" 
                           value="{{ old('fixed_lot', (isset($preset) ? $preset->fixed_lot : '')) }}" 
                           step="0.01" 
                           min="0.01" 
                           max="1000"
                           placeholder="0.01">
                    @error('fixed_lot')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Fixed lot size (e.g., 0.1, 0.5, 1.0)') }}</small>
                </div>
            </div>

            <div class="col-md-6" id="risk_per_trade_group" style="display: {{ old('position_size_mode', (isset($preset) ? $preset->position_size_mode : 'RISK_PERCENT')) == 'RISK_PERCENT' ? 'block' : 'none' }};">
                <div class="form-group">
                    <label for="risk_per_trade_pct">{{ __('Risk Per Trade (%)') }}</label>
                    <input type="number" 
                           class="form-control @error('risk_per_trade_pct') is-invalid @enderror" 
                           id="risk_per_trade_pct" 
                           name="risk_per_trade_pct" 
                           value="{{ old('risk_per_trade_pct', (isset($preset) ? $preset->risk_per_trade_pct : '')) }}" 
                           step="0.01" 
                           min="0.01" 
                           max="100"
                           placeholder="1.0">
                    @error('risk_per_trade_pct')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        {{ __('Percentage of equity to risk per trade (1-2% recommended)') }}
                        <span id="risk_warning" class="text-danger" style="display: none;">
                            <br><i class="fa fa-exclamation-triangle"></i> {{ __('Warning: Risk above 10% is very high!') }}
                        </span>
                    </small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_positions">{{ __('Max Positions') }} <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('max_positions') is-invalid @enderror" 
                           id="max_positions" 
                           name="max_positions" 
                           value="{{ old('max_positions', (isset($preset) ? $preset->max_positions : 1)) }}" 
                           min="1" 
                           max="100"
                           required>
                    @error('max_positions')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Maximum number of open positions at once') }}</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_positions_per_symbol">{{ __('Max Positions Per Symbol') }} <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('max_positions_per_symbol') is-invalid @enderror" 
                           id="max_positions_per_symbol" 
                           name="max_positions_per_symbol" 
                           value="{{ old('max_positions_per_symbol', (isset($preset) ? $preset->max_positions_per_symbol : 1)) }}" 
                           min="1" 
                           max="50"
                           required>
                    @error('max_positions_per_symbol')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Maximum positions per symbol') }}</small>
                </div>
            </div>
        </div>

        {{-- Dynamic Equity --}}
        <hr>
        <h6 class="mb-3">{{ __('Dynamic Equity') }}</h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="equity_dynamic_mode">{{ __('Dynamic Equity Mode') }} <span class="text-danger">*</span></label>
                    <select class="form-control @error('equity_dynamic_mode') is-invalid @enderror" 
                            id="equity_dynamic_mode" 
                            name="equity_dynamic_mode" 
                            required>
                        <option value="NONE" {{ old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : 'NONE')) == 'NONE' ? 'selected' : '' }}>
                            {{ __('None') }} (Use Base Equity)
                        </option>
                        <option value="LINEAR" {{ old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : '')) == 'LINEAR' ? 'selected' : '' }}>
                            {{ __('Linear') }} (Proportional Adjustment)
                        </option>
                        <option value="STEP" {{ old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : '')) == 'STEP' ? 'selected' : '' }}>
                            {{ __('Step') }} (Step-Based Adjustment)
                        </option>
                    </select>
                    @error('equity_dynamic_mode')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('How to adjust position sizing based on account performance') }}</small>
                </div>
            </div>

            <div class="col-md-6" id="equity_dynamic_fields" style="display: {{ in_array(old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : 'NONE')), ['LINEAR', 'STEP']) ? 'block' : 'none' }};">
                <div class="form-group">
                    <label for="equity_base">{{ __('Base Equity Amount') }}</label>
                    <input type="number" 
                           class="form-control @error('equity_base') is-invalid @enderror" 
                           id="equity_base" 
                           name="equity_base" 
                           value="{{ old('equity_base', (isset($preset) ? $preset->equity_base : '')) }}" 
                           step="0.01" 
                           min="0"
                           placeholder="10000">
                    @error('equity_base')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Base equity for calculation') }}</small>
                </div>
            </div>
        </div>

        <div class="row" id="equity_step_fields" style="display: {{ old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : 'NONE')) == 'STEP' ? 'block' : 'none' }};">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="equity_step_factor">{{ __('Step Factor') }}</label>
                    <input type="number" 
                           class="form-control @error('equity_step_factor') is-invalid @enderror" 
                           id="equity_step_factor" 
                           name="equity_step_factor" 
                           value="{{ old('equity_step_factor', (isset($preset) ? $preset->equity_step_factor : '')) }}" 
                           step="0.1" 
                           min="0.1" 
                           max="10"
                           placeholder="1.1">
                    @error('equity_step_factor')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Multiplier for each step (e.g., 1.1 = 10% increase per step)') }}</small>
                </div>
            </div>
        </div>

        <div class="row" id="risk_range_fields" style="display: {{ in_array(old('equity_dynamic_mode', (isset($preset) ? $preset->equity_dynamic_mode : 'NONE')), ['LINEAR', 'STEP']) ? 'block' : 'none' }};">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="risk_min_pct">{{ __('Min Risk (%)') }}</label>
                    <input type="number" 
                           class="form-control @error('risk_min_pct') is-invalid @enderror" 
                           id="risk_min_pct" 
                           name="risk_min_pct" 
                           value="{{ old('risk_min_pct', (isset($preset) ? $preset->risk_min_pct : '')) }}" 
                           step="0.01" 
                           min="0.01" 
                           max="100"
                           placeholder="0.5">
                    @error('risk_min_pct')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Minimum risk percentage') }}</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="risk_max_pct">{{ __('Max Risk (%)') }}</label>
                    <input type="number" 
                           class="form-control @error('risk_max_pct') is-invalid @enderror" 
                           id="risk_max_pct" 
                           name="risk_max_pct" 
                           value="{{ old('risk_max_pct', (isset($preset) ? $preset->risk_max_pct : '')) }}" 
                           step="0.01" 
                           min="0.01" 
                           max="100"
                           placeholder="2.0">
                    @error('risk_max_pct')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">{{ __('Maximum risk percentage') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

