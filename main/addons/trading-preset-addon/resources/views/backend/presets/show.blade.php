@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header justify-content-between">
                    <h4 class="card-title">{{ $title }}</h4>
                    <div>
                        <a href="{{ route('admin.trading-presets.edit', $preset) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-pen"></i> {{ __('Edit') }}
                        </a>
                        <form action="{{ route('admin.trading-presets.clone', $preset) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('{{ __('Are you sure you want to clone this preset?') }}');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fa fa-copy"></i> {{ __('Clone') }}
                            </button>
                        </form>
                        <form action="{{ route('admin.trading-presets.toggle-status', $preset) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-{{ $preset->enabled ? 'warning' : 'success' }}">
                                <i class="fa fa-{{ $preset->enabled ? 'pause' : 'play' }}"></i> 
                                {{ $preset->enabled ? __('Disable') : __('Enable') }}
                            </button>
                        </form>
                        @if(!$preset->is_default_template)
                            <form action="{{ route('admin.trading-presets.destroy', $preset) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this preset?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa fa-trash"></i> {{ __('Delete') }}
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.trading-presets.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Status Badges --}}
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <span class="badge badge-{{ $preset->enabled ? 'success' : 'secondary' }} badge-lg mr-2">
                                {{ $preset->enabled ? __('Enabled') : __('Disabled') }}
                            </span>
                            @if($preset->visibility === 'PUBLIC_MARKETPLACE')
                                <span class="badge badge-primary badge-lg mr-2">{{ __('Public') }}</span>
                            @else
                                <span class="badge badge-secondary badge-lg mr-2">{{ __('Private') }}</span>
                            @endif
                            @if($preset->is_default_template)
                                <span class="badge badge-info badge-lg mr-2">{{ __('Default Template') }}</span>
                            @else
                                <span class="badge badge-success badge-lg mr-2">{{ __('User Preset') }}</span>
                            @endif
                            @if($preset->clonable)
                                <span class="badge badge-warning badge-lg">{{ __('Clonable') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Basic Information --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-info-circle mr-2"></i>
                                {{ __('Basic Information') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>{{ __('Name:') }}</strong> {{ $preset->name }}</p>
                                    <p><strong>{{ __('Description:') }}</strong> {{ $preset->description ?? '-' }}</p>
                                    <p><strong>{{ __('Symbol:') }}</strong> {{ $preset->symbol ?? __('All') }}</p>
                                    <p><strong>{{ __('Timeframe:') }}</strong> {{ $preset->timeframe ?? __('All') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ __('Creator:') }}</strong> 
                                        @if($preset->creator)
                                            {{ $preset->creator->username ?? $preset->creator->email }}
                                        @else
                                            {{ __('System') }}
                                        @endif
                                    </p>
                                    <p><strong>{{ __('Created:') }}</strong> {{ $preset->created_at->format('Y-m-d H:i:s') }}</p>
                                    <p><strong>{{ __('Updated:') }}</strong> {{ $preset->updated_at->format('Y-m-d H:i:s') }}</p>
                                    @if($preset->tags)
                                        <p><strong>{{ __('Tags:') }}</strong>
                                            @foreach($preset->tags as $tag)
                                                <span class="badge badge-secondary">{{ $tag }}</span>
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Position & Risk --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-chart-line mr-2"></i>
                                {{ __('Position & Risk Management') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>{{ __('Position Size Mode:') }}</strong> 
                                        <span class="badge badge-info">{{ $preset->position_size_mode }}</span>
                                    </p>
                                    @if($preset->position_size_mode === 'FIXED')
                                        <p><strong>{{ __('Fixed Lot:') }}</strong> {{ $preset->fixed_lot }}</p>
                                    @else
                                        <p><strong>{{ __('Risk Per Trade:') }}</strong> {{ $preset->risk_per_trade_pct }}%</p>
                                    @endif
                                    <p><strong>{{ __('Max Positions:') }}</strong> {{ $preset->max_positions }}</p>
                                    <p><strong>{{ __('Max Positions Per Symbol:') }}</strong> {{ $preset->max_positions_per_symbol }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ __('Dynamic Equity Mode:') }}</strong> 
                                        <span class="badge badge-secondary">{{ $preset->equity_dynamic_mode }}</span>
                                    </p>
                                    @if($preset->equity_dynamic_mode !== 'NONE')
                                        <p><strong>{{ __('Base Equity:') }}</strong> {{ $preset->equity_base ?? '-' }}</p>
                                        @if($preset->equity_dynamic_mode === 'STEP')
                                            <p><strong>{{ __('Step Factor:') }}</strong> {{ $preset->equity_step_factor ?? '-' }}</p>
                                        @endif
                                        <p><strong>{{ __('Risk Range:') }}</strong> 
                                            {{ $preset->risk_min_pct ?? '-' }}% - {{ $preset->risk_max_pct ?? '-' }}%
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stop Loss & Take Profit --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-shield-alt mr-2"></i>
                                {{ __('Stop Loss & Take Profit') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>{{ __('SL Mode:') }}</strong> 
                                        <span class="badge badge-warning">{{ $preset->sl_mode }}</span>
                                    </p>
                                    @if($preset->sl_mode === 'PIPS')
                                        <p><strong>{{ __('SL Pips:') }}</strong> {{ $preset->sl_pips }}</p>
                                    @elseif($preset->sl_mode === 'R_MULTIPLE')
                                        <p><strong>{{ __('SL R Multiple:') }}</strong> {{ $preset->sl_r_multiple }}</p>
                                    @else
                                        <p><strong>{{ __('Structure-Based:') }}</strong> {{ __('Yes') }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ __('TP Mode:') }}</strong> 
                                        <span class="badge badge-success">{{ $preset->tp_mode }}</span>
                                    </p>
                                    @if($preset->tp_mode === 'SINGLE' && $preset->tp1_enabled)
                                        <p><strong>{{ __('TP1 R:R:') }}</strong> {{ $preset->tp1_rr }}</p>
                                        <p><strong>{{ __('TP1 Close %:') }}</strong> {{ $preset->tp1_close_pct }}%</p>
                                    @elseif($preset->tp_mode === 'MULTI')
                                        @if($preset->tp1_enabled)
                                            <p><strong>{{ __('TP1:') }}</strong> {{ $preset->tp1_rr }}R, {{ $preset->tp1_close_pct }}%</p>
                                        @endif
                                        @if($preset->tp2_enabled)
                                            <p><strong>{{ __('TP2:') }}</strong> {{ $preset->tp2_rr }}R, {{ $preset->tp2_close_pct }}%</p>
                                        @endif
                                        @if($preset->tp3_enabled)
                                            <p><strong>{{ __('TP3:') }}</strong> {{ $preset->tp3_rr }}R, {{ $preset->tp3_close_pct }}%</p>
                                        @endif
                                        @if($preset->close_remaining_at_tp3)
                                            <p><span class="badge badge-info">{{ __('Close remaining at TP3') }}</span></p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Advanced Features --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-cogs mr-2"></i>
                                {{ __('Advanced Features') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>{{ __('Break-Even:') }}</strong> 
                                        @if($preset->be_enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            <br><small>{{ __('Trigger:') }} {{ $preset->be_trigger_rr }}R, {{ __('Offset:') }} {{ $preset->be_offset_pips ?? 0 }} pips</small>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </p>
                                    <p><strong>{{ __('Trailing Stop:') }}</strong> 
                                        @if($preset->ts_enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            <br><small>{{ __('Mode:') }} {{ $preset->ts_mode }}, {{ __('Trigger:') }} {{ $preset->ts_trigger_rr }}R</small>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ __('Layering:') }}</strong> 
                                        @if($preset->layering_enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            <br><small>{{ __('Max Layers:') }} {{ $preset->max_layers_per_symbol }}, {{ __('Distance:') }} {{ $preset->layer_distance_pips ?? '-' }} pips</small>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </p>
                                    <p><strong>{{ __('Hedging:') }}</strong> 
                                        @if($preset->hedging_enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Trading Schedule & Weekly Target --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-calendar-alt mr-2"></i>
                                {{ __('Trading Schedule & Weekly Target') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>{{ __('Only Trade in Session:') }}</strong> 
                                        {{ $preset->only_trade_in_session ? __('Yes') : __('No') }}
                                    </p>
                                    @if($preset->only_trade_in_session)
                                        <p><strong>{{ __('Session Profile:') }}</strong> {{ $preset->session_profile }}</p>
                                        @if($preset->session_profile === 'CUSTOM')
                                            <p><strong>{{ __('Trading Hours:') }}</strong> 
                                                {{ $preset->trading_hours_start ? \Carbon\Carbon::parse($preset->trading_hours_start)->format('H:i') : '-' }} - 
                                                {{ $preset->trading_hours_end ? \Carbon\Carbon::parse($preset->trading_hours_end)->format('H:i') : '-' }}
                                            </p>
                                            <p><strong>{{ __('Timezone:') }}</strong> {{ $preset->trading_timezone ?? 'SERVER' }}</p>
                                        @endif
                                        <p><strong>{{ __('Trading Days:') }}</strong>
                                            @php
                                                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                                $mask = $preset->trading_days_mask ?? 31;
                                                $selectedDays = [];
                                                for($i = 0; $i < 7; $i++) {
                                                    $bit = $i == 6 ? 64 : (1 << $i);
                                                    if(($mask & $bit) != 0) {
                                                        $selectedDays[] = $days[$i];
                                                    }
                                                }
                                            @endphp
                                            {{ implode(', ', $selectedDays) }}
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p><strong>{{ __('Weekly Target:') }}</strong> 
                                        @if($preset->weekly_target_enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            <br><small>{{ __('Target:') }} {{ $preset->weekly_target_profit_pct }}%</small>
                                            <br><small>{{ __('Reset Day:') }} 
                                                @php
                                                    $resetDays = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
                                                @endphp
                                                {{ $resetDays[$preset->weekly_reset_day] ?? '-' }}
                                            </small>
                                            <br><small>{{ __('Auto Stop:') }} {{ $preset->auto_stop_on_weekly_target ? __('Yes') : __('No') }}</small>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Usage Statistics --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-chart-bar mr-2"></i>
                                {{ __('Usage Statistics') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="sp_site_card text-center">
                                        <h5 class="mb-1">{{ __('Connections') }}</h5>
                                        <span class="fw-semibold fs-4">{{ $usage['connections'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="sp_site_card text-center">
                                        <h5 class="mb-1">{{ __('Subscriptions') }}</h5>
                                        <span class="fw-semibold fs-4">{{ $usage['subscriptions'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="sp_site_card text-center">
                                        <h5 class="mb-1">{{ __('Users (Default)') }}</h5>
                                        <span class="fw-semibold fs-4">{{ $usage['users_with_default'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

