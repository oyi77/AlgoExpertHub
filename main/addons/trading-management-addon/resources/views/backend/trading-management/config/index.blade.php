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
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Exchange Connections</h6>
                        <h3>{{ $stats['total_connections'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Data Enabled</h6>
                        <h3 class="text-primary">{{ $stats['data_connections'] }}</h3>
                        <small class="text-muted">Fetching market data</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Execution Enabled</h6>
                        <h3 class="text-success">{{ $stats['execution_connections'] }}</h3>
                        <small class="text-muted">Trading actively</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Risk Presets</h6>
                        <h3>{{ $stats['total_presets'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-exchange-connections" data-toggle="tab">
                            <i class="fas fa-exchange-alt"></i> Exchange Connections
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
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-global-settings" data-toggle="tab">
                            <i class="fas fa-globe"></i> Global Settings
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Exchange Connections Tab (Unified) -->
                    <div class="tab-pane fade show active" id="tab-exchange-connections">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Exchange/Broker Connections</h5>
                            <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Connection
                            </a>
                        </div>

                        @if($connections->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Provider</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Preset</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($connections as $connection)
                                    <tr>
                                        <td>
                                            <strong>{{ $connection->name }}</strong>
                                            @if($connection->is_admin_owned)
                                            <span class="badge badge-info badge-sm">Admin</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'badge-primary' : 'badge-success' }}">
                                                {{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto' : 'Forex' }}
                                            </span>
                                        </td>
                                        <td>{{ strtoupper($connection->provider) }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $connection->getPurposeLabel() }}</span>
                                            <br>
                                            @if($connection->is_active)
                                            <small><i class="fas fa-download text-primary"></i> Data</small>
                                            @endif
                                            @if($connection->is_active)
                                            <small><i class="fas fa-bolt text-success"></i> Trading</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($connection->status === 'connected')
                                            <span class="badge badge-success">Connected</span>
                                            @elseif($connection->status === 'error')
                                            <span class="badge badge-danger">Error</span>
                                            @else
                                            <span class="badge badge-warning">{{ ucfirst($connection->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $connection->preset->name ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.config.exchange-connections.show', $connection) }}" class="btn btn-sm btn-success" title="Test & Preview">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $connection) }}" class="btn btn-sm btn-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $connections->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No exchange connections yet. <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create your first connection</a> to start fetching data and/or executing trades.
                        </div>
                        @endif
                    </div>

                    <!-- Risk Presets Tab -->
                    <div class="tab-pane fade" id="tab-risk-presets">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Risk Presets</h5>
                            <a href="{{ route('admin.trading-management.config.risk-presets.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Preset
                            </a>
                        </div>

                        @if($presets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Mode</th>
                                        <th>Risk %</th>
                                        <th>Fixed Lot</th>
                                        <th>Default</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($presets as $preset)
                                    <tr>
                                        <td><strong>{{ $preset->name }}</strong></td>
                                        <td>
                                            <span class="badge {{ $preset->position_size_mode === 'RISK_PERCENT' ? 'badge-info' : 'badge-secondary' }}">
                                                {{ $preset->position_size_mode }}
                                            </span>
                                        </td>
                                        <td>{{ $preset->risk_per_trade_pct ? $preset->risk_per_trade_pct . '%' : '-' }}</td>
                                        <td>{{ $preset->fixed_lot ?? '-' }}</td>
                                        <td>
                                            @if($preset->is_default_template)
                                            <i class="fas fa-check text-success"></i>
                                            @else
                                            <i class="fas fa-times text-muted"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($preset->enabled)
                                            <span class="badge badge-success">Enabled</span>
                                            @else
                                            <span class="badge badge-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.config.risk-presets.edit', $preset) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $presets->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No risk presets found. <a href="{{ route('admin.trading-management.config.risk-presets.create') }}">Create your first preset</a>.
                        </div>
                        @endif
                    </div>

                    <!-- Smart Risk Settings Tab -->
                    <div class="tab-pane fade" id="tab-smart-risk">
                        <h5 class="mb-3"><i class="fas fa-brain"></i> Smart Risk Settings (AI Adaptive)</h5>
                        
                        <form action="{{ route('admin.trading-management.config.smart-risk.update') }}" method="POST">
                            @csrf
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Smart Risk uses AI to adjust position sizing based on signal provider performance.
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="hidden" name="enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $smartRiskSettings['enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="enabled">
                                        <strong>Enable Smart Risk Management</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Minimum Provider Score (0-100)</label>
                                        <input type="number" name="min_provider_score" class="form-control" 
                                            value="{{ $smartRiskSettings['min_provider_score'] }}" min="0" max="100" step="1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Min Risk Multiplier</label>
                                        <input type="number" name="min_risk_multiplier" class="form-control" 
                                            value="{{ $smartRiskSettings['min_risk_multiplier'] }}" min="0.1" max="1" step="0.1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Max Risk Multiplier</label>
                                        <input type="number" name="max_risk_multiplier" class="form-control" 
                                            value="{{ $smartRiskSettings['max_risk_multiplier'] }}" min="1" max="5" step="0.1">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="slippage_buffer_enabled" value="0">
                                            <input type="checkbox" class="custom-control-input" id="slippage" name="slippage_buffer_enabled" value="1" {{ $smartRiskSettings['slippage_buffer_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="slippage">Auto-adjust SL for Slippage</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="dynamic_lot_enabled" value="0">
                                            <input type="checkbox" class="custom-control-input" id="dynamic" name="dynamic_lot_enabled" value="1" {{ $smartRiskSettings['dynamic_lot_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="dynamic">Dynamic Lot Sizing</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Global Settings Tab -->
                    <div class="tab-pane fade" id="tab-global-settings">
                        <h5 class="mb-3"><i class="fas fa-globe"></i> Global Settings</h5>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Configure global credentials and settings that apply to all connections.
                        </div>

                        <form action="{{ route('admin.trading-management.config.global-settings.update') }}" method="POST">
                            @csrf

                            <!-- MTAPI.io Global Config -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-key"></i> mtapi.io Global Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> You need an mtapi.io account to connect MT4/MT5 brokers. 
                                        <a href="https://mtapi.io" target="_blank" class="alert-link">Get API key here</a>
                                    </div>

                                    @php
                                        $globalSettings = \Illuminate\Support\Facades\Cache::get('trading_management_global_settings', [
                                            'mtapi_enabled' => false,
                                            'mtapi_api_key' => '',
                                            'mtapi_account_id' => '',
                                            'mtapi_base_url' => 'https://api.mtapi.io',
                                        ]);
                                        
                                        // Decrypt if encrypted
                                        if (!empty($globalSettings['mtapi_api_key']) && strpos($globalSettings['mtapi_api_key'], 'eyJpdiI6') === 0) {
                                            try {
                                                $globalSettings['mtapi_api_key'] = \Illuminate\Support\Facades\Crypt::decryptString($globalSettings['mtapi_api_key']);
                                            } catch (\Exception $e) {
                                                // Keep as is if decryption fails
                                            }
                                        }
                                    @endphp

                                    <div class="form-group">
                                        <div class="custom-control custom-switch custom-switch-lg">
                                            <input type="hidden" name="mtapi_enabled" value="0">
                                            <input type="checkbox" class="custom-control-input" id="mtapi_enabled" name="mtapi_enabled" value="1" {{ $globalSettings['mtapi_enabled'] ?? false ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="mtapi_enabled">
                                                <strong>Enable Global MTAPI Credentials</strong>
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">When enabled, these credentials will be used as defaults for all MT4/MT5 connections</small>
                                    </div>

                                    <div id="mtapiFields" style="{{ ($globalSettings['mtapi_enabled'] ?? false) ? '' : 'display:none;' }}">
                                        <div class="form-group">
                                            <label for="mtapi_api_key">mtapi.io API Key <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="mtapi_api_key" 
                                                   name="mtapi_api_key" 
                                                   value="{{ old('mtapi_api_key', $globalSettings['mtapi_api_key'] ?? '') }}" 
                                                   placeholder="Your mtapi.io API key"
                                                   {{ ($globalSettings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                            <small class="form-text text-muted">Get your API key from <a href="https://mtapi.io/dashboard" target="_blank">mtapi.io dashboard</a></small>
                                        </div>

                                        <div class="form-group">
                                            <label for="mtapi_account_id">mtapi.io Account ID</label>
                                            <input type="text" class="form-control" id="mtapi_account_id" 
                                                   name="mtapi_account_id" 
                                                   value="{{ old('mtapi_account_id', $globalSettings['mtapi_account_id'] ?? '') }}" 
                                                   placeholder="Your mtapi.io account ID (optional)"
                                                   {{ ($globalSettings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                            <small class="form-text text-muted">The account ID from your mtapi.io dashboard (optional, can be set per connection)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="mtapi_base_url">mtapi.io Base URL</label>
                                            <input type="url" class="form-control" id="mtapi_base_url" 
                                                   name="mtapi_base_url" 
                                                   value="{{ old('mtapi_base_url', $globalSettings['mtapi_base_url'] ?? 'https://api.mtapi.io') }}" 
                                                   placeholder="https://api.mtapi.io"
                                                   {{ ($globalSettings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                            <small class="form-text text-muted">Default: https://api.mtapi.io (usually no need to change)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Global Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict'
        
        $('#mtapi_enabled').on('change', function() {
            const enabled = $(this).is(':checked');
            const fields = $('#mtapiFields input, #mtapiFields select, #mtapiFields textarea');
            
            if (enabled) {
                $('#mtapiFields').slideDown();
                fields.prop('disabled', false);
            } else {
                $('#mtapiFields').slideUp();
                fields.prop('disabled', true);
            }
        });
    });
</script>
@endpush
