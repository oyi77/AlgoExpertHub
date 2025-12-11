@extends(Config::theme() . 'auth.master')

@section('content')
    <form class="sp_account_form mt-4" action="" method="POST" novalidate>
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
            <button type="submit" class="btn btn-primary w-100 btn-lg focus-ring">{{ __('Login') }}</button>
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

@push('script')
    <script>
        "use strict";

        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML =
                    "<span class='sp_text_danger'>{{ __('Captcha field is required.') }}</span>";
                return false;
            }
            return true;
        }

        function verifyCaptcha() {
            document.getElementById('g-recaptcha-error').innerHTML = '';
        }
    </script>
@endpush
