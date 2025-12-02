@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>AI Model Profile Marketplace</h4>
                    <a href="{{ route('user.ai-model-profiles.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> My Profiles
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Provider</th>
                                <th>Model</th>
                                <th>Mode</th>
                                <th>Owner</th>
                                <th>Status</th>
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
                                    <td>{{ $profile->owner->username ?? 'System' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $profile->enabled ? 'success' : 'danger' }}">
                                            {{ $profile->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('user.ai-model-profiles.show', $profile->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        @if($profile->canBeClonedBy(auth()->id()))
                                            <form action="{{ route('user.ai-model-profiles.clone', $profile->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fa fa-copy"></i> Clone
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No public AI model profiles found</td>
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

