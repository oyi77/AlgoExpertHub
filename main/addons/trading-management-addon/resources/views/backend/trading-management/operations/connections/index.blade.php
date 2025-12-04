@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-plug"></i> Execution Connections</h4>
                    <a href="{{ route('admin.trading-management.operations.connections.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Connection
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($connections->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Exchange</th>
                                <th>Status</th>
                                <th>Active</th>
                                <th>Preset</th>
                                <th>Data Connection</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($connections as $connection)
                            <tr>
                                <td>
                                    <strong>{{ $connection->name }}</strong>
                                    @if($connection->is_admin_owned)
                                    <span class="badge badge-info badge-sm">Admin</span>
                                    @endif
                                </td>
                                <td>
                                    @if($connection->type === 'CRYPTO_EXCHANGE')
                                    <span class="badge badge-primary">Crypto</span>
                                    @else
                                    <span class="badge badge-success">Forex</span>
                                    @endif
                                </td>
                                <td>{{ $connection->exchange_name }}</td>
                                <td>
                                    @if($connection->status === 'CONNECTED')
                                    <span class="badge badge-success">Connected</span>
                                    @elseif($connection->status === 'ERROR')
                                    <span class="badge badge-danger" title="{{ $connection->last_error }}">Error</span>
                                    @else
                                    <span class="badge badge-warning">{{ $connection->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($connection->is_active)
                                    <form action="{{ route('admin.trading-management.operations.connections.deactivate', $connection) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Active
                                        </button>
                                    </form>
                                    @else
                                    <form action="{{ route('admin.trading-management.operations.connections.activate', $connection) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-times"></i> Inactive
                                        </button>
                                    </form>
                                    @endif
                                </td>
                                <td>{{ $connection->preset->name ?? 'None' }}</td>
                                <td>{{ $connection->dataConnection->name ?? 'None' }}</td>
                                <td>{{ $connection->last_used_at ? $connection->last_used_at->diffForHumans() : 'Never' }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.trading-management.operations.connections.show', $connection) }}">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a class="dropdown-item" href="{{ route('admin.trading-management.operations.connections.edit', $connection) }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="dropdown-item test-connection" data-id="{{ $connection->id }}">
                                                <i class="fas fa-vial"></i> Test
                                            </button>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.trading-management.operations.connections.destroy', $connection) }}" method="POST" style="display:inline;" 
                                                onsubmit="return confirm('Delete this connection? This will also delete all associated data.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $connections->links() }}
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No execution connections found. <a href="{{ route('admin.trading-management.operations.connections.create') }}">Create one now</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    $('.test-connection').click(function() {
        var connectionId = $(this).data('id');
        var btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Testing...').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.trading-management.operations.connections.test") }}',
            method: 'POST',
            data: {
                connection_id: connectionId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert('✓ Connection test successful!');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON ? xhr.responseJSON.message : 'Connection test failed';
                alert('✗ ' + message);
                btn.html('<i class="fas fa-vial"></i> Test').prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endsection
