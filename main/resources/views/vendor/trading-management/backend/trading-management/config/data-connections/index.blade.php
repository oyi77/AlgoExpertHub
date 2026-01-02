@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>🔌 Data Connections</h4>
                <div class="card-header-action">
                    <a href="{{ route('admin.trading-management.config.data-connections.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Connection
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($connections->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No data connections yet. Create your first connection to start fetching market data from mtapi.io or crypto exchanges.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Provider</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Last Connected</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connections as $connection)
                                <tr>
                                    <td><strong>{{ $connection->name }}</strong></td>
                                    <td>
                                        @if($connection->type === 'mtapi')
                                            <span class="badge badge-primary">MT4/MT5</span>
                                        @elseif($connection->type === 'ccxt_crypto')
                                            <span class="badge badge-info">Crypto</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $connection->type }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $connection->provider }}</td>
                                    <td>
                                        @if($connection->isAdminOwned())
                                            <span class="badge badge-success">
                                                <i class="fas fa-user-shield"></i> Admin ({{ $connection->admin->username ?? 'N/A' }})
                                            </span>
                                        @else
                                            <span class="badge badge-info">
                                                <i class="fas fa-user"></i> User ({{ $connection->user->username ?? 'N/A' }})
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $health = $connection->getHealthStatus(); @endphp
                                        @if($health['status'] === 'healthy')
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                        @elseif($health['status'] === 'error')
                                            <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Error</span>
                                        @elseif($health['status'] === 'stale')
                                            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Stale</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> Inactive</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $health['last_checked'] }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $connection->last_connected_at ? $connection->last_connected_at->diffForHumans() : 'Never' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" onclick="testConnection({{ $connection->id }}); return false;">
                                                    <i class="fas fa-flask"></i> Test Connection
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.trading-management.config.data-connections.preview', $connection) }}">
                                                    <i class="fas fa-eye"></i> Preview Data
                                                </a>
                                                @if(!$connection->is_active)
                                                    <form action="{{ route('admin.trading-management.config.data-connections.activate', $connection) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-play"></i> Activate
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.trading-management.config.data-connections.deactivate', $connection) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-pause"></i> Deactivate
                                                        </button>
                                                    </form>
                                                @endif
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="{{ route('admin.trading-management.config.data-connections.market-data', $connection) }}">
                                                    <i class="fas fa-chart-line"></i> View Market Data
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.trading-management.config.data-connections.logs', $connection) }}">
                                                    <i class="fas fa-list"></i> View Logs
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="{{ route('admin.trading-management.config.data-connections.edit', $connection) }}">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.trading-management.config.data-connections.destroy', $connection) }}" method="POST" style="display:inline;" 
                                                      onsubmit="return confirm('Are you sure? This will also delete all market data for this connection.');">
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

                    <div class="mt-3">
                        {{ $connections->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Test Connection Modal -->
        <div class="modal fade" id="testResultModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Connection Test Result</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" id="testResultContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-3">Testing connection...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnection(connectionId) {
    $('#testResultModal').modal('show');
    $('#testResultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Testing connection...</p></div>');

    $.ajax({
        url: '{{ route("admin.trading-management.config.data-connections.test") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            connection_id: connectionId
        },
        success: function(response) {
            if (response.type === 'success') {
                $('#testResultContent').html(
                    '<div class="alert alert-success">' +
                    '<i class="fas fa-check-circle fa-2x"></i><br>' +
                    '<strong>Connection Successful!</strong><br>' +
                    '<p class="mt-2">' + response.message + '</p>' +
                    (response.data && response.data.latency ? '<small>Latency: ' + response.data.latency + ' ms</small>' : '') +
                    '</div>'
                );
            } else {
                $('#testResultContent').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-times-circle fa-2x"></i><br>' +
                    '<strong>Connection Failed!</strong><br>' +
                    '<p class="mt-2">' + response.message + '</p>' +
                    '</div>'
                );
            }
            
            // Reload page after 2 seconds to update status
            setTimeout(function() {
                location.reload();
            }, 2000);
        },
        error: function(xhr) {
            $('#testResultContent').html(
                '<div class="alert alert-danger">' +
                '<i class="fas fa-times-circle fa-2x"></i><br>' +
                '<strong>Test Failed!</strong><br>' +
                '<p class="mt-2">An error occurred while testing the connection.</p>' +
                '</div>'
            );
        }
    });
}
</script>
@endsection

