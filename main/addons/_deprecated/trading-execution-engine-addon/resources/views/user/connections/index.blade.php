@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4>{{ __($title) }}</h4>
                <a href="{{ route('user.execution-connections.create') }}" class="btn btn-primary">{{ __('Create Connection') }}</a>
            </div>
        </div>
        <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Exchange</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($connections as $connection)
                                        <tr>
                                            <td>{{ $connection->name }}</td>
                                            <td>{{ strtoupper($connection->type) }}</td>
                                            <td>{{ $connection->exchange_name }}</td>
                                            <td>
                                                <span class="badge badge-{{ $connection->status === 'active' ? 'success' : 'warning' }}">
                                                    {{ $connection->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('user.execution-connections.edit', $connection->id) }}" class="btn btn-sm btn-info">Edit</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No connections found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($connections->hasPages())
                            <div class="mt-3">
                                {{ $connections->links() }}
                            </div>
                        @endif
        </div>
    </div>
@endsection

