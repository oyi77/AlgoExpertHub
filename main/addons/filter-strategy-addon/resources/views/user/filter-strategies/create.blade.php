@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create Filter Strategy</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.filter-strategies.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Visibility *</label>
                            <select name="visibility" class="form-control" required>
                                <option value="PRIVATE" {{ old('visibility') === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                                <option value="PUBLIC_MARKETPLACE" {{ old('visibility') === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public Marketplace</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="clonable" value="1" {{ old('clonable', true) ? 'checked' : '' }}>
                                Allow cloning
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                                Enabled
                            </label>
                        </div>

                        {{-- Indicator Configuration --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>Indicator Configuration</h5>
                            </div>
                            <div class="card-body">
                                {{-- EMA Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_ema" name="enable_ema" value="1" {{ old('enable_ema', true) ? 'checked' : '' }} onchange="toggleEmaConfig()">
                                        Enable EMA (Exponential Moving Average)
                                    </label>
                                    <div id="ema_config" class="ml-4 mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Fast Period</label>
                                                <input type="number" name="ema_fast_period" class="form-control" value="{{ old('ema_fast_period', 10) }}" min="1">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Slow Period</label>
                                                <input type="number" name="ema_slow_period" class="form-control" value="{{ old('ema_slow_period', 100) }}" min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stochastic Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_stoch" name="enable_stoch" value="1" {{ old('enable_stoch', true) ? 'checked' : '' }} onchange="toggleStochConfig()">
                                        Enable Stochastic
                                    </label>
                                    <div id="stoch_config" class="ml-4 mt-2">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>%K Period</label>
                                                <input type="number" name="stoch_k" class="form-control" value="{{ old('stoch_k', 14) }}" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label>%D Period</label>
                                                <input type="number" name="stoch_d" class="form-control" value="{{ old('stoch_d', 3) }}" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Smoothing</label>
                                                <input type="number" name="stoch_smooth" class="form-control" value="{{ old('stoch_smooth', 3) }}" min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Parabolic SAR Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_psar" name="enable_psar" value="1" {{ old('enable_psar', true) ? 'checked' : '' }} onchange="togglePsarConfig()">
                                        Enable Parabolic SAR
                                    </label>
                                    <div id="psar_config" class="ml-4 mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Step</label>
                                                <input type="number" name="psar_step" class="form-control" value="{{ old('psar_step', 0.02) }}" step="0.01" min="0.01">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Max</label>
                                                <input type="number" name="psar_max" class="form-control" value="{{ old('psar_max', 0.2) }}" step="0.01" min="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Rule Logic --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>Rule Logic</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Logic Operator</label>
                                    <select name="rule_logic" class="form-control">
                                        <option value="AND" {{ old('rule_logic', 'AND') === 'AND' ? 'selected' : '' }}>AND (All conditions must pass)</option>
                                        <option value="OR" {{ old('rule_logic') === 'OR' ? 'selected' : '' }}>OR (Any condition can pass)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Rule Preset</label>
                                    <select name="rule_preset" class="form-control" onchange="applyRulePreset(this.value)">
                                        <option value="">Custom (Manual)</option>
                                        <option value="trend_momentum_psar">Trend + Momentum + PSAR (BUY: EMA10>EMA100, Stoch<80, PSAR below price)</option>
                                        <option value="trend_only">Trend Only (BUY: EMA10>EMA100)</option>
                                        <option value="momentum_only">Momentum Only (BUY: Stoch<80)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Custom Rule (JSON) - Advanced</label>
                                    <textarea name="custom_rules" id="custom_rules" class="form-control" rows="8" placeholder='[{"left": "ema_fast", "operator": ">", "right": "ema_slow"}, {"left": "stoch", "operator": "<", "right": 80}]'>{{ old('custom_rules') }}</textarea>
                                    <small class="form-text text-muted">
                                        Leave empty if using preset. Format: Array of conditions with left, operator, right.
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden JSON Config (auto-generated) --}}
                        <input type="hidden" name="config" id="config_json" value="{{ old('config', '{}') }}">

                        <script>
                        function toggleEmaConfig() {
                            document.getElementById('ema_config').style.display = document.getElementById('enable_ema').checked ? 'block' : 'none';
                            updateConfig();
                        }
                        function toggleStochConfig() {
                            document.getElementById('stoch_config').style.display = document.getElementById('enable_stoch').checked ? 'block' : 'none';
                            updateConfig();
                        }
                        function togglePsarConfig() {
                            document.getElementById('psar_config').style.display = document.getElementById('enable_psar').checked ? 'block' : 'none';
                            updateConfig();
                        }
                        function applyRulePreset(value) {
                            if (value === 'trend_momentum_psar') {
                                document.getElementById('custom_rules').value = JSON.stringify([
                                    {"left": "ema_fast", "operator": ">", "right": "ema_slow"},
                                    {"left": "stoch", "operator": "<", "right": 80},
                                    {"left": "psar", "operator": "below_price", "right": null}
                                ], null, 2);
                            } else if (value === 'trend_only') {
                                document.getElementById('custom_rules').value = JSON.stringify([
                                    {"left": "ema_fast", "operator": ">", "right": "ema_slow"}
                                ], null, 2);
                            } else if (value === 'momentum_only') {
                                document.getElementById('custom_rules').value = JSON.stringify([
                                    {"left": "stoch", "operator": "<", "right": 80}
                                ], null, 2);
                            }
                            updateConfig();
                        }
                        function updateConfig() {
                            const config = {
                                indicators: {},
                                rules: {
                                    logic: document.querySelector('select[name="rule_logic"]').value || 'AND',
                                    conditions: []
                                }
                            };
                            
                            // EMA
                            if (document.getElementById('enable_ema').checked) {
                                config.indicators.ema_fast = {period: parseInt(document.querySelector('input[name="ema_fast_period"]').value) || 10};
                                config.indicators.ema_slow = {period: parseInt(document.querySelector('input[name="ema_slow_period"]').value) || 100};
                            }
                            
                            // Stochastic
                            if (document.getElementById('enable_stoch').checked) {
                                config.indicators.stoch = {
                                    k: parseInt(document.querySelector('input[name="stoch_k"]').value) || 14,
                                    d: parseInt(document.querySelector('input[name="stoch_d"]').value) || 3,
                                    smooth: parseInt(document.querySelector('input[name="stoch_smooth"]').value) || 3
                                };
                            }
                            
                            // PSAR
                            if (document.getElementById('enable_psar').checked) {
                                config.indicators.psar = {
                                    step: parseFloat(document.querySelector('input[name="psar_step"]').value) || 0.02,
                                    max: parseFloat(document.querySelector('input[name="psar_max"]').value) || 0.2
                                };
                            }
                            
                            // Rules
                            const customRules = document.getElementById('custom_rules').value;
                            if (customRules) {
                                try {
                                    config.rules.conditions = JSON.parse(customRules);
                                } catch(e) {
                                    console.error('Invalid JSON in custom rules');
                                }
                            }
                            
                            document.getElementById('config_json').value = JSON.stringify(config);
                        }
                        
                        // Update config on form change
                        document.addEventListener('DOMContentLoaded', function() {
                            const inputs = document.querySelectorAll('input[name^="ema_"], input[name^="stoch_"], input[name^="psar_"], select[name="rule_logic"], textarea[name="custom_rules"]');
                            inputs.forEach(input => {
                                input.addEventListener('change', updateConfig);
                                input.addEventListener('input', updateConfig);
                            });
                            updateConfig();
                        });
                        </script>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Strategy</button>
                            <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

