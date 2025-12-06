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
                        <form action="{{ route('admin.execution-connections.store') }}" method="POST" id="connectionForm">
                            @csrf
                            <div class="form-group">
                                <label>Connection Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., My Binance Account" required>
                                <small class="form-text text-muted">A friendly name to identify this connection</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="type" id="connectionType" class="form-control" required>
                                    <option value="">-- Select Type --</option>
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Choose between cryptocurrency exchange or forex broker</small>
                            </div>
                            
                            <div class="form-group" id="exchangeGroup" style="display: none;">
                                <label>Exchange/Broker <span class="text-danger">*</span></label>
                                <select name="exchange_name" id="exchangeName" class="form-control" required>
                                    <option value="">-- Select Exchange/Broker --</option>
                                </select>
                                <small class="form-text text-muted" id="exchangeHelp">Select your exchange or broker</small>
                            </div>
                            
                            <div class="form-group" id="credentialsGroup" style="display: none;">
                                <label>Credentials (JSON) <span class="text-danger">*</span></label>
                                <textarea name="credentials" id="credentials" class="form-control" rows="8" required placeholder='{"api_key": "", "api_secret": ""}'></textarea>
                                <small class="form-text text-muted">
                                    <strong>Example:</strong>
                                    <pre id="credentialsExample" class="bg-light p-2 mt-2 rounded" style="font-size: 12px;"></pre>
                                </small>
                                <div id="testResult" class="mt-2"></div>
                                <button type="button" id="testConnectionBtn" class="btn btn-info mt-2" style="display: none;">
                                    <i class="fa fa-flask"></i> Test Connection
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Create Connection</button>
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

        document.getElementById('connectionType').addEventListener('change', function() {
            const type = this.value;
            const exchangeSelect = document.getElementById('exchangeName');
            const exchangeGroup = document.getElementById('exchangeGroup');
            const credentialsGroup = document.getElementById('credentialsGroup');
            const exchangeHelp = document.getElementById('exchangeHelp');
            const credentialsExample = document.getElementById('credentialsExample');
            
            // Reset
            exchangeSelect.innerHTML = '<option value="">-- Select Exchange/Broker --</option>';
            document.getElementById('credentials').value = '';
            credentialsExample.textContent = '';
            
            if (type && exchanges[type]) {
                exchangeGroup.style.display = 'block';
                exchangeSelect.required = true;
                
                // Populate exchanges - popular ones first
                const exchangeList = Object.entries(exchanges[type]);
                exchangeList.sort((a, b) => {
                    // Sort popular first
                    if (a[1].popular && !b[1].popular) return -1;
                    if (!a[1].popular && b[1].popular) return 1;
                    // Then alphabetically
                    return a[1].name.localeCompare(b[1].name);
                });
                
                // Populate exchanges - popular ones marked with ⭐
                exchangeList.forEach(([key, data]) => {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = data.name + (data.popular ? ' ⭐' : '');
                    exchangeSelect.appendChild(option);
                });
                
                exchangeHelp.textContent = type === 'crypto' 
                    ? 'Select your cryptocurrency exchange (dynamically loaded from ccxt library - ' + Object.keys(exchanges[type]).length + ' exchanges available)'
                    : 'Select your forex broker platform';
            } else {
                exchangeGroup.style.display = 'none';
                credentialsGroup.style.display = 'none';
                exchangeSelect.required = false;
            }
        });

        document.getElementById('exchangeName').addEventListener('change', function() {
            const type = document.getElementById('connectionType').value;
            const exchangeKey = this.value;
            const credentialsGroup = document.getElementById('credentialsGroup');
            const credentialsExample = document.getElementById('credentialsExample');
            const credentialsTextarea = document.getElementById('credentials');
            const testBtn = document.getElementById('testConnectionBtn');
            const testResult = document.getElementById('testResult');
            
            if (type && exchangeKey && exchanges[type] && exchanges[type][exchangeKey]) {
                credentialsGroup.style.display = 'block';
                testBtn.style.display = 'inline-block';
                const example = exchanges[type][exchangeKey].example;
                const exampleJson = JSON.stringify(example, null, 2);
                credentialsExample.textContent = exampleJson;
                credentialsTextarea.placeholder = exampleJson;
                testResult.innerHTML = '';
            } else {
                credentialsGroup.style.display = 'none';
                testBtn.style.display = 'none';
            }
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
                testResult.html('<div class="alert alert-warning">Please fill in all required fields before testing.</div>');
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

