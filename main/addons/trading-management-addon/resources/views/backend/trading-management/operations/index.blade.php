@extends('backend.layout.master')

@section('element')
<div class="row">
    <!-- Statistics Overview -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Connections</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_connections'] }}</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-plug fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Open Positions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['open_positions'] }}</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-chart-area fa-2x"></i>
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
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Executions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['today_executions'] }}</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-bolt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-{{ $stats['today_pnl'] >= 0 ? 'success' : 'danger' }} shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Today's P&L</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($stats['today_pnl'], 2) }}</div>
                            </div>
                            <div class="text-{{ $stats['today_pnl'] >= 0 ? 'success' : 'danger' }}">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold">
                        <i class="fas fa-bolt text-primary"></i> Trading Operations
                    </h5>
                    <p class="text-muted mb-0">Manage execution connections, monitor positions, and view analytics</p>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs border-bottom" role="tablist">
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

                <div class="tab-content p-4">
                    <!-- Manual Trade Tab -->
                    <div class="tab-pane fade show active" id="tab-manual-trade">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-bolt text-primary"></i> Manual Trade Execution</h5>

                        @php
                            $hasTradeExecutionColumn = \Illuminate\Support\Facades\Schema::hasColumn('execution_connections', 'trade_execution_enabled');
                            $query = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('is_active', true)
                                ->where('status', 'active');
                            if ($hasTradeExecutionColumn) {
                                $query->where('trade_execution_enabled', true);
                            }
                            $activeConnections = $query->get();
                        @endphp

                        @if($activeConnections->count() > 0)
                        <form id="manualTradeForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary shadow-sm">
                                        <div class="card-header bg-light border-bottom">
                                            <h6 class="mb-0 font-weight-bold"><i class="fas fa-cog text-primary"></i> Trade Setup</h6>
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
                                    <div class="card border-warning shadow-sm">
                                        <div class="card-header bg-light border-bottom">
                                            <h6 class="mb-0 font-weight-bold"><i class="fas fa-shield-alt text-warning"></i> Risk Management</h6>
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

                            <div class="card bg-light border shadow-sm mt-3">
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
                        <div class="alert alert-warning border">
                            <i class="fas fa-exclamation-triangle"></i> No active exchange connections with trade execution enabled. 
                            <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create a connection</a> in Trading Configuration first.
                        </div>
                        @endif
                    </div>

                    <!-- Execution Log Tab -->
                    <div class="tab-pane fade" id="tab-executions">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-list text-primary"></i> Recent Executions</h5>

                        @php
                            $executions = \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::with('connection')
                                ->orderBy('created_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($executions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
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
                                        <td><strong>{{ $exec->symbol }}</strong></td>
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
                        <div class="alert alert-info border">No executions yet.</div>
                        @endif
                    </div>

                    <!-- Open Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-open">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-chart-area text-primary"></i> Open Positions</h5>

                        @php
                            $openPositions = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::with('connection')
                                ->where('status', 'open')
                                ->orderBy('created_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($openPositions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Entry</th>
                                        <th>Current</th>
                                        <th>Lot Size</th>
                                        <th>P&L</th>
                                    </tr>
                                </thead>
                                <tbody id="positions-tbody-inline">
                                    @foreach($openPositions as $pos)
                                    <tr data-position-id="{{ $pos->id }}">
                                        <td><strong>{{ $pos->symbol }}</strong></td>
                                        <td>
                                            <span class="badge {{ in_array(strtolower($pos->direction), ['buy', 'long']) ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($pos->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $pos->entry_price }}</td>
                                        <td class="position-current-price" data-position-id="{{ $pos->id }}">{{ $pos->current_price ?? $pos->entry_price }}</td>
                                        <td>{{ $pos->quantity }}</td>
                                        <td class="position-pnl {{ $pos->pnl >= 0 ? 'text-success' : 'text-danger' }}" data-position-id="{{ $pos->id }}">
                                            $<span class="pnl-amount font-weight-bold">{{ number_format($pos->pnl, 2) }}</span>
                                            <small class="d-block text-muted position-pnl-percentage" data-position-id="{{ $pos->id }}">
                                                ({{ number_format($pos->pnl_percentage ?? 0, 2) }}%)
                                            </small>
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
                        <div class="alert alert-info border">No open positions.</div>
                        @endif
                    </div>

                    <!-- Closed Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-closed">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-history text-primary"></i> Recent Closed Positions</h5>

                        @php
                            $closedPositions = \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::with('connection')
                                ->where('status', 'closed')
                                ->orderBy('closed_at', 'desc')
                                ->limit(20)
                                ->get();
                        @endphp

                        @if($closedPositions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
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
                                        <td><strong>{{ $pos->symbol }}</strong></td>
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
                        <div class="alert alert-info border">No closed positions yet.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-4 font-weight-bold"><i class="fas fa-chart-pie text-primary"></i> Performance Analytics</h5>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                            <p class="text-muted mb-4">View detailed performance analytics with charts and metrics</p>
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
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
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
    .then(async response => {
        if (response.redirected || response.status === 302) {
            throw new Error('Request was redirected. Please check your session and try again.');
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
        }
        
        return response.json();
    })
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
            
            document.getElementById('manualTradeForm').reset();
            document.getElementById('confirmTrade').checked = false;
        } else {
            let errorHtml = `<div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> <strong>Trade Execution Failed</strong>
                <hr>`;
            
            if (data.errors && typeof data.errors === 'object') {
                errorHtml += `<p class="mb-2"><strong>${data.message || 'Validation failed'}:</strong></p><ul class="mb-0">`;
                for (const [field, messages] of Object.entries(data.errors)) {
                    const fieldName = field.split('_').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                    if (Array.isArray(messages)) {
                        messages.forEach(msg => {
                            errorHtml += `<li><strong>${fieldName}:</strong> ${msg}</li>`;
                        });
                    } else {
                        errorHtml += `<li><strong>${fieldName}:</strong> ${messages}</li>`;
                    }
                }
                errorHtml += `</ul>`;
            } else {
                errorHtml += `<p class="mb-0">${data.message || 'An error occurred'}</p>`;
            }
            
            errorHtml += `</div>`;
            resultDiv.innerHTML = errorHtml;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> <strong>Error:</strong> ${error.message}
        </div>`;
    });
}

// Real-time position updates
(function() {
    'use strict';
    
    function getPositionIds() {
        const rows = document.querySelectorAll('#positions-tbody-inline tr[data-position-id]');
        return Array.from(rows).map(row => parseInt(row.getAttribute('data-position-id')));
    }
    
    function updatePositions(updates) {
        updates.forEach(function(update) {
            const currentPriceCell = document.querySelector(`.position-current-price[data-position-id="${update.id}"]`);
            if (currentPriceCell) {
                const oldPrice = parseFloat(currentPriceCell.textContent.trim());
                const newPrice = parseFloat(update.current_price);
                currentPriceCell.textContent = newPrice.toFixed(8);
                if (oldPrice !== newPrice) {
                    currentPriceCell.classList.add('price-updated');
                    setTimeout(() => currentPriceCell.classList.remove('price-updated'), 1000);
                }
            }
            
            const pnlCell = document.querySelector(`.position-pnl[data-position-id="${update.id}"]`);
            if (pnlCell) {
                const pnlAmount = pnlCell.querySelector('.pnl-amount');
                const pnlPercentage = pnlCell.querySelector('.position-pnl-percentage');
                if (pnlAmount) pnlAmount.textContent = parseFloat(update.pnl).toFixed(2);
                if (pnlPercentage) pnlPercentage.textContent = '(' + parseFloat(update.pnl_percentage).toFixed(2) + '%)';
                pnlCell.classList.remove('text-success', 'text-danger');
                pnlCell.classList.add(parseFloat(update.pnl) >= 0 ? 'text-success' : 'text-danger');
            }
        });
    }
    
    function fetchPositionUpdates() {
        const positionIds = getPositionIds();
        if (positionIds.length === 0) return;
        
        fetch('{{ route("admin.trading-management.operations.positions.updates") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ position_ids: positionIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) updatePositions(data.data);
        })
        .catch(error => console.error('Failed to fetch position updates:', error));
    }
    
    if (getPositionIds().length > 0) {
        fetchPositionUpdates();
        setInterval(fetchPositionUpdates, 5000);
    }
})();
</script>
<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-danger { border-left: 4px solid #e74a3b !important; }
.price-updated {
    background-color: #fff3cd !important;
    transition: background-color 0.3s ease;
}
</style>
@endpush
@endsection
