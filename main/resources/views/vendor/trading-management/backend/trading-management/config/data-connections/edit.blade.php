@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Edit Data Connection: {{ $connection->name }}</h4>
                <div class="card-header-action">
                    <a href="{{ route('admin.trading-management.config.data-connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.config.data-connections.update', $connection) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Connection Name -->
                    <div class="form-group">
                        <label for="name">Connection Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $connection->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Connection Type -->
                    <div class="form-group">
                        <label for="type">Connection Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('type') is-invalid @enderror" 
                                id="type" name="type" required onchange="updateCredentialFields()">
                            @foreach($supportedTypes as $typeKey => $typeInfo)
                                <option value="{{ $typeKey }}" {{ old('type', $connection->type) === $typeKey ? 'selected' : '' }}>
                                    {{ $typeInfo['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Provider -->
                    <div class="form-group">
                        <label for="provider">Provider <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('provider') is-invalid @enderror" 
                               id="provider" name="provider" value="{{ old('provider', $connection->provider) }}" required>
                        @error('provider')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Credentials Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Credentials</h6>
                        </div>
                        <div class="card-body" id="credentialsFields">
                            @if($connection->type === 'mtapi')
                                <div class="form-group">
                                    <label for="credentials_api_key">API Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="credentials_api_key" 
                                           name="credentials[api_key]" value="{{ old('credentials.api_key', $connection->getCredential('api_key')) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="credentials_account_id">Account ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="credentials_account_id" 
                                           name="credentials[account_id]" value="{{ old('credentials.account_id', $connection->getCredential('account_id')) }}" required>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Settings Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Data Settings</h6>
                        </div>
                        <div class="card-body">
                            <!-- Symbols -->
                            <div class="form-group">
                                <label for="symbols">Symbols to Fetch</label>
                                <textarea class="form-control" id="symbols" name="settings[symbols]" rows="3">{{ old('settings.symbols', implode("\n", $connection->getSymbolsFromSettings())) }}</textarea>
                                <small class="form-text text-muted">One symbol per line</small>
                            </div>

                            <!-- Timeframes -->
                            <div class="form-group">
                                <label>Timeframes</label>
                                <div class="row">
                                    @php $selectedTimeframes = old('settings.timeframes', $connection->getTimeframesFromSettings()); @endphp
                                    @foreach(['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN'] as $tf)
                                        <div class="col-md-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="tf_{{ $tf }}" name="settings[timeframes][]" value="{{ $tf }}"
                                                       {{ in_array($tf, $selectedTimeframes) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="tf_{{ $tf }}">{{ $tf }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Connection
                        </button>
                        <button type="button" class="btn btn-info" onclick="testConnectionBeforeSave()">
                            <i class="fas fa-flask"></i> Test Connection
                        </button>
                        <a href="{{ route('admin.trading-management.config.data-connections.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const supportedTypes = @json($supportedTypes);

function updateCredentialFields() {
    const type = document.getElementById('type').value;
    const credentialsDiv = document.getElementById('credentialsFields');

    if (!type) return;

    const typeInfo = supportedTypes[type];
    
    let html = '';
    typeInfo.credentials.forEach(function(field) {
        const fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `
            <div class="form-group">
                <label for="credentials_${field}">${fieldName} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="credentials_${field}" 
                       name="credentials[${field}]" required>
            </div>
        `;
    });

    credentialsDiv.innerHTML = html;
}

function testConnectionBeforeSave() {
    alert('Please save the connection first, then use the Test button from the connections list.');
}
</script>
@endsection

