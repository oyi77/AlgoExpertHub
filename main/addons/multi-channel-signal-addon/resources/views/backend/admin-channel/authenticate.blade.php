@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channels.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-primary mb-4">
                        <h5 class="alert-heading">
                            <i class="fa fa-user"></i> {{ __('User Authentication Required') }}
                        </h5>
                        <p class="mb-0">
                            <strong>{{ __('MadelineProto uses USER authentication, NOT bot token!') }}</strong><br>
                            {{ __('You are logging in as a regular Telegram user (using your phone number), not as a bot. This allows access to private channels you are a member of.') }}
                        </p>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>{{ __('Do NOT enter a bot token here!') }}</strong> {{ __('Bot tokens are only for regular Telegram bot channels. This form is for user account authentication.') }}
                    </div>
                    
                    @if (session('info'))
                        <div class="alert alert-info">
                            {{ session('info') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($step === 'phone')
                        <form action="{{ route('admin.channels.authenticate', ['id' => $channel->id, 'step' => 'phone']) }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i>
                                        {{ __('Enter the phone number associated with your Telegram account. Use international format (e.g., +15551234567).') }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone_number" class="form-control" 
                                        value="{{ old('phone_number', $channel->config['phone_number'] ?? '') }}" 
                                        placeholder="+15551234567" required>
                                    <small class="text-muted">{{ __('International format required') }}</small>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <button type="submit" class="btn btn-primary">{{ __('Send Verification Code') }}</button>
                                    <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    @elseif ($step === 'code')
                        <form action="{{ route('admin.channels.authenticate', ['id' => $channel->id, 'step' => 'code']) }}" method="post">
                            @csrf
                            <input type="hidden" name="phone_code_hash" value="{{ session('admin_phone_code_hash') }}">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i>
                                        {{ __('Verification code has been sent to your Telegram account. Please enter the code below.') }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Verification Code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" 
                                        value="{{ old('code') }}" 
                                        placeholder="12345" required autofocus>
                                    <small class="text-muted">{{ __('Enter the 5-digit code sent to your Telegram account') }}</small>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <button type="submit" class="btn btn-primary">{{ __('Verify Code') }}</button>
                                    <a href="{{ route('admin.channels.authenticate', ['id' => $channel->id, 'step' => 'phone']) }}" class="btn btn-secondary">{{ __('Resend Code') }}</a>
                                    <a href="{{ route('admin.channels.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

