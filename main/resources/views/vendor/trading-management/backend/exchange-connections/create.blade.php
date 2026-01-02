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
                        <label for="name">Connection Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name', '') }}" aria-describedby="@error('name') name-error @else name-help @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name')
                            <div id="name-error" class="invalid-feedback d-block" role="alert">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>Error:</strong> {{ $message }}
                                <small class="d-block mt-1">How to fix: Please enter a name for this connection. Use a descriptive name like "Binance Main Account" or "MT5 Demo Account".</small>
                            </div>
                        @else
                            <small id="name-help" class="text-muted">A friendly name to identify this connection (e.g., "Binance Main Account")</small>
                        @enderror
                    </div>

                    <!-- Connection Type -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="connectionType">Connection Type <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connectionType" class="form-control @error('connection_type') is-invalid @enderror" required aria-describedby="@error('connection_type') connection_type-error @else connection_type-help @enderror" @error('connection_type') aria-invalid="true" @enderror>
                                    <option value="">Select Type</option>
                                    <option value="CRYPTO_EXCHANGE" {{ old('connection_type') === 'CRYPTO_EXCHANGE' ? 'selected' : '' }}>Crypto Exchange (CCXT)</option>
                                    <option value="FX_BROKER" {{ old('connection_type') === 'FX_BROKER' ? 'selected' : '' }}>Forex Broker (MT4/MT5)</option>
                                </select>
                                @error('connection_type')
                                    <div id="connection_type-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: Please select the type of exchange. Crypto Exchange: For Binance, Coinbase, and other cryptocurrency exchanges. Forex Broker: For MT4/MT5 brokers via MetaApi or mtapi.io.</small>
                                    </div>
                                @else
                                    <small id="connection_type-help" class="text-muted">Crypto Exchange: For cryptocurrency exchanges. Forex Broker: For MT4/MT5 brokers.</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="providerSelect">Provider/Exchange <span class="text-danger">*</span></label>
                                <select name="provider" id="providerSelect" class="form-control @error('provider') is-invalid @enderror" required aria-describedby="@error('provider') provider-error @else providerHint @enderror" @error('provider') aria-invalid="true" @enderror>
                                    <option value="">Select Provider</option>
                                    <optgroup label="Forex Brokers" id="forexProviders">
                                        <option value="metaapi" {{ old('provider') === 'metaapi' ? 'selected' : '' }}>MetaApi.cloud (MT4/MT5)</option>
                                        <option value="mtapi" {{ old('provider') === 'mtapi' ? 'selected' : '' }}>mtapi.io (MT4/MT5) REST</option>
                                        <option value="mtapi_grpc" {{ old('provider') === 'mtapi_grpc' ? 'selected' : '' }}>mtapi.io (MT4/MT5) gRPC</option>
                                    </optgroup>
                                    <optgroup label="Crypto Exchanges (CCXT)" id="cryptoProviders">
                                        <option value="" disabled>Loading exchanges...</option>
                                    </optgroup>
                                </select>
                                @error('provider')
                                    <div id="provider-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: Please select the exchange or broker provider you want to connect to. Choose from the list based on your connection type selection above.</small>
                                    </div>
                                @else
                                    <small class="text-muted" id="providerHint"></small>
                                    <small class="text-info" id="exchangeCount" style="display:none;"></small>
                                @enderror
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
                                        <label for="mtLogin">MT Account Number <span class="text-danger">*</span></label>
                                        <input type="text" id="mtLogin" class="form-control" placeholder="206764329" value="{{ old('mt_login', '') }}">
                                        <small class="text-muted">Your MetaTrader account login number</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtServer">MT Server <span class="text-danger">*</span></label>
                                        <input type="text" id="mtServer" class="form-control" placeholder="Exness-MT5Trial7" value="{{ old('mt_server', '') }}">
                                        <small class="text-muted">Your broker's MT server name (e.g., Exness-MT5Trial7)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtPassword">MT Password <span class="text-danger">*</span></label>
                                        <input type="password" id="mtPassword" class="form-control" placeholder="Your MT account password">
                                        <small class="text-muted">Use investor password for read-only, master password for trading</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtPlatform">Platform <span class="text-danger">*</span></label>
                                        <select id="mtPlatform" class="form-control">
                                            <option value="MT5" {{ old('mt_platform', 'MT5') === 'MT5' ? 'selected' : '' }}>MT5</option>
                                            <option value="MT4" {{ old('mt_platform') === 'MT4' ? 'selected' : '' }}>MT4</option>
                                        </select>
                                        <small class="text-muted">Select your MetaTrader platform version</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="mtAccountName">Account Name <span class="text-muted">(Optional)</span></label>
                                <input type="text" id="mtAccountName" class="form-control" placeholder="My Trading Account" value="{{ old('mt_account_name', '') }}">
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
                                <label for="metaapiAccountId">MetaApi Account ID <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control @error('credentials.account_id') is-invalid @enderror" placeholder="Enter MetaApi account ID" value="{{ old('credentials.account_id', '') }}" aria-describedby="@error('credentials.account_id') credentials.account_id-error @else metaapiAccountId-help @enderror" @error('credentials.account_id') aria-invalid="true" @enderror>
                                @error('credentials.account_id')
                                    <div id="credentials.account_id-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.</small>
                                    </div>
                                @else
                                    <small id="metaapiAccountId-help" class="text-muted">Get this from your MetaApi dashboard after adding the account. Go to MetaApi.cloud → Accounts → Copy the Account ID.</small>
                                @enderror
                            </div>
                            <button type="button" class="btn btn-info" id="checkMetaApiStatusBtn">
                                <i class="fas fa-check-circle"></i> Check Account Status
                            </button>
                            <div id="metaapiStatusResult" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Credentials (for non-MetaApi providers) -->
                    <div class="card mb-3" id="credentialsCard" style="display:none;">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">API Credentials</h6>
                        </div>
                        <div class="card-body">
                            <!-- MetaApi Token (hidden, auto-filled from config) -->
                            <input type="hidden" name="credentials[api_token]" id="metaapiToken" value="{{ config('trading-management.metaapi.api_token') }}">
                            
                            <div class="form-group" id="apiKeyField">
                                <label for="apiKeyInput">API Key <span class="text-danger" id="apiKeyRequired">*</span></label>
                                <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control @error('credentials.api_key') is-invalid @enderror" value="{{ old('credentials.api_key', '') }}" aria-describedby="@error('credentials.api_key') credentials.api_key-error @else apiKey-help @enderror" @error('credentials.api_key') aria-invalid="true" @enderror>
                                @error('credentials.api_key')
                                    <div id="credentials.api_key-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: API Key is required. You can find this in your exchange account settings under API Management. Create a new API key if you don't have one.</small>
                                    </div>
                                @else
                                    <small id="apiKey-help" class="text-muted">Find this in your exchange account settings under API Management. Create a new API key with appropriate permissions.</small>
                                @enderror
                            </div>
                            <div class="form-group" id="apiSecretField">
                                <label for="apiSecretInput">API Secret <span class="text-danger" id="apiSecretRequired">*</span></label>
                                <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control @error('credentials.api_secret') is-invalid @enderror" aria-describedby="@error('credentials.api_secret') credentials.api_secret-error @else apiSecret-help @enderror" @error('credentials.api_secret') aria-invalid="true" @enderror>
                                @error('credentials.api_secret')
                                    <div id="credentials.api_secret-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: API Secret is required. This is shown only once when you create the API key. If you lost it, you need to create a new API key.</small>
                                    </div>
                                @else
                                    <small id="apiSecret-help" class="text-muted">This is shown only once when you create the API key. Keep it secure and never share it. If you lost it, create a new API key.</small>
                                @enderror
                            </div>
                            <div class="form-group" id="apiPassphraseField">
                                <label for="apiPassphraseInput">API Passphrase <span class="text-muted" id="apiPassphraseOptional">(Optional)</span></label>
                                <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control @error('credentials.api_passphrase') is-invalid @enderror" aria-describedby="@error('credentials.api_passphrase') credentials.api_passphrase-error @else apiPassphraseHint @enderror" @error('credentials.api_passphrase') aria-invalid="true" @enderror>
                                @error('credentials.api_passphrase')
                                    <div id="credentials.api_passphrase-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: API Passphrase is required for this exchange. Set it when creating your API key in the exchange settings.</small>
                                    </div>
                                @else
                                    <small class="text-muted" id="apiPassphraseHint">Optional - Required for some exchanges (OKX, KuCoin, Coinbase Pro). Set this when creating your API key.</small>
                                @enderror
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
                                    <input type="hidden" name="data_fetching_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="dataFetching" name="data_fetching_enabled" value="1" checked>
                                    <label class="custom-control-label" for="dataFetching">
                                        <strong>Enable Data Fetching</strong>
                                        <br><small class="text-muted">Use this connection to fetch market data (candles, prices)</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="trade_execution_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="tradeExecution" name="trade_execution_enabled" value="1">
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
                        <label for="preset_id">Trading Preset <span class="text-muted">(Optional)</span></label>
                        <select name="preset_id" id="preset_id" class="form-control @error('preset_id') is-invalid @enderror" aria-describedby="@error('preset_id') preset_id-error @else preset_id-help @enderror" @error('preset_id') aria-invalid="true" @enderror>
                            <option value="">None</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset->id }}" {{ old('preset_id') == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                            @endforeach
                        </select>
                        @error('preset_id')
                            <div id="preset_id-error" class="invalid-feedback d-block" role="alert">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>Error:</strong> {{ $message }}
                                <small class="d-block mt-1">How to fix: Please select a valid trading preset or leave it as "None".</small>
                            </div>
                        @else
                            <small id="preset_id-help" class="text-muted">Risk management preset for trade execution. Optional - you can configure risk settings later.</small>
                        @enderror
                    </div>

                    <!-- Data Settings (if data fetching enabled) -->
                    <div class="card mb-3" id="dataSettingsCard">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Data Fetching Settings</h6>
                        </div>
                        <div class="card-body">
                            <!-- Dynamic Symbols/Pairs Field -->
                            <div class="form-group" id="symbolsField">
                                <label for="symbolsInput" id="symbolsLabel">Symbols to Monitor <span class="text-muted">(Optional)</span></label>
                                <textarea name="data_settings[symbols]" id="symbolsInput" class="form-control @error('data_settings.symbols') is-invalid @enderror" rows="3" placeholder="BTCUSDT&#10;ETHUSDT&#10;BNBUSDT" aria-describedby="@error('data_settings.symbols') data_settings.symbols-error @else symbolsHint @enderror" @error('data_settings.symbols') aria-invalid="true" @enderror>{{ old('data_settings.symbols', '') }}</textarea>
                                @error('data_settings.symbols')
                                    <div id="data_settings.symbols-error" class="invalid-feedback d-block" role="alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Error:</strong> {{ $message }}
                                        <small class="d-block mt-1">How to fix: Please enter valid symbols, one per line (e.g., BTCUSDT, ETHUSDT).</small>
                                    </div>
                                @else
                                    <small class="text-muted" id="symbolsHint">One symbol per line (e.g., BTCUSDT, ETHUSDT). Leave empty to monitor all available symbols.</small>
                                @enderror
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
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
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
// Load CCXT exchanges dynamically
let ccxtExchanges = {};
let exchangesLoaded = false;

function loadCcxtExchanges() {
    if (exchangesLoaded) {
        return Promise.resolve();
    }
    
    // Add loading indicator to crypto section
    const cryptoOptgroup = document.getElementById('cryptoProviders');
    if (cryptoOptgroup) {
        cryptoOptgroup.innerHTML = '<option value="" disabled>Loading exchanges...</option>';
    }

    // Create a timeout promise
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

    return fetch('{{ route("admin.trading-management.config.exchange-connections.ccxt-exchanges") }}', { signal: controller.signal })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Received non-JSON response from server (possible auth redirect)");
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.exchanges) {
                ccxtExchanges = data.exchanges;
                exchangesLoaded = true;
                populateCryptoProviders();
                const exchangeCount = document.getElementById('exchangeCount');
                if (exchangeCount) {
                    exchangeCount.textContent = `${Object.keys(ccxtExchanges).length} exchanges available via CCXT`;
                    exchangeCount.style.display = 'inline';
                }
            } else {
                console.warn('Failed to load CCXT exchanges (data success=false):', data.message);
                populateDefaultCryptoProviders();
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.warn('Error loading CCXT exchanges (using defaults):', error);
            if (error.name === 'AbortError') {
                console.warn('Request timed out');
            }
            populateDefaultCryptoProviders();
        });
}

function populateCryptoProviders() {
    const cryptoOptgroup = document.getElementById('cryptoProviders');
    cryptoOptgroup.innerHTML = '';
    
    // Separate popular and other exchanges
    const popular = [];
    const others = [];
    
    Object.values(ccxtExchanges).forEach(exchange => {
        if (exchange.popular) {
            popular.push(exchange);
        } else {
            others.push(exchange);
        }
    });
    
    // Add popular exchanges first
    popular.forEach(exchange => {
        const option = document.createElement('option');
        option.value = exchange.id;
        option.textContent = exchange.name;
        cryptoOptgroup.appendChild(option);
    });
    
    // Add separator if both groups exist
    if (popular.length > 0 && others.length > 0) {
        const separator = document.createElement('option');
        separator.disabled = true;
        separator.textContent = '──────────';
        cryptoOptgroup.appendChild(separator);
    }
    
    // Add other exchanges
    others.forEach(exchange => {
        const option = document.createElement('option');
        option.value = exchange.id;
        option.textContent = exchange.name;
        cryptoOptgroup.appendChild(option);
    });
}

function populateDefaultCryptoProviders() {
    const cryptoOptgroup = document.getElementById('cryptoProviders');
    cryptoOptgroup.innerHTML = '';
    
    const defaults = [
        {id: 'binance', name: 'Binance'},
        {id: 'coinbase', name: 'Coinbase'},
        {id: 'coinbasepro', name: 'Coinbase Pro'},
        {id: 'kraken', name: 'Kraken'},
        {id: 'bybit', name: 'Bybit'},
        {id: 'kucoin', name: 'KuCoin'},
        {id: 'okx', name: 'OKX'},
    ];
    
    defaults.forEach(exchange => {
        const option = document.createElement('option');
        option.value = exchange.id;
        option.textContent = exchange.name;
        cryptoOptgroup.appendChild(option);
    });
}

// Connection type change handler
function updateFormBasedOnConnectionType() {
    const connectionType = document.getElementById('connectionType').value;
    const providerSelect = document.getElementById('providerSelect');
    const forexOptgroup = document.getElementById('forexProviders');
    const cryptoOptgroup = document.getElementById('cryptoProviders');
    
    // Reset provider selection
    providerSelect.value = '';
    updateFormBasedOnProvider();
    
    // Show/hide provider options based on connection type
    if (connectionType === 'CRYPTO_EXCHANGE') {
        // Show only crypto providers
        forexOptgroup.style.display = 'none';
        cryptoOptgroup.style.display = '';
        // Load CCXT exchanges if not loaded
        if (!exchangesLoaded) {
            loadCcxtExchanges();
        }
        // Update symbols field for crypto
        document.getElementById('symbolsLabel').textContent = 'Symbols to Monitor (Crypto)';
        document.getElementById('symbolsInput').placeholder = 'BTCUSDT\nETHUSDT\nBNBUSDT';
        document.getElementById('symbolsHint').textContent = 'One symbol per line (e.g., BTCUSDT, ETHUSDT, BNBUSDT)';
        // Show API credentials for crypto
        document.getElementById('credentialsCard').style.display = 'block';
        document.getElementById('apiKeyField').style.display = 'block';
        document.getElementById('apiSecretField').style.display = 'block';
        document.getElementById('apiPassphraseField').style.display = 'block';
        // Hide MetaAPI sections
        document.getElementById('metaapiInfo').style.display = 'none';
        document.getElementById('metaapiAddAccountCard').style.display = 'none';
        document.getElementById('metaapiExistingCard').style.display = 'none';
    } else if (connectionType === 'FX_BROKER') {
        // Show only forex providers
        forexOptgroup.style.display = '';
        cryptoOptgroup.style.display = 'none';
        // Update symbols field for forex
        document.getElementById('symbolsLabel').textContent = 'Currency Pairs to Monitor (Forex)';
        document.getElementById('symbolsInput').placeholder = 'EURUSD\nGBPUSD\nUSDJPY';
        document.getElementById('symbolsHint').textContent = 'One pair per line (e.g., EURUSD, GBPUSD, USDJPY)';
        // Hide API credentials initially (will show based on provider)
        document.getElementById('credentialsCard').style.display = 'none';
    } else {
        // No connection type selected - hide everything
        forexOptgroup.style.display = '';
        cryptoOptgroup.style.display = '';
        document.getElementById('symbolsField').style.display = 'none';
        document.getElementById('credentialsCard').style.display = 'none';
        document.getElementById('metaapiInfo').style.display = 'none';
        document.getElementById('metaapiAddAccountCard').style.display = 'none';
        document.getElementById('metaapiExistingCard').style.display = 'none';
    }
    
    // Show symbols field if connection type is selected
    if (connectionType) {
        document.getElementById('symbolsField').style.display = 'block';
    }
}

// Provider change handler
// Provider change handler
// Provider change handler
function updateFormBasedOnProvider() {
    const providerSelect = document.getElementById('providerSelect');
    const provider = providerSelect ? providerSelect.value : '';
    const connectionType = document.getElementById('connectionType').value;
    
    // Elements to toggle
    const credentialsCard = document.getElementById('credentialsCard');
    const metaapiInfo = document.getElementById('metaapiInfo');
    const metaapiAddCard = document.getElementById('metaapiAddAccountCard');
    const metaapiExistingCard = document.getElementById('metaapiExistingCard');
    const providerHint = document.getElementById('providerHint');
    
    if (!credentialsCard) return;

    // Helper to set display
    const setDisplay = (elements, show) => {
        const display = show ? 'block' : 'none';
        if (Array.isArray(elements)) {
            elements.forEach(el => { if(el) el.style.display = display; });
        } else if (elements) {
            elements.style.display = display;
        }
    };

    // 1. Reset all specific sections first
    setDisplay([metaapiInfo, metaapiAddCard, metaapiExistingCard, credentialsCard], false);
    if (providerHint) providerHint.textContent = '';

    // 2. If no provider, stop here
    if (!provider) return;

    // 3. Logic based on provider type
    if (provider === 'metaapi') {
        // MetaAPI Case
        setDisplay([metaapiInfo, metaapiAddCard, metaapiExistingCard], true);
        if (providerHint) providerHint.textContent = 'MetaApi.cloud - Add MT account or use existing';
        
    } else if (['mtapi', 'mtapi_grpc'].includes(provider)) {
        // Other Forex Brokers Case
        setDisplay(credentialsCard, true);
        setDisplay(document.getElementById('apiKeyField'), true);
        setDisplay(document.getElementById('apiSecretField'), true);
        setDisplay(document.getElementById('apiPassphraseField'), false); // Disable passphrase
        
        // Set required attributes
        document.getElementById('apiKeyInput').required = true;
        document.getElementById('apiSecretInput').required = true;
        document.getElementById('apiPassphraseInput').required = false;

        if (providerHint) providerHint.textContent = 'mtapi.io - Requires API credentials';

    } else {
        // Crypto Case (Default for anything else)
        setDisplay(credentialsCard, true);
        setDisplay(document.getElementById('apiKeyField'), true);
        setDisplay(document.getElementById('apiSecretField'), true);
        setDisplay(document.getElementById('apiPassphraseField'), true);

        // Determine if passphrase is truly required from CCXT data
        let needsPassphrase = false;
        let exchangeName = provider;
        
        if (typeof ccxtExchanges !== 'undefined' && ccxtExchanges[provider]) {
            needsPassphrase = ccxtExchanges[provider].needs_passphrase;
            exchangeName = ccxtExchanges[provider].name;
        } else {
            // Default heuristics if ccxt data not loaded
            exchangeName = provider.charAt(0).toUpperCase() + provider.slice(1);
            needsPassphrase = ['coinbasepro', 'kucoin', 'okx'].includes(provider);
        }

        document.getElementById('apiKeyInput').required = true;
        document.getElementById('apiSecretInput').required = true;
        document.getElementById('apiPassphraseInput').required = needsPassphrase;

        // update hints
        const passphraseLabel = document.getElementById('apiPassphraseOptional');
        if (passphraseLabel) {
            passphraseLabel.innerHTML = needsPassphrase ? '<span class="text-danger">*</span>' : '<span class="text-muted">(Optional)</span>';
        }
        
        const passphraseHint = document.getElementById('apiPassphraseHint');
        if (passphraseHint) {
            passphraseHint.textContent = needsPassphrase ? ('Required for ' + exchangeName) : 'Optional - Required for some exchanges';
        }

        if (providerHint) providerHint.textContent = `${exchangeName} - Requires API Key and Secret`;
    }
}

// Event listeners and initialization - wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    const connectionTypeEl = document.getElementById('connectionType');
    const providerSelectEl = document.getElementById('providerSelect');
    
    if (connectionTypeEl) {
        connectionTypeEl.addEventListener('change', updateFormBasedOnConnectionType);
    }
    
    if (providerSelectEl) {
        providerSelectEl.addEventListener('change', function() {
            console.log('Provider changed to:', providerSelectEl.value);
            updateFormBasedOnProvider();
        });
    }
    
    // Initialize form state immediately
    updateFormBasedOnConnectionType();
    
    // Also update based on provider if already selected (e.g., from form validation errors)
    if (providerSelectEl && providerSelectEl.value) {
        updateFormBasedOnProvider();
    }
    
    // Then load exchanges in background
    loadCcxtExchanges();
});

document.getElementById('tradeExecution').addEventListener('change', function() {
    document.getElementById('presetField').style.display = this.checked ? 'block' : 'none';
});

document.getElementById('dataFetching').addEventListener('change', function() {
    const isChecked = this.checked;
    document.getElementById('dataSettingsCard').style.display = isChecked ? 'block' : 'none';
    // Show symbols field if connection type is selected
    if (isChecked) {
        const connectionType = document.getElementById('connectionType').value;
        if (connectionType) {
            document.getElementById('symbolsField').style.display = 'block';
        }
    }
});

// Form validation before submission
document.querySelector('form').addEventListener('submit', function(e) {
    const connectionType = document.getElementById('connectionType').value;
    const provider = document.getElementById('providerSelect').value;
    const isMetaApi = provider === 'metaapi';
    
    // Validate MetaAPI account ID if MetaAPI is selected
    if (isMetaApi) {
        const accountId = document.getElementById('metaapiAccountId').value;
        if (!accountId || accountId.trim() === '') {
            e.preventDefault();
            alert('Please enter a MetaAPI Account ID or add a new account to MetaAPI first.');
            return false;
        }
    }
    
    // Validate API credentials for crypto/other forex providers
    if (!isMetaApi && (isCryptoProvider || ['mtapi', 'mtapi_grpc'].includes(provider))) {
        const apiKey = document.getElementById('apiKeyInput').value;
        const apiSecret = document.getElementById('apiSecretInput').value;
        
        if (!apiKey || !apiSecret) {
            e.preventDefault();
            alert('Please fill in all required API credentials.');
            return false;
        }
        
        // Check passphrase if required (from CCXT exchange data)
        if (isCryptoProvider) {
            const exchange = ccxtExchanges[provider];
            if (exchange && exchange.needs_passphrase) {
                const passphrase = document.getElementById('apiPassphraseInput').value;
                if (!passphrase) {
                    e.preventDefault();
                    alert('API Passphrase is required for ' + exchange.name + '.');
                    return false;
                }
            }
        }
    }
    
    return true;
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

