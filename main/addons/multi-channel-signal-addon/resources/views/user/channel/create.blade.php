@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <a href="{{ route('user.channels.index') }}" class="btn btn-link p-0">
                    <i class="las la-arrow-left me-1"></i> {{ __('Back to Channels') }}
                </a>
                <h4 class="mb-0">{{ __('Add :type Channel', ['type' => ucfirst(str_replace('_', ' ', $type))]) }}</h4>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="sp_site_card">
                <form method="post" action="{{ route('user.channels.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Channel Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>

                        @if ($type === 'telegram')
                            <div class="col-12">
                                <label class="form-label">{{ __('Bot Token') }}</label>
                                <input type="text" class="form-control" name="bot_token" value="{{ old('bot_token') }}" required>
                                <small class="text-muted">{{ __('Create a bot using @BotFather and paste the token here.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Channel Chat ID') }}</label>
                                <input type="text" class="form-control" name="chat_id" value="{{ old('chat_id') }}">
                                <small class="text-muted">{{ __('Optional. Use if you know the numeric chat ID.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Channel Username') }}</label>
                                <input type="text" class="form-control" name="chat_username" value="{{ old('chat_username') }}">
                                <small class="text-muted">{{ __('Optional. Example: @my_channel') }}</small>
                            </div>
                        @elseif ($type === 'telegram_mtproto')
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Telegram API ID') }}</label>
                                <input type="text" class="form-control" name="api_id" value="{{ old('api_id') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Telegram API Hash') }}</label>
                                <input type="text" class="form-control" name="api_hash" value="{{ old('api_hash') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Phone Number') }}</label>
                                <input type="text" class="form-control" name="phone_number" value="{{ old('phone_number') }}">
                                <small class="text-muted">{{ __('Use international format, e.g. +15551234567.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Channel Username') }}</label>
                                <input type="text" class="form-control" name="channel_username" value="{{ old('channel_username') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Channel Numeric ID (optional)') }}</label>
                                <input type="text" class="form-control" name="channel_id" value="{{ old('channel_id') }}">
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="las la-info-circle me-1"></i>
                                    {{ __('You will be asked to authenticate this account after saving. Make sure the account can access the target channel.') }}
                                </div>
                            </div>
                        @elseif ($type === 'api')
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Existing Webhook URL (optional)') }}</label>
                                <input type="url" class="form-control" name="webhook_url" value="{{ old('webhook_url') }}">
                                <small class="text-muted">{{ __('Leave empty to auto-generate a secure URL.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Secret Key (optional)') }}</label>
                                <input type="text" class="form-control" name="secret_key" value="{{ old('secret_key') }}">
                                <small class="text-muted">{{ __('Use to sign requests with HMAC SHA-256.') }}</small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">
                                    <i class="las la-bolt me-1"></i>
                                    {{ __('Send POST requests with your payload to the generated endpoint. Include a JSON field called "message" or "text".') }}
                                </div>
                            </div>
                        @elseif ($type === 'web_scrape')
                            <div class="col-12">
                                <label class="form-label">{{ __('Target URL') }}</label>
                                <input type="url" class="form-control" name="url" value="{{ old('url') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Selector Type') }}</label>
                                <select name="selector_type" class="form-select" required>
                                    <option value="css" {{ old('selector_type', 'css') === 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('selector_type') === 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Content Selector') }}</label>
                                <input type="text" class="form-control" name="selector" value="{{ old('selector') }}" required>
                                <small class="text-muted">{{ __('Example: .signal-card .body or //div[@class="signal"]') }}</small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    <i class="las la-exclamation-triangle me-1"></i>
                                    {{ __('Respect website terms of service. The scraper will pause automatically if it encounters repeated errors or rate limits.') }}
                                </div>
                            </div>
                        @elseif ($type === 'rss')
                            <div class="col-12">
                                <label class="form-label">{{ __('Feed URL') }}</label>
                                <input type="url" class="form-control" name="feed_url" value="{{ old('feed_url') }}" required>
                                <small class="text-muted">{{ __('Supports RSS 2.0 and Atom 1.0 feeds.') }}</small>
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">{{ __('Default Plan') }}</label>
                            <select class="form-select" name="default_plan_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ old('default_plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Default Market') }}</label>
                            <select class="form-select" name="default_market_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}" {{ old('default_market_id') == $market->id ? 'selected' : '' }}>
                                        {{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Default Timeframe') }}</label>
                            <select class="form-select" name="default_timeframe_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($timeframes as $timeframe)
                                    <option value="{{ $timeframe->id }}" {{ old('default_timeframe_id') == $timeframe->id ? 'selected' : '' }}>
                                        {{ $timeframe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Auto Publish Confidence Threshold') }}</label>
                            <input type="number" class="form-control" name="auto_publish_confidence_threshold" min="0" max="100" step="5" value="{{ old('auto_publish_confidence_threshold', 90) }}">
                            <small class="text-muted">{{ __('Signals parsed with confidence ≥ this value will publish automatically.') }}</small>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn sp_theme_btn w-100">
                                <i class="las la-save me-1"></i> {{ __('Connect Channel') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="sp_site_card">
                <h5 class="mb-3">{{ __('Need Help?') }}</h5>
                <ul class="list-unstyled small mb-0">
                    @if ($type === 'telegram')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Add the bot to your channel/group and grant it admin read access.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Use @getidsbot in Telegram to discover the numeric chat ID if needed.') }}</li>
                    @elseif ($type === 'telegram_mtproto')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Install the MadelineProto session as instructed after saving to receive messages as a regular user.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Two-factor authentication must be disabled on the Telegram account.') }}</li>
                    @elseif ($type === 'api')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Send JSON payloads with the field "message" or "text".') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Include the header X-Signature to verify with your secret key.') }}</li>
                    @elseif ($type === 'web_scrape')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Confirm the selector targets a single message element.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Respect robots.txt and site rate limits.') }}</li>
                    @elseif ($type === 'rss')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Only new feed items are converted into signals.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Ensure the feed is publicly accessible without authentication.') }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection

