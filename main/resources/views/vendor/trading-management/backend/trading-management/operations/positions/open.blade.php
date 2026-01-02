@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>📈 Open Positions</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <strong>Real-Time Position Monitoring</strong><br>
            Track your open trades with live P&L, SL/TP monitoring, and auto-close capabilities.
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Connection</th>
                        <th>Symbol</th>
                        <th>Side</th>
                        <th>Size</th>
                        <th>Entry</th>
                        <th>Current</th>
                        <th>P&L</th>
                        <th>SL</th>
                        <th>TP</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No open positions. Execute a trade to see positions here!</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

