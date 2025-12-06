@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'SRM Settings' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">SRM Settings</h4>
                    </div>
                <div class="card-body">
                        <form action="{{ route('admin.srm.settings.update') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label>Performance Score Threshold *</label>
                                <input type="number" name="performance_score_threshold" class="form-control" 
                                    value="{{ old('performance_score_threshold', $settings['performance_score_threshold'] ?? 40) }}" 
                                    min="0" max="100" required>
                                <small class="form-text text-muted">Signals from providers below this score will be rejected</small>
                            </div>

                            <div class="form-group">
                                <label>Max Slippage Allowed (pips) *</label>
                                <input type="number" name="max_slippage_allowed" class="form-control" 
                                    value="{{ old('max_slippage_allowed', $settings['max_slippage_allowed'] ?? 10.0) }}" 
                                    min="0" max="50" step="0.1" required>
                                <small class="form-text text-muted">Signals with predicted slippage above this will be rejected</small>
                            </div>

                            <div class="form-group">
                                <label>Drawdown Threshold (%) *</label>
                                <input type="number" name="drawdown_threshold" class="form-control" 
                                    value="{{ old('drawdown_threshold', $settings['drawdown_threshold'] ?? 20.0) }}" 
                                    min="1" max="100" step="0.1" required>
                                <small class="form-text text-muted">Emergency stop will trigger if drawdown exceeds this percentage</small>
                            </div>

                            <div class="form-group">
                                <label>Max SL Buffer (pips) *</label>
                                <input type="number" name="slippage_buffer_max" class="form-control" 
                                    value="{{ old('slippage_buffer_max', $settings['slippage_buffer_max'] ?? 3.0) }}" 
                                    min="0" max="10" step="0.1" required>
                                <small class="form-text text-muted">Maximum buffer added to stop loss</small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="enable_srm" class="form-check-input" 
                                        value="1" {{ ($settings['enable_srm'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable SRM</label>
                                </div>
                                <small class="form-text text-muted">Toggle SRM functionality globally</small>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                                <a href="{{ route('admin.srm.settings.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
@endsection

