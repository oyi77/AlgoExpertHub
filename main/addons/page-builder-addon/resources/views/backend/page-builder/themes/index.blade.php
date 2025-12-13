@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Theme Builder') }}</h5>
                        <div>
                            <a href="{{ route('admin.manage.theme') }}" class="btn btn-info btn-sm">
                                <i data-feather="settings"></i> {{ __('Manage Themes') }}
                            </a>
                            <a href="{{ route('admin.page-builder.themes.create') }}" class="btn btn-primary btn-sm">
                                <i data-feather="plus"></i> {{ __('Create Theme') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for Frontend and Backend Themes -->
<ul class="nav nav-tabs" id="themeTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="frontend-tab" data-toggle="tab" href="#frontend" role="tab" aria-controls="frontend" aria-selected="true">
            <i data-feather="monitor"></i> Frontend Themes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="backend-tab" data-toggle="tab" href="#backend" role="tab" aria-controls="backend" aria-selected="false">
            <i data-feather="layout"></i> Backend Themes
        </a>
    </li>
</ul>

<div class="tab-content" id="themeTabContent">
    <!-- Frontend Themes Tab -->
    <div class="tab-pane fade show active" id="frontend" role="tabpanel" aria-labelledby="frontend-tab">
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Theme') }}</th>
                                        <th>{{ __('Version') }}</th>
                                        <th>{{ __('Author') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($themes ?? [] as $theme)
                                    <tr>
                                        <td>
                                            <h5 class="mb-1">{{ $theme['display_name'] }}</h5>
                                            @if($theme['description'])
                                                <small class="text-muted d-block">{{ $theme['description'] }}</small>
                                            @endif
                                            @if($theme['is_builtin'] ?? false)
                                                <span class="badge badge-info badge-sm">{{ __('Built-in') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['version'])
                                                <span class="badge badge-secondary">{{ $theme['version'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['author'])
                                                <small>{{ $theme['author'] }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['is_active'] ?? false)
                                                <span class="badge badge-success">{{ __('Activated') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/" target="_blank" class="btn btn-sm btn-info" title="{{ __('Preview') }}">
                                                    <i data-feather="eye"></i>
                                                </a>
                                                <a href="{{ route('admin.page-builder.themes.edit', ['theme' => $theme['name']]) }}" 
                                                   class="btn btn-sm btn-success" title="{{ __('Edit Theme Template') }}">
                                                    <i data-feather="edit-3"></i>
                                                </a>
                                                @if(!($theme['is_active'] ?? false))
                                                    <a href="{{ route('admin.manage.theme.update', $theme['name']) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       onclick="event.preventDefault(); document.getElementById('activate-form-{{ $theme['name'] }}').submit();">
                                                        <i data-feather="check"></i> {{ __('Activate') }}
                                                    </a>
                                                    <form id="activate-form-{{ $theme['name'] }}" action="{{ route('admin.manage.theme.update', $theme['name']) }}" method="POST" style="display: none;">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No themes found.') }}
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

    <!-- Backend Themes Tab -->
    <div class="tab-pane fade" id="backend" role="tabpanel" aria-labelledby="backend-tab">
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Theme') }}</th>
                                        <th>{{ __('Version') }}</th>
                                        <th>{{ __('Author') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($backendThemes ?? [] as $theme)
                                    <tr>
                                        <td>
                                            <h5 class="mb-1">{{ $theme['display_name'] }}</h5>
                                            @if($theme['description'])
                                                <small class="text-muted d-block">{{ $theme['description'] }}</small>
                                            @endif
                                            @if($theme['is_builtin'] ?? false)
                                                <span class="badge badge-info badge-sm">{{ __('Built-in') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['version'])
                                                <span class="badge badge-secondary">{{ $theme['version'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['author'])
                                                <small>{{ $theme['author'] }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($theme['is_active'] ?? false)
                                                <span class="badge badge-success">{{ __('Activated') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if(!($theme['is_active'] ?? false))
                                                    <a href="{{ route('admin.manage.backend.theme.update', $theme['name']) }}" 
                                                       class="btn btn-sm btn-primary"
                                                       onclick="event.preventDefault(); document.getElementById('activate-backend-form-{{ $theme['name'] }}').submit();">
                                                        <i data-feather="check"></i> {{ __('Activate') }}
                                                    </a>
                                                    <form id="activate-backend-form-{{ $theme['name'] }}" action="{{ route('admin.manage.backend.theme.update', $theme['name']) }}" method="POST" style="display: none;">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No backend themes found.') }}
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
</div>
@endsection

@push('script')
<style>
    .nav-tabs {
        border-bottom: 2px solid #ddd;
        margin-bottom: 0;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: 3px solid #007bff;
        color: #007bff;
        font-weight: 600;
    }
</style>
<script>
    $(function() {
        // Reinitialize feather icons after tab switch
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    });
</script>
@endpush
