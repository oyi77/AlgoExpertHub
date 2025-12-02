@extends('backend.layout.master')

@section('element')
    @if ($type === 'telegram_mtproto' && !$madelineproto_installed)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>{{ __('MadelineProto Library Not Installed.') }}</strong> {{ __('Please Run:') }} <code>composer require danog/madelineproto</code>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signal-sources.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.signal-sources.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label>{{ __('Source Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                <small class="text-muted">{{ __('A friendly name to identify this signal source') }}</small>
                            </div>

                            @if ($type === 'telegram_mtproto')
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API ID') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_id" class="form-control" value="{{ old('api_id') }}" required>
                                    <small class="text-muted">{{ __('Get from https://my.telegram.org/apps') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API Hash') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_hash" class="form-control" value="{{ old('api_hash') }}" required>
                                </div>
                                <div class="col-md-12 mb-4">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i>
                                        {{ __('After saving, you will be asked to authenticate your Telegram account using your phone number.') }}
                                    </div>
                                </div>
                            @elseif ($type === 'telegram')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Bot Token') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="bot_token" class="form-control" value="{{ old('bot_token') }}" required>
                                    <small class="text-muted">{{ __('Create a bot using @BotFather and paste the token here') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Channel Chat ID') }}</label>
                                    <input type="text" name="chat_id" class="form-control" value="{{ old('chat_id') }}">
                                    <small class="text-muted">{{ __('Optional. Numeric chat ID') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Channel Username') }}</label>
                                    <input type="text" name="chat_username" class="form-control" value="{{ old('chat_username') }}" placeholder="@channel">
                                    <small class="text-muted">{{ __('Optional. Example: @my_channel') }}</small>
                                </div>
                            @elseif ($type === 'api')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Webhook URL') }}</label>
                                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url') }}">
                                    <small class="text-muted">{{ __('Optional. If not provided, a webhook URL will be generated for you') }}</small>
                                </div>
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Secret Key') }}</label>
                                    <input type="text" name="secret_key" class="form-control" value="{{ old('secret_key') }}">
                                    <small class="text-muted">{{ __('Optional. Used to verify webhook requests') }}</small>
                                </div>
                            @elseif ($type === 'web_scrape')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('URL') }} <span class="text-danger">*</span></label>
                                    <input type="url" name="url" class="form-control" value="{{ old('url') }}" required>
                                    <small class="text-muted">{{ __('The URL to scrape for signals') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Selector') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="selector" class="form-control" value="{{ old('selector') }}" required>
                                    <small class="text-muted">{{ __('CSS or XPath selector') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Selector Type') }} <span class="text-danger">*</span></label>
                                    <select name="selector_type" class="form-control" required>
                                        <option value="css" {{ old('selector_type') === 'css' ? 'selected' : '' }}>CSS</option>
                                        <option value="xpath" {{ old('selector_type') === 'xpath' ? 'selected' : '' }}>XPath</option>
                                    </select>
                                </div>
                            @elseif ($type === 'rss')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('RSS Feed URL') }} <span class="text-danger">*</span></label>
                                    <input type="url" name="feed_url" class="form-control" value="{{ old('feed_url') }}" required>
                                    <small class="text-muted">{{ __('The RSS/Atom feed URL') }}</small>
                                </div>
                            @elseif ($type === 'trading_bot')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Source Type') }} <span class="text-danger">*</span></label>
                                    <select name="source_type" id="source_type" class="form-control" required>
                                        <option value="api" {{ old('source_type') === 'api' ? 'selected' : '' }}>API Endpoint</option>
                                        <option value="firebase" {{ old('source_type') === 'firebase' ? 'selected' : '' }}>Firebase</option>
                                    </select>
                                    <small class="text-muted">{{ __('Choose how to connect to the trading bot') }}</small>
                                </div>

                                {{-- API Configuration --}}
                                <div id="api_config" style="display: {{ old('source_type', 'api') === 'api' ? 'block' : 'none' }};">
                                    <div class="col-md-12 mb-4">
                                        <label>{{ __('API Endpoint') }} <span class="text-danger">*</span></label>
                                        <input type="url" name="api_endpoint" class="form-control" value="{{ old('api_endpoint') }}" placeholder="https://api.example.com/signals">
                                        <small class="text-muted">{{ __('The API endpoint URL to fetch signals from') }}</small>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label>{{ __('API Token') }}</label>
                                        <input type="text" name="api_token" class="form-control" value="{{ old('api_token') }}">
                                        <small class="text-muted">{{ __('Optional. Authentication token') }}</small>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label>{{ __('Auth Type') }}</label>
                                        <select name="auth_type" class="form-control">
                                            <option value="Bearer" {{ old('auth_type') === 'Bearer' ? 'selected' : '' }}>Bearer</option>
                                            <option value="Basic" {{ old('auth_type') === 'Basic' ? 'selected' : '' }}>Basic</option>
                                            <option value="Custom" {{ old('auth_type') === 'Custom' ? 'selected' : '' }}>Custom Header</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-4">
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
                                    <div class="col-md-12 mb-4">
                                        <label>{{ __('Firebase Project ID') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="firebase_project_id" class="form-control" value="{{ old('firebase_project_id') }}" placeholder="my-project-id">
                                        <small class="text-muted">{{ __('Your Firebase project ID') }}</small>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label>{{ __('Firebase Credentials (JSON)') }} <span class="text-danger">*</span></label>
                                        <textarea name="firebase_credentials" class="form-control" rows="5" placeholder='{"type": "service_account", ...}'>{{ old('firebase_credentials') }}</textarea>
                                        <small class="text-muted">{{ __('Paste your Firebase service account JSON credentials') }}</small>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label>{{ __('Collection Name') }}</label>
                                        <input type="text" name="firebase_collection" class="form-control" value="{{ old('firebase_collection', 'signals') }}" placeholder="signals">
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
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ __('Create Signal Source') }}
                                </button>
                                <a href="{{ route('admin.signal-sources.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

