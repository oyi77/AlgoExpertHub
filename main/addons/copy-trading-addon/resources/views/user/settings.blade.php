@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }}</h4>
        </div>
        <div class="card-body">
                        <form action="{{ route('user.copy-trading.settings.update') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" 
                                        value="1" {{ $setting->is_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_enabled">
                                        Enable Copy Trading
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    When enabled, other users can copy your trades
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="min_followers_balance">Minimum Follower Balance</label>
                                <input type="number" class="form-control" name="min_followers_balance" 
                                    id="min_followers_balance" value="{{ $setting->min_followers_balance }}" 
                                    step="0.01" min="0">
                                <small class="form-text text-muted">
                                    Minimum balance required for users to follow you (optional)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="max_copiers">Maximum Followers</label>
                                <input type="number" class="form-control" name="max_copiers" 
                                    id="max_copiers" value="{{ $setting->max_copiers }}" min="1">
                                <small class="form-text text-muted">
                                    Maximum number of users who can copy your trades (optional)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="risk_multiplier_default">Default Risk Multiplier</label>
                                <input type="number" class="form-control" name="risk_multiplier_default" 
                                    id="risk_multiplier_default" value="{{ $setting->risk_multiplier_default }}" 
                                    step="0.1" min="0.1" max="10">
                                <small class="form-text text-muted">
                                    Default risk multiplier for followers using Easy Copy mode (0.1 to 10.0)
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_manual_trades" 
                                        id="allow_manual_trades" value="1" 
                                        {{ $setting->allow_manual_trades ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_manual_trades">
                                        Allow Copying Manual Trades
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_auto_trades" 
                                        id="allow_auto_trades" value="1" 
                                        {{ $setting->allow_auto_trades ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_auto_trades">
                                        Allow Copying Auto Trades (Signal-based)
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h5>Statistics</h5>
                                <p>Active Followers: <strong>{{ $stats['follower_count'] ?? 0 }}</strong></p>
                                <p>Total Copied Trades: <strong>{{ $stats['total_copied_trades'] ?? 0 }}</strong></p>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">{{ __('Save Settings') }}</button>
                        </form>
        </div>
    </div>
@endsection

