@extends('backend.layout.master')

@section('title', $title ?? 'Performance Settings')

@section('element')
<div class="container-fluid performance-page">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="page-title mb-1">
                            <i class="las la-tachometer-alt text-white"></i>
                            {{ __('Performance Settings') }}
                        </h3>
                        <p class="text-muted mb-0 text-white-50">{{ __('Monitor and optimize your system performance') }}</p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('admin.algoexpert-plus.system-tools.dashboard') }}" class="btn btn-light btn-sm">
                            <i class="las la-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-primary">
                <div class="stats-icon"><i class="las la-server"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('PHP Version') }}</p>
                    <h4 class="stats-value" id="quick-php-version">{{ PHP_VERSION }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-success">
                <div class="stats-icon"><i class="las la-code"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Laravel Version') }}</p>
                    <h4 class="stats-value" id="quick-laravel-version">{{ app()->version() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-info">
                <div class="stats-icon"><i class="las la-database"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Database Backups') }}</p>
                    <h4 class="stats-value">{{ count($backups ?? []) }} {{ __('files') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-warning">
                <div class="stats-icon"><i class="las la-memory"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('OPcache Status') }}</p>
                    <h4 class="stats-value" id="quick-opcache-status">
                        <span class="badge badge-info">{{ __('Loading...') }}</span>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Octane Quick Status Card -->
    @if(isset($octaneStatus))
    <div class="row mb-4">
        <div class="col-12">
            <div class="stats-card stats-card-{{ $octaneStatus['available'] && $octaneStatus['running'] ? 'success' : ($octaneStatus['available'] ? 'warning' : 'secondary') }}" style="margin-bottom: 0;">
                <div class="stats-icon"><i class="las la-rocket"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Octane Status') }}</p>
                    <h4 class="stats-value">
                        @if(!$octaneStatus['available'])
                            <span class="badge badge-secondary">{{ __('Not Installed') }}</span>
                        @elseif($octaneStatus['running'])
                            <span class="badge badge-success">{{ __('Running') }}</span>
                            @if(isset($octaneStatus['server']))
                                <small class="text-muted">({{ ucfirst($octaneStatus['server']) }})</small>
                            @endif
                        @else
                            <span class="badge badge-warning">{{ __('Stopped') }}</span>
                        @endif
                    </h4>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Octane Status -->
    @if(isset($octaneStatus))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-{{ $octaneStatus['available'] && $octaneStatus['running'] ? 'success' : ($octaneStatus['available'] ? 'warning' : 'secondary') }}">
                <div class="card-header bg-{{ $octaneStatus['available'] && $octaneStatus['running'] ? 'success' : ($octaneStatus['available'] ? 'warning' : 'secondary') }} text-white">
                    <h5 class="mb-0">
                        <i class="las la-rocket"></i> {{ __('Laravel Octane') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td><strong>{{ __('Status') }}:</strong></td>
                                    <td>
                                        @if(!$octaneStatus['available'])
                                            <span class="badge badge-secondary">{{ __('Not Installed') }}</span>
                                        @elseif($octaneStatus['running'])
                                            <span class="badge badge-success">{{ __('Running') }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ __('Stopped') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($octaneStatus['available'])
                                <tr>
                                    <td><strong>{{ __('Server') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($octaneStatus['server'] ?? 'Unknown') }}</span>
                                    </td>
                                </tr>
                                @if(isset($octaneStatus['workers']))
                                <tr>
                                    <td><strong>{{ __('Workers') }}:</strong></td>
                                    <td>{{ $octaneStatus['workers'] ?? 'N/A' }}</td>
                                </tr>
                                @endif
                                @if(isset($octaneStatus['port']))
                                <tr>
                                    <td><strong>{{ __('Port') }}:</strong></td>
                                    <td>{{ $octaneStatus['port'] ?? 'N/A' }}</td>
                                </tr>
                                @endif
                                @if(isset($octaneStatus['config']['max_requests']))
                                <tr>
                                    <td><strong>{{ __('Max Requests') }}:</strong></td>
                                    <td>{{ $octaneStatus['config']['max_requests'] ?? 'N/A' }}</td>
                                </tr>
                                @endif
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-{{ $octaneStatus['available'] && $octaneStatus['running'] ? 'success' : ($octaneStatus['available'] ? 'warning' : 'info') }} mb-0">
                                @if(!$octaneStatus['available'])
                                    <i class="las la-info-circle"></i> 
                                    {{ __('Laravel Octane is not installed. Install it with: composer require laravel/octane') }}
                                @elseif($octaneStatus['running'])
                                    <i class="las la-check-circle"></i> 
                                    {{ __('Octane server is running and serving requests.') }}
                                @elseif(isset($octaneStatus['error']))
                                    <i class="las la-exclamation-triangle"></i> 
                                    {{ __('Error: ') }}{{ $octaneStatus['error'] }}
                                @else
                                    <i class="las la-exclamation-circle"></i> 
                                    {{ __('Octane server is stopped. Start it with: php artisan octane:start') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Horizon Status (Unified) -->
    @if(isset($horizonStats) || (isset($horizonSupervisorStatus) && $horizonSupervisorStatus))
    <div class="row mb-4">
        <div class="col-12">
            @php
                $isHorizonActive = isset($horizonStats) && $horizonStats['available'] && ($horizonStats['active'] ?? false);
                $isCronActive = isset($horizonSupervisorStatus) && ($horizonSupervisorStatus['enabled'] ?? false);
                $isSystemSupervisor = isset($horizonSupervisorStatus) && ($horizonSupervisorStatus['use_system_supervisor'] ?? false);
                $overallStatus = $isHorizonActive ? 'success' : ($isHorizonActive === false && isset($horizonStats) ? 'warning' : 'secondary');
            @endphp
            <div class="card border-{{ $overallStatus }}">
                <div class="card-header bg-{{ $overallStatus }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="las la-tachometer-alt"></i> {{ __('Laravel Horizon') }}
                        </h5>
                        @if($isHorizonActive)
                            <a href="{{ route('admin.algoexpert-plus.horizon') }}" class="btn btn-light btn-sm">
                                <i class="las la-external-link-alt"></i> {{ __('Open Dashboard') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Main Status Column -->
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="las la-server"></i> {{ __('Worker Status') }}
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td width="40%"><strong>{{ __('Status') }}:</strong></td>
                                    <td>
                                        @if(!isset($horizonStats) || !$horizonStats['available'] || (isset($horizonStats['installed']) && !$horizonStats['installed']))
                                            <span class="badge badge-secondary">{{ __('Not Installed') }}</span>
                                        @elseif($isHorizonActive)
                                            <span class="badge badge-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if(isset($horizonStats) && $horizonStats['available'])
                                <tr>
                                    <td><strong>{{ __('Processes') }}:</strong></td>
                                    <td>{{ $horizonStats['processes'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Throughput') }}:</strong></td>
                                    <td>{{ number_format($horizonStats['throughput'] ?? 0) }} {{ __('jobs/min') }}</td>
                                </tr>
                                @if(isset($horizonStats['diagnostics']['queue_connection']))
                                <tr>
                                    <td><strong>{{ __('Queue Connection') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $horizonStats['diagnostics']['queue_connection'] === 'redis' ? 'success' : 'warning' }}">
                                            {{ $horizonStats['diagnostics']['queue_connection'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                @if(isset($horizonStats['diagnostics']['redis_connected']))
                                <tr>
                                    <td><strong>{{ __('Redis Connection') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $horizonStats['diagnostics']['redis_connected'] ? 'success' : 'danger' }}">
                                            {{ $horizonStats['diagnostics']['redis_connected'] ? __('Connected') : __('Disconnected') }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                @endif
                            </table>
                        </div>

                        <!-- Supervisor Column -->
                        <div class="col-lg-6">
                            <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="las la-shield-alt"></i> {{ __('Supervisor Management') }}
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                @if(isset($horizonSupervisorStatus) && $horizonSupervisorStatus)
                                <tr>
                                    <td width="40%"><strong>{{ __('Cron Supervisor') }}:</strong></td>
                                    <td>
                                        @if($isSystemSupervisor)
                                            <span class="badge badge-info">{{ __('N/A (System Supervisor Active)') }}</span>
                                        @else
                                            <span class="badge badge-{{ $isCronActive ? 'success' : 'secondary' }}">
                                                {{ $isCronActive ? __('Active') : __('Inactive') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('System Supervisor') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $isSystemSupervisor ? 'info' : 'secondary' }}">
                                            {{ $isSystemSupervisor ? __('Enabled') : __('Disabled') }}
                                        </span>
                                    </td>
                                </tr>
                                @if(!$isSystemSupervisor)
                                <tr>
                                    <td><strong>{{ __('Check Interval') }}:</strong></td>
                                    <td>{{ __('Every :minutes minutes', ['minutes' => $horizonSupervisorStatus['schedule_minutes'] ?? 3]) }}</td>
                                </tr>
                                @if(isset($horizonSupervisorStatus['last_run']) && $horizonSupervisorStatus['last_run'])
                                <tr>
                                    <td><strong>{{ __('Last Check') }}:</strong></td>
                                    <td>{{ $horizonSupervisorStatus['last_run'] }}</td>
                                </tr>
                                @endif
                                @endif
                                @else
                                <tr>
                                    <td colspan="2" class="text-muted">{{ __('Supervisor information not available') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div class="row mt-3">
                        <div class="col-12">
                            @php
                                $alertType = 'info';
                                $alertMessage = '';
                                $alertIcon = 'info-circle';
                                
                                if (!isset($horizonStats) || !$horizonStats['available'] || (isset($horizonStats['installed']) && !$horizonStats['installed'])) {
                                    $alertType = 'info';
                                    $alertIcon = 'info-circle';
                                    $alertMessage = __('Laravel Horizon is not installed. Install it with: composer require laravel/horizon');
                                } elseif ($isHorizonActive) {
                                    $alertType = 'success';
                                    $alertIcon = 'check-circle';
                                    if ($isSystemSupervisor) {
                                        $alertMessage = __('Horizon is running via System Supervisor and processing jobs normally.');
                                    } else {
                                        $alertMessage = __('Horizon is running and processing jobs. ' . ($isCronActive ? 'Cron supervisor is monitoring and will auto-restart if needed.' : ''));
                                    }
                                } elseif (isset($horizonStats['message'])) {
                                    $alertType = $isHorizonActive ? 'success' : 'warning';
                                    $alertIcon = $isHorizonActive ? 'check-circle' : 'exclamation-circle';
                                    $alertMessage = $horizonStats['message'];
                                    if ($isSystemSupervisor && !$isHorizonActive) {
                                        $alertMessage .= ' ' . __('Ensure System Supervisor is configured to run Horizon.');
                                    }
                                } elseif ($isSystemSupervisor) {
                                    $alertType = 'info';
                                    $alertIcon = 'info-circle';
                                    $alertMessage = __('System Supervisor is enabled. Horizon should be managed by your system supervisor configuration. Ensure Horizon worker is configured in supervisor.');
                                } else {
                                    $alertType = 'warning';
                                    $alertIcon = 'exclamation-circle';
                                    $alertMessage = __('Horizon is not running. ' . ($isCronActive ? 'Cron supervisor is active and will attempt to restart it.' : 'Start it with: php artisan horizon or enable cron supervisor.'));
                                }
                            @endphp
                            <div class="alert alert-{{ $alertType }} mb-0">
                                <i class="las la-{{ $alertIcon }}"></i> 
                                {{ $alertMessage }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="main-content-wrapper">
                @include('backend.setting.performance')
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .performance-page { background-color: #f8f9fa; min-height: calc(100vh - 150px); padding-bottom: 2rem; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
    .page-title { color: white; font-weight: 600; font-size: 1.75rem; margin: 0; }
    .page-header .text-white-50 { color: rgba(255, 255, 255, 0.8) !important; }
    .stats-card { background: white; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; border-left: 4px solid; height: 100%; }
    .stats-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12); }
    .stats-card-primary { border-left-color: #007bff; }
    .stats-card-success { border-left-color: #28a745; }
    .stats-card-info { border-left-color: #17a2b8; }
    .stats-card-warning { border-left-color: #ffc107; }
    .stats-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-right: 1.25rem; flex-shrink: 0; color: white; }
    .stats-card-primary .stats-icon { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
    .stats-card-success .stats-icon { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
    .stats-card-info .stats-icon { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); }
    .stats-card-warning .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
    .stats-card-secondary .stats-icon { background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); }
    .stats-label { font-size: 0.875rem; color: #6c757d; margin: 0 0 0.5rem 0; font-weight: 500; }
    .stats-value { font-size: 1.5rem; font-weight: 700; margin: 0; color: #212529; }
    .main-content-wrapper { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); overflow: hidden; padding: 0; }
    .main-content-wrapper .card { border: none; box-shadow: none; border-radius: 0; background: transparent; }
    .main-content-wrapper .card-header { background: #f8f9fa; border-bottom: 2px solid #e9ecef; padding: 1.25rem 1.5rem; }
    .main-content-wrapper .card-header h4, .main-content-wrapper .card-header h5 { margin: 0; font-weight: 600; color: #495057; }
    .main-content-wrapper .card-body { padding: 1.5rem; }
    .main-content-wrapper .table thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #6c757d; padding: 0.75rem; }
    .main-content-wrapper .table tbody tr:hover { background-color: #f8f9fa; }
    .main-content-wrapper .btn { border-radius: 6px; font-weight: 500; transition: all 0.2s ease; }
    .main-content-wrapper .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); }
    .main-content-wrapper .card { border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 1.5rem; transition: all 0.3s ease; }
    .main-content-wrapper .card:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    .main-content-wrapper .progress { border-radius: 6px; overflow: hidden; }
    .main-content-wrapper h5.mb-3 { color: #495057; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef; margin-bottom: 1.5rem !important; }
    @media (max-width: 768px) { .page-header { padding: 1.5rem; } .stats-icon { width: 50px; height: 50px; font-size: 1.5rem; } .stats-value { font-size: 1.25rem; } }
</style>
@endpush

@push('script')
<script>
    $(document).ready(function() {
        setTimeout(function() {
            var phpVersion = $('#sys-php-version').text();
            var laravelVersion = $('#sys-laravel-version').text();
            var opcacheStatus = $('#opcache-enabled-status').find('.badge').text();
            if (phpVersion) $('#quick-php-version').text(phpVersion);
            if (laravelVersion) $('#quick-laravel-version').text(laravelVersion);
            if (opcacheStatus) {
                var badgeClass = opcacheStatus.includes('Enabled') ? 'badge-success' : 'badge-danger';
                $('#quick-opcache-status').html('<span class="badge ' + badgeClass + '">' + opcacheStatus + '</span>');
            }
        }, 2000);
    });
</script>
@endpush
@endsection
