@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Exchange Connection</h4>
                    <a href="{{ route('admin.trading-management.config.exchange-connections.show', $exchangeConnection) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(!$credentialsValid)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> Credentials cannot be decrypted (may have been encrypted with a different APP_KEY). 
                    Please re-enter your credentials below.
                </div>
                @endif

                <form action="{{ route('admin.trading-management.config.exchange-connections.update', $exchangeConnection) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Connection Name -->
                    <div class="form-group">
                        <label>Connection Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $exchangeConnection->name) }}" required>
                    </div>

                    <!-- Connection Type -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connectionType" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="CRYPTO_EXCHANGE" {{ old('connection_type', $exchangeConnection->connection_type) === 'CRYPTO_EXCHANGE' ? 'selected' : '' }}>Crypto Exchange (CCXT)</option>
                                    <option value="FX_BROKER" {{ old('connection_type', $exchangeConnection->connection_type) === 'FX_BROKER' ? 'selected' : '' }}>Forex Broker (MT4/MT5)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Provider/Exchange <span class="text-danger">*</span></label>
                                <select name="provider" id="providerSelect" class="form-control" required>
                                    <option value="">Select Provider</option>
                                    <optgroup label="Forex Brokers" id="forexProviders">
                                        <option value="metaapi" {{ old('provider', $exchangeConnection->provider) === 'metaapi' ? 'selected' : '' }}>MetaApi.cloud (MT4/MT5)</option>
                                        <option value="mtapi" {{ old('provider', $exchangeConnection->provider) === 'mtapi' ? 'selected' : '' }}>mtapi.io (MT4/MT5) REST</option>
                                        <option value="mtapi_grpc" {{ old('provider', $exchangeConnection->provider) === 'mtapi_grpc' ? 'selected' : '' }}>mtapi.io (MT4/MT5) gRPC</option>
                                    </optgroup>
                                    <optgroup label="Crypto Exchanges (CCXT)" id="cryptoProviders">
                                        <option value="" disabled>Loading exchanges...</option>
                                    </optgroup>
                                </select>
                                <small class="text-muted" id="providerHint"></small>
                                <small class="text-info" id="exchangeCount" style="display:none;"></small>
                            </div>
                        </div>
                    </div>

                    <!-- Credentials -->
                    <div class="card mb-3" id="credentialsCard">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">API Credentials</h6>
                        </div>
                        <div class="card-body">
                            @if($exchangeConnection->provider === 'metaapi')
                                <!-- MetaApi Account ID -->
                                <div class="form-group">
                                    <label>MetaApi Account ID <span class="text-danger">*</span></label>
                                    <input type="text" name="credentials[account_id]" class="form-control" value="{{ old('credentials.account_id', $credentials['account_id'] ?? '') }}" required>
                                    <small class="text-muted">Your MetaApi account ID</small>
                                </div>
                                <input type="hidden" name="credentials[api_token]" value="{{ config('trading-management.metaapi.api_token') }}">
                            @else
                                <!-- API Key -->
                                <div class="form-group" id="apiKeyField">
                                    <label>API Key <span class="text-danger" id="apiKeyRequired">*</span></label>
                                    <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control" value="{{ old('credentials.api_key', $credentials['api_key'] ?? '') }}">
                                </div>
                                <!-- API Secret -->
                                <div class="form-group" id="apiSecretField">
                                    <label>API Secret <span class="text-danger" id="apiSecretRequired">*</span></label>
                                    <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control" value="{{ old('credentials.api_secret', $credentials['api_secret'] ?? '') }}" placeholder="{{ $credentialsValid ? '••••••••••••' : 'Enter new API secret' }}">
                                    @if($credentialsValid)
                                    <small class="text-muted">Leave blank to keep existing secret</small>
                                    @endif
                                </div>
                                <!-- API Passphrase (for some exchanges) -->
                                <div class="form-group" id="apiPassphraseField">
                                    <label>API Passphrase <span class="text-muted" id="apiPassphraseOptional">(Optional)</span></label>
                                    <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control" value="{{ old('credentials.api_passphrase', $credentials['api_passphrase'] ?? '') }}" placeholder="{{ $credentialsValid ? '••••••••••••' : 'Enter new passphrase' }}">
                                    <small class="text-muted" id="apiPassphraseHint">Optional - Required for some exchanges (OKX, KuCoin)</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Connection Features</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="data_fetching_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="dataFetching" name="data_fetching_enabled" value="1" {{ old('data_fetching_enabled', $exchangeConnection->data_fetching_enabled) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="dataFetching">
                                        <strong>Enable Data Fetching</strong>
                                        <br><small class="text-muted">Use this connection to fetch market data</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="trade_execution_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="tradeExecution" name="trade_execution_enabled" value="1" {{ old('trade_execution_enabled', $exchangeConnection->trade_execution_enabled) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="tradeExecution">
                                        <strong>Enable Trade Execution</strong>
                                        <br><small class="text-muted">Use this connection to execute trades</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Preset -->
                    <div class="form-group" id="presetField">
                        <label>Trading Preset</label>
                        <select name="preset_id" class="form-control">
                            <option value="">None</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset->id }}" {{ old('preset_id', $exchangeConnection->preset_id) == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Risk management preset for trade execution</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Connection
                        </button>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.show', $exchangeConnection) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide preset field based on trade execution
document.getElementById('tradeExecution').addEventListener('change', function() {
    document.getElementById('presetField').style.display = this.checked ? 'block' : 'none';
});

// Initialize preset field visibility
document.getElementById('presetField').style.display = document.getElementById('tradeExecution').checked ? 'block' : 'none';
</script>
@endsection
