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
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Trader</th>
                                        <th>Win Rate</th>
                                        <th>Total PnL</th>
                                        <th>Followers</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($traders as $trader)
                                        <tr>
                                            <td>{{ $trader->user->username ?? $trader->user->email }}</td>
                                            <td>{{ number_format($trader->stats['win_rate'] ?? 0, 2) }}%</td>
                                            <td class="{{ ($trader->stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($trader->stats['total_pnl'] ?? 0, 2) }}
                                            </td>
                                            <td>{{ $trader->stats['follower_count'] ?? 0 }}</td>
                                            <td>
                                                @if($trader->is_following)
                                                    <span class="badge badge-info">Following</span>
                                                @else
                                                    <span class="badge badge-secondary">Not Following</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('user.copy-trading.traders.show', $trader->user_id) }}" 
                                                    class="btn btn-sm btn-info">View</a>
                                                @if(!$trader->is_following)
                                                    <a href="{{ route('user.copy-trading.subscriptions.create', $trader->user_id) }}" 
                                                        class="btn btn-sm btn-primary">Subscribe</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No traders available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($traders->hasPages())
                            <div class="mt-3">
                                {{ $traders->links() }}
                            </div>
                        @endif
        </div>
    </div>
@endsection

