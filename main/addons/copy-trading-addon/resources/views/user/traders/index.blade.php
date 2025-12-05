@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchTrader" placeholder="Search traders...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="sortBy">
                                    <option value="">Sort by...</option>
                                    <option value="followers">Most Followers</option>
                                    <option value="winrate">Highest Win Rate</option>
                                    <option value="pnl">Best P&L</option>
                                </select>
                            </div>
                        </div>

                        @if($traders->count() > 0)
                        <div class="row">
                            @foreach($traders as $trader)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">{{ $trader->user->username ?? $trader->user->email ?? 'Trader #' . $trader->user_id }}</h5>
                                            @if($trader->is_following)
                                            <span class="badge badge-success">Following</span>
                                            @endif
                                        </div>

                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Followers</small>
                                                    <div class="font-weight-bold">{{ $trader->stats['follower_count'] ?? 0 }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Win Rate</small>
                                                    <div class="font-weight-bold {{ ($trader->stats['win_rate'] ?? 0) >= 50 ? 'text-success' : 'text-warning' }}">
                                                        {{ number_format($trader->stats['win_rate'] ?? 0, 2) }}%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Total P&L</small>
                                                    <div class="font-weight-bold {{ ($trader->stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ ($trader->stats['total_pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($trader->stats['total_pnl'] ?? 0, 2) }}
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Trades</small>
                                                    <div class="font-weight-bold">{{ $trader->stats['total_trades'] ?? 0 }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-auto">
                                            <a href="{{ route('user.copy-trading.traders.show', $trader->user_id) }}" class="btn btn-primary btn-block">
                                                View Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            {{ $traders->links() }}
                        </div>
                        @else
                        <div class="alert alert-info">
                            No traders available at the moment.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
