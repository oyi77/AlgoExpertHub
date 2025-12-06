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
                        <a href="{{ route('admin.execution-connections.index') }}" class="btn btn-secondary">Back</a>
                        <a href="{{ route('admin.execution-connections.edit', $connection->id) }}" class="btn btn-primary">Edit</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $connection->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td>{{ strtoupper($connection->type) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Exchange/Broker</th>
                                        <td>{{ $connection->exchange_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge badge-{{ $connection->status === 'active' ? 'success' : ($connection->status === 'error' ? 'danger' : 'warning') }}">
                                                {{ $connection->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Active</th>
                                        <td>
                                            <span class="badge badge-{{ $connection->is_active ? 'success' : 'secondary' }}">
                                                {{ $connection->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($connection->last_tested_at)
                                    <tr>
                                        <th>Last Tested</th>
                                        <td>{{ $connection->last_tested_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                    @endif
                                    @if($connection->last_used_at)
                                    <tr>
                                        <th>Last Used</th>
                                        <td>{{ $connection->last_used_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                    @endif
                                    @if($connection->last_error)
                                    <tr>
                                        <th>Last Error</th>
                                        <td class="text-danger">{{ $connection->last_error }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

