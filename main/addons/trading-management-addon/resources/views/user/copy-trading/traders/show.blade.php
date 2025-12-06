@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __('Trader Profile') }}</h4>
                        <a href="{{ route('user.copy-trading.traders.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Back to Traders') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <h5>{{ $trader->display_name ?? $trader->user->username ?? 'Trader #' . $trader->user_id }}</h5>
                                @if($trader->verified)
                                    <span class="badge badge-success">{{ __('Verified') }}</span>
                                @endif
                                @if($trader->bio)
                                    <p class="text-muted mt-2">{{ $trader->bio }}</p>
                                @endif
                            </div>
                            
                            @if(isset($isFollowing))
                                @if($isFollowing)
                                    <div class="alert alert-info">
                                        <i class="fa fa-check"></i> {{ __('You are following this trader') }}
                                    </div>
                                @else
                                    <a href="{{ route('user.copy-trading.subscriptions.create', $trader->user_id) }}" class="btn btn-primary btn-block">
                                        <i class="fa fa-plus"></i> {{ __('Follow Trader') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                        
                        <div class="col-md-8">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Followers') }}</h6>
                                        <span class="fw-semibold fs-4">{{ $trader->total_followers ?? 0 }}</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Win Rate') }}</h6>
                                        <span class="fw-semibold fs-4 {{ ($trader->win_rate ?? 0) >= 50 ? 'text-success' : 'text-warning' }}">
                                            {{ number_format($trader->win_rate ?? 0, 2) }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Total Profit') }}</h6>
                                        <span class="fw-semibold fs-4 {{ ($trader->total_profit_percent ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ ($trader->total_profit_percent ?? 0) >= 0 ? '+' : '' }}{{ number_format($trader->total_profit_percent ?? 0, 2) }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="sp_site_card text-center">
                                        <h6>{{ __('Trades') }}</h6>
                                        <span class="fw-semibold fs-4">{{ $trader->trades_count ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            @if($trader->avg_monthly_return)
                                <div class="mb-3">
                                    <strong>{{ __('Average Monthly Return') }}:</strong> 
                                    <span class="{{ $trader->avg_monthly_return >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ ($trader->avg_monthly_return >= 0 ? '+' : '') }}{{ number_format($trader->avg_monthly_return, 2) }}%
                                    </span>
                                </div>
                            @endif
                            
                            @if($trader->max_drawdown)
                                <div class="mb-3">
                                    <strong>{{ __('Max Drawdown') }}:</strong> 
                                    <span class="text-danger">{{ number_format($trader->max_drawdown, 2) }}%</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
