@extends('backend.layout.master')

@section('title', $title ?? 'System Tools Dashboard')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">System Tools Dashboard</h4>
                    <p class="mb-3">Centralized system management and optimization tools.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body text-center">
                    <i data-feather="zap" style="width: 48px; height: 48px; color: #007bff;" class="mb-3"></i>
                    <h5 class="card-title">Performance</h5>
                    <p class="card-text text-muted small">Optimize cache, autoloader, and system performance</p>
                    <a href="{{ route('admin.algoexpert-plus.system-tools.performance') }}" class="btn btn-primary btn-sm">
                        Open Performance Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-info">
                <div class="card-body text-center">
                    <i data-feather="clock" style="width: 48px; height: 48px; color: #17a2b8;" class="mb-3"></i>
                    <h5 class="card-title">Cron Jobs</h5>
                    <p class="card-text text-muted small">Manage scheduled tasks and cron job setup</p>
                    <a href="{{ route('admin.algoexpert-plus.system-tools.cron-jobs') }}" class="btn btn-info btn-sm">
                        Manage Cron Jobs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body text-center">
                    <i data-feather="heart" style="width: 48px; height: 48px; color: #28a745;" class="mb-3"></i>
                    <h5 class="card-title">System Health</h5>
                    <p class="card-text text-muted small">Monitor system health and diagnostics</p>
                    <a href="{{ route('admin.algoexpert-plus.system-health') }}" class="btn btn-success btn-sm">
                        View Health Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-warning">
                <div class="card-body text-center">
                    <i data-feather="package" style="width: 48px; height: 48px; color: #ffc107;" class="mb-3"></i>
                    <h5 class="card-title">Dependencies</h5>
                    <p class="card-text text-muted small">Manage package dependencies and installation</p>
                    <a href="{{ route('admin.algoexpert-plus.index') }}" class="btn btn-warning btn-sm">
                        Manage Dependencies
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-danger">
                <div class="card-body text-center">
                    <i data-feather="archive" style="width: 48px; height: 48px; color: #dc3545;" class="mb-3"></i>
                    <h5 class="card-title">Backup</h5>
                    <p class="card-text text-muted small">Create and manage system backups</p>
                    <a href="{{ route('admin.algoexpert-plus.backup.run') }}" class="btn btn-danger btn-sm" onclick="return confirm('Run backup now?');">
                        Run Backup
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-secondary">
                <div class="card-body text-center">
                    <i data-feather="monitor" style="width: 48px; height: 48px; color: #6c757d;" class="mb-3"></i>
                    <h5 class="card-title">Queues</h5>
                    <p class="card-text text-muted small">Monitor queue jobs and workers</p>
                    @if(\Illuminate\Support\Facades\Route::has('admin.algoexpert-plus.horizon'))
                    <a href="{{ route('admin.algoexpert-plus.horizon') }}" class="btn btn-secondary btn-sm">
                        Open Horizon Dashboard
                    </a>
                    @else
                    <button class="btn btn-secondary btn-sm" disabled>
                        Horizon Not Available
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
