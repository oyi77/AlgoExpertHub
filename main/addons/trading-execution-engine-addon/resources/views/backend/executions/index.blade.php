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
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Signal</th>
                                        <th>Connection</th>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                        <tr>
                                            <td>#{{ $log->signal_id }}</td>
                                            <td>{{ $log->connection->name }}</td>
                                            <td>{{ $log->symbol }}</td>
                                            <td>{{ strtoupper($log->direction) }}</td>
                                            <td>{{ $log->quantity }}</td>
                                            <td>
                                                <span class="badge badge-{{ $log->status === 'executed' ? 'success' : 'warning' }}">
                                                    {{ $log->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.execution-executions.show', $log->id) }}" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No executions found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

