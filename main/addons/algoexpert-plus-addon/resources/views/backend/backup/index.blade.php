@extends('backend.layout.master')

@section('title', $title ?? 'Backup Dashboard')

@section('element')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1">Backup Dashboard</h4>
                            <p class="text-muted mb-0">Manage and monitor your system backups</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" id="run-backup-btn">
                                <i data-feather="play"></i> Run Backup
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" id="clean-backups-btn">
                                <i data-feather="trash-2"></i> Clean Old Backups
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Backups</h6>
                            <h3 class="mb-0">{{ $stats['total_count'] ?? 0 }}</h3>
                        </div>
                        <div class="text-primary">
                            <i data-feather="database" style="width: 48px; height: 48px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Size</h6>
                            <h3 class="mb-0">{{ $stats['total_size_human'] ?? '0 B' }}</h3>
                        </div>
                        <div class="text-info">
                            <i data-feather="hard-drive" style="width: 48px; height: 48px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Newest Backup</h6>
                            <h6 class="mb-0">
                                @if($stats['newest_backup'] ?? null)
                                    {{ $stats['newest_backup']['age_human'] }}
                                @else
                                    No backups
                                @endif
                            </h6>
                        </div>
                        <div class="text-success">
                            <i data-feather="clock" style="width: 48px; height: 48px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Oldest Backup</h6>
                            <h6 class="mb-0">
                                @if($stats['oldest_backup'] ?? null)
                                    {{ $stats['oldest_backup']['age_human'] }}
                                @else
                                    No backups
                                @endif
                            </h6>
                        </div>
                        <div class="text-warning">
                            <i data-feather="archive" style="width: 48px; height: 48px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Backups Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="list"></i> Historical Backups</h5>
                </div>
                <div class="card-body">
                    @if(count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Backup Name</th>
                                    <th>Disk</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Age</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                <tr>
                                    <td>
                                        <code class="small">{{ basename($backup['name']) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $backup['disk'] }}</span>
                                    </td>
                                    <td>{{ $backup['size_human'] }}</td>
                                    <td>{{ $backup['date_human'] }}</td>
                                    <td>
                                        <span class="badge badge-{{ $backup['age_days'] > 30 ? 'warning' : 'success' }}">
                                            {{ $backup['age_human'] }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.algoexpert-plus.backup.download', ['path' => $backup['path'], 'disk' => $backup['disk']]) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Download">
                                                <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-backup-btn" 
                                                    data-path="{{ $backup['path'] }}" 
                                                    data-disk="{{ $backup['disk'] }}"
                                                    data-name="{{ basename($backup['name']) }}"
                                                    title="Delete">
                                                <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i data-feather="inbox" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
                        <p class="text-muted">No backups found. Create your first backup by clicking "Run Backup".</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    // Run Backup
    $('#run-backup-btn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        if (!confirm('This will create a new backup. This may take a few minutes. Continue?')) {
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 16px; height: 16px;"></i> Running...');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.run') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Backup started successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert(response.message || 'Backup failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Backup failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Clean Old Backups
    $('#clean-backups-btn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        if (!confirm('This will delete old backups based on your configuration. Continue?')) {
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 16px; height: 16px;"></i> Cleaning...');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.clean') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Backup cleanup completed!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert(response.message || 'Cleanup failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Cleanup failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Delete Backup
    $('.delete-backup-btn').on('click', function() {
        const btn = $(this);
        const path = btn.data('path');
        const disk = btn.data('disk');
        const name = btn.data('name');
        
        if (!confirm(`Are you sure you want to delete backup "${name}"?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i>');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.delete') }}',
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                path: path,
                disk: disk
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Backup deleted successfully!');
                    window.location.reload();
                } else {
                    alert(response.message || 'Delete failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Delete failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endpush
@endsection
