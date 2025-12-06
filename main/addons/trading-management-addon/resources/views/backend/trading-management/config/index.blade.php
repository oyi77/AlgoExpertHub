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
                        @php
                            $globalConfig = \App\Services\GlobalConfigurationService::get('mtapi_global_settings', [
                                'api_key' => '',
                                'base_url' => 'mt5grpc.mtapi.io:443',
                                'timeout' => 30,
                                'default_host' => '78.140.180.198',
                                'default_port' => 443,
                                'demo_account' => [
                                    'user' => '62333850',
                                    'password' => 'tecimil4',
                                    'host' => '78.140.180.198',
                                    'port' => 443,
                                ],
                            ]);
                            
                            // Decrypt API key if present
                            if (!empty($globalConfig['api_key'])) {
                                try {
                                    $globalConfig['api_key'] = \Illuminate\Support\Facades\Crypt::decryptString($globalConfig['api_key']);
                                } catch (\Exception $e) {
                                    $globalConfig['api_key'] = '';
                                }
                            }
                        @endphp
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Global Configuration</strong> - These settings are shared across all MTAPI connections. Only admins can modify these settings.
                        </div>

                        <form action="{{ route('admin.trading-management.config.global-settings.update') }}" method="POST" id="globalSettingsForm">
                            @csrf

                            <!-- MTAPI gRPC Configuration -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-server"></i> MTAPI gRPC Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Configure these credentials once. All MTAPI connections will use these global settings.
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>API Key</label>
                                                <input type="password" name="api_key" class="form-control" value="{{ $globalConfig['api_key'] ?? '' }}" placeholder="Leave blank to keep existing">
                                                <small class="text-muted">MTAPI.io API key (optional, for future use)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Base URL <span class="text-danger">*</span></label>
                                                <input type="text" name="base_url" class="form-control" value="{{ $globalConfig['base_url'] ?? 'mt5grpc.mtapi.io:443' }}" required>
                                                <small class="text-muted">MTAPI gRPC server endpoint</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Timeout (seconds) <span class="text-danger">*</span></label>
                                                <input type="number" name="timeout" class="form-control" value="{{ $globalConfig['timeout'] ?? 30 }}" min="5" max="300" required>
                                                <small class="text-muted">Connection timeout in seconds</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Default Host</label>
                                                <input type="text" name="default_host" class="form-control" value="{{ $globalConfig['default_host'] ?? '78.140.180.198' }}">
                                                <small class="text-muted">Default MT5 server host</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Default Port</label>
                                                <input type="number" name="default_port" class="form-control" value="{{ $globalConfig['default_port'] ?? 443 }}" min="1" max="65535">
                                                <small class="text-muted">Default MT5 server port</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Demo Account Configuration -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-vial"></i> Demo Account Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Configure demo account credentials for testing connections. These are used for connection testing only.
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Demo User</label>
                                                <input type="text" name="demo_user" class="form-control" value="{{ $globalConfig['demo_account']['user'] ?? '62333850' }}">
                                                <small class="text-muted">MT5 demo account number</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Demo Password</label>
                                                <input type="password" name="demo_password" class="form-control" value="{{ $globalConfig['demo_account']['password'] ?? 'tecimil4' }}">
                                                <small class="text-muted">MT5 demo account password</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Demo Host</label>
                                                <input type="text" name="demo_host" class="form-control" value="{{ $globalConfig['demo_account']['host'] ?? '78.140.180.198' }}">
                                                <small class="text-muted">MT5 demo server host</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Demo Port</label>
                                                <input type="number" name="demo_port" class="form-control" value="{{ $globalConfig['demo_account']['port'] ?? 443 }}" min="1" max="65535">
                                                <small class="text-muted">MT5 demo server port</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="button" class="btn btn-info" id="testDemoConnection">
                                            <i class="fas fa-plug"></i> Test Demo Connection
                                        </button>
                                        <div id="testResult" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">
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
        
        // Test Demo Connection
        $('#testDemoConnection').on('click', function() {
            const btn = $(this);
            const result = $('#testResult');
            
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
            result.html('');
            
            $.ajax({
                url: '{{ route("admin.trading-management.config.global-settings.test-demo") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-plug"></i> Test Demo Connection');
                    
                    if (data.success) {
                        result.html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <strong>Connection Successful!</strong><br>
                                ${data.message}<br>
                                <small>Latency: ${data.latency}ms</small>
                            </div>
                        `);
                    } else {
                        result.html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> <strong>Connection Failed</strong><br>
                                ${data.message}
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-plug"></i> Test Demo Connection');
                    result.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> <strong>Error</strong><br>
                            ${xhr.responseJSON?.message || 'An error occurred'}
                        </div>
                    `);
                }
            });
        });
    });
</script>
@endpush
