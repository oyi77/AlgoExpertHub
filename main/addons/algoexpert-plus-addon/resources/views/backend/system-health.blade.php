@extends('backend.layout.master')

@section('title', $title ?? 'System Health')

@section('element')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">System Health Dashboard</h4>
                    <p class="mb-0">Comprehensive system monitoring and health checks</p>
                </div>
            </div>
        </div>
    </div>

    {{-- System Information --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="info"></i> System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">PHP Version</th>
                                    <td>{{ $system_info['php_version'] }}</td>
                                </tr>
                                <tr>
                                    <th>Laravel Version</th>
                                    <td>{{ $system_info['laravel_version'] }}</td>
                                </tr>
                                <tr>
                                    <th>Environment</th>
                                    <td>
                                        <span class="badge badge-{{ $system_info['environment'] === 'production' ? 'success' : 'warning' }}">
                                            {{ ucfirst($system_info['environment']) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Debug Mode</th>
                                    <td>
                                        <span class="badge badge-{{ $system_info['debug_mode'] ? 'danger' : 'success' }}">
                                            {{ $system_info['debug_mode'] ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Timezone</th>
                                    <td>{{ $system_info['timezone'] }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Memory Limit</th>
                                    <td>{{ $system_info['memory_limit'] }}</td>
                                </tr>
                                <tr>
                                    <th>Memory Usage</th>
                                    <td>{{ $system_info['memory_usage'] }}</td>
                                </tr>
                                <tr>
                                    <th>Peak Memory</th>
                                    <td>{{ $system_info['memory_peak'] }}</td>
                                </tr>
                                <tr>
                                    <th>Max Execution Time</th>
                                    <td>{{ $system_info['max_execution_time'] }}s</td>
                                </tr>
                                <tr>
                                    <th>Queue Connection</th>
                                    <td>{{ $system_info['queue_connection'] }}</td>
                                </tr>
                                <tr>
                                    <th>Cache Driver</th>
                                    <td>{{ $system_info['cache_driver'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Horizon Statistics --}}
        @if($horizon_stats && ($horizon_stats['available'] ?? false))
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i data-feather="monitor"></i> Horizon Queue Monitor</h5>
                    @if(\Illuminate\Support\Facades\Route::has('admin.algoexpert-plus.horizon'))
                    <a href="{{ route('admin.algoexpert-plus.horizon') }}" class="btn btn-sm btn-primary">
                        Open Horizon <i data-feather="external-link" style="width: 14px; height: 14px;"></i>
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge badge-{{ ($horizon_stats['active'] ?? false) ? 'success' : 'danger' }}">
                            Status: {{ strtoupper($horizon_stats['status'] ?? 'unknown') }}
                        </span>
                        @if(isset($horizon_stats['processes']))
                        <span class="badge badge-info ml-2">
                            Processes: {{ $horizon_stats['processes'] }}
                        </span>
                        @endif
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="mb-0">{{ number_format($horizon_stats['throughput'] ?? 0) }}</h3>
                            <small class="text-muted">Jobs/Hour</small>
                        </div>
                        <div class="col-6">
                            <h3 class="mb-0">{{ number_format($horizon_stats['wait_time'] ?? 0, 2) }}s</h3>
                            <small class="text-muted">Avg Wait Time</small>
                        </div>
                    </div>
                    @if(!($horizon_stats['active'] ?? false))
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i data-feather="alert-circle" style="width: 14px; height: 14px;"></i>
                            Horizon workers are not running. Start with: <code>php artisan horizon</code> or configure supervisor.
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Queue Statistics (Database) --}}
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="list"></i> Queue Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h3 class="mb-0 text-primary">{{ number_format($queue_stats['pending'] ?? 0) }}</h3>
                            <small class="text-muted">Pending Jobs</small>
                        </div>
                        <div class="col-6">
                            <h3 class="mb-0 text-danger">{{ number_format($queue_stats['failed'] ?? 0) }}</h3>
                            <small class="text-muted">Failed Jobs</small>
                        </div>
                    </div>
                    @if(!empty($queue_stats['queues']))
                    <div class="mt-3">
                        <h6>Queue Breakdown:</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($queue_stats['queues'] as $queue => $count)
                            <li>
                                <strong>{{ $queue }}:</strong> {{ number_format($count) }} jobs
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Database Statistics --}}
    @if($database_stats['available'] ?? false)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="database"></i> Database Statistics</h5>
                </div>
                <div class="card-body">
                    <p><strong>Connection:</strong> {{ $database_stats['connection'] }} | 
                       <strong>Database:</strong> {{ $database_stats['database'] }}</p>
                    @if(!empty($database_stats['largest_tables']))
                    <h6>Largest Tables:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Size (MB)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($database_stats['largest_tables'] as $table)
                                <tr>
                                    <td>{{ $table->table_name }}</td>
                                    <td>{{ number_format($table->table_rows) }}</td>
                                    <td>{{ number_format($table->size_mb, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Health Checks (Spatie) --}}
    @if($health_checks && ($health_checks['available'] ?? false))
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i data-feather="heart"></i> Health Checks</h5>
                    @if($spatie_health_url)
                    <a href="{{ $spatie_health_url }}" class="btn btn-sm btn-primary" target="_blank">
                        View Details <i data-feather="external-link" style="width: 14px; height: 14px;"></i>
                    </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="alert alert-{{ $health_checks['status'] === 'ok' ? 'success' : 'warning' }}">
                        <strong>Overall Status:</strong> 
                        <span class="badge badge-{{ $health_checks['status'] === 'ok' ? 'success' : 'warning' }}">
                            {{ strtoupper($health_checks['status']) }}
                        </span>
                    </div>
                    @if(!empty($health_checks['checks']))
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Check Name</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($health_checks['checks'] as $check)
                                <tr>
                                    <td>{{ $check->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ ($check->status ?? '') === 'ok' ? 'success' : 'danger' }}">
                                            {{ strtoupper($check->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>{{ $check->message ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="zap"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        @if(\Illuminate\Support\Facades\Route::has('admin.algoexpert-plus.horizon'))
                        <a href="{{ route('admin.algoexpert-plus.horizon') }}" class="btn btn-primary">
                            <i data-feather="monitor"></i> Open Horizon Dashboard
                        </a>
                        @endif
                        @if($spatie_health_url)
                        <a href="{{ $spatie_health_url }}" class="btn btn-info" target="_blank">
                            <i data-feather="heart"></i> View Health Details
                        </a>
                        @endif
                        <a href="{{ route('admin.algoexpert-plus.index') }}" class="btn btn-secondary">
                            <i data-feather="arrow-left"></i> Back to AlgoExpert++
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
