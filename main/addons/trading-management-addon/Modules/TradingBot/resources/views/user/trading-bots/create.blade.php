@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-sm btn-info me-2">
                    <i class="fa fa-store"></i> {{ __('Browse Templates') }}
                </a>
                <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Browse Templates Alert --}}
        <div class="alert alert-info mb-4">
            <i class="fa fa-lightbulb"></i> 
            <strong>Tip:</strong> Start from a prebuilt template with MA100, MA10, and PSAR indicators! 
            <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="alert-link">Browse Templates →</a>
        </div>

        {{-- Demo Mode Badge (conditional) --}}
        @php
            $isPaperTradingDefault = old('is_paper_trading', isset($bot) && $bot ? $bot->is_paper_trading : true);
        @endphp
        <div class="alert alert-warning mb-4" id="demo-mode-alert" style="display: {{ $isPaperTradingDefault ? 'block' : 'none' }};">
            <i class="fa fa-exclamation-triangle"></i> <strong>Demo Mode:</strong> This bot will run in paper trading mode. No real money will be used.
        </div>

        <form action="{{ route('user.trading-management.trading-bots.store') }}" method="POST" id="bot-form">
            @csrf

            @include('trading-management::user.trading-bots.partials.form')

        </form>
    </div>
</div>

@push('scripts')
<script>
    // Exchange-specific requirements mapping
    const exchangeRequirements = {
        'okx': { passphrase: true, help: 'OKX requires API Key, Secret, and Passphrase. Create API key with trading permissions.' },
        'kucoin': { passphrase: true, help: 'KuCoin requires API Key, Secret, and Passphrase. Enable trading permissions.' },
        'coinbasepro': { passphrase: true, help: 'Coinbase Pro requires API Key, Secret, and Passphrase. Grant trading permissions.' },
        'coinbase': { passphrase: true, help: 'Coinbase requires API Key, Secret, and Passphrase.' },
        'binance': { passphrase: false, help: 'Binance requires API Key and Secret. Enable spot trading permissions.' },
        'bybit': { passphrase: false, help: 'Bybit requires API Key and Secret. Enable trading permissions.' },
    };
    
    // Crypto exchanges list
    const cryptoExchanges = ['binance', 'bybit', 'okx', 'kucoin', 'coinbasepro', 'coinbase', 'kraken', 'bitfinex', 'huobi', 'gate', 'mexc'];
    const fxBrokers = ['metaapi', 'mtapi', 'mtapi_grpc'];
    
    // Toggle demo mode alert based on checkbox state
    function toggleDemoModeAlert() {
        const checkbox = document.getElementById('is_paper_trading');
        const alert = document.getElementById('demo-mode-alert');
        
        if (checkbox && alert) {
            if (checkbox.checked) {
                alert.style.display = 'block';
            } else {
                alert.style.display = 'none';
            }
        }
    }
    
    // Update connection health badge and requirements
    function updateConnectionHealth(connectionSelect) {
        const healthInfo = document.getElementById('connection-health-info');
        const healthBadge = document.getElementById('connection-health-badge');
        const requirementsDiv = document.getElementById('connection-requirements');
        const requirementsText = document.getElementById('connection-requirements-text');
        
        if (!connectionSelect || !healthInfo) return;
        
        const selectedOption = connectionSelect.options[connectionSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            healthInfo.style.display = 'none';
            return;
        }
        
        const healthStatus = selectedOption.getAttribute('data-health');
        const exchangeName = selectedOption.getAttribute('data-exchange-name') || '';
        const connectionType = selectedOption.getAttribute('data-connection-type') || '';
        
        // Show health info
        healthInfo.style.display = 'block';
        
        // Update badge
        let badgeHtml = '';
        if (healthStatus === 'healthy') {
            badgeHtml = '<span class="badge bg-success"><i class="fa fa-check-circle"></i> Connection Active & Ready</span>';
        } else if (healthStatus === 'error') {
            badgeHtml = '<span class="badge bg-danger"><i class="fa fa-exclamation-circle"></i> Connection Has Errors</span>';
        } else if (healthStatus === 'testing') {
            badgeHtml = '<span class="badge bg-warning"><i class="fa fa-spinner fa-spin"></i> Connection Testing</span>';
        } else {
            badgeHtml = '<span class="badge bg-secondary"><i class="fa fa-pause-circle"></i> Connection Inactive</span>';
        }
        healthBadge.innerHTML = badgeHtml;
        
        // Show requirements for crypto exchanges
        if (connectionType === 'CRYPTO_EXCHANGE' && exchangeName && exchangeRequirements[exchangeName]) {
            const req = exchangeRequirements[exchangeName];
            requirementsDiv.style.display = 'block';
            requirementsText.innerHTML = req.help;
        } else {
            requirementsDiv.style.display = 'none';
        }
    }
    
    // Initialize inline connection creation modal
    function initInlineConnectionModal() {
        const modal = document.getElementById('createConnectionModal');
        const exchangeTypeSelect = document.getElementById('inline_exchange_type');
        const exchangeNameGroup = document.getElementById('inline_exchange_name_group');
        const exchangeNameSelect = document.getElementById('inline_exchange_name');
        const connectionTypeGroup = document.getElementById('inline_connection_type_group');
        const credentialsGroup = document.getElementById('inline_credentials_group');
        const passphraseField = document.getElementById('inline_api_passphrase_field');
        const saveBtn = document.getElementById('save-inline-connection');
        
        if (!modal || !exchangeTypeSelect) return;
        
        // Load exchanges when type is selected
        exchangeTypeSelect.addEventListener('change', function() {
            const exchangeType = this.value;
            
            if (!exchangeType) {
                exchangeNameGroup.style.display = 'none';
                connectionTypeGroup.style.display = 'none';
                credentialsGroup.style.display = 'none';
                return;
            }
            
            // Show exchange name select
            exchangeNameGroup.style.display = 'block';
            exchangeNameSelect.innerHTML = '<option value="">Loading...</option>';
            
            // Populate exchanges based on type
            let exchanges = [];
            if (exchangeType === 'CRYPTO_EXCHANGE') {
                exchanges = cryptoExchanges.map(name => ({ value: name, label: name.toUpperCase() }));
                document.getElementById('inline_exchange_hint').textContent = 'Select your cryptocurrency exchange';
            } else {
                exchanges = fxBrokers.map(name => ({ value: name, label: name === 'metaapi' ? 'MetaAPI (MT4/MT5)' : name.toUpperCase() }));
                document.getElementById('inline_exchange_hint').textContent = 'Select your forex broker provider';
            }
            
            exchangeNameSelect.innerHTML = '<option value="">-- Select Exchange --</option>' +
                exchanges.map(ex => `<option value="${ex.value}">${ex.label}</option>`).join('');
            
            connectionTypeGroup.style.display = 'block';
        });
        
        // Show credential fields when exchange is selected
        exchangeNameSelect.addEventListener('change', function() {
            const exchangeName = this.value.toLowerCase();
            
            if (!exchangeName) {
                credentialsGroup.style.display = 'none';
                return;
            }
            
            credentialsGroup.style.display = 'block';
            
            // Show/hide passphrase field based on exchange
            const requiresPassphrase = exchangeRequirements[exchangeName]?.passphrase || false;
            if (passphraseField) {
                passphraseField.style.display = requiresPassphrase ? 'block' : 'none';
                const passphraseInput = document.getElementById('inline_api_passphrase');
                if (passphraseInput) {
                    passphraseInput.required = requiresPassphrase;
                }
            }
        });
        
        // Save inline connection
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                const form = document.getElementById('inline-connection-form');
                const formData = new FormData(form);
                const errorsDiv = document.getElementById('inline_connection_errors');
                
                // Show loading
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Creating...';
                errorsDiv.style.display = 'none';
                
                fetch('{{ url("user/exchange-connections") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and reset form
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) modalInstance.hide();
                        form.reset();
                        exchangeNameGroup.style.display = 'none';
                        connectionTypeGroup.style.display = 'none';
                        credentialsGroup.style.display = 'none';
                        
                        // Reload page to refresh connections list
                        window.location.reload();
                    } else {
                        // Show errors
                        let errorHtml = '<strong>Please fix the following errors:</strong><ul class="mb-0 mt-2">';
                        if (data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                data.errors[key].forEach(msg => {
                                    errorHtml += `<li>${msg}</li>`;
                                });
                            });
                        } else if (data.message) {
                            errorHtml += `<li>${data.message}</li>`;
                        }
                        errorHtml += '</ul>';
                        errorsDiv.innerHTML = errorHtml;
                        errorsDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error creating connection:', error);
                    errorsDiv.innerHTML = '<strong>Error:</strong> Failed to create connection. Please try again.';
                    errorsDiv.style.display = 'block';
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fa fa-save"></i> Create & Test Connection';
                });
            });
        }
        
        // Reset modal on close
        modal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('inline-connection-form');
            if (form) {
                form.reset();
                document.getElementById('inline_exchange_name_group').style.display = 'none';
                document.getElementById('inline_connection_type_group').style.display = 'none';
                document.getElementById('inline_credentials_group').style.display = 'none';
                document.getElementById('inline_connection_errors').style.display = 'none';
            }
        });
    }
    

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('is_paper_trading');
        if (checkbox) {
            // Set initial state
            toggleDemoModeAlert();
            
            // Listen for changes
            checkbox.addEventListener('change', toggleDemoModeAlert);
        }
        
        // Initialize connection health display
        const connectionSelect = document.getElementById('exchange_connection_id');
        if (connectionSelect) {
            // Update on change
            connectionSelect.addEventListener('change', function() {
                updateConnectionHealth(this);
            });
            
            // Initial update
            updateConnectionHealth(connectionSelect);
        }
        
        // Initialize inline connection modal
        initInlineConnectionModal();

        // AJAX Form Submission
        const form = document.getElementById('bot-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Client-side validation
                const connectionId = document.getElementById('exchange_connection_id')?.value;
                const presetId = document.getElementById('trading_preset_id')?.value;

                if (!connectionId || !presetId) {
                    e.preventDefault();
                    const errorMsg = '{{ __('Please select an exchange connection and trading preset.') }}';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    return false;
                }

                // AJAX Submission
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Creating...') }}';
                }

                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}'
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
                            toastr.success(body.message || '{{ __('Trading bot created successfully!') }}');
                        } else {
                            alert(body.message || '{{ __('Trading bot created successfully!') }}');
                        }
                        setTimeout(() => {
                            // Use replace() to prevent redirect loops
                            window.location.replace(body.redirect || '{{ route('user.trading-management.trading-bots.index') }}');
                        }, 1000);
                    } else {
                        // Error
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                        
                        if (body.redirect) {
                            // Redirect for auth errors
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
                    console.error('Form submission error:', error);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                    
                    const errorMsg = '{{ __('An unexpected error occurred. Please try again.') }}';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                });
            });
        }
    });
</script>
@endpush
@endsection
