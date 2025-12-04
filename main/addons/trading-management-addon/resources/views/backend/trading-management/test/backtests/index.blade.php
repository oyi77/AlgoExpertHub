@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>📊 Backtest Results</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.test.backtests.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Backtest
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-chart-bar"></i> <strong>Historical Performance</strong><br>
            View all backtest results with detailed metrics and performance reports.
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Symbol</th>
                        <th>Period</th>
                        <th>Total Trades</th>
                        <th>Win Rate</th>
                        <th>Profit Factor</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No backtest results yet. Create your first backtest!</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

