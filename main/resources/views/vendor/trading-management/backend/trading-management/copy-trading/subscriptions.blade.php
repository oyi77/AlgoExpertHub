@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-link"></i> Copy Trading Subscriptions</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="trader_id" class="form-control">
                                <option value="">All Traders</option>
                                @foreach($traders as $trader)
                                <option value="{{ $trader->id }}" {{ request('trader_id') == $trader->id ? 'selected' : '' }}>
                                    {{ $trader->username }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="follower_id" class="form-control">
                                <option value="">All Followers</option>
                                @foreach($followers as $follower)
                                <option value="{{ $follower->id }}" {{ request('follower_id') == $follower->id ? 'selected' : '' }}>
                                    {{ $follower->username }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.copy-trading.subscriptions') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($subscriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Trader</th>
                                <th>Follower</th>
                                <th>Copy Mode</th>
                                <th>Risk Multiplier</th>
                                <th>Connection</th>
                                <th>Subscribed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subscriptions as $sub)
                            <tr>
                                <td><strong>{{ $sub->trader->username ?? 'N/A' }}</strong></td>
                                <td>{{ $sub->follower->username ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ strtoupper($sub->copy_mode) }}</span>
                                </td>
                                <td>{{ $sub->risk_multiplier }}x</td>
                                <td>{{ $sub->executionConnection->name ?? 'N/A' }}</td>
                                <td>{{ $sub->subscribed_at->format('Y-m-d') }}</td>
                                <td>
                                    @if($sub->is_active)
                                    <span class="badge badge-success">Active</span>
                                    @else
                                    <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <form action="{{ route('admin.trading-management.copy-trading.subscriptions.toggle', $sub) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $sub->is_active ? 'btn-warning' : 'btn-success' }}">
                                                <i class="fas {{ $sub->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.trading-management.copy-trading.subscriptions.destroy', $sub) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $subscriptions->links() }}
                @else
                <div class="alert alert-info">No subscriptions found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

