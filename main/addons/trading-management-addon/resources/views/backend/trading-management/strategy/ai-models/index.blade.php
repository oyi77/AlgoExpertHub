@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-robot"></i> AI Model Profiles</h4>
                    <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add AI Profile
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($profiles->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Mode</th>
                                <th>Visibility</th>
                                <th>Enabled</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profiles as $profile)
                            <tr>
                                <td><strong>{{ $profile->name }}</strong></td>
                                <td>{{ Str::limit($profile->description, 50) }}</td>
                                <td>
                                    @if($profile->mode === 'CONFIRM')
                                    <span class="badge badge-success">Confirm</span>
                                    @elseif($profile->mode === 'SCAN')
                                    <span class="badge badge-info">Scan</span>
                                    @else
                                    <span class="badge badge-warning">Position Mgmt</span>
                                    @endif
                                </td>
                                <td>
                                    @if($profile->visibility === 'PUBLIC_MARKETPLACE')
                                    <span class="badge badge-info">Public</span>
                                    @else
                                    <span class="badge badge-secondary">Private</span>
                                    @endif
                                </td>
                                <td>
                                    @if($profile->enabled)
                                    <span class="badge badge-success">Enabled</span>
                                    @else
                                    <span class="badge badge-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td>{{ $profile->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="btn-group">
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
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $profiles->links() }}
                @else
                <div class="alert alert-info">
                    No AI model profiles found. <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}">Create one now</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
