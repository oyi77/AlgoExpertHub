@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="row gy-4">
    <div class="col-12">
        <!-- Page Header -->
        <div class="sp_site_card mb-3">
            <div class="card-body">
                <h3><i class="las la-bolt"></i> {{ __('Trading Operations') }}</h3>
                <p class="text-muted mb-0">{{ __('Manage execution connections, monitor positions, and view analytics') }}</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row gy-3 mb-3">
            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">{{ __('Active Connections') }}</h6>
                        <h3 class="mb-0">{{ $stats['active_connections'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">{{ __('Open Positions') }}</h6>
                        <h3 class="mb-0">{{ $stats['open_positions'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">{{ __("Today's Executions") }}</h6>
                        <h3 class="mb-0">{{ $stats['today_executions'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sp_site_card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">{{ __("Today's P&L") }}</h6>
                        <h3 class="mb-0">${{ number_format($stats['today_pnl'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        @if(!$tradingManagementEnabled)
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="las la-exclamation-triangle"></i> 
                    {{ __('Trading Management Addon is not enabled. Please contact administrator.') }}
                </div>
            </div>
        @else
            <!-- Tab Navigation -->
            <div class="sp_site_card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#tab-manual-trade" data-bs-toggle="tab">
                                <i class="las la-bolt"></i> {{ __('Manual Trade') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tab-executions" data-bs-toggle="tab">
                                <i class="las la-list"></i> {{ __('Execution Log') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tab-positions-open" data-bs-toggle="tab">
                                <i class="las la-chart-area"></i> {{ __('Open Positions') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tab-positions-closed" data-bs-toggle="tab">
                                <i class="las la-history"></i> {{ __('Closed Positions') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tab-analytics" data-bs-toggle="tab">
                                <i class="las la-chart-pie"></i> {{ __('Analytics') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Manual Trade Tab -->
                        <div class="tab-pane fade show active" id="tab-manual-trade">
                            <h5 class="mb-3"><i class="las la-bolt"></i> {{ __('Manual Trade Execution') }}</h5>

                            @if(isset($activeConnections) && $activeConnections->count() > 0)
                            <form id="manualTradeForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="sp_site_card border-primary">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">{{ __('Trade Setup') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group mb-3">
                                                    <label>{{ __('Exchange Connection') }} <span class="text-danger">*</span></label>
                                                    <select id="connection_id" class="form-control" required>
                                                        <option value="">{{ __('Select Connection') }}</option>
                                                        @foreach($activeConnections as $conn)
                                                        <option value="{{ $conn->id }}" data-preset="{{ $conn->preset_id ?? '' }}">
                                                            {{ $conn->name ?? 'N/A' }} 
                                                            @if(isset($conn->exchange_name))
                                                                ({{ strtoupper($conn->exchange_name) }})
                                                            @elseif(isset($conn->provider))
                                                                ({{ strtoupper($conn->provider) }})
                                                            @elseif(isset($conn->connection_type))
                                                                ({{ strtoupper($conn->connection_type) }})
                                                            @endif
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Symbol') }} <span class="text-danger">*</span></label>
                                                            <input type="text" id="symbol" class="form-control" placeholder="BTCUSDT, EURUSD" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Direction') }} <span class="text-danger">*</span></label>
                                                            <select id="direction" class="form-control" required>
                                                                <option value="BUY">{{ __('BUY / LONG') }}</option>
                                                                <option value="SELL">{{ __('SELL / SHORT') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label>{{ __('Lot Size') }} <span class="text-danger">*</span></label>
                                                    <input type="number" id="lot_size" class="form-control" step="0.01" min="0.01" value="0.1" required>
                                                    <small class="text-muted">{{ __('Position size in lots') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="sp_site_card border-warning">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">{{ __('Risk Management') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Entry Price') }}</label>
                                                            <input type="number" id="entry_price" class="form-control" step="0.00001" placeholder="{{ __('Market') }}">
                                                            <small class="text-muted">{{ __('Leave empty for market price') }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Order Type') }}</label>
                                                            <select id="order_type" class="form-control">
                                                                <option value="market">{{ __('Market') }}</option>
                                                                <option value="limit">{{ __('Limit') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Stop Loss (SL)') }}</label>
                                                            <input type="number" id="sl_price" class="form-control" step="0.00001" placeholder="{{ __('Optional') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{ __('Take Profit (TP)') }}</label>
                                                            <input type="number" id="tp_price" class="form-control" step="0.00001" placeholder="{{ __('Optional') }}">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label>{{ __('Notes') }}</label>
                                                    <textarea id="notes" class="form-control" rows="2" placeholder="{{ __('Optional trade notes') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="sp_site_card bg-light mt-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="confirmTrade" required>
                                                    <label class="form-check-label" for="confirmTrade">
                                                        <strong>{{ __('I confirm this trade execution') }}</strong>
                                                    </label>
                                                </div>
                                                <small class="text-muted">{{ __('This will place a REAL trade on the selected exchange') }}</small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button type="button" class="btn sp_theme_btn btn-lg" onclick="executeManualTrade()">
                                                    <i class="las la-bolt"></i> {{ __('Execute Trade') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div id="tradeResult" class="mt-3"></div>
                            @else
                            <div class="alert alert-warning">
                                <i class="las la-exclamation-triangle"></i> {{ __('No active exchange connections with trade execution enabled.') }}
                                @if(Route::has('user.trading.operations.index'))
                                    <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}">{{ __('Create a connection') }}</a> {{ __('first.') }}
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Execution Log Tab -->
                        <div class="tab-pane fade" id="tab-executions">
                            <h5 class="mb-3"><i class="las la-list"></i> {{ __('Recent Executions') }}</h5>

                            @if(isset($recentExecutions) && $recentExecutions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Time') }}</th>
                                            <th>{{ __('Connection') }}</th>
                                            <th>{{ __('Symbol') }}</th>
                                            <th>{{ __('Direction') }}</th>
                                            <th>{{ __('Lot Size') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentExecutions as $exec)
                                        <tr>
                                            <td>{{ $exec->created_at ? $exec->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                            <td>{{ $exec->connection->name ?? 'N/A' }}</td>
                                            <td>{{ $exec->symbol ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ in_array(strtoupper($exec->direction ?? ''), ['BUY', 'LONG']) ? 'bg-success' : 'bg-danger' }}">
                                                    {{ strtoupper($exec->direction ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>{{ $exec->quantity ?? 'N/A' }}</td>
                                            <td>
                                                @if(($exec->status ?? '') === 'SUCCESS')
                                                <span class="badge bg-success">{{ __('Success') }}</span>
                                                @elseif(($exec->status ?? '') === 'FAILED')
                                                <span class="badge bg-danger">{{ __('Failed') }}</span>
                                                @else
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('user.trading.execution-log.index') }}" class="btn sp_theme_btn">
                                    <i class="las la-external-link-alt"></i> {{ __('View All Executions') }}
                                </a>
                            </div>
                            @else
                            <div class="alert alert-info">{{ __('No executions yet.') }}</div>
                            @endif
                        </div>

                        <!-- Open Positions Tab -->
                        <div class="tab-pane fade" id="tab-positions-open">
                            <h5 class="mb-3"><i class="las la-chart-area"></i> {{ __('Open Positions') }}</h5>

                            @if(isset($openPositions) && $openPositions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Symbol') }}</th>
                                            <th>{{ __('Direction') }}</th>
                                            <th>{{ __('Entry') }}</th>
                                            <th>{{ __('Current') }}</th>
                                            <th>{{ __('Lot Size') }}</th>
                                            <th>{{ __('P&L') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($openPositions as $pos)
                                        <tr>
                                            <td>{{ $pos->symbol ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ in_array(strtolower($pos->direction ?? ''), ['buy', 'long']) ? 'bg-success' : 'bg-danger' }}">
                                                    {{ strtoupper($pos->direction ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>{{ $pos->entry_price ?? 'N/A' }}</td>
                                            <td>{{ $pos->current_price ?? 'N/A' }}</td>
                                            <td>{{ $pos->quantity ?? 'N/A' }}</td>
                                            <td class="{{ ($pos->pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($pos->pnl ?? 0, 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('user.trading.execution-log.index') }}" class="btn sp_theme_btn">
                                    <i class="las la-external-link-alt"></i> {{ __('View All Positions') }}
                                </a>
                            </div>
                            @else
                            <div class="alert alert-info">{{ __('No open positions.') }}</div>
                            @endif
                        </div>

                        <!-- Closed Positions Tab -->
                        <div class="tab-pane fade" id="tab-positions-closed">
                            <h5 class="mb-3"><i class="las la-history"></i> {{ __('Recent Closed Positions') }}</h5>

                            @if(isset($closedPositions) && $closedPositions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Closed') }}</th>
                                            <th>{{ __('Symbol') }}</th>
                                            <th>{{ __('Direction') }}</th>
                                            <th>{{ __('Entry') }}</th>
                                            <th>{{ __('Exit') }}</th>
                                            <th>{{ __('P&L') }}</th>
                                            <th>{{ __('Reason') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($closedPositions as $pos)
                                        <tr>
                                            <td>{{ $pos->closed_at ? $pos->closed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                            <td>{{ $pos->symbol ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ in_array(strtolower($pos->direction ?? ''), ['buy', 'long']) ? 'bg-success' : 'bg-danger' }}">
                                                    {{ strtoupper($pos->direction ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>{{ $pos->entry_price ?? 'N/A' }}</td>
                                            <td>{{ $pos->current_price ?? 'N/A' }}</td>
                                            <td class="{{ ($pos->pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                <strong>${{ number_format($pos->pnl ?? 0, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if(($pos->closed_reason ?? '') === 'tp')
                                                <span class="badge bg-success">{{ __('TP') }}</span>
                                                @elseif(($pos->closed_reason ?? '') === 'sl')
                                                <span class="badge bg-danger">{{ __('SL') }}</span>
                                                @else
                                                <span class="badge bg-info">{{ strtoupper($pos->closed_reason ?? 'N/A') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('user.trading.execution-log.index') }}" class="btn sp_theme_btn">
                                    <i class="las la-external-link-alt"></i> {{ __('View All Closed Positions') }}
                                </a>
                            </div>
                            @else
                            <div class="alert alert-info">{{ __('No closed positions yet.') }}</div>
                            @endif
                        </div>

                        <!-- Analytics Tab -->
                        <div class="tab-pane fade" id="tab-analytics">
                            <h5 class="mb-3"><i class="las la-chart-pie"></i> {{ __('Performance Analytics') }}</h5>
                            <div class="text-center py-4">
                                <i class="las la-chart-line la-3x text-muted mb-3"></i>
                                <p class="text-muted">{{ __('View detailed performance analytics with charts and metrics') }}</p>
                                <a href="{{ route('user.trading.execution-log.index') }}" class="btn sp_theme_btn btn-lg">
                                    <i class="las la-external-link-alt"></i> {{ __('Open Analytics Dashboard') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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
        alert('{{ __("Please fill in all required fields") }}');
        return;
    }

    if (!confirmed) {
        alert('{{ __("Please confirm the trade execution") }}');
        return;
    }

    const resultDiv = document.getElementById('tradeResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="las la-spinner la-spin"></i> {{ __("Placing trade order...") }}</div>';

    fetch('{{ route("user.trading.execution-log.manual-trade") }}', {
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
                <i class="las la-check-circle"></i> <strong>{{ __("Trade Executed Successfully!") }}</strong>
                <hr>
                <p class="mb-1"><strong>{{ __("Order ID") }}:</strong> ${data.data.order_id || 'N/A'}</p>
                <p class="mb-1"><strong>{{ __("Symbol") }}:</strong> ${data.data.symbol}</p>
                <p class="mb-1"><strong>{{ __("Direction") }}:</strong> ${data.data.direction}</p>
                <p class="mb-1"><strong>{{ __("Lot Size") }}:</strong> ${data.data.lot_size}</p>
                <p class="mb-1"><strong>{{ __("Entry Price") }}:</strong> ${data.data.entry_price || '{{ __("Market") }}'}</p>
                <p class="mb-0"><strong>{{ __("Status") }}:</strong> <span class="badge bg-success">${data.data.status}</span></p>
            </div>`;
            
            // Reset form
            document.getElementById('manualTradeForm').reset();
            document.getElementById('confirmTrade').checked = false;
            
            // Reload page after 2 seconds to show updated stats
            setTimeout(() => location.reload(), 2000);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="las la-times-circle"></i> <strong>{{ __("Trade Execution Failed") }}</strong>
                <hr>
                <p class="mb-0">${data.message}</p>
            </div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <i class="las la-times-circle"></i> <strong>{{ __("Error") }}:</strong> ${error.message}
        </div>`;
    });
}
</script>
@endpush
@endsection
