@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-robot"></i> {{ $title }}</h4>
                    <div>
                        <a href="{{ route('admin.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-secondary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Bot Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Name</th>
                                <td>{{ $bot->name }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $bot->description ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Owner</th>
                                <td>
                                    @if($bot->admin)
                                        <span class="badge bg-info">Admin: {{ $bot->admin->username }}</span>
                                    @elseif($bot->user)
                                        <span class="badge bg-primary">User: {{ $bot->user->username }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($bot->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                    @if($bot->is_paper_trading)
                                        <span class="badge bg-warning">Paper Trading</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Configuration</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Exchange Connection</th>
                                <td>{{ $bot->exchangeConnection->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Trading Preset</th>
                                <td>{{ $bot->tradingPreset->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Filter Strategy</th>
                                <td>{{ $bot->filterStrategy->name ?? 'None' }}</td>
                            </tr>
                            <tr>
                                <th>AI Model Profile</th>
                                <td>{{ $bot->aiModelProfile->name ?? 'None' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Statistics</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6>Total Executions</h6>
                                        <h3>{{ $bot->total_executions }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6>Successful</h6>
                                        <h3 class="text-success">{{ $bot->successful_executions }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6>Win Rate</h6>
                                        <h3>{{ number_format($bot->win_rate, 1) }}%</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6>Total Profit</h6>
                                        <h3 class="{{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($bot->total_profit, 2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($executions->count() > 0)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Recent Executions</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Signal</th>
                                        <th>Symbol</th>
                                        <th>Side</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($executions as $execution)
                                        <tr>
                                            <td>{{ $execution->signal->title ?? 'N/A' }}</td>
                                            <td>{{ $execution->symbol ?? 'N/A' }}</td>
                                            <td>{{ $execution->side ? strtoupper($execution->side) : 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ in_array($execution->status, ['SUCCESS', 'filled']) ? 'success' : 'warning' }}">
                                                    {{ ucfirst($execution->status ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>{{ $execution->created_at ? $execution->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $executions->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
