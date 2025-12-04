@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>📋 My Subscriptions</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary">
            <i class="fas fa-list"></i> <strong>Active Copy Trading</strong><br>
            Manage your active copy trading subscriptions here.
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Trader</th>
                        <th>Since</th>
                        <th>Total Copied</th>
                        <th>Performance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No active subscriptions. Browse traders to start copying!</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

