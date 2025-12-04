@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>⚡ Execution Connections</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.operations.connections.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Connection
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($connections->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No execution connections yet. Create connections to start auto-trading.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Exchange</th>
                            <th>Preset</th>
                            <th>Data Connection</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connections as $connection)
                        <tr>
                            <td><strong>{{ $connection->name }}</strong></td>
                            <td>
                                @if($connection->type === 'crypto')
                                    <span class="badge badge-info">Crypto</span>
                                @else
                                    <span class="badge badge-primary">FX</span>
                                @endif
                            </td>
                            <td>{{ $connection->exchange_name }}</td>
                            <td>
                                @if($connection->preset)
                                    <span class="badge badge-success">{{ $connection->preset->name }}</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($connection->dataConnection)
                                    <span class="badge badge-info">{{ $connection->dataConnection->name }}</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($connection->isActive())
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.trading-management.operations.connections.edit', $connection) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.trading-management.operations.connections.destroy', $connection) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
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
            {{ $connections->links() }}
        @endif
    </div>
</div>
@endsection

