@extends(Config::theme() . 'layout.auth')

@section('content')
<style>
/* Override dark theme for form pages - match admin panel styling */
.user-form-page {
    background-color: #F1F5F9 !important;
    min-height: calc(100vh - 75px);
    padding: 30px 0;
    margin-top: 75px; /* Account for fixed header */
}

.user-form-page .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 30px;
}

@media (max-width: 575px) {
    .user-form-page .container-fluid {
        padding: 0 15px;
    }
}

.user-form-page .sp_site_card {
    background: #ffffff !important;
    border: 1px solid #e0e0e0 !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    margin-bottom: 0;
    overflow: visible;
}

.user-form-page .sp_site_card .card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e0e0e0 !important;
    padding: 20px 25px !important;
}

.user-form-page .sp_site_card .card-header h4 {
    color: #333 !important;
    font-weight: 600;
    font-size: 1.375rem;
    margin: 0;
}

.user-form-page .sp_site_card .card-body {
    background: #ffffff !important;
    color: #333 !important;
    padding: 25px !important;
}

.user-form-page .form-group {
    margin-bottom: 1.5rem;
}

.user-form-page .form-group label {
    color: #333 !important;
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
    font-size: 0.875rem;
}

.user-form-page .form-control,
.user-form-page .form-select {
    background: #ffffff !important;
    border: 1px solid #ddd !important;
    color: #333 !important;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.user-form-page .form-control:focus,
.user-form-page .form-select:focus {
    border-color: #5169df !important;
    box-shadow: 0 0 0 0.2rem rgba(81, 105, 223, 0.25) !important;
    outline: 0;
    background: #ffffff !important;
    color: #333 !important;
}

.user-form-page .form-control::placeholder {
    color: #999 !important;
}

.user-form-page .form-control option {
    background: #ffffff !important;
    color: #333 !important;
}

.user-form-page .card {
    background: #ffffff !important;
    border: 1px solid #e0e0e0 !important;
    border-radius: 8px !important;
    margin-bottom: 1.5rem;
}

.user-form-page .card .card-header {
    background: #f8f9fa !important;
    border-bottom: 1px solid #e0e0e0 !important;
    color: #333 !important;
    padding: 15px 20px;
}

.user-form-page .card .card-header h6 {
    color: #333 !important;
    font-weight: 600;
    margin: 0;
    font-size: 1rem;
}

.user-form-page .card .card-body {
    background: #ffffff !important;
    color: #333 !important;
    padding: 20px;
}

.user-form-page .btn-secondary {
    background: #6c757d !important;
    border-color: #6c757d !important;
    color: #fff !important;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 4px;
}

.user-form-page .btn-secondary:hover {
    background: #5a6268 !important;
    border-color: #545b62 !important;
    color: #fff !important;
}

.user-form-page .sp_theme_btn {
    background: #5169df !important;
    border-color: #5169df !important;
    color: #fff !important;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 4px;
    font-weight: 500;
}

.user-form-page .sp_theme_btn:hover {
    background: #4058d0 !important;
    border-color: #3d52c8 !important;
    color: #fff !important;
}

.user-form-page .text-muted {
    color: #6c757d !important;
    font-size: 0.8125rem;
}

.user-form-page .text-danger {
    color: #dc3545 !important;
}

.user-form-page small.text-muted {
    color: #6c757d !important;
    font-size: 0.75rem;
    display: block;
    margin-top: 0.25rem;
}

/* Override any dark theme styles */
.user-form-page * {
    box-sizing: border-box;
}

.user-form-page select.form-control,
.user-form-page select.form-select {
    /* Remove default browser arrow */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    /* Add single custom arrow */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 16px 12px !important;
    padding-right: 2.5rem !important;
}

/* Remove any duplicate arrows from Bootstrap or other CSS */
.user-form-page select.form-control::after,
.user-form-page select.form-select::after {
    display: none !important;
}

.user-form-page select.form-control::-ms-expand,
.user-form-page select.form-select::-ms-expand {
    display: none !important;
}

.user-form-page optgroup {
    background: #ffffff !important;
    color: #333 !important;
}

.user-form-page optgroup option {
    background: #ffffff !important;
    color: #333 !important;
    padding: 0.5rem;
}
</style>

<div class="user-form-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="card-header p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ __('Create Data Connection') }}</h4>
                            <a href="{{ route('user.trading.configuration.index', ['tab' => 'data-connections']) }}" class="btn btn-secondary">
                                <i class="las la-arrow-left"></i> {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                <form action="{{ route('user.exchange-connections.store') }}" method="POST">
                    @csrf

                    <!-- Connection Name -->
                    <div class="form-group mb-3">
                        <label>{{ __('Connection Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('My Data Connection') }}" value="{{ old('name', '') }}" autocomplete="new-password">
                    </div>

                    <!-- Connection Type -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Connection Type') }} <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connectionType" class="form-control" required>
                                    <option value="">{{ __('Select Type') }}</option>
                                    <option value="DATA_ONLY">{{ __('Data Only') }}</option>
                                    <option value="EXECUTION_ONLY">{{ __('Execution Only') }}</option>
                                    <option value="BOTH">{{ __('Both Data & Execution') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Exchange Type') }} <span class="text-danger">*</span></label>
                                <select name="exchange_type" id="exchangeType" class="form-control" required>
                                    <option value="">{{ __('Select Exchange Type') }}</option>
                                    <option value="CRYPTO_EXCHANGE">{{ __('Crypto Exchange (CCXT)') }}</option>
                                    <option value="FX_BROKER">{{ __('Forex Broker (MT4/MT5)') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Provider/Exchange -->
                    <div class="form-group mb-3">
                        <label>{{ __('Provider/Exchange') }} <span class="text-danger">*</span></label>
                        <select name="exchange_name" id="providerSelect" class="form-control" required>
                            <option value="">{{ __('Select Provider') }}</option>
                            <optgroup label="{{ __('Forex Brokers') }}" id="forexProviders" style="display:none;">
                                <option value="metaapi">MetaApi.cloud (MT4/MT5)</option>
                                <option value="mtapi">mtapi.io (MT4/MT5) REST</option>
                                <option value="mtapi_grpc">mtapi.io (MT4/MT5) gRPC</option>
                            </optgroup>
                            <optgroup label="{{ __('Crypto Exchanges (CCXT)') }}" id="cryptoProviders" style="display:none;">
                                <option value="binance">Binance</option>
                                <option value="coinbase">Coinbase</option>
                                <option value="coinbasepro">Coinbase Pro</option>
                                <option value="kraken">Kraken</option>
                                <option value="bybit">Bybit</option>
                                <option value="kucoin">KuCoin</option>
                                <option value="okx">OKX</option>
                            </optgroup>
                        </select>
                        <small class="text-muted" id="providerHint"></small>
                    </div>

                    <!-- API Credentials -->
                    <div class="card mb-3" id="credentialsCard" style="display:none;">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('API Credentials') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group" id="apiKeyField">
                                <label>{{ __('API Key') }} <span class="text-danger" id="apiKeyRequired">*</span></label>
                                <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control">
                            </div>
                            <div class="form-group" id="apiSecretField">
                                <label>{{ __('API Secret') }} <span class="text-danger" id="apiSecretRequired">*</span></label>
                                <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control">
                            </div>
                            <div class="form-group" id="apiPassphraseField" style="display:none;">
                                <label>{{ __('API Passphrase') }} <span class="text-muted" id="apiPassphraseOptional">({{ __('Optional') }})</span></label>
                                <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control">
                                <small class="text-muted">{{ __('Required for some exchanges (OKX, KuCoin)') }}</small>
                            </div>
                            
                            <!-- MetaApi Account ID (for MetaApi provider) -->
                            <div class="form-group" id="metaapiAccountIdField" style="display:none;">
                                <label>{{ __('MetaApi Account ID') }} <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control" placeholder="{{ __('Enter MetaApi account ID') }}">
                                <small class="text-muted">{{ __('Get this from your MetaApi dashboard after adding the account') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Preset -->
                    @if(isset($presets) && $presets->count() > 0)
                    <div class="form-group mb-3">
                        <label>{{ __('Trading Preset') }}</label>
                        <select name="preset_id" class="form-control">
                            <option value="">{{ __('None') }}</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset->id }}">{{ $preset->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('Risk management preset for trade execution') }}</small>
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
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    function updateFormBasedOnExchangeType() {
        const type = exchangeType.value;
        providerSelect.value = '';
        updateFormBasedOnProvider();
        
        if (type === 'CRYPTO_EXCHANGE') {
            forexProviders.style.display = 'none';
            cryptoProviders.style.display = '';
            credentialsCard.style.display = 'block';
            apiKeyField.style.display = 'block';
            apiSecretField.style.display = 'block';
            apiPassphraseField.style.display = 'block';
            metaapiAccountIdField.style.display = 'none';
        } else if (type === 'FX_BROKER') {
            forexProviders.style.display = '';
            cryptoProviders.style.display = 'none';
            credentialsCard.style.display = 'none';
        } else {
            forexProviders.style.display = 'none';
            cryptoProviders.style.display = 'none';
            credentialsCard.style.display = 'none';
        }
    }

    function updateFormBasedOnProvider() {
        const provider = providerSelect.value;
        const exchangeType = document.getElementById('exchangeType').value;
        
        if (!provider || !exchangeType) {
            credentialsCard.style.display = 'none';
            return;
        }
        
        const isMetaApi = provider === 'metaapi';
        const isCrypto = exchangeType === 'CRYPTO_EXCHANGE';
        const needsPassphrase = ['okx', 'kucoin', 'coinbasepro'].includes(provider);
        
        if (isMetaApi) {
            credentialsCard.style.display = 'block';
            apiKeyField.style.display = 'none';
            apiSecretField.style.display = 'none';
            apiPassphraseField.style.display = 'none';
            metaapiAccountIdField.style.display = 'block';
            providerHint.textContent = '{{ __('MetaApi.cloud - Enter your MetaApi account ID') }}';
        } else if (isCrypto) {
            credentialsCard.style.display = 'block';
            apiKeyField.style.display = 'block';
            apiSecretField.style.display = 'block';
            apiPassphraseField.style.display = needsPassphrase ? 'block' : 'none';
            metaapiAccountIdField.style.display = 'none';
            if (needsPassphrase) {
                document.getElementById('apiPassphraseOptional').innerHTML = '<span class="text-danger">*</span>';
            }
            providerHint.textContent = '{{ __('Enter your API credentials from the exchange') }}';
        } else {
            // Other forex providers (mtapi)
            credentialsCard.style.display = 'block';
            apiKeyField.style.display = 'block';
            apiSecretField.style.display = 'block';
            apiPassphraseField.style.display = 'none';
            metaapiAccountIdField.style.display = 'none';
            providerHint.textContent = '{{ __('Enter your mtapi.io API credentials') }}';
        }
    }

    exchangeType.addEventListener('change', updateFormBasedOnExchangeType);
    providerSelect.addEventListener('change', updateFormBasedOnProvider);

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const exchangeType = document.getElementById('exchangeType').value;
        const provider = providerSelect.value;
        const isMetaApi = provider === 'metaapi';
        
        if (isMetaApi) {
            const accountId = document.getElementById('metaapiAccountId').value;
            if (!accountId || accountId.trim() === '') {
                e.preventDefault();
                alert('{{ __('Please enter a MetaAPI Account ID.') }}');
                return false;
            }
        } else {
            const apiKey = document.getElementById('apiKeyInput').value;
            const apiSecret = document.getElementById('apiSecretInput').value;
            
            if (!apiKey || !apiSecret) {
                e.preventDefault();
                alert('{{ __('Please fill in all required API credentials.') }}');
                return false;
            }
            
            // Check passphrase if required
            if (['okx', 'kucoin', 'coinbasepro'].includes(provider)) {
                const passphrase = document.getElementById('apiPassphraseInput').value;
                if (!passphrase) {
                    e.preventDefault();
                    alert('{{ __('API Passphrase is required for this exchange.') }}');
                    return false;
                }
            }
        }
        
        return true;
    });
});
</script>
@endpush
@endsection

