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
                        @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Trader</th>
                                        <th>Copy Mode</th>
                                        <th>Risk Multiplier</th>
                                        <th>Connection</th>
                                        <th>Status</th>
                                        <th>Started</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                    <tr>
                                        <td>
                                            <a href="{{ route('user.copy-trading.traders.show', $subscription->trader_id) }}">
                                                {{ $subscription->trader->username ?? $subscription->trader->email ?? 'Trader #' . $subscription->trader_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ ucfirst($subscription->copy_mode ?? 'easy') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($subscription->copy_mode === 'easy')
                                                {{ $subscription->risk_multiplier ?? 1.0 }}x
                                            @else
                                                {{ ucfirst($subscription->copy_method ?? 'N/A') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($subscription->connection)
                                                {{ $subscription->connection->name ?? 'Connection #' . $subscription->connection_id }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                                {{ $subscription->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <a href="{{ route('user.copy-trading.subscriptions.edit', $subscription->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('user.copy-trading.subscriptions.destroy', $subscription->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unfollow this trader?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            {{ $subscriptions->links() }}
                        </div>
                        @else
                        <div class="alert alert-info">
                            You are not following any traders yet. <a href="{{ route('user.copy-trading.traders.index') }}">Browse traders</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
