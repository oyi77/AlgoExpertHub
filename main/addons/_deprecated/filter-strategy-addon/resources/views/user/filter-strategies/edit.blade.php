@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Filter Strategy</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.filter-strategies.update', $filterStrategy->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $filterStrategy->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $filterStrategy->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Visibility *</label>
                            <select name="visibility" class="form-control" required>
                                <option value="PRIVATE" {{ old('visibility', $filterStrategy->visibility) === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                                <option value="PUBLIC_MARKETPLACE" {{ old('visibility', $filterStrategy->visibility) === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public Marketplace</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="clonable" value="1" {{ old('clonable', $filterStrategy->clonable) ? 'checked' : '' }}>
                                Allow cloning
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $filterStrategy->enabled) ? 'checked' : '' }}>
                                Enabled
                            </label>
                        </div>

                        @php
                            $config = $filterStrategy->config ?? [];
                            $indicators = $config['indicators'] ?? [];
                            $rules = $config['rules'] ?? [];
                            $conditions = $rules['conditions'] ?? [];
                        @endphp

                        {{-- Indicator Configuration --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>Indicator Configuration</h5>
                            </div>
                            <div class="card-body">
                                {{-- EMA Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_ema" name="enable_ema" value="1" {{ isset($indicators['ema_fast']) || isset($indicators['ema_slow']) ? 'checked' : '' }} onchange="toggleEmaConfig()">
                                        Enable EMA (Exponential Moving Average)
                                    </label>
                                    <div id="ema_config" class="ml-4 mt-2" style="display: {{ isset($indicators['ema_fast']) || isset($indicators['ema_slow']) ? 'block' : 'none' }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Fast Period</label>
                                                <input type="number" name="ema_fast_period" class="form-control" value="{{ old('ema_fast_period', $indicators['ema_fast']['period'] ?? 10) }}" min="1">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Slow Period</label>
                                                <input type="number" name="ema_slow_period" class="form-control" value="{{ old('ema_slow_period', $indicators['ema_slow']['period'] ?? 100) }}" min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stochastic Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_stoch" name="enable_stoch" value="1" {{ isset($indicators['stoch']) ? 'checked' : '' }} onchange="toggleStochConfig()">
                                        Enable Stochastic
                                    </label>
                                    <div id="stoch_config" class="ml-4 mt-2" style="display: {{ isset($indicators['stoch']) ? 'block' : 'none' }}">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>%K Period</label>
                                                <input type="number" name="stoch_k" class="form-control" value="{{ old('stoch_k', $indicators['stoch']['k'] ?? 14) }}" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label>%D Period</label>
                                                <input type="number" name="stoch_d" class="form-control" value="{{ old('stoch_d', $indicators['stoch']['d'] ?? 3) }}" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Smoothing</label>
                                                <input type="number" name="stoch_smooth" class="form-control" value="{{ old('stoch_smooth', $indicators['stoch']['smooth'] ?? 3) }}" min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Parabolic SAR Configuration --}}
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="enable_psar" name="enable_psar" value="1" {{ isset($indicators['psar']) ? 'checked' : '' }} onchange="togglePsarConfig()">
                                        Enable Parabolic SAR
                                    </label>
                                    <div id="psar_config" class="ml-4 mt-2" style="display: {{ isset($indicators['psar']) ? 'block' : 'none' }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Step</label>
                                                <input type="number" name="psar_step" class="form-control" value="{{ old('psar_step', $indicators['psar']['step'] ?? 0.02) }}" step="0.01" min="0.01">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Max</label>
                                                <input type="number" name="psar_max" class="form-control" value="{{ old('psar_max', $indicators['psar']['max'] ?? 0.2) }}" step="0.01" min="0.01">
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
                                        <option value="AND" {{ old('rule_logic', $rules['logic'] ?? 'AND') === 'AND' ? 'selected' : '' }}>AND (All conditions must pass)</option>
                                        <option value="OR" {{ old('rule_logic', $rules['logic'] ?? 'AND') === 'OR' ? 'selected' : '' }}>OR (Any condition can pass)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Custom Rule (JSON)</label>
                                    <textarea name="custom_rules" id="custom_rules" class="form-control" rows="8">{{ old('custom_rules', json_encode($conditions, JSON_PRETTY_PRINT)) }}</textarea>
                                    <small class="form-text text-muted">
                                        Format: Array of conditions with left, operator, right.
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden JSON Config (auto-generated) --}}
                        <input type="hidden" name="config" id="config_json" value="{{ old('config', json_encode($config)) }}">

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
                            <button type="submit" class="btn btn-primary">Update Strategy</button>
                            <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
