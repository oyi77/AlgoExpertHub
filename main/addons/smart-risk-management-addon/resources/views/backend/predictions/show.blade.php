@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'Prediction Details' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Prediction Details</h4>
                    </div>
                <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Type</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $prediction->prediction_type)) }}</td>
                                </tr>
                                <tr>
                                    <th>Symbol</th>
                                    <td>{{ $prediction->symbol }}</td>
                                </tr>
                                <tr>
                                    <th>Trading Session</th>
                                    <td>{{ $prediction->trading_session ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Predicted Value</th>
                                    <td><strong>{{ number_format($prediction->predicted_value, 4) }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Actual Value</th>
                                    <td>
                                        @if($prediction->actual_value !== null)
                                            <strong>{{ number_format($prediction->actual_value, 4) }}</strong>
                                        @else
                                            <span class="text-muted">Not available yet</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Accuracy</th>
                                    <td>
                                        @if($prediction->accuracy !== null)
                                            <span class="badge badge-{{ $prediction->accuracy >= 80 ? 'success' : ($prediction->accuracy >= 60 ? 'warning' : 'danger') }}">
                                                {{ number_format($prediction->accuracy, 2) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Confidence Score</th>
                                    <td>{{ number_format($prediction->confidence_score, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Model Version</th>
                                    <td>{{ $prediction->model_version ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Model Type</th>
                                    <td>{{ $prediction->model_type ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $prediction->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        @if($prediction->executionLog)
                            <div class="mt-4">
                                <h5>Related Execution Log</h5>
                                <a href="{{ route('admin.execution-executions.show', $prediction->execution_log_id) }}" class="btn btn-sm btn-primary">
                                    View Execution Log #{{ $prediction->execution_log_id }}
                                </a>
                            </div>
                        @endif
                </div>
            </div>
        </div>
    </div>
@endsection

