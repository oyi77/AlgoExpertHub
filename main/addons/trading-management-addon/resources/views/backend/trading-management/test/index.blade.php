@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-flask"></i> Trading Test & Backtesting</h3>
                <p class="text-muted mb-0">Test strategies on historical data and analyze performance</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Backtests</h6>
                        <h3>{{ $stats['total_backtests'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Completed</h6>
                        <h3 class="text-success">{{ $stats['completed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">Running</h6>
                        <h3 class="text-info">{{ $stats['running'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-download-data" data-toggle="tab">
                            <i class="fas fa-download"></i> Download Data
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-create" data-toggle="tab">
                            <i class="fas fa-plus"></i> Create Backtest
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-reports" data-toggle="tab">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-results" data-toggle="tab">
                            <i class="fas fa-chart-bar"></i> Results
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Download Data Tab -->
                    <div class="tab-pane fade show active" id="tab-download-data">
                        <h5 class="mb-3"><i class="fas fa-download"></i> Download Historical Data</h5>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Download historical market data for backtesting, machine learning, or AI training. Choose format and date range.
                        </div>

                        @php
                            $dataConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('is_active', 1)
                                ->where('is_active', 1)
                                ->where('status', 'connected')
                                ->get();
                        @endphp

                        @if($dataConnections->count() > 0)
                        <form id="downloadDataForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Data Source</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>Exchange Connection <span class="text-danger">*</span></label>
                                                <select id="download_connection_id" class="form-control" required>
                                                    <option value="">Select Connection</option>
                                                    @foreach($dataConnections as $conn)
                                                    <option value="{{ $conn->id }}">
                                                        {{ $conn->name }} ({{ strtoupper($conn->provider) }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Symbol <span class="text-danger">*</span></label>
                                                <input type="text" id="download_symbol" class="form-control" placeholder="BTCUSDT, EURUSD" required>
                                            </div>

                                            <div class="form-group">
                                                <label>Timeframe <span class="text-danger">*</span></label>
                                                <select id="download_timeframe" class="form-control" required>
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
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Date Range & Format</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>Download Method</label>
                                                <select id="download_method" class="form-control" onchange="toggleDateMethod()">
                                                    <option value="limit">By Candle Count</option>
                                                    <option value="date_range">By Date Range</option>
                                                </select>
                                            </div>

                                            <div id="limitMethod">
                                                <div class="form-group">
                                                    <label>Number of Candles <span class="text-danger">*</span></label>
                                                    <input type="number" id="download_limit" class="form-control" value="10000" min="100" max="100000">
                                                    <small class="text-muted">Max: 100,000 candles</small>
                                                </div>
                                            </div>

                                            <div id="dateRangeMethod" style="display:none;">
                                                <div class="form-group">
                                                    <label>Start Date</label>
                                                    <input type="date" id="download_start_date" class="form-control" value="{{ now()->subMonths(6)->format('Y-m-d') }}">
                                                </div>
                                                <div class="form-group">
                                                    <label>End Date</label>
                                                    <input type="date" id="download_end_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Export Format</label>
                                                <select id="download_format" class="form-control">
                                                    <option value="csv">CSV (Excel Compatible)</option>
                                                    <option value="json">JSON (Programming)</option>
                                                    <option value="pandas">Pandas DataFrame (Python)</option>
                                                    <option value="mt4">MT4 HST Format</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">Use Cases:</h6>
                                            <small class="text-muted">
                                                • Backtest strategies on historical data<br>
                                                • Train machine learning models<br>
                                                • AI pattern recognition learning<br>
                                                • Offline analysis and research
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <button type="button" class="btn btn-success btn-lg" onclick="downloadHistoricalData()">
                                                <i class="fas fa-download"></i> Download Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="downloadProgress" class="mt-3"></div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No active data connections found. 
                            <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create a connection</a> with data fetching enabled in Trading Configuration.
                        </div>
                        @endif
                    </div>

                    <!-- Create Backtest Tab -->
                    <div class="tab-pane fade" id="tab-create">
                        <h5 class="mb-3"><i class="fas fa-plus"></i> Create New Backtest</h5>
                        <div class="text-center py-4">
                            <i class="fas fa-flask fa-3x text-primary mb-3"></i>
                            <p class="text-muted">Start a new backtest to test your strategies on historical data</p>
                            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i> Create New Backtest
                            </a>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div class="tab-pane fade" id="tab-reports">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Backtest Reports</h5>
                            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Backtest
                            </a>
                        </div>

                        @if($backtests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Symbol</th>
                                        <th>Timeframe</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backtests as $backtest)
                                    <tr>
                                        <td><strong>{{ $backtest->name }}</strong></td>
                                        <td>{{ $backtest->symbol }}</td>
                                        <td>{{ $backtest->timeframe }}</td>
                                        <td>{{ $backtest->start_date->format('Y-m-d') }} - {{ $backtest->end_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($backtest->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                            @elseif($backtest->status === 'running')
                                            <span class="badge badge-info">Running</span>
                                            @elseif($backtest->status === 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                            @else
                                            <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px; min-width: 80px;">
                                                <div class="progress-bar" style="width: {{ $backtest->progress_percent }}%">
                                                    {{ $backtest->progress_percent }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.test.backtests.show', $backtest) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $backtests->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No backtests found. <a href="{{ route('admin.trading-management.test.backtests.create') }}">Create your first backtest</a>.
                        </div>
                        @endif
                    </div>

                    <!-- Results Tab -->
                    <div class="tab-pane fade" id="tab-results">
                        <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Backtest Results</h5>

                        @if($results->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Backtest</th>
                                        <th>Entry Time</th>
                                        <th>Direction</th>
                                        <th>Entry</th>
                                        <th>Exit</th>
                                        <th>P&L</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $result)
                                    <tr>
                                        <td>{{ $result->backtest->name ?? 'N/A' }}</td>
                                        <td>{{ $result->entry_time->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $result->direction === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($result->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $result->entry_price }}</td>
                                        <td>{{ $result->exit_price }}</td>
                                        <td class="{{ $result->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($result->pnl, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $results->links() }}
                        <div class="mt-3">
                            <a href="{{ route('admin.trading-management.test.results.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View All Results
                            </a>
                        </div>
                        @else
                        <div class="alert alert-info">No results yet.</div>
                        @endif
                    </div>

                    <!-- Analytics Tab (iframe or redirect) -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Performance Analytics</h5>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <p class="text-muted">View detailed copy trading analytics and performance charts</p>
                            <a href="{{ route('admin.trading-management.copy-trading.analytics') }}" class="btn btn-primary btn-lg">
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
function toggleDateMethod() {
    const method = document.getElementById('download_method').value;
    document.getElementById('limitMethod').style.display = method === 'limit' ? 'block' : 'none';
    document.getElementById('dateRangeMethod').style.display = method === 'date_range' ? 'block' : 'none';
}

function downloadHistoricalData() {
    const connectionId = document.getElementById('download_connection_id').value;
    const symbol = document.getElementById('download_symbol').value;
    const timeframe = document.getElementById('download_timeframe').value;
    const method = document.getElementById('download_method').value;
    const format = document.getElementById('download_format').value;
    const progressDiv = document.getElementById('downloadProgress');

    if (!connectionId || !symbol) {
        alert('Please select connection and enter symbol');
        return;
    }

    const data = {
        connection_id: connectionId,
        symbol: symbol,
        timeframe: timeframe,
        format: format
    };

    if (method === 'limit') {
        data.limit = parseInt(document.getElementById('download_limit').value);
    } else {
        data.start_date = document.getElementById('download_start_date').value;
        data.end_date = document.getElementById('download_end_date').value;
    }

    progressDiv.innerHTML = `<div class="alert alert-info">
        <i class="fas fa-spinner fa-spin"></i> Downloading historical data... This may take a few moments.
        <div class="progress mt-2">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
    </div>`;

    fetch('{{ route("admin.trading-management.test.download-data") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (response.ok) {
            return response.blob().then(blob => ({
                blob: blob,
                filename: response.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g, '') || `${symbol}_${timeframe}_data.${format}`
            }));
        }
        return response.json().then(err => Promise.reject(err));
    })
    .then(({ blob, filename }) => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

        progressDiv.innerHTML = `<div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <strong>Download Complete!</strong>
            <p class="mb-0 mt-2">File: <strong>${filename}</strong></p>
            <p class="mb-0">Format: <strong>${format.toUpperCase()}</strong></p>
            <p class="mb-0 mt-2"><small>You can now use this data for backtesting, ML training, or AI analysis.</small></p>
        </div>`;
    })
    .catch(error => {
        progressDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> <strong>Download Failed</strong>
            <p class="mb-0">${error.message || 'An error occurred'}</p>
        </div>`;
    });
}
</script>
@endpush
@endsection
