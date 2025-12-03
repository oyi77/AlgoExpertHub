@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Filter Strategies' }}
@endsection

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Filter Strategies</h4>
                <a href="{{ route('admin.filter-strategies.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Create Strategy
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>Total</h5>
                                    <h3>{{ $stats['total'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Enabled</h5>
                                    <h3>{{ $stats['enabled'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>Public</h5>
                                    <h3>{{ $stats['public'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Owner</th>
                                <th>Visibility</th>
                                <th>Linked Presets</th>
                                <th>Status</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($strategies as $strategy)
                                <tr>
                                    <td>{{ $strategy->id }}</td>
                                    <td>{{ $strategy->name }}</td>
                                    <td>{{ Str::limit($strategy->description, 50) }}</td>
                                    <td>{{ $strategy->owner->username ?? 'System' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                            {{ $strategy->visibility }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $strategy->trading_presets_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $strategy->enabled ? 'success' : 'danger' }}">
                                            {{ $strategy->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>{{ $strategy->updated_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.filter-strategies.show', $strategy->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.filter-strategies.edit', $strategy->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.filter-strategies.destroy', $strategy->id) }}" method="POST" class="d-inline delete-strategy-form" data-message="Are you sure you want to delete this filter strategy?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No filter strategies found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $strategies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict'
        
        $('.delete-strategy-form').on('submit', function(e) {
            e.preventDefault()
            const form = $(this)
            const message = form.data('message') || 'Are you sure?'
            
            if (typeof Swal !== 'undefined') {
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
            } else {
                if (confirm(message)) {
                    form.off('submit').submit()
                }
            }
        })
    })
</script>
@endpush

