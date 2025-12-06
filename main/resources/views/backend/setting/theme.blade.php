@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Theme Management') }}</h5>
                    <div>
                        <a href="{{ route('admin.manage.theme.download.template') }}" class="btn btn-info btn-sm">
                            <i data-feather="download"></i> {{ __('Download Theme Template') }}
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#uploadThemeModal">
                            <i data-feather="upload"></i> {{ __('Upload Theme ZIP') }}
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#deactivateAllThemesModal">
                            <i data-feather="x-circle"></i> {{ __('Deactivate All Frontend Themes') }}
                        </button>
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
                            <table class="table" id="frontendThemesTable">
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
                                    @foreach($themes as $theme)
                                    <tr>
                                        <td>
                                            <h5 class="mb-1">{{ $theme['display_name'] }}</h5>
                                            @if($theme['description'])
                                                <small class="text-muted d-block">{{ $theme['description'] }}</small>
                                            @endif
                                            @if($theme['is_builtin'])
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
                                            @if($theme['is_active'])
                                                <span class="badge badge-success">{{ __('Activated') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/" target="_blank" class="btn btn-sm btn-info" title="{{ __('Preview') }}">
                                                    <i data-feather="eye"></i> {{ __('Preview') }}
                                                </a>
                                                @php
                                                    $pageBuilderEnabled = \App\Support\AddonRegistry::active('page-builder-addon') 
                                                        && \App\Support\AddonRegistry::moduleEnabled('page-builder-addon', 'admin_ui');
                                                @endphp
                                                @if ($pageBuilderEnabled)
                                                    <a href="{{ route('admin.page-builder.themes.edit', ['theme' => $theme['name']]) }}" 
                                                       class="btn btn-sm btn-success" title="{{ __('Edit Theme Template') }}">
                                                        <i data-feather="edit-3"></i> {{ __('Edit Template') }}
                                                    </a>
                                                @endif
                                                @if(!$theme['is_active'])
                                                    <a data-route="{{ route('admin.manage.theme.update', $theme['name']) }}" 
                                                       data-theme="{{ $theme['name'] }}"
                                                       data-color="#9c0ac" 
                                                       class="btn btn-sm btn-primary active-btn">
                                                        <i data-feather="check"></i> {{ __('Activate') }}
                                                    </a>
                                                @endif
                                                @if(!$theme['is_builtin'] && !$theme['is_active'])
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger delete-theme-btn" 
                                                            data-theme="{{ $theme['name'] }}"
                                                            data-display-name="{{ $theme['display_name'] }}">
                                                        <i data-feather="trash-2"></i> {{ __('Delete') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    
                                    @if($themes->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No themes found. Upload a theme to get started.') }}
                                        </td>
                                    </tr>
                                    @endif
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
                            <table class="table" id="backendThemesTable">
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
                                    @foreach($backendThemes as $theme)
                                    <tr>
                                        <td>
                                            <h5 class="mb-1">{{ $theme['display_name'] }}</h5>
                                            @if($theme['description'])
                                                <small class="text-muted d-block">{{ $theme['description'] }}</small>
                                            @endif
                                            @if($theme['is_builtin'])
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
                                            @if($theme['is_active'])
                                                <span class="badge badge-success">{{ __('Activated') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if(!$theme['is_active'])
                                                    <a data-route="{{ route('admin.manage.backend.theme.update', $theme['name']) }}" 
                                                       data-theme="{{ $theme['name'] }}"
                                                       class="btn btn-sm btn-primary active-backend-btn">
                                                        <i data-feather="check"></i> {{ __('Activate') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    
                                    @if($backendThemes->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No backend themes found.') }}
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activate Frontend Theme Modal -->
<div class="modal fade" id="activeTheme" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="" method="post">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Activate Frontend Theme') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <input type="hidden" name="name" id="activeThemeName">
                        <input type="hidden" name="color" id="activeThemeColor">
                        <p>{{ __('Are you sure you want to activate this theme for the frontend?') }}</p>
                        <p class="mb-0"><strong id="activeThemeDisplayName"></strong></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Activate') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Activate Backend Theme Modal -->
<div class="modal fade" id="activeBackendTheme" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="" method="post">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Activate Backend Theme') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <input type="hidden" name="name" id="activeBackendThemeName">
                        <p>{{ __('Are you sure you want to activate this theme for the admin panel?') }}</p>
                        <p class="mb-0"><strong id="activeBackendThemeDisplayName"></strong></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Activate') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Deactivate All Frontend Themes Modal -->
<div class="modal fade" id="deactivateAllThemesModal" tabindex="-1" role="dialog" aria-labelledby="deactivateAllThemesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.manage.theme.deactivate.all') }}" method="post">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Deactivate All Frontend Themes') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <p>{{ __('Are you sure you want to deactivate all frontend themes?') }}</p>
                        <p class="text-warning mb-0">
                            <i data-feather="alert-triangle"></i> 
                            {{ __('This will set the frontend theme to null. The system will fallback to the default theme.') }}
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('Deactivate All') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Upload Theme Modal -->
<div class="modal fade" id="uploadThemeModal" tabindex="-1" role="dialog" aria-labelledby="uploadThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.manage.theme.upload') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadThemeModalLabel">{{ __('Upload Theme ZIP') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="theme_package">{{ __('Theme ZIP File') }}</label>
                        <input type="file" class="form-control-file" id="theme_package" name="theme_package" accept=".zip" required>
                        <small class="form-text text-muted">
                            {{ __('Upload a ZIP file containing your theme. Maximum file size: 10MB') }}
                        </small>
                        <div class="mt-2">
                            <strong>{{ __('Theme ZIP Structure:') }}</strong>
                            <ul class="mb-0">
                                <li><code>assets/</code> - CSS, JS, images, fonts</li>
                                <li><code>views/</code> - Blade template files</li>
                                <li>{{ __('OR') }}</li>
                                <li>{{ __('Theme folder with assets and views subdirectories') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Upload Theme') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Theme Modal -->
<div class="modal fade" id="deleteThemeModal" tabindex="-1" role="dialog" aria-labelledby="deleteThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="" method="post" id="deleteThemeForm">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteThemeModalLabel">{{ __('Delete Theme') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to delete the theme') }} <strong id="deleteThemeName"></strong>?</p>
                    <p class="text-danger"><small>{{ __('This action cannot be undone. All theme files will be permanently deleted.') }}</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Delete Theme') }}</button>
                </div>
            </div>
        </form>
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
@endpush

@push('script')
<script>
    $(function() {
        'use strict'
       
        // Activate Frontend Theme
        $('.active-btn').on('click', function(e) {
            e.preventDefault();
            const modal = $('#activeTheme');
            const route = $(this).data('route');
            const themeName = $(this).data('theme') || route.split('/').pop();
            const displayName = $(this).closest('tr').find('h5').text().trim() || themeName;

            modal.find('form').attr('action', route);
            modal.find('#activeThemeName').val(themeName);
            modal.find('#activeThemeColor').val($(this).data('color') || '#9c0ac');
            modal.find('#activeThemeDisplayName').text(displayName);

            modal.modal('show');
        });

        // Activate Backend Theme
        $('.active-backend-btn').on('click', function(e) {
            e.preventDefault();
            const modal = $('#activeBackendTheme');
            const route = $(this).data('route');
            const themeName = $(this).data('theme') || route.split('/').pop();
            const displayName = $(this).closest('tr').find('h5').text().trim() || themeName;

            modal.find('form').attr('action', route);
            modal.find('#activeBackendThemeName').val(themeName);
            modal.find('#activeBackendThemeDisplayName').text(displayName);

            modal.modal('show');
        });

        // Delete Theme
        $('.delete-theme-btn').on('click', function() {
            const themeName = $(this).data('theme');
            const displayName = $(this).data('display-name');
            const deleteUrl = '{{ route("admin.manage.theme.delete", ":theme") }}'.replace(':theme', themeName);

            $('#deleteThemeForm').attr('action', deleteUrl);
            $('#deleteThemeName').text(displayName);
            $('#deleteThemeModal').modal('show');
        });

        // Reinitialize feather icons after tab switch
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    })
</script>
@endpush
