@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-bullseye"></i> Strategy Management</h3>
                <p class="text-muted mb-0">Configure filters, AI models, and strategy parameters</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Filter Strategies</h6>
                        <h3>{{ \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">AI Model Profiles</h6>
                        <h3>{{ \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::count() }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#tab-filters" data-toggle="tab">
                            <i class="fas fa-filter"></i> Filter Strategies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tab-ai-models" data-toggle="tab">
                            <i class="fas fa-robot"></i> AI Model Profiles
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Filter Strategies Tab -->
                    <div class="tab-pane fade show active" id="tab-filters">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Filter Strategies</h5>
                            <a href="{{ route('admin.trading-management.strategy.filters.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage Filter Strategies
                            </a>
                        </div>
                        <p class="text-muted">Technical indicator filters (EMA, RSI, PSAR) to validate trading signals before execution.</p>
                    </div>

                    <!-- AI Model Profiles Tab -->
                    <div class="tab-pane fade" id="tab-ai-models">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">AI Model Profiles</h5>
                            <a href="{{ route('admin.trading-management.strategy.ai-models.index') }}" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Manage AI Models
                            </a>
                        </div>
                        <p class="text-muted">AI-powered market confirmation using OpenAI, Gemini, or other providers to analyze signal quality.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
