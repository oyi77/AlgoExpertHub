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

                    <!-- Provider/Exchange Selection -->
                    <div class="form-group">
                        <label for="providerSelect">Provider/Exchange <span class="text-danger">*</span></label>
                        <select name="exchange_name" id="providerSelect" class="form-control @error('exchange_name') is-invalid @enderror" required>
                            <option value="">Select Provider</option>
                            <optgroup label="Forex Brokers" id="forexProviders" style="display:none;">
                                <option value="metaapi" {{ old('exchange_name') === 'metaapi' ? 'selected' : '' }}>MetaApi.cloud (MT4/MT5)</option>
                                <option value="mtapi" {{ old('exchange_name') === 'mtapi' ? 'selected' : '' }}>mtapi.io (MT4/MT5) REST</option>
                                <option value="mtapi_grpc" {{ old('exchange_name') === 'mtapi_grpc' ? 'selected' : '' }}>mtapi.io (MT4/MT5) gRPC</option>
                            </optgroup>
                            <optgroup label="Crypto Exchanges (CCXT)" id="cryptoProviders" style="display:none;">
                                <option value="" disabled>Loading exchanges...</option>
                            </optgroup>
                        </select>
                        <small class="text-muted" id="providerHint"></small>
                        <small class="text-info" id="exchangeCount" style="display:none;"></small>
                        @error('exchange_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
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
                                <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control @error('credentials.account_id') is-invalid @enderror" placeholder="Enter MetaApi account ID" value="{{ old('credentials.account_id', '') }}">
                                <small class="text-muted">Get this from your MetaApi dashboard after adding the account. Go to MetaApi.cloud → Accounts → Copy the Account ID.</small>
                                @error('credentials.account_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
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
                            <input type="hidden" name="credentials[api_token]" id="metaapiToken" value="{{ config('trading-management.metaapi.api_token', '') }}">
                            
                            <div class="form-group" id="apiKeyField">
                                <label for="apiKeyInput">API Key <span class="text-danger" id="apiKeyRequired">*</span></label>
                                <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control @error('credentials.api_key') is-invalid @enderror" value="{{ old('credentials.api_key', '') }}">
                                <small class="text-muted">Find this in your exchange account settings under API Management. Create a new API key with appropriate permissions.</small>
                                @error('credentials.api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                </div>
                            <div class="form-group" id="apiSecretField">
                                <label for="apiSecretInput">API Secret <span class="text-danger" id="apiSecretRequired">*</span></label>
                                <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control @error('credentials.api_secret') is-invalid @enderror" value="{{ old('credentials.api_secret', '') }}" placeholder="{{ old('credentials.api_secret') ? '••••••••••••' : '' }}">
                                <small class="text-muted">This is shown only once when you create the API key. Keep it secure and never share it. If you lost it, create a new API key.</small>
                                @error('credentials.api_secret')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                </div>
                            <div class="form-group" id="apiPassphraseField">
                                <label for="apiPassphraseInput">API Passphrase <span class="text-muted" id="apiPassphraseOptional">(Optional)</span></label>
                                <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control @error('credentials.api_passphrase') is-invalid @enderror">
                                <small class="text-muted" id="apiPassphraseHint">Optional - Required for some exchanges (OKX, KuCoin, Coinbase Pro). Set this when creating your API key.</small>
                                @error('credentials.api_passphrase')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
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
// Load CCXT exchanges dynamically
let ccxtExchanges = {};
let exchangesLoaded = false;

function loadCcxtExchanges() {
    if (exchangesLoaded) {
        return Promise.resolve();
    }
    
    // Try to load from exchange-connections route if available
    const route = '{{ route("admin.trading-management.config.exchange-connections.ccxt-exchanges", [], false) }}';
    if (!route || route.includes('{')) {
        // Route doesn't exist, use defaults
        populateDefaultCryptoProviders();
        return Promise.resolve();
    }
    
    return fetch(route)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.exchanges) {
                ccxtExchanges = data.exchanges;
                exchangesLoaded = true;
                populateCryptoProviders();
                document.getElementById('exchangeCount').textContent = `${Object.keys(ccxtExchanges).length} exchanges available via CCXT`;
                document.getElementById('exchangeCount').style.display = 'inline';
            } else {
                console.error('Failed to load CCXT exchanges:', data.message);
                populateDefaultCryptoProviders();
            }
        })
        .catch(error => {
            console.error('Error loading CCXT exchanges:', error);
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
        // Hide API credentials initially (will show based on provider)
        document.getElementById('credentialsCard').style.display = 'none';
    } else {
        // No connection type selected - hide everything
        forexOptgroup.style.display = '';
        cryptoOptgroup.style.display = '';
        document.getElementById('credentialsCard').style.display = 'none';
        document.getElementById('metaapiInfo').style.display = 'none';
        document.getElementById('metaapiAddAccountCard').style.display = 'none';
        document.getElementById('metaapiExistingCard').style.display = 'none';
    }
}

// Provider change handler
function updateFormBasedOnProvider() {
    try {
    const provider = document.getElementById('providerSelect').value;
    const connectionType = document.getElementById('connectionType').value;
    const isMetaApi = provider === 'metaapi';
    const isForexProvider = ['metaapi', 'mtapi', 'mtapi_grpc'].includes(provider);
    const isCryptoProvider = connectionType === 'CRYPTO_EXCHANGE';
        
        console.log('updateFormBasedOnProvider called:', { provider, connectionType, isMetaApi, isForexProvider, isCryptoProvider });
        
        const credentialsCard = document.getElementById('credentialsCard');
        if (!credentialsCard) {
            console.error('credentialsCard element not found!');
            return;
        }
        
        // If no provider selected, hide everything
        if (!provider) {
            document.getElementById('metaapiInfo').style.display = 'none';
            document.getElementById('metaapiAddAccountCard').style.display = 'none';
            document.getElementById('metaapiExistingCard').style.display = 'none';
            credentialsCard.style.display = 'none';
            const providerHint = document.getElementById('providerHint');
            if (providerHint) providerHint.textContent = '';
            return;
        }
    
    // MetaAPI specific handling
    if (isMetaApi) {
        document.getElementById('metaapiInfo').style.display = 'block';
        document.getElementById('metaapiAddAccountCard').style.display = 'block';
        document.getElementById('metaapiExistingCard').style.display = 'block';
            credentialsCard.style.display = 'none';
    } else {
        document.getElementById('metaapiInfo').style.display = 'none';
        document.getElementById('metaapiAddAccountCard').style.display = 'none';
        document.getElementById('metaapiExistingCard').style.display = 'none';
    }
    
    // API Credentials handling
    if (isCryptoProvider) {
        // Crypto exchanges need API credentials
            console.log('Showing credentials for crypto provider');
            credentialsCard.style.display = 'block';
        document.getElementById('apiKeyField').style.display = 'block';
        document.getElementById('apiSecretField').style.display = 'block';
        document.getElementById('apiKeyInput').required = true;
        document.getElementById('apiSecretInput').required = true;
        // Check if exchange needs passphrase from loaded data
        const exchange = ccxtExchanges[provider];
        const needsPassphrase = exchange && exchange.needs_passphrase;
        document.getElementById('apiPassphraseField').style.display = 'block';
        document.getElementById('apiPassphraseInput').required = needsPassphrase;
        if (needsPassphrase) {
            document.getElementById('apiPassphraseOptional').innerHTML = '<span class="text-danger">*</span>';
            const exchangeName = exchange ? exchange.name : provider.charAt(0).toUpperCase() + provider.slice(1);
            document.getElementById('apiPassphraseHint').textContent = 'Required for ' + exchangeName;
        } else {
            document.getElementById('apiPassphraseOptional').innerHTML = '<span class="text-muted">(Optional)</span>';
            document.getElementById('apiPassphraseHint').textContent = 'Optional - Required for some exchanges (OKX, KuCoin, Coinbase Pro)';
        }
    } else if (isForexProvider && !isMetaApi) {
        // Other forex providers (mtapi) need API credentials
            console.log('Showing credentials for forex provider (mtapi)');
            credentialsCard.style.display = 'block';
        document.getElementById('apiKeyField').style.display = 'block';
        document.getElementById('apiSecretField').style.display = 'block';
        document.getElementById('apiPassphraseField').style.display = 'none';
        document.getElementById('apiKeyInput').required = true;
        document.getElementById('apiSecretInput').required = true;
        } else if (!isMetaApi && provider) {
            // Any other provider (fallback) - show credentials
            console.log('Showing credentials for other provider (fallback)');
            credentialsCard.style.display = 'block';
            document.getElementById('apiKeyField').style.display = 'block';
            document.getElementById('apiSecretField').style.display = 'block';
            document.getElementById('apiPassphraseField').style.display = 'block';
            document.getElementById('apiKeyInput').required = true;
            document.getElementById('apiSecretInput').required = true;
            document.getElementById('apiPassphraseInput').required = false;
    }
    
    // Update hint
    let hintText = '';
    if (isMetaApi) {
        hintText = 'MetaApi.cloud - Add MT account or use existing';
    } else if (isForexProvider) {
        hintText = 'mtapi.io - Requires API credentials';
    } else if (isCryptoProvider) {
        const exchange = ccxtExchanges[provider];
        if (exchange) {
            const passphraseNote = exchange.needs_passphrase ? ' (Requires Passphrase)' : '';
            hintText = `${exchange.name} - Requires API Key and Secret${passphraseNote}`;
        } else {
            hintText = `${provider.charAt(0).toUpperCase() + provider.slice(1)} - Requires API Key and Secret`;
        }
        } else if (provider) {
            hintText = `${provider.charAt(0).toUpperCase() + provider.slice(1)} - Requires API Key and Secret`;
        }
        const providerHint = document.getElementById('providerHint');
        if (providerHint) providerHint.textContent = hintText;
    } catch (error) {
        console.error('Error in updateFormBasedOnProvider:', error);
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

// Form validation before submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
    const connectionType = document.getElementById('connectionType').value;
    const provider = document.getElementById('providerSelect').value;
    const isMetaApi = provider === 'metaapi';
    const isCryptoProvider = connectionType === 'CRYPTO_EXCHANGE';
    
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
    }

// Initialize form state and load exchanges
loadCcxtExchanges().then(() => {
    updateFormBasedOnConnectionType();
        // Also update based on provider if already selected (e.g., from form validation errors)
        if (providerSelectEl && providerSelectEl.value) {
            updateFormBasedOnProvider();
        }
    });
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
    
    // Try exchange-connections route first, fallback to execution-connections
    const route = '{{ route("admin.trading-management.config.exchange-connections.add-metaapi-account", [], false) }}';
    const fallbackRoute = '{{ route("admin.trading-management.operations.connections.add-metaapi-account", [], false) }}';
    const useRoute = (route && !route.includes('{')) ? route : fallbackRoute;
    
    fetch(useRoute, {
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
    
    const route = '{{ route("admin.trading-management.config.exchange-connections.metaapi-account-status", [], false) }}';
    const fallbackRoute = '{{ route("admin.trading-management.operations.connections.metaapi-account-status", [], false) }}';
    const useRoute = (route && !route.includes('{')) ? route : fallbackRoute;
    
    fetch(useRoute, {
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
