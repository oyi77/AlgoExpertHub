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
                                        <th>Copy Mode</th>
                                        <th>Connection</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subscriptions as $subscription)
                                        <tr>
                                            <td>{{ $subscription->trader->username ?? $subscription->trader->email }}</td>
                                            <td>
                                                <span class="badge badge-{{ $subscription->copy_mode === 'easy' ? 'primary' : 'info' }}">
                                                    {{ ucfirst($subscription->copy_mode) }}
                                                </span>
                                            </td>
                                            <td>{{ $subscription->connection->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                                    {{ $subscription->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('user.copy-trading.subscriptions.edit', $subscription->id) }}" 
                                                    class="btn btn-sm btn-info">Edit</a>
                                                <form action="{{ route('user.copy-trading.subscriptions.destroy', $subscription->id) }}" 
                                                    method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Unsubscribe</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No subscriptions found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
        </div>
    </div>
@endsection

