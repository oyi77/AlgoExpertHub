@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Execution Connection</h4>
                    <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.operations.connections.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Connection Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" id="connectionType" required>
                                    <option value="">Select Type</option>
                                    <option value="CRYPTO_EXCHANGE">Crypto Exchange (CCXT)</option>
                                    <option value="FX_BROKER">Forex Broker (MT4/MT5)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Exchange/Broker Name *</label>
                        <input type="text" name="exchange_name" class="form-control" placeholder="e.g., binance, oanda" required>
                        <small class="text-muted">For crypto: binance, coinbase, kraken, etc. For FX: mtapi.io endpoint</small>
                    </div>

                    <div class="form-group">
                        <label>API Credentials * (JSON format)</label>
                        <textarea name="credentials[raw]" class="form-control" rows="6" placeholder='{"apiKey": "your-key", "secret": "your-secret"}' required></textarea>
                        <small class="text-muted">Crypto: {apiKey, secret, password (optional)}. FX: {accountId, password, serverUrl}</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Trading Preset</label>
                                <select name="preset_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($presets as $preset)
                                    <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data Connection (for market data)</label>
                                <select name="data_connection_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($dataConnections as $dc)
                                    <option value="{{ $dc->id }}">{{ $dc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Connection
                        </button>
                        <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
