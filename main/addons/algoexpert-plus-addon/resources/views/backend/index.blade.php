@extends('backend.layout.master')

@section('title', $title ?? 'AlgoExpert++')

@section('element')
<div class="container-fluid">
    {{-- Alert container for dynamic messages --}}
    <div id="dependency-alert" style="display: none;" class="mb-3"></div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1">AlgoExpert++</h4>
                            <p class="mb-0 text-muted">Modular integration layer for SEO, Queues dashboard, UI components, and i18n.</p>
                        </div>
                        @php
                            $allDeps = collect($modules)->filter(function($m) {
                                return isset($m['dependency']) && ($m['dependency']['needs_install'] || $m['dependency']['needs_config']);
                            });
                            $hasMissingDeps = $allDeps->isNotEmpty();
                        @endphp
                        @if($hasMissingDeps)
                        <div>
                            <button type="button" class="btn btn-primary" id="install-all-btn">
                                <i data-feather="download" style="width: 16px; height: 16px;"></i> Install All Dependencies
                            </button>
                            <div class="mt-2">
                                <small class="text-muted d-block">
                                    <strong>Manual Installation:</strong>
                                </small>
                                <code class="small d-block mt-1 p-2 bg-light rounded" style="font-size: 11px;">
                                    cd {{ base_path() }} && composer install
                                </code>
                                <small class="text-muted d-block mt-1">
                                    Make sure all packages are in <code>composer.json</code> first
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($modules as $key => $module)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 {{ $module['enabled'] ? 'border-success' : 'border-secondary' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">{{ $module['name'] }}</h5>
                        <span class="badge {{ $module['enabled'] ? 'badge-success' : 'badge-secondary' }}">
                            {{ $module['enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <p class="card-text text-muted small">{{ $module['description'] }}</p>
                    
                    @if(isset($module['status']))
                    <div class="mt-2">
                        @if($module['status'] === true || (is_array($module['status']) && ($module['status']['available'] ?? false)))
                            <span class="badge badge-success">Available</span>
                        @else
                            <span class="badge badge-warning">Not Available</span>
                            @if(isset($module['status_message']))
                            <div class="mt-1">
                                <small class="text-muted">{{ $module['status_message'] }}</small>
                            </div>
                            @endif
                            
                            @if(isset($module['dependency']))
                            <div class="mt-2">
                                @if($module['dependency']['needs_install'])
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-primary install-package-btn" 
                                            data-module="{{ $key }}" 
                                            data-action="install"
                                            data-package="{{ $module['dependency']['package'] ?? 'N/A' }}">
                                        <i data-feather="download" style="width: 14px; height: 14px;"></i> Install Package
                                    </button>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">
                                            <strong>Manual Installation:</strong>
                                        </small>
                                        <code class="small d-block mt-1 p-2 bg-light rounded" style="font-size: 11px;">
                                            cd {{ base_path() }} && composer require {{ $module['dependency']['package'] ?? 'N/A' }}
                                        </code>
                                        <small class="text-muted d-block mt-1">
                                            Or if already in composer.json: <code class="small">composer install</code>
                                        </small>
                                    </div>
                                </div>
                                @endif
                                
                                @if($module['dependency']['needs_config'])
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-info update-config-btn" 
                                            data-module="{{ $key }}" 
                                            data-action="config"
                                            data-message="{{ $module['dependency']['config_message'] ?? 'N/A' }}">
                                        <i data-feather="settings" style="width: 14px; height: 14px;"></i> Update Config
                                    </button>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">
                                            <strong>Manual Configuration:</strong>
                                        </small>
                                        <code class="small d-block mt-1 p-2 bg-light rounded" style="font-size: 11px;">
                                            {{ $module['dependency']['config_message'] ?? 'N/A' }}
                                        </code>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        @endif
                    </div>
                    @endif

                    @if(isset($module['url']) && $module['url'])
                    <div class="mt-3">
                        <a href="{{ $module['url'] }}" class="btn btn-sm btn-primary" target="_blank">
                            Open Dashboard <i data-feather="external-link" class="ml-1" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                    @endif

                    @if($key === 'backup' && $module['enabled'] && ($module['status'] === true || (is_array($module['status']) && ($module['status']['available'] ?? false))))
                    <div class="mt-3">
                        <form action="{{ route('admin.algoexpert-plus.backup.run') }}" method="GET" onsubmit="return confirm('Are you sure you want to run a backup now?');">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i data-feather="download" style="width: 14px; height: 14px;"></i> Run Backup
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
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

{{-- Command Output Modal --}}
<div class="modal fade" id="commandOutputModal" tabindex="-1" role="dialog" aria-labelledby="commandOutputModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commandOutputModalLabel">
                    <i data-feather="terminal" style="width: 20px; height: 20px;"></i> Command Output
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <strong>Command:</strong>
                    <code id="modal-command" class="d-block mt-1 p-2 bg-light rounded" style="font-size: 11px; word-break: break-all;"></code>
                </div>
                <div class="mb-2">
                    <strong>Status:</strong>
                    <span id="modal-status" class="badge badge-info ml-2">Running...</span>
                </div>
                <div>
                    <strong>Output:</strong>
                    <div id="modal-output" class="mt-2 p-3 bg-dark text-light rounded" style="font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">
                        <div class="text-center text-muted">
                            <i data-feather="loader" class="spinner-border spinner-border-sm"></i> Waiting for output...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="copy-output-btn" onclick="copyModalOutput()">
                    <i data-feather="copy" style="width: 14px; height: 14px;"></i> Copy Output
                </button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.select();
            element.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                const btn = event.target.closest('button');
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i data-feather="check" style="width: 14px; height: 14px;"></i>';
                    setTimeout(() => {
                        btn.innerHTML = original;
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }, 2000);
                }
            } catch (err) {
                alert('Failed to copy');
            }
        }
    }

    function showAlert(message, type = 'success') {
        const alertDiv = document.getElementById('dependency-alert');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        alertDiv.style.display = 'block';
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Auto-dismiss after 10 seconds for errors, 5 for success
        const timeout = type === 'danger' ? 10000 : 5000;
        setTimeout(() => {
            $(alertDiv).fadeOut();
        }, timeout);
        
        // Re-initialize feather icons
        if (typeof feather !== 'undefined') {
            setTimeout(() => {
                feather.replace();
            }, 100);
        }
    }

    // Flag to track if confirmation was given
    window.commandConfirmed = false;
    
    function showCommandModal(command, status = 'Running...') {
        // Only show modal if confirmation was given
        if (!window.commandConfirmed && status === 'Running...') {
            console.warn('Modal blocked - confirmation not given');
            return;
        }
        $('#modal-command').text(command);
        $('#modal-status').text(status).removeClass().addClass('badge badge-info ml-2');
        $('#modal-output').html('<div class="text-center text-muted"><i data-feather="loader" class="spinner-border spinner-border-sm"></i> Waiting for output...</div>');
        $('#commandOutputModal').modal('show');
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    function updateCommandOutput(output, status = 'Completed', statusClass = 'success') {
        const outputDiv = $('#modal-output');
        const statusBadge = $('#modal-status');
        
        if (output) {
            outputDiv.html('<div style="color: #00ff00;">' + escapeHtml(output) + '</div>');
        } else {
            outputDiv.html('<div class="text-muted">No output available</div>');
        }
        
        statusBadge.text(status).removeClass().addClass('badge badge-' + statusClass + ' ml-2');
        
        // Scroll to bottom
        outputDiv.scrollTop(outputDiv[0].scrollHeight);
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    function copyModalOutput() {
        const output = $('#modal-output').text();
        const textarea = $('<textarea>').val(output).appendTo('body').select();
        try {
            document.execCommand('copy');
            const btn = $('#copy-output-btn');
            const original = btn.html();
            btn.html('<i data-feather="check" style="width: 14px; height: 14px;"></i> Copied!');
            setTimeout(() => {
                btn.html(original);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }, 2000);
        } catch (err) {
            alert('Failed to copy');
        }
        textarea.remove();
    }

    // installAllDependencies function removed - logic moved inline to click handler

    $(document).ready(function() {
        // Remove any existing handlers first to prevent duplicates
        $('#install-all-btn').off('click');
        $('.install-package-btn').off('click');
        $('.update-config-btn').off('click');
        
        // Install All Dependencies button handler - confirmation FIRST
        $('#install-all-btn').on('click', function(e) {
            // CRITICAL: Stop all event propagation FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Store button reference
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            // Reset confirmation flag
            window.commandConfirmed = false;
            
            // Show confirmation FIRST - BEFORE anything else
            // This is a BLOCKING call - code stops here until user responds
            const confirmed = window.confirm('This will install all missing packages via composer install.\n\nThis may take a few minutes. Continue?');
            
            // If user cancelled, stop everything
            if (!confirmed) {
                window.commandConfirmed = false;
                return false;
            }
            
            // Set flag ONLY after user confirms
            window.commandConfirmed = true;
            
            // Only reach here if user clicked OK
            // Disable button immediately to prevent double-clicks
            btn.disabled = true;
            btn.innerHTML = '<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 16px; height: 16px;"></i> Installing...';
            
            // Prepare command
            const homeDir = '{{ sys_get_temp_dir() }}/.composer';
            const command = 'cd {{ base_path() }} && export HOME=' + homeDir + ' && export COMPOSER_HOME=' + homeDir + ' && composer install';
            
            // Show command modal AFTER confirmation (but before AJAX)
            showCommandModal(command, 'Running...');
            
            // Make AJAX call immediately after confirmation
            $.ajax({
                url: '{{ route('admin.algoexpert-plus.install-dependencies') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    module: 'all',
                    action: 'install-all'
                },
                success: function(response) {
                    // Update modal with output
                    if (response.output) {
                        updateCommandOutput(response.output, response.success ? 'Completed' : 'Failed', response.success ? 'success' : 'danger');
                    } else {
                        updateCommandOutput('No output available', response.success ? 'Completed' : 'Failed', response.success ? 'success' : 'danger');
                    }
                    
                    if (response.success) {
                        showAlert(response.message || 'Dependencies installed successfully! Please refresh the page.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    } else {
                        let errorMsg = '<strong>' + (response.message || 'Installation failed.') + '</strong>';
                        errorMsg += '<br><br><button type="button" class="btn btn-sm btn-info" onclick="$(\'#commandOutputModal\').modal(\'show\');">View Full Output</button>';
                        
                        if (response.manual_command) {
                            errorMsg += '<br><br><strong>Run this command manually:</strong><br>';
                            errorMsg += '<div class="input-group mt-2"><input type="text" class="form-control" value="' + response.manual_command + '" readonly id="manual-cmd-all" style="font-size: 12px;"><div class="input-group-append"><button class="btn btn-sm btn-primary" onclick="copyToClipboard(\'manual-cmd-all\')"><i data-feather="copy" style="width: 14px; height: 14px;"></i></button></div></div>';
                        }
                        
                        if (response.troubleshooting && response.troubleshooting.length) {
                            errorMsg += '<br><br><strong>Troubleshooting Steps:</strong><ul class="mb-0" style="font-size: 12px;">';
                            response.troubleshooting.forEach(function(step) {
                                errorMsg += '<li>' + step + '</li>';
                            });
                            errorMsg += '</ul>';
                        }
                        
                        showAlert(errorMsg, 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }
                },
                error: function(xhr) {
                    let error = xhr.responseJSON?.message || xhr.responseText || 'Installation failed. Please check the logs.';
                    if (xhr.responseJSON?.manual_command) {
                        error += '<br><br><strong>Manual Command:</strong><br><code>' + xhr.responseJSON.manual_command + '</code>';
                    }
                    showAlert(error, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            });
            
            return false;
        });
        
        // Install Package button handler
        $('.install-package-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const btn = $(this);
            const module = btn.data('module');
            const package = btn.data('package');
            const originalHtml = btn.html();
            
            // Reset confirmation flag
            window.commandConfirmed = false;
            
            // Show confirmation FIRST - BEFORE anything else (BLOCKING call)
            const confirmed = window.confirm(`This will run: composer install\n\nPackage: ${package}\n\nThis may take a few minutes. Continue?`);
            
            if (confirmed !== true) {
                window.commandConfirmed = false;
                return false;
            }
            
            // Set flag ONLY after user confirms
            window.commandConfirmed = true;

            // Only proceed after confirmation
            btn.prop('disabled', true);
            btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i> Installing...');

            // Prepare command
            const homeDir = '{{ sys_get_temp_dir() }}/.composer';
            const command = 'cd {{ base_path() }} && export HOME=' + homeDir + ' && export COMPOSER_HOME=' + homeDir + ' && composer install ' + package;
            
            // Show command modal AFTER confirmation (but before AJAX)
            showCommandModal(command, 'Running...');
            
            // Make AJAX call immediately after confirmation
            $.ajax({
                url: '{{ route('admin.algoexpert-plus.install-dependencies') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    module: module,
                    action: 'install'
                },
                success: function(response) {
                    // Update modal with output
                    if (response.output) {
                        updateCommandOutput(response.output, response.success ? 'Completed' : 'Failed', response.success ? 'success' : 'danger');
                    } else {
                        updateCommandOutput('No output available', response.success ? 'Completed' : 'Failed', response.success ? 'success' : 'danger');
                    }
                    
                    if (response.success) {
                        showAlert(response.message || 'Package installed successfully! Please refresh the page.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    } else {
                        let errorMsg = '<strong>' + (response.message || 'Installation failed.') + '</strong>';
                        errorMsg += '<br><br><button type="button" class="btn btn-sm btn-info" onclick="$(\'#commandOutputModal\').modal(\'show\');">View Full Output</button>';
                        
                        if (response.manual_command) {
                            errorMsg += '<br><br><strong>Run this command manually:</strong><br>';
                            errorMsg += '<div class="input-group mt-2"><input type="text" class="form-control" value="' + response.manual_command + '" readonly id="manual-cmd-' + module + '" style="font-size: 12px;"><div class="input-group-append"><button class="btn btn-sm btn-primary" onclick="copyToClipboard(\'manual-cmd-' + module + '\')"><i data-feather="copy" style="width: 14px; height: 14px;"></i></button></div></div>';
                        }
                        
                        if (response.troubleshooting && response.troubleshooting.length) {
                            errorMsg += '<br><br><strong>Troubleshooting Steps:</strong><ul class="mb-0" style="font-size: 12px;">';
                            response.troubleshooting.forEach(function(step) {
                                errorMsg += '<li>' + step + '</li>';
                            });
                            errorMsg += '</ul>';
                        }
                        
                        showAlert(errorMsg, 'danger');
                        btn.prop('disabled', false);
                        btn.html(originalHtml);
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }
                },
                error: function(xhr) {
                    let error = xhr.responseJSON?.message || xhr.responseText || 'Installation failed. Please check the logs.';
                    if (xhr.responseJSON?.manual_command) {
                        error += '<br><br><strong>Manual Command:</strong><br><code>' + xhr.responseJSON.manual_command + '</code>';
                    }
                    showAlert(error, 'danger');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            });
        });

        // Update Config button handler
        $('.update-config-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const btn = $(this);
            const module = btn.data('module');
            const message = btn.data('message');
            const originalHtml = btn.html();
            
            // Reset confirmation flag
            window.commandConfirmed = false;
            
            // Show confirmation FIRST - BEFORE anything else (BLOCKING call)
            const confirmed = window.confirm(`This will update .env file:\n\n${message}\n\nContinue?`);
            
            if (confirmed !== true) {
                window.commandConfirmed = false;
                return false;
            }
            
            // Set flag ONLY after user confirms
            window.commandConfirmed = true;

            btn.prop('disabled', true);
            btn.html('<i data-feather="loader" class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></i> Updating...');

            // Prepare command
            const command = 'Updating .env file: ' + message;
            
            // Show command modal AFTER confirmation (but before AJAX)
            showCommandModal(command, 'Running...');
            
            // Make AJAX call immediately after confirmation
            $.ajax({
                url: '{{ route('admin.algoexpert-plus.install-dependencies') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    module: module,
                    action: 'config'
                },
                success: function(response) {
                    // Update modal with output
                    const output = response.output || (response.success ? 'Configuration updated successfully' : 'Update failed');
                    updateCommandOutput(output, response.success ? 'Completed' : 'Failed', response.success ? 'success' : 'danger');
                    
                    if (response.success) {
                        showAlert(response.message || 'Configuration updated successfully! Refreshing page...', 'success');
                        // Reload immediately to show updated status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        let errorMsg = response.message || 'Update failed.';
                        errorMsg += '<br><br><button type="button" class="btn btn-sm btn-info" onclick="$(\'#commandOutputModal\').modal(\'show\');">View Output</button>';
                        if (response.manual_command) {
                            errorMsg += '<br><br><strong>Manual Command:</strong><br><code>' + response.manual_command + '</code>';
                        }
                        showAlert(errorMsg, 'danger');
                        btn.prop('disabled', false);
                        btn.html(originalHtml);
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                    }
                },
                error: function(xhr) {
                    let error = xhr.responseJSON?.message || xhr.responseText || 'Update failed. Please check the logs.';
                    if (xhr.responseJSON?.manual_command) {
                        error += '<br><br><strong>Manual Command:</strong><br><code>' + xhr.responseJSON.manual_command + '</code>';
                    }
                    showAlert(error, 'danger');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection

