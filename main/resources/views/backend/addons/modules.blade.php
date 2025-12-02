@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0">{{ __('Manage Modules: :addon', ['addon' => $addon['title']]) }}</h5>
                    <a href="{{ route('admin.addons.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> {{ __('Back to Addons') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong>{{ __('Addon:') }}</strong> {{ $addon['title'] }}
                            </div>
                            <div class="mb-2">
                                <strong>{{ __('Version:') }}</strong> {{ $addon['version'] ?? __('N/A') }}
                            </div>
                            <div class="mb-2">
                                <strong>{{ __('Namespace:') }}</strong> <code>{{ $addon['namespace'] ?? __('N/A') }}</code>
                            </div>
                            <div class="mb-2">
                                <strong>{{ __('Status:') }}</strong>
                                <span class="badge {{ $addon['status'] === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                    {{ ucfirst($addon['status']) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if (!empty($addon['description']))
                                <div>
                                    <strong>{{ __('Description:') }}</strong>
                                    <p class="text-muted mb-0">{{ $addon['description'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header">
                    <h5 class="mb-0">{{ __('Modules') }}</h5>
                </div>
                <div class="card-body">
                    @if (count($addon['modules']))
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Module Name') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Targets') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($addon['modules'] as $module)
                                        <tr>
                                            <td>
                                                <strong>{{ $module['name'] }}</strong>
                                                @if (!empty($module['key']))
                                                    <div class="small text-muted">
                                                        <code>{{ $module['key'] }}</code>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($module['description']))
                                                    <p class="mb-0 text-muted">{{ $module['description'] }}</p>
                                                @else
                                                    <span class="text-muted">{{ __('No description provided') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($module['targets']) && is_array($module['targets']))
                                                    <div class="d-flex flex-wrap">
                                                        @foreach ($module['targets'] as $target)
                                                            <span class="badge badge-secondary mr-1 mb-1">{{ $target }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $module['enabled'] ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ $module['enabled'] ? __('Enabled') : __('Disabled') }}
                                                </span>
                                            </td>
                                            <td>
                                                <form action="{{ route('admin.addons.modules.update', [$addon['slug'], $module['key']]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="{{ $module['enabled'] ? 'disable' : 'enable' }}">
                                                    <button type="submit" class="btn btn-sm {{ $module['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                        <i class="fas {{ $module['enabled'] ? 'fa-times' : 'fa-check' }}"></i>
                                                        {{ $module['enabled'] ? __('Disable') : __('Enable') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">{{ __('No modules declared for this addon.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

