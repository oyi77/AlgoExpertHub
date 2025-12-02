@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>My Filter Strategies</h4>
                    <a href="{{ route('user.filter-strategies.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Strategy
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
                                <th>Description</th>
                                <th>Visibility</th>
                                <th>Linked Presets</th>
                                <th>Status</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($strategies as $strategy)
                                <tr>
                                    <td>{{ $strategy->name }}</td>
                                    <td>{{ Str::limit($strategy->description, 50) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                            {{ $strategy->visibility }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $strategy->trading_presets_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $strategy->enabled ? 'success' : 'danger' }}">
                                            {{ $strategy->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>{{ $strategy->updated_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('user.filter-strategies.edit', $strategy->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('user.filter-strategies.destroy', $strategy->id) }}" method="POST" class="d-inline">
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
                                    <td colspan="7" class="text-center">No filter strategies found</td>
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

