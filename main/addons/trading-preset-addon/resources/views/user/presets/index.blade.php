@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4>{{ __($title) }}</h4>
                <div>
                    <a href="{{ route('user.trading-presets.marketplace') }}" class="btn btn-sm btn-info">
                        <i class="fa fa-store"></i> {{ __('Marketplace') }}
                    </a>
                    <a href="{{ route('user.trading-presets.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> {{ __('Create Preset') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
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
                        <form action="{{ route('user.trading-presets.index') }}" method="get" class="mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               name="search" 
                                               placeholder="{{ __('Search presets...') }}" 
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

                        {{-- Presets List --}}
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Symbol') }}</th>
                                        <th>{{ __('Filters') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Default') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($presets as $preset)
                                        <tr>
                                            <td>
                                                <strong>{{ $preset->name }}</strong>
                                                @if($preset->tags)
                                                    <div class="mt-1">
                                                        @foreach(array_slice($preset->tags, 0, 2) as $tag)
                                                            <span class="badge badge-secondary badge-sm">{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">
                                                    {{ Str::limit($preset->description ?? '-', 40) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $preset->symbol ?? '-' }}
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    @if($preset->filter_strategy_id)
                                                        <span class="badge badge-info" title="Filter Strategy Active">
                                                            <i class="fa fa-filter"></i> Filter
                                                        </span>
                                                    @endif
                                                    @if($preset->ai_model_profile_id)
                                                        <span class="badge badge-success" title="AI Model Profile Active">
                                                            <i class="fa fa-robot"></i> AI
                                                        </span>
                                                    @endif
                                                    @if(!$preset->filter_strategy_id && !$preset->ai_model_profile_id)
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($preset->symbol)
                                                    <span class="badge badge-info">{{ $preset->symbol }}</span>
                                                @else
                                                    <span class="text-muted">{{ __('All') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" 
                                                           class="custom-control-input preset-status" 
                                                           id="status{{ $preset->id }}"
                                                           data-id="{{ $preset->id }}"
                                                           data-route="{{ route('user.trading-presets.toggle-status', $preset) }}"
                                                           {{ $preset->enabled ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="status{{ $preset->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                @if(auth()->user()->default_preset_id == $preset->id)
                                                    <span class="badge badge-success">{{ __('Default') }}</span>
                                                @else
                                                    <form action="{{ route('user.trading-presets.set-default', $preset) }}" 
                                                          method="POST" 
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            {{ __('Set as Default') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('user.trading-presets.edit', $preset) }}" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fa fa-pen"></i>
                                                    </a>
                                                    <form action="{{ route('user.trading-presets.destroy', $preset) }}" 
                                                          method="POST" 
                                                          class="d-inline delete-preset-form"
                                                          data-message="{{ __('Are you sure you want to delete this preset?') }}">
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
                                                {{ __('No presets found.') }}
                                                <a href="{{ route('user.trading-presets.create') }}">{{ __('Create your first preset') }}</a> 
                                                {{ __('or') }} 
                                                <a href="{{ route('user.trading-presets.marketplace') }}">{{ __('browse marketplace') }}</a>.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($presets->hasPages())
                            <div class="mt-3">
                                {{ $presets->links() }}
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
            $('.preset-status').on('change', function() {
                let route = $(this).data('route');
                $.ajax({
                    url: route,
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        @include('backend.layout.ajax_alert', [
                            'message' => 'Successfully changed preset status',
                            'message_error' => '',
                        ])
                    },
                    error: function() {
                        @include('backend.layout.ajax_alert', [
                            'message' => '',
                            'message_error' => 'Failed to change preset status',
                        ])
                    }
                })
            })
            
            // Delete preset confirmation
            $('.delete-preset-form').on('submit', function(e) {
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

