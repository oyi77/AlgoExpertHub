@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __($title) }}</h4>
                </div>
                <div class="card-body">
                    @if($traders->count() > 0)
                        <div class="row">
                            @foreach($traders as $trader)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">
                                                    {{ $trader->display_name ?? $trader->user->username ?? 'Trader #' . $trader->user_id }}
                                                </h5>
                                                @if($trader->verified)
                                                    <span class="badge badge-success">{{ __('Verified') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if($trader->bio)
                                                <p class="text-muted">{{ Str::limit($trader->bio, 100) }}</p>
                                            @endif
                                            
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Followers') }}</small>
                                                    <div class="fw-bold">{{ $trader->total_followers ?? 0 }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Win Rate') }}</small>
                                                    <div class="fw-bold {{ ($trader->win_rate ?? 0) >= 50 ? 'text-success' : 'text-warning' }}">
                                                        {{ number_format($trader->win_rate ?? 0, 2) }}%
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Total Profit') }}</small>
                                                    <div class="fw-bold {{ ($trader->total_profit_percent ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ ($trader->total_profit_percent ?? 0) >= 0 ? '+' : '' }}{{ number_format($trader->total_profit_percent ?? 0, 2) }}%
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">{{ __('Trades') }}</small>
                                                    <div class="fw-bold">{{ $trader->trades_count ?? 0 }}</div>
                                                </div>
                                            </div>
                                            
                                            @if($trader->subscription_price > 0)
                                                <div class="mb-2">
                                                    <small class="text-muted">{{ __('Subscription Price') }}</small>
                                                    <div class="fw-bold">{{ number_format($trader->subscription_price, 2) }} {{ $trader->currency ?? 'USD' }}</div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer">
                                            @if(Route::has('user.copy-trading.traders.show'))
                                                <a href="{{ route('user.copy-trading.traders.show', $trader->user_id) }}" class="btn btn-primary btn-sm">
                                                    {{ __('View Profile') }}
                                                </a>
                                            @else
                                                <span class="text-muted small">{{ __('Profile view coming soon') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($traders->hasPages())
                            <div class="mt-3">
                                {{ $traders->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No traders available at the moment.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
