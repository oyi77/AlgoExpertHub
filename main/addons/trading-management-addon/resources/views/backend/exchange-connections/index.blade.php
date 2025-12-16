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
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Connections</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $connections->total() }}</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-plug fa-2x"></i>
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
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $connections->where('status', 'active')->count() }}
                                </div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
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
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Inactive</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $connections->where('status', 'inactive')->count() }}
                                </div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-pause-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-danger shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Errors</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $connections->where('status', 'error')->count() }}
                                </div>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Flow Overview -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold">
                        <i class="fas fa-project-diagram text-primary"></i> Complete Trading Flow
                    </h5>
                    <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New Connection
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="trading-flow-container">
                    <div class="flow-step">
                        <div class="step-icon bg-primary">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">1. Fetch Data</h6>
                            <small class="text-muted">Exchange → Market Data</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-info">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">2. Parse</h6>
                            <small class="text-muted">Extract Signals</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-warning">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">3. Aggregate</h6>
                            <small class="text-muted">Store & Combine</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-primary">
                            <i class="fas fa-filter"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">4. Filter</h6>
                            <small class="text-muted">Technical Indicators</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-info">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">5. AI Analyze</h6>
                            <small class="text-muted">Market Confirmation</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-warning">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">6. Risk Mgmt</h6>
                            <small class="text-muted">Position Sizing</small>
                        </div>
                    </div>
                    <div class="flow-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flow-step">
                        <div class="step-icon bg-success">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="step-content">
                            <h6 class="font-weight-bold mb-1">7. Execute</h6>
                            <small class="text-muted">Place Trade</small>
                        </div>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0">
                    <i class="fas fa-info-circle text-primary"></i> 
                    <strong>Unified Flow:</strong> One connection handles both data fetching (steps 1-5) and trade execution (steps 6-7). Enable/disable features as needed.
                </div>
            </div>
        </div>
    </div>

    <!-- Connections List -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold">
                        <i class="fas fa-plug text-primary"></i> Exchange Connections
                    </h5>
                    <div>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Connection
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($connections->count() > 0)
                    <div class="row">
                        @foreach($connections as $conn)
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card connection-card h-100 shadow-sm border-0 {{ $conn->status === 'active' ? 'border-left-success' : ($conn->status === 'error' ? 'border-left-danger' : 'border-left-warning') }}" style="border-left-width: 4px !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="font-weight-bold mb-1">
                                                {{ $conn->name }}
                                                @if($conn->is_admin_owned)
                                                    <span class="badge badge-info badge-sm ml-1">Admin</span>
                                                @endif
                                            </h6>
                                            <div class="mb-2">
                                                <span class="badge {{ $conn->connection_type === 'CRYPTO_EXCHANGE' ? 'badge-primary' : 'badge-success' }} badge-sm">
                                                    <i class="fas fa-{{ $conn->connection_type === 'CRYPTO_EXCHANGE' ? 'coins' : 'chart-line' }}"></i>
                                                    {{ $conn->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto' : 'Forex' }}
                                                </span>
                                                <span class="badge badge-secondary badge-sm ml-1">
                                                    {{ strtoupper($conn->provider) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="status-indicator">
                                            @if($conn->status === 'active')
                                                <span class="badge badge-success badge-sm">
                                                    <i class="fas fa-circle"></i> Active
                                                </span>
                                            @elseif($conn->status === 'error')
                                                <span class="badge badge-danger badge-sm">
                                                    <i class="fas fa-exclamation-circle"></i> Error
                                                </span>
                                            @else
                                                <span class="badge badge-warning badge-sm">
                                                    <i class="fas fa-pause-circle"></i> {{ ucfirst($conn->status) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-tag text-muted mr-2"></i>
                                            <span class="badge badge-info badge-sm">{{ $conn->getPurposeLabel() }}</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @if($conn->data_fetching_enabled)
                                                <span class="badge badge-light border">
                                                    <i class="fas fa-download text-primary"></i> Data
                                                </span>
                                            @endif
                                            @if($conn->trade_execution_enabled)
                                                <span class="badge badge-light border">
                                                    <i class="fas fa-bolt text-success"></i> Trading
                                                </span>
                                            @endif
                                            @if($conn->copy_trading_enabled)
                                                <span class="badge badge-light border">
                                                    <i class="fas fa-users text-info"></i> Copy
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($conn->last_data_fetch_at || $conn->last_trade_execution_at)
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            @if($conn->last_data_fetch_at)
                                                <i class="fas fa-clock text-primary"></i> Data: {{ $conn->last_data_fetch_at->diffForHumans() }}<br>
                                            @endif
                                            @if($conn->last_trade_execution_at)
                                                <i class="fas fa-clock text-success"></i> Trade: {{ $conn->last_trade_execution_at->diffForHumans() }}
                                            @endif
                                        </small>
                                    </div>
                                    @endif

                                    <div class="btn-group w-100" role="group">
                                        <a href="{{ route('admin.trading-management.config.exchange-connections.show', $conn) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View & Test">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $conn) }}" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning" 
                                                title="Transfer Ownership" 
                                                data-toggle="modal" 
                                                data-target="#transferModal{{ $conn->id }}">
                                            <i class="fas fa-user-friends"></i>
                                        </button>
                                        <form action="{{ route('admin.trading-management.config.exchange-connections.destroy', $conn) }}" 
                                              method="POST" 
                                              class="d-inline delete-connection-form"
                                              data-confirm-message="Are you sure you want to delete this connection? This action cannot be undone.">
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
                        <div class="modal fade" id="transferModal{{ $conn->id }}" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel{{ $conn->id }}" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="transferModalLabel{{ $conn->id }}">Transfer Ownership - {{ $conn->name }}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.trading-management.config.exchange-connections.transfer-ownership', $conn) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="user_id{{ $conn->id }}">Select User</label>
                                                <select name="user_id" id="user_id{{ $conn->id }}" class="form-control" required>
                                                    <option value="">-- Select User --</option>
                                                    @foreach(\App\Models\User::orderBy('username')->get() as $user)
                                                        <option value="{{ $user->id }}" {{ $conn->user_id == $user->id ? 'selected' : '' }}>
                                                            {{ $user->username }} ({{ $user->email }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">Select the user who will own this connection.</small>
                                            </div>
                                            <div class="alert alert-info">
                                                <strong>Current Owner:</strong><br>
                                                @if($conn->is_admin_owned)
                                                    <span class="badge badge-info">Admin</span>
                                                @elseif($conn->user)
                                                    <span class="badge badge-success">User: {{ $conn->user->username }} ({{ $conn->user->email }})</span>
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
                        {{ $connections->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-plug fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted mb-2">No Exchange Connections</h5>
                        <p class="text-muted mb-4">Get started by creating your first exchange connection.</p>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Connection
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-danger { border-left: 4px solid #e74a3b !important; }

    .trading-flow-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 20px 0;
    }

    .flow-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        min-width: 120px;
        max-width: 140px;
    }

    .step-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin-bottom: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .step-content {
        text-align: center;
    }

    .step-content h6 {
        font-size: 13px;
        color: #333;
    }

    .flow-arrow {
        color: #6c757d;
        font-size: 20px;
        flex-shrink: 0;
    }

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

    .gap-2 > * {
        margin-right: 4px;
        margin-bottom: 4px;
    }

    @media (max-width: 768px) {
        .trading-flow-container {
            flex-direction: column;
        }
        
        .flow-arrow {
            transform: rotate(90deg);
            margin: 5px 0;
        }

        .flow-step {
            max-width: 100%;
        }
    }
</style>
@endpush
@push('scripts')
<script>
    $(document).ready(function() {
        // Handle delete connection form with confirmation
        $('.delete-connection-form').on('submit', function(e) {
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
