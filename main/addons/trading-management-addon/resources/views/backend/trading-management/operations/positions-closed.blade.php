@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Stats Row -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Closed</h6>
                        <h3>{{ $stats['total_closed'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Total Profit</h6>
                        <h3 class="text-success">${{ number_format($stats['total_profit'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Total Loss</h6>
                        <h3 class="text-danger">${{ number_format($stats['total_loss'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Win Rate</h6>
                        <h3>{{ number_format($stats['win_rate'], 2) }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-history"></i> Closed Positions</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" name="symbol" class="form-control" value="{{ request('symbol') }}" placeholder="Symbol">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.operations.positions.closed') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($positions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Opened</th>
                                <th>Closed</th>
                                <th>Symbol</th>
                                <th>Direction</th>
                                <th>Entry</th>
                                <th>Exit</th>
                                <th>Lot Size</th>
                                <th>Duration</th>
                                <th>P&L</th>
                                <th>Close Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($positions as $position)
                            <tr>
                                <td>{{ $position->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $position->closed_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $position->symbol }}</td>
                                <td>
                                    @if(in_array($position->direction, ['BUY', 'LONG']))
                                    <span class="badge badge-success">{{ $position->direction }}</span>
                                    @else
                                    <span class="badge badge-danger">{{ $position->direction }}</span>
                                    @endif
                                </td>
                                <td>{{ $position->entry_price }}</td>
                                <td>{{ $position->current_price }}</td>
                                <td>{{ $position->lot_size }}</td>
                                <td>{{ $position->closed_at->diffForHumans($position->created_at, true) }}</td>
                                <td class="{{ $position->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($position->pnl, 2) }}</strong>
                                </td>
                                <td>
                                    @if($position->close_reason === 'TP_HIT')
                                    <span class="badge badge-success">Take Profit</span>
                                    @elseif($position->close_reason === 'SL_HIT')
                                    <span class="badge badge-danger">Stop Loss</span>
                                    @elseif($position->close_reason === 'MANUAL')
                                    <span class="badge badge-info">Manual</span>
                                    @else
                                    <span class="badge badge-secondary">{{ $position->close_reason }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $positions->links() }}
                @else
                <div class="alert alert-info">No closed positions found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

