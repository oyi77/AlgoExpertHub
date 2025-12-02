@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <a href="{{ route('user.signal-sources.index') }}" class="btn btn-link p-0">
                    <i class="las la-arrow-left me-1"></i> {{ __('Back to Signal Sources') }}
                </a>
                <h4 class="mb-0">{{ __('Add :type Signal Source', ['type' => ucfirst(str_replace('_', ' ', $type))]) }}</h4>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="sp_site_card">
                <form method="post" action="{{ route('user.signal-sources.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Source Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                            <small class="text-muted">{{ __('A friendly name to identify this signal source') }}</small>
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
                        @elseif ($type === 'trading_bot')
                            <div class="col-12">
                                <label class="form-label">{{ __('Source Type') }}</label>
                                <select name="source_type" id="source_type" class="form-select" required>
                                    <option value="api" {{ old('source_type', 'api') === 'api' ? 'selected' : '' }}>API Endpoint</option>
                                    <option value="firebase" {{ old('source_type') === 'firebase' ? 'selected' : '' }}>Firebase</option>
                                </select>
                                <small class="text-muted">{{ __('Choose how to connect to the trading bot') }}</small>
                            </div>

                            {{-- API Configuration --}}
                            <div id="api_config" style="display: {{ old('source_type', 'api') === 'api' ? 'block' : 'none' }};">
                                <div class="col-12">
                                    <label class="form-label">{{ __('API Endpoint') }}</label>
                                    <input type="url" class="form-control" name="api_endpoint" value="{{ old('api_endpoint') }}" placeholder="https://api.example.com/signals">
                                    <small class="text-muted">{{ __('The API endpoint URL to fetch signals from') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('API Token') }}</label>
                                    <input type="text" class="form-control" name="api_token" value="{{ old('api_token') }}">
                                    <small class="text-muted">{{ __('Optional. Authentication token') }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Auth Type') }}</label>
                                    <select name="auth_type" class="form-select">
                                        <option value="Bearer" {{ old('auth_type', 'Bearer') === 'Bearer' ? 'selected' : '' }}>Bearer</option>
                                        <option value="Basic" {{ old('auth_type') === 'Basic' ? 'selected' : '' }}>Basic</option>
                                        <option value="Custom" {{ old('auth_type') === 'Custom' ? 'selected' : '' }}>Custom Header</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="require_auth" value="1" class="form-check-input" id="require_auth" {{ old('require_auth') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="require_auth">
                                            {{ __('Require Authentication') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Firebase Configuration --}}
                            <div id="firebase_config" style="display: {{ old('source_type') === 'firebase' ? 'block' : 'none' }};">
                                <div class="col-12">
                                    <label class="form-label">{{ __('Firebase Project ID') }}</label>
                                    <input type="text" class="form-control" name="firebase_project_id" value="{{ old('firebase_project_id') }}" placeholder="my-project-id">
                                    <small class="text-muted">{{ __('Your Firebase project ID') }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('Firebase Credentials (JSON)') }}</label>
                                    <textarea name="firebase_credentials" class="form-control" rows="5" placeholder='{"type": "service_account", ...}'>{{ old('firebase_credentials') }}</textarea>
                                    <small class="text-muted">{{ __('Paste your Firebase service account JSON credentials') }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('Collection Name') }}</label>
                                    <input type="text" class="form-control" name="firebase_collection" value="{{ old('firebase_collection', 'signals') }}" placeholder="signals">
                                    <small class="text-muted">{{ __('Firestore collection name (default: signals)') }}</small>
                                </div>
                            </div>

                            <script>
                                document.getElementById('source_type').addEventListener('change', function() {
                                    var sourceType = this.value;
                                    document.getElementById('api_config').style.display = sourceType === 'api' ? 'block' : 'none';
                                    document.getElementById('firebase_config').style.display = sourceType === 'firebase' ? 'block' : 'none';
                                });
                            </script>
                        @endif

                        <div class="col-12">
                            <button type="submit" class="btn sp_theme_btn w-100">
                                <i class="las la-save me-1"></i> {{ __('Create Signal Source') }}
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
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('You will authenticate using your phone number after saving.') }}</li>
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
                    @elseif ($type === 'trading_bot')
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('For API: Ensure the endpoint returns signals in JSON format.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('For Firebase: Use service account credentials with Firestore read access.') }}</li>
                        <li class="mb-2"><i class="las la-check-circle text-success me-1"></i>{{ __('Signals are fetched every 2 minutes automatically.') }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection

