@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-7 col-md-9">
            <div class="sp_site_card">
                <div class="card-header border-0">
                    <h4 class="mb-0">{{ __('Authenticate Telegram Account') }}</h4>
                    <p class="text-muted mb-0">{{ __('Complete the MTProto login to start receiving channel messages.') }}</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-1">{{ $channel->name }}</h6>
                        <span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', $channel->type) }}</span>
                    </div>

                    @if ($step === 'phone')
                        <form method="post" action="{{ route('user.channels.authenticate.post', $channel->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Phone Number') }}</label>
                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $channel->config['phone_number'] ?? '') }}" placeholder="+15551234567" required>
                                <small class="text-muted">{{ __('Use the phone number linked to the Telegram account. Two-factor must be disabled.') }}</small>
                            </div>
                            <button type="submit" class="btn sp_theme_btn w-100">
                                <i class="las la-sms me-1"></i> {{ __('Send Verification Code') }}
                            </button>
                        </form>
                    @elseif ($step === 'code')
                        <form method="post" action="{{ route('user.channels.authenticate.post', $channel->id) }}">
                            @csrf
                            <input type="hidden" name="phone_code_hash" value="{{ session('phone_code_hash') }}">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Verification Code') }}</label>
                                <input type="text" name="code" class="form-control text-center" maxlength="6" placeholder="12345" required>
                                <small class="text-muted">{{ __('Enter the code sent to your Telegram app. Expires in a few minutes.') }}</small>
                            </div>
                            <button type="submit" class="btn sp_theme_btn w-100">
                                <i class="las la-check-circle me-1"></i> {{ __('Verify & Connect') }}
                            </button>
                        </form>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="las la-info-circle me-1"></i>
                            {{ __('If you did not receive a code, go back and resend it or confirm the phone number is correct.') }}
                        </div>
                    @endif
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('user.channels.index') }}" class="btn btn-link px-0">
                        <i class="las la-arrow-left me-1"></i> {{ __('Back to Channels') }}
                    </a>
                    <span class="small text-muted">
                        {{ __('Channel ID: :id', ['id' => $channel->id]) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection

