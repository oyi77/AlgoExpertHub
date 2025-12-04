@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Stats Row -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Executions</h6>
                        <h3>{{ $stats['total'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Success</h6>
                        <h3 class="text-success">{{ $stats['success'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Failed</h6>
                        <h3 class="text-danger">{{ $stats['failed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Pending</h6>
                        <h3 class="text-warning">{{ $stats['pending'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-list"></i> Execution Log</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="SUCCESS" {{ request('status') === 'SUCCESS' ? 'selected' : '' }}>Success</option>
                                <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.operations.executions') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($executions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Connection</th>
                                <th>Signal</th>
                                <th>Symbol</th>
                                <th>Direction</th>
                                <th>Lot Size</th>
                                <th>Entry Price</th>
                                <th>Status</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executions as $execution)
                            <tr>
                                <td>{{ $execution->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $execution->connection->name ?? 'N/A' }}</td>
                                <td>{{ $execution->signal->title ?? 'N/A' }}</td>
                                <td>{{ $execution->symbol }}</td>
                                <td>
                                    @if($execution->direction === 'BUY' || $execution->direction === 'LONG')
                                    <span class="badge badge-success">{{ $execution->direction }}</span>
                                    @else
                                    <span class="badge badge-danger">{{ $execution->direction }}</span>
                                    @endif
                                </td>
                                <td>{{ $execution->lot_size }}</td>
                                <td>{{ $execution->entry_price }}</td>
                                <td>
                                    @if($execution->status === 'SUCCESS')
                                    <span class="badge badge-success">Success</span>
                                    @elseif($execution->status === 'FAILED')
                                    <span class="badge badge-danger">Failed</span>
                                    @else
                                    <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($execution->error_message)
                                    <span class="text-danger" title="{{ $execution->error_message }}">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $executions->links() }}
                @else
                <div class="alert alert-info">No executions found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

