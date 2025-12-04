@extends('frontend.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>📊 Trading Management</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Manage all your trading activities from one place.</p>
                </div>

                <div class="row">
                    <!-- My Configuration -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-cog fa-3x text-primary mb-3"></i>
                                <h5>My Configuration</h5>
                                <p class="text-muted">Data connections and risk settings</p>
                                <a href="{{ route('user.trading-management.config.index') }}" class="btn btn-primary btn-sm">
                                    Configure
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Auto Trading -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bolt fa-3x text-success mb-3"></i>
                                <h5>Auto Trading</h5>
                                <p class="text-muted">Monitor your positions</p>
                                <a href="{{ route('user.trading-management.operations.index') }}" class="btn btn-success btn-sm">
                                    View Operations
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- My Strategies -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bullseye fa-3x text-info mb-3"></i>
                                <h5>My Strategies</h5>
                                <p class="text-muted">Filters and AI models</p>
                                <a href="{{ route('user.trading-management.strategy.index') }}" class="btn btn-info btn-sm">
                                    Manage
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Copy Trading -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-warning mb-3"></i>
                                <h5>Copy Trading</h5>
                                <p class="text-muted">Follow other traders</p>
                                <a href="{{ route('user.trading-management.copy-trading.index') }}" class="btn btn-warning btn-sm">
                                    Browse Traders
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Backtesting -->
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-flask fa-3x text-secondary mb-3"></i>
                                <h5>Backtesting</h5>
                                <p class="text-muted">Test your strategies</p>
                                <a href="{{ route('user.trading-management.test.index') }}" class="btn btn-secondary btn-sm">
                                    Run Tests
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

