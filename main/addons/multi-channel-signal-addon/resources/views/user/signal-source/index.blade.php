@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">{{ __('My Signal Sources') }}</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('user.signal-sources.create', ['type' => 'telegram']) }}" class="btn sp_theme_btn">
                    <i class="lab la-telegram-plane me-1"></i> {{ __('Add Telegram') }}
                </a>
                <a href="{{ route('user.signal-sources.create', ['type' => 'telegram_mtproto']) }}" class="btn btn-outline-primary">
                    <i class="las la-mobile me-1"></i> {{ __('Add Telegram MTProto') }}
                </a>
                <a href="{{ route('user.signal-sources.create', ['type' => 'api']) }}" class="btn btn-outline-secondary">
                    <i class="las la-code-branch me-1"></i> {{ __('Add API') }}
                </a>
                <a href="{{ route('user.signal-sources.create', ['type' => 'web_scrape']) }}" class="btn btn-outline-info">
                    <i class="las la-spider me-1"></i> {{ __('Add Web Scrape') }}
                </a>
                <a href="{{ route('user.signal-sources.create', ['type' => 'rss']) }}" class="btn btn-outline-success">
                    <i class="las la-rss me-1"></i> {{ __('Add RSS') }}
                </a>
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1">{{ __('Total') }}</h5>
                        <span class="fw-semibold fs-4">{{ $stats['total'] }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-success">{{ __('Active') }}</h5>
                        <span class="fw-semibold fs-4 text-success">{{ $stats['active'] }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-warning">{{ __('Paused') }}</h5>
                        <span class="fw-semibold fs-4 text-warning">{{ $stats['paused'] }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-danger">{{ __('Error') }}</h5>
                        <span class="fw-semibold fs-4 text-danger">{{ $stats['error'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <form class="row g-2 align-items-end" method="get" action="{{ route('user.signal-sources.index') }}">
                        <div class="col-auto">
                            <label class="form-label d-block">{{ __('Type') }}</label>
                            <select name="type" class="form-select">
                                <option value="">{{ __('All Types') }}</option>
                                @foreach (['telegram' => 'Telegram', 'telegram_mtproto' => 'Telegram MTProto', 'api' => 'API', 'web_scrape' => 'Web Scrape', 'rss' => 'RSS'] as $value => $label)
                                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                                        {{ __($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label d-block">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('All Statuses') }}</option>
                                @foreach (['active' => 'Active', 'paused' => 'Paused', 'pending' => 'Pending', 'error' => 'Error'] as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                        {{ __($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="las la-filter"></i> {{ __('Filter') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Last Processed') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sources as $source)
                                <tr>
                                    <td>
                                        <strong>{{ $source->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $source->type)) }}</span>
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
                                                <i class="las la-plug"></i>
                                            </button>
                                            
                                            @if ($source->type === 'telegram_mtproto' && $source->status === 'pending')
                                                <a href="{{ route('user.signal-sources.authenticate', $source->id) }}" 
                                                   class="btn btn-xs btn-warning" 
                                                   title="{{ __('Authenticate') }}">
                                                    <i class="las la-key"></i>
                                                </a>
                                            @endif
                                            
                                            @if ($source->status === 'active')
                                                <form action="{{ route('user.signal-sources.status', $source->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="paused">
                                                    <button type="submit" class="btn btn-xs btn-warning" title="{{ __('Pause') }}">
                                                        <i class="las la-pause"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('user.signal-sources.status', $source->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-xs btn-success" title="{{ __('Resume') }}">
                                                        <i class="las la-play"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <form action="{{ route('user.signal-sources.destroy', $source->id) }}" 
                                                  method="POST" 
                                                  class="d-inline delete-source-form"
                                                  data-message="{{ __('Are you sure?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" title="{{ __('Delete') }}">
                                                    <i class="las la-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="las la-inbox la-2x text-muted mb-2"></i>
                                        <p class="mb-0 text-muted">{{ __('No signal sources found.') }}</p>
                                        <a href="{{ route('user.signal-sources.create') }}" class="btn btn-sm btn-primary mt-2">
                                            {{ __('Create First Source') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($sources->hasPages())
                    <div class="mt-3">
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
                const route = '{{ route("user.signal-sources.test-connection", ":id") }}'.replace(':id', sourceId);
                
                const originalHtml = btnElement.innerHTML;
                btnElement.disabled = true;
                btnElement.innerHTML = '<i class="las la-spinner la-spin"></i>';
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
                    if (!response.ok) {
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
                        btnElement.innerHTML = '<i class="las la-check"></i>';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-success');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 2000);
                        
                        // Show success toast/alert
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('Success') }}',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert('✓ ' + data.message);
                        }
                    } else {
                        btnElement.classList.remove('btn-secondary', 'btn-success');
                        btnElement.classList.add('btn-danger');
                        btnElement.innerHTML = '<i class="las la-times"></i>';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-danger');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 2000);
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('Error') }}',
                                text: data.message
                            });
                        } else {
                            alert('✗ ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    btnElement.classList.remove('btn-secondary');
                    btnElement.classList.add('btn-danger');
                    btnElement.innerHTML = '<i class="las la-times"></i>';
                    setTimeout(() => {
                        btnElement.classList.remove('btn-danger');
                        btnElement.classList.add('btn-info');
                        btnElement.innerHTML = originalHtml;
                    }, 2000);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: error.message
                        });
                    } else {
                        alert('Error: ' + error.message);
                    }
                })
                .finally(() => {
                    btnElement.disabled = false;
                });
            });
        });
    </script>
    
    <script>
        $(function() {
            'use strict'
            
            $('.delete-source-form').on('submit', function(e) {
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
        })
    </script>
    @endpush
@endsection

