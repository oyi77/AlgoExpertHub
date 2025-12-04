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
                        <h3>{{ $filterStrategies->total() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">AI Model Profiles</h6>
                        <h3>{{ $aiProfiles->total() }}</h3>
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
                            <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Strategies</h5>
                            <a href="{{ route('admin.trading-management.strategy.filters.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Filter Strategy
                            </a>
                        </div>

                        @if($filterStrategies->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Visibility</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($filterStrategies as $strategy)
                                    <tr>
                                        <td><strong>{{ $strategy->name }}</strong></td>
                                        <td>{{ Str::limit($strategy->description, 50) }}</td>
                                        <td>
                                            <span class="badge {{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? 'badge-info' : 'badge-secondary' }}">
                                                {{ $strategy->visibility }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($strategy->enabled)
                                            <span class="badge badge-success">Enabled</span>
                                            @else
                                            <span class="badge badge-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.strategy.filters.edit', $strategy) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $filterStrategies->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No filter strategies found. <a href="{{ route('admin.trading-management.strategy.filters.create') }}">Create one now</a>.
                        </div>
                        @endif
                    </div>

                    <!-- AI Model Profiles Tab -->
                    <div class="tab-pane fade" id="tab-ai-models">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-robot"></i> AI Model Profiles</h5>
                            <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create AI Profile
                            </a>
                        </div>

                        @if($aiProfiles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Mode</th>
                                        <th>Visibility</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aiProfiles as $profile)
                                    <tr>
                                        <td><strong>{{ $profile->name }}</strong></td>
                                        <td>{{ Str::limit($profile->description, 40) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $profile->mode === 'CONFIRM' ? 'success' : 'info' }}">
                                                {{ $profile->mode }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $profile->visibility === 'PUBLIC_MARKETPLACE' ? 'badge-info' : 'badge-secondary' }}">
                                                {{ $profile->visibility }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($profile->enabled)
                                            <span class="badge badge-success">Enabled</span>
                                            @else
                                            <span class="badge badge-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.trading-management.strategy.ai-models.edit', $profile) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $aiProfiles->links() }}
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No AI model profiles found. <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}">Create one now</a>.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
