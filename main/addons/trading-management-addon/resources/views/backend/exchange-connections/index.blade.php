@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Complete Trading Flow Visualization -->
        <div class="card mb-3">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0"><i class="fas fa-project-diagram"></i> Complete Trading Flow</h4>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <!-- Step 1 -->
                    <div class="col-md">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <i class="fas fa-download fa-3x text-primary mb-2"></i>
                                <h6 class="text-primary">1. Fetch Data</h6>
                                <small class="text-muted">Exchange → Market Data</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-md">
                        <div class="card border-info h-100">
                            <div class="card-body">
                                <i class="fas fa-code fa-3x text-info mb-2"></i>
                                <h6 class="text-info">2. Parse</h6>
                                <small class="text-muted">Extract Signals</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 3 -->
                    <div class="col-md">
                        <div class="card border-warning h-100">
                            <div class="card-body">
                                <i class="fas fa-database fa-3x text-warning mb-2"></i>
                                <h6 class="text-warning">3. Aggregate</h6>
                                <small class="text-muted">Store & Combine</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 4 -->
                    <div class="col-md">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <i class="fas fa-filter fa-3x text-primary mb-2"></i>
                                <h6 class="text-primary">4. Filter</h6>
                                <small class="text-muted">Technical Indicators</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 5 -->
                    <div class="col-md">
                        <div class="card border-info h-100">
                            <div class="card-body">
                                <i class="fas fa-robot fa-3x text-info mb-2"></i>
                                <h6 class="text-info">5. AI Analyze</h6>
                                <small class="text-muted">Market Confirmation</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 6 -->
                    <div class="col-md">
                        <div class="card border-warning h-100">
                            <div class="card-body">
                                <i class="fas fa-shield-alt fa-3x text-warning mb-2"></i>
                                <h6 class="text-warning">6. Risk Mgmt</h6>
                                <small class="text-muted">Position Sizing</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center px-1">
                        <i class="fas fa-arrow-right fa-lg text-muted"></i>
                    </div>

                    <!-- Step 7 -->
                    <div class="col-md">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <i class="fas fa-bolt fa-3x text-success mb-2"></i>
                                <h6 class="text-success">7. Execute</h6>
                                <small class="text-muted">Place Trade</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i> <strong>Unified Flow:</strong> One connection handles both data fetching (steps 1-5) and trade execution (steps 6-7). Enable/disable features as needed.
                </div>
            </div>
        </div>

        <!-- Connections List -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-plug"></i> Exchange Connections</h4>
                    <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Connection
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($connections->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Provider</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($connections as $conn)
                            <tr>
                                <td>
                                    <strong>{{ $conn->name }}</strong>
                                    @if($conn->is_admin_owned)
                                    <span class="badge badge-info badge-sm">Admin</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $conn->connection_type === 'CRYPTO_EXCHANGE' ? 'badge-primary' : 'badge-success' }}">
                                        {{ $conn->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto' : 'Forex' }}
                                    </span>
                                </td>
                                <td>{{ strtoupper($conn->provider) }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $conn->getPurposeLabel() }}</span>
                                    <br>
                                    @if($conn->is_active)
                                    <small><i class="fas fa-download text-primary"></i> Data</small>
                                    @endif
                                    @if($conn->is_active)
                                    <small><i class="fas fa-bolt text-success"></i> Trading</small>
                                    @endif
                                </td>
                                <td>
                                    @if($conn->status === 'connected')
                                    <span class="badge badge-success">Connected</span>
                                    @elseif($conn->status === 'error')
                                    <span class="badge badge-danger">Error</span>
                                    @else
                                    <span class="badge badge-warning">{{ ucfirst($conn->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($conn->last_data_fetch_at)
                                    <small>Data: {{ $conn->last_data_fetch_at->diffForHumans() }}</small><br>
                                    @endif
                                    @if($conn->last_trade_execution_at)
                                    <small>Trade: {{ $conn->last_trade_execution_at->diffForHumans() }}</small>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.trading-management.config.exchange-connections.show', $conn) }}" class="btn btn-sm btn-success" title="Test & Preview">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $conn) }}" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $connections->links() }}
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No exchange connections yet. <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create your first connection</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

