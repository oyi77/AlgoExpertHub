@php
    use App\Helpers\Helper\Helper;
@endphp

@extends(\App\Helpers\Helper\Helper::theme().'auth.master')

@section('title', 'Login - ' . (Config::config()->appname ?? 'AlgoExpertHub'))

@section('content')
    <h2 class="tv-auth-title">Welcome Back</h2>
    <p class="tv-auth-subtitle">Sign in to your account to continue</p>
    
    <form action="{{ route('user.login.post') }}" method="POST">
        @csrf
        
        <!-- Email -->
        <div class="tv-form-group">
            <label for="email" class="tv-form-label">Email Address</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="tv-form-input @error('email') error @enderror" 
                   placeholder="Enter your email"
                   value="{{ old('email') }}"
                   required>
            @error('email')
                <span class="tv-alert tv-alert-danger" style="margin-top: 0.5rem; display: block;">{{ $message }}</span>
            @enderror
        </div>
        
        <!-- Password -->
        <div class="tv-form-group">
            <label for="password" class="tv-form-label">Password</label>
            <div style="position: relative;">
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="tv-form-input @error('password') error @enderror" 
                       placeholder="Enter your password"
                       required
                       style="padding-right: 40px;">
                <button type="button" 
                        onclick="togglePasswordVisibility()" 
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer; color: var(--tv-text-muted, #888); padding: 0;">
                    <i class="fas fa-eye" id="password-eye-icon"></i>
                </button>
            </div>
            @error('password')
                <span class="tv-alert tv-alert-danger" style="margin-top: 0.5rem; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('password-eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        </script>
        
        <!-- Remember Me & Forgot Password -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; cursor: pointer;">
                <input type="checkbox" name="remember" style="width: 16px; height: 16px;">
                <span>Remember me</span>
            </label>
            <a href="{{ route('user.forgot.password') }}" style="font-size: 0.875rem; color: var(--tv-primary);">
                Forgot Password?
            </a>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="tv-btn tv-btn-primary tv-btn-lg" style="width: 100%;">
            Sign In
            <i class="fas fa-arrow-right"></i>
        </button>
    </form>
    
    <!-- Divider -->
    <div class="tv-auth-divider">
        <span>or</span>
    </div>
    
    <!-- Social Login -->
    @if(Config::config()->google_login == 1 || Config::config()->facebook_login == 1)
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            @if(Config::config()->google_login == 1)
                <a href="{{ route('auth.google') }}" class="tv-btn tv-btn-outline" style="flex: 1;">
                    <i class="fab fa-google"></i>
                    Google
                </a>
            @endif
            @if(Config::config()->facebook_login == 1)
                <a href="{{ route('auth.facebook') }}" class="tv-btn tv-btn-outline" style="flex: 1;">
                    <i class="fab fa-facebook-f"></i>
                    Facebook
                </a>
            @endif
        </div>
    @endif
    
    <!-- Footer -->
    <div class="tv-auth-footer">
        Don't have an account? <a href="{{ route('user.register') }}" style="color: var(--tv-primary); font-weight: 600;">Sign Up</a>
    </div>
@endsection

