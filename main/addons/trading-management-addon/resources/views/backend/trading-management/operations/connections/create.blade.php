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
                        <select name="exchange_name" id="exchangeName" class="form-control" required>
                            <option value="">Select Exchange/Broker</option>
                            <optgroup label="Crypto Exchanges" id="cryptoExchanges" style="display:none;">
                                <option value="binance">Binance</option>
                                <option value="coinbase">Coinbase</option>
                                <option value="kraken">Kraken</option>
                                <option value="okx">OKX</option>
                                <option value="kucoin">KuCoin</option>
                            </optgroup>
                            <optgroup label="Forex Brokers" id="fxBrokers" style="display:none;">
                                <option value="MT4">MT4 (MetaTrader 4)</option>
                                <option value="MT5">MT5 (MetaTrader 5)</option>
                            </optgroup>
                        </select>
                        <small class="text-muted">Select your exchange or broker platform</small>
                    </div>

                    <!-- MT4/MT5 Specific Fields -->
                    <div id="mtFields" style="display:none;">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-cog"></i> MT4/MT5 Connection Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Account Number *</label>
                                            <input type="text" name="credentials[account_number]" class="form-control" placeholder="e.g., 12345678">
                                            <small class="text-muted">Your MT4/MT5 account number</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Server Name *</label>
                                            <input type="text" name="credentials[server]" class="form-control" placeholder="e.g., broker-Demo">
                                            <small class="text-muted">MT4/MT5 server name</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Broker Name</label>
                                    <input type="text" name="credentials[broker_name]" class="form-control" placeholder="e.g., IC Markets, FXTM">
                                    <small class="text-muted">Optional: Your broker's name</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- mtapi.io Credentials -->
                    <div id="mtApiFields" style="display:none;">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-key"></i> mtapi.io API Credentials</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> You need an mtapi.io account to connect MT4/MT5. 
                                    <a href="https://mtapi.io" target="_blank" class="alert-link">Get API key here</a>
                                </div>
                                <div class="form-group">
                                    <label>mtapi.io API Key *</label>
                                    <input type="text" name="credentials[api_key]" class="form-control" placeholder="Your mtapi.io API key">
                                </div>
                                <div class="form-group">
                                    <label>mtapi.io Account ID *</label>
                                    <input type="text" name="credentials[account_id]" class="form-control" placeholder="Your mtapi.io account ID">
                                    <small class="text-muted">The account ID from your mtapi.io dashboard</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Crypto Exchange Credentials -->
                    <div id="cryptoFields" style="display:none;">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">API Credentials</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>API Key *</label>
                                    <input type="text" name="credentials[api_key]" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>API Secret *</label>
                                    <input type="password" name="credentials[api_secret]" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>API Passphrase</label>
                                    <input type="password" name="credentials[api_passphrase]" class="form-control">
                                    <small class="text-muted">Optional - Required for some exchanges (OKX, KuCoin)</small>
                                </div>
                            </div>
                        </div>
                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const connectionType = document.getElementById('connectionType');
    const exchangeName = document.getElementById('exchangeName');
    const cryptoExchanges = document.getElementById('cryptoExchanges');
    const fxBrokers = document.getElementById('fxBrokers');
    const mtFields = document.getElementById('mtFields');
    const mtApiFields = document.getElementById('mtApiFields');
    const cryptoFields = document.getElementById('cryptoFields');

    function updateFormFields() {
        const type = connectionType.value;
        const exchange = exchangeName.value;

        // Hide all fields first
        cryptoExchanges.style.display = 'none';
        fxBrokers.style.display = 'none';
        mtFields.style.display = 'none';
        mtApiFields.style.display = 'none';
        cryptoFields.style.display = 'none';

        // Show appropriate options based on type
        if (type === 'CRYPTO_EXCHANGE') {
            cryptoExchanges.style.display = 'block';
            cryptoFields.style.display = 'block';
        } else if (type === 'FX_BROKER') {
            fxBrokers.style.display = 'block';
            if (exchange === 'MT4' || exchange === 'MT5') {
                mtFields.style.display = 'block';
                mtApiFields.style.display = 'block';
            }
        }
    }

    connectionType.addEventListener('change', function() {
        exchangeName.value = '';
        updateFormFields();
    });

    exchangeName.addEventListener('change', function() {
        updateFormFields();
    });

    // Initial update
    updateFormFields();
});
</script>
@endsection
