@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ __('Create Data Connection') }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __('Create Data Connection') }}</h4>
            <a href="{{ route('user.trading.configuration.index', ['tab' => 'data-connections']) }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('Back') }}
            </a>
        </div>
    </div>
    <div class="card-body">
                <form action="{{ route('user.exchange-connections.store') }}" method="POST">
                    @csrf

                    <!-- Connection Name -->
                    <div class="form-group mb-3">
                        <label for="name">{{ __('Connection Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required placeholder="{{ __('My Data Connection') }}" value="{{ old('name', '') }}" autocomplete="new-password" aria-describedby="@error('name') name-error @else name-help @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name')
                            <div id="name-error" class="invalid-feedback d-block" role="alert">
                                <i class="las la-exclamation-circle"></i>
                                <strong>{{ __('Error:') }}</strong> {{ $message }}
                                <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('Please enter a name for this connection. Use a descriptive name like "Binance Main Account" or "MT5 Demo Account".') }}</small>
                            </div>
                        @else
                            <small id="name-help" class="text-muted">{{ __('A friendly name to identify this connection (e.g., "Binance Main Account")') }}</small>
                        @enderror
                    </div>

                    <!-- Connection Type -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="connectionType">{{ __('Connection Type') }} <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connectionType" class="form-control @error('connection_type') is-invalid @enderror" required aria-describedby="@error('connection_type') connection_type-error @else connection_type-help @enderror" @error('connection_type') aria-invalid="true" @enderror>
                                    <option value="">{{ __('Select Type') }}</option>
                                    <option value="DATA_ONLY" {{ old('connection_type') === 'DATA_ONLY' ? 'selected' : '' }}>{{ __('Data Only') }}</option>
                                    <option value="EXECUTION_ONLY" {{ old('connection_type') === 'EXECUTION_ONLY' ? 'selected' : '' }}>{{ __('Execution Only') }}</option>
                                    <option value="BOTH" {{ old('connection_type') === 'BOTH' ? 'selected' : '' }}>{{ __('Both Data & Execution') }}</option>
                                </select>
                                @error('connection_type')
                                    <div id="connection_type-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('Please select what this connection will be used for. Data Only: Fetch market data only. Execution Only: Execute trades only. Both: Full access for data and trading.') }}</small>
                                    </div>
                                @else
                                    <small id="connection_type-help" class="text-muted">{{ __('Data Only: Fetch market data only. Execution Only: Execute trades only. Both: Full access.') }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="exchangeType">{{ __('Exchange Type') }} <span class="text-danger">*</span></label>
                                <select name="exchange_type" id="exchangeType" class="form-control @error('exchange_type') is-invalid @enderror" required aria-describedby="@error('exchange_type') exchange_type-error @else exchange_type-help @enderror" @error('exchange_type') aria-invalid="true" @enderror>
                                    <option value="">{{ __('Select Exchange Type') }}</option>
                                    <option value="CRYPTO_EXCHANGE" {{ old('exchange_type') === 'CRYPTO_EXCHANGE' ? 'selected' : '' }}>{{ __('Crypto Exchange (CCXT)') }}</option>
                                    <option value="FX_BROKER" {{ old('exchange_type') === 'FX_BROKER' ? 'selected' : '' }}>{{ __('Forex Broker (MT4/MT5)') }}</option>
                                </select>
                                @error('exchange_type')
                                    <div id="exchange_type-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('Please select the type of exchange. Crypto Exchange: For Binance, Coinbase, and other cryptocurrency exchanges. Forex Broker: For MT4/MT5 brokers via MetaApi or mtapi.io.') }}</small>
                                    </div>
                                @else
                                    <small id="exchange_type-help" class="text-muted">{{ __('Crypto Exchange: Binance, Coinbase, etc. Forex Broker: MT4/MT5 via MetaApi or mtapi.io') }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Provider/Exchange -->
                    <div class="form-group mb-3">
                        <label for="providerSelect">{{ __('Provider/Exchange') }} <span class="text-danger">*</span></label>
                        <select name="exchange_name" id="providerSelect" class="form-control @error('exchange_name') is-invalid @enderror" required aria-describedby="@error('exchange_name') exchange_name-error @else providerHint @enderror" @error('exchange_name') aria-invalid="true" @enderror onchange="if(typeof updateFormBasedOnProvider === 'function') updateFormBasedOnProvider()">
                            <option value="">{{ __('Select Provider') }}</option>
                            <optgroup label="{{ __('Forex Brokers') }}" id="forexProviders">
                                <option value="metaapi" {{ old('exchange_name') === 'metaapi' ? 'selected' : '' }}>MetaApi.cloud (MT4/MT5)</option>
                                <option value="mtapi" {{ old('exchange_name') === 'mtapi' ? 'selected' : '' }}>mtapi.io (MT4/MT5) REST</option>
                                <option value="mtapi_grpc" {{ old('exchange_name') === 'mtapi_grpc' ? 'selected' : '' }}>mtapi.io (MT4/MT5) gRPC</option>
                            </optgroup>
                            <optgroup label="{{ __('Crypto Exchanges (CCXT)') }}" id="cryptoProviders">
                                <option value="binance" {{ old('exchange_name') === 'binance' ? 'selected' : '' }}>Binance</option>
                                <option value="coinbase" {{ old('exchange_name') === 'coinbase' ? 'selected' : '' }}>Coinbase</option>
                                <option value="coinbasepro" {{ old('exchange_name') === 'coinbasepro' ? 'selected' : '' }}>Coinbase Pro</option>
                                <option value="kraken" {{ old('exchange_name') === 'kraken' ? 'selected' : '' }}>Kraken</option>
                                <option value="bybit" {{ old('exchange_name') === 'bybit' ? 'selected' : '' }}>Bybit</option>
                                <option value="kucoin" {{ old('exchange_name') === 'kucoin' ? 'selected' : '' }}>KuCoin</option>
                                <option value="okx" {{ old('exchange_name') === 'okx' ? 'selected' : '' }}>OKX</option>
                            </optgroup>
                        </select>
                        @error('exchange_name')
                            <div id="exchange_name-error" class="invalid-feedback d-block" role="alert">
                                <i class="las la-exclamation-circle"></i>
                                <strong>{{ __('Error:') }}</strong> {{ $message }}
                                <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('Please select the exchange or broker provider you want to connect to. Choose from the list based on your exchange type selection above.') }}</small>
                            </div>
                        @else
                            <small id="providerHint" class="text-muted">{{ __('Select the exchange or broker provider you want to connect to') }}</small>
                        @enderror
                    </div>

                    <!-- MetaApi Account Addition Section -->
                    <div class="alert alert-info" id="metaapiInfo" style="display:none;">
                        <h6><i class="las la-info-circle"></i> {{ __('MetaApi.cloud Integration') }}</h6>
                        <p class="mb-2">{{ __('You can either:') }}</p>
                        <ol class="mb-0">
                            <li><strong>{{ __('Add new MT account to MetaApi') }}</strong> - {{ __('We\'ll automatically add your MT account to MetaApi and create the connection') }}</li>
                            <li><strong>{{ __('Use existing MetaApi account') }}</strong> - {{ __('If you already added the account to MetaApi, just enter the MetaApi account ID') }}</li>
                        </ol>
                    </div>

                    <!-- Option 1: Add New Account to MetaApi -->
                    <div class="card mb-3" id="metaapiAddAccountCard" style="display:none;">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="las la-plus-circle"></i> {{ __('Add MT Account to MetaApi') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtLogin">{{ __('MT Account Number') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="mtLogin" class="form-control" placeholder="206764329" value="{{ old('mt_login', '') }}">
                                        <small class="text-muted">{{ __('Your MetaTrader account login number') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtServer">{{ __('MT Server') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="mtServer" class="form-control" placeholder="Exness-MT5Trial7" value="{{ old('mt_server', '') }}">
                                        <small class="text-muted">{{ __('Your broker\'s MT server name (e.g., Exness-MT5Trial7)') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtPassword">{{ __('MT Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" id="mtPassword" class="form-control" placeholder="{{ __('Your MT account password') }}">
                                        <small class="text-muted">{{ __('Use investor password for read-only, master password for trading') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mtPlatform">{{ __('Platform') }} <span class="text-danger">*</span></label>
                                        <select id="mtPlatform" class="form-control">
                                            <option value="MT5" {{ old('mt_platform', 'MT5') === 'MT5' ? 'selected' : '' }}>MT5</option>
                                            <option value="MT4" {{ old('mt_platform') === 'MT4' ? 'selected' : '' }}>MT4</option>
                                        </select>
                                        <small class="text-muted">{{ __('Select your MetaTrader platform version') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="mtAccountName">{{ __('Account Name') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                                <input type="text" id="mtAccountName" class="form-control" placeholder="{{ __('My Trading Account') }}" value="{{ old('mt_account_name', '') }}">
                                <small class="text-muted">{{ __('Human-readable name for this account') }}</small>
                            </div>
                            <button type="button" class="btn btn-success" id="addToMetaApiBtn">
                                <i class="las la-cloud-upload-alt"></i> {{ __('Add Account to MetaApi') }}
                            </button>
                            <div id="metaapiAddResult" class="mt-3"></div>
                            <div class="mt-2 text-center">
                                <a href="javascript:void(0)" id="showExistingAccountLink" class="text-muted">{{ __('Cancel / I have an Account ID') }}</a>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Use Existing MetaApi Account -->
                    <div class="card mb-3" id="metaapiExistingCard" style="display:none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="las la-link"></i> {{ __('Use Existing MetaApi Account') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="metaapiAccountId">{{ __('MetaApi Account ID') }} <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control @error('credentials.account_id') is-invalid @enderror" placeholder="{{ __('Enter MetaApi account ID') }}" value="{{ old('credentials.account_id', '') }}" aria-describedby="@error('credentials.account_id') credentials.account_id-error @else metaapiAccountId-help @enderror" @error('credentials.account_id') aria-invalid="true" @enderror>
                                @error('credentials.account_id')
                                    <div id="credentials.account_id-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.') }}</small>
                                    </div>
                                @else
                                    <small id="metaapiAccountId-help" class="text-muted">{{ __('Get this from your MetaApi dashboard after adding the account. Go to MetaApi.cloud → Accounts → Copy the Account ID.') }}</small>
                                @enderror
                            </div>
                            <button type="button" class="btn btn-primary" id="checkMetaApiStatusBtn">
                                <i class="las la-check-circle"></i> {{ __('Check Account Status') }}
                            </button>
                            <div id="metaapiStatusResult" class="mt-3"></div>
                            
                            <div class="mt-3 text-center">
                                <a href="javascript:void(0)" id="showAddAccountLink">{{ __('Don\'t have an Account ID? Add new MT account') }}</a>
                            </div>
                        </div>
                    </div>

                    <!-- API Credentials -->
                    <div class="card mb-3" id="credentialsCard" style="display:none;">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="las la-key"></i> {{ __('API Credentials') }}</h6>
                        </div>
                        <div class="card-body">
                            <!-- Security Warning -->
                            <div class="alert alert-warning" role="alert">
                                <h6 class="alert-heading"><i class="las la-shield-alt"></i> {{ __('Security Notice') }}</h6>
                                <ul class="mb-0 small">
                                    <li>{{ __('Never share your API keys with anyone') }}</li>
                                    <li>{{ __('We recommend creating a separate API key for this platform') }}</li>
                                    <li>{{ __('Enable IP whitelist in your exchange settings if available') }}</li>
                                    <li>{{ __('Required permissions: Read, Trade (No withdrawal permissions needed)') }}</li>
                                </ul>
                            </div>
                            
                            <!-- MetaApi Token (hidden, auto-filled from config) -->
                            <input type="hidden" name="credentials[api_token]" id="metaapiToken" value="{{ config('trading-management.metaapi.api_token', '') }}">
                            
                            <div class="form-group" id="apiKeyField" style="display:none;">
                                <label for="apiKeyInput">{{ __('API Key') }} <span class="text-danger" id="apiKeyRequired">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control @error('credentials.api_key') is-invalid @enderror" value="{{ old('credentials.api_key', '') }}" placeholder="{{ __('Enter your API Key') }}" aria-describedby="@error('credentials.api_key') credentials.api_key-error @else apiKey-help @enderror" @error('credentials.api_key') aria-invalid="true" @enderror>
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('apiKeyInput').type = document.getElementById('apiKeyInput').type === 'password' ? 'text' : 'password'; this.innerHTML = document.getElementById('apiKeyInput').type === 'password' ? '<i class=\'las la-eye\'></i>' : '<i class=\'las la-eye-slash\'></i>';" title="{{ __('Toggle visibility') }}">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                                @error('credentials.api_key')
                                    <div id="credentials.api_key-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('API Key is required. You can find this in your exchange account settings under API Management. Create a new API key if you don\'t have one.') }}</small>
                                    </div>
                                @else
                                    <small id="apiKey-help" class="text-muted">
                                        <i class="las la-info-circle"></i> {{ __('Find this in your exchange account settings under API Management. Create a new API key with appropriate permissions (Read & Trade, no withdrawal).') }}
                                    </small>
                                @enderror
                            </div>
                            <div class="form-group" id="apiSecretField" style="display:none;">
                                <label for="apiSecretInput">{{ __('API Secret') }} <span class="text-danger" id="apiSecretRequired">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control @error('credentials.api_secret') is-invalid @enderror" placeholder="{{ __('Enter your API Secret') }}" aria-describedby="@error('credentials.api_secret') credentials.api_secret-error @else apiSecret-help @enderror" @error('credentials.api_secret') aria-invalid="true" @enderror>
                                    <button type="button" class="btn btn-outline-secondary" onclick="const input = document.getElementById('apiSecretInput'); input.type = input.type === 'password' ? 'text' : 'password'; this.innerHTML = input.type === 'password' ? '<i class=\'las la-eye\'></i>' : '<i class=\'las la-eye-slash\'></i>';" title="{{ __('Toggle visibility') }}">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                                @error('credentials.api_secret')
                                    <div id="credentials.api_secret-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('API Secret is required. This is shown only once when you create the API key. If you lost it, you need to create a new API key.') }}</small>
                                    </div>
                                @else
                                    <small id="apiSecret-help" class="text-muted">
                                        <i class="las la-info-circle"></i> {{ __('This is shown only once when you create the API key. Keep it secure and never share it. If you lost it, create a new API key.') }}
                                    </small>
                                @enderror
                            </div>
                            <div class="form-group" id="apiPassphraseField" style="display:none;">
                                <label for="apiPassphraseInput">{{ __('API Passphrase') }} <span class="text-muted" id="apiPassphraseOptional">({{ __('Optional') }})</span></label>
                                <div class="input-group">
                                    <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control @error('credentials.api_passphrase') is-invalid @enderror" placeholder="{{ __('Enter your API Passphrase (if required)') }}" aria-describedby="@error('credentials.api_passphrase') credentials.api_passphrase-error @else apiPassphrase-help @enderror" @error('credentials.api_passphrase') aria-invalid="true" @enderror>
                                    <button type="button" class="btn btn-outline-secondary" onclick="const input = document.getElementById('apiPassphraseInput'); input.type = input.type === 'password' ? 'text' : 'password'; this.innerHTML = input.type === 'password' ? '<i class=\'las la-eye\'></i>' : '<i class=\'las la-eye-slash\'></i>';" title="{{ __('Toggle visibility') }}">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                                @error('credentials.api_passphrase')
                                    <div id="credentials.api_passphrase-error" class="invalid-feedback d-block" role="alert">
                                        <i class="las la-exclamation-circle"></i>
                                        <strong>{{ __('Error:') }}</strong> {{ $message }}
                                        <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('API Passphrase is required for this exchange. Set it when creating your API key in the exchange settings.') }}</small>
                                    </div>
                                @else
                                    <small id="apiPassphrase-help" class="text-muted">
                                        <i class="las la-info-circle"></i> {{ __('Required for some exchanges (OKX, KuCoin, Coinbase Pro). Set this when creating your API key.') }}
                                    </small>
                                @enderror
                            </div>
                            
                            <!-- Test Connection Button -->
                            <div class="mt-3" id="testConnectionSection" style="display:none;">
                                <button type="button" class="btn btn-primary" id="testConnectionBtn">
                                    <i class="las la-vial"></i> {{ __('Test Connection') }}
                                </button>
                                <small class="text-muted d-block mt-2">{{ __('Test your credentials before saving to ensure they work correctly.') }}</small>
                                <div id="testConnectionResult" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Preset -->
                    @if(isset($presets) && $presets->count() > 0)
                    <div class="form-group mb-3">
                        <label for="preset_id">{{ __('Trading Preset') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                        <select name="preset_id" id="preset_id" class="form-control @error('preset_id') is-invalid @enderror" aria-describedby="@error('preset_id') preset_id-error @else preset_id-help @enderror" @error('preset_id') aria-invalid="true" @enderror>
                            <option value="">{{ __('None') }}</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset->id }}" {{ old('preset_id') == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                            @endforeach
                        </select>
                        @error('preset_id')
                            <div id="preset_id-error" class="invalid-feedback d-block" role="alert">
                                <i class="las la-exclamation-circle"></i>
                                <strong>{{ __('Error:') }}</strong> {{ $message }}
                                <small class="d-block mt-1">{{ __('How to fix:') }} {{ __('Please select a valid trading preset or leave it as "None".') }}</small>
                            </div>
                        @else
                            <small id="preset_id-help" class="text-muted">{{ __('Risk management preset for trade execution. Optional - you can configure risk settings later.') }}</small>
                        @enderror
                    </div>
                    @endif

                    <div class="form-group">
                        <button type="submit" class="btn sp_theme_btn">
                            <i class="las la-save"></i> {{ __('Create Connection') }}
                        </button>
                        <a href="{{ route('user.trading.configuration.index', ['tab' => 'data-connections']) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DEBUG: Alert to confirm script is running
    // alert('DEBUG: DOMContentLoaded fired'); 

    const exchangeType = document.getElementById('exchangeType');
    const providerSelect = document.getElementById('providerSelect');
    const forexProviders = document.getElementById('forexProviders');
    const cryptoProviders = document.getElementById('cryptoProviders');
    const credentialsCard = document.getElementById('credentialsCard');
    const apiKeyField = document.getElementById('apiKeyField');
    const apiSecretField = document.getElementById('apiSecretField');
    const apiPassphraseField = document.getElementById('apiPassphraseField');
    const metaapiAccountIdField = document.getElementById('metaapiAccountIdField');
    const providerHint = document.getElementById('providerHint');

    // DEBUG: Check if elements exist
    if (!providerSelect) {
        console.error('CRITICAL: providerSelect element missing!');
    } else {
        console.log('providerSelect found:', providerSelect);
    }

    // Check if elements exist
    if (!exchangeType || !providerSelect) return;

    // CCXT Exchanges Data
    let ccxtExchanges = {};
    let exchangesLoaded = false;
    let isLoading = false;

    // Helper to safely update Select2 or native select
    function updateSelectValue(element, value) {
        if (window.jQuery && $(element).hasClass('select2-hidden-accessible')) {
            $(element).val(value).trigger('change');
        } else {
            element.value = value;
            const event = new Event('change', { bubbles: true });
            element.dispatchEvent(event);
        }
    }

    function loadCcxtExchanges() {
        if (exchangesLoaded || isLoading) {
            // If already loaded, ensure dropdowns are populated
            if (exchangesLoaded && Object.keys(ccxtExchanges).length > 0) {
                populateCryptoProviders();
            } else if (!exchangesLoaded) {
                // If not loaded yet but loading, wait a bit
                return new Promise((resolve) => {
                    setTimeout(() => {
                        if (exchangesLoaded) {
                            populateCryptoProviders();
                        } else {
                            populateDefaultCryptoProviders();
                        }
                        resolve();
                    }, 100);
                });
            }
            return Promise.resolve();
        }
        
        isLoading = true;
        
        // Always populate defaults first so user can see options immediately
        populateDefaultCryptoProviders();
        
        // Create a timeout promise to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

        return fetch('{{ route("user.exchange-connections.ccxt-exchanges") }}', { 
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(response => {
                 clearTimeout(timeoutId);
                 
                 if (!response.ok) {
                     // If 401/403, might be auth issue, but still use defaults
                     if (response.status === 401 || response.status === 403) {
                         console.warn('Authentication issue loading exchanges, using defaults');
                         populateDefaultCryptoProviders();
                         return null;
                     }
                     throw new Error('Network response was not ok: ' + response.status);
                 }
                 
                 const contentType = response.headers.get("content-type");
                 if (!contentType || !contentType.includes("application/json")) {
                     console.warn('Received non-JSON response, using defaults');
                     populateDefaultCryptoProviders();
                     return null;
                 }
                 
                 return response.json();
            })
            .then(data => {
                if (data && data.success && data.exchanges && Object.keys(data.exchanges).length > 0) {
                    ccxtExchanges = data.exchanges;
                    exchangesLoaded = true;
                    populateCryptoProviders();
                    console.log('Loaded ' + Object.keys(ccxtExchanges).length + ' exchanges from API');
                } else {
                    console.warn('Failed to load exchanges from API, using defaults:', data?.message || 'Unknown error');
                    populateDefaultCryptoProviders();
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.warn('Error loading exchanges from API, using defaults:', error.message);
                if (error.name === 'AbortError') {
                    console.warn('Request timed out after 10 seconds');
                }
                // Ensure defaults are populated even on error
                populateDefaultCryptoProviders();
            })
            .finally(() => {
                isLoading = false;
            });
    }

    function populateCryptoProviders() {
        if (!cryptoProviders) return;
        cryptoProviders.innerHTML = '';
        
        const popular = [];
        const others = [];
        
        Object.values(ccxtExchanges).forEach(exchange => {
            if (exchange.popular) popular.push(exchange);
            else others.push(exchange);
        });
        
        popular.forEach(exchange => {
            const option = document.createElement('option');
            option.value = exchange.id;
            option.textContent = exchange.name;
            cryptoProviders.appendChild(option);
        });
        
        if (popular.length > 0 && others.length > 0) {
            const separator = document.createElement('option');
            separator.disabled = true;
            separator.textContent = '──────────';
            cryptoProviders.appendChild(separator);
        }
        
        others.forEach(exchange => {
            const option = document.createElement('option');
            option.value = exchange.id;
            option.textContent = exchange.name;
            cryptoProviders.appendChild(option);
        });
    }

    function populateDefaultCryptoProviders() {
        if (!cryptoProviders) return;
        const defaults = [
            {id: 'binance', name: 'Binance', popular: true},
            {id: 'coinbase', name: 'Coinbase', popular: true},
            {id: 'kraken', name: 'Kraken', popular: true},
            {id: 'bybit', name: 'Bybit', popular: true},
            {id: 'kucoin', name: 'KuCoin', popular: true},
            {id: 'okx', name: 'OKX', popular: true}
        ];
        
        cryptoProviders.innerHTML = '';
        defaults.forEach(exchange => {
            const option = document.createElement('option');
            option.value = exchange.id;
            option.textContent = exchange.name;
            cryptoProviders.appendChild(option);
        });
    }

    function updateFormBasedOnExchangeType() {
        const type = exchangeType.value;
        const providerForReset = providerSelect.value;
        
        // 1. Reset provider selection to default/empty if switching types
        // simple approach: clear value.
        updateSelectValue(providerSelect, '');

        // 2. Toggle Optgroups
        if (type === 'CRYPTO_EXCHANGE') {
            if (forexProviders) forexProviders.style.display = 'none';
            if (cryptoProviders) cryptoProviders.style.display = ''; // default block/inline
            
            if (!exchangesLoaded) {
                loadCcxtExchanges();
            }
        } else if (type === 'FX_BROKER') {
            if (forexProviders) forexProviders.style.display = '';
            if (cryptoProviders) cryptoProviders.style.display = 'none';
        } else {
            if (forexProviders) forexProviders.style.display = '';
            if (cryptoProviders) cryptoProviders.style.display = '';
        }

        updateFormBasedOnProvider();
    }

    function updateFormBasedOnProvider() {
        // Exposed to window for inline calls
        if (!window.updateFormBasedOnProvider) {
            window.updateFormBasedOnProvider = updateFormBasedOnProvider;
        }

        try {
            const provider = providerSelect.value;
            const type = exchangeType.value;
            
            // Elements
            const metaapiInfo = document.getElementById('metaapiInfo');
            const metaapiAddCard = document.getElementById('metaapiAddAccountCard');
            const metaapiExistingCard = document.getElementById('metaapiExistingCard');
            
            // Helper to set display
            const setDisplay = (elements, show) => {
                const display = show ? 'block' : 'none';
                if (Array.isArray(elements)) {
                    elements.forEach(el => { if(el) el.style.display = display; });
                } else if (elements) {
                    elements.style.display = display;
                }
            };

            // 1. If no provider or type, hide complex sections
            if (!provider) {
                setDisplay([metaapiInfo, metaapiAddCard, metaapiExistingCard, credentialsCard], false);
                return;
            }

            const isMetaApi = provider === 'metaapi';
            const isCrypto = type === 'CRYPTO_EXCHANGE';
            
            // 2. Explicit MetaApi Handling
            if (isMetaApi) {
                // SHOW MetaApi sections
                // Default: Show Info and Existing Card. Hide Add Card.
                setDisplay(metaapiInfo, true);
                setDisplay(metaapiExistingCard, true);
                setDisplay(metaapiAddCard, false); // Collapsed by default
                
                // HIDE credentials
                setDisplay(credentialsCard, false);
                
                providerHint.textContent = '{{ __('MetaApi.cloud - Add MT account or use existing') }}';
            } else {
                // HIDE MetaApi sections
                setDisplay([metaapiInfo, metaapiAddCard, metaapiExistingCard], false);
                
                // SHOW credentials for everything else
                setDisplay(credentialsCard, true);
                
                if (isCrypto) {
                    setDisplay(apiKeyField, true);
                    setDisplay(apiSecretField, true);
                    
                    let needsPassphrase = false;
                    // Check passphrase requirement
                    if (ccxtExchanges[provider]) {
                        needsPassphrase = ccxtExchanges[provider].needs_passphrase;
                    } else {
                         needsPassphrase = ['okx', 'kucoin', 'coinbasepro', 'coinbase'].includes(provider);
                    }

                    setDisplay(apiPassphraseField, true);
                    if (document.getElementById('apiPassphraseInput')) {
                        document.getElementById('apiPassphraseInput').style.display = 'block';
                    }

                    const passphraseOptional = document.getElementById('apiPassphraseOptional');
                    if (passphraseOptional) {
                        if (needsPassphrase) {
                            passphraseOptional.innerHTML = '<span class="text-danger">*</span>';
                        } else {
                            passphraseOptional.innerHTML = '<span class="text-muted">({{ __('Optional') }})</span>';
                        }
                    }
                    
                    // Show test connection button for crypto exchanges
                    const testSection = document.getElementById('testConnectionSection');
                    if (testSection) testSection.style.display = 'block';
                    
                    providerHint.textContent = '{{ __('Enter your API credentials from the exchange') }}';
                } else {
                    // MTApi or other Forex providers
                    setDisplay(apiKeyField, true);
                    setDisplay(apiSecretField, true);
                    setDisplay(apiPassphraseField, false); // No passphrase for basic forex API
                    
                    // Show test connection button for forex providers too
                    const testSection = document.getElementById('testConnectionSection');
                    if (testSection) testSection.style.display = 'block';
                    
                    providerHint.textContent = '{{ __('Enter your mtapi.io API credentials') }}';
                }
            }
        } catch(e) {
            console.error('Error in updateFormBasedOnProvider:', e);
        }
    }

    // Event Listeners - Robust Binding for Select2
    function bindEvents() {
        // Native Listeners
        if (exchangeType) exchangeType.addEventListener('change', updateFormBasedOnExchangeType);
        if (providerSelect) providerSelect.addEventListener('change', updateFormBasedOnProvider);

        // jQuery / Select2 Listeners
        if (window.jQuery) {
            console.log('Attaching jQuery/Select2 listeners');
            
            // Standard jQuery change
            $(exchangeType).on('change', updateFormBasedOnExchangeType);
            $(providerSelect).on('change', updateFormBasedOnProvider);
            
            // Select2 specific events (force trigger)
            $(exchangeType).on('select2:select', updateFormBasedOnExchangeType);
            $(providerSelect).on('select2:select', updateFormBasedOnProvider);
            
            // Fallback: Catch any interaction
            $(providerSelect).on('select2:close', updateFormBasedOnProvider);
        }
    }

    bindEvents();
    
    // Initial run - ensure dropdowns are visible and populated
    updateFormBasedOnExchangeType();
    
    // Load exchanges immediately (will populate defaults first, then try API)
    loadCcxtExchanges();
    
    // Also ensure dropdowns are visible (in case CSS is hiding them)
    if (forexProviders) forexProviders.style.display = '';
    if (cryptoProviders) cryptoProviders.style.display = '';
    
    // Validation
    const form = document.querySelector('form');
    if (form) {
        console.log('Form found, attaching submit listener');
        form.addEventListener('submit', function(e) {
            console.log('Form submit triggered');
            try {
                const provider = providerSelect ? providerSelect.value : '';
                const isMetaApi = provider === 'metaapi';
                const type = exchangeType ? exchangeType.value : '';

                console.log('Validation Params:', { provider, isMetaApi, type });

                // Validate MetaApi
                if (isMetaApi) {
                    const accountIdField = document.getElementById('metaapiAccountId');
                    const addCard = document.getElementById('metaapiAddAccountCard');
                    
                    if (accountIdField) {
                        const accountId = accountIdField.value;
                        
                        // Client-side check for MetaApi
                        if (!accountId || accountId.trim() === '') {
                            e.preventDefault();
                            if (addCard && addCard.style.display !== 'none') {
                                alert('{{ __('Please click "Add Account to MetaApi" to generate your Account ID before creating the connection.') }}');
                            } else {
                                alert('{{ __('Please enter a MetaAPI Account ID.') }}');
                            }
                            return false;
                        }
                    }
                }

                // AJAX Submission (replaces default submit)
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ __('Creating...') }}';

                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', // Important for Laravel to detect AJAX
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    credentials: 'same-origin'
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    let data;
                    
                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        // If we get HTML (redirect), it means session expired or non-AJAX response
                        const text = await response.text();
                        console.warn('Received non-JSON response:', text.substring(0, 200));
                        
                        if (response.status === 401 || response.status === 403) {
                            data = {
                                success: false,
                                message: '{{ __('Your session has expired. Please log in again.') }}',
                                redirect: '{{ route('user.login') }}'
                            };
                        } else {
                            data = {
                                success: false,
                                message: '{{ __('An unexpected error occurred. Please try again.') }}'
                            };
                        }
                    }
                    
                    return { status: response.status, body: data };
                })
                .then(({ status, body }) => {
                    if (status >= 200 && status < 300 && body.success) {
                        // Success
                        if (typeof toastr !== 'undefined') {
                            toastr.success(body.message || '{{ __('Connection created successfully') }}');
                        } else {
                            alert(body.message || '{{ __('Connection created successfully') }}');
                        }
                        setTimeout(() => {
                            // Use replace() to prevent redirect loops
                            window.location.replace(body.redirect || '{{ route('user.trading.configuration.index', ['tab' => 'data-connections']) }}');
                        }, 1000);
                    } else {
                        // Error
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        
                        if (body.redirect) {
                            // Redirect for auth errors - use replace() to prevent loops
                            window.location.replace(body.redirect);
                            return;
                        }
                        
                        if (status === 422 && body.errors) {
                            // Validation errors
                            const errorMessages = [];
                            Object.keys(body.errors).forEach(key => {
                                const messages = Array.isArray(body.errors[key]) ? body.errors[key] : [body.errors[key]];
                                messages.forEach(msg => errorMessages.push(msg));
                            });
                            
                            if (typeof toastr !== 'undefined') {
                                errorMessages.forEach(msg => toastr.error(msg));
                            } else {
                                alert(errorMessages.join('\n'));
                            }
                        } else {
                            const errorMsg = body.message || '{{ __('An error occurred') }}';
                            if (typeof toastr !== 'undefined') {
                                toastr.error(errorMsg);
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Submission error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    
                    const errorMsg = '{{ __('Network error. Please check your connection and try again.') }}';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                });

            } catch(err) {
                console.error('Submit handler error:', err);
                submitBtn.disabled = false;
            }
        });
    }

    // Add account to MetaApi
    const addToMetaApiBtn = document.getElementById('addToMetaApiBtn');
    if (addToMetaApiBtn) {
        addToMetaApiBtn.addEventListener('click', function() {
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
                resultDiv.innerHTML = '<div class="alert alert-danger">' + '{{ __('Please fill in all required fields') }}' + '</div>';
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="las la-spinner la-spin"></i> {{ __('Adding to MetaApi...') }}';
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="las la-spinner la-spin"></i> {{ __('Adding account to MetaApi...') }}' + '</div>';
            
            @php
                try {
                    $userRoute = route("user.exchange-connections.add-metaapi-account", [], false);
                } catch (\Exception $e) {
                    $userRoute = '';
                }
                try {
                    $adminRoute = route("admin.trading-management.config.exchange-connections.add-metaapi-account", [], false);
                } catch (\Exception $e) {
                    $adminRoute = '';
                }
            @endphp
            const route = '{{ $userRoute }}';
            const fallbackRoute = '{{ $adminRoute }}';
            const useRoute = (route && !route.includes('{') && route !== '') ? route : fallbackRoute;
            
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
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="las la-check-circle"></i> ' + data.message + '<br><small>{{ __('MetaApi Account ID:') }} ' + data.metaapi_account_id + '</small></div>';
                    document.getElementById('metaapiAccountId').value = data.metaapi_account_id;
                    document.getElementById('metaapiAddAccountCard').style.display = 'none';
                    document.getElementById('metaapiExistingCard').style.display = 'block';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="las la-times-circle"></i> ' + data.message + '</div>';
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="las la-cloud-upload-alt"></i> {{ __('Add Account to MetaApi') }}';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="las la-times-circle"></i> {{ __('Error:') }} ' + error.message + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="las la-cloud-upload-alt"></i> {{ __('Add Account to MetaApi') }}';
            });
        });
    }

    // Check MetaApi account status
    const checkMetaApiStatusBtn = document.getElementById('checkMetaApiStatusBtn');
    if (checkMetaApiStatusBtn) {
        checkMetaApiStatusBtn.addEventListener('click', function() {
            const accountId = document.getElementById('metaapiAccountId').value;
            const resultDiv = document.getElementById('metaapiStatusResult');
            
            if (!accountId) {
                resultDiv.innerHTML = '<div class="alert alert-warning">{{ __('Please enter MetaApi Account ID') }}' + '</div>';
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="las la-spinner la-spin"></i> {{ __('Checking...') }}';
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="las la-spinner la-spin"></i> {{ __('Checking account status...') }}' + '</div>';
            
            @php
                try {
                    $statusUserRoute = route("user.exchange-connections.metaapi-account-status", [], false);
                } catch (\Exception $e) {
                    $statusUserRoute = '';
                }
                try {
                    $statusAdminRoute = route("admin.trading-management.config.exchange-connections.metaapi-account-status", [], false);
                } catch (\Exception $e) {
                    $statusAdminRoute = '';
                }
            @endphp
            const route = '{{ $statusUserRoute }}';
            const fallbackRoute = '{{ $statusAdminRoute }}';
            const useRoute = (route && !route.includes('{') && route !== '') ? route : fallbackRoute;
            
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
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="las la-check-circle"></i> {{ __('Account Status:') }} <strong>' + data.status + '</strong></div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="las la-times-circle"></i> ' + (data.message || '{{ __('Failed to check status') }}') + '</div>';
                }
                this.disabled = false;
                this.innerHTML = '<i class="las la-check-circle"></i> {{ __('Check Account Status') }}';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="las la-times-circle"></i> {{ __('Error:') }} ' + error.message + '</div>';
                this.disabled = false;
                this.innerHTML = '<i class="las la-check-circle"></i> {{ __('Check Account Status') }}';
            });
        });
    }
    // Toggle MetaApi Cards
    const showAddAccountLink = document.getElementById('showAddAccountLink');
    const showExistingAccountLink = document.getElementById('showExistingAccountLink');
    const metaapiAddAccountCard = document.getElementById('metaapiAddAccountCard');
    const metaapiExistingCard = document.getElementById('metaapiExistingCard');

    if (showAddAccountLink) {
        showAddAccountLink.addEventListener('click', function(e) {
            e.preventDefault();
            metaapiExistingCard.style.display = 'none';
            metaapiAddAccountCard.style.display = 'block';
        });
    }

    if (showExistingAccountLink) {
        showExistingAccountLink.addEventListener('click', function(e) {
            e.preventDefault();
            metaapiAddAccountCard.style.display = 'none';
            metaapiExistingCard.style.display = 'block';
        });
    }
});
</script>
@endpush
@endsection

