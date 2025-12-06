@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Filter Strategy: {{ $filterStrategy->name }}</h4>
                    <div>
                        <a href="{{ route('admin.filter-strategies.edit', $filterStrategy->id) }}" class="btn btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.filter-strategies.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Basic Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID</th>
                                    <td>{{ $filterStrategy->id }}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $filterStrategy->name }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $filterStrategy->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner</th>
                                    <td>{{ $filterStrategy->owner->username ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th>Visibility</th>
                                    <td>
                                        <span class="badge badge-{{ $filterStrategy->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                            {{ $filterStrategy->visibility }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $filterStrategy->enabled ? 'success' : 'danger' }}">
                                            {{ $filterStrategy->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Clonable</th>
                                    <td>{{ $filterStrategy->clonable ? 'Yes' : 'No' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Configuration</h5>
                            <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">{{ json_encode($filterStrategy->config, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

