@extends('backend.auth.auth')


@section('element')
    <div class="auth-header mb-4">
        @if(Config::config()->logo && Config::config()->logo !== 'placeholder.png')
            <div class="auth-logo mb-3">
                <img src="{{ Config::fetchImage('logo', Config::config()->logo, true) }}" 
                     alt="{{ Config::config()->appname }}" 
                     class="auth-logo-img"
                     style="max-height: 35px !important; max-width: 120px !important; width: auto !important; height: auto !important; object-fit: contain !important;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <h2 class="auth-logo-text" style="display: none;">{{ Config::config()->appname }}</h2>
            </div>
        @else
            <div class="auth-logo mb-3">
                <h2 class="auth-logo-text">{{ Config::config()->appname }}</h2>
            </div>
        @endif
        <h4 class="auth-title">{{ __('Welcome Back') }}</h4>
        <p class="auth-subtitle">{{ __('Sign in to continue to your admin panel') }}</p>
    </div>

    <form action="" method="POST" id="adminLoginForm" class="auth-form-content">
        @csrf
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="las la-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="las la-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="form-group mb-4">
            <label class="form-label">
                <i class="las la-user me-2"></i>{{ __('Email Or Username') }}
            </label>
            <div class="input-group input-group-lg">
                <span class="input-group-text">
                    <i class="las la-envelope"></i>
                </span>
                <input type="text" 
                       name="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       placeholder="{{ __('Enter your email or username') }}"
                       value="{{ old('email') }}"
                       autocomplete="username"
                       required
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group mb-4">
            <label class="form-label">
                <i class="las la-lock me-2"></i>{{ __('Password') }}
            </label>
            <div class="input-group input-group-lg">
                <span class="input-group-text">
                    <i class="las la-key"></i>
                </span>
                <input type="password" 
                       name="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       placeholder="{{ __('Enter your password') }}"
                       autocomplete="current-password"
                       required
                       id="passwordInput">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                    <i class="las la-eye" id="togglePasswordIcon"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if (Config::config()->allow_recaptcha)
            <div class="form-group mb-4">
                <script src="https://www.google.com/recaptcha/api.js"></script>
                <div class="g-recaptcha" data-sitekey="{{ Config::config()->recaptcha_key }}" data-callback="verifyCaptcha">
                </div>
                <div id="g-recaptcha-error" class="text-danger mt-2"></div>
            </div>
        @endif

        <div class="form-options d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                <label class="form-check-label" for="rememberMe">
                    {{ __('Remember me') }}
                </label>
            </div>
            <a href="{{ route('admin.password.reset') }}" class="forgot-password-link">
                {{ __('Forgot Password?') }}
            </a>
        </div>

        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit-btn" id="submitBtn">
                <span class="btn-text">{{ __('Sign me in') }}</span>
                <span class="btn-spinner d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ __('Signing in...') }}
                </span>
            </button>
        </div>
    </form>
@endsection

@push('script')
    <script>
        "use strict";

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    if (type === 'password') {
                        togglePasswordIcon.classList.remove('la-eye-slash');
                        togglePasswordIcon.classList.add('la-eye');
                    } else {
                        togglePasswordIcon.classList.remove('la-eye');
                        togglePasswordIcon.classList.add('la-eye-slash');
                    }
                });
            }

            // Form submission with loading state
            const loginForm = document.getElementById('adminLoginForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function(e) {
                    // Check reCAPTCHA if enabled
                    @if (Config::config()->allow_recaptcha)
                    var response = grecaptcha.getResponse();
                    if (response.length == 0) {
                        e.preventDefault();
                        document.getElementById('g-recaptcha-error').innerHTML =
                            "<span class='text-danger'><i class='las la-exclamation-circle me-1'></i>{{__('Captcha field is required.')}}</span>";
                        return false;
                    }
                    @endif
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.querySelector('.btn-text').classList.add('d-none');
                    submitBtn.querySelector('.btn-spinner').classList.remove('d-none');
                });
            }
        });

        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML =
                    "<span class='text-danger'><i class='las la-exclamation-circle me-1'></i>{{__('Captcha field is required.')}}</span>";
                return false;
            }
            return true;
        }

        function verifyCaptcha() {
            document.getElementById('g-recaptcha-error').innerHTML = '';
        }
    </script>
@endpush
