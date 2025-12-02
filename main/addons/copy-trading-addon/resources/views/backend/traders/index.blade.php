@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $title }}</h4>
                        <div class="btn-group">
                            <a href="{{ route('admin.copy-trading.traders.index', ['type' => 'user']) }}" 
                                class="btn btn-sm btn-outline-primary">Users</a>
                            <a href="{{ route('admin.copy-trading.traders.index', ['type' => 'admin']) }}" 
                                class="btn btn-sm btn-outline-primary">Admins</a>
                            <a href="{{ route('admin.copy-trading.traders.index') }}" 
                                class="btn btn-sm btn-outline-secondary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">
                                {{ $error }}
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Trader</th>
                                        <th>Type</th>
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
                                            <td>{{ $trader->name ?? ($trader->is_admin_owned ? 'Admin #' . ($trader->admin_id ?? 'N/A') : 'User #' . ($trader->user_id ?? 'N/A')) }}</td>
                                            <td>
                                                <span class="badge badge-{{ ($trader->type ?? ($trader->is_admin_owned ? 'admin' : 'user')) === 'admin' ? 'info' : 'success' }}">
                                                    {{ ucfirst($trader->type ?? ($trader->is_admin_owned ? 'admin' : 'user')) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format(($trader->stats['win_rate'] ?? $trader->stats['winRate'] ?? 0), 2) }}%</td>
                                            <td class="{{ (($trader->stats['total_pnl'] ?? $trader->stats['totalPnL'] ?? 0) >= 0 ? 'text-success' : 'text-danger') }}">
                                                ${{ number_format(($trader->stats['total_pnl'] ?? $trader->stats['totalPnL'] ?? 0), 2) }}
                                            </td>
                                            <td>{{ $trader->stats['follower_count'] ?? $trader->stats['followerCount'] ?? 0 }}</td>
                                            <td>
                                                @if(isset($trader->is_enabled) && $trader->is_enabled)
                                                    <span class="badge badge-success">Enabled</span>
                                                @else
                                                    <span class="badge badge-secondary">Disabled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.copy-trading.traders.show', $trader->id) }}" 
                                                    class="btn btn-sm btn-info">View</a>
                                                <form action="{{ route('admin.copy-trading.traders.toggle', $trader->id) }}" 
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-{{ (isset($trader->is_enabled) && $trader->is_enabled) ? 'warning' : 'success' }}">
                                                        {{ (isset($trader->is_enabled) && $trader->is_enabled) ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No traders found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($traders, 'links'))
                            {{ $traders->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

