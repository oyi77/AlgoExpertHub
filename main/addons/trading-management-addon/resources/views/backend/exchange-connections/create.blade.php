@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Create Exchange Connection</h4>
                    <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.config.exchange-connections.store') }}" method="POST">
                    @csrf

                    <!-- Connection Name -->
                    <div class="form-group">
                        <label>Connection Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <!-- Connection Type -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connectionType" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="CRYPTO_EXCHANGE">Crypto Exchange (CCXT)</option>
                                    <option value="FX_BROKER">Forex Broker (MT4/MT5)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Provider/Exchange <span class="text-danger">*</span></label>
                                <select name="provider" id="providerSelect" class="form-control" required>
                                    <option value="">Select Provider</option>
                                    <optgroup label="Forex Brokers">
                                        <option value="metaapi">MetaApi.cloud (MT4/MT5)</option>
                                        <option value="mtapi">mtapi.io (MT4/MT5) REST</option>
                                        <option value="mtapi_grpc">mtapi.io (MT4/MT5) gRPC</option>
                                    </optgroup>
                                    <optgroup label="Crypto Exchanges">
                                        <option value="binance">Binance</option>
                                        <option value="coinbase">Coinbase</option>
                                        <option value="kraken">Kraken</option>
                                        <option value="bybit">Bybit</option>
                                    </optgroup>
                                </select>
                                <small class="text-muted" id="providerHint"></small>
                            </div>
                        </div>
                    </div>

                    <!-- MetaApi Account Addition Section -->
                    <div class="alert alert-info" id="metaapiInfo" style="display:none;">
                        <h6><i class="fas fa-info-circle"></i> MetaApi.cloud Integration</h6>
                        <p class="mb-2">You can either:</p>
                        <ol class="mb-0">
                            <li><strong>Add new MT account to MetaApi</strong> - We'll automatically add your MT account to MetaApi and create the connection</li>
                            <li><strong>Use existing MetaApi account</strong> - If you already added the account to MetaApi, just enter the MetaApi account ID</li>
                        </ol>
                    </div>

                    <!-- Option 1: Add New Account to MetaApi -->
                    <div class="card mb-3" id="metaapiAddAccountCard" style="display:none;">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-plus-circle"></i> Add MT Account to MetaApi</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>MT Account Number <span class="text-danger">*</span></label>
                                        <input type="text" id="mtLogin" class="form-control" placeholder="206764329">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>MT Server <span class="text-danger">*</span></label>
                                        <input type="text" id="mtServer" class="form-control" placeholder="Exness-MT5Trial7">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>MT Password <span class="text-danger">*</span></label>
                                        <input type="password" id="mtPassword" class="form-control" placeholder="Your MT account password">
                                        <small class="text-muted">Use investor password for read-only, master password for trading</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Platform <span class="text-danger">*</span></label>
                                        <select id="mtPlatform" class="form-control">
                                            <option value="MT5">MT5</option>
                                            <option value="MT4">MT4</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Account Name</label>
                                <input type="text" id="mtAccountName" class="form-control" placeholder="My Trading Account">
                                <small class="text-muted">Human-readable name for this account</small>
                            </div>
                            <button type="button" class="btn btn-success" id="addToMetaApiBtn">
                                <i class="fas fa-cloud-upload-alt"></i> Add Account to MetaApi
                            </button>
                            <div id="metaapiAddResult" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Option 2: Use Existing MetaApi Account -->
                    <div class="card mb-3" id="metaapiExistingCard" style="display:none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-link"></i> Use Existing MetaApi Account</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>MetaApi Account ID <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control" placeholder="Enter MetaApi account ID">
                                <small class="text-muted">Get this from your MetaApi dashboard after adding the account</small>
                            </div>
                            <button type="button" class="btn btn-info" id="checkMetaApiStatusBtn">
                                <i class="fas fa-check-circle"></i> Check Account Status
                            </button>
                            <div id="metaapiStatusResult" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Credentials (for non-MetaApi providers) -->
                    <div class="card mb-3" id="credentialsCard">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">API Credentials</h6>
                        </div>
                        <div class="card-body">
                            <!-- MetaApi Token (hidden, auto-filled from config) -->
                            <input type="hidden" name="credentials[api_token]" id="metaapiToken" value="{{ config('trading-management.metaapi.api_token') }}">
                            
                            <div class="form-group" id="apiKeyField">
                                <label>API Key <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[api_key]" class="form-control">
                            </div>
                            <div class="form-group" id="apiSecretField">
                                <label>API Secret <span class="text-danger">*</span></label>
                                <input type="password" name="credentials[api_secret]" class="form-control">
                            </div>
                            <div class="form-group" id="apiPassphraseField">
                                <label>API Passphrase</label>
                                <input type="password" name="credentials[api_passphrase]" class="form-control">
                                <small class="text-muted">Optional - Required for some exchanges (OKX, KuCoin)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Features - What to use this connection for -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Connection Features</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="custom-control-input" id="dataFetching" name="is_active" value="1" checked>
                                    <label class="custom-control-label" for="dataFetching">
                                        <strong>Enable Data Fetching</strong>
                                        <br><small class="text-muted">Use this connection to fetch market data (candles, prices)</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="custom-control-input" id="tradeExecution" name="is_active" value="1">
                                    <label class="custom-control-label" for="tradeExecution">
                                        <strong>Enable Trade Execution</strong>
                                        <br><small class="text-muted">Use this connection to execute trades automatically</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Preset (if execution enabled) -->
                    <div class="form-group" id="presetField" style="display:none;">
                        <label>Trading Preset</label>
                        <select name="preset_id" class="form-control">
                            <option value="">None</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Risk management preset for trade execution</small>
                    </div>

                    <!-- Data Settings (if data fetching enabled) -->
                    <div class="card mb-3" id="dataSettingsCard">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Data Fetching Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Symbols to Monitor</label>
                                <textarea name="data_settings[symbols]" class="form-control" rows="3" placeholder="BTCUSDT&#10;ETHUSDT&#10;BNBUSDT"></textarea>
                                <small class="text-muted">One symbol per line</small>
                            </div>
                            <div class="form-group">
                                <label>Timeframes</label>
                                <div class="row">
                                    @foreach(['M1', 'M5', 'M15', 'H1', 'H4', 'D1'] as $tf)
                                    <div class="col-md-4">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="tf_{{ $tf }}" name="data_settings[timeframes][]" value="{{ $tf }}">
                                            <label class="custom-control-label" for="tf_{{ $tf }}">{{ $tf }}</label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Create Connection
                        </button>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('tradeExecution').addEventListener('change', function() {
    document.getElementById('presetField').style.display = this.checked ? 'block' : 'none';
});

document.getElementById('dataFetching').addEventListener('change', function() {
    document.getElementById('dataSettingsCard').style.display = this.checked ? 'block' : 'none';
});

// MetaApi provider handling
document.getElementById('providerSelect').addEventListener('change', function() {
    const provider = this.value;
    const isMetaApi = provider === 'metaapi';
    
    // Show/hide MetaApi sections
    document.getElementById('metaapiInfo').style.display = isMetaApi ? 'block' : 'none';
    document.getElementById('metaapiAddAccountCard').style.display = isMetaApi ? 'block' : 'none';
    document.getElementById('metaapiExistingCard').style.display = isMetaApi ? 'block' : 'none';
    
    // Hide/show regular credentials
    document.getElementById('credentialsCard').style.display = isMetaApi ? 'none' : 'block';
    document.getElementById('apiKeyField').style.display = isMetaApi ? 'none' : 'block';
    document.getElementById('apiSecretField').style.display = isMetaApi ? 'none' : 'block';
    document.getElementById('apiPassphraseField').style.display = isMetaApi ? 'none' : 'block';
    
    // Update hint
    const hints = {
        'metaapi': 'MetaApi.cloud - Add MT account or use existing',
        'mtapi': 'mtapi.io REST API',
        'mtapi_grpc': 'mtapi.io gRPC (faster)',
        'binance': 'Binance exchange',
        'coinbase': 'Coinbase exchange',
        'kraken': 'Kraken exchange',
        'bybit': 'Bybit exchange'
    };
    document.getElementById('providerHint').textContent = hints[provider] || '';
});

// Add account to MetaApi
document.getElementById('addToMetaApiBtn').addEventListener('click', function() {
    const btn = this;
    const resultDiv = document.getElementById('metaapiAddResult');
    
    const data = {
        login: document.getElementById('mtLogin').value,
        password: document.getElementById('mtPassword').value,
        server: document.getElementById('mtServer').value,
        name: document.getElementById('mtAccountName').value || document.getElementById('mtLogin').value,
        platform: document.getElementById('mtPlatform').value,
        account_type: 'cloud-g2',
        _token: '{{ csrf_token() }}'
    };
    
    if (!data.login || !data.password || !data.server) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Please fill in all required fields</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding to MetaApi...';
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Adding account to MetaApi...</div>';
    
    fetch('{{ route("admin.trading-management.config.exchange-connections.add-metaapi-account") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '<br><small>MetaApi Account ID: ' + data.metaapi_account_id + '</small></div>';
            document.getElementById('metaapiAccountId').value = data.metaapi_account_id;
            document.getElementById('metaapiAddAccountCard').style.display = 'none';
            document.getElementById('metaapiExistingCard').style.display = 'block';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</div>';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Add Account to MetaApi';
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Error: ' + error.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Add Account to MetaApi';
    });
});

// Check MetaApi account status
document.getElementById('checkMetaApiStatusBtn').addEventListener('click', function() {
    const accountId = document.getElementById('metaapiAccountId').value;
    const resultDiv = document.getElementById('metaapiStatusResult');
    
    if (!accountId) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Please enter MetaApi Account ID</div>';
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Checking account status...</div>';
    
    fetch('{{ route("admin.trading-management.config.exchange-connections.metaapi-account-status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({account_id: accountId, _token: '{{ csrf_token() }}'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Account Status: <strong>' + data.status + '</strong></div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + (data.message || 'Failed to check status') + '</div>';
        }
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check-circle"></i> Check Account Status';
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Error: ' + error.message + '</div>';
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check-circle"></i> Check Account Status';
    });
});
</script>
@endsection

