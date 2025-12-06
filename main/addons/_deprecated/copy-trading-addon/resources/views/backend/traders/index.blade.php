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
                            <a href="{{ route('admin.copy-trading.traders.index') }}" class="btn btn-sm btn-outline-primary {{ !request('type') ? 'active' : '' }}">
                                All
                            </a>
                            <a href="{{ route('admin.copy-trading.traders.index', ['type' => 'user']) }}" class="btn btn-sm btn-outline-primary {{ request('type') === 'user' ? 'active' : '' }}">
                                Users
                            </a>
                            <a href="{{ route('admin.copy-trading.traders.index', ['type' => 'admin']) }}" class="btn btn-sm btn-outline-danger {{ request('type') === 'admin' ? 'active' : '' }}">
                                Admins
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($traders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Trader</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Followers</th>
                                        <th>Copied Trades</th>
                                        <th>Win Rate</th>
                                        <th>Total P&L</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($traders as $trader)
                                    <tr>
                                        <td>{{ $trader->id }}</td>
                                        <td>{{ $trader->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $trader->type === 'admin' ? 'danger' : 'primary' }}">
                                                {{ ucfirst($trader->type ?? 'N/A') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $trader->is_enabled ? 'success' : 'secondary' }}">
                                                {{ $trader->is_enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td>{{ $trader->stats['follower_count'] ?? 0 }}</td>
                                        <td>{{ $trader->stats['total_copied_trades'] ?? 0 }}</td>
                                        <td>
                                            <span class="badge badge-{{ ($trader->stats['win_rate'] ?? 0) >= 50 ? 'success' : 'warning' }}">
                                                {{ number_format($trader->stats['win_rate'] ?? 0, 2) }}%
                                            </span>
                                        </td>
                                        <td class="{{ ($trader->stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ ($trader->stats['total_pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($trader->stats['total_pnl'] ?? 0, 2) }}
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.copy-trading.traders.show', $trader->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.copy-trading.traders.toggle', $trader->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-{{ $trader->is_enabled ? 'warning' : 'success' }}" 
                                                        onclick="return confirm('Are you sure you want to {{ $trader->is_enabled ? 'disable' : 'enable' }} this trader?')">
                                                    <i class="fas fa-{{ $trader->is_enabled ? 'ban' : 'check' }}"></i>
                                                </button>
                                            </form>
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
                        <div class="alert alert-info">
                            No traders found.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
