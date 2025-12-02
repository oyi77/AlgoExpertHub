@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>AI Model Profile: {{ $aiModelProfile->name }}</h4>
                    <div>
                        <a href="{{ route('admin.ai-model-profiles.edit', $aiModelProfile->id) }}" class="btn btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.ai-model-profiles.index') }}" class="btn btn-secondary">
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
                                    <td>{{ $aiModelProfile->id }}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $aiModelProfile->name }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $aiModelProfile->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Owner</th>
                                    <td>{{ $aiModelProfile->owner->username ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th>Provider</th>
                                    <td><span class="badge badge-info">{{ $aiModelProfile->provider }}</span></td>
                                </tr>
                                <tr>
                                    <th>Model</th>
                                    <td>{{ $aiModelProfile->model_name }}</td>
                                </tr>
                                <tr>
                                    <th>Mode</th>
                                    <td><span class="badge badge-secondary">{{ $aiModelProfile->mode }}</span></td>
                                </tr>
                                <tr>
                                    <th>Visibility</th>
                                    <td>
                                        <span class="badge badge-{{ $aiModelProfile->visibility === 'PUBLIC_MARKETPLACE' ? 'success' : 'secondary' }}">
                                            {{ $aiModelProfile->visibility }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $aiModelProfile->enabled ? 'success' : 'danger' }}">
                                            {{ $aiModelProfile->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Clonable</th>
                                    <td>{{ $aiModelProfile->clonable ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Linked Presets</th>
                                    <td><span class="badge badge-info">{{ $aiModelProfile->trading_presets_count ?? 0 }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Configuration</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>API Key Ref</th>
                                    <td>{{ $aiModelProfile->api_key_ref ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Max Calls/Min</th>
                                    <td>{{ $aiModelProfile->max_calls_per_minute ?? 'Unlimited' }}</td>
                                </tr>
                                <tr>
                                    <th>Max Calls/Day</th>
                                    <td>{{ $aiModelProfile->max_calls_per_day ?? 'Unlimited' }}</td>
                                </tr>
                            </table>
                            
                            <h5 class="mt-3">Settings</h5>
                            <pre class="bg-light p-3" style="max-height: 200px; overflow-y: auto;">{{ json_encode($aiModelProfile->settings ?? [], JSON_PRETTY_PRINT) }}</pre>
                            
                            <h5 class="mt-3">Prompt Template</h5>
                            <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">{{ $aiModelProfile->prompt_template ?? 'N/A' }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

