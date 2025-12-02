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
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channels.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.channels.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label>{{ __('Channel Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
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
                                        {{ __('After saving, you will be asked to authenticate your Telegram account. Once authenticated, you can select which channel to monitor from your available channels.') }}
                                    </div>
                                </div>
                            @elseif ($type === 'telegram')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Bot Token') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="bot_token" class="form-control" value="{{ old('bot_token') }}" required>
                                    <small class="text-muted">{{ __('Create a bot using @BotFather') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Channel Chat ID') }}</label>
                                    <input type="text" name="chat_id" class="form-control" value="{{ old('chat_id') }}">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Channel Username') }}</label>
                                    <input type="text" name="chat_username" class="form-control" value="{{ old('chat_username') }}" placeholder="@channel_name">
                                </div>
                            @elseif ($type === 'api')
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Webhook URL (optional)') }}</label>
                                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url') }}">
                                    <small class="text-muted">{{ __('Leave empty to auto-generate') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Secret Key (optional)') }}</label>
                                    <input type="text" name="secret_key" class="form-control" value="{{ old('secret_key') }}">
                                </div>
                            @elseif ($type === 'web_scrape')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Target URL') }} <span class="text-danger">*</span></label>
                                    <input type="url" name="url" class="form-control" value="{{ old('url') }}" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Selector Type') }} <span class="text-danger">*</span></label>
                                    <select name="selector_type" class="form-control" required>
                                        <option value="css" {{ old('selector_type', 'css') === 'css' ? 'selected' : '' }}>CSS</option>
                                        <option value="xpath" {{ old('selector_type') === 'xpath' ? 'selected' : '' }}>XPath</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Content Selector') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="selector" class="form-control" value="{{ old('selector') }}" required>
                                </div>
                            @elseif ($type === 'rss')
                                <div class="col-md-12 mb-4">
                                    <label>{{ __('Feed URL') }} <span class="text-danger">*</span></label>
                                    <input type="url" name="feed_url" class="form-control" value="{{ old('feed_url') }}" required>
                                </div>
                            @endif

                            <div class="col-md-4 mb-4">
                                <label>{{ __('Default Plan') }}</label>
                                <select name="default_plan_id" class="form-control">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('default_plan_id') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label>{{ __('Default Market') }}</label>
                                <select name="default_market_id" class="form-control">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($markets as $market)
                                        <option value="{{ $market->id }}" {{ old('default_market_id') == $market->id ? 'selected' : '' }}>
                                            {{ $market->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label>{{ __('Default Timeframe') }}</label>
                                <select name="default_timeframe_id" class="form-control">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($timeframes as $timeframe)
                                        <option value="{{ $timeframe->id }}" {{ old('default_timeframe_id') == $timeframe->id ? 'selected' : '' }}>
                                            {{ $timeframe->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label>{{ __('Auto-Publish Confidence Threshold') }}</label>
                                <input type="number" name="auto_publish_confidence_threshold" class="form-control" 
                                    value="{{ old('auto_publish_confidence_threshold', 90) }}" min="0" max="100">
                                <small class="text-muted">{{ __('Signals with confidence >= this value will be auto-published (0-100)') }}</small>
                            </div>

                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('Create Channel') }}</button>
                                <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

