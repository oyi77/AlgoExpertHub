@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Create Data Connection</h4>
                <div class="card-header-action">
                    <a href="{{ route('admin.trading-management.config.data-connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.config.data-connections.store') }}" method="POST">
                    @csrf

                    <!-- Connection Name -->
                    <div class="form-group">
                        <label for="name">Connection Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Connection Type -->
                    <div class="form-group">
                        <label for="type">Connection Type <span class="text-danger">*</span></label>
                        <select class="form-control @error('type') is-invalid @enderror" 
                                id="type" name="type" required onchange="updateCredentialFields()">
                            <option value="">Select Type</option>
                            @foreach($supportedTypes as $typeKey => $typeInfo)
                                <option value="{{ $typeKey }}" {{ old('type') === $typeKey ? 'selected' : '' }}>
                                    {{ $typeInfo['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted" id="typeDescription"></small>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Provider -->
                    <div class="form-group">
                        <label for="provider">Provider <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('provider') is-invalid @enderror" 
                               id="provider" name="provider" value="{{ old('provider') }}" 
                               placeholder="e.g., binance, mt4_account_123" required>
                        <small class="form-text text-muted">Provider identifier</small>
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
                            <p class="text-muted">Select a connection type to see required credentials</p>
                        </div>
                    </div>

                    <!-- Settings Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Data Settings (Optional)</h6>
                        </div>
                        <div class="card-body">
                            <!-- Symbols -->
                            <div class="form-group">
                                <label for="symbols">Symbols to Fetch</label>
                                <textarea class="form-control" id="symbols" name="settings[symbols]" rows="3" 
                                          placeholder="EURUSD&#10;GBPUSD&#10;USDJPY">{{ old('settings.symbols') }}</textarea>
                                <small class="form-text text-muted">One symbol per line</small>
                            </div>

                            <!-- Timeframes -->
                            <div class="form-group">
                                <label for="timeframes">Timeframes</label>
                                <div class="row">
                                    @foreach(['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN'] as $tf)
                                        <div class="col-md-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="tf_{{ $tf }}" name="settings[timeframes][]" value="{{ $tf }}"
                                                       {{ in_array($tf, old('settings.timeframes', ['H1', 'H4', 'D1'])) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="tf_{{ $tf }}">{{ $tf }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">Select timeframes to fetch</small>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Owned -->
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_admin_owned" name="is_admin_owned" value="1"
                                   {{ old('is_admin_owned') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_admin_owned">
                                Admin-Owned (Global connection, shared across platform)
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Connection
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
    const typeDescription = document.getElementById('typeDescription');

    if (!type) {
        credentialsDiv.innerHTML = '<p class="text-muted">Select a connection type to see required credentials</p>';
        typeDescription.textContent = '';
        return;
    }

    const typeInfo = supportedTypes[type];
    typeDescription.textContent = typeInfo.description;

    let html = '';
    typeInfo.credentials.forEach(function(field) {
        const fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `
            <div class="form-group">
                <label for="credentials_${field}">${fieldName} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="credentials_${field}" 
                       name="credentials[${field}]" value="{{ old('credentials.${field}') }}" required>
            </div>
        `;
    });

    credentialsDiv.innerHTML = html;
}

// Trigger on page load if type is selected
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('type').value) {
        updateCredentialFields();
    }
});
</script>
@endsection

