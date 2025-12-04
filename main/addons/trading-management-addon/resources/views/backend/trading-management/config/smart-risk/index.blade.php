@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-brain"></i> Smart Risk Settings (AI Adaptive Risk)</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> <strong>Smart Risk Management</strong> uses AI to dynamically adjust position sizing and risk based on signal provider performance metrics.
                </div>

                <form action="{{ route('admin.trading-management.config.smart-risk.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $settings['enabled'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">
                                <strong>Enable Smart Risk Management</strong>
                            </label>
                        </div>
                        <small class="text-muted">Automatically adjust risk based on AI analysis of signal provider performance</small>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Provider Score (0-100)</label>
                                <input type="number" name="min_provider_score" class="form-control" 
                                    value="{{ $settings['min_provider_score'] }}" min="0" max="100" step="1" required>
                                <small class="text-muted">Only execute signals from providers with score above this threshold</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Risk Multiplier Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" name="min_risk_multiplier" class="form-control" 
                                            value="{{ $settings['min_risk_multiplier'] }}" min="0.1" max="1" step="0.1" required>
                                        <small class="text-muted">Min (0.1-1.0)</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" name="max_risk_multiplier" class="form-control" 
                                            value="{{ $settings['max_risk_multiplier'] }}" min="1" max="5" step="0.1" required>
                                        <small class="text-muted">Max (1.0-5.0)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="slippage_buffer" name="slippage_buffer_enabled" value="1" {{ $settings['slippage_buffer_enabled'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="slippage_buffer">
                                <strong>Auto-adjust SL for Slippage</strong>
                            </label>
                        </div>
                        <small class="text-muted">Automatically widen stop loss based on historical slippage patterns</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dynamic_lot" name="dynamic_lot_enabled" value="1" {{ $settings['dynamic_lot_enabled'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dynamic_lot">
                                <strong>Dynamic Lot Sizing</strong>
                            </label>
                        </div>
                        <small class="text-muted">Adjust position size based on recent performance trends</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

