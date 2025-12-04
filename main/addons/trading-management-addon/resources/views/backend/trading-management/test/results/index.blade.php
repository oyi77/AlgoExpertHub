@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Backtest Results</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <select name="backtest_id" class="form-control">
                                <option value="">All Backtests</option>
                                @foreach($backtests as $bt)
                                <option value="{{ $bt->id }}" {{ request('backtest_id') == $bt->id ? 'selected' : '' }}>
                                    {{ $bt->name }} ({{ $bt->symbol }} - {{ $bt->timeframe }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.trading-management.test.results.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                @if($results->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Backtest</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Direction</th>
                                <th>Entry Price</th>
                                <th>Exit Price</th>
                                <th>Lot Size</th>
                                <th>P&L</th>
                                <th>P&L %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.trading-management.test.backtests.show', $result->backtest) }}">
                                        {{ $result->backtest->name }}
                                    </a>
                                </td>
                                <td>{{ $result->entry_time->format('Y-m-d H:i') }}</td>
                                <td>{{ $result->exit_time ? $result->exit_time->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $result->direction === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                        {{ strtoupper($result->direction) }}
                                    </span>
                                </td>
                                <td>{{ $result->entry_price }}</td>
                                <td>{{ $result->exit_price }}</td>
                                <td>{{ $result->lot_size }}</td>
                                <td class="{{ $result->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>${{ number_format($result->pnl, 2) }}</strong>
                                </td>
                                <td class="{{ $result->pnl_percentage >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($result->pnl_percentage, 2) }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $results->links() }}
                @else
                <div class="alert alert-info">No backtest results found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

