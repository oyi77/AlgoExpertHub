@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-control" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="executed">Executed</option>
                                    <option value="failed">Failed</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        @if($executions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Trader</th>
                                        <th>Original Qty</th>
                                        <th>Copied Qty</th>
                                        <th>Status</th>
                                        <th>P&L</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($executions as $execution)
                                    <tr>
                                        <td>{{ $execution->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($execution->trader)
                                                {{ $execution->trader->username ?? $execution->trader->email ?? 'Trader #' . $execution->trader_id }}
                                            @else
                                                Trader #{{ $execution->trader_id }}
                                            @endif
                                        </td>
                                        <td>{{ number_format($execution->original_quantity, 4) }}</td>
                                        <td>{{ number_format($execution->copied_quantity, 4) }}</td>
                                        <td>
                                            @if($execution->status === 'executed')
                                                <span class="badge badge-success">Executed</span>
                                            @elseif($execution->status === 'failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-warning">{{ ucfirst($execution->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($execution->followerPosition && $execution->followerPosition->pnl)
                                                <span class="{{ $execution->followerPosition->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $execution->followerPosition->pnl >= 0 ? '+' : '' }}{{ number_format($execution->followerPosition->pnl, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($execution->error_message)
                                                <button class="btn btn-sm btn-info" data-toggle="tooltip" title="{{ $execution->error_message }}">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            {{ $executions->links() }}
                        </div>
                        @else
                        <div class="alert alert-info">
                            No copy trading history yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
