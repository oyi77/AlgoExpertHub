@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Filter Strategy Marketplace</h4>
                    <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> My Strategies
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($strategies as $strategy)
                                <tr>
                                    <td>{{ $strategy->name }}</td>
                                    <td>{{ Str::limit($strategy->description, 50) }}</td>
                                    <td>{{ $strategy->owner->username ?? 'System' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $strategy->enabled ? 'success' : 'danger' }}">
                                            {{ $strategy->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('user.filter-strategies.show', $strategy->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        @if($strategy->canBeClonedBy(auth()->id()))
                                            <form action="{{ route('user.filter-strategies.clone', $strategy->id) }}" method="POST" class="d-inline">
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
                                    <td colspan="5" class="text-center">No public filter strategies found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $strategies->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

