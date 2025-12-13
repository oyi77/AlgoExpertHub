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
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($styles as $style)
                                        <tr>
                                            <td>{{ $style->name }}</td>
                                            <td><span class="badge badge-info">{{ strtoupper($style->type) }}</span></td>
                                            <td>
                                                @if($style->is_active)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.page-builder.global-styles.edit', $style->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i>
                                                </a>
                                                <form action="{{ route('admin.page-builder.global-styles.destroy', $style->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i data-feather="trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i data-feather="edit" style="width: 64px; height: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                                    <h5 class="text-muted mb-2">{{ __('No Global Styles Found') }}</h5>
                                                    <p class="text-muted mb-3">{{ __('Create global CSS, SCSS, or LESS styles that apply across all pages and themes.') }}</p>
                                                    <a href="{{ route('admin.page-builder.global-styles.create') }}" class="btn btn-primary">
                                                        <i data-feather="plus"></i> {{ __('Create Your First Global Style') }}
                                                    </a>
                                                    <div class="mt-3">
                                                        <small class="text-muted">
                                                            <i data-feather="info" style="width: 14px; height: 14px;"></i>
                                                            {{ __('Tip: Global styles are compiled and automatically included in all pages.') }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
