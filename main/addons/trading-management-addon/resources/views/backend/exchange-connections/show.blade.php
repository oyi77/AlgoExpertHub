@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Connection Info -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt"></i> {{ $connection->name }}</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto Exchange' : 'FX Broker' }}</p>
                        <p><strong>Provider:</strong> {{ strtoupper($connection->provider) }}</p>
                        <p><strong>Status:</strong> 
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
                            <span class="badge {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </p>
                        <p><strong>Purpose:</strong> <span class="badge badge-info">{{ $connection->getPurposeLabel() }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data Fetching:</strong> 
                            <i class="fas {{ $connection->is_active ? 'fa-check text-success' : 'fa-times text-muted' }}"></i>
                            @if($connection->last_data_fetch_at)
                            <small class="text-muted">(Last: {{ $connection->last_data_fetch_at->diffForHumans() }})</small>
                            @endif
                        </p>
                        <p><strong>Trade Execution:</strong> 
                            <i class="fas {{ $connection->is_active ? 'fa-check text-success' : 'fa-times text-muted' }}"></i>
                            @if($connection->last_trade_execution_at)
                            <small class="text-muted">(Last: {{ $connection->last_trade_execution_at->diffForHumans() }})</small>
                            @endif
                        </p>
                        <p><strong>Preset:</strong> {{ $connection->preset->name ?? 'None' }}</p>
                        <p><strong>Copy Trading:</strong> 
                            <span class="badge {{ $connection->copy_trading_enabled ? 'badge-success' : 'badge-secondary' }}">
                                {{ $connection->copy_trading_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </p>
                    </div>
                </div>
                <div>
                    @if($connection->status !== 'active' || !$connection->is_active)
                    <button type="button" class="btn btn-warning" id="testConnectionBtn">
                        <i class="fas fa-vial"></i> Test Connection
                    </button>
                    @if($connection->status === 'inactive' && $connection->last_tested_at)
                    <button type="button" class="btn btn-success" id="activateConnectionBtn">
                        <i class="fas fa-power-off"></i> Activate Connection
                    </button>
                    @endif
                    @else
                    <button type="button" class="btn btn-danger" id="deactivateConnectionBtn">
                        <i class="fas fa-power-off"></i> Deactivate
                    </button>
                    @endif
                    @if($connection->canExecuteTrades())
                    <button type="button" class="btn btn-{{ $connection->copy_trading_enabled ? 'warning' : 'success' }}" id="toggleCopyTradingBtn">
                        <i class="fas fa-users"></i> {{ $connection->copy_trading_enabled ? 'Disable' : 'Enable' }} Copy Trading
                    </button>
                    @endif
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tradingFlowModal">
                        <i class="fas fa-project-diagram"></i> See Trading Flow
                    </button>
                    @if(strtolower($connection->provider) === 'metaapi')
                    <button type="button" class="btn btn-info" id="monitorConnectionBtn">
                        <i class="fas fa-tachometer-alt"></i> Monitor Connection
                    </button>
                    <button type="button" class="btn btn-secondary" id="generateAccountTokenBtn" title="Generate account-specific token for secure monitoring">
                        <i class="fas fa-key"></i> Generate Account Token
                    </button>
                    @endif
                    <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $connection) }}" class="btn btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>


        <div class="row">
            <!-- Data Fetching Tests -->
            @if($connection->is_active)
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Data Fetching Tests</h5>
                    </div>
                    <div class="card-body">
                        <form id="testDataForm">
                            <div class="form-group">
                                <label>Symbol</label>
                                <input type="text" id="data_symbol" class="form-control" value="BTCUSDT" placeholder="BTCUSDT, EURUSD">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Timeframe</label>
                                        <select id="data_timeframe" class="form-control">
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
                                    <div class="form-group">
                                        <label>Limit</label>
                                        <input type="number" id="data_limit" class="form-control" value="100" min="1" max="1000">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-block" onclick="testDataFetch()">
                                <i class="fas fa-play"></i> Fetch Sample Data
                            </button>
                        </form>

                        <div id="dataResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Trade Execution Tests -->
            @if($connection->is_active)
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Trade Execution Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <button class="list-group-item list-group-item-action" onclick="testExecution('balance')">
                                <i class="fas fa-wallet"></i> Test Fetch Balance
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testExecution('positions')">
                                <i class="fas fa-chart-line"></i> Test Fetch Positions
                            </button>
                            <button class="list-group-item list-group-item-action" onclick="testExecution('test_order')">
                                <i class="fas fa-shopping-cart"></i> Test Place Order (Dry Run)
                            </button>
                        </div>

                        <div id="executionResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            @endif

            <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            </div>
        </div>
    </div>
</div>

<script>
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
                html += `<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light sticky-top">
                            <tr>
                                <th>Time</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                data.data.slice(0, 20).forEach(candle => {
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
                
                if (data.count > 20) {
                    html += `<p class="text-muted text-center">Showing first 20 of ${data.count} candles</p>`;
                }
            }
            
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-times"></i> ${data.message}
            </div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times"></i> Error: ${error.message}
        </div>`;
    });
}

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
                <pre class="mt-2">${JSON.stringify(data.data, null, 2)}</pre>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-times"></i> ${data.message}
            </div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times"></i> Error: ${error.message}
        </div>`;
    });
}

@if(strtolower($connection->provider) === 'metaapi')
@php
    $credentials = $connection->credentials ?? [];
    $metaApiAccountId = $credentials['account_id'] ?? '';
@endphp
let monitorEventSource = null;
const metaApiAccountId = '{{ $metaApiAccountId }}';

document.getElementById('monitorConnectionBtn').addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    
    if (monitorEventSource && monitorEventSource.readyState === EventSource.OPEN) {
        // Close existing connection
        monitorEventSource.close();
        monitorEventSource = null;
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-info');
        return;
    }
    
    // Open monitoring modal
    $('#monitorModal').modal('show');
    
    // Start SSE connection
    if (!metaApiAccountId) {
        alert('MetaApi Account ID not found');
        return;
    }
    
    btn.innerHTML = '<i class="fas fa-stop"></i> Stop Monitoring';
    btn.classList.remove('btn-info');
    btn.classList.add('btn-danger');
    
    const monitorUrl = '{{ route("admin.trading-management.config.exchange-connections.monitor-metaapi", $connection) }}';
    monitorEventSource = new EventSource(monitorUrl);
    
    monitorEventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateMonitorDisplay(data);
    };
    
    monitorEventSource.onerror = function(error) {
        console.error('Monitor error:', error);
        document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-danger">Connection error. Retrying...</div>';
    };
    
    monitorEventSource.onopen = function() {
        document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-info">Connected to MetaApi. Waiting for data...</div>';
    };
});

const monitorModalElem = document.getElementById('monitorModal');
if (monitorModalElem) {
    monitorModalElem.addEventListener('hidden.bs.modal', function() {
        if (monitorEventSource) {
            monitorEventSource.close();
            monitorEventSource = null;
        }
        const btn = document.getElementById('monitorConnectionBtn');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-tachometer-alt"></i> Monitor Connection';
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-info');
        }
    });
}

function updateMonitorDisplay(data) {
    if (data.type === 'connected') {
        document.getElementById('monitorStatus').innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
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
</script>

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
                    <!-- Step 1: Fetch Data -->
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

                    <!-- Step 2: Parse Data -->
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

                    <!-- Step 3: Aggregate Data -->
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body">
                                <i class="fas fa-database fa-2x text-warning"></i>
                                <p class="mt-2 mb-0"><strong>3. Aggregate</strong></p>
                                <small class="text-muted">Store & combine</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-auto d-flex align-items-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>

                    <!-- Step 4: Filter Data -->
                    <div class="col-md-2">
                        <div class="card border-primary">
                            <div class="card-body">
                                <i class="fas fa-filter fa-2x text-primary"></i>
                                <p class="mt-2 mb-0"><strong>4. Filter</strong></p>
                                <small class="text-muted">Indicators</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row text-center mt-3">
                    <!-- Step 5: Analyze with AI -->
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

                    <!-- Step 6: Risk Management -->
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

                    <!-- Step 7: Execute Trade -->
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
// Test Connection
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
            setTimeout(() => {
                location.reload(); 
            }, 1500);
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

// Activate Connection
document.getElementById('activateConnectionBtn')?.addEventListener('click', function() {
    if (!confirm('Are you sure you want to activate this connection?')) {

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
        return;
    }

});

// Deactivate Connection
document.getElementById('deactivateConnectionBtn')?.addEventListener('click', function() {
    if (!confirm('Are you sure you want to deactivate this connection? This will stop all trading and data fetching.')) {
        return;
    }

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

// Toggle Copy Trading
document.getElementById('toggleCopyTradingBtn')?.addEventListener('click', function() {
    const enabled = {{ $connection->copy_trading_enabled ? 'true' : 'false' }};
    const action = enabled ? 'disable' : 'enable';
    
    if (!confirm(`Are you sure you want to ${action} copy trading for this connection?`)) {
        return;
    }

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

// Generate Account Token
document.getElementById('generateAccountTokenBtn')?.addEventListener('click', function() {
    if (confirm('Generate a new account-specific token? This token will be scoped to this account only and can be used for monitoring.\n\nNote: If MetaApi requires CAPTCHA verification, you may need to generate the token manually from the MetaApi web interface.')) {
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
            body: JSON.stringify({
                validity_hours: 'Infinity'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Account token generated and saved successfully!\n\nThe connection will now use this account token for monitoring, which is more secure than using the main API token.');
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
        return;
    }
});
</script>

@endsection

