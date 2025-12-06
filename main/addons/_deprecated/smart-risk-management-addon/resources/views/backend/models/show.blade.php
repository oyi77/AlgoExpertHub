@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Model Details' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ ucfirst(str_replace('_', ' ', $model->model_type)) }} - {{ $model->version }}</h4>
                        <div class="card-tools">
                            @if($model->status == 'testing')
                                <form action="{{ route('admin.srm.models.deploy', $model->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Deploy this model to production?')">
                                        <i class="fas fa-rocket"></i> Deploy
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.srm.models.retrain', $model->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-sync"></i> Retrain
                                </button>
                            </form>
                        </div>
                    </div>
                <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Model Type</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $model->model_type)) }}</td>
                                </tr>
                                <tr>
                                    <th>Version</th>
                                    <td>{{ $model->version }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $model->status == 'active' ? 'success' : ($model->status == 'testing' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($model->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Accuracy</th>
                                    <td>{{ $model->accuracy ? number_format($model->accuracy, 2) . '%' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>MSE</th>
                                    <td>{{ $model->mse ? number_format($model->mse, 6) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>R² Score</th>
                                    <td>{{ $model->r2_score ? number_format($model->r2_score, 4) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Training Data Count</th>
                                    <td>{{ number_format($model->training_data_count) }}</td>
                                </tr>
                                <tr>
                                    <th>Training Period</th>
                                    <td>
                                        @if($model->training_date_start && $model->training_date_end)
                                            {{ $model->training_date_start->format('Y-m-d') }} to {{ $model->training_date_end->format('Y-m-d') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Deployed At</th>
                                    <td>{{ $model->deployed_at ? $model->deployed_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Parameters</th>
                                    <td>
                                        <pre class="bg-light p-2">{{ json_encode($model->parameters, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                </tr>
                                @if($model->notes)
                                <tr>
                                    <th>Notes</th>
                                    <td>{{ $model->notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

