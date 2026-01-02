@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Edit Execution Connection</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.operations.connections.update', $connection) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Connection Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $connection->name }}" required>
            </div>

            <div class="form-group">
                <label for="type">Connection Type</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="crypto" {{ $connection->type === 'crypto' ? 'selected' : '' }}>Crypto Exchange</option>
                    <option value="fx" {{ $connection->type === 'fx' ? 'selected' : '' }}>FX Broker</option>
                </select>
            </div>

            <div class="form-group">
                <label for="exchange_name">Exchange/Broker Name</label>
                <input type="text" class="form-control" id="exchange_name" name="exchange_name" value="{{ $connection->exchange_name }}" required>
            </div>

            <div class="form-group">
                <label>API Credentials (JSON)</label>
                <textarea class="form-control" name="credentials[raw]" rows="5">{{ json_encode($connection->credentials, JSON_PRETTY_PRINT) }}</textarea>
            </div>

            <div class="form-group">
                <label for="preset_id">Risk Preset</label>
                <select class="form-control" id="preset_id" name="preset_id">
                    <option value="">Select preset</option>
                    @foreach($presets as $preset)
                        <option value="{{ $preset->id }}" {{ $connection->preset_id == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="data_connection_id">Data Connection</label>
                <select class="form-control" id="data_connection_id" name="data_connection_id">
                    <option value="">Select data connection</option>
                    @foreach($dataConnections as $dc)
                        <option value="{{ $dc->id }}" {{ $connection->data_connection_id == $dc->id ? 'selected' : '' }}>{{ $dc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Connection
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

