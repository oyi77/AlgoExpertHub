@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>My AI Model Profiles</h4>
                    <a href="{{ route('user.ai-model-profiles.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Profile
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Provider</th>
                                <th>Model</th>
                                <th>Mode</th>
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
                                    <td>{{ $profile->name }}</td>
                                    <td><span class="badge badge-info">{{ $profile->provider }}</span></td>
                                    <td>{{ $profile->model_name }}</td>
                                    <td><span class="badge badge-secondary">{{ $profile->mode }}</span></td>
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
                                        <a href="{{ route('user.ai-model-profiles.edit', $profile->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('user.ai-model-profiles.destroy', $profile->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No AI model profiles found</td>
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

