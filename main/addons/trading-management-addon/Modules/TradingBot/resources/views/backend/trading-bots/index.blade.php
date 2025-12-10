@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header with Tabs -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3><i class="fas fa-robot"></i> {{ $title }}</h3>
                        <p class="text-muted mb-0">Manage trading bots (admin and user bots)</p>
                    </div>
                    <a href="{{ route('admin.trading-management.trading-bots.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Trading Bot
                    </a>
                </div>
                
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-bots" role="tab">
                            <i class="fas fa-robot"></i> All Bots
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.trading-management.marketplace.bots.index') }}">
                            <i class="fas fa-store"></i> Bot Marketplace
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-bots" role="tabpanel">

        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Bots</h6>
                        <h3>{{ $stats['total'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Active</h6>
                        <h3 class="text-success">{{ $stats['active'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">User Bots</h6>
                        <h3>{{ $stats['user_bots'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Admin Bots</h6>
                        <h3>{{ $stats['admin_bots'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search bots..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="is_active" class="form-control">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="user_id" class="form-control">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="admin_id" class="form-control">
                            <option value="">All Admins</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bots Table -->
        <div class="card">
            <div class="card-body">
                @if($bots->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Owner</th>
                                    <th>Exchange</th>
                                    <th>Preset</th>
                                    <th>Status</th>
                                    <th>Executions</th>
                                    <th>Win Rate</th>
                                    <th>Profit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bots as $bot)
                                    <tr>
                                        <td>{{ $bot->id }}</td>
                                        <td>
                                            <strong>{{ $bot->name }}</strong>
                                            @if($bot->is_paper_trading)
                                                <span class="badge bg-warning">Demo</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bot->admin)
                                                <span class="badge bg-info">Admin: {{ $bot->admin->username }}</span>
                                            @elseif($bot->user)
                                                <span class="badge bg-primary">User: {{ $bot->user->username }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $bot->exchangeConnection->name ?? 'N/A' }}</td>
                                        <td>{{ $bot->tradingPreset->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($bot->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $bot->total_executions }}</td>
                                        <td>{{ number_format($bot->win_rate, 1) }}%</td>
                                        <td class="{{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($bot->total_profit, 2) }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.trading-management.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-{{ $bot->is_active ? 'warning' : 'success' }}" title="{{ $bot->is_active ? 'Pause' : 'Start' }}">
                                                        <i class="fa fa-{{ $bot->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-warning" title="Transfer Ownership" data-toggle="modal" data-target="#transferModal{{ $bot->id }}">
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                                <form action="{{ route('admin.trading-management.trading-bots.destroy', $bot->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trading bot? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Transfer Ownership Modal -->
                                            <div class="modal fade" id="transferModal{{ $bot->id }}" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel{{ $bot->id }}" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="transferModalLabel{{ $bot->id }}">Transfer Ownership - {{ $bot->name }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form action="{{ route('admin.trading-management.trading-bots.transfer-ownership', $bot->id) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label for="user_id{{ $bot->id }}">Select User</label>
                                                                    <select name="user_id" id="user_id{{ $bot->id }}" class="form-control" required>
                                                                        <option value="">-- Select User --</option>
                                                                        @foreach(\App\Models\User::orderBy('username')->get() as $user)
                                                                            <option value="{{ $user->id }}" {{ $bot->user_id == $user->id ? 'selected' : '' }}>
                                                                                {{ $user->username }} ({{ $user->email }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <small class="form-text text-muted">Select the user who will own this trading bot.</small>
                                                                </div>
                                                                <div class="alert alert-info">
                                                                    <strong>Current Owner:</strong><br>
                                                                    @if($bot->admin)
                                                                        <span class="badge badge-info">Admin: {{ $bot->admin->username }}</span>
                                                                    @elseif($bot->user)
                                                                        <span class="badge badge-success">User: {{ $bot->user->username }} ({{ $bot->user->email }})</span>
                                                                    @else
                                                                        <span class="badge badge-secondary">No Owner</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Transfer Ownership</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $bots->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa fa-robot fa-3x text-muted mb-3"></i>
                        <h5>No Trading Bots Found</h5>
                        <p class="text-muted">Create your first trading bot to start automated trading!</p>
                        <a href="{{ route('admin.trading-management.trading-bots.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create Trading Bot
                        </a>
                    </div>
                @endif
            </div>
        </div>
            </div><!-- End tab-bots -->
        </div><!-- End tab-content -->
    </div>
</div>
@endsection
