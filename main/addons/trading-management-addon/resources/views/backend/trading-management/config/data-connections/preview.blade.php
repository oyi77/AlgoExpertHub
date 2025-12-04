@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-eye"></i> Preview Data - {{ $connection->name }}</h4>
                    <a href="{{ route('admin.trading-management.config.data-connections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> {{ $connection->type }}</p>
                        <p><strong>Provider:</strong> {{ $connection->provider }}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge {{ $connection->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                {{ ucfirst($connection->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Last Connected:</strong> {{ $connection->last_connected_at ? $connection->last_connected_at->diffForHumans() : 'Never' }}</p>
                        <p><strong>Error Count:</strong> {{ $connection->error_count }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fetch Sample Data -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download"></i> Fetch Sample Data</h5>
            </div>
            <div class="card-body">
                <form id="fetchForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Symbol</label>
                                <input type="text" id="symbol" class="form-control" value="{{ $symbols[0] ?? 'EURUSD' }}" placeholder="EURUSD, BTCUSDT">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Timeframe</label>
                                <select id="timeframe" class="form-control">
                                    <option value="M1">1 Minute</option>
                                    <option value="M5">5 Minutes</option>
                                    <option value="M15">15 Minutes</option>
                                    <option value="M30">30 Minutes</option>
                                    <option value="H1" selected>1 Hour</option>
                                    <option value="H4">4 Hours</option>
                                    <option value="D1">1 Day</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Limit</label>
                                <input type="number" id="limit" class="form-control" value="100" min="1" max="1000">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-primary btn-block" onclick="fetchSampleData()">
                                    <i class="fas fa-download"></i> Fetch Data
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="fetchStatus"></div>
            </div>
        </div>

        <!-- Data Display -->
        <div class="card" id="dataCard" style="display:none;">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Fetched Data (<span id="dataCount">0</span> candles)</h5>
                    <div>
                        <button class="btn btn-sm btn-info" onclick="toggleView('table')">
                            <i class="fas fa-table"></i> Table View
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="toggleView('json')">
                            <i class="fas fa-code"></i> JSON View
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Table View -->
                <div id="tableView">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Open</th>
                                    <th>High</th>
                                    <th>Low</th>
                                    <th>Close</th>
                                    <th>Volume</th>
                                </tr>
                            </thead>
                            <tbody id="dataTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- JSON View -->
                <div id="jsonView" style="display:none;">
                    <div class="alert alert-secondary">
                        <h6>Sample Data Structure (First Candle):</h6>
                        <pre id="sampleJson" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                    <div class="alert alert-info">
                        <h6>Full Data (All Candles):</h6>
                        <pre id="fullJson" style="max-height: 500px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let fetchedData = [];

function fetchSampleData() {
    const symbol = document.getElementById('symbol').value;
    const timeframe = document.getElementById('timeframe').value;
    const limit = document.getElementById('limit').value;
    const statusDiv = document.getElementById('fetchStatus');

    if (!symbol) {
        alert('Please enter a symbol');
        return;
    }

    statusDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Fetching data...</div>';

    fetch('{{ route("admin.trading-management.config.data-connections.fetch-sample") }}', {
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
            statusDiv.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check"></i> Successfully fetched ${data.count} candles
            </div>`;
            
            displayData(data.data, data.sample);
            document.getElementById('dataCard').style.display = 'block';
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-times"></i> ${data.message}
            </div>`;
        }
    })
    .catch(error => {
        statusDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times"></i> Error: ${error.message}
        </div>`;
    });
}

function displayData(data, sample) {
    fetchedData = data;
    document.getElementById('dataCount').textContent = data.length;

    // Update table
    const tbody = document.getElementById('dataTableBody');
    tbody.innerHTML = '';
    
    data.slice(0, 50).forEach(candle => {
        const timestamp = new Date(candle.timestamp).toLocaleString();
        tbody.innerHTML += `
            <tr>
                <td>${timestamp}</td>
                <td>${candle.open}</td>
                <td>${candle.high}</td>
                <td>${candle.low}</td>
                <td>${candle.close}</td>
                <td>${candle.volume || 'N/A'}</td>
            </tr>
        `;
    });

    if (data.length > 50) {
        tbody.innerHTML += `<tr><td colspan="6" class="text-center text-muted">... ${data.length - 50} more rows (showing first 50)</td></tr>`;
    }

    // Update JSON views
    document.getElementById('sampleJson').textContent = JSON.stringify(sample, null, 2);
    document.getElementById('fullJson').textContent = JSON.stringify(data, null, 2);
}

function toggleView(view) {
    document.getElementById('tableView').style.display = view === 'table' ? 'block' : 'none';
    document.getElementById('jsonView').style.display = view === 'json' ? 'block' : 'none';
}
</script>
@endsection

