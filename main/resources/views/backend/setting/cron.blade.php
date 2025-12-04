<div class="card">
    <div class="card-header">
        <h4 class="m-0">{{ __('Cron Job Settings') }}</h4>
        <p class="text-muted mb-0">{{ __('Configure cron jobs for your server. Copy the commands below and add them to your crontab.') }}</p>
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

        {{-- Instructions --}}
        <div class="mt-4 p-3 bg-light rounded">
            <h6 class="mb-2"><i class="las la-question-circle"></i> {{ __('How to Setup Cron Jobs') }}</h6>
            <ol class="mb-0 small">
                <li>{{ __('SSH into your server') }}</li>
                <li>{{ __('Run') }}: <code>crontab -e</code></li>
                <li>{{ __('Add the required cron job commands above (especially Laravel Scheduler)') }}</li>
                <li>{{ __('Save and exit the editor') }}</li>
                <li>{{ __('Verify with') }}: <code>crontab -l</code></li>
            </ol>
            <div class="alert alert-info mt-3 mb-0 small">
                <strong>{{ __('Note') }}:</strong> 
                {{ __('The Laravel Scheduler cron job (running every minute) is required. It will automatically execute all scheduled tasks defined in app/Console/Kernel.php.') }}
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    $(document).ready(function() {
        // Enhanced copy functionality for all cron job inputs
        $('.copy-btn').on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            const input = $('#' + targetId);
            
            if (input.length) {
                input.select();
                input[0].setSelectionRange(0, 99999); // For mobile devices
                
                try {
                    document.execCommand('copy');
                    
                    // Visual feedback
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
                    console.error('Failed to copy:', err);
                    alert('{{ __('Failed to copy. Please select and copy manually.') }}');
                }
            }
        });

        // Auto-select on input click
        $('.copy-text-0, .copy-text-1, .copy-text-2, .copy-text-3').on('click', function() {
            $(this).select();
            this.setSelectionRange(0, 99999);
        });
    });
</script>
@endpush
