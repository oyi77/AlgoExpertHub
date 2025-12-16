@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">{{ $title }}</h4>
                        <div>
                            <a href="{{ route('admin.page-builder.global-styles.css.compiled') }}" target="_blank" class="btn btn-info">
                                <i data-feather="download"></i> {{ __('View Compiled CSS') }}
                            </a>
                            <a href="{{ route('admin.page-builder.global-styles.create') }}" class="btn btn-primary">
                                <i data-feather="plus"></i> {{ __('Create Style') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                            $styles = $styles ?? collect();
                        @endphp

                        @if(isset($error) && $error)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>{{ __('Error') }}:</strong> {{ $error }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if($styles->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th class="text-end">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($styles as $style)
                                            <tr>
                                                <td>
                                                    <strong>{{ $style->name ?? 'N/A' }}</strong>
                                                    @if(!empty($style->description))
                                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($style->description, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ strtoupper($style->type ?? 'css') }}</span>
                                                </td>
                                                <td>
                                                    @if(!empty($style->is_active) && $style->is_active)
                                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('admin.page-builder.global-styles.edit', $style->id) }}" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="{{ __('Edit') }}">
                                                            <i data-feather="edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.page-builder.global-styles.destroy', $style->id) }}" 
                                                              method="POST" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this global style?') }}')">
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
                                    <i data-feather="edit" style="width: 64px; height: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                    <h5 class="text-muted mb-2">{{ __('No Global Styles Found') }}</h5>
                                    <p class="text-muted mb-4">{{ __('Create global CSS, SCSS, or LESS styles that apply across all pages and themes.') }}</p>
                                    <a href="{{ route('admin.page-builder.global-styles.create') }}" class="btn btn-primary">
                                        <i data-feather="plus"></i> {{ __('Create Your First Global Style') }}
                                    </a>
                                    <div class="mt-4">
                                        <small class="text-muted">
                                            <i data-feather="info" style="width: 14px; height: 14px;"></i>
                                            {{ __('Tip: Global styles are compiled and automatically included in all pages.') }}
                                        </small>
                                    </div>
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
