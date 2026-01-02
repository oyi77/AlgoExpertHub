@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Stats -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total</h6>
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
                <h4 class="card-title mb-0"><i class="fas fa-history"></i> Copy Trading Executions</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.copy-trading.executions') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($executions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Trader</th>
                                <th>Follower</th>
                                <th>Symbol</th>
                                <th>Direction</th>
                                <th>Lot Size</th>
                                <th>Entry</th>
                                <th>Status</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executions as $exec)
                            <tr>
                                <td>{{ $exec->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $exec->subscription->trader->username ?? 'N/A' }}</td>
                                <td>{{ $exec->subscription->follower->username ?? 'N/A' }}</td>
                                <td>{{ $exec->symbol }}</td>
                                <td>
                                    @if(in_array($exec->direction, ['buy', 'long']))
                                    <span class="badge badge-success">{{ strtoupper($exec->direction) }}</span>
                                    @else
                                    <span class="badge badge-danger">{{ strtoupper($exec->direction) }}</span>
                                    @endif
                                </td>
                                <td>{{ $exec->lot_size }}</td>
                                <td>{{ $exec->entry_price }}</td>
                                <td>
                                    @if($exec->status === 'success')
                                    <span class="badge badge-success">Success</span>
                                    @elseif($exec->status === 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                    @else
                                    <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($exec->error_message)
                                    <span class="text-danger" title="{{ $exec->error_message }}">
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

