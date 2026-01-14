@extends(Config::themeView('auth.master'))

@section('content')
    <form class="sp_account_form mt-4" id="loginForm" action="{{ route('user.login.post') }}" method="POST" novalidate>
        @csrf
        
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('User email') }} <span class="text-danger" aria-label="required">*</span></label>
            <div class="sp_input_icon_field">
                <input 
                    type="email" 
                    class="form-control form-control-modern @error('email') is-invalid @enderror focus-ring" 
                    name="email" 
                    id="email"
                    value="{{ old('email') }}"
                    placeholder="{{ __('Enter Your Email') }}"
                    required
                    aria-describedby="@error('email') email-error @enderror"
                    @error('email') aria-invalid="true" @enderror
                >
                <i class="las la-envelope" aria-hidden="true"></i>
            </div>
            @error('email')
                <div id="email-error" class="text-danger small mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }} <span class="text-danger" aria-label="required">*</span></label>
            <div class="sp_input_icon_field">
                <input 
                    type="password" 
                    class="form-control form-control-modern @error('password') is-invalid @enderror focus-ring" 
                    name="password" 
                    id="password"
                    placeholder="{{ __('Enter Password') }}"
                    required
                    aria-describedby="@error('password') password-error @enderror"
                    @error('password') aria-invalid="true" @enderror
                >
                <i class="las la-lock" aria-hidden="true"></i>
            </div>
            @error('password')
                <div id="password-error" class="text-danger small mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>

        @if (Config::config()->allow_recaptcha == 1)
            <div class="col-md-12 my-3">
                <script src="https://www.google.com/recaptcha/api.js" defer></script>
                <div class="g-recaptcha" data-sitekey="{{ Config::config()->recaptcha_key }}" data-callback="verifyCaptcha" aria-label="reCAPTCHA verification">
                </div>
                <div id="g-recaptcha-error" role="alert" aria-live="polite"></div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input focus-ring" type="checkbox" name="remember" value="1" id="remember">
                <label class="form-check-label" for="remember">
                    {{ __('Remember Me') }}
                </label>
            </div>
            <a href="{{ route('user.forgot.password') }}" class="text-primary focus-ring">{{ __('Forgot Password?') }}</a>
        </div>

        <div class="mb-4">
            <button type="submit" id="loginBtn" class="btn btn-primary w-100 btn-lg focus-ring">
                <span class="btn-text">{{ __('Login') }}</span>
                <span class="btn-spinner d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ __('Logging In...') }}
                </span>
            </button>
        </div>

        @if ((Config::config()->allow_facebook ?? false) || (Config::config()->allow_google ?? false))
            <div class="or-text text-center mb-3">
                <span>{{ __('Or Login With') }}</span>
            </div>

            <div class="other-login-btns">
                @if (Config::config()->allow_facebook)
                    <a class="other-login-btn btn btn-outline-primary w-100 mb-2" href="{{ route('user.facebook.login') }}" aria-label="{{ __('Login with Facebook') }}">
                        <i class="fab fa-facebook-f me-2"></i>
                        <span>{{ __('Login with Facebook') }}</span>
                    </a>
                @endif

                @if (Config::config()->allow_google)
                    <a class="other-login-btn btn btn-outline-danger w-100" href="{{ route('user.google.login') }}" aria-label="{{ __('Login with Google') }}">
                        <i class="fab fa-google me-2"></i>
                        <span>{{ __('Login with Google') }}</span>
                    </a>
                @endif
            </div>
        @endif

        <p class="mt-4 text-center"> {{ __('Haven\'t an account') }} ? <a href="{{ route('user.register') }}" class="text-primary fw-semibold">{{ __('Sign Up') }}</a></p>
    </form>
@endsection

@push('scripts')
    <script>
        "use strict";

        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const btnSpinner = loginBtn.querySelector('.btn-spinner');

            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Client-side validation for reCAPTCHA if present
                if (typeof grecaptcha !== 'undefined' && document.querySelector('.g-recaptcha')) {
                    var response = grecaptcha.getResponse();
                    if (response.length == 0) {
                        notify().error().message("{{ __('Captcha field is required.') }}").send();
                        return;
                    }
                }

                // Show loading state
                loginBtn.disabled = true;
                btnText.classList.add('d-none');
                btnSpinner.classList.remove('d-none');

                // Prepare form data
                const formData = new FormData(loginForm);

                // Send AJAX request
                fetch(loginForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.type === 'success') {
                        notify().success().message(data.message).send();
                        // Redirect after short delay
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                    } else {
                        // Show error
                        notify().error().message(data.message || 'Login failed').send();
                        resetButton();
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notify().error().message('Something went wrong. Please try again.').send();
                    resetButton();
                });
            });

            function resetButton() {
                loginBtn.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');
            }
        });

        function verifyCaptcha() {
            // Callback for reCAPTCHA
        }
    </script>
@endpush
