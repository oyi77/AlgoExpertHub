@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4><i class="fas fa-history"></i> Execution History - {{ $bot->name }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Bot
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Statistics Summary --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Executions</h6>
                        <h3 class="mb-0">{{ $statistics['total_executions'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Successful</h6>
                        <h3 class="mb-0">{{ $statistics['successful'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Failed</h6>
                        <h3 class="mb-0">{{ $statistics['failed'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Success Rate</h6>
                        <h3 class="mb-0">{{ number_format($statistics['success_rate'] ?? 0, 2) }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="row mb-4">
            <div class="col-md-12">
                <form method="GET" action="{{ route('user.trading-management.trading-bots.executions', $bot->id) }}" class="d-flex flex-wrap align-items-end" style="gap: 1rem;">
                    <div class="form-group mb-0">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="executed" {{ request('status') == 'executed' ? 'selected' : '' }}>Executed</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Symbol</label>
                        <input type="text" name="symbol" class="form-control" value="{{ request('symbol') }}" placeholder="e.g., EURUSD">
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Execution History Table --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Execution History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Signal</th>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Quantity</th>
                                        <th>Entry Price</th>
                                        <th>Status</th>
                                        <th>Executed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($executions as $execution)
                                    <tr>
                                        <td>{{ $execution->id }}</td>
                                        <td>
                                            @if($execution->signal)
                                                <a href="{{ route('user.trading-management.signals.show', $execution->signal_id) }}">
                                                    {{ Str::limit($execution->signal->title, 30) }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $execution->symbol }}</td>
                                        <td>
                                            <span class="badge {{ $execution->direction == 'buy' || $execution->direction == 'long' ? 'bg-success' : 'bg-danger' }}">
                                                {{ strtoupper($execution->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ $execution->quantity }}</td>
                                        <td>{{ number_format($execution->entry_price, 5) }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($execution->status == 'executed') bg-success
                                                @elseif($execution->status == 'failed') bg-danger
                                                @elseif($execution->status == 'pending') bg-warning
                                                @else bg-secondary
                                                @endif">
                                                {{ ucfirst($execution->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $execution->executed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info" onclick="viewExecutionDetails({{ $execution->id }})">
                                                <i class="fa fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No executions found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-3">
                            {{ $executions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Execution Details Modal --}}
<div class="modal fade" id="executionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Execution Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="executionDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function viewExecutionDetails(executionId) {
        $('#executionDetailsModal').modal('show');
        $('#executionDetailsContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
        
        fetch(`{{ route('user.trading-management.trading-bots.executions', $bot->id) }}?execution_id=${executionId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.execution) {
                const exec = data.execution;
                let html = `
                    <table class="table">
                        <tr><th>Execution ID</th><td>${exec.id}</td></tr>
                        <tr><th>Signal</th><td>${exec.signal_title || 'N/A'}</td></tr>
                        <tr><th>Symbol</th><td>${exec.symbol}</td></tr>
                        <tr><th>Direction</th><td>${exec.direction.toUpperCase()}</td></tr>
                        <tr><th>Quantity</th><td>${exec.quantity}</td></tr>
                        <tr><th>Entry Price</th><td>${exec.entry_price}</td></tr>
                        <tr><th>Stop Loss</th><td>${exec.sl_price || 'N/A'}</td></tr>
                        <tr><th>Take Profit</th><td>${exec.tp_price || 'N/A'}</td></tr>
                        <tr><th>Status</th><td><span class="badge bg-${exec.status == 'executed' ? 'success' : 'danger'}">${exec.status}</span></td></tr>
                        <tr><th>Executed At</th><td>${exec.executed_at || 'N/A'}</td></tr>
                        ${exec.error_message ? `<tr><th>Error</th><td class="text-danger">${exec.error_message}</td></tr>` : ''}
                    </table>
                `;
                $('#executionDetailsContent').html(html);
            } else {
                $('#executionDetailsContent').html('<div class="alert alert-danger">Failed to load execution details</div>');
            }
        })
        .catch(error => {
            $('#executionDetailsContent').html('<div class="alert alert-danger">Error loading details</div>');
        });
    }
</script>
@endpush
@endsection

