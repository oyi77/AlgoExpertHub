@php
    use App\Helpers\Helper\Helper;
@endphp

@extends(\App\Helpers\Helper\Helper::theme().'auth.master')

@section('title', 'Register - ' . (Config::config()->appname ?? 'AlgoExpertHub'))

@section('content')
    <h2 class="tv-auth-title">Create Account</h2>
    <p class="tv-auth-subtitle">Start your trading journey today</p>
    
    <form action="{{ route('user.register.post') }}" method="POST">
        @csrf
        
        <!-- Username -->
        <div class="tv-form-group">
            <label for="username" class="tv-form-label">Username</label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   class="tv-form-input @error('username') error @enderror" 
                   placeholder="Choose a username"
                   value="{{ old('username') }}"
                   required>
            @error('username')
                <span class="tv-alert tv-alert-danger" style="margin-top: 0.5rem; display: block;">{{ $message }}</span>
            @enderror
        </div>
        
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
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="tv-form-input @error('password') error @enderror" 
                   placeholder="Create a password"
                   required>
            @error('password')
                <span class="tv-alert tv-alert-danger" style="margin-top: 0.5rem; display: block;">{{ $message }}</span>
            @enderror
        </div>
        
        <!-- Confirm Password -->
        <div class="tv-form-group">
            <label for="password_confirmation" class="tv-form-label">Confirm Password</label>
            <input type="password" 
                   id="password_confirmation" 
                   name="password_confirmation" 
                   class="tv-form-input" 
                   placeholder="Confirm your password"
                   required>
        </div>
        
        <!-- Terms & Conditions -->
        <div style="margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: start; gap: 0.5rem; font-size: 0.875rem; cursor: pointer;">
                <input type="checkbox" name="terms" style="width: 16px; height: 16px; margin-top: 0.25rem;" required>
                <span>I agree to the <a href="#" style="color: var(--tv-primary);">Terms of Service</a> and <a href="#" style="color: var(--tv-primary);">Privacy Policy</a></span>
            </label>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="tv-btn tv-btn-primary tv-btn-lg" style="width: 100%;">
            Create Account
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
        Already have an account? <a href="{{ route('user.login') }}" style="color: var(--tv-primary); font-weight: 600;">Sign In</a>
    </div>
@endsection

