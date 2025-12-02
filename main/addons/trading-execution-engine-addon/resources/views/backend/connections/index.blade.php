@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $title }}</h4>
                        <a href="{{ route('admin.execution-connections.create') }}" class="btn btn-primary">Create Connection</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
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
                                                <a href="{{ route('admin.execution-connections.edit', $connection->id) }}" class="btn btn-sm btn-info">Edit</a>
                                                <form action="{{ route('admin.execution-connections.destroy', $connection->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
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
                        {{ $connections->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

