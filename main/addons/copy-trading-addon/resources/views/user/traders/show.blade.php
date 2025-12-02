@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }} - {{ $trader->username ?? $trader->email }}</h4>
        </div>
        <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Statistics</h5>
                                <p>Win Rate: <strong>{{ number_format($stats['win_rate'] ?? 0, 2) }}%</strong></p>
                                <p>Total PnL: <strong class="{{ ($stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($stats['total_pnl'] ?? 0, 2) }}
                                </strong></p>
                                <p>Total Trades: <strong>{{ $stats['total_trades'] ?? 0 }}</strong></p>
                                <p>Followers: <strong>{{ $stats['follower_count'] ?? 0 }}</strong></p>
                            </div>
                        </div>

                        @if(!$is_following)
                            <a href="{{ route('user.copy-trading.subscriptions.create', $trader->id) }}" 
                                class="btn btn-primary">Subscribe to This Trader</a>
                        @else
                            <span class="badge badge-info">You are already following this trader</span>
                        @endif
                    </div>
                </div>
        </div>
    </div>
@endsection

