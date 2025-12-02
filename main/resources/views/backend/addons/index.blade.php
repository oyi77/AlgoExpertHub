@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0">{{ __('Upload Addon Package') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.addons.upload') }}" method="POST" enctype="multipart/form-data" class="row gy-3 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Addon Package (ZIP)') }}</label>
                            <input type="file" name="package" class="form-control" required accept=".zip">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button type="submit" class="btn btn-primary w-100">{{ __('Upload') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0">{{ __('Installed Addons') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Addon') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Version') }}</th>
                                    <th>{{ __('Namespace') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Modules') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($addons as $addon)
                                    <tr>
                                        <td>
                                            <strong>{{ $addon['title'] }}</strong>
                                            <div class="small text-muted">{{ $addon['slug'] }}</div>
                                        </td>
                                        <td>{{ $addon['description'] ?? __('No description provided') }}</td>
                                        <td>{{ $addon['version'] ?? __('N/A') }}</td>
                                        <td><code>{{ $addon['namespace'] ?? __('N/A') }}</code></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge {{ $addon['status'] === 'active' ? 'badge-success' : 'badge-secondary' }} mr-2">
                                                    {{ ucfirst($addon['status']) }}
                                                </span>
                                                <form action="{{ route('admin.addons.status', $addon['slug']) }}" method="POST" class="mb-0">
                                                    @csrf
                                                    <input type="hidden" name="action" value="{{ $addon['status'] === 'active' ? 'disable' : 'enable' }}">
                                                    <button type="submit" class="btn btn-sm {{ $addon['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                        {{ $addon['status'] === 'active' ? __('Disable') : __('Enable') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            @if (count($addon['modules']))
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-info mr-2">{{ count($addon['modules']) }} {{ __('Modules') }}</span>
                                                    <a href="{{ route('admin.addons.modules', $addon['slug']) }}" class="btn btn-sm btn-outline-primary">
                                                        {{ __('Manage Modules') }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('No modules declared') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            {{ __('No addons detected. Upload a package to get started.') }}
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
@endsection

