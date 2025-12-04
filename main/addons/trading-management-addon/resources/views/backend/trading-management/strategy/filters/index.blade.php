@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>🔍 Filter Strategies</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.filters.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Filter
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($strategies->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No filter strategies yet. Create technical indicator filters (EMA, Stochastic, PSAR).
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Owner</th>
                            <th>Visibility</th>
                            <th>Clonable</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($strategies as $strategy)
                        <tr>
                            <td>
                                <strong>{{ $strategy->name }}</strong><br>
                                <small class="text-muted">{{ $strategy->description }}</small>
                            </td>
                            <td>
                                @if($strategy->owner)
                                    {{ $strategy->owner->username }}
                                @else
                                    <span class="badge badge-success">Admin</span>
                                @endif
                            </td>
                            <td>
                                @if($strategy->isPublic())
                                    <span class="badge badge-info">Public</span>
                                @else
                                    <span class="badge badge-secondary">Private</span>
                                @endif
                            </td>
                            <td>
                                @if($strategy->isClonable())
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                                @endif
                            </td>
                            <td>
                                @if($strategy->enabled)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
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
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $strategies->links() }}
        @endif
    </div>
</div>
@endsection

