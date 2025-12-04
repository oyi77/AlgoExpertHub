@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-bolt"></i> Trading Operations</h3>
                <p class="text-muted mb-0">Manage execution connections, monitor positions, and view analytics</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Active Connections</h6>
                        <h3>{{ $stats['active_connections'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Open Positions</h6>
                        <h3>{{ $stats['open_positions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Executions</h6>
                        <h3>{{ $stats['today_executions'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's P&L</h6>
                        <h3>${{ number_format($stats['today_pnl'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-connections" data-toggle="tab">
                            <i class="fas fa-plug"></i> Execution Connections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-executions" data-toggle="tab">
                            <i class="fas fa-list"></i> Execution Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-positions-open" data-toggle="tab">
                            <i class="fas fa-chart-area"></i> Open Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-positions-closed" data-toggle="tab">
                            <i class="fas fa-history"></i> Closed Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-analytics" data-toggle="tab">
                            <i class="fas fa-chart-pie"></i> Analytics
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Execution Connections Tab -->
                    <div class="tab-pane fade show active" id="tab-connections">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Execution Connections</h5>
                            <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage Connections
                            </a>
                        </div>
                        <p class="text-muted">Configure and manage execution connections to crypto exchanges and FX brokers for automated trading.</p>
                    </div>

                    <!-- Execution Log Tab -->
                    <div class="tab-pane fade" id="tab-executions">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Execution Log</h5>
                            <a href="{{ route('admin.trading-management.operations.executions') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Executions
                            </a>
                        </div>
                        <p class="text-muted">Complete history of all trade executions with success/failure status and error details.</p>
                    </div>

                    <!-- Open Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-open">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Open Positions</h5>
                            <a href="{{ route('admin.trading-management.operations.positions.open') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Monitor Positions
                            </a>
                        </div>
                        <p class="text-muted">Real-time monitoring of all open trading positions with current P&L and status.</p>
                    </div>

                    <!-- Closed Positions Tab -->
                    <div class="tab-pane fade" id="tab-positions-closed">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Closed Positions</h5>
                            <a href="{{ route('admin.trading-management.operations.positions.closed') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View History
                            </a>
                        </div>
                        <p class="text-muted">Historical record of all closed positions with final P&L and close reasons (TP/SL/Manual).</p>
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="tab-analytics">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Analytics</h5>
                            <a href="{{ route('admin.trading-management.operations.analytics') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Analytics
                            </a>
                        </div>
                        <p class="text-muted">Performance metrics including win rate, profit factor, drawdown, and daily P&L charts.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
