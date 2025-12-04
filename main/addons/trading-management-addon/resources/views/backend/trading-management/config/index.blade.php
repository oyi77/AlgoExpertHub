@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-cog"></i> Trading Configuration</h3>
                <p class="text-muted mb-0">Setup and configure data connections, risk presets, and smart risk settings</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Data Connections</h6>
                        <h3>{{ $stats['total_connections'] }}</h3>
                        <small class="text-success">{{ $stats['active_connections'] }} active</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Risk Presets</h6>
                        <h3>{{ $stats['total_presets'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Smart Risk</h6>
                        <h3><i class="fas fa-brain text-info"></i></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-data-connections" data-toggle="tab">
                            <i class="fas fa-plug"></i> Data Connections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-risk-presets" data-toggle="tab">
                            <i class="fas fa-shield-alt"></i> Risk Presets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-smart-risk" data-toggle="tab">
                            <i class="fas fa-brain"></i> Smart Risk Settings
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Data Connections Tab -->
                    <div class="tab-pane fade show active" id="tab-data-connections">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Data Connections</h5>
                            <a href="{{ route('admin.trading-management.config.data-connections.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage Data Connections
                            </a>
                        </div>
                        <p class="text-muted">Configure connections to mtapi.io and CCXT exchanges for real-time market data.</p>
                    </div>

                    <!-- Risk Presets Tab -->
                    <div class="tab-pane fade" id="tab-risk-presets">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Risk Presets</h5>
                            <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage Risk Presets
                            </a>
                        </div>
                        <p class="text-muted">Create and manage manual risk management presets with position sizing rules.</p>
                    </div>

                    <!-- Smart Risk Tab -->
                    <div class="tab-pane fade" id="tab-smart-risk">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Smart Risk Settings</h5>
                            <a href="{{ route('admin.trading-management.config.smart-risk.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Configure Smart Risk
                            </a>
                        </div>
                        <p class="text-muted">AI-powered adaptive risk management based on signal provider performance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
