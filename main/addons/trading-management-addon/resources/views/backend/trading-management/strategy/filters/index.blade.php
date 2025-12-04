@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-filter"></i> Filter Strategies</h4>
                    <a href="{{ route('admin.trading-management.strategy.filters.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Filter Strategy
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($strategies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Visibility</th>
                                <th>Enabled</th>
                                <th>Clonable</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($strategies as $strategy)
                            <tr>
                                <td><strong>{{ $strategy->name }}</strong></td>
                                <td>{{ Str::limit($strategy->description, 50) }}</td>
                                <td>
                                    @if($strategy->visibility === 'PUBLIC_MARKETPLACE')
                                    <span class="badge badge-info">Public</span>
                                    @else
                                    <span class="badge badge-secondary">Private</span>
                                    @endif
                                </td>
                                <td>
                                    @if($strategy->enabled)
                                    <span class="badge badge-success">Enabled</span>
                                    @else
                                    <span class="badge badge-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($strategy->clonable)
                                    <i class="fas fa-check text-success"></i>
                                    @else
                                    <i class="fas fa-times text-muted"></i>
                                    @endif
                                </td>
                                <td>{{ $strategy->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.trading-management.strategy.filters.edit', $strategy) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.trading-management.strategy.filters.destroy', $strategy) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
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
                {{ $strategies->links() }}
                @else
                <div class="alert alert-info">
                    No filter strategies found. <a href="{{ route('admin.trading-management.strategy.filters.create') }}">Create one now</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
