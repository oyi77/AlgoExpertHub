@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.signal-sources.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Create Signal Source') }}
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
                            {{ session('info') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @php
                        $pendingMtproto = $sources->filter(function($src) {
                            return $src->type === 'telegram_mtproto' && $src->status === 'pending';
                        });
                    @endphp
                    @if ($pendingMtproto->count() > 0)
                        <div class="alert alert-warning m-3">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>{{ __('Authentication Required!') }}</strong><br>
                            {{ __('You have') }} {{ $pendingMtproto->count() }} {{ __('Telegram MTProto source(s) that require user authentication.') }}
                            {{ __('Click the "Authenticate" button to complete setup.') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Processed') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sources as $source)
                                    <tr>
                                        <td>{{ $source->id }}</td>
                                        <td>
                                            <strong>{{ $source->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $source->type)) }}</span>
                                        </td>
                                        <td>
                                            @if ($source->is_admin_owned)
                                                <span class="badge bg-primary">{{ __('Admin') }}</span>
                                            @elseif ($source->user)
                                                <span class="badge bg-secondary">{{ $source->user->username ?? 'User #' . $source->user_id }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($source->status === 'active')
                                                @if ($source->type === 'telegram_mtproto' && !($source->config['authenticated'] ?? false))
                                                    <span class="badge bg-info">{{ __('Pending - Auth Required') }}</span>
                                                @else
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                                @endif
                                            @elseif ($source->status === 'paused')
                                                <span class="badge bg-warning">{{ __('Paused') }}</span>
                                            @elseif ($source->status === 'error')
                                                <span class="badge bg-danger">{{ __('Error') }}</span>
                                            @elseif ($source->status === 'pending')
                                                @if ($source->type === 'telegram_mtproto')
                                                    <span class="badge bg-info">{{ __('Pending - Auth Required') }}</span>
                                                @else
                                                    <span class="badge bg-info">{{ __('Pending') }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if ($source->last_processed_at)
                                                {{ $source->last_processed_at->diffForHumans() }}
                                            @else
                                                <span class="text-muted">{{ __('Never') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-xs btn-info test-connection-btn" 
                                                        data-source-id="{{ $source->id }}"
                                                        title="{{ __('Test Connection') }}">
                                                    <i class="fa fa-plug"></i>
                                                </button>
                                                
                                                @if ($source->type === 'telegram_mtproto' && $source->status === 'pending')
                                                    <a href="{{ route('admin.signal-sources.authenticate', $source->id) }}" 
                                                       class="btn btn-xs btn-warning" 
                                                       title="{{ __('Authenticate') }}">
                                                        <i class="fa fa-key"></i>
                                                    </a>
                                                @endif
                                                
                                                <a href="{{ route('admin.signal-sources.edit', $source->id) }}" 
                                                   class="btn btn-xs btn-outline-secondary" 
                                                   title="{{ __('Edit') }}">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                
                                                <form action="{{ route('admin.signal-sources.destroy', $source->id) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ __('Are you sure you want to delete this source?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-xs btn-outline-danger" 
                                                            title="{{ __('Delete') }}">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">{{ __('No signal sources found.') }}</p>
                                            <a href="{{ route('admin.signal-sources.create') }}" class="btn btn-sm btn-primary mt-2">
                                                {{ __('Create First Source') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($sources->hasPages())
                    <div class="card-footer">
                        {{ $sources->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('script')
    <script>
        document.querySelectorAll('.test-connection-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const btnElement = this;
                const sourceId = btnElement.dataset.sourceId;
                const route = '{{ route("admin.signal-sources.test-connection", ":id") }}'.replace(':id', sourceId);
                
                const originalHtml = btnElement.innerHTML;
                btnElement.disabled = true;
                btnElement.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                btnElement.classList.remove('btn-info');
                btnElement.classList.add('btn-secondary');
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                
                // Use FormData to send CSRF token properly
                const formData = new FormData();
                formData.append('_token', csrfToken);
                
                fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => {
                    // Check if response is ok (status 200-299)
                    if (!response.ok) {
                        // Try to parse error response
                        return response.json().then(data => {
                            throw new Error(data.message || `Server error: ${response.status} ${response.statusText}`);
                        }).catch(() => {
                            throw new Error(`Server error: ${response.status} ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        btnElement.classList.remove('btn-secondary', 'btn-danger');
                        btnElement.classList.add('btn-success');
                        btnElement.innerHTML = '<i class="fa fa-check"></i>';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-success');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 2000);
                        
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show m-3';
                        alert.innerHTML = `
                            <strong>✓ ${data.message || 'Connection successful!'}</strong>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        const cardBody = document.querySelector('.card-body');
                        if (cardBody) {
                            cardBody.insertBefore(alert, cardBody.firstChild);
                        setTimeout(() => alert.remove(), 5000);
                        }
                    } else {
                        btnElement.classList.remove('btn-secondary', 'btn-success');
                        btnElement.classList.add('btn-danger');
                        btnElement.innerHTML = '<i class="fa fa-times"></i>';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-danger');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 2000);
                        
                        // Show error message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger alert-dismissible fade show m-3';
                        alert.innerHTML = `
                            <strong>✗ ${data.message || 'Connection failed'}</strong>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        const cardBody = document.querySelector('.card-body');
                        if (cardBody) {
                            cardBody.insertBefore(alert, cardBody.firstChild);
                        setTimeout(() => alert.remove(), 5000);
                        }
                    }
                })
                .catch(error => {
                    btnElement.classList.remove('btn-secondary');
                    btnElement.classList.add('btn-danger');
                    btnElement.innerHTML = '<i class="fa fa-times"></i>';
                    setTimeout(() => {
                        btnElement.classList.remove('btn-danger');
                        btnElement.classList.add('btn-info');
                        btnElement.innerHTML = originalHtml;
                    }, 2000);
                    
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show m-3';
                    alert.innerHTML = `
                        <strong>Error: ${error.message || 'Failed to test connection'}</strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    `;
                    const cardBody = document.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.insertBefore(alert, cardBody.firstChild);
                    setTimeout(() => alert.remove(), 5000);
                    }
                })
                .finally(() => {
                    btnElement.disabled = false;
                });
            });
        });
    </script>
    @endpush
@endsection

