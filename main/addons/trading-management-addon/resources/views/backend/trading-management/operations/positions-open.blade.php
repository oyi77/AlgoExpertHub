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
                                <th>TP Progress</th>
                                <th>P&L</th>
                            </tr>
                        </thead>
                        <tbody id="positions-tbody">
                            @foreach($positions as $position)
                            <tr data-position-id="{{ $position->id }}">
                                <td>{{ $position->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $position->connection->name ?? 'N/A' }}</td>
                                <td>{{ $position->symbol }}</td>
                                <td>
                                    @if(in_array($position->direction, ['BUY', 'LONG', 'buy', 'long']))
                                    <span class="badge badge-success">{{ strtoupper($position->direction) }}</span>
                                    @else
                                    <span class="badge badge-danger">{{ strtoupper($position->direction) }}</span>
                                    @endif
                                </td>
                                <td>{{ $position->quantity ?? $position->lot_size }}</td>
                                <td>{{ $position->entry_price }}</td>
                                <td class="position-current-price" data-position-id="{{ $position->id }}">{{ $position->current_price ?? $position->entry_price }}</td>
                                <td>{{ $position->sl_price }}</td>
                                <td>{{ $position->tp_price }}</td>
                                <td>
                                    @php
                                        $signal = $position->signal;
                                        $openTps = $signal ? $signal->openTakeProfits()->orderBy('tp_level')->get() : collect();
                                        $closedTps = $signal ? $signal->closedTakeProfits()->orderBy('tp_level')->get() : collect();
                                    @endphp
                                    @if($openTps->count() > 0)
                                        <div class="progress" style="height: 20px;">
                                            @foreach($openTps as $tp)
                                                @php
                                                    $currentPrice = $position->current_price ?? $position->entry_price;
                                                    $distance = abs($currentPrice - $tp->tp_price);
                                                    $totalDistance = abs($position->entry_price - $tp->tp_price);
                                                    $progress = $totalDistance > 0 ? (1 - ($distance / $totalDistance)) * 100 : 0;
                                                    $progress = max(0, min(100, $progress));
                                                @endphp
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $progress }}%" title="TP{{ $tp->tp_level }}: {{ $tp->tp_price }}">
                                                    TP{{ $tp->tp_level }}
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">
                                            Closed: {{ $closedTps->count() }}/{{ $openTps->count() + $closedTps->count() }}
                                        </small>
                                    @else
                                        <span class="text-muted">Single TP</span>
                                    @endif
                                </td>
                                <td class="position-pnl {{ $position->pnl >= 0 ? 'text-success' : 'text-danger' }}" data-position-id="{{ $position->id }}">
                                    $<span class="pnl-amount">{{ number_format($position->pnl, 2) }}</span>
                                    <small class="d-block text-muted position-pnl-percentage" data-position-id="{{ $position->id }}">
                                        ({{ number_format($position->pnl_percentage ?? 0, 2) }}%)
                                    </small>
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

@push('script')
<script>
(function() {
    'use strict';
    
    // Get all position IDs from the table
    function getPositionIds() {
        const rows = document.querySelectorAll('#positions-tbody tr[data-position-id]');
        return Array.from(rows).map(row => parseInt(row.getAttribute('data-position-id')));
    }
    
    // Update position data in the table
    function updatePositions(updates) {
        updates.forEach(function(update) {
            // Update current price
            const currentPriceCell = document.querySelector(`.position-current-price[data-position-id="${update.id}"]`);
            if (currentPriceCell) {
                const oldPrice = parseFloat(currentPriceCell.textContent.trim());
                const newPrice = parseFloat(update.current_price);
                currentPriceCell.textContent = newPrice.toFixed(8);
                
                // Add visual indicator if price changed
                if (oldPrice !== newPrice) {
                    currentPriceCell.classList.add('price-updated');
                    setTimeout(() => {
                        currentPriceCell.classList.remove('price-updated');
                    }, 1000);
                }
            }
            
            // Update P/L
            const pnlCell = document.querySelector(`.position-pnl[data-position-id="${update.id}"]`);
            if (pnlCell) {
                const pnlAmount = pnlCell.querySelector('.pnl-amount');
                const pnlPercentage = pnlCell.querySelector('.position-pnl-percentage');
                
                if (pnlAmount) {
                    pnlAmount.textContent = parseFloat(update.pnl).toFixed(2);
                }
                
                if (pnlPercentage) {
                    pnlPercentage.textContent = '(' + parseFloat(update.pnl_percentage).toFixed(2) + '%)';
                }
                
                // Update color based on P/L
                pnlCell.classList.remove('text-success', 'text-danger');
                pnlCell.classList.add(parseFloat(update.pnl) >= 0 ? 'text-success' : 'text-danger');
            }
        });
    }
    
    // Fetch position updates
    function fetchPositionUpdates() {
        const positionIds = getPositionIds();
        
        if (positionIds.length === 0) {
            return;
        }
        
        fetch('{{ route("admin.trading-management.operations.positions.updates") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                position_ids: positionIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updatePositions(data.data);
            }
        })
        .catch(error => {
            console.error('Failed to fetch position updates:', error);
        });
    }
    
    // Start polling every 5 seconds
    if (getPositionIds().length > 0) {
        // Initial fetch
        fetchPositionUpdates();
        
        // Poll every 5 seconds
        setInterval(fetchPositionUpdates, 5000);
    }
})();
</script>
<style>
.price-updated {
    background-color: #fff3cd !important;
    transition: background-color 0.3s ease;
}
</style>
@endpush
@endsection

