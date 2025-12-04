@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>🧠 AI Model Profiles</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Profile
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($profiles->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No AI model profiles yet. Create profiles to enable AI-powered signal confirmation.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Owner</th>
                            <th>Min Confidence</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($profiles as $profile)
                        <tr>
                            <td>
                                <strong>{{ $profile->name }}</strong><br>
                                <small class="text-muted">{{ $profile->description }}</small>
                            </td>
                            <td>
                                @if($profile->owner)
                                    {{ $profile->owner->username }}
                                @else
                                    <span class="badge badge-success">Admin</span>
                                @endif
                            </td>
                            <td>{{ $profile->min_confidence_required }}%</td>
                            <td>
                                @if($profile->enabled)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.trading-management.strategy.ai-models.edit', $profile) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.trading-management.strategy.ai-models.destroy', $profile) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $profiles->links() }}
        @endif
    </div>
</div>
@endsection

