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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('is_paper_trading');
        if (checkbox) {
            // Set initial state
            toggleDemoModeAlert();
            
            // Listen for changes
            checkbox.addEventListener('change', toggleDemoModeAlert);
        }

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
