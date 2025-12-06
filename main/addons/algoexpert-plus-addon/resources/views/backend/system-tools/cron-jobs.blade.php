@extends('backend.layout.master')

@section('title', $title ?? 'Cron Jobs Management')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="m-0">Cron Jobs Management</h4>
                            <p class="text-muted mb-0">Configure and manage scheduled tasks for your server</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.algoexpert-plus.system-tools.cron-jobs.generate') }}" class="btn btn-primary">
                                <i data-feather="download" style="width: 16px; height: 16px;"></i> Download Crontab File
                            </a>
                            <button type="button" class="btn btn-info ml-2" onclick="testCron()">
                                <i data-feather="play" style="width: 16px; height: 16px;"></i> Test Cron
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($cronJobs) && !empty($cronJobs))
                        @foreach($cronJobs as $index => $cronJob)
                            @if(isset($cronJob['tasks']))
                                {{-- Scheduled Tasks Information --}}
                                <div class="mb-4">
                                    <h5 class="mb-3">
                                        <i class="las la-info-circle text-info"></i> 
                                        {{ $cronJob['title'] }}
                                    </h5>
                                    <p class="text-muted">{{ $cronJob['description'] }}</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Task Name') }}</th>
                                                    <th>{{ __('Frequency') }}</th>
                                                    <th>{{ __('Command') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($cronJob['tasks'] as $task)
                                                    <tr>
                                                        <td>{{ $task['name'] }}</td>
                                                        <td><span class="badge badge-primary">{{ $task['frequency'] }}</span></td>
                                                        <td><code class="small">{{ $task['command'] }}</code></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                {{-- Actual Cron Job Commands --}}
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                @if($cronJob['required'] ?? false)
                                                    <span class="badge badge-danger mr-2">{{ __('Required') }}</span>
                                                @endif
                                                {{ $cronJob['title'] }}
                                            </h6>
                                            <small class="text-muted">{{ $cronJob['description'] }}</small>
                                            @if(isset($cronJob['frequency']))
                                                <br><small class="text-info"><i class="las la-clock"></i> {{ __('Frequency') }}: {{ $cronJob['frequency'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control copy-text-{{ $index }}" 
                                               value="{{ $cronJob['command'] }}" 
                                               readonly
                                               id="cron-command-{{ $index }}">
                                        <div class="input-group-append">
                                            <button type="button" 
                                                    class="btn btn-primary copy-btn" 
                                                    data-target="cron-command-{{ $index }}">
                                                <i class="las la-copy"></i> {{ __('Copy') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="las la-exclamation-triangle"></i>
                            {{ __('No cron jobs configured.') }}
                        </div>
                    @endif

                    {{-- Full Crontab Content --}}
                    <div class="mt-4">
                        <h5 class="mb-3">
                            <i class="las la-file-alt"></i> Complete Crontab File
                        </h5>
                        <p class="text-muted small">Copy this entire content to your crontab (crontab -e)</p>
                        <div class="input-group">
                            <textarea class="form-control" rows="10" readonly id="full-crontab">{{ $crontabContent ?? '' }}</textarea>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary copy-btn" data-target="full-crontab">
                                    <i class="las la-copy"></i> Copy All
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Setup Instructions --}}
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2"><i class="las la-question-circle"></i> Quick Setup</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="small">Option 1: Manual Setup</h6>
                                <ol class="mb-0 small">
                                    <li>{{ __('SSH into your server') }}</li>
                                    <li>{{ __('Run') }}: <code>crontab -e</code></li>
                                    <li>{{ __('Paste the crontab content above') }}</li>
                                    <li>{{ __('Save and exit') }}</li>
                                    <li>{{ __('Verify with') }}: <code>crontab -l</code></li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small">Option 2: One-Line Setup</h6>
                                <p class="small mb-2">Run this command on your server:</p>
                                <div class="input-group input-group-sm">
                                    <input type="text" 
                                           class="form-control" 
                                           value="(crontab -l 2>/dev/null; echo '{{ $mainCronCommand }}') | crontab -"
                                           readonly
                                           id="setup-command">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary btn-sm copy-btn" data-target="setup-command">
                                            <i class="las la-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0 small">
                            <strong>{{ __('Note') }}:</strong> 
                            {{ __('The Laravel Scheduler cron job (running every minute) is required. It will automatically execute all scheduled tasks defined in app/Console/Kernel.php.') }}
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
        // Copy functionality
        $('.copy-btn').on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            const element = $('#' + targetId);
            
            if (element.length) {
                element.select();
                if (element[0].setSelectionRange) {
                    element[0].setSelectionRange(0, 99999);
                }
                
                try {
                    document.execCommand('copy');
                    
                    const btn = $(this);
                    const originalHtml = btn.html();
                    btn.html('<i class="las la-check"></i> {{ __('Copied!') }}')
                       .removeClass('btn-primary')
                       .addClass('btn-success');
                    
                    setTimeout(function() {
                        btn.html(originalHtml)
                           .removeClass('btn-success')
                           .addClass('btn-primary');
                    }, 2000);
                } catch (err) {
                    alert('{{ __('Failed to copy. Please select and copy manually.') }}');
                }
            }
        });

        // Auto-select on input click
        $('.copy-text-0, .copy-text-1, .copy-text-2, .copy-text-3, #full-crontab, #setup-command').on('click', function() {
            $(this).select();
            if (this.setSelectionRange) {
                this.setSelectionRange(0, 99999);
            }
        });
    });

    function testCron() {
        if (!confirm('This will run: php artisan schedule:run\n\nContinue?')) {
            return;
        }

        $.ajax({
            url: '{{ route('admin.algoexpert-plus.system-tools.cron-jobs.test') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                $('button[onclick="testCron()"]').prop('disabled', true).html('<i data-feather="loader"></i> Testing...');
            },
            success: function(response) {
                alert('Cron test successful!\n\nOutput: ' + (response.output || 'No output'));
                $('button[onclick="testCron()"]').prop('disabled', false).html('<i data-feather="play"></i> Test Cron');
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Test failed';
                alert('Cron test failed:\n\n' + error);
                $('button[onclick="testCron()"]').prop('disabled', false).html('<i data-feather="play"></i> Test Cron');
            }
        });
    }
</script>
@endpush
@endsection
