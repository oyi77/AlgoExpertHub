@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><i class="fas fa-trophy"></i> Trader Marketplace & Leaderboard</h3>
                        <p class="text-muted mb-0">Manage top traders and moderate the trading marketplace</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.trading-management.copy-trading.index') }}" class="btn btn-secondary mr-2">
                            <i class="fa fa-arrow-left"></i> Back to Copy Trading
                        </a>
                        <form action="{{ route('admin.trading-management.marketplace.traders.recalculate') }}" 
                              method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-sync"></i> Recalculate Leaderboard
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Traders</h6>
                        <h3>{{ $traders->total() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Verified</h6>
                        <h3 class="text-success">{{ $traders->where('is_verified', true)->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Followers</h6>
                        <h3>{{ $traders->sum('follower_count') }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Avg Win Rate</h6>
                        <h3>{{ number_format($traders->avg('win_rate'), 1) }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-trophy"></i> Top Traders</h5>
                
                @if($traders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Trader</th>
                                    <th>Win Rate</th>
                                    <th>Total Profit</th>
                                    <th>Trades</th>
                                    <th>Followers</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($traders as $index => $trader)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <i class="fas fa-medal text-warning"></i>
                                            @endif
                                            {{ $traders->firstItem() + $index }}
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.user.details', $trader->id) }}">
                                                <strong>{{ $trader->username }}</strong>
                                            </a>
                                            @if($trader->is_verified)
                                                <i class="fas fa-check-circle text-primary" title="Verified"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $trader->win_rate >= 60 ? 'success' : ($trader->win_rate >= 50 ? 'warning' : 'danger') }}">
                                                {{ number_format($trader->win_rate, 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="{{ $trader->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $trader->total_profit >= 0 ? '+' : '' }}{{ number_format($trader->total_profit, 2) }}%
                                            </span>
                                        </td>
                                        <td>{{ $trader->total_trades }}</td>
                                        <td>{{ $trader->follower_count }}</td>
                                        <td>
                                            <i class="fas fa-star text-warning"></i> 
                                            {{ number_format($trader->rating ?? 0, 1) }}
                                        </td>
                                        <td>
                                            @if($trader->is_verified)
                                                <span class="badge badge-success">Verified</span>
                                            @else
                                                <span class="badge badge-secondary">Unverified</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.trading-management.marketplace.traders.show', $trader->id) }}" 
                                                   class="btn btn-info" title="View Profile">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(!$trader->is_verified)
                                                    <form action="{{ route('admin.trading-management.marketplace.traders.verify', $trader->id) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success" title="Verify Trader">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('admin.trading-management.marketplace.traders.destroy', $trader->id) }}" 
                                                      method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Remove this trader from marketplace?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Remove">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $traders->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                        <h5>No Traders Found</h5>
                        <p class="text-muted">No traders in the marketplace yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

