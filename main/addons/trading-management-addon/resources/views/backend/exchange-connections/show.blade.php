@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Connection Info -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt"></i> {{ $connection->name }}</h4>
                    <div>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.edit', $connection) }}" class="btn btn-info">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto Exchange' : 'FX Broker' }}</p>
                        <p><strong>Provider:</strong> {{ strtoupper($connection->provider) }}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge {{ $connection->status === 'connected' ? 'badge-success' : 'badge-warning' }}">
                                {{ ucfirst($connection->status) }}
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Complete Trading Flow -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-project-diagram"></i> Complete Trading Flow</h5>
            </div>
            <div class="card-body">
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
</script>
@endsection

