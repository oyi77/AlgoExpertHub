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
                        <h4 class="card-title">{{ $title ?? 'Widget Library' }}</h4>
                        <a href="{{ route('admin.page-builder.widgets.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> {{ __('Create Widget') }}
                        </a>
                    </div>
                    <div class="card-body">
                        @php
                            $widgets = $widgets ?? collect();
                            $categories = $categories ?? [];
                            $selectedCategory = $selectedCategory ?? 'all';
                        @endphp

                        @if(isset($error) && $error)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>{{ __('Error') }}:</strong> {{ $error }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if(count($categories) > 0)
                            <div class="mb-4">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.page-builder.widgets.index') }}" 
                                       class="btn btn-sm {{ $selectedCategory === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ __('All') }}
                                    </a>
                                    @foreach($categories as $category)
                                        <a href="{{ route('admin.page-builder.widgets.index', ['category' => $category]) }}" 
                                           class="btn btn-sm {{ $selectedCategory === $category ? 'btn-primary' : 'btn-outline-primary' }}">
                                            {{ ucfirst($category) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($widgets->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Widget') }}</th>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th class="text-end">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($widgets as $widget)
                                            <tr>
                                                <td>
                                                    <strong>{{ $widget->title ?? $widget->name ?? 'N/A' }}</strong>
                                                    @if(!empty($widget->description))
                                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($widget->description, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ ucfirst($widget->category ?? 'general') }}</span>
                                                </td>
                                                <td>
                                                    @if(!empty($widget->is_pro) && $widget->is_pro)
                                                        <span class="badge badge-warning">{{ __('Pro') }}</span>
                                                    @else
                                                        <span class="badge badge-success">{{ __('Free') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($widget->is_active) && $widget->is_active)
                                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('admin.page-builder.widgets.edit', $widget->id) }}" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="{{ __('Edit') }}">
                                                            <i data-feather="edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.page-builder.widgets.destroy', $widget->id) }}" 
                                                              method="POST" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this widget?') }}')">
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
                                    <i data-feather="package" style="width: 64px; height: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                    <h5 class="text-muted mb-2">{{ __('No Widgets Found') }}</h5>
                                    <p class="text-muted mb-4">{{ __('Get started by creating your first widget for the page builder.') }}</p>
                                    <a href="{{ route('admin.page-builder.widgets.create') }}" class="btn btn-primary">
                                        <i data-feather="plus"></i> {{ __('Create Your First Widget') }}
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

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Feather icons if available
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endpush
