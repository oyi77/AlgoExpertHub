@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-bolt"></i> Trading Operations</h3>
                <p class="text-muted mb-0">Manage execution connections, monitor positions, and view analytics</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Active Connections</h6>
                        <h3>{{ $stats['active_connections'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Open Positions</h6>
                        <h3>{{ $stats['open_positions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Executions</h6>
                        <h3>{{ $stats['today_executions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's P&L</h6>
                        <h3>${{ number_format($stats['today_pnl'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-connections" data-toggle="tab">
                            <i class="fas fa-plug"></i> Execution Connections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-executions" data-toggle="tab">
                            <i class="fas fa-list"></i> Execution Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-positions-open" data-toggle="tab">
                            <i class="fas fa-chart-area"></i> Open Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-positions-closed" data-toggle="tab">
                            <i class="fas fa-history"></i> Closed Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-analytics" data-toggle="tab">
                            <i class="fas fa-chart-pie"></i> Analytics
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Execution Connections Tab -->
                    <div class="tab-pane fade show active" id="tab-connections">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-plug"></i> Execution Connections</h5>
                            <a href="{{ route('admin.trading-management.operations.connections.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Connection
                            </a>
                        </div>

                        @php
                            $connections = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::with(['preset', 'dataConnection'])
                                ->orderBy('created_at', 'desc')
                                ->paginate(10, ['*'], 'conn_page');
                        @endphp

                        @if($connections->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Exchange</th>
                                        <th>Status</th>
                                        <th>Preset</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($connections as $conn)
                                    <tr>
                                        <td><strong>{{ $conn->name }}</strong></td>
                                        <td>
                                            <span class="badge {{ $conn->type === 'CRYPTO_EXCHANGE' ? 'badge-primary' : 'badge-success' }}">
                                                {{ $conn->type === 'CRYPTO_EXCHANGE' ? 'Crypto' : 'Forex' }}
                                            </span>
                                        </td>
                                        <td>{{ $conn->exchange_name }}</td>
                                        <td>
                                            @if($conn->is_active)
                                            <span class="badge badge-success">Active</span>
                                            @else
                                            <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $conn->preset->name ?? 'None' }}</td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.operations.connections.edit', $conn) }}" class="btn btn-sm btn-info">
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
                            <i class="fas fa-info-circle"></i> No execution connections. <a href="{{ route('admin.trading-management.operations.connections.create') }}">Create one now</a>.
                        </div>
                        @endif
                    </div>

                    <!-- Execution Log Tab -->
                    <div class="tab-pane fade" id="tab-executions">
                        <h5 class="mb-3"><i class="fas fa-list"></i> Recent Executions</h5>

                        @php
                            $executions = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::with('connection')
                                ->orderBy('created_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($executions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Connection</th>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Lot Size</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($executions as $exec)
                                    <tr>
                                        <td>{{ $exec->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $exec->connection->name ?? 'N/A' }}</td>
                                        <td>{{ $exec->symbol }}</td>
                                        <td>
                                            <span class="badge {{ in_array($exec->direction, ['BUY', 'LONG']) ? 'badge-success' : 'badge-danger' }}">
                                                {{ $exec->direction }}
                                            </span>
                                        </td>
                                        <td>{{ $exec->lot_size }}</td>
                                        <td>
                                            @if($exec->status === 'SUCCESS')
                                            <span class="badge badge-success">Success</span>
                                            @elseif($exec->status === 'FAILED')
                                            <span class="badge badge-danger">Failed</span>
                                            @else
                                            <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.operations.executions') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Executions
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No executions yet.</div>
                        @endif
                    </div>

                    <!-- Open Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-open">
                        <h5 class="mb-3"><i class="fas fa-chart-area"></i> Open Positions</h5>

                        @php
                            $openPositions = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::with('connection')
                                ->where('status', 'open')
                                ->orderBy('created_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($openPositions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Entry</th>
                                        <th>Current</th>
                                        <th>Lot Size</th>
                                        <th>P&L</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($openPositions as $pos)
                                    <tr>
                                        <td>{{ $pos->symbol }}</td>
                                        <td>
                                            <span class="badge {{ in_array($pos->direction, ['buy', 'long']) ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($pos->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $pos->entry_price }}</td>
                                        <td>{{ $pos->current_price }}</td>
                                        <td>{{ $pos->quantity }}</td>
                                        <td class="{{ $pos->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($pos->pnl, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.operations.positions.open') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Positions
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No open positions.</div>
                        @endif
                    </div>

                    <!-- Closed Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-closed">
                        <h5 class="mb-3"><i class="fas fa-history"></i> Recent Closed Positions</h5>

                        @php
                            $closedPositions = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::with('connection')
                                ->where('status', 'closed')
                                ->orderBy('closed_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($closedPositions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Closed</th>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Entry</th>
                                        <th>Exit</th>
                                        <th>P&L</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($closedPositions as $pos)
                                    <tr>
                                        <td>{{ $pos->closed_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $pos->symbol }}</td>
                                        <td>
                                            <span class="badge {{ in_array($pos->direction, ['buy', 'long']) ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($pos->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $pos->entry_price }}</td>
                                        <td>{{ $pos->current_price }}</td>
                                        <td class="{{ $pos->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>${{ number_format($pos->pnl, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($pos->closed_reason === 'tp')
                                            <span class="badge badge-success">TP</span>
                                            @elseif($pos->closed_reason === 'sl')
                                            <span class="badge badge-danger">SL</span>
                                            @else
                                            <span class="badge badge-info">{{ strtoupper($pos->closed_reason) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.operations.positions.closed') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Closed Positions
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No closed positions yet.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-3"><i class="fas fa-chart-pie"></i> Performance Analytics</h5>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted">View detailed performance analytics with charts and metrics</p>
                            <a href="{{ route('admin.trading-management.operations.analytics') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-external-link-alt"></i> Open Analytics Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
