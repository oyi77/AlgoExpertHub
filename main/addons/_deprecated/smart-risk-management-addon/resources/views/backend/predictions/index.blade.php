@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'SRM Predictions' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">SRM Predictions</h4>
                    </div>
                <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" action="{{ route('admin.srm.predictions.index') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Type</label>
                                    <select name="type" class="form-control">
                                        <option value="">All Types</option>
                                        <option value="slippage" {{ request('type') == 'slippage' ? 'selected' : '' }}>Slippage</option>
                                        <option value="performance_score" {{ request('type') == 'performance_score' ? 'selected' : '' }}>Performance Score</option>
                                        <option value="lot_optimization" {{ request('type') == 'lot_optimization' ? 'selected' : '' }}>Lot Optimization</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-3">
                                    <label>Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-3">
                                    <label>Accuracy</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" name="accuracy_min" class="form-control" placeholder="Min" value="{{ request('accuracy_min') }}">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="accuracy_max" class="form-control" placeholder="Max" value="{{ request('accuracy_max') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('admin.srm.predictions.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <!-- Stats -->
                        <div class="alert alert-info">
                            <strong>Average Accuracy:</strong> {{ number_format($avg_accuracy, 2) }}%
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Symbol</th>
                                        <th>Predicted Value</th>
                                        <th>Actual Value</th>
                                        <th>Accuracy</th>
                                        <th>Confidence</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($predictions as $prediction)
                                        <tr>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $prediction->prediction_type)) }}</span>
                                            </td>
                                            <td>{{ $prediction->symbol }}</td>
                                            <td>{{ number_format($prediction->predicted_value, 4) }}</td>
                                            <td>
                                                @if($prediction->actual_value !== null)
                                                    {{ number_format($prediction->actual_value, 4) }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($prediction->accuracy !== null)
                                                    <span class="badge badge-{{ $prediction->accuracy >= 80 ? 'success' : ($prediction->accuracy >= 60 ? 'warning' : 'danger') }}">
                                                        {{ number_format($prediction->accuracy, 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($prediction->confidence_score, 2) }}%</td>
                                            <td>{{ $prediction->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                <a href="{{ route('admin.srm.predictions.show', $prediction->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No predictions found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $predictions->links() }}
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

