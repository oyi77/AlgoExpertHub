@extends('backend.layout.master')

@section('title', $title ?? 'Backup Dashboard')

@section('element')
<div class="container-fluid">
    {{-- Header --}}
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
                            @if($spatieAvailable)
                            <button type="button" class="btn btn-primary" id="run-backup-btn">
                                <i data-feather="play"></i> Run Full Backup
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" id="clean-backups-btn">
                                <i data-feather="trash-2"></i> Clean Old Backups
                            </button>
                            @endif
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
                            <h6 class="text-muted mb-1">Full System Backups</h6>
                            <h3 class="mb-0">{{ $spatieStats['total_count'] ?? 0 }}</h3>
                            <small class="text-muted">{{ $spatieStats['total_size_human'] ?? '0 B' }}</small>
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
                            <h6 class="text-muted mb-1">SQL Database Backups</h6>
                            <h3 class="mb-0">{{ $sqlStats['total_count'] ?? 0 }}</h3>
                            <small class="text-muted">{{ $sqlStats['total_size_human'] ?? '0 B' }}</small>
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
                                @if($spatieStats['newest_backup'] ?? null)
                                    {{ $spatieStats['newest_backup']['age_human'] }}
                                @elseif($sqlStats['newest_backup'] ?? null)
                                    {{ \Carbon\Carbon::parse($sqlStats['newest_backup']['created_at'])->diffForHumans() }}
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
                            <h6 class="text-muted mb-1">Total Size</h6>
                            <h3 class="mb-0">
                                @php
                                    $totalSize = ($spatieStats['total_size'] ?? 0) + ($sqlStats['total_size'] ?? 0);
                                    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                    $bytes = $totalSize;
                                    $i = 0;
                                    while ($bytes > 1024 && $i < count($units) - 1) {
                                        $bytes /= 1024;
                                        $i++;
                                    }
                                    echo round($bytes, 2) . ' ' . $units[$i];
                                @endphp
                            </h3>
                        </div>
                        <div class="text-warning">
                            <i data-feather="archive" style="width: 48px; height: 48px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-tabs" id="backupTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="sql-tab" data-toggle="tab" href="#sql-backups" role="tab">
                        <i data-feather="database"></i> SQL Database Backups
                    </a>
                </li>
                @if($spatieAvailable)
                <li class="nav-item">
                    <a class="nav-link" id="spatie-tab" data-toggle="tab" href="#spatie-backups" role="tab">
                        <i data-feather="server"></i> Full System Backups
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" id="restore-tab" data-toggle="tab" href="#restore-options" role="tab">
                        <i data-feather="refresh-cw"></i> Restore Options
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content" id="backupTabsContent">
        {{-- SQL Database Backups Tab --}}
        <div class="tab-pane fade show active" id="sql-backups" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i data-feather="database"></i> SQL Database Backups</h5>
                                <button type="button" class="btn btn-sm btn-primary" id="create-sql-backup-btn">
                                    <i data-feather="plus"></i> Create SQL Backup
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($sqlBackups) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Backup Name</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sqlBackups as $backup)
                                        <tr class="{{ $backup['is_factory'] ? 'table-success' : '' }}">
                                            <td>
                                                <code class="small">{{ $backup['filename'] }}</code>
                                                @if($backup['is_factory'])
                                                    <span class="badge badge-success ml-2">Factory Default</span>
                                                @endif
                                            </td>
                                            <td>{{ $backup['size_human'] }}</td>
                                            <td>{{ $backup['created_at'] }}</td>
                                            <td class="text-right">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.algoexpert-plus.backup.sql.download', ['backup_file' => $backup['filename']]) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Download">
                                                        <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                                    </a>
                                                    @if(!$backup['is_factory'])
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning save-factory-btn" 
                                                            data-filename="{{ $backup['filename'] }}"
                                                            title="Set as Factory Default">
                                                        <i data-feather="star" style="width: 14px; height: 14px;"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-sql-backup-btn" 
                                                            data-filename="{{ $backup['filename'] }}"
                                                            title="Delete">
                                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                    </button>
                                                    @endif
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
                                <p class="text-muted">No SQL backups found. Create your first backup by clicking "Create SQL Backup".</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Full System Backups Tab --}}
        @if($spatieAvailable)
        <div class="tab-pane fade" id="spatie-backups" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i data-feather="server"></i> Full System Backups</h5>
                        </div>
                        <div class="card-body">
                            @if(count($spatieBackups) > 0)
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
                                        @foreach($spatieBackups as $backup)
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
                                <p class="text-muted">No full system backups found. Create your first backup by clicking "Run Full Backup".</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Restore Options Tab --}}
        <div class="tab-pane fade" id="restore-options" role="tabpanel">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card border border-info">
                        <div class="card-body">
                            <h6 class="card-title text-info">
                                <i data-feather="refresh-cw"></i> Restore from SQL Backup
                            </h6>
                            <p class="card-text text-muted small">
                                Restore database from a specific SQL backup file. This will wipe current data and restore from the selected backup.
                            </p>
                            
                            @if(count($sqlBackups) > 0)
                            <form id="load-sql-backup-form">
                                @csrf
                                <div class="form-group">
                                    <label>Select Backup:</label>
                                    <select name="backup_file" class="form-control form-control-sm" required>
                                        <option value="">Select backup...</option>
                                        @foreach($sqlBackups as $backup)
                                            @if(!$backup['is_factory'])
                                            <option value="{{ $backup['filename'] }}">
                                                {{ $backup['filename'] }} ({{ $backup['size_human'] }}) - {{ $backup['created_at'] }}
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i data-feather="upload"></i> Load Selected Backup
                                </button>
                            </form>
                            @else
                            <p class="text-muted small">No backups available. Create one first.</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card border border-success">
                        <div class="card-body">
                            <h6 class="card-title text-success">
                                <i data-feather="industry"></i> Restore Factory State
                            </h6>
                            <p class="card-text text-muted small">
                                Restore database to factory default state using seeders ({{ $seederCount ?? 0 }} seeders). This will wipe ALL data and re-seed fresh demo data.
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>✅ Fresh install with demo data</li>
                                <li>✅ Uses DatabaseSeeder ({{ $seederCount ?? 0 }} seeders)</li>
                                <li>⚠️ Wipes ALL data, re-migrates, seeds</li>
                            </ul>
                            <form id="load-factory-form">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i data-feather="industry"></i> Restore Factory State
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Run Full Backup (Spatie)
    $('#run-backup-btn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        if (!confirm('This will create a new full system backup. This may take a few minutes. Continue?')) {
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
    
    // Create SQL Backup
    $('#create-sql-backup-btn').on('click', function() {
        const name = prompt('Enter backup name (optional):', 'backup_{{ date('Y-m-d_H-i-s') }}');
        if (name === null) return;
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true);
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i> Creating...');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.sql.create') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                backup_name: name
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Backup created successfully!');
                    window.location.reload();
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
    
    // Delete SQL Backup
    $('.delete-sql-backup-btn').on('click', function() {
        const btn = $(this);
        const filename = btn.data('filename');
        
        if (!confirm(`Are you sure you want to delete backup "${filename}"?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i>');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.sql.delete') }}',
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                backup_file: filename
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
    
    // Save as Factory State
    $('.save-factory-btn').on('click', function() {
        const btn = $(this);
        const filename = btn.data('filename');
        
        if (!confirm(`Set "${filename}" as factory default state?`)) {
            return;
        }
        
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i>');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.sql.save-factory') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                backup_file: filename
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Factory state saved successfully!');
                    window.location.reload();
                } else {
                    alert(response.message || 'Save failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Save failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Load SQL Backup
    $('#load-sql-backup-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('⚠️ WARNING: This will wipe ALL current database data and restore from the selected backup.\n\nYou will be logged out after restore. Continue?')) {
            return;
        }
        
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.prop('disabled', true);
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i> Restoring...');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.sql.load') }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Database restored successfully! Redirecting to login...');
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = '{{ route('admin.login') }}';
                    }
                } else {
                    alert(response.message || 'Restore failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Restore failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Load Factory State
    $('#load-factory-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('⚠️ WARNING: This will wipe ALL current database data and restore to factory state.\n\nYou will be logged out after restore. Continue?')) {
            return;
        }
        
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.prop('disabled', true);
        btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i> Restoring...');
        
        $.ajax({
            url: '{{ route('admin.algoexpert-plus.backup.sql.load-factory') }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Factory state restored successfully! Redirecting to login...');
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = '{{ route('admin.login') }}';
                    }
                } else {
                    alert(response.message || 'Restore failed');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Restore failed. Please check the logs.';
                alert(error);
                btn.prop('disabled', false);
                btn.html(originalHtml);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    });
    
    // Delete Spatie Backup
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
    
    // Re-initialize feather icons after AJAX
    $(document).ajaxComplete(function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
});
</script>
@endpush
@endsection
