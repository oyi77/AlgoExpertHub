@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-brain"></i> AI Decision Logs</h4>
                    <a href="{{ route('admin.trading-management.strategy.ai-decisions.export', request()->all()) }}" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.trading-management.strategy.ai-decisions.index') }}">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Symbol</label>
                                <input type="text" name="symbol" value="{{ request('symbol') }}" placeholder="e.g. BTC/USDT" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Action</label>
                                <select name="action" class="form-control">
                                    <option value="">All Actions</option>
                                    <option value="BUY" {{ request('action') == 'BUY' ? 'selected' : '' }}>BUY</option>
                                    <option value="SELL" {{ request('action') == 'SELL' ? 'selected' : '' }}>SELL</option>
                                    <option value="HOLD" {{ request('action') == 'HOLD' ? 'selected' : '' }}>HOLD</option>
                                    <option value="NEUTRAL" {{ request('action') == 'NEUTRAL' ? 'selected' : '' }}>NEUTRAL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Connection ID</label>
                                <input type="text" name="ai_connection_id" value="{{ request('ai_connection_id') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Symbol</th>
                                <th>Timeframe</th>
                                <th>Action</th>
                                <th>Confidence</th>
                                <th>Model Used</th>
                                <th>Reasoning</th>
                                <th>Connection</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($decisions as $decision)
                                <tr>
                                    <td>{{ $decision->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td><strong>{{ $decision->symbol }}</strong></td>
                                    <td>{{ $decision->timeframe }}</td>
                                    <td>
                                        @if($decision->action === 'BUY')
                                            <span class="badge badge-success">BUY</span>
                                        @elseif($decision->action === 'SELL')
                                            <span class="badge badge-danger">SELL</span>
                                        @elseif($decision->action === 'HOLD')
                                            <span class="badge badge-warning">HOLD</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $decision->action }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $decision->confidence }}%;" aria-valuenow="{{ $decision->confidence }}" aria-valuemin="0" aria-valuemax="100">{{ $decision->confidence }}%</div>
                                        </div>
                                    </td>
                                    <td>{{ $decision->model_used ?? 'N/A' }}</td>
                                    <td title="{{ $decision->reasoning }}">{{ Str::limit($decision->reasoning, 50) }}</td>
                                    <td>{{ $decision->ai_connection_id ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.trading-management.operations.executions', ['date_from' => $decision->created_at->format('Y-m-d'), 'date_to' => $decision->created_at->format('Y-m-d')]) }}" class="btn btn-sm btn-info" title="View Executions">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No AI decisions found matching your criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $decisions->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
