@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">{{ $title }}</h4>
                        <a href="{{ route('admin.page-builder.layouts.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> {{ __('Create Layout') }}
                        </a>
                    </div>
                    <div class="card-body">
                        @php
                            $layouts = $layouts ?? collect();
                        @endphp

                        @if(isset($error) && $error)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>{{ __('Error') }}:</strong> {{ $error }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if($layouts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Default') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th class="text-end">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($layouts as $layout)
                                            <tr>
                                                <td>
                                                    <strong>{{ $layout->title ?? $layout->name ?? 'N/A' }}</strong>
                                                    @if(!empty($layout->description))
                                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($layout->description, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ ucfirst($layout->type ?? 'standard') }}</span>
                                                </td>
                                                <td>
                                                    @if(!empty($layout->is_default) && $layout->is_default)
                                                        <span class="badge badge-success">{{ __('Yes') }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ __('No') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($layout->is_active) && $layout->is_active)
                                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('admin.page-builder.layouts.edit', $layout->id) }}" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="{{ __('Edit') }}">
                                                            <i data-feather="edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.page-builder.layouts.destroy', $layout->id) }}" 
                                                              method="POST" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this layout?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-danger" 
                                                                    title="{{ __('Delete') }}">
                                                                <i data-feather="trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i data-feather="layout" style="width: 64px; height: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                    <h5 class="text-muted mb-2">{{ __('No Layouts Found') }}</h5>
                                    <p class="text-muted mb-4">{{ __('Create custom page layouts (header, footer, sidebar, content areas) for your pages.') }}</p>
                                    <a href="{{ route('admin.page-builder.layouts.create') }}" class="btn btn-primary">
                                        <i data-feather="plus"></i> {{ __('Create Your First Layout') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Feather icons if available
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endpush
