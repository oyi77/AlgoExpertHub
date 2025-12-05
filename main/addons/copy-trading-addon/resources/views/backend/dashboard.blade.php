@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        <div class="row">
            <!-- Total Traders Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Traders
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_traders'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Active Subscriptions
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_subscriptions'] ?? 0 }}</div>
                                <small class="text-muted">of {{ $stats['total_subscriptions'] ?? 0 }} total</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-link fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Executions Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Executions
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_executions'] ?? 0 }}</div>
                                <small class="text-success">{{ $stats['successful_executions'] ?? 0 }} success</small> | 
                                <small class="text-danger">{{ $stats['failed_executions'] ?? 0 }} failed</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Followers Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Active Followers
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_followers'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{ route('admin.copy-trading.traders.index') }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-users"></i> Manage Traders
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.copy-trading.subscriptions.index') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-link"></i> View Subscriptions
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.copy-trading.analytics') }}" class="btn btn-info btn-block">
                                    <i class="fas fa-chart-bar"></i> View Analytics
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('admin.copy-trading.settings') }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

