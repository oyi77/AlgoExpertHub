@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>AI Model Profiles</h4>
                    <a href="{{ route('admin.ai-model-profiles.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Profile
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
                                <th>Provider</th>
                                <th>Model</th>
                                <th>Mode</th>
                                <th>Owner</th>
                                <th>Visibility</th>
                                <th>Linked Presets</th>
                                <th>Status</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profiles as $profile)
                                <tr>
                                    <td>{{ $profile->id }}</td>
                                    <td>{{ $profile->name }}</td>
                                    <td><span class="badge badge-info">{{ $profile->provider }}</span></td>
                                    <td>{{ $profile->model_name }}</td>
                                    <td><span class="badge badge-secondary">{{ $profile->mode }}</span></td>
                                    <td>{{ $profile->owner->username ?? 'System' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $profile->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                            {{ $profile->visibility }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $profile->trading_presets_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $profile->enabled ? 'success' : 'danger' }}">
                                            {{ $profile->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>{{ $profile->updated_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.ai-model-profiles.show', $profile->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.ai-model-profiles.edit', $profile->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.ai-model-profiles.destroy', $profile->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No AI model profiles found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $profiles->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

