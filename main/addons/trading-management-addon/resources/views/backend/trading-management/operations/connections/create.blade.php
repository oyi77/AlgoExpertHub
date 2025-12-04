@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Create Execution Connection</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.operations.connections.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Connection Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="type">Connection Type</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="crypto">Crypto Exchange</option>
                    <option value="fx">FX Broker</option>
                </select>
            </div>

            <div class="form-group">
                <label for="exchange_name">Exchange/Broker Name</label>
                <input type="text" class="form-control" id="exchange_name" name="exchange_name" placeholder="e.g., Binance, MT4, MT5" required>
            </div>

            <div class="form-group">
                <label>API Credentials (JSON)</label>
                <textarea class="form-control" name="credentials[raw]" rows="5" placeholder='{"api_key": "...", "api_secret": "..."}'></textarea>
                <small class="text-muted">Enter credentials as JSON object</small>
            </div>

            <div class="form-group">
                <label for="preset_id">Risk Preset</label>
                <select class="form-control" id="preset_id" name="preset_id">
                    <option value="">Select preset</option>
                    @foreach($presets as $preset)
                        <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="data_connection_id">Data Connection</label>
                <select class="form-control" id="data_connection_id" name="data_connection_id">
                    <option value="">Select data connection</option>
                    @foreach($dataConnections as $dc)
                        <option value="{{ $dc->id }}">{{ $dc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_admin_owned" name="is_admin_owned" value="1">
                    <label class="custom-control-label" for="is_admin_owned">Admin-Owned (Global)</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Connection
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

