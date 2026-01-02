@extends('backend.layout.master')

@section('element')
<div class="row">
    <!-- Statistics Overview -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Phase 1</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">Foundation</div>
                                <small class="text-success"><i class="fas fa-check-circle"></i> Complete</small>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Version</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">2.0.0</div>
                                <small class="text-muted">In Development</small>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-code-branch fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Progress</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">10%</div>
                                <small class="text-muted">Phases 2-10 Pending</small>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-tasks fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Status</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">Active</div>
                                <small class="text-success">System Running</small>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-heartbeat fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-chart-line text-primary"></i> Trading Management Dashboard
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-light border mb-4">
                    <h6 class="font-weight-bold mb-2"><i class="fas fa-info-circle text-primary"></i> Trading Management Addon</h6>
                    <p class="mb-2">Unified trading management system consolidating 7 addons into one modular addon.</p>
                    <p class="mb-0">
                        <strong>Version:</strong> 2.0.0 | 
                        <strong>Status:</strong> <span class="badge badge-success">In Development</span>
                    </p>
                </div>

                <div class="row">
                    <!-- Trading Configuration -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-left-primary shadow-sm h-100 connection-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-icon bg-primary mr-3">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="font-weight-bold mb-1">Trading Configuration</h6>
                                        <small class="text-muted">Setup infrastructure and risk settings</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.trading-management.config.index') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-arrow-right"></i> Configure
                                    </a>
                                    <span class="badge badge-warning">Phase 2</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Operations -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-left-success shadow-sm h-100 connection-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-icon bg-success mr-3">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="font-weight-bold mb-1">Trading Operations</h6>
                                        <small class="text-muted">Monitor executions and positions</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.trading-management.operations.index') }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-arrow-right"></i> View Operations
                                    </a>
                                    <span class="badge badge-warning">Phase 5</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Strategy -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-left-info shadow-sm h-100 connection-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-icon bg-info mr-3">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="font-weight-bold mb-1">Trading Strategy</h6>
                                        <small class="text-muted">Manage filters and AI models</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.trading-management.strategy.index') }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-arrow-right"></i> Manage Strategies
                                    </a>
                                    <span class="badge badge-warning">Phase 3</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Copy Trading -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-left-warning shadow-sm h-100 connection-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-icon bg-warning mr-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="font-weight-bold mb-1">Copy Trading</h6>
                                        <small class="text-muted">Social trading features</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.trading-management.copy-trading.index') }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-arrow-right"></i> View Traders
                                    </a>
                                    <span class="badge badge-warning">Phase 6</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Test -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-left-secondary shadow-sm h-100 connection-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="step-icon bg-secondary mr-3">
                                        <i class="fas fa-flask"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="font-weight-bold mb-1">Trading Test</h6>
                                        <small class="text-muted">Backtest strategies</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.trading-management.test.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-right"></i> Run Tests
                                    </a>
                                    <span class="badge badge-warning">Phase 8</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Development Progress -->
                <div class="mt-4">
                    <h6 class="font-weight-bold mb-3"><i class="fas fa-tasks text-primary"></i> Development Progress</h6>
                    <div class="progress mb-2" style="height: 30px;">
                        <div class="progress-bar bg-success d-flex align-items-center justify-content-center" style="width: 10%">
                            <span class="font-weight-bold">Phase 1: Foundation ✅</span>
                        </div>
                        <div class="progress-bar bg-secondary d-flex align-items-center justify-content-center" style="width: 90%">
                            <span class="font-weight-bold text-white">Phases 2-10</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        <strong>Next:</strong> Phase 2 - Data Layer (Data Provider + Market Data modules)
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-secondary { border-left: 4px solid #858796 !important; }

    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
    }

    .connection-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .connection-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }
</style>
@endpush
@endsection
