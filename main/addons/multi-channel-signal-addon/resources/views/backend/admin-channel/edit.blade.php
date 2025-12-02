@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channels.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.channels.update', $channel->id) }}" method="post">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label>{{ __('Channel Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $channel->name) }}" required>
                            </div>

                            @if ($channel->type === 'telegram_mtproto')
                                <div class="col-md-12 mb-4">
                                    <div class="alert alert-info">
                                        <h6><i class="fa fa-info-circle"></i> {{ __('Telegram API Credentials') }}</h6>
                                        <p class="mb-2"><strong>{{ __('If you\'re getting UPDATE_APP_TO_LOGIN error, you need to get NEW API credentials:') }}</strong></p>
                                        <ol class="mb-2 pl-3">
                                            <li>{{ __('Go to') }} <a href="https://my.telegram.org/apps" target="_blank">https://my.telegram.org/apps</a> {{ __('and login with your phone number') }}</li>
                                            <li>{{ __('After login, look for the section "Your applications" or "API development tools"') }}</li>
                                            <li>{{ __('If you see an existing app, you can use those credentials OR create a new one') }}</li>
                                            <li>{{ __('To create new: Look for "Create application" or "New application" button/link') }}</li>
                                            <li>{{ __('Fill in: App title, Short name, Platform: Other') }}</li>
                                            <li>{{ __('After creation, you\'ll see API ID (number) and API Hash (long string)') }}</li>
                                            <li>{{ __('Copy both values and paste them in the fields below') }}</li>
                                        </ol>
                                        <p class="mb-0">
                                            <strong>{{ __('Important:') }}</strong> 
                                            {{ __('If you don\'t see "Create application" button on my.telegram.org/apps, try these solutions:') }}
                                        </p>
                                        <ul class="mb-0 pl-3">
                                            <li>{{ __('Scroll to the VERY TOP of the page - the "Your applications" section should be above "FCM credentials"') }}</li>
                                            <li>{{ __('Look for a "+" icon or "Add" button in the top-right of the applications section') }}</li>
                                            <li>{{ __('Try a different browser (Chrome, Firefox) or incognito/private mode') }}</li>
                                            <li>{{ __('Disable VPN and browser extensions (especially ad blockers)') }}</li>
                                            <li>{{ __('Use a DIFFERENT Telegram account to create new API credentials, then use those credentials here') }}</li>
                                            <li>{{ __('Contact Telegram support if the button is still missing') }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API ID') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_id" class="form-control" 
                                        value="{{ old('api_id', $channel->config['api_id'] ?? '') }}" 
                                        placeholder="12345678" required>
                                    <small class="text-muted">{{ __('Get from https://my.telegram.org/apps') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API Hash') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_hash" class="form-control" 
                                        value="{{ old('api_hash', $channel->config['api_hash'] ?? '') }}" 
                                        placeholder="abcdef1234567890abcdef1234567890" required>
                                    <small class="text-muted">{{ __('Get from https://my.telegram.org/apps') }}</small>
                                </div>
                            @endif

                            <div class="col-md-4 mb-4">
                                <label>{{ __('Default Plan') }}</label>
                                <select name="default_plan_id" class="form-control">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('default_plan_id', $channel->default_plan_id) == $plan->id ? 'selected' : '' }}>
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
                                        <option value="{{ $market->id }}" {{ old('default_market_id', $channel->default_market_id) == $market->id ? 'selected' : '' }}>
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
                                        <option value="{{ $timeframe->id }}" {{ old('default_timeframe_id', $channel->default_timeframe_id) == $timeframe->id ? 'selected' : '' }}>
                                            {{ $timeframe->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label>{{ __('Auto-Publish Confidence Threshold') }}</label>
                                <input type="number" name="auto_publish_confidence_threshold" class="form-control" 
                                    value="{{ old('auto_publish_confidence_threshold', $channel->auto_publish_confidence_threshold) }}" min="0" max="100">
                                <small class="text-muted">{{ __('Signals with confidence >= this value will be auto-published (0-100)') }}</small>
                            </div>

                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('Update Channel') }}</button>
                                <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                <a href="{{ route('admin.channels.assign', $channel->id) }}" class="btn btn-info">
                                    <i class="fa fa-users"></i> {{ __('Manage Assignments') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

