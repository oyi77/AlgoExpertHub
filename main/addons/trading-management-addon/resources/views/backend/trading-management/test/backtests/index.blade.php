@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-history"></i> Backtests</h4>
                    <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Backtest
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="symbol" class="form-control" value="{{ request('symbol') }}" placeholder="Symbol">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.test.backtests.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($backtests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Symbol</th>
                                <th>Timeframe</th>
                                <th>Period</th>
                                <th>Initial Balance</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Created</th>
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
                                <td>${{ number_format($backtest->initial_balance, 2) }}</td>
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
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $backtest->progress_percent }}%">
                                            {{ $backtest->progress_percent }}%
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $backtest->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.trading-management.test.backtests.show', $backtest) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($backtest->status !== 'running')
                                        <form action="{{ route('admin.trading-management.test.backtests.destroy', $backtest) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $backtests->links() }}
                @else
                <div class="alert alert-info">
                    No backtests found. <a href="{{ route('admin.trading-management.test.backtests.create') }}">Create your first backtest</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
