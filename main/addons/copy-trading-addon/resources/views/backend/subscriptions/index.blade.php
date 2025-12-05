@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>{{ $title }}</h4>
                        <div>
                            <a href="{{ route('admin.copy-trading.subscriptions.index') }}" class="btn btn-sm btn-outline-primary {{ !request('status') ? 'active' : '' }}">
                                All
                            </a>
                            <a href="{{ route('admin.copy-trading.subscriptions.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-success {{ request('status') === 'active' ? 'active' : '' }}">
                                Active
                            </a>
                            <a href="{{ route('admin.copy-trading.subscriptions.index', ['status' => 'inactive']) }}" class="btn btn-sm btn-outline-secondary {{ request('status') === 'inactive' ? 'active' : '' }}">
                                Inactive
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Trader</th>
                                        <th>Follower</th>
                                        <th>Copy Mode</th>
                                        <th>Risk Multiplier</th>
                                        <th>Connection</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->id }}</td>
                                        <td>
                                            @if($subscription->trader)
                                                {{ $subscription->trader->username ?? $subscription->trader->email ?? 'User #' . $subscription->trader_id }}
                                            @else
                                                User #{{ $subscription->trader_id }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($subscription->follower)
                                                {{ $subscription->follower->username ?? $subscription->follower->email ?? 'User #' . $subscription->follower_id }}
                                            @else
                                                User #{{ $subscription->follower_id }}
                                            @endif
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
                                                <span class="text-muted">{{ ucfirst($subscription->copy_method ?? 'N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($subscription->connection)
                                                <span class="badge badge-secondary">
                                                    {{ $subscription->connection->name ?? 'Connection #' . $subscription->connection_id }}
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                                {{ $subscription->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            {{ $subscriptions->appends(request()->query())->links() }}
                        </div>
                        @else
                        <div class="alert alert-info">
                            No subscriptions found.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

