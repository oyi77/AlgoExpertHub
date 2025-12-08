@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Connections
            </a>
        </div>
        
        <!-- Connection Header Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt"></i> {{ $connection->name }}</h4>
                    <div>
                        @php
                            $statusClass = 'badge-warning';
                            $statusText = ucfirst($connection->status ?? 'inactive');
                            if ($connection->status === 'active' && $connection->is_active) {
                                $statusClass = 'badge-success';
                                $statusText = 'Active';
                            } elseif ($connection->status === 'error') {
                                $statusClass = 'badge-danger';
                                $statusText = 'Error';
                            } elseif ($connection->status === 'testing') {
                                $statusClass = 'badge-info';
                                $statusText = 'Testing';
                            }
                        @endphp
                        <span class="badge {{ $statusClass }} badge-lg">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-tag"></i> Type:</strong> 
                            <span class="ml-2">{{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto Exchange' : 'FX Broker' }}</span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-server"></i> Provider:</strong> 
                            <span class="ml-2 badge badge-info">{{ strtoupper($connection->provider) }}</span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-bullseye"></i> Purpose:</strong> 
                            <span class="ml-2 badge badge-secondary">{{ $connection->getPurposeLabel() }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-download"></i> Data Fetching:</strong> 
                            <span class="ml-2">
                                <i class="fas {{ $connection->is_active ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' }}"></i>
                                @if($connection->last_data_fetch_at)
                                <small class="text-muted">(Last: {{ $connection->last_data_fetch_at->diffForHumans() }})</small>
                                @endif
                            </span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-bolt"></i> Trade Execution:</strong> 
                            <span class="ml-2">
                                <i class="fas {{ $connection->is_active ? 'fa-check-circle text-success' : 'fa-times-circle text-muted' }}"></i>
                                @if($connection->last_trade_execution_at)
                                <small class="text-muted">(Last: {{ $connection->last_trade_execution_at->diffForHumans() }})</small>
                                @endif
                            </span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-sliders-h"></i> Preset:</strong> 
                            <span class="ml-2">{{ $connection->preset->name ?? 'None' }}</span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-users"></i> Copy Trading:</strong> 
                            <span class="ml-2 badge {{ $connection->copy_trading_enabled ? 'badge-success' : 'badge-secondary' }}">
                                {{ $connection->copy_trading_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <hr>
                <div class="d-flex flex-wrap gap-2">
                    @if($connection->status !== 'active' || !$connection->is_active)
                    <button type="button" class="btn btn-warning btn-sm" id="testConnectionBtn">
                        <i class="fas fa-vial"></i> Test Connection
                    </button>
                    @if($connection->status === 'inactive' && $connection->last_tested_at)
                    <button type="button" class="btn btn-success btn-sm" id="activateConnectionBtn">
                        <i class="fas fa-power-off"></i> Activate
                    </button>
                    @endif
                    @else
                    <button type="button" class="btn btn-danger btn-sm" id="deactivateConnectionBtn">
                        <i class="fas fa-power-off"></i> Deactivate
                    </button>
                    @endif
                    
                    @if($connection->canExecuteTrades())
                    <button type="button" class="btn btn-{{ $connection->copy_trading_enabled ? 'warning' : 'success' }} btn-sm" id="toggleCopyTradingBtn">
                        <i class="fas fa-users"></i> {{ $connection->copy_trading_enabled ? 'Disable' : 'Enable' }} Copy Trading
                    </button>
                    @endif
                    
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#tradingFlowModal">
                        <i class="fas fa-project-diagram"></i> Trading Flow
                    </button>
                    
                    @if(strtolower($connection->provider) === 'metaapi')
                    <button type="button" class="btn btn-info btn-sm" id="monitorConnectionBtn">
                        <i class="fas fa-tachometer-alt"></i> <span id="monitorBtnText">Start Monitoring</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="generateAccountTokenBtn" title="Generate account-specific token">
                        <i class="fas fa-key"></i> Generate Token
                    </button>
                    @endif
                    
                    <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $connection) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Testing & Monitoring Section -->
        <div class="row">
            @if($connection->is_active)
            <!-- Data Fetching Tests -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Data Fetching</h5>
                    </div>
                    <div class="card-body">
                        <form id="testDataForm">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold">Symbol</label>
                                <input type="text" id="data_symbol" class="form-control form-control-sm" value="{{ $connection->connection_type === 'FX_BROKER' ? 'XAUUSDc' : 'BTCUSDT' }}" placeholder="{{ $connection->connection_type === 'FX_BROKER' ? 'XAUUSDc, EURUSD, GBPUSD' : 'BTCUSDT, ETHUSDT' }}">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label class="small font-weight-bold">Timeframe</label>
                                        <select id="data_timeframe" class="form-control form-control-sm">
                                            <option value="M1">1m</option>
                                            <option value="M5">5m</option>
                                            <option value="M15">15m</option>
                                            <option value="H1" selected>1h</option>
                                            <option value="H4">4h</option>
                                            <option value="D1">1d</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label class="small font-weight-bold">Limit</label>
                                        <input type="number" id="data_limit" class="form-control form-control-sm" value="100" min="1" max="1000">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btn-block" onclick="testDataFetch()">
                                <i class="fas fa-play"></i> Fetch Data
                            </button>
                        </form>
                        <div id="dataResult" class="mt-3" style="max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            <!-- Trade Execution Tests -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Trade Execution</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <button class="list-group-item list-group-item-action" onclick="testExecution('balance')">
                                <i class="fas fa-wallet text-primary"></i> Test Fetch Balance
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testExecution('positions')">
                                <i class="fas fa-chart-line text-success"></i> Test Fetch Positions
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testExecution('test_order')">
                                <i class="fas fa-shopping-cart text-warning"></i> Test Place Order (Dry Run)
                            </button>
                        </div>
                        <div id="executionResult" class="mt-3" style="max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            @if(strtolower($connection->provider) === 'metaapi')
            <!-- Data Fetch Tests (One-time) -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-secondary h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Data Fetch Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <button class="list-group-item list-group-item-action" onclick="testFetchMarketData()">
                                <i class="fas fa-chart-candlestick text-info"></i> Test Market Data Fetch
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testFetchPositions()">
                                <i class="fas fa-chart-line text-success"></i> Test Positions Fetch
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testFetchOrders()">
                                <i class="fas fa-list-alt text-warning"></i> Test Order History Fetch
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testFetchBalance()">
                                <i class="fas fa-wallet text-primary"></i> Test Balance Fetch
                            </button>
                        </div>
                        <div id="fetchResult" class="mt-3" style="max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            <!-- Real-Time Streaming Tests -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-info h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-stream"></i> Real-Time Streaming</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <button class="list-group-item list-group-item-action" id="streamMarketDataBtn" onclick="toggleStreamMarketData()">
                                <i class="fas fa-chart-candlestick text-info"></i> <span id="streamMarketDataText">Start Market Data Stream</span>
                            </button>
                            <button class="list-group-item list-group-item-action" id="streamPositionsBtn" onclick="toggleStreamPositions()">
                                <i class="fas fa-chart-line text-success"></i> <span id="streamPositionsText">Start Positions Stream</span>
                            </button>
                            <button class="list-group-item list-group-item-action" id="streamOrdersBtn" onclick="toggleStreamOrders()">
                                <i class="fas fa-list-alt text-warning"></i> <span id="streamOrdersText">Start Order History Stream</span>
                            </button>
                            <button class="list-group-item list-group-item-action" id="streamBalanceBtn" onclick="toggleStreamBalance()">
                                <i class="fas fa-wallet text-primary"></i> <span id="streamBalanceText">Start Balance Stream</span>
                            </button>
                        </div>
                        <div id="streamResult" class="mt-3" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;"></div>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

<!-- Trading Flow Modal -->
<div class="modal fade" id="tradingFlowModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-project-diagram"></i> Complete Trading Flow</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="card {{ $connection->is_active ? 'border-success' : 'border-secondary' }}">
                            <div class="card-body">
                                <i class="fas fa-download fa-2x {{ $connection->is_active ? 'text-success' : 'text-muted' }}"></i>
                                <p class="mt-2 mb-0"><strong>1. Fetch Data</strong></p>
                                <small class="text-muted">Get market data</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-info">
                            <div class="card-body">
                                <i class="fas fa-code fa-2x text-info"></i>
                                <p class="mt-2 mb-0"><strong>2. Parse Data</strong></p>
                                <small class="text-muted">Extract signals</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body">
                                <i class="fas fa-database fa-2x text-warning"></i>
                                <p class="mt-2 mb-0"><strong>3. Aggregate</strong></p>
                                <small class="text-muted">Store & combine</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-md-2">
                        <div class="card border-primary">
                            <div class="card-body">
                                <i class="fas fa-filter fa-2x text-primary"></i>
                                <p class="mt-2 mb-0"><strong>4. Filter</strong></p>
                                <small class="text-muted">Indicators</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-info">
                            <div class="card-body">
                                <i class="fas fa-robot fa-2x text-info"></i>
                                <p class="mt-2 mb-0"><strong>5. AI Analyze</strong></p>
                                <small class="text-muted">Market confirm</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body">
                                <i class="fas fa-shield-alt fa-2x text-warning"></i>
                                <p class="mt-2 mb-0"><strong>6. Risk Mgmt</strong></p>
                                <small class="text-muted">Position sizing</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-2">
                        <div class="card {{ $connection->is_active ? 'border-success' : 'border-secondary' }}">
                            <div class="card-body">
                                <i class="fas fa-bolt fa-2x {{ $connection->is_active ? 'text-success' : 'text-muted' }}"></i>
                                <p class="mt-2 mb-0"><strong>7. Execute</strong></p>
                                <small class="text-muted">Place trade</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@if(strtolower($connection->provider) === 'metaapi')
<!-- MetaApi Monitor Modal -->
<div class="modal fade" id="monitorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-tachometer-alt"></i> MetaApi Connection Monitor</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="monitorStatus">
                    <div class="alert alert-info">Click "Start Monitoring" to begin...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
@if(strtolower($connection->provider) === 'metaapi')
@php
    $credentials = $connection->credentials ?? [];
    $metaApiAccountId = $credentials['account_id'] ?? '';
@endphp
let monitorEventSource = null;
let isMonitoring = false;
const metaApiAccountId = '{{ $metaApiAccountId }}';

// Fixed monitoring button logic
document.getElementById('monitorConnectionBtn').addEventListener('click', function() {
    const btn = this;
    const btnText = document.getElementById('monitorBtnText');
    
    if (isMonitoring && monitorEventSource) {
        // Stop monitoring
        monitorEventSource.close();
        monitorEventSource = null;
        isMonitoring = false;
        
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-info');
        btnText.textContent = 'Start Monitoring';
        btn.innerHTML = '<i class="fas fa-tachometer-alt"></i> <span id="monitorBtnText">Start Monitoring</span>';
        
        // Close modal if open
        $('#monitorModal').modal('hide');
        return;
    }
    
    // Start monitoring
    if (!metaApiAccountId) {
        alert('MetaApi Account ID not found');
        return;
    }
    
    // Open monitoring modal
    $('#monitorModal').modal('show');
    
    // Update button state
    btn.classList.remove('btn-info');
    btn.classList.add('btn-danger');
    btnText.textContent = 'Stop Monitoring';
    btn.innerHTML = '<i class="fas fa-stop"></i> <span id="monitorBtnText">Stop Monitoring</span>';
    isMonitoring = true;
    
    // Start SSE connection
    const monitorUrl = '{{ route("admin.trading-management.config.exchange-connections.monitor-metaapi", $connection) }}';
    monitorEventSource = new EventSource(monitorUrl);
    
    monitorEventSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            updateMonitorDisplay(data);
        } catch (e) {
            console.error('Failed to parse monitor data:', e);
        }
    };
    
    monitorEventSource.onerror = function(error) {
        console.error('Monitor error:', error);
        if (monitorEventSource.readyState === EventSource.CLOSED) {
            document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-danger">Connection closed. Click "Start Monitoring" to reconnect.</div>';
            // Reset button state
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-info');
            btnText.textContent = 'Start Monitoring';
            btn.innerHTML = '<i class="fas fa-tachometer-alt"></i> <span id="monitorBtnText">Start Monitoring</span>';
            isMonitoring = false;
            monitorEventSource = null;
        } else {
            document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-warning">Connection error. Retrying...</div>';
        }
    };
    
    monitorEventSource.onopen = function() {
        document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-success">Connected to MetaApi. Waiting for data...</div>';
    };
});

// Handle modal close
const monitorModalElem = document.getElementById('monitorModal');
if (monitorModalElem) {
    monitorModalElem.addEventListener('hidden.bs.modal', function() {
        if (monitorEventSource) {
            monitorEventSource.close();
            monitorEventSource = null;
        }
        const btn = document.getElementById('monitorConnectionBtn');
        const btnText = document.getElementById('monitorBtnText');
        if (btn && btnText) {
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-info');
            btnText.textContent = 'Start Monitoring';
            btn.innerHTML = '<i class="fas fa-tachometer-alt"></i> <span id="monitorBtnText">Start Monitoring</span>';
            isMonitoring = false;
        }
    });
}

function updateMonitorDisplay(data) {
    if (data.type === 'connected') {
        document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-success">' + (data.message || 'Connected') + '</div>';
    } else if (data.type === 'status') {
        let html = '<div class="card">';
        html += '<div class="card-header"><h5>Connection Status</h5></div>';
        html += '<div class="card-body">';
        
        if (data.account) {
            html += '<p><strong>State:</strong> <span class="badge badge-info">' + (data.account.state || 'N/A') + '</span></p>';
            html += '<p><strong>Connected:</strong> ' + (data.account.connected ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-danger">No</span>') + '</p>';
            html += '<p><strong>Connected to Broker:</strong> ' + (data.account.connectedToBroker ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>') + '</p>';
            
            if (data.account.accountInformation) {
                html += '<hr><h6>Account Information</h6>';
                html += '<p><strong>Balance:</strong> ' + (data.account.accountInformation.balance || 0) + '</p>';
                html += '<p><strong>Equity:</strong> ' + (data.account.accountInformation.equity || 0) + '</p>';
                html += '<p><strong>Margin:</strong> ' + (data.account.accountInformation.margin || 0) + '</p>';
            }
            
            if (data.account.positions && data.account.positions.length > 0) {
                html += '<hr><h6>Open Positions (' + data.account.positions.length + ')</h6>';
                html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Symbol</th><th>Volume</th><th>Type</th><th>Profit</th></tr></thead><tbody>';
                data.account.positions.forEach(pos => {
                    html += '<tr><td>' + (pos.symbol || 'N/A') + '</td><td>' + (pos.volume || 0) + '</td><td>' + (pos.type || 'N/A') + '</td><td>' + (pos.profit || 0) + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            
            if (data.account.orders && data.account.orders.length > 0) {
                html += '<hr><h6>Pending Orders (' + data.account.orders.length + ')</h6>';
                html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Symbol</th><th>Volume</th><th>Type</th></tr></thead><tbody>';
                data.account.orders.forEach(order => {
                    html += '<tr><td>' + (order.symbol || 'N/A') + '</td><td>' + (order.volume || 0) + '</td><td>' + (order.type || 'N/A') + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
        }
        
        html += '<hr><small class="text-muted">Last updated: ' + (data.timestamp || new Date().toLocaleString()) + '</small>';
        html += '</div></div>';
        document.getElementById('monitorStatus').innerHTML = html;
    } else if (data.type === 'error') {
        let errorHtml = '<div class="alert alert-danger">';
        errorHtml += '<h6><i class="fas fa-exclamation-triangle"></i> Error</h6>';
        errorHtml += '<p>' + (data.message || 'Unknown error') + '</p>';
        
        if (data.suggestion) {
            errorHtml += '<hr><p class="mb-0"><strong>Suggestion:</strong> ' + data.suggestion + '</p>';
        }
        
        if (data.status_code === 404) {
            errorHtml += '<hr><div class="mt-2">';
            errorHtml += '<p class="mb-1"><strong>Possible solutions:</strong></p>';
            errorHtml += '<ul class="mb-0">';
            errorHtml += '<li>Verify the Account ID in your MetaApi dashboard</li>';
            errorHtml += '<li>Check if the account was deleted or moved</li>';
            errorHtml += '<li>Recreate the connection with the correct Account ID</li>';
            errorHtml += '<li>Ensure your API token has access to this account</li>';
            errorHtml += '</ul></div>';
        }
        
        errorHtml += '</div>';
        document.getElementById('monitorStatus').innerHTML = errorHtml;
    }
}
@endif

// Test Data Fetch
function testDataFetch() {
    const symbol = document.getElementById('data_symbol').value;
    const timeframe = document.getElementById('data_timeframe').value;
    const limit = document.getElementById('data_limit').value;
    const resultDiv = document.getElementById('dataResult');

    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching data...</div>';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-data-fetch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            connection_id: {{ $connection->id }},
            symbol: symbol,
            timeframe: timeframe,
            limit: parseInt(limit)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = `<div class="alert alert-success">
                <i class="fas fa-check"></i> Fetched ${data.count} candles successfully!
            </div>`;
            
            if (data.data && data.data.length > 0) {
                html += `<div class="table-responsive"><table class="table table-sm table-hover">
                    <thead class="thead-light sticky-top">
                        <tr><th>Time</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th></tr>
                    </thead><tbody>`;
                
                data.data.slice(0, 10).forEach(candle => {
                    const time = new Date(candle.timestamp).toLocaleString();
                    html += `<tr>
                        <td>${time}</td>
                        <td>${candle.open}</td>
                        <td>${candle.high}</td>
                        <td>${candle.low}</td>
                        <td>${candle.close}</td>
                        <td>${candle.volume || 'N/A'}</td>
                    </tr>`;
                });
                
                html += `</tbody></table></div>`;
                
                if (data.count > 10) {
                    html += `<p class="text-muted text-center">Showing first 10 of ${data.count} candles</p>`;
                }
            }
            
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

// Test Execution
function testExecution(testType) {
    const resultDiv = document.getElementById('executionResult');
    
    const labels = {
        'balance': 'Fetching account balance',
        'positions': 'Fetching open positions',
        'test_order': 'Testing order placement'
    };
    
    resultDiv.innerHTML = `<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> ${labels[testType]}...</div>`;

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-execution") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            connection_id: {{ $connection->id }},
            test_type: testType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> Test successful!
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

// Data Fetch Functions (One-time API calls)
function testFetchMarketData() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching market data...</div>';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-stream-market-data", $connection) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            symbol: document.getElementById('data_symbol')?.value || 'EURUSD',
            timeframe: document.getElementById('data_timeframe')?.value || 'H1'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> ${data.message}
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

function testFetchPositions() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching positions...</div>';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-stream-positions", $connection) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> ${data.message} (${data.count || 0} positions)
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

function testFetchOrders() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching orders...</div>';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-stream-orders", $connection) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> ${data.message} (${data.count || 0} orders)
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

function testFetchBalance() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching balance...</div>';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test-stream-balance", $connection) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> ${data.message}
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times"></i> Error: ${error.message}</div>`;
    });
}

@if(strtolower($connection->provider) === 'metaapi')
// Real-Time Streaming Functions with Start/Stop
let streamEventSources = {
    marketData: null,
    positions: null,
    orders: null,
    balance: null
};

function toggleStreamMarketData() {
    if (streamEventSources.marketData) {
        stopStreamMarketData();
    } else {
        startStreamMarketData();
    }
}

function startStreamMarketData() {
    const resultDiv = document.getElementById('streamResult');
    const btn = document.getElementById('streamMarketDataBtn');
    const text = document.getElementById('streamMarketDataText');
    
    const defaultSymbol = '{{ $connection->connection_type === 'FX_BROKER' ? 'XAUUSDc' : 'BTCUSDT' }}';
    const symbol = document.getElementById('data_symbol')?.value || defaultSymbol;
    const timeframe = document.getElementById('data_timeframe')?.value || 'H1';
    const streamUrl = '{{ route("admin.trading-management.config.exchange-connections.stream-market-data", $connection) }}' + 
        `?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(timeframe)}`;
    
    resultDiv.innerHTML = '<div class="text-info">🔴 <strong>Market Data Stream Started</strong><br><small>Waiting for data...</small></div>';
    btn.classList.remove('list-group-item-action');
    btn.classList.add('active');
    text.textContent = 'Stop Market Data Stream';
    
    streamEventSources.marketData = new EventSource(streamUrl);
    
    streamEventSources.marketData.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            const timestamp = new Date().toLocaleTimeString();
            resultDiv.innerHTML += `<div class="mt-2 p-2 bg-light border-left border-info">
                <small class="text-muted">[${timestamp}]</small> 
                <strong>${data.type || 'update'}:</strong><br>
                <pre style="margin: 0; font-size: 0.75rem;">${JSON.stringify(data, null, 2)}</pre>
            </div>`;
            resultDiv.scrollTop = resultDiv.scrollHeight;
        } catch (e) {
            resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Error parsing: ${e.message}</small></div>`;
        }
    };
    
    streamEventSources.marketData.onerror = function(error) {
        resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Connection error. Stream may have stopped.</small></div>`;
        stopStreamMarketData();
    };
}

function stopStreamMarketData() {
    if (streamEventSources.marketData) {
        streamEventSources.marketData.close();
        streamEventSources.marketData = null;
    }
    const btn = document.getElementById('streamMarketDataBtn');
    const text = document.getElementById('streamMarketDataText');
    btn.classList.remove('active');
    btn.classList.add('list-group-item-action');
    text.textContent = 'Start Market Data Stream';
}

function toggleStreamPositions() {
    if (streamEventSources.positions) {
        stopStreamPositions();
    } else {
        startStreamPositions();
    }
}

function startStreamPositions() {
    const resultDiv = document.getElementById('streamResult');
    const btn = document.getElementById('streamPositionsBtn');
    const text = document.getElementById('streamPositionsText');
    
    const streamUrl = '{{ route("admin.trading-management.config.exchange-connections.stream-positions", $connection) }}';
    
    resultDiv.innerHTML = '<div class="text-success">🔴 <strong>Positions Stream Started</strong><br><small>Waiting for updates...</small></div>';
    btn.classList.remove('list-group-item-action');
    btn.classList.add('active');
    text.textContent = 'Stop Positions Stream';
    
    streamEventSources.positions = new EventSource(streamUrl);
    
    streamEventSources.positions.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            const timestamp = new Date().toLocaleTimeString();
            resultDiv.innerHTML += `<div class="mt-2 p-2 bg-light border-left border-success">
                <small class="text-muted">[${timestamp}]</small> 
                <strong>${data.type || 'update'}:</strong><br>
                <pre style="margin: 0; font-size: 0.75rem;">${JSON.stringify(data, null, 2)}</pre>
            </div>`;
            resultDiv.scrollTop = resultDiv.scrollHeight;
        } catch (e) {
            resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Error parsing: ${e.message}</small></div>`;
        }
    };
    
    streamEventSources.positions.onerror = function(error) {
        resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Connection error. Stream may have stopped.</small></div>`;
        stopStreamPositions();
    };
}

function stopStreamPositions() {
    if (streamEventSources.positions) {
        streamEventSources.positions.close();
        streamEventSources.positions = null;
    }
    const btn = document.getElementById('streamPositionsBtn');
    const text = document.getElementById('streamPositionsText');
    btn.classList.remove('active');
    btn.classList.add('list-group-item-action');
    text.textContent = 'Start Positions Stream';
}

function toggleStreamOrders() {
    if (streamEventSources.orders) {
        stopStreamOrders();
    } else {
        startStreamOrders();
    }
}

function startStreamOrders() {
    const resultDiv = document.getElementById('streamResult');
    const btn = document.getElementById('streamOrdersBtn');
    const text = document.getElementById('streamOrdersText');
    
    const streamUrl = '{{ route("admin.trading-management.config.exchange-connections.stream-orders", $connection) }}';
    
    resultDiv.innerHTML = '<div class="text-warning">🔴 <strong>Orders Stream Started</strong><br><small>Waiting for updates...</small></div>';
    btn.classList.remove('list-group-item-action');
    btn.classList.add('active');
    text.textContent = 'Stop Order History Stream';
    
    streamEventSources.orders = new EventSource(streamUrl);
    
    streamEventSources.orders.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            const timestamp = new Date().toLocaleTimeString();
            resultDiv.innerHTML += `<div class="mt-2 p-2 bg-light border-left border-warning">
                <small class="text-muted">[${timestamp}]</small> 
                <strong>${data.type || 'update'}:</strong><br>
                <pre style="margin: 0; font-size: 0.75rem;">${JSON.stringify(data, null, 2)}</pre>
            </div>`;
            resultDiv.scrollTop = resultDiv.scrollHeight;
        } catch (e) {
            resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Error parsing: ${e.message}</small></div>`;
        }
    };
    
    streamEventSources.orders.onerror = function(error) {
        resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Connection error. Stream may have stopped.</small></div>`;
        stopStreamOrders();
    };
}

function stopStreamOrders() {
    if (streamEventSources.orders) {
        streamEventSources.orders.close();
        streamEventSources.orders = null;
    }
    const btn = document.getElementById('streamOrdersBtn');
    const text = document.getElementById('streamOrdersText');
    btn.classList.remove('active');
    btn.classList.add('list-group-item-action');
    text.textContent = 'Start Order History Stream';
}

function toggleStreamBalance() {
    if (streamEventSources.balance) {
        stopStreamBalance();
    } else {
        startStreamBalance();
    }
}

function startStreamBalance() {
    const resultDiv = document.getElementById('streamResult');
    const btn = document.getElementById('streamBalanceBtn');
    const text = document.getElementById('streamBalanceText');
    
    const streamUrl = '{{ route("admin.trading-management.config.exchange-connections.stream-balance", $connection) }}';
    
    resultDiv.innerHTML = '<div class="text-primary">🔴 <strong>Balance Stream Started</strong><br><small>Waiting for updates...</small></div>';
    btn.classList.remove('list-group-item-action');
    btn.classList.add('active');
    text.textContent = 'Stop Balance Stream';
    
    streamEventSources.balance = new EventSource(streamUrl);
    
    streamEventSources.balance.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            const timestamp = new Date().toLocaleTimeString();
            resultDiv.innerHTML += `<div class="mt-2 p-2 bg-light border-left border-primary">
                <small class="text-muted">[${timestamp}]</small> 
                <strong>${data.type || 'update'}:</strong><br>
                <pre style="margin: 0; font-size: 0.75rem;">${JSON.stringify(data, null, 2)}</pre>
            </div>`;
            resultDiv.scrollTop = resultDiv.scrollHeight;
        } catch (e) {
            resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Error parsing: ${e.message}</small></div>`;
        }
    };
    
    streamEventSources.balance.onerror = function(error) {
        resultDiv.innerHTML += `<div class="mt-2 text-danger"><small>[${new Date().toLocaleTimeString()}] Connection error. Stream may have stopped.</small></div>`;
        stopStreamBalance();
    };
}

function stopStreamBalance() {
    if (streamEventSources.balance) {
        streamEventSources.balance.close();
        streamEventSources.balance = null;
    }
    const btn = document.getElementById('streamBalanceBtn');
    const text = document.getElementById('streamBalanceText');
    btn.classList.remove('active');
    btn.classList.add('list-group-item-action');
    text.textContent = 'Start Balance Stream';
}

// Cleanup all streams on page unload
window.addEventListener('beforeunload', function() {
    Object.values(streamEventSources).forEach(eventSource => {
        if (eventSource) eventSource.close();
    });
});
@endif

// Connection management handlers
document.getElementById('testConnectionBtn')?.addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';

    fetch('{{ route("admin.trading-management.config.exchange-connections.test", $connection->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection test successful! ' + (data.message || ''));
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Connection test failed: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

document.getElementById('activateConnectionBtn')?.addEventListener('click', function() {
    if (!confirm('Are you sure you want to activate this connection?')) return;

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Activating...';
    
    fetch('{{ route("admin.trading-management.config.exchange-connections.activate", $connection->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection activated successfully!');
            location.reload();
        } else {
            alert('Activation failed: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

document.getElementById('deactivateConnectionBtn')?.addEventListener('click', function() {
    if (!confirm('Are you sure you want to deactivate this connection? This will stop all trading and data fetching.')) return;

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deactivating...';

    fetch('{{ route("admin.trading-management.config.exchange-connections.deactivate", $connection->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection deactivated successfully!');
            location.reload();
        } else {
            alert('Deactivation failed: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

document.getElementById('toggleCopyTradingBtn')?.addEventListener('click', function() {
    const enabled = {{ $connection->copy_trading_enabled ? 'true' : 'false' }};
    const action = enabled ? 'disable' : 'enable';
    
    if (!confirm(`Are you sure you want to ${action} copy trading for this connection?`)) return;

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    fetch('{{ route("admin.trading-management.config.exchange-connections.toggle-copy-trading", $connection->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Copy trading ' + action + 'd successfully!');
            location.reload();
        } else {
            alert('Failed: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

document.getElementById('generateAccountTokenBtn')?.addEventListener('click', function() {
    if (!confirm('Generate a new account-specific token? This token will be scoped to this account only.')) return;
    
    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    fetch('{{ route("admin.trading-management.config.exchange-connections.generate-account-token", $connection->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ validity_hours: 'Infinity' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Account token generated and saved successfully!');
            location.reload();
        } else {
            let message = data.message || 'Unknown error';
            if (data.requires_captcha) {
                message += '\n\nYou may need to generate the token manually from the MetaApi web interface due to CAPTCHA requirements.';
            }
            alert('Failed: ' + message);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});
</script>

<style>
.info-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}
.info-item:last-child {
    border-bottom: none;
}
.gap-2 > * {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
</style>

@endsection
