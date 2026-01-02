@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <form action="" method="get" class="form-inline" id="filter-form">
                            <select name="provider_id" id="filter_provider_id" class="form-control form-control-sm mr-2">
                                <option value="">{{ __('All Providers') }}</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }}>
                                        {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="status" id="filter_status" class="form-control form-control-sm mr-2">
                                <option value="">{{ __('All Status') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>{{ __('Error') }}</option>
                            </select>
                            <button type="submit" id="filter_submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-filter"></i> {{ __('Filter') }}
                            </button>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.ai-connections.connections.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Add Connection') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Rate Limits') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Health') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($connections as $connection)
                                    <tr>
                                        <td><span class="badge badge-secondary">{{ $connection->priority }}</span></td>
                                        <td>{{ $connection->name }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $connection->provider->name }}</span>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $connection->rate_limit_per_minute ?? '∞' }}/min<br>
                                                {{ $connection->rate_limit_per_day ?? '∞' }}/day
                                            </small>
                                        </td>
                                        <td>
                                            @if ($connection->status === 'active')
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @elseif ($connection->status === 'inactive')
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Error') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($connection->error_count > 0)
                                                <span class="badge badge-warning">
                                                    {{ $connection->error_count }} {{ __('errors') }}
                                                </span>
                                            @else
                                                <span class="badge badge-success">{{ __('Healthy') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info test-connection"
                                                    data-id="{{ $connection->id }}"
                                                    data-url="{{ route('admin.ai-connections.connections.test', $connection->id) }}"
                                                    title="{{ __('Test Connection') }}">
                                                    <i class="fa fa-plug"></i>
                                                </button>
                                                <a href="{{ route('admin.ai-connections.connections.edit', $connection->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.ai-connections.connections.toggle-status', $connection->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $connection->status === 'active' ? 'warning' : 'success' }}">
                                                        <i class="fa fa-{{ $connection->status === 'active' ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.ai-connections.connections.destroy', $connection->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Connections Created Yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($connections->hasPages())
                    <div class="card-footer">
                        {{ $connections->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    'use strict';
    
    // Wait for jQuery to be available before initializing
    (function() {
        function initTestConnection() {
            // Check if jQuery is available
            if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                // Listen for jquery-loaded event or poll
                var handler = function() {
                    window.removeEventListener('jquery-loaded', handler);
                    initTestConnection();
                };
                window.addEventListener('jquery-loaded', handler, { once: true });
                
                // Poll as fallback
                setTimeout(function() {
                    if (typeof jQuery !== 'undefined') {
                        window.removeEventListener('jquery-loaded', handler);
                        initTestConnection();
                    }
                }, 100);
                return;
            }
            
            // jQuery is ready, initialize
            $(function() {
                // Helper function to show notification
                function showNotification(success, title, message) {
                    if (typeof notify !== 'undefined') {
                        try {
                            if (success) {
                                notify()
                                    .success()
                                    .title(title || '{{ __('Success') }}')
                                    .message(message)
                                    .send();
                            } else {
                                notify()
                                    .error()
                                    .title(title || '{{ __('Error') }}')
                                    .message(message)
                                    .send();
                            }
                        } catch(e) {
                            console.error('Error showing notification:', e);
                            alert(message);
                        }
                    } else {
                        // Fallback to alert if Laravel Notify not available
                        alert(message);
                    }
                }

                // Debug: Check if buttons exist
                console.log('Test connection buttons found:', $('.test-connection').length);
                
                // Bind click handler using event delegation for dynamic content
                $(document).on('click', '.test-connection', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    let btn = $(this);
                    let url = btn.data('url');
                    let connectionId = btn.data('id');
                    let originalHtml = btn.html();
                    
                    console.log('Test connection clicked:', { url: url, connectionId: connectionId, button: btn[0] });
                    
                    // Validate URL
                    if (!url) {
                        console.error('Test connection: URL not found');
                        showNotification(false, '{{ __('Error') }}', '{{ __('Invalid connection URL') }}');
                        return false;
                    }
                    
                    // Disable button and show loading state
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                    $.ajax({
                        url: url,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                        },
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Test connection response:', response);
                            
                            // Restore button
                            btn.prop('disabled', false).html(originalHtml);
                            
                            if (response && response.success) {
                                let message = response.message || '{{ __('Connection test successful!') }}';
                                if (response.response_time_ms) {
                                    message += ' ({{ __('Response time') }}: ' + response.response_time_ms + 'ms)';
                                }
                                showNotification(true, '{{ __('Connection Test') }}', message);
                                
                                // Optionally refresh the page after a short delay to show updated status
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                let errorMessage = (response && response.message) ? response.message : '{{ __('Connection test failed') }}';
                                showNotification(false, '{{ __('Connection Test Failed') }}', errorMessage);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Test connection AJAX error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText,
                                statusCode: xhr.status,
                                url: url
                            });
                            
                            // Restore button
                            btn.prop('disabled', false).html(originalHtml);
                            
                            let errorMessage = '{{ __('Failed to test connection') }}';
                            try {
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    let parsed = JSON.parse(xhr.responseText);
                                    if (parsed.message) {
                                        errorMessage = parsed.message;
                                    }
                                }
                            } catch(e) {
                                // If parsing fails, use default message
                            }
                            
                            if (xhr.status === 0) {
                                errorMessage = '{{ __('Network error. Please check your connection.') }}';
                            } else if (xhr.status === 401) {
                                errorMessage = '{{ __('Unauthorized. Please refresh the page and try again.') }}';
                            } else if (xhr.status === 403) {
                                errorMessage = '{{ __('Permission denied.') }}';
                            } else if (xhr.status === 404) {
                                errorMessage = '{{ __('Route not found. Please refresh the page.') }}';
                            } else if (xhr.status === 500) {
                                errorMessage = '{{ __('Server error. Please try again later.') }}';
                            }
                            
                            showNotification(false, '{{ __('Error') }}', errorMessage);
                        }
                    });
                    
                    return false;
                });
                
                console.log('Test connection handler initialized');
            });
        }
        
        // Start initialization
        initTestConnection();
    })();
</script>
@endpush

