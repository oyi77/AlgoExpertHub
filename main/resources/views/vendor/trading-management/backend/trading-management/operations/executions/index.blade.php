@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>📋 Execution Logs</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Execution Logs View</strong><br>
            This view will display all trade execution history from your connections. Coming soon!
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Connection</th>
                        <th>Signal</th>
                        <th>Symbol</th>
                        <th>Side</th>
                        <th>Size</th>
                        <th>Entry</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No executions yet. Execute your first trade!</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

