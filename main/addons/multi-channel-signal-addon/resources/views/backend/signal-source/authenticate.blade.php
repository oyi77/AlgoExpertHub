@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signal-sources.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $source->name }}</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info">
                            {{ session('info') }}
                        </div>
                    @endif

                    @if ($step === 'phone')
                        <div class="alert alert-info mb-4">
                            <i class="fa fa-info-circle"></i>
                            {{ __('Enter your phone number in international format (e.g., +1234567890) to authenticate your Telegram account.') }}
                        </div>

                        <form action="{{ route('admin.signal-sources.authenticate.post', $source->id) }}" method="post">
                            @csrf
                            <input type="hidden" name="step" value="phone">

                            <div class="form-group">
                                <label>{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" class="form-control" 
                                       value="{{ old('phone_number', $source->config['phone_number'] ?? '') }}" 
                                       placeholder="+1234567890" required>
                                <small class="text-muted">{{ __('Include country code, e.g., +1 for USA, +44 for UK') }}</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-paper-plane"></i> {{ __('Send Verification Code') }}
                                </button>
                                <a href="{{ route('admin.signal-sources.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
                    @elseif ($step === 'code')
                        <div class="alert alert-info mb-4">
                            <i class="fa fa-info-circle"></i>
                            {{ __('Enter the verification code sent to your Telegram app.') }}
                        </div>

                        <form action="{{ route('admin.signal-sources.authenticate.post', $source->id) }}" method="post">
                            @csrf
                            <input type="hidden" name="step" value="code">

                            <div class="form-group">
                                <label>{{ __('Verification Code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" 
                                       placeholder="12345" required autofocus>
                                <small class="text-muted">{{ __('Check your Telegram app for the code') }}</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check"></i> {{ __('Verify Code') }}
                                </button>
                                <a href="{{ route('admin.signal-sources.authenticate', ['id' => $source->id, 'step' => 'phone']) }}" class="btn btn-secondary">
                                    {{ __('Back') }}
                                </a>
                            </div>
                        </form>
                    @elseif ($step === 'password')
                        <div class="alert alert-warning mb-4">
                            <i class="fa fa-shield-alt"></i>
                            {{ __('Two-factor authentication is enabled. Please enter your account password.') }}
                        </div>

                        @if (!empty($password_hint))
                            <div class="alert alert-info mb-4">
                                <i class="fa fa-lightbulb"></i>
                                <strong>{{ __('Password Hint') }}:</strong> {{ $password_hint }}
                            </div>
                        @endif

                        <form action="{{ route('admin.signal-sources.authenticate.post', $source->id) }}" method="post">
                            @csrf
                            <input type="hidden" name="step" value="password">

                            <div class="form-group">
                                <label>{{ __('Password') }} <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="{{ __('Enter your 2FA password') }}" required autofocus>
                                <small class="text-muted">{{ __('This is your Telegram account password (not your phone PIN)') }}</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-key"></i> {{ __('Authenticate') }}
                                </button>
                                <a href="{{ route('admin.signal-sources.authenticate', ['id' => $source->id, 'step' => 'code']) }}" class="btn btn-secondary">
                                    {{ __('Back') }}
                                </a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

