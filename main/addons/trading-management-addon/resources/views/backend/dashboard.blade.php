@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>📊 Trading Management Dashboard</h4>
                <span class="badge badge-success">Phase 1 Complete</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Trading Management Addon</h5>
                    <p>Unified trading management system consolidating 7 addons into one modular addon.</p>
                    <p><strong>Version:</strong> 2.0.0 | <strong>Status:</strong> In Development</p>
                </div>

                <div class="row mt-4">
                    <!-- Trading Configuration -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h5><i class="fas fa-cog text-primary"></i> Trading Configuration</h5>
                                <p class="text-muted">Setup infrastructure and risk settings</p>
                                <a href="{{ route('admin.trading-management.config.index') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-right"></i> Configure
                                </a>
                                <span class="badge badge-warning">Phase 2</span>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Operations -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <h5><i class="fas fa-bolt text-success"></i> Trading Operations</h5>
                                <p class="text-muted">Monitor executions and positions</p>
                                <a href="{{ route('admin.trading-management.operations.index') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-arrow-right"></i> View Operations
                                </a>
                                <span class="badge badge-warning">Phase 5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Strategy -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-info">
                            <div class="card-body">
                                <h5><i class="fas fa-bullseye text-info"></i> Trading Strategy</h5>
                                <p class="text-muted">Manage filters and AI models</p>
                                <a href="{{ route('admin.trading-management.strategy.index') }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-arrow-right"></i> Manage Strategies
                                </a>
                                <span class="badge badge-warning">Phase 3</span>
                            </div>
                        </div>
                    </div>

                    <!-- Copy Trading -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h5><i class="fas fa-users text-warning"></i> Copy Trading</h5>
                                <p class="text-muted">Social trading features</p>
                                <a href="{{ route('admin.trading-management.copy-trading.index') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-arrow-right"></i> View Traders
                                </a>
                                <span class="badge badge-warning">Phase 6</span>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Test -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h5><i class="fas fa-flask text-secondary"></i> Trading Test</h5>
                                <p class="text-muted">Backtest strategies</p>
                                <a href="{{ route('admin.trading-management.test.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-right"></i> Run Tests
                                </a>
                                <span class="badge badge-warning">Phase 8</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Development Progress</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: 10%">
                                Phase 1: Foundation ✅
                            </div>
                            <div class="progress-bar bg-secondary" style="width: 90%">
                                Phases 2-10
                            </div>
                        </div>
                        <p class="mt-2 text-muted">
                            <strong>Next:</strong> Phase 2 - Data Layer (Data Provider + Market Data modules)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

