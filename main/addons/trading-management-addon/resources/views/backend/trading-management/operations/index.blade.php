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
                        <a class="nav-link active" href="#tab-manual-trade" data-toggle="tab">
                            <i class="fas fa-bolt"></i> Manual Trade
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
                    <!-- Manual Trade Tab -->
                    <div class="tab-pane fade show active" id="tab-manual-trade">
                        <h5 class="mb-3"><i class="fas fa-bolt"></i> Manual Trade Execution</h5>

                        @php
                            $activeConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('trade_execution_enabled', 1)
                                ->where('is_active', 1)
                                ->where('status', 'connected')
                                ->get();
                        @endphp

                        @if($activeConnections->count() > 0)
                        <form id="manualTradeForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Trade Setup</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>Exchange Connection <span class="text-danger">*</span></label>
                                                <select id="connection_id" class="form-control" required>
                                                    <option value="">Select Connection</option>
                                                    @foreach($activeConnections as $conn)
                                                    <option value="{{ $conn->id }}" data-preset="{{ $conn->preset_id }}">
                                                        {{ $conn->name }} ({{ strtoupper($conn->provider) }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Symbol <span class="text-danger">*</span></label>
                                                        <input type="text" id="symbol" class="form-control" placeholder="BTCUSDT, EURUSD" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Direction <span class="text-danger">*</span></label>
                                                        <select id="direction" class="form-control" required>
                                                            <option value="BUY">BUY / LONG</option>
                                                            <option value="SELL">SELL / SHORT</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Lot Size <span class="text-danger">*</span></label>
                                                <input type="number" id="lot_size" class="form-control" step="0.01" min="0.01" value="0.1" required>
                                                <small class="text-muted">Position size in lots</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-warning">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Risk Management</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Entry Price</label>
                                                        <input type="number" id="entry_price" class="form-control" step="0.00001" placeholder="Market">
                                                        <small class="text-muted">Leave empty for market price</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Order Type</label>
                                                        <select id="order_type" class="form-control">
                                                            <option value="market">Market</option>
                                                            <option value="limit">Limit</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Stop Loss (SL)</label>
                                                        <input type="number" id="sl_price" class="form-control" step="0.00001" placeholder="Optional">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Take Profit (TP)</label>
                                                        <input type="number" id="tp_price" class="form-control" step="0.00001" placeholder="Optional">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Notes</label>
                                                <textarea id="notes" class="form-control" rows="2" placeholder="Optional trade notes"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="confirmTrade" required>
                                                <label class="custom-control-label" for="confirmTrade">
                                                    <strong>I confirm this trade execution</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">This will place a REAL trade on the selected exchange</small>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <button type="button" class="btn btn-success btn-lg" onclick="executeManualTrade()">
                                                <i class="fas fa-bolt"></i> Execute Trade
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="tradeResult" class="mt-3"></div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No active exchange connections with trade execution enabled. 
                            <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create a connection</a> in Trading Configuration first.
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

@push('script')
<script>
function executeManualTrade() {
    const connectionId = document.getElementById('connection_id').value;
    const symbol = document.getElementById('symbol').value;
    const direction = document.getElementById('direction').value;
    const lotSize = document.getElementById('lot_size').value;
    const orderType = document.getElementById('order_type').value;
    const entryPrice = document.getElementById('entry_price').value;
    const slPrice = document.getElementById('sl_price').value;
    const tpPrice = document.getElementById('tp_price').value;
    const notes = document.getElementById('notes').value;
    const confirmed = document.getElementById('confirmTrade').checked;

    if (!connectionId || !symbol || !lotSize) {
        alert('Please fill in all required fields');
        return;
    }

    if (!confirmed) {
        alert('Please confirm the trade execution');
        return;
    }

    const resultDiv = document.getElementById('tradeResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Placing trade order...</div>';

    fetch('{{ route("admin.trading-management.operations.manual-trade") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            connection_id: connectionId,
            symbol: symbol,
            direction: direction,
            lot_size: parseFloat(lotSize),
            order_type: orderType,
            entry_price: entryPrice ? parseFloat(entryPrice) : null,
            sl_price: slPrice ? parseFloat(slPrice) : null,
            tp_price: tpPrice ? parseFloat(tpPrice) : null,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>Trade Executed Successfully!</strong>
                <hr>
                <p class="mb-1"><strong>Order ID:</strong> ${data.data.order_id || 'N/A'}</p>
                <p class="mb-1"><strong>Symbol:</strong> ${data.data.symbol}</p>
                <p class="mb-1"><strong>Direction:</strong> ${data.data.direction}</p>
                <p class="mb-1"><strong>Lot Size:</strong> ${data.data.lot_size}</p>
                <p class="mb-1"><strong>Entry Price:</strong> ${data.data.entry_price || 'Market'}</p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success">${data.data.status}</span></p>
            </div>`;
            
            // Reset form
            document.getElementById('manualTradeForm').reset();
            document.getElementById('confirmTrade').checked = false;
            
            // Reload page after 2 seconds to show updated stats
            setTimeout(() => location.reload(), 2000);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> <strong>Trade Execution Failed</strong>
                <hr>
                <p class="mb-0">${data.message}</p>
            </div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> <strong>Error:</strong> ${error.message}
        </div>`;
    });
}
</script>
@endpush
@endsection
