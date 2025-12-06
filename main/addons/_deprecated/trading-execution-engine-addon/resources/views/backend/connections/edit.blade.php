@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.execution-connections.update', $connection->id) }}" method="POST" id="connectionForm">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Connection Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $connection->name }}" placeholder="e.g., My Binance Account" required>
                                <small class="form-text text-muted">A friendly name to identify this connection</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="type" id="connectionType" class="form-control" required>
                                    <option value="">-- Select Type --</option>
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}" {{ $connection->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Choose between cryptocurrency exchange or forex broker</small>
                            </div>
                            
                            <div class="form-group" id="exchangeGroup">
                                <label>Exchange/Broker <span class="text-danger">*</span></label>
                                <select name="exchange_name" id="exchangeName" class="form-control" required>
                                    <option value="">-- Select Exchange/Broker --</option>
                                </select>
                                <small class="form-text text-muted" id="exchangeHelp">Select your exchange or broker</small>
                            </div>
                            
                            <div class="form-group" id="credentialsGroup">
                                <label>Credentials (JSON) <span class="text-danger">*</span></label>
                                <textarea name="credentials" id="credentials" class="form-control" rows="8" required>@php
                                    $creds = $connection->credentials ?? [];
                                    echo is_array($creds) ? json_encode($creds, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (is_string($creds) ? $creds : '{}');
                                @endphp</textarea>
                                <small class="form-text text-muted">
                                    <strong>Example:</strong>
                                    <pre id="credentialsExample" class="bg-light p-2 mt-2 rounded" style="font-size: 12px;"></pre>
                                </small>
                                <div id="testResult" class="mt-2"></div>
                                <button type="button" id="testConnectionBtn" class="btn btn-info mt-2">
                                    <i class="fa fa-flask"></i> Test Connection
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Connection</button>
                                <a href="{{ route('admin.execution-connections.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        const exchanges = {
            crypto: @json($cryptoExchanges ?? []),
            fx: @json($forexBrokers ?? [])
        };

        function updateExchangeOptions(type) {
            const exchangeSelect = document.getElementById('exchangeName');
            const exchangeGroup = document.getElementById('exchangeGroup');
            const exchangeHelp = document.getElementById('exchangeHelp');
            
            exchangeSelect.innerHTML = '<option value="">-- Select Exchange/Broker --</option>';
            
            if (type && exchanges[type]) {
                exchangeGroup.style.display = 'block';
                
                Object.keys(exchanges[type]).forEach(key => {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = exchanges[type][key].name;
                    if ('{{ $connection->exchange_name }}' === key) {
                        option.selected = true;
                    }
                    exchangeSelect.appendChild(option);
                });
                
                exchangeHelp.textContent = type === 'crypto' 
                    ? 'Select your cryptocurrency exchange (100+ exchanges supported via ccxt)'
                    : 'Select your forex broker platform';
            } else {
                exchangeGroup.style.display = 'none';
            }
        }

        function updateCredentialsExample() {
            const type = document.getElementById('connectionType').value;
            const exchangeKey = document.getElementById('exchangeName').value;
            const credentialsExample = document.getElementById('credentialsExample');
            const credentialsTextarea = document.getElementById('credentials');
            
            if (type && exchangeKey && exchanges[type] && exchanges[type][exchangeKey]) {
                const example = exchanges[type][exchangeKey].example;
                const exampleJson = JSON.stringify(example, null, 2);
                credentialsExample.textContent = exampleJson;
                if (!credentialsTextarea.value || credentialsTextarea.value.trim() === '{}') {
                    credentialsTextarea.placeholder = exampleJson;
                }
            } else {
                credentialsExample.textContent = '';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentType = '{{ $connection->type }}';
            if (currentType) {
                updateExchangeOptions(currentType);
                setTimeout(() => {
                    updateCredentialsExample();
                }, 100);
            }
        });

        document.getElementById('connectionType').addEventListener('change', function() {
            updateExchangeOptions(this.value);
            updateCredentialsExample();
        });

        document.getElementById('exchangeName').addEventListener('change', function() {
            updateCredentialsExample();
        });

        // Test connection button handler
        document.getElementById('testConnectionBtn').addEventListener('click', function() {
            const type = document.getElementById('connectionType').value;
            const exchangeName = document.getElementById('exchangeName').value;
            const credentials = document.getElementById('credentials').value;
            const testResult = document.getElementById('testResult');
            const testBtn = this;
            const originalText = testBtn.innerHTML;
            
            if (!type || !exchangeName || !credentials) {
                testResult.innerHTML = '<div class="alert alert-warning">Please fill in all required fields before testing.</div>';
                return;
            }

            // Validate JSON
            try {
                JSON.parse(credentials);
            } catch (e) {
                testResult.innerHTML = '<div class="alert alert-danger">Invalid JSON format: ' + e.message + '</div>';
                return;
            }

            // Disable button and show loading
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';

            // Test connection via AJAX
            fetch('{{ route("admin.execution-connections.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    exchange_name: exchangeName,
                    credentials: credentials
                })
            })
            .then(response => response.json())
            .then(data => {
                testBtn.disabled = false;
                testBtn.innerHTML = originalText;
                
                if (data.success) {
                    testResult.innerHTML = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> <strong>Connection Successful!</strong><br>' + (data.message || 'Connection test passed.') + '</div>';
                } else {
                    testResult.innerHTML = '<div class="alert alert-danger"><i class="fa fa-times-circle"></i> <strong>Connection Failed!</strong><br>' + (data.message || 'Unknown error occurred.') + '</div>';
                }
            })
            .catch(error => {
                testBtn.disabled = false;
                testBtn.innerHTML = originalText;
                testResult.innerHTML = '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <strong>Error:</strong> ' + error.message + '</div>';
            });
        });
    </script>
    @endpush
@endsection

