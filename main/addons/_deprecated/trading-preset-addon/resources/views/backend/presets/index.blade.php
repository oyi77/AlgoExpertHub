@extends('backend.layout.master')

@section('element')
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-12">
            <div class="row mb-3">
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Total</h5>
                            <h3>{{ $stats['total'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Default</h5>
                            <h3>{{ $stats['default'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Public</h5>
                            <h3>{{ $stats['public'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Private</h5>
                            <h3>{{ $stats['private'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Enabled</h5>
                            <h3>{{ $stats['enabled'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h5 class="mb-1">Disabled</h5>
                            <h3>{{ $stats['disabled'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presets Table -->
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header align-items-center justify-content-between">
                    <div class="card-header-left">
                        <form action="{{ route('admin.trading-presets.index') }}" method="get">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="{{ __('Search presets...') }}" 
                                               name="search" 
                                               value="{{ request('search') }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-sm btn-primary" type="submit">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <select name="visibility" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="">{{ __('All Visibility') }}</option>
                                        <option value="PRIVATE" {{ request('visibility') == 'PRIVATE' ? 'selected' : '' }}>{{ __('Private') }}</option>
                                        <option value="PUBLIC_MARKETPLACE" {{ request('visibility') == 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>{{ __('Public') }}</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select name="enabled" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="">{{ __('All Status') }}</option>
                                        <option value="1" {{ request('enabled') == '1' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                                        <option value="0" {{ request('enabled') == '0' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select name="is_default" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="">{{ __('All Types') }}</option>
                                        <option value="1" {{ request('is_default') == '1' ? 'selected' : '' }}>{{ __('Default Templates') }}</option>
                                        <option value="0" {{ request('is_default') == '0' ? 'selected' : '' }}>{{ __('User Presets') }}</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.trading-presets.create') }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus mr-1"></i> {{ __('Create Preset') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}.</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Symbol') }}</th>
                                    <th>{{ __('Visibility') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Creator') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($presets as $preset)
                                    <tr>
                                        <td>{{ $loop->iteration + ($presets->currentPage() - 1) * $presets->perPage() }}</td>
                                        <td>
                                            <strong>{{ $preset->name }}</strong>
                                            @if($preset->tags)
                                                <div class="mt-1">
                                                    @foreach(array_slice($preset->tags, 0, 3) as $tag)
                                                        <span class="badge badge-secondary badge-sm">{{ $tag }}</span>
                                                    @endforeach
                                                    @if(count($preset->tags) > 3)
                                                        <span class="badge badge-secondary badge-sm">+{{ count($preset->tags) - 3 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ Str::limit($preset->description ?? '-', 50) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($preset->symbol)
                                                <span class="badge badge-info">{{ $preset->symbol }}</span>
                                            @else
                                                <span class="text-muted">{{ __('All') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($preset->visibility === 'PUBLIC_MARKETPLACE')
                                                <span class="badge badge-primary">{{ __('Public') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Private') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($preset->is_default_template)
                                                <span class="badge badge-info">{{ __('Default Template') }}</span>
                                            @else
                                                <span class="badge badge-success">{{ __('User Preset') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       name="enabled" 
                                                       {{ $preset->enabled ? 'checked' : '' }}
                                                       class="custom-control-input preset-status" 
                                                       id="status{{ $preset->id }}"
                                                       data-id="{{ $preset->id }}"
                                                       data-route="{{ route('admin.trading-presets.toggle-status', $preset) }}">
                                                <label class="custom-control-label" for="status{{ $preset->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            @if($preset->creator)
                                                {{ $preset->creator->username ?? $preset->creator->email }}
                                            @else
                                                <span class="text-muted">{{ __('System') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.trading-presets.show', $preset) }}" 
                                                   class="btn btn-outline-info btn-sm" 
                                                   title="{{ __('View') }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.trading-presets.edit', $preset) }}" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="{{ __('Edit') }}">
                                                    <i class="fa fa-pen"></i>
                                                </a>
                                                    <form action="{{ route('admin.trading-presets.clone', $preset) }}" 
                                                      method="POST" 
                                                      class="d-inline clone-preset-form"
                                                      data-message="{{ __('Are you sure you want to clone this preset?') }}">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-outline-success btn-sm" 
                                                            title="{{ __('Clone') }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </form>
                                                @if(!$preset->is_default_template)
                                                    <form action="{{ route('admin.trading-presets.destroy', $preset) }}" 
                                                          method="POST" 
                                                          class="d-inline delete-preset-form"
                                                          data-message="{{ __('Are you sure you want to delete this preset?') }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger btn-sm" 
                                                                title="{{ __('Delete') }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Presets Found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($presets->hasPages())
                    <div class="card-footer">
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
            
            $('.clone-preset-form').on('submit', function(e) {
                e.preventDefault()
                const form = $(this)
                const message = form.data('message')
                
                Swal.fire({
                    title: '{{ __('Confirmation') }}',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Clone') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit()
                    }
                })
            })
            
            $('.preset-status').on('change', function() {
    <script>
        'use strict'

        $(function() {
            $('.preset-status').on('change', function() {
                let id = $(this).data('id');
                let route = $(this).data('route');

                $.ajax({
                    url: route,
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        @include('backend.layout.ajax_alert', [
                            'message' => 'Successfully changed preset status',
                            'message_error' => '',
                        ])
                    },
                    error: function(xhr) {
                        @include('backend.layout.ajax_alert', [
                            'message' => '',
                            'message_error' => 'Failed to change preset status',
                        ])
                        // Revert checkbox
                        $('.preset-status[data-id="' + id + '"]').prop('checked', !$('.preset-status[data-id="' + id + '"]').prop('checked'));
                    }
                })
            })
        })
    </script>
@endpush

