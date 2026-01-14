@extends('backend.layout.master')

@section('title', $title ?? 'Horizon Queue Dashboard')

@section('element')
<div class="container-fluid horizon-page">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="page-title mb-1">
                            <i class="las la-tachometer-alt text-white"></i>
                            {{ __('Horizon Queue Dashboard') }}
                        </h3>
                        <p class="text-muted mb-0 text-white-50">{{ __('Monitor and manage your queue jobs in real-time') }}</p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('admin.algoexpert-plus.system-tools.dashboard') }}" class="btn btn-light btn-sm">
                            <i class="las la-arrow-left"></i> {{ __('Back') }}
                        </a>
                        @if($isAvailable && $isRunning)
                        <button type="button" class="btn btn-light btn-sm ml-2" onclick="refreshHorizon()">
                            <i class="las la-sync-alt"></i> {{ __('Refresh Stats') }}
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-{{ $isRunning ? 'success' : 'warning' }}">
                <div class="stats-icon">
                    <i class="las la-{{ $isRunning ? 'check-circle' : 'exclamation-circle' }}"></i>
                </div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Horizon Status') }}</p>
                    <h4 class="stats-value">
                        @if(!$isAvailable)
                            <span class="badge badge-secondary">{{ __('Not Installed') }}</span>
                        @elseif($isRunning)
                            <span class="badge badge-success">{{ __('Running') }}</span>
                        @else
                            <span class="badge badge-warning">{{ __('Stopped') }}</span>
                        @endif
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-info">
                <div class="stats-icon"><i class="las la-list"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Pending Jobs') }}</p>
                    <h4 class="stats-value" id="pending-jobs-count">
                        {{ number_format($queueStats['pending'] ?? 0) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-danger">
                <div class="stats-icon"><i class="las la-exclamation-triangle"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Failed Jobs') }}</p>
                    <h4 class="stats-value" id="failed-jobs-count">
                        {{ number_format($queueStats['failed'] ?? 0) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card stats-card-primary">
                <div class="stats-icon"><i class="las la-server"></i></div>
                <div class="stats-content">
                    <p class="stats-label">{{ __('Processes') }}</p>
                    <h4 class="stats-value" id="processes-count">
                        {{ $horizonStats['processes'] ?? 0 }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Horizon Status & Info Cards -->
    @if(isset($horizonStats) && $horizonStats)
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card border-{{ $isRunning ? 'success' : 'warning' }}">
                <div class="card-header bg-{{ $isRunning ? 'success' : 'warning' }} text-white">
                    <h5 class="mb-0">
                        <i class="las la-info-circle"></i> {{ __('Horizon Information') }}
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td><strong>{{ __('Status') }}:</strong></td>
                            <td>
                                @if($isRunning)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-warning">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        @if(isset($horizonStats['throughput']))
                        <tr>
                            <td><strong>{{ __('Throughput') }}:</strong></td>
                            <td>{{ number_format($horizonStats['throughput'] ?? 0) }} {{ __('jobs/min') }}</td>
                        </tr>
                        @endif
                        @if(isset($horizonStats['wait_time']))
                        <tr>
                            <td><strong>{{ __('Wait Time') }}:</strong></td>
                            <td>{{ number_format($horizonStats['wait_time'] ?? 0, 2) }}s</td>
                        </tr>
                        @endif
                        @if(isset($horizonStats['processes']))
                        <tr>
                            <td><strong>{{ __('Worker Processes') }}:</strong></td>
                            <td>{{ $horizonStats['processes'] ?? 0 }}</td>
                        </tr>
                        @endif
                        @if(isset($queueStats['queue_connection']))
                        <tr>
                            <td><strong>{{ __('Queue Connection') }}:</strong></td>
                            <td>
                                <span class="badge badge-{{ $queueStats['queue_connection'] === 'redis' ? 'success' : 'warning' }}">
                                    {{ $queueStats['queue_connection'] ?? 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    </table>
                    @if(isset($horizonStats['message']))
                    <div class="alert alert-{{ $isRunning ? 'success' : 'warning' }} mt-3 mb-0">
                        <i class="las la-{{ $isRunning ? 'check-circle' : 'exclamation-circle' }}"></i>
                        {{ __($horizonStats['message']) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="las la-cogs"></i> {{ __('Quick Actions') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        @if(!$isRunning)
                        <a href="{{ route('admin.algoexpert-plus.system-tools.performance') }}" class="btn btn-warning btn-sm">
                            <i class="las la-play-circle"></i> {{ __('Start Horizon') }}
                        </a>
                        @endif
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#horizonModal" {{ !$isRunning ? 'disabled' : '' }}>
                            <i class="las la-expand-arrows-alt"></i> {{ __('Open Fullscreen Dashboard') }}
                        </button>
                        <a href="{{ route('horizon.index') }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="las la-external-link-alt"></i> {{ __('Open in New Tab') }}
                        </a>
                        @if(isset($queueStats['failed']) && $queueStats['failed'] > 0)
                        <button type="button" class="btn btn-danger btn-sm" id="clear-failed-btn" onclick="clearFailedJobs()">
                            <i class="las la-trash"></i> {{ __('Clear All Failed Jobs') }} ({{ $queueStats['failed'] }})
                        </button>
                        @endif
                        @if($isAvailable && isset($queueDiagnostics) && $queueDiagnostics['horizon_compatible'])
                        <button type="button" class="btn btn-success btn-sm" id="test-job-btn" onclick="testJobDispatch()">
                            <i class="las la-vial"></i> {{ __('Test Job Dispatch') }}
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Queue Diagnostics -->
    @if(isset($queueDiagnostics))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-{{ $queueDiagnostics['horizon_compatible'] ? 'success' : 'warning' }}">
                <div class="card-header bg-{{ $queueDiagnostics['horizon_compatible'] ? 'success' : 'warning' }} text-white">
                    <h5 class="mb-0">
                        <i class="las la-search"></i> {{ __('Queue Configuration Diagnostics') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td width="40%"><strong>{{ __('Queue Connection') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $queueDiagnostics['is_redis'] ? 'success' : 'warning' }}">
                                            {{ $queueDiagnostics['queue_connection'] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Queue Driver') }}:</strong></td>
                                    <td>{{ $queueDiagnostics['queue_driver'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Horizon Compatible') }}:</strong></td>
                                    <td>
                                        @if($queueDiagnostics['horizon_compatible'])
                                            <span class="badge badge-success">{{ __('Yes') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if(isset($queueStats['redis_pending']))
                                <tr>
                                    <td><strong>{{ __('Jobs in Redis') }}:</strong></td>
                                    <td>{{ number_format($queueStats['redis_pending']) }}</td>
                                </tr>
                                @endif
                                @if(isset($queueStats['database_pending']))
                                <tr>
                                    <td><strong>{{ __('Jobs in Database') }}:</strong></td>
                                    <td>{{ number_format($queueStats['database_pending']) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if(!empty($queueDiagnostics['issues']))
                            <div class="alert alert-warning mb-2">
                                <h6 class="alert-heading"><i class="las la-exclamation-triangle"></i> {{ __('Issues Found') }}:</h6>
                                <ul class="mb-0">
                                    @foreach($queueDiagnostics['issues'] as $issue)
                                    <li>{{ __($issue) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @if(!empty($queueDiagnostics['recommendations']))
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading"><i class="las la-lightbulb"></i> {{ __('Recommendations') }}:</h6>
                                <ul class="mb-0">
                                    @foreach($queueDiagnostics['recommendations'] as $rec)
                                    <li>{{ __($rec) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @if(empty($queueDiagnostics['issues']) && $queueDiagnostics['horizon_compatible'])
                            <div class="alert alert-success mb-0">
                                <i class="las la-check-circle"></i> 
                                {{ __('Queue configuration is correct for Horizon. Jobs should appear here when dispatched.') }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Horizon Dashboard Trigger -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <span class="icon-circle bg-primary-light text-primary">
                            <i class="las la-tachometer-alt" style="font-size: 3rem;"></i>
                        </span>
                    </div>
                    <h3 class="mb-2">{{ __('Horizon Dashboard') }}</h3>
                    <p class="text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
                        {{ __('Access the full Horizon dashboard to monitor job throughput, runtime metrics, and job failures in real-time. The dashboard runs in a dedicated fullscreen view for better visibility.') }}
                    </p>
                    
                    @if($isAvailable && $isRunning)
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-primary btn-lg shadow-sm" data-toggle="modal" data-target="#horizonModal">
                                <i class="las la-expand-arrows-alt"></i> {{ __('Open Fullscreen Dashboard') }}
                            </button>
                            <a href="{{ route('horizon.index') }}" target="_blank" class="btn btn-outline-primary btn-lg shadow-sm ml-3">
                                <i class="las la-external-link-alt"></i> {{ __('Open in New Tab') }}
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning d-inline-block">
                            <i class="las la-exclamation-triangle"></i> 
                            {{ __('Horizon is not running. Please start the worker to access the dashboard.') }}
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.algoexpert-plus.system-tools.performance') }}" class="btn btn-primary">
                                <i class="las la-cog"></i> {{ __('Go to Performance Settings') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Horizon Modal -->
<div class="modal fade p-0" id="horizonModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 100%; width: 100%; height: 100%; margin: 0;">
        <div class="modal-content" style="height: 100%; border: 0; border-radius: 0;">
            <div class="modal-header py-2" style="background: #4052bf; color: white; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div class="d-flex align-items-center">
                    <i class="las la-tachometer-alt mr-2" style="font-size: 1.5rem;"></i>
                    <h5 class="modal-title text-white mb-0">{{ __('Horizon Dashboard') }}</h5>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-light mr-3 text-primary">
                        <i class="las la-circle text-success" style="font-size: 0.7em;"></i> {{ __('Live') }}
                    </span>
                    <button type="button" class="btn btn-sm btn-light text-primary font-weight-bold" data-dismiss="modal">
                        <i class="las la-arrow-left"></i> {{ __('Back to Admin') }}
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light" style="position: relative;">
                <div id="modal-loading" class="d-flex flex-column justify-content-center align-items-center h-100 bg-white">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">{{ __('Loading...') }}</span>
                    </div>
                    <p class="mt-3 text-muted">{{ __('Connecting to Horizon...') }}</p>
                </div>
                <iframe id="horizon-modal-iframe" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .horizon-page {
        background-color: #f8f9fa;
        min-height: calc(100vh - 150px);
        padding-bottom: 2rem;
    }
    
    .icon-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: rgba(64, 82, 191, 0.1);
    }

    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem;
        border-radius: 12px;
        color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .page-title {
        color: white;
        font-weight: 600;
        font-size: 1.75rem;
        margin: 0;
    }

    .page-header .text-white-50 {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .stats-card-primary { border-left-color: #007bff; }
    .stats-card-success { border-left-color: #28a745; }
    .stats-card-info { border-left-color: #17a2b8; }
    .stats-card-warning { border-left-color: #ffc107; }
    .stats-card-danger { border-left-color: #dc3545; }
    .stats-card-secondary { border-left-color: #6c757d; }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-right: 1.25rem;
        flex-shrink: 0;
        color: white;
    }

    .stats-card-primary .stats-icon { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
    .stats-card-success .stats-icon { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
    .stats-card-info .stats-icon { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); }
    .stats-card-warning .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
    .stats-card-danger .stats-icon { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
    .stats-card-secondary .stats-icon { background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); }

    .stats-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0 0 0.5rem 0;
        font-weight: 500;
    }

    .stats-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: #212529;
    }

    .queue-stat-box {
        text-align: center;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .queue-stat-box:hover {
        border-color: #007bff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .queue-name {
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .queue-count {
        font-size: 2rem;
        font-weight: 700;
        color: #007bff;
        margin-bottom: 0.25rem;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
        margin-bottom: 1.5rem;
    }

    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        padding: 1.25rem 1.5rem;
        font-weight: 600;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .page-header {
            padding: 1.5rem;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }
        .stats-value {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Modal handling for Horizon
        $('#horizonModal').on('show.bs.modal', function () {
            var iframe = $('#horizon-modal-iframe');
            var loading = $('#modal-loading');
            
            // Force reset on open
            loading.show();
            iframe.hide();
            
            // Set src only if empty or to reload
            if (!iframe.attr('src')) {
                iframe.attr('src', '{{ $horizonUrl }}');
            } else {
                // If src exists, force reload by setting src to itself
                var currentSrc = iframe.attr('src');
                iframe.attr('src', currentSrc);
            }
            
            // Remove previous handlers to avoid duplicates
            iframe.off('load');
            
            // One-time load handler
            iframe.on('load', function() {
                loading.hide();
                iframe.show();
            });
            
            // Fallback timeout to show iframe anyway if load event misses
            setTimeout(function() {
                if(iframe.is(':hidden')) {
                    loading.hide();
                    iframe.show();
                }
            }, 5000); // 5 seconds timeout
        });
    });

    function refreshHorizon() {
        // Only refresh stats, as dashboard is now separate
        window.location.reload();
    }





    function testJobDispatch() {
        var btn = document.getElementById('test-job-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="las la-spinner la-spin"></i> Dispatching...';
        }

        fetch('{{ route("admin.algoexpert-plus.horizon.test-job") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                alert('❌ ' + data.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="las la-vial"></i> Test Job Dispatch';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to dispatch test job. Check console for details.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="las la-vial"></i> {{ __("Test Job Dispatch") }}';
            }
        });
    }

    // Ensure function is in global scope
    window.clearFailedJobs = function clearFailedJobs() {
        var count = {{ $queueStats['failed'] ?? 0 }};
        if (count === 0) {
            alert('ℹ️ No failed jobs to clear.');
            return;
        }

        if (!confirm('Are you sure you want to permanently delete all ' + count + ' failed job(s)? This action cannot be undone.')) {
            return;
        }

        var btn = document.getElementById('clear-failed-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="las la-spinner la-spin"></i> Clearing...';
        }

        fetch('{{ route("admin.algoexpert-plus.horizon.clear-failed") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                alert('❌ ' + data.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="las la-trash"></i> Clear All Failed Jobs (' + count + ')';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to clear failed jobs. Check console for details.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="las la-trash"></i> Clear All Failed Jobs (' + count + ')';
            }
        });
    };
</script>
@endpush
@endsection
