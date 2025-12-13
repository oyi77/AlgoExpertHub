@extends('backend.layout.master')

@section('element')
<div class="row">
    <!-- Statistics Overview -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Bots</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-robot fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active'] }}</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">User Bots</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['user_bots'] }}</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Admin Bots</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['admin_bots'] }}</div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-user-shield fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 font-weight-bold">
                            <i class="fas fa-robot text-primary"></i> {{ $title }}
                        </h5>
                        <small class="text-muted">Manage trading bots (admin and user bots)</small>
                    </div>
                    <a href="{{ route('admin.trading-management.trading-bots.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Trading Bot
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs border-bottom" role="tablist">
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

                <div class="tab-content p-4">
                    <div class="tab-pane fade show active" id="tab-bots" role="tabpanel">
                        <!-- Filters -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <form method="GET" class="row">
                                    <div class="col-md-3 mb-2">
                                        <input type="text" name="search" class="form-control" placeholder="Search bots..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <select name="is_active" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <select name="user_id" class="form-control">
                                            <option value="">All Users</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->username }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <select name="admin_id" class="form-control">
                                            <option value="">All Admins</option>
                                            @foreach($admins as $admin)
                                                <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                                                    {{ $admin->username }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Bots Cards -->
                        @if($bots->count() > 0)
                            <div class="row">
                                @foreach($bots as $bot)
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card connection-card h-100 shadow-sm border-0 {{ $bot->is_active ? 'border-left-success' : 'border-left-secondary' }}" style="border-left-width: 4px !important;">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="font-weight-bold mb-1">
                                                        {{ $bot->name }}
                                                        @if($bot->is_paper_trading)
                                                            <span class="badge badge-warning badge-sm ml-1">Demo</span>
                                                        @endif
                                                    </h6>
                                                    <div class="mb-2">
                                                        @if($bot->admin)
                                                            <span class="badge badge-info badge-sm">Admin: {{ $bot->admin->username }}</span>
                                                        @elseif($bot->user)
                                                            <span class="badge badge-primary badge-sm">User: {{ $bot->user->username }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="status-indicator">
                                                    @if($bot->is_active)
                                                        <span class="badge badge-success badge-sm">
                                                            <i class="fas fa-circle"></i> Active
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary badge-sm">
                                                            <i class="fas fa-pause-circle"></i> Inactive
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-exchange-alt text-muted mr-2"></i>
                                                    <small class="text-muted">{{ $bot->exchangeConnection->name ?? 'N/A' }}</small>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-cog text-muted mr-2"></i>
                                                    <small class="text-muted">{{ $bot->tradingPreset->name ?? 'N/A' }}</small>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-4 text-center">
                                                    <div class="text-xs text-muted">Executions</div>
                                                    <div class="font-weight-bold">{{ $bot->total_executions }}</div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="text-xs text-muted">Win Rate</div>
                                                    <div class="font-weight-bold text-{{ $bot->win_rate >= 50 ? 'success' : 'warning' }}">
                                                        {{ number_format($bot->win_rate, 1) }}%
                                                    </div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="text-xs text-muted">Profit</div>
                                                    <div class="font-weight-bold {{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                        ${{ number_format($bot->total_profit, 2) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('admin.trading-management.trading-bots.show', $bot->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.trading-management.trading-bots.edit', $bot->id) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.trading-management.trading-bots.toggle-active', $bot->id) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-{{ $bot->is_active ? 'warning' : 'success' }}" 
                                                            title="{{ $bot->is_active ? 'Pause' : 'Start' }}">
                                                        <i class="fa fa-{{ $bot->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-warning" 
                                                        title="Transfer Ownership" 
                                                        data-toggle="modal" 
                                                        data-target="#transferModal{{ $bot->id }}">
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                                <form action="{{ route('admin.trading-management.trading-bots.destroy', $bot->id) }}" 
                                                      method="POST" 
                                                      class="d-inline delete-bot-form"
                                                      data-confirm-message="Are you sure you want to delete this trading bot? This action cannot be undone.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="mt-4">
                                {{ $bots->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-robot fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted mb-2">No Trading Bots Found</h5>
                                <p class="text-muted mb-4">Create your first trading bot to start automated trading!</p>
                                <a href="{{ route('admin.trading-management.trading-bots.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Create Trading Bot
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-secondary { border-left: 4px solid #858796 !important; }

    .connection-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .connection-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }

    .status-indicator .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle delete bot form with confirmation
        $('.delete-bot-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const message = form.data('confirm-message') || 'Are you sure?';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '{{ __("Confirmation") }}',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __("Delete") }}',
                    cancelButtonText: '{{ __("Cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit();
                    }
                });
            } else {
                if (confirm(message)) {
                    form.off('submit').submit();
                }
            }
            
            return false;
        });
    });
</script>
@endpush
@endsection
