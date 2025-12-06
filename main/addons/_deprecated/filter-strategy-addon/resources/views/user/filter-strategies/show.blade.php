@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Filter Strategy: {{ $filterStrategy->name }}</h4>
                    <div>
                        @if($filterStrategy->canEditBy(auth()->id()))
                            <a href="{{ route('user.filter-strategies.edit', $filterStrategy->id) }}" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                        @endif
                        @if($filterStrategy->canBeClonedBy(auth()->id()))
                            <form action="{{ route('user.filter-strategies.clone', $filterStrategy->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <i class="fa fa-copy"></i> Clone
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-secondary">
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
                                <tr>
                                    <th>Linked Presets</th>
                                    <td><span class="badge badge-info">{{ $filterStrategy->trading_presets_count ?? 0 }}</span></td>
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

