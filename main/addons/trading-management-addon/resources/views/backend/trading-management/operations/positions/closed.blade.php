@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>✅ Closed Positions</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary">
            <i class="fas fa-history"></i> <strong>Trade History</strong><br>
            View all closed positions with profit/loss, exit reason, and performance metrics.
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Closed At</th>
                        <th>Connection</th>
                        <th>Symbol</th>
                        <th>Side</th>
                        <th>Size</th>
                        <th>Entry</th>
                        <th>Exit</th>
                        <th>P&L</th>
                        <th>Exit Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No closed positions yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

