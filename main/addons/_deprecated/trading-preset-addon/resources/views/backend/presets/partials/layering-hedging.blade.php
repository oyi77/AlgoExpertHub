{{-- Layering, Hedging & Exit Logic Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-layer-group mr-2"></i>
            {{ __('Layering, Hedging & Exit Logic') }}
        </h5>
    </div>
    <div class="card-body">
        {{-- Layering / Grid --}}
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary text-white">
                <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="layering_enabled" 
                           name="layering_enabled" 
                           value="1"
                           {{ old('layering_enabled', (isset($preset) ? $preset->layering_enabled : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label text-white" for="layering_enabled">
                        <strong>{{ __('Layering / Grid Trading') }}</strong>
                    </label>
                </div>
            </div>
            <div class="card-body" id="layering_fields" style="display: {{ old('layering_enabled', (isset($preset) ? $preset->layering_enabled : false)) ? 'block' : 'none' }};">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_layers_per_symbol">{{ __('Max Layers Per Symbol') }} <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('max_layers_per_symbol') is-invalid @enderror" 
                                   id="max_layers_per_symbol" 
                                   name="max_layers_per_symbol" 
                                   value="{{ old('max_layers_per_symbol', (isset($preset) ? $preset->max_layers_per_symbol : 1)) }}" 
                                   min="1" 
                                   max="20"
                                   required>
                            @error('max_layers_per_symbol')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Maximum number of layers/positions per symbol') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="layer_distance_pips">{{ __('Layer Distance (Pips)') }}</label>
                            <input type="number" 
                                   class="form-control @error('layer_distance_pips') is-invalid @enderror" 
                                   id="layer_distance_pips" 
                                   name="layer_distance_pips" 
                                   value="{{ old('layer_distance_pips', (isset($preset) ? $preset->layer_distance_pips : '')) }}" 
                                   min="1" 
                                   max="1000"
                                   placeholder="50">
                            @error('layer_distance_pips')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Distance between layers in pips') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="layer_martingale_mode">{{ __('Martingale Mode') }} <span class="text-danger">*</span></label>
                            <select class="form-control @error('layer_martingale_mode') is-invalid @enderror" 
                                    id="layer_martingale_mode" 
                                    name="layer_martingale_mode" 
                                    required>
                                <option value="NONE" {{ old('layer_martingale_mode', (isset($preset) ? $preset->layer_martingale_mode : 'NONE')) == 'NONE' ? 'selected' : '' }}>
                                    {{ __('None') }} (Same Size)
                                </option>
                                <option value="MULTIPLY" {{ old('layer_martingale_mode', (isset($preset) ? $preset->layer_martingale_mode : '')) == 'MULTIPLY' ? 'selected' : '' }}>
                                    {{ __('Multiply') }} (Exponential)
                                </option>
                                <option value="ADD" {{ old('layer_martingale_mode', (isset($preset) ? $preset->layer_martingale_mode : '')) == 'ADD' ? 'selected' : '' }}>
                                    {{ __('Add') }} (Linear)
                                </option>
                            </select>
                            @error('layer_martingale_mode')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('How to adjust position size for each layer') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6" id="martingale_factor_group" style="display: {{ in_array(old('layer_martingale_mode', (isset($preset) ? $preset->layer_martingale_mode : 'NONE')), ['MULTIPLY', 'ADD']) ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="layer_martingale_factor">{{ __('Martingale Factor') }}</label>
                            <input type="number" 
                                   class="form-control @error('layer_martingale_factor') is-invalid @enderror" 
                                   id="layer_martingale_factor" 
                                   name="layer_martingale_factor" 
                                   value="{{ old('layer_martingale_factor', (isset($preset) ? $preset->layer_martingale_factor : '')) }}" 
                                   step="0.1" 
                                   min="0.1" 
                                   max="10"
                                   placeholder="1.5">
                            @error('layer_martingale_factor')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('For MULTIPLY: multiplier (e.g., 1.5 = 1.5x per layer)') }}<br>
                                {{ __('For ADD: amount to add per layer') }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="layer_max_total_risk_pct">{{ __('Max Total Risk (%)') }}</label>
                            <input type="number" 
                                   class="form-control @error('layer_max_total_risk_pct') is-invalid @enderror" 
                                   id="layer_max_total_risk_pct" 
                                   name="layer_max_total_risk_pct" 
                                   value="{{ old('layer_max_total_risk_pct', (isset($preset) ? $preset->layer_max_total_risk_pct : '')) }}" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="100"
                                   placeholder="5.0">
                            @error('layer_max_total_risk_pct')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Maximum total risk across all layers (e.g., 5% = stop adding layers if total risk exceeds 5%)') }}</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>{{ __('Warning:') }}</strong>
                    {{ __('Layering with martingale can significantly increase risk. Use with caution and proper risk management.') }}
                </div>
            </div>
        </div>

        {{-- Hedging --}}
        <div class="card border-danger mb-3">
            <div class="card-header bg-danger text-white">
                <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="hedging_enabled" 
                           name="hedging_enabled" 
                           value="1"
                           {{ old('hedging_enabled', (isset($preset) ? $preset->hedging_enabled : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label text-white" for="hedging_enabled">
                        <strong>{{ __('Hedging') }}</strong>
                    </label>
                </div>
            </div>
            <div class="card-body" id="hedging_fields" style="display: {{ old('hedging_enabled', (isset($preset) ? $preset->hedging_enabled : false)) ? 'block' : 'none' }};">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hedge_trigger_drawdown_pct">{{ __('Trigger Drawdown (%)') }}</label>
                            <input type="number" 
                                   class="form-control @error('hedge_trigger_drawdown_pct') is-invalid @enderror" 
                                   id="hedge_trigger_drawdown_pct" 
                                   name="hedge_trigger_drawdown_pct" 
                                   value="{{ old('hedge_trigger_drawdown_pct', (isset($preset) ? $preset->hedge_trigger_drawdown_pct : '')) }}" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="100"
                                   placeholder="10.0">
                            @error('hedge_trigger_drawdown_pct')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Open hedge position when drawdown reaches this percentage') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hedge_distance_pips">{{ __('Hedge Distance (Pips)') }}</label>
                            <input type="number" 
                                   class="form-control @error('hedge_distance_pips') is-invalid @enderror" 
                                   id="hedge_distance_pips" 
                                   name="hedge_distance_pips" 
                                   value="{{ old('hedge_distance_pips', (isset($preset) ? $preset->hedge_distance_pips : '')) }}" 
                                   min="1" 
                                   max="1000"
                                   placeholder="20">
                            @error('hedge_distance_pips')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Distance from current price to place hedge order') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hedge_lot_factor">{{ __('Hedge Lot Factor') }}</label>
                            <input type="number" 
                                   class="form-control @error('hedge_lot_factor') is-invalid @enderror" 
                                   id="hedge_lot_factor" 
                                   name="hedge_lot_factor" 
                                   value="{{ old('hedge_lot_factor', (isset($preset) ? $preset->hedge_lot_factor : '')) }}" 
                                   step="0.1" 
                                   min="0.1" 
                                   max="10"
                                   placeholder="1.0">
                            @error('hedge_lot_factor')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Multiplier for hedge position size (e.g., 1.0 = same size, 0.5 = half size)') }}</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    {{ __('Hedging opens an opposite position to reduce risk when drawdown threshold is reached.') }}
                </div>
            </div>
        </div>

        {{-- Exit Per Candle --}}
        <div class="card border-secondary mb-3">
            <div class="card-header bg-secondary text-white">
                <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="auto_close_on_candle_close" 
                           name="auto_close_on_candle_close" 
                           value="1"
                           {{ old('auto_close_on_candle_close', (isset($preset) ? $preset->auto_close_on_candle_close : false)) ? 'checked' : '' }}>
                    <label class="custom-control-label text-white" for="auto_close_on_candle_close">
                        <strong>{{ __('Auto Close on Candle Close') }}</strong>
                    </label>
                </div>
            </div>
            <div class="card-body" id="candle_exit_fields" style="display: {{ old('auto_close_on_candle_close', (isset($preset) ? $preset->auto_close_on_candle_close : false)) ? 'block' : 'none' }};">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="auto_close_timeframe">{{ __('Timeframe') }}</label>
                            <select class="form-control @error('auto_close_timeframe') is-invalid @enderror" 
                                    id="auto_close_timeframe" 
                                    name="auto_close_timeframe">
                                <option value="">{{ __('Select Timeframe') }}</option>
                                <option value="M1" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'M1' ? 'selected' : '' }}>M1 (1 Minute)</option>
                                <option value="M5" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'M5' ? 'selected' : '' }}>M5 (5 Minutes)</option>
                                <option value="M15" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'M15' ? 'selected' : '' }}>M15 (15 Minutes)</option>
                                <option value="M30" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'M30' ? 'selected' : '' }}>M30 (30 Minutes)</option>
                                <option value="H1" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'H1' ? 'selected' : '' }}>H1 (1 Hour)</option>
                                <option value="H4" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'H4' ? 'selected' : '' }}>H4 (4 Hours)</option>
                                <option value="D1" {{ old('auto_close_timeframe', (isset($preset) ? $preset->auto_close_timeframe : '')) == 'D1' ? 'selected' : '' }}>D1 (Daily)</option>
                            </select>
                            @error('auto_close_timeframe')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Timeframe to monitor for candle close') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hold_max_candles">{{ __('Hold Max Candles') }}</label>
                            <input type="number" 
                                   class="form-control @error('hold_max_candles') is-invalid @enderror" 
                                   id="hold_max_candles" 
                                   name="hold_max_candles" 
                                   value="{{ old('hold_max_candles', (isset($preset) ? $preset->hold_max_candles : '')) }}" 
                                   min="1" 
                                   max="10000"
                                   placeholder="24">
                            @error('hold_max_candles')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">{{ __('Maximum number of candles to hold position (optional, leave empty for no limit)') }}</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    {{ __('Positions will be automatically closed when the specified candle closes, or after holding for the maximum number of candles.') }}
                </div>
            </div>
        </div>
    </div>
</div>

