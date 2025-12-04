@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Create Exchange Connection</h4>
                    <a href="{{ route('admin.exchange-connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.exchange-connections.store') }}" method="POST">
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
                                <input type="text" name="provider" class="form-control" placeholder="binance, kraken, mt4" required>
                            </div>
                        </div>
                    </div>

                    <!-- Credentials -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">API Credentials</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>API Key <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[api_key]" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>API Secret <span class="text-danger">*</span></label>
                                <input type="password" name="credentials[api_secret]" class="form-control" required>
                            </div>
                            <div class="form-group">
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
                        <a href="{{ route('admin.exchange-connections.index') }}" class="btn btn-secondary">Cancel</a>
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
</script>
@endsection

