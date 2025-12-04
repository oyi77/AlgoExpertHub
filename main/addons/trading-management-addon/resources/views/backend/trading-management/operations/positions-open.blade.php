@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Stats Row -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Open Positions</h6>
                        <h3>{{ $stats['total_open'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total P&L</h6>
                        <h3 class="{{ $stats['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($stats['total_pnl'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Avg P&L</h6>
                        <h3 class="{{ $stats['avg_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($stats['avg_pnl'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-chart-area"></i> Open Positions</h4>
            </div>
            <div class="card-body">
                @if($positions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Opened</th>
                                <th>Connection</th>
                                <th>Symbol</th>
                                <th>Direction</th>
                                <th>Lot Size</th>
                                <th>Entry</th>
                                <th>Current</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>P&L</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($positions as $position)
                            <tr>
                                <td>{{ $position->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $position->connection->name ?? 'N/A' }}</td>
                                <td>{{ $position->symbol }}</td>
                                <td>
                                    @if(in_array($position->direction, ['BUY', 'LONG']))
                                    <span class="badge badge-success">{{ $position->direction }}</span>
                                    @else
                                    <span class="badge badge-danger">{{ $position->direction }}</span>
                                    @endif
                                </td>
                                <td>{{ $position->lot_size }}</td>
                                <td>{{ $position->entry_price }}</td>
                                <td>{{ $position->current_price }}</td>
                                <td>{{ $position->sl_price }}</td>
                                <td>{{ $position->tp_price }}</td>
                                <td class="{{ $position->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($position->pnl, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $positions->links() }}
                @else
                <div class="alert alert-info">No open positions.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

