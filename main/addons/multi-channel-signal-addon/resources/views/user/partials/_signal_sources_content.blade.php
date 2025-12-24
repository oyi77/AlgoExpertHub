<div class="row gy-4">
    <!-- Instructions Section -->
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-start">
            <i class="las la-info-circle la-2x me-3 mt-1"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2">{{ __('How to Add Signal Sources') }}</h6>
                <p class="mb-2">{{ __('Signal sources are where your trading signals come from. Choose the type that matches your needs:') }}</p>
                <ul class="mb-0 small">
                    <li><strong>{{ __('Telegram Bot') }}</strong> - {{ __('Connect a Telegram to receive signals from channels/groups.') }}</li>
                    <li><strong>{{ __('API/Webhook') }}</strong> - {{ __('Receive signals via HTTP POST requests (Coming Soon)') }}</li>
                    <li><strong>{{ __('Web Scrape') }}</strong> - {{ __('Automatically scrape trading signals from websites (Coming Soon)') }}</li>
                    <li><strong>{{ __('RSS Feed') }}</strong> - {{ __('Receive signals from RSS feeds (Coming Soon)') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">{{ __('My Signal Sources') }}</h4>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group">
                <button type="button" class="btn btn-success btn-lg dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #10b981 !important;">
                    <i class="las la-plus me-1"></i> {{ __('Add Signal Source') }}
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('user.signal-sources.create', ['type' => 'telegram_mtproto']) }}">
                            <i class="lab la-telegram-plane me-2"></i> {{ __('Telegram') }}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user.signal-sources.create', ['type' => 'api']) }}">
                            <i class="las la-code-branch me-2"></i> {{ __('API / Webhook') }}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user.signal-sources.create', ['type' => 'web_scrape']) }}">
                            <i class="las la-spider me-2"></i> {{ __('Web Scrape') }}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user.signal-sources.create', ['type' => 'rss']) }}">
                            <i class="las la-rss me-2"></i> {{ __('RSS Feed') }}
                        </a>
                    </li>
                </ul>
            </div>
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
                                <td colspan="5" class="text-center py-5">
                                    <i class="las la-inbox la-3x text-muted mb-3"></i>
                                    <h5 class="mb-2">{{ __('No Signal Sources Yet') }}</h5>
                                    <p class="text-muted mb-4">{{ __('Get started by adding your first signal source. Choose from Telegram Bot or Telegram MTProto above.') }}</p>
                                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                                        <a href="{{ route('user.signal-sources.create', ['type' => 'telegram']) }}" class="btn btn-primary">
                                            <i class="lab la-telegram-plane me-1"></i> {{ __('Add Telegram Bot') }}
                                        </a>
                                        <a href="{{ route('user.signal-sources.create', ['type' => 'telegram_mtproto']) }}" class="btn btn-outline-primary">
                                            <i class="las la-mobile me-1"></i> {{ __('Add Telegram MTProto') }}
                                        </a>
                                    </div>
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

@push('scripts')
<script>
    // Handle "Coming Soon" buttons
    document.querySelectorAll('.coming-soon-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const feature = this.dataset.feature || 'This feature';
            const message = feature + ' {{ __('feature is coming soon. We\'re working hard to bring you this functionality.') }}';
            
            // Use toastr if available (preferred for simple notifications)
            if (typeof toastr !== 'undefined') {
                toastr.info(message, '{{ __('Coming Soon') }}', {
                    positionClass: "toast-top-right",
                    timeOut: 5000,
                    extendedTimeOut: 2000
                });
            } 
            // Fallback to SweetAlert for modal-style notification
            else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: '{{ __('Coming Soon') }}',
                    html: `<p>${message}</p><p class="small text-muted mt-2">{{ __('Stay tuned for updates!') }}</p>`,
                    confirmButtonText: '{{ __('Got it') }}',
                    confirmButtonColor: '#3085d6'
                });
            } 
            // Final fallback to alert
            else {
                alert('{{ __('Coming Soon') }}: ' + message);
            }
        });
    });

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

