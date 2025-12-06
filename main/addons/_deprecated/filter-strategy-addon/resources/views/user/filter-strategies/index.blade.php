@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4>{{ __($title) }}</h4>
                <div>
                    <a href="{{ route('user.filter-strategies.marketplace') }}" class="btn btn-sm btn-info">
                        <i class="fa fa-store"></i> {{ __('Marketplace') }}
                    </a>
                    <a href="{{ route('user.filter-strategies.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> {{ __('Create Strategy') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- Statistics --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1">{{ __('Total') }}</h5>
                        <span class="fw-semibold fs-4">{{ $stats['total'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-success">{{ __('Enabled') }}</h5>
                        <span class="fw-semibold fs-4 text-success">{{ $stats['enabled'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-danger">{{ __('Disabled') }}</h5>
                        <span class="fw-semibold fs-4 text-danger">{{ $stats['disabled'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Search and Filter --}}
            <form action="{{ route('user.filter-strategies.index') }}" method="get" class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="{{ __('Search strategies...') }}" 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="enabled" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="1" {{ request('enabled') == '1' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                            <option value="0" {{ request('enabled') == '0' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                        </select>
                    </div>
                </div>
            </form>

            {{-- Strategies List --}}
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Visibility') }}</th>
                            <th>{{ __('Linked Presets') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated At') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($strategies as $strategy)
                            <tr>
                                <td>
                                    <strong>{{ $strategy->name }}</strong>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        {{ Str::limit($strategy->description ?? '-', 50) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                        {{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? __('Public') : __('Private') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $strategy->trading_presets_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input strategy-status" 
                                               id="status{{ $strategy->id }}"
                                               data-id="{{ $strategy->id }}"
                                               {{ $strategy->enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="status{{ $strategy->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $strategy->updated_at->format('Y-m-d H:i') }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('user.filter-strategies.edit', $strategy->id) }}" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                        <form action="{{ route('user.filter-strategies.destroy', $strategy->id) }}" 
                                              method="POST" 
                                              class="d-inline delete-strategy-form"
                                              data-message="{{ __('Are you sure you want to delete this strategy?') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="100%">
                                    {{ __('No filter strategies found.') }}
                                    <a href="{{ route('user.filter-strategies.create') }}">{{ __('Create your first strategy') }}</a> 
                                    {{ __('or') }} 
                                    <a href="{{ route('user.filter-strategies.marketplace') }}">{{ __('browse marketplace') }}</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($strategies->hasPages())
                <div class="mt-3">
                    {{ $strategies->links() }}
                </div>
            @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict'
        $(function() {
            // Delete strategy confirmation
            $('.delete-strategy-form').on('submit', function(e) {
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
