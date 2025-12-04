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
                        <h3><i class="fas fa-brain text-{{ $smartRiskSettings['enabled'] ? 'success' : 'muted' }}"></i></h3>
                        <small class="{{ $smartRiskSettings['enabled'] ? 'text-success' : 'text-muted' }}">
                            {{ $smartRiskSettings['enabled'] ? 'Enabled' : 'Disabled' }}
                        </small>
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
                            <h5 class="mb-0"><i class="fas fa-plug"></i> Data Connections</h5>
                            <a href="{{ route('admin.trading-management.config.data-connections.create') }}" class="btn btn-primary">
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
                                        <th>Status</th>
                                        <th>Last Connected</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($connections as $connection)
                                    <tr>
                                        <td><strong>{{ $connection->name }}</strong></td>
                                        <td>
                                            @if($connection->type === 'mtapi')
                                            <span class="badge badge-primary">MT4/MT5</span>
                                            @elseif($connection->type === 'ccxt_crypto')
                                            <span class="badge badge-info">Crypto</span>
                                            @else
                                            <span class="badge badge-secondary">{{ $connection->type }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $connection->provider }}</td>
                                        <td>
                                            @if($connection->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                            @elseif($connection->status === 'error')
                                            <span class="badge badge-danger">Error</span>
                                            @else
                                            <span class="badge badge-secondary">{{ ucfirst($connection->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $connection->last_connected_at ? $connection->last_connected_at->diffForHumans() : 'Never' }}</td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.config.data-connections.edit', $connection) }}" class="btn btn-sm btn-info">
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
                            <i class="fas fa-info-circle"></i> No data connections yet. <a href="{{ route('admin.trading-management.config.data-connections.create') }}">Create your first connection</a>.
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
