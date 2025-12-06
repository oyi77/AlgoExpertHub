{{-- Trading Schedule & Weekly Target Section --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa fa-calendar-alt mr-2"></i>
            {{ __('Trading Schedule & Weekly Target') }}
        </h5>
    </div>
    <div class="card-body">
        {{-- Trading Schedule --}}
        <h6 class="mb-3">{{ __('Trading Schedule') }}</h6>
        
        <div class="form-group">
            <div class="custom-control custom-switch">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="only_trade_in_session" 
                       name="only_trade_in_session" 
                       value="1"
                       {{ old('only_trade_in_session', $preset->only_trade_in_session ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="only_trade_in_session">
                    <strong>{{ __('Only Trade in Session') }}</strong>
                </label>
            </div>
            <small class="form-text text-muted">{{ __('Enable to restrict trading to specific hours and days') }}</small>
        </div>

        <div id="trading_schedule_fields" style="display: {{ old('only_trade_in_session', $preset->only_trade_in_session ?? false) ? 'block' : 'none' }};">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="session_profile">{{ __('Session Profile') }} <span class="text-danger">*</span></label>
                        <select class="form-control @error('session_profile') is-invalid @enderror" 
                                id="session_profile" 
                                name="session_profile" 
                                required>
                            <option value="CUSTOM" {{ old('session_profile', $preset->session_profile ?? 'CUSTOM') == 'CUSTOM' ? 'selected' : '' }}>
                                {{ __('Custom') }} (Manual Hours)
                            </option>
                            <option value="ASIA" {{ old('session_profile', $preset->session_profile ?? '') == 'ASIA' ? 'selected' : '' }}>
                                {{ __('Asia Session') }} (00:00 - 09:00 UTC)
                            </option>
                            <option value="LONDON" {{ old('session_profile', $preset->session_profile ?? '') == 'LONDON' ? 'selected' : '' }}>
                                {{ __('London Session') }} (08:00 - 17:00 UTC)
                            </option>
                            <option value="NY" {{ old('session_profile', $preset->session_profile ?? '') == 'NY' ? 'selected' : '' }}>
                                {{ __('New York Session') }} (13:00 - 22:00 UTC)
                            </option>
                        </select>
                        @error('session_profile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Predefined session or custom hours') }}</small>
                    </div>
                </div>
            </div>

            <div class="row" id="custom_hours_group" style="display: {{ old('session_profile', $preset->session_profile ?? 'CUSTOM') == 'CUSTOM' ? 'block' : 'none' }};">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="trading_hours_start">{{ __('Trading Hours Start') }}</label>
                        <input type="time" 
                               class="form-control @error('trading_hours_start') is-invalid @enderror" 
                               id="trading_hours_start" 
                               name="trading_hours_start" 
                               value="{{ old('trading_hours_start', $preset && $preset->trading_hours_start ? \Carbon\Carbon::parse($preset->trading_hours_start)->format('H:i') : '') }}">
                        @error('trading_hours_start')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Start time (HH:MM format)') }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="trading_hours_end">{{ __('Trading Hours End') }}</label>
                        <input type="time" 
                               class="form-control @error('trading_hours_end') is-invalid @enderror" 
                               id="trading_hours_end" 
                               name="trading_hours_end" 
                               value="{{ old('trading_hours_end', $preset && $preset->trading_hours_end ? \Carbon\Carbon::parse($preset->trading_hours_end)->format('H:i') : '') }}">
                        @error('trading_hours_end')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('End time (HH:MM format, can be next day)') }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="trading_timezone">{{ __('Timezone') }}</label>
                        <select class="form-control @error('trading_timezone') is-invalid @enderror" 
                                id="trading_timezone" 
                                name="trading_timezone">
                            <option value="SERVER" {{ old('trading_timezone', ($preset->trading_timezone ?? 'SERVER')) == 'SERVER' ? 'selected' : '' }}>
                                {{ __('Server Timezone') }}
                            </option>
                            <option value="UTC" {{ old('trading_timezone', ($preset->trading_timezone ?? '')) == 'UTC' ? 'selected' : '' }}>
                                UTC
                            </option>
                            <option value="America/New_York" {{ old('trading_timezone', $preset->trading_timezone ?? '') == 'America/New_York' ? 'selected' : '' }}>
                                America/New_York (EST)
                            </option>
                            <option value="Europe/London" {{ old('trading_timezone', $preset->trading_timezone ?? '') == 'Europe/London' ? 'selected' : '' }}>
                                Europe/London (GMT)
                            </option>
                            <option value="Asia/Tokyo" {{ old('trading_timezone', $preset->trading_timezone ?? '') == 'Asia/Tokyo' ? 'selected' : '' }}>
                                Asia/Tokyo (JST)
                            </option>
                            <option value="Australia/Sydney" {{ old('trading_timezone', $preset->trading_timezone ?? '') == 'Australia/Sydney' ? 'selected' : '' }}>
                                Australia/Sydney (AEST)
                            </option>
                        </select>
                        @error('trading_timezone')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Timezone for trading hours') }}</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>{{ __('Trading Days') }} <span class="text-danger">*</span></label>
                        <div class="row">
                            @php
                                $days = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday'
                                ];
                                $currentMask = old('trading_days_mask', $preset->trading_days_mask ?? 31); // Default: Mon-Fri (1+2+4+8+16=31)
                            @endphp
                            @foreach($days as $dayNum => $dayName)
                                <div class="col-md-3 col-sm-4 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        @php
                                            $bitValue = $dayNum == 7 ? 64 : (1 << ($dayNum - 1));
                                            $isChecked = ($currentMask & $bitValue) != 0;
                                        @endphp
                                        <input type="checkbox" 
                                               class="custom-control-input trading-day-checkbox" 
                                               id="day_{{ $dayNum }}" 
                                               name="trading_days[]" 
                                               value="{{ $dayNum }}"
                                               data-bit="{{ $bitValue }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="day_{{ $dayNum }}">
                                            {{ __($dayName) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" id="trading_days_mask" name="trading_days_mask" value="{{ $currentMask }}">
                        @error('trading_days_mask')
                            <span class="text-danger"><small>{{ $message }}</small></span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Select days when trading is allowed') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        {{-- Weekly Target --}}
        <h6 class="mb-3">{{ __('Weekly Target') }}</h6>
        
        <div class="form-group">
            <div class="custom-control custom-switch">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="weekly_target_enabled" 
                       name="weekly_target_enabled" 
                       value="1"
                       {{ old('weekly_target_enabled', $preset->weekly_target_enabled ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="weekly_target_enabled">
                    <strong>{{ __('Enable Weekly Target') }}</strong>
                </label>
            </div>
            <small class="form-text text-muted">{{ __('Track and enforce weekly profit targets') }}</small>
        </div>

        <div id="weekly_target_fields" style="display: {{ old('weekly_target_enabled', $preset->weekly_target_enabled ?? false) ? 'block' : 'none' }};">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weekly_target_profit_pct">{{ __('Weekly Target Profit (%)') }}</label>
                        <input type="number" 
                               class="form-control @error('weekly_target_profit_pct') is-invalid @enderror" 
                               id="weekly_target_profit_pct" 
                               name="weekly_target_profit_pct" 
                               value="{{ old('weekly_target_profit_pct', $preset->weekly_target_profit_pct ?? '') }}" 
                               step="0.01" 
                               min="0.01" 
                               max="1000"
                               placeholder="5.0">
                        @error('weekly_target_profit_pct')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Target profit percentage per week') }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weekly_reset_day">{{ __('Weekly Reset Day') }}</label>
                        <select class="form-control @error('weekly_reset_day') is-invalid @enderror" 
                                id="weekly_reset_day" 
                                name="weekly_reset_day">
                            <option value="">{{ __('Select Day') }}</option>
                            <option value="1" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '1' ? 'selected' : '' }}>{{ __('Monday') }}</option>
                            <option value="2" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '2' ? 'selected' : '' }}>{{ __('Tuesday') }}</option>
                            <option value="3" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '3' ? 'selected' : '' }}>{{ __('Wednesday') }}</option>
                            <option value="4" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '4' ? 'selected' : '' }}>{{ __('Thursday') }}</option>
                            <option value="5" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '5' ? 'selected' : '' }}>{{ __('Friday') }}</option>
                            <option value="6" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '6' ? 'selected' : '' }}>{{ __('Saturday') }}</option>
                            <option value="7" {{ old('weekly_reset_day', $preset->weekly_reset_day ?? '') == '7' ? 'selected' : '' }}>{{ __('Sunday') }}</option>
                        </select>
                        @error('weekly_reset_day')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">{{ __('Day of week to reset weekly target tracking') }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-4">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="auto_stop_on_weekly_target" 
                                   name="auto_stop_on_weekly_target" 
                                   value="1"
                                   {{ old('auto_stop_on_weekly_target', $preset->auto_stop_on_weekly_target ?? false) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="auto_stop_on_weekly_target">
                                {{ __('Auto Stop on Weekly Target') }}
                            </label>
                        </div>
                        <small class="form-text text-muted">{{ __('Stop new trades when weekly target is reached') }}</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                {{ __('Weekly target is tracked per connection. When target is reached and auto-stop is enabled, new trades will be blocked until the reset day.') }}
            </div>
        </div>
    </div>
</div>

