@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __($title) }}</h4>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @endif

                    @if(isset($setting))
                        <form action="{{ route('user.copy-trading.settings.update') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" 
                                        value="1" {{ ($setting->is_enabled ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_enabled">
                                        {{ __('Enable Copy Trading') }}
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    {{ __('When enabled, other users can copy your trades') }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="min_followers_balance">{{ __('Minimum Follower Balance') }}</label>
                                <input type="number" class="form-control" name="min_followers_balance" 
                                    id="min_followers_balance" value="{{ $setting->min_followers_balance ?? '' }}" 
                                    step="0.01" min="0">
                                <small class="form-text text-muted">
                                    {{ __('Minimum balance required for users to follow you (optional)') }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="max_copiers">{{ __('Maximum Followers') }}</label>
                                <input type="number" class="form-control" name="max_copiers" 
                                    id="max_copiers" value="{{ $setting->max_copiers ?? '' }}" min="1">
                                <small class="form-text text-muted">
                                    {{ __('Maximum number of users who can copy your trades (optional)') }}
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="risk_multiplier_default">{{ __('Default Risk Multiplier') }}</label>
                                <input type="number" class="form-control" name="risk_multiplier_default" 
                                    id="risk_multiplier_default" value="{{ $setting->risk_multiplier_default ?? 1.0 }}" 
                                    step="0.1" min="0.1" max="10">
                                <small class="form-text text-muted">
                                    {{ __('Default risk multiplier for followers using Easy Copy mode (0.1 to 10.0)') }}
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_manual_trades" 
                                        id="allow_manual_trades" value="1" 
                                        {{ ($setting->allow_manual_trades ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_manual_trades">
                                        {{ __('Allow Copying Manual Trades') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_auto_trades" 
                                        id="allow_auto_trades" value="1" 
                                        {{ ($setting->allow_auto_trades ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_auto_trades">
                                        {{ __('Allow Copying Auto Trades (Signal-based)') }}
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Settings') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <p>{{ __('Copy trading settings form will be available soon.') }}</p>
                            <p>{{ __('Please ensure the trading execution engine is enabled.') }}</p>
                        </div>
                    @endif

                    @if(isset($stats))
                        <div class="mt-4">
                            <h5>{{ __('Statistics') }}</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Followers') }}</h6>
                                        <span class="fw-semibold fs-4">{{ $stats['follower_count'] ?? 0 }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Copied Trades') }}</h6>
                                        <span class="fw-semibold fs-4">{{ $stats['total_copied_trades'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
