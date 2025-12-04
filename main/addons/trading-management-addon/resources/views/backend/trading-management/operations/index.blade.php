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
                        <h3>{{ \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('is_active', 1)->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Open Positions</h6>
                        <h3>{{ \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('status', 'open')->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Executions</h6>
                        <h3>{{ \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::whereDate('created_at', today())->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Today's P&L</h6>
                        <h3>${{ number_format(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('status', 'closed')->whereDate('closed_at', today())->sum('pnl'), 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-plug"></i>
                            <div class="mt-2">Execution Connections</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="{{ route('admin.trading-management.operations.executions') }}" class="btn btn-info btn-block btn-lg">
                            <i class="fas fa-list"></i>
                            <div class="mt-2">Execution Log</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="{{ route('admin.trading-management.operations.positions.open') }}" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-chart-area"></i>
                            <div class="mt-2">Open Positions</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="{{ route('admin.trading-management.operations.analytics') }}" class="btn btn-warning btn-block btn-lg">
                            <i class="fas fa-chart-pie"></i>
                            <div class="mt-2">Analytics</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
