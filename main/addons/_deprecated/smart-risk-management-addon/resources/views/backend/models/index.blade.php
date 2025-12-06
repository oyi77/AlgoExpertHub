@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'ML Model Management' }}
@endsection

@section('element')
    <div class="row">
            <!-- Slippage Prediction Model -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Slippage Prediction</h5>
                    </div>
                    <div class="card-body">
                        @if($active_slippage)
                            <p><strong>Active Version:</strong> {{ $active_slippage->version }}</p>
                            <p><strong>Accuracy:</strong> {{ number_format($active_slippage->accuracy ?? 0, 2) }}%</p>
                            <p><strong>Last Training:</strong> {{ $active_slippage->training_date_end ? $active_slippage->training_date_end->format('Y-m-d') : 'N/A' }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-success">{{ ucfirst($active_slippage->status) }}</span>
                            </p>
                        @else
                            <p class="text-muted">No active model</p>
                        @endif
                        <hr>
                        <h6>Model History</h6>
                        <ul class="list-group">
                            @foreach($slippage_models->take(5) as $model)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $model->version }} ({{ $model->status }})</span>
                                    <span>{{ number_format($model->accuracy ?? 0, 2) }}%</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('admin.srm.models.show', $slippage_models->first()->id ?? 0) }}" class="btn btn-sm btn-info">View All</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Score Model -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Performance Score</h5>
                    </div>
                    <div class="card-body">
                        @if($active_performance)
                            <p><strong>Active Version:</strong> {{ $active_performance->version }}</p>
                            <p><strong>Accuracy:</strong> {{ number_format($active_performance->accuracy ?? 0, 2) }}%</p>
                            <p><strong>Last Training:</strong> {{ $active_performance->training_date_end ? $active_performance->training_date_end->format('Y-m-d') : 'N/A' }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-success">{{ ucfirst($active_performance->status) }}</span>
                            </p>
                        @else
                            <p class="text-muted">No active model</p>
                        @endif
                        <hr>
                        <h6>Model History</h6>
                        <ul class="list-group">
                            @foreach($performance_models->take(5) as $model)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $model->version }} ({{ $model->status }})</span>
                                    <span>{{ number_format($model->accuracy ?? 0, 2) }}%</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('admin.srm.models.show', $performance_models->first()->id ?? 0) }}" class="btn btn-sm btn-info">View All</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Optimization Model -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Risk Optimization</h5>
                    </div>
                    <div class="card-body">
                        @if($active_risk)
                            <p><strong>Active Version:</strong> {{ $active_risk->version }}</p>
                            <p><strong>Accuracy:</strong> {{ number_format($active_risk->accuracy ?? 0, 2) }}%</p>
                            <p><strong>Last Training:</strong> {{ $active_risk->training_date_end ? $active_risk->training_date_end->format('Y-m-d') : 'N/A' }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-success">{{ ucfirst($active_risk->status) }}</span>
                            </p>
                        @else
                            <p class="text-muted">No active model</p>
                        @endif
                        <hr>
                        <h6>Model History</h6>
                        <ul class="list-group">
                            @foreach($risk_models->take(5) as $model)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $model->version }} ({{ $model->status }})</span>
                                    <span>{{ number_format($model->accuracy ?? 0, 2) }}%</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('admin.srm.models.show', $risk_models->first()->id ?? 0) }}" class="btn btn-sm btn-info">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

