@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><i class="fas fa-cog text-primary"></i> Global Settings</h3>
                        <p class="text-muted mb-0">Configure global provider credentials shared across all connections</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.trading-management.config.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Configuration
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info border-left-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x mr-3"></i>
                <div>
                    <strong>Global Configuration</strong><br>
                    <small>These settings are shared across all MT4/MT5 connections. Configure your provider credentials here to use them globally.</small>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.trading-management.config.global-settings.update') }}" method="POST" id="globalSettingsForm">
            @csrf

            <!-- MTAPI.io Configuration -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-server"></i> MTAPI.io Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-left-warning mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                            <div>
                                <strong>Important:</strong> You need an mtapi.io account to connect MT4/MT5 brokers.
                                <a href="https://mtapi.io" target="_blank" class="alert-link font-weight-bold">Get API key here</a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-key text-primary"></i> API Key
                                </label>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="mtapi_api_key" 
                                       name="mtapi_api_key" 
                                       value="{{ old('mtapi_api_key', $mtapiConfig['api_key'] ?? '') }}" 
                                       placeholder="Enter your mtapi.io API key">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Get your API key from 
                                    <a href="https://mtapi.io/dashboard" target="_blank">mtapi.io dashboard</a>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-link text-primary"></i> gRPC Base URL
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="mtapi_base_url" 
                                       name="mtapi_base_url" 
                                       value="{{ old('mtapi_base_url', $mtapiConfig['base_url'] ?? 'mt5grpc.mtapi.io:443') }}" 
                                       placeholder="mt5grpc.mtapi.io:443">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Default: mt5grpc.mtapi.io:443
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-clock text-primary"></i> Timeout (seconds)
                                </label>
                                <input type="number" 
                                       class="form-control form-control-lg" 
                                       id="mtapi_timeout" 
                                       name="mtapi_timeout" 
                                       value="{{ old('mtapi_timeout', $mtapiConfig['timeout'] ?? 30) }}" 
                                       min="5" 
                                       max="300">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Connection timeout in seconds (5-300)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MetaApi.cloud Configuration -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cloud"></i> MetaApi.cloud Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-left-info mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle mr-2 mt-1"></i>
                            <div>
                                <strong>Note:</strong> You need a MetaApi.cloud account to connect MT4/MT5 brokers.
                                <a href="https://metaapi.cloud" target="_blank" class="alert-link font-weight-bold">Get API token here</a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-key text-info"></i> API Token <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="metaapi_api_token" 
                               name="metaapi_api_token" 
                               value="{{ old('metaapi_api_token', $metaapiConfig['api_token'] ?? '') }}" 
                               placeholder="Enter your MetaApi API token">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Get your API token from 
                            <a href="https://app.metaapi.cloud" target="_blank">MetaApi dashboard</a>. 
                            This token will be used for all MetaApi connections.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-globe text-info"></i> Main API Base URL
                                </label>
                                <input type="url" 
                                       class="form-control" 
                                       id="metaapi_base_url" 
                                       name="metaapi_base_url" 
                                       value="{{ old('metaapi_base_url', $metaapiConfig['base_url'] ?? 'https://mt-client-api-v1.london.agiliumtrade.ai') }}" 
                                       placeholder="https://mt-client-api-v1.london.agiliumtrade.ai">
                                <small class="form-text text-muted">Main API endpoint</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-chart-line text-info"></i> Market Data API Base URL
                                </label>
                                <input type="url" 
                                       class="form-control" 
                                       id="metaapi_market_data_base_url" 
                                       name="metaapi_market_data_base_url" 
                                       value="{{ old('metaapi_market_data_base_url', $metaapiConfig['market_data_base_url'] ?? 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai') }}" 
                                       placeholder="https://mt-market-data-client-api-v1.london.agiliumtrade.ai">
                                <small class="form-text text-muted">Market data API endpoint</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-plus-circle text-info"></i> Provisioning API Base URL
                                </label>
                                <input type="url" 
                                       class="form-control" 
                                       id="metaapi_provisioning_base_url" 
                                       name="metaapi_provisioning_base_url" 
                                       value="{{ old('metaapi_provisioning_base_url', $metaapiConfig['provisioning_base_url'] ?? 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai') }}" 
                                       placeholder="https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai">
                                <small class="form-text text-muted">Provisioning API endpoint</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-credit-card text-info"></i> Billing API Base URL
                                </label>
                                <input type="url" 
                                       class="form-control" 
                                       id="metaapi_billing_base_url" 
                                       name="metaapi_billing_base_url" 
                                       value="{{ old('metaapi_billing_base_url', $metaapiConfig['billing_base_url'] ?? 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai') }}" 
                                       placeholder="https://billing-api-v1.agiliumtrade.agiliumtrade.ai">
                                <small class="form-text text-muted">Billing API endpoint</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-clock text-info"></i> Timeout (seconds)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="metaapi_timeout" 
                                       name="metaapi_timeout" 
                                       value="{{ old('metaapi_timeout', $metaapiConfig['timeout'] ?? 30) }}" 
                                       min="5" 
                                       max="300">
                                <small class="form-text text-muted">Connection timeout in seconds (5-300)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Changes will be applied to all connections using these providers
                            </small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save"></i> Save Global Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict'
        
        // Form submission with loading state
        $('#globalSettingsForm').on('submit', function() {
            const btn = $(this).find('button[type="submit"]');
            const originalHtml = btn.html();
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            // Re-enable after 5 seconds as fallback
            setTimeout(function() {
                btn.prop('disabled', false);
                btn.html(originalHtml);
            }, 5000);
        });
    });
</script>
@endpush

@push('style')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8;
    }
    
    .border-left-warning {
        border-left: 4px solid #ffc107;
    }
    
    .card.shadow-sm {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card.shadow-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .form-control-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
</style>
@endpush
