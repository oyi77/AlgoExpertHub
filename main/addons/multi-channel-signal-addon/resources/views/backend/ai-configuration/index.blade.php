@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('AI Configuration') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.ai-connections.providers.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Add AI Provider') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Model') }}</th>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($configurations as $config)
                                    <tr>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($config->provider) }}</span>
                                        </td>
                                        <td>{{ $config->name }}</td>
                                        <td>{{ $config->model ?? 'N/A' }}</td>
                                        <td>{{ $config->priority }}</td>
                                        <td>
                                            @if($config->enabled)
                                                <span class="badge bg-success">{{ __('Enabled') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Disabled') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-info test-connection-btn" 
                                                        data-id="{{ $config->id }}" 
                                                        data-name="{{ $config->name }}">
                                                    <i class="fa fa-plug"></i> {{ __('Test') }}
                                                </button>
                                                <a href="{{ route('admin.ai-configuration.edit', $config->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> {{ __('Edit') }}
                                                </a>
                                                <form action="{{ route('admin.ai-configuration.destroy', $config->id) }}" 
                                                      method="POST" 
                                                      class="d-inline delete-config-form"
                                                      data-message="{{ __('Are you sure you want to delete this configuration?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <p class="text-muted mb-0">{{ __('No AI configurations found.') }}</p>
                                            <a href="{{ route('admin.ai-connections.providers.create') }}" class="btn btn-sm btn-primary mt-2">
                                                <i class="fa fa-plus"></i> {{ __('Create First Configuration') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.test-connection-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const originalText = this.innerHTML;
                    
                    this.disabled = true;
                    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Testing...') }}';
                    
                    fetch(`{{ url('admin/ai-configuration') }}/${id}/test-connection`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ __('Connection Successful!') }}',
                                    text: '{{ __('The AI configuration is working correctly.') }}'
                                });
                            } else {
                                alert('{{ __('Connection successful!') }}');
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('Connection Failed') }}',
                                    text: data.message || '{{ __('Unknown error occurred while testing connection.') }}'
                                });
                            } else {
                                alert('{{ __('Connection failed:') }} ' + (data.message || '{{ __('Unknown error') }}'));
                            }
                        }
                    })
                    .catch(error => {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('Error') }}',
                                text: '{{ __('Error testing connection:') }} ' + error.message
                            });
                        } else {
                            alert('{{ __('Error testing connection:') }} ' + error.message);
                        }
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
                });
            });
            
            // Delete configuration confirmation
            $('.delete-config-form').on('submit', function(e) {
                e.preventDefault()
                const form = $(this)
                const message = form.data('message')
                
                Swal.fire({
                    title: '{{ __('Confirmation') }}',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Delete') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit()
                    }
                })
            })
        });
    </script>
@endsection

