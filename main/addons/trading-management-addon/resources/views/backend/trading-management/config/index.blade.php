@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Trading Configuration</h4>
                <p class="text-muted">Manage data connections, risk presets, and smart risk settings</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Phase 7 - UI Consolidation</strong>
                    <p class="mb-0 mt-2">Trading configuration features are currently being migrated. This section will include:</p>
                    <ul class="mt-2 mb-0">
                        <li><strong>Data Connections</strong> - Connect to mtapi.io and CCXT exchanges for market data</li>
                        <li><strong>Risk Presets</strong> - Configure position sizing and risk management presets</li>
                        <li><strong>Smart Risk Settings</strong> - AI-powered adaptive risk management</li>
                    </ul>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                <h5>Data Connections</h5>
                                <p class="text-muted">Connect to market data sources</p>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-lock"></i> Coming in Phase 7
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <h5>Risk Presets</h5>
                                <p class="text-muted">Configure risk management presets</p>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-lock"></i> Coming in Phase 7
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border">
                            <div class="card-body text-center">
                                <i class="fas fa-brain fa-3x text-warning mb-3"></i>
                                <h5>Smart Risk</h5>
                                <p class="text-muted">AI-powered adaptive risk</p>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-lock"></i> Coming in Phase 7
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-wrench"></i>
                    <strong>Development Status:</strong> These features are functional at the backend but UI migration is pending.
                    For now, please use the existing execution and preset management interfaces.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

