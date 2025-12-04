@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('Create Signal Source') }}</h4>
                    <a class="btn btn-secondary" href="{{ route('admin.signal-sources.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.signal-sources.store') }}" method="post">
                    @csrf

                    <!-- Source Name -->
                    <div class="form-group">
                        <label>{{ __('Source Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        <small class="text-muted">{{ __('A friendly name to identify this signal source') }}</small>
                    </div>

                    <!-- Source Type Selector -->
                    <div class="form-group">
                        <label>{{ __('Source Type') }} <span class="text-danger">*</span></label>
                        <select name="type" id="sourceType" class="form-control" required onchange="updateSourceFields()">
                            <option value="">{{ __('Select Source Type') }}</option>
                            @if($globalConfig['telegram_enabled'] ?? false)
                            <option value="telegram_mtproto" {{ old('type') === 'telegram_mtproto' ? 'selected' : '' }}>
                                <i class="fab fa-telegram"></i> Telegram (MTProto) - User Channels
                            </option>
                            @endif
                            <option value="telegram" {{ old('type') === 'telegram' ? 'selected' : '' }}>
                                <i class="fab fa-telegram"></i> Telegram Bot
                            </option>
                            <option value="api" {{ old('type') === 'api' ? 'selected' : '' }}>
                                <i class="fas fa-code"></i> API / Webhook
                            </option>
                            <option value="web_scrape" {{ old('type') === 'web_scrape' ? 'selected' : '' }}>
                                <i class="fas fa-spider"></i> Web Scraping
                            </option>
                            <option value="rss" {{ old('type') === 'rss' ? 'selected' : '' }}>
                                <i class="fas fa-rss"></i> RSS Feed
                            </option>
                        </select>
                        <small class="text-muted" id="typeDescription"></small>
                    </div>

                    <!-- Dynamic Configuration Fields -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('Connection Configuration') }}</h6>
                        </div>
                        <div class="card-body" id="configFields">
                            <p class="text-muted">{{ __('Select a source type to see configuration options') }}</p>
                        </div>
                    </div>

                    <!-- Admin Owned -->
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_admin_owned" name="is_admin_owned" value="1" {{ old('is_admin_owned') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_admin_owned">
                                {{ __('Admin-Owned (Global source, can be assigned to users/plans)') }}
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ __('Create Signal Source') }}
                        </button>
                        <a href="{{ route('admin.signal-sources.index') }}" class="btn btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const telegramGlobalConfigured = {{ ($globalConfig['telegram_enabled'] ?? false) ? 'true' : 'false' }};

function updateSourceFields() {
    const type = document.getElementById('sourceType').value;
    const configDiv = document.getElementById('configFields');
    const typeDesc = document.getElementById('typeDescription');

    if (!type) {
        configDiv.innerHTML = '<p class="text-muted">Select a source type to see configuration options</p>';
        typeDesc.textContent = '';
        return;
    }

    let html = '';
    let description = '';

    switch (type) {
        case 'telegram_mtproto':
            description = 'Connect to Telegram user channels using MTProto (read messages from channels you joined)';
            html = `
                <div class="alert alert-success">
                    <i class="fas fa-check"></i> <strong>Global Telegram Configuration Active</strong><br>
                    API credentials are configured globally. You only need to authenticate with your phone number after creation.
                </div>
                <div class="form-group">
                    <label>Channel Username</label>
                    <input type="text" name="channel_username" class="form-control" value="{{ old('channel_username') }}" placeholder="@channelname">
                    <small class="text-muted">Optional. You can select channels after authentication.</small>
                </div>
            `;
            break;

        case 'telegram':
            description = 'Connect using Telegram Bot (requires bot token from @BotFather)';
            html = `
                <div class="form-group">
                    <label>Bot Token <span class="text-danger">*</span></label>
                    <input type="text" name="bot_token" class="form-control" value="{{ old('bot_token') }}" required>
                    <small class="text-muted">Create a bot using @BotFather and paste the token here</small>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Channel Chat ID</label>
                            <input type="text" name="chat_id" class="form-control" value="{{ old('chat_id') }}">
                            <small class="text-muted">Numeric chat ID</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Channel Username</label>
                            <input type="text" name="chat_username" class="form-control" value="{{ old('chat_username') }}" placeholder="@channel">
                            <small class="text-muted">Example: @my_channel</small>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'api':
            description = 'Receive signals via API webhook endpoint';
            html = `
                <div class="form-group">
                    <label>Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url') }}" readonly>
                    <small class="text-muted">A webhook URL will be generated for you after creation</small>
                </div>
                <div class="form-group">
                    <label>Secret Key</label>
                    <input type="text" name="secret_key" class="form-control" value="{{ old('secret_key', Str::random(32)) }}">
                    <small class="text-muted">Used to verify webhook requests</small>
                </div>
            `;
            break;

        case 'web_scrape':
            description = 'Scrape signals from a website using CSS or XPath selectors';
            html = `
                <div class="form-group">
                    <label>URL <span class="text-danger">*</span></label>
                    <input type="url" name="url" class="form-control" value="{{ old('url') }}" required>
                    <small class="text-muted">The URL to scrape for signals</small>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Selector <span class="text-danger">*</span></label>
                            <input type="text" name="selector" class="form-control" value="{{ old('selector') }}" 
                                placeholder=".signal-container, //div[@class='signal']" required>
                            <small class="text-muted">CSS or XPath selector for signal elements</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Selector Type <span class="text-danger">*</span></label>
                            <select name="selector_type" class="form-control" required>
                                <option value="css" {{ old('selector_type', 'css') === 'css' ? 'selected' : '' }}>CSS</option>
                                <option value="xpath" {{ old('selector_type') === 'xpath' ? 'selected' : '' }}>XPath</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Scrape Interval (minutes)</label>
                    <input type="number" name="scrape_interval" class="form-control" value="{{ old('scrape_interval', 5) }}" min="1">
                    <small class="text-muted">How often to check for new signals</small>
                </div>
            `;
            break;

        case 'rss':
            description = 'Monitor RSS/Atom feeds for new signal entries';
            html = `
                <div class="form-group">
                    <label>RSS Feed URL <span class="text-danger">*</span></label>
                    <input type="url" name="feed_url" class="form-control" value="{{ old('feed_url') }}" required>
                    <small class="text-muted">The RSS/Atom feed URL</small>
                </div>
                <div class="form-group">
                    <label>Check Interval (minutes)</label>
                    <input type="number" name="check_interval" class="form-control" value="{{ old('check_interval', 5) }}" min="1">
                    <small class="text-muted">How often to check feed for new items</small>
                </div>
            `;
            break;
    }

    configDiv.innerHTML = html;
    typeDesc.textContent = description;
}

// Trigger on page load if type is selected
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('sourceType').value) {
        updateSourceFields();
    }
});
</script>
@endsection

