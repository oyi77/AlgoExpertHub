@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ __('Edit Data Connection') }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __('Edit Data Connection') }}</h4>
            <a href="{{ route('user.exchange-connections.show', $connection->id) }}" class="btn btn-sm btn-secondary">
                <i class="las la-arrow-left"></i> {{ __('Back to Connection') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('user.exchange-connections.update', $connection->id) }}" method="POST" id="editConnectionForm">
            @csrf
            @method('PUT')

            <!-- Connection Name -->
            <div class="form-group mb-3">
                <label for="name">{{ __('Connection Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required placeholder="{{ __('My Data Connection') }}" value="{{ old('name', $connection->name) }}" autocomplete="off">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <small class="text-muted">{{ __('A friendly name to identify this connection') }}</small>
                @enderror
            </div>

            <!-- Connection Type -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="connectionType">{{ __('Connection Type') }} <span class="text-danger">*</span></label>
                        <select name="connection_type" id="connectionType" class="form-control @error('connection_type') is-invalid @enderror" required>
                            <option value="DATA_ONLY" {{ old('connection_type', $connection->connection_type) === 'DATA_ONLY' ? 'selected' : '' }}>{{ __('Data Only') }}</option>
                            <option value="EXECUTION_ONLY" {{ old('connection_type', $connection->connection_type) === 'EXECUTION_ONLY' ? 'selected' : '' }}>{{ __('Execution Only') }}</option>
                            <option value="BOTH" {{ old('connection_type', $connection->connection_type) === 'BOTH' ? 'selected' : '' }}>{{ __('Both Data & Execution') }}</option>
                        </select>
                        @error('connection_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('Exchange Type') }}</label>
                        <input type="text" class="form-control bg-light" value="{{ $connection->exchange_type === 'CRYPTO_EXCHANGE' ? __('Crypto Exchange') : __('Forex Broker') }}" disabled>
                        <small class="text-muted">{{ __('Exchange type cannot be changed after creation.') }}</small>
                    </div>
                </div>
            </div>

            <!-- Provider/Exchange (Locked) -->
            <div class="form-group mb-3">
                <label>{{ __('Provider/Exchange') }}</label>
                <input type="text" class="form-control bg-light" value="{{ strtoupper($connection->exchange_name ?? $connection->provider) }}" disabled>
                <small class="text-muted">{{ __('Provider cannot be changed. Create a new connection if you need a different provider.') }}</small>
            </div>

            <!-- API Credentials -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="las la-key"></i> {{ __('Update API Credentials') }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small">
                        <i class="las la-info-circle"></i> {{ __('Leave these fields empty to keep current sensitive credentials.') }}
                    </div>

                    @if($connection->exchange_name === 'metaapi' || $connection->provider === 'metaapi')
                        <div class="form-group">
                            <label for="metaapiAccountId">{{ __('MetaApi Account ID') }}</label>
                            <input type="text" name="credentials[account_id]" id="metaapiAccountId" class="form-control" 
                                   placeholder="{{ __('Enter new Account ID or leave empty') }}" 
                                   value="{{ old('credentials.account_id', $connection->credentials['account_id'] ?? '') }}">
                            <small class="text-muted">{{ __('Current ID is shown above. Update if you changed your account in MetaApi.') }}</small>
                        </div>
                    @else
                        <div class="form-group mb-3">
                            <label for="apiKeyInput">{{ __('API Key') }}</label>
                            <input type="text" name="credentials[api_key]" id="apiKeyInput" class="form-control" 
                                   placeholder="{{ __('Leave empty to keep existing') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label for="apiSecretInput">{{ __('API Secret') }}</label>
                            <input type="password" name="credentials[api_secret]" id="apiSecretInput" class="form-control" 
                                   placeholder="{{ __('Leave empty to keep existing') }}">
                        </div>
                        @php
                            $exchangeName = $connection->exchange_name ?? $connection->provider;
                        @endphp
                        @if(in_array($exchangeName, ['okx', 'kucoin', 'coinbasepro', 'coinbase']))
                        <div class="form-group">
                            <label for="apiPassphraseInput">{{ __('API Passphrase') }}</label>
                            <input type="password" name="credentials[api_passphrase]" id="apiPassphraseInput" class="form-control" 
                                   placeholder="{{ __('Leave empty to keep existing') }}">
                        </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Trading Preset -->
            <div class="form-group mb-3">
                <label for="preset_id">{{ __('Trading Preset') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                <select name="preset_id" id="preset_id" class="form-control">
                    <option value="">{{ __('None') }}</option>
                    @foreach($presets as $preset)
                    <option value="{{ $preset->id }}" {{ old('preset_id', $connection->preset_id) == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('Risk management preset for trade execution.') }}</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn sp_theme_btn" id="submitBtn">
                    <i class="las la-save"></i> {{ __('Update Connection') }}
                </button>
                <a href="{{ route('user.exchange-connections.show', $connection->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#editConnectionForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = $('#submitBtn');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> {{ __("Updating...") }}');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    btn.prop('disabled', false).html(originalText);
                    alert(response.message || '{{ __("Update failed") }}');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                let msg = '{{ __("An error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });
});
</script>
@endpush
@endsection
