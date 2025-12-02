@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Edit AI Configuration') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-configuration.update', $config->id) }}" method="POST">
                        @csrf
                        @method('POST')

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Provider') }}</label>
                                    <input type="text" class="form-control" value="{{ ucfirst($config->provider) }}" disabled>
                                    <small class="text-muted">{{ __('Provider cannot be changed after creation') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Configuration Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $config->name) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ __('API Key') }}</label>
                            <input type="password" name="api_key" id="api_key" class="form-control" placeholder="Leave empty to keep existing key">
                            <small class="text-muted">{{ __('Only enter if you want to change the API key') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('API URL') }}</label>
                                    <input type="url" name="api_url" class="form-control" value="{{ old('api_url', $config->api_url) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Model') }}</label>
                                    <div class="input-group">
                                        @if($config->provider === 'gemini')
                                            <select name="model" id="model" class="form-control">
                                                <option value="">{{ __('Select a model') }}</option>
                                                <option value="{{ old('model', $config->model) }}" selected>{{ old('model', $config->model) }}</option>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-info" id="refresh-models-btn">
                                                    <i class="fa fa-refresh"></i> {{ __('Refresh') }}
                                                </button>
                                            </div>
                                        @else
                                            <input type="text" name="model" id="model" class="form-control" value="{{ old('model', $config->model) }}">
                                        @endif
                                    </div>
                                    <small class="text-muted" id="model-help-text">
                                        @if($config->provider === 'gemini')
                                            {{ __('Click Refresh to fetch available models') }}
                                        @else
                                            {{ __('Enter model name manually') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('Priority') }}</label>
                                    <input type="number" name="priority" class="form-control" value="{{ old('priority', $config->priority) }}" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('Timeout (seconds)') }}</label>
                                    <input type="number" name="timeout" class="form-control" value="{{ old('timeout', $config->timeout) }}" min="5" max="300">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('Temperature') }}</label>
                                    <input type="number" name="temperature" step="0.1" class="form-control" value="{{ old('temperature', $config->temperature) }}" min="0" max="2">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('Max Tokens') }}</label>
                                    <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', $config->max_tokens) }}" min="50" max="4000">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="enabled" class="form-check-input" id="enabled" value="1" {{ old('enabled', $config->enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enabled">
                                    {{ __('Enable this configuration') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('Update Configuration') }}
                            </button>
                            <a href="{{ route('admin.ai-configuration.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                            <button type="button" class="btn btn-info test-connection-btn" data-id="{{ $config->id }}">
                                <i class="fa fa-plug"></i> {{ __('Test Connection') }}
                            </button>
                            <button type="button" class="btn btn-success test-parse-btn" data-id="{{ $config->id }}">
                                <i class="fa fa-code"></i> {{ __('Test Parse') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.querySelector('.test-connection-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const originalText = this.innerHTML;
                    
                    this.disabled = true;
                    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Testing...') }}';
                    
                    fetch(`{{ url('admin/ai-configuration') }}/${id}/test-connection`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('{{ __('Connection successful!') }}');
                        } else {
                            alert('{{ __('Connection failed:') }} ' + (data.message || '{{ __('Unknown error') }}'));
                        }
                    })
                    .catch(error => {
                        alert('{{ __('Error testing connection:') }} ' + error.message);
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
                });
            }

            // Test Parse button
            const testParseBtn = document.querySelector('.test-parse-btn');
            if (testParseBtn) {
                testParseBtn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const originalText = this.innerHTML;
                    
                    // Prompt for test message
                    const testMessage = prompt('{{ __('Enter a trading signal message to test parsing:') }}\n\nExample:\nBUY EUR/USD\nEntry: 1.0850\nSL: 1.0800\nTP: 1.0950');
                    
                    if (!testMessage || !testMessage.trim()) {
                        return;
                    }
                    
                    this.disabled = true;
                    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Parsing...') }}';
                    
                    fetch(`{{ url('admin/ai-configuration') }}/${id}/test-parse`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            message: testMessage
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.parsed) {
                            const parsed = data.parsed;
                            const result = `{{ __('Parsing successful!') }}\n\n` +
                                `{{ __('Currency Pair:') }} ${parsed.currency_pair || 'N/A'}\n` +
                                `{{ __('Direction:') }} ${parsed.direction || 'N/A'}\n` +
                                `{{ __('Open Price:') }} ${parsed.open_price || 0}\n` +
                                `{{ __('Stop Loss:') }} ${parsed.sl || 'N/A'}\n` +
                                `{{ __('Take Profit:') }} ${parsed.tp || 'N/A'}\n` +
                                `{{ __('Timeframe:') }} ${parsed.timeframe || 'N/A'}\n` +
                                `{{ __('Title:') }} ${parsed.title || 'N/A'}\n` +
                                `{{ __('Description:') }} ${(parsed.description || '').substring(0, 100)}...`;
                            alert(result);
                        } else {
                            alert('{{ __('Parsing failed:') }} ' + (data.message || '{{ __('Unknown error') }}'));
                        }
                    })
                    .catch(error => {
                        alert('{{ __('Error testing parse:') }} ' + error.message);
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
                });
            }

            // Model fetching for Gemini
            @if($config->provider === 'gemini')
            const refreshBtn = document.getElementById('refresh-models-btn');
            const modelSelect = document.getElementById('model');
            const apiKeyInput = document.getElementById('api_key');
            const helpText = document.getElementById('model-help-text');
            const provider = '{{ $config->provider }}';
            const currentModel = '{{ old('model', $config->model) }}';

            function fetchModels() {
                const apiKey = apiKeyInput.value.trim();
                
                // Show loading state
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Loading...') }}';
                helpText.textContent = '{{ __('Fetching models...') }}';
                modelSelect.disabled = true;
                
                // If API key is provided in form, use it; otherwise use stored key from config
                let fetchUrl, fetchBody;
                if (apiKey) {
                    // Use provided API key
                    fetchUrl = '{{ route("admin.ai-configuration.fetch-models") }}';
                    fetchBody = JSON.stringify({
                        provider: provider,
                        api_key: apiKey
                    });
                } else {
                    // Use stored API key from configuration
                    fetchUrl = '{{ route("admin.ai-configuration.fetch-models-from-config", ["id" => $config->id]) }}';
                    fetchBody = null;
                }
                
                fetch(fetchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fetchBody
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.models && data.models.length > 0) {
                        // Clear existing options except the first one
                        modelSelect.innerHTML = '<option value="">{{ __('Select a model') }}</option>';
                        
                        // Add fetched models
                        data.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.name;
                            option.textContent = model.displayName || model.name;
                            if (model.description) {
                                option.title = model.description;
                            }
                            // Select current model if it matches
                            if (model.name === currentModel) {
                                option.selected = true;
                            }
                            modelSelect.appendChild(option);
                        });
                        
                        // Ensure current model is selected if it exists
                        if (currentModel && !modelSelect.value) {
                            modelSelect.value = currentModel;
                        }
                        
                        helpText.textContent = `{{ __('Found') }} ${data.models.length} {{ __('available models') }}`;
                    } else {
                        helpText.textContent = data.message || '{{ __('No models found or failed to fetch') }}';
                        if (data.message) {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching models:', error);
                    helpText.textContent = '{{ __('Error fetching models. Please try again.') }}';
                    alert('{{ __('Error fetching models:') }} ' + error.message);
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fa fa-refresh"></i> {{ __('Refresh') }}';
                    modelSelect.disabled = false;
                });
            }

            if (refreshBtn) {
                refreshBtn.addEventListener('click', fetchModels);
            }

            // Auto-fetch when API key is entered (with debounce)
            let fetchTimeout;
            if (apiKeyInput) {
                apiKeyInput.addEventListener('input', function() {
                    clearTimeout(fetchTimeout);
                    if (this.value.trim().length > 10) {
                        // Auto-fetch after 1 second of no typing
                        fetchTimeout = setTimeout(() => {
                            fetchModels();
                        }, 1000);
                    }
                });
            }
            
            // Auto-fetch models on page load if API key exists in config
            // Only fetch if model select is empty or has default value
            if (modelSelect && (!modelSelect.value || modelSelect.options.length <= 1)) {
                setTimeout(() => {
                    fetchModels();
                }, 500);
            }
            @endif
        });
    </script>
@endsection

